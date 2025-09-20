# /ssl-feature - Complete SSL Feature Development Workflow

**Purpose**: Implement SSL monitoring features using TDD on existing professional frontend foundation.

**Usage**: `/ssl-feature [feature-name] [description]`

## Phase 2 Development Workflow

Please implement the SSL monitoring feature: **$ARGUMENTS**.

**Strategic Context**: Frontend is already professional with Vue 3 + TypeScript + shadcn/ui. Focus on connecting real SSL backend to existing UI components.

Follow these steps:

### 1. Context Discovery & Planning
```bash
# Get current application state
application-info
database-schema
list-routes

# Research Laravel SSL patterns
search-docs ["ssl certificate", "inertia controllers", "laravel jobs"]

# Check existing SSL backend services
filesystem-mcp: read-directory app/Services/
filesystem-mcp: read-file app/Services/SslCertificateChecker.php
```

### 2. Git Flow Setup
```bash
# Create feature branch
git-mcp: create-branch feature/ssl-$ARGUMENTS develop
git-mcp: push origin feature/ssl-$ARGUMENTS --set-upstream
```

### 3. TDD Implementation (RED-GREEN-REFACTOR)
```bash
# Create feature test file
./vendor/bin/sail artisan make:test --pest Feature/Ssl${ARGUMENTS}Test

# Write failing tests first (RED)
# - Controller endpoints return correct Inertia responses
# - SSL service integration works correctly
# - Data validation and error handling

# Run tests to confirm they fail
./vendor/bin/sail artisan test --filter=Ssl${ARGUMENTS}Test
```

### 4. Inertia.js Controller Implementation (GREEN)
```bash
# Create Inertia controller (not API controller)
./vendor/bin/sail artisan make:controller Ssl${ARGUMENTS}Controller
./vendor/bin/sail artisan make:request Ssl${ARGUMENTS}Request

# Implement controller methods returning Inertia::render()
# Connect to existing SSL services (SslCertificateChecker, etc.)
# Add routes to web.php (not api.php)

# Run tests until they pass
./vendor/bin/sail artisan test --filter=Ssl${ARGUMENTS}Test
```

### 5. Vue.js Page Integration
```bash
# Create Vue page using existing component patterns
# Check existing pages for component structure
filesystem-mcp: read-file resources/js/pages/Dashboard.vue
filesystem-mcp: read-directory resources/js/components/ui/

# Create SSL page following existing patterns
# Use existing shadcn/ui components (Card, Button, Table, etc.)
# Implement TypeScript interfaces for SSL data
# Connect to Inertia controller endpoints
```

### 6. Frontend Enhancement
```bash
# Research existing component patterns
use context7: "Vue 3 composition API with TypeScript"
use context7: "Inertia.js form handling best practices"

# Enhance Dashboard.vue or create new SSL pages
# Use existing layout components (DashboardLayout)
# Implement proper loading states and error handling
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

### Phase 2 SSL Feature Standards
- ✅ **Existing SSL backend** integration (SslCertificateChecker, Website model)
- ✅ **Inertia.js controllers** returning structured data
- ✅ **Professional Vue components** using existing shadcn/ui patterns
- ✅ **Real-time SSL status** updates and notifications
- ✅ **Comprehensive testing** (Feature + Browser tests)
- ✅ **TypeScript interfaces** for SSL data structures
- ✅ **Responsive design** using existing layout components

### Testing Requirements
- **Feature Tests**: Inertia.js responses, SSL service integration
- **Browser Tests**: User workflows with existing UI components
- **Error Scenarios**: SSL failures, network issues, validation errors
- **Data Validation**: SSL certificate data structures and responses

### Integration Standards
- Use existing **DashboardLayout** and UI component patterns
- Connect to proven **SslCertificateChecker** and **Website** model
- Follow existing **TypeScript and Vue 3 Composition API** patterns
- Maintain consistency with current **authentication and settings** pages

## Success Criteria
1. All Pest tests pass (feature and browser)
2. Real SSL data connects to professional frontend
3. SSL monitoring integrates with existing UI patterns
4. Code follows established Laravel + Vue patterns
5. Git Flow workflow completed with proper commits
6. No regression in existing functionality

**Ready to build production-ready SSL features on solid foundation!** 🚀