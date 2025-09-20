# SSL Monitor Architecture

Comprehensive overview of the SSL Monitor application architecture, technology stack, and design decisions.

## 🏗️ Technology Stack

### Core Framework
- **Laravel 12.x** - Modern PHP framework with latest features
- **PHP 8.2+** - Latest PHP with performance improvements and type safety
- **MySQL 8.0+ / MariaDB 10.3+** - Relational database for data persistence
- **Redis** - Caching and queue management

### Frontend Stack
- **Livewire 3** - Full-stack reactive components
- **Alpine.js** - Minimal JavaScript framework (included with Livewire)
- **Tailwind CSS v4** - Utility-first CSS framework
- **Flux UI Free** - Professional UI component library
- **Vite** - Modern frontend build tool

### Development & Testing
- **Pest PHP 4** - Modern PHP testing framework
- **Laravel Pint** - Code formatting and style enforcement
- **Laravel Sail** - Docker development environment
- **Composer** - PHP dependency management
- **npm** - JavaScript package management

### Infrastructure & Deployment
- **Laravel Queue** - Background job processing
- **Laravel Scheduler** - Task scheduling system
- **Nginx/Apache** - Web server options
- **Let's Encrypt** - SSL certificate management
- **Systemd** - Service management for queue workers

## 🎯 Architectural Principles

### Design Patterns
- **Service Layer Pattern** - Business logic separation
- **Repository Pattern** - Data access abstraction
- **Job Queue Pattern** - Asynchronous processing
- **Observer Pattern** - Event-driven architecture
- **Factory Pattern** - Object creation for testing

### SOLID Principles
- **Single Responsibility** - Each class has one purpose
- **Open/Closed** - Open for extension, closed for modification
- **Dependency Injection** - Laravel's IoC container throughout
- **Interface Segregation** - Focused service contracts
- **Dependency Inversion** - Depend on abstractions, not concretions

## 📊 Application Architecture

### Layer Architecture

```
┌─────────────────────────────────────────────────┐
│                 Presentation Layer              │
│  ┌─────────────┐  ┌──────────────┐  ┌─────────┐ │
│  │   Blade     │  │   Livewire   │  │  Flux   │ │
│  │ Templates   │  │ Components   │  │   UI    │ │
│  └─────────────┘  └──────────────┘  └─────────┘ │
└─────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────┐
│                Application Layer                │
│  ┌─────────────┐  ┌──────────────┐  ┌─────────┐ │
│  │ Controllers │  │    Routes    │  │  Middleware │
│  │             │  │              │  │         │ │
│  └─────────────┘  └──────────────┘  └─────────┘ │
└─────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────┐
│                 Business Layer                  │
│  ┌─────────────┐  ┌──────────────┐  ┌─────────┐ │
│  │   Services  │  │     Jobs     │  │  Events │ │
│  │             │  │              │  │         │ │
│  └─────────────┘  └──────────────┘  └─────────┘ │
└─────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────┐
│                  Domain Layer                   │
│  ┌─────────────┐  ┌──────────────┐  ┌─────────┐ │
│  │   Models    │  │  Policies    │  │  Rules  │ │
│  │             │  │              │  │         │ │
│  └─────────────┘  └──────────────┘  └─────────┘ │
└─────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────┐
│                Infrastructure Layer             │
│  ┌─────────────┐  ┌──────────────┐  ┌─────────┐ │
│  │  Database   │  │    Queue     │  │  Cache  │ │
│  │             │  │              │  │         │ │
│  └─────────────┘  └──────────────┘  └─────────┘ │
└─────────────────────────────────────────────────┘
```

## 🗄️ Domain Model Design

### Core Entities

```php
User (1) ──────────┐
                   │
                   ▼ hasMany
                Website (n) ──────┐
                   │               │
                   ▼ hasMany       ▼ hasMany
             SslCertificate (n)  SslCheck (n)
                   │
                   ▼ belongsTo
                Website
```

### Entity Relationships

#### User Model
```php
class User extends Authenticatable
{
    // Relationships
    public function websites(): HasMany;
    
    // No direct SSL certificate access (through websites)
}
```

#### Website Model  
```php
class Website extends Model
{
    // Relationships
    public function user(): BelongsTo;
    public function sslCertificates(): HasMany;
    public function sslChecks(): HasMany;
    
    // Business Logic
    public function getCurrentSslCertificate(): ?SslCertificate;
    public function getLatestSslCheck(): ?SslCheck;
    
    // URL Sanitization
    protected function setUrlAttribute(string $value): void;
}
```

#### SslCertificate Model
```php
class SslCertificate extends Model  
{
    // Relationships
    public function website(): BelongsTo;
    
    // Business Logic
    public function isValid(): bool;
    public function isExpired(): bool;
    public function isExpiringSoon(int $days = 14): bool;
    public function getDaysUntilExpiry(): int;
    
    // Query Scopes
    public function scopeValid(Builder $query): void;
    public function scopeExpired(Builder $query): void;
    public function scopeExpiringSoon(Builder $query, int $days = 14): void;
}
```

#### SslCheck Model
```php
class SslCheck extends Model
{
    // Relationships  
    public function website(): BelongsTo;
    
    // Status Constants
    const STATUS_VALID = 'valid';
    const STATUS_EXPIRED = 'expired';
    const STATUS_EXPIRING_SOON = 'expiring_soon';
    const STATUS_INVALID = 'invalid';
    const STATUS_ERROR = 'error';
    
    // Query Scopes
    public function scopeValid(Builder $query): void;
    public function scopeFailed(Builder $query): void;
    public function scopeForWebsite(Builder $query, Website $website): void;
}
```

## 🔧 Service Layer Architecture

### SSL Certificate Services

#### SslCertificateChecker Service
```php
class SslCertificateChecker
{
    public function __construct(
        private Certificate $certificateClient
    ) {}
    
    public function checkCertificate(string $url): SslCertificateResult;
    public function validateCertificate(SslCertificate $certificate): bool;
    
    // Uses spatie/ssl-certificate package
    // Handles network failures and timeouts
    // Returns structured result objects
}
```

#### SslStatusCalculator Service  
```php
class SslStatusCalculator
{
    public function calculateStatus(
        ?SslCertificate $certificate, 
        ?SslCheck $latestCheck
    ): string;
    
    public function calculatePriority(string $status): int;
    public function getStatusColor(string $status): string;
    
    // Centralized status calculation logic
    // Priority-based status determination
    // Consistent status representation
}
```

### Email Configuration Services

#### EmailSettings Model
```php
class EmailSettings extends Model
{
    // Encrypted password handling
    public function setPasswordAttribute(?string $value): void;
    public function getPasswordAttribute(?string $value): ?string;
    
    // Laravel mail configuration generation
    public function toMailConfig(): array;
    
    // Email testing functionality  
    public function test(): bool;
    
    // Active settings management
    public static function active(): ?self;
}
```

#### EmailConfigurationProvider
```php  
class EmailConfigurationProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Runtime mail configuration override
        $this->configureMail();
    }
    
    protected function configureMail(): void
    {
        // Load active EmailSettings
        // Override Laravel's mail configuration
        // Graceful error handling
    }
}
```

## 🔄 Background Processing Architecture

### Job Queue System

```php
// Queue Configuration
QUEUE_CONNECTION=redis
QUEUE_DEFAULT=default  
SSL_MONITORING_QUEUE=ssl-monitoring
```

#### CheckSslCertificateJob
```php
class CheckSslCertificateJob implements ShouldQueue
{
    use Queueable, SerializesModels;
    
    public int $tries = 3;
    public int $timeout = 60;
    public string $queue = 'ssl-monitoring';
    
    public function handle(
        SslCertificateChecker $checker,
        SslStatusCalculator $calculator
    ): void;
    
    public function failed(Throwable $exception): void;
    
    // Idempotent design (safe to run multiple times)
    // Comprehensive error handling and logging
    // Integration with notification system
}
```

### Scheduling System

```php
// App/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('ssl:check-all')
             ->dailyAt('06:00')
             ->withoutOverlapping()
             ->runInBackground();
             
    // Additional maintenance tasks
    $schedule->command('queue:prune-batches --hours=48')
             ->daily();
}
```

## 🎨 Frontend Architecture

### Livewire Component Pattern

#### Website Management Component
```php
class WebsiteManagement extends Component
{
    // Component State
    public Collection $websites;
    public bool $showAddForm = false;
    public Website $editingWebsite;
    
    // Real-time Features
    #[On('website-added')]
    public function refreshWebsites(): void;
    
    // Form Handling
    public function addWebsite(): void;
    public function deleteWebsite(Website $website): void;
    
    // Authorization
    public function mount(): void
    {
        $this->authorize('viewAny', Website::class);
    }
}
```

### UI Component Standards

#### Flux UI Integration
- **Consistent component usage** across all interfaces
- **Accessibility compliance** built into components  
- **Dark mode support** throughout application
- **Responsive design** with mobile optimization

#### Alpine.js Enhancement
```html
<!-- Minimal JavaScript for enhanced UX -->
<div x-data="{ showPassword: false }">
    <input 
        :type="showPassword ? 'text' : 'password'" 
        wire:model="password"
    />
    <button @click="showPassword = !showPassword">
        <span x-show="!showPassword">Show</span>
        <span x-show="showPassword">Hide</span>
    </button>
</div>
```

## 🧪 Test-Driven Development Architecture  

### Testing Philosophy

**Red-Green-Refactor Cycle:**
1. **Red** - Write failing test first
2. **Green** - Write minimal code to pass  
3. **Refactor** - Improve code while maintaining tests

### Test Structure

```php
tests/
├── Feature/
│   ├── Models/           # Model behavior and relationships
│   ├── Services/         # Service layer functionality  
│   ├── Livewire/         # Component interactions
│   ├── Jobs/             # Background job processing
│   ├── Commands/         # Artisan command behavior
│   └── EmailSettingsTest.php  # Email configuration
└── Unit/
    ├── Helpers/          # Utility functions
    └── Rules/            # Validation rules
```

### Test Patterns

#### Model Testing Pattern
```php
test('website belongs to user', function () {
    $user = User::factory()->create();
    $website = Website::factory()->for($user)->create();
    
    expect($website->user)->toBeInstanceOf(User::class);
    expect($website->user->id)->toBe($user->id);
});

test('website url is automatically sanitized', function () {
    $website = Website::factory()->create(['url' => 'HTTP://Example.COM/Path/']);
    
    expect($website->url)->toBe('https://example.com/path');
});
```

#### Service Testing Pattern
```php
test('ssl certificate checker handles valid certificate', function () {
    $checker = app(SslCertificateChecker::class);
    
    $result = $checker->checkCertificate('https://google.com');
    
    expect($result->isValid())->toBeTrue();
    expect($result->getDaysUntilExpiry())->toBeGreaterThan(0);
});
```

#### Livewire Testing Pattern
```php
test('user can add website with ssl preview', function () {
    $user = User::factory()->create();
    
    Livewire::actingAs($user)
        ->test(WebsiteManagement::class)
        ->set('url', 'https://example.com')
        ->call('previewSsl')
        ->assertSet('previewResult.isValid', true)
        ->call('addWebsite')
        ->assertHasNoErrors();
        
    expect($user->websites()->count())->toBe(1);
});
```

## 🔐 Security Architecture

### Authentication & Authorization

```php
// Policy-based authorization
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

// Route protection
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/websites', WebsiteManagement::class)->name('websites');
    Route::get('/settings/email', EmailSettings::class)->name('settings.email');
});
```

### Data Security

#### Encrypted Email Passwords
```php
class EmailSettings extends Model
{
    public function setPasswordAttribute(?string $value): void
    {
        $this->attributes['password'] = $value ? Crypt::encryptString($value) : null;
    }
    
    public function getPasswordAttribute(?string $value): ?string  
    {
        return $value ? Crypt::decryptString($value) : null;
    }
}
```

#### CSRF Protection
- **All forms protected** with CSRF tokens
- **Livewire components** automatically include protection
- **API endpoints** (if added) use Sanctum

## 📈 Performance Architecture

### Caching Strategy

```php
// Configuration caching
php artisan config:cache
php artisan route:cache  
php artisan view:cache

// Redis caching
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### Database Optimization

#### Proper Indexing
```php
// Migration indexes
Schema::create('ssl_checks', function (Blueprint $table) {
    $table->index(['website_id', 'created_at']);
    $table->index(['status', 'created_at']);
    $table->index('created_at');
});
```

#### Query Optimization
```php
// Eager loading to prevent N+1 queries
$websites = Website::with(['sslCertificates', 'sslChecks'])
    ->where('user_id', auth()->id())
    ->get();
```

### Background Processing
- **Queue workers** handle SSL checks asynchronously
- **Job batching** for bulk operations
- **Failed job handling** with retry mechanisms

## 🔄 Development Workflow

### Laravel 12 Structure
```
app/
├── Http/
│   ├── Controllers/      # Minimal controllers (mostly Livewire)
│   ├── Middleware/       # Custom middleware
│   └── Requests/         # Form request validation
├── Livewire/            # Livewire components
│   ├── Actions/         # User actions (logout, etc.)
│   └── Settings/        # Settings components
├── Models/              # Eloquent models
├── Services/            # Business logic services
├── Jobs/                # Background jobs
├── Mail/                # Mailable classes
├── Policies/            # Authorization policies
└── Providers/           # Service providers

bootstrap/
├── app.php             # Application bootstrapping
└── providers.php       # Service provider registration

routes/
├── web.php             # Web routes
├── auth.php            # Authentication routes
└── console.php         # Console commands
```

### Code Quality Standards

#### Laravel Pint Configuration
```json
{
    "preset": "laravel",
    "rules": {
        "method_chaining_indentation": true,
        "no_unused_imports": true
    }
}
```

#### Pest Configuration  
```php
// tests/Pest.php
uses(Tests\TestCase::class)->in('Feature');
uses(Tests\TestCase::class)->in('Unit');

uses(Illuminate\Foundation\Testing\RefreshDatabase::class)->in('Feature');
```

## 🎯 Design Decisions

### Technology Choices

#### Why Laravel 12?
- **Latest features** and performance improvements
- **Streamlined structure** with minimal boilerplate
- **Built-in security** and best practices
- **Extensive ecosystem** with mature packages

#### Why Livewire over Vue/React?
- **Server-side rendering** with reactive components  
- **Reduced complexity** - no API layer needed
- **Laravel integration** - authentication, validation, etc.
- **Progressive enhancement** - works without JavaScript

#### Why Pest over PHPUnit?
- **Readable test syntax** closer to natural language
- **Modern features** like datasets and higher-order tests
- **Better developer experience** with focused assertions
- **Laravel integration** with helpful testing utilities

#### Why Redis for Queues?
- **Better performance** than database queues
- **Persistent storage** unlike memory-based solutions
- **Clustering support** for high availability  
- **Laravel optimization** for Redis-backed queues

### Architectural Decisions

#### Service Layer Pattern
- **Separation of concerns** - business logic outside controllers
- **Testability** - services can be unit tested in isolation
- **Reusability** - services used by both web and console commands
- **Dependency injection** - clean dependencies and mocking

#### Job Queue Architecture
- **Reliability** - jobs can be retried on failure
- **Scalability** - horizontal scaling with multiple workers
- **Monitoring** - failed jobs can be tracked and retried
- **Performance** - non-blocking SSL certificate checks

## 🔮 Future Architecture Considerations

### Scalability Improvements
- **Queue sharding** for high-volume SSL monitoring
- **Database read replicas** for dashboard queries
- **CDN integration** for static asset delivery
- **Load balancing** for multiple application servers

### Feature Extensions
- **API layer** for third-party integrations
- **Webhook system** for external notifications  
- **Multi-tenancy** for SaaS deployment
- **Advanced reporting** with data visualization

### Technology Evolution
- **Laravel Octane** for improved performance
- **Laravel Horizon** for advanced queue monitoring
- **Soketi** for real-time WebSocket updates
- **Telescope** for application debugging

## 🎯 Next Steps

- **[Testing Guide](testing.md)** - Running and writing tests
- **[API Reference](api-reference.md)** - Models, services, and jobs
- **[Contributing Guide](contributing.md)** - Development setup and guidelines

---

**Next**: [Testing Guide](testing.md) - Comprehensive testing documentation