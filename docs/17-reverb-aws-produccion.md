# Reverb + AWS t3.micro - Guía de Producción
**Basado en documentación oficial de Laravel y AWS · Abril 2026**

---

## AWS t3.micro - Capa Gratuita

### ¿Qué incluye?
- **750 horas/mes** de instancia t3.micro durante **12 meses** desde la creación de la cuenta
- 2 vCPUs (burstable), 1 GB RAM
- Hasta 5 Gbps de ancho de banda
- Precio después del free tier: ~$7.59/mes

### Límites importantes para Elysium
| Recurso | Límite | Configurado en |
|---------|--------|----------------|
| Conexiones Reverb simultáneas | 50 | `REVERB_MAX_CONNECTIONS=50` |
| Payload WebSocket | 1 MB | `REVERB_MAX_PAYLOAD_SIZE=1048576` |
| RAM disponible para PHP | ~700 MB | (200MB para OS) |
| CPU burst | 6 créditos/hora | Monitorear en CloudWatch |

---

## Configuración de Reverb para Producción

### Variables de entorno (.env)

```env
# Servidor (donde escucha Reverb internamente)
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080

# Cliente (donde conecta el JS del navegador)
REVERB_HOST=tu-dominio.com      # Sin https://
REVERB_PORT=443                  # Puerto HTTPS estándar
REVERB_SCHEME=https

# Credenciales (generar valores aleatorios)
REVERB_APP_ID=elysium_prod
REVERB_APP_KEY=clave_aleatoria_32chars
REVERB_APP_SECRET=secreto_aleatorio_32chars

# Vite (para el frontend)
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

> **Nota:** `REVERB_SERVER_HOST` ≠ `REVERB_HOST`.
> El primero es donde Reverb escucha en el servidor.
> El segundo es donde el navegador se conecta.

---

## Nginx - Configuración del Proxy Reverso

Reverb corre en el puerto 8080 internamente. Nginx lo expone en el 443 (HTTPS).

```nginx
server {
    listen 443 ssl;
    server_name tu-dominio.com;

    # SSL (Let's Encrypt)
    ssl_certificate /etc/letsencrypt/live/tu-dominio.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/tu-dominio.com/privkey.pem;

    # Laravel app
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Reverb WebSocket proxy
    location /app {
        proxy_http_version 1.1;
        proxy_set_header Host $http_host;
        proxy_set_header Scheme $scheme;
        proxy_set_header SERVER_PORT $server_port;
        proxy_set_header REMOTE_ADDR $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_pass http://0.0.0.0:8080;
    }

    # Reverb API
    location /apps {
        proxy_http_version 1.1;
        proxy_set_header Host $http_host;
        proxy_pass http://0.0.0.0:8080;
    }
}
```

---

## Supervisor - Mantener Reverb corriendo

Reverb es un proceso de larga duración. Supervisor lo reinicia si cae.

```ini
# /etc/supervisor/conf.d/reverb.conf
[program:reverb]
command=php /var/www/elysium/artisan reverb:start --host=0.0.0.0 --port=8080
directory=/var/www/elysium
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/reverb.log
minfds=10000
```

```bash
# Aplicar configuración
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start reverb
```

---

## Límites del Sistema Operativo (Open Files)

Cada conexión WebSocket usa un file descriptor. Por defecto Linux limita a 1024.

```bash
# Ver límite actual
ulimit -n

# Aumentar para el usuario www-data
# Editar /etc/security/limits.conf
www-data    soft    nofile    10000
www-data    hard    nofile    10000
```

---

## Comandos de Despliegue

```bash
# 1. Clonar y configurar
git clone <repo> /var/www/elysium
cd /var/www/elysium
cp .env.example .env

# 2. Instalar dependencias
composer install --no-dev --optimize-autoloader
npm install
npm run build

# 3. Configurar entorno
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force

# 4. Optimizar Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Permisos
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# 6. Iniciar Reverb (via Supervisor)
sudo supervisorctl start reverb
```

---

## Comandos de Mantenimiento

```bash
# Reiniciar Reverb después de cambios de código
php artisan reverb:restart

# Ver logs de Reverb
tail -f /var/log/reverb.log

# Ver estado
sudo supervisorctl status reverb

# Limpiar caché
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

---

## Verificación de Conexión

Desde la consola del navegador, verificar que Echo conecta:

```javascript
// Debe mostrar "connected" sin errores
window.Echo.connector.pusher.connection.state
```

Si muestra `failed` o `unavailable`:
1. Verificar que Reverb está corriendo: `sudo supervisorctl status reverb`
2. Verificar que Nginx proxy está configurado correctamente
3. Verificar que `REVERB_HOST` en `.env` coincide con el dominio real
4. Verificar que el puerto 8080 no está bloqueado por el firewall de AWS

---

## Firewall AWS (Security Groups)

En la consola de AWS, el Security Group de la instancia debe tener:

| Tipo | Puerto | Origen |
|------|--------|--------|
| HTTP | 80 | 0.0.0.0/0 |
| HTTPS | 443 | 0.0.0.0/0 |
| SSH | 22 | Tu IP |

**No necesitas abrir el puerto 8080** — Nginx hace el proxy internamente.
