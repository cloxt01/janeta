<?php

namespace App\Support;

use App\Models\OltServiceRequest;

class OltActivityPresenter
{
    private const LABELS = [
        'ont.version' => 'Cek ONT (ID Customer)',
        'ont.lookup' => 'Cek ONT (ID Customer)',
        'ont.serial' => 'Cek ONT (Serial Number)',
        'ont.find' => 'Cari ONT di port',
        'ont.port_scan' => 'Scan port GPON',
        'ont.restart' => 'Restart ONT',
        'ont.activate' => 'Aktifkan ONT',
        'ont.deactivate' => 'Nonaktifkan ONT',
        'ont.wlan' => 'Cek WLAN',
    ];

    public static function make(
        OltServiceRequest $task,
        ?string $status = null,
        ?string $message = null,
        ?string $error = null
    ): array {
        $p = $task->payload ?? [];
        $result = is_array($task->result) ? $task->result : [];
        $isTelegram = !empty($p['chat_id']);

        $target = $p['id'] ?? $p['sn']
            ?? (isset($p['frame'])
                ? "{$p['frame']}/{$p['slot']}/{$p['port']}"
                    . (isset($p['ont_id']) ? ":{$p['ont_id']}" : '')
                : '-');

        $summary = match ($task->operation) {
            'ont.version', 'ont.lookup', 'ont.serial', 'ont.find' => [
                'customer' => $result['customerName'] ?? null,
                'state' => $result['runState'] ?? null,
                'rx' => $result['rxPower'] ?? null,
            ],
            'ont.port_scan' => [
                'total' => $result['totalOnt'] ?? null,
                'online' => $result['online'] ?? null,
                'offline' => $result['offline'] ?? null,
            ],
            default => null,
        };

        return [
            'request_id' => $task->uuid,
            'operation' => $task->operation,
            'command' => self::LABELS[$task->operation] ?? $task->operation,
            'target' => $target,
            'status' => $status ?? $task->status,
            'message' => $message,
            'error' => $error ?? $task->error,

            'olt' => $p['olt'] ?? $result['olt'] ?? $task->olt?->name ?? '-',

            'source' => $isTelegram ? 'telegram' : 'dashboard',
            'user' => [
                'id' => $isTelegram ? ($p['tg_user_id'] ?? null) : $task->user_id,
                'name' => $isTelegram
                    ? ($p['tg_name'] ?? 'Telegram User')
                    : ($task->user?->name ?? 'System'),
            ],
            'chat_id' => $p['chat_id'] ?? null,

            'queued_at' => $task->created_at?->toIso8601String(),
            'started_at' => $task->started_at?->toIso8601String(),
            'finished_at' => $task->finished_at?->toIso8601String(),
            'duration_ms' => $task->duration_ms,

            // Ringkasan saja. Jangan kirim result penuh (raw CLI / WLAN),
            // bisa melebihi batas payload broadcast (Pusher 10KB).
            'summary' => $summary,
        ];
    }
}