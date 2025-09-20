# SSL Monitor Installation Guide

Complete guide for installing and setting up SSL Monitor in production or development environments.

## 🎯 Overview

SSL Monitor is a Laravel 12 application that provides automated SSL certificate monitoring with a web interface. This guide covers complete installation from scratch.

## 📋 System Requirements

### Minimum Requirements
- **PHP**: 8.2 or higher
- **Laravel**: 12.x (installed automatically)
- **Database**: MySQL 8.0+ or MariaDB 10.3+
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **Queue Driver**: Redis (recommended) or Database
- **SSL/TLS**: Required for HTTPS (recommended)

### Recommended Server Specs
- **RAM**: 2GB minimum, 4GB recommended
- **Storage**: 10GB available space
- **CPU**: 2 cores recommended
- **Network**: Outbound HTTPS access for SSL checking

### PHP Extensions Required
```bash
php-cli php-fpm php-mysql php-redis php-curl php-zip php-xml
php-mbstring php-tokenizer php-json php-bcmath php-ctype
php-fileinfo php-openssl php-pdo php-pdo_mysql
```

## 🚀 Installation Methods

### Method 1: Standard Laravel Installation

#### 1. Clone the Repository
```bash
cd /var/www
git clone https://github.com/yourorg/ssl-monitor.git
cd ssl-monitor
```

#### 2. Install Dependencies
```bash
# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node.js dependencies
npm install

# Build frontend assets
npm run build
```

#### 3. Configure Environment
```bash
# Copy environment template
cp .env.example .env

# Generate application key
php artisan key:generate
```

#### 4. Configure Database
Edit `.env` file with your database settings:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ssl_monitor
DB_USERNAME=ssl_monitor_user
DB_PASSWORD=secure_password
```

#### 5. Run Database Migrations
```bash
php artisan migrate
```

#### 6. Set Permissions
```bash
chown -R www-data:www-data /var/www/ssl-monitor
chmod -R 755 /var/www/ssl-monitor
chmod -R 775 /var/www/ssl-monitor/storage
chmod -R 775 /var/www/ssl-monitor/bootstrap/cache
```

### Method 2: Laravel Sail (Docker) - Development

#### 1. Clone and Setup
```bash
git clone https://github.com/yourorg/ssl-monitor.git
cd ssl-monitor
cp .env.example .env
```

#### 2. Start with Sail
```bash
# Install dependencies
./vendor/bin/sail up -d
./vendor/bin/sail composer install
./vendor/bin/sail npm install
./vendor/bin/sail npm run build

# Setup application
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
```

## ⚙️ Environment Configuration

### Essential Environment Variables

```env
# Application
APP_NAME="SSL Monitor"
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_KEY
APP_DEBUG=false
APP_URL=https://ssl-monitor.yourdomain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ssl_monitor
DB_USERNAME=ssl_monitor_user
DB_PASSWORD=secure_password

# Queue Configuration
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail (Optional - can be configured in-app)
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_FROM_ADDRESS="hello@example.com"

# Session & Cache
SESSION_DRIVER=redis
CACHE_DRIVER=redis
```

### Optional Configuration

```env
# Timezone
APP_TIMEZONE=UTC

# SSL Certificate Settings
SSL_CHECK_TIMEOUT=30
SSL_EXPIRY_WARNING_DAYS=14

# Queue Workers
QUEUE_RETRY_AFTER=90
QUEUE_MAX_TRIES=3
```

## 🗄️ Database Setup

### Create Database and User
```sql
-- MySQL/MariaDB
CREATE DATABASE ssl_monitor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ssl_monitor_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON ssl_monitor.* TO 'ssl_monitor_user'@'localhost';
FLUSH PRIVILEGES;
```

### Run Migrations
```bash
php artisan migrate
```

### Create First User (Optional)
```bash
php artisan tinker
```
```php
User::factory()->create([
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'email_verified_at' => now(),
]);
```

## 🔄 Queue Configuration

SSL Monitor requires background queue workers for SSL certificate checking.

### Redis Setup (Recommended)

#### Install Redis
```bash
# Ubuntu/Debian
apt install redis-server

# CentOS/RHEL
yum install redis

# Start and enable
systemctl start redis
systemctl enable redis
```

#### Configure Redis
```bash
# /etc/redis/redis.conf
bind 127.0.0.1
port 6379
# Set password if desired
requirepass your_redis_password
```

### Queue Worker Setup

#### Create Systemd Service
Create `/etc/systemd/system/ssl-monitor-worker.service`:
```ini
[Unit]
Description=SSL Monitor Queue Worker
After=redis.service

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/ssl-monitor/artisan queue:work --queue=ssl-monitoring --sleep=3 --tries=3 --max-time=3600 --timeout=60

[Install]
WantedBy=multi-user.target
```

#### Start Queue Worker
```bash
systemctl daemon-reload
systemctl enable ssl-monitor-worker
systemctl start ssl-monitor-worker
```

#### Monitor Queue Worker
```bash
systemctl status ssl-monitor-worker
journalctl -u ssl-monitor-worker -f
```

## ⏰ Schedule Configuration

SSL Monitor runs daily SSL checks automatically.

### Add Cron Entry
```bash
crontab -e -u www-data
```

Add this line:
```bash
* * * * * cd /var/www/ssl-monitor && php artisan schedule:run >> /dev/null 2>&1
```

### Verify Scheduling
```bash
# Check scheduled tasks
php artisan schedule:list

# Test schedule manually
php artisan schedule:run
```

## 🌐 Web Server Configuration

### Nginx Configuration

Create `/etc/nginx/sites-available/ssl-monitor`:
```nginx
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

    ssl_certificate /path/to/ssl-certificate.crt;
    ssl_certificate_key /path/to/private-key.key;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable the site:
```bash
ln -s /etc/nginx/sites-available/ssl-monitor /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx
```

### Apache Configuration

Create `/etc/apache2/sites-available/ssl-monitor.conf`:
```apache
<VirtualHost *:80>
    ServerName ssl-monitor.yourdomain.com
    Redirect permanent / https://ssl-monitor.yourdomain.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName ssl-monitor.yourdomain.com
    DocumentRoot /var/www/ssl-monitor/public

    SSLEngine on
    SSLCertificateFile /path/to/ssl-certificate.crt
    SSLCertificateKeyFile /path/to/private-key.key

    <Directory /var/www/ssl-monitor/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Enable the site:
```bash
a2ensite ssl-monitor
a2enmod rewrite ssl
systemctl reload apache2
```

## 🔐 Security Hardening

### File Permissions
```bash
chown -R www-data:www-data /var/www/ssl-monitor
chmod -R 755 /var/www/ssl-monitor
chmod -R 775 /var/www/ssl-monitor/storage
chmod -R 775 /var/www/ssl-monitor/bootstrap/cache
chmod 600 /var/www/ssl-monitor/.env
```

### Environment Security
- **Disable debug mode** in production: `APP_DEBUG=false`
- **Use strong APP_KEY** - Generate with `php artisan key:generate`
- **Secure database credentials** - Use strong passwords
- **Enable HTTPS** - Always use SSL certificates
- **Restrict file access** - Proper web server configuration

### Firewall Configuration
```bash
# Allow HTTP/HTTPS
ufw allow 80
ufw allow 443

# Allow SSH (if needed)
ufw allow 22

# Enable firewall
ufw enable
```

## ✅ Installation Verification

### 1. Test Application Access
- Visit `https://ssl-monitor.yourdomain.com`
- Verify login page appears
- Check for any error messages

### 2. Test Queue Processing
```bash
# Check queue worker status
systemctl status ssl-monitor-worker

# Test queue manually
php artisan queue:work --queue=ssl-monitoring --once
```

### 3. Test SSL Monitoring
```bash
# Run manual SSL check
php artisan ssl:check-all

# Check logs for errors
tail -f storage/logs/laravel.log
```

### 4. Test Scheduled Tasks
```bash
# Run schedule manually
php artisan schedule:run

# Verify cron is working
grep CRON /var/log/syslog
```

## 🔧 Post-Installation Tasks

### 1. Create Admin User
Login to the application and create your first user account.

### 2. Configure Email Settings
- Navigate to Settings → Email Settings
- Configure SMTP for notifications
- Test email configuration

### 3. Add First Websites
- Go to Websites page
- Add websites to monitor
- Verify SSL checks are working

### 4. Set Up Monitoring
- Configure application monitoring (optional)
- Set up log rotation
- Configure backup procedures

## 🆘 Troubleshooting Installation

### Common Issues

#### Permission Denied Errors
```bash
# Fix file permissions
chown -R www-data:www-data /var/www/ssl-monitor
chmod -R 755 /var/www/ssl-monitor
chmod -R 775 storage bootstrap/cache
```

#### Database Connection Failed
- Verify database credentials in `.env`
- Test database connection manually
- Check database service status

#### Queue Worker Not Processing
- Check systemd service status
- Verify Redis connection
- Check worker logs

#### 500 Internal Server Error
- Check Laravel logs: `tail -f storage/logs/laravel.log`
- Verify file permissions
- Clear application cache: `php artisan cache:clear`

### Getting Help

1. **Check logs** - Laravel logs are in `storage/logs/`
2. **Verify configuration** - Use `php artisan config:show`
3. **Test components** - Use artisan commands to test individual parts
4. **Review documentation** - Check other admin guides

## 🎯 Next Steps

- **[Deployment Guide](deployment.md)** - Production deployment checklist
- **[Environment Configuration](environment-config.md)** - Detailed configuration reference
- **[Queue Management](queue-management.md)** - Managing background jobs
- **[Monitoring](monitoring.md)** - System monitoring and health checks

---

**Next**: [Deployment Guide](deployment.md) - Production deployment best practices