# Production Deployment Guide

Complete checklist and best practices for deploying SSL Monitor to production environments.

## 🎯 Overview

This guide provides a comprehensive deployment checklist to ensure SSL Monitor runs securely and reliably in production.

## ✅ Pre-Deployment Checklist

### Environment Preparation
- [ ] **Server meets minimum requirements** (PHP 8.2+, MySQL 8.0+, etc.)
- [ ] **SSL certificate installed** for HTTPS
- [ ] **Domain name configured** and pointing to server
- [ ] **Firewall configured** (ports 80, 443 open)
- [ ] **Database server running** and accessible
- [ ] **Redis server installed** and configured (for queues)
- [ ] **Web server configured** (Nginx or Apache)
- [ ] **PHP-FPM configured** with appropriate resources

### Security Requirements
- [ ] **SSH key-based authentication** enabled
- [ ] **Root login disabled** via SSH
- [ ] **Firewall enabled** with restrictive rules
- [ ] **Automatic security updates** configured
- [ ] **File permissions** properly set
- [ ] **Database access restricted** to localhost/specific IPs
- [ ] **Strong passwords** for all accounts

## 🚀 Deployment Steps

### 1. Code Deployment

#### Option A: Git Deployment
```bash
# Clone repository
cd /var/www
git clone https://github.com/yourorg/ssl-monitor.git
cd ssl-monitor

# Checkout specific version/tag
git checkout tags/v1.0.0  # or specific branch

# Install dependencies
composer install --no-dev --optimize-autoloader
npm install --production
npm run build
```

#### Option B: Upload Deployment
```bash
# Upload pre-built application
rsync -avz --exclude node_modules --exclude .git /local/ssl-monitor/ user@server:/var/www/ssl-monitor/

# Install only production dependencies
composer install --no-dev --optimize-autoloader
```

### 2. Environment Configuration

```bash
# Copy and configure environment
cp .env.example .env
nano .env  # Configure all production settings
```

**Critical production settings**:
```env
APP_NAME="SSL Monitor"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://ssl-monitor.yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=ssl_monitor
DB_USERNAME=ssl_monitor_user
DB_PASSWORD=strong_production_password

QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1

MAIL_FROM_ADDRESS="ssl-alerts@yourdomain.com"
MAIL_FROM_NAME="SSL Monitor"
```

### 3. Application Setup

```bash
# Generate application key
php artisan key:generate

# Run database migrations
php artisan migrate --force

# Cache configuration for performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create storage symlink
php artisan storage:link
```

### 4. Permissions and Security

```bash
# Set correct ownership
chown -R www-data:www-data /var/www/ssl-monitor

# Set secure permissions
chmod -R 755 /var/www/ssl-monitor
chmod -R 775 /var/www/ssl-monitor/storage
chmod -R 775 /var/www/ssl-monitor/bootstrap/cache
chmod 600 /var/www/ssl-monitor/.env

# Secure sensitive files
chmod 600 /var/www/ssl-monitor/.env.example
chmod -R 700 /var/www/ssl-monitor/.git
```

## ⚙️ Service Configuration

### Queue Worker Service

Create systemd service at `/etc/systemd/system/ssl-monitor-worker.service`:

```ini
[Unit]
Description=SSL Monitor Queue Worker
After=network.target redis.service mysql.service

[Service]
User=www-data
Group=www-data
Restart=always
RestartSec=3
ExecStart=/usr/bin/php /var/www/ssl-monitor/artisan queue:work redis --queue=ssl-monitoring --sleep=3 --tries=3 --max-time=3600 --timeout=60
StandardOutput=journal
StandardError=journal
SyslogIdentifier=ssl-monitor-worker

# Performance settings
LimitNOFILE=65536
PrivateTmp=true
ProtectSystem=full
NoNewPrivileges=yes

[Install]
WantedBy=multi-user.target
```

```bash
# Enable and start service
systemctl daemon-reload
systemctl enable ssl-monitor-worker
systemctl start ssl-monitor-worker
```

### Cron Schedule

```bash
# Edit www-data user crontab
crontab -e -u www-data
```

Add:
```bash
* * * * * cd /var/www/ssl-monitor && php artisan schedule:run >> /dev/null 2>&1
```

## 🌐 Web Server Final Configuration

### Nginx Production Configuration

```nginx
# /etc/nginx/sites-available/ssl-monitor
server {
    listen 80;
    server_name ssl-monitor.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name ssl-monitor.yourdomain.com;

    root /var/www/ssl-monitor/public;
    index index.php;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/ssl-monitor.yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/ssl-monitor.yourdomain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Gzip Compression
    gzip on;
    gzip_vary on;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        
        # Production PHP settings
        fastcgi_read_timeout 300;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    # Deny access to sensitive files
    location ~ /\.(?!well-known).* {
        deny all;
    }

    location ~ \.(log|env|git)$ {
        deny all;
    }

    # Asset caching
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1M;
        add_header Cache-Control "public, immutable";
    }
}
```

### PHP-FPM Production Configuration

Edit `/etc/php/8.2/fpm/pool.d/www.conf`:

```ini
[www]
user = www-data
group = www-data

listen = /var/run/php/php8.2-fpm.sock
listen.owner = www-data
listen.group = www-data

pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.process_idle_timeout = 10s
pm.max_requests = 500

php_admin_value[error_log] = /var/log/php/www-error.log
php_admin_flag[log_errors] = on
php_admin_value[memory_limit] = 256M
php_admin_value[max_execution_time] = 300
php_admin_value[upload_max_filesize] = 10M
php_admin_value[post_max_size] = 10M
```

## 🔐 Security Hardening

### Application Security

```bash
# Clear sensitive caches in production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Verify no debug mode
grep APP_DEBUG .env  # Should be false

# Check file permissions
find /var/www/ssl-monitor -type f -perm /o+w -exec ls -la {} \;  # Should be empty
```

### Database Security

```sql
-- Create dedicated database user with minimal privileges
CREATE USER 'ssl_monitor'@'localhost' IDENTIFIED BY 'strong_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON ssl_monitor.* TO 'ssl_monitor'@'localhost';
FLUSH PRIVILEGES;

-- Remove test databases and users
DROP DATABASE IF EXISTS test;
DELETE FROM mysql.user WHERE User='';
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');
FLUSH PRIVILEGES;
```

### System Security

```bash
# Configure automatic security updates
apt install unattended-upgrades
dpkg-reconfigure -plow unattended-upgrades

# Configure firewall
ufw --force reset
ufw default deny incoming
ufw default allow outgoing
ufw allow ssh
ufw allow 'Nginx Full'
ufw enable

# Disable unnecessary services
systemctl disable apache2  # If not using
systemctl disable postfix  # If not using for email
```

## 📊 Performance Optimization

### Database Optimization

```sql
-- Configure MySQL for production
# /etc/mysql/mysql.conf.d/mysqld.cnf
[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
query_cache_type = 1
query_cache_size = 256M
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
```

### Redis Optimization

```bash
# /etc/redis/redis.conf
maxmemory 512mb
maxmemory-policy allkeys-lru
save 900 1
save 300 10
save 60 10000
```

### PHP Performance

```ini
# /etc/php/8.2/fpm/php.ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
```

## 🔍 Deployment Verification

### 1. Application Health Check

```bash
# Test web interface
curl -I https://ssl-monitor.yourdomain.com
# Should return 200 OK

# Check Laravel installation
php artisan --version

# Verify database connection
php artisan migrate:status
```

### 2. Service Health Check

```bash
# Check queue worker
systemctl status ssl-monitor-worker

# Verify Redis connection
redis-cli ping

# Test scheduled tasks
php artisan schedule:list
```

### 3. SSL Monitoring Test

```bash
# Run manual SSL check
php artisan ssl:check-all

# Check logs for errors
tail -f storage/logs/laravel.log

# Verify queue processing
php artisan queue:monitor ssl-monitoring
```

### 4. Performance Testing

```bash
# Test response times
ab -n 100 -c 10 https://ssl-monitor.yourdomain.com/login

# Monitor resource usage
htop
iotop
free -m
```

## 📝 Post-Deployment Tasks

### 1. Create Admin Account

```bash
php artisan tinker
```

```php
User::factory()->create([
    'name' => 'Admin User',
    'email' => 'admin@yourdomain.com',
    'email_verified_at' => now(),
    'password' => Hash::make('secure_password')
]);
```

### 2. Configure Monitoring

Set up application monitoring (optional):
- **Error tracking** (e.g., Sentry)
- **Performance monitoring** (e.g., New Relic)
- **Uptime monitoring** (e.g., Pingdom)
- **Log aggregation** (e.g., ELK stack)

### 3. Backup Configuration

```bash
# Database backup script
#!/bin/bash
mysqldump -u ssl_monitor -p ssl_monitor > /backups/ssl_monitor_$(date +%Y%m%d_%H%M%S).sql

# Application backup
tar -czf /backups/ssl_monitor_app_$(date +%Y%m%d_%H%M%S).tar.gz /var/www/ssl-monitor --exclude-from=/var/www/ssl-monitor/.gitignore
```

### 4. Documentation

- [ ] **Document server configuration** for team reference
- [ ] **Create recovery procedures** for disasters
- [ ] **Document access credentials** in secure location
- [ ] **Share user access information** with team

## 🚨 Deployment Troubleshooting

### Common Issues

#### 500 Internal Server Error
```bash
# Check Laravel logs
tail -f /var/www/ssl-monitor/storage/logs/laravel.log

# Check web server logs
tail -f /var/log/nginx/error.log

# Verify file permissions
ls -la /var/www/ssl-monitor/storage/
```

#### Queue Jobs Not Processing
```bash
# Check worker status
systemctl status ssl-monitor-worker

# Restart worker
systemctl restart ssl-monitor-worker

# Check Redis connection
redis-cli ping
```

#### Database Connection Issues
```bash
# Test database connection
mysql -u ssl_monitor -p ssl_monitor

# Check Laravel database config
php artisan tinker
DB::connection()->getPdo();
```

## 📋 Deployment Checklist

### Pre-Go-Live
- [ ] All services running (web server, database, Redis, queue worker)
- [ ] SSL certificate valid and properly configured
- [ ] All environment variables set correctly
- [ ] Database migrations completed successfully
- [ ] File permissions set securely
- [ ] Cron jobs configured and running
- [ ] Firewall configured with minimal required ports
- [ ] Application logs rotating properly
- [ ] Backup procedures in place

### Post-Go-Live
- [ ] Admin user account created
- [ ] Email configuration tested
- [ ] First website added and SSL check verified
- [ ] Queue processing confirmed working
- [ ] Scheduled tasks running correctly
- [ ] Performance monitoring in place
- [ ] Team access documented and distributed

## 🎯 Next Steps

- **[Environment Configuration](environment-config.md)** - Detailed configuration reference
- **[Queue Management](queue-management.md)** - Managing background jobs
- **[Monitoring](monitoring.md)** - System monitoring and health checks
- **[Maintenance](maintenance.md)** - Updates and ongoing maintenance

---

**Previous**: [Installation](installation.md) | **Next**: [Environment Configuration](environment-config.md)