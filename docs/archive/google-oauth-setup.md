# Configuración Google OAuth — Elysium Ito

## Arquitectura de la integración

Este proyecto usa **una sola app OAuth de Google** para dos flujos distintos:

| Flujo | Ruta de callback | Scopes | Propósito |
|---|---|---|---|
| Login / Registro | `/auth/google/login-callback` | `openid profile email drive.file drive.readonly` | Autenticar al usuario y obtener tokens de Drive en un solo paso |
| Vincular Drive (re-vincular) | `/auth/google/callback` | `drive.file drive.readonly` | Para usuarios con cuenta manual que quieran activar Drive después |

Ambos flujos usan las mismas credenciales (`GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET`).
Ambas URIs deben estar registradas en Google Cloud Console.

---

## 1. Crear proyecto en Google Cloud Console

1. Ve a [console.cloud.google.com](https://console.cloud.google.com)
2. Selector de proyectos → **Nuevo proyecto**
3. Nombre: `Elysium Ito` (o el que prefieras) → **Crear**

---

## 2. Habilitar APIs necesarias

1. **APIs y servicios → Biblioteca**
2. Habilitar:
   - **Google Drive API** (para backup de archivos)
   - **People API** (para datos de perfil en login)

---

## 3. Configurar pantalla de consentimiento OAuth

1. **APIs y servicios → Pantalla de consentimiento de OAuth**
2. Tipo de usuario: **Externo** → **Crear**
3. Campos obligatorios:
   - Nombre de la app: `Elysium Ito`
   - Correo de asistencia: tu correo
   - Correo de contacto del desarrollador: tu correo
4. **Permisos (Scopes)** → añadir:
   - `.../auth/userinfo.email`
   - `.../auth/userinfo.profile`
   - `openid`
   - `.../auth/drive.file`
   - `.../auth/drive.readonly`
5. Guardar y continuar

> En desarrollo puedes dejarlo en modo "Testing" y agregar tu correo como usuario de prueba.

---

## 4. Crear credenciales OAuth 2.0

1. **APIs y servicios → Credenciales → Crear credenciales → ID de cliente OAuth**
2. Tipo: **Aplicación web**
3. Nombre: `Elysium Ito Web`
4. **Orígenes de JavaScript autorizados:**
   ```
   http://localhost
   http://localhost:8000
   ```
5. **URIs de redireccionamiento autorizados** — registrar AMBAS:
   ```
   http://localhost:8000/auth/google/login-callback
   http://localhost:8000/auth/google/callback
   ```
   En producción agregar también:
   ```
   https://tu-dominio.com/auth/google/login-callback
   https://tu-dominio.com/auth/google/callback
   ```
6. **Crear** → copiar **Client ID** y **Client Secret**

---

## 5. Configurar `.env`

```env
GOOGLE_CLIENT_ID=tu-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=tu-client-secret
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/login-callback
GOOGLE_DRIVE_REDIRECT_URI=http://localhost:8000/auth/google/callback
```

En producción cambiar las URIs al dominio real.

---

## 6. Limpiar caché

```bash
php artisan config:clear
php artisan config:cache
```

---

## Comportamiento del `GoogleAuthController`

| Caso | Resultado |
|---|---|
| Email ya existe con `google_id` | Login directo |
| Email existe (cuenta manual) | Vincula `google_id` + login |
| Email no existe | Crea cuenta nueva + login |
| En todos los casos | Guarda `google_access_token` + `google_refresh_token` para Drive |

Los tokens de Drive se guardan **en el mismo paso del login** — el usuario no necesita un segundo flujo para activar el backup de archivos.

---

## Notas de seguridad

- `prompt=consent` fuerza la entrega del `refresh_token` en el primer consentimiento.
- **Refresh Token Preservation**: si Google no entrega `refresh_token` en reautenticaciones silenciosas, se conserva el existente. Sin esto, el backup de archivos falla silenciosamente cuando el `access_token` expira (60 min).
- Los tokens se guardan **encriptados** con `encrypt()` — nunca en texto plano.
- `google_id` está en `$hidden` del modelo User — nunca se expone en JSON.
- Las cuentas creadas por OAuth tienen contraseña aleatoria (`Str::random(32)`) — no usable para login manual.
- **Re-vinculación requerida**: usuarios que vincularon Drive antes de agregar `drive.readonly` deben reconectar en `/auth/google/drive` para que Google emita el nuevo scope.

---

## Checklist de activación

- [ ] Proyecto creado en Google Cloud Console
- [ ] Google Drive API y People API habilitadas
- [ ] Pantalla de consentimiento configurada con scopes correctos (`drive.file` + `drive.readonly`)
- [ ] Credenciales OAuth creadas con **ambas** URIs de callback registradas
- [ ] `GOOGLE_CLIENT_ID` y `GOOGLE_CLIENT_SECRET` en `.env`
- [ ] `GOOGLE_REDIRECT_URI` y `GOOGLE_DRIVE_REDIRECT_URI` apuntan a las rutas correctas
- [ ] `php artisan config:clear` ejecutado
- [ ] Probar login con Google → verificar que `google_access_token` se guarda en DB
- [ ] Usuarios con Drive vinculado previamente deben reconectar para obtener `drive.readonly`
