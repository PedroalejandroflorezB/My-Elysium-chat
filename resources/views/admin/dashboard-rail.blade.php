@extends('layouts.admin-rail')

@section('title', 'Admin Dashboard')

@section('content')
<div class="admin-header">
    <h1 class="admin-title">Panel de Administración</h1>
    <p class="admin-subtitle">Gestiona usuarios, roles y configuraciones del sistema</p>
</div>

<div class="dashboard-grid">
    <div class="dashboard-card">
        <div class="card-header">
            <div class="card-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </div>
            <div class="card-title">Usuarios Totales</div>
        </div>
        <div class="card-stats">{{ $totalUsers }}</div>
        <div class="card-description">Usuarios registrados en el sistema</div>
    </div>

    <div class="dashboard-card">
        <div class="card-header">
            <div class="card-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
            <div class="card-title">Administradores</div>
        </div>
        <div class="card-stats">{{ $totalAdmins }}</div>
        <div class="card-description">Usuarios con permisos de administrador</div>
    </div>

    <div class="dashboard-card">
        <div class="card-header">
            <div class="card-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
            </div>
            <div class="card-title">Actividad Reciente</div>
        </div>
        <div class="card-stats">{{ $recentActivity }}</div>
        <div class="card-description">Acciones realizadas hoy</div>
    </div>

    <div class="dashboard-card">
        <div class="card-header">
            <div class="card-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <div class="card-title">Sistema</div>
        </div>
        <div class="card-stats" style="font-size: 1.2rem;">Online</div>
        <div class="card-description">Estado del servidor P2P</div>
    </div>
</div>
@endsection

@section('modals')
<!-- Modal Perfil Admin -->
<div id="profile-modal" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="24" height="24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                Perfil de Administrador
            </div>
            <button class="modal-close" onclick="closeModal('profile-modal')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PATCH')
                
                <div class="form-group">
                    <label class="form-label">Nombre Completo</label>
                    <input type="text" name="name" class="form-input" value="{{ auth()->user()->name }}" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nombre de Usuario</label>
                    <input type="text" class="form-input" value="{{ auth()->user()->username }}" readonly style="opacity: 0.6;">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Correo Electrónico</label>
                    <input type="email" name="email" class="form-input" value="{{ auth()->user()->email }}" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nueva Contraseña (opcional)</label>
                    <input type="password" name="password" class="form-input" placeholder="Dejar vacío para mantener actual">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Confirmar Nueva Contraseña</label>
                    <input type="password" name="password_confirmation" class="form-input" placeholder="Confirmar nueva contraseña">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('profile-modal')">Cancelar</button>
            <button class="btn btn-primary" onclick="document.querySelector('#profile-modal form').submit()">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Actualizar Perfil
            </button>
        </div>
    </div>
</div>

<!-- Modal Gestionar Usuarios -->
<div id="users-modal" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="24" height="24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                Gestionar Usuarios
            </div>
            <button class="modal-close" onclick="closeModal('users-modal')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Username</th>
                        <th>Rol</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users->where('id', '!=', auth()->id()) as $user)
                        <tr>
                            <td class="user-id">#{{ $user->id }}</td>
                            <td>{{ $user->name }}</td>
                            <td style="color: rgba(255, 255, 255, 0.7);">{{ $user->email }}</td>
                            <td class="user-id">{{ $user->username }}</td>
                            <td>
                                <span class="role-badge {{ $user->is_admin ? 'role-admin' : 'role-user' }}">
                                    {{ $user->is_admin ? 'Admin' : 'Usuario' }}
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-primary" onclick="editUser({{ $user->id }})">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="14" height="14">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Editar
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteUser({{ $user->id }}, '{{ $user->name }}')">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="14" height="14">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('users-modal')">Cerrar</button>
            <button class="btn btn-primary" onclick="closeModal('users-modal'); openModal('create-user-modal')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Crear Usuario
            </button>
        </div>
    </div>
</div>

<!-- Modal Crear Usuario -->
<div id="create-user-modal" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="24" height="24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
                Crear Nuevo Usuario
            </div>
            <button class="modal-close" onclick="closeModal('create-user-modal')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <form method="POST" action="{{ route('admin.users.store') }}">
                @csrf
                
                <div class="form-group">
                    <label class="form-label">Nombre Completo</label>
                    <input type="text" name="name" class="form-input" placeholder="Ingresa el nombre completo" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nombre de Usuario</label>
                    <input type="text" name="username" class="form-input" placeholder="username_sin_espacios" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Correo Electrónico</label>
                    <input type="email" name="email" class="form-input" placeholder="usuario@ejemplo.com" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Rol</label>
                    <select name="is_admin" class="form-select" required>
                        <option value="0">Usuario</option>
                        <option value="1">Administrador</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Contraseña</label>
                    <input type="password" name="password" class="form-input" placeholder="••••••••" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Confirmar Contraseña</label>
                    <input type="password" name="password_confirmation" class="form-input" placeholder="••••••••" required>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('create-user-modal')">Cancelar</button>
            <button class="btn btn-primary" onclick="document.querySelector('#create-user-modal form').submit()">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Crear Usuario
            </button>
        </div>
    </div>
</div>

<!-- Modal Gestionar Roles -->
<div id="roles-modal" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="24" height="24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                Gestionar Roles y Permisos
            </div>
            <button class="modal-close" onclick="closeModal('roles-modal')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Rol</th>
                        <th>Descripción</th>
                        <th>Usuarios</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <span class="role-badge role-admin">Administrador</span>
                        </td>
                        <td>Acceso completo al sistema</td>
                        <td>{{ $totalAdmins }}</td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="14" height="14">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Editar
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="role-badge role-user">Usuario</span>
                        </td>
                        <td>Acceso básico al chat P2P</td>
                        <td>{{ $totalUsers - $totalAdmins }}</td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="14" height="14">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Editar
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <div style="margin-top: 2rem; padding: 1rem; background: rgba(255, 255, 255, 0.03); border-radius: 8px;">
                <h4 style="margin-bottom: 1rem; color: rgba(255, 255, 255, 0.9);">Permisos del Sistema</h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <div>
                        <strong style="color: #818cf8;">Administrador:</strong>
                        <ul style="margin: 0.5rem 0 0 1rem; color: rgba(255, 255, 255, 0.7);">
                            <li>Gestionar usuarios</li>
                            <li>Gestionar roles</li>
                            <li>Acceso al panel admin</li>
                            <li>Todas las funciones de usuario</li>
                        </ul>
                    </div>
                    <div>
                        <strong style="color: #9ca3af;">Usuario:</strong>
                        <ul style="margin: 0.5rem 0 0 1rem; color: rgba(255, 255, 255, 0.7);">
                            <li>Chat P2P</li>
                            <li>Transferencia de archivos</li>
                            <li>Gestionar contactos</li>
                            <li>Actualizar perfil</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('roles-modal')">Cerrar</button>
        </div>
    </div>
</div>

<!-- Modal Códigos de Recuperación -->
<div id="recovery-codes-modal" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="24" height="24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
                Códigos de Recuperación
            </div>
            <button class="modal-close" onclick="closeModal('recovery-codes-modal')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <div style="margin-bottom: 1.5rem;">
                <h4 style="margin-bottom: 0.5rem; color: rgba(255, 255, 255, 0.9);">¿Qué son los códigos de recuperación?</h4>
                <p style="color: rgba(255, 255, 255, 0.7); font-size: 0.9rem; line-height: 1.5;">
                    Los códigos de recuperación son una forma segura de acceder a tu cuenta si pierdes tu contraseña. 
                    Cada código se puede usar solo una vez y se almacenan localmente en tu navegador.
                </p>
            </div>
            
            <div id="recovery-codes-list">
                <!-- Los códigos se cargarán aquí dinámicamente -->
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('recovery-codes-modal')">Cerrar</button>
            <button class="btn btn-secondary" onclick="downloadRecoveryCodes()">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Descargar TXT
            </button>
            <button class="btn btn-primary" onclick="generateRecoveryCodes()">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Generar Nuevos Códigos
            </button>
        </div>
    </div>
</div>

<script>
    function editUser(userId) {
        // Aquí podrías abrir un modal de edición o redirigir
        window.location.href = `/admin/users/${userId}/edit`;
    }

    function deleteUser(userId, userName) {
        if (confirm(`¿Estás seguro de que quieres eliminar al usuario "${userName}"? Esta acción no se puede deshacer.`)) {
            // Crear y enviar formulario de eliminación
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/admin/users/${userId}`;
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            const methodField = document.createElement('input');
            methodField.type = 'hidden';
            methodField.name = '_method';
            methodField.value = 'DELETE';
            
            form.appendChild(csrfToken);
            form.appendChild(methodField);
            document.body.appendChild(form);
            form.submit();
        }
    }
</script>
@endsection