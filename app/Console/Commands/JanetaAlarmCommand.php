<?php

namespace App\Console\Commands;

use App\Services\OltService;
use App\Services\TelegramClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class JanetaAlarmCommand extends Command
{
    protected $signature = 'janeta:alarms';

    protected $description = 'Monitor mass OLT LOS events';

    public function handle(
        OltService $olt,
        TelegramClient $telegram
    ): int {
        $chatId = config('janeta.alarm_chat_id');

        if (!$chatId) {
            $this->error('JANETA_ALARM_CHAT_ID belum dikonfigurasi.');

            return self::FAILURE;
        }

        $threshold = max(
            1,
            (int) config('janeta.alarm_threshold', 8)
        );

        $interval = max(
            10,
            (int) config('janeta.alarm_interval', 60)
        );

        $cacheKey = 'janeta:los_alert_state';

        $this->info('JANETA LOS Monitor started.');
        $this->info("Threshold: {$threshold} ONTs");
        $this->info("Interval scan: {$interval} seconds");

        while (true) {
            try {
                // Scan seluruh port GPON yang dikonfigurasi.
                $alarms = $olt->scanActiveAlarms();

                $this->info(
                    'Total alarm ditemukan: ' . count($alarms)
                );

                /*
                 * Hanya hitung:
                 * - Control flag: active
                 * - Run state: offline
                 * - Type: LOS
                 *
                 * DYING-GASP dan OTHER otomatis diabaikan.
                 */
                $currentAlarms = array_filter(
                    $alarms,
                    function ($alarm) {
                        return
                            strtolower($alarm['controlFlag'] ?? '') === 'active'
                            && strtolower($alarm['runState'] ?? '') === 'offline'
                            && ($alarm['type'] ?? '') === 'LOS';
                    }
                );

                $this->info(
                    'Total ONT LOS: ' . count($currentAlarms)
                );

                // Kelompokkan berdasarkan OLT dan port GPON.
                $grouped = [];

                foreach ($currentAlarms as $alarm) {
                    $key = implode(':', [
                        $alarm['olt'],
                        $alarm['frame'],
                        $alarm['slot'],
                        $alarm['port'],
                    ]);

                    if (!isset($grouped[$key])) {
                        $grouped[$key] = [
                            'olt' => $alarm['olt'],
                            'frame' => $alarm['frame'],
                            'slot' => $alarm['slot'],
                            'port' => $alarm['port'],
                            'onts' => [],
                        ];
                    }

                    // Hindari ONT yang sama dihitung dua kali.
                    $grouped[$key]['onts'][$alarm['ontId']] = $alarm;
                }

                $this->info(
                    'Total port terdampak: ' . count($grouped)
                );

                // Ambil status notifikasi sebelumnya.
                $previous = Cache::get($cacheKey, []);

                if (!is_array($previous)) {
                    $previous = [];
                }

                // Status port yang memenuhi threshold saat ini.
                $current = [];

                foreach ($grouped as $key => $group) {
                    $los = $group['onts'];

                    $losCount = count($los);

                    $this->line(
                        "{$key} => Total LOS: {$losCount}"
                    );

                    // Belum memenuhi threshold.
                    if ($losCount < $threshold) {
                        continue;
                    }

                    // Tandai port sedang mengalami mass LOS.
                    $current[$key] = true;

                    // Jangan kirim notifikasi berulang.
                    if (isset($previous[$key])) {
                        $this->line(
                            "Notifikasi dilewati: {$key} "
                            . "(LOS masih berlangsung)"
                        );

                        continue;
                    }

                    $portCoordinate = implode('/', [
                        $group['frame'],
                        $group['slot'],
                        $group['port'],
                    ]);

                    // Susun pesan Telegram.
                    $message =
                        "🚨 *\GANGGUAN MASSAL (GAMAS)*\n\n"
                        ."-----------------------------\n"
                        . "• OLT: `{$group['olt']}`\n"
                        . "• Port GPON: `{$portCoordinate}`\n"
                        . "• Total ONT LOS: *{$losCount}*\n"
                        . "• Threshold: {$threshold} ONT\n"
                        . "• Interval scan: {$interval} detik\n\n";

                    $message .= "*🔴 DAFTAR ONT LOS*\n";

                    foreach ($los as $ont) {
                        $ontId = $ont['ontId'];
                        $cause = $ont['lastDownCause'] ?? '-';

                        $message .=
                            "• ONT ID: `{$ontId}`"
                            . " — {$cause}\n";
                    }

                    $message .=
                        "\n⚠️ *Gangguan LOS terdeteksi "
                        . "pada port GPON.*";

                    // Kirim notifikasi Telegram.
                    $telegram->sendMessage(
                        $chatId,
                        $message
                    );

                    $this->warn(
                        "MASS LOS: {$group['olt']} "
                        . "{$portCoordinate} "
                        . "({$losCount} ONTs)"
                    );
                }

                /*
                 * Reset status port yang tidak lagi memenuhi
                 * threshold agar notifikasi baru bisa dikirim
                 * jika terjadi gangguan berikutnya.
                 */
                Cache::put(
                    $cacheKey,
                    $current,
                    now()->addDays(7)
                );

                $this->info('Monitoring selesai.');

            } catch (\Throwable $e) {
                report($e);

                $this->error(
                    'Monitoring error: ' . $e->getMessage()
                );
            }

            $this->info(
                "Menunggu {$interval} detik sebelum scan berikutnya..."
            );

            sleep($interval);
        }

        return self::SUCCESS;
    }
}
