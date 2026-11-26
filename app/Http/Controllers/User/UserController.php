<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\AuditController;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    protected AuditController $auditController;

    public function __construct(AuditController $auditController)
    {
        $this->auditController = $auditController;
    }

    public function index(): View
    {
        return view('dashboard.management.users', [
            'users' => User::with('roles')->latest()->paginate(15),
            'roles' => Role::orderBy('display_name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
            ],
            'role_id' => [
                'exists:roles,id',
            ],
        ]);


        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password']
        ]);

        $user->roles()->sync([$request->role_id]);
        $this->auditController->audit(
            $request,
            'user.created',
            'Pengguna dashboard ditambahkan.',
            $user
        );

        return back()->with(
            'success',
            'Pengguna dashboard berhasil ditambahkan.'
        );
    }

    public function update(
        Request $request,
        User $user
    ): RedirectResponse {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email,'.$user->id,
            ],
            'password' => [
                'nullable',
                'string',
                'min:8',
            ],
            'hris_role' => [
                'required',
                Rule::in([
                    'super_admin',
                    'admin',
                    'operator',
                    'viewer',
                ]),
            ],
        ]);

        if (
            ! array_key_exists('password', $data) ||
            $data['password'] === null ||
            $data['password'] === ''
        ) {
            unset($data['password']);
        }

        $data['role'] = $data['hris_role'];

        $user->update($data);

        $this->auditController->audit($request,
            'user.updated',
            'Pengguna dashboard diperbarui.',
            $user
        );

        return back()->with(
            'success',
            'Pengguna dashboard berhasil diperbarui.'
        );
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_if(
            $user->is(Auth::user()),
            422,
            'Akun yang sedang digunakan tidak dapat dihapus.'
        );

        $user->delete();

        $this->auditController->audit(request(),
            'user.deleted',
            'Pengguna dashboard dihapus.',
            $user
        );

        return back()->with(
            'success',
            'Pengguna dashboard berhasil dihapus.'
        );
    }

}
