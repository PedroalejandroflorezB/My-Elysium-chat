<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">

    @auth
    <meta name="user-id" content="{{ auth()->user()->id }}">
    <meta name="user-username" content="{{ auth()->user()->username }}">
    <meta name="user-name" content="{{ auth()->user()->name }}">
    @endauth

    {{-- Aplicar tema ANTES de que cargue el CSS para evitar FOUC --}}
    <script>
        (function() {
            const themes = ['dark', 'light', 'petrol', 'noir', 'vegas', 'neon', 'storm'];
            let theme = localStorage.getItem('elysium-theme-mode') || 'dark';
            if (!themes.includes(theme)) theme = 'dark';
            document.documentElement.classList.add('theme-' + theme);
        })();
    </script>

    <title>@yield('title', 'Elysium P2P')</title>

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    @vite([
        'resources/css/app.css',
        'resources/css/components/topbar.css',
        'resources/css/components/sidebar.css',
        'resources/css/components/chat.css',
        'resources/css/components/modals.css',
        'resources/css/components/toast.css',
        'resources/css/components/qr-generator.css',
        'resources/js/app.js',
        'resources/js/components/chat.js',
        'resources/js/components/qr-generator.js'
    ])
    
    @stack('styles')
    {{-- Revelar página cuando los CSS estén listos --}}
</head>
<body class="antialiased">
    <div class="app-container">
        @yield('content')
    </div>
    
    <!-- ✅ CRÍTICO: Incluir modal de perfil -->
    @include('chat.partials.modals.profile')
    
    <!-- ✅ CRÍTICO: Incluir modal de seguridad -->
    @include('chat.partials.modals.security')

    <!-- ✅ CRÍTICO: Incluir modal de solicitud de contacto entrante -->
    @include('chat.partials.modals.contact-request-pending')

    <!-- ✅ CRÍTICO: Incluir modales QR (solo generador — scanner es solo Android) -->
    @include('chat.partials.modals.qr-generator')
    
    <!-- ✅ CRÍTICO: Incluir modales P2P -->
    @include('chat.partials.p2p-modals')
    
    @stack('scripts')
</body>
</html>