# 🚀 LaravelChatify - Guía de Configuración Ubuntu

> ⚙️ Esta guía cubre todo lo necesario para que el proyecto funcione en **Ubuntu 22.04 LTS o superior**

---

## 📋 Requisitos Previos

- Ubuntu 22.04 LTS o superior
- Acceso a terminal/SSH
- Conexión a internet
- (Opcional) Base de datos MySQL/MariaDB remota o local

---

## ⚡ Instalación Rápida (Totalmente Automática)

### Para nuevos desarrolladores/servidores:

```bash
# 1. Clonar el repositorio
git clone <URL_DEL_REPOSITORIO> laravelchatify
cd laravelchatify

# 2. Ejecutar script de instalación (Instala PHP, Node, Nginx y configura el sitio)
chmod +x install-ubuntu.sh
./install-ubuntu.sh

# El script te pedirá tu dominio/IP y configurará Nginx automáticamente.

# 3. Configurar base de datos
nano .env
# Editar: DB_HOST, DB_USERNAME, DB_PASSWORD, DB_DATABASE

# 4. Finalizar configuración
php artisan migrate
php artisan db:seed --class=AdminUserSeeder
```

## 🚀 Despliegue de Actualizaciones (deploy.sh)

Una vez que el proyecto esté instalado, puedes usar el script de despliegue para actualizar el código, instalar dependencias y limpiar cache en un solo paso:

```bash
chmod +x deploy.sh
./deploy.sh
```

---

## 📖 Instalación Manual (Paso a Paso)

### 1️⃣ Clonar Repositorio

```bash
cd /var/www
sudo git clone <URL_DEL_REPOSITORIO> laravelchatify
cd laravelchatify
sudo chown -R $USER:$USER .
```

### 2️⃣ Instalar Dependencias del Sistema

```bash
# Actualizar paquetes
sudo apt update && sudo apt upgrade -y

# Instalar herramientas básicas
sudo apt install -y curl wget git zip unzip build-essential

# Instalar PHP 8.2
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.2 php8.2-cli php8.2-fpm

# Instalar extensiones PHP necesarias
sudo apt install -y \
    php8.2-mysql \
    php8.2-json \
    php8.2-bcmath \
    php8.2-mbstring \
    php8.2-xml \
    php8.2-curl \
    php8.2-gd \
    php8.2-intl \
    php8.2-zip

# Instalar Node.js
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Instalar MySQL client (para bases de datos remotas)
sudo apt install -y mysql-client

# Instalar Nginx
sudo apt install -y nginx
```

### 3️⃣ Instalar Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Verificar instalación
composer --version
```

### 4️⃣ Configurar Proyecto

```bash
cd /var/www/laravelchatify

# Copiar archivo de entorno
cp .env.example .env

# Editar configuración (reemplazar valores)
nano .env
```

#### Valores mínimos a configurar en `.env`:

```env
# Base de Datos (reemplazar con tus valores)
DB_HOST=127.0.0.1          # localhost si es local, o IP/domain remoto
DB_PORT=3306
DB_DATABASE=laravelchatify
DB_USERNAME=root           # tu usuario BD
DB_PASSWORD=               # tu contraseña BD

# Usuario administrador
SUPER_USER_EMAIL=admin@example.com
SUPER_USER_PASSWORD=ClaveSegura123!

# Email (para verificación y notificaciones)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_USERNAME=tu_email@gmail.com
MAIL_PASSWORD=tu_app_password
MAIL_FROM_ADDRESS=tu_email@gmail.com
```

### 5️⃣ Instalar Dependencias PHP y Node.js

```bash
# Dependencias PHP
composer install --optimize-autoloader

# Dependencias Node.js
npm install

# Compilar assets (CSS, JS)
npm run build
```

### 6️⃣ Generar Clave de Aplicación

```bash
php artisan key:generate
```

### 7️⃣ Crear Base de Datos

**Si usas MySQL local:**
```bash
sudo mysql -u root -p
```

```sql
CREATE DATABASE laravelchatify CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'laraveluser'@'localhost' IDENTIFIED BY 'password123';
GRANT ALL PRIVILEGES ON laravelchatify.* TO 'laraveluser'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 8️⃣ Ejecutar Migraciones y Seeders

```bash
php artisan migrate
php artisan db:seed --class=AdminUserSeeder
```

### 9️⃣ Configurar Permisos

```bash
sudo chown -R www-data:www-data /var/www/laravelchatify
sudo chmod -R 755 /var/www/laravelchatify
sudo chmod -R 777 /var/www/laravelchatify/storage
sudo chmod -R 777 /var/www/laravelchatify/bootstrap/cache
```

### 🔟 Configurar Nginx

#### 1. Copiar configuración:
```bash
sudo cp nginx-config.conf /etc/nginx/sites-available/laravelchatify
```

#### 2. Habilitar sitio:
```bash
sudo ln -s /etc/nginx/sites-available/laravelchatify /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
```

#### 3. Validar configuración:
```bash
sudo nginx -t
```

#### 4. Reiniciar servicios:
```bash
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm
```

---

## ✅ Verificar Instalación

```bash
# Ejecutar pruebas unitarias
cd /var/www/laravelchatify
php artisan test tests/Unit/UsuarioRegistroTest.php

# Debería mostrar:
# ✓ nombre es obligatorio
# ✓ email debe ser valido
# ✓ password debe coincidir con la confirmacion
# ✓ datos validos pasan la validacion
```

---

## 🔧 Acceso a la Aplicación

### Desarrollo (con artisan):
```bash
php artisan serve
# Acceder a: http://localhost:8000
```

### Producción (con Nginx):
```bash
# Acceder a: http://tu_ip_o_dominio
```

### Login Inicial
- **Email**: admin@example.com (o el configurado en SUPER_USER_EMAIL)
- **Contraseña**: Según configuraste en SUPER_USER_PASSWORD

---

## 📊 Comandos Útiles

```bash
# Ver versiones instaladas
php --version
mysql --version
node --version
npm --version
composer --version
nginx -v

# Verificar estado de servicios
sudo systemctl status nginx
sudo systemctl status php8.2-fpm

# Ver logs
tail -f /var/log/nginx/laravelchatify_error.log
tail -f /var/log/nginx/laravelchatify_access.log

# Limpiar caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Optimizar para producción
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 🚨 Problemas Comunes

### "Permission denied" en storage
```bash
sudo chown -R www-data:www-data /var/www/laravelchatify/storage
sudo chmod -R 777 /var/www/laravelchatify/storage
```

### PHP-FPM no inicia
```bash
# Verificar errores
php -l /var/www/laravelchatify/public/index.php

# Reiniciar
sudo systemctl restart php8.2-fpm
```

### 502 Bad Gateway (Nginx)
```bash
# Verificar socket PHP-FPM
sudo systemctl status php8.2-fpm

# Reintentar
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
```

### "SQLSTATE[HY000] [2002]" (Error de BD)
```bash
# Verificar conexión a BD
mysql -h 127.0.0.1 -u laraveluser -p laravelchatify

# Verificar credenciales en .env
nano .env
```

---

## 🔒 Configuración de Seguridad

```bash
# Deshabilitar acceso a archivos de configuración
sudo a2enmod rewrite  # Para Apache (si lo usas)

# Permisos restrictivos
sudo chmod 640 /var/www/laravelchatify/.env
sudo chown www-data:www-data /var/www/laravelchatify/.env

# Firewall
sudo ufw enable
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
```

---

## 📝 Próximos Pasos (Producción)

Para desplegar en producción en AWS, ver: **AWS_DEPLOYMENT.md**

---

## 📚 Recursos

- 📖 [Documentación Laravel 12](https://laravel.com/docs/12.x)
- 📖 [Documentación Livewire](https://livewire.laravel.com)
- 📖 [Documentación Nginx](https://nginx.org/en/docs/)
- 🐛 [Problemas frecuentes con Laravel](https://laravel.com/docs/12.x/installation)

---

**Última actualización**: 25/03/2026
