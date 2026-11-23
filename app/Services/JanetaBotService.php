<?php

namespace App\Services;

use App\Jobs\ProcessOltServices;
use App\Models\OltServiceRequest;
use App\Models\OntAuditLog;
use App\Models\WhitelistEntry;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class JanetaBotService
{
    private array $actor = [];

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
        $this->actor = [
            'tg_user_id' => (string) ($message['from']['id'] ?? ''),
            'tg_name' => trim(($message['from']['first_name'] ?? '') . ' ' . ($message['from']['last_name'] ?? ''))
                ?: ($message['from']['username'] ?? 'Telegram User'),
        ];
        $text = trim($message['text'] ?? '');
        $entry = WhitelistEntry::where('chat_id', $chatId)->first();

        if (!$entry) {
            if (str_starts_with($text, '/')) {
                $this->send($chatId, "🚫 Akses Ditolak. Anda tidak memiliki izin.\nID Chat Anda: {$chatId}\nMinta Administrator/NOC untuk mendaftarkan ID Anda.");
            }
            return;
        }

        if (!str_starts_with($text, '/')) return;

        [$command, $args] = array_pad(preg_split('/\s+/', $text, 2), 2, '');
        $command = strtolower(explode('@', $command)[0]);

        try {
            match ($command) {
                '/start', '/help' => $this->send($chatId, $this->help($message['from']['first_name'] ?? 'Rekan')),
                '/add' => $this->addUser($chatId, $args, $entry->isAdmin()),
                '/delete' => $this->deleteUser($chatId, $args, $entry->isAdmin()),
                '/list' => $this->listUsers($chatId),
                '/add_admin' => $this->addAdmin($chatId, $args, $entry->isAdmin()),
                '/delete_admin' => $this->deleteAdmin($chatId, $args, $entry->isAdmin()),
                '/list_admin' => $this->listAdmins($chatId),
                '/check' => $this->dispatchAsync($chatId, 'ont.version', ['id' => trim($args)], 'Memeriksa detail ONT...'),
                '/checksn' => $this->dispatchAsync($chatId, 'ont.serial', ['sn' => strtoupper(trim($args))], 'Memeriksa ONT via Serial Number...'),
                '/findport' => $this->handleFindPortAsync($chatId, $args),
                '/findportont' => $this->handleFindPortOntAsync($chatId, $args),
                '/deactive' => $this->dispatchAsync($chatId, 'ont.version', $this->parseOntArgs($args), 'Mencari ONT, gunakan tombol Nonaktifkan...'),
                '/active'   => $this->dispatchAsync($chatId, 'ont.version', $this->parseOntArgs($args), 'Mencari ONT, gunakan tombol Aktifkan...'),
                default => null,
            };
        } catch (\Throwable $e) {
            Log::error('JANETA command failed', ['command' => $command, 'error' => $e->getMessage()]);
            $this->send($chatId, '❌ Terjadi gangguan internal saat memproses perintah.');
        }
    }

    private function hasRequiredParams(string $operation, array $payload): bool
    {
        $required = match ($operation) {
            'ont.version' => ['id'],
            'ont.serial' => ['sn'],
            'ont.port_scan' => ['olt', 'frame', 'slot', 'port'],
            'ont.find' => ['frame', 'slot', 'port', 'ont_id'],
            'ont.restart',
            'ont.activate',
            'ont.deactivate',
            'ont.wlan' => ['olt', 'frame', 'slot', 'port', 'ont_id'],
            default => [],
        };

        foreach ($required as $key) {
            if (
                !array_key_exists($key, $payload) ||
                trim((string) $payload[$key]) === ''
            ) {
                return false;
            }
        }

        return true;
    }



    private function dispatchAsync(
        string $chatId,
        string $operation,
        array $payload,
        string $loadingText
    ): void {
        if (!$this->hasRequiredParams($operation, $payload)) {
            $this->send($chatId, '⚠️ Parameter perintah tidak lengkap.');
            return;
        }

        $payload['chat_id'] = $chatId;
        $payload += $this->actor;

        $request = OltServiceRequest::create([
            'uuid' => (string) Str::uuid(),
            'operation' => $operation,
            'payload' => $payload,
            'status' => 'pending',
        ]);

        // Masuk antrean secepat mungkin.
        ProcessOltServices::dispatch($request->id)->onQueue('olt');

        try {
            event(new \App\Events\OltActivityUpdated(
                \App\Support\OltActivityPresenter::make($request, 'pending', 'Menunggu antrean.')
            ));
        } catch (\Throwable $e) {
            Log::warning('JANETA broadcast pending gagal', ['error' => $e->getMessage()]);
        }

        $this->send($chatId, "⏳ {$loadingText}");
    }


    private function parseOntArgs(string $args): array
    {
        $id = trim($args);
        // Jika argumen berupa ID customer, lakukan lookup cepat untuk dapatkan F/S/P/ONT-ID jika diperlukan worker
        return ['id' => $id];
    }


    private function handleFindPortAsync(string $chatId, string $args): void
    {
        $parts = preg_split('/\s+/', trim($args), 2);

        if (count($parts) !== 2) {
            $this->send(
                $chatId,
                '⚠️ Format salah. Contoh penggunaan: `/findport DUTA 0/2/1`'
            );
            return;
        }

        [$olt, $fsp] = $parts;

        if (!preg_match('/^(\d+)\/(\d+)\/(\d+)$/', $fsp, $matches)) {
            $this->send(
                $chatId,
                '⚠️ Format port tidak valid. Contoh: `/findport DUTA 0/2/1`'
            );
            return;
        }

        [$f, $s, $p] = [$matches[1], $matches[2], $matches[3]];

        $this->dispatchAsync(
            $chatId,
            'ont.port_scan',
            [
                'olt' => strtoupper($olt),
                'frame' => $f,
                'slot' => $s,
                'port' => $p,
            ],
            "Memindai port OLT {$olt} {$fsp}..."
        );
    }



    private function handleFindPortOntAsync(string $chatId, string $args): void
    {
        $parts = preg_split('/\s+/', trim($args), 2);

        if (count($parts) !== 2) {
            $this->send(
                $chatId,
                '⚠️ Format salah. Contoh: `/findportont 0/5/13 5`'
            );
            return;
        }

        [$fsp, $id] = $parts;

        if (
            !preg_match('/^(\d+)\/(\d+)\/(\d+)$/', $fsp, $matches) ||
            !ctype_digit($id)
        ) {
            $this->send(
                $chatId,
                '⚠️ Format tidak valid. Contoh: `/findportont 0/5/13 5`'
            );
            return;
        }

        [$f, $s, $p] = [
            $matches[1],
            $matches[2],
            $matches[3],
        ];

        $this->dispatchAsync(
            $chatId,
            'ont.find',
            [
                'frame' => $f,
                'slot' => $s,
                'port' => $p,
                'ont_id' => $id,
            ],
            "Mencari ONT ID {$id} di port {$fsp}..."
        );
    }


    private function send(string $chatId, string $text, ?array $buttons = null): void
    {
        $this->telegram->sendMessage($chatId, $text, $buttons);
    }

    private function isAdmin(string $chatId): bool
    {
        return WhitelistEntry::where('chat_id', $chatId)->whereIn('role', WhitelistEntry::ADMIN_ROLES)->exists();
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
        $target = trim($args);
        $user = WhitelistEntry::where('chat_id', $target)->whereNotIn('role', WhitelistEntry::ADMIN_ROLES)->first();
        if (!$user) { $this->send($chatId, '⚠️ User ID tidak ditemukan dalam whitelist.'); return; }
        $user->delete();
        $this->send($chatId, "🗑️ Berhasil menghapus akses untuk:\n👤 Nama: {$user->name}\n🔑 ID Chat: {$target}");
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
        $existing = WhitelistEntry::where('chat_id', $id)->first();
        if ($existing?->isAdmin()) { $this->send($chatId, 'ℹ️ ID Chat tersebut sudah berstatus sebagai Admin.'); return; }
        WhitelistEntry::updateOrCreate(['chat_id' => $id], ['role' => 'admin']);
        $this->send($chatId, "👑 Berhasil menambahkan Administrator baru!\n🔑 ID Chat: `{$id}`");
    }

    private function deleteAdmin(string $chatId, string $args, bool $admin): void
    {
        if (!$this->adminOnly($chatId, $admin)) return;
        $id = trim($args);
        if (!$id) { $this->send($chatId, '⚠️ Format salah. Contoh: `/delete_admin 8601271578`'); return; }
        if ($id === $chatId) { $this->send($chatId, '❌ Anda tidak bisa menghapus status admin Anda sendiri demi keamanan.'); return; }
        $user = WhitelistEntry::where('chat_id', $id)->whereIn('role', WhitelistEntry::ADMIN_ROLES)->first();
        if (!$user) { $this->send($chatId, '⚠️ ID Chat tersebut tidak ditemukan dalam daftar Admin.'); return; }
        $user->update(['role' => 'user']);
        $this->send($chatId, "🗑️ Berhasil menghapus status Admin untuk ID Chat: `{$id}`");
    }

    private function listAdmins(string $chatId): void
    {
        $text = "👑 *DAFTAR ADMINISTRATOR NOC JANGKAUNET*\n\n";
        $this->send($chatId, $text . WhitelistEntry::whereIn('role', WhitelistEntry::ADMIN_ROLES)->get()->values()->map(fn ($u, $i) => ($i + 1) . ". `{$u->chat_id}`\n")->implode(''));
    }

    private function help(string $name): string
    {
        return "🚀 *JANETA - JangkauNet NetOps Assistant*\nHalo *{$name}*, berikut adalah daftar perintah Anda:\n\n"
            . "*OPERASIONAL GPON OLT*\n"
            . "`/check [ID_Customer]` : Cek redaman, fisik ONT, dan status WAN Internet.\n"
            . "`/checksn [Serial_Number]` : Cek redaman & WAN berdasarkan Serial Number.\n"
            . "`/findport [NAMA_OLT] [Frame]/[Slot]/[Port]` : Statistik port OLT.\n"
            . "`/findportont [Frame]/[Slot]/[Port] [ONT_ID]` : Cek ONT spesifik.\n"
            . "`/deactive [ID_Customer]` : Nonaktifkan ONT.\n"
            . "`/active [ID_Customer]` : Aktifkan kembali ONT.\n\n"
            . "*ADMINISTRASI*\n"
            . "`/list` dan `/list_admin` : Lihat whitelist.\n"
            . "`/add`, `/delete`, `/add_admin`, `/delete_admin` : Kelola akses.";
    }

    private function callback(array $callback): void
    {
        $data = $callback['data'] ?? '';
        $chatId = (string) ($callback['message']['chat']['id'] ?? '');
        $fromId = (string) ($callback['from']['id'] ?? '');
        $fromName = $callback['from']['first_name'] ?? null;
        $this->actor = ['tg_user_id' => $fromId, 'tg_name' => $fromName ?? 'Telegram User'];
        $messageId = $callback['message']['message_id'] ?? null;

        if (!$this->isWhitelisted($fromId)) {
            $this->telegram->answerCallback($callback['id'], 'Akses ditolak', true);
            return;
        }

        $parts = explode('_', $data);
        [, $olt, $f, $s, $p, $id] = array_pad($parts, 6, '');
        $this->telegram->answerCallback($callback['id'], '⏳ Memproses di background...');
        $coord = "{$f}/{$s}/{$p}:{$id}";

        $ontPayload = [
            'olt' => $olt,
            'frame' => $f,
            'slot' => $s,
            'port' => $p,
            'ont_id' => $id,
        ];

        if (str_starts_with($data, 'reboot_')) {
            $this->dispatchAsync($chatId, 'ont.restart', $ontPayload, "Mengirim perintah restart ONT {$coord}...");

        } elseif (str_starts_with($data, 'wlan_')) {
            $this->dispatchAsync($chatId, 'ont.wlan', $ontPayload, "Memeriksa WLAN ONT {$coord}...");

        } elseif (str_starts_with($data, 'cancel_')) {
            if ($messageId) {
                $this->telegram->deleteMessage($chatId, (int) $messageId);
            }

        } elseif (str_starts_with($data, 'deact_')) {
            $this->send(
                $chatId,
                "⚠️ *KONFIRMASI DEAKTIVASI ONT*\n\n"
                . "• OLT: `{$olt}`\n"
                . "• Koordinat: `{$coord}`\n\n"
                . "Pelanggan akan kehilangan layanan internet.\n\n"
                . "Apakah Anda yakin ingin melanjutkan?",
                [[
                    ['text' => '✅ Ya, nonaktifkan', 'callback_data' => "deactok_{$olt}_{$f}_{$s}_{$p}_{$id}"],
                    ['text' => '❌ Batal', 'callback_data' => 'cancel_x_x_x_x_x'],
                ]]
            );

        } elseif (str_starts_with($data, 'deactok_') || str_starts_with($data, 'act_')) {
            $activate = str_starts_with($data, 'act_');

            // Audit log kini dicatat di ProcessOltServices::audit()
            $this->dispatchAsync(
                $chatId,
                $activate ? 'ont.activate' : 'ont.deactivate',
                $ontPayload,
                ($activate ? 'Mengaktifkan' : 'Menonaktifkan') . " ONT {$coord}..."
            );

            // Hapus tombol agar tidak diklik dua kali.
            if ($messageId) {
                try {
                    $this->telegram->editReplyMarkup($chatId, (int) $messageId, ['inline_keyboard' => []]);
                } catch (\Throwable $e) {
                    Log::warning('JANETA editReplyMarkup gagal', ['error' => $e->getMessage()]);
                }
            }
        }
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
