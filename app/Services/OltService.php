<?php

namespace App\Services;

use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\PublicKeyLoader;
use App\Models\Olt;
use App\Models\OltSnmpSnapshot;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OltService
{
    private const SYSTEM_DESCRIPTION_OID = '1.3.6.1.2.1.1.1.0';
    private const INTERFACE_DESCRIPTION_OID = '1.3.6.1.2.1.2.2.1.2';
    private const INTERFACE_STATUS_OID = '1.3.6.1.2.1.2.2.1.8';
    private const HUAWEI_GPON_PORT_STATUS_OID = '1.3.6.1.4.1.2011.6.128.1.1.2.21.1.10';
    
    private const SNMP_OIDS = [
        'ont_serial'         => '1.3.6.1.4.1.2011.6.128.1.1.2.43.1.3',
        'line_profile'       => '1.3.6.1.4.1.2011.6.128.1.1.2.43.1.7',
        'service_profile'    => '1.3.6.1.4.1.2011.6.128.1.1.2.43.1.8',
        'ont_rx_power'       => '1.3.6.1.4.1.2011.6.128.1.1.2.51.1.4',
        'ont_status'         => '1.3.6.1.4.1.2011.6.128.1.1.2.62.1.22',
        'ont_temperature'    => '1.3.6.1.4.1.2011.6.128.1.1.2.51.1.1',
        'gpon_status'        => self::HUAWEI_GPON_PORT_STATUS_OID,
        'olt_uptime'         => '1.3.6.1.2.1.1.3',
        'interface_status'   => self::INTERFACE_STATUS_OID,
        'ont_inbound_bytes'  => '1.3.6.1.4.1.2011.6.128.1.1.4.23.1.4',
        'ont_outbound_bytes' => '1.3.6.1.4.1.2011.6.128.1.1.4.23.1.3',
        'unconfigured_ont'   => '1.3.6.1.4.1.2011.6.128.1.1.2.52.1.2',
    ];

    private array $sessions = [];
    private string $traceId;

    public function __construct()
    {
        $this->traceId = (string) Str::uuid();
    }

    public function names(): array
    {
        return Olt::where('is_active', true)->pluck('name')->all();
    }

    public function collectSnmpSnapshot(Olt $olt, string $frame = '0'): OltSnmpSnapshot
    {
        $this->debug('snmp.snapshot.started', ['olt' => $olt->name, 'frame' => $frame]);
        if (! $olt->snmp_enabled && $olt->connection_type !== 'snmp') {
            throw new \RuntimeException("OLT {$olt->name} bukan koneksi SNMP.");
        }

        $data = ['monitor' => $this->scanAllSnmpPorts($olt, $frame, 12, 16), 'oids' => []];
        $errors = [];
        $peer = $this->snmpPeer($olt);
        $timeout = (int) config('janeta.snmp_timeout');
        $retries = (int) config('janeta.snmp_retries');

        foreach (self::SNMP_OIDS as $name => $oid) {
            $error = null;
            $value = $this->snmpWalk($peer, $olt->snmp_community ?: $olt->username, $oid, $timeout, $retries, $error);
            if ($value === false) {
                $errors[$name] = $error ?: 'OID tidak merespons.';
                continue;
            }
            $data['oids'][$name] = $value;
        }

        return OltSnmpSnapshot::updateOrCreate(
            ['olt_id' => $olt->id, 'frame' => $frame],
            ['data' => $data, 'error' => $errors ? json_encode($errors, JSON_UNESCAPED_SLASHES) : null, 'collected_at' => now()],
        );
    }

    public function latestSnmpSnapshot(Olt $olt, string $frame = '0'): ?OltSnmpSnapshot
    {
        return OltSnmpSnapshot::where(['olt_id' => $olt->id, 'frame' => $frame])->first();
    }

    private function activeOlt(string $oltName): ?Olt
    {
        return Olt::where('name', strtoupper($oltName))->where('is_active', true)->first();
    }

    private function olt(string $oltName): ?Olt
    {
        return Olt::where('name', strtoupper($oltName))->first();
    }

    private function command(string $oltName, string $command): ?string
    {
        $startedAt = microtime(true);
        try {
            $olt = $this->activeOlt($oltName);
            if (!$olt) return null;

            $ssh = $this->session($olt);
            if (!$ssh) throw new \RuntimeException('SSH session unavailable.');

            $ssh->write($command . "\n");
            $output = $ssh->read('#');

            if (str_contains($output, '{ <cr>||<K> }') || str_contains($output, '<cr>')) {
                $ssh->write("\n");
                $output .= $ssh->read('#');
            }

            while (str_contains($output, '---- More')) {
                $ssh->write(" ");
                $output .= $ssh->read('#');
            }

            return $output ?: null;
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    private function session(Olt $olt): SSH2
    {
        if (! $olt->ssh_enabled && $olt->connection_type !== 'ssh') {
            throw new \RuntimeException("OLT {$olt->name} bukan koneksi SSH.");
        }
        if ($olt->transport_type !== 'tcp') {
            throw new \RuntimeException("Koneksi SSH OLT {$olt->name} harus menggunakan TCP.");
        }

        if (isset($this->sessions[$olt->name])) return $this->sessions[$olt->name];

        $ssh = new SSH2($olt->host, $olt->ssh_port ?: $olt->port, config('janeta.ssh_timeout'));
        if (!$ssh->login($olt->ssh_username ?: $olt->username, $this->sshCredential($olt))) {
            throw new \RuntimeException("Login SSH OLT {$olt->name} gagal.");
        }
        $ssh->setTimeout(3);
        $ssh->write("enable\n");
        $ssh->read('#');
        usleep(500000);
        $ssh->write("scroll 512\n");
        $ssh->read('#');
        usleep(150000);
        
        $this->sessions[$olt->name] = $ssh;
        return $ssh;
    }

    private function close(string $oltName): void
    {
        if (isset($this->sessions[$oltName])) {
            $this->sessions[$oltName]->disconnect();
            unset($this->sessions[$oltName]);
        }
    }

    public function testConnection(string $oltName): array
    {
        $olt = $this->olt($oltName);
        if (!$olt) return ['ok' => false, 'message' => 'OLT tidak ditemukan.'];
        if (!$olt->host || ($olt->connection_type === 'ssh' ? ! ($olt->ssh_username ?: $olt->username) : ! ($olt->snmp_community ?: $olt->username))) {
            return $this->saveConnectionResult($olt, false, 'Konfigurasi host/username belum lengkap.');
        }

        try {
            $result = $this->activeConnectionType($olt) === 'snmp' ? $this->testSnmp($olt) : $this->testSsh($olt);
        } catch (\Throwable $e) {
            $this->close($olt->name);
            $result = ['ok' => false, 'message' => $e->getMessage()];
        }
        return $this->saveConnectionResult($olt, $result['ok'], $result['message']);
    }

    public function testServices(Olt $olt): array
    {
        $results = [];
        foreach (['ssh', 'snmp'] as $service) {
            if (! $olt->{$service.'_enabled'}) {
                $olt->update([$service.'_status' => 'off']);
                $results[$service] = ['ok' => false, 'message' => 'Service tidak aktif.'];
                continue;
            }

            try {
                $result = $service === 'ssh' ? $this->testSsh($olt) : $this->testSnmp($olt);
            } catch (\Throwable $exception) {
                $this->close($olt->name);
                $result = ['ok' => false, 'message' => $exception->getMessage()];
            }

            $results[$service] = $result;
            $olt->update([
                $service.'_status'           => $result['ok'] ? 'up' : 'down',
                $service.'_last_checked_at'  => now(),
                $service.'_last_error'       => $result['ok'] ? null : $result['message'],
            ]);
        }

        $activeResults = collect($results)->filter(fn ($r, $s) => $olt->{$s.'_enabled'});
        $overallOk = $activeResults->isNotEmpty() && $activeResults->every(fn ($r) => $r['ok']);
        $firstFailure = $activeResults->first(fn ($r) => ! $r['ok']);
        
        $olt->update([
            'status'          => $overallOk ? 'online' : 'offline',
            'last_error'      => $overallOk ? null : ($firstFailure['message'] ?? 'Tidak ada service aktif.'),
            'last_checked_at' => now(),
        ]);

        return $results;
    }

    private function testSsh(Olt $olt): array
    {
        if ($olt->transport_type !== 'tcp') return ['ok' => false, 'message' => 'SSH harus TCP.'];
        $port = $olt->ssh_port ?: $olt->port;
        $ssh = new SSH2($olt->host, $port, min((int) config('janeta.ssh_timeout'), 5));
        $ok = $ssh->login($olt->ssh_username ?: $olt->username, $this->sshCredential($olt));
        $ssh->disconnect();
        return ['ok' => $ok, 'message' => $ok ? 'SSH login berhasil.' : "Login SSH gagal ke {$olt->host}:{$port}."];
    }

    private function testSnmp(Olt $olt): array
    {
        if (!function_exists('snmp2_get')) throw new \RuntimeException('Ekstensi PHP SNMP belum aktif.');
        $error = null;
        $value = $this->snmpGet($olt, self::SYSTEM_DESCRIPTION_OID, $error);
        return ['ok' => $value !== false, 'message' => $value !== false ? 'Koneksi SNMP berhasil.' : ($error ?: 'SNMP tidak merespons.')];
    }

    private function activeConnectionType(Olt $olt): string
    {
        return $olt->snmp_enabled ? 'snmp' : ($olt->ssh_enabled ? 'ssh' : $olt->connection_type);
    }

    private function saveConnectionResult(Olt $olt, bool $ok, string $message): array
    {
        $olt->update(['status' => $ok ? 'online' : 'offline', 'last_error' => $ok ? null : $message, 'last_checked_at' => now()]);
        return ['ok' => $ok, 'message' => $message];
    }

    private function search(string $command, ?string $preferred = null): ?array
    {
        $names = $preferred ? [$preferred] : $this->names();
        foreach ($names as $name) {
            try {
                $raw = $this->command($name, $command);
                if ($raw && !str_contains($raw, 'The required')) return [$name, $raw];
            } catch (\Throwable $e) {}
        }
        return null;
    }

    private function parse(array $match, string $raw, string $olt, ?string $id = null): array
    {
        [$all, $fsp, $ontId, $sn, $state] = $match;
        [$frame, $slot, $port] = explode('/', preg_replace('/\s+/', '', $fsp));
        $description = $this->extractDescription($raw, $id);
        
        // PERBAIKAN: Jika tidak ada tanda hubung, anggap semuanya sebagai nama/id
        $parts = explode('-', $description, 2);
        if (count($parts) > 1) {
            $customerId = trim($parts[0]);
            $customerName = trim($parts[1]);
        } else {
            $customerId = trim($description);
            $customerName = trim($description);
        }
        
        return compact('olt', 'frame', 'slot', 'port', 'ontId', 'sn', 'state', 'customerId', 'customerName');
    }

    public function byDescription(string $id): ?array
    {
        $id = trim($id);
        $found = $this->search("display ont info by-desc {$id}") ?? $this->search('display ont info by-desc "' . $id . '"');
        if (!$found || !preg_match('/(\d+\s*\/\s*\d+\s*\/\s*\d+)\s+(\d+)\s+([A-Z0-9]{16})\s+\w+\s+(\w+)/i', $found[1], $m)) return null;

        $data = $this->parse($m, $found[1], $found[0], $id);
        $data['descriptionTarget'] = $id;
        $data['customerName'] = trim($data['customerName']);
        $result = $this->details($data);
        $this->close($found[0]);
        return $result;
    }

    public function bySerial(string $sn): ?array
    {
        $found = $this->search("display ont info by-sn {$sn}");
        if (!$found || !preg_match('/F\/S\/P\s*:\s*([.\/\d]+)/i', $found[1], $fsp) || !preg_match('/ONT-ID\s*:\s*(\d+)/i', $found[1], $ontId) || !preg_match('/Run\s*state\s*:\s*([a-zA-Z0-9_-]+)/i', $found[1], $state)) return null;
        
        $serial = preg_match('/SN\s*:\s*([A-Z0-9]{16})/i', $found[1], $serialMatch) ? $serialMatch[1] : $sn;
        $description = preg_match('/Description\s*:\s*([^\r\n]+)/i', $found[1], $descriptionMatch) ? trim($descriptionMatch[1]) : '-';
        [$frame, $slot, $port] = explode('/', trim($fsp[1]));
        
        // PERBAIKAN: Jika tidak ada tanda hubung, anggap semuanya sebagai nama/id
        $parts = explode('-', $description, 2);
        if (count($parts) > 1) {
            $customerId = trim($parts[0]);
            $customerName = trim($parts[1]);
        } else {
            $customerId = trim($description);
            $customerName = trim($description);
        }
        
        $data = compact('frame', 'slot', 'port') + ['olt' => $found[0], 'ontId' => $ontId[1], 'sn' => $serial, 'state' => $state[1], 'customerId' => $customerId, 'customerName' => $customerName, 'descriptionTarget' => $customerId];
        $result = $this->details($data);
        $this->close($found[0]);
        return $result;
    }

    private function details(array $data): array
    {
        $info = $this->command($data['olt'], "display ont info {$data['frame']} {$data['slot']} {$data['port']} {$data['ontId']}") ?? '';
        $infoValue = function (string $pattern, string $default = '-') use ($info): string {
            return preg_match($pattern, $info, $m) ? trim($m[1]) : $default;
        };

        $raw = '';
        if (strtolower($data['state']) === 'online') {
            $this->command($data['olt'], 'config');
            $this->command($data['olt'], "interface gpon {$data['frame']}/{$data['slot']}");
            $raw = $this->command($data['olt'], "display ont optical-info {$data['port']} {$data['ontId']}") ?? '';
        }
        $value = function (string $pattern, string $default) use ($raw): string {
            return preg_match($pattern, $raw, $m) ? trim($m[1]) : $default;
        };

        $rawLastUpTime   = $infoValue('/Last\s+up\s+time\s*:\s*([^\r\n]+)/i');
        $rawLastDownTime = $infoValue('/Last\s+down\s+time\s*:\s*([^\r\n]+)/i');

        return array_merge($data, [
            'runState'    => $data['state'],
            'rxPower'     => $value('/Rx\s*optical\s*power(?:\(dBm\))?\s*:\s*([-\d.]+)/i', '-80.00'),
            'txPower'     => $value('/Tx\s*optical\s*power(?:\(dBm\))?\s*:\s*([-\d.]+)/i', '-80.00'),
            'oltRxPower'  => $value('/OLT\s*Rx\s*ONT\s*optical\s*power(?:\(dBm\))?\s*:\s*([-\d.]+)/i', '-80.00'),
            'temp'        => $value('/Temperature(?:\(C\))?\s*:\s*([\d.]+)/i', '-'),
            'voltage'     => $value('/Voltage(?:\(V\))?\s*:\s*([\d.]+)/i', '0'),
            
            'ontOnlineDuration'   => $this->parseOntDuration($info, 'ONT\s+online\s+duration'),
            'ontSystemUpDuration' => $this->parseOntDuration($info, 'ONT\s+system\s+up\s+duration'),
            'ontDistance'   => $infoValue('/ONT\s+distance\(m\)\s*:\s*(\d+)/i', '-'),
            'cpuOccupation' => $infoValue('/CPU\s+occupation\s*:\s*(\d+)%/i', '-'),
            'memOccupation' => $infoValue('/Memory\s+occupation\s*:\s*(\d+)%/i', '-'),
            'lastDownCause'    => $infoValue('/Last\s+down\s+cause\s*:\s*([^\r\n]+)/i'),
            'lastDownTime'     => $this->normalizeDateTime($rawLastDownTime),
            'lastDownTimeRaw'  => trim($rawLastDownTime),
            'lastUpTime'       => $this->normalizeDateTime($rawLastUpTime),
        ]);
    }

    public function getWanInfo(string $olt, string $frame, string $slot, string $port, string $ontId): array
    {
        try {
            $this->command($olt, 'config');
            $this->command($olt, "interface gpon {$frame}/{$slot}");
            $raw = $this->command($olt, "display ont wan-info {$port} {$ontId}") ?? '';
            return [
                'wanList' => $this->parseWanInfo($raw),
                'raw'     => $raw
            ];
        } finally {
            $this->close($olt);
        }
    }

    private function parseWanInfo(string $raw): array
    {
        preg_match_all('/Index\s*:\s*(\d+)([\s\S]*?)(?=Index\s*:\s*\d+|$)/i', $raw, $matches, PREG_SET_ORDER);
        $list = [];
        foreach ($matches as $m) {
            $index = $m[1];
            $block = $m[2];
            $list[] = [
                'index'       => $index,
                'name'        => $this->field($block, '/Name\s*:\s*([^\r\n]+)/i'),
                'serviceType' => $this->field($block, '/Service\s+type\s*:\s*([^\r\n]+)/i'),
                'connType'    => $this->field($block, '/Connection\s+type\s*:\s*([^\r\n]+)/i'),
                'status'      => $this->field($block, '/IPv4\s+Connection\s+status\s*:\s*([^\r\n]+)/i'),
                'accessType'  => $this->field($block, '/IPv4\s+access\s+type\s*:\s*([^\r\n]+)/i'),
                'ip'          => $this->field($block, '/IPv4\s+address\s*:\s*([.\d]+)/i'),
                'subnet'      => $this->field($block, '/Subnet\s+mask\s*:\s*([.\d]+)/i'),
                'gateway'     => $this->field($block, '/Default\s+gateway\s*:\s*([.\d]+)/i'),
                'dns1'        => $this->field($block, '/Primary\s+DNS\s*:\s*([.\d]+)/i'),
                'dns2'        => $this->field($block, '/Secondary\s+DNS\s*:\s*([.\d]+)/i'),
            ];
        }
        return $list;
    }

    private function parseOntDuration(string $raw, string $fieldPattern): string
    {
        $regex = '/' . $fieldPattern . '\s*:\s*(\d+)\s*day\(s\)[,\s]+(\d+)\s*hour\(s\)[,\s]+(\d+)\s*minute\(s\)[,\s]+(\d+)\s*second\(s\)/i';
        if (! preg_match($regex, $raw, $m)) return '-';

        $days = (int) $m[1]; $hours = (int) $m[2]; $minutes = (int) $m[3]; $seconds = (int) $m[4];
        $parts = [];
        if ($days >= 365) {
            $parts[] = intdiv($days, 365) . " tahun";
            if ($days % 365 > 0) $parts[] = ($days % 365) . " hari";
        } elseif ($days > 0) {
            $parts[] = "{$days} hari";
        }
        if ($hours > 0)   $parts[] = "{$hours} jam";
        if ($minutes > 0) $parts[] = "{$minutes} menit";
        if ($seconds > 0 || $parts === []) $parts[] = "{$seconds} detik";

        return implode(' ', $parts);
    }

    public function describeDownCause(?string $cause): string
    {
        if ($cause === null || trim($cause) === '' || trim($cause) === '-') return '-';
        return match (strtolower(trim($cause))) {
            'dying-gasp', 'dyinggasp', 'dying gasp' => 'ONT mati mendadak, kehilangan daya listrik',
            'los', 'losi'                          => 'LOS: tidak ada sinyal optik (fiber putus/bermasalah)',
            'lobi', 'loobi'                        => 'LOBi: OLT tidak menerima burst optik dari ONT',
            'lofi'                                 => 'LOFi: loss of frame (sinkronisasi GPON hilang)',
            'reboot'                               => 'ONT di-reboot manual',
            'power-off'                            => 'ONT dimatikan (power off)',
            'ont-reset', 'reset'                   => 'ONT di-reset',
            'wire-down'                            => 'Kabel/konektor ONT terlepas',
            'deactive', 'deactivated'              => 'ONT dinonaktifkan dari OLT',
            default                                => $cause,
        };
    }

    private function normalizeDateTime(string $value): string
    {
        $value = trim($value);
        if ($value === '' || $value === '-') return '-';
        $formats = [['d-m-Y H:i:sP', true], ['Y-m-d H:i:sP', true], ['Y-m-d\TH:i:sP', true], ['d-m-Y H:i:s', false], ['Y-m-d H:i:s', false]];
        foreach ($formats as [$format, $hasZone]) {
            try { return \Carbon\Carbon::createFromFormat($format, $value)->format('d-m-Y H:i:s'); } catch (\Throwable) {}
        }
        return $value;
    }

    private function extractDescription(string $raw, ?string $id = null): string
    {
        if (preg_match('/Description\s*:\s*([^\r\n]+)/i', $raw, $m)) return trim($m[1]);
        if ($id !== null && preg_match('/' . preg_quote($id, '/') . '[ \t]*-[ \t]*([^\r\n]+)/i', $raw, $m)) return trim($id) . '-' . trim($m[1]);
        if (preg_match('/^\s*\d+\s*\/\s*\d+\s*\/\s*\d+\s+\d+\s+(\S+-[^\r\n]+)$/m', $raw, $m)) return trim($m[1]);
        return '-';
    }

    public function setOntState(string $olt, string $frame, string $slot, string $port, string $ontId, bool $activate): array
    {
        $action = $activate ? 'activate' : 'deactivate';
        try {
            $this->command($olt, 'config');
            $this->command($olt, "interface gpon {$frame}/{$slot}");
            $output = $this->command($olt, "ont {$action} {$port} {$ontId}") ?? '';
            if (preg_match('/(Failure|Error|Parameter error|Unknown command)/i', $output)) {
                return ['ok' => false, 'message' => 'OLT menolak perintah: ' . trim(preg_replace('/\s+/', ' ', $output))];
            }
            $this->command($olt, 'quit');
            $info = $this->command($olt, "display ont info {$frame} {$slot} {$port} {$ontId}") ?? '';
            $flag = preg_match('/Control\s*flag\s*:\s*(\w+)/i', $info, $m) ? strtolower($m[1]) : null;
            $expected = $activate ? 'active' : 'deactive';
            return ['ok' => $flag === null || $flag === $expected, 'message' => $flag ? "Control flag: {$flag}" : 'Terkirim.'];
        } finally {
            $this->close($olt);
        }
    }

    public function ontOfflineReason(string $state): string
    {
        return match (strtolower($state)) {
            'los' => 'ONT LOS: tidak ada sinyal optik.',
            'dying-gasp', 'dyinggasp' => 'ONT mati mendadak (dying gasp).',
            'offline' => 'ONT offline: perangkat tidak terhubung ke OLT.',
            default => "ONT tidak online (state: {$state}).",
        };
    }

    public function apiVersion(string $id): ?array
    {
        $id = trim($id);
        $found = $this->search("display ont info by-desc {$id}") ?? $this->search('display ont info by-desc "' . $id . '"');
        if (!$found || !preg_match('/(\d+\s*\/\s*\d+\s*\/\s*\d+)\s+(\d+)\s+([A-Z0-9]{12,20})\s+(\S+)\s+(\S+)/i', $found[1], $m)) return null;
        
        [$frame, $slot, $port] = explode('/', preg_replace('/\s+/', '', $m[1]));
        $state = $m[5];
        if (preg_match('/Run\s*state\s*:\s*([a-zA-Z0-9_-]+)/i', $found[1], $stateMatch)) $state = $stateMatch[1];
        $description = $this->extractDescription($found[1], $id);
        [, $customerName] = array_pad(explode('-', $description, 2), 2, '-');
        $customerName = trim($customerName) ?: '-';
        
        $raw = ''; $versionReason = null;
        if (strtolower($state) === 'online') {
            $raw = $this->command($found[0], "display ont version {$frame} {$slot} {$port} {$m[2]}") ?? '';
            if (preg_match('/Failure\s*:\s*([^\r\n]+)/i', $raw, $failure)) {
                $versionReason = stripos($failure[1], 'not online') !== false ? $this->ontOfflineReason($state) : 'OLT menolak command version: ' . trim($failure[1]);
            }
        } else {
            $versionReason = $this->ontOfflineReason($state);
        }
        $details = $this->details(['olt' => $found[0], 'frame' => $frame, 'slot' => $slot, 'port' => $port, 'ontId' => $m[2], 'sn' => $m[3], 'state' => $state, 'customerId' => $id, 'customerName' => $customerName, 'descriptionTarget' => $id]);
        $equipment = ($versionReason === null && preg_match('/Equipment-ID\s*:\s*([^\r\n]+)/i', $raw, $e)) ? trim($e[1]) : '-';
        $result = array_merge($details, ['idcust' => $id, 'customerName' => $customerName, 'sn' => $m[3], 'frame' => $frame, 'slot' => $slot, 'port' => $port, 'ontId' => $m[2], 'brand' => 'Unknown', 'type' => $equipment, 'wlanType' => 'Unknown', 'versionAvailable' => $versionReason === null, 'versionReason' => $versionReason]);
        $this->close($found[0]);
        return $result;
    }

    public function findPort(string $olt, string $frame, string $slot, string $port): ?array
    {
        $raw = $this->command($olt, "display ont info {$frame} {$slot} {$port} all");
        if (!$raw || str_contains($raw, 'The required port')) return null;
        $counts = ['online' => preg_match_all('/online/i', $raw), 'offline' => preg_match_all('/offline/i', $raw), 'los' => preg_match_all('/los/i', $raw), 'dyingGasp' => preg_match_all('/dying-gasp/i', $raw)];
        $result = compact('olt', 'frame', 'slot', 'port') + ['totalOnt' => array_sum($counts)] + $counts;
        $this->close($olt);
        return $result;
    }

    public function findPortOnt(string $frame, string $slot, string $port, string $ontId): ?array
    {
        $found = $this->search("display ont info {$frame} {$slot} {$port} {$ontId}");
        if (!$found || !preg_match('/Run\s*state\s*:\s*([a-zA-Z0-9_-]+)/i', $found[1], $s) || !preg_match('/SN\s*:\s*([A-Z0-9]{16})/i', $found[1], $sn)) return null;
        $description = preg_match('/Description\s*:\s*([^\r\n]+)/i', $found[1], $d) ? trim($d[1]) : '-';
        [$id, $name] = array_pad(explode('-', $description, 2), 2, '-');
        $result = $this->details(['olt' => $found[0], 'frame' => $frame, 'slot' => $slot, 'port' => $port, 'ontId' => $ontId, 'sn' => $sn[1], 'state' => $s[1], 'customerId' => $id, 'customerName' => $name, 'descriptionTarget' => $id]);
        $this->close($found[0]);
        return $result;
    }

    public function wlan(string $olt, string $frame, string $slot, string $port, string $ontId): array
    {
        try {
            $raw = $this->command($olt, "display ont wlan-info {$frame}/{$slot}/{$port} {$ontId}") ?? '';
            preg_match_all('/SSID\s*(?:Index|index)\s*:\s*(\d+)([\s\S]*?)(?=SSID\s*(?:Index|index)\s*:|Notes:|$)/i', $raw, $blocks, PREG_SET_ORDER);
            $ssids = array_map(fn ($m) => [
                'index' => $m[1],
                'name' => $this->field($m[2], '/(?:SSID|Service\s*set\s*identifier)\s*:\s*([^\r\n]+)/i'),
                'adminState' => $this->field($m[2], '/Administrative\s+state\s*:\s*([^\r\n]+)/i'),
                'opState' => $this->field($m[2], '/Operational\s+state\s*:\s*([^\r\n]+)/i'),
                'deviceMax' => $this->field($m[2], '/Maximum\s+associate\s+number\s*:\s*(\d+)/i'),
                'deviceCount' => $this->field($m[2], '/Current\s+associate\s+number\s*:\s*(\d+)/i', '0'),
            ], $blocks);
            return ['raw' => $raw, 'ssids' => $ssids];
        } finally {
            $this->close($olt);
        }
    }

    private function field(string $raw, string $pattern, string $default = '-'): string
    {
        return preg_match($pattern, $raw, $m) ? trim($m[1]) : $default;
    }

    public function restart(string $olt, string $frame, string $slot, string $port, string $ontId): ?string
    {
        try {
            $record = $this->activeOlt($olt);
            if (!$record) throw new \RuntimeException("OLT {$olt} tidak aktif.");
            $config = $this->command($olt, 'config') ?? '';
            if (preg_match('/Failure|Error|Unknown command/i', $config)) throw new \RuntimeException('Gagal masuk config mode: ' . trim($config));
            $interface = $this->command($olt, "interface gpon {$frame}/{$slot}") ?? '';
            if (preg_match('/Failure|Error|Unknown command/i', $interface)) throw new \RuntimeException('Gagal masuk interface: ' . trim($interface));
            
            $ssh = $this->session($record);
            $ssh->setTimeout(10);
            $ssh->write("ont reset {$port} {$ontId}\n");
            $confirmation = $ssh->read('/\(y\/n\)\[n\]:\s*$/i', SSH2::READ_REGEX);
            if ($confirmation === false || !preg_match('/\(y\/n\)\[n\]:/i', $confirmation)) throw new \RuntimeException('OLT tidak konfirmasi reset.');
            
            $ssh->write("y\n");
            $result = (string) $confirmation . (string) $ssh->read('#');
            if (preg_match('/Failure|Error/i', $result)) throw new \RuntimeException('OLT menolak reset.');
            return $result;
        } finally {
            $this->close($olt);
        }
    }

    public function scanActiveAlarms(): array
    {
        $alarms = [];
        foreach ($this->names() as $olt) {
            $raw = $this->command($olt, 'display alarm active all list') ?? '';
            $blocks = preg_split('/\n\s*(?=\d{5,}\s+\d{2}-\d{2}-\d{4})/', $raw);
            foreach ($blocks as $block) {
                $type = str_contains($block, 'LOSi/LOBi') ? 'LOS' : (str_contains($block, '(DGi)') ? 'DYING-GASP' : null);
                if (!$type || !preg_match('/FrameID\s*:\s*(\d+)/i', $block, $f) || !preg_match('/SlotID\s*:\s*(\d+)/i', $block, $s) || !preg_match('/PortID\s*:\s*(\d+)/i', $block, $p)) continue;
                $alarms[] = ['olt' => $olt, 'frame' => $f[1], 'slot' => $s[1], 'port' => $p[1], 'type' => $type];
            }
            $this->close($olt);
        }
        return $alarms;
    }

    public function getOltStatus(string $oltName): ?array
    {
        $record = $this->activeOlt($oltName);
        if (!$record) return null;

        $error = null;
        $uptime = $this->snmpGet($record, '1.3.6.1.2.1.1.3.0', $error);
        
        $cpu = '-'; $mem = '-';
        try {
            $ssh = $this->session($record);
            $ssh->write("display cpu-usage\n");
            $outputCpu = $ssh->read('#');
            if (preg_match('/CPU occupation\s*:\s*(\d+)%/i', $outputCpu, $m)) $cpu = $m[1] . '%';
            
            $ssh->write("display memory-usage\n");
            $outputMem = $ssh->read('#');
            if (preg_match('/Memory occupation\s*:\s*(\d+)%/i', $outputMem, $m)) $mem = $m[1] . '%';
        } catch (\Throwable $e) {}

        $uptimeStr = '-';
        if (is_string($uptime) && preg_match('/\((\d+)\)/', $uptime, $m)) {
            $days = floor(((int)$m[1] / 100) / 86400);
            $uptimeStr = "{$days} hari";
        }

        return ['olt' => $record->name, 'ip' => $record->host, 'cpu' => $cpu, 'mem' => $mem, 'uptime' => $uptimeStr];
    }

    private function scanAllSnmpPorts(Olt $olt, string $frame, int $slotCount, int $portCount): array { return ['olt' => $olt->name, 'frame' => $frame, 'slots' => [], 'summary' => [], 'scanned_at' => now()]; }
    private function scanSnmpPorts(Olt $olt, string $frame, string $slot, int $portCount): array { return []; }
    private function snmpWalk(string $peer, string $community, string $oid, int $timeout, int $retries, ?string &$error): array|false { return false; }
    private function snmpGet(Olt $olt, string $oid, ?string &$error): mixed { return false; }
    private function snmpPeer(Olt $olt): string { return "{$olt->host}:{$olt->port}"; }
    private function sshCredential(Olt $olt): string|\phpseclib3\Crypt\Common\PrivateKey { return $olt->ssh_password ?: $olt->password; }
    private function debug(string $event, array $context = []): void {}
    private function summarizePorts(array $ports): array { return []; }
    private function summarizeSlots(array $slots): array { return []; }
    private function formatSnmpVendorPorts(Olt $olt, string $frame, string $slot, array $statuses, int $portCount): array { return []; }
}