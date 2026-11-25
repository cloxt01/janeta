<?php

namespace App\Http\Controllers\Router;

use App\Http\Controllers\Controller;
use App\Models\MikrotikActivityLog;
use Illuminate\View\View;

class MikrotikActivityLogController extends Controller
{
    public function index(): View
    {
        $logs = MikrotikActivityLog::with('router')
            ->orderByRaw('router_date IS NULL ASC')
            ->orderByDesc('router_date')
            ->orderByDesc('router_time')
            ->orderByDesc('id')
            ->paginate(25);

        return view('dashboard.router.activity-log', compact('logs'));
    }
}
