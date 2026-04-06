# 🚀 Guía de Instalación Paso a Paso - AWS (t3.micro)

Esta guía proporciona un manual exacto para desplegar **Elysium P2P** en una instancia **AWS EC2 t3.micro** con Ubuntu 22.04 LTS o 24.04 LTS.

---

## 🏗️ Fase 1: Aprovisionamiento en AWS

1.  **Lanzar Instancia**:
    *   **AMI**: Ubuntu 22.04 LTS (o superior).
    *   **Tipo**: `t3.micro` (Elegible para capa gratuita).
2.  **Security Group (Reglas de Entrada)**:
    *   `SSH` (Puerto 22): Tu IP.
    *   `HTTP` (Puerto 80): Cualquier lugar (0.0.0.0/0).
    *   `HTTPS` (Puerto 443): Cualquier lugar (0.0.0.0/0).
    *   `Custom TCP` (Puerto 8080): Cualquier lugar (Para Reverb/WebSockets).
3.  **Elastic IP**: Asigna una IP elástica a tu instancia para evitar que cambie al reiniciar.

---

## 🛠️ Fase 2: Robustecimiento del Sistema (Paso Crítico)

El `t3.micro` tiene solo 1GB de RAM. Sin **Swap**, el servidor se caerá al ejecutar `npm build` o al recibir múltiples conexiones.

```bash
# Crear un archivo swap de 2GB
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile

# Hacerlo permanente
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab

# Optimizar sistema
sudo apt update && sudo apt upgrade -y
```

---

## 📦 Fase 3: Stack de Software (PHP 8.3 + Nginx)

Instalaremos PHP 8.3 con las extensiones necesarias. Para capa gratuita usaremos SQLite, pero incluimos MySQL para futuras migraciones.

```bash
# Agregar repositorio de PHP
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Instalación básica (GRATUITA)
sudo apt install -y nginx php8.3-fpm php8.3-cli php8.3-sqlite3 \
    php8.3-curl php8.3-mbstring php8.3-xml php8.3-zip php8.3-bcmath \
    php8.3-pcntl php8.3-intl php8.3-gd git unzip

# Opcional: MySQL client (para migración futura a Aurora)
# sudo apt install -y php8.3-mysql mysql-client-8.0

# Instalar Composer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm composer-setup.php

# Instalar Node.js (v20+)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

---

## 🚀 Fase 4A: Configuración GRATUITA (SQLite) - Para Demo/Portfolio

### 4A.1 Base de Datos SQLite (Capa Gratuita)

```bash
# Para proyectos de demostración usando capa gratuita AWS
cd /var/www/elysium-p2p

# Crear base de datos SQLite
touch database/database.sqlite
chmod 664 database/database.sqlite
sudo chown www-data:www-data database/database.sqlite

# Configurar permisos del directorio
sudo chown -R www-data:www-data database/
```

### 4A.2 Variables de Entorno para Capa Gratuita

```env
# ==========================================
# DATABASE (SQLite - GRATIS)
# ==========================================
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/elysium-p2p/database/database.sqlite

# ==========================================
# CACHE & SESSION (File driver - GRATIS)
# ==========================================
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

---

## 🚀 Fase 4B: Configuración PRODUCCIÓN (Aurora MySQL) - Recomendado

> **💡 NOTA**: Esta sección es para cuando tengas presupuesto para producción real. 
> Para demo/portfolio, usa la Fase 4A (SQLite gratuita).

### 4B.1 Crear Cluster Aurora en AWS Console

1. **Ir a RDS Console** → Create Database
2. **Engine**: Aurora (MySQL Compatible)
3. **Version**: MySQL 8.0 compatible
4. **Templates**: Production (o Dev/Test para ahorrar costos)
5. **DB Cluster Identifier**: `elysium-p2p-cluster`
6. **Master Username**: `elysium_admin`
7. **Master Password**: [Genera una contraseña segura]
8. **DB Instance Class**: `db.t3.small` (mínimo recomendado)
9. **VPC Security Group**: Crear nuevo o usar existente
10. **Initial Database Name**: `elysium_p2p`

### 4B.2 Configurar Security Group para Aurora

```bash
# En AWS Console → EC2 → Security Groups
# Crear regla de entrada para el Security Group de Aurora:
Type: MySQL/Aurora (3306)
Source: Security Group de tu instancia EC2
```

### 4B.3 Variables de Entorno para Producción

```env
# ==========================================
# DATABASE (Aurora MySQL - PRODUCCIÓN)
# ==========================================
DB_CONNECTION=mysql
DB_HOST=elysium-p2p-cluster.cluster-xxxxx.us-east-1.rds.amazonaws.com
DB_PORT=3306
DB_DATABASE=elysium_p2p
DB_USERNAME=elysium_admin
DB_PASSWORD=TU_PASSWORD_SEGURA_AQUI

# ==========================================
# CACHE & SESSION (Database driver - PRODUCCIÓN)
# ==========================================
CACHE_DRIVER=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database
```

---

## 🚀 Fase 5: Despliegue de la Aplicación

```bash
# Clonar y configurar permisos
cd /var/www
sudo git clone https://github.com/TuUsuario/elysium-p2p.git
sudo chown -R $USER:$USER /var/www/elysium-p2p
cd elysium-p2p

# Instalación de dependencias
composer install --no-dev --optimize-autoloader
npm install
npm run build

# Configuración de entorno (ver sección 9.1 para .env completo)
cp .env.example .env
php artisan key:generate

# OPCIÓN A: SQLite (GRATUITA - Para demo/portfolio)
touch database/database.sqlite
chmod 664 database/database.sqlite
sudo chown www-data:www-data database/database.sqlite
php artisan migrate --force
php artisan db:seed --force

# OPCIÓN B: Aurora (PRODUCCIÓN - Solo si tienes presupuesto)
# Configurar .env con datos de Aurora primero, luego:
# php artisan migrate --force
# php artisan db:seed --force

# Permisos de servidor web
sudo chown -R www-data:www-data storage bootstrap/cache database/
```

---

## 🌐 Fase 6: Configuración de Nginx & WebSockets

### 1. Configurar el Site de Nginx
Crea el archivo `/etc/nginx/sites-available/elysium`:

```nginx
server {
    listen 80;
    server_name tu-dominio.com;
    root /var/www/elysium-p2p/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PROXY PARA REVERB (WebSockets)
    location /app {
        proxy_http_version 1.1;
        proxy_set_header Host $http_host;
        proxy_set_header Scheme $scheme;
        proxy_set_header SERVER_PORT $server_port;
        proxy_set_header REMOTE_ADDR $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";

        proxy_pass http://127.0.0.1:8080;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
# Habilitar sitio
sudo ln -s /etc/nginx/sites-available/elysium /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

---

## ⚡ Fase 7: Servicio de Señalización (Reverb)

Para que el chat y el P2P funcionen 24/7, Reverb debe correr como un servicio de sistema.

Crea el archivo `/etc/systemd/system/reverb.service`:

```ini
[Unit]
Description=Laravel Reverb WebSocket Server
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/elysium-p2p
ExecStart=/usr/bin/php artisan reverb:start --host=0.0.0.0 --port=8080
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable reverb
sudo systemctl start reverb
```

---

## 🔒 Fase 8: SSL (Certbot) - Obligatorio

WebRTC y el Service Worker **no funcionan** sin HTTPS (excepto en localhost).

```bash
sudo apt install certbot python3-certbot-nginx -y
sudo certbot --nginx -d tu-dominio.com

# Actualiza tu .env con la nueva URL https
# APP_URL=https://tu-dominio.com
```

---

## 🔧 Fase 9: Verificación de API y Configuración

### 9.1 Variables de Entorno (.env) - Configuración GRATUITA vs PRODUCCIÓN

#### 🆓 Configuración GRATUITA (Para Demo/Portfolio)

```env
# ==========================================
# APPLICATION
# ==========================================
APP_NAME="Elysium P2P"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com
APP_KEY=base64:TU_CLAVE_GENERADA_AQUI

# ==========================================
# DATABASE (SQLite - COMPLETAMENTE GRATIS)
# ==========================================
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/elysium-p2p/database/database.sqlite

# ==========================================
# CACHE & SESSION (File driver - GRATIS)
# ==========================================
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

# ==========================================
# BROADCASTING & REVERB (Optimizado para t3.micro)
# ==========================================
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=elysium
REVERB_APP_KEY=elysium-app-key
REVERB_APP_SECRET=elysium-app-secret
REVERB_HOST="127.0.0.1"
REVERB_PORT=8080
REVERB_SCHEME=http
REVERB_APP_CLUSTER=mt1

# Límite de conexiones Reverb para 1GB RAM
REVERB_APP_MAX_CONNECTIONS=50
REVERB_APP_MAX_MESSAGE_SIZE=1048576

# ==========================================
# LOGGING (Local files - GRATIS)
# ==========================================
LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

# ==========================================
# MAIL (Log driver - GRATIS para testing)
# ==========================================
MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@tu-dominio.com"
MAIL_FROM_NAME="${APP_NAME}"

# Vite Frontend
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

#### 💰 Configuración PRODUCCIÓN (Aurora MySQL - Costo ~$40/mes)

```env
# ==========================================
# APPLICATION
# ==========================================
APP_NAME="Elysium P2P"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com
APP_KEY=base64:TU_CLAVE_GENERADA_AQUI

# ==========================================
# DATABASE (Aurora MySQL - PRODUCCIÓN)
# ==========================================
DB_CONNECTION=mysql
DB_HOST=elysium-p2p-cluster.cluster-xxxxx.us-east-1.rds.amazonaws.com
DB_PORT=3306
DB_DATABASE=elysium_p2p
DB_USERNAME=elysium_admin
DB_PASSWORD=TU_PASSWORD_SEGURA_AQUI

# Configuraciones MySQL optimizadas para Aurora
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci

# ==========================================
# CACHE & SESSION (Database driver para Aurora)
# ==========================================
CACHE_DRIVER=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database

# ==========================================
# BROADCASTING & REVERB (Optimizado para t3.micro)
# ==========================================
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=elysium
REVERB_APP_KEY=elysium-app-key
REVERB_APP_SECRET=elysium-app-secret
REVERB_HOST="127.0.0.1"
REVERB_PORT=8080
REVERB_SCHEME=http
REVERB_APP_CLUSTER=mt1

# Límite de conexiones Reverb para 1GB RAM
REVERB_APP_MAX_CONNECTIONS=50
REVERB_APP_MAX_MESSAGE_SIZE=1048576

# ==========================================
# LOGGING (CloudWatch compatible)
# ==========================================
LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

# ==========================================
# MAIL (SES recomendado para AWS)
# ==========================================
MAIL_MAILER=ses
MAIL_HOST=email-smtp.us-east-1.amazonaws.com
MAIL_PORT=587
MAIL_USERNAME=TU_SES_ACCESS_KEY
MAIL_PASSWORD=TU_SES_SECRET_KEY
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@tu-dominio.com"
MAIL_FROM_NAME="${APP_NAME}"

# Vite Frontend
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### 9.2 Configuración según Base de Datos Elegida

#### 🆓 Para SQLite (Configuración Gratuita)

```bash
# No se necesitan tablas adicionales para cache/session
# SQLite ya está configurado y listo

# Verificar que SQLite funciona
php artisan tinker
# En tinker:
DB::connection()->getPdo();
\App\Models\User::count();
exit
```

#### 💰 Para Aurora (Configuración Producción)

```bash
# Crear tablas necesarias para cache y sessions en Aurora
php artisan cache:table
php artisan session:table
php artisan queue:table
php artisan migrate --force

# Verificar conexión a Aurora
php artisan tinker
# En tinker:
DB::connection()->getPdo();
DB::select('SELECT VERSION()');
exit
```

### 9.3 Verificación según Configuración

#### 🆓 Verificación SQLite (Gratuita)

```bash
# Probar API básica
curl -X GET https://tu-dominio.com/api/test

# Verificar WebSocket
curl -I -H "Connection: Upgrade" -H "Upgrade: websocket" https://tu-dominio.com/app

# Verificar Reverb
sudo systemctl status reverb

# Verificar SQLite
ls -la database/database.sqlite
php artisan migrate:status
```

#### 💰 Verificación Aurora (Producción)

```bash
# Probar API básica
curl -X GET https://tu-dominio.com/api/test

# Verificar WebSocket
curl -I -H "Connection: Upgrade" -H "Upgrade: websocket" https://tu-dominio.com/app

# Verificar Reverb
sudo systemctl status reverb

# Verificar Aurora
mysql -h tu-aurora-endpoint.cluster-xxxxx.us-east-1.rds.amazonaws.com \
      -u elysium_admin -p elysium_p2p \
      -e "SHOW TABLES;"
```

### 9.4 Verificación de Rutas API

Verifica que todas las rutas API estén funcionando:

```bash
# Probar API básica
curl -X GET https://tu-dominio.com/api/test

# Verificar WebSocket (debe devolver 101 Switching Protocols)
curl -I -H "Connection: Upgrade" -H "Upgrade: websocket" https://tu-dominio.com/app

# Verificar que Reverb esté corriendo
sudo systemctl status reverb

# Verificar base de datos (SQLite o Aurora)
php artisan migrate:status
```

### 9.5 Rutas API Disponibles

| Método | Ruta | Descripción | Autenticación |
|--------|------|-------------|---------------|
| `GET` | `/api/test` | Prueba básica de API | No |
| `POST` | `/api/messages/send` | Enviar mensaje | Sí |
| `GET` | `/api/messages/{userId}` | Obtener mensajes | Sí |
| `POST` | `/api/contacts/search` | Buscar usuarios | Sí |
| `POST` | `/api/contacts/request` | Enviar solicitud de contacto | Sí |
| `POST` | `/api/contacts/respond` | Responder solicitud | Sí |
| `GET` | `/api/contacts-list` | Lista de contactos | Sí |
| `POST` | `/api/p2p/signal` | Señalización P2P | Sí |
| `POST` | `/api/chat/delete` | Eliminar conversación | Sí |

### 9.6 Configuración de CORS (si es necesario)

Si planeas usar la API desde otros dominios, configura CORS en `config/cors.php`:

```php
'paths' => ['api/*', 'sanctum/csrf-cookie', 'app/*'],
'allowed_methods' => ['*'],
'allowed_origins' => ['https://tu-dominio.com'],
'allowed_origins_patterns' => [],
'allowed_headers' => ['*'],
'exposed_headers' => [],
'max_age' => 0,
'supports_credentials' => true,
```

---

## 🔧 Optimizaciones Específicas

### Para SQLite + t3.micro (Configuración GRATUITA)

```ini
; /etc/php/8.3/fpm/pool.d/www.conf
pm = dynamic
pm.max_children = 3              ; Conservador para SQLite: 3 × 64MB = ~192MB
pm.start_servers = 1
pm.min_spare_servers = 1
pm.max_spare_servers = 2
pm.max_requests = 300            ; Reciclar procesos frecuentemente
php_admin_value[memory_limit] = 64M
catch_workers_output = yes
php_admin_value[error_log] = /var/log/php-fpm/www-error.log
php_flag[log_errors] = on

; Optimizaciones SQLite
php_admin_value[sqlite3.extension_dir] = /usr/lib/php/20230831/
```

### Para Aurora + t3.micro (Configuración PRODUCCIÓN)

```ini
; /etc/php/8.3/fpm/pool.d/www.conf
pm = dynamic
pm.max_children = 5              ; Aumentado para Aurora: 5 × 64MB = ~320MB
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
pm.max_requests = 500            ; Más requests por proceso con Aurora
php_admin_value[memory_limit] = 64M
catch_workers_output = yes
php_admin_value[error_log] = /var/log/php-fpm/www-error.log
php_flag[log_errors] = on

; Optimizaciones MySQL para Aurora
php_admin_value[mysql.default_socket] = /var/run/mysqld/mysqld.sock
php_admin_value[mysqli.default_socket] = /var/run/mysqld/mysqld.sock
```

### Monitoreo Básico (Ambas Configuraciones)

```bash
# Script de monitoreo básico (crear en /home/ubuntu/monitor.sh)
#!/bin/bash
echo "=== $(date) ==="
echo "RAM Usage:"
free -h
echo "PHP-FPM Processes:"
ps aux | grep php-fpm | grep -v grep | wc -l
echo "Reverb Status:"
sudo systemctl is-active reverb
echo "Disk Usage:"
df -h /var/www/elysium-p2p
echo "==================="

# Hacer ejecutable
chmod +x /home/ubuntu/monitor.sh

# Agregar a crontab para ejecutar cada 10 minutos
echo "*/10 * * * * /home/ubuntu/monitor.sh >> /var/log/elysium-monitor.log" | crontab -
```

---

## 🏁 Verificación Final

1.  **Acceso Web**: Entra a `https://tu-dominio.com`.
2.  **WebSocket**: Abre la consola del navegador y verifica que **Echo** se conecte sin errores.
3.  **API**: Prueba `https://tu-dominio.com/api/test` - debe devolver JSON.
4.  **Base de Datos**: Verifica conexión: `php artisan migrate:status`
5.  **Chat**: Prueba el chat y la transferencia P2P.
6.  **Reverb**: Verifica que el servicio esté activo: `sudo systemctl status reverb`

### Comandos de Diagnóstico

#### 🆓 Para SQLite (Configuración Gratuita)

```bash
# Verificar logs de Reverb
sudo journalctl -u reverb -f

# Verificar logs de Nginx
sudo tail -f /var/log/nginx/error.log

# Verificar logs de Laravel
tail -f /var/www/elysium-p2p/storage/logs/laravel.log

# Verificar SQLite
ls -la database/database.sqlite
sqlite3 database/database.sqlite ".tables"

# Verificar uso de memoria
free -h

# Verificar procesos PHP
ps aux | grep php-fpm

# Test de la aplicación
php artisan tinker
# En tinker:
\App\Models\User::count();
exit
```

#### 💰 Para Aurora (Configuración Producción)

```bash
# Verificar logs de Reverb
sudo journalctl -u reverb -f

# Verificar logs de Nginx
sudo tail -f /var/log/nginx/error.log

# Verificar logs de Laravel
tail -f /var/www/elysium-p2p/storage/logs/laravel.log

# Verificar conexiones Aurora
mysql -h tu-aurora-endpoint -u elysium_admin -p \
      -e "SHOW PROCESSLIST; SHOW STATUS LIKE 'Threads_connected';"

# Verificar uso de memoria
free -h

# Verificar procesos PHP
ps aux | grep php-fpm

# Test completo de la aplicación
php artisan tinker
# En tinker:
\App\Models\User::count();
DB::table('cache')->count();
exit
```

### Costos Reales AWS (us-east-1)

#### 🆓 Configuración GRATUITA (Para Demo/Portfolio)

| Recurso | Configuración | Costo/mes |
|---------|--------------|-----------|
| **EC2 t3.micro** | 1 vCPU, 1GB RAM (750h gratis) | **$0** |
| **EBS Storage** | 8GB gp3 (30GB gratis) | **$0** |
| **Elastic IP** | 1 IP estática (gratis si está en uso) | **$0** |
| **SQLite** | Base de datos local | **$0** |
| **Certificado SSL** | Let's Encrypt | **$0** |
| **Total** | | **$0/mes** ✅ |

#### 💰 Configuración PRODUCCIÓN (Recomendada para tráfico real)

| Recurso | Configuración | Costo/mes |
|---------|--------------|-----------|
| **EC2 t3.micro** | 1 vCPU, 1GB RAM | ~$7.50 |
| **Aurora MySQL** | db.t3.small (2 vCPU, 2GB) | ~$25-35 |
| **EBS Storage** | 8GB gp3 | ~$1 |
| **Aurora Storage** | ~1GB + I/O | ~$2-5 |
| **Elastic IP** | 1 IP estática | ~$3.65 |
| **Total** | | **~$39-52/mes** |

### Cuándo Migrar de SQLite a Aurora

| Métrica | SQLite (Gratis) | Momento de Migrar |
|---------|-----------------|-------------------|
| **Usuarios concurrentes** | < 10 | > 20 usuarios simultáneos |
| **Mensajes por día** | < 1,000 | > 5,000 mensajes/día |
| **Transferencias P2P** | < 5 simultáneas | > 10 transferencias simultáneas |
| **Tamaño de DB** | < 100 MB | > 500 MB |
| **Uptime requerido** | 95% | > 99% (SLA crítico) |

**💡 Recomendación**: Usa SQLite para mostrar tu proyecto y migra a Aurora cuando tengas tráfico real o necesites alta disponibilidad.
