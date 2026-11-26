<?php

namespace App\Services\Requests;

use App\Models\Olt;
use App\Services\OltService;
use Illuminate\Support\Collection;

class OltManagementService
{
    public function __construct(
        protected OltService $oltService
    ) {}

    /**
     * Membuat OLT baru dan menguji service yang aktif.
     */
    public function create(array $data): array
    {
        $data['name'] = strtoupper($data['name']);
        $data = $this->normalizeServices($data);

        $olt = Olt::create($data);

        return [
            'olt' => $olt,
            'result' => $this->testServices($olt),
        ];
    }

    /**
     * Memperbarui konfigurasi OLT.
     */
    public function update(Olt $olt, array $data): array
    {
        $data['name'] = strtoupper($data['name']);
        $data = $this->normalizeServices($data, $olt);

        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        if (! $data['is_active']) {
            $data['status'] = 'unknown';
            $data['last_error'] = null;
        }

        $olt->update($data);

        $result = $data['is_active']
            ? $this->testServices($olt)
            : [
                'ok' => true,
                'message' => 'OLT dinonaktifkan.',
            ];

        return [
            'olt' => $olt,
            'result' => $result,
        ];
    }

    /**
     * Menghapus OLT.
     */
    public function delete(Olt $olt): void
    {
        $olt->delete();
    }

    /**
     * Menguji seluruh service aktif pada satu OLT.
     */
    public function testServices(Olt $olt): array
    {
        return $this->summarizeServiceResults(
            $this->oltService->testServices($olt)
        );
    }

    /**
     * Menguji seluruh OLT aktif.
     */
    public function testAll(): array
    {
        $results = Olt::where('is_active', true)
            ->get()
            ->mapWithKeys(fn (Olt $olt) => [
                $olt->name => $this->testServices($olt),
            ]);

        $failed = $results->filter(
            fn (array $result) => ! $result['ok']
        );

        return [
            'results' => $results,
            'failed' => $failed,
            'ok' => $failed->isEmpty(),
        ];
    }

    /**
     * Normalisasi konfigurasi SSH dan SNMP.
     */
    private function normalizeServices(
        array $data,
        ?Olt $olt = null
    ): array {
        $data['ssh_enabled'] = (bool) ($data['ssh_enabled'] ?? false);
        $data['snmp_enabled'] = (bool) ($data['snmp_enabled'] ?? false);

        $data['ssh_port'] = (int) ($data['ssh_port'] ?? 22);
        $data['snmp_port'] = (int) ($data['snmp_port'] ?? 161);

        $data['transport_type'] = 'tcp';

        $data['connection_type'] =
            $data['snmp_enabled'] && ! $data['ssh_enabled']
                ? 'snmp'
                : 'ssh';

        $data['port'] = $data['connection_type'] === 'snmp'
            ? $data['snmp_port']
            : $data['ssh_port'];

        $data['username'] = $data['connection_type'] === 'snmp'
            ? ($data['snmp_community'] ?? '')
            : ($data['ssh_username'] ?? '');

        $data['password'] = $data['connection_type'] === 'ssh'
            ? ($data['ssh_password'] ?? $olt?->ssh_password ?? '')
            : '';

        if ($olt && empty($data['ssh_password'])) {
            $data['ssh_password'] = $olt->ssh_password;
        }

        if ($olt && empty($data['ssh_private_key'])) {
            $data['ssh_private_key'] = $olt->ssh_private_key;
        }

        return $data;
    }

    /**
     * Merangkum hasil tes koneksi SSH/SNMP.
     */
    private function summarizeServiceResults(array $results): array
    {
        $failed = collect($results)->filter(
            fn (array $result): bool => ! $result['ok']
        );

        return [
            'ok' => $failed->isEmpty(),

            'message' => $failed->isEmpty()
                ? 'Seluruh service aktif berhasil terhubung.'
                : 'Service gagal: '.$failed
                    ->map(
                        fn (array $result, string $service): string =>
                            strtoupper($service).' - '.$result['message']
                    )
                    ->implode('; '),
        ];
    }
}
