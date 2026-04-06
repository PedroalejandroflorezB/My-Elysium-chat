# Guía de Gestión: Google Cloud API & OAuth

Este documento explica cómo se gestionan los servicios de Google Cloud para el proyecto **Elysium Ito** y el flujo técnico de autenticación.

## 🛠️ Gestión en Google Cloud Console

Para que el sistema de backup y login funcione, se deben gestionar tres pilares en la [Google Cloud Console](https://console.cloud.google.com/):

### 1. APIs Habilitadas
Las APIs son los "servicios" que Google nos presta. Para este proyecto, deben estar activas:
- **Google Drive API**: Permite subir y gestionar archivos en la nube.
- **Google People API / Google Auth**: Maneja el perfil y email del usuario para el inicio de sesión.
> [!IMPORTANT]
> Si una API no está habilitada, el servidor recibirá un error `403 Forbidden` a pesar de tener las credenciales correctas.

### 2. Pantalla de Consentimiento OAuth
Es lo que el usuario ve cuando hace clic en "Entrar con Google".
- **Scopes (Alcances)**: Hemos configurado `./auth/drive.file`. Esto significa que la app **solo** puede ver los archivos que ella misma ha creado, no toda la unidad del usuario (máxima privacidad).
- **Estado de Verificación**: Mientras el proyecto esté en "Testing", solo los usuarios agregados como "Test Users" podrán entrar.

### 3. Credenciales
Se utiliza un **ID de cliente de OAuth 2.0**.
- **Client ID & Secret**: Identifican de forma segura a nuestra aplicación ante Google.
- **URIs de Redirección**: Deben coincidir exactamente con las configuradas en el archivo `.env` (`GOOGLE_REDIRECT_URI`).

---

## 🔄 Flujo de Autenticación (OAuth 2.0)

El proceso técnico sigue estos pasos:

1.  **Solicitud**: El usuario hace clic en "Conectar Drive". Laravel lo redirige a Google pidiendo un `refresh_token` (acceso offline).
2.  **Consentimiento**: El usuario autoriza a la app.
3.  **Callback**: Google redirige a nuestro servidor con un `code`.
4.  **Intercambio**: Nuestro servidor cambia ese `code` por un `access_token` (llave temporal de 1 hora) y un `refresh_token` (llave maestra permanente).
5.  **Persistencia**: Guardamos estas llaves encriptadas en la base de datos.

## 💾 Flujo de Backup Automático

Cuando un usuario envía un archivo:
1.  Laravel guarda el archivo temporalmente en el servidor.
2.  Se dispara un **Job en segundo plano** (`UploadFileToDrive`).
3.  El Job verifica si el `access_token` ha expirado. Si es así, usa la "llave maestra" (`refresh_token`) para pedir una nueva llave sin que el usuario intervenga.
4.  El archivo se sube a Drive en "pedazos" (Resumable Upload) para asegurar que no falle con archivos grandes.
5.  Una vez en la nube, se borra el temporal del servidor y se guarda el link de Drive en el chat.

---
*Documentación técnica de Elysium Ito*
