<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Display a listing of roles and users.
     */
    public function index()
    {
        $users = User::orderBy('is_admin', 'desc')->orderBy('name', 'asc')->paginate(15);
        return view('admin.roles.index', compact('users'));
    }

    /**
     * Toggle the Admin role for a specific user.
     */
    public function toggleAdmin(Request $request, User $user)
    {
        // El administrador actual no puede quitarse su propio privilegio
        if (auth()->id() === $user->id) {
            return back()->with('error', 'No puedes alterar tus propios roles de administrador por seguridad.');
        }

        $user->is_admin = !$user->is_admin;
        $user->save();

        $status = $user->is_admin ? 'Otorgaste permisos de administrador a' : 'Revocaste permisos de administrador a';

        return back()->with('success', "{$status} {$user->name}.");
    }
}
