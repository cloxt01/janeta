<?php

namespace App\Console\Commands;

use App\Services\OltService;
use App\Services\TelegramClient;
use Illuminate\Console\Command;

class JanetaAlarmCommand extends Command
{
    protected $signature = 'janeta:alarms';
    protected $description = 'Monitor active OLT alarms and notify the configured NOC chat';

    public function handle(OltService $olt, TelegramClient $telegram): int
    {
        $chatId = config('janeta.alarm_chat_id');
        if (!$chatId) {
            $this->error('JANETA_ALARM_CHAT_ID belum dikonfigurasi.');
            return self::FAILURE;
        }
        $sent = [];
        while (true) {
            foreach ($olt->scanActiveAlarms() as $alarm) {
                $key = implode(':', [$alarm['olt'], $alarm['frame'], $alarm['slot'], $alarm['port'], $alarm['type']]);
                if (isset($sent[$key])) continue;
                $sent[$key] = true;
                $telegram->sendMessage($chatId, "🚨 *ALARM OLT TERDETEKSI*\n• OLT: `{$alarm['olt']}`\n• Port: `{$alarm['frame']}/{$alarm['slot']}/{$alarm['port']}`\n• Jenis: *{$alarm['type']}*");
            }
            sleep(config('janeta.alarm_interval', 60));
        }
    }
}
