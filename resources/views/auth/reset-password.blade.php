<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <title>Restablecer Contraseña | Elysium P2P</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
    
    @vite('resources/css/auth.css')
    
    <style>
        .admin-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 8px;
            color: #818cf8;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="auth-glow" aria-hidden="true"></div>
    
    <main class="auth-card">
        <header class="auth-header">
            <div class="auth-logo">
                <div class="auth-logo-icon" aria-hidden="true"></div>
                <span class="auth-logo-text">ELYSIUM</span>
            </div>
            <h1 class="auth-title">NUEVA CONTRASEÑA</h1>
            <p class="auth-subtitle">Ingresa tu nueva contraseña de forma segura.</p>
        </header>

        @if($errors->any())
            <div class="auth-validation-errors" role="alert">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(session('recovery_verified'))
            <div class="admin-badge">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                @if(session('recovery_type') === 'gmail')
                    Código de Gmail Verificado
                @else
                    Código de Recuperación Verificado
                @endif
            </div>
        @endif

        <form method="POST" action="{{ session('recovery_verified') ? route('admin.recovery.reset') : route('password.store') }}" class="auth-form">
            @csrf

            @if(!session('recovery_verified'))
                <input type="hidden" name="token" value="{{ $request->route('token') }}">
            @endif

            <div class="form-group">
                <label class="form-label" for="email">CORREO ELECTRÓNICO</label>
                <input 
                    id="email" 
                    type="email" 
                    name="email" 
                    class="form-input @error('email') is-invalid @enderror" 
                    value="{{ session('recovery_email') ?? old('email', $request->email) }}" 
                    placeholder="tu@correo.com"
                    required 
                    autofocus 
                    autocomplete="username"
                    {{ session('recovery_verified') ? 'readonly' : '' }}
                >
                @error('email')
                    <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="password">NUEVA CONTRASEÑA</label>
                <div style="position: relative;">
                    <input 
                        id="password" 
                        type="password" 
                        name="password" 
                        class="form-input @error('password') is-invalid @enderror" 
                        placeholder="••••••••"
                        required 
                        autocomplete="new-password"
                    >
                    <button type="button" id="togglePassword" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 4px; display: flex; align-items: center; justify-content: center; opacity: 0.6; transition: opacity 0.2s ease;" aria-label="Mostrar contraseña">
                        <svg id="eyeIcon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--auth-on-surface);">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <svg id="eyeOffIcon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--auth-on-surface); display: none;">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                        </svg>
                    </button>
                </div>
                @error('password')
                    <span class="error-message">{{ $message }}</span>
                @enderror
                <div id="passwordStrength" style="margin-top: 8px; display: none;">
                    <div style="display: flex; gap: 4px; height: 4px; margin-bottom: 6px;">
                        <div id="strength1" style="flex: 1; background: rgba(255,255,255,0.1); border-radius: 2px; transition: all 0.2s ease;"></div>
                        <div id="strength2" style="flex: 1; background: rgba(255,255,255,0.1); border-radius: 2px; transition: all 0.2s ease;"></div>
                        <div id="strength3" style="flex: 1; background: rgba(255,255,255,0.1); border-radius: 2px; transition: all 0.2s ease;"></div>
                        <div id="strength4" style="flex: 1; background: rgba(255,255,255,0.1); border-radius: 2px; transition: all 0.2s ease;"></div>
                    </div>
                    <span id="strengthText" style="font-size: 0.75rem; color: var(--auth-on-surface); opacity: 0.7;"></span>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="password_confirmation">CONFIRMAR CONTRASEÑA</label>
                <div style="position: relative;">
                    <input 
                        id="password_confirmation" 
                        type="password" 
                        name="password_confirmation" 
                        class="form-input" 
                        placeholder="••••••••"
                        required 
                        autocomplete="new-password"
                    >
                    <button type="button" id="togglePasswordConfirmation" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 4px; display: flex; align-items: center; justify-content: center; opacity: 0.6; transition: opacity 0.2s ease;" aria-label="Mostrar contraseña">
                        <svg id="eyeIconConfirm" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--auth-on-surface);">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <svg id="eyeOffIconConfirm" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--auth-on-surface); display: none;">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                        </svg>
                    </button>
                </div>
                <span id="passwordMatch" style="font-size: 0.75rem; margin-top: 6px; display: none;"></span>
            </div>

            <button type="submit" class="auth-btn auth-btn-primary">
                RESTABLECER CONTRASEÑA
            </button>
        </form>

        <footer class="auth-footer">
            <a href="{{ route('login') }}">← Volver al inicio de sesión</a>
        </footer>
    </main>

    @if(session('recovery_verified'))
        <input type="hidden" id="recovery-code" value="{{ session('recovery_code') }}">
        @if(session('recovery_type') === 'gmail')
            <input type="hidden" id="gmail-code" value="{{ session('gmail_code') }}">
        @endif
    @endif

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

        // Add recovery code to form if admin recovery
        document.addEventListener('DOMContentLoaded', function() {
            const recoveryCodeInput = document.getElementById('recovery-code');
            const gmailCodeInput = document.getElementById('gmail-code');
            
            if (recoveryCodeInput || gmailCodeInput) {
                const form = document.querySelector('.auth-form');
                
                if (recoveryCodeInput) {
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'recovery_code';
                    hiddenInput.value = recoveryCodeInput.value;
                    form.appendChild(hiddenInput);
                }
                
                if (gmailCodeInput) {
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'gmail_code';
                    hiddenInput.value = gmailCodeInput.value;
                    form.appendChild(hiddenInput);
                }
            }
        });
    </script>
</body>
</html>