@extends('layouts.admin')

@section('title', 'Editar Usuario: ' . $user->name)
@section('subtitle', 'Modifica los datos de contacto o restablece la contraseña.')

@section('content')
<section class="dashboard-section">
    <div class="dashboard-card" style="max-width: 600px; margin: 0 auto;">
        
        <form action="{{ route('admin.users.update', $user) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="admin-form-group">
                <label for="name" class="admin-label">Nombre Completo</label>
                <input type="text" id="name" name="name" class="admin-input" value="{{ old('name', $user->name) }}" required>
            </div>

            <div class="admin-form-group">
                <label for="username" class="admin-label">Nombre de Usuario (Username)</label>
                <input type="text" id="username" name="username" class="admin-input" value="{{ old('username', $user->username) }}" required>
            </div>

            <div class="admin-form-group">
                <label for="email" class="admin-label">Correo Electrónico</label>
                <input type="email" id="email" name="email" class="admin-input" value="{{ old('email', $user->email) }}" required>
            </div>

            <hr style="border-color: rgba(255,255,255,0.05); margin: 2rem 0;">
            <p style="font-size: 0.85rem; color: #9ca3af; margin-bottom: 1rem;">Opcional: Deja en blanco si no deseas cambiar la contraseña actual del usuario.</p>

            <div class="admin-form-group">
                <label for="password" class="admin-label">Nueva Contraseña</label>
                <div style="position: relative;">
                    <input type="password" id="password" name="password" class="admin-input" placeholder="Mínimo 8 caracteres">
                    <button type="button" id="togglePassword" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 4px; display: flex; align-items: center; justify-content: center; opacity: 0.6; transition: opacity 0.2s ease;" aria-label="Mostrar contraseña">
                        <svg id="eyeIcon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #9ca3af;">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <svg id="eyeOffIcon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #9ca3af; display: none;">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="admin-form-group">
                <label for="password_confirmation" class="admin-label">Confirmar Contraseña Nueva</label>
                <div style="position: relative;">
                    <input type="password" id="password_confirmation" name="password_confirmation" class="admin-input" placeholder="Repite la contraseña">
                    <button type="button" id="togglePasswordConfirmation" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 4px; display: flex; align-items: center; justify-content: center; opacity: 0.6; transition: opacity 0.2s ease;" aria-label="Mostrar contraseña">
                        <svg id="eyeIconConfirm" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #9ca3af;">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <svg id="eyeOffIconConfirm" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #9ca3af; display: none;">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                        </svg>
                    </button>
                </div>
                <span id="passwordMatch" style="font-size: 0.75rem; margin-top: 6px; display: none;"></span>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" class="btn-primary" style="flex: 1; padding: 0.75rem;">Guardar Cambios</button>
                <a href="{{ route('admin.users.index') }}" class="btn-sm btn-edit" style="text-decoration: none; padding: 0.75rem 2rem; display:flex; align-items:center;">Cancelar</a>
            </div>
        </form>
    </div>
</section>

<script>
    // Password toggle
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');
    const eyeOffIcon = document.getElementById('eyeOffIcon');

    togglePassword.addEventListener('click', () => {
        const type = passwordInput.type === 'password' ? 'text' : 'password';
        passwordInput.type = type;
        eyeIcon.style.display = type === 'password' ? 'block' : 'none';
        eyeOffIcon.style.display = type === 'password' ? 'none' : 'block';
    });

    togglePassword.addEventListener('mouseenter', () => {
        togglePassword.style.opacity = '1';
    });

    togglePassword.addEventListener('mouseleave', () => {
        togglePassword.style.opacity = '0.6';
    });

    // Password confirmation toggle
    const togglePasswordConfirmation = document.getElementById('togglePasswordConfirmation');
    const passwordConfirmationInput = document.getElementById('password_confirmation');
    const eyeIconConfirm = document.getElementById('eyeIconConfirm');
    const eyeOffIconConfirm = document.getElementById('eyeOffIconConfirm');

    togglePasswordConfirmation.addEventListener('click', () => {
        const type = passwordConfirmationInput.type === 'password' ? 'text' : 'password';
        passwordConfirmationInput.type = type;
        eyeIconConfirm.style.display = type === 'password' ? 'block' : 'none';
        eyeOffIconConfirm.style.display = type === 'password' ? 'none' : 'block';
    });

    togglePasswordConfirmation.addEventListener('mouseenter', () => {
        togglePasswordConfirmation.style.opacity = '1';
    });

    togglePasswordConfirmation.addEventListener('mouseleave', () => {
        togglePasswordConfirmation.style.opacity = '0.6';
    });

    // Password match verification (only if password is being changed)
    function checkPasswordMatch() {
        const password = passwordInput.value;
        const confirmation = passwordConfirmationInput.value;
        const matchIndicator = document.getElementById('passwordMatch');
        
        if (password.length > 0 && confirmation.length > 0) {
            matchIndicator.style.display = 'block';
            if (password === confirmation) {
                matchIndicator.textContent = '✓ Las contraseñas coinciden';
                matchIndicator.style.color = '#10b981';
            } else {
                matchIndicator.textContent = '✗ Las contraseñas no coinciden';
                matchIndicator.style.color = '#ef4444';
            }
        } else {
            matchIndicator.style.display = 'none';
        }
    }

    passwordInput.addEventListener('input', checkPasswordMatch);
    passwordConfirmationInput.addEventListener('input', checkPasswordMatch);
</script>
@endsection
