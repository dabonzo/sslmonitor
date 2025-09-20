# Development Workflow for SSL Monitor v3

## 🎯 Overview

This document provides a comprehensive, step-by-step guide for developing SSL Monitor v3 features using Test-Driven Development (TDD), VRISTO template integration, and professional development practices.

**Core Principles:**
- **Documentation-First**: Plan before coding
- **UI-First**: Perfect the interface before backend integration
- **TDD Methodology**: Write tests first, implement second
- **Quality Focus**: Comprehensive testing and code review

---

## 🔄 Complete Development Cycle

### Phase 0: Laravel Boost Context & Discovery (MANDATORY FIRST STEP)

#### 0.1 Application Context Discovery
```bash
# ALWAYS start with Laravel Boost to understand the current application state
1. application-info                    # Get comprehensive app context
2. list-routes                        # Understand existing API structure
3. database-schema                    # Review current database structure
4. list-artisan-commands              # See available commands for feature
```

#### 0.2 Documentation Research (Four-MCP Strategy)
```bash
# 1. Laravel Boost: Search Laravel ecosystem documentation
search-docs ["feature-specific", "technology-stack"]

# Examples for SSL Monitor v3 Laravel features:
search-docs ["ssl certificate", "laravel jobs", "queue workers"]    # SSL monitoring
search-docs ["vue inertia", "component patterns", "forms"]          # UI development
search-docs ["pest testing", "browser testing", "mocking"]          # Testing
search-docs ["tailwind components", "responsive design"]            # Styling
search-docs ["notification mail", "email queues"]                   # Notifications

# 2. Context7: Get non-Laravel technology documentation
use context7: "VRISTO admin template integration patterns"          # Template-specific
use context7: "Vue 3 composition API TypeScript best practices"     # Frontend
use context7: "Playwright browser testing SSL certificates"         # Testing
use context7: "WebSocket real-time notifications implementation"    # Real-time features
use context7: "JavaScript SSL certificate validation browser APIs"  # Browser APIs

# 3. Filesystem MCP: Analyze existing project structure
filesystem-mcp: list-directory resources/
filesystem-mcp: read-file .env.example
filesystem-mcp: analyze-directory-structure app/

# 4. Git MCP: Understand development history and current state
git-mcp: status
git-mcp: log --oneline --graph -10
git-mcp: list-branches
git-mcp: show-current-branch
```

#### 0.3 Codebase Understanding
```bash
# Use Laravel Boost to understand existing patterns
tinker
# Test existing models and relationships
# Example: Website::with('certificates')->first()

list-available-config-keys            # Understand configuration options
get-config database.default          # Check database configuration
```

### Phase 1: Planning & Documentation (After Context Discovery)

#### 1.1 Feature Analysis
```bash
# After understanding current state with Laravel Boost, plan the feature
1. Read feature specification in PROJECT_PLAN.md
2. Understand user stories and acceptance criteria
3. Identify required VRISTO components and layouts
4. Plan database schema changes (use database-schema results)
5. Design API endpoints and data flow (reference list-routes output)
```

#### 1.2 Technical Design
```bash
# Create technical specification
1. Define Vue components needed
2. Plan Inertia.js page structure
3. Identify backend services and models
4. Design test scenarios (happy path, edge cases, errors)
5. Document component props and API contracts
```

#### 1.3 Git Branch Setup (Enhanced with Git MCP)
```bash
# Follow Git Flow workflow with Git MCP integration
git-mcp: switch-branch develop
git-mcp: pull origin develop
git-mcp: create-branch feature/descriptive-feature-name develop
git-mcp: push origin feature/descriptive-feature-name --set-upstream

# Alternative: Traditional Git commands (if Git MCP not available)
git checkout develop
git pull origin develop
git checkout -b feature/descriptive-feature-name
git push -u origin feature/descriptive-feature-name
```

### Phase 2: Test-First Development (TDD Red-Green-Refactor)

#### 2.1 Write Failing Tests (RED)
```bash
# Start with failing tests
./vendor/bin/sail artisan test --filter=FeatureName
# Tests should fail initially (RED)

# Example test structure for SSL monitoring feature:
tests/
├── Feature/
│   ├── SslMonitoring/
│   │   ├── WebsiteManagementTest.php     # CRUD operations
│   │   ├── SslCertificateCheckTest.php   # SSL validation
│   │   └── SslAlertNotificationTest.php  # Email notifications
│   └── Browser/
│       ├── WebsiteManagementFlowTest.php # Full user workflow
│       └── SslDashboardTest.php          # Dashboard interactions
└── Unit/
    ├── Services/
    │   └── SslCertificateCheckerTest.php # Service layer testing
    └── Models/
        └── WebsiteTest.php               # Model behavior testing
```

#### 2.2 Test Examples

##### Unit Test Example
```php
<?php
// tests/Unit/Services/SslCertificateCheckerTest.php

use App\Services\SslCertificateChecker;
use App\Models\Website;

test('ssl certificate checker validates valid certificate', function () {
    $checker = app(SslCertificateChecker::class);
    $website = Website::factory()->create(['url' => 'https://google.com']);

    $result = $checker->checkCertificate($website);

    expect($result->isValid())->toBeTrue()
        ->and($result->getDaysUntilExpiry())->toBeGreaterThan(0)
        ->and($result->getIssuer())->not->toBeEmpty();
});

test('ssl certificate checker handles invalid domain', function () {
    $checker = app(SslCertificateChecker::class);
    $website = Website::factory()->create(['url' => 'https://invalid-domain-12345.com']);

    $result = $checker->checkCertificate($website);

    expect($result->isValid())->toBeFalse()
        ->and($result->getError())->toContain('domain not found');
});
```

##### Feature Test Example
```php
<?php
// tests/Feature/SslMonitoring/WebsiteManagementTest.php

use App\Models\User;
use App\Models\Website;

test('user can create website with ssl preview', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post('/websites', [
            'name' => 'Test Website',
            'url' => 'https://example.com',
        ]);

    $response->assertRedirect(route('websites.index'))
        ->assertSessionHas('success');

    expect(Website::where('url', 'https://example.com')->exists())->toBeTrue();
});

test('website creation validates url format', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post('/websites', [
            'name' => 'Test Website',
            'url' => 'invalid-url',
        ]);

    $response->assertSessionHasErrors(['url']);
    expect(Website::count())->toBe(0);
});
```

##### Browser Test Example
```php
<?php
// tests/Browser/WebsiteManagementFlowTest.php

use App\Models\User;

test('user can add website through complete ui flow', function () {
    $user = User::factory()->create();

    $page = visit('/login')
        ->fill('email', $user->email)
        ->fill('password', 'password')
        ->click('Log in')
        ->assertUrlIs('/dashboard')
        ->click('Websites')
        ->assertSee('Website Management')
        ->click('Add Website')
        ->assertSee('Add New Website')
        ->fill('name', 'My Test Site')
        ->fill('url', 'https://example.com')
        ->screenshot('website-form-filled')
        ->click('Preview SSL')
        ->waitForText('SSL Certificate Preview')
        ->assertSee('Valid Certificate')
        ->screenshot('ssl-preview-loaded')
        ->click('Add Website')
        ->assertSee('Website added successfully')
        ->assertSee('My Test Site');

    // Take final screenshot
    $page->screenshot('website-added-success');
});
```

#### 2.3 Implement Minimal Code (GREEN)
```bash
# Write minimal code to make tests pass
1. Create models, migrations, factories
2. Implement service classes
3. Create controllers and routes
4. Build Vue components
5. Run tests until they pass (GREEN)

./vendor/bin/sail artisan test --filter=FeatureName
# All tests should now pass (GREEN)
```

#### 2.3.1 Debugging Test Failures (Laravel Boost Integration)
```bash
# When tests fail, use Laravel Boost for debugging
1. last-error                        # Get details of most recent application error
2. read-log-entries --entries=20     # Read recent application logs
3. tinker                           # Test problematic code in isolation
   # Example: Test SSL certificate parsing
   # $website = Website::first(); $cert = \Spatie\SslCertificate\SslCertificate::createForHostName($website->domain)

4. browser-logs --entries=10        # Check browser console for frontend issues
5. database-query                   # Verify database state
   # Example: SELECT * FROM websites WHERE created_at > NOW() - INTERVAL 1 HOUR

# Debug specific feature areas:
# SSL Monitoring debugging:
tinker
# \App\Jobs\CheckSslCertificate::dispatch(Website::first())

# Vue/Inertia debugging:
browser-logs --entries=20
get-absolute-url --path="/dashboard"

# VRISTO template debugging (Context7):
use context7: "Vue.js debugging reactive data issues"
use context7: "Alpine.js Vue component integration troubleshooting"

# Database relationship debugging:
tinker
# Website::with(['certificates', 'uptimeChecks'])->first()
```

#### 2.4 Refactor & Improve (REFACTOR)
```bash
# Improve code while keeping tests green
1. Extract reusable components
2. Optimize database queries
3. Improve error handling
4. Enhance user experience
5. Add code documentation

./vendor/bin/sail artisan test --filter=FeatureName
# Tests should still pass after refactoring
```

### Phase 3: VRISTO UI Implementation

#### 3.1 Component Structure Planning
```bash
# Plan Vue component hierarchy for feature
Pages/
├── Websites/
│   ├── Index.vue           # Website listing with VRISTO table
│   ├── Create.vue          # Add website form
│   ├── Show.vue            # Website details
│   └── Edit.vue            # Edit website
└── Components/
    ├── Websites/
    │   ├── WebsiteTable.vue      # Data table component
    │   ├── WebsiteModal.vue      # Add/edit modal
    │   ├── SslPreview.vue        # SSL certificate preview
    │   └── StatusBadge.vue       # SSL status indicator
    └── Common/
        ├── DataTable.vue         # Reusable VRISTO table
        ├── Modal.vue             # VRISTO modal wrapper
        └── LoadingSpinner.vue    # Loading states
```

#### 3.2 VRISTO Component Implementation

##### Main Page Component
```vue
<!-- resources/js/Pages/Websites/Index.vue -->
<template>
    <VristoLayout>
        <Head title="Website Management" />

        <!-- Page Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Website Management
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">
                    Manage your SSL certificates and uptime monitoring
                </p>
            </div>
            <VristoButton
                variant="primary"
                @click="showAddModal = true"
            >
                <PlusIcon class="w-4 h-4 mr-2" />
                Add Website
            </VristoButton>
        </div>

        <!-- Websites Table -->
        <div class="panel">
            <WebsiteTable
                :websites="websites"
                @edit="editWebsite"
                @delete="deleteWebsite"
                @check-ssl="checkSsl"
            />
        </div>

        <!-- Add Website Modal -->
        <WebsiteModal
            v-model:show="showAddModal"
            title="Add New Website"
            @saved="handleWebsiteAdded"
        />
    </VristoLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import VristoLayout from '@/Layouts/VristoLayout.vue';
import VristoButton from '@/Components/Vristo/VristoButton.vue';
import WebsiteTable from '@/Components/Websites/WebsiteTable.vue';
import WebsiteModal from '@/Components/Websites/WebsiteModal.vue';
import { PlusIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    websites: Array,
});

const showAddModal = ref(false);

const handleWebsiteAdded = () => {
    showAddModal.value = false;
    // Refresh page data
    router.reload({ only: ['websites'] });
};

const editWebsite = (website) => {
    // Implementation for edit
};

const deleteWebsite = (website) => {
    // Implementation for delete
};

const checkSsl = (website) => {
    // Implementation for manual SSL check
};
</script>
```

##### VRISTO Data Table Component
```vue
<!-- resources/js/Components/Websites/WebsiteTable.vue -->
<template>
    <div class="table-responsive">
        <table class="table-hover">
            <thead>
                <tr>
                    <th class="!text-center">
                        <input
                            type="checkbox"
                            v-model="selectAll"
                            @change="toggleSelectAll"
                            class="form-checkbox"
                        />
                    </th>
                    <th>Website</th>
                    <th>SSL Status</th>
                    <th>Expiry Date</th>
                    <th>Uptime</th>
                    <th>Last Check</th>
                    <th class="!text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="website in websites" :key="website.id">
                    <td class="!text-center">
                        <input
                            type="checkbox"
                            v-model="selectedWebsites"
                            :value="website.id"
                            class="form-checkbox"
                        />
                    </td>
                    <td>
                        <div class="flex items-center">
                            <img
                                :src="`https://www.google.com/s2/favicons?domain=${website.url}`"
                                :alt="website.name"
                                class="w-6 h-6 rounded mr-3"
                                @error="$event.target.style.display = 'none'"
                            />
                            <div>
                                <div class="font-semibold">{{ website.name }}</div>
                                <div class="text-xs text-gray-500">{{ website.url }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <SslStatusBadge :status="website.ssl_status" :days-until-expiry="website.days_until_expiry" />
                    </td>
                    <td>
                        <span v-if="website.ssl_expiry_date" class="text-sm">
                            {{ formatDate(website.ssl_expiry_date) }}
                        </span>
                        <span v-else class="text-gray-400 text-sm">Unknown</span>
                    </td>
                    <td>
                        <UptimeIndicator :uptime-percentage="website.uptime_percentage" />
                    </td>
                    <td>
                        <span v-if="website.last_checked_at" class="text-sm">
                            {{ formatRelativeTime(website.last_checked_at) }}
                        </span>
                        <span v-else class="text-gray-400 text-sm">Never</span>
                    </td>
                    <td class="!text-center">
                        <div class="flex items-center justify-center space-x-2">
                            <VristoButton
                                variant="outline-primary"
                                size="sm"
                                @click="$emit('check-ssl', website)"
                            >
                                Check Now
                            </VristoButton>
                            <Dropdown>
                                <template #trigger>
                                    <VristoButton variant="outline" size="sm">
                                        <EllipsisVerticalIcon class="w-4 h-4" />
                                    </VristoButton>
                                </template>
                                <template #content>
                                    <DropdownItem @click="$emit('edit', website)">
                                        Edit
                                    </DropdownItem>
                                    <DropdownItem @click="viewDetails(website)">
                                        View Details
                                    </DropdownItem>
                                    <DropdownItem
                                        @click="$emit('delete', website)"
                                        class="text-red-600"
                                    >
                                        Delete
                                    </DropdownItem>
                                </template>
                            </Dropdown>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Bulk Actions Bar -->
    <div v-if="selectedWebsites.length > 0" class="bg-gray-50 dark:bg-gray-800 p-4 rounded-b-lg border-t">
        <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600 dark:text-gray-400">
                {{ selectedWebsites.length }} websites selected
            </span>
            <div class="flex space-x-2">
                <VristoButton variant="outline" size="sm" @click="bulkCheckSsl">
                    Check SSL
                </VristoButton>
                <VristoButton variant="outline-danger" size="sm" @click="bulkDelete">
                    Delete Selected
                </VristoButton>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import SslStatusBadge from './SslStatusBadge.vue';
import UptimeIndicator from './UptimeIndicator.vue';
import VristoButton from '@/Components/Vristo/VristoButton.vue';
import Dropdown from '@/Components/Vristo/Dropdown.vue';
import DropdownItem from '@/Components/Vristo/DropdownItem.vue';
import { EllipsisVerticalIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    websites: Array,
});

const emit = defineEmits(['edit', 'delete', 'check-ssl']);

const selectedWebsites = ref([]);
const selectAll = computed({
    get: () => selectedWebsites.value.length === props.websites.length,
    set: (value) => {
        selectedWebsites.value = value ? props.websites.map(w => w.id) : [];
    }
});

const toggleSelectAll = () => {
    if (selectAll.value) {
        selectedWebsites.value = props.websites.map(w => w.id);
    } else {
        selectedWebsites.value = [];
    }
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};

const formatRelativeTime = (date) => {
    const now = new Date();
    const checkDate = new Date(date);
    const diffInSeconds = Math.floor((now - checkDate) / 1000);

    if (diffInSeconds < 60) return 'Just now';
    if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
    if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
    return `${Math.floor(diffInSeconds / 86400)}d ago`;
};

const viewDetails = (website) => {
    router.visit(route('websites.show', website.id));
};

const bulkCheckSsl = () => {
    router.post(route('websites.bulk-check'), {
        website_ids: selectedWebsites.value
    });
};

const bulkDelete = () => {
    if (confirm(`Are you sure you want to delete ${selectedWebsites.value.length} websites?`)) {
        router.delete(route('websites.bulk-delete'), {
            data: { website_ids: selectedWebsites.value }
        });
    }
};
</script>
```

#### 3.3 Inertia.js Integration

##### Controller Implementation
```php
<?php
// app/Http/Controllers/WebsiteController.php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Http\Requests\StoreWebsiteRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WebsiteController extends Controller
{
    public function index(): Response
    {
        $websites = auth()->user()
            ->websites()
            ->with(['latestSslCheck', 'latestUptimeCheck'])
            ->latest()
            ->get()
            ->map(function ($website) {
                return [
                    'id' => $website->id,
                    'name' => $website->name,
                    'url' => $website->url,
                    'ssl_status' => $website->calculateSslStatus(),
                    'ssl_expiry_date' => $website->ssl_expiry_date,
                    'days_until_expiry' => $website->getDaysUntilExpiry(),
                    'uptime_percentage' => $website->calculateUptimePercentage(),
                    'last_checked_at' => $website->last_checked_at,
                    'created_at' => $website->created_at,
                    'updated_at' => $website->updated_at,
                ];
            });

        return Inertia::render('Websites/Index', [
            'websites' => $websites,
        ]);
    }

    public function store(StoreWebsiteRequest $request)
    {
        $website = auth()->user()->websites()->create($request->validated());

        // Dispatch SSL check job
        \App\Jobs\CheckSslCertificateJob::dispatch($website);

        return redirect()->route('websites.index')
            ->with('success', 'Website added successfully. SSL check is in progress.');
    }

    public function show(Website $website): Response
    {
        $this->authorize('view', $website);

        return Inertia::render('Websites/Show', [
            'website' => [
                'id' => $website->id,
                'name' => $website->name,
                'url' => $website->url,
                'ssl_status' => $website->calculateSslStatus(),
                'ssl_certificate' => $website->latestSslCertificate,
                'ssl_checks' => $website->sslChecks()->latest()->limit(10)->get(),
                'uptime_checks' => $website->uptimeChecks()->latest()->limit(10)->get(),
                'uptime_percentage' => $website->calculateUptimePercentage(),
                'created_at' => $website->created_at,
                'updated_at' => $website->updated_at,
            ],
        ]);
    }
}
```

### Phase 4: Backend Service Implementation

#### 4.1 Service Layer Pattern
```php
<?php
// app/Services/SslCertificateChecker.php

namespace App\Services;

use App\Models\Website;
use App\Models\SslCertificate;
use App\Models\SslCheck;
use Spatie\SslCertificate\SslCertificate as SpatieCertificate;
use Spatie\SslCertificate\Exceptions\CouldNotDownloadCertificate;

class SslCertificateChecker
{
    public function checkCertificate(Website $website): SslCheckResult
    {
        try {
            $certificate = SpatieCertificate::createForHostName($website->url);

            $sslCertificate = $this->storeCertificate($website, $certificate);
            $checkResult = $this->performCheck($website, $certificate);

            return $checkResult;
        } catch (CouldNotDownloadCertificate $e) {
            return $this->handleError($website, $e->getMessage());
        }
    }

    private function storeCertificate(Website $website, SpatieCertificate $certificate): SslCertificate
    {
        return $website->sslCertificates()->updateOrCreate(
            ['fingerprint' => $certificate->getFingerprint()],
            [
                'domain' => $certificate->getDomain(),
                'issuer' => $certificate->getIssuer(),
                'valid_from' => $certificate->validFromDate(),
                'valid_until' => $certificate->expirationDate(),
                'is_valid' => $certificate->isValid(),
                'certificate_data' => $certificate->getRawCertificateFields(),
            ]
        );
    }

    private function performCheck(Website $website, SpatieCertificate $certificate): SslCheckResult
    {
        $status = $this->calculateStatus($certificate);

        $sslCheck = $website->sslChecks()->create([
            'status' => $status,
            'expires_at' => $certificate->expirationDate(),
            'days_until_expiry' => $certificate->daysUntilExpirationDate(),
            'issuer' => $certificate->getIssuer(),
            'checked_at' => now(),
        ]);

        return new SslCheckResult(
            status: $status,
            isValid: $certificate->isValid(),
            daysUntilExpiry: $certificate->daysUntilExpirationDate(),
            issuer: $certificate->getIssuer(),
            sslCheck: $sslCheck
        );
    }

    private function calculateStatus(SpatieCertificate $certificate): string
    {
        if (!$certificate->isValid()) {
            return 'invalid';
        }

        if ($certificate->isExpired()) {
            return 'expired';
        }

        $daysUntilExpiry = $certificate->daysUntilExpirationDate();

        if ($daysUntilExpiry <= 7) {
            return 'expiring_soon';
        }

        if ($daysUntilExpiry <= 14) {
            return 'expiring_warning';
        }

        return 'valid';
    }

    private function handleError(Website $website, string $error): SslCheckResult
    {
        $sslCheck = $website->sslChecks()->create([
            'status' => 'error',
            'error_message' => $error,
            'checked_at' => now(),
        ]);

        return new SslCheckResult(
            status: 'error',
            isValid: false,
            error: $error,
            sslCheck: $sslCheck
        );
    }
}

// Value object for SSL check results
class SslCheckResult
{
    public function __construct(
        public string $status,
        public bool $isValid,
        public ?int $daysUntilExpiry = null,
        public ?string $issuer = null,
        public ?string $error = null,
        public ?SslCheck $sslCheck = null
    ) {}

    public function isExpiringSoon(): bool
    {
        return $this->daysUntilExpiry !== null && $this->daysUntilExpiry <= 14;
    }

    public function hasError(): bool
    {
        return $this->error !== null;
    }
}
```

#### 4.2 Background Jobs
```php
<?php
// app/Jobs/CheckSslCertificateJob.php

namespace App\Jobs;

use App\Models\Website;
use App\Services\SslCertificateChecker;
use App\Notifications\SslCertificateExpiringNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckSslCertificateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(
        public Website $website
    ) {}

    public function handle(SslCertificateChecker $checker): void
    {
        Log::info("Checking SSL certificate for website: {$this->website->url}");

        $result = $checker->checkCertificate($this->website);

        // Update website last check timestamp
        $this->website->update([
            'last_ssl_check_at' => now(),
            'ssl_status' => $result->status,
        ]);

        // Send notification if certificate is expiring
        if ($result->isExpiringSoon()) {
            $this->website->user->notify(
                new SslCertificateExpiringNotification($this->website, $result)
            );
        }

        Log::info("SSL certificate check completed for: {$this->website->url}, Status: {$result->status}");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("SSL certificate check failed for: {$this->website->url}", [
            'error' => $exception->getMessage(),
            'website_id' => $this->website->id,
        ]);

        $this->website->update([
            'last_ssl_check_at' => now(),
            'ssl_status' => 'error',
        ]);
    }
}
```

### Phase 5: Testing & Quality Assurance

#### 5.1 Run Test Suite
```bash
# Run all tests
./vendor/bin/sail artisan test

# Run specific test categories
./vendor/bin/sail artisan test --filter=SslMonitoring
./vendor/bin/sail artisan test tests/Feature/
./vendor/bin/sail artisan test tests/Unit/
./vendor/bin/sail artisan test tests/Browser/

# Run with coverage
./vendor/bin/sail artisan test --coverage

# Run browser tests with screenshots
./vendor/bin/sail artisan test tests/Browser/ --browser-screenshots
```

#### 5.2 Code Quality Checks
```bash
# Format code with Laravel Pint
./vendor/bin/sail exec laravel.test ./vendor/bin/pint

# Run static analysis (if using PHPStan)
./vendor/bin/sail exec laravel.test ./vendor/bin/phpstan analyse

# Check for security vulnerabilities
./vendor/bin/sail composer audit
```

#### 5.3 Performance Testing
```bash
# Test application performance
./vendor/bin/sail artisan test --filter=Performance

# Check database query efficiency
# Enable query logging in tests
# Verify N+1 query prevention
```

### Phase 6: Documentation & Review

#### 6.1 Update Documentation
```bash
# Update relevant documentation files
1. Add feature to PROJECT_PLAN.md completion status
2. Update API_SPECIFICATION.md with new endpoints
3. Add UI components to UI_SPECIFICATIONS.md
4. Update test coverage statistics
```

#### 6.2 Create Pull Request
```bash
# Prepare for pull request
git add .
git commit -m "Implement SSL monitoring with VRISTO UI integration

- Add WebsiteController with full CRUD operations
- Implement SslCertificateChecker service with validation
- Create VRISTO-styled website management interface
- Add comprehensive test suite with browser testing
- Include background job for automated SSL checking

Closes #123"

git push origin feature/ssl-monitoring

# Create PR via GitHub/GitLab interface
# Title: "Implement SSL Monitoring with VRISTO UI"
# Include screenshots of UI components
# List test coverage improvements
# Note any breaking changes or migrations needed
```

---

## 🧪 Testing Best Practices

### Test Categories
1. **Unit Tests** (40%): Service classes, models, value objects
2. **Feature Tests** (35%): HTTP endpoints, form submissions, database operations
3. **Browser Tests** (25%): User workflows, UI interactions, visual validation

### Browser Testing with Screenshots
```php
test('ssl monitoring dashboard displays correctly', function () {
    $user = User::factory()->create();
    $websites = Website::factory()->count(5)->create(['user_id' => $user->id]);

    $page = visit('/login')
        ->fill('email', $user->email)
        ->fill('password', 'password')
        ->click('Log in')
        ->click('Websites')
        ->screenshot('websites-index-page');

    $page->assertSee('Website Management')
        ->assertSee('Add Website')
        ->screenshot('websites-management-loaded');

    // Test responsive design
    $page->resize(375, 667) // iPhone dimensions
        ->screenshot('websites-mobile-view');
});
```

---

## 📊 Quality Gates

### Before Merge Checklist
- [ ] All tests passing (unit, feature, browser)
- [ ] Code coverage >90%
- [ ] Laravel Pint formatting applied
- [ ] No N+1 query issues
- [ ] Browser tests include visual validation
- [ ] Documentation updated
- [ ] Security vulnerabilities checked
- [ ] Performance benchmarks met

### Definition of Done
- [ ] Feature fully implemented according to specifications
- [ ] Comprehensive test coverage with multiple test types
- [ ] VRISTO UI components properly integrated
- [ ] Responsive design tested across devices
- [ ] Error handling and edge cases covered
- [ ] Documentation updated and reviewed
- [ ] Code review completed and approved
- [ ] Performance requirements met

This development workflow ensures consistent, high-quality code delivery while maintaining the professional standards expected for SSL Monitor v3.