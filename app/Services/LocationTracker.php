<?php

namespace App\Services;

use App\Events\LocationStopped;
use App\Events\LocationUpdated;
use App\Models\User;
use App\Models\UserLocation;
use App\Models\UserLocationHistory;
use Illuminate\Support\Facades\DB;

class LocationTracker
{
    /** Simpan riwayat hanya jika pindah >= jarak ini (meter) ... */
    private const MIN_DISTANCE_METERS = 10;

    /** ... atau jika sudah lewat waktu ini (detik) sejak riwayat terakhir. */
    private const MIN_INTERVAL_SECONDS = 60;

    /**
     * Simpan lokasi terakhir + riwayat (dengan filter), lalu broadcast ke admin.
     */
    public function record(User $user, array $data, bool $forceHistory = false): array
    {
        $now = now();

        $payload = DB::transaction(function () use ($user, $data, $now, $forceHistory) {
            $previous = UserLocation::where('user_id', $user->id)->first();

            $location = UserLocation::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                    'accuracy' => $data['accuracy'] ?? null,
                    'tracking_enabled' => true,
                    'tracked_at' => $now,
                ]
            );

            if ($forceHistory || $this->shouldSaveHistory($user->id, $data, $now)) {
                UserLocationHistory::create([
                    'user_id' => $user->id,
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                    'accuracy' => $data['accuracy'] ?? null,
                    'recorded_at' => $now,
                ]);
            }

            return [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'latitude' => (float) $location->latitude,
                'longitude' => (float) $location->longitude,
                'accuracy' => $location->accuracy,
                'tracked_at' => $now->toISOString(),
                'is_online' => true,
            ];
        });

        // Broadcast SETELAH transaksi DB sukses.
        event(new LocationUpdated($payload));

        return $payload;
    }

    public function stop(User $user): void
    {
        UserLocation::where('user_id', $user->id)->update(['tracking_enabled' => false]);

        event(new LocationStopped($user->id));
    }

    private function shouldSaveHistory(int $userId, array $data, $now): bool
    {
        $last = UserLocationHistory::where('user_id', $userId)
            ->latest('recorded_at')
            ->first();

        if (! $last) {
            return true;
        }

        if ($last->recorded_at->diffInSeconds($now) >= self::MIN_INTERVAL_SECONDS) {
            return true;
        }

        return $this->distanceMeters(
            $last->latitude,
            $last->longitude,
            (float) $data['latitude'],
            (float) $data['longitude']
        ) >= self::MIN_DISTANCE_METERS;
    }

    /** Jarak Haversine dalam meter. */
    private function distanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $r = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
