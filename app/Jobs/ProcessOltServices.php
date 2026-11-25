<?php

namespace App\Jobs;

use App\Events\OltActivityUpdated;
use App\Events\OltServiceRequestUpdated;
use App\Models\OltServiceRequest;
use App\Models\OntAuditLog;
use App\Services\OltService;
use App\Services\TelegramClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessOltServices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * tries = 0 + retryUntil(): job yang "dilepas" oleh WithoutOverlapping
     * tidak dihitung gagal. Kegagalan OLT TIDAK di-retry karena exception
     * tidak dilempar ulang (lihat catch di handle()).
     */
    public int $tries = 0;
    public int $timeout = 120;
    public bool $failOnTimeout = true;

    /** Operasi baca: aman dijalankan paralel, tanpa lock. */
    private const READ_ONLY = [
        'ont.version',
        'ont.lookup',
        'ont.serial',
        'ont.find',
        'ont.port_scan',
        'ont.wlan',
        'ont.description',
    ];

    public function __construct(public int $requestId)
    {
        $this->onQueue('olt');
    }

    public function retryUntil(): \DateTimeInterface
    {
        return now()->addMinutes(5);
    }

    public function middleware(): array
    {
        $task = OltServiceRequest::find($this->requestId);

        if ($task && in_array($task->operation, self::READ_ONLY, true)) {
            return [];
        }

        // Operasi tulis (restart/aktif/nonaktif): satu per OLT, bukan satu global.
        $payload = (array) ($task?->payload ?? []);
        $oltKey = strtolower((string) ($payload['olt'] ?? 'global'));

        return [
            (new WithoutOverlapping("olt:{$oltKey}"))
                ->releaseAfter(3)
                ->expireAfter(180),
        ];
    }

    private function sanitizeUtf8(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($v) => $this->sanitizeUtf8($v), $value);
        }

        if (is_string($value)) {
            // Byte invalid diganti '?'
            $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');

            // Buang ANSI escape dan control char (kecuali \n \r \t)
            $value = preg_replace('/\x1B\[[0-9;]*[A-Za-z]/', '', $value) ?? $value;
            $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value) ?? $value;

            return $value;
        }

        return $value;
    }
    public function handle(OltService $service, TelegramClient $telegram): void
    {
        $task = OltServiceRequest::findOrFail($this->requestId);
        $startedAt = microtime(true);
        $params = (array) ($task->payload ?? []);

        $waited = $task->created_at ? (int) $task->created_at->diffInSeconds(now()) : null;

        $task->update([
            'status' => 'running',
            'started_at' => now(),
            'finished_at' => null,
            'duration_ms' => null,
            'error' => null,
        ]);

        $this->notify($task, 'running', 'Proses OLT sedang dijalankan.');

        try {
            $result = match ($task->operation) {
                'ont.lookup' => $this->lookupOnt($service, $params),
                'ont.description' => $service->apiByDescription($params['id']),
                'ont.version' => $service->apiVersion($params['id']),
                'ont.serial' => $service->bySerial($params['sn']),
                'ont.find' => $service->findPortOnt(
                    $params['frame'], $params['slot'], $params['port'], $params['ont_id']
                ),
                'ont.restart' => $service->restart(
                    $params['olt'], $params['frame'], $params['slot'], $params['port'], $params['ont_id']
                ),
                'ont.activate' => $service->setOntState(
                    $params['olt'], $params['frame'], $params['slot'], $params['port'], $params['ont_id'], true
                ),
                'ont.deactivate' => $service->setOntState(
                    $params['olt'], $params['frame'], $params['slot'], $params['port'], $params['ont_id'], false
                ),
                'ont.wlan' => $service->wlan(
                    $params['olt'], $params['frame'], $params['slot'], $params['port'], $params['ont_id']
                ),
                'ont.port_scan' => $service->findPort(
                    $params['olt'], $params['frame'], $params['slot'], $params['port']
                ),
                default => throw new \InvalidArgumentException(
                    "Unknown OLT operation: {$task->operation}"
                ),
            };

            $result = $this->sanitizeUtf8($result);

            $duration = (int) round((microtime(true) - $startedAt) * 1000);

            $task->update([
                'status' => 'completed',
                'result' => $result,
                'duration_ms' => $duration,
                'finished_at' => now(),
            ]);

            Log::info('JANETA OLT job done', [
                'id' => $task->id,
                'op' => $task->operation,
                'waited_s' => $waited,   // lama di antrean
                'run_ms' => $duration,   // lama eksekusi di OLT
            ]);

            $this->notify($task, 'completed', 'Proses OLT berhasil.', $result);
            $this->audit($task->operation, $params, $result);
        } catch (Throwable $e) {
            $duration = (int) round((microtime(true) - $startedAt) * 1000);

            $errorMessage = $this->sanitizeUtf8($e->getMessage());
            $task->update([
                'status' => 'failed',
                'error' => $errorMessage,
                'duration_ms' => $duration,
                'finished_at' => now(),
            ]);

            Log::error('JANETA OLT job failed', [
                'id' => $task->id,
                'op' => $task->operation,
                'waited_s' => $waited,
                'run_ms' => $duration,
                'error' => $e->getMessage(),
            ]);

            $this->notify($task, 'failed', 'Proses OLT gagal.', null, $e->getMessage());

            if (!empty($params['chat_id'])) {
                $this->safely(fn () => $telegram->sendMessage(
                    $params['chat_id'],
                    '❌ Gagal memproses perintah: ' . $this->telegramEscape($e->getMessage()),
                    null,
                    'HTML'
                ));
            }

            // Sengaja TIDAK throw: tidak ada retry otomatis ke OLT
            // dan teknisi hanya menerima satu pesan gagal.
            return;
        }

        // Kirim ke Telegram di luar try utama: kegagalan Telegram
        // tidak boleh mengubah status task yang sudah completed.
        if (!empty($params['chat_id'])) {
            $this->safely(function () use ($telegram, $task, $result, $params) {
                $telegram->sendMessage(
                    $params['chat_id'],
                    $this->formatTelegramResult($task->operation, $result),
                    is_array($result) ? $this->telegramActionButtons($task->operation, $result) : null,
                    'HTML'
                );
            });
        }
    }

    /* ------------------------------------------------------------------ */
    /* Helper                                                              */
    /* ------------------------------------------------------------------ */

    private function safely(callable $fn): void
    {
        try {
            $fn();
        } catch (Throwable $e) {
            Log::warning('JANETA side-effect gagal (diabaikan)', ['error' => $e->getMessage()]);
        }
    }

    private function notify(
        OltServiceRequest $task,
        string $status,
        ?string $message = null,
        mixed $result = null,
        ?string $error = null
    ): void {
        $this->safely(fn () => event(new OltServiceRequestUpdated(
            request_id: $task->uuid,
            user_id: (int) $task->user_id,
            operation: $task->operation,
            status: $status,
            message: $message,
            result: $result,
            error: $error,
        )));

        $this->safely(fn () => event(new OltActivityUpdated(
            \App\Support\OltActivityPresenter::make($task, $status, $message, $error)
        )));
    }

    private function audit(string $operation, array $params, mixed $result): void
    {
        if (!in_array($operation, ['ont.activate', 'ont.deactivate'], true) || !is_array($result)) {
            return;
        }

        $this->safely(fn () => OntAuditLog::create([
            'chat_id' => (string) ($params['tg_user_id'] ?? $params['chat_id'] ?? ''),
            'user_name' => $params['tg_name'] ?? null,
            'action' => $operation === 'ont.activate' ? 'activate' : 'deactivate',
            'olt' => $params['olt'],
            'frame' => $params['frame'],
            'slot' => $params['slot'],
            'port' => $params['port'],
            'ont_id' => $params['ont_id'],
            'ok' => (bool) ($result['ok'] ?? false),
            'message' => $result['message'] ?? null,
        ]));
    }

    private function telegramEscape(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? '-'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function lookupOnt(OltService $service, array $params): ?array
    {
        $id = trim((string) ($params['id'] ?? ''));
        if ($id === '') {
            throw new \InvalidArgumentException('ID customer wajib diisi.');
        }
        $result = $service->apiVersion($id);
        if ($result && !empty($params['wifi'])) {
            $result['wlan'] = $service->wlan(
                $result['olt'], $result['frame'], $result['slot'], $result['port'], $result['ontId']
            );
        }
        return $result;
    }

    /* ------------------------------------------------------------------ */
    /* Format pesan Telegram                                               */
    /* ------------------------------------------------------------------ */

    private function formatPortScanResult(array $result): string
    {
        $onts = $result['onts'] ?? [];

        $olt = $this->telegramEscape($result['olt'] ?? '-');
        $frame = $this->telegramEscape($result['frame'] ?? '-');
        $slot = $this->telegramEscape($result['slot'] ?? '-');
        $port = $this->telegramEscape($result['port'] ?? '-');

        $total = (int) ($result['totalOnt'] ?? count($onts));
        $online = (int) ($result['online'] ?? 0);
        $offline = (int) ($result['offline'] ?? 0);

        $message = "📡 <b>HASIL SCAN PORT GPON</b>\n\n"
            . "OLT: <code>{$olt}</code>\n"
            . "Port: <code>{$frame}/{$slot}/{$port}</code>\n\n"
            . "Total ONT: <b>{$total}</b>\n"
            . "🟢 Online: <b>{$online}</b>\n"
            . "🔴 Offline: <b>{$offline}</b>\n\n"
            . "<b>DAFTAR ONT</b>\n";

        if (empty($onts)) {
            return $message . "\n⚠️ Tidak ada data ONT yang berhasil diparsing.";
        }

        foreach ($onts as $ont) {
            $ontId = $this->telegramEscape($ont['ontId'] ?? '-');
            $sn = $this->telegramEscape($ont['sn'] ?? '-');
            $description = $this->telegramEscape($ont['description'] ?? '-');
            $state = strtoupper((string) ($ont['runState'] ?? 'UNKNOWN'));
            $icon = $state === 'ONLINE' ? '🟢' : '🔴';
            $stateEsc = $this->telegramEscape($state);

            $message .= "{$icon} <b>ONT ID {$ontId}</b>\n"
                . "SN: <code>{$sn}</code>\n"
                . "Pelanggan: {$description}\n"
                . "Status: <b>{$stateEsc}</b>\n\n";
        }

        return $message;
    }

    private function formatWlanResult(array $w, array $result = []): string
    {
        $header = "<b>📶 HASIL ANALISIS WLAN / WI-FI ONT</b>\n\n";

        if (!($w['success'] ?? false)) {
            $label = match ($w['status'] ?? 'error') {
                'unsupported' => '⚠️ FITUR WLAN TIDAK DIDUKUNG',
                'rejected' => '❌ PERINTAH DITOLAK OLT',
                'no_data' => '⚠️ DATA WLAN TIDAK TERSEDIA',
                default => '❌ PEMERIKSAAN WLAN GAGAL',
            };

            $text = $header . "<b>{$label}</b>\n\n"
                . $this->telegramEscape($w['message'] ?? 'Terjadi kesalahan saat memeriksa WLAN.');

            if (!empty($w['original_message'])) {
                $original = str_replace(["\n", "\r"], ' ', (string) $w['original_message']);
                $text .= "\n\n<b>Detail Respons OLT:</b>\n<code>" . $this->telegramEscape($original) . '</code>';
            }

            return $text;
        }

        $text = $header . "✅ <b>DATA WLAN BERHASIL DIPEROLEH</b>\n\n";

        foreach (($w['ssids'] ?? []) as $ssid) {
            $text .= '<b>[SSID INDEX ' . $this->telegramEscape($ssid['index'] ?? '-') . '] - '
                . $this->telegramEscape($ssid['name'] ?? '-') . "</b>\n"
                . '  • Administrative state: ' . $this->telegramEscape($ssid['adminState'] ?? '-') . "\n"
                . '  • Operational state: ' . $this->telegramEscape($ssid['opState'] ?? '-') . "\n"
                . '  • Maximum associate number: ' . $this->telegramEscape($ssid['deviceMax'] ?? '-') . "\n"
                . '  • Current associate number: <b>' . $this->telegramEscape($ssid['deviceCount'] ?? '-') . " Devices</b>\n\n";
        }

        return $text;
    }

    private function formatTelegramResult(string $operation, mixed $result): string
    {
        if (!$result || !is_array($result)) {
            return '❌ Data tidak ditemukan atau perangkat offline.';
        }

        $operation = strtolower(trim($operation));

        if ($operation === 'ont.port_scan') {
            return $this->formatPortScanResult($result);
        }

        if ($operation === 'ont.wlan') {
            return $this->formatWlanResult($result);
        }

        if (in_array($operation, ['ont.restart', 'ont.activate', 'ont.deactivate'], true)) {
            $ok = (bool) ($result['ok'] ?? $result['success'] ?? false);
            $label = match ($operation) {
                'ont.restart' => 'RESTART',
                'ont.activate' => 'AKTIVASI',
                default => 'DEAKTIVASI',
            };

            return ($ok ? '✅' : '❌') . " <b>{$label} ONT</b>\n"
                . $this->telegramEscape($result['message'] ?? ($ok ? 'Berhasil.' : 'Gagal.'));
        }

        if (in_array($operation, ['ont.version', 'ont.lookup', 'ont.serial'], true)) {
            $online = strtolower((string) ($result['runState'] ?? '')) === 'online';

            $customerId = $this->telegramEscape($result['customerId'] ?? '-');
            $customerName = $this->telegramEscape($result['customerName'] ?? '-');
            $frame = $this->telegramEscape($result['frame'] ?? '0');
            $slot = $this->telegramEscape($result['slot'] ?? '0');
            $port = $this->telegramEscape($result['port'] ?? '0');
            $ontId = $this->telegramEscape($result['ontId'] ?? '0');
            $sn = $this->telegramEscape($result['sn'] ?? '-');
            $runState = $this->telegramEscape(strtoupper($result['runState'] ?? '-'));
            $lastUpTime = $this->telegramEscape($result['lastUpTime'] ?? '-');
            $lastDownCause = $this->telegramEscape($result['lastDownCause'] ?? '-');

            $customer = "=== <b>HASIL PENGECEKAN ONT JANGKAUNET</b> ===\n\n"
                . "<b>[ DATA PELANGGAN ]</b>\n"
                . "ID Customer: <code>{$customerId}</code>\n"
                . "Nama Pelanggan: <i>{$customerName}</i>\n"
                . "Frame/Slot/Port:ID: <code>{$frame}/{$slot}/{$port}:{$ontId}</code>\n"
                . "Serial Number: <code>{$sn}</code>\n";

            if (!$online) {
                return $customer
                    . "Status ONT: <code>{$runState}</code>\n"
                    . "Last Down Cause: <code>{$lastDownCause}</code>\n"
                    . "Last Up Time: <code>{$lastUpTime}</code>\n\n"
                    . '⚠️ <b>Keterangan:</b> ONT tidak online.';
            }

            $rxRaw = (string) ($result['rxPower'] ?? '-');
            $rx = $this->telegramEscape($rxRaw);

            $signal = ($rxRaw !== '-80.00' && $rxRaw !== '-')
                ? ((float) $rxRaw < -29 || (float) $rxRaw > -15
                    ? ' <i>(Sinyal Lemah)</i>'
                    : ' <b>(Normal)</b>')
                : ' <b>(LOS)</b>';

            $e = fn (string $k) => $this->telegramEscape($result[$k] ?? '-');

            return $customer
                . "\n<b>[ INFORMASI OPTIK / FISIK ]</b>\n"
                . "RX Power (ONT): <b>{$rx} dBm</b>{$signal}\n"
                . "TX Power (ONT): <b>{$e('txPower')} dBm</b>\n"
                . "OLT Rx ONT Power: <b>{$e('oltRxPower')} dBm</b>\n"
                . "Temperature: <i>{$e('temp')} C</i>\n"
                . "Voltage: <i>{$e('voltage')} V</i>\n"
                . "Status ONT: <code>{$runState}</code>\n"
                . "Last Up Time: <code>{$lastUpTime}</code>\n\n"
                . "<b>[ STATUS WAN INTERNET ]</b>\n"
                . "Connection Type: <code>{$e('wanConnType')}</code>\n"
                . "IPv4 Conn Status: <b>{$e('wanStatus')}</b>\n"
                . "IPv4 Access Type: <i>{$e('wanAccessType')}</i>\n"
                . "IPv4 Address: <code>{$e('wanIp')}</code>\n"
                . "Subnet Mask: <code>{$e('wanSubnet')}</code>\n"
                . "Default Gateway: <code>{$e('wanGateway')}</code>\n"
                . "Primary DNS: <code>{$e('wanDns1')}</code>\n"
                . "Secondary DNS: <code>{$e('wanDns2')}</code>";
        }

        return "✅ Operasi <code>{$this->telegramEscape($operation)}</code> berhasil diselesaikan.";
    }

    private function telegramActionButtons(string $operation, array $result): ?array
    {
        if (!in_array($operation, ['ont.version', 'ont.lookup', 'ont.serial'], true)) {
            return null;
        }

        $olt = strtoupper((string) ($result['olt'] ?? ''));
        $frame = (string) ($result['frame'] ?? '');
        $slot = (string) ($result['slot'] ?? '');
        $port = (string) ($result['port'] ?? '');
        $ontId = (string) ($result['ontId'] ?? '');

        if (
            $olt === '' ||
            !ctype_digit($frame) || !ctype_digit($slot) ||
            !ctype_digit($port) || !ctype_digit($ontId)
        ) {
            Log::warning('JANETA action buttons skipped: incomplete ONT coordinates', [
                'operation' => $operation, 'olt' => $olt,
                'frame' => $frame, 'slot' => $slot, 'port' => $port, 'ont_id' => $ontId,
            ]);
            return null;
        }

        $coordinate = "{$olt}_{$frame}_{$slot}_{$port}_{$ontId}";

        $controlFlag = strtolower(trim((string) ($result['controlFlag'] ?? '')));
        $runState = strtolower(trim((string) ($result['runState'] ?? '')));

        if (in_array($controlFlag, ['deactive', 'deactivated'], true)) {
            return [[['text' => '🟢 Aktifkan ONT', 'callback_data' => "act_{$coordinate}"]]];
        }

        if ($runState !== 'online') {
            return null;
        }

        return [
            [['text' => '🔄 Restart ONT', 'callback_data' => "reboot_{$coordinate}"]],
            [['text' => '🔴 Nonaktifkan ONT', 'callback_data' => "deact_{$coordinate}"]],
            [['text' => '📶 Cek WLAN / Wi-Fi', 'callback_data' => "wlan_{$coordinate}"]],
        ];
    }
}
