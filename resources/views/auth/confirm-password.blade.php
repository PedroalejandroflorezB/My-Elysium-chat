<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <title>Confirmar Contraseña | Elysium P2P</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
    
    @vite('resources/css/auth.css')
</head>
<body>
    <div class="auth-glow" aria-hidden="true"></div>
    
    <main class="auth-card">
        <header class="auth-header">
            <div class="auth-logo">
                <div class="auth-logo-icon" aria-hidden="true"></div>
                <span class="auth-logo-text">ELYSIUM</span>
            </div>
            <h1 class="auth-title">CONFIRMAR CONTRASEÑA</h1>
            <p class="auth-subtitle">Esta es un área segura. Por seguridad, confirma tu contraseña.</p>
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

        <form method="POST" action="{{ route('password.confirm') }}" class="auth-form">
            @csrf

            <div class="form-group">
                <label class="form-label" for="password">CONTRASEÑA</label>
                <div style="position: relative;">
                    <input 
                        id="password" 
                        type="password" 
                        name="password" 
                        class="form-input @error('password') is-invalid @enderror" 
                        placeholder="••••••••"
                        required 
                        autofocus 
                        autocomplete="current-password"
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
            </div>

            <button type="submit" class="auth-btn auth-btn-primary">
                CONFIRMAR
            </button>
        </form>

        <footer class="auth-footer">
            <a href="{{ route('login') }}">← Volver al inicio de sesión</a>
        </footer>
    </main>

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
    </script>
</body>
</html>