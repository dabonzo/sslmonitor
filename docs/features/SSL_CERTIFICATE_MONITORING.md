# SSL Certificate Monitoring

## Overview

SSL Monitor v4 uses intelligent, adaptive thresholds to determine when SSL certificates are approaching expiration. Unlike traditional monitoring systems that use fixed day-based thresholds, our system calculates expiration status based on the percentage of the certificate's total validity period remaining.

This approach solves a critical problem: a certificate with 73 days remaining looks very different depending on its total validity:
- **Let's Encrypt (90-day cert)**: 73 days = 81% of lifetime = Plenty of time
- **1-Year commercial**: 73 days = 20% of lifetime = Time to renew
- **2-Year commercial**: 73 days = 10% of lifetime = Urgent!

## How Dynamic Thresholds Work

The system considers both:

1. **Percentage-based threshold**: Certificate enters "expires_soon" status when less than 33% of its validity period remains
2. **Minimum day threshold**: Certificates always show "expires_soon" when less than 30 days remain (safety net)

The system uses **whichever threshold is more conservative** (triggers the alert sooner).

### Example Calculation

For a 1-year commercial certificate:
```
Total validity period: 365 days
33% threshold: 365 × 0.33 = 120 days
Minimum threshold: 30 days

When 73 days remain:
Percentage remaining: (73 / 365) × 100 = 20%
Status: EXPIRES_SOON (20% < 33%)
```

## Certificate Status Levels

| Status | Indicator | Meaning |
|--------|-----------|---------|
| **Valid** | ✅ | Certificate is healthy, more than 33% of validity period remaining |
| **Expires Soon** | ⚠️ | Certificate should be renewed (< 33% remaining OR < 30 days) |
| **Expired** | ❌ | Certificate expiration date has passed |
| **Invalid** | 🚫 | Certificate has validation errors (hostname mismatch, untrusted CA, etc.) |

## Real-World Examples

### Let's Encrypt Certificate (90-day validity)

| Metric | Value |
|--------|-------|
| **Issue Date** | 2025-10-27 |
| **Expiration Date** | 2025-12-26 |
| **Total Validity** | 90 days |
| **Days Remaining** | 73 days |
| **Percentage Remaining** | 81% |
| **33% Threshold** | 30 days |
| **Status** | ✅ **Valid** |

**Why Valid?** With 81% of the certificate's lifetime still remaining, alerting would be premature and create alert fatigue. You have plenty of time to plan renewal.

---

### 1-Year Commercial Certificate (365-day validity)

| Metric | Value |
|--------|-------|
| **Issue Date** | 2024-10-27 |
| **Expiration Date** | 2025-10-27 |
| **Total Validity** | 365 days |
| **Days Remaining** | 73 days |
| **Percentage Remaining** | 20% |
| **33% Threshold** | 120 days |
| **Status** | ⚠️ **Expires Soon** |

**Why Expires Soon?** With only 20% of the certificate's lifetime remaining, you're in the renewal window. Commercial certificate renewal typically takes 1-2 weeks, so action is needed.

---

### 2-Year Commercial Certificate (730-day validity)

| Metric | Value |
|--------|-------|
| **Issue Date** | 2023-10-27 |
| **Expiration Date** | 2025-10-27 |
| **Total Validity** | 730 days |
| **Days Remaining** | 73 days |
| **Percentage Remaining** | 10% |
| **33% Threshold** | 241 days |
| **Status** | ⚠️ **Expires Soon** |

**Why Expires Soon?** With only 10% of the certificate's lifetime remaining, renewal should already be in progress. This is critical timing.

---

### Edge Case: Ultra-Long Certificate with Tight Deadline

| Metric | Value |
|--------|-------|
| **Certificate Validity** | 10 years (3650 days) |
| **Days Remaining** | 25 days |
| **Percentage Remaining** | 0.68% |
| **33% Threshold** | 1205 days |
| **Minimum Threshold** | 30 days |
| **Status** | ⚠️ **Expires Soon** |

**Why Expires Soon?** Even though this is a 10-year certificate, the 30-day minimum ensures we catch it. The percentage (0.68%) would trigger the alert anyway, but the minimum provides a critical safety net.

## Benefits of This Approach

✅ **Appropriate Alerts for Short-Lived Certificates**
- Let's Encrypt certificates won't trigger false alarms mid-lifetime
- Reduces alert fatigue while maintaining security

✅ **Earlier Warnings for Long-Lived Certificates**
- Commercial certificates get alerts with sufficient renewal time
- Gives your team 2-4 months to plan and execute renewal

✅ **Consistent Monitoring Logic**
- All certificates evaluated using the same percentage-based algorithm
- Behavior is predictable and fair across different certificate types

✅ **Safety Net for Edge Cases**
- 30-day minimum ensures no certificate is missed
- Protects against ultra-long certificates that would otherwise get warnings too late

✅ **Less Manual Configuration**
- No need to adjust threshold settings based on certificate provider
- System adapts automatically

## Technical Details

### Data Extraction

SSL Monitor extracts certificate metadata using OpenSSL during SSL checks:

- **Issued Date**: "Not Valid Before" timestamp from the certificate
- **Expiration Date**: "Not Valid After" timestamp from the certificate
- **Serial Number**: Unique certificate identifier (used to detect renewals)
- **Issuer**: Certificate Authority (e.g., Let's Encrypt, DigiCert)
- **Subject**: Domain name(s) covered by the certificate

### Calculation Formula

```
total_validity_days = expiration_date - issue_date
days_remaining = expiration_date - today
percent_remaining = (days_remaining / total_validity_days) * 100

Status = "expires_soon" if:
  - percent_remaining < 33% OR
  - days_remaining < 30
```

### Backward Compatibility

For older monitoring results where the issue date wasn't extracted:
- Falls back to traditional 30-day threshold
- Allows gradual migration as new checks populate issue date data
- Eventually all results will have complete data for percentage-based logic

## Certificate Renewal Workflow

### When You Receive an Alert

1. **Check Expiration Date**: Click the certificate to see exact expiration date
2. **Review Certificate Details**: Verify issuer, domains, and validity period
3. **Plan Renewal**: Order renewal 2-4 weeks before expiration
4. **Test Installation**: Verify new certificate in staging before production
5. **Deploy**: Install new certificate 1-2 days before old one expires
6. **Verify**: Monitor shows "Valid" status immediately after deployment

### Renewal Timeline by Certificate Type

| Certificate Type | Action Timeline | Safety Window |
|-----------------|-----------------|---------------|
| **Let's Encrypt** | At 33% remaining (~30 days) | Run auto-renewal 30 days before expiry |
| **1-Year Commercial** | At 33% remaining (~120 days) | Order 60 days before, install 30 days before |
| **2-Year Commercial** | At 33% remaining (~241 days) | Order 120 days before, install 30 days before |

## Monitoring Dashboard Integration

### Certificate Status Badge

The monitoring dashboard displays certificate status with color coding:

- **Green**: ✅ Valid - No action needed
- **Yellow**: ⚠️ Expires Soon - Schedule renewal
- **Red**: ❌ Expired - Immediate action required
- **Gray**: 🚫 Invalid - Check certificate configuration

### Monitoring Results

Each check records:
- Certificate status (valid/expires_soon/expired/invalid)
- Days until expiration
- Percentage of validity remaining
- Issue and expiration dates
- Certificate issuer and subject

### Historical Tracking

SSL Monitor maintains a complete history of all certificate checks, allowing you to:
- Track certificate renewal dates
- Monitor renewal patterns
- Identify problematic certificates
- Generate compliance reports

## Configuration

### Default Settings

SSL Monitor uses these default settings (no configuration required):

- **33% Expiration Threshold**: Triggers "expires_soon" alert
- **30-Day Minimum**: Safety net for any certificate type
- **Certificate Check Interval**: 12 hours (adjusts based on certificate status)
- **Frequent Checks for Expiring**: Daily checks when < 7 days remaining
- **Frequent Checks for Soon**: Every 4 hours when < 30 days remaining

### Alert Frequency

The system intelligently adjusts check frequency based on certificate urgency:

| Days Remaining | Check Frequency | Purpose |
|---|---|---|
| > 30 days | Every 12 hours | Standard monitoring |
| 8-30 days | Every 4 hours | Increased vigilance |
| ≤ 7 days | Every 24 hours | Critical monitoring |
| Invalid cert | Every 12 hours | Error monitoring |

## Troubleshooting

### Certificate Shows "Expires Soon" But Has Months Left

**Possible Causes:**
- Certificate is short-lived (Let's Encrypt, trial certificates)
- Certificate percentage remaining is genuinely < 33%

**Resolution:**
- Check the actual percentage remaining in certificate details
- This is expected behavior for short-lived certificates
- Schedule renewal per your certificate type's timeline

### Certificate Shows "Invalid" Status

**Possible Causes:**
- Hostname mismatch (certificate doesn't match domain)
- Untrusted certificate authority
- Self-signed certificate
- Certificate chain issue

**Resolution:**
1. Verify certificate covers the correct domain
2. Check certificate issuer is trusted
3. For self-signed certs, this is expected
4. Ensure full certificate chain is installed

### Certificate Doesn't Update After Renewal

**Possible Causes:**
- SSL check hasn't run yet (wait up to 12 hours)
- Certificate not properly deployed
- DNS still pointing to old server

**Resolution:**
1. Force manual SSL check from monitor details
2. Verify certificate is installed and accessible
3. Check DNS resolution points to correct server
4. Wait 5 minutes and refresh dashboard

## FAQ

**Q: Why does my Let's Encrypt certificate show "Expires Soon" after just 30 days?**

A: This is normal. Let's Encrypt certificates are 90 days long, so at 33 days remaining, you're at the 33% threshold. This aligns with Let's Encrypt's recommended auto-renewal timing.

**Q: Why does the system check more frequently as expiration approaches?**

A: More frequent checks give you earlier warning if renewal fails or certificate deployment has issues. The closer to expiration, the more important it is to catch problems immediately.

**Q: Can I customize the expiration thresholds?**

A: The current implementation uses optimal defaults (33% + 30-day minimum). These settings are not yet customizable to maintain simplicity, but can be made configurable in future versions.

**Q: What happens if I ignore an "Expires Soon" alert?**

A: The certificate will continue to be monitored. When expiration date passes, status changes to "Expired" and the certificate becomes non-functional, potentially causing service outages.

**Q: How often does the system check certificates?**

A: Default check interval is 12 hours, with more frequent checks automatically triggered for certificates in the expiration window (every 4 hours if < 30 days, daily if < 7 days).

**Q: Does the system send notifications?**

A: Yes, SSL Monitor integrates with your email system to send notifications for certificate expiration alerts. Configure notification recipients in team settings.

## See Also

- **Monitoring Architecture** - Technical overview of the monitoring system
- **API Documentation** - Programmatic access to monitoring data
- **SSL Certificate Best Practices** - Let's Encrypt certificate types
