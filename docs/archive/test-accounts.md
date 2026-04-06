# Cuentas de prueba — Elysium Ito

> ⚠️ Solo para uso en desarrollo local. No compartir ni subir a repositorios públicos.

## Usuario 1

| Campo | Valor |
|---|---|
| Email | `user1@test.com` |
| Contraseña | `password` |
| Rol | Usuario normal |
| Drive | ❌ No disponible (no es Gmail) |

## Usuario 2

| Campo | Valor |
|---|---|
| Email | `user2@test.com` |
| Contraseña | `password` |
| Rol | Usuario normal |
| Drive | ❌ No disponible (no es Gmail) |

## Admin

| Campo | Valor |
|---|---|
| Email | `admin@gmail.com` |
| Contraseña | `admin0909` |
| Rol | Administrador |
| Drive | ✅ Disponible (Gmail) |
| Dashboard | `/dashboard` |

---

## Notas

- `user1` y `user2` tienen `email_verified_at` seteado en el seeder — entran directo sin verificar correo.
- `admin` también tiene `email_verified_at` seteado — acceso directo al dashboard.
- La función de backup a Google Drive **solo está disponible para cuentas @gmail.com**. Las cuentas de prueba `user1` y `user2` no pueden vincular Drive.
- Para recrear estas cuentas: `php artisan db:seed --class=DatabaseSeeder` y `php artisan db:seed --class=AdminUserSeeder`.
