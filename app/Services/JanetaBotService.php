<?php

namespace App\Services;

use App\Models\OntAuditLog;
use App\Models\WhitelistEntry;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class JanetaBotService
{
    public function __construct(private TelegramClient $telegram, private OltService $olt) {}

    public function handle(array $update): void
    {
        if (isset($update['callback_query'])) {
            $this->callback($update['callback_query']);
            return;
        }
        $message = $update['message'] ?? null;
        if (!$message || !isset($message['chat']['id'])) return;
        $chatId = (string) $message['chat']['id'];
        $text   = trim($message['text'] ?? '');
        $entry  = WhitelistEntry::where('chat_id', $chatId)->first();
        
        if (!$entry) {
            if (str_starts_with($text, '/')) $this->send($chatId, "Akses Ditolak. Anda tidak memiliki izin.\nID Chat Anda: {$chatId}\nMinta Administrator/NOC untuk mendaftarkan ID Anda.");
            return;
        }
        if (!str_starts_with($text, '/')) return;
        
        [$command, $args] = array_pad(preg_split('/\s+/', $text, 2), 2, '');
        $command = strtolower(explode('@', $command)[0]);
        
        $this->telegram->sendChatAction($chatId, 'typing');

        try {
            match ($command) {
                '/start', '/help'  => $this->send($chatId, $this->help($message['from']['first_name'] ?? 'Rekan')),
                '/add'             => $this->addUser($chatId, $args, $entry->is_admin),
                '/delete'          => $this->deleteUser($chatId, $args, $entry->is_admin),
                '/list'            => $this->listUsers($chatId),
                '/add_admin'       => $this->addAdmin($chatId, $args, $entry->is_admin),
                '/delete_admin'    => $this->deleteAdmin($chatId, $args, $entry->is_admin),
                '/list_admin'      => $this->listAdmins($chatId),
                '/check'           => $this->check($chatId, $args),
                '/checksn'         => $this->checkSn($chatId, $args),
                '/findport'        => $this->findPort($chatId, $args),
                '/findportont'     => $this->findPortOnt($chatId, $args),
                '/oltstatus'       => $this->oltStatus($chatId, $args),
                '/deactive'        => $this->changeState($chatId, $args, false),
                '/active'          => $this->changeState($chatId, $args, true),
                default            => null,
            };
        } catch (\Throwable $e) {
            Log::error('JANETA command failed', ['command' => $command, 'error' => $e->getMessage()]);
            $this->send($chatId, 'Terjadi gangguan internal komunikasi saat membaca data OLT.');
        }
    }

    private function send(string $chatId, string $text, ?array $buttons = null): void
    {
        $this->telegram->sendMessage($chatId, $text, $buttons);
    }

    private function isAdmin(string $chatId): bool
    {
        return WhitelistEntry::where('chat_id', $chatId)->where('is_admin', true)->exists();
    }

    private function isWhitelisted(string $chatId): bool
    {
        return WhitelistEntry::where('chat_id', $chatId)->exists();
    }

    private function adminOnly(string $chatId, bool $isAdmin): bool
    {
        if (!$isAdmin) $this->send($chatId, 'Perintah ini khusus untuk Administrator/NOC.');
        return $isAdmin;
    }

    private function addUser(string $chatId, string $args, bool $admin): void
    {
        if (!$this->adminOnly($chatId, $admin)) return;
        [$id, $name] = array_pad(preg_split('/\s+/', trim($args), 2), 2, '');
        if (!$id || !$name) { $this->send($chatId, 'Format salah. Contoh: `/add 612345678 Ahmad - Teknisi Lapangan`'); return; }
        WhitelistEntry::updateOrCreate(['chat_id' => $id], ['name' => $name]);
        $this->send($chatId, "Berhasil menambahkan pengguna baru!\nNama: {$name}\nID Chat: {$id}");
    }

    private function deleteUser(string $chatId, string $args, bool $admin): void
    {
        if (!$this->adminOnly($chatId, $admin)) return;
        $target = trim($args);
        $user = WhitelistEntry::where('chat_id', $target)->where('is_admin', false)->first();
        if (!$user) { $this->send($chatId, 'User ID tidak ditemukan dalam whitelist.'); return; }
        $user->delete();
        $this->send($chatId, "Berhasil menghapus akses untuk:\nNama: {$user->name}\nID Chat: {$target}");
    }

    private function listUsers(string $chatId): void
    {
        $users = WhitelistEntry::whereNotNull('name')->get();
        $text  = "DAFTAR TEKNISI & CS TER-IZINKAN JANGKAUNET\n\n";
        $this->send($chatId, $users->isEmpty() ? $text . '_Belum ada user yang terdaftar._' : $text . $users->map(fn ($u) => "• `{$u->chat_id}` - {$u->name}\n")->implode(''));
    }

    private function addAdmin(string $chatId, string $args, bool $admin): void
    {
        if (!$this->adminOnly($chatId, $admin)) return;
        $id = trim($args);
        if (!$id || !is_numeric($id)) { $this->send($chatId, 'Format salah. Contoh: `/add_admin 8601271578`'); return; }
        $user = WhitelistEntry::updateOrCreate(['chat_id' => $id], ['is_admin' => true]);
        if (!$user->wasRecentlyCreated && $user->is_admin) { $this->send($chatId, 'ID Chat tersebut sudah berstatus sebagai Admin.'); return; }
        $this->send($chatId, "Berhasil menambahkan Administrator baru!\nID Chat: `{$id}`");
    }

    private function deleteAdmin(string $chatId, string $args, bool $admin): void
    {
        if (!$this->adminOnly($chatId, $admin)) return;
        $id = trim($args);
        if (!$id) { $this->send($chatId, 'Format salah. Contoh: `/delete_admin 8601271578`'); return; }
        if ($id === $chatId) { $this->send($chatId, 'Anda tidak bisa menghapus status admin Anda sendiri demi keamanan.'); return; }
        $user = WhitelistEntry::where('chat_id', $id)->where('is_admin', true)->first();
        if (!$user) { $this->send($chatId, 'ID Chat tersebut tidak ditemukan dalam daftar Admin.'); return; }
        $user->update(['is_admin' => false]);
        $this->send($chatId, "Berhasil menghapus status Admin untuk ID Chat: `{$id}`");
    }

    private function listAdmins(string $chatId): void
    {
        $text = "DAFTAR ADMINISTRATOR NOC JANGKAUNET\n\n";
        $this->send($chatId, $text . WhitelistEntry::where('is_admin', true)->get()->values()->map(fn ($u, $i) => ($i + 1) . ". `{$u->chat_id}`\n")->implode(''));
    }

    private function help(string $name): string
    {
        return "JANETA - JangkauNet NetOps Assistant\nHalo *{$name}*, berikut adalah daftar perintah Anda:\n\n"
            . "*OPERASIONAL GPON OLT*\n"
            . "• `/check [ID_Customer]` : Cek redaman, fisik ONT, dan status WAN Internet.\n"
            . "• `/checksn [Serial_Number]` : Cek redaman & WAN berdasarkan Serial Number.\n"
            . "• `/findport [NAMA_OLT] [Frame]/[Slot]/[Port]` : Statistik port OLT.\n"
            . "• `/findportont [Frame]/[Slot]/[Port] [ONT_ID]` : Cek ONT spesifik.\n"
            . "• `/oltstatus [NAMA_OLT]` : Cek kesehatan CPU, Memory, dan Uptime OLT.\n"
            . "• `/deactive [ID_Customer]` : Nonaktifkan ONT.\n"
            . "• `/active [ID_Customer]` : Aktifkan kembali ONT.\n\n"
            . "*ADMINISTRASI*\n"
            . "• `/list` dan `/list_admin` : Lihat whitelist.\n"
            . "• `/add`, `/delete`, `/add_admin`, `/delete_admin` : Kelola akses.\n\n"
            . "*ISTILAH PENTING*\n"
            . "• _Online Duration_ = lama ONT terkoneksi ke jaringan JangkauNet.\n"
            . "• _System Up Duration_ = lama ONT menyala sejak terakhir restart.\n"
            . "• _Last Down Cause_ = penyebab terakhir ONT terputus (dying-gasp = listrik mati, LOS = fiber putus).";
    }

    private function check(string $chatId, string $id): void
    {
        if (!$id) { $this->send($chatId, 'Format salah. Contoh penggunaan: /check 1JN202*********'); return; }
        $this->report($chatId, fn() => $this->olt->byDescription($id));
    }

    private function checkSn(string $chatId, string $sn): void
    {
        $sn = strtoupper(trim($sn));
        if (strlen($sn) !== 16) { $this->send($chatId, 'Serial Number tidak valid! Pastikan berjumlah tepat 16 karakter tanpa spasi.'); return; }
        $this->report($chatId, fn() => $this->olt->bySerial($sn));
    }

    private function findPortOnt(string $chatId, string $args): void
    {
        [$fsp, $id] = array_pad(preg_split('/\s+/', trim($args)), 2, '');
        [$f, $s, $p] = array_pad(explode('/', $fsp), 3, '');
        if ($fsp === '' || !ctype_digit($f) || !ctype_digit($s) || !ctype_digit($p) || !ctype_digit($id)) {
            $this->send($chatId, 'Format salah. Contoh: `/findportont 0/5/13 5`');
            return;
        }
        $this->report($chatId, fn() => $this->olt->findPortOnt($f, $s, $p, $id));
    }

    private function oltStatus(string $chatId, string $args): void
    {
        $olt = strtoupper(trim($args));
        if (!$olt) { $this->send($chatId, 'Format salah. Contoh: `/oltstatus DUTA`'); return; }

        $msg = $this->telegram->sendMessage($chatId, "Memproses...\nMengambil status kesehatan OLT `{$olt}`...");
        $messageId = $msg['message_id'] ?? null;

        $status = $this->olt->getOltStatus($olt);
        
        if (!$status) {
            $text = "OLT `{$olt}` tidak ditemukan atau tidak aktif.";
        } else {
            $text = "STATUS KESEHATAN OLT: {$status['olt']}\n\n"
                . "• IP Address: `{$status['ip']}`\n"
                . "• CPU Usage: *{$status['cpu']}*\n"
                . "• Memory Usage: *{$status['mem']}*\n"
                . "• Uptime: *{$status['uptime']}*\n";
        }

        if ($messageId) {
            $this->telegram->editMessageText($chatId, $messageId, $text);
        } else {
            $this->send($chatId, $text);
        }
    }

    private function report(string $chatId, callable $fetcher): void
    {
        $msg = $this->telegram->sendMessage($chatId, "Memproses...\nSedang mengambil data dari OLT. Mohon tunggu sekitar 5-15 detik.");
        $messageId = $msg['message_id'] ?? null;

        $data = null;
        try {
            $data = $fetcher();
        } catch (\Throwable $e) {
            Log::error('JANETA report fetch failed', ['error' => $e->getMessage()]);
        }

        if (!$data) {
            $text = 'Data ONT tidak ditemukan di OLT atau terjadi kesalahan koneksi.';
            $buttons = null;
        } else {
            $text = $this->format($data);
            $buttons = null; // Default tidak ada tombol
            
            $runState = strtolower($data['runState']);
            $causeLower = strtolower(trim($data['lastDownCause'] ?? ''));

            if ($runState === 'online') {
                // Tombol untuk ONT Online
                $suffix = "{$data['olt']}_{$data['frame']}_{$data['slot']}_{$data['port']}_{$data['ontId']}";
                $buttons = [
                    [
                        ['text' => 'Cek WAN / Internet', 'callback_data' => "wan_{$suffix}"],
                        ['text' => 'Cek WLAN / Wi-Fi', 'callback_data' => "wlan_{$suffix}"]
                    ]
                ];
                $secondRow = [];
                if (str_starts_with($data['sn'], '48575443')) {
                    $secondRow[] = ['text' => 'Restart ONT', 'callback_data' => "reboot_{$suffix}"];
                }
                $secondRow[] = ['text' => 'Nonaktifkan', 'callback_data' => "deact_{$suffix}"];
                $buttons[] = $secondRow;
            } elseif (str_contains($causeLower, 'deactive')) {
                // Tombol untuk ONT Offline karena Deactive
                $suffix = "{$data['olt']}_{$data['frame']}_{$data['slot']}_{$data['port']}_{$data['ontId']}";
                $buttons = [['text' => 'Aktifkan ONT', 'callback_data' => "act_{$suffix}"]];
            }
            // Jika offline karena LOS/Dying-gasp, $buttons tetap null (tidak ada tombol)
        }

        if ($messageId) {
            $this->telegram->editMessageText($chatId, $messageId, $text, $buttons);
        } else {
            $this->send($chatId, $text, $buttons);
        }
    }

    private function mdEscape(?string $value): string
    {
        if ($value === null || trim($value) === '') return '-';
        return preg_replace('/([_*\[\]`])/', '\\\\$1', (string) $value) ?? (string) $value;
    }

    private function parseDateTimeWithZone(string $value): ?\Carbon\Carbon
    {
        $value = trim($value);
        if ($value === '' || $value === '-') return null;
        foreach (['d-m-Y H:i:sP', 'Y-m-d H:i:sP', 'Y-m-d\TH:i:sP', 'd-m-Y H:i:s', 'Y-m-d H:i:s'] as $fmt) {
            try { return \Carbon\Carbon::createFromFormat($fmt, $value); } catch (\Throwable) {}
        }
        return null;
    }

    private function humanizeRelative(?string $datetimeRaw): string
    {
        $when = $this->parseDateTimeWithZone($datetimeRaw ?? '');
        if (!$when) return '';
        if ($when->isFuture()) return '';
        try { return ' (' . $when->diffForHumans(now(), ['parts' => 2]) . ')'; } catch (\Throwable) { return ''; }
    }

    private function format(array $d): string
    {
        $online = strtolower($d['runState']) === 'online';
        $customerName = $this->mdEscape($d['customerName'] ?? null);
        $customerId   = $this->mdEscape($d['descriptionTarget'] ?? null);

        $statusText = $online ? 'ONLINE' : 'OFFLINE';

        $text = "======================================\n"
              . "     *ONT STATUS REPORT*\n"
              . "======================================\n\n"
              . "*CUSTOMER PROFILE*\n"
              . "ID Pelanggan  : `{$customerId}`\n"
              . "Nama          : `{$customerName}`\n"
              . "Lokasi F/S/P  : `{$d['frame']}/{$d['slot']}/{$d['port']}:{$d['ontId']}`\n"
              . "Serial Number : `{$d['sn']}`\n\n";

        $lastDownCause = $d['lastDownCause']  ?? '-';
        $lastDownTime  = $d['lastDownTime']   ?? '-';
        $lastUpTime    = $d['lastUpTime']     ?? '-';
        $downCauseDesc = $this->olt->describeDownCause($lastDownCause);
        $downAgo       = $this->humanizeRelative($d['lastDownTimeRaw'] ?? null);

        $isRecentDown = false;
        $when = $this->parseDateTimeWithZone($d['lastDownTimeRaw'] ?? '');
        if ($when && !$when->isFuture()) $isRecentDown = $when->gt(now()->subDays(7));

        // JIKA OFFLINE (LAPORAN SISTEM & INSTRUKSI DINAMIS)
        if (!$online) {
            $causeLower = strtolower(trim($lastDownCause));
            $tindakan = "";

            if (str_contains($causeLower, 'deactive') || str_contains($causeLower, 'deactivated')) {
                $tindakan = "1. ONT dinonaktifkan secara manual dari sistem OLT.\n"
                          . "2. Jika ini ONT baru atau selesai maintenance, silakan klik tombol [Aktifkan ONT] di bawah.";
            } elseif (str_contains($causeLower, 'dying-gasp') || str_contains($causeLower, 'dyinggasp') || str_contains($causeLower, 'dying gasp')) {
                $tindakan = "1. ONT mati mendadak (kehilangan daya listrik).\n"
                          . "2. Mohon pastikan adaptor listrik ONT terpasang dan stopkontak menyala.\n"
                          . "3. Jika listrik menyala tapi ONT tetap mati, kemungkinan adaptor rusak.\n"
                          . "4. Jika aman, harap buat tiket dispatch teknisi ke lokasi.";
            } elseif (str_contains($causeLower, 'los') || str_contains($causeLower, 'losi')) {
                $tindakan = "1. ONT kehilangan sinyal optik (Fiber terputus/tertekuk).\n"
                          . "2. Periksa kabel fiber (warna kuning/hijau) dari ODP ke ONT.\n"
                          . "3. Pastikan ujung konektor bersih dan tidak patah.\n"
                          . "4. Jika aman, harap buat tiket dispatch teknisi ke lokasi.";
            } elseif (str_contains($causeLower, 'wire-down') || str_contains($causeLower, 'lofi')) {
                $tindakan = "1. ONT kehilangan sinyal optik (Fiber terputus/tertekuk).\n"
                          . "2. Periksa kabel fiber (warna kuning/hijau) dari ODP ke ONT.\n"
                          . "3. Pastikan ujung konektor bersih dan tidak patah.\n"
                          . "4. Jika aman, harap buat tiket dispatch teknisi ke lokasi.";
            } else {
                $tindakan = "1. Perangkat ONT terdeteksi tidak merespons (Offline).\n"
                          . "2. Mohon periksa adaptor listrik dan kabel fiber di lokasi.\n"
                          . "3. Jika masih mati, harap buat tiket dispatch teknisi ke lokasi.";
            }

            $text .= "*[ SYSTEM STATUS ]*\n"
                   . "Status ONT    : *{$statusText}*\n"
                   . "Down Cause    : `{$lastDownCause}`" . ($downCauseDesc !== '-' ? " ({$downCauseDesc})" : '') . "\n"
                   . "Down Time     : `{$lastDownTime}`{$downAgo}\n"
                   . "Last Up Time  : `{$lastUpTime}`\n\n"
                   . "*[ TINDAKAN YANG DISARANKAN ]*\n"
                   . $tindakan . "\n"
                   . "======================================\n";
            return $text;
        }

        // JIKA ONLINE (LAPORAN LENGKAP)
        $rx = $d['rxPower']; $rxNum = (float) $rx;
        $isWeak = $rx !== '-80.00' && ($rxNum < -29 || $rxNum > -15);
        $signal = $rx === '-80.00' ? '(LOS)' : ($isWeak ? '(Sinyal Lemah)' : '(Normal)');
        $rxHint = $isWeak ? "\n                  _Peringatan: Redaman di luar standar (-15 s/d -29 dBm)_" : '';

        $hwBits = [];
        if (($d['ontDistance']   ?? '-') !== '-') $hwBits[] = "Jarak {$d['ontDistance']}m";
        if (($d['cpuOccupation'] ?? '-') !== '-') $hwBits[] = "CPU {$d['cpuOccupation']}%";
        if (($d['memOccupation'] ?? '-') !== '-') $hwBits[] = "Mem {$d['memOccupation']}%";
        $hwString = $hwBits !== [] ? implode(" | ", $hwBits) : '-';

        $text .= "*OPTICAL & HARDWARE*\n"
               . "RX Power      : {$rx} dBm {$signal}{$rxHint}\n"
               . "TX Power      : {$d['txPower']} dBm\n"
               . "OLT RX Power  : {$d['oltRxPower']} dBm\n"
               . "Suhu / Volt   : {$d['temp']} C / {$d['voltage']} V\n"
               . "Spesifikasi   : {$hwString}\n\n";

        $text .= "*SYSTEM & HISTORY*\n"
               . "Status ONT    : *{$statusText}*\n";

        $onlineDur = $d['ontOnlineDuration']   ?? '-';
        $systemUp  = $d['ontSystemUpDuration'] ?? '-';
        if ($onlineDur !== '-') $text .= "Online Durasi : {$onlineDur}\n";
        if ($systemUp  !== '-') $text .= "System Uptime : {$systemUp}\n";

        if ($isRecentDown) {
            $text .= "Down Cause    : `{$lastDownCause}`" . ($downCauseDesc !== '-' ? " ({$downCauseDesc})" : '') . "\n"
                   . "Down Time     : `{$lastDownTime}`{$downAgo}\n";
        } else {
            $text .= "Down Cause    : _Tidak ada gangguan > 7 hari_\n";
        }
        
        $text .= "Last Up Time  : `{$lastUpTime}`\n"
               . "======================================\n";
        
        return $text;
    }

    private function formatWanList(array $wanList): string
    {
        if (empty($wanList)) {
            return "Tidak ada data WAN.\n";
        }
        
        $text = "";
        foreach ($wanList as $wan) {
            $isConnected = stripos($wan['status'], 'Connected') !== false && stripos($wan['status'], 'Disconnected') === false;
            $statusText = $isConnected ? '[CONNECTED]' : '[DISCONNECTED]';
            
            $text .= "*[WAN {$wan['index']}] - {$wan['name']}*\n"
                   . "  • {$wan['serviceType']} | {$wan['connType']} ({$wan['accessType']})\n"
                   . "  • Status: {$statusText}\n";
            
            if ($isConnected && $wan['ip'] !== '-') {
                $text .= "  • IP: `{$wan['ip']}`\n"
                       . "  • Gateway: `{$wan['gateway']}`\n"
                       . "  • DNS: `{$wan['dns1']}`\n";
            }
            $text .= "\n";
        }
        return $text;
    }

    private function findPort(string $chatId, string $args): void
    {
        [$olt, $fsp] = array_pad(preg_split('/\s+/', trim($args)), 2, '');
        [$f, $s, $p] = array_pad(explode('/', $fsp), 3, '');
        if ($olt === '' || !ctype_digit($f) || !ctype_digit($s) || !ctype_digit($p)) {
            $this->send($chatId, 'Format salah. Contoh penggunaan: `/findport DUTA 0/5/13`');
            return;
        }

        $oltUpper = strtoupper($olt);
        $msg = $this->telegram->sendMessage($chatId, "Memproses...\nMenghitung statistik port OLT `{$oltUpper} {$f}/{$s}/{$p}`...");
        $messageId = $msg['message_id'] ?? null;

        $cacheTtl = (int) config('janeta.findport_cache_seconds', 120);
        $cacheKey = "findport:{$oltUpper}:{$f}:{$s}:{$p}";
        $d = $cacheTtl > 0 ? Cache::get($cacheKey) : null;
        
        if ($d === null) {
            $d = $this->olt->findPort($oltUpper, $f, $s, $p);
            if ($d !== null && $cacheTtl > 0) Cache::put($cacheKey, $d, now()->addSeconds($cacheTtl));
        }

        if (!$d) {
            $text = 'Gagal menarik data port OLT.';
        } else {
            $text = "*LAPORAN STATUS PORT GLOBAL*\n\n"
                . "• *Nama OLT:* OLT-{$d['olt']}\n"
                . "• *Koordinat Port:* `{$f}/{$s}/{$p}`\n"
                . "• *Total ONT Terpasang:* {$d['totalOnt']} unit\n\n"
                . "* ONLINE:* {$d['online']} Perangkat\n"
                . "* OFFLINE:* {$d['offline']} Perangkat\n"
                . "* LOS:* {$d['los']} Perangkat\n"
                . "* DYING GASP:* {$d['dyingGasp']} Perangkat";
        }

        if ($messageId) {
            $this->telegram->editMessageText($chatId, $messageId, $text);
        } else {
            $this->send($chatId, $text);
        }
    }

    private function callback(array $callback): void
    {
        $data      = $callback['data'] ?? '';
        $chatId    = (string) ($callback['message']['chat']['id'] ?? '');
        $fromId    = (string) ($callback['from']['id'] ?? '');
        $fromName  = $callback['from']['first_name'] ?? null;
        $messageId = $callback['message']['message_id'] ?? null;

        if (!$this->isWhitelisted($fromId)) {
            $this->telegram->answerCallback($callback['id'], 'Akses ditolak', true);
            return;
        }

        $parts = explode('_', $data);
        [, $olt, $f, $s, $p, $id] = array_pad($parts, 6, '');
        $this->telegram->answerCallback($callback['id'], 'Memproses...');
        $this->telegram->sendChatAction($chatId, 'typing');
        
        $coord = "{$f}/{$s}/{$p}:{$id}";

        if (str_starts_with($data, 'reboot_')) {
            $this->olt->restart($olt, $f, $s, $p, $id);
            $this->audit($fromId, $fromName, 'restart', $olt, $f, $s, $p, $id, ['ok' => true, 'message' => null]);
            $this->send($chatId, "RESTART ONT BERHASIL!\n• *Koordinat ONT:* `{$coord}`");
        } elseif (str_starts_with($data, 'wlan_')) {
            $w    = $this->olt->wlan($olt, $f, $s, $p, $id);
            $text = "*HASIL ANALISIS WLAN / WI-FI ONT*\n\n• *Koordinat Perangkat:* `{$coord}`\n\n";
            foreach ($w['ssids'] as $ssid) {
                $text .= "*[SSID INDEX {$ssid['index']}] - {$ssid['name']}*\n"
                    . "  • Administrative state: {$ssid['adminState']}\n"
                    . "  • Operational state: {$ssid['opState']}\n"
                    . "  • Current associate number: *{$ssid['deviceCount']} Devices*\n\n";
            }
            $this->send($chatId, $text . (empty($w['ssids']) ? '_Tidak ada SSID/Wi-Fi yang terdeteksi aktif._' : ''));
        } elseif (str_starts_with($data, 'wan_')) {
            $w = $this->olt->getWanInfo($olt, $f, $s, $p, $id);
            $text = "*HASIL ANALISIS WAN / INTERNET ONT*\n\n• *Koordinat Perangkat:* `{$coord}`\n\n";
            $text .= $this->formatWanList($w['wanList']);
            
            $wanWarn = '';
            foreach ($w['wanList'] as $wan) {
                if (stripos($wan['serviceType'], 'Internet') !== false && stripos($wan['status'], 'Disconnected') !== false) {
                    $wanWarn = "\nPERHATIAN: Terdapat layanan Internet (Index {$wan['index']}) yang berstatus Disconnected. Cek konfigurasi PPPoE, kredensial, atau status VLAN di sisi OLT/BNG.";
                    break;
                }
            }
            $text .= $wanWarn;
            $this->send($chatId, $text);
        } elseif (str_starts_with($data, 'cancel_')) {
            if ($messageId) {
                $this->telegram->deleteMessage($chatId, (int) $messageId);
            }
        } elseif (str_starts_with($data, 'deact_')) {
            $this->send($chatId, "Yakin nonaktifkan ONT `{$coord}` di OLT {$olt}?\nPelanggan akan putus layanan.",
                [['text' => 'Ya, nonaktifkan', 'callback_data' => "deactok_{$olt}_{$f}_{$s}_{$p}_{$id}"],
                 ['text' => 'Batal', 'callback_data' => 'cancel_x_x_x_x_x']]);
        } elseif (str_starts_with($data, 'deactok_') || str_starts_with($data, 'act_')) {
            $activate = str_starts_with($data, 'act_');
            $r = $this->olt->setOntState($olt, $f, $s, $p, $id, $activate);
            $this->audit($fromId, $fromName, $activate ? 'activate' : 'deactivate', $olt, $f, $s, $p, $id, $r);

            $this->send($chatId, ($r['ok'] ? 'BERHASIL: ' : 'GAGAL: ')
                . ($activate ? 'AKTIVASI' : 'DEAKTIVASI')
                . " ONT `{$coord}`\nOLT: {$olt}\n{$r['message']}");
            if ($messageId) {
                $this->telegram->editReplyMarkup($chatId, (int) $messageId, ['inline_keyboard' => []]);
            }
        }
    }

    private function changeState(string $chatId, string $id, bool $activate): void
    {
        $id = trim($id);
        if (!$id) {
            $this->send($chatId, 'Format salah. Contoh: `/' . ($activate ? 'active' : 'deactive') . ' 1JN202307000322`');
            return;
        }
        $d = $this->olt->byDescription($id);
        if (!$d) { $this->send($chatId, 'Data ONT tidak ditemukan di OLT.'); return; }

        $coord  = "{$d['frame']}/{$d['slot']}/{$d['port']}:{$d['ontId']}";
        $suffix = "{$d['olt']}_{$d['frame']}_{$d['slot']}_{$d['port']}_{$d['ontId']}";

        if ($activate) {
            $this->send($chatId, "Aktifkan ONT `{$coord}` ({$d['customerName']}) di OLT {$d['olt']}?",
                [['text' => 'Ya, aktifkan', 'callback_data' => "act_{$suffix}"],
                 ['text' => 'Batal', 'callback_data' => 'cancel_x_x_x_x_x']]);
            return;
        }
        $this->send($chatId, "Yakin nonaktifkan ONT `{$coord}`?\nPelanggan: {$d['customerName']}\nOLT: {$d['olt']}\n\nPelanggan akan putus layanan.",
            [['text' => 'Ya, nonaktifkan', 'callback_data' => "deactok_{$suffix}"],
             ['text' => 'Batal', 'callback_data' => 'cancel_x_x_x_x_x']]);
    }

    private function audit(string $chatId, ?string $name, string $action, string $olt, string $f, string $s, string $p, string $id, array $result): void
    {
        OntAuditLog::create([
            'chat_id'   => $chatId, 'user_name' => $name, 'action' => $action,
            'olt'       => $olt, 'frame' => $f, 'slot' => $s, 'port' => $p, 'ont_id' => $id,
            'ok'        => $result['ok'], 'message' => $result['message'] ?? null,
        ]);
    }
}