# 🚀 Optimización para AWS RDS db.t4g.micro (1GB RAM)

## Especificaciones de la Instancia

- **Clase**: db.t4g.micro
- **vCPU**: 2 (ARM Graviton2)
- **RAM**: 1 GB
- **Almacenamiento**: 20 GiB SSD gp2 (escalable hasta 1000 GiB)
- **Sistema Operativo**: Ubuntu 8.4.7

---

## ⚙️ Cambios Realizados en el Proyecto

### 1. Variables de Entorno (.env.example)

```bash
# Optimizado para producción
APP_ENV=production
APP_DEBUG=false

# Reducir workers para bajo consumo de memoria
PHP_CLI_SERVER_WORKERS=4

# BCRYPT reducido para menor uso de CPU
BCRYPT_ROUNDS=10

# Cache en archivo (NO en database para ahorrar conexiones DB)
CACHE_STORE=file

# Configuración MySQL optimizada
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
DB_STRICT=true
DB_ENGINE=InnoDB
```

### 2. Configuración de Base de Datos (config/database.php)

```php
'mysql' => [
    // ... otras configuraciones
    'strict' => env('DB_STRICT', true),
    'engine' => env('DB_ENGINE', 'InnoDB'),
    'options' => [
        PDO::ATTR_PERSISTENT => env('DB_PERSISTENT_CONNECTIONS', false),
    ],
],
```

**Importante**: Las conexiones persistentes están DESACTIVADAS por defecto para evitar agotar las conexiones en la instancia pequeña.

### 3. Configuración de Caché (config/cache.php)

```php
'default' => env('CACHE_STORE', 'file'),  // Changed from 'database'
```

**Razón**: Usar caché en archivos reduce las consultas a la base de datos, crucial para una instancia con solo 1GB RAM.

### 4. Configuración Nginx (nginx-config.conf)

```nginx
location ~ \.php$ {
    # Timeouts extendidos para procesos lentos
    fastcgi_connect_timeout 60s;
    fastcgi_send_timeout 120s;
    fastcgi_read_timeout 120s;
    
    # Buffers reducidos para bajo consumo de memoria
    fastcgi_buffer_size 4k;
    fastcgi_buffers 8 4k;
    fastcgi_busy_buffers_size 8k;
}

# Compresión GZIP habilitada
gzip on;
gzip_vary on;
gzip_min_length 1024;
gzip_types text/plain text/css application/javascript;
```

### 5. Script de Instalación (install-ubuntu.sh)

- Eliminado cambio automático a `APP_DEBUG=true` y `APP_ENV=local`
- Ahora respeta la configuración de producción del `.env.example`

---

## 📊 Recomendaciones Adicionales

### 1. Parámetros de MySQL en RDS

Configurar en el Parameter Group de RDS:

```
innodb_buffer_pool_size = 536870912        # 512MB (50% de RAM)
innodb_log_file_size = 67108864            # 64MB
innodb_flush_log_at_trx_commit = 2         # Mejor rendimiento, riesgo mínimo
max_connections = 50                       # Limitar conexiones
thread_cache_size = 8                      # Reutilizar threads
query_cache_type = 0                       # Desactivar (obsoleto en MySQL 8)
```

### 2. PHP-FPM Pool Configuration

Crear `/etc/php/8.2/fpm/pool.d/www.conf`:

```ini
[www]
pm = dynamic
pm.max_children = 10
pm.start_servers = 3
pm.min_spare_servers = 2
pm.max_spare_servers = 5
pm.max_requests = 500
request_terminate_timeout = 120s
```

### 3. Monitoreo

Habilitar CloudWatch para monitorear:

- **CPU Utilization**: Alerta si > 80% por 5 min
- **Freeable Memory**: Alerta si < 100MB
- **Database Connections**: Alerta si > 40
- **Disk Queue Depth**: Alerta si > 2

### 4. Optimizaciones de Laravel

#### Limpiar cachés periódicamente:

```bash
# En crontab (cada hora)
0 * * * * cd /var/www/laravelchatify && php artisan cache:prune-stale-tags
```

#### Configurar queue worker con límites de memoria:

```bash
php artisan queue:work --memory=256 --timeout=120 --sleep=3
```

#### Deshabilitar servicios no esenciales:

En `config/app.php` o mediante variables de entorno:
- Telescope (si está instalado)
- Debugbar (solo desarrollo)

### 5. Indexación de Base de Datos

Asegurar índices en tablas frecuentes:

```sql
-- Ejecutar después de migraciones
ALTER TABLE sessions ADD INDEX last_activity_idx (last_activity);
ALTER TABLE jobs ADD INDEX available_at_idx (available_at);
ALTER TABLE cache ADD INDEX expiration_idx (expiration);
```

### 6. Backup Automático

Configurar automated backups en RDS:
- **Retention Period**: 7 días
- **Backup Window**: Hora de menor tráfico (ej. 3:00-4:00 AM)
- **Maintenance Window**: Similar al backup window

---

## 🔧 Comandos de Mantenimiento

### Limpiar logs antiguos:

```bash
find /var/www/laravelchatify/storage/logs -name "*.log" -mtime +7 -delete
```

### Optimizar autoload de Composer:

```bash
composer dump-autoload --optimize --classmap-authoritative
```

### Clear cachés de Laravel:

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

### Reiniciar servicios (después de cambios):

```bash
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
```

---

## ⚠️ Advertencias

1. **NO usar Redis/Memcached** para caché a menos que tengas una instancia ElastiCache separada
2. **NO ejecutar queue workers múltiples** - máximo 1-2 workers
3. **Monitorizar siempre** el uso de memoria antes de escalar tráfico
4. **Evitar operaciones bulk** grandes en horas pico
5. **Usar Google Drive** para almacenamiento de archivos (ya configurado)

---

## 📈 Escalabilidad

Cuando el tráfico aumente:

1. **Primero**: Optimizar queries y agregar índices
2. **Segundo**: Mover caché a ElastiCache Redis
3. **Tercero**: Escalar RDS a db.t4g.small (2GB RAM)
4. **Cuarto**: Implementar read replicas

---

## ✅ Checklist Post-Instalación

- [ ] Verificar que `CACHE_STORE=file` en .env
- [ ] Confirmar `APP_DEBUG=false` en producción
- [ ] Ejecutar `php artisan config:cache`
- [ ] Ejecutar `php artisan route:cache`
- [ ] Ejecutar `php artisan view:cache`
- [ ] Configurar parameter group de RDS
- [ ] Habilitar CloudWatch alarms
- [ ] Configurar backup automático RDS
- [ ] Ajustar PHP-FPM pool settings
- [ ] Ejecutar optimize:clear y luego optimize

---

**Documentación creada para**: Elysium Ito  
**Fecha**: $(date +%Y-%m-%d)  
**Versión**: 1.0
