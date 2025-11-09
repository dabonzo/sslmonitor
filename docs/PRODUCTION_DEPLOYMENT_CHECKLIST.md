# Production Deployment Checklist - Historical Data System

## Pre-Deployment (Staging Environment)

### Database
- [ ] All migrations run successfully
- [ ] Indexes verified with `SHOW INDEX FROM monitoring_results`
- [ ] Data retention policy configured (90 days)
- [ ] Database backups automated

### Performance
- [ ] Load testing completed (50+ websites, 72,000 checks/day)
- [ ] Dashboard loads in < 2 seconds
- [ ] All queries complete in < 100ms
- [ ] Test suite passes in < 20 seconds (parallel)

### Caching
- [ ] Redis configured and running
- [ ] Cache TTL values optimized
- [ ] Cache invalidation strategy tested

### Queue Processing
- [ ] Horizon running and healthy
- [ ] Queue workers configured (monitoring-history, monitoring-aggregation)
- [ ] Failed job retry strategy configured
- [ ] Queue depth alerts configured

### Monitoring
- [ ] Horizon dashboard accessible
- [ ] Health check command scheduled
- [ ] Slow query logging enabled
- [ ] Database growth monitoring in place

### Testing
- [ ] Full test suite passing (530 tests)
- [ ] Browser tests passing
- [ ] Performance tests passing
- [ ] Load tests passing

## Deployment Steps

### 1. Staging Validation (1 week)
```bash
# Deploy to staging
dep deploy staging

# Run tests
./vendor/bin/sail artisan test --parallel

# Load test
./vendor/bin/sail artisan monitoring:load-test --websites=50 --checks=1000

# Monitor for 1 week
./vendor/bin/sail artisan horizon:health-check
```

### 2. Production Deployment
```bash
# Backup database
./vendor/bin/sail artisan backup:run --only-db

# Deploy with zero downtime
dep deploy production

# Verify migrations
./vendor/bin/sail artisan migrate:status

# Start Horizon
./vendor/bin/sail artisan horizon

# Verify health
./vendor/bin/sail artisan horizon:health-check
```

### 3. Post-Deployment Monitoring (1 week)
- Monitor Horizon dashboard daily
- Check queue depth every hour
- Review slow query logs
- Verify database growth (~23 MB/day expected)
- Monitor cache hit rates

## Rollback Plan

If issues occur:
```bash
# Stop Horizon
./vendor/bin/sail artisan horizon:terminate

# Rollback deployment
dep rollback production

# Restore database from backup
./vendor/bin/sail artisan backup:restore --backup-file=<file>

# Restart Horizon
./vendor/bin/sail artisan horizon
```

## Success Criteria

- ✅ Dashboard loads in < 2 seconds
- ✅ Queue depth stays < 50 jobs
- ✅ Failed job rate < 1%
- ✅ All queries < 100ms
- ✅ Database growth ~23 MB/day
- ✅ No user-reported issues for 1 week

## Performance Verification Commands

### Check Query Performance
```bash
./vendor/bin/sail artisan monitoring:optimize-queries
```

### Verify Caching
```bash
./vendor/bin/sail artisan tinker
>>> Cache::flush();
>>> $monitor = App\Models\Monitor::first();
>>> $service = new App\Services\MonitoringCacheService();
>>> $service->getSummaryStats($monitor, '30d'); // Should cache
>>> Cache::has("monitor:{$monitor->id}:summary:30d"); // Should be true
```

### Load Testing
```bash
./vendor/bin/sail artisan monitoring:load-test --websites=50 --checks=100
```

### Health Check
```bash
./vendor/bin/sail artisan horizon:health-check
```

## Performance Targets

| Metric | Target | How to Verify |
|--------|--------|---------------|
| Dashboard Load | < 2s | `time curl -s http://localhost/...` |
| Query Performance | All < 100ms | `monitoring:optimize-queries` |
| Queue Depth | < 50 jobs | `horizon:health-check` |
| Processing Rate | > 100 jobs/min | `horizon:health-check` |
| Database Growth | ~23 MB/day | Load test verification |
| Cache Hit Rate | > 70% | Redis monitoring |
| Failed Job Rate | < 1% | Horizon dashboard |
| Test Suite | < 20s parallel | `time test --parallel` |

## Monitoring & Alerting

### Daily Checks
- Horizon dashboard status
- Queue depth < 50 jobs
- Failed jobs count < 10
- Database backup verification

### Weekly Reviews
- Performance metrics trends
- Database growth rate
- Cache hit rate statistics
- Slow query log analysis

### Monthly Reviews
- Load testing regression check
- Capacity planning assessment
- Performance optimization opportunities
- Security updates and patches

## Emergency Contacts

- System Administrator: [Contact Info]
- Database Administrator: [Contact Info]
- On-Call Engineer: [Contact Info]

## Common Issues & Solutions

### High Queue Depth
```bash
# Check queue status
./vendor/bin/sail artisan horizon:status

# Restart Horizon workers
./vendor/bin/sail artisan horizon:terminate
./vendor/bin/sail artisan horizon

# Scale workers if needed
# Edit config/horizon.php and increase worker count
```

### Slow Queries
```bash
# Analyze queries
./vendor/bin/sail artisan monitoring:optimize-queries

# Check indexes
./vendor/bin/sail artisan tinker
>>> DB::select("SHOW INDEX FROM monitoring_results");

# Review slow query log
tail -f storage/logs/laravel.log | grep "Slow query"
```

### Cache Issues
```bash
# Clear all caches
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan route:clear

# Verify Redis connection
./vendor/bin/sail artisan tinker
>>> Redis::ping();
```

### Database Growth Issues
```bash
# Check table sizes
./vendor/bin/sail artisan monitoring:optimize-queries

# Run data pruning
./vendor/bin/sail artisan monitoring:prune-old-data --days=90

# Verify retention policy
grep "RETENTION_DAYS" .env
```

## Phase 5 Implementation Complete

This checklist ensures all Phase 5 production optimization features are properly deployed:

✅ Advanced caching with MonitoringCacheService
✅ Query optimization tools and monitoring
✅ Load testing infrastructure
✅ Production health monitoring
✅ Comprehensive documentation

**Status**: Ready for Production Deployment
