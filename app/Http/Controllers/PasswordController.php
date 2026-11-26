<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class PasswordController extends Controller
{
    protected AuditController $auditController;

    public function __construct(AuditController $auditController)
    {
        $this->auditController = $auditController;
    }

    /**
     * Halaman ganti password sendiri.
     */
    public function index()
    {
        return view('dashboard.profile.password');
    }

    /**
     * User mengganti password sendiri.
     */
    public function update(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => [
                'required',
                'confirmed',
                'different:current_password',
                Password::min(8)->letters()->numbers(),
            ],
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Password saat ini tidak sesuai.',
            ]);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        $this->auditController->audit(request(),
            'user.updated',
            'Password pengguna diupdate.',
            $user
        );

        return back()->with('success', 'Password berhasil diperbarui.');
    }

    /**
     * Admin mereset password user lain.
     */
    public function adminReset(Request $request, User $user)
    {
        abort_unless(
            $request->user()->hasPermission('users.password.reset'),
            403
        );

        if ($user->hasRole('super_admin')) {
            return back()->with('error', 'Password Super Admin tidak dapat direset.');
        }

        $user->update([
            'password' => Hash::make('12345678'),
        ]);

        $this->auditController->audit(request(),
            'admin.password.reset',
            'Password pengguna direset.',
            $user
        );

        return back()->with(
            'success',
            "Password {$user->name} berhasil direset ke password default (12345678)."
        );
    }
}
