# Funcionamiento del Backup en Elysium Ito

Este documento explica de manera técnica y funcional cómo se gestionan las copias de seguridad de archivos hacia Google Drive.

## 1. El Proceso de Subida
Cuando un usuario envía un archivo, este se procesa en el servidor mediante **Chunked Uploads** (subida por fragmentos). Una vez que todas las partes han llegado y el archivo se ensambla, se dispara el flujo de backup.

## 2. Backup Dual e Independiente
El sistema realiza dos comprobaciones automáticas al momento del envío:

### A. Para el Emisor (Quien envía)
- Si el emisor tiene su cuenta de Google vinculada y la opción **"Copia de seguridad"** activada en su panel lateral.
- El sistema crea un **Job** en segundo plano que sube el archivo a su unidad de Google Drive personal bajo el scope `drive.file`.

### B. Para el Receptor (Quien recibe)
- Si la persona que recibe el mensaje tiene su propia cuenta vinculada y el backup activo.
- El sistema genera una **copia independiente** del archivo y la sube al Drive del receptor.
- Esto sucede incluso si el emisor no tiene activada la copia de seguridad.

## 3. Características Clave
- **Asíncrono**: La subida a Drive ocurre en "segundo plano" mediante colas de Laravel (`drive-uploads`). Esto significa que el chat no se bloquea ni se vuelve lento mientras se realiza el respaldo.
- **Privacidad**: Cada usuario solo tiene acceso a sus copias en su propia nube. El servidor no actúa como repositorio central compartido, sino como un puente seguro.
- **Resiliencia**: Si la subida a Drive falla (por ejemplo, falta de espacio en la nube del usuario), el chat muestra un aviso y ofrece la opción de reintentar si el archivo aún se encuentra en el servidor temporal.

## 4. Requisitos para el Backup
Para que un archivo se respalde, el usuario debe haber completado el flujo de:
1.  **Conexión**: Vínculo con Google vía OAuth2.
2.  **Consentimiento**: Autorizar el permiso específico para ver y gestionar solo archivos creados por esta aplicación.
3.  **Activación**: Tener el interruptor de "Copia de seguridad" (toggle) en posición de encendido.
