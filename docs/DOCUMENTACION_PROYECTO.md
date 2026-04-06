# 📄 Documentación Técnica y Sustentación - Elysium Ito

## 1. Introducción
Elysium Ito es una plataforma de comunicación en tiempo real diseñada bajo un modelo de investigación de software, enfocada en la seguridad, escalabilidad y la integración de servicios en la nube (AWS).

## 2. Planteamiento del Problema
En la actualidad, las comunicaciones digitales enfrentan retos críticos en términos de privacidad de datos y dependencia de servidores centralizados. Muchas soluciones comerciales carecen de transparencia en el manejo de archivos y no ofrecen mecanismos robustos de respaldo controlado por el usuario.

## 3. Justificación Técnica
La creación de Elysium Ito surge de la necesidad de investigar y desarrollar un sistema que combine la eficiencia de la mensajería instantánea con la seguridad de la nube. El uso de **WebRTC** para transferencias P2P y **Laravel 12** como motor de backend permite demostrar que es posible construir herramientas de alta fidelidad con un consumo de recursos optimizado, incluso en infraestructuras modestas (AWS T4G Micro).

## 4. Tecnologías Implementadas (Resumen)

Para el desarrollo del proyecto se han utilizado las siguientes tecnologías clave:

- **Backend:** Laravel 12 y PHP 8.2+.
- **Frontend:** Livewire 3 y Tailwind CSS.
- **Comunicación en tiempo real:** Pusher (WebSockets) y WebRTC.
- **Seguridad:** Laravel Fortify y Jetstream con autenticación 2FA personalizada.
- **Infraestructura Cloud:** Nginx sobre Ubuntu 22.04 LTS y base de datos MySQL (AWS RDS).
- **Integración Social:** Google OAuth 2.0 y sincronización con Google Drive API.

---

## 5. Casos de Uso (Use Cases)

### CU-01: Registro y Verificación de Usuario
**Actor:** Usuario no registrado.
**Descripción:** El usuario crea una cuenta y debe verificar su identidad vía correo electrónico para activar las funciones de chat.
**Flujo Principal:**
1. El usuario ingresa nombre, email y contraseña.
2. El sistema valida el dominio del email (MX Check).
3. Se envía un token de verificación.
4. El usuario confirma el token y accede a la plataforma.

### CU-02: Comunicación P2P (WebRTC)
**Actor:** Usuario autenticado.
**Descripción:** Los usuarios pueden establecer conexiones directas para transferir archivos o datos sin pasar por el servidor central, garantizando privacidad.
**Flujo Principal:**
1. El Usuario A inicia una solicitud de señalización.
2. El sistema (via Pusher) entrega la oferta al Usuario B.
3. Se establece el túnel WebRTC.

### CU-03: Gestión de Mensajería y Archivos
**Actor:** Usuario autenticado.
**Descripción:** Envío de mensajes de texto y archivos con respaldo automático en Google Drive si está configurado.

---

## 6. Historias de Usuario (User Stories)

| ID | Historia | Prioridad | Criterio de Aceptación |
|----|----------|-----------|------------------------|
| HU-01 | Como usuario, quiero verificar mi correo para asegurar que mi cuenta es real y segura. | Alta | El sistema bloquea el chat hasta que el email sea verificado. |
| HU-02 | Como usuario, quiero enviar archivos grandes mediante "chunks" para evitar fallos de red. | Media | Archivos de hasta 100MB se suben en fragmentos de 2MB. |
| HU-03 | Como administrador, quiero ver logs de auditoría para monitorear el comportamiento del sistema. | Alta | El dashboard muestra IP, fecha y acción de cada evento crítico. |
| HU-04 | Como usuario, quiero vincular mi Google Drive para que mis archivos tengan un backup externo. | Baja | Opción de toggle en perfil para activar/desactivar backup automático. |

---

## 7. Requisitos y Requerimientos

### Requerimientos Funcionales (RF)
- **RF-01:** Autenticación multi-factor (2FA) mediante código por correo.
- **RF-02:** Sistema de mensajería en tiempo real con estados (visto, escribiendo).
- **RF-03:** Panel de administración para gestión de usuarios y estadísticas.
- **RF-04:** Integración con OAuth 2.0 (Google Login).

### Requerimientos No Funcionales (RNF)
- **RNF-01 (Seguridad):** Encriptación de datos en tránsito (SSL/TLS) y en reposo (Bcrypt para contraseñas).
- **RNF-02 (Escalabilidad):** Despliegue optimizado para instancias AWS db.t4g.micro.
- **RNF-03 (Disponibilidad):** Arquitectura desacoplada usando Pusher para mensajería.
- **RNF-04 (Rendimiento):** Uso de buffers optimizados en Nginx para URLs largas de OAuth.

---

## 8. Modelo de Investigación de Software
El proyecto se basa en la **Metodología Ágil (Scrum)** adaptada a la investigación, donde cada incremento de software (Sprints) valida una hipótesis técnica (ej. eficiencia de WebRTC frente a WebSockets tradicionales para transferencia de archivos).
