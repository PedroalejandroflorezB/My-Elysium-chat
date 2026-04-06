# gmail-error-account — Errores de consola al usar Google OAuth

## Descripción

Al abrir el modal de login/registro con el botón "Ingresar con Google", la consola del navegador muestra estos errores:

```
Content-Security-Policy: Ignorando "'unsafe-inline'" dentro de script-src:
se ha especificado nonce-source o hash-source.

Solicitud de origen cruzado bloqueada: La política de mismo origen no permite
la lectura de recursos remotos en https://play.google.com/log?...
(Razón: Solicitud CORS sin éxito). Código de estado: (null).
```

## Causa

Estos errores los genera el **SDK de Google** (el script de `accounts.google.com`) que se carga cuando el usuario interactúa con el flujo OAuth. No son errores del código de la aplicación.

- **CORS a `play.google.com/log`**: Google intenta enviar telemetría/analytics a sus propios servidores. El navegador bloquea esa petición por CORS porque el origen es `localhost` o un dominio que Google no tiene en su lista de permitidos para ese endpoint. Es inofensivo — no afecta el flujo de autenticación.

- **CSP `unsafe-inline` ignorado**: Si la app tiene una política CSP con `nonce` o `hash`, Chrome ignora `unsafe-inline` en `script-src` (comportamiento correcto según la spec). El SDK de Google intenta inyectar scripts inline que quedan bloqueados. Tampoco afecta el flujo OAuth basado en redirección (`Socialite::driver('google')->redirect()`).

## Estado

**No es un error de la aplicación.** El flujo OAuth de Elysium Ito usa redirección server-side (Laravel Socialite), no el SDK de Google en el cliente. Estos mensajes aparecen porque el navegador carga recursos de Google al visitar la página y Google intenta registrar eventos de analytics.

## Cuándo sí sería un problema

- Si se implementara **Google One Tap** o **Sign In With Google** (SDK client-side) — ahí sí se necesitaría configurar CSP correctamente para permitir los scripts de Google.
- Si el flujo OAuth dejara de funcionar completamente — en ese caso revisar las URIs de redirección en Google Cloud Console y las variables `GOOGLE_CLIENT_ID` / `GOOGLE_REDIRECT_URI` en `.env`.

## Referencia

- Ver `docs/google-oauth-setup.md` para la configuración completa de la app OAuth.
- Ver `.kiro/steering/laravel-best-practices.md` Nivel 17 para las reglas del flujo OAuth.
