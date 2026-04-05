<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <title>Verificar Correo | Elysium P2P</title>
    
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
            <h1 class="auth-title">VERIFICAR CORREO</h1>
            <p class="auth-subtitle">Revisa tu bandeja de entrada y confirma tu dirección.</p>
        </header>

        @if(session('status') == 'verification-link-sent')
            <div class="auth-status auth-status-success" role="status">
                ✅ Hemos enviado un nuevo enlace de verificación a {{ auth()->user()->email }}
            </div>
        @endif

        <div style="background: rgba(133, 173, 255, 0.1); border: 1px solid rgba(133, 173, 255, 0.3); border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem; text-align: center;">
            <p style="color: var(--auth-on-surface-variant); font-size: 0.85rem; line-height: 1.6;">
                Antes de continuar, por favor verifica tu dirección de correo electrónico revisando el enlace que te enviamos.
            </p>
        </div>

        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <form method="POST" action="{{ route('verification.send') }}" class="auth-form">
                @csrf
                <button type="submit" class="auth-btn auth-btn-primary">
                    REENVIAR CORREO DE VERIFICACIÓN
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="auth-form">
                @csrf
                <button type="submit" class="auth-btn auth-btn-secondary">
                    CERRAR SESIÓN
                </button>
            </form>
        </div>

        <footer class="auth-footer">
            <p style="font-size: 0.75rem; margin-top: 1rem;">
                ¿No recibiste el correo? Revisa tu carpeta de spam o 
                <button 
                    onclick="event.preventDefault(); document.querySelector('form[action=\'{{ route('verification.send') }}\']').submit();" 
                    style="background: none; border: none; color: var(--auth-primary); cursor: pointer; text-decoration: underline; font-size: 0.75rem;"
                >
                    reenviar
                </button>
            </p>
        </footer>
    </main>
</body>
</html>