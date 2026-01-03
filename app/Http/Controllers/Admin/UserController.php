<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->orderBy('name')->get()->map(function ($u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'roles' => $u->getRoleNames(),
            ];
        });

        $roles = ['Admin', 'LabStaff', 'Student'];

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'roles' => $roles,
        ]);
    }

    public function updateRole(Request $request, User $user)
    {
        $request->validate(['role' => 'required|string']);

        $role = $request->input('role');

        try {
            $user->syncRoles([$role]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update role'], 500);
        }

        return response()->json(['success' => true]);
    }
}
