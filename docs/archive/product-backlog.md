# Especificación de Requerimientos y Documentación de Proyecto — Elysium Ito

**Plataforma de Mensajería Web Segura y Gestión Administrativa**

---

## 📅 Resumen del Proyecto

**Versión:** 2.0 · **Última actualización:** 24 de marzo de 2026
**Autor:** [Nombre del Usuario]

Elysium Ito es una plataforma web avanzada diseñada para la comunicación privada y la gestión administrativa. Este sistema integra mensajería en tiempo real, transferencia masiva de archivos y un robusto panel de administración de usuarios, todo bajo una arquitectura de seguridad multinivel y cumplimiento normativo (Colombia Ley 1581).

---

## 🎯 Objetivos

### Objetivo General
Desarrollar un sistema de mensajería privada que garantice la integridad, confidencialidad y rapidez en el intercambio de información y archivos de gran tamaño entre usuarios dentro de una organización.

### Objetivos Específicos
- Implementar un motor de mensajería en tiempo real mediante WebSockets (Pusher/Reverb).
- Facilitar la transferencia de archivos de hasta 1GB utilizando tecnología de carga por fragmentos (Chunk Upload) y transferencias P2P vía WebRTC DataChannels.
- Garantizar la seguridad mediante autenticación de doble factor y un esquema de roles jerárquico inexpugnable.
- Integrar almacenamiento externo automático en Google Drive personal para optimizar recursos del servidor.

---

## 🏗️ Arquitectura Técnica

### Stack Tecnológico
| Capa | Tecnología | Justificación |
|---|---|---|
| **Backend** | Laravel 12 (PHP 8.3+) | Marco de trabajo robusto, seguro y escalable. |
| **Frontend** | Blade + Alpine.js + Tailwind CSS v4 | Interfaz reactiva, moderna y de alto rendimiento. |
| **Broadcasting** | Pusher / Laravel Reverb | Comunicación bidireccional en tiempo real. |
| **Seguridad** | Fortify + Jetstream + Sanctum | Autenticación y protección de API de última generación. |
| **Base de Datos**| MySQL 8.0 (UUIDs) | Identificadores universales únicos para mayor seguridad. |
| **Almacenamiento**| Google Drive API | Escalabilidad infinita con costo operacional $0. |

### Modelo de Roles y Permisos (RBAC)

1.  **Super Usuario (The King)**
    - **Esencia**: Cuenta blindada inmutable, oculta de listados públicos.
    - **Autenticación**: Credenciales estándar + KingCode (Puzzle de teclas por sesión).
    - **Alcance**: Control total, gestión de administradores, acceso a rutas críticas.
2.  **Administrador**
    - **Esencia**: Rol de gestión operativa.
    - **Alcance**: Gestión completa del CRUD de usuarios (Crear, Editar, Activar/Desactivar, Eliminar).
    - **Restricción**: No puede ver ni modificar al Super Usuario.
3.  **Usuario Regular**
    - **Esencia**: Rol orientado exclusivamente a la mensajería.
    - **Alcance**: Uso del Messenger, personalización de interfaz y almacenamiento.

---

## 🛡️ Seguridad y Cumplimiento

### Blindaje de Acceso
- **Middlewares Dinámicos**: `KingChallengeMiddleware` intercepta accesos del Super Usuario para verificación de puzzle.
- **Protección contra IDOR**: Resolución de rutas personalizada que bloquea el acceso directo a IDs sensibles vía URL.
- **Rate Limiting**: Protección contra fuerza bruta en rutas de login y descarga de archivos.

### Protección de Datos (Ley 1581)
- **Consentimiento Informado**: Registro de fecha e IP de aceptación de políticas de privacidad.
- **Minimización de Datos**: Almacenamiento de tokens de Google de forma encriptada (`Argon2id`).
- **Privacidad**: Archivos transferidos por WebRTC nunca tocan el servidor, garantizando la privacidad absoluta.

---

## 📋 Especificación de Requerimientos (Product Backlog)

### EP-01 · Seguridad y Autenticación
| ID | Descripción | Prioridad | Estado |
|---|---|---|---|
| US-01 | Autenticación estándar con email y contraseña. | Alta | ✅ Completado |
| US-03 | Desafío KingCode (puzzle de teclas) para Super Usuario. | Crítica | ✅ Completado |
| US-04 | Recuperación de acceso KingCode mediante secuencia de teclas. | Crítica | ✅ Completado |
| US-23 | Ocultamiento de campos sensibles en respuestas JSON. | Alta | ✅ Completado |
| US-24 | Restauración de rutas de seguridad y blindaje por Middleware. | Crítica | ✅ Completado |

### EP-02 · Gestión Administrativa (Dashboard)
| ID | Descripción | Prioridad | Estado |
|---|---|---|---|
| US-11 | Listado administrativo de usuarios con filtrado por rol. | Alta | ✅ Completado |
| US-12 | Creación de cuentas con asignación de roles jerárquicos. | Alta | ✅ Completado |
| US-14 | Bloqueo/Activación inmediata de cuentas de usuario. | Alta | ✅ Completado |
| US-16 | Revocación automática de sesiones para usuarios desactivados. | Crítica | ✅ Completado |

### EP-03 · Mensajería en Tiempo Real
| ID | Descripción | Prioridad | Estado |
|---|---|---|---|
| US-17 | Chat bidireccional instantáneo con indicadores de estado. | Crítica | ✅ Completado |
| US-31 | Filtrado de contactos basado en roles (Segregación de roles). | Alta | ✅ Completado |
| US-53 | Eliminación física de mensajes para todos los participantes. | Media | ✅ Completado |
| US-54 | Borrado completo de conversaciones y limpieza de caché. | Media | ✅ Completado |

### EP-04 · Gestión de Archivos y Backup
| ID | Descripción | Prioridad | Estado |
|---|---|---|---|
| US-44 | Carga de archivos masivos (>1GB) por fragmentos (Chunks). | Crítica | ✅ Completado |
| US-66 | Transferencia P2P (WebRTC) para privacidad absoluta. | Alta | ✅ Completado |
| US-88 | Backup automático en Google Drive personal (Costo $0). | Alta | ✅ Completado |
| US-96 | Notificaciones de estado de sincronización en tiempo real. | Media | ✅ Completado |
| US-97 | Soporte para UUIDs y optimización de Timeouts en Google API. | Alta | ✅ Completado |

---

## 🚀 Funcionalidades de Valor Agregado

1.  **WebRTC DataChannels**: Permite la transferencia directa de archivos entre navegadores, eliminando la carga del servidor y protegiendo la privacidad de los datos.
2.  **Arquitectura de Chunks**: Implementación personalizada que permite subir archivos de hasta 2GB en servidores estándar superando las limitaciones habituales de PHP.
3.  **Bóveda Google Drive**: Integración con el almacenamiento personal del usuario mediante tokens OAuth2 encriptados, permitiendo una escalabilidad de almacenamiento infinita sin costo operacional.

---

## 📈 Próximos Pasos (Vision)

- [x] **EP-05**: Historial detallado de auditoría administrativa (Audit Logs) desde el dashboard.
- [x] **Optimización**: Conversión automática de imágenes a formato WebP durante la subida.
