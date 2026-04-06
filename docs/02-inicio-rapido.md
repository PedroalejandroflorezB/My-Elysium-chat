# Elysium Ito 💬

Una aplicación de chat en tiempo real moderna con transferencia de archivos P2P, construida con Laravel 11 y tecnologías web avanzadas.

## ✨ Características Principales

- 💬 **Chat en Tiempo Real** - Mensajes instantáneos con WebSockets
- 📁 **Transferencia P2P** - Intercambio directo de archivos sin servidor
- 🎨 **Interfaz Moderna** - Diseño responsive con temas claro/oscuro
- 🔐 **Seguridad Avanzada** - Autenticación robusta y validación de datos
- 📱 **PWA Ready** - Funciona como aplicación nativa
- 🌐 **Multiidioma** - Soporte para español e inglés

## 🚀 Tecnologías

### Backend
- **Laravel 11** - Framework PHP moderno
- **Laravel Reverb** - WebSocket server nativo
- **SQLite/MySQL** - Base de datos flexible
- **Laravel Echo** - Broadcasting en tiempo real

### Frontend
- **Vite** - Build tool ultrarrápido
- **TailwindCSS** - Framework CSS utility-first
- **Alpine.js** - Framework JavaScript reactivo
- **PeerJS** - WebRTC simplificado para P2P

### Infraestructura
- **AWS EC2** - Servidor de aplicación
- **AWS RDS Aurora** - Base de datos en producción
- **CloudFront** - CDN global
- **Docker** - Containerización

## 📦 Instalación Rápida

```bash
# Clonar repositorio
git clone https://github.com/tu-usuario/elysium-ito.git
cd elysium-ito

# Instalar dependencias
composer install
npm install

# Configurar entorno
cp .env.example .env
php artisan key:generate
php artisan migrate --seed

# Compilar assets y ejecutar
npm run dev
php artisan serve
php artisan reverb:start
```

## 🎯 Funcionalidades

### Chat en Tiempo Real
- Mensajes instantáneos con confirmación de lectura
- Indicadores de presencia (online/offline)
- Eliminación de conversaciones
- Búsqueda de usuarios en tiempo real

### Transferencia P2P
- Envío directo de archivos entre usuarios
- Múltiples archivos simultáneos
- Barra de progreso en tiempo real
- Historial de transferencias

### Gestión de Contactos
- Sistema de contactos con búsqueda
- Códigos QR para agregar contactos
- Perfiles de usuario con avatares
- Modal de información de contacto

### Interfaz de Usuario
- Diseño responsive para móvil y desktop
- Sistema de temas adaptativos
- Animaciones suaves y transiciones
- Componentes reutilizables

## 🔧 Configuración de Desarrollo

### Requisitos
- PHP 8.2+
- Node.js 18+
- Composer
- SQLite o MySQL

### Variables de Entorno
```env
APP_NAME="Elysium Ito"
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
# DB_DATABASE=/path/to/database.sqlite

BROADCAST_DRIVER=reverb
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
```

### Comandos Útiles
```bash
# Desarrollo
npm run dev          # Compilar assets en modo desarrollo
php artisan serve    # Servidor de desarrollo
php artisan reverb:start  # WebSocket server

# Producción
npm run build        # Compilar para producción
php artisan optimize # Optimizar aplicación
php artisan queue:work  # Procesar colas
```

## 📚 Documentación

- 📖 [Documentación Técnica Completa](api.md)
- 🏗️ [Arquitectura P2P](docs/arquitectura_p2p.md)
- 🎨 [Guía de Diseño](docs/chat_design.md)
- 🚀 [Despliegue en AWS](docs/instalacion_aws.md)
- 🔐 [Gestión de Contraseñas](docs/password-manager-usage.md)

## 🌐 Despliegue en Producción

### AWS (Recomendado)
```bash
# Configuración para producción
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm run build
```

Ver [guía completa de despliegue](docs/produccion_aws.md) para instrucciones detalladas.

## 🤝 Contribuir

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo [LICENSE](LICENSE) para más detalles.

## 🎯 Roadmap

- [ ] Llamadas de voz y video
- [ ] Grupos de chat
- [ ] Mensajes con multimedia
- [ ] Notificaciones push
- [ ] Modo offline avanzado
- [ ] Migración a TypeScript
- [ ] Tests E2E completos

## 📞 Soporte

¿Tienes preguntas o necesitas ayuda? 

- 📧 Email: soporte@elysium-ito.com
- 💬 Discord: [Únete a nuestra comunidad](https://discord.gg/elysium-ito)
- 📖 Wiki: [Documentación completa](https://github.com/tu-usuario/elysium-ito/wiki)

---

**Hecho con ❤️ por el equipo Elysium Ito**
