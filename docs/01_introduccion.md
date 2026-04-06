# 01. Introducción — Elysium Ito

## Descripción del proyecto

Elysium Ito es una plataforma web avanzada de comunicación privada y gestión administrativa. Integra mensajería en tiempo real, transferencia masiva de archivos y un panel de administración de usuarios bajo una arquitectura de seguridad multinivel con cumplimiento normativo (Ley 1581 de Colombia).

## Problema

La transferencia de archivos de gran tamaño (>1 GB) y la comunicación segura en entornos corporativos enfrentan tres problemas concretos:

- **Almacenamiento**: los servidores se saturan rápidamente con archivos pesados.
- **Timeouts**: las subidas tradicionales de un solo bloque fallan en conexiones lentas o inestables.
- **Privacidad**: los archivos quedan expuestos en servidores de terceros sin control del usuario.

## Justificación

El proyecto resuelve estos problemas con una arquitectura de **costo de almacenamiento $0** en servidor:

- Los archivos se transfieren en fragmentos (Chunked Upload) para superar los límites de PHP y evitar timeouts.
- El servidor actúa como **orquestador temporal** — nunca almacena archivos de forma permanente.
- La persistencia se delega al **Google Drive personal** de cada usuario (15 GB gratuitos).
- Las transferencias directas vía **WebRTC DataChannel** eliminan al servidor del flujo de datos por completo.
- El cumplimiento con la **Ley 1581** se garantiza mediante registro de IP y fecha de consentimiento.

## Alcance

- Mensajería instantánea en tiempo real (WebSockets via Pusher/Reverb).
- Gestión administrativa de usuarios con modelo de roles jerárquico (RBAC): Super Usuario → Admin → Usuario.
- Transferencia de archivos de hasta 1 GB mediante Chunked Upload asíncrono.
- Transferencia P2P directa entre navegadores via WebRTC DataChannel.
- Sincronización automática con Google Drive personal (backup dual emisor + receptor).
- Formulario de contacto público que enruta mensajes al Super Usuario del sistema.
- Interfaz premium y responsive con Modo Oscuro, protegida por autenticación de doble factor.
