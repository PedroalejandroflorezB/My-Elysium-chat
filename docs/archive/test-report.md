# Elysium Ito — Reporte de Pruebas Funcionales

**Fecha:** 21 de marzo de 2026  
**Proyecto:** Elysium Ito  
**Estado general:** ✅ Aprobado

---

## 1. Autenticación

| # | Prueba | Resultado |
|---|--------|-----------|
| 1.1 | Inicio de sesión con credenciales válidas | ✅ |
| 1.2 | Redirección al dashboard tras login exitoso | ✅ |
| 1.3 | Registro de nuevo usuario | ✅ |
| 1.4 | Validación de campos requeridos en login y registro | ✅ |
| 1.5 | Enlace "¿Olvidaste tu contraseña?" funcional | ✅ |
| 1.6 | Cierre de sesión desde el menú de navegación | ✅ |

---

## 2. Dashboard

| # | Prueba | Resultado |
|---|--------|-----------|
| 2.1 | Carga correcta del dashboard para usuarios autenticados | ✅ |
| 2.2 | Visualización de tarjetas de información (email, fecha de ingreso, estado) | ✅ |
| 2.3 | Acceso a Messenger desde el dashboard | ✅ |
| 2.4 | Acceso a gestión de Usuarios desde el dashboard | ✅ |
| 2.5 | Modo oscuro / modo claro funcional desde el menú de navegación | ✅ |
| 2.6 | Persistencia del modo oscuro entre sesiones | ✅ |

---

## 3. Gestión de Usuarios

| # | Prueba | Resultado |
|---|--------|-----------|
| 3.1 | Listado de usuarios registrados | ✅ |
| 3.2 | Creación de nuevo usuario desde el panel | ✅ |
| 3.3 | Edición de datos de usuario existente | ✅ |
| 3.4 | Actualización de contraseña desde el modal de edición | ✅ |
| 3.5 | Activación / desactivación de usuario | ✅ |
| 3.6 | Eliminación de usuario | ✅ |
| 3.7 | Restricción: no se puede modificar ni eliminar al administrador principal | ✅ |

---

## 4. Mensajería (Chatify)

| # | Prueba | Resultado |
|---|--------|-----------|
| 4.1 | Carga de la interfaz de chat | ✅ |
| 4.2 | Envío y recepción de mensajes de texto en tiempo real | ✅ |
| 4.3 | Listado de contactos disponibles | ✅ |
| 4.4 | Búsqueda de usuarios no devuelve al usuario logueado en los resultados | ✅ |
| 4.5 | Envío de archivos adjuntos (upload chunked) | ✅ |
| 4.6 | Barra de progreso visible durante el upload con velocidad y tiempo estimado | ✅ |
| 4.7 | Archivos de hasta 600 MB enviados correctamente | ✅ |
| 4.8 | Botón de envío se deshabilita durante el upload (previene doble envío) | ✅ |
| 4.9 | Mensaje con archivo aparece en el chat al completar el upload | ✅ |
| 4.10 | Ícono del archivo refleja el tipo según extensión (video, audio, PDF, ZIP, etc.) | ✅ |
| 4.11 | Lista de contactos muestra nombre del archivo en lugar de "Attachment" | ✅ |
| 4.12 | Tarjeta de archivo muestra nombre original sin HTML entities | ✅ |
| 4.13 | Tarjeta de archivo muestra el peso del archivo (KB / MB / GB) | ✅ |
| 4.14 | Botón de descarga inicia la descarga directamente en la carpeta de descargas del navegador | ✅ |
| 4.15 | El archivo se descarga con el nombre original con el que fue subido | ✅ |
| 4.16 | Skeletons de carga visibles al abrir una conversación (lista de contactos y burbujas) | ✅ |
| 4.17 | Botón de 3 puntos oculto cuando no hay chat activo | ✅ |
| 4.18 | Botón de 3 puntos visible al seleccionar un contacto | ✅ |
| 4.19 | Dropdown muestra: Borrar conversación, Ver perfil, Cerrar chat | ✅ |
| 4.20 | "Cerrar chat" limpia el chat, oculta el input y deselecciona el contacto | ✅ |
| 4.21 | Input de escritura oculto cuando no hay chat activo | ✅ |
| 4.22 | Panel lateral (ⓘ) muestra datos del contacto activo (nombre, tagname, estado) | ✅ |
| 4.23 | Panel lateral muestra badge "Activo" / "Inactivo" según estado real del contacto | ✅ |
| 4.24 | Badge de estado del contacto se actualiza en tiempo real vía Pusher presence channel | ✅ |
| 4.25 | Panel propio muestra badge "Activo" al conectarse (actualizado por Pusher, no por BD) | ✅ |
| 4.26 | Activar una sesión no altera el estado de la otra sesión (fix setActiveStatus) | ✅ |

---

## 5. Interfaz y Experiencia de Usuario

| # | Prueba | Resultado |
|---|--------|-----------|
| 5.1 | Diseño responsive en pantallas de escritorio | ✅ |
| 5.2 | Navegación entre secciones sin errores | ✅ |
| 5.3 | Formularios con textos en español | ✅ |
| 5.4 | Consistencia visual en modo oscuro y modo claro | ✅ |
| 5.5 | Menú de navegación superior funcional | ✅ |
| 5.6 | Dropdown de cuenta con opciones centradas y correctas | ✅ |

---

## 6. Seguridad

| # | Prueba | Resultado |
|---|--------|-----------|
| 6.1 | Rutas protegidas redirigen a login si no hay sesión activa | ✅ |
| 6.2 | Usuarios sin rol de administrador no acceden al dashboard | ✅ |
| 6.3 | Campos sensibles no expuestos en respuestas JSON | ✅ |
| 6.4 | Validación de entradas en todos los formularios | ✅ |
| 6.5 | Ruta de descarga de archivos requiere autenticación activa | ✅ |
| 6.6 | Ruta de descarga protege contra path traversal (basename sanitization) | ✅ |

---

## 7. Sistema de Permisos y Roles

| # | Prueba | Resultado |
|---|--------|-----------|
| 7.1 | Usuario regular no puede acceder al dashboard (redirige silenciosamente) | ✅ |
| 7.2 | Usuario regular no puede acceder al CRUD de usuarios (redirige silenciosamente) | ✅ |
| 7.3 | Admin activo accede correctamente al dashboard y CRUD | ✅ |
| 7.4 | Admin desactivado por el Super Usuario pierde acceso al siguiente request | ✅ |
| 7.5 | Admin no puede modificar ni eliminar al Super Usuario | ✅ |
| 7.6 | Super Usuario accede a todas las rutas tras pasar el KingChallenge | ✅ |
| 7.7 | Super Usuario sin verificar puzzle es redirigido al KingChallenge | ✅ |
| 7.8 | Rutas `/king/puzzle` y `/king/password` devuelven redirección silenciosa a no-Super Usuario | ✅ |
| 7.9 | El Super Usuario no aparece en el listado del CRUD | ✅ |
| 7.10 | Estado de cuenta del Super Usuario y admins muestra "Verificada" siempre | ✅ |

---

## 8. Seguridad de Rutas y URLs

| # | Prueba | Resultado |
|---|--------|-----------|
| 8.1 | Acceso directo por ID al Super Usuario en URL (`/users/1`) devuelve 404 | ✅ |
| 8.2 | Middleware `admin` redirige a `/` sin revelar existencia de la ruta (sin 403) | ✅ |
| 8.3 | Middleware `king` redirige a `/` sin revelar existencia de la ruta (sin 403) | ✅ |
| 8.4 | Rate limiting en `/king-challenge`: máx 5 intentos/min por IP | ✅ |
| 8.5 | Rate limiting en `/king-forgot` y `/king-reset`: máx 10 intentos/min por IP | ✅ |
| 8.6 | Consultas a la base de datos usan Eloquent (prepared statements, inmune a SQL injection) | ✅ |
| 8.7 | Campos `is_king` y `king_puzzle` ocultos en respuestas JSON (`$hidden`) | ✅ |

---

## 9. Transferencia P2P WebRTC

| # | Prueba | Resultado |
|---|--------|-----------|
| 9.1 | Banner de archivo entrante aparece con nombre y tamaño al recibir transfer-request | ⬜ Pendiente |
| 9.2 | Botón "Aceptar" inicia el handshake WebRTC y la transferencia | ⬜ Pendiente |
| 9.3 | Botón "Rechazar" cancela la transferencia y notifica al sender | ⬜ Pendiente |
| 9.4 | Barra de progreso del receptor se actualiza durante la recepción | ⬜ Pendiente |
| 9.5 | Archivo se descarga automáticamente al completarse la recepción | ⬜ Pendiente |
| 9.6 | Sender ve el mensaje con el archivo en su chat tras completar la transferencia | ⬜ Pendiente |
| 9.7 | Receptor recibe el mensaje vía Pusher tras el save de metadatos | ⬜ Pendiente |
| 9.8 | El temp card del sender se reemplaza correctamente (no queda duplicado) | ⬜ Pendiente |
| 9.9 | El servidor no almacena bytes del archivo (solo metadatos en `ch_messages`) | ⬜ Pendiente |
| 9.10 | Señales WebRTC (offer/answer/ICE) se relayan correctamente vía Pusher | ⬜ Pendiente |

---

## 10. Manejo de Errores

| # | Prueba | Resultado |
|---|--------|-----------|
| 10.1 | Error de red en envío de mensaje muestra toast en lugar de `alert()` nativo | ✅ |
| 10.2 | Archivo de tipo no permitido muestra toast de error (no `alert()`) | ✅ |
| 10.3 | Archivo demasiado grande muestra toast de error (no `alert()`) | ✅ |
| 10.4 | Error en chunked upload muestra `errorMessageCard` + toast descriptivo | ✅ |
| 10.5 | Error al eliminar conversación muestra toast y cierra el modal de carga | ✅ |
| 10.6 | Error al eliminar mensaje muestra toast y cierra el modal de carga | ✅ |
| 10.7 | Error al editar mensaje muestra toast y cierra el modal | ✅ |
| 10.8 | Error al guardar configuración muestra toast y cierra el modal de carga | ✅ |
| 10.9 | Rechazo de transferencia P2P muestra toast `warning` (no `alert()`) | ✅ |
| 10.10 | Error en DataChannel sender/receiver muestra toast y limpia estado WebRTC | ✅ |
| 10.11 | Error en `acceptTransfer` muestra toast y hace cleanup | ✅ |
| 10.12 | Error en `assembleAndDownload` muestra toast sin romper la UI | ✅ |
| 10.13 | Error en señalización WebRTC (`signal()`) capturado con `.catch()` | ✅ |
| 10.14 | Error en `p2pSaveTransfer` capturado con `.catch()` y muestra toast | ✅ |
| 10.15 | Página 404 muestra diseño personalizado consistente con el sistema | ✅ |
| 10.16 | Página 403 muestra diseño personalizado consistente con el sistema | ✅ |
| 10.17 | Página 419 (CSRF expirado) muestra diseño personalizado con instrucción de recarga | ✅ |
| 10.18 | Página 429 (rate limit) muestra diseño personalizado | ✅ |
| 10.19 | Página 500 muestra diseño personalizado sin exponer stack trace | ✅ |
| 10.20 | Página 503 muestra diseño personalizado | ✅ |
| 10.21 | Rutas AJAX devuelven JSON estructurado en errores (no HTML de excepción) | ✅ |
| 10.22 | Errores de controller registrados en `storage/logs/laravel.log` vía `Log::error` | ✅ |

---

*Documento generado internamente. Uso exclusivo del equipo de desarrollo.*
