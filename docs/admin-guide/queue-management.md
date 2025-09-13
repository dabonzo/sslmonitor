# Queue Management

SSL Monitor uses Laravel's queue system with **Laravel Horizon** for advanced queue management and monitoring. This guide covers queue management for administrators.

## Overview

All background jobs use Laravel's **default queue** with Horizon for advanced monitoring:
- **SSL certificate checks** (`CheckSslCertificateJob`)
- **Uptime monitoring checks** (`CheckWebsiteUptimeJob`) 
- **SSL notifications** (`SendSslNotificationJob`)
- **Uptime notifications** (`SendUptimeNotificationJob`)

## Laravel Horizon Dashboard

**Horizon provides a beautiful web dashboard** for monitoring queue performance:
- **URL**: http://localhost/horizon (development)  
- **Features**: Real-time job metrics, failed job management, performance insights
- **Access**: Automatically available in local development environment

## Queue Worker Management

### Development Environment (Laravel Sail)

#### Starting Horizon (Recommended)
```bash
# Horizon starts automatically with Sail
./vendor/bin/sail up -d

# Start all development services (includes Horizon)
./vendor/bin/sail composer run dev

# Manual Horizon commands (if needed)
./vendor/bin/sail artisan horizon          # Start Horizon
./vendor/bin/sail artisan horizon:terminate # Stop Horizon
```

#### Stopping Queue Worker
```bash
# Graceful shutdown (completes current jobs)
./vendor/bin/sail artisan queue:restart

# Force stop (via Docker)
./vendor/bin/sail restart queue-worker
```

#### Monitoring Queue Worker Status
```bash
# Check if queue worker container is running
./vendor/bin/sail ps

# Check queue size
./vendor/bin/sail artisan queue:work --once --verbose
```

### Production Environment

#### Starting Queue Worker
```bash
# Basic queue worker
php artisan queue:work --sleep=3 --tries=3 --max-time=3600

# With process management (recommended)
php artisan queue:work --daemon --sleep=3 --tries=3 --max-time=3600

# Using supervisor (most reliable)
# See deployment guide for supervisor configuration
```

#### Process Management
```bash
# Graceful restart (completes current jobs)
php artisan queue:restart

# Check worker processes
ps aux | grep "queue:work"

# Kill specific worker process
kill -TERM <process_id>
```

## Manual Job Processing

### Processing Single Jobs
```bash
# Process one job from the default queue
./vendor/bin/sail artisan queue:work --once

# Process with verbose output
./vendor/bin/sail artisan queue:work --once --verbose

# Process with timeout
./vendor/bin/sail artisan queue:work --once --timeout=60
```

### Force Processing All Jobs
```bash
# Process all queued jobs (development)
./vendor/bin/sail artisan queue:work --stop-when-empty

# Production equivalent
php artisan queue:work --stop-when-empty
```

## Queue Monitoring

### Queue Status Commands
```bash
# Check current queue size
./vendor/bin/sail artisan tinker --execute="echo 'Queue size: ' . queue()->size() . PHP_EOL;"

# List failed jobs
./vendor/bin/sail artisan queue:failed

# Clear all failed jobs
./vendor/bin/sail artisan queue:flush

# Retry failed jobs
./vendor/bin/sail artisan queue:retry all
```

### Real-time Monitoring
```bash
# Monitor queue processing in real-time
./vendor/bin/sail artisan queue:monitor

# Watch log output during processing
./vendor/bin/sail artisan pail --timeout=0
```

## Troubleshooting

### Common Issues

#### Queue Worker Not Processing Jobs
1. **Check if worker is running:**
   ```bash
   ./vendor/bin/sail ps
   # Look for: ssl-monitor-queue-worker-1 (Up X minutes)
   ```

2. **Restart the queue worker:**
   ```bash
   ./vendor/bin/sail restart queue-worker
   ```

3. **Check for errors:**
   ```bash
   ./vendor/bin/sail logs queue-worker
   ```

#### Jobs Failing Silently
1. **Check failed jobs:**
   ```bash
   ./vendor/bin/sail artisan queue:failed
   ```

2. **Process jobs with verbose output:**
   ```bash
   ./vendor/bin/sail artisan queue:work --once --verbose
   ```

3. **Check application logs:**
   ```bash
   ./vendor/bin/sail artisan pail
   ```

#### High Memory Usage
1. **Restart queue worker periodically:**
   ```bash
   # Add to crontab for production
   0 * * * * php /path/to/project/artisan queue:restart
   ```

2. **Use max-time and max-jobs limits:**
   ```bash
   php artisan queue:work --max-time=3600 --max-jobs=1000
   ```

### Queue Worker Configuration

The queue worker is configured in `docker-compose.yml`:
```yaml
queue-worker:
  command: bash -c "sleep 10 && php artisan queue:work --sleep=3 --tries=3 --max-time=3600"
  restart: unless-stopped
```

**Configuration Options:**
- `--sleep=3`: Wait 3 seconds between jobs
- `--tries=3`: Retry failed jobs up to 3 times  
- `--max-time=3600`: Restart worker every hour
- `restart: unless-stopped`: Auto-restart if container fails

## Manual Testing Commands

### SSL Certificate Checks
```bash
# Check all SSL certificates
./vendor/bin/sail artisan ssl:check-all --force --detailed

# Check specific website
./vendor/bin/sail artisan tinker --execute="
App\Jobs\CheckSslCertificateJob::dispatch(App\Models\Website::first());
"
```

### Uptime Monitoring Checks  
```bash
# Check all websites uptime
./vendor/bin/sail artisan uptime:check-all --force --detailed

# Check specific website
./vendor/bin/sail artisan tinker --execute="
App\Jobs\CheckWebsiteUptimeJob::dispatch(App\Models\Website::first());
"
```

### Notification Testing
```bash
# Test SSL notification
./vendor/bin/sail artisan tinker --execute="
\$sslCheck = App\Models\SslCheck::where('status', 'expiring_soon')->first();
if (\$sslCheck) {
    App\Jobs\SendSslNotificationJob::dispatch(\$sslCheck, 'expiry');
    echo 'SSL notification job dispatched';
}
"

# Test uptime notification  
./vendor/bin/sail artisan tinker --execute="
\$uptimeCheck = App\Models\UptimeCheck::where('status', 'down')->first();
if (\$uptimeCheck) {
    App\Jobs\SendUptimeNotificationJob::dispatch(\$uptimeCheck, 'downtime');
    echo 'Uptime notification job dispatched';
}
"
```

## Performance Considerations

### Development Environment
- Single queue worker is sufficient
- Use `composer run dev` to start all services
- Monitor logs with `./vendor/bin/sail artisan pail`

### Production Environment  
- Use multiple queue workers for high load
- Implement supervisor for process management
- Monitor queue sizes and processing times
- Set up log rotation for queue worker logs

### Queue Optimization
- Use Redis for queue driver (better performance than database)
- Set appropriate `sleep` and `timeout` values
- Monitor memory usage and restart workers periodically
- Use failed job monitoring and alerting

## Integration with Monitoring Commands

The queue system integrates with the monitoring commands:

```bash
# These commands dispatch jobs to the queue:
./vendor/bin/sail artisan ssl:check-all      # Dispatches CheckSslCertificateJob
./vendor/bin/sail artisan uptime:check-all   # Dispatches CheckWebsiteUptimeJob

# Jobs are processed by the queue worker automatically
# Results are stored in database and notifications sent as needed
```

For more information on monitoring commands, see [Artisan Commands](artisan-commands.md).

## Laravel Horizon Features

### Dashboard Overview
Access the Horizon dashboard at **http://localhost/horizon** to monitor:
- **Real-time Job Processing**: See jobs being processed live
- **Queue Metrics**: Throughput, response times, and job counts
- **Failed Jobs**: Easy retry and investigation of failed jobs
- **Supervisor Status**: Monitor queue worker health
- **Job Tags**: Track specific job types (SSL checks, uptime monitoring)

### Key Metrics Available
- **Jobs per Minute**: Current processing rate
- **Average Wait Time**: How long jobs wait in queue
- **Processes**: Number of active worker processes
- **Recent Jobs**: Latest completed and failed jobs
- **Failed Jobs**: Detailed error information and retry options

### Production Benefits
- **Performance Monitoring**: Track SSL check and uptime monitoring performance
- **Auto-scaling**: Horizon can automatically scale workers based on queue size
- **Job Tagging**: Organize jobs by type (ssl-monitoring, uptime-checks, notifications)
- **Detailed Metrics**: Historical job performance data
- **Failure Management**: Easy retry and debugging of failed checks