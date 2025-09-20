# Getting Started with SSL Monitor

Welcome to SSL Monitor! This guide will help you get started with monitoring your website SSL certificates.

## 📋 Overview

SSL Monitor is a Laravel application that automatically checks your website SSL certificates and alerts you before they expire. Key features include:

- **Automated Daily Checks** - Certificates checked every day at 6:00 AM
- **Real-time Dashboard** - See all your SSL statuses at a glance  
- **Email Notifications** - Get alerts when certificates are expiring
- **Easy Management** - Add websites with instant SSL preview
- **Secure Configuration** - Manage SMTP settings in-app

## 🚀 First Steps

### 1. Login to Your Account

After your administrator has set up SSL Monitor, you'll receive login credentials. Navigate to your SSL Monitor URL and sign in.

### 2. Configure Email Notifications

Before adding websites, set up email notifications so you'll receive alerts:

1. **Navigate to Settings** → **Email Settings**
2. **Enter your SMTP details**:
   - **SMTP Host**: Your mail server (e.g., `mail.yourdomain.com`)
   - **Port**: Usually `587` for TLS or `465` for SSL
   - **Encryption**: Select `TLS` (recommended) or `SSL`
   - **Username**: Your email account username
   - **Password**: Your email account password
   - **From Address**: Email address for notifications
   - **From Name**: Display name (e.g., "SSL Monitor")

3. **Test your configuration** using the "Test Email" button
4. **Save your settings** once the test succeeds

> **💡 Tip**: Most email providers support TLS on port 587. Check your email provider's SMTP settings if you're unsure.

### 3. Add Your First Website

1. **Go to Websites** from the main navigation
2. **Click "Add Website"**
3. **Enter the website URL** (e.g., `https://example.com`)
4. **Preview SSL certificate** - The system will immediately check the certificate
5. **Review certificate details** - Expiry date, issuer, validity status
6. **Click "Add Website"** to start monitoring

### 4. View Your Dashboard

Return to the **Dashboard** to see:

- **Status Overview Cards** - Summary of valid, expiring, and expired certificates
- **Critical Issues** - Urgent attention needed for expired or invalid certificates
- **Recent Checks** - Latest SSL certificate verification results
- **Website List** - All monitored sites with current status

## 📊 Understanding SSL Status

### Status Types

| Status | Meaning | Action Needed |
|--------|---------|---------------|
| **✅ Valid** | Certificate is valid and not expiring soon | None |
| **⚠️ Expiring Soon** | Certificate expires within 14 days | Plan renewal |
| **❌ Expired** | Certificate has already expired | Immediate renewal required |
| **🔧 Invalid** | Certificate has issues (wrong domain, untrusted) | Fix certificate configuration |
| **❌ Error** | Unable to check certificate | Check website accessibility |

### Days Until Expiry

- **Green (30+ days)**: Certificate is healthy
- **Yellow (15-29 days)**: Monitor closely, plan renewal
- **Orange (8-14 days)**: Renewal needed soon
- **Red (0-7 days)**: Urgent renewal required

## ✅ Best Practices

### Website Management
- **Use HTTPS URLs** - Always enter `https://` for accurate monitoring
- **Monitor Main Domains** - Include your primary website and important subdomains
- **Check Regularly** - Review your dashboard weekly
- **Act on Alerts** - Don't ignore expiring certificate notifications

### Email Configuration  
- **Use Reliable SMTP** - Ensure your mail server is stable
- **Test Configuration** - Always test before saving settings
- **Monitor Email Delivery** - Check that notifications are being received
- **Keep Credentials Secure** - Only authorized users should access email settings

### Security
- **Strong Passwords** - Use secure passwords for your SSL Monitor account
- **Limited Access** - Only give access to users who need SSL monitoring
- **Regular Updates** - Keep your SSL Monitor instance updated

## 🔔 Email Notifications

Once configured, you'll receive email notifications for:

- **Certificates expiring in 14 days**
- **Certificates expiring in 7 days**  
- **Certificates expiring in 1 day**
- **Certificate errors or issues**

Notifications include:
- Website name and URL
- Current certificate status
- Days until expiry
- Certificate issuer information
- Recommended actions

## 🆘 Common Issues

### "Certificate Not Found" Errors
- **Check URL format** - Use `https://` prefix
- **Verify website accessibility** - Ensure site is online
- **Check DNS resolution** - Confirm domain points to correct server

### Email Notifications Not Working
- **Verify SMTP settings** - Double-check host, port, and credentials
- **Test email configuration** - Use built-in test functionality
- **Check spam folder** - Notifications might be filtered
- **Contact IT support** - If using corporate email server

### Dashboard Shows No Data
- **Add websites first** - Dashboard requires monitored websites
- **Wait for first check** - Initial SSL checks may take a few minutes
- **Check background jobs** - Ensure queue workers are running (admin task)

## 🎯 Next Steps

- **[Dashboard Overview](dashboard-overview.md)** - Learn about dashboard features
- **[Website Management](website-management.md)** - Advanced website management
- **[Email Configuration](email-configuration.md)** - Detailed SMTP setup
- **[SSL Status Guide](ssl-status-guide.md)** - Understanding certificate statuses

## 📞 Getting Help

If you encounter issues:

1. **Check this documentation** - Most common questions are answered here
2. **Review troubleshooting guide** - [Troubleshooting](troubleshooting.md)
3. **Contact your administrator** - For technical or access issues
4. **Test your setup** - Use built-in testing features when available

---

**Next**: [Dashboard Overview](dashboard-overview.md) - Learn how to use your SSL monitoring dashboard effectively.