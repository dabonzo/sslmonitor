# Uptime Monitoring Guide

Advanced uptime monitoring that goes far beyond simple HTTP 200 checks to provide comprehensive website validation.

## 🎯 Overview

SSL Monitor's uptime monitoring uses a **multi-level validation system** to detect issues that simple ping tests miss:

- ✅ **HTTP Status Validation** - Configurable expected status codes
- ✅ **Content Validation** - Detect expected content presence
- ✅ **Forbidden Content Detection** - Identify hosting company error pages
- ✅ **Response Time Monitoring** - Configurable performance thresholds
- ✅ **Redirect Handling** - Intelligent redirect following with loop prevention

## 🔍 Why Multi-Level Validation?

### The Problem with Simple HTTP Checks
Many monitoring services only check if a website returns HTTP 200 OK. This misses critical scenarios:

- 🚫 **Hosting Company Default Pages** - Site returns 200 but shows "Domain Parked" page
- 🚫 **Maintenance Screens** - Site returns 200 but displays maintenance message
- 🚫 **Error Messages** - Site returns 200 but shows application errors
- 🚫 **Performance Issues** - Site responds but takes too long to load

### Our Solution
SSL Monitor validates multiple aspects of your website's health:

1. **HTTP Status** - Ensures correct status code (default: 200)
2. **Expected Content** - Confirms specific text exists on the page
3. **Forbidden Content** - Alerts if error messages are detected
4. **Response Time** - Monitors page load performance
5. **Redirect Safety** - Handles redirects while preventing infinite loops

## ⚙️ Configuration Options

### Basic Settings

#### Expected Status Code
- **Default**: 200 (OK)
- **Common alternatives**: 201 (Created), 204 (No Content)
- **Use case**: APIs or special endpoints with non-200 success codes

#### Maximum Response Time
- **Default**: 30,000ms (30 seconds)
- **Recommended**: 5,000ms (5 seconds) for production sites
- **Use case**: Monitor performance and detect slow responses

### Content Validation

#### Expected Content
Text that **must be present** on your website for it to be considered "up":

```
✅ Good examples:
- "Welcome to Our Store"
- "Copyright 2024"
- "Dashboard" (for login-protected pages)
- Your company name

❌ Avoid generic text:
- "Home"
- "Welcome"
- "Loading..."
```

#### Forbidden Content
Text that indicates a **problem** if found on your website:

```
✅ Good examples:
- "Error 503"
- "Site Temporarily Unavailable" 
- "Domain Parked"
- "This site can't be reached"
- "Under Construction"
- "Database Error"

💡 Pro tip: Check what your hosting company shows
when sites are down and add those phrases here.
```

### Redirect Settings

#### Follow Redirects
- **Default**: Enabled
- **Disabled**: Treats any redirect (301, 302) as down
- **Enabled**: Follows redirects to final destination

#### Maximum Redirects
- **Default**: 3 redirects
- **Range**: 1-10 redirects
- **Protection**: Prevents infinite redirect loops

## 📊 Uptime Statuses

### Status Types

#### 🟢 Up
- HTTP status matches expected code
- All content validation passes
- Response time within threshold
- No issues detected

#### 🔴 Down  
- HTTP error (4xx, 5xx status codes)
- Connection timeout or network error
- Unexpected status code received
- Too many redirects encountered

#### 🟡 Slow
- Correct HTTP status and content
- Response time exceeds threshold
- Still functional but performance issue

#### 🟠 Content Mismatch
- Correct HTTP status and timing
- Expected content missing OR forbidden content found
- Often indicates hosting company takeover or application errors

#### ❓ Unknown
- No recent uptime checks available
- Monitoring not yet started
- System unable to reach website

### Status Priority

When multiple issues exist, SSL Monitor prioritizes statuses as:
1. **Down** (most critical - complete failure)
2. **Content Mismatch** (critical - wrong content served)
3. **Slow** (warning - performance issue) 
4. **Up** (healthy - all checks pass)

## 📈 Uptime Statistics

### Uptime Percentage Calculation

SSL Monitor calculates uptime as:
```
Uptime % = (Number of "Up" checks / Total checks) × 100
```

**Important Notes:**
- Only **"Up"** status counts as uptime
- **"Slow"** and **"Content Mismatch"** count as downtime
- Configurable time periods (7, 30, 90 days)
- Excludes periods with no monitoring data

### Example Scenarios

#### Scenario 1: Hosting Company Takeover
```
Website Status: 🔴 Down (Content Mismatch)
HTTP Status: 200 OK ✅
Response Time: 450ms ✅  
Expected Content: "Our Store" ❌ (not found)
Forbidden Content: "Domain Parked" ❌ (found)
Result: Detected hosting company default page
```

#### Scenario 2: Slow Performance
```
Website Status: 🟡 Slow
HTTP Status: 200 OK ✅
Response Time: 8,500ms ❌ (exceeds 5,000ms threshold)
Content Validation: ✅ (all pass)
Result: Site working but performance issue detected
```

#### Scenario 3: Maintenance Mode
```
Website Status: 🟠 Content Mismatch  
HTTP Status: 200 OK ✅
Response Time: 1,200ms ✅
Expected Content: "Dashboard" ❌ (not found)
Forbidden Content: "Under Maintenance" ❌ (found)
Result: Detected maintenance screen
```

## 🔔 Downtime Incidents

### Automatic Incident Management

SSL Monitor automatically:
- **Creates incidents** when transitioning from Up → Down/Slow/Content Mismatch
- **Continues incidents** while status remains problematic  
- **Resolves incidents** when transitioning back to Up
- **Calculates duration** from start to resolution
- **Tracks incident types**: HTTP Error, Timeout, Content Mismatch

### Incident Types

#### HTTP Error
- 4xx Client errors (404, 403, etc.)
- 5xx Server errors (500, 502, 503, etc.)
- Unexpected status codes

#### Timeout
- Connection timeouts
- Slow response times (exceeding threshold)
- Network connectivity issues

#### Content Mismatch
- Missing expected content
- Forbidden content detected
- Wrong page content served

## 🛠️ Configuration Best Practices

### For E-commerce Sites
```yaml
Expected Content: "Add to Cart" or "Shop Now"  
Forbidden Content: "Error 503,Database Error,Maintenance"
Max Response Time: 3000ms
Follow Redirects: Yes
```

### For Corporate Websites
```yaml
Expected Content: "About Us" or Company Name
Forbidden Content: "Domain Parked,Under Construction"  
Max Response Time: 5000ms
Follow Redirects: Yes
```

### For APIs
```yaml
Expected Status: 200 or 201
Expected Content: '"status":"success"' or API-specific response
Forbidden Content: '"error":,"exception":'
Max Response Time: 2000ms
Follow Redirects: No
```

### For WordPress Sites  
```yaml
Expected Content: Site title or "WordPress"
Forbidden Content: "Database Error,White Screen,Fatal Error"
Max Response Time: 4000ms
Follow Redirects: Yes
```

## 🔧 Troubleshooting

### Common Issues

#### Website Showing as Down but Loads Fine
- **Check expected content** - ensure text exists on page
- **Review forbidden content** - may be detecting false positives
- **Verify status code** - API endpoints may return 201 instead of 200

#### False Content Mismatch Alerts
- **Dynamic content** - avoid time-sensitive expected content
- **Login walls** - use content that appears before authentication
- **A/B testing** - choose content that appears in all variants

#### High Response Times
- **CDN issues** - monitor from multiple locations if possible
- **Database performance** - optimize queries and indexes  
- **Third-party APIs** - identify slow external dependencies

### Getting Help

- **Check logs** - Admin can review detailed error messages
- **Test manually** - Visit website to confirm actual behavior
- **Adjust thresholds** - Fine-tune response time and content validation
- **Contact support** - Provide specific URL and error details

## 🎯 Next Steps

- **[Dashboard Overview](dashboard-overview.md)** - View uptime status and trends
- **[Website Management](website-management.md)** - Configure uptime settings per site
- **[Team Management](team-management.md)** - Share uptime monitoring with colleagues

---

**SSL Monitor** - Professional website monitoring with intelligent validation.