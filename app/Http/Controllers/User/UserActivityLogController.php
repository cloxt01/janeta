<?php

namespace App\Http\Controllers\User;

use App\Models\UserActivityLog;
use Illuminate\View\View;

class UserActivityLogController
{
    public function index(): View
    {
        return view('dashboard.activity.user-activity', [
            'logs' => UserActivityLog::with('user')
                ->latest()
                ->paginate(25),
        ]);
    }
}
