# Configuración Google OAuth — Elysium Ito

## Arquitectura de la integración

Una sola app OAuth de Google para dos flujos:

| Flujo | Ruta de callback | Scopes | Propósito |
|---|---|---|---|
| Login / Registro | `/auth/google/login-callback` | `openid profile email drive.file` | Autenticar y obtener tokens de Drive en un solo paso |
| Vincular Drive | `/auth/google/callback` | `drive.file` | Para usuarios con cuenta manual que activan Drive después |

Ambos flujos usan las mismas credenciales (`GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET`). Ambas URIs deben estar registradas en Google Cloud Console.

---

## Pasos de configuración

### 1. Crear proyecto en Google Cloud Console

1. [console.cloud.google.com](https://console.cloud.google.com) → **Nuevo proyecto** → Nombre: `Elysium Ito`

### 2. Habilitar APIs

- **Google Drive API**
- **People API**

### 3. Pantalla de consentimiento OAuth

1. APIs y servicios → Pantalla de consentimiento → Tipo: **Externo**
2. Scopes a agregar:
   - `.../auth/userinfo.email`
   - `.../auth/userinfo.profile`
   - `openid`
   - `.../auth/drive.file`

### 4. Crear credenciales OAuth 2.0

1. Credenciales → Crear → **ID de cliente OAuth** → Tipo: **Aplicación web**
2. URIs de redireccionamiento autorizados (registrar ambas):
   ```
   http://localhost:8000/auth/google/login-callback
   http://localhost:8000/auth/google/callback
   ```
   En producción agregar también con el dominio real.

### 5. Variables de entorno

```env
GOOGLE_CLIENT_ID=tu-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=tu-client-secret
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/login-callback
GOOGLE_DRIVE_REDIRECT_URI=http://localhost:8000/auth/google/callback
```

### 6. Limpiar caché

```bash
php artisan config:clear && php artisan config:cache
```

---

## Comportamiento del callback de login

| Caso | Resultado |
|---|---|
| Email ya existe con `google_id` | Login directo |
| Email existe (cuenta manual) | Vincula `google_id` + login |
| Email no existe | Crea cuenta nueva + login |
| En todos los casos | Guarda `google_access_token` + `google_refresh_token` para Drive |

---

## Notas de seguridad

- `prompt=consent` fuerza la entrega del `refresh_token` en el primer consentimiento.
- **Refresh Token Preservation**: si Google no entrega `refresh_token` en reautenticaciones, se conserva el existente. Sin esto el backup falla silenciosamente cuando el `access_token` expira (60 min).
- Los tokens se guardan **encriptados** con `encrypt()` — nunca en texto plano en BD.
- `google_id` está en `$hidden` del modelo User — nunca se expone en JSON.
- Las cuentas creadas por OAuth tienen contraseña aleatoria (`Str::random(32)`) — no usable para login manual.

---

## Checklist de activación

- [ ] Proyecto creado en Google Cloud Console
- [ ] Google Drive API y People API habilitadas
- [ ] Pantalla de consentimiento con scopes correctos (`drive.file`)
- [ ] Credenciales OAuth con **ambas** URIs de callback registradas
- [ ] `GOOGLE_CLIENT_ID` y `GOOGLE_CLIENT_SECRET` en `.env`
- [ ] `GOOGLE_REDIRECT_URI` y `GOOGLE_DRIVE_REDIRECT_URI` correctos
- [ ] `php artisan config:clear` ejecutado
- [ ] Probar login con Google → verificar que `google_access_token` se guarda en BD
