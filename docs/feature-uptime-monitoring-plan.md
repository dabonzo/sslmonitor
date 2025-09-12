# Uptime Monitoring System - Implementation Plan

**Project**: SSL Monitor v2 - Uptime Monitoring Extension  
**Date**: 2025-09-12  
**Status**: 📋 Planning Phase  

## Problem Statement

Current SSL certificate monitoring only checks certificate validity, not whether websites are actually accessible and functioning. A website can have a valid SSL certificate but be completely down or showing error pages.

### Real-World Uptime Challenges

❌ **HTTP 200 is NOT enough:**
- Default hosting provider pages (200 OK but wrong content)
- Error pages from the application (200 OK but "Database connection failed")  
- Maintenance pages (200 OK but site is down for maintenance)
- Empty pages or broken applications (200 OK but no content)
- Wrong applications deployed (200 OK but different site entirely)

✅ **What we ACTUALLY need:**
- HTTP status validation with flexibility
- **Content validation** - ensure specific text appears on the page
- Response time monitoring
- Proper redirect handling
- Integration with existing SSL monitoring

## System Architecture

### 1. Multi-Level Uptime Validation

#### Level 1: HTTP Status Checking
- **Primary Status Codes**: 200 (OK), 301/302 (Redirects)
- **Configurable per website**: Some sites legitimately redirect
- **Redirect Following**: Follow up to 3 redirects, detect loops
- **Timeout Handling**: Configurable timeout (default 30 seconds)

#### Level 2: Content Validation (Optional)
- **Expected Content Text**: User-configurable text that should appear
- **Examples**:
  - Site title: "My Company Dashboard"
  - Specific content: "Welcome to our application"  
  - Login indicator: "Sign in" or "Dashboard"
- **Negative Content**: Text that should NOT appear
  - "Database connection failed"
  - "Maintenance mode"
  - "This domain is parked"

#### Level 3: Response Analysis
- **Response Time Tracking**: Monitor site performance
- **Content-Type Validation**: Ensure HTML response (not JSON error)
- **Response Size Validation**: Detect unusually small/large responses

### 2. Database Schema Design

```sql
-- Extend websites table
ALTER TABLE websites ADD COLUMN uptime_monitoring BOOLEAN DEFAULT false;
ALTER TABLE websites ADD COLUMN expected_status_code INTEGER DEFAULT 200;
ALTER TABLE websites ADD COLUMN expected_content TEXT NULL;
ALTER TABLE websites ADD COLUMN forbidden_content TEXT NULL;
ALTER TABLE websites ADD COLUMN max_response_time INTEGER DEFAULT 30000; -- milliseconds
ALTER TABLE websites ADD COLUMN follow_redirects BOOLEAN DEFAULT true;
ALTER TABLE websites ADD COLUMN max_redirects INTEGER DEFAULT 3;

-- Uptime checks table (similar to ssl_checks)
CREATE TABLE uptime_checks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    website_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(20) NOT NULL, -- 'up', 'down', 'slow', 'content_mismatch'
    http_status_code INTEGER NULL,
    response_time_ms INTEGER NULL,
    response_size_bytes INTEGER NULL,
    content_check_passed BOOLEAN NULL,
    content_check_error TEXT NULL,
    error_message TEXT NULL,
    checked_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (website_id) REFERENCES websites(id) ON DELETE CASCADE,
    INDEX idx_website_checked_at (website_id, checked_at),
    INDEX idx_status_checked_at (status, checked_at)
);

-- Downtime incidents table (for tracking outages)
CREATE TABLE downtime_incidents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    website_id BIGINT UNSIGNED NOT NULL,
    started_at TIMESTAMP NOT NULL,
    ended_at TIMESTAMP NULL,
    duration_minutes INTEGER NULL,
    max_response_time_ms INTEGER NULL,
    incident_type VARCHAR(50) NOT NULL, -- 'timeout', 'http_error', 'content_mismatch'
    error_details TEXT NULL,
    resolved_automatically BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (website_id) REFERENCES websites(id) ON DELETE CASCADE,
    INDEX idx_website_started_at (website_id, started_at),
    INDEX idx_incident_type (incident_type)
);
```

### 3. Service Architecture

#### UptimeChecker Service
```php
class UptimeChecker
{
    public function checkWebsite(Website $website): UptimeCheckResult
    {
        // 1. HTTP Request with proper headers and timeout
        // 2. Follow redirects if enabled
        // 3. Validate HTTP status code
        // 4. Check response time
        // 5. Validate content if configured
        // 6. Return comprehensive result
    }
    
    private function validateContent(string $content, Website $website): bool
    {
        // Check for expected content
        // Check for forbidden content  
        // Return validation result
    }
    
    private function followRedirects(string $url, Website $website): RedirectResult
    {
        // Follow redirects up to max limit
        // Detect redirect loops
        // Return final URL and status
    }
}
```

#### UptimeStatus Calculator
```php
class UptimeStatusCalculator  
{
    public function calculateStatus(Website $website): string
    {
        // 'up' - All checks pass
        // 'down' - HTTP error or timeout
        // 'slow' - Responds but over threshold
        // 'content_mismatch' - Wrong content
        // 'unknown' - No recent checks
    }
    
    public function calculateUptimePercentage(Website $website, int $days = 30): float
    {
        // Calculate uptime percentage over period
    }
    
    public function detectDowntimeIncident(Website $website): ?DowntimeIncident
    {
        // Detect if current failure starts new incident
        // Or continues existing incident
    }
}
```

### 4. Queue Jobs Integration

#### CheckWebsiteUptimeJob  
```php
class CheckWebsiteUptimeJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue;
    
    public function handle(UptimeChecker $checker): void
    {
        // 1. Perform uptime check
        // 2. Store result in uptime_checks
        // 3. Update website status
        // 4. Detect/resolve downtime incidents  
        // 5. Trigger notifications if needed
    }
}
```

#### CheckAllWebsitesUptimeCommand
```php
class CheckAllWebsitesUptimeCommand extends Command
{
    protected $signature = 'uptime:check-all {--force}';
    
    public function handle(): void
    {
        // Queue uptime checks for all websites with uptime monitoring enabled
        // Similar to ssl:check-all command
    }
}
```

### 5. UI/UX Integration

#### Website Management Enhancements
```php
// Add to website form
- Enable Uptime Monitoring (checkbox)
- Expected HTTP Status (dropdown: 200, 301, 302)  
- Expected Content Text (optional textarea)
- Forbidden Content Text (optional textarea)
- Response Time Threshold (input, default 30 seconds)
- Follow Redirects (checkbox, default true)
```

#### Dashboard Integration  
```php
// Add to SSL Dashboard
- Uptime Status Cards (Up/Down/Slow websites)
- Combined SSL + Uptime status indicators
- Recent downtime incidents
- Average response times
- Uptime percentage statistics
```

#### Website Details Page
```php
// Add to individual website view
- Uptime check history (last 30 days)
- Response time graphs
- Downtime incident timeline  
- Content validation results
- Uptime percentage over time
```

## Implementation Phases (TDD)

### Phase 1: Core Uptime Models & Database (TDD) 📋 **PLANNED**

#### Task 1.1: Uptime Check Model & Migration
- [ ] Write tests for UptimeCheck model (status, response times, relationships)
- [ ] Create migration for uptime_checks table
- [ ] Implement UptimeCheck model with status calculations
- [ ] Test uptime history queries and aggregations  
- [ ] Add Website->uptimeChecks() relationship

#### Task 1.2: Downtime Incident Model & Migration  
- [ ] Write tests for DowntimeIncident model (duration calculations, resolution)
- [ ] Create migration for downtime_incidents table
- [ ] Implement DowntimeIncident model with duration logic
- [ ] Test incident detection and automatic resolution
- [ ] Add Website->downtimeIncidents() relationship

#### Task 1.3: Website Model Extensions
- [ ] Write tests for uptime monitoring website settings
- [ ] Create migration to add uptime columns to websites table
- [ ] Update Website model with uptime configuration methods
- [ ] Test uptime settings validation and defaults
- [ ] Update WebsitePolicy for uptime monitoring permissions

### Phase 2: Uptime Checking Service (TDD) 📋 **PLANNED**

#### Task 2.1: UptimeChecker Service
- [ ] Write tests for HTTP status checking (200, redirects, timeouts)
- [ ] Test content validation (expected/forbidden text)
- [ ] Test response time measurement and thresholds
- [ ] Implement UptimeChecker service with HTTP client
- [ ] Test error handling for various failure scenarios

#### Task 2.2: UptimeStatusCalculator Service
- [ ] Write tests for uptime status calculations (up/down/slow/content_mismatch)
- [ ] Test uptime percentage calculations over time periods
- [ ] Test downtime incident detection and resolution
- [ ] Implement status calculation with priority system
- [ ] Test edge cases and invalid data handling

### Phase 3: Background Uptime Monitoring (TDD) 📋 **PLANNED**

#### Task 3.1: Uptime Monitoring Job
- [ ] Write tests for CheckWebsiteUptimeJob
- [ ] Test job queuing and processing with Redis
- [ ] Test uptime result storage and incident detection
- [ ] Implement job with retry logic and error handling
- [ ] Test integration with notification system

#### Task 3.2: Uptime Check Command
- [ ] Write tests for uptime monitoring command
- [ ] Test batch processing of all websites with uptime enabled
- [ ] Implement artisan command for uptime checks (uptime:check-all)
- [ ] Test command scheduling and execution
- [ ] Test force option and filtering logic

### Phase 4: UI Integration (TDD) 📋 **PLANNED**

#### Task 4.1: Website Management UI Enhancements
- [ ] Write tests for uptime monitoring form fields
- [ ] Test uptime configuration validation and saving
- [ ] Update website management component with uptime options
- [ ] Test "Check Before Adding" workflow with uptime validation
- [ ] Implement uptime settings in website modal

#### Task 4.2: Dashboard Integration
- [ ] Write tests for uptime dashboard statistics
- [ ] Test combined SSL + uptime status display
- [ ] Update dashboard component with uptime metrics
- [ ] Test uptime status cards and recent incidents
- [ ] Implement uptime filtering and sorting

#### Task 4.3: Website Details Enhancement  
- [ ] Write tests for uptime history display
- [ ] Test uptime check timeline and response time graphs
- [ ] Update website details component with uptime data
- [ ] Test downtime incident display and resolution
- [ ] Implement uptime statistics and percentage calculations

### Phase 5: Notifications & Alerting (TDD) 📋 **PLANNED**

#### Task 5.1: Uptime Notification Integration
- [ ] Write tests for downtime notification triggers
- [ ] Test notification content for uptime incidents
- [ ] Update notification system to include uptime alerts
- [ ] Test email notifications for downtime/recovery
- [ ] Implement notification preferences for uptime events

#### Task 5.2: Advanced Uptime Features
- [ ] Write tests for uptime trends and analytics
- [ ] Test uptime reporting and export functionality  
- [ ] Implement uptime performance insights
- [ ] Test integration with Telescope monitoring
- [ ] Add uptime-specific notification channels

## Monitoring Strategy

### Check Frequencies
- **High Priority Sites**: Every 5 minutes
- **Standard Sites**: Every 15 minutes  
- **Low Priority Sites**: Every 30 minutes
- **Configurable per website**

### Notification Triggers
- **Immediate**: Site goes down (first failure)
- **Escalation**: Still down after 15 minutes
- **Recovery**: Site comes back up
- **Performance**: Response time consistently over threshold

### Content Validation Examples

#### E-commerce Site
- **Expected**: "Add to Cart", "Shop Now"
- **Forbidden**: "Database Error", "Maintenance Mode"

#### Corporate Website  
- **Expected**: Company name, "Contact Us"
- **Forbidden**: "This domain is for sale", "Coming Soon"

#### Web Application
- **Expected**: "Dashboard", "Sign In"
- **Forbidden**: "500 Internal Server Error", "Connection refused"

## Integration with Existing Systems

### SSL Certificate Monitoring
- **Combined Status**: Website can be SSL-valid but down, or SSL-expired but up
- **Unified Dashboard**: Show both SSL and uptime status together
- **Coordinated Notifications**: Avoid spam by grouping related alerts

### Team Management
- **Role-Based Access**: Same permissions for uptime and SSL monitoring
- **Team-Wide Settings**: Uptime monitoring preferences per team
- **Shared Notifications**: Team-wide uptime alerts

### Performance Monitoring
- **Telescope Integration**: Monitor uptime check performance
- **Redis Caching**: Cache uptime results for fast dashboard loading
- **Database Optimization**: Proper indexing for uptime history queries

## Success Criteria

### Functional Requirements
- [ ] Accurate detection of actual website availability (not just HTTP 200)
- [ ] Content validation prevents false positives from error pages
- [ ] Response time monitoring identifies performance issues
- [ ] Downtime incident tracking with automatic resolution
- [ ] Integration with existing SSL monitoring system

### Performance Requirements  
- [ ] Uptime checks complete within 30 seconds per website
- [ ] Dashboard loads uptime data in under 2 seconds
- [ ] Minimal impact on existing SSL monitoring performance
- [ ] Efficient Redis queue processing for concurrent checks

### User Experience Requirements
- [ ] Intuitive uptime configuration in website settings
- [ ] Clear visual indicators for uptime status
- [ ] Historical uptime data presentation
- [ ] Actionable notifications for downtime events
- [ ] Combined SSL + uptime status overview

---

## Implementation Notes

### Development Workflow
1. **Git Branching Strategy**: Create feature branch `feature/uptime-monitoring` for all development
2. **Follow TDD**: Write failing tests first for all functionality
3. **Use existing patterns**: Mirror SSL monitoring architecture
4. **Leverage Redis**: Use Redis for caching and job processing
5. **Test with Telescope**: Monitor performance during development
6. **Phase-wise commits**: Commit each completed phase with comprehensive messages
7. **Documentation**: Update PROJECT_PLAN.md and user guides
8. **Final merge**: Merge to main after all phases complete and tested

#### Git Workflow per Phase:
```bash
# Initial feature branch
git checkout -b feature/uptime-monitoring

# Phase 1: Models & Database
git add . && git commit -m "Phase 1: Implement uptime monitoring models and database schema"

# Phase 2: Services
git add . && git commit -m "Phase 2: Implement UptimeChecker and status calculation services"

# Phase 3: Background Jobs
git add . && git commit -m "Phase 3: Add uptime monitoring background jobs and commands"

# Phase 4: UI Integration  
git add . && git commit -m "Phase 4: Integrate uptime monitoring into dashboard and website management"

# Phase 5: Notifications
git add . && git commit -m "Phase 5: Complete uptime monitoring with notifications and alerting"

# Final merge to main
git checkout main && git merge feature/uptime-monitoring
git branch -d feature/uptime-monitoring
```

### Technical Considerations
- **HTTP Client**: Use Guzzle for robust HTTP requests
- **Timeout Handling**: Proper timeout and retry logic
- **Memory Usage**: Efficient processing for large website lists
- **Error Logging**: Comprehensive logging for debugging
- **Security**: No credential storage in uptime checks

### Future Enhancements (Phase 6+)
- **API Endpoint Monitoring**: Check specific API endpoints
- **Database Connection Checks**: Verify database connectivity
- **Multi-Region Monitoring**: Check from multiple geographic locations
- **Custom Scripts**: User-defined health check scripts
- **Integration APIs**: Webhooks for external monitoring systems

This comprehensive uptime monitoring system will provide real-world website availability monitoring that goes far beyond simple HTTP status checks, ensuring users know their websites are truly accessible and functioning correctly.