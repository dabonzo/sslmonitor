# Monitoring History System Plan

## Overview

This document outlines the implementation plan for a comprehensive monitoring history system that will track all SSL certificate checks, uptime checks, alerts, and system events for data analysis and reporting.

## Requirements

1. **Data Collection**: Collect as much monitoring data as possible for future analysis
2. **Manual vs Automated Tracking**: Distinguish between manual checks (user-triggered) and scheduled checks
3. **Historical Trends**: Enable 7-day and longer trend analysis for dashboard
4. **Performance**: System should handle 50+ websites efficiently
5. **Data Retention**: Keep at least 1 month of data, preferably longer with aggregation
6. **Event Logging**: Track all significant monitoring events and user actions

## Database Schema Design

### 1. Monitoring History Table (`monitoring_history`)
**Purpose**: Store every check result (SSL and uptime) with full details

**Columns**:
- `id` (primary)
- `monitor_id` (foreign key to monitors)
- `website_id` (foreign key to websites, nullable)
- `check_type` (enum: 'ssl', 'uptime', 'both')
- `status` (enum: 'success', 'failure', 'warning', 'timeout', 'content_mismatch')
- `check_data` (json, full check results)
- `response_time_ms` (decimal, response time in milliseconds)
- `http_status_code` (string, HTTP status for uptime checks)
- `error_message` (text, error details if failed)
- `ip_address` (string, server IP that performed check)
- `user_agent` (string, client/user agent)
- `certificate_details` (json, SSL certificate info)
- `is_manual_check` (boolean, manual vs automated)
- `trigger_source` (string: 'manual', 'scheduled', 'api', 'webhook')
- `checked_at` (timestamp, when check was performed)
- `created_at`, `updated_at` (timestamps)

**Indexes**:
- `(monitor_id, check_type, checked_at)`
- `(website_id, check_type, checked_at)`
- `(status, checked_at)`
- `(is_manual_check, checked_at)`

### 2. SSL Certificate History Table (`ssl_certificate_history`)
**Purpose**: Track SSL certificate changes and expiration history

**Columns**:
- `id` (primary)
- `monitor_id` (foreign key)
- `website_id` (foreign key, nullable)
- `domain` (string, monitored domain)
- `issuer` (string, certificate issuer)
- `subject` (string, certificate subject)
- `valid_from` (timestamp, certificate validity start)
- `expires_at` (timestamp, certificate expiration)
- `days_until_expiry` (integer, days remaining until expiration)
- `fingerprint_sha1` (string, certificate fingerprint)
- `fingerprint_sha256` (string, certificate fingerprint)
- `status` (enum: 'valid', 'expired', 'expiring_soon', 'invalid', 'self_signed', 'mismatch')
- `certificate_details` (json, full certificate chain info)
- `checked_at` (timestamp, when certificate was checked)
- `created_at`, `updated_at` (timestamps)

**Indexes**:
- `(monitor_id, checked_at)`
- `(domain, expires_at)`
- `(status, expires_at)`

### 3. Alert History Table (`alert_history`)
**Purpose**: Track all alert notifications sent to users

**Columns**:
- `id` (primary)
- `monitor_id` (foreign key, nullable)
- `website_id` (foreign key, nullable)
- `user_id` (foreign key, nullable)
- `alert_type` (enum: 'ssl_expiry', 'ssl_issue', 'uptime_down', 'uptime_slow', 'content_mismatch', 'custom')
- `severity` (enum: 'low', 'medium', 'high', 'critical')
- `channel` (string: 'email', 'slack', 'webhook', 'sms')
- `recipient` (string, email/webhook URL)
- `subject` (string, alert subject)
- `message` (text, alert message)
- `alert_data` (json, additional context)
- `status` (enum: 'sent', 'failed', 'pending', 'retrying')
- `error_message` (text, delivery error details)
- `retry_count` (integer, number of retries)
- `sent_at` (timestamp, when alert was sent)
- `acknowledged_at` (timestamp, when user acknowledged)
- `acknowledged_by` (foreign key to users, nullable)
- `created_at`, `updated_at` (timestamps)

**Indexes**:
- `(alert_type, status, created_at)`
- `(monitor_id, alert_type, created_at)`
- `(severity, status, created_at)`

### 4. Monitoring Events Table (`monitoring_events`)
**Purpose**: Log significant system and user events

**Columns**:
- `id` (primary)
- `monitor_id` (foreign key, nullable)
- `website_id` (foreign key, nullable)
- `user_id` (foreign key, nullable)
- `event_type` (enum: 'monitor_created', 'monitor_updated', 'monitor_deleted', 'ssl_status_changed', 'ssl_certificate_renewed', 'ssl_issuer_changed', 'uptime_status_changed', 'performance_degraded', 'performance_improved', 'content_validation_failed', 'content_validation_passed', 'monitor_enabled', 'monitor_disabled', 'check_interval_changed', 'alert_triggered', 'alert_resolved', 'user_acknowledged')
- `event_name` (string, descriptive event name)
- `description` (text, event description)
- `old_values` (json, previous values)
- `new_values` (json, new values)
- `event_data` (json, additional context)
- `ip_address` (string, user IP)
- `user_agent` (string, user agent)
- `source` (string: 'system', 'user', 'api', 'webhook')
- `created_at`, `updated_at` (timestamps)

**Indexes**:
- `(event_type, created_at)`
- `(monitor_id, event_type, created_at)`
- `(user_id, event_type, created_at)`

### 5. Performance Metrics Table (`performance_metrics`)
**Purpose**: Store detailed performance data for analysis

**Columns**:
- `id` (primary)
- `monitor_id` (foreign key)
- `website_id` (foreign key, nullable)
- `response_time_ms` (decimal, response time)
- `time_to_first_byte_ms` (integer, TTFB)
- `dom_load_time_ms` (integer, DOM load time)
- `total_load_time_ms` (integer, total page load time)
- `size_bytes` (integer, page size in bytes)
- `http_status_code` (string, HTTP status)
- `performance_breakdown` (json, DNS, TCP, SSL timing breakdown)
- `resource_timing` (json, individual resource timing)
- `geo_location` (string, server location)
- `check_server` (string, which server performed check)
- `measured_at` (timestamp, when performance was measured)
- `created_at`, `updated_at` (timestamps)

**Indexes**:
- `(monitor_id, measured_at)`
- `(response_time_ms, measured_at)`

### 6. Content Validation History Table (`content_validation_history`)
**Purpose**: Track content validation checks

**Columns**:
- `id` (primary)
- `monitor_id` (foreign key)
- `website_id` (foreign key, nullable)
- `validation_type` (enum: 'expected_string', 'forbidden_string', 'regex_pattern', 'javascript_rendered')
- `validation_rule` (text, the validation rule)
- `passed` (boolean, validation result)
- `content_snippet` (text, matched/failed content snippet)
- `failure_reason` (text, why validation failed)
- `validation_details` (json, additional validation info)
- `checked_at` (timestamp, when validation was performed)
- `created_at`, `updated_at` (timestamps)

**Indexes**:
- `(monitor_id, validation_type, checked_at)`
- `(passed, checked_at)`

## Implementation Plan

### Phase 1: Core Infrastructure

1. **Database Migrations**
   - Create all 6 history tables with proper foreign keys and indexes
   - Add `certificate_last_checked_at` and `certificate_subject` columns to monitors table
   - Ensure proper data types and constraints

2. **Model Creation**
   - Create Laravel Eloquent models for all history tables
   - Define proper relationships and scopes
   - Add casts for JSON columns and timestamps
   - Use custom table names where needed

3. **Service Layer**
   - Create `MonitoringHistoryService` to handle data recording
   - Implement methods for each type of check (SSL, uptime, performance, validation)
   - Add trend calculation methods for dashboard
   - Handle website_id resolution properly

### Phase 2: Integration with Monitoring System

1. **Monitor Model Enhancement**
   - Add manual check methods (`performManualSslCheck()`, `performManualUptimeCheck()`, `performManualChecks()`)
   - Implement event listeners for automatic history recording
   - Add certificate data extraction methods
   - Fix timestamp issues (`certificate_last_checked_at`)

2. **Check Integration**
   - Integrate history recording into Spatie's monitoring workflow
   - Ensure both manual and scheduled checks are recorded
   - Track user who triggered manual checks
   - Record response times and detailed results

3. **Event Logging**
   - Log monitor creation, updates, deletions
   - Track configuration changes
   - Record status changes and significant events
   - Capture user actions and acknowledgments

### Phase 3: Dashboard Integration

1. **Trend Analysis**
   - Implement 7-day trend calculations
   - Add uptime percentage trends
   - Include response time performance trends
   - Show SSL certificate expiry trends

2. **Recent Activity**
   - Display recent monitoring history
   - Show manual vs automatic check distribution
   - Highlight recent events and status changes
   - Provide quick access to detailed history

3. **Performance Optimization**
   - Implement efficient queries for large datasets
   - Add database indexes for performance
   - Consider data aggregation for older records
   - Optimize for 50+ website monitoring

### Phase 4: Advanced Features

1. **Alert Integration**
   - Record all alert notifications in alert history
   - Track alert delivery status and failures
   - Implement user acknowledgment system
   - Add alert analytics and reporting

2. **Data Aggregation**
   - Implement weekly/monthly data aggregation
   - Create summary tables for long-term trends
   - Add data retention policies
   - Enable AI analysis on historical data

3. **Reporting and Analytics**
   - Create comprehensive monitoring reports
   - Add export functionality for historical data
   - Implement custom date range analysis
   - Build performance dashboards

## Technical Considerations

### Data Storage
- **MariaDB** is sufficient for this scale (50+ websites)
- Use JSON columns for flexible data storage
- Implement proper indexing for query performance
- Consider table partitioning for large datasets

### Performance
- Target < 20-second response for dashboard queries
- Use efficient database queries with proper indexing
- Implement caching for frequently accessed trend data
- Consider read replicas for reporting queries

### Data Retention
- Keep raw data for at least 1 month
- Implement weekly aggregation for older data
- Consider archival storage for very old data
- Provide user-configurable retention policies

### Security
- Log all user actions with IP addresses
- Implement proper access controls for historical data
- Sanitize sensitive information in logs
- Ensure GDPR compliance for user data

## Migration Strategy

1. **Backward Compatibility**: Existing monitoring functionality continues to work
2. **Gradual Rollout**: Implement in phases to minimize risk
3. **Data Migration**: No existing data migration needed (starting fresh)
4. **Testing**: Comprehensive testing of all history recording features
5. **Monitoring**: Monitor system performance after implementation

## Success Metrics

1. **Data Completeness**: 100% of checks recorded in history
2. **Performance**: Dashboard loads in < 2 seconds with historical data
3. **Reliability**: No missed check recordings
4. **User Adoption**: Users utilize historical data for analysis
5. **System Stability**: No performance degradation in monitoring

## Future Enhancements

1. **AI Analysis**: Use historical data for predictive analytics
2. **Custom Alerts**: Advanced alerting based on historical patterns
3. **API Access**: REST API for historical data access
4. **Integration**: Third-party monitoring tool integration
5. **Mobile App**: Mobile dashboard with historical data

---

*This plan provides a comprehensive approach to implementing a monitoring history system that will enable advanced analytics, trend analysis, and improved monitoring capabilities for the SSL Monitor application.*