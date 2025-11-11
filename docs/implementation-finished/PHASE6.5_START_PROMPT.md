# Phase 6.5 - Real Browser Automation Testing - START PROMPT

Copy and paste this entire prompt into a new Claude Code session:

---

Read @docs/implementation-plans/PHASE6.5_REAL_BROWSER_AUTOMATION.md and implement the testing suite exactly as specified.

## Context

Phase 6 created 100+ integration tests using HTTP/Inertia assertions, but did NOT test real browser interactions. Phase 6.5 fills this gap by validating actual user workflows with real browser automation.

**What Phase 6 Did:**
- ✅ Integration tests with HTTP assertions
- ✅ Inertia component verification
- ✅ Database state validation

**What Phase 6 Did NOT Do:**
- ❌ Real button clicks and form interactions
- ❌ Visual verification of UI elements
- ❌ Form validation error messages
- ❌ Email verification in Mailpit interface
- ❌ Modal and confirmation dialog testing
- ❌ Real-world user workflow testing

## Implementation Requirements

**Use These Agents:**
- `browser-tester` (primary) - Playwright MCP browser automation
- `testing-specialist` (secondary) - Test quality and patterns

**Test Against:**
- Development environment: `http://localhost` (ensure `./vendor/bin/sail up -d` is running)
- Mailpit: `http://localhost:8025` (for email verification)

**Follow All 6 Parts:**

### Part 1: User Authentication Workflows (1 hour)
- User registration with email verification in Mailpit
- Login flow with 2FA setup and challenge
- Password reset flow with email verification
- Test invalid credentials and validation errors

### Part 2: Website Management Workflows (1.5 hours)
- Create website with real button clicks and form fills
- Edit website configuration (URL, monitoring settings)
- Delete website with confirmation modal
- Test form validation (invalid URLs, HTTPS requirement)

### Part 3: Team Management Workflows (1.5 hours)
- Create team and invite members
- Accept team invitation (new browser session/incognito)
- Move website to team
- Test role permissions (Owner, Admin, Viewer)
- Remove team members

### Part 4: Alert Configuration & Email Verification (30 min)
- Configure SSL and uptime alerts via UI
- Trigger test alert via Debug Menu
- Verify alert email in Mailpit (open email, check content)
- Test action buttons in email (if applicable)

### Part 5: Dashboard & Visual Verification (30 min)
- Dashboard metrics and charts rendering
- Real-time updates and data refresh
- Console and network monitoring (zero errors expected)
- Visual verification of all major UI components

### Part 6: Form Validation & Error Handling (30 min)
- Invalid URL validation messages
- HTTPS requirement enforcement
- Password strength validation
- Login error messages
- Form field validation (required fields, formats)

## Deliverables Required

1. **Test Execution Report**:
   - 35+ test scenarios executed and documented
   - Pass/fail status for each scenario
   - Execution time and performance notes

2. **Visual Documentation**:
   - 35+ screenshots captured (one per scenario minimum)
   - Organized by workflow part
   - Annotated with findings

3. **Technical Logs**:
   - Console logs (should be clean, zero errors)
   - Network requests (monitor for failed requests)
   - Any JavaScript errors or warnings

4. **Issues & Improvements Document**:
   - UI/UX issues discovered
   - Form validation gaps
   - Error message clarity
   - Performance observations
   - Recommendations for Phase 9 (UI/UX Refinement)

5. **Test Report Structure**:
   ```markdown
   # Phase 6.5 - Real Browser Automation Testing Report

   ## Executive Summary
   - Total scenarios tested: X/35+
   - Pass rate: X%
   - Critical issues found: X
   - Recommendations: X

   ## Part 1: User Authentication Workflows
   ### Scenario 1.1: User Registration
   - Status: ✅ PASS / ❌ FAIL
   - Screenshot: [path]
   - Console logs: [clean/errors]
   - Notes: [findings]

   [... continue for all scenarios ...]

   ## Issues & Improvements
   [Detailed findings with priorities]

   ## Console & Network Analysis
   [Summary of technical observations]

   ## Recommendations for Phase 9
   [Actionable improvements for UI/UX refinement]
   ```

## Success Criteria

- ✅ All 35+ scenarios executed and documented
- ✅ 35+ screenshots captured
- ✅ Console logs clean (zero JavaScript errors)
- ✅ Network requests successful (no failed API calls)
- ✅ Comprehensive test report created
- ✅ Issues document ready for Phase 9

## Technology Details

**Playwright MCP Tools Available:**
- `browser_navigate` - Navigate to URLs
- `browser_click` - Click buttons/links
- `browser_type` - Fill form fields
- `browser_snapshot` - Capture accessibility tree
- `browser_take_screenshot` - Visual documentation
- `browser_console_messages` - Console log monitoring
- `browser_network_requests` - Network traffic analysis
- `browser_wait_for` - Wait for elements/text

**Test Data:**
- Use unique timestamps for email addresses (avoid conflicts)
- Use test data that can be cleaned up after
- Document any test users/websites created

## Important Notes

1. **Run tests against clean database** - Consider `./vendor/bin/sail artisan migrate:fresh --seed` before starting
2. **Mailpit verification** - Actually open emails in Mailpit UI and verify content/formatting
3. **Form validation** - Test invalid inputs to verify error messages display correctly
4. **Modal interactions** - Verify confirmation dialogs work properly
5. **Console monitoring** - Check for ANY JavaScript errors or warnings
6. **Network monitoring** - Watch for failed API requests or slow responses
7. **Screenshot naming** - Use descriptive names: `part1_scenario1_registration_form.png`

## File Locations

**Save Report To:** `docs/implementation-plans/PHASE6.5_BROWSER_AUTOMATION_REPORT.md`
**Save Screenshots To:** `docs/testing/screenshots/phase6.5/`
**Save Issues To:** `docs/ui/PHASE6.5_ISSUES_AND_IMPROVEMENTS.md`

---

**Estimated Time:** 4-5 hours total

**Start by:** Reading the full implementation plan at @docs/implementation-plans/PHASE6.5_REAL_BROWSER_AUTOMATION.md

**Remember:** This is about validating real user workflows that a human would perform. Test as if you're a new user discovering the application for the first time.
