<?php

namespace App\Services\Router;

use App\Models\MikrotikActivityLog;
use App\Models\MikrotikRouter;
use Illuminate\Support\Carbon;
use RouterOS\Client as RouterOSClient;

class MikrotikLogCollector
{
    /**
     * Membuat koneksi RouterOS API.
     */
    protected function connect(MikrotikRouter $device): RouterOSClient
    {
        if (! $device->is_active) {
            throw new \RuntimeException(
                "Router {$device->name} sedang dinonaktifkan."
            );
        }

        if (! $device->api_enabled) {
            throw new \RuntimeException(
                "RouterOS API dinonaktifkan pada {$device->name}."
            );
        }

        $username = filled($device->api_username)
            ? $device->api_username
            : $device->username;

        $password = filled($device->api_password)
            ? $device->api_password
            : ($device->password ?? '');

        if (! $device->host || ! $username || $password === '') {
            throw new \RuntimeException(
                'Konfigurasi koneksi RouterOS belum lengkap.'
            );
        }

        return new RouterOSClient([
            'host' => trim($device->host),
            'user' => trim($username),
            'pass' => $password,
            'port' => (int) (
            $device->api_port ?: ($device->api_ssl ? 8729 : 8728)
            ),
            'ssl' => (bool) $device->api_ssl,
            'ssl_options' => [
                'ciphers' => 'ADH:ALL@SECLEVEL=0',
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
            'timeout' => 10,
            'socket_timeout' => 15,
            'attempts' => 1,
            'delay' => 0,
        ]);
    }

    protected function isJanetaUser(?string $username, string $apiUsername): bool
    {
        if (! filled($username) || ! filled($apiUsername)) {
            return false;
        }

        return strcasecmp(trim($username), trim($apiUsername)) === 0;
    }
    /**
     * Mendapatkan username API JANETA.
     */
    protected function getApiUsername(MikrotikRouter $device): string
    {
        return trim(
            filled($device->api_username)
                ? $device->api_username
                : $device->username
        );
    }

    /**
     * Mengambil informasi waktu RouterOS.
     */
    protected function getRouterClock(RouterOSClient $client): array
    {
        $response = $client
            ->query('/system/clock/print')
            ->read();

        $clock = $response[0] ?? [];

        $date = $this->normalizeRouterDate(
            (string) ($clock['date'] ?? '')
        );

        $time = (string) ($clock['time'] ?? '');

        $timezone = (string) (
            $clock['time-zone-name'] ?? ''
        );

        if (! $date || ! preg_match('/^\d{1,2}:\d{2}:\d{2}$/', $time)) {
            throw new \RuntimeException(
                'Gagal membaca tanggal atau waktu dari MikroTik.'
            );
        }

        return [
            'date' => $date,
            'time' => $time,
            'timezone' => $timezone ?: null,
        ];
    }

    /**
     * Mengubah tanggal RouterOS menjadi Y-m-d.
     */
    protected function normalizeRouterDate(string $date): ?string
    {
        $date = trim($date);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return checkdate(
                (int) substr($date, 5, 2),
                (int) substr($date, 8, 2),
                (int) substr($date, 0, 4)
            ) ? $date : null;
        }

        if (! preg_match(
            '/^([a-z]{3})\/(\d{1,2})\/(\d{4})$/i',
            $date,
            $matches
        )) {
            return null;
        }

        $months = [
            'jan' => '01',
            'feb' => '02',
            'mar' => '03',
            'apr' => '04',
            'may' => '05',
            'jun' => '06',
            'jul' => '07',
            'aug' => '08',
            'sep' => '09',
            'oct' => '10',
            'nov' => '11',
            'dec' => '12',
        ];

        $month = strtolower($matches[1]);

        if (! isset($months[$month])) {
            return null;
        }

        $year = (int) $matches[3];
        $day = (int) $matches[2];
        $monthNumber = (int) $months[$month];

        if (! checkdate($monthNumber, $day, $year)) {
            return null;
        }

        return sprintf(
            '%04d-%02d-%02d',
            $year,
            $monthNumber,
            $day
        );
    }

    /**
     * Memisahkan tanggal dan jam dari waktu aktivitas RouterOS.
     */
    protected function resolveRouterTimestamp(
        string $rawTime,
        array $clock
    ): array {
        $rawTime = trim($rawTime);

        $date = $clock['date'];

        // Format tanggal ISO + jam.
        if (preg_match(
            '/^(\d{4}-\d{2}-\d{2})\s+(\d{1,2}:\d{2}:\d{2})$/',
            $rawTime,
            $matches
        )) {
            return [
                'date' => $this->normalizeRouterDate($matches[1]),
                'time' => $matches[2],
            ];
        }

        // Format RouterOS: nov/25/2026 09:15:22.
        if (preg_match(
            '/^([a-z]{3}\/\d{1,2}\/\d{4})\s+(\d{1,2}:\d{2}:\d{2})$/i',
            $rawTime,
            $matches
        )) {
            return [
                'date' => $this->normalizeRouterDate($matches[1]),
                'time' => $matches[2],
            ];
        }

        // Format RouterOS tanpa tahun: nov/25 09:15:22.
        if (preg_match(
            '/^([a-z]{3}\/\d{1,2})\s+(\d{1,2}:\d{2}:\d{2})$/i',
            $rawTime,
            $matches
        )) {
            $candidate = $this->normalizeRouterDate(
                $matches[1] . '/' . substr($clock['date'], 0, 4)
            );

            if ($candidate) {
                $candidateDate = Carbon::parse($candidate);
                $routerDate = Carbon::parse($clock['date']);

                // Jika tanggal berada jauh di masa depan,
                // kemungkinan aktivitas berasal dari tahun sebelumnya.
                if ($candidateDate->gt($routerDate->copy()->addDays(1))) {
                    $candidate = $candidateDate
                        ->subYear()
                        ->format('Y-m-d');
                }
            }

            return [
                'date' => $candidate,
                'time' => $matches[2],
            ];
        }

        // Format jam saja: 09:15:22.
        if (preg_match(
            '/^\d{1,2}:\d{2}:\d{2}$/',
            $rawTime
        )) {
            $eventTime = Carbon::createFromFormat(
                'H:i:s',
                $rawTime,
                'UTC'
            );

            $currentTime = Carbon::createFromFormat(
                'H:i:s',
                $clock['time'],
                'UTC'
            );

            if ($eventTime && $currentTime && $eventTime->gt($currentTime)) {
                $date = Carbon::parse($date)
                    ->subDay()
                    ->format('Y-m-d');
            }

            return [
                'date' => $date,
                'time' => $rawTime,
            ];
        }

        return [
            'date' => null,
            'time' => $rawTime ?: null,
        ];
    }

    /**
     * Mengumpulkan log dan system history RouterOS.
     */
    public function collect(MikrotikRouter $device): array
    {
        $client = $this->connect($device);

        $apiUsername = $this->getApiUsername($device);

        $collectedAt = now();

        $clock = $this->getRouterClock($client);

        $result = [
            'system' => 0,
            'account' => 0,
            'history' => 0,
            'ignored_api' => 0,
        ];

        /*
        |--------------------------------------------------------------------------
        | COLLECT ROUTEROS LOG
        |--------------------------------------------------------------------------
        */

        $logs = $client
            ->query('/log/print')
            ->read();

        foreach ($logs as $log) {
            $topics = (string) ($log['topics'] ?? '');

            // Simpan topic asli, parsing hanya untuk klasifikasi.
            $topicList = array_unique(array_filter(
                array_map(
                    'trim',
                    explode(',', strtolower($topics))
                )
            ));

            $isSystem = in_array('system', $topicList, true);
            $isAccount = in_array('account', $topicList, true);

            // Hanya simpan log yang memiliki topic system/account.
            if (! $isSystem && ! $isAccount) {
                continue;
            }

            $message = (string) ($log['message'] ?? '');

            $routerUser = $this->extractUser($message);

            // Abaikan aktivitas account dari API JANETA.
            if ($this->isJanetaUser($routerUser, $apiUsername)) {
                $result['ignored_api']++;
                continue;
            }

            $rawRouterTime = (string) ($log['time'] ?? '');

            $timestamp = $this->resolveRouterTimestamp(
                $rawRouterTime,
                $clock
            );

            $routerTime = $rawRouterTime;
            $routerId = (string) ($log['.id'] ?? '');

            /*
             * Satu log bisa memiliki beberapa kategori.
             * Contoh: system,account menghasilkan system + account.
             */
            $eventTypes = [];

            if ($isSystem) {
                $eventTypes[] = 'system';
            }

            if ($isAccount) {
                $eventTypes[] = 'account';
            }

            foreach ($eventTypes as $eventType) {
                $fingerprint = hash('sha256', implode('|', [
                    $device->id,
                    $routerId,
                    $routerTime,
                    $topics,
                    $message,
                    $eventType,
                ]));

                $created = MikrotikActivityLog::firstOrCreate(
                    [
                        'fingerprint' => $fingerprint,
                    ],
                    [
                        'mikrotik_router_id' => $device->id,
                        'event_type' => $eventType,
                        'topics' => $topics,

                        'router_date' => $timestamp['date'],
                        'router_time' => $timestamp['time'],
                        'router_timezone' => $clock['timezone'],

                        'router_user' => $routerUser,
                        'message' => $message,
                        'raw_data' => $log,

                        'collected_at' => $collectedAt,
                    ]
                );

                if ($created->wasRecentlyCreated) {
                    $result[$eventType]++;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | COLLECT SYSTEM HISTORY
        |--------------------------------------------------------------------------
        */

        $history = $client
            ->query('/system/history/print')
            ->read();

        foreach ($history as $entry) {
            $routerId = (string) ($entry['.id'] ?? '');

            $rawRouterTime = (string) ($entry['time'] ?? '');

            $timestamp = $this->resolveRouterTimestamp(
                $rawRouterTime,
                $clock
            );

            $action = (string) ($entry['action'] ?? '');

            $routerUser = trim((string) ($entry['by'] ?? ''));

            if ($this->isJanetaUser($routerUser, $apiUsername)) {
                $result['ignored_api']++;
                continue;
            }

            $fingerprint = hash('sha256', implode('|', [
                $device->id,
                'history',
                $routerId,
                $rawRouterTime,
                $routerUser,
                $action,
                (string) ($entry['undo'] ?? ''),
                (string) ($entry['redo'] ?? ''),
            ]));

            $created = MikrotikActivityLog::firstOrCreate(
                [
                    'fingerprint' => $fingerprint,
                ],
                [
                    'mikrotik_router_id' => $device->id,
                    'event_type' => 'config_history',
                    'topics' => 'system,history',

                    'router_date' => $timestamp['date'],
                    'router_time' => $timestamp['time'],
                    'router_timezone' => $clock['timezone'],

                    'router_user' => $routerUser ?: null,
                    'action' => $action ?: null,
                    'message' => $this->historyMessage($entry),
                    'raw_data' => $entry,

                    'collected_at' => $collectedAt,
                ]
            );

            if ($created->wasRecentlyCreated) {
                $result['history']++;
            }
        }

        return $result;
    }

    /**
     * Mengekstrak username dari pesan log.
     */
    protected function extractUser(string $message): ?string
    {
        $patterns = [
            '/\buser\s+([^\s]+)\s+/i',
            '/^([^\s]+)\s+logged\s+(?:in|out)\b/i',
            '/\bby\s+([^\s]+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                return trim($matches[1]);
            }
        }

        return null;
    }

    /**
     * Membentuk pesan dari System History.
     */
    protected function historyMessage(array $entry): string
    {
        $parts = [];

        foreach ([
                     'action',
                     'undo',
                     'redo',
                     'proplist',
                     'policy',
                 ] as $field) {
            if (filled($entry[$field] ?? null)) {
                $parts[] = $field . ': ' . $entry[$field];
            }
        }

        return implode(' | ', $parts)
            ?: 'Perubahan konfigurasi tercatat di System History.';
    }
}
