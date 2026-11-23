<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLocationAuthorized
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // User yang tidak wajib di-track tidak perlu izin lokasi.
        if (! $user->hasPermission('location.track')) {
            return $next($request);
        }

        if ($request->session()->get('location_authorized') === true) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Izin lokasi diperlukan.',
                'redirect' => route('location.permission'),
            ], 403);
        }

        return redirect()->route('location.permission');
    }
}
