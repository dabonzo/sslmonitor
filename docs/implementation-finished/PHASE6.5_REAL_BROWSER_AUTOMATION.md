# Phase 6.5: Real Browser Automation Testing

**Document Version**: 1.0
**Created**: November 10, 2025
**Status**: 📋 Planned
**Purpose**: Real-world browser automation testing with Playwright MCP for everyday use scenarios
**Progress**: 0%
**Estimated Time**: 4-5 hours

---

## Overview

**Problem Statement**:
Phase 6 created 100+ integration tests that verify HTTP responses and Inertia components, but did NOT test real browser interactions. Critical everyday workflows need validation with actual browser automation.

**Gap Identified**:
- ✅ Phase 6 Part 2: Integration tests (HTTP/Inertia assertions)
- ❌ **Missing**: Real browser automation (button clicks, form fills, visual verification)

**What Needs Testing**:
1. User signup flow with email verification in Mailpit
2. Website creation with real button clicks and form interactions
3. Moving websites to teams via UI drag-and-drop or modals
4. Deleting websites with confirmation modal
5. Real form validation and error message display
6. Visual verification of UI state at each step

---

## Approach: Playwright MCP Browser Automation

**Technology**: Playwright MCP extension with real browser testing

**Available Tools**:
- `mcp__playwright-extension__browser_navigate` - Navigate to URLs
- `mcp__playwright-extension__browser_click` - Click elements
- `mcp__playwright-extension__browser_type` - Type into forms
- `mcp__playwright-extension__browser_snapshot` - Capture accessibility tree
- `mcp__playwright-extension__browser_take_screenshot` - Visual capture
- `mcp__playwright-extension__browser_console_messages` - Check console errors
- `mcp__playwright-extension__browser_network_requests` - Monitor network
- `mcp__playwright-extension__browser_fill_form` - Fill multiple fields at once

**Test Environment**:
- **URL**: http://localhost (Laravel Sail)
- **Database**: Fresh migration with seed data
- **Mailpit**: http://localhost:8025
- **Browser**: Chromium (headless or headed)

---

## Part 1: User Authentication Workflows (1 hour)

### Task 1.1: User Registration Flow

**Agent**: `browser-tester`

**Test Scenario**:
```
1. Navigate to http://localhost/register
2. Capture screenshot: registration form
3. Fill in registration form:
   - Name: "Test User"
   - Email: "testuser@example.com"
   - Password: "SecurePassword123!"
   - Confirm Password: "SecurePassword123!"
4. Check "I agree to terms" checkbox
5. Click "Register" button
6. Verify: Redirected to email verification page
7. Capture screenshot: email verification notice
8. Check Mailpit for verification email (mcp__playwright-extension__browser_navigate to http://localhost:8025)
9. Capture screenshot: verification email in Mailpit
10. Click verification link in email
11. Verify: Email verified, redirected to dashboard
12. Capture screenshot: dashboard after signup
13. Check console for errors (mcp__playwright-extension__browser_console_messages)
```

**Verification Points**:
- ✅ Registration form displays correctly
- ✅ Form validation works (required fields, password confirmation)
- ✅ Email sent to Mailpit
- ✅ Email contains correct verification link
- ✅ Verification link works
- ✅ User redirected to dashboard after verification
- ✅ Zero JavaScript console errors

**Screenshot Artifacts**:
- `01-registration-form.png`
- `02-email-verification-notice.png`
- `03-mailpit-verification-email.png`
- `04-dashboard-after-signup.png`

---

### Task 1.2: Login Flow with 2FA

**Test Scenario**:
```
1. Navigate to http://localhost/login
2. Enter credentials (testuser@example.com)
3. Click "Log In"
4. Verify: Redirected to dashboard (no 2FA yet)
5. Navigate to Settings → Security
6. Click "Enable Two-Factor Authentication"
7. Capture screenshot: QR code and recovery codes
8. Verify: QR code displayed, recovery codes listed
9. Simulate scanning QR code (save secret manually)
10. Click "Disable Two-Factor Authentication" (for testing)
11. Re-enable 2FA
12. Log out
13. Log in again
14. Verify: 2FA challenge page displayed
15. Enter 6-digit code from authenticator
16. Click "Verify"
17. Verify: Successfully logged in
18. Capture screenshot: dashboard after 2FA login
```

**Verification Points**:
- ✅ Login form works without 2FA
- ✅ 2FA setup displays QR code and recovery codes
- ✅ 2FA challenge appears after enabling
- ✅ Valid 2FA code grants access
- ✅ Invalid 2FA code shows error

**Screenshot Artifacts**:
- `05-login-form.png`
- `06-2fa-setup-qr-codes.png`
- `07-2fa-challenge-page.png`
- `08-dashboard-after-2fa.png`

---

## Part 2: Website Management Workflows (1.5 hours)

### Task 2.1: Create Website End-to-End

**Test Scenario**:
```
1. Login as testuser@example.com
2. Navigate to /websites
3. Capture screenshot: empty website list
4. Click "Add Website" button
5. Verify: Redirected to /websites/create
6. Capture screenshot: create website form
7. Fill in form:
   - URL: "https://redgas.at"
   - Name: "Redgas Website"
   - Enable SSL Monitoring: check
   - Enable Uptime Monitoring: check
   - Check Interval: select "5 minutes"
8. Click "Create Website" button
9. Verify: Success toast notification appears
10. Verify: Redirected to /websites
11. Capture screenshot: website list with new website
12. Wait 10 seconds for first check to complete
13. Refresh page
14. Verify: SSL status updated (valid or checking)
15. Verify: Uptime status updated (up or checking)
16. Click on website name to view details
17. Capture screenshot: website details page
18. Verify: SSL certificate details displayed
19. Verify: Recent checks timeline visible
```

**Verification Points**:
- ✅ "Add Website" button navigates to create form
- ✅ Form validation works (URL required, must be HTTPS)
- ✅ Website created successfully
- ✅ Success notification displayed
- ✅ Website appears in list immediately
- ✅ First monitoring check completes within 10 seconds
- ✅ Website details page shows certificate info

**Screenshot Artifacts**:
- `09-empty-website-list.png`
- `10-create-website-form.png`
- `11-website-list-with-website.png`
- `12-website-details-page.png`

---

### Task 2.2: Edit Website Configuration

**Test Scenario**:
```
1. On website list, click "Edit" button for "Redgas Website"
2. Verify: Edit form displayed with current values
3. Capture screenshot: edit form
4. Change name to "Redgas Production Site"
5. Change check interval to "10 minutes"
6. Disable uptime monitoring
7. Click "Update Website" button
8. Verify: Success notification
9. Verify: Redirected to website list
10. Verify: Changes reflected (new name, no uptime status)
11. Capture screenshot: updated website list
```

**Verification Points**:
- ✅ Edit form pre-populated with current values
- ✅ Changes saved successfully
- ✅ Website list shows updated information
- ✅ Uptime monitoring disabled (status no longer visible)

**Screenshot Artifacts**:
- `13-edit-website-form.png`
- `14-updated-website-list.png`

---

### Task 2.3: Delete Website with Confirmation

**Test Scenario**:
```
1. Create second website: "https://gebrauchte.at"
2. On website list, click "Delete" button for "Gebrauchte Website"
3. Verify: Confirmation modal appears
4. Capture screenshot: delete confirmation modal
5. Verify modal content:
   - Warning message
   - Website name displayed
   - "Are you sure?" text
   - "Cancel" and "Confirm Delete" buttons
6. Click "Cancel"
7. Verify: Modal closes, website still in list
8. Click "Delete" again
9. Click "Confirm Delete"
10. Verify: Success notification
11. Verify: Website removed from list
12. Verify: Website count updated
13. Capture screenshot: website list after deletion
```

**Verification Points**:
- ✅ Delete confirmation modal appears
- ✅ Cancel button works (no deletion)
- ✅ Confirm delete removes website
- ✅ Success notification shown
- ✅ Website immediately removed from list

**Screenshot Artifacts**:
- `15-delete-confirmation-modal.png`
- `16-website-list-after-deletion.png`

---

## Part 3: Team Management Workflows (1.5 hours)

### Task 3.1: Create Team and Invite Member

**Test Scenario**:
```
1. Navigate to Settings → Teams
2. Capture screenshot: empty team list (or personal team only)
3. Click "Create New Team" button
4. Fill in team name: "Redgas Team"
5. Click "Create Team" button
6. Verify: Team created, success notification
7. Capture screenshot: team list with new team
8. Click on "Redgas Team" to manage
9. Click "Invite Member" button
10. Fill in invitation form:
    - Email: "teammember@example.com"
    - Role: select "Admin"
11. Click "Send Invitation" button
12. Verify: Success notification
13. Capture screenshot: invitation sent confirmation
14. Navigate to Mailpit (http://localhost:8025)
15. Verify: Invitation email received
16. Capture screenshot: invitation email in Mailpit
17. Verify email content:
    - Contains team name "Redgas Team"
    - Contains role "Admin"
    - Contains invitation link
```

**Verification Points**:
- ✅ Team creation form works
- ✅ Team appears in list
- ✅ Invite member form works
- ✅ Invitation email sent to Mailpit
- ✅ Email contains correct information

**Screenshot Artifacts**:
- `17-empty-team-list.png`
- `18-team-list-with-team.png`
- `19-invitation-sent.png`
- `20-mailpit-invitation-email.png`

---

### Task 3.2: Accept Invitation (New Browser Session)

**Test Scenario**:
```
1. Open new incognito browser window (or clear cookies)
2. From Mailpit, copy invitation link
3. Navigate to invitation link
4. Verify: Registration/login page with invitation context
5. Capture screenshot: invitation acceptance page
6. Register as new user:
   - Name: "Team Member"
   - Email: "teammember@example.com"
   - Password: "TeamPass123!"
7. Click "Accept Invitation"
8. Verify: Automatically added to team
9. Verify: Redirected to dashboard
10. Verify: Team "Redgas Team" visible in team switcher
11. Capture screenshot: team member dashboard
12. Navigate to Team Settings
13. Verify: Role displayed as "Admin"
14. Capture screenshot: team settings as member
```

**Verification Points**:
- ✅ Invitation link works
- ✅ Registration with invitation succeeds
- ✅ User automatically added to team
- ✅ Team switcher shows new team
- ✅ Role correctly assigned

**Screenshot Artifacts**:
- `21-invitation-acceptance-page.png`
- `22-team-member-dashboard.png`
- `23-team-settings-as-member.png`

---

### Task 3.3: Move Website to Team

**Test Scenario**:
```
1. Switch back to owner account (testuser@example.com)
2. Navigate to /websites
3. Click on "Redgas Production Site"
4. Look for "Move to Team" button or edit website
5. Click "Move to Team" (or edit and change team)
6. Select team: "Redgas Team"
7. Click "Move" or "Update"
8. Verify: Success notification
9. Verify: Website owner changed to "Redgas Team"
10. Capture screenshot: website now owned by team
11. Switch to team member account (teammember@example.com)
12. Navigate to /websites
13. Verify: "Redgas Production Site" visible in list
14. Verify: Team member can view details
15. Capture screenshot: team member viewing team website
```

**Verification Points**:
- ✅ Move to team functionality works
- ✅ Website ownership transferred
- ✅ Team member can see team website
- ✅ Permissions enforced (admin can edit, viewer cannot)

**Screenshot Artifacts**:
- `24-website-moved-to-team.png`
- `25-team-member-viewing-website.png`

---

### Task 3.4: Test Role Permissions

**Test Scenario**:
```
1. As owner, change team member role to "Viewer"
2. Switch to team member account
3. Try to edit "Redgas Production Site"
4. Verify: Edit button disabled OR 403 Forbidden error
5. Capture screenshot: viewer role restrictions
6. Try to delete website
7. Verify: Delete button disabled OR 403 Forbidden error
8. Verify: Can still view website details
9. Switch back to owner
10. Change role back to "Admin"
11. Switch to team member
12. Verify: Edit button now enabled
13. Edit website successfully
```

**Verification Points**:
- ✅ Viewer role cannot edit or delete
- ✅ Viewer role can view details
- ✅ Admin role can edit and delete
- ✅ Role changes apply immediately

**Screenshot Artifacts**:
- `26-viewer-role-restrictions.png`

---

## Part 4: Alert Configuration & Email Verification (30 min)

### Task 4.1: Configure Alerts and Verify Email

**Test Scenario**:
```
1. Navigate to website details for "Redgas Production Site"
2. Click "Alert Settings" tab
3. Capture screenshot: alert configuration form
4. Enable SSL Expiry Alerts
5. Set warning threshold: 30 days
6. Set critical threshold: 7 days
7. Enable email notifications
8. Click "Save Alert Settings"
9. Verify: Success notification
10. Use Debug Menu (if available) to trigger test alert
11. OR manually set SSL override to expire in 5 days
12. Wait for alert processing (check Horizon)
13. Navigate to Mailpit
14. Verify: Alert email received
15. Capture screenshot: alert email in Mailpit
16. Open email
17. Verify email content:
    - Subject: "SSL Certificate Expiring Soon"
    - Gradient header with color-coded severity
    - Website name
    - Days remaining
    - Action button linking to website
18. Click action button in email
19. Verify: Navigates to website details page
20. Capture screenshot: website details after alert
```

**Verification Points**:
- ✅ Alert configuration form works
- ✅ Settings saved successfully
- ✅ Alert triggered (via debug or real check)
- ✅ Email sent to Mailpit
- ✅ Email formatted correctly
- ✅ Action button in email works

**Screenshot Artifacts**:
- `27-alert-configuration-form.png`
- `28-mailpit-alert-email.png`
- `29-website-after-alert.png`

---

## Part 5: Dashboard & Visual Verification (30 min)

### Task 5.1: Dashboard Metrics and Charts

**Test Scenario**:
```
1. Navigate to /dashboard
2. Capture screenshot: full dashboard
3. Verify metric cards:
   - Total Websites count (should match created websites)
   - SSL Certificates status (valid/invalid counts)
   - Uptime Status (up/down counts)
   - Response Time average
4. Verify quick actions visible:
   - Add Website button
   - Bulk Check button
   - View All Websites button
5. Verify recent activity timeline:
   - Recent checks displayed
   - Timestamps accurate
   - Event types color-coded
6. Verify response time chart:
   - Chart.js canvas rendered
   - Data points visible (if data available)
   - Hover tooltips work
7. Check browser console for errors
8. Check network requests for failed API calls
9. Capture screenshot: response time chart detail
```

**Verification Points**:
- ✅ All metric cards display correct counts
- ✅ Quick actions buttons work
- ✅ Recent activity timeline populated
- ✅ Charts render without errors
- ✅ Zero console errors
- ✅ All network requests successful (200 responses)

**Screenshot Artifacts**:
- `30-full-dashboard.png`
- `31-response-time-chart.png`

---

## Part 6: Form Validation & Error Handling (30 min)

### Task 6.1: Test Form Validation

**Test Scenario**:
```
1. Try to create website with invalid URL: "not-a-url"
2. Verify: Validation error displayed
3. Capture screenshot: URL validation error
4. Try to create website with HTTP (not HTTPS): "http://example.com"
5. Verify: Validation error: "URL must use HTTPS"
6. Capture screenshot: HTTPS validation error
7. Try to register with weak password: "password"
8. Verify: Validation error: "Password must be at least 8 characters"
9. Capture screenshot: password validation error
10. Try to login with wrong password
11. Verify: Error: "These credentials do not match our records"
12. Capture screenshot: login error
13. Try to access protected page without auth
14. Verify: Redirected to login page
15. Capture screenshot: auth redirect
```

**Verification Points**:
- ✅ URL validation works
- ✅ HTTPS requirement enforced
- ✅ Password validation works
- ✅ Login errors displayed clearly
- ✅ Auth middleware works

**Screenshot Artifacts**:
- `32-url-validation-error.png`
- `33-https-validation-error.png`
- `34-password-validation-error.png`
- `35-login-error.png`

---

## Deliverables

### 1. Test Execution Report

**File**: `docs/testing/PHASE6.5_REAL_BROWSER_TESTING_REPORT.md`

**Contents**:
- Test execution summary
- Pass/fail status for each workflow
- Screenshots embedded in report
- Console errors (if any)
- Network errors (if any)
- Performance observations
- Issues discovered
- Recommendations

### 2. Screenshot Library

**Folder**: `docs/ui/screenshots/real-browser-tests/`

**Files**: 35+ screenshots documenting:
- Every major workflow step
- Forms and validation states
- Success and error states
- Email verification in Mailpit
- UI state at critical points

### 3. Console & Network Logs

**File**: `docs/testing/PHASE6.5_CONSOLE_NETWORK_LOGS.md`

**Contents**:
- Browser console messages from all tests
- Network request/response logs
- Failed requests (if any)
- Performance metrics (page load times)

### 4. Issues & Improvements

**File**: `docs/testing/PHASE6.5_ISSUES_FOUND.md`

**Contents**:
- List of bugs discovered during testing
- UX issues identified
- Performance concerns
- Recommendations for fixes
- Priority levels (Critical → Low)

---

## Success Criteria

### Test Coverage
- ✅ All 6 parts completed (35+ test scenarios)
- ✅ 35+ screenshots captured
- ✅ All critical workflows validated

### Quality Metrics
- ✅ Zero JavaScript console errors
- ✅ Zero network failures (all 200/201 responses)
- ✅ All forms work as expected
- ✅ All emails deliver to Mailpit
- ✅ All visual states match expectations

### Documentation
- ✅ Comprehensive test report
- ✅ Screenshot library with annotations
- ✅ Console/network logs documented
- ✅ Issues prioritized with recommendations

---

## Agent Usage

**Primary Agent**: `browser-tester`
- Specialized in Playwright MCP automation
- Can capture screenshots and accessibility trees
- Monitors console and network requests
- Visual verification of UI state

**Supporting Agent**: `testing-specialist`
- Verifies test coverage
- Documents findings
- Creates test reports
- Analyzes issues

---

## Timeline

**Estimated Time**: 4-5 hours

**Breakdown**:
- Part 1: User Authentication (1 hour)
- Part 2: Website Management (1.5 hours)
- Part 3: Team Management (1.5 hours)
- Part 4: Alert Configuration (30 minutes)
- Part 5: Dashboard Verification (30 minutes)
- Part 6: Form Validation (30 minutes)
- Documentation: 30 minutes (ongoing)

---

## How to Execute This Phase

### Option 1: Automated with Browser Tester Agent

```
Read @docs/implementation-plans/PHASE6.5_REAL_BROWSER_AUTOMATION.md and implement using the browser-tester agent. Run all 35 test scenarios with Playwright MCP, capture screenshots at each step, and create comprehensive test report.
```

### Option 2: Manual Testing with Checklist

Follow `docs/testing/MANUAL_TESTING_CHECKLIST.md` manually, capturing screenshots yourself, and filling out the test report template.

---

## Integration with Phase 6

**Phase 6**: Created integration tests (HTTP/Inertia)
**Phase 6.5**: Validates same workflows with real browser automation

**Complementary Approach**:
- Phase 6 tests run fast in CI/CD (2.3 seconds)
- Phase 6.5 tests provide visual verification (4-5 hours)
- Both together provide comprehensive coverage

**When to Run**:
- Phase 6 tests: Every commit (CI/CD)
- Phase 6.5 tests: Before production deployment, after major UI changes

---

## Next Steps After Completion

1. **Update Phase 6 Completion Summary** with Phase 6.5 results
2. **Integrate findings** into `UX_IMPROVEMENT_SUGGESTIONS.md`
3. **Fix any bugs** discovered during testing
4. **Update browser integration tests** if behavior changes
5. **Proceed to Phase 7** (Documentation Suite) with confidence

---

**Status**: 📋 Planned
**Ready to Execute**: Yes (all prerequisites complete)
**Dependencies**: Phase 6 Complete ✅, Mailpit running ✅, Laravel Sail up ✅
