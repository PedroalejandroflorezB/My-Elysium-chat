# 10. Conclusiones — Elysium Ito

## Conclusiones del proyecto

Elysium Ito demuestra que es posible construir una plataforma de comunicación privada con transferencia de archivos de gran tamaño a costo de almacenamiento $0 en servidor, delegando la persistencia a Google Drive personal de cada usuario. La arquitectura de "Servidor como Orquestador" resultó ser la decisión de diseño más impactante del proyecto: el servidor valida, ensambla y despacha — nunca almacena.

La implementación del Chunked Upload con Double-Lock, Magic Bytes validation y aislamiento por usuario en disco resolvió los tres problemas originales (almacenamiento, timeouts y privacidad) sin depender de servicios de terceros para el almacenamiento temporal. El uso de Jobs y Queues fue determinante para mantener la experiencia de usuario fluida durante subidas de 1 GB.

El modelo de seguridad multinivel (RBAC + KingChallenge + tokens encriptados + rate limiting + Magic Bytes) cubre los vectores de ataque más comunes en aplicaciones de transferencia de archivos, sin sacrificar usabilidad.

## Aprendizajes clave

- Desacoplar los assets estáticos de `public/` y gestionarlos con Vite permite un pipeline de desarrollo estable y despliegues reproducibles, especialmente en Windows donde el HMR requiere polling. Sin embargo, las librerías UMD legacy (`autosize.js`, `cropper.min.js`) no son compatibles con el contexto ESM estricto de Vite — deben servirse como scripts estáticos desde `public/` para evitar errores de `global is undefined`.

- Los Jobs y Queues son indispensables para procesos de larga duración. Intentar subir 1 GB a Drive en el hilo principal del servidor resulta en timeouts inevitables. La cola `drive-uploads` con 3 reintentos y 15 minutos de timeout por intento es la solución correcta.

- El protocolo Resumable Upload de Google Drive API v3 es la única opción viable para archivos grandes. El upload simple falla con archivos >5 MB en conexiones inestables.

- La gestión de tokens OAuth2 requiere más cuidado del esperado: el `access_token` expira en 1 hora, el `refresh_token` puede ser revocado por el usuario en cualquier momento, y ambos deben almacenarse encriptados. Verificar la expiración por timestamp local evita round-trips innecesarios a Google. Además, los flujos de login social y vinculación de Drive deben usar URIs de callback separadas — configuradas como bloques independientes en `config/services.php`.

- Las funciones `env()` en archivos de configuración de Laravel no pueden devolver arrays. Cualquier valor que deba ser un array debe definirse directamente en el archivo de configuración, no leerse desde variables de entorno.

- La arquitectura RBAC debe definirse antes de escribir la primera línea de código. Añadir restricciones de roles a un sistema ya construido es costoso y propenso a errores.

- La validación de Magic Bytes es más confiable que confiar en la extensión del archivo o en el `Content-Type` del request. Un atacante puede falsificar ambos; los primeros bytes del archivo no.

- Los comentarios inline en archivos `.env` (ej. `LOG_LEVEL=debug # comentario`) corrompen el valor leído por Laravel. Los comentarios deben ir siempre en líneas propias.

## Mejoras futuras

- Implementar Audit Logs detallados para rastrear cada acción de los administradores (creación, edición, desactivación de usuarios) con IP y timestamp.

- Agregar soporte para Google Drive File Picker para que los usuarios puedan compartir archivos ya existentes en sus cuentas de Drive sin necesidad de re-subirlos.

- Implementar llamadas de voz P2P via WebRTC integradas directamente en el chat, reutilizando la infraestructura de señalización ya existente.

- Optimización WebP: convertir imágenes automáticamente a WebP durante el ensamblado de chunks para reducir el tamaño de los archivos subidos a Drive.

- Soporte para múltiples proveedores de almacenamiento (OneDrive, Dropbox) como alternativa a Google Drive, manteniendo la misma arquitectura de Jobs.
