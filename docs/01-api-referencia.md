# Elysium Ito - Documentación Técnica del Proyecto

## 📋 Información General

**Nombre del Proyecto:** Elysium Ito  
**Tipo:** Aplicación de Chat en Tiempo Real con P2P File Transfer  
**Framework:** Laravel 11 + Vite + TailwindCSS  
**Base de Datos:** SQLite (desarrollo) / MySQL Aurora (producción)  
**WebSockets:** Laravel Reverb + Laravel Echo  
**P2P:** PeerJS + WebRTC  

---

## 🏗️ Estructura del Proyecto

```
elysium-ito/
├── 📁 app/                          # Lógica de aplicación Laravel
│   ├── 📁 Events/                   # Eventos WebSocket
│   │   ├── 📄 ChatDeleted.php       # Evento eliminación de chat
│   │   ├── 📄 ContactRequestSent.php # Evento solicitud de contacto
│   │   ├── 📄 MessageSent.php       # Evento mensaje enviado
│   │   ├── 📄 MessagesMarkedAsRead.php # Evento mensajes leídos
│   │   ├── 📄 P2PSignaling.php      # Señalización P2P
│   │   ├── 📄 WebRtcAnswer.php      # WebRTC Answer
│   │   ├── 📄 WebRtcIceCandidate.php # WebRTC ICE Candidate
│   │   └── 📄 WebRtcOffer.php       # WebRTC Offer
│   ├── 📁 Http/
│   │   ├── 📁 Controllers/          # Controladores
│   │   │   ├── 📁 Admin/            # Panel administrativo
│   │   │   │   ├── 📄 RoleController.php
│   │   │   │   └── 📄 UserController.php
│   │   │   ├── 📁 Auth/             # Autenticación
│   │   │   │   ├── 📄 AuthenticatedSessionController.php
│   │   │   │   ├── 📄 ConfirmablePasswordController.php
│   │   │   │   ├── 📄 EmailVerificationNotificationController.php
│   │   │   │   ├── 📄 EmailVerificationPromptController.php
│   │   │   │   ├── 📄 NewPasswordController.php
│   │   │   │   ├── 📄 PasswordController.php
│   │   │   │   ├── 📄 PasswordResetLinkController.php
│   │   │   │   ├── 📄 RegisteredUserController.php
│   │   │   │   └── 📄 VerifyEmailController.php
│   │   │   ├── 📁 P2P/              # Controladores P2P
│   │   │   │   └── 📄 SignalingController.php
│   │   │   ├── 📄 ChatController.php # Controlador principal de chat
│   │   │   ├── 📄 ContactController.php # Gestión de contactos
│   │   │   ├── 📄 Controller.php    # Controlador base
│   │   │   └── 📄 ProfileController.php # Perfil de usuario
│   │   ├── 📁 Middleware/           # Middleware personalizado
│   │   │   ├── 📄 DetectAjaxRequest.php
│   │   │   └── 📄 IsAdmin.php
│   │   └── 📁 Requests/             # Form Requests
│   │       ├── 📁 Auth/
│   │       │   ├── 📄 LoginRequest.php
│   │       │   └── 📄 RegisterRequest.php
│   │       └── 📄 ProfileUpdateRequest.php
│   ├── 📁 Models/                   # Modelos Eloquent
│   │   ├── 📄 Contact.php           # Modelo de contactos
│   │   ├── 📄 ContactRequest.php    # Solicitudes de contacto
│   │   ├── 📄 Message.php           # Mensajes de chat
│   │   └── 📄 User.php              # Usuario
│   ├── 📁 Providers/                # Service Providers
│   │   └── 📄 AppServiceProvider.php
│   └── 📁 View/                     # Componentes de vista
│       └── 📁 Components/
│           ├── 📄 AppLayout.php
│           └── 📄 GuestLayout.php
├── 📁 bootstrap/                    # Bootstrap de Laravel
│   ├── 📁 cache/                    # Cache de bootstrap
│   ├── 📄 app.php                   # Configuración de aplicación
│   └── 📄 providers.php             # Providers registrados
├── 📁 config/                       # Configuraciones
│   ├── 📄 app.php                   # Configuración general
│   ├── 📄 auth.php                  # Autenticación
│   ├── 📄 broadcasting.php          # WebSockets/Broadcasting
│   ├── 📄 cache.php                 # Cache
│   ├── 📄 database.php              # Base de datos
│   ├── 📄 filesystems.php           # Sistemas de archivos
│   ├── 📄 logging.php               # Logging
│   ├── 📄 mail.php                  # Email
│   ├── 📄 queue.php                 # Colas
│   ├── 📄 reverb.php                # Laravel Reverb
│   ├── 📄 services.php              # Servicios externos
│   └── 📄 session.php               # Sesiones
├── 📁 database/                     # Base de datos
│   ├── 📁 factories/                # Factories para testing
│   │   └── 📄 UserFactory.php
│   ├── 📁 migrations/               # Migraciones
│   │   ├── 📄 0001_01_01_000000_create_users_table.php
│   │   ├── 📄 0001_01_01_000001_create_cache_table.php
│   │   ├── 📄 0001_01_01_000002_create_jobs_table.php
│   │   ├── 📄 2026_03_28_132148_create_transfer_sessions_table.php
│   │   ├── 📄 2026_03_28_232052_add_is_admin_to_users_table.php
│   │   ├── 📄 2026_03_29_025831_add_username_avatar_to_users_table.php
│   │   ├── 📄 2026_03_29_034638_create_contact_requests_table.php
│   │   ├── 📄 2026_03_29_044342_create_contacts_table.php
│   │   ├── 📄 2026_03_30_050000_add_fields_to_messages_table.php
│   │   ├── 📄 2026_03_30_060000_fix_messages_id_auto_increment.php
│   │   ├── 📄 2026_03_30_070000_add_deletion_flags_to_messages_table.php
│   │   └── 📄 2026_04_01_160308_create_contacts_table.php
│   ├── 📁 seeders/                  # Seeders
│   │   ├── 📄 DatabaseSeeder.php
│   │   └── 📄 TestUsersSeeder.php
│   └── 📄 database.sqlite           # Base de datos SQLite
├── 📁 docs/                         # Documentación
│   ├── 📄 arquitectura_p2p.md       # Arquitectura P2P
│   ├── 📄 chat_design.md            # Diseño del chat
│   ├── 📄 credenciales.md           # Credenciales
│   ├── 📄 frontend.md               # Frontend
│   ├── 📄 instalacion_aws.md        # Instalación en AWS
│   ├── 📄 intercambio_de_archivos.md # Intercambio de archivos
│   ├── 📄 password-manager-usage.md # Uso del password manager
│   └── 📄 produccion_aws.md         # Producción en AWS
├── 📁 lang/                         # Idiomas
│   ├── 📁 en/                       # Inglés
│   │   ├── 📄 auth.php
│   │   ├── 📄 pagination.php
│   │   ├── 📄 passwords.php
│   │   └── 📄 validation.php
│   └── 📁 es/                       # Español
│       ├── 📄 auth.php
│       ├── 📄 pagination.php
│       ├── 📄 passwords.php
│       └── 📄 validation.php
├── 📁 public/                       # Archivos públicos
│   ├── 📁 build/                    # Assets compilados
│   │   ├── 📁 assets/
│   │   └── 📄 manifest.json
│   ├── 📄 .htaccess                 # Configuración Apache
│   ├── 📄 favicon.ico               # Favicon
│   ├── 📄 index.php                 # Punto de entrada
│   ├── 📄 robots.txt                # Robots.txt
│   └── 📄 sw.js                     # Service Worker
├── 📁 resources/                    # Recursos
│   ├── 📁 css/                      # Estilos CSS
│   │   ├── 📁 components/           # Componentes CSS
│   │   │   ├── 📄 animations.css    # Animaciones
│   │   │   ├── 📄 buttons.css       # Botones
│   │   │   ├── 📄 chat.css          # Chat
│   │   │   ├── 📄 contact-modal.css # Modal de contacto
│   │   │   ├── 📄 layout.css        # Layout
│   │   │   ├── 📄 modals.css        # Modales
│   │   │   ├── 📄 onboarding.css    # Onboarding
│   │   │   ├── 📄 password-components.css # Componentes de contraseña
│   │   │   ├── 📄 responsive.css    # Responsive
│   │   │   ├── 📄 sidebar.css       # Sidebar
│   │   │   ├── 📄 standard-forms.css # Formularios estándar
│   │   │   ├── 📄 toast.css         # Toasts
│   │   │   └── 📄 topbar.css        # Barra superior
│   │   ├── 📁 utils/                # Utilidades CSS
│   │   │   ├── 📄 animations.css
│   │   │   ├── 📄 colors.css
│   │   │   ├── 📄 reset.css
│   │   │   ├── 📄 shadows.css
│   │   │   ├── 📄 spacing.css
│   │   │   ├── 📄 themes.css
│   │   │   ├── 📄 transitions.css
│   │   │   └── 📄 typography.css
│   │   ├── 📄 app.css               # CSS principal
│   │   ├── 📄 auth.css              # Autenticación
│   │   └── 📄 dashboard.css         # Dashboard
│   ├── 📁 js/                       # JavaScript
│   │   ├── 📁 components/           # Componentes JS
│   │   │   ├── 📄 chat.js           # Chat principal
│   │   │   ├── 📄 messages.js       # Manejo de mensajes
│   │   │   ├── 📄 PasswordManager.js # Gestor de contraseñas
│   │   │   ├── 📄 presence.js       # Presencia de usuarios
│   │   │   ├── 📄 search.js         # Búsqueda
│   │   │   ├── 📄 sidebar.js        # Sidebar
│   │   │   ├── 📄 theme.js          # Temas
│   │   │   └── 📄 websocket.js      # WebSockets
│   │   ├── 📁 p2p/                  # P2P File Transfer
│   │   │   ├── 📄 p2p-file-transfer.js # Transferencia P2P
│   │   │   └── 📄 webrtc-handler.js # Manejo WebRTC
│   │   ├── 📁 utils/                # Utilidades JS
│   │   │   ├── 📄 confirm.js        # Confirmaciones
│   │   │   ├── 📄 helpers.js        # Helpers
│   │   │   └── 📄 toast.js          # Toasts
│   │   ├── 📄 app.js                # JS principal
│   │   ├── 📄 bootstrap.js          # Bootstrap JS
│   │   ├── 📄 echo.js               # Laravel Echo
│   │   ├── 📄 fontawesome.js        # FontAwesome
│   │   └── 📄 password-config-example.js # Configuración de contraseñas
│   └── 📁 views/                    # Vistas Blade
│       ├── 📁 admin/                # Panel admin
│       │   ├── 📁 roles/
│       │   └── 📁 users/
│       ├── 📁 auth/                 # Autenticación
│       │   ├── 📄 confirm-password.blade.php
│       │   ├── 📄 forgot-password.blade.php
│       │   ├── 📄 login.blade.php
│       │   ├── 📄 register.blade.php
│       │   ├── 📄 reset-password.blade.php
│       │   └── 📄 verify-email.blade.php
│       ├── 📁 chat/                 # Chat
│       │   ├── 📁 partials/         # Parciales del chat
│       │   │   ├── 📁 modals/       # Modales
│       │   │   │   ├── 📄 profile.blade.php # Modal de perfil
│       │   │   │   ├── 📄 qr-generator.blade.php # Generador QR
│       │   │   │   └── 📄 qr-scanner.blade.php # Scanner QR
│       │   │   ├── 📄 chat-empty.blade.php # Estado vacío
│       │   │   ├── 📄 conversation-item.blade.php # Item de conversación
│       │   │   ├── 📄 empty-state.blade.php # Estado vacío
│       │   │   ├── 📄 messages-container.blade.php # Contenedor de mensajes
│       │   │   ├── 📄 sidebar.blade.php # Sidebar
│       │   │   └── 📄 topbar.blade.php # Barra superior
│       │   ├── 📄 index.blade.php   # Lista de chats
│       │   └── 📄 show.blade.php    # Chat individual
│       ├── 📁 components/           # Componentes Blade
│       │   ├── 📄 app-layout.blade.php
│       │   └── 📄 guest-layout.blade.php
│       ├── 📁 layouts/              # Layouts
│       │   ├── 📄 app.blade.php
│       │   ├── 📄 guest.blade.php
│       │   └── 📄 navigation.blade.php
│       ├── 📁 partials/             # Parciales globales
│       │   └── 📁 modals/
│       │       └── 📄 profile.blade.php
│       ├── 📁 profile/              # Perfil
│       │   ├── 📁 partials/
│       │   │   ├── 📄 delete-user-form.blade.php
│       │   │   ├── 📄 update-password-form.blade.php
│       │   │   └── 📄 update-profile-information-form.blade.php
│       │   └── 📄 edit.blade.php
│       ├── 📄 chat.blade.php        # Vista principal de chat
│       ├── 📄 dashboard.blade.php   # Dashboard
│       └── 📄 welcome.blade.php     # Página de bienvenida
├── 📁 routes/                       # Rutas
│   ├── 📄 api.php                   # Rutas API
│   ├── 📄 auth.php                  # Rutas de autenticación
│   ├── 📄 channels.php              # Canales de broadcasting
│   ├── 📄 console.php               # Comandos de consola
│   └── 📄 web.php                   # Rutas web
├── 📁 storage/                      # Almacenamiento
│   ├── 📁 app/                      # Archivos de aplicación
│   │   ├── 📁 private/              # Archivos privados
│   │   └── 📁 public/               # Archivos públicos
│   ├── 📁 framework/                # Framework cache
│   │   ├── 📁 cache/
│   │   ├── 📁 sessions/
│   │   ├── 📁 testing/
│   │   └── 📁 views/
│   └── 📁 logs/                     # Logs
├── 📄 .editorconfig                 # Configuración del editor
├── 📄 .env                          # Variables de entorno
├── 📄 .env.example                  # Ejemplo de variables
├── 📄 .gitattributes                # Atributos de Git
├── 📄 .gitignore                    # Archivos ignorados por Git
├── 📄 artisan                       # CLI de Laravel
├── 📄 composer.json                 # Dependencias PHP
├── 📄 composer.lock                 # Lock de dependencias PHP
├── 📄 package.json                  # Dependencias Node.js
├── 📄 package-lock.json             # Lock de dependencias Node.js
├── 📄 phpunit.xml                   # Configuración PHPUnit
├── 📄 postcss.config.js             # Configuración PostCSS
├── 📄 README.md                     # Documentación principal
├── 📄 tailwind.config.js            # Configuración TailwindCSS
└── 📄 vite.config.js                # Configuración Vite
```

---

## 🔧 Tecnologías y Dependencias

### Backend (PHP/Laravel)
- **Laravel 11** - Framework principal
- **Laravel Reverb** - WebSocket server
- **Laravel Echo** - Cliente WebSocket
- **SQLite/MySQL** - Base de datos
- **Pusher** - Broadcasting (alternativo)

### Frontend
- **Vite** - Build tool
- **TailwindCSS** - Framework CSS
- **Alpine.js** - Framework JS ligero
- **FontAwesome** - Iconos
- **Axios** - Cliente HTTP

### P2P y WebRTC
- **PeerJS** - Simplificación de WebRTC
- **WebRTC Adapter** - Compatibilidad cross-browser

### Desarrollo
- **Laravel Breeze** - Autenticación
- **Laravel Pint** - Code style
- **PHPUnit** - Testing

---

## 🚀 Funcionalidades Implementadas

### ✅ Sistema de Autenticación
- Registro de usuarios con validación
- Login/Logout
- Recuperación de contraseñas
- Verificación de email
- Gestión de perfiles con avatar

### ✅ Chat en Tiempo Real
- Mensajes instantáneos vía WebSocket
- Indicadores de mensaje leído/no leído
- Presencia de usuarios (online/offline)
- Eliminación de chats (para mí/para todos)
- Navegación SPA sin recargas

### ✅ Gestión de Contactos
- Búsqueda de usuarios
- Añadir/eliminar contactos
- Modal de perfil de contacto
- Sistema QR para agregar contactos

### ✅ P2P File Transfer
- Transferencia directa de archivos
- Múltiples archivos simultáneos
- Barra de progreso en tiempo real
- Cancelación de transferencias
- Historial de transferencias

### ✅ Interfaz de Usuario
- Diseño responsive (móvil/desktop)
- Sistema de temas (claro/oscuro/automático)
- Componentes reutilizables
- Animaciones suaves
- Toasts y confirmaciones

### ✅ Características Avanzadas
- Service Worker para PWA
- Búsqueda en tiempo real
- Gestión de estados offline
- Optimización de rendimiento
- Seguridad XSS/CSRF

---

## 🔐 Seguridad Implementada

### Autenticación y Autorización
- Hash de contraseñas con bcrypt
- Tokens CSRF en formularios
- Middleware de autenticación
- Validación de permisos

### Validación de Datos
- Form Requests personalizados
- Sanitización de inputs
- Escape de HTML en outputs
- Validación de archivos

### WebSocket Security
- Canales privados autenticados
- Verificación de permisos por canal
- Rate limiting en eventos

---

## 📊 Base de Datos

### Tablas Principales
- **users** - Usuarios del sistema
- **messages** - Mensajes de chat
- **contacts** - Relaciones de contactos
- **contact_requests** - Solicitudes de contacto
- **transfer_sessions** - Sesiones P2P

### Relaciones
- User hasMany Messages
- User hasMany Contacts
- Message belongsTo User (sender/receiver)
- Contact belongsTo User

---

## 🌐 API Endpoints

### Autenticación
- `POST /login` - Iniciar sesión
- `POST /register` - Registrar usuario
- `POST /logout` - Cerrar sesión

### Chat
- `GET /api/conversations-list` - Lista de conversaciones
- `POST /api/messages/send` - Enviar mensaje
- `POST /api/messages/read/all/{userId}` - Marcar como leído
- `POST /api/chat/delete` - Eliminar chat

### Contactos
- `POST /api/contacts/search` - Buscar usuarios
- `POST /api/contacts/add` - Añadir contacto
- `DELETE /api/contacts/remove/{id}` - Eliminar contacto
- `GET /api/contacts/check/{id}` - Verificar si es contacto

### Perfil
- `POST /api/profile/avatar` - Actualizar avatar

---

## 🔄 WebSocket Events

### Eventos de Chat
- `message.sent` - Mensaje enviado
- `messages.read` - Mensajes marcados como leídos
- `chat.deleted` - Chat eliminado

### Eventos P2P
- `p2p.signaling` - Señalización P2P
- `webrtc.offer` - Oferta WebRTC
- `webrtc.answer` - Respuesta WebRTC
- `webrtc.ice-candidate` - Candidato ICE

---

## 🎨 Sistema de Temas

### Temas Disponibles
- **Light** - Tema claro
- **Dark** - Tema oscuro
- **Auto** - Según preferencia del sistema

### Variables CSS
- Colores primarios y secundarios
- Gradientes adaptativos
- Sombras y efectos
- Transiciones consistentes

---

## 📱 Responsive Design

### Breakpoints
- **Mobile**: < 768px
- **Tablet**: 768px - 1024px
- **Desktop**: > 1024px

### Adaptaciones Móviles
- Sidebar colapsable
- Botones táctiles optimizados
- Navegación por gestos
- Teclado virtual friendly

---

## 🚀 Despliegue

### Desarrollo Local
```bash
# Instalar dependencias
composer install
npm install

# Configurar entorno
cp .env.example .env
php artisan key:generate

# Base de datos
php artisan migrate --seed

# Compilar assets
npm run dev

# Iniciar servidores
php artisan serve
php artisan reverb:start
```

### Producción AWS
- **EC2** - Servidor de aplicación
- **RDS Aurora MySQL** - Base de datos
- **S3** - Almacenamiento de archivos
- **CloudFront** - CDN
- **Route 53** - DNS

---

## 📈 Rendimiento

### Optimizaciones Frontend
- Lazy loading de componentes
- Compresión de assets
- Cache de navegador
- Service Worker

### Optimizaciones Backend
- Query optimization
- Eager loading
- Cache de sesiones
- Compresión gzip

---

## 🧪 Testing

### Tipos de Tests
- Unit tests (PHPUnit)
- Feature tests (Laravel)
- Browser tests (Dusk)
- JavaScript tests (Jest)

### Cobertura
- Modelos y relaciones
- Controladores y APIs
- Middleware y validaciones
- Componentes frontend

---

## 📝 Notas de Desarrollo

### Convenciones de Código
- PSR-12 para PHP
- ESLint para JavaScript
- Prettier para formateo
- Conventional Commits

### Estructura de Commits
- `feat:` - Nueva funcionalidad
- `fix:` - Corrección de bugs
- `docs:` - Documentación
- `style:` - Cambios de estilo
- `refactor:` - Refactorización
- `test:` - Tests

---

## 🔮 Roadmap Futuro

### Funcionalidades Pendientes
- [ ] Llamadas de voz/video
- [ ] Grupos de chat
- [ ] Mensajes con multimedia
- [ ] Notificaciones push
- [ ] Modo offline avanzado

### Mejoras Técnicas
- [ ] Migración a TypeScript
- [ ] Tests E2E completos
- [ ] Monitoreo con Sentry
- [ ] CI/CD con GitHub Actions
- [ ] Docker containerization

---

## 📞 Soporte y Mantenimiento

### Logs y Debugging
- Laravel logs en `storage/logs/`
- Browser DevTools para frontend
- WebSocket debugging con Echo
- P2P debugging con PeerJS

### Monitoreo
- Uptime monitoring
- Performance metrics
- Error tracking
- User analytics

---

**Última actualización:** Abril 2026  
**Versión:** 1.0.0  
**Estado:** Proyecto limpio y optimizado - archivos innecesarios removidos  
**Mantenedor:** Equipo Elysium Ito