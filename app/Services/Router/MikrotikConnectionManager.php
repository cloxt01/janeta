<?php

namespace App\Services\Router;

use App\Models\MikrotikRouter;
use Closure;
use RouterOS\Client as RouterOSClient;
use Illuminate\Support\Facades\Log;
use Throwable;

class MikrotikConnectionManager
{
    /**
     * Cache koneksi selama worker hidup.
     *
     * Format:
     * [
     *     router_id => [
     *         'signature' => '...',
     *         'client' => RouterOSClient
     *     ]
     * ]
     */
    protected array $connections = [];

    /**
     * Mengambil koneksi yang tersedia atau membuat koneksi baru.
     */
    public function get(
        MikrotikRouter $device,
        Closure $factory
    ): RouterOSClient {
        $routerId = (int) $device->id;

        $signature = $this->signature($device);

        /*
         * Cek koneksi yang sudah tersimpan.
         */
        if (isset($this->connections[$routerId])) {

            $cached = $this->connections[$routerId];

            /*
             * Pastikan konfigurasi router belum berubah.
             */
            if ($cached['signature'] === $signature) {

                try {
                    /*
                     * Health check menggunakan koneksi yang sama.
                     */
                    $cached['client']
                        ->query('/system/identity/print')
                        ->read();

                    Log::debug('[JANETA] MikroTik connection reused', [
                        'router_id' => $routerId,
                        'router' => $device->name,
                    ]);

                    return $cached['client'];

                } catch (Throwable $e) {

                    Log::warning('[JANETA] MikroTik cached connection invalid', [
                        'router_id' => $routerId,
                        'router' => $device->name,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            /*
             * Koneksi lama tidak valid atau konfigurasi berubah.
             * Lepaskan referensi client lama.
             */
            unset($this->connections[$routerId]);
        }

        /*
         * Buat koneksi baru.
         */
        $client = $factory();

        $this->connections[$routerId] = [
            'signature' => $signature,
            'client' => $client,
        ];

        Log::info('[JANETA] MikroTik new connection established', [
            'router_id' => $routerId,
            'router' => $device->name,
        ]);

        return $client;
    }

    /**
     * Membuat signature konfigurasi koneksi.
     *
     * Jika IP, port, username, password atau SSL berubah,
     * koneksi lama tidak akan digunakan kembali.
     */
    protected function signature(MikrotikRouter $device): string
    {
        $username = filled($device->api_username)
            ? $device->api_username
            : $device->username;

        $password = filled($device->api_password)
            ? $device->api_password
            : ($device->password ?? '');

        return hash('sha256', serialize([
            $device->host,
            (int) ($device->api_port ?: ($device->api_ssl ? 8729 : 8728)),
            (bool) $device->api_ssl,
            trim((string) $username),
            hash('sha256', (string) $password),
        ]));
    }

    /**
     * Membuang koneksi router tertentu dari cache.
     */
    public function forget(int $routerId): void
    {
        unset($this->connections[$routerId]);
    }

    /**
     * Membuang seluruh koneksi dari cache.
     */
    public function clear(): void
    {
        $this->connections = [];
    }

    /**
     * Informasi jumlah koneksi yang tersimpan.
     */
    public function count(): int
    {
        return count($this->connections);
    }
}
