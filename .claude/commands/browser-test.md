# /browser-test - Comprehensive Browser Testing Workflow

**Purpose**: Execute Playwright browser testing with real database integration for SSL Monitor v3

## Usage
```
/browser-test [test-type] [device]
```

**Parameters**:
- `test-type` (optional): `comprehensive` | `visual` | `performance` | `responsive` | `cross-browser`
- `device` (optional): `desktop` | `mobile` | `tablet` | `all`

**Examples**:
- `/browser-test` - Run comprehensive test suite
- `/browser-test visual mobile` - Visual testing on mobile devices
- `/browser-test performance` - Performance testing only
- `/browser-test cross-browser` - Test across multiple browsers

## Workflow Steps

### 1. **Environment Verification**
Check that development environment is properly configured:

```bash
# Verify Laravel Sail is running
./vendor/bin/sail ps

# Verify assets are built for testing
./vendor/bin/sail npm run build

# Check if Playwright browsers are installed
npx playwright --version
```

### 2. **Playwright Setup**
Install Playwright browsers if not already available:

```bash
# Install Playwright and browsers (one-time setup)
npx playwright install

# Install system dependencies (if needed)
npx playwright install-deps
```

### 3. **Browser Test Execution**

#### Comprehensive Test Suite
```bash
# Run the main comprehensive test
node run-comprehensive-test.js

# Expected output:
# 🚀 Starting comprehensive SSL Monitor v3 user flow test...
# ✅ Login page loads successfully
# ✅ Registration page navigation works
# ✅ Registration form accepts input
# ✅ Forgot password page loads
# ✅ Dashboard page loads
# ✅ Login form functionality works
# ✅ Responsive design works on mobile/tablet
# ✅ Password visibility toggle works
# 📸 Screenshots saved to tests/Browser/screenshots/
```

#### Advanced Playwright Test Suite
```bash
# Run structured Playwright tests
npx playwright test tests/Browser/ComprehensiveUserFlowTest.js

# Run with UI mode for debugging
npx playwright test --ui

# Run specific test groups
npx playwright test --grep "user registration"
npx playwright test --grep "responsive design"
```

### 4. **Visual Validation**
Review generated screenshots:

```bash
# List all screenshots
ls -la tests/Browser/screenshots/

# Key screenshots to verify:
# - comprehensive-01-login-page.png
# - comprehensive-02-register-page.png
# - comprehensive-05-dashboard.png
# - comprehensive-07-mobile-view.png
# - comprehensive-08-tablet-view.png
```

### 5. **Cross-Browser Testing**
Test across multiple browsers:

```bash
# Test in Chromium (default)
npx playwright test --project=chromium

# Test in Firefox
npx playwright test --project=firefox

# Test in WebKit (Safari)
npx playwright test --project=webkit

# Test in all browsers
npx playwright test
```

### 6. **Performance Analysis**
Monitor page load times and performance:

```bash
# Generate performance report
npx playwright test --reporter=html

# Open performance report
npx playwright show-report
```

## Testing Features Covered

### ✅ **Authentication Flow**
- Login page rendering and functionality
- Registration form interaction
- Forgot password page navigation
- Form validation and error handling

### ✅ **Navigation Testing**
- Route transitions between auth pages
- Dashboard access after login
- Link functionality and routing

### ✅ **Responsive Design**
- Desktop layout (1920x1080)
- Tablet layout (768x1024)
- Mobile layout (375x667)
- Mobile layout large (414x896)

### ✅ **Interactive Elements**
- Password visibility toggle
- Form field interactions
- Button click handlers
- Input validation

### ✅ **Visual Validation**
- VRISTO template styling
- Color schemes and gradients
- Typography and spacing
- Component rendering

### ✅ **Real Database Integration**
- Actual form submissions
- Database interaction testing
- No complex mocking required
- Development data safety

## Troubleshooting

### Common Issues

#### 1. **Blank Page Errors**
```bash
# Build fresh assets
./vendor/bin/sail npm run build

# Clear Laravel caches
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan view:clear
./vendor/bin/sail artisan config:clear
```

#### 2. **Playwright Installation Issues**
```bash
# Install browsers with system dependencies
npx playwright install-deps
npx playwright install

# For Docker environments, may need to run inside container
./vendor/bin/sail exec laravel.test npx playwright install
```

#### 3. **Asset Loading Errors**
```bash
# Check Vite development server
./vendor/bin/sail npm run dev

# Or ensure production build is available
./vendor/bin/sail npm run build
```

#### 4. **Port Conflicts**
```bash
# Check if Vite dev server is running on port 5173
netstat -tulpn | grep :5173

# Kill conflicting processes if needed
pkill -f "vite"
```

### Debug Mode
Run tests with debugging enabled:

```bash
# Run with visible browser (non-headless)
PLAYWRIGHT_HEADLESS=false node run-comprehensive-test.js

# Run with step-by-step debugging
npx playwright test --debug

# Run specific test with browser UI
npx playwright test tests/Browser/ComprehensiveUserFlowTest.js --headed
```

## Integration with Development Workflow

### 1. **Pre-Deployment Validation**
```bash
# Before any major release
/browser-test comprehensive
```

### 2. **Feature Development**
```bash
# After implementing new UI features
/browser-test visual desktop
/browser-test responsive
```

### 3. **Bug Investigation**
```bash
# When investigating UI issues
/browser-test performance
# Review screenshots in tests/Browser/screenshots/
```

### 4. **Continuous Integration**
```bash
# Add to CI/CD pipeline
npx playwright test --reporter=junit
```

## Expected Outcomes

### ✅ **Successful Test Results**
- All pages load without JavaScript errors
- Forms accept input and submit correctly
- Navigation works across all pages
- Responsive design functions on all device sizes
- Visual elements render with proper VRISTO styling

### 📸 **Screenshot Gallery**
Complete visual documentation of:
- Login page (desktop/mobile/tablet)
- Registration page
- Forgot password page
- Dashboard interface
- Form interactions
- Error states

### 🚀 **Ready for Backend Development**
Once browser tests pass, the frontend is validated and ready for:
- SSL monitoring backend implementation
- Database integration
- Real authentication system
- API endpoint development

## Notes

- **Development Database**: Tests use real development database, safe for testing
- **Visual Regression**: Screenshots provide visual change detection
- **Performance Monitoring**: Load times and interaction speeds measured
- **Cross-Browser**: Validated across Chromium, Firefox, and WebKit
- **Mobile-First**: Responsive design thoroughly tested

This browser testing workflow ensures SSL Monitor v3 provides a flawless user experience across all devices and browsers before backend development begins.