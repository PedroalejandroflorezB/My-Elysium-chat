@extends('layouts.admin-modern')

@section('title', 'Editar Usuario')

@section('header-actions')
    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        Volver
    </a>
@endsection

@section('content')
<div class="admin-panel" style="max-width: 600px; margin: 0 auto;">
    <div class="panel-header">Editar Usuario: {{ $user->name }}</div>
    <div class="panel-content" style="padding: 2rem;">
        <form method="POST" action="{{ route('admin.users.update', $user) }}">
            @csrf
            @method('PUT')

            <div style="margin-bottom: 1.5rem;">
                <label for="name" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: rgba(255, 255, 255, 0.9);">
                    Nombre Completo
                </label>
                <input 
                    id="name" 
                    type="text" 
                    name="name" 
                    value="{{ old('name', $user->name) }}" 
                    required 
                    style="width: 100%; padding: 0.75rem 1rem; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: #ffffff; font-size: 0.9rem;"
                    placeholder="Ingresa el nombre completo"
                >
                @error('name')
                    <div style="color: #ef4444; font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</div>
                @enderror
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label for="username" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: rgba(255, 255, 255, 0.9);">
                    Nombre de Usuario
                </label>
                <input 
                    id="username" 
                    type="text" 
                    name="username" 
                    value="{{ old('username', $user->username) }}" 
                    required 
                    style="width: 100%; padding: 0.75rem 1rem; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: #ffffff; font-size: 0.9rem; font-family: 'Courier New', monospace;"
                    placeholder="username_sin_espacios"
                >
                @error('username')
                    <div style="color: #ef4444; font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</div>
                @enderror
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label for="email" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: rgba(255, 255, 255, 0.9);">
                    Correo Electrónico
                </label>
                <input 
                    id="email" 
                    type="email" 
                    name="email" 
                    value="{{ old('email', $user->email) }}" 
                    required 
                    style="width: 100%; padding: 0.75rem 1rem; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: #ffffff; font-size: 0.9rem;"
                    placeholder="usuario@ejemplo.com"
                >
                @error('email')
                    <div style="color: #ef4444; font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</div>
                @enderror
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label for="is_admin" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: rgba(255, 255, 255, 0.9);">
                    Rol de Usuario
                </label>
                <select 
                    id="is_admin" 
                    name="is_admin" 
                    style="width: 100%; padding: 0.75rem 1rem; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: #ffffff; font-size: 0.9rem;"
                >
                    <option value="0" {{ old('is_admin', $user->is_admin) == 0 ? 'selected' : '' }}>Usuario</option>
                    <option value="1" {{ old('is_admin', $user->is_admin) == 1 ? 'selected' : '' }}>Administrador</option>
                </select>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label for="password" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: rgba(255, 255, 255, 0.9);">
                    Nueva Contraseña <span style="color: rgba(255, 255, 255, 0.5); font-weight: 400;">(opcional)</span>
                </label>
                <input 
                    id="password" 
                    type="password" 
                    name="password" 
                    style="width: 100%; padding: 0.75rem 1rem; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: #ffffff; font-size: 0.9rem;"
                    placeholder="••••••••"
                >
                @error('password')
                    <div style="color: #ef4444; font-size: 0.8rem; margin-top: 0.25rem;">{{ $message }}</div>
                @enderror
            </div>

            <div style="margin-bottom: 2rem;">
                <label for="password_confirmation" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: rgba(255, 255, 255, 0.9);">
                    Confirmar Nueva Contraseña
                </label>
                <input 
                    id="password_confirmation" 
                    type="password" 
                    name="password_confirmation" 
                    style="width: 100%; padding: 0.75rem 1rem; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: #ffffff; font-size: 0.9rem;"
                    placeholder="••••••••"
                >
            </div>

            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                    Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Actualizar Usuario
                </button>
            </div>
        </form>
    </div>
</div>
@endsection