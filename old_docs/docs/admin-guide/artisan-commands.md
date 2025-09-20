# Artisan Commands Reference

Complete reference for all SSL Monitor artisan commands for system administration and maintenance.

## 🎯 Overview

SSL Monitor includes several artisan commands for administration, monitoring, and maintenance. This guide covers all available commands with usage examples.

## 🔍 SSL Monitoring Commands

### `ssl:check-all`

Manually run SSL certificate checks for all monitored websites.

```bash
# Basic usage - check all websites
php artisan ssl:check-all

# Force check even if recently checked
php artisan ssl:check-all --force

# Check specific user's websites only  
php artisan ssl:check-all --user=user@example.com

# Verbose output with detailed information
php artisan ssl:check-all --verbose
```

#### Options
- `--force` - Skip recent check filtering, check all websites
- `--user=email` - Only check websites belonging to specific user
- `--verbose` - Show detailed progress and results

#### Examples
```bash
# Daily maintenance check
php artisan ssl:check-all

# Emergency check after server changes
php artisan ssl:check-all --force

# Debug specific user's SSL issues
php artisan ssl:check-all --user=admin@company.com --verbose
```

#### Output Example
```
SSL Certificate Checker
=======================

Checking SSL certificates for 5 websites...

✓ example.com - Valid (expires in 45 days)
✓ shop.example.com - Valid (expires in 23 days) 
⚠ blog.example.com - Expiring soon (expires in 12 days)
✗ old.example.com - Expired (expired 5 days ago)
✗ test.example.com - Connection failed

Summary:
- Total websites: 5
- Valid certificates: 2
- Expiring soon: 1  
- Issues found: 2
- Checks completed: 5
```

## 📊 Queue Management Commands

### Standard Laravel Queue Commands

```bash
# Start queue worker for SSL monitoring
php artisan queue:work --queue=ssl-monitoring

# Start worker with specific options
php artisan queue:work --queue=ssl-monitoring --sleep=3 --tries=3 --timeout=60

# Monitor queue status
php artisan queue:monitor ssl-monitoring

# Clear failed jobs
php artisan queue:clear ssl-monitoring

# Restart all queue workers
php artisan queue:restart

# Show queue statistics
php artisan queue:stats
```

#### Production Queue Worker
```bash
# Recommended production command
php artisan queue:work redis \
  --queue=ssl-monitoring \
  --sleep=3 \
  --tries=3 \
  --max-time=3600 \
  --timeout=60 \
  --memory=512
```

### Queue Monitoring

```bash
# Monitor failed jobs
php artisan queue:failed

# Retry failed job by ID
php artisan queue:retry 1

# Retry all failed jobs
php artisan queue:retry all

# Clear all failed jobs  
php artisan queue:flush
```

## ⏰ Scheduler Commands

### `schedule:run`

Execute scheduled tasks (used by cron).

```bash
# Run scheduled tasks (called by cron)
php artisan schedule:run

# Show scheduled tasks
php artisan schedule:list

# Test scheduled tasks without running
php artisan schedule:test
```

#### Cron Configuration
```bash
# Add to crontab (www-data user)
* * * * * cd /var/www/ssl-monitor && php artisan schedule:run >> /dev/null 2>&1
```

### Scheduled Task Details

SSL Monitor schedules these tasks:
- **Daily SSL checks** - 6:00 AM daily (`ssl:check-all`)
- **Queue maintenance** - Every hour (cleanup old jobs)
- **Log rotation** - Daily at midnight
- **Cache cleanup** - Daily at 2:00 AM

## 🗄️ Database Commands

### Migration Commands

```bash
# Run pending migrations
php artisan migrate

# Run migrations in production (no confirmation)
php artisan migrate --force

# Show migration status
php artisan migrate:status

# Rollback last migration batch
php artisan migrate:rollback

# Reset all migrations (destructive)
php artisan migrate:reset

# Fresh migration (drop all tables and re-migrate)
php artisan migrate:fresh
```

### Seeding Commands

```bash
# Run database seeders
php artisan db:seed

# Run specific seeder
php artisan db:seed --class=UserSeeder

# Fresh migration with seeding
php artisan migrate:fresh --seed
```

## 🧹 Cache and Configuration Commands

### Configuration Caching

```bash
# Cache configuration for performance
php artisan config:cache

# Clear configuration cache
php artisan config:clear

# Show current configuration
php artisan config:show

# Show specific config section
php artisan config:show mail
```

### Route Caching

```bash
# Cache routes for performance
php artisan route:cache

# Clear route cache
php artisan route:clear

# List all routes
php artisan route:list

# Show routes for specific domain
php artisan route:list --domain=api
```

### View Caching

```bash
# Cache compiled views
php artisan view:cache

# Clear view cache
php artisan view:clear
```

### Application Cache

```bash
# Clear application cache
php artisan cache:clear

# Clear specific cache store
php artisan cache:clear --store=redis

# Cache application data
php artisan cache:table
```

## 🔧 Maintenance Commands

### Application Key

```bash
# Generate new application key
php artisan key:generate

# Generate key without confirmation (production)
php artisan key:generate --force
```

### Storage Commands

```bash
# Create storage symlink
php artisan storage:link

# Clear storage logs (custom command)
php artisan storage:clear-logs --days=30
```

### Optimization Commands

```bash
# Full application optimization
php artisan optimize

# Clear all caches
php artisan optimize:clear

# Generate optimized class loader
composer dump-autoload --optimize
```

## 👥 User Management Commands

### Create Admin User

```bash
# Interactive user creation
php artisan tinker
```

```php
// In tinker console
User::factory()->create([
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'email_verified_at' => now(),
    'password' => Hash::make('secure_password')
]);
```

### User Operations

```bash
# List all users (custom command)
php artisan user:list

# Verify user email
php artisan user:verify admin@example.com

# Reset user password  
php artisan user:reset-password admin@example.com
```

## 📈 Monitoring and Diagnostics

### Health Check Commands

```bash
# Check application health
php artisan health:check

# Test database connection
php artisan db:show

# Show application status
php artisan about

# Check queue connection
php artisan queue:monitor ssl-monitoring
```

### Log Commands

```bash
# Show application logs
php artisan log:show

# Clear old logs
php artisan log:clear --days=30

# Monitor logs in real-time
tail -f storage/logs/laravel.log
```

### Performance Commands

```bash
# Show performance metrics
php artisan performance:metrics

# Database query analysis
php artisan db:stats

# Show memory usage
php artisan debug:memory
```

## 🔒 Security Commands

### Permission Commands

```bash
# Fix file permissions
php artisan permissions:fix

# Check security configuration
php artisan security:check

# Rotate application key (advanced)
php artisan key:rotate
```

## 🛠️ Development Commands

### Testing Commands

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test tests/Feature/Models/

# Run tests with coverage
php artisan test --coverage

# Run parallel tests
php artisan test --parallel
```

### Code Quality Commands

```bash
# Format code with Laravel Pint
./vendor/bin/pint

# Check code style without fixing
./vendor/bin/pint --test

# Format specific files
./vendor/bin/pint app/Models/
```

## 🔄 Custom SSL Monitor Commands

### Email Configuration

```bash
# Test email configuration
php artisan email:test admin@example.com

# Show email settings
php artisan email:show

# Clear email configuration cache
php artisan email:clear-cache
```

### SSL Certificate Utilities

```bash
# Show SSL certificate details
php artisan ssl:show example.com

# Test SSL connection
php artisan ssl:test https://example.com

# Import SSL certificates from file
php artisan ssl:import certificates.csv
```

### Maintenance Mode

```bash
# Enable maintenance mode
php artisan down

# Enable with custom message
php artisan down --message="Scheduled maintenance in progress"

# Allow specific IPs during maintenance
php artisan down --allow=127.0.0.1 --allow=192.168.1.100

# Disable maintenance mode
php artisan up
```

## 📋 Command Scheduling Reference

### Current Schedule (Daily)

```bash
# View current schedule
php artisan schedule:list
```

**Scheduled Tasks:**
- `06:00` - SSL certificate checks (`ssl:check-all`)
- `02:00` - Application cache cleanup
- `03:00` - Log file rotation
- `04:00` - Queue maintenance

### Custom Scheduling

Add custom schedules in `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Daily SSL checks at 6 AM
    $schedule->command('ssl:check-all')
             ->dailyAt('06:00')
             ->withoutOverlapping();
             
    // Weekly email configuration test
    $schedule->command('email:test')
             ->weekly()
             ->sundays()
             ->at('07:00');
}
```

## 🚨 Emergency Commands

### Recovery Commands

```bash
# Emergency cache clear (if app broken)
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Reset queue workers
php artisan queue:restart

# Check application status
php artisan about

# Emergency SSL check
php artisan ssl:check-all --force --verbose
```

### Troubleshooting Commands

```bash
# Debug configuration
php artisan config:show

# Check environment
php artisan env

# Verify database connection
php artisan migrate:status

# Test queue processing
php artisan queue:work --once

# Check storage permissions
ls -la storage/logs/
```

## 📖 Command Help

### Getting Help

```bash
# List all commands
php artisan list

# Get help for specific command
php artisan help ssl:check-all

# Show command options
php artisan ssl:check-all --help
```

### Command Categories

```bash
# Show commands by category
php artisan list ssl      # SSL-related commands
php artisan list queue    # Queue commands  
php artisan list cache    # Cache commands
php artisan list route    # Route commands
```

## 🎯 Best Practices

### Production Commands

```bash
# Recommended production deployment sequence
php artisan down
php artisan optimize:clear
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
php artisan up
```

### Daily Maintenance

```bash
# Daily administrative tasks
php artisan ssl:check-all
php artisan queue:monitor ssl-monitoring
php artisan log:show --tail=50
systemctl status ssl-monitor-worker
```

### Weekly Maintenance

```bash
# Weekly administrative tasks
php artisan optimize:clear
php artisan ssl:check-all --force
php artisan queue:failed
./vendor/bin/pint --test
php artisan test
```

## 🎯 Next Steps

- **[Queue Management](queue-management.md)** - Detailed queue worker management
- **[Monitoring](monitoring.md)** - System monitoring and health checks
- **[Maintenance](maintenance.md)** - Regular maintenance procedures
- **[Security](security.md)** - Security best practices

---

**Previous**: [Deployment](deployment.md) | **Next**: [Queue Management](queue-management.md)