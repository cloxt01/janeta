<?php

namespace App\Http\Controllers;

use App\Models\UserActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditController
{

    public function audit(
        Request $request,
        string $action,
        string $description,
        ?object $subject = null
    ): void {
        UserActivityLog::create([
            'user_id' => $request->user()?->id,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->id,
            'description' => $description,
            'ip_address' => $request->ip(),
        ]);
    }
}
