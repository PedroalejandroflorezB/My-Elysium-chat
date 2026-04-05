@extends('layouts.admin')

@section('title', 'Gestión de Roles de Administrador')
@section('subtitle', 'Promueve o revoca instantáneamente el acceso administrativo a cuentas existentes.')

@section('content')
<section class="dashboard-section">
    <div class="dashboard-card" style="margin-top: 1rem; padding: 0;">
        <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 50%">Usuario Registrado</th>
                        <th style="width: 25%">Rol Actual</th>
                        <th style="width: 25%; text-align:right;">Permisos Administrativos</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>
                                <div style="display:flex; align-items:center; gap:0.75rem;">
                                    @if($user->avatar)
                                        <img src="{{ asset('storage/'.$user->avatar) }}" alt="" style="width:36px; height:36px; border-radius:50%; object-fit:cover;">
                                    @else
                                        <div style="width:36px; height:36px; border-radius:50%; background:var(--primary); color:white; display:flex; align-items:center; justify-content:center; font-size:14px; font-weight: bold;">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div>
                                        <div style="font-weight: 600;">{{ $user->name }}</div>
                                        <div style="font-size: 0.8rem; opacity: 0.7;">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($user->is_admin)
                                    <span class="admin-badge badge-admin">🎖️ Administrador</span>
                                @else
                                    <span class="admin-badge badge-user">👤 Usuario Estándar</span>
                                @endif
                            </td>
                            <td style="text-align:right;">
                                @if(auth()->id() !== $user->id)
                                    <form action="{{ route('admin.roles.toggle', $user) }}" method="POST">
                                        @csrf
                                        @if($user->is_admin)
                                            <button type="submit" class="btn-sm btn-danger" style="margin-left: auto;">
                                                Revocar Poderes
                                            </button>
                                        @else
                                            <button type="submit" class="btn-sm btn-edit" style="margin-left: auto; border: 1px solid var(--primary); color: var(--primary);">
                                                Promover a Admin
                                            </button>
                                        @endif
                                    </form>
                                @else
                                    <span style="opacity: 0.5; font-size: 0.8rem; font-weight: 600;">(Cuenta Propia)</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align: center; padding: 3rem; opacity: 0.6;">No hay usuarios registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div style="padding: 1rem;">
            {{ $users->links() }}
        </div>
    </div>
</section>
@endsection
