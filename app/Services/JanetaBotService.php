<?php

namespace App\Services;

use App\Models\OntAuditLog;
use App\Models\WhitelistEntry;
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
        $text = trim($message['text'] ?? '');
        $entry = WhitelistEntry::where('chat_id', $chatId)->first();
        if (!$entry) {
            if (str_starts_with($text, '/')) $this->send($chatId, "🚫 Akses Ditolak. Anda tidak memiliki izin.\nID Chat Anda: {$chatId}\nMinta Administrator/NOC untuk mendaftarkan ID Anda.");
            return;
        }
        if (!str_starts_with($text, '/')) return;
        [$command, $args] = array_pad(preg_split('/\s+/', $text, 2), 2, '');
        $command = strtolower(explode('@', $command)[0]);
        try {
            match ($command) {
                '/start', '/help' => $this->send($chatId, $this->help($message['from']['first_name'] ?? 'Rekan')),
                '/add' => $this->addUser($chatId, $args, $entry->is_admin),
                '/delete' => $this->deleteUser($chatId, $args, $entry->is_admin),
                '/list' => $this->listUsers($chatId),
                '/add_admin' => $this->addAdmin($chatId, $args, $entry->is_admin),
                '/delete_admin' => $this->deleteAdmin($chatId, $args, $entry->is_admin),
                '/list_admin' => $this->listAdmins($chatId),
                '/check' => $this->check($chatId, $args),
                '/checksn' => $this->checkSn($chatId, $args),
                '/findport' => $this->findPort($chatId, $args),
                '/findportont' => $this->findPortOnt($chatId, $args),
                '/deactive' => $this->changeState($chatId, $args, false),
                '/active' => $this->changeState($chatId, $args, true),
                default => null,
            };
        } catch (\Throwable $e) {
            Log::error('JANETA command failed', ['command' => $command, 'error' => $e->getMessage()]);
            $this->send($chatId, '❌ Terjadi gangguan internal komunikasi saat membaca data OLT.');
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
        if (!$isAdmin) $this->send($chatId, '❌ Perintah ini khusus untuk Administrator/NOC.');
        return $isAdmin;
    }

    private function addUser(string $chatId, string $args, bool $admin): void
    {
        if (!$this->adminOnly($chatId, $admin)) return;
        [$id, $name] = array_pad(preg_split('/\s+/', trim($args), 2), 2, '');
        if (!$id || !$name) { $this->send($chatId, '⚠️ Format salah. Contoh: `/add 612345678 Ahmad - Teknisi Lapangan`'); return; }
        WhitelistEntry::updateOrCreate(['chat_id' => $id], ['name' => $name]);
        $this->send($chatId, "✅ Berhasil menambahkan pengguna baru!\n👤 Nama: {$name}\n🔑 ID Chat: {$id}");
    }

    private function deleteUser(string $chatId, string $args, bool $admin): void
    {
        if (!$this->adminOnly($chatId, $admin)) return;
        $target = trim($args); $user = WhitelistEntry::where('chat_id', $target)->where('is_admin', false)->first();
        if (!$user) { $this->send($chatId, '⚠️ User ID tidak ditemukan dalam whitelist.'); return; }
        $user->delete(); $this->send($chatId, "🗑️ Berhasil menghapus akses untuk:\n👤 Nama: {$user->name}\n🔑 ID Chat: {$target}");
    }

    private function listUsers(string $chatId): void
    {
        $users = WhitelistEntry::whereNotNull('name')->get();
        $text = "📋 *DAFTAR TEKNISI & CS TER-IZINKAN JANGKAUNET*\n\n";
        $this->send($chatId, $users->isEmpty() ? $text . '_Belum ada user yang terdaftar._' : $text . $users->map(fn ($u) => "• `{$u->chat_id}` - {$u->name}\n")->implode(''));
    }

    private function addAdmin(string $chatId, string $args, bool $admin): void
    {
        if (!$this->adminOnly($chatId, $admin)) return;
        $id = trim($args);
        if (!$id || !is_numeric($id)) { $this->send($chatId, '⚠️ Format salah. Contoh: `/add_admin 8601271578`'); return; }
        $user = WhitelistEntry::updateOrCreate(['chat_id' => $id], ['is_admin' => true]);
        if (!$user->wasRecentlyCreated && $user->is_admin) { $this->send($chatId, 'ℹ️ ID Chat tersebut sudah berstatus sebagai Admin.'); return; }
        $this->send($chatId, "👑 Berhasil menambahkan Administrator baru!\n🔑 ID Chat: `{$id}`");
    }

    private function deleteAdmin(string $chatId, string $args, bool $admin): void
    {
        if (!$this->adminOnly($chatId, $admin)) return;
        $id = trim($args);
        if (!$id) { $this->send($chatId, '⚠️ Format salah. Contoh: `/delete_admin 8601271578`'); return; }
        if ($id === $chatId) { $this->send($chatId, '❌ Anda tidak bisa menghapus status admin Anda sendiri demi keamanan.'); return; }
        $user = WhitelistEntry::where('chat_id', $id)->where('is_admin', true)->first();
        if (!$user) { $this->send($chatId, '⚠️ ID Chat tersebut tidak ditemukan dalam daftar Admin.'); return; }
        $user->update(['is_admin' => false]); $this->send($chatId, "🗑️ Berhasil menghapus status Admin untuk ID Chat: `{$id}`");
    }

    private function listAdmins(string $chatId): void
    {
        $text = "👑 *DAFTAR ADMINISTRATOR NOC JANGKAUNET*\n\n";
        $this->send($chatId, $text . WhitelistEntry::where('is_admin', true)->get()->values()->map(fn ($u, $i) => ($i + 1) . ". `{$u->chat_id}`\n")->implode(''));
    }

    private function help(string $name): string
    {
        return "🚀 *JANETA - JangkauNet NetOps Assistant*\nHalo *{$name}*, berikut adalah daftar perintah Anda:\n\n*OPERASIONAL GPON OLT*\n• `/check [ID_Customer]` : Cek redaman, fisik ONT, dan status WAN Internet.\n• `/checksn [Serial_Number]` : Cek redaman & WAN berdasarkan Serial Number.\n• `/findport [NAMA_OLT] [Frame]/[Slot]/[Port]` : Statistik port OLT.\n• `/findportont [Frame]/[Slot]/[Port] [ONT_ID]` : Cek ONT spesifik.\n• `/deactive [ID_Customer]` : Nonaktifkan ONT.\n• `/active [ID_Customer]` : Aktifkan kembali ONT.\n\n*ADMINISTRASI*\n• `/list` dan `/list_admin` : Lihat whitelist.\n• `/add`, `/delete`, `/add_admin`, `/delete_admin` : Kelola akses.";
    }

    private function check(string $chatId, string $id): void
    {
        if (!$id) { $this->send($chatId, '⚠️ Format salah. Contoh penggunaan: /check 1JN202*********'); return; }
        $this->report($chatId, $this->olt->byDescription($id));
    }

    private function checkSn(string $chatId, string $sn): void
    {
        $sn = strtoupper(trim($sn));
        if (strlen($sn) !== 16) { $this->send($chatId, '⚠️ Serial Number tidak valid! Pastikan berjumlah tepat 16 karakter tanpa spasi.'); return; }
        $this->report($chatId, $this->olt->bySerial($sn));
    }

    private function report(string $chatId, ?array $data): void
    {
        if (!$data) { $this->send($chatId, '❌ Data ONT tidak ditemukan di OLT.'); return; }
        $suffix = "{$data['olt']}_{$data['frame']}_{$data['slot']}_{$data['port']}_{$data['ontId']}";

        if (strtolower($data['runState']) === 'online') {
            $buttons = [['text' => '📶 Cek WLAN / Wi-Fi', 'callback_data' => "wlan_{$suffix}"]];
            if (str_starts_with($data['sn'], '48575443')) {
                $buttons[] = ['text' => '🔄 Restart ONT', 'callback_data' => "reboot_{$suffix}"];
            }
            $buttons[] = ['text' => '⏸ Nonaktifkan', 'callback_data' => "deact_{$suffix}"];
        } else {
            $buttons = [['text' => '▶️ Aktifkan ONT', 'callback_data' => "act_{$suffix}"]];
        }

        $this->send($chatId, $this->format($data), $buttons);
    }

    private function format(array $d): string
    {
        $online = strtolower($d['runState']) === 'online';

        $customer = "=== *HASIL PENGECEKAN ONT JANGKAUNET* ===\n\n__*[ DATA PELANGGAN ]*__\n• ID Customer: `{$d['descriptionTarget']}`\n• Nama Pelanggan: _{$d['customerName']}_\n• Frame/Slot/Port:ID: `{$d['frame']}/{$d['slot']}/{$d['port']}:{$d['ontId']}`\n• Serial Number: `{$d['sn']}`\n";

        if (!$online) {
            return $customer
                . "• Status ONT: `" . strtoupper($d['runState']) . "`\n"
                . "• Last Down Cause: `" . ($d['lastDownCause'] ?? '-') . "`\n"
                . "• Last Up Time: `{$d['lastUpTime']}`\n\n"
                . "⚠️ *Keterangan:* " . $this->olt->ontOfflineReason($d['runState']) . "\n";
        }

        $rx = $d['rxPower'];
        $signal = $rx !== '-80.00' ? ((float) $rx < -29 || (float) $rx > -15 ? ' _(Sinyal Lemah)_' : ' *(Normal)*') : ' *(LOS)*';

        return $customer . "\n__*[ INFORMASI OPTIK / FISIK ]*__\n• RX Power (ONT): *{$rx} dBm*{$signal}\n• TX Power (ONT): *{$d['txPower']} dBm*\n• OLT Rx ONT Power: *{$d['oltRxPower']} dBm*\n• Temperature: _{$d['temp']} C_\n• Voltage: _{$d['voltage']} V_\n• Status ONT: `" . strtoupper($d['runState']) . "`\n• Last Up Time: `{$d['lastUpTime']}`\n\n__*[ STATUS WAN INTERNET ]*__\n• Connection Type: `{$d['wanConnType']}`\n• IPv4 Conn Status: *{$d['wanStatus']}*\n• IPv4 Access Type: _{$d['wanAccessType']}_\n• IPv4 Address: `{$d['wanIp']}`\n• Subnet Mask: `{$d['wanSubnet']}`\n• Default Gateway: `{$d['wanGateway']}`\n• Primary DNS: `{$d['wanDns1']}`\n• Secondary DNS: `{$d['wanDns2']}`";
    }

        private function findPort(string $chatId, string $args): void
    {
        [$olt, $fsp] = array_pad(preg_split('/\s+/', trim($args)), 2, '');
        [$f, $s, $p] = array_pad(explode('/', $fsp), 3, '');
        if ($olt === '' || !ctype_digit($f) || !ctype_digit($s) || !ctype_digit($p)) {
            $this->send($chatId, '⚠️ Format salah. Contoh penggunaan: `/findport DUTA 0/5/13`');
            return;
        }
        $d = $this->olt->findPort(strtoupper($olt), $f, $s, $p);
        if (!$d) { $this->send($chatId, '❌ Gagal menarik data port OLT.'); return; }
        $this->send($chatId, "*LAPORAN STATUS PORT GLOBAL*\n\n• *Nama OLT:* OLT-{$d['olt']}\n• *Koordinat Port:* `{$f}/{$s}/{$p}`\n• *Total ONT Terpasang:* {$d['totalOnt']} unit\n\n* ONLINE:* {$d['online']} Perangkat\n* OFFLINE:* {$d['offline']} Perangkat\n* LOS:* {$d['los']} Perangkat\n* DYING GASP:* {$d['dyingGasp']} Perangkat");
        
    
    }

    private function findPortOnt(string $chatId, string $args): void
    {
        [$fsp, $id] = array_pad(preg_split('/\s+/', trim($args)), 2, '');
        [$f, $s, $p] = array_pad(explode('/', $fsp), 3, '');
        if ($fsp === '' || !ctype_digit($f) || !ctype_digit($s) || !ctype_digit($p) || !ctype_digit($id)) {
            $this->send($chatId, '⚠️ Format salah. Contoh: `/findportont 0/5/13 5`');
            return;
        }
        $this->report($chatId, $this->olt->findPortOnt($f, $s, $p, $id));
    }


    private function callback(array $callback): void
    {
        $data = $callback['data'] ?? '';
        $chatId = (string) ($callback['message']['chat']['id'] ?? '');
        $fromId = (string) ($callback['from']['id'] ?? '');
        $fromName = $callback['from']['first_name'] ?? null;
        $messageId = $callback['message']['message_id'] ?? null;

        // Semua user whitelist boleh menekan tombol; selain itu ditolak
        if (!$this->isWhitelisted($fromId)) {
            $this->telegram->answerCallback($callback['id'], 'Akses ditolak', true);
            return;
        }

        $parts = explode('_', $data);
        [, $olt, $f, $s, $p, $id] = array_pad($parts, 6, '');
        $this->telegram->answerCallback($callback['id'], '⏳ Memproses...');
        $coord = "{$f}/{$s}/{$p}:{$id}";

        if (str_starts_with($data, 'reboot_')) {
            $this->olt->restart($olt, $f, $s, $p, $id);
            $this->audit($fromId, $fromName, 'restart', $olt, $f, $s, $p, $id, ['ok' => true, 'message' => null]);
            $this->send($chatId, "✅ *RESTART ONT BERHASIL!*\n• *Koordinat ONT:* `{$coord}`");
        } elseif (str_starts_with($data, 'wlan_')) {
            $w = $this->olt->wlan($olt, $f, $s, $p, $id);
            $text = "*📶 HASIL ANALISIS WLAN / WI-FI ONT*\n\n• *Koordinat Perangkat:* `{$coord}`\n\n";
            foreach ($w['ssids'] as $ssid) {
                $text .= "*[SSID INDEX {$ssid['index']}] - {$ssid['name']}*\n  • Administrative state: {$ssid['adminState']}\n  • Operational state: {$ssid['opState']}\n  • Current associate number: *{$ssid['deviceCount']} Devices*\n\n";
            }
            $this->send($chatId, $text . (empty($w['ssids']) ? '⚠️ _Tidak ada SSID/Wi-Fi yang terdeteksi aktif._' : ''));
        } elseif (str_starts_with($data, 'cancel_')) {
            if ($messageId) {
                $this->telegram->deleteMessage($chatId, (int) $messageId);
            }
        } elseif (str_starts_with($data, 'deact_')) {
            $this->send($chatId, "⚠️ Yakin nonaktifkan ONT `{$coord}` di OLT {$olt}?\nPelanggan akan putus layanan.",
                [['text' => '✅ Ya, nonaktifkan', 'callback_data' => "deactok_{$olt}_{$f}_{$s}_{$p}_{$id}"],
                 ['text' => '❌ Batal', 'callback_data' => 'cancel_x_x_x_x_x']]);
        } elseif (str_starts_with($data, 'deactok_') || str_starts_with($data, 'act_')) {
            $activate = str_starts_with($data, 'act_');
            $r = $this->olt->setOntState($olt, $f, $s, $p, $id, $activate);
            $this->audit($fromId, $fromName, $activate ? 'activate' : 'deactivate', $olt, $f, $s, $p, $id, $r);

            $this->send($chatId, ($r['ok'] ? '✅' : '❌') . ' ' . ($activate ? 'AKTIVASI' : 'DEAKTIVASI') . " ONT `{$coord}`\nOLT: {$olt}\n{$r['message']}");
            if ($messageId) {
                $this->telegram->editReplyMarkup($chatId, (int) $messageId, ['inline_keyboard' => []]);
            }
        }
    }

    private function changeState(string $chatId, string $id, bool $activate): void
    {
        $id = trim($id);
        if (!$id) {
            $this->send($chatId, '⚠️ Format salah. Contoh: `/' . ($activate ? 'active' : 'deactive') . ' 1JN202307000322`');
            return;
        }
        $d = $this->olt->byDescription($id);
        if (!$d) { $this->send($chatId, '❌ Data ONT tidak ditemukan di OLT.'); return; }

        $coord = "{$d['frame']}/{$d['slot']}/{$d['port']}:{$d['ontId']}";
        $suffix = "{$d['olt']}_{$d['frame']}_{$d['slot']}_{$d['port']}_{$d['ontId']}";

        if ($activate) {
            $this->send($chatId, "ℹ️ Aktifkan ONT `{$coord}` ({$d['customerName']}) di OLT {$d['olt']}?",
                [['text' => '✅ Ya, aktifkan', 'callback_data' => "act_{$suffix}"],
                 ['text' => '❌ Batal', 'callback_data' => 'cancel_x_x_x_x_x']]);
            return;
        }
        $this->send($chatId, "⚠️ Yakin nonaktifkan ONT `{$coord}`?\n👤 {$d['customerName']}\n🏢 OLT {$d['olt']}\n\nPelanggan akan putus layanan.",
            [['text' => '✅ Ya, nonaktifkan', 'callback_data' => "deactok_{$suffix}"],
             ['text' => '❌ Batal', 'callback_data' => 'cancel_x_x_x_x_x']]);
    }

    private function audit(string $chatId, ?string $name, string $action, string $olt, string $f, string $s, string $p, string $id, array $result): void
    {
        OntAuditLog::create([
            'chat_id' => $chatId, 'user_name' => $name, 'action' => $action,
            'olt' => $olt, 'frame' => $f, 'slot' => $s, 'port' => $p, 'ont_id' => $id,
            'ok' => $result['ok'], 'message' => $result['message'] ?? null,
        ]);
    }
}