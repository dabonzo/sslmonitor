# Manual Testing Scenarios - SSL Monitor

This document provides comprehensive manual testing scenarios to validate the SSL Monitor application functionality, including user registration, email configuration, team management, and SSL monitoring features.

## Prerequisites

- Application running locally via `./vendor/bin/sail up -d`
- Clean database state (run `./vendor/bin/sail artisan migrate:fresh` if needed)
- Mailpit available at http://localhost:8025 for email testing
- Two email addresses available for testing (e.g., your personal email and a secondary email)

## 📸 UX Documentation During Testing

**While performing these manual tests, take screenshots to document the current UX:**

1. **Save screenshots** to `docs/images/` folder
2. **Document UX issues** you encounter in `docs/ux-improvement-audit.md`
3. **Note specific problems**: confusing layouts, poor visual hierarchy, unclear navigation, etc.
4. **Focus areas for screenshots**:
   - Dashboard overview (empty state and with data)
   - Website management flow (add, edit, certificate preview)
   - Team management interface (creation, member management)
   - Email settings configuration
   - Form validation and error states
   - Mobile/responsive views (if testing on different devices)

This will create a baseline for UX improvements after manual testing is complete.

---

## Scenario 1: User Registration and Initial Setup

### 1.1 User Registration
**Objective**: Verify new user can register and access the application

**Steps**:
1. Navigate to `http://localhost/register`
2. Fill in registration form:
   - Name: `John Doe`
   - Email: `john@example.com` 
   - Password: `password123`
   - Confirm Password: `password123`
3. Click "Register"

**Expected Results**:
- ✅ User successfully registered
- ✅ Redirected to dashboard
- ✅ Dashboard shows empty state (no websites yet)
- ✅ Navigation shows user name "John Doe"

### 1.2 Dashboard Empty State
**Steps**:
1. Observe dashboard content

**Expected Results**:
- ✅ Status cards show all zeros
- ✅ "No websites added yet" message displayed
- ✅ "Add your first website" call-to-action visible

---

## Scenario 2: Website Management

### 2.1 Add First Website
**Objective**: Test the "Check Before Adding" workflow

**Steps**:
1. Click "Websites" in navigation or "Add Website" button
2. Fill in website form:
   - Name: `Google`
   - URL: `https://google.com`
3. Click "Check Certificate" button
4. Wait for certificate preview to load
5. Click "Add Website" button

**Expected Results**:
- ✅ Certificate preview shows valid SSL details
- ✅ Certificate shows issuer, expiry date, validity status
- ✅ Website successfully added
- ✅ Success toast notification appears
- ✅ Form resets after adding

### 2.2 Add Invalid Website
**Steps**:
1. Add second website:
   - Name: `Invalid Site`
   - URL: `https://invalid-ssl-test-site.com`
2. Click "Check Certificate"
3. Observe error handling

**Expected Results**:
- ✅ Shows certificate error message
- ✅ Can still add website despite SSL issues
- ✅ Website appears in list with error status

### 2.3 Website List Management
**Steps**:
1. View websites list
2. Edit the Google website (change name to "Google Search")
3. Delete the invalid website

**Expected Results**:
- ✅ Websites display with SSL status indicators
- ✅ Edit functionality works correctly
- ✅ Delete functionality works with confirmation
- ✅ List updates in real-time

---

## Scenario 3: Email Settings Configuration

### 3.1 Personal Email Settings
**Objective**: Configure SMTP settings for notifications

**Steps**:
1. Navigate to "Settings" → "Email Settings"
2. Click "Configure Email Settings"
3. Fill in SMTP configuration:
   - Host: `smtp.gmail.com`
   - Port: `587`
   - Encryption: `TLS`
   - Username: `your-email@gmail.com`
   - Password: `your-app-password`
   - From Address: `your-email@gmail.com`
   - From Name: `SSL Monitor`
4. Click "Test Configuration"
5. Click "Save Settings"

**Expected Results**:
- ✅ Form validates required fields
- ✅ Test email functionality works (check email inbox)
- ✅ Settings save successfully
- ✅ Success notification appears

### 3.2 Email Settings Display
**Steps**:
1. Refresh the page
2. Observe loaded settings

**Expected Results**:
- ✅ Previously saved settings are loaded
- ✅ Password field is empty (security)
- ✅ All other fields show saved values

---

## Scenario 4: Team Management Setup

### 4.1 Create Team
**Objective**: Transition from individual to team mode

**Steps**:
1. Navigate to "Settings" → "Team Management"
2. Observe initial team state
3. Fill in team creation form:
   - Team Name: `Development Team`
4. Click "Create Team"

**Expected Results**:
- ✅ Shows "No team yet" message initially
- ✅ Team creation form visible
- ✅ Team created successfully
- ✅ Page updates to show team information
- ✅ Current user listed as "Owner"

### 4.2 Invite Team Member
**Steps**:
1. In team settings, use "Invite User" section
2. Fill in:
   - Email: `colleague@example.com`
   - Role: `Admin`
3. Click "Invite User"

**Expected Results**:
- ✅ User successfully invited
- ✅ New member appears in team list
- ✅ Role correctly displayed
- ✅ Success notification shown

### 4.3 Transfer Personal Websites to Team
**Steps**:
1. In "Personal Websites" section, click "Transfer to Team" for Google website
2. Confirm the transfer

**Expected Results**:
- ✅ Website moves from personal to team section
- ✅ Website shows "Added by: John Doe" attribution
- ✅ Team websites count updates

### 4.4 Team Email Settings Override
**Steps**:
1. Navigate to "Email Settings"
2. Notice the team context indicator
3. Configure different team email settings:
   - Use different SMTP settings than personal
4. Save team settings

**Expected Results**:
- ✅ Page shows "Team Settings for Development Team"
- ✅ Team settings save separately from personal
- ✅ Team settings take precedence when user has team

---

## Scenario 5: Multi-User Testing

### 5.1 Second User Registration
**Objective**: Test team invitation flow

**Steps**:
1. Open incognito/private browser window
2. Register second user:
   - Name: `Jane Smith`
   - Email: `colleague@example.com`
3. Login as second user
4. Check team status

**Expected Results**:
- ✅ Second user can register independently
- ✅ Second user automatically appears in team (if invitation system works)
- ✅ Second user sees team websites
- ✅ Role permissions work correctly

### 5.2 Role Permission Testing
**Steps**:
1. As admin user (Jane), try to:
   - Add new website to team
   - Modify email settings
   - Try to remove team members (should fail)
2. As owner (John), change Jane's role to "Viewer"
3. As viewer (Jane), verify limited permissions

**Expected Results**:
- ✅ Admin can manage websites and settings
- ✅ Admin cannot manage team members
- ✅ Viewer has read-only access
- ✅ Role changes apply immediately

---

## Scenario 6: SSL Monitoring Validation

### 6.1 Dashboard Data Display
**Steps**:
1. Return to main dashboard
2. Observe SSL status summary
3. Check recent checks section

**Expected Results**:
- ✅ Status cards show correct counts
- ✅ Percentages calculate properly
- ✅ Recent checks list displays
- ✅ Critical issues section shows problems

### 6.2 Website Details View
**Steps**:
1. Click on a website from dashboard
2. View detailed SSL information
3. Trigger manual SSL check

**Expected Results**:
- ✅ Detailed certificate information displayed
- ✅ SSL check history visible
- ✅ Manual check triggers successfully
- ✅ Loading states work correctly

---

## Scenario 7: Data Persistence and Navigation

### 7.1 Cross-Session Persistence
**Steps**:
1. Logout
2. Login again
3. Verify all data persists:
   - Websites
   - Team membership
   - Email settings
   - SSL check history

**Expected Results**:
- ✅ All user data persists across sessions
- ✅ Team associations maintained
- ✅ Settings remembered
- ✅ Dashboard shows correct data

### 7.2 Navigation and User Experience
**Steps**:
1. Navigate through all application sections
2. Test responsive design (if applicable)
3. Verify toast notifications
4. Check loading states

**Expected Results**:
- ✅ All navigation links work
- ✅ Page titles update correctly
- ✅ UI remains responsive
- ✅ Error handling works gracefully

---

## Scenario 8: Edge Cases and Error Handling

### 8.1 Form Validation Testing
**Steps**:
1. Try submitting empty forms
2. Test invalid email formats
3. Test invalid URLs
4. Test password requirements

**Expected Results**:
- ✅ Proper validation messages shown
- ✅ Form doesn't submit with invalid data
- ✅ Error messages are user-friendly
- ✅ Forms retain valid input after errors

### 8.2 SSL Check Error Scenarios
**Steps**:
1. Add website with expired SSL certificate
2. Add website that's completely unreachable
3. Add website with self-signed certificate

**Expected Results**:
- ✅ Different error types handled properly
- ✅ Error messages are descriptive
- ✅ Application remains stable
- ✅ Users can still manage problematic websites

---

## Automated Alternative: Laravel Dusk

If you want to automate these manual tests, you can implement them using **Laravel Dusk**:

```bash
# Install Dusk
./vendor/bin/sail composer require --dev laravel/dusk
./vendor/bin/sail artisan dusk:install

# Create browser tests
./vendor/bin/sail artisan dusk:make UserRegistrationTest
./vendor/bin/sail artisan dusk:make TeamManagementTest
./vendor/bin/sail artisan dusk:make WebsiteManagementTest

# Run Dusk tests
./vendor/bin/sail dusk
```

Example Dusk test structure:
```php
public function testUserCanRegisterAndCreateTeam()
{
    $this->browse(function (Browser $browser) {
        $browser->visit('/register')
                ->type('name', 'John Doe')
                ->type('email', 'john@example.com')
                ->type('password', 'password123')
                ->type('password_confirmation', 'password123')
                ->press('Register')
                ->assertPathIs('/dashboard')
                ->assertSee('John Doe');
    });
}
```

---

## Testing Checklist

**Before Release:**
- [ ] All registration flows work
- [ ] Email settings save and load correctly  
- [ ] Team creation and invitation process
- [ ] Website adding and SSL checking
- [ ] Role-based permissions enforced
- [ ] Data persistence across sessions
- [ ] Error handling and validation
- [ ] Cross-browser compatibility
- [ ] Responsive design (mobile/tablet)
- [ ] Performance with multiple websites

**Post-Release Monitoring:**
- [ ] SSL check accuracy
- [ ] Email delivery rates
- [ ] Team collaboration workflows
- [ ] Database performance
- [ ] Queue processing efficiency

This manual testing approach ensures comprehensive validation of your SSL Monitor's team management functionality before production deployment.