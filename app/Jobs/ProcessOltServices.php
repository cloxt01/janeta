<?php

namespace App\Jobs;

use App\Events\OltServiceRequestUpdated;
use App\Events\OltActivityUpdated;
use App\Models\Olt;
use App\Models\OltServiceRequest;
use App\Services\OltService;
use App\Services\TelegramClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessOltServices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    public function __construct(
        public int $requestId
    ) {
        $this->onQueue('olt');
    }

    public function middleware(): array
    {
        $task = OltServiceRequest::find($this->requestId);

        return [
            (new \Illuminate\Queue\Middleware\WithoutOverlapping(
                'olt:'.($task?->olt_id ?? 'global')
            ))
                ->releaseAfter(10)
                ->expireAfter(360),
        ];
    }


    public function handle(OltService $service, TelegramClient $telegram): void
    {
        $task = OltServiceRequest::findOrFail($this->requestId);
        $startedAt = microtime(true);

        $task->update([
            'status' => 'running',
            'started_at' => now(),
            'finished_at' => null,
            'duration_ms' => null,
            'error' => null,
        ]);

        $params = $task->payload ?? [];
        $this->broadcast(
            $task->fresh(),
            'running',
            'Proses OLT sedang dijalankan.'
        );

        $this->broadcastActivity(
            $task->fresh(),
            'running',
            'Proses OLT sedang dijalankan.'
        );

        try {
            $result = match ($task->operation) {
                'ont.lookup' => $this->lookupOnt($service, $params),
                'ont.description' => $service->apiByDescription($params['id']),
                'ont.version' => $service->apiVersion($params['id']),
                'ont.serial' => $service->bySerial($params['sn']),
                'ont.find' => $service->findPortOnt(
                    $params['frame'],
                    $params['slot'],
                    $params['port'],
                    $params['ont_id']
                ),
                'ont.restart' => $service->restart(
                    $params['olt'],
                    $params['frame'],
                    $params['slot'],
                    $params['port'],
                    $params['ont_id']
                ),
                'ont.activate' => $service->setOntState(
                    $params['olt'],
                    $params['frame'],
                    $params['slot'],
                    $params['port'],
                    $params['ont_id'],
                    true
                ),
                'ont.deactivate' => $service->setOntState(
                    $params['olt'],
                    $params['frame'],
                    $params['slot'],
                    $params['port'],
                    $params['ont_id'],
                    false
                ),
                'ont.wlan' => $service->wlan(
                    $params['olt'],
                    $params['frame'],
                    $params['slot'],
                    $params['port'],
                    $params['ont_id']
                ),
                'ont.port_scan' => $service->findPort(
                    $params['olt'],
                    $params['frame'],
                    $params['slot'],
                    $params['port']
                ),
                default => throw new \InvalidArgumentException(
                    "Unknown OLT operation: {$task->operation}"
                ),
            };

            $duration = (int) round(
                (microtime(true) - $startedAt) * 1000
            );

            $task->update([
                'status' => 'completed',
                'result' => $result,
                'duration_ms' => $duration,
                'finished_at' => now(),
            ]);

            $task = $task->fresh();

            $this->broadcast(
                $task,
                'completed',
                'Proses OLT berhasil.',
                $result
            );

            $this->broadcastActivity(
                $task,
                'completed',
                'Proses OLT berhasil.',
                $result
            );

            // KIRIM HASIL DAN INLINE KEYBOARD KE TELEGRAM
            if (!empty($params['chat_id'])) {
                Log::info('JANETA TELEGRAM FORMAT TRACE', [
                    'task_id' => $task->id,
                    'operation' => '[' . $task->operation . ']',
                    'operation_length' => strlen($task->operation),
                    'result_type' => gettype($result),
                    'result_keys' => is_array($result)
                        ? array_keys($result)
                        : null,
                ]);

                $formattedMessage = $this->formatTelegramResult(
                    $task->operation,
                    $result
                );

                $buttons = is_array($result)
                    ? $this->telegramActionButtons(
                        $task->operation,
                        $result
                    )
                    : null;

                $telegram->sendMessage(
                    $params['chat_id'],
                    $formattedMessage,
                    $buttons,
                    'HTML'
                );
            }

        } catch (Throwable $e) {
            $duration = (int) round(
                (microtime(true) - $startedAt) * 1000
            );

            $task->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
                'finished_at' => now(),
            ]);

            $task = $task->fresh();

            $this->broadcast(
                $task,
                'failed',
                'Proses OLT gagal.',
                null,
                $e->getMessage()
            );

            $this->broadcastActivity(
                $task,
                'failed',
                'Proses OLT gagal.',
                null,
                $e->getMessage()
            );

            if (!empty($params['chat_id'])) {
                $telegram->sendMessage(
                    $params['chat_id'],
                    "❌ Gagal memproses perintah: {$e->getMessage()}"
                );
            }

            throw $e;
        }
    }


        private function telegramEscape(mixed $value): string
    {
        return htmlspecialchars(
            (string) ($value ?? '-'),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }

    private function lookupOnt(OltService $service, array $params): ?array
    {
        $id = trim((string) ($params['id'] ?? ''));
        if ($id === '') {
            throw new \InvalidArgumentException('ID customer wajib diisi.');
        }
        $result = $service->apiVersion($id);
        if ($result && !empty($params['wifi'])) {
            $result['wlan'] = $service->wlan($result['olt'], $result['frame'], $result['slot'], $result['port'], $result['ontId']);
        }
        return $result;
    }


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

            $message .= "{$icon} <b>ONT ID {$ontId}</b>\n"
                . "SN: <code>{$sn}</code>\n"
                . "Pelanggan: {$description}\n"
                . "Status: <b>{$this->telegramEscape($state)}</b>\n\n";
        }

        return $message;
    }



    private function formatTelegramResult(string $operation, mixed $result): string
    {
        if (!$result || !is_array($result)) {
            return "❌ Data tidak ditemukan atau perangkat offline.";
        }

        $operation = strtolower(trim($operation));

        if ($operation === 'ont.port_scan') {
            Log::info('JANETA PORT SCAN RAW RESULT', [
                'result' => $result,
            ]);
            return $this->formatPortScanResult($result);
        }

        if (in_array($operation, [
            'ont.version',
            'ont.lookup',
        ], true)) {
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
                    . "⚠️ <b>Keterangan:</b> ONT tidak online.";
            }

            $rxRaw = (string) ($result['rxPower'] ?? '-');
            $rx = $this->telegramEscape($rxRaw);

            $signal = ($rxRaw !== '-80.00' && $rxRaw !== '-')
                ? ((float) $rxRaw < -29 || (float) $rxRaw > -15
                    ? ' <i>(Sinyal Lemah)</i>'
                    : ' <b>(Normal)</b>')
                : ' <b>(LOS)</b>';

            return $customer
                . "\n<b>[ INFORMASI OPTIK / FISIK ]</b>\n"
                . "RX Power (ONT): <b>{$rx} dBm</b>{$signal}\n"
                . "TX Power (ONT): <b>{$this->telegramEscape($result['txPower'] ?? '-')} dBm</b>\n"
                . "OLT Rx ONT Power: <b>{$this->telegramEscape($result['oltRxPower'] ?? '-')} dBm</b>\n"
                . "Temperature: <i>{$this->telegramEscape($result['temp'] ?? '-')} C</i>\n"
                . "Voltage: <i>{$this->telegramEscape($result['voltage'] ?? '-')} V</i>\n"
                . "Status ONT: <code>{$runState}</code>\n"
                . "Last Up Time: <code>{$lastUpTime}</code>\n\n"
                . "<b>[ STATUS WAN INTERNET ]</b>\n"
                . "Connection Type: <code>{$this->telegramEscape($result['wanConnType'] ?? '-')}</code>\n"
                . "IPv4 Conn Status: <b>{$this->telegramEscape($result['wanStatus'] ?? '-')}</b>\n"
                . "IPv4 Access Type: <i>{$this->telegramEscape($result['wanAccessType'] ?? '-')}</i>\n"
                . "IPv4 Address: <code>{$this->telegramEscape($result['wanIp'] ?? '-')}</code>\n"
                . "Subnet Mask: <code>{$this->telegramEscape($result['wanSubnet'] ?? '-')}</code>\n"
                . "Default Gateway: <code>{$this->telegramEscape($result['wanGateway'] ?? '-')}</code>\n"
                . "Primary DNS: <code>{$this->telegramEscape($result['wanDns1'] ?? '-')}</code>\n"
                . "Secondary DNS: <code>{$this->telegramEscape($result['wanDns2'] ?? '-')}</code>";
        }

        return "✅ Operasi <code>{$this->telegramEscape($operation)}</code> berhasil diselesaikan.";
    }



    private function telegramActionButtons(string $operation, array $result): ?array
    {
        // Tombol aksi hanya untuk hasil pengecekan ONT.

        if (!in_array($operation, ['ont.version', 'ont.lookup'], true)) {
            return null;
        }

        // Jangan tampilkan aksi jika ONT offline.
        if (strtolower($result['runState'] ?? '') !== 'online') {
            return null;
        }

        $olt = strtoupper((string) ($result['olt'] ?? ''));
        $frame = (string) ($result['frame'] ?? '');
        $slot = (string) ($result['slot'] ?? '');
        $port = (string) ($result['port'] ?? '');
        $ontId = (string) ($result['ontId'] ?? '');

        // Pastikan seluruh koordinat tersedia.
        if (
            $olt === '' ||
            !ctype_digit($frame) ||
            !ctype_digit($slot) ||
            !ctype_digit($port) ||
            !ctype_digit($ontId)
        ) {
            Log::warning('JANETA action buttons skipped: incomplete ONT coordinates', [
                'operation' => $operation,
                'olt' => $olt,
                'frame' => $frame,
                'slot' => $slot,
                'port' => $port,
                'ont_id' => $ontId,
            ]);

            return null;
        }

        $coordinate = "{$olt}_{$frame}_{$slot}_{$port}_{$ontId}";

        return [
            [
                [
                    'text' => '🔄 Restart ONT',
                    'callback_data' => "reboot_{$coordinate}",
                ],
            ],
            [
                [
                    'text' => '🔴 Nonaktifkan ONT',
                    'callback_data' => "deact_{$coordinate}",
                ],
            ],
            [
                [
                    'text' => '📶 Cek WLAN / Wi-Fi',
                    'callback_data' => "wlan_{$coordinate}",
                ],
            ],
        ];
    }


    private function broadcast(?OltServiceRequest $task, string $status, ?string $message = null, mixed $result = null, ?string $error = null): void 
    {

        event(new OltServiceRequestUpdated(
            request_id: $task->uuid,
            user_id: (int) $task->user_id,
            operation: $task->operation,
            status: $status,
            message: $message,
            result: $result,
            error: $error,
        ));
    }

    private function broadcastActivity(
        ?OltServiceRequest $task,
        string $status,
        ?string $message = null,
        mixed $result = null,
        ?string $error = null
    ): void {
        if (!$task) return;

        event(new OltActivityUpdated(
            \App\Support\OltActivityPresenter::make($task, $status, $message, $error)
        ));
    }
}