# 02. Objetivos — Elysium Ito

## Objetivo General

Desarrollar un sistema de mensajería privada que garantice la integridad, confidencialidad y rapidez en el intercambio de información y archivos de gran tamaño entre usuarios dentro de una organización, con costo de almacenamiento $0 en servidor.

## Objetivos Específicos

- Implementar un motor de mensajería en tiempo real mediante WebSockets (Pusher/Reverb) con canales privados y de presencia.
- Facilitar la transferencia de archivos de hasta 1 GB utilizando Chunked Upload (fragmentos de 10 MB, 3 en paralelo) y transferencias P2P directas vía WebRTC DataChannels.
- Garantizar la seguridad mediante autenticación de doble factor, un esquema de roles jerárquico (Super Usuario → Admin → Usuario) y middlewares de protección contra IDOR y fuerza bruta.
- Integrar almacenamiento externo automático en Google Drive personal (scope `drive.file`, protocolo Resumable Upload) para mantener el disco del servidor siempre vacío.
- Cumplir con la Ley 1581 de Colombia registrando IP y fecha de aceptación de políticas de privacidad, y almacenando tokens OAuth2 encriptados.
- Proveer un mecanismo de recuperación del Super Usuario via CLI (`php artisan super:setup`) para garantizar continuidad operativa ante pérdida de acceso.
