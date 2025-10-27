# SSL Monitor v4 - Production Deployment Guide

## Overview
This guide covers deploying SSL Monitor v4 to an Ubuntu 24.04 ARM server with ISPConfig, using GitHub Actions for CI/CD with zero-downtime deployments.

---

## Deployment Architecture

### Strategy: Zero-Downtime Deployment with Symlinks
- Build assets in GitHub Actions (faster than ARM server builds)
- Deploy to timestamped release directories
- Symlink `current` → latest release
- Keep last 5 releases for quick rollback

### Technology Stack
- **Server**: Ubuntu 24.04 ARM with ISPConfig
- **Domain**: monitor.intermedien.at (SSL via ISPConfig/Let's Encrypt)
- **Database**: MariaDB (local)
- **Cache/Queue**: Redis (local)
- **Queue Worker**: systemd (native, no dependencies)
- **Process Manager**: systemd for Laravel Horizon
- **Mail**: External SMTP account
- **CI/CD**: GitHub Actions (free for public repo)

---

## Phase 1: Server Preparation

### 1.1 Directory Structure

The application uses a standard zero-downtime deployment structure:

```
/var/www/monitor.intermedien.at/
├── releases/
│   ├── 20250107_143022/  # timestamped releases
│   ├── 20250107_150815/
│   └── ...
├── current → releases/20250107_150815/  # symlink to active release
├── shared/
│   ├── .env
│   ├── storage/
│   │   ├── app/
│   │   ├── framework/
│   │   │   ├── cache/
│   │   │   ├── sessions/
│   │   │   └── views/
│   │   └── logs/
│   └── public/storage → ../../current/public/storage
└── repo/  # bare git repository (optional)
```

**Key Concepts:**
- **releases/**: Each deployment creates a new timestamped directory
- **current/**: Symlink pointing to the active release
- **shared/**: Contains files that persist across deployments (.env, storage)
- Atomic symlink swap ensures zero downtime during deployment

### 1.2 Required System Packages

Verify these packages are installed (ISPConfig typically includes them):

```bash
# PHP 8.2+ with required extensions
php -v
php -m | grep -E "mbstring|xml|curl|zip|gd|bcmath|redis|mysql"

# MariaDB 10.6+
mysql --version

# Redis
redis-cli --version

# Composer 2.x
composer --version

# Node.js 20+ & npm
node --version
npm --version

# Git
git --version
```

**Required PHP Extensions:**
- mbstring
- xml
- curl
- zip
- gd
- bcmath
- redis
- mysql (or pdo_mysql)

### 1.3 Create Deployment User

Create a dedicated user for deployments with limited permissions:

```bash
# Create deploy user
sudo useradd -m -s /bin/bash deploy

# Add to www-data group for file permissions
sudo usermod -aG www-data deploy

# Setup SSH key for GitHub Actions
sudo su - deploy
ssh-keygen -t ed25519 -C "github-actions@monitor.intermedien.at"

# Display public key (add to GitHub Actions as secret)
cat ~/.ssh/id_ed25519.pub

# Add public key to authorized_keys for GitHub Actions SSH access
cat ~/.ssh/id_ed25519.pub >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
```

**Security Note:** The deploy user should NOT have sudo privileges for general commands, only specific commands needed for deployment (PHP-FPM restart, etc.).

### 1.4 Database Setup

Create the production database and user:

```bash
# Login to MariaDB as root
sudo mysql -u root -p
```

```sql
-- Create database
CREATE DATABASE ssl_monitor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create dedicated user
CREATE USER 'ssl_monitor'@'localhost' IDENTIFIED BY 'secure-password-here';

-- Grant privileges
GRANT ALL PRIVILEGES ON ssl_monitor.* TO 'ssl_monitor'@'localhost';

-- Apply changes
FLUSH PRIVILEGES;

-- Verify
SHOW DATABASES;
SELECT User, Host FROM mysql.user WHERE User = 'ssl_monitor';

-- Exit
EXIT;
```

**Security Best Practices:**
- Use a strong, randomly generated password
- Store password securely in `/var/www/monitor.intermedien.at/shared/.env`
- Do NOT grant privileges to all databases (`*.*`)
- Limit connections to `localhost` only

### 1.5 Create Application Directory Structure

```bash
# Create base directory
sudo mkdir -p /var/www/monitor.intermedien.at/{releases,shared/storage}

# Create shared storage subdirectories
sudo mkdir -p /var/www/monitor.intermedien.at/shared/storage/{app,framework,logs}
sudo mkdir -p /var/www/monitor.intermedien.at/shared/storage/framework/{cache,sessions,views}
sudo mkdir -p /var/www/monitor.intermedien.at/shared/storage/app/{public,private}

# Set ownership
sudo chown -R deploy:www-data /var/www/monitor.intermedien.at

# Set permissions
sudo chmod -R 775 /var/www/monitor.intermedien.at/shared/storage
sudo chmod -R 755 /var/www/monitor.intermedien.at/releases
```

---

## Phase 2: Systemd Services Configuration

### 2.1 Laravel Horizon (Queue Worker)

Laravel Horizon manages all queue workers for SSL monitoring, email notifications, and background tasks.

Create `/etc/systemd/system/ssl-monitor-horizon.service`:

```ini
[Unit]
Description=SSL Monitor Horizon Queue Worker
After=network.target redis.service mariadb.service
Requires=redis.service mariadb.service

[Service]
Type=simple
User=deploy
Group=www-data
Restart=always
RestartSec=3
ExecStart=/usr/bin/php /var/www/monitor.intermedien.at/current/artisan horizon
StandardOutput=append:/var/www/monitor.intermedien.at/shared/storage/logs/horizon.log
StandardError=append:/var/www/monitor.intermedien.at/shared/storage/logs/horizon-error.log

# Security hardening
PrivateTmp=true
NoNewPrivileges=true

[Install]
WantedBy=multi-user.target
```

**Key Configuration Points:**
- **Restart=always**: Automatically restart if Horizon crashes
- **RestartSec=3**: Wait 3 seconds before restarting
- **After/Requires**: Ensure Redis and MariaDB are running first
- **Log files**: Located in shared storage for persistence across deployments

### 2.2 Laravel Scheduler

The scheduler runs periodic tasks (SSL checks, uptime monitoring, cleanup).

Create `/etc/systemd/system/ssl-monitor-scheduler.service`:

```ini
[Unit]
Description=SSL Monitor Laravel Scheduler
After=network.target

[Service]
Type=oneshot
User=deploy
Group=www-data
ExecStart=/usr/bin/php /var/www/monitor.intermedien.at/current/artisan schedule:run
```

Create `/etc/systemd/system/ssl-monitor-scheduler.timer`:

```ini
[Unit]
Description=Run SSL Monitor Laravel Scheduler every minute

[Timer]
OnCalendar=*:0/1
Persistent=true

[Install]
WantedBy=timers.target
```

**How It Works:**
- The timer triggers the service every minute
- Laravel's scheduler determines which tasks to run based on `routes/console.php`
- No cron configuration needed

### 2.3 Enable and Start Services

```bash
# Reload systemd to recognize new services
sudo systemctl daemon-reload

# Enable services to start on boot
sudo systemctl enable ssl-monitor-horizon
sudo systemctl enable ssl-monitor-scheduler.timer

# Start services
sudo systemctl start ssl-monitor-horizon
sudo systemctl start ssl-monitor-scheduler.timer

# Check status
sudo systemctl status ssl-monitor-horizon
sudo systemctl status ssl-monitor-scheduler.timer

# View Horizon logs
tail -f /var/www/monitor.intermedien.at/shared/storage/logs/horizon.log
```

**Common Commands:**
```bash
# Restart Horizon (after deployment)
sudo systemctl restart ssl-monitor-horizon

# Stop services
sudo systemctl stop ssl-monitor-horizon
sudo systemctl stop ssl-monitor-scheduler.timer

# View logs
journalctl -u ssl-monitor-horizon -f
journalctl -u ssl-monitor-scheduler -f
```

### 2.4 Grant Sudo Permissions for Deploy User

The deploy user needs to restart services during deployment. Create `/etc/sudoers.d/ssl-monitor-deploy`:

```bash
# Allow deploy user to restart specific services without password
deploy ALL=(ALL) NOPASSWD: /bin/systemctl restart ssl-monitor-horizon
deploy ALL=(ALL) NOPASSWD: /bin/systemctl restart php8.4-fpm
deploy ALL=(ALL) NOPASSWD: /bin/systemctl reload nginx
deploy ALL=(ALL) NOPASSWD: /bin/systemctl status ssl-monitor-horizon
```

Set correct permissions:
```bash
sudo chmod 0440 /etc/sudoers.d/ssl-monitor-deploy
sudo visudo -c  # Verify syntax
```

---

## Phase 3: Environment Configuration

### 3.1 Create Production `.env` File

Create `/var/www/monitor.intermedien.at/shared/.env`:

```bash
# ============================================
# Application Settings
# ============================================
APP_NAME="SSL Monitor"
APP_ENV=production
APP_KEY=base64:WILL_BE_GENERATED_BELOW
APP_DEBUG=false
APP_URL=https://monitor.intermedien.at

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
PHP_CLI_SERVER_WORKERS=4

# ============================================
# Security
# ============================================
BCRYPT_ROUNDS=12

# ============================================
# Logging
# ============================================
LOG_CHANNEL=daily
LOG_LEVEL=error
LOG_DEPRECATIONS_CHANNEL=null

# ============================================
# Database Configuration
# ============================================
DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ssl_monitor
DB_USERNAME=ssl_monitor
DB_PASSWORD=your-secure-password-here

# ============================================
# Session & Cache
# ============================================
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

CACHE_STORE=redis

# ============================================
# Queue Configuration
# ============================================
QUEUE_CONNECTION=redis
REDIS_QUEUE_CONNECTION=default
REDIS_QUEUE=default
REDIS_QUEUE_RETRY_AFTER=90

# Queue names used by application
QUEUE_IMMEDIATE=immediate
QUEUE_UPTIME=uptime
QUEUE_SSL=ssl
QUEUE_NOTIFICATIONS=notifications
QUEUE_CLEANUP=cleanup

# ============================================
# Redis Configuration
# ============================================
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# ============================================
# Mail Configuration (External SMTP)
# ============================================
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host.com
MAIL_PORT=587
MAIL_USERNAME=your-smtp-username
MAIL_PASSWORD=your-smtp-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@monitor.intermedien.at"
MAIL_FROM_NAME="${APP_NAME}"

# ============================================
# Laravel Horizon
# ============================================
HORIZON_DOMAIN=monitor.intermedien.at
HORIZON_PATH=horizon

# ============================================
# Optional: Failed Job Notifications
# Enable during initial deployment, disable later
# ============================================
QUEUE_FAILED_JOB_NOTIFICATION_ENABLED=true
QUEUE_FAILED_JOB_NOTIFICATION_EMAIL=admin@intermedien.at

# ============================================
# Optional: Uptime Monitor Slack Integration
# ============================================
UPTIME_MONITOR_SLACK_WEBHOOK_URL=

# ============================================
# Optional: Monitoring Thresholds
# ============================================
MAX_FAILED_JOBS=10

# ============================================
# Filesystem & Broadcasting
# ============================================
BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local

# ============================================
# Vite (for asset building)
# ============================================
VITE_APP_NAME="${APP_NAME}"
```

### 3.2 Generate Application Key

```bash
# Switch to deploy user
sudo su - deploy

# Navigate to shared directory
cd /var/www/monitor.intermedien.at/shared

# Generate key (requires PHP and Laravel)
# Temporarily create a minimal Laravel installation or use artisan from a release
php artisan key:generate

# Or generate manually:
php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
# Copy output to APP_KEY in .env
```

### 3.3 Secure Environment File

```bash
# Set restrictive permissions
sudo chown deploy:www-data /var/www/monitor.intermedien.at/shared/.env
sudo chmod 640 /var/www/monitor.intermedien.at/shared/.env
```

**Security Notes:**
- Never commit `.env` to version control
- Use strong, unique passwords for database and SMTP
- Regularly rotate sensitive credentials
- Keep backup of `.env` in secure location

---

## Phase 4: GitHub Repository Setup

### 4.1 Create GitHub Secrets

Navigate to your repository: **Settings → Secrets and variables → Actions → New repository secret**

Create the following secrets:

| Secret Name | Value | Description |
|-------------|-------|-------------|
| `SSH_HOST` | `monitor.intermedien.at` | Server hostname or IP |
| `SSH_PORT` | `22` | SSH port (default 22) |
| `SSH_USER` | `deploy` | Deployment user |
| `SSH_PRIVATE_KEY` | `[contents of ~/.ssh/id_ed25519]` | Private key from deploy user |
| `DEPLOYMENT_PATH` | `/var/www/monitor.intermedien.at` | Base deployment path |

**To get the private key:**
```bash
sudo su - deploy
cat ~/.ssh/id_ed25519
# Copy entire output including BEGIN and END lines
```

### 4.2 Create GitHub Actions Workflow

Create `.github/workflows/deploy.yml` in your repository:

```yaml
name: Deploy to Production

on:
  push:
    branches: [ main ]
  workflow_dispatch:  # Allow manual trigger from GitHub UI

jobs:
  tests:
    name: Run Tests
    runs-on: ubuntu-latest

    services:
      mariadb:
        image: mariadb:11
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: testing
        ports:
          - 3306:3306
        options: --health-cmd="healthcheck.sh --connect --innodb_initialized" --health-interval=10s --health-timeout=5s --health-retries=3

      redis:
        image: redis:alpine
        ports:
          - 6379:6379
        options: --health-cmd="redis-cli ping" --health-interval=10s --health-timeout=5s --health-retries=3

    steps:
      - name: Checkout Code
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          extensions: mbstring, xml, curl, zip, bcmath, redis, mysql
          coverage: none

      - name: Copy .env
        run: |
          cp .env.example .env
          sed -i 's/DB_CONNECTION=sqlite/DB_CONNECTION=mariadb/' .env
          sed -i 's/# DB_HOST=127.0.0.1/DB_HOST=127.0.0.1/' .env
          sed -i 's/# DB_DATABASE=laravel/DB_DATABASE=testing/' .env
          sed -i 's/# DB_USERNAME=root/DB_USERNAME=root/' .env
          sed -i 's/# DB_PASSWORD=/DB_PASSWORD=password/' .env

      - name: Install Composer Dependencies
        run: composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

      - name: Generate Application Key
        run: php artisan key:generate

      - name: Run Database Migrations
        run: php artisan migrate --force
        env:
          DB_CONNECTION: mariadb
          DB_HOST: 127.0.0.1
          DB_DATABASE: testing
          DB_USERNAME: root
          DB_PASSWORD: password

      - name: Run Tests
        run: php artisan test --parallel
        env:
          DB_CONNECTION: mariadb
          DB_HOST: 127.0.0.1
          DB_DATABASE: testing
          DB_USERNAME: root
          DB_PASSWORD: password
          REDIS_HOST: 127.0.0.1

  build:
    name: Build Assets
    needs: tests
    runs-on: ubuntu-latest

    steps:
      - name: Checkout Code
        uses: actions/checkout@v4

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: '20'
          cache: 'npm'

      - name: Install NPM Dependencies
        run: npm ci

      - name: Build Frontend Assets
        run: npm run build

      - name: Upload Build Artifacts
        uses: actions/upload-artifact@v4
        with:
          name: build-artifacts
          path: |
            public/build
            bootstrap/ssr
          retention-days: 1

  deploy:
    name: Deploy to Production
    needs: build
    runs-on: ubuntu-latest

    steps:
      - name: Checkout Code
        uses: actions/checkout@v4

      - name: Download Build Artifacts
        uses: actions/download-artifact@v4
        with:
          name: build-artifacts

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          extensions: mbstring, xml, curl, zip
          coverage: none

      - name: Install Composer Dependencies
        run: composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

      - name: Create Deployment Archive
        run: |
          tar -czf deployment.tar.gz \
            --exclude='.git' \
            --exclude='node_modules' \
            --exclude='tests' \
            --exclude='.env' \
            --exclude='storage/logs/*' \
            --exclude='storage/framework/cache/*' \
            --exclude='storage/framework/sessions/*' \
            --exclude='storage/framework/views/*' \
            .

      - name: Copy Deployment Archive to Server
        uses: appleboy/scp-action@v0.1.7
        with:
          host: ${{ secrets.SSH_HOST }}
          port: ${{ secrets.SSH_PORT }}
          username: ${{ secrets.SSH_USER }}
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          source: deployment.tar.gz
          target: /tmp/

      - name: Deploy to Server
        uses: appleboy/ssh-action@v1.0.0
        env:
          RELEASE_NAME: ${{ github.sha }}_${{ github.run_number }}
        with:
          host: ${{ secrets.SSH_HOST }}
          port: ${{ secrets.SSH_PORT }}
          username: ${{ secrets.SSH_USER }}
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          envs: RELEASE_NAME
          script: |
            set -e

            DEPLOY_PATH="${{ secrets.DEPLOYMENT_PATH }}"
            RELEASE_PATH="$DEPLOY_PATH/releases/$RELEASE_NAME"
            CURRENT_PATH="$DEPLOY_PATH/current"
            SHARED_PATH="$DEPLOY_PATH/shared"

            echo "🚀 Starting deployment: $RELEASE_NAME"

            # Create release directory
            mkdir -p "$RELEASE_PATH"

            # Extract deployment archive
            echo "📦 Extracting deployment archive..."
            tar -xzf /tmp/deployment.tar.gz -C "$RELEASE_PATH"
            rm /tmp/deployment.tar.gz

            # Create symlinks to shared resources
            echo "🔗 Creating symlinks to shared resources..."
            rm -rf "$RELEASE_PATH/storage"
            ln -nfs "$SHARED_PATH/storage" "$RELEASE_PATH/storage"

            ln -nfs "$SHARED_PATH/.env" "$RELEASE_PATH/.env"

            # Set permissions
            echo "🔐 Setting permissions..."
            chown -R deploy:www-data "$RELEASE_PATH"
            chmod -R 755 "$RELEASE_PATH"
            chmod -R 775 "$RELEASE_PATH/bootstrap/cache"

            # Run migrations
            echo "📊 Running database migrations..."
            cd "$RELEASE_PATH"
            php artisan migrate --force

            # Optimize Laravel
            echo "⚡ Optimizing Laravel..."
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            php artisan event:cache

            # Switch symlink atomically (zero downtime!)
            echo "🔄 Switching to new release..."
            ln -nfs "$RELEASE_PATH" "$CURRENT_PATH"_tmp
            mv -Tf "$CURRENT_PATH"_tmp "$CURRENT_PATH"

            # Restart services
            echo "♻️  Restarting services..."
            sudo systemctl restart ssl-monitor-horizon
            sudo systemctl restart php8.4-fpm
            sudo systemctl reload nginx

            # Cleanup old releases (keep last 5)
            echo "🧹 Cleaning up old releases..."
            cd "$DEPLOY_PATH/releases"
            ls -t | tail -n +6 | xargs -r rm -rf

            echo "✅ Deployment completed successfully!"
            echo "📍 Release: $RELEASE_NAME"
            echo "🌐 URL: https://monitor.intermedien.at"

      - name: Notify on Failure
        if: failure()
        run: |
          echo "❌ Deployment failed!"
          echo "Check the Actions tab for detailed logs"
```

### 4.3 Workflow Explanation

**Three Jobs:**

1. **Tests Job**:
   - Runs on every push to `main`
   - Sets up MariaDB and Redis services
   - Installs dependencies
   - Runs full test suite
   - **Must pass before proceeding to build**

2. **Build Job**:
   - Runs after tests pass
   - Builds frontend assets (Vue.js, CSS)
   - Uploads built assets as artifacts
   - **Building in CI is faster than on ARM server**

3. **Deploy Job**:
   - Runs after build completes
   - Downloads built assets
   - Creates deployment archive
   - Copies to server via SCP
   - Executes deployment script via SSH
   - **Zero downtime via atomic symlink swap**

**Key Features:**
- ✅ Automated testing before deployment
- ✅ Build artifacts cached between jobs
- ✅ Zero-downtime deployment
- ✅ Automatic rollback on failure (keeps old release)
- ✅ Cleanup of old releases (keeps last 5)
- ✅ Service restarts handled automatically

---

## Phase 5: ISPConfig Configuration

### 5.1 Create Website in ISPConfig

1. **Login to ISPConfig** (usually at `https://your-server:8080`)

2. **Navigate to**: Sites → Website → Add new website

3. **Configure website**:
   - **Domain**: `monitor.intermedien.at`
   - **Auto-subdomain**: `www` or `*` (if you want wildcard)
   - **PHP**: PHP-FPM
   - **PHP Version**: PHP 8.4
   - **Document Root**: `/var/www/monitor.intermedien.at/current/public`
   - **SSL**: Let's Encrypt SSL (check the box)
   - **Let's Encrypt**: Enable
   - **SSL Domain**: monitor.intermedien.at, www.monitor.intermedien.at

4. **Options Tab**:
   - PHP: PHP-FPM
   - HTTP/2: Enabled
   - Perl: Disabled (not needed)
   - Python: Disabled (not needed)
   - Ruby: Disabled (not needed)

5. **Click Save**

### 5.2 Custom Nginx Configuration

In ISPConfig, edit the website and add to **Nginx Directives** field:

```nginx
# Laravel public directory
root /var/www/monitor.intermedien.at/current/public;
index index.php index.html;

# Handle Laravel routing
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

# PHP-FPM configuration
location ~ \.php$ {
    try_files $uri =404;
    fastcgi_pass unix:/var/lib/php8.4-fpm/monitor.intermedien.at.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
    fastcgi_read_timeout 300;
}

# Deny access to hidden files
location ~ /\. {
    deny all;
}

# Security headers
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;

# Laravel Horizon Dashboard (accessible to authenticated users)
location /horizon {
    try_files $uri $uri/ /index.php?$query_string;
}

# Deny access to sensitive files
location ~ /\.(env|git|htaccess) {
    deny all;
}
```

### 5.3 PHP-FPM Pool Configuration (Optional)

For better performance, configure PHP-FPM pool settings:

1. **Navigate to**: Sites → PHP-FPM
2. **Find your website pool**
3. **Edit and configure**:

```ini
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 10
pm.max_spare_servers = 20
pm.max_requests = 500
pm.process_idle_timeout = 10s
```

**Explanation:**
- `pm.max_children = 50`: Maximum worker processes
- `pm.start_servers = 10`: Processes on startup
- `pm.min_spare_servers = 10`: Minimum idle processes
- `pm.max_spare_servers = 20`: Maximum idle processes
- `pm.max_requests = 500`: Recycle process after 500 requests

**Adjust based on server resources:**
- Low RAM (2GB): Use smaller values (max_children = 20)
- High RAM (8GB+): Use larger values (max_children = 100)

### 5.4 Verify Configuration

```bash
# Test Nginx configuration
sudo nginx -t

# Reload Nginx if test passes
sudo systemctl reload nginx

# Check PHP-FPM status
sudo systemctl status php8.4-fpm

# Verify website is accessible
curl -I https://monitor.intermedien.at
```

---

## Phase 6: Initial Manual Deployment

Before setting up automated deployments, perform a manual first deployment to verify everything works.

### 6.1 Clone Repository

```bash
# Switch to deploy user
sudo su - deploy

# Navigate to deployment path
cd /var/www/monitor.intermedien.at

# Clone repository into first release
git clone https://github.com/yourusername/ssl-monitor-v4.git releases/initial

# Navigate to release
cd releases/initial
```

### 6.2 Install Dependencies

```bash
# Install PHP dependencies (production only)
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Install Node.js dependencies
npm ci

# Build frontend assets
npm run build
```

### 6.3 Configure Environment

```bash
# Create symlink to shared .env
ln -nfs ../../shared/.env .env

# Create symlink to shared storage
rm -rf storage
ln -nfs ../../shared/storage storage

# Verify .env is readable
cat .env | head -n 5
```

### 6.4 Run Migrations

```bash
# Generate application key (if not already in .env)
php artisan key:generate

# Run database migrations
php artisan migrate --force

# (Optional) Seed initial data
# php artisan db:seed --force
```

### 6.5 Optimize Laravel

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Cache events
php artisan event:cache
```

### 6.6 Set Permissions

```bash
# Ensure correct ownership
cd /var/www/monitor.intermedien.at
sudo chown -R deploy:www-data releases/initial
sudo chmod -R 755 releases/initial
sudo chmod -R 775 releases/initial/bootstrap/cache
```

### 6.7 Activate Release

```bash
# Create symlink to current
cd /var/www/monitor.intermedien.at
ln -nfs releases/initial current

# Restart services
sudo systemctl restart ssl-monitor-horizon
sudo systemctl restart php8.4-fpm
sudo systemctl reload nginx
```

### 6.8 Verify Deployment

```bash
# Check application is accessible
curl -I https://monitor.intermedien.at

# Check Horizon is running
curl -I https://monitor.intermedien.at/horizon

# View application logs
tail -f /var/www/monitor.intermedien.at/shared/storage/logs/laravel.log

# Check Horizon logs
tail -f /var/www/monitor.intermedien.at/shared/storage/logs/horizon.log

# Verify queue workers are processing
sudo systemctl status ssl-monitor-horizon
```

### 6.9 Test Application

1. **Visit**: https://monitor.intermedien.at
2. **Register/Login**: Create first user account
3. **Add Website**: Test SSL monitoring functionality
4. **Check Horizon**: https://monitor.intermedien.at/horizon
5. **Monitor Logs**: Watch for any errors

---

## Phase 7: Automated Deployments

Once manual deployment works, enable automated deployments via GitHub Actions.

### 7.1 Push Workflow to Repository

```bash
# On your local machine (not server)
cd /path/to/ssl-monitor-v4

# Ensure workflow file exists
ls -la .github/workflows/deploy.yml

# Commit and push
git add .github/workflows/deploy.yml
git commit -m "Add GitHub Actions deployment workflow"
git push origin main
```

### 7.2 Monitor First Automated Deployment

1. **Go to GitHub**: Your repository → Actions tab
2. **Watch workflow**: Should trigger automatically on push to `main`
3. **Monitor progress**:
   - Tests job (5-10 minutes)
   - Build job (3-5 minutes)
   - Deploy job (2-3 minutes)

### 7.3 Verify Automated Deployment

```bash
# On server, check latest release
ls -lah /var/www/monitor.intermedien.at/current

# Should point to new timestamped release
# current -> releases/abc123_456

# Check services restarted
sudo systemctl status ssl-monitor-horizon

# View deployment logs
tail -n 100 /var/www/monitor.intermedien.at/shared/storage/logs/laravel.log
```

### 7.4 Deployment Workflow

**Normal Workflow:**
1. Make code changes locally
2. Commit and push to `main` branch
3. GitHub Actions automatically:
   - Runs tests
   - Builds assets
   - Deploys to server
   - Restarts services
4. Zero downtime for users

**Manual Trigger:**
- Go to GitHub Actions → Deploy to Production → Run workflow
- Useful for re-deploying without new commits

---

## Phase 8: Rollback Procedures

### 8.1 Create Rollback Script

Create `/var/www/monitor.intermedien.at/rollback.sh`:

```bash
#!/bin/bash
set -e

DEPLOY_PATH="/var/www/monitor.intermedien.at"
CURRENT_PATH="$DEPLOY_PATH/current"
RELEASES_PATH="$DEPLOY_PATH/releases"

echo "🔄 SSL Monitor Rollback Script"
echo "================================"

# Get current release
CURRENT_RELEASE=$(readlink "$CURRENT_PATH" | xargs basename)
echo "Current release: $CURRENT_RELEASE"

# Get previous release
PREVIOUS_RELEASE=$(ls -t "$RELEASES_PATH" | grep -v "$CURRENT_RELEASE" | head -n1)

if [ -z "$PREVIOUS_RELEASE" ]; then
    echo "❌ No previous release found!"
    echo "Available releases:"
    ls -1t "$RELEASES_PATH"
    exit 1
fi

echo "Previous release: $PREVIOUS_RELEASE"
echo ""
read -p "Rollback to $PREVIOUS_RELEASE? (y/N) " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Rollback cancelled"
    exit 0
fi

echo "🔄 Rolling back to: $PREVIOUS_RELEASE"

# Switch symlink atomically
ln -nfs "$RELEASES_PATH/$PREVIOUS_RELEASE" "$CURRENT_PATH"_tmp
mv -Tf "$CURRENT_PATH"_tmp "$CURRENT_PATH"

# Clear cache (important!)
cd "$CURRENT_PATH"
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Re-cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart services
echo "♻️  Restarting services..."
sudo systemctl restart ssl-monitor-horizon
sudo systemctl restart php8.4-fpm
sudo systemctl reload nginx

echo ""
echo "✅ Rollback completed successfully!"
echo "Current release: $(readlink $CURRENT_PATH | xargs basename)"
echo "🌐 URL: https://monitor.intermedien.at"
```

Make executable:
```bash
chmod +x /var/www/monitor.intermedien.at/rollback.sh
```

### 8.2 Perform Rollback

```bash
# Switch to deploy user
sudo su - deploy

# Run rollback script
cd /var/www/monitor.intermedien.at
./rollback.sh

# Verify rollback
ls -lah current
curl -I https://monitor.intermedien.at
```

### 8.3 Manual Rollback (Alternative)

```bash
# Switch to deploy user
sudo su - deploy
cd /var/www/monitor.intermedien.at

# List available releases
ls -lt releases/

# Switch to specific release
ln -nfs releases/SPECIFIC_RELEASE current

# Clear caches
cd current
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart services
sudo systemctl restart ssl-monitor-horizon
sudo systemctl restart php8.4-fpm
sudo systemctl reload nginx
```

---

## Phase 9: Monitoring & Maintenance

### 9.1 Application Health Monitoring

**Create Health Check Endpoint**

Add to `routes/web.php`:

```php
Route::get('/health', function () {
    try {
        $dbStatus = DB::connection()->getPdo() ? 'connected' : 'disconnected';
    } catch (\Exception $e) {
        $dbStatus = 'error: ' . $e->getMessage();
    }

    try {
        $redisStatus = Redis::connection()->ping() ? 'connected' : 'disconnected';
    } catch (\Exception $e) {
        $redisStatus = 'error: ' . $e->getMessage();
    }

    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'services' => [
            'database' => $dbStatus,
            'redis' => $redisStatus,
            'horizon' => 'check /horizon/api/stats',
        ],
    ]);
});
```

**Test Health Check:**
```bash
curl https://monitor.intermedien.at/health | jq
```

### 9.2 Log Monitoring

**View Application Logs:**
```bash
# Laravel application log
tail -f /var/www/monitor.intermedien.at/shared/storage/logs/laravel.log

# Horizon queue worker log
tail -f /var/www/monitor.intermedien.at/shared/storage/logs/horizon.log

# Scheduler log
tail -f /var/www/monitor.intermedien.at/shared/storage/logs/scheduler.log

# Nginx access log
sudo tail -f /var/log/nginx/monitor.intermedien.at.access.log

# Nginx error log
sudo tail -f /var/log/nginx/monitor.intermedien.at.error.log

# PHP-FPM error log
sudo tail -f /var/log/php8.4-fpm/error.log

# Systemd service logs
journalctl -u ssl-monitor-horizon -f
```

**Log Rotation:**

ISPConfig typically handles log rotation, but verify:
```bash
cat /etc/logrotate.d/nginx
cat /etc/logrotate.d/php8.4-fpm
```

### 9.3 Queue Monitoring

**Monitor Horizon Dashboard:**
- URL: https://monitor.intermedien.at/horizon
- Shows: Active jobs, failed jobs, metrics, throughput

**Monitor via CLI:**
```bash
# Check Horizon status
sudo systemctl status ssl-monitor-horizon

# View queue statistics
cd /var/www/monitor.intermedien.at/current
php artisan horizon:status

# List failed jobs
php artisan queue:failed

# Retry failed job
php artisan queue:retry <job-id>

# Retry all failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```

### 9.4 Performance Monitoring

**Monitor Server Resources:**
```bash
# CPU and Memory usage
htop

# Disk usage
df -h

# Check MariaDB status
sudo systemctl status mariadb
mysqladmin -u root -p status

# Check Redis status
redis-cli info stats
redis-cli info memory
```

**Monitor PHP-FPM Pool:**
```bash
# Check PHP-FPM pool status
sudo systemctl status php8.4-fpm

# View pool statistics (if status page enabled)
# Add to PHP-FPM pool: pm.status_path = /status
curl http://localhost/status?full
```

### 9.5 Database Maintenance

**Regular Tasks:**

```bash
# Optimize database tables
mysql -u ssl_monitor -p ssl_monitor -e "OPTIMIZE TABLE websites, monitors, team_members;"

# Check database size
mysql -u ssl_monitor -p ssl_monitor -e "
SELECT
    table_schema AS 'Database',
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)'
FROM information_schema.tables
WHERE table_schema = 'ssl_monitor'
GROUP BY table_schema;
"

# Cleanup old failed jobs (older than 7 days)
mysql -u ssl_monitor -p ssl_monitor -e "
DELETE FROM failed_jobs WHERE failed_at < DATE_SUB(NOW(), INTERVAL 7 DAY);
"
```

**Database Backups:**

ISPConfig handles backups, but you can create manual backups:

```bash
# Create backup
mysqldump -u ssl_monitor -p ssl_monitor > /var/www/monitor.intermedien.at/backup_$(date +%Y%m%d_%H%M%S).sql

# Create compressed backup
mysqldump -u ssl_monitor -p ssl_monitor | gzip > /var/www/monitor.intermedien.at/backup_$(date +%Y%m%d_%H%M%S).sql.gz

# Restore backup
mysql -u ssl_monitor -p ssl_monitor < backup_20250107_143022.sql
```

### 9.6 Failed Job Email Notifications

If enabled in `.env`, you'll receive emails for failed queue jobs.

**To enable:**
```bash
QUEUE_FAILED_JOB_NOTIFICATION_ENABLED=true
QUEUE_FAILED_JOB_NOTIFICATION_EMAIL=admin@intermedien.at
```

**To disable after initial deployment:**
```bash
QUEUE_FAILED_JOB_NOTIFICATION_ENABLED=false
```

Then restart Horizon:
```bash
sudo systemctl restart ssl-monitor-horizon
```

---

## Phase 10: Security Best Practices

### 10.1 File Permissions

**Correct permissions:**
```bash
# Application files
sudo chown -R deploy:www-data /var/www/monitor.intermedien.at
sudo chmod -R 755 /var/www/monitor.intermedien.at

# Storage directory
sudo chmod -R 775 /var/www/monitor.intermedien.at/shared/storage

# .env file
sudo chmod 640 /var/www/monitor.intermedien.at/shared/.env

# Sensitive files
sudo chmod 600 /var/www/monitor.intermedien.at/shared/.env
sudo chmod 600 /home/deploy/.ssh/id_ed25519
```

### 10.2 Firewall Configuration

Ensure only necessary ports are open:
```bash
# Check current firewall rules
sudo ufw status

# Should allow:
# - 22 (SSH)
# - 80 (HTTP)
# - 443 (HTTPS)
# - 8080 (ISPConfig, restrict to specific IPs)

# Block unnecessary ports
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### 10.3 Horizon Dashboard Protection

**Option 1: Authentication via Middleware**

Update `config/horizon.php`:
```php
'middleware' => ['web', 'auth'],
```

This requires login to access Horizon dashboard.

**Option 2: IP Whitelisting**

Add to Nginx configuration:
```nginx
location /horizon {
    allow 123.123.123.123;  # Your IP
    deny all;
    try_files $uri $uri/ /index.php?$query_string;
}
```

**Option 3: HTTP Basic Auth**

Create password file:
```bash
sudo htpasswd -c /etc/nginx/.htpasswd admin
```

Add to Nginx configuration:
```nginx
location /horizon {
    auth_basic "Restricted Access";
    auth_basic_user_file /etc/nginx/.htpasswd;
    try_files $uri $uri/ /index.php?$query_string;
}
```

### 10.4 SSL/TLS Configuration

ISPConfig handles SSL certificates via Let's Encrypt, but verify:

```bash
# Check SSL certificate
openssl s_client -connect monitor.intermedien.at:443 -servername monitor.intermedien.at

# Check certificate expiry
openssl s_client -connect monitor.intermedien.at:443 -servername monitor.intermedien.at 2>/dev/null | openssl x509 -noout -dates

# Test SSL configuration
curl -I https://monitor.intermedien.at
```

**SSL Best Practices:**
- ✅ Auto-renewal enabled (Let's Encrypt)
- ✅ HTTP → HTTPS redirect (configured in ISPConfig)
- ✅ HSTS header (add to Nginx config if needed)
- ✅ Strong cipher suites (ISPConfig default is good)

### 10.5 Regular Security Updates

```bash
# Update system packages
sudo apt update
sudo apt upgrade -y

# Update PHP packages
sudo apt upgrade php8.4-* -y

# Restart services after updates
sudo systemctl restart php8.4-fpm
sudo systemctl restart nginx
sudo systemctl restart mariadb
sudo systemctl restart ssl-monitor-horizon
```

**Schedule regular updates:**
- Weekly: Check for security updates
- Monthly: Full system update
- Quarterly: Review access logs for suspicious activity

---

## Troubleshooting

### Common Issues

#### 1. Deployment Fails: "Permission Denied"

**Symptoms:** GitHub Actions fails with permission errors

**Solution:**
```bash
# Fix ownership
sudo chown -R deploy:www-data /var/www/monitor.intermedien.at

# Fix permissions
sudo chmod -R 755 /var/www/monitor.intermedien.at
sudo chmod -R 775 /var/www/monitor.intermedien.at/shared/storage

# Verify deploy user can write
sudo su - deploy
touch /var/www/monitor.intermedien.at/test
rm /var/www/monitor.intermedien.at/test
```

#### 2. Horizon Not Processing Jobs

**Symptoms:** Jobs stuck in queue, not processing

**Solution:**
```bash
# Check Horizon status
sudo systemctl status ssl-monitor-horizon

# Restart Horizon
sudo systemctl restart ssl-monitor-horizon

# Check logs
tail -f /var/www/monitor.intermedien.at/shared/storage/logs/horizon.log

# Verify Redis connection
redis-cli ping
# Should return: PONG

# Clear failed jobs
cd /var/www/monitor.intermedien.at/current
php artisan queue:flush
```

#### 3. 500 Internal Server Error After Deployment

**Symptoms:** Website returns 500 error after deployment

**Solution:**
```bash
# Check Laravel logs
tail -f /var/www/monitor.intermedien.at/shared/storage/logs/laravel.log

# Check Nginx error logs
sudo tail -f /var/log/nginx/error.log

# Clear all caches
cd /var/www/monitor.intermedien.at/current
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Re-cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Check .env file
cat /var/www/monitor.intermedien.at/shared/.env

# Verify permissions
sudo chown -R deploy:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Restart PHP-FPM
sudo systemctl restart php8.4-fpm
```

#### 4. Database Connection Failed

**Symptoms:** "SQLSTATE[HY000] [2002] Connection refused"

**Solution:**
```bash
# Check MariaDB status
sudo systemctl status mariadb

# Start MariaDB if stopped
sudo systemctl start mariadb

# Test database connection
mysql -u ssl_monitor -p ssl_monitor -e "SELECT 1;"

# Verify .env database credentials
cat /var/www/monitor.intermedien.at/shared/.env | grep DB_

# Check MariaDB logs
sudo tail -f /var/log/mysql/error.log
```

#### 5. Asset Files Not Loading (404)

**Symptoms:** CSS/JS files return 404, page looks unstyled

**Solution:**
```bash
# Verify assets were built
ls -la /var/www/monitor.intermedien.at/current/public/build

# Rebuild assets manually
cd /var/www/monitor.intermedien.at/current
npm ci
npm run build

# Clear view cache
php artisan view:clear
php artisan view:cache

# Check Nginx configuration
sudo nginx -t

# Restart Nginx
sudo systemctl reload nginx
```

#### 6. Symlink Not Working

**Symptoms:** Deployment succeeds but site shows old version

**Solution:**
```bash
# Check current symlink
ls -lah /var/www/monitor.intermedien.at/current

# Manually recreate symlink
cd /var/www/monitor.intermedien.at
rm current
ln -nfs releases/LATEST_RELEASE current

# Verify ISPConfig document root
# Should point to: /var/www/monitor.intermedien.at/current/public
```

#### 7. Scheduler Not Running

**Symptoms:** Scheduled tasks (SSL checks, uptime monitoring) not executing

**Solution:**
```bash
# Check timer status
sudo systemctl status ssl-monitor-scheduler.timer

# Check service status
sudo systemctl status ssl-monitor-scheduler.service

# View recent executions
journalctl -u ssl-monitor-scheduler.service -n 50

# Restart timer
sudo systemctl restart ssl-monitor-scheduler.timer

# Manually test schedule
cd /var/www/monitor.intermedien.at/current
php artisan schedule:run
```

---

## Maintenance Commands Reference

### Daily Operations

```bash
# View application status
sudo systemctl status ssl-monitor-horizon
curl -I https://monitor.intermedien.at/health

# Check logs
tail -f /var/www/monitor.intermedien.at/shared/storage/logs/laravel.log

# Monitor Horizon dashboard
# Visit: https://monitor.intermedien.at/horizon
```

### After Deployment

```bash
# Clear caches (usually done automatically)
cd /var/www/monitor.intermedien.at/current
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart services
sudo systemctl restart ssl-monitor-horizon
sudo systemctl restart php8.4-fpm
sudo systemctl reload nginx
```

### Emergency Rollback

```bash
# Quick rollback
cd /var/www/monitor.intermedien.at
./rollback.sh

# Or manual rollback
ls -t releases/
ln -nfs releases/PREVIOUS_RELEASE current
sudo systemctl restart ssl-monitor-horizon
sudo systemctl restart php8.4-fpm
sudo systemctl reload nginx
```

### Database Operations

```bash
# Run migrations
cd /var/www/monitor.intermedien.at/current
php artisan migrate --force

# Rollback last migration
php artisan migrate:rollback --step=1

# Create backup
mysqldump -u ssl_monitor -p ssl_monitor | gzip > backup_$(date +%Y%m%d).sql.gz
```

### Queue Management

```bash
# View failed jobs
php artisan queue:failed

# Retry specific job
php artisan queue:retry <job-id>

# Retry all failed jobs
php artisan queue:retry all

# Clear all failed jobs
php artisan queue:flush

# Restart queue workers
sudo systemctl restart ssl-monitor-horizon
```

---

## Summary

### What You Now Have

✅ **Zero-Downtime Deployments**: Atomic symlink swaps ensure no user sees errors
✅ **Automated CI/CD**: Push to `main` → tests → build → deploy automatically
✅ **Fast Builds**: Assets built in GitHub Actions (faster than ARM server)
✅ **Easy Rollback**: Keep 5 releases, rollback in seconds
✅ **Reliable Queue Processing**: systemd-managed Horizon with auto-restart
✅ **Automated Scheduling**: systemd timers run Laravel scheduler every minute
✅ **Health Monitoring**: Built-in health check endpoint
✅ **Security**: Proper file permissions, secure .env, optional failed job notifications
✅ **Maintenance-Friendly**: Clear logs, easy debugging, comprehensive documentation

### Deployment Workflow

1. **Develop locally** → make changes, test
2. **Push to GitHub** → `git push origin main`
3. **GitHub Actions**:
   - ✅ Run tests (must pass)
   - ✅ Build assets
   - ✅ Deploy to server
4. **Server automatically**:
   - ✅ Extract new release
   - ✅ Run migrations
   - ✅ Swap symlink (zero downtime!)
   - ✅ Restart services
   - ✅ Cleanup old releases
5. **Users** → no downtime, instant updates

### Key URLs

- **Application**: https://monitor.intermedien.at
- **Horizon Dashboard**: https://monitor.intermedien.at/horizon
- **Health Check**: https://monitor.intermedien.at/health
- **GitHub Actions**: https://github.com/yourusername/ssl-monitor-v4/actions

### Support & Resources

- **Laravel Documentation**: https://laravel.com/docs/12.x
- **Laravel Horizon**: https://laravel.com/docs/12.x/horizon
- **GitHub Actions**: https://docs.github.com/en/actions
- **ISPConfig**: https://www.ispconfig.org/documentation/

---

**Document Version**: 1.0
**Last Updated**: January 7, 2025
**For**: SSL Monitor v4
**Server**: monitor.intermedien.at (Ubuntu 24.04 ARM)

---
