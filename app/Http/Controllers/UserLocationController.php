<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLocationRequest;
use App\Services\LocationTracker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserLocationController extends Controller
{
    public function __construct(private LocationTracker $tracker) {}

    public function revoke(Request $request): JsonResponse
    {
        $this->tracker->stop($request->user());

        $request->session()->forget('location_authorized');

        return response()->json(['success' => true]);
    }
    /**
     * Dipanggil sekali dari halaman izin lokasi.
     */
    public function authorizeLocation(StoreLocationRequest $request): JsonResponse
    {
        $data = $this->tracker->record($request->user(), $request->validated(), true);

        session(['location_authorized' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Lokasi berhasil diaktifkan.',
            'data' => $data,
        ]);
    }

    /**
     * Update GPS berkala dari browser user.
     */
    public function update(StoreLocationRequest $request): JsonResponse
    {
        $data = $this->tracker->record($request->user(), $request->validated());

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * User menghentikan tracking.
     */
    public function stop(Request $request): JsonResponse
    {
        $this->tracker->stop($request->user());

        return response()->json(['success' => true]);
    }
}
