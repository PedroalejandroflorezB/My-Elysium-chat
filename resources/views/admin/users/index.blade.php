@extends('layouts.admin')

@section('title', 'Usuarios')
@section('subtitle', 'Gestión de usuarios del sistema')

@section('content')
<section class="dashboard-section">
    <div class="dashboard-section-header" style="justify-content: space-between; align-items: center;">
        <h2 class="dashboard-section-title">Usuarios Registrados</h2>
        <a href="{{ route('admin.users.create') }}" class="btn-primary" style="text-decoration: none; padding: 0.6875rem 1.25rem; display: inline-flex; align-items: center; gap: 0.5rem; min-height: 44px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            Crear Usuario
        </a>
    </div>
    
    <div class="dashboard-card">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Username</th>
                    <th>Rol</th>
                    <th>Registro</th>
                    <th style="text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td style="font-family: 'Courier New', monospace; opacity: 0.7;">#{{ $user->id }}</td>
                        <td style="font-weight: 600;">{{ $user->name }}</td>
                        <td style="opacity: 0.8;">{{ $user->email }}</td>
                        <td style="font-family: 'Courier New', monospace; opacity: 0.7;">@{{ $user->username }}</td>
                        <td>
                            <span class="admin-badge {{ $user->is_admin ? 'badge-admin' : 'badge-user' }}">
                                {{ $user->is_admin ? 'Admin' : 'Usuario' }}
                            </span>
                        </td>
                        <td style="opacity: 0.7; font-size: 0.8125rem;">{{ $user->created_at->format('d/m/Y') }}</td>
                        <td>
                            <div class="admin-actions" style="justify-content: flex-end;">
                                <a href="{{ route('admin.users.edit', $user) }}" class="btn-sm btn-edit" style="text-decoration:none;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    Editar
                                </a>
                                
                                @if(auth()->id() !== $user->id)
                                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('¿Eliminar este usuario? Esta acción no se puede deshacer.');" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-sm btn-danger">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            Eliminar
                                        </button>
                                    </form>
                                @else
                                    <span class="btn-sm" style="opacity: 0.5; cursor: not-allowed; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05);">
                                        Tu cuenta
                                    </span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 3rem; opacity: 0.5;">
                            No hay usuarios registrados
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
