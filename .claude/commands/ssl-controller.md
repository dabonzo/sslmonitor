# /ssl-controller - Inertia.js SSL Controller Generator

**Purpose**: Create Inertia.js controllers with SSL-specific patterns and testing.

**Usage**: `/ssl-controller [controller-name] [description]`

## Controller Development Workflow

Please create the SSL Inertia.js controller: **$ARGUMENTS**.

**Context**: Generate controllers that return Inertia::render() responses for existing Vue.js frontend.

Follow these steps:

### 1. Context Analysis
```bash
# Check existing controller patterns
filesystem-mcp: read-file app/Http/Controllers/Controller.php
filesystem-mcp: read-directory app/Http/Controllers/

# Review existing SSL services
filesystem-mcp: read-file app/Services/SslCertificateChecker.php
filesystem-mcp: read-file app/Models/Website.php

# Check current routes structure
list-routes
```

### 2. TDD Setup
```bash
# Create feature test for controller
./vendor/bin/sail artisan make:test --pest Feature/${ARGUMENTS}ControllerTest

# Write failing tests first:
# - Index method returns Inertia response with SSL data
# - Store method creates and validates SSL resources
# - Show method returns individual SSL resource data
# - Update/Delete methods with proper authorization

# Confirm tests fail
./vendor/bin/sail artisan test --filter=${ARGUMENTS}ControllerTest
```

### 3. Controller Implementation
```bash
# Create Inertia controller (not API controller)
./vendor/bin/sail artisan make:controller ${ARGUMENTS}Controller

# Create form request for validation
./vendor/bin/sail artisan make:request ${ARGUMENTS}Request

# Implement controller methods:
# - Use Inertia::render() for all responses
# - Connect to existing SSL services
# - Return structured data for Vue components
# - Handle errors with proper Inertia error responses
```

### 4. Route Configuration
```bash
# Add routes to web.php (not api.php)
# Use resourceful routes where appropriate
# Apply auth middleware for SSL management routes
# Consider route model binding for Website resources

# Example route structure:
# Route::resource('ssl/websites', WebsiteController::class)->middleware('auth');
```

### 5. Data Structure Design
```bash
# Design controller response data for Vue components
# Check existing page props structure
filesystem-mcp: read-file resources/js/pages/Dashboard.vue

# Ensure consistent data formatting:
# - SSL certificate status arrays
# - Website monitoring configuration
# - Pagination for large datasets
# - Real-time status indicators
```

### 6. Service Integration
```bash
# Connect to existing SSL backend services
# Use dependency injection for services
# Handle SSL checking with proper error handling
# Integrate with Website model relationships

# Example service usage:
# - SslCertificateChecker for manual checks
# - Website model for CRUD operations
# - SslStatusCalculator for status determination
```

### 7. Testing Validation
```bash
# Run controller tests to ensure they pass
./vendor/bin/sail artisan test --filter=${ARGUMENTS}ControllerTest

# Test Inertia responses:
# - Correct component names returned
# - Proper data structure in props
# - Error handling returns correct status codes
# - Authorization works correctly
```

### 8. Code Quality
```bash
# Format code according to project standards
./vendor/bin/sail exec laravel.test ./vendor/bin/pint

# Validate no syntax errors
./vendor/bin/sail artisan config:cache
```

## Controller Pattern Standards

### Inertia.js Response Pattern
```php
public function index()
{
    return Inertia::render('Ssl/Websites/Index', [
        'websites' => Website::with('sslCertificates')
            ->where('user_id', auth()->id())
            ->paginate(15),
        'statistics' => $this->getWebsiteStatistics(),
    ]);
}
```

### SSL Service Integration
```php
public function check(Website $website, SslCertificateChecker $checker)
{
    $result = $checker->check($website->url);

    return Inertia::render('Ssl/Websites/Show', [
        'website' => $website,
        'certificateResult' => $result,
        'lastChecks' => $website->sslChecks()->latest()->take(10)->get(),
    ]);
}
```

### Error Handling Pattern
```php
public function store(SslWebsiteRequest $request)
{
    try {
        $website = Website::create($request->validated());

        return redirect()
            ->route('ssl.websites.show', $website)
            ->with('success', 'Website added for SSL monitoring');

    } catch (Exception $e) {
        return back()
            ->withErrors(['url' => 'Failed to add website for monitoring'])
            ->withInput();
    }
}
```

## Testing Requirements

### Feature Test Structure
- **HTTP Response Tests**: Correct status codes and Inertia responses
- **Data Validation**: Proper data structure in component props
- **Authorization Tests**: Route protection and user access control
- **SSL Integration**: Service calls work correctly
- **Error Scenarios**: Network failures and validation errors

### Test Coverage Areas
- All controller methods (index, create, store, show, edit, update, destroy)
- Form validation with various input scenarios
- SSL service integration and error handling
- User authorization and data filtering
- Pagination and sorting functionality

## Success Criteria
1. All feature tests pass
2. Controller returns proper Inertia responses
3. SSL services integrate correctly
4. Data structure matches Vue component expectations
5. Error handling works consistently
6. Code follows Laravel conventions

**Ready to build efficient Inertia.js controllers for SSL monitoring!** ⚡