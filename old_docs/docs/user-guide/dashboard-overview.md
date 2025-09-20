# Dashboard Overview

Complete guide to understanding and using your SSL Monitor dashboard effectively.

## 🎯 Overview

The SSL Monitor dashboard is your central hub for monitoring website SSL certificates. It provides at-a-glance status information, critical alerts, and detailed certificate information.

## 📊 Dashboard Layout

### Top Navigation
- **Dashboard** - Current page with SSL overview
- **Websites** - Manage monitored websites  
- **Settings** - Account and email configuration
- **User Menu** - Profile settings and logout

### Main Dashboard Sections
1. **Status Overview Cards** - Summary statistics
2. **Critical Issues** - Urgent attention items
3. **Recent SSL Checks** - Latest verification results
4. **Website List** - All monitored sites with status

## 📈 Status Overview Cards

### Websites Card
- **Total Count** - Number of monitored websites
- **Percentage Active** - Websites with valid certificates
- **Quick Indicator** - Green (good), Yellow (warnings), Red (issues)

### Valid Certificates Card
- **Count** - Websites with valid, non-expiring certificates
- **Percentage** - Proportion of total websites
- **Status Colors**:
  - **Green (90-100%)** - Excellent SSL health
  - **Yellow (70-89%)** - Some attention needed
  - **Red (<70%)** - Significant issues require attention

### Expiring Soon Card
- **Count** - Certificates expiring within 14 days
- **Urgency Level** - Based on how soon certificates expire
- **Action Required** - Immediate attention for renewals

### Issues Card  
- **Count** - Certificates with errors or problems
- **Issue Types** - Invalid, expired, or connection failures
- **Priority** - Critical issues require immediate action

## 🚨 Critical Issues Section

### When Issues Appear
This section appears when you have:
- **Expired certificates** - Already past expiration date
- **Expiring certificates** - Within 7 days of expiry
- **Invalid certificates** - Configuration problems
- **Connection failures** - Unable to check certificate

### Issue Information Displayed
- **Website Name** - Friendly name or URL
- **Issue Type** - Specific problem description
- **Days Until Expiry** - For expiring certificates
- **Last Checked** - When issue was detected
- **Action Required** - Recommended next steps

### Example Issues
- "**example.com** - Certificate expired 3 days ago"
- "**shop.example.com** - Certificate expires in 2 days"  
- "**api.example.com** - Invalid certificate (wrong domain)"
- "**blog.example.com** - Connection failed (unreachable)"

## ⏰ Recent SSL Checks

### Information Displayed
- **Website Name** - Site that was checked
- **Status Result** - Check outcome (Valid, Expiring, Invalid, etc.)
- **Days Until Expiry** - Time remaining on certificate
- **Check Time** - When verification was performed
- **Certificate Issuer** - Who issued the SSL certificate

### Status Icons and Colors
- **✅ Green** - Valid certificate, no issues
- **⚠️ Yellow** - Expiring soon (15-30 days)
- **🔶 Orange** - Expiring very soon (8-14 days)  
- **❌ Red** - Expired, invalid, or error
- **🔧 Blue** - Recently added, first check pending

### Understanding Check Results
- **Recent checks show within 24 hours** by default
- **Automatic checks run daily** at 6:00 AM
- **Manual checks** triggered from website management
- **Failed checks** indicate connectivity or certificate issues

## 🌐 Website List Section

### Website Information
Each website shows:
- **Website Name** - Friendly name or domain
- **URL** - Full website address
- **SSL Status** - Current certificate status
- **Expiry Date** - When certificate expires
- **Days Remaining** - Countdown to expiry
- **Last Checked** - Most recent verification

### Status Indicators
- **Green Badge** - Certificate valid (30+ days remaining)
- **Yellow Badge** - Expiring soon (15-29 days)
- **Orange Badge** - Expiring very soon (8-14 days)
- **Red Badge** - Expired or invalid
- **Gray Badge** - Never checked or checking in progress

### Quick Actions
- **Click website name** - View detailed information
- **Status badge** - Quick status reference
- **Last checked time** - Hover for exact timestamp

## 🔍 Understanding SSL Status

### Certificate Lifecycle
1. **Valid** - Certificate active and trusted
2. **Expiring Soon** - Renewal needed within timeframe
3. **Expiring Very Soon** - Urgent renewal required
4. **Expired** - Certificate no longer valid
5. **Invalid** - Certificate has configuration issues

### Expiry Timeframes
- **30+ days** - Healthy, no immediate action needed
- **15-29 days** - Monitor closely, plan renewal
- **8-14 days** - Schedule renewal soon
- **1-7 days** - Urgent renewal required
- **0 days (expired)** - Immediate action required

### Common Status Messages
- **"Valid for X days"** - Certificate healthy
- **"Expires in X days"** - Renewal needed
- **"Expired X days ago"** - Immediate renewal required
- **"Invalid certificate"** - Configuration problem
- **"Connection failed"** - Website unreachable
- **"Check pending"** - First verification in progress

## 📱 Dashboard Responsiveness

### Desktop View
- **Full layout** with all sections visible
- **Side-by-side cards** for efficient space usage
- **Detailed information** in expanded format

### Tablet View  
- **Stacked cards** maintaining readability
- **Condensed website list** with essential information
- **Touch-friendly navigation** elements

### Mobile View
- **Single column layout** optimized for small screens
- **Collapsible sections** to save space
- **Swipe-friendly** interface elements
- **Essential information prioritized**

## 🔄 Real-time Updates

### Auto-refresh Features
- **Dashboard data updates** automatically every 5 minutes
- **Status changes reflected** in real-time
- **New SSL checks** appear as they complete
- **Critical issues** highlighted immediately

### Manual Refresh
- **Browser refresh** updates all dashboard data
- **Individual website checks** can be triggered manually
- **Background processing** continues regardless of page views

## 📋 Dashboard Best Practices

### Daily Monitoring
- **Check dashboard daily** for new issues
- **Address critical issues** immediately
- **Plan certificate renewals** based on expiry dates
- **Monitor trends** in SSL health over time

### Efficient Workflow
1. **Review status cards** for overall health
2. **Address critical issues** first
3. **Plan upcoming renewals** from expiring list
4. **Investigate failed checks** for connectivity issues
5. **Add new websites** as your infrastructure grows

### Proactive Management
- **Set renewal reminders** 30 days before expiry
- **Monitor certificate issuers** for consistency
- **Track certificate types** (wildcard vs single domain)
- **Document certificate renewal procedures**

## 🚨 Alert Response Guide

### Immediate Actions for Critical Issues

#### Expired Certificates
1. **Renew certificate immediately** - Business impact likely
2. **Update certificate** on web server
3. **Restart web server** to load new certificate
4. **Verify fix** by checking website directly
5. **Monitor dashboard** for status update

#### Expiring Within 7 Days  
1. **Schedule renewal** within 24 hours
2. **Prepare certificate installation** procedure
3. **Notify stakeholders** of planned maintenance
4. **Set backup reminder** for 3 days before expiry

#### Invalid Certificates
1. **Check certificate configuration** on server
2. **Verify domain names** match certificate
3. **Check certificate chain** completeness
4. **Test with SSL checker tools** for detailed errors

#### Connection Failures
1. **Verify website accessibility** from multiple locations
2. **Check DNS resolution** for domain
3. **Test server connectivity** and firewall rules
4. **Review server logs** for connection issues

## 🎯 Dashboard Customization

### Personalization Options
- **Time zone display** automatically adjusted to user settings
- **Date format** follows browser locale preferences
- **Dark mode support** available through user preferences
- **Mobile optimization** automatically applied

### Future Enhancements
The dashboard may include these features in future updates:
- **Custom alert thresholds** for expiry warnings
- **Email digest settings** for periodic summaries
- **Export capabilities** for reporting
- **Advanced filtering** options for large website lists

## 🔗 Quick Navigation

### Dashboard Actions
- **Add Website** - Quick access from any dashboard section
- **View Details** - Click any website name for comprehensive information
- **Email Settings** - Configure notifications from settings menu
- **Manual Refresh** - Force update of dashboard data

### Keyboard Shortcuts
- **R** - Refresh dashboard data
- **A** - Add new website (when available)
- **S** - Navigate to settings
- **H** - Navigate to home/dashboard

## 🎯 Next Steps

- **[Website Management](website-management.md)** - Learn to add and manage websites
- **[Email Configuration](email-configuration.md)** - Set up SMTP notifications
- **[SSL Status Guide](ssl-status-guide.md)** - Understand certificate statuses
- **[Troubleshooting](troubleshooting.md)** - Resolve common issues

---

**Previous**: [Getting Started](getting-started.md) | **Next**: [Website Management](website-management.md)