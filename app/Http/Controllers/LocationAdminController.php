<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserLocation;
use App\Models\UserLocationHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationAdminController extends Controller
{
    /**
     * Halaman live tracking.
     */
    public function index()
    {
        $totalUsers = User::count();

        return view('dashboard.tools.live-location', compact('totalUsers'));
    }

    /**
     * Snapshot awal dari DB (dipanggil SEKALI saat halaman dibuka).
     * Update berikutnya datang lewat Reverb.
     */
    public function data(): JsonResponse
    {
        $locations = UserLocation::query()
            ->with('user:id,name,email')
            ->where('tracking_enabled', true)
            ->whereNotNull('tracked_at')
            ->orderByDesc('tracked_at')
            ->get()
            ->map(fn ($l) => [
                'user_id' => $l->user_id,
                'name' => $l->user?->name ?? 'Unknown',
                'email' => $l->user?->email,
                'latitude' => $l->latitude,
                'longitude' => $l->longitude,
                'accuracy' => $l->accuracy,
                'tracked_at' => $l->tracked_at->toISOString(),
            ]);

        return response()->json([
            'success' => true,
            // Dipakai browser admin untuk menghitung selisih jam server vs browser.
            'server_time' => now()->toISOString(),
            'data' => $locations,
        ]);
    }

    /**
     * Riwayat lokasi satu user.
     * Sesuaikan nama view dengan yang sudah kamu punya.
     */
    public function history(Request $request, User $user)
    {
        $histories = UserLocationHistory::query()
            ->where('user_id', $user->id)
            ->when($request->filled('from'), fn ($q) => $q->whereDate('recorded_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('recorded_at', '<=', $request->input('to')))
            ->orderBy('recorded_at')
            ->paginate(100)
            ->withQueryString();

        return view('location.history', compact('user', 'histories'));
    }
}
