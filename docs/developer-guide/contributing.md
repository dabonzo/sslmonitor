# Contributing to SSL Monitor

Guide for developers who want to contribute to the SSL Monitor project.

## 🎯 Overview

SSL Monitor is built with Laravel 12, Livewire 3, and follows strict Test-Driven Development (TDD) practices. This guide will help you set up your development environment and contribute effectively.

## 🔧 Development Setup

### Prerequisites

- **PHP 8.2+** with required extensions
- **Composer 2.0+** for dependency management
- **Node.js 18+** and npm for frontend assets
- **Docker** (optional, for Laravel Sail)
- **Git** for version control

### Local Development with Laravel Sail (Recommended)

#### 1. Clone and Setup

```bash
# Clone repository
git clone https://github.com/yourorg/ssl-monitor.git
cd ssl-monitor

# Copy environment file
cp .env.example .env

# Start Docker containers
./vendor/bin/sail up -d

# Install dependencies
./vendor/bin/sail composer install
./vendor/bin/sail npm install

# Generate application key
./vendor/bin/sail artisan key:generate

# Run migrations
./vendor/bin/sail artisan migrate

# Build frontend assets
./vendor/bin/sail npm run dev
```

#### 2. Access the Application

- **Web Interface**: http://localhost
- **Database**: localhost:3306 (MySQL)
- **Redis**: localhost:6379
- **Mailpit**: http://localhost:8025

### Alternative: Native Development Setup

#### 1. Environment Setup

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies  
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Configure database (MySQL/PostgreSQL)
# Update .env with your database credentials

# Run migrations
php artisan migrate

# Start development servers
php artisan serve          # Backend (port 8000)
npm run dev                # Frontend (Vite)
```

#### 2. Queue Worker Setup

```bash
# Start queue worker for SSL monitoring
php artisan queue:work --queue=ssl-monitoring
```

## 🧪 Running Tests

### Test Suite Execution

```bash
# With Sail (recommended)
./vendor/bin/sail artisan test

# Native PHP
php artisan test

# Run specific test file
./vendor/bin/sail artisan test tests/Feature/Models/WebsiteTest.php

# Run with coverage (requires Xdebug)
./vendor/bin/sail artisan test --coverage
```

### Test Types and Organization

```bash
# Run specific test categories
./vendor/bin/sail artisan test tests/Feature/Models/      # Model tests
./vendor/bin/sail artisan test tests/Feature/Services/   # Service tests  
./vendor/bin/sail artisan test tests/Feature/Livewire/   # Component tests
```

## 🔄 Development Workflow

### 1. Feature Development (TDD Approach)

#### Start with a Test
```bash
# Create feature test
./vendor/bin/sail artisan make:test --pest WebsiteGroupsTest

# Write failing test first
test('user can create website group', function () {
    $user = User::factory()->create();
    
    Livewire::actingAs($user)
        ->test(WebsiteGroups::class)
        ->set('name', 'Production Sites')
        ->call('createGroup')
        ->assertHasNoErrors();
        
    expect($user->websiteGroups()->count())->toBe(1);
});
```

#### Implement Feature
```bash
# Create model
./vendor/bin/sail artisan make:model WebsiteGroup -m

# Create Livewire component
./vendor/bin/sail artisan make:livewire WebsiteGroups

# Run tests to ensure they pass
./vendor/bin/sail artisan test --filter="website group"
```

#### Refactor and Optimize
```bash
# Format code
./vendor/bin/pint

# Run full test suite
./vendor/bin/sail artisan test

# Check for any regressions
```

### 2. Git Workflow

#### Branch Naming Convention

```bash
# Feature branches
git checkout -b feature/website-groups
git checkout -b feature/email-templates

# Bug fixes
git checkout -b fix/ssl-check-timeout
git checkout -b fix/dashboard-loading

# Hotfixes
git checkout -b hotfix/critical-ssl-bug
```

#### Commit Message Format

```bash
# Format: type(scope): description

# Examples
git commit -m "feat(website): add website grouping functionality"
git commit -m "fix(ssl): handle timeout errors in certificate checker"
git commit -m "test(livewire): add website management component tests"
git commit -m "docs(api): update SSL service documentation"
git commit -m "refactor(dashboard): improve query performance"
```

#### Pull Request Process

1. **Create feature branch** from `develop`
2. **Write tests first** (TDD approach)
3. **Implement feature** with passing tests
4. **Run full test suite** to ensure no regressions
5. **Format code** with Laravel Pint
6. **Create pull request** with detailed description
7. **Address review feedback** if needed
8. **Merge** after approval and passing CI/CD

## 📝 Code Standards

### PHP Standards (Laravel Pint)

```bash
# Check code style
./vendor/bin/pint --test

# Fix code style issues
./vendor/bin/pint

# Format specific files/directories
./vendor/bin/pint app/Models/
./vendor/bin/pint app/Services/SslCertificateChecker.php
```

#### Pint Configuration (`.pint.json`)

```json
{
    "preset": "laravel",
    "rules": {
        "method_chaining_indentation": true,
        "no_unused_imports": true,
        "ordered_imports": true,
        "phpdoc_separation": true,
        "single_trait_insert_per_statement": true
    }
}
```

### Code Quality Guidelines

#### Model Standards
```php
class Website extends Model
{
    // Use explicit type hints
    protected function setUrlAttribute(string $value): void
    {
        // Implementation
    }
    
    // Document complex relationships
    public function sslCertificates(): HasMany
    {
        return $this->hasMany(SslCertificate::class);
    }
    
    // Use descriptive method names
    public function getCurrentSslCertificate(): ?SslCertificate
    {
        return $this->sslCertificates()
            ->where('is_valid', true)
            ->latest('created_at')
            ->first();
    }
}
```

#### Service Standards
```php
class SslCertificateChecker
{
    // Use constructor property promotion
    public function __construct(
        private Certificate $certificateClient,
        private SslStatusCalculator $statusCalculator
    ) {}
    
    // Return type hints are mandatory
    public function checkCertificate(string $url): SslCertificateResult
    {
        // Implementation with proper error handling
        try {
            $certificate = $this->certificateClient->get($url);
            return new SslCertificateResult($certificate);
        } catch (Exception $e) {
            return SslCertificateResult::failed($e->getMessage());
        }
    }
}
```

#### Livewire Component Standards
```php
class WebsiteManagement extends Component  
{
    // Use typed properties
    public Collection $websites;
    public bool $showAddForm = false;
    public string $url = '';
    
    // Mount with authorization
    public function mount(): void
    {
        $this->authorize('viewAny', Website::class);
        $this->loadWebsites();
    }
    
    // Descriptive action methods
    public function addWebsite(): void
    {
        $this->validate([
            'url' => 'required|url|unique:websites,url,NULL,id,user_id,' . auth()->id(),
        ]);
        
        // Implementation
    }
}
```

### Testing Standards

#### Test Naming
```php
// ❌ Poor test names
test('website test', function () {});
test('ssl works', function () {});

// ✅ Descriptive test names  
test('website url is automatically sanitized to lowercase https', function () {});
test('ssl certificate checker handles connection timeouts gracefully', function () {});
```

#### Test Organization
```php
// Group related tests
describe('Website URL sanitization', function () {
    test('converts http to https', function () {
        $website = Website::factory()->create(['url' => 'http://example.com']);
        expect($website->url)->toBe('https://example.com');
    });
    
    test('converts to lowercase', function () {
        $website = Website::factory()->create(['url' => 'HTTPS://EXAMPLE.COM']);  
        expect($website->url)->toBe('https://example.com');
    });
});

describe('SSL Certificate Status Calculation', function () {
    test('calculates days until expiry correctly', function () {
        // Test implementation
    });
    
    test('identifies expiring soon certificates', function () {
        // Test implementation
    });
});
```

## 🏗️ Architecture Guidelines

### Service Layer Pattern

```php
// Create services for business logic
class SslMonitoringService
{
    public function __construct(
        private SslCertificateChecker $checker,
        private SslStatusCalculator $calculator
    ) {}
    
    public function checkAllWebsites(User $user): Collection
    {
        return $user->websites()
            ->get()
            ->map(fn($website) => $this->checkWebsite($website));
    }
    
    private function checkWebsite(Website $website): SslCheckResult
    {
        // Business logic implementation
    }
}
```

### Job Design Patterns

```php
class CheckSslCertificateJob implements ShouldQueue
{
    use Queueable, SerializesModels;
    
    // Job configuration
    public int $tries = 3;
    public int $timeout = 60;  
    public string $queue = 'ssl-monitoring';
    
    // Idempotent design
    public function handle(SslCertificateChecker $checker): void
    {
        // Skip if recently checked (idempotent)
        if ($this->website->wasRecentlyChecked()) {
            return;
        }
        
        // Perform SSL check
        $result = $checker->checkCertificate($this->website->url);
        
        // Store results
        $this->website->sslChecks()->create([
            'status' => $result->getStatus(),
            'error_message' => $result->getErrorMessage(),
        ]);
    }
    
    // Failure handling
    public function failed(Throwable $exception): void
    {
        Log::error('SSL check failed', [
            'website_id' => $this->website->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

### Database Design Guidelines

#### Migration Standards
```php
public function up(): void
{
    Schema::create('websites', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('url')->index();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->timestamps();
        
        // Composite indexes for common queries
        $table->unique(['user_id', 'url']);
        $table->index(['user_id', 'created_at']);
    });
}
```

#### Model Relationship Standards
```php
// Always include return types
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}

// Use descriptive method names for complex relationships
public function activeSslCertificate(): HasOne
{
    return $this->hasOne(SslCertificate::class)
        ->where('is_valid', true)
        ->latest('created_at');
}
```

## 🔒 Security Guidelines

### Input Validation
```php
// Always validate user input
class StoreWebsiteRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'url' => [
                'required',
                'url',
                'max:255',
                Rule::unique('websites')->where('user_id', auth()->id()),
            ],
        ];
    }
}
```

### Authorization
```php
// Use policies for authorization
class WebsitePolicy  
{
    public function view(User $user, Website $website): bool
    {
        return $user->id === $website->user_id;
    }
    
    public function update(User $user, Website $website): bool
    {
        return $user->id === $website->user_id;
    }
}

// Apply authorization in components
class WebsiteDetails extends Component
{
    public function mount(Website $website): void
    {
        $this->authorize('view', $website);
        $this->website = $website;
    }
}
```

### Data Protection
```php
// Encrypt sensitive data
class EmailSettings extends Model
{
    public function setPasswordAttribute(?string $value): void
    {
        $this->attributes['password'] = $value ? 
            Crypt::encryptString($value) : null;
    }
    
    public function getPasswordAttribute(?string $value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }
}
```

## 📦 Adding New Features

### 1. Plan the Feature

```bash
# Create GitHub issue with:
# - Feature description
# - Acceptance criteria  
# - Technical considerations
# - Test scenarios
```

### 2. Design Tests First

```php
// Plan comprehensive test scenarios
describe('Website Groups Feature', function () {
    test('user can create website group', function () {
        // Test creation
    });
    
    test('user can add websites to group', function () {
        // Test assignment
    });
    
    test('user can only see their own groups', function () {
        // Test isolation
    });
    
    test('deleting group does not delete websites', function () {
        // Test data integrity
    });
});
```

### 3. Implement Incrementally

```bash
# Step 1: Model and migration
./vendor/bin/sail artisan make:model WebsiteGroup -m

# Step 2: Basic Livewire component
./vendor/bin/sail artisan make:livewire WebsiteGroups

# Step 3: Add to existing components
# Update WebsiteManagement to support groups

# Step 4: Routes and navigation
# Add to routes/web.php
```

### 4. Document the Feature

```markdown
# Update documentation
docs/user-guide/website-management.md    # User-facing docs
docs/developer-guide/api-reference.md    # Technical docs  
docs/README.md                           # Feature summary
```

## 🚀 Deployment Contributions

### Environment Configuration

```bash
# Test deployment configuration
cp .env.example .env.production

# Verify production settings
php artisan config:show --env=production
```

### Documentation Updates

```bash
# Update deployment docs when adding:
# - New environment variables
# - New dependencies
# - New services or queues
# - New cron jobs
```

## 🐛 Bug Reporting and Fixing

### Bug Report Template

```markdown
## Bug Description
Clear description of the issue

## Steps to Reproduce
1. Go to X
2. Click Y
3. See error Z

## Expected Behavior
What should happen

## Actual Behavior  
What actually happens

## Environment
- PHP version: X.X
- Laravel version: X.X
- Browser: X

## Additional Context
Screenshots, logs, etc.
```

### Bug Fix Process

1. **Create issue** with detailed bug report
2. **Write failing test** that reproduces the bug
3. **Fix the bug** with minimal changes
4. **Ensure test passes** and no regressions
5. **Update documentation** if needed
6. **Create pull request** with test and fix

## 🔍 Code Review Guidelines

### As a Reviewer

- **Check test coverage** - All new code should have tests
- **Verify TDD approach** - Tests should have been written first
- **Review security** - Check for input validation and authorization
- **Performance considerations** - Look for N+1 queries, memory usage
- **Code style** - Ensure Laravel Pint standards are followed
- **Documentation** - Verify documentation is updated

### As an Author

- **Self-review first** - Review your own PR before requesting review
- **Provide context** - Explain complex changes in PR description
- **Include screenshots** - For UI changes, include before/after
- **Test thoroughly** - Ensure all test cases pass
- **Update documentation** - Include relevant documentation updates

## 🎯 Performance Guidelines

### Database Optimization

```php
// Use eager loading to prevent N+1 queries
$websites = Website::with(['sslCertificates', 'sslChecks'])
    ->where('user_id', auth()->id())
    ->get();

// Use database-level aggregation  
$stats = Website::selectRaw('
    COUNT(*) as total,
    SUM(CASE WHEN ssl_status = "valid" THEN 1 ELSE 0 END) as valid,
    SUM(CASE WHEN ssl_status = "expiring" THEN 1 ELSE 0 END) as expiring
')->where('user_id', auth()->id())->first();
```

### Caching Strategy

```php
// Cache expensive operations
public function getDashboardStats(): array
{
    return Cache::remember(
        "dashboard_stats_{$this->user->id}",
        now()->addMinutes(5),
        fn() => $this->calculateDashboardStats()
    );
}
```

### Queue Optimization

```php
// Batch jobs for efficiency
Bus::batch([
    new CheckSslCertificateJob($website1),
    new CheckSslCertificateJob($website2),
    new CheckSslCertificateJob($website3),
])->dispatch();
```

## 🎯 Next Steps

After setting up your development environment:

1. **Read the codebase** - Start with models, then services, then components
2. **Run the test suite** - Understand the existing test coverage
3. **Pick a good first issue** - Look for issues labeled "good first issue"
4. **Join discussions** - Participate in issue discussions and pull requests
5. **Ask questions** - Don't hesitate to ask for clarification

## 📚 Additional Resources

- **[Architecture Guide](architecture.md)** - Understanding the system design
- **[Testing Guide](testing.md)** - Comprehensive testing practices
- **[API Reference](api-reference.md)** - Complete code documentation
- **Laravel Documentation** - https://laravel.com/docs
- **Livewire Documentation** - https://laravel-livewire.com/docs
- **Pest Documentation** - https://pestphp.com/docs

---

**Previous**: [Testing Guide](testing.md) | **Main Documentation**: [../README.md](../README.md)