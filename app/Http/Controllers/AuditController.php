<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditController
{

    public function logs(): View
    {
        return view('dashboard.audit-logs', [
            'logs' => AuditLog::with('user')
                ->latest()
                ->paginate(25),
        ]);
    }
    public function audit(
        Request $request,
        string $action,
        string $description,
        ?object $subject = null
    ): void {
        AuditLog::create([
            'user_id' => $request->user()?->id,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->id,
            'description' => $description,
            'ip_address' => $request->ip(),
        ]);
    }
}
