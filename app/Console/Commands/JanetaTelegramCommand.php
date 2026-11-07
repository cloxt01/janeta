<?php

namespace App\Console\Commands;

use App\Services\JanetaBotService;
use App\Services\TelegramClient;
use Illuminate\Console\Command;

class JanetaTelegramCommand extends Command
{
    protected $signature = 'janeta:telegram';
    protected $description = 'Run JANETA Telegram long polling worker';

    public function handle(TelegramClient $telegram, JanetaBotService $bot): int
    {
        $offset = 0;
        $this->info('JANETA Telegram worker aktif.');
        while (true) {
            foreach ($telegram->getUpdates($offset) as $update) {
                $offset = ($update['update_id'] ?? $offset) + 1;
                $bot->handle($update);
            }
        }
    }
}
