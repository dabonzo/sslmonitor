# SSL Monitor v4 - Comprehensive Manual Testing Plan

## Test Environment Setup

**Test User Credentials:**
- **Email:** bonzo@konjscina.com
- **Password:** to16ro12

**Test Websites:**
- **Personal → Team Transfer:** omp.office-manager-pro.com, www.fairnando.at, www.redgas.at
- **Stays Personal:** www.gebrauchte.at

**Team Information:**
- **Team Name:** Intermedien
- **Owner:** bonzo@konjscina.com
- **Member:** d.speh@intermedien.at (invited)

**Authentication Configuration Status:**
- ✅ **User Registration**: ENABLED - Custom controllers handle registration (`/register`)
- ✅ **Password Reset**: ENABLED - Custom controllers with security fix (`/forgot-password`)
- ✅ **2FA**: ENABLED via Fortify - Full two-factor authentication available
- ❌ **Email Verification**: DISABLED - Users can register and access immediately (no `MustVerifyEmail`)
- ❌ **Fortify Features**: Most Fortify features commented out, using custom auth controllers instead

---

## 🔐 Authentication & Account Management

### User Registration
- [ ] **Register New Account**
  1. Navigate to `/register`
  2. Fill in name, email, password, password confirmation
  3. Submit registration form
  4. Verify redirect to email verification page
  5. Check email verification link works
  6. Verify account activation successful

- [ ] **Email Verification Status Check**
  1. **CURRENT STATE**: Email verification is DISABLED in this application
  2. Users can register and immediately access all features without email verification
  3. The `User` model has `MustVerifyEmail` commented out
  4. Fortify config has `Features::emailVerification()` disabled
  5. **Test**: Register new account and verify immediate access to dashboard
  6. **Optional**: If you want to enable email verification, see configuration notes below

  **To Enable Email Verification (Optional):**
  - Uncomment `use Illuminate\Contracts\Auth\MustVerifyEmail;` in `app/Models/User.php`
  - Add `implements MustVerifyEmail` to User class
  - Uncomment `Features::emailVerification()` in `config/fortify.php`
  - Then test the email verification flow with Mailpit

### User Login
- [ ] **Successful Login**
  1. Navigate to `/login`
  2. Enter: `bonzo@konjscina.com` / `to16ro12`
  3. Click "Sign In"
  4. Verify redirect to dashboard
  5. Check user info displayed correctly in header

- [ ] **Failed Login Attempts**
  1. Try wrong password
  2. Try non-existent email
  3. Try empty fields
  4. Verify appropriate error messages
  5. Check rate limiting after multiple failures

### Password Reset
- [ ] **Forgot Password Flow**
  1. Go to `/login`
  2. Click "Forgot Password?"
  3. Enter: `bonzo@konjscina.com`
  4. Submit form
  5. **Check Mailpit** (http://localhost:8025) for reset email
  6. Click reset link from Mailpit email
  7. Enter new password twice
  8. Submit password reset
  9. Verify login with new password works
  10. Reset back to `to16ro12` for other tests

- [ ] **Test Non-Existent Email (Security Check)**
  1. Try password reset with `nonexistent@example.com`
  2. Verify form shows same success message as valid emails (prevents user enumeration)
  3. **Check Mailpit** - NO email should be sent for non-existent users
  4. Verify application doesn't reveal whether email exists or not

### Two-Factor Authentication (2FA)
- [ ] **Enable 2FA**
  1. Go to Settings → Security
  2. Click "Enable Two-Factor Authentication"
  3. Scan QR code with authenticator app
  4. Enter confirmation code
  5. Save recovery codes
  6. Verify 2FA is enabled

- [ ] **Login with 2FA**
  1. Logout
  2. Login with username/password
  3. Enter 2FA code from authenticator
  4. Verify successful login
  5. Test "Remember this device" option

- [ ] **2FA Recovery**
  1. Use recovery code instead of authenticator
  2. Verify login successful
  3. Check recovery code is marked as used

- [ ] **Disable 2FA**
  1. Go to Settings → Security
  2. Disable 2FA with current password
  3. Verify 2FA is disabled
  4. Test login without 2FA required

### Logout
- [ ] **User Logout**
  1. Click user menu in header
  2. Click "Logout"
  3. Verify redirect to login page
  4. Try accessing protected pages
  5. Confirm redirect to login

---

## 🏠 Dashboard Functionality

### Main Dashboard
- [ ] **Dashboard Overview**
  1. Login as `bonzo@konjscina.com`
  2. Verify SSL statistics displayed
  3. Check uptime monitoring stats
  4. Verify recent activity feed
  5. Test stat card hover effects
  6. Check gradient styling and animations

- [ ] **SSL Certificate Statistics**
  1. Verify "Total Websites" count
  2. Check "Valid Certificates" number
  3. Verify "Expiring Soon" alerts
  4. Check "Expired Certificates" count
  5. Verify "Average Response Time" display

- [ ] **Uptime Monitoring Statistics**
  1. Check "Total Monitors" count
  2. Verify "Healthy Monitors" number
  3. Check "Down Monitors" alerts
  4. Verify "Uptime Percentage" display
  5. Check response time accuracy

- [ ] **Activity Feed**
  1. Verify recent SSL checks listed
  2. Check uptime check results
  3. Test activity timestamps
  4. Verify "View All Activity" link
  5. Check activity filtering

### Quick Actions
- [ ] **Add Website Button**
  1. Click "Add Website" on dashboard
  2. Verify redirect to website creation
  3. Test modal or form functionality

- [ ] **View All Websites**
  1. Click "View All Websites"
  2. Verify redirect to websites list
  3. Check website count matches dashboard

---

## 🌐 Website Management

### Add Websites
- [ ] **Add Personal Website: omp.office-manager-pro.com**
  1. Navigate to "Add Website"
  2. Enter URL: `omp.office-manager-pro.com`
  3. Enter name: "Office Manager Pro"
  4. Enable SSL monitoring
  5. Enable uptime monitoring
  6. Set check frequency to 5 minutes
  7. Save website
  8. Verify website appears in list
  9. Check SSL certificate data loads

- [ ] **Add Personal Website: www.fairnando.at**
  1. Add URL: `www.fairnando.at`
  2. Name: "Fairnando"
  3. Enable both SSL and uptime monitoring
  4. Save and verify

- [ ] **Add Personal Website: www.redgas.at**
  1. Add URL: `www.redgas.at`
  2. Name: "Redgas Austria"
  3. Enable both monitoring types
  4. Save and verify

- [ ] **Add Personal Website: www.gebrauchte.at**
  1. Add URL: `www.gebrauchte.at`
  2. Name: "Gebrauchte (Personal)"
  3. Enable monitoring
  4. **Note: This stays personal - do NOT transfer to team**
  5. Save and verify

### Website List Management
- [ ] **Website List View**
  1. Navigate to Websites section
  2. Verify all 4 websites display
  3. Check SSL status indicators
  4. Verify uptime status displays
  5. Test sorting by name, status, added date
  6. Check pagination if many websites

- [ ] **Website Search and Filtering**
  1. Test search by website name
  2. Filter by SSL status (Valid, Expiring, Expired)
  3. Filter by uptime status (Up, Down)
  4. Test combined filters
  5. Verify real-time filtering (500ms debounce)

### Individual Website Management
- [ ] **Edit Website Settings**
  1. Click edit on `omp.office-manager-pro.com`
  2. Change name to "OMP - Office Manager"
  3. Modify check frequency
  4. Toggle monitoring options
  5. Save changes
  6. Verify updates reflected in list

- [ ] **View Website Details**
  1. Click on website name
  2. Verify SSL certificate details
  3. Check uptime history
  4. Review check logs
  5. Verify response times displayed
  6. Check certificate expiry date

- [ ] **Manual SSL Check**
  1. Open website details
  2. Click "Check Now" for SSL
  3. Verify loading indicator
  4. Check results update
  5. Verify timestamp updates

- [ ] **Manual Uptime Check**
  1. Click "Check Now" for uptime
  2. Verify response time recorded
  3. Check status updates
  4. Verify check history

### Website Deletion
- [ ] **Delete Website (Create Test Website First)**
  1. Add a test website: `test.example.com`
  2. Verify it appears in list
  3. Click delete button
  4. Confirm deletion in modal
  5. Verify website removed from list
  6. Check associated data cleaned up

---

## 👥 Team Management

### Team Creation
- [ ] **Create Team: Intermedien**
  1. Navigate to Teams section
  2. Click "Create New Team"
  3. Enter team name: "Intermedien"
  4. Add description: "SSL monitoring for Intermedien projects"
  5. Create team
  6. Verify team created successfully
  7. Check owner role assigned to `bonzo@konjscina.com`

### Team Member Invitation
- [ ] **Invite Member: d.speh@intermedien.at**
  1. Go to team management
  2. Click "Invite Member"
  3. Enter email: `d.speh@intermedien.at`
  4. Select role: "Manager" or "Viewer"
  5. Add invitation message
  6. Send invitation
  7. **Check Mailpit** (http://localhost:8025) for invitation email
  8. Verify invitation appears in pending list
  9. **Optional:** Click invitation link from Mailpit to test acceptance flow

### Website Transfer to Team
- [ ] **Transfer omp.office-manager-pro.com to Team**
  1. Go to website list
  2. Select `omp.office-manager-pro.com`
  3. Click "Transfer to Team"
  4. Select team: "Intermedien"
  5. Confirm transfer
  6. Verify website now shows under team
  7. Check personal list no longer shows it

- [ ] **Transfer www.fairnando.at to Team**
  1. Select `www.fairnando.at`
  2. Transfer to "Intermedien" team
  3. Verify transfer successful

- [ ] **Transfer www.redgas.at to Team**
  1. Select `www.redgas.at`
  2. Transfer to "Intermedien" team
  3. Verify transfer successful

### Bulk Transfer Operations
- [ ] **Bulk Transfer Multiple Websites**
  1. Go to bulk operations
  2. Select multiple websites for transfer
  3. Choose "Transfer to Team"
  4. Select "Intermedien" team
  5. Confirm bulk operation
  6. Verify all selected websites transferred
  7. Check success notification

### Team Website Management
- [ ] **Manage Team Websites**
  1. Switch to team view
  2. Verify 3 transferred websites appear
  3. Check SSL monitoring still works
  4. Verify uptime checks continue
  5. Test editing team websites
  6. Confirm team members can access

### Team Settings
- [ ] **Team Configuration**
  1. Go to team settings
  2. Update team description
  3. Change team preferences
  4. Configure team-wide alert settings
  5. Save changes
  6. Verify settings applied

---

## ⚙️ Settings & Configuration

### Profile Settings
- [ ] **Update Profile Information**
  1. Navigate to Settings → Profile
  2. Change display name
  3. Update profile information
  4. Upload profile picture (if available)
  5. Save changes
  6. Verify updates in header display

### Account Settings
- [ ] **Change Password**
  1. Go to Settings → Security
  2. Enter current password: `to16ro12`
  3. Enter new password twice
  4. Save password change
  5. Logout and login with new password
  6. Reset back to `to16ro12`

### Notification Preferences
- [ ] **Email Notifications**
  1. Go to Settings → Notifications
  2. Configure SSL expiry notifications
  3. Set uptime alert preferences
  4. Choose notification timing
  5. Save notification settings
  6. Test notification preferences

### Alert Configuration
- [ ] **SSL Certificate Alerts**
  1. Navigate to Settings → Alerts
  2. Create new SSL expiry alert
  3. Set threshold: 30 days before expiry
  4. Choose notification channels
  5. Test alert rule
  6. Verify alert appears in dashboard

- [ ] **Uptime Monitoring Alerts**
  1. Create uptime alert rule
  2. Set threshold: website down for 5 minutes
  3. Configure escalation rules
  4. Test alert functionality
  5. Verify notifications work

- [ ] **Response Time Alerts**
  1. Create response time alert
  2. Set threshold: >2000ms response time
  3. Configure alert frequency
  4. Test with slow website
  5. Verify alert triggers

### Theme and Appearance
- [ ] **Dark Mode Toggle**
  1. Click theme toggle in header
  2. Switch to dark mode
  3. Navigate through all pages
  4. Verify dark theme consistent
  5. Check readability and contrast
  6. Switch back to light mode

- [ ] **Theme Persistence**
  1. Set dark mode
  2. Logout and login
  3. Verify theme preference saved
  4. Test across browser sessions

---

## 📊 Analytics & Reporting

### Analytics Dashboard
- [ ] **Performance Analytics**
  1. Navigate to Analytics section
  2. Verify performance metrics display
  3. Check response time graphs
  4. Review uptime percentages
  5. Test date range filters
  6. Export analytics data

### Historical Trends
- [ ] **SSL Certificate Trends**
  1. View SSL certificate status over time
  2. Check expiry predictions
  3. Review certificate renewals
  4. Verify trend graphs load
  5. Test different time periods

- [ ] **Uptime Trends**
  1. Review uptime history charts
  2. Check downtime incidents
  3. Analyze response time trends
  4. Verify data accuracy
  5. Test trend filtering

### Reports Generation
- [ ] **Create Custom Report**
  1. Go to Reports section
  2. Click "Create New Report"
  3. Select report type: SSL Summary
  4. Choose date range: Last 30 days
  5. Select websites: All team websites
  6. Generate report
  7. Verify report content
  8. Test report download/export

- [ ] **Scheduled Reports**
  1. Create weekly SSL report
  2. Set email recipients
  3. Schedule delivery: Monday mornings
  4. Save scheduled report
  5. Verify schedule appears in list

### Data Export
- [ ] **Export SSL Data**
  1. Go to data export section
  2. Select SSL certificate data
  3. Choose date range
  4. Export as CSV
  5. Verify download works
  6. Check exported data accuracy

---

## 🔍 SSL Monitoring Features

### SSL Certificate Analysis
- [ ] **Certificate Details**
  1. View certificate for `omp.office-manager-pro.com`
  2. Check issuer information
  3. Verify expiry date accuracy
  4. Review certificate chain
  5. Check signature algorithm
  6. Verify certificate validity

- [ ] **Certificate History**
  1. View certificate renewal history
  2. Check previous certificates
  3. Review renewal timeline
  4. Verify certificate changes tracked

### SSL Alerts and Notifications
- [ ] **Expiry Warnings**
  1. Create test certificate expiring soon
  2. Verify alert triggers
  3. Check notification delivery
  4. Test escalation rules
  5. Confirm alert resolution

### SSL Check Scheduling
- [ ] **Automated Checks**
  1. Configure check frequency
  2. Set check intervals per website
  3. Verify checks run on schedule
  4. Check system load handling
  5. Review check reliability

---

## 📈 Uptime Monitoring Features

### Uptime Status Monitoring
- [ ] **Real-time Status**
  1. Check current uptime status
  2. Verify response times accurate
  3. Test status page display
  4. Check status history
  5. Review uptime percentages

### Downtime Detection
- [ ] **Downtime Alerts**
  1. Simulate website downtime (if possible)
  2. Verify alert triggers
  3. Check notification speed
  4. Test recovery notifications
  5. Review incident timeline

### Response Time Tracking
- [ ] **Performance Monitoring**
  1. Check response time graphs
  2. Verify average response times
  3. Review performance trends
  4. Test response time alerts
  5. Check geographic differences

---

## 🎛️ Bulk Operations

### Bulk Website Management
- [ ] **Bulk SSL Checks**
  1. Navigate to bulk operations
  2. Select multiple websites
  3. Run bulk SSL check
  4. Verify all checks execute
  5. Check results summary
  6. Review any failed checks

- [ ] **Bulk Uptime Checks**
  1. Select websites for uptime check
  2. Execute bulk uptime check
  3. Verify all responses recorded
  4. Check bulk operation status
  5. Review operation summary

### Bulk Configuration Changes
- [ ] **Bulk Settings Update**
  1. Select multiple websites
  2. Change monitoring frequency
  3. Apply bulk changes
  4. Verify all websites updated
  5. Check change confirmation

### Bulk Alerts Management
- [ ] **Bulk Alert Configuration**
  1. Select websites for alert setup
  2. Apply standard alert rules
  3. Configure bulk notifications
  4. Save bulk alert settings
  5. Verify alerts applied to all

---

## 📱 Mobile Responsiveness

### Mobile Navigation
- [ ] **Mobile Menu**
  1. Open site on mobile device/browser
  2. Test hamburger menu functionality
  3. Navigate through all sections
  4. Verify menu collapse/expand
  5. Check touch targets adequate size

### Mobile Dashboard
- [ ] **Dashboard on Mobile**
  1. View dashboard on mobile
  2. Check stats cards layout
  3. Test scrolling and swiping
  4. Verify charts display correctly
  5. Check activity feed readability

### Mobile Website Management
- [ ] **Add Website on Mobile**
  1. Try adding website on mobile
  2. Test form field inputs
  3. Check keyboard behavior
  4. Verify save functionality
  5. Test validation messages

### Touch Interactions
- [ ] **Mobile Touch Testing**
  1. Test tap targets on buttons
  2. Check swipe gestures
  3. Test long press actions
  4. Verify touch feedback
  5. Check accidental tap prevention

---

## 🔧 Advanced Features

### API Access (if available)
- [ ] **API Endpoints**
  1. Test API authentication
  2. Check website data endpoints
  3. Verify SSL status API
  4. Test uptime data API
  5. Check rate limiting

### Webhooks (if available)
- [ ] **Webhook Configuration**
  1. Set up webhook endpoint
  2. Configure webhook events
  3. Test webhook delivery
  4. Verify payload format
  5. Check webhook reliability

### Integrations
- [ ] **External Service Integration**
  1. Test Slack notifications (if configured)
  2. Check email service integration
  3. Verify third-party connections
  4. Test integration authentication
  5. Check integration reliability

---

## 🚨 Error Handling & Edge Cases

### Network Error Handling
- [ ] **Connection Failures**
  1. Test behavior with network disconnect
  2. Check offline functionality
  3. Verify error messages
  4. Test reconnection handling
  5. Check data synchronization

### Invalid Input Handling
- [ ] **Form Validation**
  1. Submit forms with empty required fields
  2. Enter invalid email formats
  3. Test extremely long inputs
  4. Try special characters
  5. Test SQL injection attempts (should fail safely)

### Server Error Responses
- [ ] **Error Pages**
  1. Navigate to non-existent pages (404)
  2. Test server error handling (500)
  3. Check error page design
  4. Verify error logging
  5. Test error recovery

### Browser Compatibility
- [ ] **Cross-Browser Testing**
  1. Test in Chrome (latest)
  2. Test in Firefox (latest)
  3. Test in Safari (if available)
  4. Test in Edge (if available)
  5. Check for browser-specific issues

---

## 🔍 Security Testing

### Authentication Security
- [ ] **Session Management**
  1. Test session timeout
  2. Check concurrent session handling
  3. Verify logout clears session
  4. Test session fixation protection
  5. Check remember me security

### Authorization Testing
- [ ] **Access Controls**
  1. Test accessing other users' data
  2. Check team permission enforcement
  3. Verify role-based access
  4. Test URL manipulation attempts
  5. Check API authorization

### Input Security
- [ ] **XSS Protection**
  1. Test script injection in forms
  2. Check HTML sanitization
  3. Verify output encoding
  4. Test reflected XSS protection
  5. Check stored XSS prevention

---

## 🎯 Performance Testing

### Page Load Performance
- [ ] **Load Time Testing**
  1. Measure dashboard load time
  2. Check website list loading
  3. Time SSL check responses
  4. Verify asset loading speed
  5. Test with slow connections

### Concurrent User Simulation
- [ ] **Multi-User Testing**
  1. Simulate multiple users adding websites
  2. Test concurrent SSL checks
  3. Check system under load
  4. Verify database performance
  5. Test caching effectiveness

---

## ✅ Final Integration Testing

### End-to-End Workflows
- [ ] **Complete User Journey**
  1. Register new user account
  2. Verify email and login
  3. Add all test websites
  4. Configure monitoring settings
  5. Set up alerts and notifications
  6. Create team and invite members
  7. Transfer websites to team
  8. Generate reports and analytics
  9. Test mobile access
  10. Complete logout

### Data Consistency
- [ ] **Data Integrity**
  1. Verify SSL data accuracy across pages
  2. Check uptime statistics consistency
  3. Confirm team data synchronization
  4. Test data export accuracy
  5. Verify report data matches dashboard

### System Recovery
- [ ] **Recovery Testing**
  1. Test graceful failure handling
  2. Check automatic retry mechanisms
  3. Verify data backup and restore
  4. Test system restart recovery
  5. Check monitoring continuity

---

## 📋 Testing Checklist Summary

### Pre-Testing Setup
- [ ] Clear browser cache and cookies
- [ ] Prepare test data and credentials
- [ ] Set up test environment
- [ ] Document testing environment details

### Testing Execution
- [ ] Follow test cases in sequence
- [ ] Document any bugs or issues found
- [ ] Take screenshots of important states
- [ ] Note performance observations
- [ ] Record browser console errors

### Post-Testing Activities
- [ ] Compile bug reports
- [ ] Prioritize issues found
- [ ] Verify bug fixes
- [ ] Update documentation
- [ ] Archive test results

---

## 🐛 Bug Reporting Template

When you find issues, document them using this format:

**Bug Title:** Brief description of the issue

**Steps to Reproduce:**
1. Step 1
2. Step 2
3. Step 3

**Expected Result:** What should happen

**Actual Result:** What actually happened

**Browser/Device:** Browser version and device

**Screenshots:** Attach relevant screenshots

**Priority:** High/Medium/Low

**Additional Notes:** Any other relevant information

---

**Testing Status:** ⏳ In Progress
**Last Updated:** [Date]
**Tested By:** [Your Name]