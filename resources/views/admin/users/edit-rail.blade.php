@extends('layouts.admin-rail')

@section('title', 'Editar Usuario')

@section('content')
<div class="admin-header">
    <h1 class="admin-title">Editar Usuario</h1>
    <p class="admin-subtitle">Modificar información de {{ $user->name }}</p>
</div>

<div style="max-width: 600px; margin: 0 auto;">
    <div class="dashboard-card">
        <form method="POST" action="{{ route('admin.users.update', $user) }}">
            @csrf
            @method('PUT')
            
            <div class="form-group">
                <label class="form-label">Nombre Completo</label>
                <input type="text" name="name" class="form-input" value="{{ old('name', $user->name) }}" required>
                @error('name')
                    <div style="color: #ef4444; font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="form-group">
                <label class="form-label">Nombre de Usuario</label>
                <input type="text" name="username" class="form-input" value="{{ old('username', $user->username) }}" required>
                @error('username')
                    <div style="color: #ef4444; font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="form-group">
                <label class="form-label">Correo Electrónico</label>
                <input type="email" name="email" class="form-input" value="{{ old('email', $user->email) }}" required>
                @error('email')
                    <div style="color: #ef4444; font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="form-group">
                <label class="form-label">Rol de Usuario</label>
                <select name="is_admin" class="form-select" required>
                    <option value="0" {{ old('is_admin', $user->is_admin) == 0 ? 'selected' : '' }}>Usuario</option>
                    <option value="1" {{ old('is_admin', $user->is_admin) == 1 ? 'selected' : '' }}>Administrador</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Nueva Contraseña <span style="color: rgba(255, 255, 255, 0.5); font-weight: 400;">(opcional)</span></label>
                <input type="password" name="password" class="form-input" placeholder="Dejar vacío para mantener actual">
                @error('password')
                    <div style="color: #ef4444; font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="form-group">
                <label class="form-label">Confirmar Nueva Contraseña</label>
                <input type="password" name="password_confirmation" class="form-input" placeholder="Confirmar nueva contraseña">
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Volver
                </a>
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Actualizar Usuario
                </button>
            </div>
        </form>
    </div>
</div>
@endsection