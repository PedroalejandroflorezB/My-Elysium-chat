<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <title>Iniciar Sesión | Elysium P2P</title>
    
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
    <main class="auth-card">
        <!-- Header -->
        <header class="auth-header">
            <div class="auth-logo">
                <x-application-logo class="w-10 h-10" />
                <span class="auth-logo-text">ELYSIUM</span>
            </div>
            <h1 class="auth-title">Iniciar Sesión</h1>
            <p class="auth-subtitle">Accede a tu espacio de transferencia segura.</p>
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

        <!-- Session Status -->
        @if(session('status'))
            <div class="auth-status auth-status-success" role="status">
                {{ session('status') }}
            </div>
        @endif

        <!-- Login Form -->
        <form method="POST" action="{{ route('login') }}" class="auth-form">
            @csrf

            <!-- Email -->
            <div class="form-group">
                <label class="form-label" for="email">Correo electrónico</label>
                <input 
                    id="email" 
                    type="email" 
                    name="email" 
                    class="form-input @error('email') is-invalid @enderror" 
                    autocomplete="email"
                    value="{{ old('email') }}" 
                    placeholder="tu@correo.com"
                    required 
                    autofocus 
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
                        autocomplete="current-password"
                        style="padding-right: 2.5rem;"
                    >
                    <button type="button" onclick="togglePassword('password')" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--text-muted); padding: 0.25rem; display: flex; align-items: center; justify-content: center;" aria-label="Mostrar contraseña">
                        <svg id="eye-password" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                    </button>
                </div>
                @error('password')
                    <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <!-- Remember Me -->
            <div class="auth-checkbox-group">
                <input 
                    id="remember" 
                    type="checkbox" 
                    name="remember"
                    class="auth-checkbox"
                >
                <label for="remember" class="auth-checkbox-label">
                    Mantener sesión activa
                </label>
            </div>

            <!-- Forgot Password Link -->
            @if(Route::has('password.request'))
                <div style="text-align: right;">
                    <a 
                        href="{{ route('password.request') }}" 
                        style="font-size: 0.75rem; color: var(--auth-on-surface-variant); text-decoration: none;"
                    >
                        ¿Olvidaste tu contraseña?
                    </a>
                </div>
            @endif

            <button type="submit" class="auth-btn auth-btn-primary">
                Acceder
            </button>
        </form>

        <!-- Footer -->
        <footer class="auth-footer">
            ¿No tienes cuenta? 
            <a href="{{ route('register') }}">Crear una ahora</a>
        </footer>
    </main>

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
    </script>
</body>
</html>