<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <title>Recuperar Contraseña | Elysium P2P</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
    
    <!-- Auth CSS -->
    @vite('resources/css/auth.css')
    
    <style>
        .recovery-step {
            display: none;
        }
        
        .recovery-step.active {
            display: block;
        }
        
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
        
        .recovery-code-input {
            font-family: 'Courier New', monospace;
            text-align: center;
            font-size: 1.1rem;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--auth-on-surface);
            opacity: 0.7;
            text-decoration: none;
            font-size: 0.8rem;
            margin-bottom: 1rem;
            transition: opacity 0.2s ease;
        }
        
        .back-link:hover {
            opacity: 1;
        }
        
        .info-box {
            margin-top: 1.5rem;
            padding: 1rem;
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.2);
            border-radius: 8px;
        }
        
        .info-box-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .info-box-title {
            color: #f59e0b;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .info-box-content {
            margin: 0;
            padding-left: 1rem;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.75rem;
        }
    </style>
</head>
<body>
    <!-- Background Glow -->
    <div class="auth-glow" aria-hidden="true"></div>
    
    <!-- Auth Card -->
    <main class="auth-card">
        <!-- Header -->
        <header class="auth-header">
            <div class="auth-logo">
                <div class="auth-logo-icon" aria-hidden="true"></div>
                <span class="auth-logo-text">ELYSIUM</span>
            </div>
            <h1 class="auth-title">Recuperar Contraseña</h1>
            <p class="auth-subtitle">Recupera el acceso a tu cuenta de forma segura.</p>
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

        <!-- Step 1: Email Input -->
        <div id="step-1" class="recovery-step active">
            <a href="{{ route('login') }}" class="back-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Volver al login
            </a>
            
            <form class="auth-form" onsubmit="checkEmail(event)">
                <div class="form-group">
                    <label class="form-label" for="email">Correo electrónico</label>
                    <input 
                        id="email" 
                        type="email" 
                        class="form-input" 
                        placeholder="tu@correo.com"
                        required 
                        autofocus
                        autocomplete="email"
                    >
                    <div style="font-size: 0.75rem; color: var(--auth-on-surface); opacity: 0.7; margin-top: 0.5rem;">
                        Ingresa el correo asociado a tu cuenta
                    </div>
                </div>

                <button type="submit" class="auth-btn auth-btn-primary">
                    Continuar
                </button>
            </form>
        </div>

        <!-- Step 2: Admin Recovery Code -->
        <div id="step-2" class="recovery-step">
            <a href="#" class="back-link" onclick="goToStep(1)">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Cambiar correo
            </a>
            
            <div class="admin-badge">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                Cuenta de Administrador Detectada
            </div>
            
            <form method="POST" action="{{ route('admin.recovery.verify') }}" class="auth-form">
                @csrf
                <input type="hidden" id="admin-email" name="email" value="">
                
                <div class="form-group">
                    <label class="form-label" for="recovery_code">Código de Recuperación</label>
                    <input 
                        id="recovery_code" 
                        type="text" 
                        name="recovery_code"
                        class="form-input recovery-code-input" 
                        placeholder="XXXX-XXXX-XXXX"
                        required
                        maxlength="14"
                        pattern="[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}"
                        autocomplete="one-time-code"
                    >
                    <div style="font-size: 0.75rem; color: var(--auth-on-surface); opacity: 0.7; margin-top: 0.5rem;">
                        Ingresa uno de tus códigos de recuperación de 12 caracteres
                    </div>
                </div>

                <button type="submit" class="auth-btn auth-btn-primary">
                    Verificar Código
                </button>
            </form>
            
            <div class="info-box">
                <div class="info-box-header">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color: #f59e0b;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <strong class="info-box-title">Información</strong>
                </div>
                <ul class="info-box-content">
                    <li>Los códigos tienen el formato: XXXX-XXXX-XXXX</li>
                    <li>Cada código solo se puede usar una vez</li>
                    <li>Si no tienes códigos, contacta a otro administrador</li>
                </ul>
            </div>
        </div>

        <!-- Step 3: Gmail Recovery Code (Usuarios Regulares) -->
        <div id="step-3" class="recovery-step">
            <a href="#" class="back-link" onclick="goToStep(1)">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Cambiar correo
            </a>
            
            <div class="admin-badge" style="background: rgba(34, 197, 94, 0.1); border-color: rgba(34, 197, 94, 0.2); color: #22c55e;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                Gmail Vinculado Detectado
            </div>
            
            <form method="POST" action="{{ route('user.gmail.verify') }}" class="auth-form" id="gmail-verify-form">
                @csrf
                <input type="hidden" id="gmail-user-email" name="email" value="">

                {{-- Alerta rate limit: solo visible después de 3 intentos --}}
                @if(session('attempts') >= 3 || $errors->has('gmail_code') && str_contains($errors->first('gmail_code'), 'Demasiados'))
                <div style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); border-radius: 8px; padding: 0.75rem 1rem; margin-bottom: 1rem; font-size: 0.8rem; color: #ef4444;">
                    ⚠️ {{ $errors->first('gmail_code') }}
                </div>
                @elseif($errors->has('gmail_code'))
                <div style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); border-radius: 8px; padding: 0.75rem 1rem; margin-bottom: 1rem; font-size: 0.8rem; color: #ef4444;">
                    {{ $errors->first('gmail_code') }}
                </div>
                @endif

                <div class="form-group">
                    <label class="form-label" for="gmail_code">Código de Gmail</label>
                    <input
                        id="gmail_code"
                        type="text"
                        name="gmail_code"
                        class="form-input recovery-code-input"
                        placeholder="123456"
                        required
                        maxlength="6"
                        pattern="[0-9]{6}"
                        style="letter-spacing: 4px;"
                        autocomplete="one-time-code"
                    >
                    <div style="font-size: 0.75rem; color: var(--auth-on-surface); opacity: 0.7; margin-top: 0.5rem;">
                        Código de 6 dígitos enviado a tu correo · Válido 10 minutos
                    </div>
                </div>

                <button type="submit" class="auth-btn auth-btn-primary">
                    Verificar Código
                </button>

                <button type="button" onclick="resendGmailCode()" id="resend-btn"
                    style="width:100%; margin-top:0.75rem; background:none; border:1px solid rgba(255,255,255,0.15); color:var(--auth-on-surface); border-radius:8px; padding:0.65rem; font-size:0.8rem; cursor:pointer; opacity:0.7;">
                    Reenviar código
                </button>
            </form>
            
            <div class="info-box">
                <div class="info-box-header">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color: #f59e0b;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <strong class="info-box-title">Información</strong>
                </div>
                <ul class="info-box-content">
                    <li>El código tiene 6 dígitos numéricos</li>
                    <li>Revisa tu bandeja de entrada y spam</li>
                    <li>El código expira en 10 minutos</li>
                    <li>Puedes solicitar un nuevo código si es necesario</li>
                </ul>
            </div>
        </div>

        <!-- Step 4: Regular User Email Sent -->
        <div id="step-4" class="recovery-step">
            <div style="text-align: center; margin-bottom: 2rem;">
                <div style="width: 64px; height: 64px; margin: 0 auto 1rem; background: rgba(16, 185, 129, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color: #10b981;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <h3 style="color: var(--auth-on-surface); margin-bottom: 0.5rem; font-size: 1.1rem;">Correo Enviado</h3>
                <p style="color: var(--auth-on-surface); opacity: 0.7; font-size: 0.9rem; margin: 0;">
                    Hemos enviado un enlace de recuperación a tu correo electrónico.
                </p>
            </div>
            
            <div class="info-box">
                <div class="info-box-header">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="color: #f59e0b;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <strong class="info-box-title">Instrucciones</strong>
                </div>
                <ul class="info-box-content">
                    <li>Revisa tu bandeja de entrada y carpeta de spam</li>
                    <li>El enlace expira en 60 minutos</li>
                    <li>Si no recibes el correo, intenta nuevamente</li>
                </ul>
            </div>
            
            <div style="margin-top: 1.5rem; text-align: center;">
                <a href="{{ route('login') }}" class="back-link" style="justify-content: center;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Volver al login
                </a>
            </div>
        </div>
    </main>

    <script>
        // Lista de emails de administradores
        const adminEmails = @json(\App\Models\User::where('is_admin', true)->pluck('email')->toArray());
        
        function goToStep(step) {
            // Ocultar todos los pasos
            document.querySelectorAll('.recovery-step').forEach(el => el.classList.remove('active'));
            
            // Mostrar el paso actual
            document.getElementById(`step-${step}`).classList.add('active');
        }
        
        function checkEmail(event) {
            event.preventDefault();
            
            const email = document.getElementById('email').value;
            
            // Verificar si es admin
            if (adminEmails.includes(email)) {
                // Es admin, ir al paso de código de recuperación
                document.getElementById('admin-email').value = email;
                goToStep(2);
            } else {
                // Es usuario regular, verificar si tiene Gmail vinculado
                checkUserGmailStatus(email);
            }
        }
        
        function checkUserGmailStatus(email) {
            // Aquí verificarías en el backend si el usuario tiene Gmail vinculado
            // Por ahora simulamos que algunos usuarios tienen Gmail vinculado
            
            // Simulación: usuarios con Gmail vinculado
            const usersWithGmail = JSON.parse(localStorage.getItem('users-with-gmail') || '[]');
            const hasGmail = usersWithGmail.includes(email);
            
            if (hasGmail) {
                // Usuario tiene Gmail vinculado, enviar código por Gmail
                sendGmailRecoveryCode(email);
            } else {
                // Usuario no tiene Gmail, usar recuperación estándar por email
                sendPasswordResetEmail(email);
            }
        }
        
        function sendGmailRecoveryCode(email) {
            document.getElementById('gmail-user-email').value = email;

            // Enviar código al backend (genera y envía por email)
            fetch('{{ route("user.gmail.send") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ email })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    goToStep(3);
                    showNotification('Código enviado a tu correo', 'success');
                    startResendCooldown();
                } else {
                    showNotification(data.message || 'Error al enviar el código', 'error');
                }
            })
            .catch(() => showNotification('Error de conexión', 'error'));
        }

        function resendGmailCode() {
            const email = document.getElementById('gmail-user-email').value;
            if (!email) return;
            sendGmailRecoveryCode(email);
        }

        // Cooldown de 60s para reenvío
        function startResendCooldown() {
            const btn = document.getElementById('resend-btn');
            if (!btn) return;
            let seconds = 60;
            btn.disabled = true;
            btn.style.opacity = '0.4';
            const interval = setInterval(() => {
                seconds--;
                btn.textContent = `Reenviar código (${seconds}s)`;
                if (seconds <= 0) {
                    clearInterval(interval);
                    btn.disabled = false;
                    btn.style.opacity = '0.7';
                    btn.textContent = 'Reenviar código';
                }
            }, 1000);
        }
        
        function sendPasswordResetEmail(email) {
            // Crear formulario para enviar email de recuperación
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("password.email") }}';
            form.style.display = 'none';
            
            // Token CSRF
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            form.appendChild(csrfToken);
            
            // Email
            const emailInput = document.createElement('input');
            emailInput.type = 'hidden';
            emailInput.name = 'email';
            emailInput.value = email;
            form.appendChild(emailInput);
            
            document.body.appendChild(form);
            form.submit();
        }
        
        // Auto-formatear código de recuperación
        document.addEventListener('DOMContentLoaded', function() {
            const codeInput = document.getElementById('recovery_code');
            if (codeInput) {
                codeInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/[^A-Z0-9]/g, '').toUpperCase();
                    
                    if (value.length > 12) {
                        value = value.substring(0, 12);
                    }
                    
                    // Formatear con guiones
                    if (value.length > 8) {
                        value = value.substring(0, 4) + '-' + value.substring(4, 8) + '-' + value.substring(8);
                    } else if (value.length > 4) {
                        value = value.substring(0, 4) + '-' + value.substring(4);
                    }
                    
                    e.target.value = value;
                });
            }
            
            // Auto-formatear código de Gmail
            const gmailCodeInput = document.getElementById('gmail_code');
            if (gmailCodeInput) {
                gmailCodeInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/[^0-9]/g, '');
                    
                    if (value.length > 6) {
                        value = value.substring(0, 6);
                    }
                    
                    e.target.value = value;
                });
            }
        });
        
        function showNotification(message, type = 'info') {
            // Crear notificación temporal
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 1rem 1.5rem;
                background: ${type === 'success' ? '#22c55e' : type === 'warning' ? '#f59e0b' : '#3b82f6'};
                color: white;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                z-index: 10000;
                font-size: 0.875rem;
                max-width: 300px;
                animation: slideIn 0.3s ease;
            `;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }
    </script>
</body>
</html>