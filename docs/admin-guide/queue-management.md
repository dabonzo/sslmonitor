# Queue Management

SSL Monitor uses Laravel's queue system with **Laravel Horizon** for advanced queue management and monitoring. This guide covers queue management for administrators.

## Overview

All background jobs use Laravel's **default queue** with Horizon for advanced monitoring:
- **SSL certificate checks** (`CheckSslCertificateJob`)
- **Uptime monitoring checks** (`CheckWebsiteUptimeJob`) 
- **SSL notifications** (`SendSslNotificationJob`)
- **Uptime notifications** (`SendUptimeNotificationJob`)

## Performance Monitoring Dashboards

### Laravel Horizon Dashboard
**Horizon provides advanced queue management and monitoring**:
- **URL**: http://localhost/horizon (development)  
- **Features**: Real-time job metrics, failed job management, performance insights
- **Access**: Automatically available in local development environment

### Laravel Pulse Dashboard 🆕
**Pulse provides comprehensive application performance monitoring**:
- **URL**: http://localhost/pulse (development)
- **Features**: SSL check performance, slow job tracking, exception monitoring, outgoing request analysis
- **Perfect for SSL Monitor**: Track certificate fetch times, identify slow domains, monitor uptime check performance
- **Access**: Automatically available in local development environment

## Queue Worker Management

### ⭐ NEW: Auto-Starting Queue Worker

**Horizon now starts automatically** when you boot the development environment:

```bash
./vendor/bin/sail up -d    # Horizon starts automatically!
```

**Technical Implementation:**
- Custom supervisor configuration in `/docker/supervisord.conf`
- Manages both web server and Horizon queue worker
- Auto-restart if Horizon crashes
- Logs to `/storage/logs/horizon.log`

**Check Auto-Start Status:**
```bash
./vendor/bin/sail artisan horizon:status
# Should show: "INFO Horizon is running."
```

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

## Laravel Pulse Features 🆕

### Performance Insights Dashboard
Access the Pulse dashboard at **http://localhost/pulse** to monitor:
- **SSL Certificate Fetch Performance**: Track how long SSL checks take for different domains
- **Uptime Check Response Times**: Monitor website response times and identify slow sites
- **Failed SSL/Uptime Checks**: See exceptions and errors in real-time
- **Queue Job Performance**: Identify slow running SSL and uptime monitoring jobs
- **Database Query Performance**: Monitor queries as your SSL monitoring data grows
- **Server Resource Usage**: Track CPU, memory, and disk usage

### SSL Monitor Specific Metrics
- **Slow Outgoing Requests**: Perfect for tracking SSL certificate fetches and uptime checks
- **Slow Jobs**: Identify SSL checks taking longer than expected (default: 1000ms)
- **Exceptions**: Monitor failed SSL certificate validations and uptime check errors
- **Queue Monitoring**: Track SSL and uptime job processing rates and wait times
- **Server Health**: Monitor server resources during high-load SSL checking periods

### Production Optimization Benefits
- **Performance Bottlenecks**: Identify slow SSL certificate providers or problematic domains
- **Scaling Insights**: Understand when you need more worker processes
- **Error Trending**: Track SSL/uptime failure patterns over time
- **Resource Planning**: Monitor server resource usage as you monitor more websites
- **Alert Thresholds**: Set up notifications for performance degradation

## Laravel Reverb Real-time Features 🚀 NEW

### Real-time WebSocket Communication
SSL Monitor now includes **Laravel Reverb** for instant, live updates without page refreshes:
- **URL**: http://localhost:8080 (WebSocket server)
- **Features**: Real-time SSL status changes, live uptime monitoring, instant notifications
- **Technology**: Native Laravel WebSocket server built on ReactPHP

### Real-time SSL & Uptime Monitoring
- **Instant Status Updates**: SSL certificate status changes broadcast immediately to all connected users
- **Live Dashboard**: Dashboard updates in real-time when SSL certificates expire or recover
- **Real-time Uptime Changes**: Website downtime and recovery events broadcast instantly
- **Team Collaboration**: Multiple team members see the same real-time updates simultaneously
- **No Refresh Required**: Page updates happen automatically via WebSocket connections

### Broadcasting Channels
SSL Monitor uses secure private channels for real-time updates:

#### SSL Monitoring Channels
- **`ssl-monitoring`**: General SSL status changes (all authenticated users)
- **`ssl-monitoring.website.{id}`**: Website-specific SSL updates (website owners + team members only)

#### Uptime Monitoring Channels  
- **`uptime-monitoring`**: General uptime status changes (all authenticated users)
- **`uptime-monitoring.website.{id}`**: Website-specific uptime updates (website owners + team members only)

### WebSocket Events
Real-time events broadcasted when status changes occur:

#### SSL Status Events
```javascript
// SSL status change event structure
{
  "ssl_check": {
    "id": 123,
    "website_id": 45,
    "website_url": "https://example.com",
    "status": "expiring_soon",
    "previous_status": "valid",
    "expires_at": "2025-10-15T10:30:00Z",
    "days_until_expiry": 7,
    "checked_at": "2025-09-13T21:30:00Z",
    "issuer": "Let's Encrypt",
    "fingerprint": "abc123..."
  },
  "timestamp": "2025-09-13T21:30:00Z"
}
```

#### Uptime Status Events
```javascript
// Uptime status change event structure
{
  "uptime_check": {
    "id": 456,
    "website_id": 45,
    "website_url": "https://example.com",
    "status": "down",
    "previous_status": "up", 
    "response_time": 0,
    "status_code": null,
    "checked_at": "2025-09-13T21:30:00Z",
    "failure_reason": "Connection timeout",
    "uptime_percentage": 98.5
  },
  "timestamp": "2025-09-13T21:30:00Z"
}
```

### Frontend Integration
The SSL Monitor frontend automatically connects to Reverb WebSocket server:
- **Laravel Echo**: JavaScript library handles WebSocket connections automatically
- **Auto-reconnection**: Handles network interruptions and automatically reconnects
- **Visual Updates**: Status indicators update in real-time without JavaScript intervention
- **Toast Notifications**: Optional real-time notifications for status changes
- **Channel Authorization**: Secure channel access based on website ownership and team membership

### Development Configuration
Reverb WebSocket server runs automatically with Laravel Sail:

```yaml
# docker-compose.yml - Reverb service configuration
reverb:
  image: 'sail-8.4/app'
  ports:
    - '${REVERB_PORT:-8080}:8080'
  command: bash -c "sleep 20 && php artisan reverb:start --host=0.0.0.0 --port=8080"
  depends_on:
    - mariadb
    - redis
```

### Production Deployment
For production environments, consider:
- **SSL/TLS Termination**: Use HTTPS/WSS for secure WebSocket connections
- **Load Balancing**: Distribute WebSocket connections across multiple Reverb instances
- **Horizontal Scaling**: Use Redis for cross-server event broadcasting
- **Monitoring**: Track WebSocket connection counts and broadcast performance
- **Error Handling**: Implement graceful fallbacks when WebSocket connections fail

### Testing Real-time Events
Test real-time broadcasting functionality:

```bash
# Manually trigger SSL status change event
./vendor/bin/sail artisan tinker --execute="
use App\Events\SslStatusChanged;
use App\Models\{Website, SslCheck};

\$website = Website::first();
\$sslCheck = \$website->sslChecks()->create([
    'status' => 'expiring_soon',
    'expires_at' => now()->addDays(7),
    'days_until_expiry' => 7,
    'checked_at' => now(),
    'is_valid' => true,
    'issuer' => 'Test CA',
    'fingerprint' => 'test-fingerprint'
]);

SslStatusChanged::dispatch(\$sslCheck, 'valid');
echo 'SSL status change broadcasted!';
"

# Test uptime status change event
./vendor/bin/sail artisan tinker --execute="
use App\Events\UptimeStatusChanged;
use App\Models\{Website, UptimeCheck};

\$website = Website::first();
\$uptimeCheck = \$website->uptimeChecks()->create([
    'status' => 'down',
    'http_status_code' => 500,
    'error_message' => 'Connection timeout',
    'checked_at' => now()
]);

UptimeStatusChanged::dispatch(\$uptimeCheck, 'up');
echo 'Uptime status change broadcasted!';
"
```

### Real-time Monitoring Benefits
- **Instant Awareness**: Know immediately when SSL certificates expire or websites go down
- **Collaborative Monitoring**: Teams see the same real-time status across all dashboards
- **Reduced Alert Fatigue**: Visual updates reduce need for constant email notifications  
- **Professional UX**: Modern, responsive interface that feels like enterprise monitoring tools
- **Scalable Architecture**: WebSocket connections scale independently of HTTP requests