# Manual Testing Checklist - Real-World Usage Scenarios

**Purpose**: This checklist covers everyday use cases that should be tested manually or with real browser automation (Playwright MCP).

**Status**: ⚠️ **NOT YET TESTED** - Phase 6 created integration tests, but did not perform real browser testing

---

## Critical Gap Identified

**What Phase 6 Did**:
- ✅ Created 100+ integration tests (HTTP/Inertia assertions)
- ✅ Tested alert emails in Mailpit (12 emails verified)
- ✅ Captured screenshots of UI state
- ✅ Verified zero console errors

**What Phase 6 Did NOT Do**:
- ❌ Actually click buttons in a real browser
- ❌ Test complete user signup flow end-to-end
- ❌ Visually verify emails render correctly in Mailpit
- ❌ Test drag-and-drop or complex interactions
- ❌ Test real-world user workflows (create → move → delete)

---

## Manual Testing Checklist

### Part 1: User Signup & Authentication (30 min)

**Test Environment**:
- URL: http://localhost
- Fresh database: `./vendor/bin/sail artisan migrate:fresh --seed`
- Mailpit: http://localhost:8025

#### Task 1.1: New User Registration
- [ ] Navigate to `/register`
- [ ] Fill in registration form:
  - Name: "Test User"
  - Email: "testuser@example.com"
  - Password: "SecurePassword123!"
  - Confirm Password: "SecurePassword123!"
- [ ] Check "I agree to terms" checkbox
- [ ] Click "Register" button
- [ ] **Expected**: Redirected to email verification page
- [ ] **Verify Mailpit**: Email sent to testuser@example.com
- [ ] **Verify Email Content**:
  - Subject: "Verify Email Address"
  - Contains verification link
  - Professional formatting
- [ ] Click verification link in email
- [ ] **Expected**: Email verified, redirected to dashboard

**Screenshot**: Capture registration success state

#### Task 1.2: Login with Verified Account
- [ ] Log out
- [ ] Navigate to `/login`
- [ ] Enter credentials:
  - Email: "testuser@example.com"
  - Password: "SecurePassword123!"
- [ ] Click "Log In" button
- [ ] **Expected**: Redirected to dashboard
- [ ] **Verify**: User name displayed in top navigation

**Screenshot**: Capture logged-in dashboard

#### Task 1.3: Two-Factor Authentication Setup
- [ ] Navigate to Settings → Security
- [ ] Click "Enable Two-Factor Authentication"
- [ ] **Verify**: QR code displayed
- [ ] **Verify**: Recovery codes displayed (8 codes)
- [ ] Save recovery codes (copy to clipboard)
- [ ] Scan QR code with authenticator app (Google Authenticator, Authy)
- [ ] Enter 6-digit code from app
- [ ] Click "Confirm"
- [ ] **Expected**: 2FA enabled successfully

**Screenshot**: Capture 2FA enabled state

#### Task 1.4: Login with 2FA
- [ ] Log out
- [ ] Navigate to `/login`
- [ ] Enter credentials (testuser@example.com)
- [ ] **Expected**: Redirected to 2FA challenge page
- [ ] Enter 6-digit code from authenticator app
- [ ] Click "Verify"
- [ ] **Expected**: Successfully logged in to dashboard

**Screenshot**: Capture 2FA challenge page

#### Task 1.5: Password Reset Flow
- [ ] Log out
- [ ] Navigate to `/forgot-password`
- [ ] Enter email: "testuser@example.com"
- [ ] Click "Send Password Reset Link"
- [ ] **Verify Mailpit**: Password reset email sent
- [ ] **Verify Email Content**:
  - Subject: "Reset Password"
  - Contains reset link
  - Link expires in 60 minutes notice
- [ ] Click reset link in email
- [ ] Enter new password: "NewPassword456!"
- [ ] Confirm new password: "NewPassword456!"
- [ ] Click "Reset Password"
- [ ] **Expected**: Password reset, redirected to login
- [ ] Login with new password
- [ ] **Expected**: Successfully logged in

**Screenshot**: Capture password reset success

---

### Part 2: Website Management (45 min)

#### Task 2.1: Create First Website
- [ ] Login as testuser@example.com
- [ ] Navigate to Websites page
- [ ] Click "Add Website" button
- [ ] Fill in form:
  - URL: "https://redgas.at"
  - Name: "Redgas Website" (optional)
  - Enable SSL Monitoring: ✓
  - Enable Uptime Monitoring: ✓
  - Check Interval: 5 minutes
- [ ] Click "Create Website" button
- [ ] **Expected**: Success notification displayed
- [ ] **Expected**: Redirected to website list
- [ ] **Verify**: "Redgas Website" appears in list
- [ ] **Verify**: Status shows "Checking..." initially
- [ ] Wait 5-10 seconds
- [ ] Refresh page
- [ ] **Verify**: SSL status updated (valid/invalid)
- [ ] **Verify**: Uptime status updated (up/down)

**Screenshot**: Capture website list with first website

#### Task 2.2: Create Multiple Websites
- [ ] Click "Add Website" again
- [ ] Create website: "https://fairnando.at" → "Fairnando Website"
- [ ] Create website: "https://gebrauchte.at" → "Gebrauchte Website"
- [ ] Create website: "https://omp.office-manager-pro.com" → "OMP Website"
- [ ] **Verify**: All 4 websites appear in list
- [ ] **Verify**: Each has SSL and uptime status

**Screenshot**: Capture website list with 4 websites

#### Task 2.3: Edit Website Configuration
- [ ] Click "Edit" on "Redgas Website"
- [ ] Change check interval to 10 minutes
- [ ] Disable uptime monitoring
- [ ] Add custom name: "Redgas Production Site"
- [ ] Click "Update Website"
- [ ] **Expected**: Success notification
- [ ] **Verify**: Changes saved and reflected in list
- [ ] **Verify**: Uptime monitoring no longer active

**Screenshot**: Capture edit form

#### Task 2.4: View Website Details
- [ ] Click on "Redgas Production Site" name to view details
- [ ] **Verify**: SSL certificate details displayed:
  - Issuer
  - Expiry date
  - Days remaining
  - Certificate valid status
- [ ] **Verify**: Recent checks timeline displayed
- [ ] **Verify**: Response time chart displayed (if data available)
- [ ] **Verify**: Alert history displayed (if any)

**Screenshot**: Capture website details page

#### Task 2.5: Bulk Check All Websites
- [ ] Navigate back to website list
- [ ] Click "Check All" button (if available)
- [ ] **Expected**: Progress indicator displayed
- [ ] **Expected**: All websites status updated
- [ ] **Verify**: Last checked timestamp updated for all

**Screenshot**: Capture bulk check in progress

---

### Part 3: Team Management (45 min)

#### Task 3.1: Create Team
- [ ] Navigate to Settings → Teams
- [ ] Click "Create New Team" button
- [ ] Enter team name: "Redgas Team"
- [ ] Click "Create Team"
- [ ] **Expected**: Team created successfully
- [ ] **Verify**: Team appears in team list
- [ ] **Verify**: Current user is team owner

**Screenshot**: Capture team list with new team

#### Task 3.2: Invite Team Member
- [ ] Click on "Redgas Team" to manage
- [ ] Click "Invite Member" button
- [ ] Enter email: "teammember@example.com"
- [ ] Select role: "Admin"
- [ ] Click "Send Invitation"
- [ ] **Expected**: Success notification
- [ ] **Verify Mailpit**: Invitation email sent to teammember@example.com
- [ ] **Verify Email Content**:
  - Subject: "Team Invitation"
  - Contains team name "Redgas Team"
  - Contains invitation link
  - Contains role "Admin"

**Screenshot**: Capture invitation sent confirmation

#### Task 3.3: Accept Team Invitation (New Browser/Incognito)
- [ ] Open new incognito window
- [ ] Check Mailpit for invitation email
- [ ] Click invitation link in email
- [ ] **Expected**: Redirected to registration/login page
- [ ] Register as new user (if not exists):
  - Name: "Team Member"
  - Email: "teammember@example.com"
  - Password: "TeamPass123!"
- [ ] OR Login if account exists
- [ ] **Expected**: Automatically added to team
- [ ] **Expected**: Team "Redgas Team" visible in team switcher
- [ ] **Verify**: Role displayed as "Admin"

**Screenshot**: Capture team member view after joining

#### Task 3.4: Move Website to Team
- [ ] Switch back to owner account (testuser@example.com)
- [ ] Navigate to Websites page
- [ ] Select "Redgas Production Site"
- [ ] Click "Move to Team" button (or edit website)
- [ ] Select team: "Redgas Team"
- [ ] Click "Move" or "Update"
- [ ] **Expected**: Website moved successfully
- [ ] **Verify**: Website owner changed to "Redgas Team"
- [ ] Switch to team member account (teammember@example.com)
- [ ] Navigate to Websites page
- [ ] **Verify**: "Redgas Production Site" visible in team member's list
- [ ] **Verify**: Team member can view website details
- [ ] **Verify**: Team member can edit website (Admin role)

**Screenshot**: Capture website list showing team-owned website

#### Task 3.5: Test Role Permissions
- [ ] As team owner, navigate to Team Settings
- [ ] Change teammember@example.com role to "Viewer"
- [ ] Click "Update Role"
- [ ] Switch to team member account
- [ ] Navigate to Websites page
- [ ] Try to edit "Redgas Production Site"
- [ ] **Expected**: Edit button disabled OR 403 Forbidden error
- [ ] Try to delete website
- [ ] **Expected**: Delete button disabled OR 403 Forbidden error
- [ ] **Verify**: Viewer can only view, not modify

**Screenshot**: Capture viewer role restrictions

#### Task 3.6: Remove Team Member
- [ ] Switch back to team owner account
- [ ] Navigate to Team Settings
- [ ] Click "Remove" on teammember@example.com
- [ ] Confirm removal
- [ ] **Expected**: Member removed from team
- [ ] Switch to team member account
- [ ] Refresh page
- [ ] **Verify**: "Redgas Team" no longer visible
- [ ] **Verify**: Team website no longer accessible

**Screenshot**: Capture team member removed

---

### Part 4: Alert Configuration & Testing (45 min)

#### Task 4.1: Configure SSL Expiry Alerts
- [ ] Navigate to website "Redgas Production Site"
- [ ] Click "Alert Settings" tab
- [ ] Enable SSL Expiry Alerts
- [ ] Set warning threshold: 30 days
- [ ] Set critical threshold: 7 days
- [ ] Enable email notifications
- [ ] Click "Save Alert Settings"
- [ ] **Expected**: Settings saved successfully

**Screenshot**: Capture alert configuration

#### Task 4.2: Configure Uptime Alerts
- [ ] On same website, enable uptime monitoring
- [ ] Enable Uptime Alerts
- [ ] Set failure threshold: 3 consecutive failures
- [ ] Enable email notifications
- [ ] Click "Save Alert Settings"
- [ ] **Expected**: Settings saved successfully

**Screenshot**: Capture uptime alert configuration

#### Task 4.3: Test Alert Email Delivery (Use Debug Menu)
- [ ] Navigate to Debug Menu (if available)
- [ ] OR manually trigger alert:
  - Use SSL override to set expiry date 5 days from now
  - Trigger monitor check
- [ ] Wait for alert processing (check Horizon)
- [ ] **Verify Mailpit**: Alert email received
- [ ] **Verify Email Content**:
  - Subject: "SSL Certificate Expiring Soon"
  - Contains website name
  - Contains days remaining
  - Contains action button linking to website
  - Professional formatting with gradient header

**Screenshot**: Capture alert email in Mailpit

#### Task 4.4: Verify Alert History
- [ ] Navigate back to website details
- [ ] Click "Alert History" tab
- [ ] **Verify**: Alert appears in history
- [ ] **Verify**: Alert details displayed:
  - Type (SSL Expiry)
  - Severity (Warning/Critical)
  - Timestamp
  - Status (Active/Resolved)
- [ ] Click on alert to view details
- [ ] **Verify**: Full alert information displayed

**Screenshot**: Capture alert history

---

### Part 5: Dashboard & Monitoring (30 min)

#### Task 5.1: Dashboard Overview
- [ ] Navigate to Dashboard
- [ ] **Verify Metric Cards**:
  - Total Websites count (should be 4)
  - SSL Certificates status (valid/invalid counts)
  - Uptime Status (up/down counts)
  - Response Time average
- [ ] **Verify Quick Actions**:
  - Add Website button
  - Bulk Check button
  - View All Websites button
- [ ] **Verify Recent Activity**:
  - Recent checks displayed
  - Timestamps accurate
  - Event types color-coded

**Screenshot**: Capture full dashboard

#### Task 5.2: Response Time Chart
- [ ] On dashboard, locate Response Time chart
- [ ] **Verify**: Chart.js canvas element rendered
- [ ] **Verify**: Chart shows data points (if available)
- [ ] Hover over data points
- [ ] **Verify**: Tooltips display values
- [ ] **Verify**: Chart legend displayed

**Screenshot**: Capture response time chart

#### Task 5.3: Real-Time Updates
- [ ] Keep dashboard open
- [ ] In another tab, trigger a website check
- [ ] Return to dashboard
- [ ] Refresh page
- [ ] **Verify**: Recent activity updated with new check
- [ ] **Verify**: Metrics updated if status changed

**Screenshot**: Capture updated dashboard

---

### Part 6: Settings & Profile (20 min)

#### Task 6.1: Update Profile Information
- [ ] Navigate to Settings → Profile
- [ ] Change name: "Test User Updated"
- [ ] Change email: "testuserupdated@example.com"
- [ ] Click "Save"
- [ ] **Expected**: Email verification required for email change
- [ ] **Verify Mailpit**: Verification email sent to new address
- [ ] Click verification link
- [ ] **Expected**: Email changed successfully
- [ ] **Verify**: Name updated in navigation

**Screenshot**: Capture profile settings

#### Task 6.2: Change Password
- [ ] Navigate to Settings → Security
- [ ] Enter current password: "NewPassword456!"
- [ ] Enter new password: "FinalPassword789!"
- [ ] Confirm new password: "FinalPassword789!"
- [ ] Click "Update Password"
- [ ] **Expected**: Password changed successfully
- [ ] Log out and log in with new password
- [ ] **Expected**: Login successful with new password

**Screenshot**: Capture password change success

#### Task 6.3: Disable Two-Factor Authentication
- [ ] Navigate to Settings → Security
- [ ] Click "Disable Two-Factor Authentication"
- [ ] Enter password to confirm
- [ ] Click "Disable"
- [ ] **Expected**: 2FA disabled successfully
- [ ] Log out and log in
- [ ] **Expected**: No 2FA challenge, direct to dashboard

**Screenshot**: Capture 2FA disabled state

---

### Part 7: Website Deletion (15 min)

#### Task 7.1: Delete Individual Website
- [ ] Navigate to Websites page
- [ ] Click "Delete" on "Gebrauchte Website"
- [ ] **Expected**: Confirmation modal displayed
- [ ] **Verify Modal Content**:
  - Warning message
  - Website name displayed
  - "Are you sure?" text
- [ ] Click "Cancel"
- [ ] **Verify**: Website still in list (not deleted)
- [ ] Click "Delete" again
- [ ] Click "Confirm Delete"
- [ ] **Expected**: Success notification
- [ ] **Expected**: Website removed from list
- [ ] **Verify**: Website count updated (now 3 websites)

**Screenshot**: Capture delete confirmation modal

#### Task 7.2: Verify Deletion Cascade
- [ ] Check that deleted website:
  - [ ] No longer appears in dashboard
  - [ ] Monitoring results removed (or marked as deleted)
  - [ ] Alerts removed (or marked as resolved)
  - [ ] Historical data removed (based on retention policy)

---

### Part 8: Edge Cases & Error Handling (30 min)

#### Task 8.1: Invalid Website URL
- [ ] Try to create website with invalid URL: "not-a-url"
- [ ] **Expected**: Validation error displayed
- [ ] **Expected**: Form not submitted

#### Task 8.2: Duplicate Website URL
- [ ] Try to create website with existing URL: "https://redgas.at"
- [ ] **Expected**: Error: "Website already exists"

#### Task 8.3: Expired Session
- [ ] Clear session cookies or wait for session timeout
- [ ] Try to navigate to dashboard
- [ ] **Expected**: Redirected to login page
- [ ] **Expected**: Message: "Session expired, please log in"

#### Task 8.4: Invalid 2FA Code
- [ ] Enable 2FA again
- [ ] Log out
- [ ] Login with password
- [ ] Enter invalid 2FA code: "000000"
- [ ] **Expected**: Error: "Invalid authentication code"
- [ ] **Expected**: Remain on 2FA challenge page

#### Task 8.5: Weak Password
- [ ] Try to register with weak password: "password"
- [ ] **Expected**: Validation error: "Password must be at least 8 characters"

---

## Summary Checklist

**Total Tasks**: 35 manual test scenarios

**Status**:
- [ ] Part 1: User Signup & Authentication (5 tasks)
- [ ] Part 2: Website Management (5 tasks)
- [ ] Part 3: Team Management (6 tasks)
- [ ] Part 4: Alert Configuration & Testing (4 tasks)
- [ ] Part 5: Dashboard & Monitoring (3 tasks)
- [ ] Part 6: Settings & Profile (3 tasks)
- [ ] Part 7: Website Deletion (2 tasks)
- [ ] Part 8: Edge Cases & Error Handling (5 tasks)

**Estimated Time**: 4-5 hours for complete manual testing

---

## Automated Testing with Playwright MCP (Recommended)

**Would you like me to run these tests using real Playwright browser automation?**

I can use the Playwright MCP tools to:
- ✅ Actually open a real browser
- ✅ Click buttons and fill forms
- ✅ Capture screenshots at each step
- ✅ Verify emails in Mailpit visually
- ✅ Test complete user workflows end-to-end

**Example command**:
```bash
# I would use these MCP tools:
mcp__playwright-extension__browser_navigate --url http://localhost/register
mcp__playwright-extension__browser_type --element "Email field" --text "testuser@example.com"
mcp__playwright-extension__browser_click --element "Register button"
mcp__playwright-extension__browser_snapshot # Capture current page state
mcp__playwright-extension__browser_console_messages # Check for errors
```

**Should I proceed with real browser automation testing now?**
