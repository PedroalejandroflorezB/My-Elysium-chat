<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <title>@yield('title', 'Admin Dashboard') | Elysium P2P</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #0a0a0a;
            color: #ffffff;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Nav Rail Vertical */
        .nav-rail {
            position: fixed;
            left: 0;
            top: 0;
            width: 80px;
            height: 100vh;
            background: rgba(255, 255, 255, 0.03);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1.5rem 0;
            z-index: 1000;
        }

        .nav-logo {
            font-family: 'Manrope', sans-serif;
            font-size: 0.9rem;
            font-weight: 800;
            letter-spacing: 1px;
            color: #ffffff;
            margin-bottom: 2rem;
            writing-mode: vertical-rl;
            text-orientation: mixed;
        }

        .nav-buttons {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            flex: 1;
        }

        .nav-btn {
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            color: rgba(255, 255, 255, 0.7);
            position: relative;
        }

        .nav-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            transform: translateX(4px);
        }

        .nav-btn.active {
            background: rgba(99, 102, 241, 0.2);
            border-color: rgba(99, 102, 241, 0.4);
            color: #818cf8;
        }

        .nav-btn svg {
            width: 20px;
            height: 20px;
        }

        /* Tooltip */
        .nav-btn::after {
            content: attr(data-tooltip);
            position: absolute;
            left: 60px;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            font-size: 0.8rem;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1001;
        }

        .nav-btn:hover::after {
            opacity: 1;
            visibility: visible;
            left: 70px;
        }

        /* Main Content */
        .main-content {
            margin-left: 80px;
            min-height: 100vh;
            padding: 2rem;
            display: flex;
            flex-direction: column;
        }

        .admin-header {
            margin-bottom: 2rem;
        }

        .admin-title {
            font-family: 'Manrope', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .admin-subtitle {
            color: rgba(255, 255, 255, 0.6);
            font-size: 1rem;
        }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            flex: 1;
        }

        .dashboard-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }

        .dashboard-card:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .card-icon {
            width: 40px;
            height: 40px;
            background: rgba(99, 102, 241, 0.2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #818cf8;
        }

        .card-title {
            font-weight: 600;
            font-size: 1.1rem;
        }

        .card-stats {
            font-size: 2rem;
            font-weight: 700;
            color: #818cf8;
            margin-bottom: 0.5rem;
        }

        .card-description {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow: hidden;
            transform: scale(0.9) translateY(20px);
            transition: all 0.3s ease;
        }

        .modal-overlay.active .modal {
            transform: scale(1) translateY(0);
        }

        .modal-header {
            background: rgba(255, 255, 255, 0.05);
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-title {
            font-family: 'Manrope', sans-serif;
            font-size: 1.3rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .modal-close {
            width: 32px;
            height: 32px;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 8px;
            color: rgba(255, 255, 255, 0.7);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
        }

        .modal-body {
            padding: 1.5rem;
            max-height: 60vh;
            overflow-y: auto;
        }

        .modal-footer {
            background: rgba(255, 255, 255, 0.02);
            padding: 1rem 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.9rem;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: #ffffff;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: rgba(99, 102, 241, 0.5);
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
        }

        .form-select {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: #ffffff;
            font-size: 0.9rem;
            cursor: pointer;
        }

        /* Button Styles */
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-primary {
            background: rgba(99, 102, 241, 0.2);
            color: #818cf8;
            border: 1px solid rgba(99, 102, 241, 0.3);
        }

        .btn-primary:hover {
            background: rgba(99, 102, 241, 0.3);
            border-color: rgba(99, 102, 241, 0.5);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .btn-danger {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .btn-danger:hover {
            background: rgba(239, 68, 68, 0.2);
            border-color: rgba(239, 68, 68, 0.4);
        }

        /* Table Styles */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .data-table th {
            background: rgba(255, 255, 255, 0.05);
            padding: 0.75rem 1rem;
            text-align: left;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: rgba(255, 255, 255, 0.7);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .data-table td {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 0.9rem;
        }

        .data-table tr:hover {
            background: rgba(255, 255, 255, 0.02);
        }

        .user-id {
            font-family: 'Courier New', monospace;
            color: rgba(255, 255, 255, 0.6);
            font-weight: 600;
        }

        .role-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .role-admin {
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        .role-user {
            background: rgba(156, 163, 175, 0.2);
            color: #9ca3af;
            border: 1px solid rgba(156, 163, 175, 0.3);
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-sm {
            padding: 0.5rem 0.75rem;
            font-size: 0.8rem;
        }

        /* Alert Styles */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
            border: 1px solid rgba(34, 197, 94, 0.2);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-rail {
                width: 60px;
            }
            
            .main-content {
                margin-left: 60px;
                padding: 1rem;
            }
            
            .modal {
                width: 95%;
                margin: 1rem;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Nav Rail -->
    <nav class="nav-rail">
        <div class="nav-logo">ELYSIUM</div>
        
        <div class="nav-buttons">
            <button class="nav-btn" data-tooltip="Perfil Admin" onclick="openModal('profile-modal')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </button>
            
            <button class="nav-btn" data-tooltip="Gestionar Usuarios" onclick="openModal('users-modal')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </button>
            
            <button class="nav-btn" data-tooltip="Crear Usuario" onclick="openModal('create-user-modal')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
            </button>
            
            <button class="nav-btn" data-tooltip="Gestionar Roles" onclick="openModal('roles-modal')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </button>
            
            <button class="nav-btn" data-tooltip="Códigos de Recuperación" onclick="openModal('recovery-codes-modal')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
            </button>
        </div>
        
        <button class="nav-btn" data-tooltip="Cerrar Sesión" onclick="logout()">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
        </button>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-error">
                <ul style="margin:0; padding-left:20px;">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    @yield('modals')

    <!-- Modal de Códigos de Recuperación -->
    <div id="recovery-codes-modal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 20px; height: 20px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                    Códigos de Recuperación
                </h3>
                <button class="modal-close" onclick="closeModal('recovery-codes-modal')">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 16px; height: 16px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <div class="modal-body">
                <div style="margin-bottom: 1.5rem;">
                    <p style="color: rgba(255, 255, 255, 0.8); line-height: 1.5;">
                        Los códigos de recuperación te permiten acceder a tu cuenta de administrador si pierdes tu contraseña principal. 
                        Cada código solo se puede usar una vez.
                    </p>
                </div>
                
                <div id="recovery-codes-list">
                    <!-- Los códigos se cargan dinámicamente aquí -->
                </div>
            </div>
            
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="downloadRecoveryCodes()">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 16px; height: 16px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Descargar TXT
                </button>
                <button class="btn btn-primary" onclick="generateRecoveryCodes()">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 16px; height: 16px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Generar Nuevos Códigos
                </button>
                <button class="btn btn-secondary" onclick="closeModal('recovery-codes-modal')">Cerrar</button>
            </div>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
                
                // Si es el modal de códigos de recuperación, cargar códigos existentes
                if (modalId === 'recovery-codes-modal') {
                    loadRecoveryCodes();
                }
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        }

        function logout() {
            if (confirm('¿Estás seguro de que quieres cerrar sesión?')) {
                document.getElementById('logout-form').submit();
            }
        }

        // Generar códigos de recuperación
        function generateRecoveryCodes() {
            const codes = [];
            for (let i = 0; i < 8; i++) {
                codes.push(generateRandomCode());
            }

            // Guardar en localStorage (para mostrar en UI y descargar)
            localStorage.setItem('elysium_recovery_codes', JSON.stringify({
                codes: codes,
                generated_at: new Date().toISOString(),
                user_id: {{ auth()->id() }}
            }));

            // ✅ Enviar al servidor para que el backend pueda validarlos
            fetch('{{ route("admin.recovery.save-codes") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ codes })
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) console.error('[Admin] Error guardando códigos en servidor:', data.message);
                else console.log('[Admin] ✅ Códigos guardados en servidor');
            })
            .catch(err => console.error('[Admin] Error de red al guardar códigos:', err));

            displayRecoveryCodes(codes);
        }

        function generateRandomCode() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let code = '';
            for (let i = 0; i < 12; i++) {
                if (i > 0 && i % 4 === 0) code += '-';
                code += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            return code;
        }

        function loadRecoveryCodes() {
            const stored = localStorage.getItem('elysium_recovery_codes');
            if (stored) {
                const data = JSON.parse(stored);
                if (data.user_id == {{ auth()->id() }}) {
                    displayRecoveryCodes(data.codes, data.generated_at);
                    return;
                }
            }
            
            // No hay códigos, mostrar mensaje
            document.getElementById('recovery-codes-list').innerHTML = `
                <div style="text-align: center; padding: 2rem; color: rgba(255, 255, 255, 0.6);">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 48px; height: 48px; margin: 0 auto 1rem; opacity: 0.5;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                    <p>No tienes códigos de recuperación generados.</p>
                    <p style="font-size: 0.8rem; margin-top: 0.5rem;">Haz clic en "Generar Nuevos Códigos" para crear tus códigos de respaldo.</p>
                </div>
            `;
        }

        function displayRecoveryCodes(codes, generatedAt = null) {
            const generatedDate = generatedAt ? new Date(generatedAt).toLocaleString('es-ES') : new Date().toLocaleString('es-ES');
            
            document.getElementById('recovery-codes-list').innerHTML = `
                <div style="margin-bottom: 1rem; padding: 1rem; background: rgba(255, 255, 255, 0.03); border-radius: 8px;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 16px; height: 16px; color: #22c55e;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <strong style="color: #22c55e;">Códigos Generados</strong>
                    </div>
                    <p style="font-size: 0.8rem; color: rgba(255, 255, 255, 0.6);">
                        Generados el: ${generatedDate}
                    </p>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.75rem; margin-bottom: 1.5rem;">
                    ${codes.map((code, index) => `
                        <div style="background: rgba(255, 255, 255, 0.05); padding: 0.75rem; border-radius: 6px; border: 1px solid rgba(255, 255, 255, 0.1);">
                            <div style="font-size: 0.7rem; color: rgba(255, 255, 255, 0.5); margin-bottom: 0.25rem;">Código ${index + 1}</div>
                            <div style="font-family: 'Courier New', monospace; font-size: 0.9rem; font-weight: 600; color: #ffffff;">${code}</div>
                        </div>
                    `).join('')}
                </div>
                
                <div style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.2); border-radius: 8px; padding: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width: 16px; height: 16px; color: #f59e0b;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                        <strong style="color: #f59e0b;">Importante</strong>
                    </div>
                    <ul style="margin: 0; padding-left: 1rem; color: rgba(255, 255, 255, 0.8); font-size: 0.8rem;">
                        <li>Guarda estos códigos en un lugar seguro</li>
                        <li>Úsalos solo si pierdes acceso a tu cuenta</li>
                        <li>Cada código solo se puede usar una vez</li>
                        <li>Descárgalos como archivo de texto para mayor seguridad</li>
                    </ul>
                </div>
            `;
        }

        function downloadRecoveryCodes() {
            const stored = localStorage.getItem('elysium_recovery_codes');
            if (!stored) {
                alert('No hay códigos de recuperación para descargar. Genera códigos primero.');
                return;
            }
            
            const data = JSON.parse(stored);
            const content = `ELYSIUM P2P - CÓDIGOS DE RECUPERACIÓN
========================================

Usuario: {{ auth()->user()->name }} ({{ auth()->user()->email }})
Generados: ${new Date(data.generated_at).toLocaleString('es-ES')}

CÓDIGOS DE RECUPERACIÓN:
${data.codes.map((code, index) => `${index + 1}. ${code}`).join('\n')}

INSTRUCCIONES:
- Guarda este archivo en un lugar seguro
- Cada código solo se puede usar una vez
- Úsalos solo si pierdes acceso a tu cuenta principal
- No compartas estos códigos con nadie

========================================
Elysium P2P Admin Recovery Codes`;

            const blob = new Blob([content], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `elysium-recovery-codes-${new Date().toISOString().split('T')[0]}.txt`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }

        // Cerrar modal con Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const activeModal = document.querySelector('.modal-overlay.active');
                if (activeModal) {
                    activeModal.classList.remove('active');
                    document.body.style.overflow = '';
                }
            }
        });

        // Cerrar modal clickeando fuera
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-overlay')) {
                e.target.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    </script>

    <!-- Logout Form -->
    <form id="logout-form" method="POST" action="{{ route('logout') }}" style="display: none;">
        @csrf
    </form>
</body>
</html>