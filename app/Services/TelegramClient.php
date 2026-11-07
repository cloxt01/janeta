<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TelegramClient
{
    private function request(string $method): PendingRequest
    {
        $token = config('janeta.telegram.token');
        if (!$token) {
            throw new RuntimeException('TELEGRAM_BOT_TOKEN belum dikonfigurasi.');
        }

        return Http::baseUrl(rtrim(config('janeta.telegram.api_url'), '/') . '/bot' . $token)
            ->asJson()->acceptJson()->timeout(60);
    }

    public function call(string $method, array $parameters = []): array|bool
    {
        $response = $this->request($method)->post('/' . $method, $parameters);
        if (!$response->successful() || !$response->json('ok')) {
            throw new RuntimeException("Telegram {$method} gagal: " . $response->body());
        }
        $result = $response->json('result', []);
        if (! is_array($result) && ! is_bool($result)) {
            throw new RuntimeException("Telegram {$method} mengembalikan result dengan tipe tidak valid.");
        }

        return $result;
    }

    public function sendMessage(string|int $chatId, string $text, ?array $keyboard = null): array
    {
        $params = ['chat_id' => $chatId, 'text' => $text, 'parse_mode' => 'Markdown'];
        if ($keyboard !== null) $params['reply_markup'] = ['inline_keyboard' => [$keyboard]];
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
        $result = $this->call('getUpdates', ['offset' => $offset, 'timeout' => config('janeta.telegram.polling_timeout', 30), 'allowed_updates' => ['message', 'callback_query']]);
        if (! is_array($result)) {
            throw new RuntimeException('Telegram getUpdates mengembalikan result bukan array.');
        }

        return $result;
    }
}
