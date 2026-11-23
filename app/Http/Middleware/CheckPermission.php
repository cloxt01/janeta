<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(
        Request $request,
        Closure $next,
        string $permission
    ): Response {

        $user = $request->user();

        abort_unless($user, 401);

        abort_unless(
            $user->hasPermission($permission),
            403,
            'Kamu tidak memiliki hak akses untuk fitur ini.'
        );

        return $next($request);
    }
}
