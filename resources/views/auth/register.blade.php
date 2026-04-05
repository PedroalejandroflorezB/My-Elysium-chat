<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <title>Crear Cuenta | Elysium P2P</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
    
    <!-- Theme Script -->
    <script>
        (function() {
            const theme = localStorage.getItem('elysium-theme-mode') || 'petrol';
            document.documentElement.classList.add('theme-' + theme);
            
            const isDark = localStorage.getItem('elysium-dark-mode') === 'true';
            if (isDark) document.documentElement.classList.add('dark-mode');
        })();
    </script>
    
    <!-- Auth CSS & Global Variables -->
    @vite(['resources/css/app.css', 'resources/css/auth.css'])
</head>
<body>
    <!-- Background Glow -->
    <div class="auth-glow" aria-hidden="true"></div>
    
    <!-- Auth Card -->
    <div class="safe-center">
        <main class="auth-card viewport-form">
            <!-- Header -->
            <header class="auth-header">
                <div class="auth-logo">
                    <x-application-logo class="w-10 h-10" />
                    <span class="auth-logo-text">ELYSIUM</span>
                </div>
            <h1 class="auth-title">Crear Cuenta</h1>
            <p class="auth-subtitle">Únete a la transferencia P2P sin límites.</p>
        </header>

        <!-- Validation Errors -->
        @if($errors->any())
            <div class="auth-validation-errors" role="alert">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Register Form -->
        <form method="POST" action="{{ route('register') }}" class="auth-form">
            @csrf

            <!-- Name -->
            <div class="form-group">
                <label class="form-label" for="name">Nombre completo</label>
                <input 
                    id="name" 
                    type="text" 
                    name="name" 
                    class="form-input @error('name') is-invalid @enderror" 
                    value="{{ old('name') }}" 
                    placeholder="Tu nombre"
                    required 
                    autofocus 
                    autocomplete="name"
                >
                @error('name')
                    <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <!-- Email -->
            <div class="form-group">
                <label class="form-label" for="email">Correo electrónico</label>
                <input 
                    id="email" 
                    type="email" 
                    name="email" 
                    class="form-input @error('email') is-invalid @enderror" 
                    value="{{ old('email') }}" 
                    placeholder="tu@correo.com"
                    required 
                    autocomplete="username"
                >
                @error('email')
                    <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <!-- Password -->
            <div class="form-group">
                <label class="form-label" for="password">Contraseña</label>
                <div style="position: relative;">
                    <input 
                        id="password" 
                        type="password" 
                        name="password" 
                        class="form-input @error('password') is-invalid @enderror" 
                        placeholder="••••••••"
                        required 
                        autocomplete="new-password"
                        style="padding-right: 2.5rem;"
                    >
                    <button type="button" onclick="togglePassword('password')" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--text-muted); padding: 0.25rem; display: flex; align-items: center; justify-content: center;" aria-label="Mostrar contraseña">
                        <svg id="eye-password" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                    </button>
                </div>
                @error('password')
                    <span class="error-message">{{ $message }}</span>
                @enderror
                <div id="password-strength" style="margin-top: 0.5rem; font-size: 0.75rem; display: none;">
                    <div style="display: flex; gap: 0.25rem; margin-bottom: 0.375rem;">
                        <div id="strength-bar-1" style="flex: 1; height: 3px; background: rgba(255,255,255,0.1); border-radius: 2px; transition: all 0.3s;"></div>
                        <div id="strength-bar-2" style="flex: 1; height: 3px; background: rgba(255,255,255,0.1); border-radius: 2px; transition: all 0.3s;"></div>
                        <div id="strength-bar-3" style="flex: 1; height: 3px; background: rgba(255,255,255,0.1); border-radius: 2px; transition: all 0.3s;"></div>
                        <div id="strength-bar-4" style="flex: 1; height: 3px; background: rgba(255,255,255,0.1); border-radius: 2px; transition: all 0.3s;"></div>
                    </div>
                    <span id="strength-text" style="color: var(--text-muted);"></span>
                </div>
            </div>

            <!-- Confirm Password -->
            <div class="form-group">
                <label class="form-label" for="password_confirmation">Confirmar contraseña</label>
                <div style="position: relative;">
                    <input 
                        id="password_confirmation" 
                        type="password" 
                        name="password_confirmation" 
                        class="form-input" 
                        placeholder="••••••••"
                        required 
                        autocomplete="new-password"
                        style="padding-right: 2.5rem;"
                    >
                    <button type="button" onclick="togglePassword('password_confirmation')" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--text-muted); padding: 0.25rem; display: flex; align-items: center; justify-content: center;" aria-label="Mostrar contraseña">
                        <svg id="eye-password_confirmation" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                    </button>
                </div>
                <span id="password-match" style="margin-top: 0.375rem; font-size: 0.75rem; display: none;"></span>
            </div>

            <button type="submit" class="auth-btn auth-btn-primary">
                Registrarse
            </button>
        </form>

        <!-- Footer -->
        <footer class="auth-footer">
            ¿Ya tienes cuenta? 
            <a href="{{ route('login') }}">Iniciar sesión</a>
        </footer>
        </main>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const eye = document.getElementById('eye-' + fieldId);
            
            if (field.type === 'password') {
                field.type = 'text';
                eye.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />';
            } else {
                field.type = 'password';
                eye.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />';
            }
        }

        // Password strength checker
        const passwordInput = document.getElementById('password');
        const passwordConfirmation = document.getElementById('password_confirmation');
        const strengthIndicator = document.getElementById('password-strength');
        const strengthText = document.getElementById('strength-text');
        const matchIndicator = document.getElementById('password-match');

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            
            if (password.length === 0) {
                strengthIndicator.style.display = 'none';
                return;
            }
            
            strengthIndicator.style.display = 'block';
            
            let strength = 0;
            const bars = [
                document.getElementById('strength-bar-1'),
                document.getElementById('strength-bar-2'),
                document.getElementById('strength-bar-3'),
                document.getElementById('strength-bar-4')
            ];
            
            // Reset bars
            bars.forEach(bar => bar.style.background = 'rgba(255,255,255,0.1)');
            
            // Check length
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            
            // Check complexity
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            // Normalize to 4 levels
            const level = Math.min(Math.floor(strength / 1.5), 4);
            
            const colors = ['#ef4444', '#f59e0b', '#eab308', '#10b981'];
            const texts = ['Muy débil', 'Débil', 'Aceptable', 'Fuerte'];
            
            for (let i = 0; i < level; i++) {
                bars[i].style.background = colors[level - 1];
            }
            
            strengthText.textContent = texts[level - 1] || '';
            strengthText.style.color = colors[level - 1] || '';
            
            // Check match
            checkPasswordMatch();
        });

        passwordConfirmation.addEventListener('input', checkPasswordMatch);

        function checkPasswordMatch() {
            const password = passwordInput.value;
            const confirmation = passwordConfirmation.value;
            
            if (confirmation.length === 0) {
                matchIndicator.style.display = 'none';
                passwordConfirmation.style.borderColor = '';
                return;
            }
            
            matchIndicator.style.display = 'block';
            
            if (password === confirmation) {
                matchIndicator.textContent = '✓ Las contraseñas coinciden';
                matchIndicator.style.color = '#10b981';
                passwordConfirmation.style.borderColor = '#10b981';
            } else {
                matchIndicator.textContent = '✗ Las contraseñas no coinciden';
                matchIndicator.style.color = '#ef4444';
                passwordConfirmation.style.borderColor = '#ef4444';
            }
        }
    </script>
</body>
</html>