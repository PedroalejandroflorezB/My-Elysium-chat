# 🚀 Configuración de Despliegue - AWS RDS db.t4g.micro

## ✅ Archivo `.env` Listo para Producción

Se ha creado el archivo `.env` optimizado para tu instancia **AWS RDS db.t4g.micro** (2 vCPU, 1GB RAM, MySQL puerto 3306).

---

## 🔧 VALORES QUE DEBES ACTUALIZAR

### 1. Base de Datos AWS RDS (OBLIGATORIO)
```bash
DB_HOST=tu-endpoint-rds.xxxxxx.us-east-1.rds.amazonaws.com
DB_DATABASE=elysium_ito
DB_USERNAME=admin
DB_PASSWORD=TuPasswordSeguro123!
```

### 2. Dominio/IP Pública (OBLIGATORIO)
```bash
APP_URL=http://TU_IP_PUBLICA_o_DOMINIO
SESSION_DOMAIN=.tu_dominio.com
GOOGLE_REDIRECT_URI=http://TU_IP_PUBLICA/auth/google/login-callback
GOOGLE_DRIVE_REDIRECT_URI=http://TU_IP_PUBLICA/auth/google/callback
```

### 3. Google OAuth (OBLIGATORIO)
```bash
GOOGLE_CLIENT_ID=tu_client_id_de_google_cloud
GOOGLE_CLIENT_SECRET=tu_client_secret_de_google_cloud
```

### 4. Correo Gmail (OPCIONAL pero RECOMENDADO)
```bash
MAIL_USERNAME=tu_correo@gmail.com
MAIL_PASSWORD=tu_app_password_de_gmail
SUPER_USER_EMAIL=tu_correo@gmail.com
SUPER_USER_PASSWORD=ClaveSegura123!
```

### 5. Generar APP_KEY (OBLIGATORIO)
Después de subir el proyecto, ejecuta:
```bash
php artisan key:generate
```

---

## 📋 CHECKLIST PRE-DESPLIEGUE

### En tu Instancia EC2/Ubuntu:

```bash
# 1. Verificar PHP y extensiones
php -v
php -m | grep -E "mysql|pdo|mbstring|xml|curl|gd"

# 2. Instalar dependencias de Composer
composer install --optimize-autoloader --no-dev

# 3. Instalar dependencias de NPM y compilar assets
npm install
npm run build

# 4. Copiar archivo .env (ya está creado)
# El archivo .env ya existe en /workspace/.env

# 5. Generar APP_KEY
php artisan key:generate

# 6. Optimizar configuración
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Ejecutar migraciones
php artisan migrate --force

# 8. Crear usuario administrador
php artisan db:seed --class=SuperUserSeeder

# 9. Configurar permisos
sudo chown -R www-data:www-data /workspace/storage
sudo chmod -R 775 /workspace/storage
sudo chown -R www-data:www-data /workspace/bootstrap/cache
sudo chmod -R 775 /workspace/bootstrap/cache

# 10. Configurar cron para colas
(crontab -l 2>/dev/null; echo "* * * * * cd /workspace && php artisan schedule:run >> /dev/null 2>&1") | crontab -
```

---

## ⚙️ CONFIGURACIÓN NGINX

El archivo `nginx-config.conf` ya está optimizado para db.t4g.micro:
- Buffers reducidos (4k)
- Timeouts extendidos (120s)
- GZIP activado
- Worker connections: 1024

### Comandos para Nginx:
```bash
# Copiar configuración
sudo cp nginx-config.conf /etc/nginx/sites-available/elysium
sudo ln -s /etc/nginx/sites-available/elysium /etc/nginx/sites-enabled/

# Probar configuración
sudo nginx -t

# Reiniciar Nginx
sudo systemctl restart nginx
```

---

## ⚙️ CONFIGURACIÓN PHP-FPM OPTIMIZADA

Para db.t4g.micro (1GB RAM), configura `/etc/php/8.x/fpm/pool.d/www.conf`:

```ini
pm = dynamic
pm.max_children = 10
pm.start_servers = 2
pm.min_spare_servers = 2
pm.max_spare_servers = 5
pm.max_requests = 500
request_terminate_timeout = 120s
```

### Comandos:
```bash
sudo systemctl restart php8.3-fpm  # O tu versión de PHP
```

---

## 🔍 VERIFICACIÓN POST-DESPLIEGUE

```bash
# Verificar conexión a BD
php artisan tinker
>>> DB::connection()->getPdo();

# Verificar migraciones
php artisan migrate:status

# Verificar colas
php artisan queue:table
php artisan migrate --force

# Verificar logs
tail -f storage/logs/laravel.log

# Probar endpoint
curl http://TU_IP_PUBLICA/api/health
```

---

## 📊 MONITOREO RECOMENDADO (AWS CloudWatch)

Configura alertas para:
- **CPU > 80%** por 5 minutos
- **Memoria Libre < 100MB**
- **Conexiones Activas > 40** (máximo ~50 en t4g.micro)
- **Espacio Disco > 15GB** (de 20GB totales)

---

## 🛠️ COMANDOS DE MANTENIMIENTO

```bash
# Limpiar caché
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Regenerar caché (producción)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimizar autoload
composer dump-autoload --optimize

# Reiniciar servicios
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx
```

---

## ⚠️ IMPORTANTE: Limitaciones db.t4g.micro

| Recurso | Límite | Recomendación |
|---------|--------|---------------|
| RAM | 1GB | CACHE_STORE=file, max_children=10 |
| vCPU | 2 | PHP_CLI_SERVER_WORKERS=4 |
| Conexiones BD | ~50 | Sin conexiones persistentes |
| Almacenamiento | 20GB | Monitorear, escalado automático habilitado |

---

## 📞 SOPORTE

Si tienes problemas:
1. Revisa `storage/logs/laravel.log`
2. Verifica `sudo tail -f /var/log/nginx/error.log`
3. Comprueba conexión RDS: `mysql -h TU_ENDPOINT -u TU_USUARIO -p`

---

**✅ Proyecto listo para desplegar en AWS con RDS db.t4g.micro**
