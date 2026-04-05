<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <title>Elysium P2P | Transferencia Segura y Mensajería Privada Nexo</title>

    <script>
        (function() {
            const theme = localStorage.getItem('elysium-theme-mode') || 'petrol';
            document.documentElement.classList.add('theme-' + theme);
        })();
    </script>
    <meta name="description" content="Elysium P2P: La plataforma definitiva para transferencia de archivos segura y mensajería privada. Sin intermediarios, sin límites de tamaño, 100% privacidad Nexo.">
    <meta name="keywords" content="Elysium, P2P, Transferencia archivos, Mensajería privada, Privacidad Nexo, Seguridad digital, Peer-to-peer">
    <meta name="robots" content="index, follow">

    <!-- Open Graph / Social Media -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:title" content="Elysium P2P | Transferencia Segura Nexo">
    <meta property="og:description" content="Tus archivos, sin límites. Envía datos directamente de dispositivo a dispositivo con total privacidad y velocidad máxima.">
    <meta property="og:image" content="{{ asset('favicon.ico') }}"> 

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@700;800&family=Cinzel:wght@700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        html { scroll-behavior: smooth; }
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            font-size: 0.9rem;
            transition: background 0.5s ease, color 0.5s ease;
        }

        header {
            position: fixed; 
            top: 0; 
            width: 100%; 
            height: 80px; 
            background: var(--glass-bg); 
            backdrop-filter: blur(20px);
            z-index: 1000; 
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
        }

        nav { 
            width: 100%; 
            height: 100%; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 0 6%; 
        }

        .logo {
            font-family: 'Cinzel', serif;
            font-weight: 900;
            font-size: 1.25rem;
            letter-spacing: 0.15em;
            text-decoration: none;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.4rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        }

        .logo-icon { 
            width: 20px; 
            height: 20px; 
            background: var(--primary-gradient); 
            border-radius: 5px; 
            box-shadow: var(--shadow-glow);
        }

        /* 🎨 PALETTE SWITCHER - HEADER */
        .btn-palette-header {
            background: var(--glass-bg);
            border: 1px solid var(--border-color);
            color: var(--primary);
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.875rem;
            backdrop-filter: blur(5px);
        }

        .btn-palette-header:hover {
            background: var(--bg-secondary);
            border-color: var(--primary);
            box-shadow: var(--shadow-glow);
            transform: translateY(-1px);
        }

        .btn-palette-header:active {
            transform: translateY(0);
            box-shadow: var(--input-focus-glow);
        }

        /* 🎨 PALETTE SWITCHER - HERO */
        .palette-switcher-hero {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 1.5rem;
            animation: fadeIn 1s ease 0.5s both;
        }

        .btn-palette {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-color);
            color: var(--primary);
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            font-size: 1.1rem;
            backdrop-filter: blur(5px);
        }

        .btn-palette:hover {
            background: var(--bg-secondary);
            border-color: var(--primary);
            box-shadow: var(--shadow-glow);
            transform: translateY(-2px);
        }

        .palette-label {
            font-size: 0.65rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 700;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .nav-links { display: flex; gap: 32px; list-style: none; }
        .nav-links a { 
            color: var(--text-muted); 
            text-decoration: none; 
            font-size: 0.8rem; 
            font-weight: 500;
            transition: 0.3s; 
        }
        .nav-links a:hover { color: var(--primary); }

        .btn-primary, .btn-secondary {
            display: inline-block; 
            padding: 0.7rem 1.4rem; 
            border-radius: 8px;
            font-weight: 700; 
            text-decoration: none; 
            text-transform: uppercase;
            letter-spacing: 0.08em; 
            font-size: 0.65rem; 
            transition: 0.3s; 
            cursor: pointer; 
            border: none;
        }
        
        .btn-primary { 
            background: var(--primary-gradient); 
            color: var(--bg-primary); 
            box-shadow: var(--shadow-glow);
        }
        
        .btn-secondary { 
            background: rgba(255, 255, 255, 0.03); 
            color: var(--text-primary); 
            border: 1px solid var(--border-color); 
        }

        .btn-secondary:hover {
            background: var(--bg-secondary);
            border-color: var(--primary);
        }

        main section { 
            min-height: 100dvh;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 100px 8% 40px 8%; 
            position: relative;
        }

        .hero-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            align-items: center;
            gap: 40px;
            width: 100%;
        }

        .hero-title {
            font-family: 'Manrope', sans-serif; font-size: clamp(1.8rem, 4vw, 3.5rem);
            font-weight: 800; line-height: 1.1; margin-bottom: 1.5rem;
            background: var(--primary-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            text-transform: uppercase;
            letter-spacing: -1px;
        }

        .hero-description {
            color: var(--text-muted); max-width: 450px;
            font-size: 0.95rem; line-height: 1.6; margin-bottom: 2rem;
            border-left: 2px solid var(--primary);
            padding-left: 20px;
        }

        .hero-image-container {
            width: 100%; height: 420px; background: var(--bg-secondary);
            border-radius: 24px; display: flex; align-items: center;
            justify-content: center; border: 1px solid var(--border-color);
            box-shadow: 0 10px 40px rgba(0,0,0,0.4);
            position: relative;
            overflow: hidden;
        }

        .hero-image-container::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, transparent, var(--bg-primary));
            opacity: 0.6;
        }

        .features-content { width: 100%; max-width: 1100px; text-align: center; }
        
        .features-grid {
            display: grid; 
            grid-template-columns: repeat(3, 1fr);
            gap: 24px; 
            margin-top: 40px;
        }
        
        .feature-card {
            background: var(--bg-secondary); 
            padding: 45px 35px;
            border-radius: 24px;
            border: 1px solid var(--border-color); 
            transition: 0.4s ease;
            text-align: left;
            min-height: 240px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }

        .feature-card:hover {
            transform: translateY(-8px);
            border-color: var(--primary-container);
            background: var(--bg-hover);
        }

        .feature-card h3 { 
            font-family: 'Manrope'; 
            font-size: 1rem;
            margin-bottom: 15px; 
            color: var(--primary); 
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .feature-card p { 
            font-size: 0.85rem;
            color: var(--text-muted); 
            line-height: 1.6; 
        }

        .contact-container {
            width: 100%;
            max-width: 350px; 
            background: var(--bg-secondary);
            padding: 1.5rem 1.25rem; 
            border-radius: 24px; 
            border: 1px solid var(--border-color);
            backdrop-filter: blur(10px);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .contact-container h2 { 
            font-family: 'Manrope'; 
            font-size: 1.125rem; 
            margin-bottom: 0.375rem; 
            text-align: center; 
            letter-spacing: 1px; 
            color: var(--primary); 
        }

        .input-group { 
            margin-bottom: 0.875rem; 
        }
        
        label { 
            display: block; 
            font-size: 0.8125rem; 
            color: var(--primary); 
            margin-bottom: 0.375rem; 
            letter-spacing: 2px; 
            font-weight: 700; 
            text-transform: uppercase; 
        }
        
        input, textarea {
            width: 100%; 
            background: var(--bg-primary); 
            border: 1px solid var(--border-color);
            padding: 0.6875rem 0.875rem; 
            border-radius: 8px; 
            color: var(--text-primary); 
            font-family: inherit; 
            font-size: 0.875rem;
            transition: 0.2s;
            box-sizing: border-box;
            min-height: 44px;
        }
        
        textarea {
            min-height: 80px;
            max-height: 120px;
            resize: vertical;
        }
        
        input:focus, textarea:focus { 
            outline: none; 
            border-color: var(--primary); 
            background: var(--bg-hover);
            box-shadow: 0 0 0 2px rgba(133, 173, 255, 0.2);
        }

        .glow {
            position: absolute; width: 500px; height: 500px;
            background: radial-gradient(circle, var(--primary) 0%, transparent 70%);
            border-radius: 50%; filter: blur(80px); z-index: -1;
            opacity: 0.15;
        }

        @media (max-width: 768px) {
            .hero-container { grid-template-columns: 1fr; text-align: center; }
            .hero-description { margin: 0 auto 2rem auto; border-left: none; border-top: 2px solid var(--primary); padding: 20px 0 0 0; }
            .features-grid { grid-template-columns: 1fr; }
            .nav-links { display: none; }
            .hero-image-container { height: 280px; }
            .palette-switcher-hero { justify-content: center; }
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div style="display: flex; align-items: center; gap: 2rem;">
                <a href="#" class="logo">
                    <img src="{{ asset('logo.png') }}" alt="Elysium" style="height: 80px; width: auto; object-fit: contain; display: block; margin-right: -0.25rem;"> ELYSIUM
                </a>
                <!-- Palette Switcher en Header -->
                <button class="btn-palette-header" onclick="window.cycleTheme()" title="Cambiar tema de colores">
                    <i class="fas fa-palette"></i>
                </button>
            </div>
            <ul class="nav-links">
                <li><a href="#inicio">Inicio</a></li>
                <li><a href="#caracteristicas">Características</a></li>
                <li><a href="#contacto">Contacto</a></li>
            </ul>
           <div class="auth-group">
            @auth
                <span style="color: var(--text-muted); font-size: 0.7rem; margin-right: 15px;">
                    {{ auth()->user()->name }}
                </span>
                <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn-secondary">Salir</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="btn-secondary">Entrar</a>
                <a href="{{ route('register') }}" class="btn-primary" style="margin-left: 10px;">Comenzar</a>
            @endauth
        </div>
        </nav>
    </header>

    <main>
        <section id="inicio">
            <div class="glow" style="top: 10%; left: -5%;"></div>
            <div class="hero-container">
                <article>
                    <div class="hero-logo-wrapper" style="margin-bottom: 2rem; animation: fadeIn 0.8s ease;">
                        <x-application-logo class="w-16 h-16" style="filter: drop-shadow(var(--shadow-glow))" />
                    </div>
                    <h1 class="hero-title">TUS ARCHIVOS. <br> SIN LÍMITES.</h1>
                    <p class="hero-description">
                        Mueve lo que quieras, cuando quieras. Con <strong>Elysium</strong>, tus archivos viajan directamente de dispositivo a dispositivo con tecnología P2P Nexo.
                    </p>
                    
                    <div class="palette-switcher-hero">
                        <button class="btn-palette" onclick="window.cycleTheme()" title="Cambiar estética (PETROL/NOIR/VEGAS/NEON)">
                            <i id="theme-icon" class="fas fa-palette"></i>
                        </button>
                        <span class="palette-label">Personalizar Nexus</span>
                    </div>
                </article>
                <div class="hero-image-container">
                    <span style="color: var(--primary); letter-spacing: 0.5em; font-size: 0.65rem; font-weight: 800; font-family: 'Manrope'; opacity: 0.6; z-index: 1;">CONEXIÓN_DIRECTA_P2P_ACTIVA</span>
                    <div class="glow" style="width: 200px; height: 200px; opacity: 0.4;"></div>
                </div>
            </div>
        </section>

        <section id="caracteristicas">
            <div class="features-content">
                <h2 style="font-family: 'Manrope'; font-size: 1.3rem; opacity: 0.9; letter-spacing: 4px; margin-bottom: 15px;">LO QUE NOS HACE DIFERENTES</h2>
                <div class="features-grid">
                    <article class="feature-card">
                        <h3>LIBERTAD DE TAMAÑO</h3>
                        <p>Desde una simple foto hasta proyectos de varios gigas. Al ser una conexión directa, no dependes de límites de almacenamiento ni de planes de pago.</p>
                    </article>
                    <article class="feature-card">
                        <h3>FIDELIDAD TOTAL</h3>
                        <p>Tus fotos, videos y documentos llegan exactamente como están en tu equipo. Sin procesos automáticos que alteren o compriman tus archivos originales.</p>
                    </article>
                    <article class="feature-card">
                        <h3>DIRECTO Y PRIVADO</h3>
                        <p>Tus datos no pasan por manos de terceros. Creamos un túnel seguro donde solo tú y quien recibe tienen el control absoluto de la información.</p>
                    </article>
                </div>
            </div>
        </section>

        <section id="contacto">
            <div class="glow" style="bottom: 10%; right: 0%;"></div>
            <div class="contact-container">
                <h2>¿TIENES ALGUNA DUDA?</h2>
                <p style="color: var(--text-muted); font-size: 0.8125rem; text-align: center; margin-bottom: 1.25rem; line-height: 1.4;">Si necesitas ayuda o quieres darnos una idea, escríbenos con confianza.</p>
                
                <div id="contact-success" style="display:none; text-align:center; padding: 1rem; background: rgba(13,148,136,0.1); border: 1px solid rgba(13,148,136,0.3); border-radius: 8px; color: #0d9488; margin-bottom: 1rem; font-size: 0.875rem;">
                    ✅ ¡Mensaje enviado! Te responderemos pronto.
                </div>
                <div id="contact-error" style="display:none; text-align:center; padding: 1rem; background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); border-radius: 8px; color: #ef4444; margin-bottom: 1rem; font-size: 0.875rem;"></div>

                <form id="contact-form" onsubmit="submitContactForm(event)">
                    @csrf
                    <div class="input-group">
                        <label for="contact-name">TU NOMBRE</label>
                        <input type="text" id="contact-name" name="name" placeholder="Escribe tu nombre" required autocomplete="name">
                    </div>
                    <div class="input-group">
                        <label for="contact-email">TU CORREO</label>
                        <input type="email" id="contact-email" name="email" placeholder="ejemplo@correo.com" required autocomplete="email">
                    </div>
                    <div class="input-group">
                        <label for="contact-message">¿EN QUÉ PODEMOS AYUDARTE?</label>
                        <textarea id="contact-message" name="message" placeholder="Cuéntanos aquí..." required minlength="10"></textarea>
                    </div>
                    <button type="submit" id="contact-btn" class="btn-primary" style="width: 100%; padding: 0.6875rem 0.875rem; margin-top: 0.375rem; min-height: 44px; display: flex; align-items: center; justify-content: center; font-size: 0.875rem; gap: 0.5rem;">
                        <span id="contact-btn-text">ENVIAR MENSAJE</span>
                    </button>
                </form>
            </div>
        </section>
    </main>

    <footer style="padding: 30px; text-align: center; opacity: 0.4;">
        <p style="font-size: 0.55rem; letter-spacing: 3px; font-weight: 600;">ELYSIUM P2P • TU ARCHIVO, TU CONTROL • 2026</p>
    </footer>

    <!-- Theme Switcher Script -->
    <script type="module">
        // Importar funciones de tema
        const THEMES = ['dark', 'light', 'petrol', 'noir', 'vegas', 'neon', 'storm'];
        const STORAGE_KEY = 'elysium-theme-mode';

        function setTheme(themeName) {
            if (!themeName || !THEMES.includes(themeName)) {
                themeName = 'petrol'; // Fallback al tema por defecto
            }
            
            // Remover todos los temas previos
            THEMES.forEach(t => document.documentElement.classList.remove(`theme-${t}`));
            
            // Aplicar nuevo tema
            document.documentElement.classList.add(`theme-${themeName}`);
            localStorage.setItem(STORAGE_KEY, themeName);
            
            console.log(`[THEME] 🎨 Modo cambiado a: ${themeName.toUpperCase()}`);
            
            // Actualizar iconos si existen - MANTENER SIEMPRE PALETTE
            const icon = document.getElementById('theme-icon');
            if (icon) {
                // Mantener siempre el icono de palette
                icon.className = 'fas fa-palette';
            }
        }

        function cycleTheme() {
            const current = localStorage.getItem(STORAGE_KEY) || THEMES[2]; // petrol por defecto
            const currentIndex = THEMES.indexOf(current);
            const nextIndex = (currentIndex + 1) % THEMES.length;
            setTheme(THEMES[nextIndex]);
        }

        // Hacer funciones disponibles globalmente
        window.cycleTheme = cycleTheme;
        window.setTheme = setTheme;

        // Inicializar tema al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem(STORAGE_KEY) || 'petrol';
            setTheme(savedTheme);
        });
    </script>

    <script>
    async function submitContactForm(e) {
        e.preventDefault();

        const btn     = document.getElementById('contact-btn');
        const btnText = document.getElementById('contact-btn-text');
        const success = document.getElementById('contact-success');
        const error   = document.getElementById('contact-error');

        // Reset
        success.style.display = 'none';
        error.style.display   = 'none';
        btn.disabled = true;
        btnText.textContent = 'Enviando...';

        const form = document.getElementById('contact-form');
        const data = {
            name:    document.getElementById('contact-name').value.trim(),
            email:   document.getElementById('contact-email').value.trim(),
            message: document.getElementById('contact-message').value.trim(),
        };

        try {
            const res = await fetch('{{ route("contact.send") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const json = await res.json();

            if (json.success) {
                success.style.display = 'block';
                form.reset();
            } else {
                error.textContent = json.message || 'Error al enviar. Intenta de nuevo.';
                error.style.display = 'block';
            }
        } catch (err) {
            error.textContent = 'Error de conexión. Intenta de nuevo.';
            error.style.display = 'block';
        } finally {
            btn.disabled = false;
            btnText.textContent = 'ENVIAR MENSAJE';
        }
    }
    </script>
</body>
</html>