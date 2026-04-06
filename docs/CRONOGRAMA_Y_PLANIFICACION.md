# 📅 Cronograma y Planificación - Elysium Ito

Este documento detalla el plan de trabajo seguido durante el ciclo de vida del proyecto, estructurado en fases de investigación y desarrollo de software.

## 1. Fase de Análisis y Planificación (Semanas 1-2)
- [x] **Estudio de Mercado:** Investigación de herramientas de chat actuales.
- [x] **Definición de Alcance:** Elección de Laravel + Chatify + WebRTC.
- [x] **Especificación de Requisitos:** Listado de RF y RNF iniciales.
- [x] **Estructura de Base de Datos:** Diseño de las tablas de usuarios, mensajes y logs.

## 2. Fase de Diseño e Infraestructura (Semanas 3-4)
- [x] **Configuración de Servidores:** Setup inicial en Ubuntu/AWS.
- [x] **Diseño de Interfaz:** Personalización de "Elysium" sobre Chatify.
- [x] **Arquitectura en la Nube:** Configuración de RDS Aurora MySQL y S3 (opcional).
- [x] **Configuración de Nginx:** Optimización de buffers y headers de seguridad.

## 3. Fase de Desarrollo de Core (Semanas 5-8)
- [x] **Sistema de Autenticación:** Fortify + Jetstream + 2FA por email.
- [x] **Mensajería en Tiempo Real:** Integración con Pusher.
- [x] **Gestión de Archivos:** Implementación de "Chunked Upload" para archivos grandes.
- [x] **Google OAuth:** Login y sincronización con Google Drive.

## 4. Fase de Pruebas y Optimización (Semanas 9-10)
- [x] **Pruebas de Estrés:** Verificación de carga en instancias db.t4g.micro.
- [x] **Seguridad:** Auditoría de dependencias (npm audit, composer validate).
- [x] **Corrección de Errores:** Ajuste de rutas (GET a POST) y Nginx SCRIPT_FILENAME.
- [x] **Documentación Técnica:** Creación de manuales de despliegue y sustentación.

## 5. Fase de Lanzamiento y Sustentación (Semana 11)
- [ ] **Despliegue Final:** Uso de `deploy.sh` en producción.
- [ ] **Sustentación de Proyecto:** Presentación del modelo de investigación.
- [ ] **Mantenimiento:** Monitoreo de logs y feedback de usuarios.

---

## Metodología de Trabajo
Se utilizó **SCRUM** con sprints de 2 semanas, permitiendo ajustes rápidos basados en los resultados de las pruebas de integración con APIs externas (Pusher, Google).
