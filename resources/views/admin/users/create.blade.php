@extends('layouts.admin')

@section('title', 'Alta de Nuevo Usuario')
@section('subtitle', 'Registra manualmente un nuevo usuario en la plataforma.')

@section('content')
<section class="dashboard-section">
    <div class="dashboard-card" style="max-width: 600px; margin: 0 auto;">
        <form action="{{ route('admin.users.store') }}" method="POST">
            @csrf
            
            <div class="admin-form-group">
                <label for="name" class="admin-label">Nombre Completo</label>
                <input type="text" id="name" name="name" class="admin-input" value="{{ old('name') }}" required autofocus placeholder="Ej: Juan Pérez">
            </div>

            <div class="admin-form-group">
                <label for="username" class="admin-label">Nombre de Usuario (Username)</label>
                <input type="text" id="username" name="username" class="admin-input" value="{{ old('username') }}" required placeholder="Ej: juanperez123">
            </div>

            <div class="admin-form-group">
                <label for="email" class="admin-label">Correo Electrónico</label>
                <input type="email" id="email" name="email" class="admin-input" value="{{ old('email') }}" required placeholder="juan@ejemplo.com">
            </div>

            <div class="admin-form-group">
                <label for="password" class="admin-label">Contraseña</label>
                <div style="position: relative;">
                    <input type="password" id="password" name="password" class="admin-input" required placeholder="Mínimo 8 caracteres">
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
                <div id="passwordStrength" style="margin-top: 8px; display: none;">
                    <div style="display: flex; gap: 4px; height: 4px; margin-bottom: 6px;">
                        <div id="strength1" style="flex: 1; background: rgba(255,255,255,0.1); border-radius: 2px; transition: all 0.2s ease;"></div>
                        <div id="strength2" style="flex: 1; background: rgba(255,255,255,0.1); border-radius: 2px; transition: all 0.2s ease;"></div>
                        <div id="strength3" style="flex: 1; background: rgba(255,255,255,0.1); border-radius: 2px; transition: all 0.2s ease;"></div>
                        <div id="strength4" style="flex: 1; background: rgba(255,255,255,0.1); border-radius: 2px; transition: all 0.2s ease;"></div>
                    </div>
                    <span id="strengthText" style="font-size: 0.75rem; color: #9ca3af;"></span>
                </div>
            </div>

            <div class="admin-form-group">
                <label for="password_confirmation" class="admin-label">Confirmar Contraseña</label>
                <div style="position: relative;">
                    <input type="password" id="password_confirmation" name="password_confirmation" class="admin-input" required placeholder="Repite la contraseña">
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
                <button type="submit" class="btn-primary" style="flex: 1; padding: 0.75rem;">Crear Usuario</button>
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

    // Password strength indicator
    function checkPasswordStrength(password) {
        let strength = 0;
        if (password.length >= 8) strength++;
        if (password.length >= 12) strength++;
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
        if (/\d/.test(password)) strength++;
        if (/[^a-zA-Z0-9]/.test(password)) strength++;
        return Math.min(strength, 4);
    }

    passwordInput.addEventListener('input', () => {
        const password = passwordInput.value;
        const strengthIndicator = document.getElementById('passwordStrength');
        const strengthText = document.getElementById('strengthText');
        
        if (password.length > 0) {
            strengthIndicator.style.display = 'block';
            const strength = checkPasswordStrength(password);
            
            const bars = [
                document.getElementById('strength1'),
                document.getElementById('strength2'),
                document.getElementById('strength3'),
                document.getElementById('strength4')
            ];
            
            bars.forEach((bar, index) => {
                if (index < strength) {
                    if (strength === 1) {
                        bar.style.background = '#ef4444';
                    } else if (strength === 2) {
                        bar.style.background = '#f59e0b';
                    } else if (strength === 3) {
                        bar.style.background = '#3b82f6';
                    } else {
                        bar.style.background = '#10b981';
                    }
                } else {
                    bar.style.background = 'rgba(255,255,255,0.1)';
                }
            });
            
            const strengthLabels = ['Muy débil', 'Débil', 'Aceptable', 'Fuerte'];
            strengthText.textContent = strengthLabels[strength - 1] || '';
        } else {
            strengthIndicator.style.display = 'none';
        }
        
        checkPasswordMatch();
    });

    // Password match verification
    function checkPasswordMatch() {
        const password = passwordInput.value;
        const confirmation = passwordConfirmationInput.value;
        const matchIndicator = document.getElementById('passwordMatch');
        
        if (confirmation.length > 0) {
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

    passwordConfirmationInput.addEventListener('input', checkPasswordMatch);
</script>
@endsection
