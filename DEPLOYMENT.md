# SSL Monitor v4 - Production Deployment Guide

## Prerequisites

- Laravel 12 application running
- Redis server installed and configured
- Supervisor installed on production server
- Proper environment variables configured

## Queue Worker Configuration

### 1. Copy Supervisor Configuration

Copy the supervisor configuration file to your system:

```bash
sudo cp supervisor/ssl-monitor-queues.conf /etc/supervisor/conf.d/
```

### 2. Update File Paths

Edit the configuration file and update the paths to match your production environment:

```bash
sudo nano /etc/supervisor/conf.d/ssl-monitor-queues.conf
```

Update these paths:
- `/var/www/html/` → Your application path
- `/var/www/html/storage/logs/` → Your storage logs path

### 3. Update User

Change the `user=www-data` directive to match your web server user.

### 4. Reload Supervisor

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start ssl-monitor-queues:*
```

## Queue Configuration

The system uses 5 different queues with specific purposes:

- **immediate** (2 workers): High-priority immediate website checks
- **notifications** (3 workers): Email and alert processing
- **uptime** (2 workers): Regular uptime monitoring tasks
- **ssl** (1 worker): SSL certificate monitoring
- **cleanup** (1 worker): System maintenance and cleanup

## Monitoring

### Check Queue Status

```bash
sudo supervisorctl status
```

### View Logs

```bash
# Immediate queue logs
tail -f /var/www/html/storage/logs/queue-immediate.log

# All queue logs
tail -f /var/www/html/storage/logs/queue-*.log
```

### Laravel Commands

```bash
# Monitor queue size
php artisan queue:monitor

# List failed jobs
php artisan queue:failed

# Restart all workers (after code deployment)
php artisan queue:restart
```

## Scheduler Configuration

Add this to your crontab:

```bash
* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
```

## Environment Variables

Ensure these environment variables are set:

```env
QUEUE_CONNECTION=redis
QUEUE_IMMEDIATE=immediate
QUEUE_NOTIFICATIONS=notifications
QUEUE_UPTIME=uptime
QUEUE_SSL=ssl
QUEUE_CLEANUP=cleanup

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

## Performance Tuning

### Redis Configuration

For production, consider tuning Redis for queue workloads:

```redis
# In redis.conf
maxmemory-policy allkeys-lru
timeout 0
tcp-keepalive 60
```

### Queue Worker Options

The supervisor configuration uses these optimized settings:

- `--sleep=3`: 3-second sleep between job polls
- `--tries=3`: Retry failed jobs up to 3 times
- `--max-time=3600`: Restart worker every hour
- `--timeout=90-600`: Job timeout based on queue type

## Troubleshooting

### Workers Not Starting

1. Check supervisor logs: `sudo tail -f /var/log/supervisor/supervisord.log`
2. Verify paths in configuration file
3. Check Redis connection: `php artisan queue:monitor`

### Jobs Failing

1. Check application logs: `tail -f storage/logs/laravel.log`
2. Review failed jobs: `php artisan queue:failed`
3. Check specific queue logs in `storage/logs/queue-*.log`

### Performance Issues

1. Monitor Redis memory usage: `redis-cli info memory`
2. Check queue sizes: `php artisan queue:monitor`
3. Scale workers if needed by updating `numprocs` in supervisor config

## Security Considerations

- Ensure queue worker processes run as appropriate user (not root)
- Limit access to supervisor control commands
- Monitor queue logs for sensitive data leakage
- Use secure Redis configuration (password, firewall)

## Maintenance

### Regular Tasks

- Monitor queue worker health
- Review failed job patterns
- Clean up old log files
- Update supervisor configuration as needed

### Code Deployments

1. `php artisan queue:restart` - Restart all workers
2. `sudo supervisorctl restart ssl-monitor-queues:*` - If needed
3. Monitor for any failed jobs after deployment