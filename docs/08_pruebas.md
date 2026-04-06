# 08. Pruebas — Elysium Ito

## Estrategia de pruebas

Las pruebas se realizaron de forma manual y exploratoria, cubriendo los flujos críticos del sistema. Se priorizaron los escenarios de borde relacionados con la seguridad, la integridad de archivos y la resiliencia ante fallos de red.

## Casos de prueba

### EP-01 · Autenticación y Seguridad

| ID | Caso de Prueba | Resultado | Observaciones |
|---|---|---|---|
| CP-01 | Login con email y contraseña válidos | ✅ Exitoso | Redirección correcta al chat |
| CP-02 | Login con credenciales incorrectas | ✅ Exitoso | Error genérico sin revelar si el email existe |
| CP-03 | Login con Google OAuth2 | ✅ Exitoso | Cuenta creada/vinculada correctamente |
| CP-04 | Acceso a ruta protegida sin autenticación | ✅ Exitoso | Redirección a `/login` |
| CP-05 | KingChallenge con secuencia correcta | ✅ Exitoso | `session('king_verified')` establecida |
| CP-06 | KingChallenge con secuencia incorrecta | ✅ Exitoso | Error sin revelar la secuencia correcta |
| CP-07 | Rate limiting en `/king-challenge` (>5 intentos/min) | ✅ Exitoso | HTTP 429 tras superar el límite |
| CP-08 | Acceso a ruta `/dashboard` con usuario normal | ✅ Exitoso | HTTP 403 / redirección silenciosa |
| CP-09 | Intento de acceso al Super Usuario por ID (`/users/1`) | ✅ Exitoso | HTTP 404 (route model binding) |
| CP-10 | Activación de 2FA (TOTP) | ✅ Exitoso | Código TOTP validado correctamente |

### EP-02 · Gestión Administrativa

| ID | Caso de Prueba | Resultado | Observaciones |
|---|---|---|---|
| CP-11 | Crear usuario desde panel admin | ✅ Exitoso | Avatar DiceBear generado automáticamente |
| CP-12 | Crear usuario con dominio sin registros MX | ✅ Exitoso | Advertencia mostrada, usuario creado igualmente |
| CP-13 | Editar nombre y tagname de usuario | ✅ Exitoso | Cambios reflejados en tiempo real via Pusher |
| CP-14 | Desactivar cuenta de usuario activo | ✅ Exitoso | Sesión revocada en siguiente request del usuario |
| CP-15 | Intentar desactivar la propia cuenta | ✅ Exitoso | HTTP 403 con mensaje de error |
| CP-16 | Eliminar usuario | ✅ Exitoso | Eliminación física confirmada |
| CP-17 | Búsqueda por `@tagname` | ✅ Exitoso | Resultado exacto, segregado por rol |

### EP-03 · Mensajería en Tiempo Real

| ID | Caso de Prueba | Resultado | Observaciones |
|---|---|---|---|
| CP-21 | Envío de mensaje de texto entre dos usuarios | ✅ Exitoso | Entrega instantánea sin recargar página |
| CP-22 | Indicador de presencia online/offline | ✅ Exitoso | Actualización en tiempo real via `presence-activeStatus` |
| CP-23 | Eliminación de mensaje para todos los participantes | ✅ Exitoso | Mensaje eliminado de ambas vistas |
| CP-24 | Borrado completo de conversación | ✅ Exitoso | Historial y caché limpiados |
| CP-25 | Modo Oscuro — sincronización entre pestañas | ✅ Exitoso | Cambio instantáneo sin recargar |

### EP-04 · Chunked Upload

| ID | Caso de Prueba | Resultado | Observaciones |
|---|---|---|---|
| CP-31 | Subida de archivo de 50 MB | ✅ Exitoso | Ensamblado correcto, mensaje creado |
| CP-32 | Subida de archivo de 600 MB | ✅ Exitoso | Disco del servidor liberado al 100% tras Job |
| CP-33 | Subida con extensión no permitida | ✅ Exitoso | HTTP 422, archivo rechazado |
| CP-34 | Subida con Magic Bytes incorrectos (`.jpg` con contenido `.exe`) | ✅ Exitoso | HTTP 422, validación de MIME real |
| CP-35 | Subida con `uploadId` inválido (no UUID v4) | ✅ Exitoso | HTTP 422 |
| CP-36 | Intento de inyección de UUID ajeno | ✅ Exitoso | HTTP 403, aislamiento por usuario |
| CP-37 | Más de 3 uploads concurrentes por usuario | ✅ Exitoso | HTTP 429 al superar el límite |
| CP-38 | Barra de progreso con velocidad y tiempo estimado | ✅ Exitoso | Actualización fluida en UI |

### EP-05 · Google Drive Backup

| ID | Caso de Prueba | Resultado | Observaciones |
|---|---|---|---|
| CP-41 | Vincular Google Drive (OAuth2) | ✅ Exitoso | Tokens encriptados guardados en BD |
| CP-42 | Subida a Drive tras Chunked Upload | ✅ Exitoso | Estado: `processing` → `synced` con link |
| CP-43 | Backup dual (emisor + receptor) | ✅ Exitoso | Dos Jobs independientes, dos copias en Drive |
| CP-44 | Refresh automático de `access_token` expirado | ✅ Exitoso | Token refrescado sin intervención del usuario |
| CP-45 | Token revocado por el usuario en Google | ✅ Exitoso | Estado `error_authorization`, enlace "Vincular Drive" |
| CP-46 | Fallo de red durante subida a Drive (3 reintentos) | ✅ Exitoso | Estado `failed` tras agotar reintentos |
| CP-47 | Toggle backup (pausar/reanudar) | ✅ Exitoso | Sin revocar tokens |
| CP-48 | Desvincular Drive | ✅ Exitoso | Token revocado en Google y limpiado en BD |
| CP-49 | Drive sin espacio suficiente | ✅ Exitoso | Job falla con mensaje descriptivo en log |

### EP-06 · WebRTC P2P

| ID | Caso de Prueba | Resultado | Observaciones |
|---|---|---|---|
| CP-51 | Solicitud de transferencia P2P | ✅ Exitoso | Notificación recibida por el receptor |
| CP-52 | Rechazo de transferencia por el receptor | ✅ Exitoso | Señal `transfer-cancel` enviada al emisor |
| CP-53 | Transferencia P2P completada (archivo pequeño) | ✅ Exitoso | Archivo descargado en receptor, metadatos en BD |
| CP-54 | Verificación de que el servidor no almacena bytes | ✅ Exitoso | Solo metadatos en `ch_messages` |

### EP-07 · Infraestructura

| ID | Caso de Prueba | Resultado | Observaciones |
|---|---|---|---|
| CP-61 | Monitor de disco al superar 90% | ✅ Exitoso | Nuevas subidas bloqueadas (HTTP 503) |
| CP-62 | Páginas de error personalizadas (404, 403, 500) | ✅ Exitoso | Vistas personalizadas renderizadas |
| CP-63 | Verificación de email tras registro | ✅ Exitoso | Email enviado con link firmado |

### EP-08 · Correcciones de Configuración

| ID | Caso de Prueba | Resultado | Observaciones |
|---|---|---|---|
| CP-71 | `autosize` disponible como global en el chat | ✅ Exitoso | Sin `TypeError: global is undefined` en consola |
| CP-72 | Cropper.js funcional en cambio de avatar | ✅ Exitoso | UMD cargado como script estático |
| CP-73 | Vinculación de Drive con `google_drive.redirect` correcto | ✅ Exitoso | Callback URI separada del login social |
| CP-74 | Middlewares de Chatify aplicados correctamente | ✅ Exitoso | Arrays fijos en `config/chatify.php` |
| CP-75 | `LOG_LEVEL` sin comentario inline corrupto | ✅ Exitoso | Valor leído correctamente por Laravel |

## Resumen de resultados

| Épica | Total | Exitosos | Fallidos |
|---|---|---|---|
| Autenticación y Seguridad | 10 | 10 | 0 |
| Gestión Administrativa | 7 | 7 | 0 |
| Mensajería en Tiempo Real | 5 | 5 | 0 |
| Chunked Upload | 8 | 8 | 0 |
| Google Drive Backup | 9 | 9 | 0 |
| WebRTC P2P | 4 | 4 | 0 |
| Infraestructura | 3 | 3 | 0 |
| Correcciones de Configuración | 5 | 5 | 0 |
| **Total** | **51** | **51** | **0** |
