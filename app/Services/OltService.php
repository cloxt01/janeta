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
        // 'ont_description' => '1.3.6.1.4.1.2011.6.128.1.1.2.43.1.9',
        'ont_serial' => '1.3.6.1.4.1.2011.6.128.1.1.2.43.1.3',
        'line_profile' => '1.3.6.1.4.1.2011.6.128.1.1.2.43.1.7',
        'service_profile' => '1.3.6.1.4.1.2011.6.128.1.1.2.43.1.8',
        'ont_rx_power' => '1.3.6.1.4.1.2011.6.128.1.1.2.51.1.4',
        'ont_status' => '1.3.6.1.4.1.2011.6.128.1.1.2.62.1.22',
        'ont_temperature' => '1.3.6.1.4.1.2011.6.128.1.1.2.51.1.1',
        // 'ont_mac_count' => '1.3.6.1.4.1.2011.6.128.1.1.2.46.1.21',
        // 'ont_distance' => '1.3.6.1.4.1.2011.6.128.1.1.2.46.1.20',
        // 'ont_disconnect_reason' => '1.3.6.1.4.1.2011.6.128.1.1.2.46.1.24',
        'gpon_status' => self::HUAWEI_GPON_PORT_STATUS_OID,
        'olt_uptime' => '1.3.6.1.2.1.1.3',
        // 'interface_description' => self::INTERFACE_DESCRIPTION_OID,
        'interface_status' => self::INTERFACE_STATUS_OID,
        'ont_inbound_bytes' => '1.3.6.1.4.1.2011.6.128.1.1.4.23.1.4',
        'ont_outbound_bytes' => '1.3.6.1.4.1.2011.6.128.1.1.4.23.1.3',
        'unconfigured_ont' => '1.3.6.1.4.1.2011.6.128.1.1.2.52.1.2',
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
        $this->debug('snmp.snapshot.started', [
            'olt' => $olt->name,
            'frame' => $frame,
        ]);
        if (! $olt->snmp_enabled && $olt->connection_type !== 'snmp') {
            throw new \RuntimeException("OLT {$olt->name} bukan koneksi SNMP.");
        }

        $data = [
            'monitor' => $this->scanAllSnmpPorts($olt, $frame, 12, 16),
            'oids' => [],
        ];
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

        $snapshot = OltSnmpSnapshot::updateOrCreate(
            ['olt_id' => $olt->id, 'frame' => $frame],
            [
                'data' => $data,
                'error' => $errors ? json_encode($errors, JSON_UNESCAPED_SLASHES) : null,
                'collected_at' => now(),
            ],
        );
        $this->debug('snmp.snapshot.completed', [
            'olt' => $olt->name,
            'frame' => $frame,
            'oid_count' => count($data['oids']),
            'error_count' => count($errors),
        ]);

        return $snapshot;
    }

    public function latestSnmpSnapshot(Olt $olt, string $frame = '0'): ?OltSnmpSnapshot
    {
        return OltSnmpSnapshot::where([
            'olt_id' => $olt->id,
            'frame' => $frame,
        ])->first();
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
        $this->debug('ssh.command.started', [
            'olt' => $oltName,
            'command' => $command,
        ]);
        $olt = $this->activeOlt($oltName);
        if (!$olt) {
            $this->debug('ssh.command.skipped', ['olt' => $oltName, 'reason' => 'OLT tidak aktif atau tidak ditemukan.']);
            return null;
        }
        $ssh = $this->session($olt);
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
        $this->debug('ssh.command.completed', [
            'olt' => $oltName,
            'command' => $command,
            'duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
            'output_length' => strlen($output),
            'output' => $output,
        ]);
        return $output ?: null;
    }

    private function session(Olt $olt): SSH2
    {
        if (! $olt->ssh_enabled && $olt->connection_type !== 'ssh') {
            throw new \RuntimeException("OLT {$olt->name} bukan koneksi SSH.");
        }
        if ($olt->transport_type !== 'tcp') {
            throw new \RuntimeException("Koneksi SSH OLT {$olt->name} harus menggunakan TCP.");
        }

        if (isset($this->sessions[$olt->name])) {
            $this->debug('ssh.session.reused', ['olt' => $olt->name]);
            return $this->sessions[$olt->name];
        }

        $startedAt = microtime(true);
        $this->debug('ssh.session.connecting', [
            'olt' => $olt->name,
            'host' => $olt->host,
            'port' => $olt->ssh_port ?: $olt->port,
            'timeout' => config('janeta.ssh_timeout'),
        ]);
        $ssh = new SSH2($olt->host, $olt->ssh_port ?: $olt->port, config('janeta.ssh_timeout'));
        if (!$ssh->login($olt->ssh_username ?: $olt->username, $this->sshCredential($olt))) {
            $this->debug('ssh.session.login_failed', [
                'olt' => $olt->name,
                'duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
            ]);
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
        $this->debug('ssh.session.connected', [
            'olt' => $olt->name,
            'duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
        ]);

        return $ssh;
    }

    private function close(string $oltName): void
    {
        if (isset($this->sessions[$oltName])) {
            $this->sessions[$oltName]->disconnect();
            unset($this->sessions[$oltName]);
            $this->debug('ssh.session.closed', ['olt' => $oltName]);
        }
    }

    public function testConnection(string $oltName): array
    {
        $this->debug('connection.test.started', ['olt' => $oltName]);
        $olt = $this->olt($oltName);
        if (!$olt) {
            $this->debug('connection.test.not_found', ['olt' => $oltName]);
            return ['ok' => false, 'message' => 'OLT tidak ditemukan.'];
        }
        if (!$olt->host || ($olt->connection_type === 'ssh' ? ! ($olt->ssh_username ?: $olt->username) : ! ($olt->snmp_community ?: $olt->username))) {
            return $this->saveConnectionResult($olt, false, 'Konfigurasi host/username belum lengkap.');
        }

        try {
            $result = $this->activeConnectionType($olt) === 'snmp'
                ? $this->testSnmp($olt)
                : $this->testSsh($olt);
        } catch (\Throwable $e) {
            $this->close($olt->name);
            $result = ['ok' => false, 'message' => $e->getMessage()];
        }

        $savedResult = $this->saveConnectionResult($olt, $result['ok'], $result['message']);
        $this->debug('connection.test.completed', [
            'olt' => $olt->name,
            'connection_type' => $olt->connection_type,
            'ok' => $savedResult['ok'],
            'message' => $savedResult['message'],
        ]);

        return $savedResult;
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

            $caughtException = false;
            try {
                $result = $service === 'ssh' ? $this->testSsh($olt) : $this->testSnmp($olt);
            } catch (\Throwable $exception) {
                $caughtException = true;
                $this->close($olt->name);
                $result = ['ok' => false, 'message' => $exception->getMessage()];
                Log::error('OLT service connection failed', [
                    'olt' => $olt->name,
                    'service' => strtoupper($service),
                    'host' => $olt->host,
                    'port' => $service === 'ssh' ? ($olt->ssh_port ?: $olt->port) : ($olt->snmp_port ?: $olt->port),
                    'username' => $service === 'ssh' ? ($olt->ssh_username ?: $olt->username) : null,
                    'reason' => $exception->getMessage(),
                    'exception' => $exception::class,
                ]);
            }

            $results[$service] = $result;
            if (! $result['ok'] && ! $caughtException) {
                Log::error('OLT service connection rejected', [
                    'olt' => $olt->name,
                    'service' => strtoupper($service),
                    'host' => $olt->host,
                    'port' => $service === 'ssh' ? ($olt->ssh_port ?: $olt->port) : ($olt->snmp_port ?: $olt->port),
                    'username' => $service === 'ssh' ? ($olt->ssh_username ?: $olt->username) : null,
                    'reason' => $result['message'],
                ]);
            }
            $olt->update([
                $service.'_status' => $result['ok'] ? 'up' : 'down',
                $service.'_last_checked_at' => now(),
                $service.'_last_error' => $result['ok'] ? null : $result['message'],
            ]);
        }

        $activeResults = collect($results)->filter(fn (array $result, string $service): bool => $olt->{$service.'_enabled'});
        $overallOk = $activeResults->isNotEmpty() && $activeResults->every(fn (array $result): bool => $result['ok']);
        $firstFailure = $activeResults->first(fn (array $result): bool => ! $result['ok']);
        $olt->update([
            'status' => $overallOk ? 'online' : 'offline',
            'last_error' => $overallOk ? null : ($firstFailure['message'] ?? 'Tidak ada service aktif.'),
            'last_checked_at' => now(),
        ]);

        return $results;
    }

    private function testSsh(Olt $olt): array
    {
        if ($olt->transport_type !== 'tcp') {
            Log::error('OLT SSH connection rejected: invalid transport', [
                'olt' => $olt->name,
                'host' => $olt->host,
                'port' => $olt->ssh_port ?: $olt->port,
                'transport' => $olt->transport_type,
                'reason' => 'SSH requires TCP transport.',
            ]);

            return ['ok' => false, 'message' => 'Koneksi SSH harus menggunakan transport TCP.'];
        }

        $port = $olt->ssh_port ?: $olt->port;
        $username = $olt->ssh_username ?: $olt->username;
        $this->debug('ssh.connection.testing', [
            'olt' => $olt->name,
            'host' => $olt->host,
            'port' => $port,
            'username' => $username,
        ]);
        $ssh = new SSH2($olt->host, $port, min((int) config('janeta.ssh_timeout'), 5));
        $ok = $ssh->login($username, $this->sshCredential($olt));
        $ssh->disconnect();

        $message = $ok
            ? 'SSH login berhasil.'
            : "Login SSH gagal ke {$olt->host}:{$port}. Periksa port SSH, username, password/private key, dan firewall.";

        return ['ok' => $ok, 'message' => $message];
    }

    private function testSnmp(Olt $olt): array
    {
        if (!function_exists('snmp2_get')) {
            throw new \RuntimeException('Ekstensi PHP SNMP belum aktif. Aktifkan extension=snmp pada php.ini.');
        }

        $error = null;
        $value = $this->snmpGet($olt, self::SYSTEM_DESCRIPTION_OID, $error);
        $ok = $value !== false;

        return [
            'ok' => $ok,
            'message' => $ok
                ? 'Koneksi SNMP berhasil.'
                : ($error ?: 'SNMP tidak merespons. Cek community, versi SNMP, ACL, dan port.'),
        ];
    }

    private function activeConnectionType(Olt $olt): string
    {
        if ($olt->snmp_enabled) {
            return 'snmp';
        }

        if ($olt->ssh_enabled) {
            return 'ssh';
        }

        return $olt->connection_type;
    }

    private function saveConnectionResult(Olt $olt, bool $ok, string $message): array
    {
        $olt->update([
            'status' => $ok ? 'online' : 'offline',
            'last_error' => $ok ? null : $message,
            'last_checked_at' => now(),
        ]);

        return ['ok' => $ok, 'message' => $message];
    }

    private function search(string $command, ?string $preferred = null): ?array
    {
        $names = $preferred ? [$preferred] : $this->names();
        $this->debug('ssh.search.started', [
            'command' => $command,
            'preferred_olt' => $preferred,
            'olt_count' => count($names),
        ]);
        foreach ($names as $name) {
            try {
                $raw = $this->command($name, $command);
                if ($raw && !str_contains($raw, 'The required')) {
                    $this->debug('ssh.search.matched', [
                        'olt' => $name,
                        'command' => $command,
                        'output_length' => strlen($raw),
                    ]);
                    return [$name, $raw];
                }
            } catch (\Throwable $e) {
                Log::warning('OLT command failed', ['olt' => $name, 'error' => $e->getMessage()]);
                $this->debug('ssh.search.failed', [
                    'olt' => $name,
                    'command' => $command,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        $this->debug('ssh.search.not_found', ['command' => $command]);
        return null;
    }

    private function parse(array $match, string $raw, string $olt, ?string $id = null): array
    {
        [$all, $fsp, $ontId, $sn, $state] = $match;
        [$frame, $slot, $port] = explode('/', preg_replace('/\s+/', '', $fsp));
        $description = $this->extractDescription($raw, $id);
        [$customerId, $customerName] = array_pad(explode('-', $description, 2), 2, '-');
        return compact('olt', 'frame', 'slot', 'port', 'ontId', 'sn', 'state', 'customerId', 'customerName');
    }

    public function byDescription(string $id): ?array
    {
        $id = trim($id);
        // Coba cari tanpa kutip, lalu fallback dengan kutip ganda jika mengandung spasi/karakter khusus
        $found = $this->search("display ont info by-desc {$id}")
            ?? $this->search('display ont info by-desc "' . $id . '"');

        if (!$found || !preg_match('/(\d+\s*\/\s*\d+\s*\/\s*\d+)\s+(\d+)\s+([A-Z0-9]{16})\s+\w+\s+(\w+)/i', $found[1], $m)) {
            return null;
        }

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
        if (!$found) return null;
        if (!preg_match('/F\/S\/P\s*:\s*([.\/\d]+)/i', $found[1], $fsp) ||
            !preg_match('/ONT-ID\s*:\s*(\d+)/i', $found[1], $ontId) ||
            !preg_match('/Run\s*state\s*:\s*([a-zA-Z0-9_-]+)/i', $found[1], $state)) return null;
        $serial = preg_match('/SN\s*:\s*([A-Z0-9]{16})/i', $found[1], $serialMatch) ? $serialMatch[1] : $sn;
        $description = preg_match('/Description\s*:\s*([^\r\n]+)/i', $found[1], $descriptionMatch) ? trim($descriptionMatch[1]) : '-';
        [$frame, $slot, $port] = explode('/', trim($fsp[1]));
        [$customerId, $customerName] = array_pad(explode('-', $description, 2), 2, '-');
        $data = compact('frame', 'slot', 'port') + ['olt' => $found[0], 'ontId' => $ontId[1], 'sn' => $serial, 'state' => $state[1], 'customerId' => trim($customerId), 'customerName' => trim($customerName), 'descriptionTarget' => trim($customerId)];
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
            $raw = ($this->command($data['olt'], "display ont optical-info {$data['port']} {$data['ontId']}") ?? '') .
                ($this->command($data['olt'], "display ont wan-info {$data['port']} {$data['ontId']}") ?? '');
        }
        $value = function (string $pattern, string $default) use ($raw): string {
            return preg_match($pattern, $raw, $m) ? trim($m[1]) : $default;
        };
        return array_merge($data, [
            'runState' => $data['state'], 'rxPower' => $value('/Rx\s*optical\s*power(?:\(dBm\))?\s*:\s*([-\d.]+)/i', '-80.00'),
            'txPower' => $value('/Tx\s*optical\s*power(?:\(dBm\))?\s*:\s*([-\d.]+)/i', '-80.00'),
            'oltRxPower' => $value('/OLT\s*Rx\s*ONT\s*optical\s*power(?:\(dBm\))?\s*:\s*([-\d.]+)/i', '-80.00'),
            'temp' => $value('/Temperature(?:\(C\))?\s*:\s*([\d.]+)/i', '-'), 'voltage' => $value('/Voltage(?:\(V\))?\s*:\s*([\d.]+)/i', '0'),
            'wanConnType' => $value('/Connection\s+type\s*:\s*([^\r\n]+)/i', '-'), 'wanStatus' => $value('/IPv4\s+Connection\s+status\s*:\s*([^\r\n]+)/i', '-'),
            'wanAccessType' => $value('/IPv4\s+access\s+type\s*:\s*([^\r\n]+)/i', '-'), 'wanIp' => $value('/IPv4\s+address\s*:\s*([.\d]+)/i', '-'),
            'wanSubnet' => $value('/Subnet\s+mask\s*:\s*([.\d]+)/i', '-'), 'wanGateway' => $value('/Default\s+gateway\s*:\s*([.\d]+)/i', '-'),
            'wanDns1' => $value('/Primary\s+DNS\s*:\s*([.\d]+)/i', '-'), 'wanDns2' => $value('/Secondary\s+DNS\s*:\s*([.\d]+)/i', '-'),
            'lastDownCause' => strtolower($data['state']) === 'online'
                ? null
                : $infoValue('/Last\s+down\s+cause\s*:\s*([^\r\n]+)/i'),
            'lastUpTime' => $this->normalizeDateTime($infoValue('/Last\s+up\s+time\s*:\s*([^\r\n]+)/i')),
        ]);
    }

    private function normalizeDateTime(string $value): string
    {
        $value = trim($value);
        if ($value === '' || $value === '-') {
            return '-';
        }

        $formats = [
            ['d-m-Y H:i:sP', true],
            ['Y-m-d H:i:sP', true],
            ['Y-m-d\TH:i:sP', true],
            ['d-m-Y H:i:s', false],
            ['Y-m-d H:i:s', false],
        ];

        foreach ($formats as [$format, $hasZone]) {
            try {
                $date = \Carbon\Carbon::createFromFormat($format, $value);
                
                // Ubah format output sesuai keinginan, contoh: 'd M Y, H:i:s' (08 Nov 2026, 20:56:12)
                return $date->format('d-m-Y H:i:s'); 
            } catch (\Throwable) {
                // coba format berikutnya
            }
        }

        return $value; 
    }

    public function apiByDescription(string $id): ?array
    {
        $data = $this->byDescription($id);
        return $data ? ['descriptionTarget' => $id, 'customerName' => $data['customerName'], 'sn' => $data['sn'], 'frame' => $data['frame'], 'slot' => $data['slot'], 'port' => $data['port'], 'ontID' => $data['ontId']] : null;
    }
    private function extractDescription(string $raw, ?string $id = null): string
    {
        if (preg_match('/Description\s*:\s*([^\r\n]+)/i', $raw, $m)) {
            return trim($m[1]);
        }

        if ($id !== null && preg_match('/' . preg_quote($id, '/') . '[ \t]*-[ \t]*([^\r\n]+)/i', $raw, $m)) {
            return trim($id) . '-' . trim($m[1]);
        }

        if (preg_match('/^\s*\d+\s*\/\s*\d+\s*\/\s*\d+\s+\d+\s+(\S+-[^\r\n]+)$/m', $raw, $m)) {
            return trim($m[1]);
        }

        return '-';
    }

    public function setOntState(string $olt, string $frame, string $slot, string $port, string $ontId, bool $activate): array
    {
        $action = $activate ? 'activate' : 'deactivate';
        $this->debug("ssh.ont_{$action}.started", compact('olt', 'frame', 'slot', 'port', 'ontId'));

        try {
            $this->command($olt, 'config');
            $this->command($olt, "interface gpon {$frame}/{$slot}");
            $output = $this->command($olt, "ont {$action} {$port} {$ontId}") ?? '';

            if (preg_match('/(Failure|Error|Parameter error|Unknown command)/i', $output)) {
                return ['ok' => false, 'message' => 'OLT menolak perintah: ' . trim(preg_replace('/\s+/', ' ', $output))];
            }

            // Verifikasi Control flag
            $this->command($olt, 'quit');
            $info = $this->command($olt, "display ont info {$frame} {$slot} {$port} {$ontId}") ?? '';
            $flag = preg_match('/Control\s*flag\s*:\s*(\w+)/i', $info, $m) ? strtolower($m[1]) : null;

            $expected = $activate ? 'active' : 'deactive';
            $ok = $flag === null || $flag === $expected;

            return [
                'ok' => $ok,
                'message' => $flag ? "Control flag: {$flag}" : 'Perintah terkirim (status belum bisa diverifikasi).',
            ];
        } finally {
            $this->close($olt);
        }
    }
    public function ontOfflineReason(string $state): string
    {
        return match (strtolower($state)) {
            'los' => 'ONT LOS: tidak ada sinyal optik (fiber putus/bermasalah atau ONT tidak terhubung).',
            'dying-gasp', 'dyinggasp' => 'ONT mati mendadak (dying gasp): kehilangan daya listrik.',
            'offline' => 'ONT offline: perangkat tidak terhubung ke OLT.',
            default => "ONT tidak online (state: {$state}).",
        };
    }

    private function versionFailureReason(string $message, string $state): string
    {
        if (stripos($message, 'not online') !== false) {
            return strtolower($state) === 'online'
                ? "ONT tidak online saat data versi dibaca (state terakhir: {$state})."
                : $this->ontOfflineReason($state);
        }

        return 'OLT menolak command version: ' . $message;
    }

    public function apiVersion(string $id): ?array
    {
        $id = trim($id);
        $found = $this->search("display ont info by-desc {$id}")
            ?? $this->search('display ont info by-desc "' . $id . '"');
        if (!$found || !preg_match('/(\d+\s*\/\s*\d+\s*\/\s*\d+)\s+(\d+)\s+([A-Z0-9]{12,20})\s+(\S+)\s+(\S+)/i', $found[1], $m)) {
            Log::warning('ONT lookup response did not match expected format', [
                'olt' => $found[0] ?? null,
                'customer_id' => $id
            ]);
            $this->debug('ssh.ont_lookup.unparseable_response', [
                'olt' => $found[0] ?? null,
                'customer_id' => $id,
                'response' => $found[1] ?? null,
            ]);
            return null;
        }
        [$frame, $slot, $port] = explode('/', preg_replace('/\s+/', '', $m[1]));
        $state = $m[5];
        if (preg_match('/Run\s*state\s*:\s*([a-zA-Z0-9_-]+)/i', $found[1], $stateMatch)) {
            $state = $stateMatch[1];
        }

        $description = $this->extractDescription($found[1], $id);
        [, $customerName] = array_pad(explode('-', $description, 2), 2, '-');
        $customerName = trim($customerName) ?: '-';

        $stateLower = strtolower($state);
        $raw = '';
        $versionReason = null;

        if ($stateLower === 'online') {
            $raw = $this->command($found[0], "display ont version {$frame} {$slot} {$port} {$m[2]}") ?? '';
            if (preg_match('/Failure\s*:\s*([^\r\n]+)/i', $raw, $failure)) {
                $versionReason = $this->versionFailureReason(trim($failure[1]), $state);
            }
        } else {
            $versionReason = $this->ontOfflineReason($state);
        }

        $details = $this->details([
            'olt' => $found[0],
            'frame' => $frame,
            'slot' => $slot,
            'port' => $port,
            'ontId' => $m[2],
            'sn' => $m[3],
            'state' => $state,
            'customerId' => $id,
            'customerName' => $customerName,
            'descriptionTarget' => $id,
        ]);

        $equipment = ($versionReason === null && preg_match('/Equipment-ID\s*:\s*([^\r\n]+)/i', $raw, $e))
            ? trim($e[1])
            : '-';

        $result = array_merge($details, [
            'idcust' => $id,
            'customerName' => $customerName,
            'sn' => $m[3],
            'frame' => $frame,
            'slot' => $slot,
            'port' => $port,
            'ontId' => $m[2],
            'brand' => 'Unknown',
            'type' => $equipment,
            'wlanType' => 'Unknown',
            'versionAvailable' => $versionReason === null,
            'versionReason' => $versionReason,
        ]);
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

    public function scanGponPorts(string $olt, string $frame, string $slot, int $portCount = 16): array
    {
        $this->debug('ssh.gpon_scan.started', [
            'olt' => $olt,
            'frame' => $frame,
            'slot' => $slot,
            'port_count' => $portCount,
        ]);
        $record = Olt::where('name', strtoupper($olt))->where('is_active', true)->first();
        if ($record && $this->activeConnectionType($record) === 'snmp') {
            return $this->scanSnmpPorts($record, $frame, $slot, $portCount);
        }

        set_time_limit(180);
        $ports = [];
        try {
            for ($port = 0; $port < $portCount; $port++) {
                set_time_limit(180);
                $raw = $this->command($olt, "display ont info {$frame} {$slot} {$port} all") ?? '';
                $hasData = $raw !== '' && ! str_contains($raw, 'The required port');
                $online = $hasData ? preg_match_all('/\bonline\b/i', $raw) : 0;
                $offline = $hasData ? preg_match_all('/\boffline\b/i', $raw) : 0;
                $los = $hasData ? preg_match_all('/\blos\b/i', $raw) : 0;
                $dyingGasp = $hasData ? preg_match_all('/dying[-\s]?gasp/i', $raw) : 0;
                $totalOnt = $online + $offline + $los + $dyingGasp;
                $state = $los > 0 ? 'los' : ($online > 0 ? 'online' : ($offline > 0 ? 'offline' : 'inactive'));

                $ports[] = compact('port', 'online', 'offline', 'los', 'dyingGasp', 'totalOnt', 'state');
            }
        } finally {
            $this->close($olt);
        }

        $summary = $this->summarizePorts($ports);
        $this->debug('ssh.gpon_scan.completed', [
            'olt' => $olt,
            'frame' => $frame,
            'slot' => $slot,
            'summary' => $summary,
        ]);

        return [
            'olt' => $olt,
            'frame' => $frame,
            'slot' => $slot,
            'ports' => $ports,
            'summary' => $this->summarizePorts($ports),
            'scanned_at' => now(),
        ];
    }

    public function scanAllGponPorts(string $olt, string $frame, int $slotCount = 12, int $portCount = 16): array
    {
        $this->debug('gpon_scan_all.started', [
            'olt' => $olt,
            'frame' => $frame,
            'slot_count' => $slotCount,
            'port_count' => $portCount,
        ]);
        $record = $this->activeOlt($olt);
        if (!$record) {
            throw new \RuntimeException("OLT {$olt} tidak aktif atau tidak ditemukan.");
        }

        if ($this->activeConnectionType($record) === 'snmp') {
            return $this->scanAllSnmpPorts($record, $frame, $slotCount, $portCount);
        }

        $slots = [];
        for ($slot = 0; $slot < $slotCount; $slot++) {
            try {
                $slots[] = $this->scanGponPorts($olt, $frame, (string) $slot, $portCount);
            } catch (\Throwable $exception) {
                Log::warning('OLT slot scan failed', [
                    'olt' => $olt,
                    'frame' => $frame,
                    'slot' => $slot,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $summary = $this->summarizeSlots($slots);
        $this->debug('gpon_scan_all.completed', [
            'olt' => $olt,
            'frame' => $frame,
            'slot_count' => count($slots),
            'summary' => $summary,
        ]);

        return [
            'olt' => $olt,
            'frame' => $frame,
            'slots' => $slots,
            'summary' => $summary,
            'scanned_at' => now(),
        ];
    }

    private function scanAllSnmpPorts(Olt $olt, string $frame, int $slotCount, int $portCount): array
    {
        $slots = [];
        for ($slot = 0; $slot < $slotCount; $slot++) {
            try {
                $slots[] = $this->scanSnmpPorts($olt, $frame, (string) $slot, $portCount);
            } catch (\Throwable $exception) {
                Log::info('SNMP slot has no GPON data', [
                    'olt' => $olt->name,
                    'frame' => $frame,
                    'slot' => $slot,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return [
            'olt' => $olt->name,
            'frame' => $frame,
            'slots' => $slots,
            'summary' => $this->summarizeSlots($slots),
            'scanned_at' => now(),
        ];
    }

    private function summarizePorts(array $ports): array
    {
        return [
            'totalOnt' => array_sum(array_map(
                static fn (array $port): int => (int) ($port['totalOnt'] ?? 0),
                $ports,
            )),
            'online' => array_sum(array_column($ports, 'online')),
            'offline' => array_sum(array_column($ports, 'offline')),
            'los' => array_sum(array_column($ports, 'los')),
            'dyingGasp' => array_sum(array_column($ports, 'dyingGasp')),
            'ports' => count($ports),
        ];
    }

    private function summarizeSlots(array $slots): array
    {
        $summary = [
            'totalOnt' => 0,
            'online' => 0,
            'offline' => 0,
            'los' => 0,
            'dyingGasp' => 0,
            'ports' => 0,
            'slots' => count($slots),
        ];

        foreach ($slots as $slot) {
            foreach (['totalOnt', 'online', 'offline', 'los', 'dyingGasp', 'ports'] as $key) {
                $summary[$key] += (int) ($slot['summary'][$key] ?? 0);
            }
        }

        return $summary;
    }

    private function scanSnmpPorts(Olt $olt, string $frame, string $slot, int $portCount): array
    {
        $this->debug('snmp.gpon_scan.started', [
            'olt' => $olt->name,
            'frame' => $frame,
            'slot' => $slot,
            'port_count' => $portCount,
        ]);
        if (! function_exists('snmp2_real_walk')) {
            throw new \RuntimeException('Ekstensi PHP SNMP belum aktif. Aktifkan extension=snmp pada php.ini.');
        }

        $peer = $this->snmpPeer($olt);
        $timeout = (int) config('janeta.snmp_timeout');
        $retries = (int) config('janeta.snmp_retries');
        $vendorError = null;
        $vendorStatuses = $this->snmpWalk($peer, $olt->snmp_community ?: $olt->username, self::HUAWEI_GPON_PORT_STATUS_OID, $timeout, $retries, $vendorError);
        if ($vendorStatuses !== false && $vendorStatuses !== []) {
            $this->debug('snmp.gpon_scan.vendor_result', [
                'olt' => $olt->name,
                'frame' => $frame,
                'slot' => $slot,
                'status_count' => count($vendorStatuses),
            ]);
            return $this->formatSnmpVendorPorts($olt, $frame, $slot, $vendorStatuses, $portCount);
        }
        $this->debug('snmp.gpon_scan.fallback_if_mib', [
            'olt' => $olt->name,
            'frame' => $frame,
            'slot' => $slot,
            'vendor_error' => $vendorError,
        ]);

        $descriptionError = null;
        $descriptions = $this->snmpWalk($peer, $olt->snmp_community ?: $olt->username, self::INTERFACE_DESCRIPTION_OID, $timeout, $retries, $descriptionError);
        if ($descriptions === false) {
            throw new \RuntimeException($vendorError ?: $descriptionError ?: 'SNMP tidak merespons saat membaca daftar port.');
        }
        $statusError = null;
        $statuses = $this->snmpWalk($peer, $olt->snmp_community ?: $olt->username, self::INTERFACE_STATUS_OID, $timeout, $retries, $statusError);
        if ($statuses === false) {
            throw new \RuntimeException($statusError ?: 'SNMP tidak merespons saat membaca status interface.');
        }
        $statusByIndex = [];
        foreach ($statuses as $oid => $status) {
            if (preg_match('/(\d+)$/', (string) $oid, $indexMatch)) {
                $statusByIndex[$indexMatch[1]] = (string) $status;
            }
        }

        $requestedSlot = (int) $slot;
        $ports = [];
        foreach ($descriptions as $oid => $description) {
            $label = trim(preg_replace('/^[^:]+:\s*/', '', (string) $description));
            if (! preg_match('/\b(gpon|pon)\b/i', $label)) {
                continue;
            }

            preg_match('/(\d+)$/', (string) $oid, $indexMatch);
            $index = $indexMatch[1] ?? null;
            if ($index === null) {
                continue;
            }
            if ((((int) $index - 0xFA000000) >> 13) !== $requestedSlot) {
                continue;
            }

            $status = $statusByIndex[$index] ?? '';
            $isUp = preg_match('/\bup\s*\(1\)|\b1\b/i', $status) === 1;
            $port = count($ports);
            if ($port >= $portCount) {
                break;
            }

            $ports[] = [
                'port' => $port,
                'label' => $label,
                'online' => $isUp ? 1 : 0,
                'offline' => $isUp ? 0 : 1,
                'los' => 0,
                'dyingGasp' => 0,
                'totalOnt' => null,
                'trafficIn' => null,
                'trafficOut' => null,
                'state' => $isUp ? 'online' : 'offline',
            ];
        }

        if ($ports === []) {
            throw new \RuntimeException("SNMP aktif, tetapi tidak ada interface GPON untuk slot {$slot}.");
        }

        $this->debug('snmp.gpon_scan.completed', [
            'olt' => $olt->name,
            'frame' => $frame,
            'slot' => $slot,
            'summary' => $this->summarizePorts($ports),
        ]);

        return [
            'olt' => $olt->name,
            'frame' => $frame,
            'slot' => $slot,
            'ports' => $ports,
            'monitor_mode' => 'snmp',
            'monitor_source' => 'if-mib',
            'ont_count_available' => false,
            'traffic_available' => false,
            'summary' => $this->summarizePorts($ports),
            'scanned_at' => now(),
        ];
    }

    private function snmpWalk(string $peer, string $community, string $oid, int $timeout, int $retries, ?string &$error): array|false
    {
        $startedAt = microtime(true);
        $this->debug('snmp.walk.started', [
            'peer' => $peer,
            'oid' => $oid,
            'timeout' => $timeout,
            'retries' => $retries,
        ]);
        set_error_handler(static function (int $severity, string $message) use (&$error): bool {
            $error = $message;

            return true;
        });
        try {
            $result = snmp2_real_walk($peer, $community, $oid, $timeout, $retries);
            $this->debug('snmp.walk.completed', [
                'peer' => $peer,
                'oid' => $oid,
                'duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
                'success' => $result !== false,
                'result_count' => is_array($result) ? count($result) : 0,
                'error' => $error,
            ]);

            return $result;
        } finally {
            restore_error_handler();
        }
    }

    private function snmpGet(Olt $olt, string $oid, ?string &$error): mixed
    {
        $peer = $this->snmpPeer($olt);
        $startedAt = microtime(true);
        $this->debug('snmp.get.started', [
            'olt' => $olt->name,
            'peer' => $peer,
            'oid' => $oid,
            'timeout' => config('janeta.snmp_timeout'),
            'retries' => config('janeta.snmp_retries'),
        ]);
        set_error_handler(static function (int $severity, string $message) use (&$error): bool {
            $error = $message;

            return true;
        });
        try {
            $result = snmp2_get(
                $peer,
                $olt->snmp_community ?: $olt->username,
                $oid,
                (int) config('janeta.snmp_timeout'),
                (int) config('janeta.snmp_retries'),
            );
            $this->debug('snmp.get.completed', [
                'olt' => $olt->name,
                'peer' => $peer,
                'oid' => $oid,
                'duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
                'success' => $result !== false,
                'result' => $result,
                'error' => $error,
            ]);

            return $result;
        } finally {
            restore_error_handler();
        }
    }

    private function snmpPeer(Olt $olt): string
    {
        $port = $olt->snmp_port ?: $olt->port;

        return "{$olt->host}:{$port}";
    }

    private function sshCredential(Olt $olt): string|\phpseclib3\Crypt\Common\PrivateKey
    {
        if ($olt->ssh_private_key) {
            return PublicKeyLoader::load($olt->ssh_private_key);
        }

        return $olt->ssh_password ?: $olt->password;
    }

    private function debug(string $event, array $context = []): void
    {
        if (! config('janeta.debug_enabled')) {
            return;
        }

        Log::channel((string) config('janeta.debug_log_channel', 'olt'))->debug($event, [
            'trace_id' => $this->traceId,
            ...$this->redact($context),
        ]);
    }

    private function redact(mixed $value): mixed
    {
        if (is_array($value)) {
            $redacted = [];
            foreach ($value as $key => $item) {
                $keyString = strtolower((string) $key);
                $redacted[$key] = str_contains($keyString, 'password')
                    || str_contains($keyString, 'passphrase')
                    || str_contains($keyString, 'pre-shared')
                    || str_contains($keyString, 'community')
                    || str_contains($keyString, 'secret')
                    || str_contains($keyString, 'token')
                    ? '[redacted]'
                    : $this->redact($item);
            }

            return $redacted;
        }

        if (! is_string($value)) {
            return $value;
        }

        return preg_replace(
            '/((?:password|passphrase|pre-shared\s+key|community|secret|token)\s*[:=]\s*)([^\s,;]+)/i',
            '$1[redacted]',
            $value,
        ) ?? $value;
    }

    private function formatSnmpVendorPorts(Olt $olt, string $frame, string $slot, array $statuses, int $portCount): array
    {
        $requestedSlot = (int) $slot;
        $ports = [];
        foreach ($statuses as $oid => $value) {
            if (count($ports) >= $portCount) {
                break;
            }
            preg_match('/(\d+)$/', (string) $oid, $indexMatch);
            $index = $indexMatch[1] ?? (string) count($ports);
            if ((((int) $index - 0xFA000000) >> 13) !== $requestedSlot) {
                continue;
            }
            $rawValue = trim((string) $value);
            $isUp = preg_match('/(?:INTEGER|GAUGE32|COUNTER32):\s*1\b/i', $rawValue) === 1
                || preg_match('/\bup\b/i', $rawValue) === 1
                || $rawValue === '1';
            $ports[] = [
                'port' => count($ports),
                'label' => "GPON {$index}",
                'online' => $isUp ? 1 : 0,
                'offline' => $isUp ? 0 : 1,
                'los' => 0,
                'dyingGasp' => 0,
                'totalOnt' => null,
                'trafficIn' => null,
                'trafficOut' => null,
                'state' => $isUp ? 'online' : 'offline',
            ];
        }

        return [
            'olt' => $olt->name,
            'frame' => $frame,
            'slot' => $slot,
            'ports' => $ports,
            'monitor_mode' => 'snmp',
            'monitor_source' => 'huawei',
            'ont_count_available' => false,
            'traffic_available' => false,
            'summary' => $this->summarizePorts($ports),
            'scanned_at' => now(),
        ];
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
        $this->debug('ssh.wlan.started', compact('olt', 'frame', 'slot', 'port', 'ontId'));
        try {
            $raw = $this->command($olt, "display ont wlan-info {$frame}/{$slot}/{$port} {$ontId}") ?? '';
            if (str_contains($raw, 'Parameter error') || str_contains($raw, 'Error:')) {
                Log::warning('OLT rejected WLAN command', [
                    'olt' => $olt,
                    'command' => "display ont wlan-info {$frame}/{$slot}/{$port} {$ontId}",
                ]);
                $this->debug('ssh.wlan.rejected', [
                    'olt' => $olt,
                    'frame' => $frame,
                    'slot' => $slot,
                    'port' => $port,
                    'ont_id' => $ontId,
                    'response' => $raw,
                ]);
            }
            preg_match_all('/SSID\s*(?:Index|index)\s*:\s*(\d+)([\s\S]*?)(?=SSID\s*(?:Index|index)\s*:|Notes:|$)/i', $raw, $blocks, PREG_SET_ORDER);
            $ssids = array_map(fn ($m) => [
                'index' => $m[1],
                'name' => $this->field($m[2], '/(?:SSID|Service\s*set\s*identifier)\s*:\s*([^\r\n]+)/i'),
                'adminState' => $this->field($m[2], '/Administrative\s+state\s*:\s*([^\r\n]+)/i'),
                'opState' => $this->field($m[2], '/Operational\s+state\s*:\s*([^\r\n]+)/i'),
                'deviceMax' => $this->field($m[2], '/Maximum\s+associate\s+number\s*:\s*(\d+)/i'),
                'deviceCount' => $this->field($m[2], '/Current\s+associate\s+number\s*:\s*(\d+)/i', '0'),
            ], $blocks);
            if ($raw !== '' && empty($ssids)) {
                Log::warning('ONT WLAN response did not contain parseable SSID blocks', [
                    'olt' => $olt,
                    'frame' => $frame,
                    'slot' => $slot,
                    'port' => $port,
                    'ont_id' => $ontId,
                ]);
                $this->debug('ssh.wlan.unparseable_response', [
                    'olt' => $olt,
                    'frame' => $frame,
                    'slot' => $slot,
                    'port' => $port,
                    'ont_id' => $ontId,
                    'response' => $raw,
                ]);
            }

            $this->debug('ssh.wlan.completed', [
                'olt' => $olt,
                'frame' => $frame,
                'slot' => $slot,
                'port' => $port,
                'ont_id' => $ontId,
                'ssid_count' => count($ssids),
                'output_length' => strlen($raw),
                'response' => $raw,
            ]);

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
        $this->debug('ssh.ont_restart.started', compact('olt', 'frame', 'slot', 'port', 'ontId'));
        $this->command($olt, "interface gpon {$frame}/{$slot}");
        $result = $this->command($olt, "ont reset {$port} {$ontId}");
        $this->close($olt);
        $this->debug('ssh.ont_restart.completed', [
            'olt' => $olt,
            'frame' => $frame,
            'slot' => $slot,
            'port' => $port,
            'ont_id' => $ontId,
            'output_length' => strlen((string) $result),
            'response' => $result,
        ]);
        return $result;
    }

    public function scanActiveAlarms(): array
    {
        $this->debug('ssh.alarms_scan.started');
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
        $this->debug('ssh.alarms_scan.completed', ['alarm_count' => count($alarms), 'alarms' => $alarms]);
        return $alarms;
    }
}
