# Notification Settings

SSL Monitor provides a comprehensive notification system to keep you informed about SSL certificate issues and website uptime problems. You can configure exactly which alerts you want to receive and customize your notification preferences.

## 📧 Email Notification Types

SSL Monitor supports multiple types of email notifications to cover all aspects of your website monitoring.

### SSL Certificate Notifications

**Certificate Expiring Soon**
- Sent when SSL certificates are approaching expiration
- Configurable notification periods (default: 7, 14, and 30 days before expiration)
- Includes certificate details and renewal instructions

**Certificate Expired**
- Immediate alert when a certificate has expired
- Critical priority notification
- Includes impact information and resolution steps

**SSL Check Errors**
- Sent when SSL certificate validation fails
- Covers connection issues, certificate mismatches, and configuration problems
- Includes technical error details for troubleshooting

### Uptime Monitoring Notifications ⭐ NEW

**Website Down**
- Immediate alert when your website becomes unreachable
- Includes HTTP status codes and error details
- Provides troubleshooting guidance

**Website Recovered**
- Confirmation when your website comes back online
- Includes downtime duration and current performance metrics
- Helps you verify everything is working properly

**Slow Response Times**
- Alert when website response times exceed thresholds
- Includes performance metrics and trending information
- Helps identify performance degradation

**Content Mismatch**
- Critical alert for potential hosting takeovers or unexpected content changes
- Detects when expected content is no longer found
- Includes security recommendations

## ⚙️ Configuring Notification Preferences

### Accessing Notification Settings

1. **Login** to your SSL Monitor account
2. **Navigate** to Settings → Notification Preferences
3. **Configure** your email and alert preferences

### Email Configuration

**Enable Email Notifications**
- Toggle to turn all email notifications on/off
- When disabled, no notifications will be sent regardless of other settings

**Email Address**
- Specify the email address where notifications should be sent
- Can be different from your account email
- Supports team email addresses or distribution lists

### SSL Certificate Alert Preferences

**Certificate Expiry Notices**
- **Days Notice**: Configure which expiry intervals trigger notifications
- **Default**: 7, 14, and 30 days before expiration
- **Custom**: Add your own notification intervals (e.g., 1, 3, 60 days)

**SSL Error Alerts**
- **Enable/Disable**: Control whether SSL validation errors trigger notifications
- **Recommended**: Keep enabled for critical SSL issues

### Uptime Monitoring Alert Preferences ⭐ NEW

**Downtime Alerts**
- **Enable/Disable**: Control notifications when websites go down
- **Immediate**: Notifications sent as soon as downtime is detected
- **Critical**: Highest priority alerts

**Recovery Notifications**
- **Enable/Disable**: Control notifications when websites come back online
- **Confirmation**: Helps verify successful recovery
- **Optional**: Can be disabled if you only want problem alerts

**Performance Alerts**
- **Slow Response**: Notifications for response time issues
- **Threshold-based**: Configurable performance thresholds
- **Trending**: Helps identify gradual performance degradation

**Content Validation Alerts**
- **Content Mismatch**: Critical security alerts for content changes
- **Takeover Detection**: Identifies potential hosting company takeovers
- **Security Priority**: Recommended to keep enabled

## 📱 Notification Delivery

### Email Templates

SSL Monitor uses professional email templates that include:

- **Clear Subject Lines**: Immediately identify the issue and affected website
- **Detailed Information**: Complete context about the problem
- **Actionable Guidance**: Step-by-step resolution instructions
- **Direct Links**: Quick access to check your website
- **Technical Details**: Error messages and diagnostic information

### Delivery Timing

**Immediate Alerts**
- Downtime notifications: Within 5 minutes of detection
- Content mismatch alerts: Immediate upon detection
- SSL errors: During scheduled checks or manual verification

**Scheduled Alerts**
- Certificate expiry notices: Daily check at configured intervals
- Performance summaries: Based on monitoring frequency

## 🔧 Best Practices

### Recommended Settings

**For Critical Websites**
- Enable all notification types
- Set multiple certificate expiry intervals (7, 14, 30 days)
- Enable immediate downtime and recovery alerts
- Enable content validation for security

**For Development/Testing Sites**
- Disable recovery notifications to reduce noise
- Focus on SSL certificate and critical downtime alerts
- Consider disabling performance alerts for staging environments

**For Large Teams**
- Use team email addresses or distribution lists
- Configure role-based notification preferences
- Enable daily digest options for summary reports

### Avoiding Alert Fatigue

**Prioritize Critical Alerts**
- Always enable: Downtime, SSL expiry, content mismatch
- Consider disabling: Recovery notifications for stable sites
- Customize: Performance thresholds based on your requirements

**Use Appropriate Email Addresses**
- **Operations Teams**: All critical alerts
- **Development Teams**: SSL and performance alerts
- **Management**: Summary reports and critical issues only

## 🎯 Advanced Configuration

### Multiple Notification Addresses

While the interface supports one primary email address, you can:
- Use email distribution lists for team notifications
- Configure mail forwarding rules
- Set up email filters for different alert types

### Integration with External Systems

Notifications can be integrated with:
- **Slack**: Using email-to-Slack forwarding
- **PagerDuty**: Email integration for critical alerts
- **Ticketing Systems**: Email-based ticket creation

## 🔍 Troubleshooting Notifications

### Not Receiving Notifications

**Check Email Settings**
1. Verify your email address in Notification Preferences
2. Check spam/junk folders for SSL Monitor emails
3. Ensure SMTP is properly configured (Admin Settings)

**Check Notification Preferences**
1. Verify "Email Notifications" is enabled
2. Check specific alert type settings
3. Confirm websites have monitoring enabled

### Too Many Notifications

**Adjust Sensitivity**
1. Disable recovery notifications for stable sites
2. Increase performance thresholds
3. Reduce certificate expiry notification intervals

**Use Filters**
1. Set up email rules to organize alerts
2. Create separate folders for different alert types
3. Use email client filtering for priority levels

## 📊 Notification History

SSL Monitor tracks notification delivery to help you:
- **Verify Delivery**: Confirm notifications were sent
- **Audit Alerts**: Review notification history
- **Troubleshoot Issues**: Identify delivery problems

Access notification history through:
1. **Settings** → Notification Preferences → History
2. **Individual Website** → Notification Log
3. **Dashboard** → Recent Alerts panel

---

For technical issues with email delivery, see the [Email Configuration Guide](email-configuration.md) or contact your system administrator.

For general troubleshooting, see the [Troubleshooting Guide](troubleshooting.md).