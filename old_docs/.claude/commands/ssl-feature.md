# /ssl-feature - SSL Monitoring Feature Development

**Purpose**: Implement a new SSL monitoring feature using TDD and VRISTO integration.

**Usage**: `/ssl-feature [feature-name] [description]`

## Development Workflow

Please implement the SSL monitoring feature: **$ARGUMENTS**.

Follow these steps:

### 1. Context Discovery & Planning
```bash
# Get application context
application-info
database-schema
list-routes --path=ssl

# Research SSL monitoring patterns
search-docs ["ssl certificate", "spatie ssl certificate", "laravel jobs"]
use context7: "SSL certificate validation JavaScript browser APIs"

# Check existing SSL monitoring code
filesystem-mcp: read-directory app/Services/
filesystem-mcp: grep-files app/ "SslCertificate"
```

### 2. Git Flow Setup
```bash
# Create feature branch
git-mcp: create-branch feature/ssl-$ARGUMENTS develop
git-mcp: push origin feature/ssl-$ARGUMENTS --set-upstream
```

### 3. TDD Implementation
```bash
# Create test file
./vendor/bin/sail artisan make:test --pest Ssl${ARGUMENTS}Test

# Write failing tests first (RED)
# - Happy path: Valid SSL certificate
# - Error cases: Expired, invalid, unreachable
# - Edge cases: Wildcard certificates, chain validation

# Run tests to confirm they fail
./vendor/bin/sail artisan test --filter=Ssl${ARGUMENTS}Test
```

### 4. Core Implementation (GREEN)
```bash
# Create necessary classes
./vendor/bin/sail artisan make:service SslCertificate${ARGUMENTS}Service
./vendor/bin/sail artisan make:job CheckSsl${ARGUMENTS}Job
./vendor/bin/sail artisan make:model Ssl${ARGUMENTS} -m

# Implement minimal code to pass tests
# Run tests until they pass
./vendor/bin/sail artisan test --filter=Ssl${ARGUMENTS}Test
```

### 5. VRISTO UI Integration
```bash
# Extract relevant VRISTO templates
filesystem-mcp: read-file vristo-html-main/forms.html
filesystem-mcp: copy-file vristo-html-main/dashboard.html resources/views/ssl/dashboard.blade.php

# Research Vue.js patterns for SSL monitoring UI
use context7: "Vue 3 composition API form validation"
use context7: "VRISTO dashboard cards SSL certificate status"

# Create Vue components
./vendor/bin/sail artisan make:volt ssl/certificate-status
./vendor/bin/sail artisan make:volt ssl/certificate-form
```

### 6. API Integration
```bash
# Create API controllers and routes
./vendor/bin/sail artisan make:controller Api/Ssl${ARGUMENTS}Controller --api
./vendor/bin/sail artisan make:request Ssl${ARGUMENTS}Request

# Add routes to api.php
# Implement REST endpoints for SSL feature
```

### 7. Browser Testing
```bash
# Create Playwright browser tests
./vendor/bin/sail artisan make:test --pest Browser/Ssl${ARGUMENTS}BrowserTest

# Test user workflows:
# - Adding SSL monitoring
# - Viewing certificate status
# - Receiving notifications
# - Mobile responsive behavior
```

### 8. Refactoring & Optimization (REFACTOR)
```bash
# Code quality checks
./vendor/bin/sail exec laravel.test ./vendor/bin/pint

# Performance optimization
# - Database query optimization
# - Caching strategies
# - Job queue efficiency

# Documentation updates
filesystem-mcp: update-file docs/ssl-monitoring/api.md
```

### 9. Testing & Quality Assurance
```bash
# Run full test suite
./vendor/bin/sail artisan test

# Check logs for errors
filesystem-mcp: tail-file storage/logs/laravel.log 50
last-error

# Browser console testing
browser-logs --entries=20
```

### 10. Commit & Review
```bash
# Stage and commit changes
git-mcp: add-all
git-mcp: commit "Implement SSL ${ARGUMENTS} feature with VRISTO UI and comprehensive tests"
git-mcp: push origin feature/ssl-$ARGUMENTS

# Create pull request
# Target: feature/ssl-$ARGUMENTS → develop
```

## Key Requirements

### SSL Feature Standards
- ✅ **Spatie SSL Certificate** integration for validation
- ✅ **Background job processing** for certificate checks
- ✅ **VRISTO UI components** for professional interface
- ✅ **Real-time notifications** for certificate issues
- ✅ **Comprehensive testing** (Unit + Feature + Browser)
- ✅ **Mobile responsive** design
- ✅ **Error handling** for network and certificate issues

### Testing Requirements
- **Unit Tests**: Service layer logic, validation rules
- **Feature Tests**: HTTP endpoints, database operations
- **Browser Tests**: User workflows, VRISTO interactions
- **Error Scenarios**: Network failures, expired certificates

### Documentation Updates
- Update API specification with new endpoints
- Add feature documentation to project plan
- Update VRISTO integration guide if new patterns used

## Success Criteria
1. All tests pass (unit, feature, browser)
2. VRISTO UI integrates seamlessly
3. SSL monitoring works reliably
4. Code follows Laravel conventions
5. Git Flow workflow completed
6. Documentation updated

**Ready to build professional SSL monitoring features!** 🔐