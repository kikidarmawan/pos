<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $request->user()->can('view_users') || abort(403, 'Forbidden');

        $users = User::with('roles')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                });
            })
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $request->user()->can('create_users') || abort(403, 'Forbidden');

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'roles' => 'array',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        if ($request->roles) {
            $user->syncRoles($request->roles);
        }

        $user->load('roles');

        return response()->json([
            'message' => 'User berhasil ditambahkan',
            'user' => $user,
        ], 201);
    }

    public function show(User $user)
    {
        $request = request();
        $request->user()->can('view_users') || abort(403, 'Forbidden');

        $user->load('roles.permissions');

        return response()->json($user);
    }

    public function update(Request $request, User $user)
    {
        $request->user()->can('edit_users') || abort(403, 'Forbidden');

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'roles' => 'array',
        ]);

        $user->update($request->only(['name', 'email']));

        if ($request->has('roles')) {
            $user->syncRoles($request->roles);
        }

        $user->load('roles');

        return response()->json([
            'message' => 'User berhasil diperbarui',
            'user' => $user,
        ]);
    }

    public function destroy(User $user)
    {
        request()->user()->can('delete_users') || abort(403, 'Forbidden');
        if ($user->id === request()->user()->id) {
            abort(400, 'Tidak dapat menghapus akun sendiri.');
        }

        $user->delete();

        return response()->json([
            'message' => 'User berhasil dihapus',
        ]);
    }

    public function resetPassword(Request $request, User $user)
    {
        $request->user()->can('edit_users') || abort(403, 'Forbidden');

        $request->validate([
            'password' => 'required|min:8',
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Password berhasil direset',
        ]);
    }
}
