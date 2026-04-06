# Mapa del Frontend - Elysium P2P

Este documento detalla la estructura de las vistas y componentes del frontend para facilitar el mantenimiento y prevenir errores durante cambios futuros.

---

## 🏗️ Estructura de Vistas (Blade Tree) - Actualizada 2026

```text
resources/views/
├── layouts/
│   ├── app.blade.php                 # Layout base de Laravel Breeze
│   ├── chat.blade.php                # Layout principal (App autenticada)
│   │   └── Incluye: Topbar, Scripts Globales, Echo WebSocket
│   ├── guest.blade.php               # Layout para páginas externas (Login/Register)
│   ├── admin.blade.php               # Layout para panel administrativo
│   └── navigation.blade.php          # Navegación de Breeze (no usado en chat)
│
├── welcome.blade.php                 # [PAGE] Hero / Home (Landing Page)
│   └── Estilos: Inline (CSS en el mismo archivo)
│
├── dashboard.blade.php               # [PAGE] Dashboard básico de Breeze
│
├── auth/                             # Sistema de autenticación Laravel Breeze
│   ├── login.blade.php               # [PAGE] Formulario de Acceso
│   ├── register.blade.php            # [PAGE] Formulario de Registro
│   ├── confirm-password.blade.php    # Confirmación de contraseña
│   ├── forgot-password.blade.php     # Recuperar contraseña
│   ├── reset-password.blade.php      # Resetear contraseña
│   └── verify-email.blade.php        # Verificación de email
│
├── profile/                          # Gestión de perfil de usuario
│   ├── edit.blade.php                # [PAGE] Editar perfil
│   └── partials/                     # Componentes del perfil
│
├── admin/                            # Panel administrativo
│   ├── users/                        # Gestión de usuarios
│   └── roles/                        # Gestión de roles
│
├── components/                       # Componentes Blade reutilizables
│   ├── application-logo.blade.php    # Logo de la aplicación
│   ├── auth-session-status.blade.php # Estado de sesión
│   ├── input-error.blade.php         # Errores de formulario
│   ├── message-bubble.blade.php      # Burbuja de mensaje de chat
│   ├── modal.blade.php               # Modal genérico
│   ├── primary-button.blade.php      # Botón primario
│   ├── secondary-button.blade.php    # Botón secundario
│   ├── text-input.blade.php          # Input de texto
│   └── [otros componentes Breeze]
│
├── partials/                         # Partials globales
│   └── modals/                       # Modales globales
│
└── chat/                             # Sistema de chat P2P
    ├── index.blade.php               # [PAGE] Vista principal (Empty State)
    │   └── Carga: sidebar, topbar, empty-state, QR modals
    ├── show.blade.php                # [PAGE] Vista de Conversación Activa
    │   └── Carga: sidebar, topbar, chat-content, QR modals
    └── partials/
        ├── topbar.blade.php          # Barra superior (Selector de temas, logout, perfil)
        ├── sidebar.blade.php         # Barra lateral (Contactos, Búsqueda, Tab de Archivos P2P)
        ├── empty-state.blade.php     # UI de "¡Empecemos!" (Dashboard vacío)
        ├── chat-empty.blade.php      # Estado vacío dentro del chat
        ├── chat-content.blade.php    # Contenedor principal del chat activo
        ├── chat-header.blade.php     # Info del contacto (Nombre, foto, estado online)
        ├── chat-input.blade.php      # Campo de texto y botón de adjuntar (P2P)
        ├── messages-container.blade.php # Contenedor scrollable de mensajes
        ├── messages.blade.php        # Lógica de burbujas (Enviado/Recibido)
        ├── conversation-item.blade.php # Item individual de conversación en sidebar
        ├── p2p-modals.blade.php      # Modales de transferencia (Solicitud, Progreso, Éxito)
        ├── onboarding.blade.php      # Tutorial inicial para nuevos usuarios
        └── modals/                   # Modales específicos del chat
            ├── contact-confirm.blade.php      # Confirmación de contacto
            ├── contact-request.blade.php      # Solicitud de contacto
            ├── contact-request-pending.blade.php # Solicitud pendiente
            ├── profile.blade.php              # Perfil de contacto
            ├── qr-scanner.blade.php           # Escáner QR (Android)
            └── qr-generator.blade.php         # Generador QR (PC/Android)
```

---

## 🎨 Sistema de Estilos (CSS) - Estructura Actualizada

```text
resources/css/
├── app.css                           # Variables globales, tokens de diseño y reset
├── auth.css                          # Estilos específicos para autenticación
├── dashboard.css                     # Estilos para dashboard básico
├── components/                       # Componentes específicos
│   ├── animations.css                # Animaciones globales
│   ├── buttons.css                   # Estilos de botones
│   ├── chat.css                      # Burbujas de chat, input area y modales P2P
│   ├── contact-modal.css             # Modales de contactos
│   ├── layout.css                    # Layout general
│   ├── modals.css                    # Estilos generales para ventanas modales
│   ├── onboarding.css                # Tutorial de onboarding
│   ├── password-components.css       # Componentes de contraseña
│   ├── responsive.css                # Responsive design
│   ├── sidebar.css                   # Lista de contactos y estados de transferencia
│   ├── standard-forms.css            # Formularios estándar
│   ├── toast.css                     # Sistema de notificaciones flotantes
│   └── topbar.css                    # Estilos de la barra superior y selector de temas
└── utils/                            # Utilidades CSS
    ├── animations.css                # Animaciones utilitarias
    ├── responsive.css                # Utilidades responsive
    └── viewport-forms.css            # Formularios con viewport units
```

---

## ⚙️ Lógica de Cliente (JavaScript) - Estructura Actualizada

```text
resources/js/
├── app.js                            # Punto de entrada principal
├── bootstrap.js                      # Configuración de Laravel (Axios, CSRF)
├── echo.js                           # Configuración de Laravel Echo (WebSockets)
├── fontawesome.js                    # Configuración de Font Awesome
├── password-config-example.js        # Configuración de ejemplo para passwords
├── components/                       # Componentes JavaScript
│   ├── chat.js                       # Manejo de UI de mensajes y scrolls
│   ├── contact-modal.js              # Lógica de modales de contacto
│   ├── messages.js                   # Gestión de mensajes
│   ├── modals.js                     # Sistema de modales genérico
│   ├── onboarding.js                 # Tutorial interactivo
│   ├── p2p-file-transfer.js          # **Lógica Core**: WebRTC, fragmentación de archivos
│   ├── PasswordManager.js            # Gestión de contraseñas con validación
│   ├── presence.js                   # Sincronización de estados online en tiempo real
│   ├── profile.js                    # Gestión de perfil de usuario
│   ├── search.js                     # Funcionalidad de búsqueda
│   ├── sidebar.js                    # Tabs de contactos/archivos y filtrado
│   ├── theme.js                      # Cambio de temas
│   ├── toast.js                      # Sistema de notificaciones
│   └── websocket.js                  # Gestión de WebSocket
├── p2p/                              # Sistema P2P específico
│   ├── connection.js                 # Gestión de conexiones WebRTC
│   └── listener.js                   # Listeners de eventos P2P
└── utils/                            # Utilidades JavaScript
    └── helpers.js                    # Funciones auxiliares
```

---

## 🔍 Detalle de Componentes Clave

### 1. Hero Page (`welcome.blade.php`)
*   **Propósito**: Presentación del producto, propuesta de valor P2P.
*   **Componentes**: Navegación (`header`), Hero Section, Features Grid, Contact Form.
*   **Estilos**: Variables CSS definidas en `:root`.

### 2. Autenticación (`auth/login.blade.php` & `register.blade.php`)
*   **Propósito**: Acceso y creación de cuentas con Laravel Breeze.
*   **Estilos**: Usa `resources/css/auth.css` (Glassmorphism, fondos degradados).
*   **Validación**: Integrada con `PasswordManager.js` para validación en tiempo real.

### 3. Estado Vacío / Entrada (`chat/partials/empty-state.blade.php`)
*   **Texto central**: *"¡Empecemos! Este chat está vacío..."*
*   **Acciones**: Copiar @usuario, abrir buscador de contactos, QR codes.
*   **Ubicación**: Se muestra en `chat/index.blade.php`.
*   **QR Functionality**: Botones específicos por plataforma (PC vs Android).

### 4. Chat Activo (`chat/show.blade.php`)
*   **Header** (`chat-header`): Muestra avatar del contacto y estado.
*   **Contenido** (`messages`): Renderiza burbujas de texto con estilos hero home.
*   **Input Area** (`chat-input`): 
    *   `#message-input`: Área de texto con forma de píldora.
    *   `#send-file-btn`: Lanza el selector P2P.
*   **Modales P2P** (`p2p-modals`): Gestionan el flujo de transferencia directa.

### 5. Sistema QR (`chat/partials/modals/qr-*.blade.php`)
*   **QR Scanner**: Solo visible en Android, para escanear códigos.
*   **QR Generator**: Visible en PC y Android, genera código del usuario.
*   **Platform Detection**: JavaScript detecta automáticamente el dispositivo.

---

## 🔗 Rutas y API Endpoints

### Rutas Web Principales
| Ruta | Vista | Descripción |
|------|-------|-------------|
| `/` | `welcome.blade.php` | Landing page |
| `/chat` | `chat/index.blade.php` | Chat principal (empty state) |
| `/@{username}` | `chat/show.blade.php` | Chat con usuario específico |
| `/login` | `auth/login.blade.php` | Autenticación |
| `/register` | `auth/register.blade.php` | Registro |
| `/profile` | `profile/edit.blade.php` | Editar perfil |

### API Endpoints Principales
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `GET` | `/api/test` | Prueba de API |
| `POST` | `/api/messages/send` | Enviar mensaje |
| `GET` | `/api/messages/{userId}` | Obtener mensajes |
| `POST` | `/api/contacts/search` | Buscar usuarios |
| `POST` | `/api/contacts/request` | Solicitud de contacto |
| `POST` | `/api/p2p/signal` | Señalización WebRTC |

---

## ⚠️ Notas de Seguridad para Cambios

### IDs Críticos (No Cambiar Sin Actualizar JS)
```javascript
// Chat
#message-input, #send-file-btn, #chat-main
#current-username, #modal-search-input

// P2P
#p2p-progress-container, #modal-qr, #modal-qr-generate
.qr-scan-btn, .qr-generate-btn

// Modales
#add-contact-modal, #contact-profile-modal
```

### Dependencias de Layout
1.   **`layouts/chat.blade.php`**: Carga `app.js`, `chat.js` y configuración de Echo.
2.   **QR Modals**: Incluidos en ambas vistas de chat (`index.blade.php` y `show.blade.php`).
3.   **Platform Detection**: Funciones `isAndroid()` e `isMobile()` deben estar disponibles globalmente.

### Funciones JavaScript Críticas
```javascript
// Globales requeridas
window.copyUsername, window.showQRModal, window.generateQR
window.isAndroid, window.isMobile
window.showOnboarding, window.closeModal

// P2P Core
window.P2PFileTransfer (clase principal)
window.Echo (Laravel Echo para WebSockets)
```

### CSS Variables Importantes
```css
--primary, --secondary, --bg-primary, --bg-secondary
--text-primary, --text-muted, --border-color
--glass-bg, --shadow-glow, --radius-full
```

---

## 🚀 Nuevas Funcionalidades Implementadas

### QR Code System
- **Detección de plataforma**: Automática entre PC y Android
- **Generación QR**: Modal con código visual del usuario
- **Escaneo QR**: Modal para Android con activación de cámara
- **Compartir**: Integración con Web Share API

### Android Optimizations
- **Touch targets**: Mínimo 44px para todos los botones
- **Responsive**: Breakpoints específicos para móviles
- **Viewport**: Uso de `100dvh` para altura dinámica
- **Performance**: Optimizaciones de CSS para dispositivos móviles

### Theme System
- **Consistencia**: Mismo sistema entre hero home y chat
- **Variables CSS**: Todos los colores centralizados
- **Glassmorphism**: Efectos aplicados consistentemente