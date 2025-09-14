# Production Deployment Guide

This guide provides complete instructions for deploying SSL Monitor to production with all services: Laravel Horizon, Laravel Pulse, Laravel Reverb, and background workers.

## 🏗️ Production Architecture

SSL Monitor in production consists of multiple interconnected services:

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   Web Server    │    │  Laravel App     │    │     Redis       │
│  (Nginx/Apache) │────│  (SSL Monitor)   │────│ (Cache/Queues)  │
└─────────────────┘    └──────────────────┘    └─────────────────┘
                                │
        ┌───────────────────────┼───────────────────────┐
        │                       │                       │
┌───────▼──────┐    ┌───────────▼──────┐    ┌─────────▼─────────┐
│   Horizon    │    │     Reverb       │    │    Scheduler     │
│ (Queue Mgmt) │    │  (WebSockets)    │    │   (Cron Jobs)    │
└──────────────┘    └──────────────────┘    └───────────────────┘
        │                       │
        │               ┌───────▼──────┐
        │               │     Pulse    │
        └───────────────│ (Monitoring) │
                        └──────────────┘
```

### Service Overview

- **Web Server**: Serves the main SSL Monitor application
- **Laravel Horizon**: Queue management and monitoring (Port: varies by config)
- **Laravel Pulse**: Application performance monitoring (Port: varies by config)  
- **Laravel Reverb**: Real-time WebSocket server (Port: 8080)
- **Background Scheduler**: Runs scheduled SSL/uptime checks
- **Redis**: Handles caching, queues, and session storage
- **Database**: Stores SSL monitoring data (MySQL/MariaDB/PostgreSQL)

## 🚀 Quick Production Setup

### Prerequisites

- **Server**: Ubuntu 20.04+ / CentOS 8+ / Debian 11+
- **PHP**: 8.2+ with required extensions
- **Database**: MySQL 8.0+ / MariaDB 10.3+ / PostgreSQL 13+
- **Redis**: 6.0+
- **Web Server**: Nginx 1.18+ or Apache 2.4+
- **Process Manager**: Supervisor
- **SSL Certificate**: For HTTPS/WSS connections

### 1. Application Deployment

```bash
# Clone repository
git clone https://github.com/your-org/ssl-monitor.git /var/www/ssl-monitor
cd /var/www/ssl-monitor

# Install PHP dependencies
composer install --optimize-autoloader --no-dev

# Install and build frontend assets
npm ci
npm run build

# Set permissions
sudo chown -R www-data:www-data /var/www/ssl-monitor
sudo chmod -R 755 /var/www/ssl-monitor/storage
sudo chmod -R 755 /var/www/ssl-monitor/bootstrap/cache
```

### 2. Environment Configuration

```bash
# Copy and configure environment
cp .env.example .env
php artisan key:generate

# Edit .env file with production values
nano .env
```

#### Production .env Configuration

```env
# Application
APP_NAME="SSL Monitor"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://ssl-monitor.yourcompany.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ssl_monitor
DB_USERNAME=ssl_monitor_user
DB_PASSWORD=secure_database_password

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=secure_redis_password
REDIS_PORT=6379

# Queue Configuration
QUEUE_CONNECTION=redis
BROADCAST_CONNECTION=reverb

# Laravel Reverb (Real-time WebSockets)
REVERB_APP_ID=ssl-monitor-prod
REVERB_APP_KEY=your-secure-app-key-32-chars
REVERB_APP_SECRET=your-secure-app-secret-32-chars
REVERB_HOST=ssl-monitor.yourcompany.com
REVERB_PORT=443
REVERB_SCHEME=https

# Internal service communication
REVERB_SERVER_HOST=127.0.0.1
REVERB_SERVER_PORT=8080

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.yourcompany.com
MAIL_PORT=587
MAIL_USERNAME=ssl-monitor@yourcompany.com
MAIL_PASSWORD=secure_mail_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=ssl-monitor@yourcompany.com
MAIL_FROM_NAME="SSL Monitor"

# Session and Cache
SESSION_DRIVER=redis
CACHE_STORE=redis

# Security
SESSION_ENCRYPT=true
APP_CIPHER=AES-256-CBC

# Logging
LOG_CHANNEL=daily
LOG_LEVEL=warning

# Performance
BCRYPT_ROUNDS=12
```

### 3. Database Setup

```bash
# Run migrations
php artisan migrate --force

# Create initial user (optional)
php artisan tinker --execute="
\$user = App\Models\User::create([
    'name' => 'Admin User',
    'email' => 'admin@yourcompany.com',
    'password' => bcrypt('secure-password-here'),
    'email_verified_at' => now(),
]);
echo 'Admin user created: ' . \$user->email;
"
```

## 🔧 Process Management with Supervisor

### Install Supervisor

```bash
# Ubuntu/Debian
sudo apt install supervisor

# CentOS/RHEL
sudo yum install supervisor
# or
sudo dnf install supervisor
```

### Supervisor Configuration

Create `/etc/supervisor/conf.d/ssl-monitor.conf`:

```ini
[group:ssl-monitor]
programs=ssl-monitor-horizon,ssl-monitor-reverb,ssl-monitor-scheduler
priority=999

# Laravel Horizon (Queue Management)
[program:ssl-monitor-horizon]
process_name=%(program_name)s
command=php /var/www/ssl-monitor/artisan horizon
directory=/var/www/ssl-monitor
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/ssl-monitor/storage/logs/horizon.log
stopwaitsecs=3600

# Laravel Reverb (WebSocket Server)  
[program:ssl-monitor-reverb]
process_name=%(program_name)s
command=php /var/www/ssl-monitor/artisan reverb:start --host=127.0.0.1 --port=8080
directory=/var/www/ssl-monitor
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/ssl-monitor/storage/logs/reverb.log
stopwaitsecs=30

# Laravel Scheduler
[program:ssl-monitor-scheduler]
process_name=%(program_name)s
command=php /var/www/ssl-monitor/artisan schedule:work
directory=/var/www/ssl-monitor
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/ssl-monitor/storage/logs/scheduler.log
numprocs=1
```

### Start Services

```bash
# Reload supervisor configuration
sudo supervisorctl reread
sudo supervisorctl update

# Start SSL Monitor services
sudo supervisorctl start ssl-monitor:*

# Check status
sudo supervisorctl status ssl-monitor:*
```

## 🌐 Web Server Configuration

### Nginx Configuration

Create `/etc/nginx/sites-available/ssl-monitor`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name ssl-monitor.yourcompany.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name ssl-monitor.yourcompany.com;
    root /var/www/ssl-monitor/public;
    
    index index.php;
    
    # SSL Configuration
    ssl_certificate /path/to/ssl-certificate.crt;
    ssl_certificate_key /path/to/ssl-private.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    
    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: ws: wss: data: blob: 'unsafe-inline'; frame-ancestors 'self';" always;
    
    # Laravel Application
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }
    
    # WebSocket Proxy for Laravel Reverb
    location /app/ {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
        proxy_read_timeout 86400;
    }
    
    # Static Assets
    location ~* \.(css|js|ico|png|jpg|jpeg|gif|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }
    
    # Security
    location ~ /\.(?!well-known).* {
        deny all;
    }
    
    # Logging
    access_log /var/log/nginx/ssl-monitor_access.log;
    error_log /var/log/nginx/ssl-monitor_error.log;
}

# Laravel Horizon Dashboard (Optional: Restrict Access)
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name horizon.ssl-monitor.yourcompany.com;
    
    # SSL Configuration (same as above)
    ssl_certificate /path/to/ssl-certificate.crt;
    ssl_certificate_key /path/to/ssl-private.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    
    # Basic Auth (Optional)
    auth_basic "Laravel Horizon";
    auth_basic_user_file /etc/nginx/.htpasswd-horizon;
    
    location / {
        proxy_pass http://ssl-monitor.yourcompany.com/horizon/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}

# Laravel Pulse Dashboard (Optional: Restrict Access)
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name pulse.ssl-monitor.yourcompany.com;
    
    # SSL Configuration (same as above)
    ssl_certificate /path/to/ssl-certificate.crt;
    ssl_certificate_key /path/to/ssl-private.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    
    # Basic Auth (Optional)
    auth_basic "Laravel Pulse";
    auth_basic_user_file /etc/nginx/.htpasswd-pulse;
    
    location / {
        proxy_pass http://ssl-monitor.yourcompany.com/pulse/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

### Enable Nginx Configuration

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/ssl-monitor /etc/nginx/sites-enabled/

# Test configuration
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx
```

## 🔐 Security Configuration

### ⚠️ CRITICAL: WebSocket Security

**NEVER run Reverb with `--host=0.0.0.0` in production!**

The WebSocket server must be properly secured:

```bash
# ✅ SECURE (localhost only)
php artisan reverb:start --host=127.0.0.1 --port=8080

# ❌ INSECURE (accepts connections from anywhere)  
php artisan reverb:start --host=0.0.0.0 --port=8080
```

**Security measures for single-server deployment:**

1. **Host Binding**: Reverb binds to `127.0.0.1` (localhost only)
2. **Origin Restrictions**: Only your domain can connect (see `config/reverb.php`)
3. **Firewall Rules**: Block external access to port 8080
4. **Direct HTTPS/WSS**: Use SSL certificates for encrypted connections
5. **Same-Server Benefits**: All communication stays internal

**Production firewall setup:**
```bash
# Block external access to WebSocket port
sudo ufw deny 8080
# Allow internal localhost connections (automatic)
```

**Production environment variables:**
```bash
# Single-server WebSocket configuration
REVERB_HOST=yourdomain.com
REVERB_PORT=8080
REVERB_SCHEME=https

# Optional: Direct SSL/TLS for WSS
SSL_CERT_PATH=/path/to/certificate.crt
SSL_KEY_PATH=/path/to/private.key
```

### Additional Security

### Dashboard Access Control

Update `app/Providers/AppServiceProvider.php` for production access control:

```php
public function boot(): void
{
    // Configure Pulse dashboard authorization
    Gate::define('viewPulse', function ($user = null) {
        return in_array(optional($user)->email, [
            'admin@yourcompany.com',
            'devops@yourcompany.com',
        ]);
    });
}
```

Create `app/Providers/HorizonServiceProvider.php`:

```php
<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        Gate::define('viewHorizon', function ($user = null) {
            return in_array(optional($user)->email, [
                'admin@yourcompany.com',
                'devops@yourcompany.com',
            ]);
        });
    }
}
```

### Firewall Configuration

```bash
# Ubuntu UFW
sudo ufw allow 22/tcp      # SSH
sudo ufw allow 80/tcp      # HTTP
sudo ufw allow 443/tcp     # HTTPS
sudo ufw allow 8080/tcp    # Reverb (if not proxied)
sudo ufw enable

# CentOS/RHEL Firewalld
sudo firewall-cmd --permanent --add-service=ssh
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --permanent --add-port=8080/tcp  # Reverb
sudo firewall-cmd --reload
```

## 📊 Monitoring and Health Checks

### Application Health Check

Create a health check endpoint (already available at `/up`):

```bash
# Test application health
curl -f https://ssl-monitor.yourcompany.com/up || exit 1
```

### Service Health Monitoring

Create `/usr/local/bin/ssl-monitor-health.sh`:

```bash
#!/bin/bash

# Health check script for SSL Monitor services

check_service() {
    local service_name="$1"
    local service_status=$(sudo supervisorctl status "ssl-monitor:$service_name")
    
    if [[ $service_status == *"RUNNING"* ]]; then
        echo "✅ $service_name is running"
        return 0
    else
        echo "❌ $service_name is not running: $service_status"
        return 1
    fi
}

echo "🔍 SSL Monitor Health Check - $(date)"
echo "================================="

# Check all services
SERVICES=("ssl-monitor-horizon" "ssl-monitor-reverb" "ssl-monitor-scheduler")
FAILED_SERVICES=0

for service in "${SERVICES[@]}"; do
    if ! check_service "$service"; then
        ((FAILED_SERVICES++))
    fi
done

# Check Redis connection
if redis-cli ping > /dev/null 2>&1; then
    echo "✅ Redis is responding"
else
    echo "❌ Redis is not responding"
    ((FAILED_SERVICES++))
fi

# Check database connection
if php /var/www/ssl-monitor/artisan tinker --execute="DB::connection()->getPdo(); echo 'Database connected';" > /dev/null 2>&1; then
    echo "✅ Database is connected"
else
    echo "❌ Database connection failed"
    ((FAILED_SERVICES++))
fi

# Check WebSocket server
if curl -f -s http://127.0.0.1:8080 > /dev/null 2>&1; then
    echo "✅ Reverb WebSocket server is responding"
else
    echo "❌ Reverb WebSocket server is not responding"
    ((FAILED_SERVICES++))
fi

echo "================================="
if [ $FAILED_SERVICES -eq 0 ]; then
    echo "🎉 All services are healthy!"
    exit 0
else
    echo "⚠️  $FAILED_SERVICES service(s) failed health check"
    exit 1
fi
```

Make it executable and add to cron:

```bash
chmod +x /usr/local/bin/ssl-monitor-health.sh

# Add to crontab for monitoring
echo "*/5 * * * * /usr/local/bin/ssl-monitor-health.sh >> /var/log/ssl-monitor-health.log 2>&1" | sudo crontab -
```

## 🚀 Performance Optimization

### PHP-FPM Configuration

Edit `/etc/php/8.2/fpm/pool.d/www.conf`:

```ini
; Process management
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 500

; Performance
request_slowlog_timeout = 10s
slowlog = /var/log/php8.2-fpm-slow.log
```

### Redis Configuration

Edit `/etc/redis/redis.conf`:

```conf
# Memory optimization
maxmemory 512mb
maxmemory-policy allkeys-lru

# Persistence (adjust for your needs)
save 900 1
save 300 10
save 60 10000

# Security
requirepass your-secure-redis-password
```

### Laravel Optimizations

```bash
# Cache configurations
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize
```

## 📈 Scaling Considerations

### Horizontal Scaling

For high-load environments:

```bash
# Multiple Horizon workers
[program:ssl-monitor-horizon-1]
command=php /var/www/ssl-monitor/artisan horizon
numprocs=1

[program:ssl-monitor-horizon-2]  
command=php /var/www/ssl-monitor/artisan horizon
numprocs=1

# Multiple Reverb instances (with load balancer)
[program:ssl-monitor-reverb-1]
command=php /var/www/ssl-monitor/artisan reverb:start --host=127.0.0.1 --port=8080

[program:ssl-monitor-reverb-2]
command=php /var/www/ssl-monitor/artisan reverb:start --host=127.0.0.1 --port=8081
```

### Load Balancing WebSockets

Use HAProxy or Nginx upstream for Reverb:

```nginx
upstream reverb_backend {
    server 127.0.0.1:8080;
    server 127.0.0.1:8081;
}

location /app/ {
    proxy_pass http://reverb_backend;
    # ... other WebSocket proxy settings
}
```

## 🔄 Maintenance and Updates

### Update Process

```bash
# 1. Put application in maintenance mode
php artisan down --message="System maintenance in progress"

# 2. Pull latest changes
git pull origin main

# 3. Update dependencies
composer install --optimize-autoloader --no-dev
npm ci && npm run build

# 4. Run migrations
php artisan migrate --force

# 5. Clear and cache configurations
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Restart services
sudo supervisorctl restart ssl-monitor:*

# 7. Bring application back online
php artisan up
```

### Backup Strategy

```bash
#!/bin/bash
# Backup script: /usr/local/bin/ssl-monitor-backup.sh

BACKUP_DIR="/var/backups/ssl-monitor"
DATE=$(date +%Y-%m-%d_%H-%M-%S)
mkdir -p "$BACKUP_DIR"

# Database backup
mysqldump -u ssl_monitor_user -p'secure_password' ssl_monitor > "$BACKUP_DIR/database_$DATE.sql"

# Application backup
tar -czf "$BACKUP_DIR/application_$DATE.tar.gz" /var/www/ssl-monitor --exclude=/var/www/ssl-monitor/storage/logs

# Keep only last 30 days of backups
find "$BACKUP_DIR" -type f -mtime +30 -delete

echo "Backup completed: $DATE"
```

## 📱 Monitoring Dashboards Access

After deployment, access your monitoring dashboards:

- **SSL Monitor**: https://ssl-monitor.yourcompany.com
- **Laravel Horizon**: https://ssl-monitor.yourcompany.com/horizon  
- **Laravel Pulse**: https://ssl-monitor.yourcompany.com/pulse

## 🆘 Troubleshooting

### Common Issues

**Horizon not processing jobs:**
```bash
sudo supervisorctl status ssl-monitor:ssl-monitor-horizon
sudo supervisorctl restart ssl-monitor:ssl-monitor-horizon
tail -f /var/www/ssl-monitor/storage/logs/horizon.log
```

**Reverb WebSocket connection issues:**
```bash
# Check if Reverb is running
sudo supervisorctl status ssl-monitor:ssl-monitor-reverb
curl -I http://127.0.0.1:8080

# Test WebSocket connection
wscat -c ws://127.0.0.1:8080/app/ssl-monitor-prod
```

**Permission issues:**
```bash
sudo chown -R www-data:www-data /var/www/ssl-monitor
sudo chmod -R 755 /var/www/ssl-monitor/storage
sudo chmod -R 755 /var/www/ssl-monitor/bootstrap/cache
```

### Log Locations

- **Application**: `/var/www/ssl-monitor/storage/logs/laravel.log`
- **Horizon**: `/var/www/ssl-monitor/storage/logs/horizon.log`
- **Reverb**: `/var/www/ssl-monitor/storage/logs/reverb.log`
- **Scheduler**: `/var/www/ssl-monitor/storage/logs/scheduler.log`
- **Nginx**: `/var/log/nginx/ssl-monitor_*.log`
- **PHP-FPM**: `/var/log/php8.2-fpm.log`

This comprehensive deployment guide ensures your SSL Monitor production environment runs smoothly with all services properly configured and monitored! 🚀