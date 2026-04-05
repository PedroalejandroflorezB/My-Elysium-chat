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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
    
    <!-- Dashboard CSS Centralizado -->
    @vite(['resources/css/dashboard.css', 'resources/js/app.js'])
    <style>
        .admin-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 1rem; 
        }
        .admin-table th, .admin-table td { 
            padding: 0.875rem 1rem; 
            text-align: left; 
            border-bottom: 1px solid rgba(255, 255, 255, 0.05); 
        }
        .admin-table th { 
            font-family: 'Manrope', sans-serif; 
            opacity: 0.7; 
            font-size: 0.8125rem; 
            font-weight: 600;
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
        }
        .admin-table tr:hover {
            background: rgba(255, 255, 255, 0.02);
        }
        .admin-badge { 
            padding: 0.375rem 0.625rem; 
            border-radius: 6px; 
            font-size: 0.75rem; 
            font-weight: 600; 
            display: inline-block;
        }
        .badge-admin { 
            background: rgba(56, 189, 248, 0.1); 
            color: #38bdf8; 
            border: 1px solid rgba(56, 189, 248, 0.2);
        }
        .badge-user { 
            background: rgba(255, 255, 255, 0.05); 
            color: #9ca3af; 
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .admin-actions { 
            display: flex; 
            gap: 0.5rem; 
        }
        .btn-sm { 
            padding: 0.5rem 0.875rem; 
            font-size: 0.8125rem; 
            border-radius: 6px; 
            cursor: pointer; 
            transition: all 0.2s ease; 
            border: none; 
            font-weight: 500;
            min-height: 36px;
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
        }
        .btn-edit { 
            background: rgba(255, 255, 255, 0.03); 
            color: var(--text-primary); 
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .btn-edit:hover { 
            background: rgba(255, 255, 255, 0.08); 
            border-color: rgba(255, 255, 255, 0.2);
        }
        .btn-danger { 
            background: rgba(239, 68, 68, 0.1); 
            color: #ef4444; 
            border: 1px solid rgba(239, 68, 68, 0.2); 
        }
        .btn-danger:hover { 
            background: #ef4444; 
            color: white; 
            border-color: #ef4444;
        }
        
        .admin-form-group { 
            margin-bottom: 1.25rem; 
        }
        .admin-label { 
            display: block; 
            margin-bottom: 0.375rem; 
            font-family: 'Manrope', sans-serif; 
            font-size: 0.8125rem; 
            font-weight: 600;
            color: var(--text-primary);
        }
        .admin-input { 
            width: 100%; 
            padding: 0.6875rem 0.875rem; 
            background: var(--bg-secondary); 
            border: 1px solid rgba(255, 255, 255, 0.1); 
            color: var(--text-primary); 
            border-radius: 8px; 
            font-family: 'Inter', sans-serif; 
            font-size: 0.875rem;
            transition: all 0.2s ease; 
            min-height: 44px;
        }
        .admin-input:focus { 
            border-color: var(--primary); 
            outline: none; 
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2); 
        }
        .alert { 
            padding: 0.875rem 1rem; 
            border-radius: 8px; 
            margin-bottom: 1rem; 
            font-weight: 500; 
            font-size: 0.8125rem;
            line-height: 1.5;
        }
        .alert-success { 
            background: rgba(16, 185, 129, 0.1); 
            color: #10b981; 
            border: 1px solid rgba(16, 185, 129, 0.2); 
        }
        .alert-error { 
            background: rgba(239, 68, 68, 0.1); 
            color: #ef4444; 
            border: 1px solid rgba(239, 68, 68, 0.2); 
        }
        .alert ul {
            margin: 0.5rem 0 0 0;
            padding-left: 1.25rem;
        }
        .alert li {
            margin: 0.25rem 0;
        }
    </style>
</head>
<body>
    <div class="dashboard-glow" aria-hidden="true"></div>
    
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="dashboard-sidebar">
            <div class="dashboard-logo">
                <x-application-logo class="w-8 h-8" />
                <span class="dashboard-logo-text">ELYSIUM</span>
            </div>
            
            <nav class="dashboard-nav">
                <a href="{{ route('dashboard') }}" class="dashboard-nav-link">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    Volver a Panel Usuario
                </a>
                
                <h4 style="margin: 2rem 0 0.5rem 1.5rem; font-size: 0.75rem; color: #888; text-transform: uppercase;">Administración</h4>
                
                <a href="{{ route('admin.users.index') }}" class="dashboard-nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                    Usuarios
                </a>
            </nav>
            
            <div class="dashboard-sidebar-user">
                <div class="dashboard-sidebar-user-name">{{ auth()->user()->name }}</div>
                <div class="dashboard-sidebar-user-email">Administrador</div>
            </div>
            
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="dashboard-logout-btn">
                    Cerrar Sesión
                </button>
            </form>
        </aside>
        
        <!-- Main Content -->
        <main class="dashboard-main">
            <header class="dashboard-header">
                <div>
                    <h1 class="dashboard-title">@yield('title')</h1>
                    <p class="dashboard-welcome">@yield('subtitle', 'Panel de Administración de Elysium P2P')</p>
                </div>
            </header>
            
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
            
            <!-- Dynamic Content -->
            @yield('content')
            
        </main>
    </div>
</body>
</html>
