# 05. Backlog — Elysium Ito

## EP-01 · Seguridad y Autenticación

| ID | Historia de Usuario | Prioridad | Estado |
|---|---|---|---|
| US-01 | Autenticación estándar con email y contraseña | Alta | ✅ Terminado |
| US-02 | Autenticación Google OAuth2 (login social) | Alta | ✅ Terminado |
| US-03 | Rol Super Usuario con acceso total y recuperación via CLI | Crítica | ✅ Terminado |
| US-04 | Recuperación de acceso del Super Usuario con `php artisan super:setup` | Crítica | ✅ Terminado |
| US-23 | Ocultamiento de campos sensibles en respuestas JSON | Alta | ✅ Terminado |
| US-24 | Blindaje por Middleware contra IDOR y acceso no autorizado | Crítica | ✅ Terminado |

## EP-02 · Gestión Administrativa

| ID | Historia de Usuario | Prioridad | Estado |
|---|---|---|---|
| US-11 | Listado de usuarios con filtrado por rol | Alta | ✅ Terminado |
| US-12 | Creación de cuentas con asignación de roles jerárquicos | Alta | ✅ Terminado |
| US-14 | Bloqueo/Activación inmediata de cuentas | Alta | ✅ Terminado |
| US-16 | Revocación automática de sesiones para usuarios desactivados | Crítica | ✅ Terminado |

## EP-03 · Mensajería en Tiempo Real

| ID | Historia de Usuario | Prioridad | Estado |
|---|---|---|---|
| US-17 | Chat bidireccional instantáneo con indicadores de presencia | Crítica | ✅ Terminado |
| US-31 | Segregación de contactos basada en roles | Alta | ✅ Terminado |
| US-53 | Eliminación física de mensajes para todos los participantes | Media | ✅ Terminado |
| US-54 | Borrado completo de conversaciones y limpieza de caché | Media | ✅ Terminado |

## EP-04 · Gestión de Archivos y Backup

| ID | Historia de Usuario | Prioridad | Estado |
|---|---|---|---|
| US-44 | Carga de archivos hasta 1 GB por fragmentos (Chunked Upload) | Crítica | ✅ Terminado |
| US-66 | Transferencia P2P (WebRTC DataChannel) para privacidad absoluta | Alta | ✅ Terminado |
| US-88 | Backup automático dual en Google Drive personal (costo $0) | Alta | ✅ Terminado |
| US-96 | Notificaciones de estado de sincronización en tiempo real | Media | ✅ Terminado |
| US-97 | Soporte para UUIDs y optimización de timeouts en Google API | Alta | ✅ Terminado |

## EP-05 · Contacto y Comunicación

| ID | Historia de Usuario | Prioridad | Estado |
|---|---|---|---|
| US-105 | Formulario de contacto del home enruta al Super Usuario en BD | Media | ✅ Terminado |
| US-106 | Fallback a `MAIL_ADMIN_ADDRESS` si no hay Super Usuario en BD | Media | ✅ Terminado |

## EP-06 · Infraestructura y Calidad

| ID | Historia de Usuario | Prioridad | Estado |
|---|---|---|---|
| US-98 | Monitor de disco con alertas por correo y bloqueo al 90% | Alta | ✅ Terminado |
| US-99 | Páginas de error personalizadas (404, 403, 419, 429, 500, 503) | Media | ✅ Terminado |
| US-100 | Corrección compatibilidad UMD/ESM en pipeline Vite | Alta | ✅ Terminado |
| US-101 | Corrección variables de configuración (chatify middleware, google_drive, LOG_LEVEL) | Alta | ✅ Terminado |

## Completadas (Visión futura)

| ID | Historia de Usuario | Prioridad | Estado |
|---|---|---|---|
| HU-08 | Auditoría de logs administrativos detallados | Media | ✅ Terminado |
| HU-09 | Optimización WebP para imágenes durante la subida | Baja | ✅ Terminado |
