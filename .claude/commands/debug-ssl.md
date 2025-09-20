# /debug-ssl - SSL Monitoring Debug Assistant

**Purpose**: Debug SSL monitoring issues using all MCP servers and comprehensive logging.

**Usage**: `/debug-ssl [issue-description]`

## SSL Debug Workflow

Please debug the SSL monitoring issue: **$ARGUMENTS**.

Follow these comprehensive debugging steps:

### 1. Initial Context & Error Analysis
```bash
# Get recent application errors
last-error
read-log-entries --entries=50

# Check SSL-specific logs
filesystem-mcp: tail-file storage/logs/ssl-monitoring.log 100
filesystem-mcp: grep-files storage/logs/ "ssl.*error"
filesystem-mcp: grep-files storage/logs/ "certificate.*failed"

# Application state
application-info
list-routes --path=ssl
```

### 2. Database Investigation
```bash
# Check SSL monitoring data
database-query "SELECT * FROM websites WHERE ssl_status = 'error' ORDER BY updated_at DESC LIMIT 10"
database-query "SELECT * FROM ssl_certificates WHERE is_valid = 0 ORDER BY checked_at DESC LIMIT 10"
database-query "SELECT * FROM failed_jobs WHERE queue = 'ssl-monitoring' ORDER BY failed_at DESC LIMIT 5"

# Database schema verification
database-schema --filter=ssl
database-schema --filter=certificates
```

### 3. SSL Certificate Testing with Tinker
```bash
# Test SSL certificate validation directly
tinker

# Test examples:
# Check specific website SSL
# $website = Website::find(1);
# $ssl = \Spatie\SslCertificate\SslCertificate::createForHostName($website->domain);
# $ssl->isValid();
# $ssl->expirationDate();
# $ssl->getDaysUntilExpirationDate();

# Test SSL validation service
# app(\App\Services\SslCertificateChecker::class)->checkCertificate($website);

# Test job dispatch
# \App\Jobs\CheckSslCertificate::dispatch($website);
```

### 4. Network & Connectivity Issues
```bash
# Test external connectivity from container
./vendor/bin/sail exec laravel.test ping -c 3 google.com
./vendor/bin/sail exec laravel.test curl -I https://expired.badssl.com/
./vendor/bin/sail exec laravel.test openssl s_client -connect badssl.com:443 -servername badssl.com

# Check DNS resolution
./vendor/bin/sail exec laravel.test nslookup badssl.com
./vendor/bin/sail exec laravel.test dig badssl.com
```

### 5. Job Queue Investigation
```bash
# Check queue status
./vendor/bin/sail artisan queue:work --once --queue=ssl-monitoring
./vendor/bin/sail artisan horizon:status

# Queue table investigation
database-query "SELECT * FROM jobs WHERE queue = 'ssl-monitoring' ORDER BY created_at DESC LIMIT 10"
database-query "SELECT * FROM failed_jobs ORDER BY failed_at DESC LIMIT 10"

# Retry failed jobs
./vendor/bin/sail artisan queue:retry all
```

### 6. Configuration Verification
```bash
# Check SSL monitoring configuration
get-config ssl-monitoring
get-config queue.default
get-config database.default

# Environment variables
list-available-env-vars
filesystem-mcp: grep-files .env "SSL_"
filesystem-mcp: grep-files .env "QUEUE_"
```

### 7. Frontend Debug (VRISTO + Vue Issues)
```bash
# Check browser console errors
browser-logs --entries=30

# VRISTO template issues
filesystem-mcp: grep-files resources/js/ "ssl"
filesystem-mcp: grep-files resources/css/ "certificate"

# Vue component debugging
use context7: "Vue.js debugging reactive data SSL certificate status"
use context7: "Inertia.js error handling SSL monitoring dashboard"
```

### 8. SSL Validation Edge Cases
```bash
# Test different SSL scenarios with tinker
tinker

# Test cases to run:
# 1. Valid certificate
# \Spatie\SslCertificate\SslCertificate::createForHostName('google.com')

# 2. Expired certificate
# \Spatie\SslCertificate\SslCertificate::createForHostName('expired.badssl.com')

# 3. Self-signed certificate
# \Spatie\SslCertificate\SslCertificate::createForHostName('self-signed.badssl.com')

# 4. Wrong host certificate
# \Spatie\SslCertificate\SslCertificate::createForHostName('wrong.host.badssl.com')

# 5. Untrusted root certificate
# \Spatie\SslCertificate\SslCertificate::createForHostName('untrusted-root.badssl.com')
```

### 9. Performance & Timeout Issues
```bash
# Check SSL check performance
tinker
# Measure SSL check time:
# $start = microtime(true);
# $ssl = \Spatie\SslCertificate\SslCertificate::createForHostName('slow.example.com');
# $end = microtime(true);
# echo "Time: " . ($end - $start) . " seconds";

# Check timeout configurations
get-config ssl-monitoring.timeout
get-config ssl-monitoring.max_retries

# Monitor system resources
./vendor/bin/sail exec laravel.test top -bn1
./vendor/bin/sail exec laravel.test df -h
```

### 10. Laravel Horizon & Queue Monitoring
```bash
# Check Horizon dashboard
./vendor/bin/sail artisan horizon:status
./vendor/bin/sail artisan horizon:terminate

# Monitor queue processing
./vendor/bin/sail artisan queue:monitor ssl-monitoring
./vendor/bin/sail artisan queue:work --queue=ssl-monitoring --timeout=60

# Check supervisor status (if used)
./vendor/bin/sail exec laravel.test supervisorctl status
```

### 11. Git History & Recent Changes
```bash
# Check recent SSL-related changes
git-mcp: log --grep="ssl" --oneline -10
git-mcp: log --grep="certificate" --oneline -10
git-mcp: diff HEAD~5..HEAD app/Services/
git-mcp: blame app/Services/SslCertificateChecker.php

# Check for recent migrations or config changes
git-mcp: log --oneline database/migrations/
git-mcp: log --oneline config/
```

### 12. External Dependencies & Services
```bash
# Check external SSL validation services
./vendor/bin/sail exec laravel.test curl -s "https://api.ssllabs.com/api/v3/analyze?host=example.com"

# Test different SSL endpoints
./vendor/bin/sail exec laravel.test openssl s_client -connect example.com:443 -servername example.com < /dev/null

# Check certificate chain
./vendor/bin/sail exec laravel.test openssl s_client -showcerts -connect example.com:443
```

## Common SSL Monitoring Issues & Solutions

### Issue Categories

#### Certificate Validation Errors
- **Expired certificates**: Check system time, certificate dates
- **Invalid chains**: Verify intermediate certificates
- **Hostname mismatches**: Check SAN extensions
- **Self-signed certificates**: Update validation logic

#### Network & Connectivity
- **Timeouts**: Increase timeout values, check DNS
- **Firewall issues**: Verify container network access
- **DNS resolution**: Check domain resolution from container
- **Port accessibility**: Verify 443 access from container

#### Queue & Job Processing
- **Failed jobs**: Check error messages, retry logic
- **Queue delays**: Monitor Horizon, check worker count
- **Memory issues**: Increase PHP memory limits
- **Job timeouts**: Adjust timeout configurations

#### Frontend Integration
- **VRISTO styling issues**: Check CSS conflicts
- **Vue reactivity problems**: Verify data binding
- **Inertia.js errors**: Check page props and responses
- **Real-time updates**: Verify WebSocket connections

### Debug Output Documentation
```bash
# Create debug report
filesystem-mcp: create-file storage/debug/ssl-debug-$(date +%Y%m%d-%H%M%S).log

# Include in report:
# - Error messages and stack traces
# - SSL test results
# - Database query results
# - Network connectivity tests
# - Configuration values
# - Recent code changes
```

## Success Criteria
1. Root cause identified and documented
2. Fix implemented with proper testing
3. Monitoring restored to full functionality
4. Debug information logged for future reference
5. Preventive measures added if needed

**Ready to debug and fix SSL monitoring issues!** 🔍