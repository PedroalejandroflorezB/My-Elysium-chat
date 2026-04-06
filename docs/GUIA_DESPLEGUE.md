# Guía de Despliegue en Producción - Elysium Ito

Esta guía detalla los pasos necesarios para instalar, configurar y mantener el proyecto **Elysium Ito** en un servidor Ubuntu con IP pública.

## Requisitos Previos
- Servidor Ubuntu (20.04 o 22.04 recomendado)
- Acceso SSH con usuario `ubuntu` (o similar con sudo)
- Dominio o IP Pública estática configurada
- Base de datos MySQL/MariaDB accesible (RDS o local)

---

## 1. Preparación del Entorno

Actualiza el sistema e instala las dependencias necesarias (PHP, Nginx, Node.js, Composer).

```bash
# Actualizar paquetes del sistema
sudo apt update && sudo apt upgrade -y

# Instalar PHP y extensiones requeridas por Laravel
sudo apt install -y php-fpm php-mysql php-curl php-gd php-mbstring php-xml php-zip unzip curl git nginx

# Instalar Composer (Gestor de dependencias PHP)
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Instalar Node.js (Versión LTS reciente)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs
```

---

## 2. Instalación del Proyecto

Navega al directorio del proyecto, ajusta permisos temporales para instalar dependencias y compila los assets.

```bash
cd /var/www/Elysiumchat

# Dar permisos temporales al usuario actual para instalar paquetes
sudo chown -R $USER:$USER .

# Instalar dependencias de PHP (optimizadas para producción)
composer install --optimize-autoloader --no-dev

# Instalar dependencias de Node.js
npm install

# Compilar assets frontend (CSS/JS) para producción
npm run build
```

---

## 3. Configuración de Base de Datos y Entorno

Asegúrate de que tu archivo `.env` tenga los datos correctos de la base de datos y ejecuta las migraciones.

```bash
# Limpiar caché de configuración anterior
php artisan config:clear

# Ejecutar migraciones de base de datos (force para producción)
php artisan migrate --force

# Optimizar Laravel para producción (Caché de config, rutas y vistas)
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

> **Nota:** Verifica que en tu `.env` `APP_DEBUG=false` y `APP_ENV=production`.

### 3.1 Configuración de Google Drive y OAuth

Para que funcionen el login con Google y los backups automáticos, debes configurar las siguientes variables en tu archivo `.env`:

```env
GOOGLE_CLIENT_ID=tu_client_id_de_google_cloud
GOOGLE_CLIENT_SECRET=tu_client_secret_de_google_cloud
GOOGLE_REDIRECT_URI=http://16.59.137.153/auth/google/callback
GOOGLE_DRIVE_BACKUP_FOLDER_ID=id_de_la_carpeta_en_drive
GOOGLE_SCOPES=drive.file,drive.appdata,openid,email,profile
```

**Pasos para obtener estas credenciales:**
1. Ve a [Google Cloud Console](https://console.cloud.google.com/).
2. Crea un proyecto o selecciona el existente.
3. Habilita la **Google Drive API** y la **Google+ API**.
4. Ve a **APIs y servicios > Credenciales** y crea un **ID de cliente de OAuth 2.0**.
   - Tipo de aplicación: Aplicación web.
   - URI de redireccionamiento autorizado: `http://16.59.137.153/auth/google/callback`
5. Copia el **Client ID** y **Client Secret** generados.
6. Crea una carpeta en tu Google Drive y copia su ID (lo que está después de `/folders/` en la URL) para `GOOGLE_DRIVE_BACKUP_FOLDER_ID`.

---

## 4. Permisos Finales de Seguridad

Es crucial que los archivos pertenezcan al usuario del servidor web (`www-data`) para evitar errores de escritura y vulnerabilidades.

```bash
# Cambiar propietario al usuario de Nginx/PHP-FPM
sudo chown -R www-data:www-data /var/www/Elysiumchat

# Dar permisos de escritura solo a carpetas necesarias
sudo chmod -R 775 storage bootstrap/cache
```

---

## 5. Configuración de Nginx

Configura el servidor web para atender la aplicación Laravel.

1. Crea el archivo de configuración:
   ```bash
   sudo nano /etc/nginx/sites-available/elysium
   ```

2. Pega el siguiente contenido (ajusta `server_name` si usas dominio):
   ```nginx
   server {
       listen 80;
       server_name 16.59.137.153; # Cambia esto por tu dominio o IP
       root /var/www/Elysiumchat/public;

       add_header X-Frame-Options "SAMEORIGIN";
       add_header X-Content-Type-Options "nosniff";

       index index.php;
       charset utf-8;

       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }

       location = /favicon.ico { access_log off; log_not_found off; }
       location = /robots.txt  { access_log off; log_not_found off; }

       error_page 404 /index.php;

       location ~ \.php$ {
           fastcgi_pass unix:/var/run/php/php8.1-fpm.sock; # Verifica tu versión de PHP
           fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
           include fastcgi_params;
       }

       location ~ /\.(?!well-known).* {
           deny all;
       }
   }
   ```

3. Activa el sitio y reinicia Nginx:
   ```bash
   # Crear enlace simbólico para activar el sitio
   sudo ln -s /etc/nginx/sites-available/elysium /etc/nginx/sites-enabled/
   
   # Eliminar el sitio por defecto (opcional pero recomendado)
   sudo rm /etc/nginx/sites-enabled/default
   
   # Probar configuración y reiniciar
   sudo nginx -t
   sudo systemctl restart nginx
   ```

---

## 6. Seguridad para IP Pública (Firewall)

Si tu servidor tiene una IP pública, protege los puertos esenciales.

```bash
# Instalar UFW si no existe
sudo apt install ufw

# Permitir tráfico web y SSH
sudo ufw allow 'Nginx Full'
sudo ufw allow 'OpenSSH'

# Habilitar firewall
sudo ufw enable
```

---

## 7. Rutina de Actualización Futura

Cada vez que despliegues nuevos cambios desde tu repositorio:

```bash
cd /var/www/Elysiumchat

# 1. Temporalmente recuperar permisos para tu usuario
sudo chown -R $USER:$USER .

# 2. Obtener cambios
git pull origin main # o la rama que uses

# 3. Reinstalar dependencias si hubo cambios en composer.json o package.json
composer install --optimize-autoloader --no-dev
npm install
npm run build

# 4. Migrar base de datos si hay nuevos cambios
php artisan migrate --force

# 5. Volver a optimizar
php artisan optimize

# 6. RESTAURAR PERMISOS DE SEGURIDAD (Muy Importante)
sudo chown -R www-data:www-data /var/www/Elysiumchat
sudo chmod -R 775 storage bootstrap/cache
```

---

## Solución de Problemas Comunes

- **Error 403 Forbidden:** Revisa los permisos de la carpeta `storage` y `bootstrap/cache`. Deben ser propiedad de `www-data`.
- **Error 500 Internal Server Error:** Revisa los logs en `storage/logs/laravel.log` o `/var/log/nginx/error.log`. Asegúrate de que el `.env` esté correcto.
- **Error de conexión a BD:** Verifica que el Security Group de AWS (si usas RDS) permita el tráfico desde la IP de tu servidor EC2.
