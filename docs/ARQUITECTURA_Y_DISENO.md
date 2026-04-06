# 🏗️ Arquitectura y Diseño de Software - Elysium Ito

Este documento detalla el diseño técnico y las decisiones arquitectónicas tomadas para la sustentación del proyecto bajo un modelo de investigación.

## 1. Patrón de Diseño: MVC (Model-View-Controller)
El proyecto utiliza el patrón MVC nativo de **Laravel 12**, separando claramente la lógica de negocio (Models), la presentación (Blade Views) y el flujo de control (Controllers).

## 2. Arquitectura de Servicios (Cloud Native)
La arquitectura está desacoplada para permitir alta disponibilidad:
- **Web Server:** Nginx 1.18+ sobre Ubuntu 22.04 LTS.
- **Backend:** PHP 8.2 (FPM) con extensiones de seguridad.
- **Database:** MySQL (AWS RDS Aurora) para escalabilidad vertical.
- **Real-time Engine:** Pusher (WebSockets) para mensajería instantánea.
- **Storage:** Almacenamiento local con opción de respaldo en Google Drive (OAuth 2.0).

## 3. Stack Tecnológico Detallado (Tecnologías Usadas)

Para la implementación del proyecto se seleccionaron tecnologías de vanguardia que garantizan un entorno robusto y escalable:

### Backend & Core
- **PHP 8.2+:** Motor de ejecución con mejoras en rendimiento y tipado estricto.
- **Laravel 12:** Framework PHP basado en el patrón MVC, proporcionando herramientas de seguridad y routing avanzadas.
- **Composer:** Gestor de dependencias para PHP, asegurando la integridad de las librerías del servidor.

### Frontend & UI/UX
- **Livewire 3:** Framework full-stack para Laravel que permite crear interfaces dinámicas sin salir de PHP.
- **Tailwind CSS:** Framework de CSS orientado a utilidades para un diseño responsivo y moderno.
- **Blade:** Motor de plantillas de Laravel para una renderización eficiente en el servidor.

### Comunicación & Tiempo Real
- **Pusher (WebSockets):** Servicio de mensajería en tiempo real para notificaciones y estados de chat instantáneos.
- **WebRTC:** Protocolo para comunicación P2P (Peer-to-Peer), utilizado para transferencias directas de datos.

### Seguridad & Autenticación
- **Laravel Fortify:** Frontend-agnostic engine para la implementación de login, registro y 2FA.
- **Laravel Jetstream:** Proporciona el andamiaje para la gestión de perfiles y seguridad de sesiones.
- **Google OAuth 2.0:** Protocolo de autorización para login social y acceso a Google Drive.

### Infraestructura & Despliegue
- **Nginx:** Servidor web de alto rendimiento optimizado para el manejo de múltiples conexiones concurrentes.
- **Node.js & NPM:** Entorno de ejecución y gestor de paquetes para las dependencias del frontend.
- **Vite:** Herramienta de construcción (bundler) rápida para assets modernos.

## 4. Seguridad y Protección de Datos
Se implementaron capas de seguridad avanzadas:
1. **Firewall a Nivel de Aplicación:** Nginx con headers `X-Frame-Options`, `X-Content-Type-Options`.
2. **Encriptación:** Contraseñas hasheadas con Bcrypt ( rounds=12 ).
3. **Validación de Correo (MX Check):** Antes de enviar correos de recuperación, el sistema verifica que el dominio del destinatario sea capaz de recibir mensajes.
4. **2FA por Email:** Flujo personalizado de seguridad para accesos no autorizados.

## 5. Diseño de Base de Datos
El modelo de datos incluye:
- **Usuarios:** Gestión de perfiles, estados y tokens.
- **Mensajes:** Relación entre emisor, receptor y metadatos del archivo.
- **Auditoría:** Tabla de `audit_logs` que registra acciones administrativas.
- **Favoritos:** Gestión de contactos destacados.

## 6. Decisiones de Investigación (Rationale)
- **Por qué WebRTC?** Para permitir transferencias P2P directas sin cargar el servidor central.
- **Por qué Nginx sobre Apache?** Por su manejo superior de conexiones concurrentes en instancias de bajos recursos (db.t4g.micro).
- **Por qué PHP 8.2?** Por las mejoras en rendimiento y tipos estrictos que reducen errores en tiempo de ejecución.
