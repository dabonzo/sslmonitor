# Email Configuration Guide

This guide explains how to configure email notifications for SSL certificate monitoring using your own mail server.

## 📧 Overview

SSL Monitor can send email notifications when certificates are expiring or have issues. The email configuration is managed entirely within the application - no server access required.

## ⚙️ Accessing Email Settings

1. **Login** to SSL Monitor
2. Navigate to **Settings** → **Email Settings**
3. You'll see either:
   - **Configuration Form** (if no settings exist)
   - **Current Settings Display** (if already configured)

## 🔧 SMTP Configuration

### Required Information

Before starting, gather this information from your email provider:

| Setting | Description | Example |
|---------|-------------|---------|
| **SMTP Host** | Mail server hostname or IP | `mail.example.com` |
| **Port** | SMTP server port | `587` (TLS) or `465` (SSL) |
| **Encryption** | Security method | `TLS` (recommended) |
| **Username** | Email account username | `alerts@example.com` |
| **Password** | Email account password | `your-password` |
| **From Address** | Sender email address | `ssl-alerts@example.com` |
| **From Name** | Display name for emails | `SSL Monitor` |

### Step-by-Step Configuration

#### 1. Enter SMTP Server Details

- **SMTP Host**: Enter your mail server hostname
  - Corporate: Often `mail.company.com` or `smtp.company.com`
  - Gmail: `smtp.gmail.com`
  - Outlook: `smtp-mail.outlook.com`

- **Port**: Select based on encryption type
  - `587` for TLS (most common)
  - `465` for SSL
  - `25` for unencrypted (not recommended)

#### 2. Choose Encryption Method

- **TLS (Recommended)**: Most secure and widely supported
- **SSL**: Legacy but still secure
- **None**: Not recommended for production

#### 3. Enter Authentication Details

- **Username**: Usually your full email address
- **Password**: Your email account password
  - Use the eye icon to toggle password visibility
  - Consider using app passwords for Gmail/Outlook

#### 4. Configure Sender Information

- **From Address**: Email address that will appear as sender
  - Should be a valid address on your domain
  - Recipients will see this as the sender

- **From Name**: Friendly display name
  - Examples: "SSL Monitor", "Security Alerts", "IT Notifications"

#### 5. Advanced Settings

- **Connection Timeout**: How long to wait for server response (default: 30 seconds)
- **Verify SSL Certificates**: Leave enabled for production (recommended)

### 📧 Testing Your Configuration

**Always test before saving!**

1. **Click "Test Email"** after entering all settings
2. **Wait for test result** - appears below the form
3. **Check your inbox** - Test email should arrive within minutes
4. **Verify success message** - Green message indicates success
5. **Address any errors** - Red messages show configuration issues

#### Test Email Content

The test email includes:
- Subject: "SSL Monitor - Email Configuration Test"
- Simple message confirming email configuration works
- Sent to the configured "From Address"

## 💾 Saving Configuration

1. **Test first** - Always test before saving
2. **Click "Save Settings"** - Only appears after successful test
3. **Confirmation message** - Green message confirms settings saved
4. **Automatic activation** - Settings become active immediately

## 🔄 Updating Configuration

To modify existing email settings:

1. **Click "Edit Settings"** in the current settings display
2. **Modify fields** as needed
3. **Test configuration** with new settings
4. **Save changes** once test succeeds

> **💡 Note**: Password field will be empty when editing. Leave blank to keep current password, or enter new password to update.

## 📨 Email Notification Types

Once configured, you'll receive notifications for:

### Certificate Expiry Alerts
- **14 days before expiry** - Early warning
- **7 days before expiry** - Urgent attention needed  
- **1 day before expiry** - Critical renewal required
- **After expiry** - Immediate action required

### Certificate Error Alerts
- **Invalid certificates** - Wrong domain, untrusted issuer
- **Connection failures** - Website unreachable
- **SSL configuration issues** - Certificate problems

### Email Content Includes
- Website name and URL
- Certificate status and issue description
- Days until expiry (if applicable)
- Certificate issuer information
- Recommended next steps

## 🔒 Security Considerations

### Password Security
- **Passwords encrypted** - Stored securely using Laravel encryption
- **Never transmitted in plain text** - Passwords encrypted before database storage
- **Limited access** - Only authorized users can view/modify email settings

### Best Practices
- **Use strong passwords** - Complex passwords for email accounts
- **App passwords recommended** - Use app-specific passwords for Gmail/Outlook
- **Limit access** - Only authorized users should configure email settings
- **Test regularly** - Verify email notifications are working monthly

## 🚨 Troubleshooting

### Common Issues

#### "Authentication Failed" Error
- **Double-check credentials** - Verify username and password
- **Try app password** - Gmail/Outlook may require app-specific passwords
- **Check account status** - Ensure email account is active

#### "Connection Timeout" Error
- **Verify SMTP host** - Check hostname spelling
- **Try different port** - Test ports 587, 465, or 25
- **Check firewall** - Ensure SMTP ports aren't blocked

#### "SSL Certificate Verification Failed"
- **Try different encryption** - Switch between TLS and SSL
- **Disable SSL verification** - Only for internal/test environments
- **Check server certificates** - Mail server may have certificate issues

#### Test Email Not Received
- **Check spam folder** - Notifications might be filtered
- **Verify from address** - Must be valid email address
- **Wait longer** - Some servers have delays
- **Check email logs** - Contact IT if using corporate mail server

### Getting Help

1. **Use test functionality** - Built-in testing identifies most issues
2. **Check error messages** - Specific errors guide troubleshooting
3. **Contact IT support** - For corporate email server issues
4. **Review server logs** - Admins can check application logs

## 📋 Common SMTP Settings

### Popular Email Providers

#### Gmail
- **Host**: `smtp.gmail.com`
- **Port**: `587`
- **Encryption**: `TLS`
- **Note**: Requires app password, not regular password

#### Microsoft Outlook/Office 365
- **Host**: `smtp-mail.outlook.com`
- **Port**: `587`
- **Encryption**: `TLS`
- **Note**: May require app password

#### Generic Corporate Settings
- **Host**: `mail.company.com` or `smtp.company.com`
- **Port**: `587` (TLS) or `465` (SSL)
- **Encryption**: `TLS` (recommended)
- **Note**: Contact IT for exact settings

## 🎯 Next Steps

- **[Getting Started](getting-started.md)** - Complete setup walkthrough
- **[Website Management](website-management.md)** - Add websites to monitor
- **[Dashboard Overview](dashboard-overview.md)** - Monitor your SSL certificates
- **[Troubleshooting](troubleshooting.md)** - Common issues and solutions

---

**Previous**: [Getting Started](getting-started.md) | **Next**: [Website Management](website-management.md)