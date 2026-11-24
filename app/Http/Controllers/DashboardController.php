<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WhitelistEntry;
use App\Models\Olt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    protected AuditController $auditController;

    public function __construct(AuditController $auditController)
    {
        $this->auditController = $auditController;
    }

    /*
    |--------------------------------------------------------------------------
    | LOGIN
    |--------------------------------------------------------------------------
    */

    public function login(): View|RedirectResponse
    {
        return Auth::check()
            ? redirect()->route('dashboard')
            : view('auth.login');
    }

    /*
    |--------------------------------------------------------------------------
    | AUTHENTICATE
    |--------------------------------------------------------------------------
    */

    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt(
            $credentials,
            $request->boolean('remember')
        )) {
            $this->auditController->audit(
                $request,
                'auth.login_failed',
                'Percobaan login gagal untuk '.$credentials['email'].'.'
            );

            return back()
                ->withErrors([
                    'email' => 'Email atau password tidak valid.',
                ])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        $request->user()->update([
            'last_login_at' => now(),
        ]);

        $this->auditController->audit(
            $request,
            'auth.login',
            'User berhasil login.'
        );

        return redirect()->intended(route('dashboard'));
    }

    /*
    |--------------------------------------------------------------------------
    | LOGOUT
    |--------------------------------------------------------------------------
    */

    public function logout(Request $request): RedirectResponse
    {
        $this->auditController->audit(
            $request,
            'auth.logout',
            'User berhasil logout.'
        );

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD INDEX
    |--------------------------------------------------------------------------
    */

    public function index(): View
    {
        return view('dashboard.index', [
            'olts' => Olt::latest()->get(),

            'whitelistCount' => WhitelistEntry::count(),

            'adminCount' => User::whereHas('roles', function ($query) {
                $query->whereIn('name', [
                    'admin',
                    'super-admin',
                ]);
            })->count(),

            'userCount' => User::count(),

            'recentWhitelist' => WhitelistEntry::latest()
                ->limit(5)
                ->get(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ONT LOOKUP PAGE
    |--------------------------------------------------------------------------
    */

    public function ontLookup(): View
    {
        return view('dashboard.ont-lookup', [
            'id' => null,
            'result' => null,
            'error' => null,
        ]);
    }


}
