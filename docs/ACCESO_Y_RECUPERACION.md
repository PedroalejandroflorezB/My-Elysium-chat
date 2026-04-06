# Acceso y Recuperación de Administrador — Elysium Ito

> ⚠️ DOCUMENTO CONFIDENCIAL — No subir a repositorios públicos.

---

## Cuentas actuales en base de datos

| ID | Nombre | Email | Rol | Estado |
|----|--------|-------|-----|--------|
| 1 | Usuario Uno | user1@test.com | Usuario | Activo |
| 2 | Usuario Dos | user2@test.com | Usuario | Activo |
| 3 | Admin | admin@gmail.com | Admin | Activo |
| 5 | Jack Bossman | arkconnetchatmedia01@gmail.com | Usuario | Activo |

---

## Credenciales del sistema

### Correo de sistema (envío de emails)
- **Cuenta:** ArkConnetChatMedia01@gmail.com
- **Contraseña app Gmail:** `ckwo gsnm dsfz mont`
- **Uso:** envío de emails de contacto, verificación, recuperación

### Super Usuario (.env)
- **Email:** ArkConnetChatMedia01@gmail.com
- **Contraseña:** `cambiar_esta_clave_segura`
- ⚠️ Cambiar esta contraseña antes de producción

### Admin genérico (DB)
- **Email:** admin@gmail.com
- **Contraseña:** la que se asignó al crear la cuenta

---

## Métodos de recuperación de acceso

### Método 1 — Comando Artisan (acceso al servidor)
El más directo. Requiere acceso SSH o terminal al servidor.

```bash
# Promover cualquier usuario existente a admin
php artisan make:admin tu@correo.com

# Crear o reemplazar el Super Usuario completo
php artisan super:setup --email=nuevo@correo.com --password=NuevaClave123!

# Recuperación interactiva (menú guiado)
php artisan admin:recover
```

### Método 2 — Token de emergencia (sin servidor)
Se genera un token de un solo uso. Si pierdes acceso al servidor,
puedes recuperar el admin desde el navegador.

```bash
# Generar token de recuperación
php artisan admin:recovery-token
```

Luego visita:
```
https://tu-dominio.com/admin-recover/{token}
```

El token expira en 24 horas y solo funciona una vez.

### Método 3 — Correo de respaldo (.env)
Define `ADMIN_RECOVERY_EMAIL` en el `.env` con un correo personal
de confianza (diferente al correo del admin).

```env
ADMIN_RECOVERY_EMAIL=tu_correo_personal@gmail.com
```

Si el admin pierde acceso, desde `/admin-recover` puede solicitar
un link de recuperación a ese correo de respaldo.

### Método 4 — Promover cuenta Google existente
Si tienes una cuenta Google vinculada al sistema (como Jack Bossman),
puedes promoverla a admin desde terminal:

```bash
php artisan make:admin arkconnetchatmedia01@gmail.com
```

---

## Protecciones del admin genérico

- El Super Usuario (`is_super = true`) **no puede ser eliminado** desde el dashboard
- El Super Usuario **no puede ser desactivado** desde el dashboard
- Solo se puede modificar desde terminal con `php artisan super:setup`

---

## Cambiar correo del admin sin perder acceso

1. Primero asegúrate de tener otro método de recuperación activo (token o correo de respaldo)
2. Ejecuta:
```bash
php artisan super:setup --email=nuevo_correo@dominio.com --password=TuClaveActual
```
3. Verifica que puedes entrar con el nuevo correo
4. Actualiza `SUPER_USER_EMAIL` en el `.env`

---

## Checklist antes de producción

- [ ] Cambiar `SUPER_USER_PASSWORD` en `.env` por una contraseña segura
- [ ] Definir `ADMIN_RECOVERY_EMAIL` con un correo personal de confianza
- [ ] Ejecutar `php artisan admin:recovery-token` y guardar el token en lugar seguro
- [ ] Cambiar la contraseña del admin genérico (admin@gmail.com) por una real
- [ ] Activar 2FA en la cuenta admin desde Ajustes de seguridad
