# SSL Monitor v4 - Deployment Guide

**Target Server**: monitor.intermedien.at (ISPConfig managed)
**Deploy Path**: `/var/www/monitor.intermedien.at/web`
**User**: `default_deploy` (maps to `web6:client0`)
**Method**: Deployer.org with systemd services

---

## 📋 Prerequisites Checklist

- ✅ Deployer installed: `composer require --dev deployer/deployer`
- ✅ SSH key configured: `~/.ssh/ssl-monitor-deploy`
- ✅ SSH access working: `ssh -i ~/.ssh/ssl-monitor-deploy default_deploy@monitor.intermedien.at`
- ✅ Server has: PHP 8.4, Composer, Node.js 22, Redis 7.0.15
- ✅ Database ready with credentials in `.env`
- ✅ Systemd services configured (Horizon + Scheduler)

---

## 🚀 Server Preparation (First Time Only)

### Step 1: Backup Existing Installation (if migrating)

```bash
# SSH to server as root or web6
ssh root@monitor.intermedien.at

# Backup current installation
cd /var/www/monitor.intermedien.at/web
tar -czf ~/ssl-monitor-backup-$(date +%Y%m%d-%H%M%S).tar.gz .

# Backup database
mysqldump -u monitor -p monitor > ~/ssl-monitor-db-backup-$(date +%Y%m%d-%H%M%S).sql
```

### Step 2: Prepare Deployer Directory Structure

```bash
# SSH as default_deploy user
ssh -i ~/.ssh/ssl-monitor-deploy default_deploy@monitor.intermedien.at

cd /var/www/monitor.intermedien.at/web

# Create Deployer structure
mkdir -p releases shared shared/storage shared/.playwright

# If migrating from existing installation, copy important files
# Copy existing .env (you already have it)
# Copy existing storage if needed
cp -r OLD_INSTALLATION_PATH/storage/* shared/storage/ 2>/dev/null || true

# Set correct permissions
chmod -R 775 shared/storage
chmod 644 shared/.env
```

### Step 3: Verify .env in Shared Directory

The `.env` file should be at: `/var/www/monitor.intermedien.at/web/shared/.env`

**Important variables to verify**:
```bash
# Edit .env on server
nano /var/www/monitor.intermedien.at/web/shared/.env
```

Make sure these are set:
```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:SU5wjA6XaCh5kHN2DlO8jqBHJit2wXPL+A+O8Yg56Ys=
APP_URL=https://monitor.intermedien.at

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=monitor
DB_USERNAME=monitor
DB_PASSWORD=q48TUU3QaaGL$y

QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1

# JavaScript Content Fetcher Service
BROWSERSHOT_SERVICE_URL=http://127.0.0.1:3000
BROWSERSHOT_TIMEOUT=30
BROWSERSHOT_WAIT_SECONDS=5
```

### Step 4: Setup JavaScript Content Fetcher Service

**NEW**: We use a separate HTTP service for JavaScript rendering to avoid permission issues.

```bash
# SSH as root
ssh root@monitor.intermedien.at

# Create service directory
mkdir -p /opt/js-content-fetcher
cd /opt/js-content-fetcher

# Copy files from repository (services/js-content-fetcher/)
# - server.js
# - package.json

# Install dependencies
npm install

# Install Playwright Firefox browser
PLAYWRIGHT_BROWSERS_PATH=/var/www/monitor.intermedien.at/web/shared/.playwright \
  npx playwright install firefox

# Install systemd service
cp js-content-fetcher.service /etc/systemd/system/
systemctl daemon-reload
systemctl enable js-content-fetcher
systemctl start js-content-fetcher
systemctl status js-content-fetcher
```

**Service Configuration**: `/etc/systemd/system/js-content-fetcher.service`
```ini
[Unit]
Description=JavaScript Content Fetcher Service
After=network.target

[Service]
Type=simple
User=root
WorkingDirectory=/opt/js-content-fetcher
ExecStart=/usr/bin/node /opt/js-content-fetcher/server.js
Restart=always
RestartSec=5
StandardOutput=append:/var/log/js-content-fetcher.log
StandardError=append:/var/log/js-content-fetcher-error.log

[Install]
WantedBy=multi-user.target
```

### Step 5: Verify Systemd Services

The Horizon service configuration:

```bash
# /etc/systemd/system/ssl-monitor-horizon.service
[Unit]
Description=SSL Monitor Horizon Queue Worker
After=network.target redis.service mariadb.service

[Service]
Type=simple
User=web6
Group=client0
Restart=always
RestartSec=3
Environment="PATH=/usr/local/bin:/usr/bin:/bin"
Environment="HOME=/var/www/clients/client0/web6"
ExecStart=/usr/bin/php /var/www/monitor.intermedien.at/web/current/artisan horizon
WorkingDirectory=/var/www/monitor.intermedien.at/web/current
StandardOutput=append:/var/www/monitor.intermedien.at/web/shared/storage/logs/horizon.log
StandardError=append:/var/www/monitor.intermedien.at/web/shared/storage/logs/horizon-error.log

[Install]
WantedBy=multi-user.target
```

**Note**: No special browser-related environment variables needed - JavaScript rendering is handled by the separate HTTP service.

### Step 5: Test SSH Connection from Local Machine

```bash
# From your local development machine
ssh -i ~/.ssh/ssl-monitor-deploy default_deploy@monitor.intermedien.at "pwd"
# Should output: /var/www/clients/client0/web6
```

---

## 🎯 Deployment Process

### 1. Commit and Push Your Code

```bash
# From local development machine
cd /home/bonzo/code/ssl-monitor-v4

# Commit all changes
git add .
git commit -m "Ready for deployment with Deployer.org"
git push origin main
```

### 2. Dry Run (Test Without Executing)

```bash
./vendor/bin/dep deploy production --dry-run
```

This shows you what **would** happen without actually doing it.

### 3. First Deployment

```bash
./vendor/bin/dep deploy production -v
```

**Expected Output**:
```
✓ Preparing host(s) for deployment
✓ Determine release name
✓ Creating release directory
✓ Cloning repository
✓ Installing Composer dependencies
✓ Installing NPM dependencies
✓ Building production assets
✓ Installing Playwright browsers (first time: 2-3 minutes)
✓ Creating symbolic links to shared files and dirs
✓ Updating Chrome path in .env
✓ Creating storage link
✓ Clearing Laravel caches
✓ Optimizing Laravel caches
✓ Running database migrations
✓ Terminating Horizon gracefully
✓ Creating current symlink
✓ Restarting Horizon service
✓ Cleanup old releases
✓ Deployment Successful!
```

**Deployment time**: ~5-7 minutes (first time), ~3-4 minutes (subsequent deployments)

### 4. Verify Deployment

```bash
# Check application
curl https://monitor.intermedien.at

# Check Horizon
curl https://monitor.intermedien.at/horizon

# SSH and verify
ssh -i ~/.ssh/ssl-monitor-deploy default_deploy@monitor.intermedien.at

# Check current release
ls -la /var/www/monitor.intermedien.at/web/current
# Should be a symlink to latest release

# Check Horizon status
sudo systemctl status ssl-monitor-horizon

# Check logs
tail -f /var/www/monitor.intermedien.at/web/shared/storage/logs/laravel.log
```

---

## 🔄 Subsequent Deployments

After the first successful deployment, future deployments are simple:

```bash
# 1. Make changes in your code
# 2. Commit and push
git add .
git commit -m "Feature: XYZ"
git push origin main

# 3. Deploy
./vendor/bin/dep deploy production -v
```

That's it! Deployer handles everything automatically.

---

## ↩️ Rollback (If Needed)

If something goes wrong, rollback to the previous release:

```bash
./vendor/bin/dep rollback production
```

This instantly reverts to the previous working release (atomic symlink swap).

---

## 🛠️ Useful Commands

### View All Deployer Tasks
```bash
./vendor/bin/dep list
```

### SSH to Production Server
```bash
./vendor/bin/dep ssh production
# Or directly:
ssh -i ~/.ssh/ssl-monitor-deploy default_deploy@monitor.intermedien.at
```

### Check Deployed Releases
```bash
./vendor/bin/dep releases production
```

### Run Custom Artisan Commands
```bash
./vendor/bin/dep artisan 'horizon:status' production
./vendor/bin/dep artisan 'cache:clear' production
```

### Check Logs Remotely
```bash
./vendor/bin/dep run 'tail -n 50 {{deploy_path}}/shared/storage/logs/laravel.log' production
```

---

## 🔍 Troubleshooting

### Issue: JavaScript content fetcher service not working

**Check service status**:
```bash
ssh arm002 "systemctl status js-content-fetcher"
```

**Check logs**:
```bash
ssh arm002 "tail -f /var/log/js-content-fetcher.log"
ssh arm002 "tail -f /var/log/js-content-fetcher-error.log"
```

**Test service manually**:
```bash
ssh arm002 'curl -X POST http://127.0.0.1:3000/fetch \
  -H "Content-Type: application/json" \
  -d "{\"url\":\"https://www.redgas.at\",\"waitSeconds\":5}"'
```

**Restart service**:
```bash
ssh arm002 "systemctl restart js-content-fetcher && systemctl status js-content-fetcher"
```

### Issue: Playwright Firefox browser not found

**Check Firefox installation**:
```bash
ssh arm002 "ls -la /var/www/monitor.intermedien.at/web/shared/.playwright/firefox-*/firefox/firefox"
```

**Reinstall Firefox**:
```bash
ssh arm002 "cd /opt/js-content-fetcher && \
  PLAYWRIGHT_BROWSERS_PATH=/var/www/monitor.intermedien.at/web/shared/.playwright \
  npx playwright install firefox"
```

### Issue: Horizon not restarting

**Check status**:
```bash
ssh -i ~/.ssh/ssl-monitor-deploy default_deploy@monitor.intermedien.at
sudo systemctl status ssl-monitor-horizon
sudo journalctl -u ssl-monitor-horizon -n 50
```

**Manual restart**:
```bash
sudo systemctl restart ssl-monitor-horizon
```

### Issue: Permission denied errors

**Fix ownership**:
```bash
ssh root@monitor.intermedien.at
chown -R web6:client0 /var/www/monitor.intermedien.at/web/shared
chmod -R 775 /var/www/monitor.intermedien.at/web/shared/storage
```

### Issue: Deployment hangs

1. Check SSH connection works
2. Check disk space: `df -h`
3. Run with verbose mode: `./vendor/bin/dep deploy production -vvv`
4. Check if a deployment is locked: `./vendor/bin/dep deploy:unlock production`

### Issue: Database migration fails

**Check database connection**:
```bash
ssh -i ~/.ssh/ssl-monitor-deploy default_deploy@monitor.intermedien.at
cd /var/www/monitor.intermedien.at/web/current
php artisan tinker
>>> DB::connection()->getPdo();
```

**Run migrations manually**:
```bash
./vendor/bin/dep artisan 'migrate --force' production
```

---

## 📊 Deployment Checklist

### Pre-Deployment
- [ ] All tests passing locally: `./vendor/bin/sail artisan test`
- [ ] Code committed and pushed to `main`
- [ ] `.env` exists on server with correct credentials
- [ ] SSH key authentication working
- [ ] Backup taken (if migrating)

### During Deployment
- [ ] Run dry-run first: `./vendor/bin/dep deploy production --dry-run`
- [ ] Deploy: `./vendor/bin/dep deploy production -v`
- [ ] Monitor output for errors
- [ ] Wait for completion (~3-7 minutes)

### Post-Deployment
- [ ] Visit: https://monitor.intermedien.at
- [ ] Check Horizon: https://monitor.intermedien.at/horizon
- [ ] Verify monitors are running
- [ ] Check logs for errors
- [ ] Test critical workflows (login, dashboard, monitors)

---

## 🎨 Directory Structure After Deployment

```
/var/www/monitor.intermedien.at/web/
├── .dep/                  # Deployer metadata
├── releases/              # Release directories
│   ├── 1/                # Release 1 (timestamp)
│   ├── 2/                # Release 2 (timestamp)
│   └── 3/                # Release 3 (current, timestamp)
├── shared/               # Persistent shared data
│   ├── .env              # Environment file (persistent!)
│   ├── storage/          # Laravel storage (persistent!)
│   │   ├── app/
│   │   ├── framework/
│   │   └── logs/
│   └── .playwright/      # Playwright browsers (persistent!)
│       └── chromium-1194/
└── current -> releases/3 # Symlink to current release
```

**Key Points**:
- Each deployment creates a new release directory
- `current` symlink points to active release (atomic swap!)
- `shared` directory persists across deployments
- Old releases kept for quick rollback (last 3)

---

## 🔐 Security Notes

1. **SSH Key**: Keep `~/.ssh/ssl-monitor-deploy` private, never commit
2. **`.env.deployer`**: Already gitignored, never commit
3. **Sudo Access**: Limited to Horizon systemd commands only
4. **File Permissions**: Web server can't write to code directories
5. **Database Backups**: Always backup before major deployments

---

## 📚 References

- [Deployer.org Documentation](https://deployer.org/docs/8.x)
- [Laravel Deployment Guide](https://laravel.com/docs/12.x/deployment)
- [Horizon Documentation](https://laravel.com/docs/12.x/horizon)

---

## 🎉 Quick Start Summary

```bash
# 1. First time: Prepare server (see "Server Preparation" section above)

# 2. Deploy
cd /home/bonzo/code/ssl-monitor-v4
./vendor/bin/dep deploy production -v

# 3. Verify
curl https://monitor.intermedien.at
ssh -i ~/.ssh/ssl-monitor-deploy default_deploy@monitor.intermedien.at
sudo systemctl status ssl-monitor-horizon

# 4. If issues, rollback
./vendor/bin/dep rollback production
```

**That's it! Happy deploying! 🚀**
