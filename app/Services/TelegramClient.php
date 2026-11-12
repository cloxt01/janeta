<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TelegramClient
{
    private function request(string $method, int $timeout = 60): PendingRequest
    {
        $token = config('janeta.telegram.token');
        if (!$token) {
            throw new RuntimeException('TELEGRAM_BOT_TOKEN belum dikonfigurasi.');
        }

        return Http::baseUrl(rtrim(config('janeta.telegram.api_url'), '/') . '/bot' . $token)
            ->asJson()->acceptJson()
            ->timeout($timeout)
            ->connectTimeout(10);
    }

    public function call(string $method, array $parameters = [], int $timeout = 60): array|bool
    {
        $attempts = 3;

        for ($i = 1; $i <= $attempts; $i++) {
            try {
                $response = $this->request($method, $timeout)->post('/' . $method, $parameters);
            } catch (ConnectionException $e) {
                if ($i === $attempts) {
                    throw new RuntimeException("Telegram {$method} gagal (koneksi): " . $e->getMessage(), 0, $e);
                }

                Log::warning("Telegram {$method} koneksi timeout/gagal (percobaan {$i}/{$attempts}), mencoba ulang...", [
                    'error' => $e->getMessage()
                ]);

                // Perbaikan syntax usleep dengan perkalian waktu tunggu
                usleep(500_000 * $i);
                continue;
            }

            if ($response->serverError() || $response->status() === 429) {
                if ($i < $attempts) {
                    $wait = $response->status() === 429
                        ? (int) $response->json('parameters.retry_after', 1)
                        : $i;
                    
                    Log::warning("Telegram {$method} merespons status {$response->status()}, menunggu {$wait} detik...", [
                        'attempt' => $i
                    ]);

                    sleep(max(1, $wait));
                    continue;
                }
            }

            if (!$response->successful() || !$response->json('ok')) {
                throw new RuntimeException("Telegram {$method} gagal: " . $response->body());
            }

            $result = $response->json('result', []);
            if (!is_array($result) && !is_bool($result)) {
                throw new RuntimeException("Telegram {$method} mengembalikan result dengan tipe tidak valid.");
            }

            return $result;
        }

        throw new RuntimeException("Telegram {$method} gagal setelah {$attempts} percobaan.");
    }



    public function sendMessage(
        string|int $chatId,
        string $text,
        ?array $keyboard = null,
        string $parseMode = 'Markdown'
    ): array {
        $params = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode,
        ];

        if ($keyboard !== null && !empty($keyboard)) {

            // Kompatibilitas dengan format array tombol datar.
            // Jika elemen pertama memiliki key "text",
            // berarti keyboard belum dibungkus menjadi baris.
            if (
                isset($keyboard[0]['text']) &&
                !isset($keyboard[0][0])
            ) {
                $keyboard = [$keyboard];
            }

            // Validasi struktur inline keyboard.
            foreach ($keyboard as $row) {
                if (!is_array($row)) {
                    throw new RuntimeException(
                        'Format inline keyboard tidak valid: baris harus berupa array.'
                    );
                }

                foreach ($row as $button) {
                    if (
                        !is_array($button) ||
                        empty($button['text']) ||
                        (
                            empty($button['callback_data']) &&
                            empty($button['url']) &&
                            empty($button['web_app'])
                        )
                    ) {
                        throw new RuntimeException(
                            'Format InlineKeyboardButton tidak valid.'
                        );
                    }
                }
            }

            $params['reply_markup'] = [
                'inline_keyboard' => $keyboard,
            ];
        }

        return $this->call('sendMessage', $params);
    }





    public function answerCallback(string $id, string $text = '', bool $alert = false): void
    {
        try {
            $this->call('answerCallbackQuery', ['callback_query_id' => $id, 'text' => $text, 'show_alert' => $alert]);
        } catch (RuntimeException $exception) {
            if (! str_contains($exception->getMessage(), 'query is too old') &&
                ! str_contains($exception->getMessage(), 'query ID is invalid')) {
                throw $exception;
            }

            Log::warning('Telegram callback sudah kedaluwarsa atau tidak valid.', [
                'callback_query_id' => $id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function deleteMessage(string|int $chatId, int $messageId): void
    {
        $this->call('deleteMessage', ['chat_id' => $chatId, 'message_id' => $messageId]);
    }

    public function editReplyMarkup(string|int $chatId, int $messageId, ?array $markup): void
    {
        $this->call('editMessageReplyMarkup', ['chat_id' => $chatId, 'message_id' => $messageId, 'reply_markup' => $markup]);
    }

    public function getUpdates(int $offset = 0): array
    {
        $poll = (int) config('janeta.telegram.polling_timeout', 30);

        $result = $this->call('getUpdates', [
            'offset' => $offset,
            'timeout' => $poll,
            'allowed_updates' => ['message', 'callback_query'],
        ], $poll + 15); 

        if (!is_array($result)) {
            throw new RuntimeException('Telegram getUpdates mengembalikan result bukan array.');
        }

        return $result;
    }
}