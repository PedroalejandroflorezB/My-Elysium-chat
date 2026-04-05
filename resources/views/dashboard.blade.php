<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <title>Panel | Elysium P2P</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
    
    <!-- Dashboard CSS -->
    @vite(['resources/css/dashboard.css', 'resources/js/app.js'])
</head>
<body>
    <!-- Background Glow -->
    <div class="dashboard-glow" aria-hidden="true"></div>
    
    <!-- MOBILE HEADER (Hamburger) -->
    <header class="mobile-header">
        <div class="mobile-logo-text">ELYSIUM</div>
        <button class="hamburger-btn" id="mobile-menu-toggle" aria-label="Menú">
            <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
        </button>
    </header>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="dashboard-sidebar" id="dashboard-sidebar">
            <div class="dashboard-logo">
                <div class="dashboard-logo-icon"></div>
                <span class="dashboard-logo-text">ELYSIUM</span>
            </div>
            
            <nav class="dashboard-nav">
                <a href="{{ route('dashboard') }}" class="dashboard-nav-link active">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                    Inicio
                </a>
                <a href="{{ route('chat.index') }}" class="dashboard-nav-link">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                    Chat & P2P
                </a>
                <a href="{{ route('profile.edit') }}" class="dashboard-nav-link">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    Perfil
                </a>
                @if(auth()->user()->is_admin ?? false)
                    <a href="{{ route('admin.dashboard') }}" class="dashboard-nav-link admin-link">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                        Admin (CRUD)
                    </a>
                @endif
            </nav>
            
            <!-- User Info -->
            <div class="dashboard-sidebar-user">
                <div class="dashboard-sidebar-user-name">{{ auth()->user()->name }}</div>
                <div class="dashboard-sidebar-user-email">{{ auth()->user()->email }}</div>
            </div>
            
            <!-- Logout -->
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="dashboard-logout-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                    Cerrar Sesión
                </button>
            </form>
        </aside>

        <!-- Overlay para móvil -->
        <div class="sidebar-overlay" id="sidebar-overlay"></div>
        
        <!-- Main Content -->
        <main class="dashboard-main">
            <header class="dashboard-header desktop-only">
                <div>
                    <h1 class="dashboard-title">Panel de Control</h1>
                    <p class="dashboard-welcome">Bienvenido, {{ auth()->user()->name }}</p>
                </div>
            </header>
            
            <!-- Bloque Simplificado CRUD -->
            <section class="dashboard-section simplified-hub">
                <div class="welcome-box">
                    <h2>¿Qué deseas hacer hoy?</h2>
                    <p>Elysium es tu portal privado para transferencias P2P seguras y mensajería en tiempo real.</p>
                </div>
                
                <div class="quick-actions-grid">
                    <a href="{{ route('chat.index') }}" class="action-card primary-action">
                        <div class="action-icon">💬</div>
                        <div class="action-text">
                            <h3>Lanzar Aplicación (Chat & P2P)</h3>
                            <p>Envía mensajes y transfiere archivos grandes de forma directa.</p>
                        </div>
                    </a>
                    
                    <a href="{{ route('profile.edit') }}" class="action-card secondary-action">
                        <div class="action-icon">⚙️</div>
                        <div class="action-text">
                            <h3>Gestionar Perfil</h3>
                            <p>Actualiza tu avatar, nombre o contraseña desde aquí.</p>
                        </div>
                    </a>
                </div>
            </section>
        </main>
    </div>

    <script>
        // Lógica súper rápida para el menú hamburguesa (Soporte Touch + Click nativo)
        document.addEventListener('DOMContentLoaded', () => {
            const toggleBtn = document.getElementById('mobile-menu-toggle');
            const sidebar = document.getElementById('dashboard-sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            
            const toggleMenu = (e) => {
                e?.preventDefault();
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            };
            
            if (toggleBtn && sidebar && overlay) {
                const handleInteraction = (e) => {
                    if (e.type === 'touchstart') window._isTouchDash = true;
                    if (e.type === 'click' && window._isTouchDash) return;
                    toggleMenu(e);
                };

                toggleBtn.addEventListener('click', handleInteraction);
                toggleBtn.addEventListener('touchstart', handleInteraction, { passive: false });
                
                overlay.addEventListener('click', handleInteraction);
                overlay.addEventListener('touchstart', handleInteraction, { passive: false });
            }
        });
    </script>
</body>
</html>