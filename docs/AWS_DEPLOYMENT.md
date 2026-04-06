Gemini dijo

Esta es la versión actualizada y corregida de tu guía, integrando las soluciones que aplicamos para Nginx, los permisos de www-data, la configuración de Google Auth y los ajustes específicos para tu proyecto Elysium-Ito-chat.
📋 Guía de Despliegue en AWS: Elysium-Ito-chat
✅ Pre-Requisitos en AWS

    EC2: Instancia Ubuntu 22.04 LTS (Recomendado para PHP 8.2+).

    RDS: Aurora o MySQL v8.0.

    S3: Para almacenamiento de archivos de chat (opcional).

    Google Cloud Console: Credenciales OAuth configuradas con el dominio sslip.io o definitivo.

🚀 Pasos de Despliegue Actualizados
1. Preparar la Instancia y PHP 8.2
Bash

sudo apt update && sudo apt upgrade -y

# Instalar PHP 8.2 y extensiones necesarias para Laravel/Chatify
sudo apt install -y php8.2-fpm php8.2-cli php8.2-mysql php8.2-gd php8.2-curl \
    php8.2-xml php8.2-mbstring php8.2-zip php8.2-bcmath php8.2-intl \
    nginx nodejs npm git unzip

2. Clonar y Permisos Iniciales
Bash

cd /var/www
sudo git clone <tu_repo> Elysium-Ito-chat
cd Elysium-Ito-chat

# IMPORTANTE: El dueño debe ser www-data para que Nginx no de "File not found"
sudo chown -R www-data:www-data /var/www/Elysium-Ito-chat
sudo chmod -R 755 /var/www/Elysium-Ito-chat

3. Configuración de Nginx (La "Receta" Correcta)

Edita el archivo: sudo nano /etc/nginx/sites-available/default y asegúrate de que el bloque PHP sea así para evitar errores de Google Auth:
Nginx

server {
    listen 80;
    server_name 16.59.137.153.sslip.io; # Tu IP con sslip
    root /var/www/Elysium-Ito-chat/public;

    index index.php index.html;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        
        # Necesario para Google Socialite y Proxies
        fastcgi_param HTTP_X_FORWARDED_PROTO $scheme;
        fastcgi_param HTTP_X_FORWARDED_FOR $proxy_add_x_forwarded_for;

        # Buffers para URLs largas de OAuth
        fastcgi_buffer_size 32k;
        fastcgi_buffers 16 16k;
    }
}

Activar y Reiniciar:
Bash

sudo ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx

4. Variables de Entorno (.env)
Bash

cp .env.example .env
nano .env

# Ajustes críticos:
APP_URL=http://16.59.137.153.sslip.io
DB_HOST=tu_endpoint_rds
GOOGLE_CLIENT_ID=tu_id
GOOGLE_CLIENT_SECRET=tu_secret
GOOGLE_REDIRECT_URI=http://16.59.137.153.sslip.io/auth/google/callback

5. Dependencias y Optimización
Bash

# Composer
composer install --optimize-autoloader --no-dev

# Node assets
npm install && npm run build

# Artisan
php artisan key:generate
php artisan migrate --force
php artisan storage:link # Crucial para archivos del chat

6. Ajuste Final de Permisos

Laravel necesita escribir en storage y cache. Si Nginx da errores de escritura, ejecuta:
Bash

sudo chmod -R 775 /var/www/Elysium-Ito-chat/storage
sudo chmod -R 775 /var/www/Elysium-Ito-chat/bootstrap/cache
