# SSL Monitor v4 - Development Primer & Quick Start Guide

**Purpose**: This document provides essential information for Claude Code to understand the codebase structure, development flow, and where to start when adding new features or debugging.

**Last Updated**: October 12, 2025
**Test Suite Status**: 494 tests passing, 10 failing (98.0% success rate)

---

## 🎯 **Application Overview**

**SSL Monitor v4** is an enterprise SSL certificate and uptime monitoring platform built with Laravel 12 + Vue 3 + TypeScript + Inertia.js. It extends Spatie Laravel Uptime Monitor with custom features for team collaboration and advanced monitoring.

### Core Functionality
- **SSL Certificate Monitoring**: Automated daily checks with expiry alerts
- **Uptime Monitoring**: Real-time availability and response time tracking
- **Team Management**: Role-based collaboration (OWNER, ADMIN, VIEWER)
- **JavaScript Content Validation**: Dynamic content checking with BrowserShot
- **Advanced Notifications**: Toast system with Laravel flash message integration

### 🔍 **Before Starting: Ask Clarifying Questions**

**IMPORTANT**: Always ask clarifying questions before making assumptions about the task or codebase. This prevents wasted time and ensures accurate implementation.

#### **Available MCP Servers for Self-Verification**
**You have two powerful MCP servers available to check things yourself:**

1. **Laravel Boost MCP** - For all Laravel-related tasks:
   - `mcp__laravel-boost__application-info` - Get app overview and packages
   - `mcp__laravel-boost__database-schema` - Inspect database structure (**IMPORTANT**: Use `database: "mariadb"`)
   - `mcp__laravel-boost__list-routes` - See all available routes
   - `mcp__laravel-boost__list-artisan-commands` - Discover available commands
   - `mcp__laravel-boost__search-docs` - Get up-to-date Laravel documentation
   - `mcp__laravel-boost__tinker` - Test PHP code in context
   - `mcp__laravel-boost__last-error` - Check recent backend errors

2. **Playwright Extension MCP** - For browser automation and frontend verification:
   - `mcp__playwright-extension__browser_navigate` - Navigate to URLs
   - `mcp__playwright-extension__browser_snapshot` - See current page state
   - `mcp__playwright-extension__browser_console_messages` - Check for JavaScript errors
   - `mcp__playwright-extension__browser_click` - Test UI interactions
   - `mcp__playwright-extension__browser_take_screenshot` - Visual verification
   - `mcp__playwright-extension__browser_evaluate` - Run JavaScript in browser context

**How to Use MCP Servers for Verification:**
- **Backend Questions**: Use Laravel Boost to check models, routes, schema
- **Frontend Issues**: Use Playwright to navigate pages and check console errors
- **Database Questions**: Use Laravel Boost to inspect tables and relationships
- **Documentation**: Use Laravel Boost to get current Laravel version-specific docs

### 🚢 **CRITICAL: Always Use Sail Commands**

**IMPORTANT**: This application uses Laravel Sail as the development environment. **ALWAYS** use `./vendor/bin/sail` prefix for Laravel commands, never run them directly on the host system.

#### **Command Patterns**
```bash
# ❌ WRONG - Direct commands (won't work)
php artisan route:list
composer install
npm run dev

# ✅ CORRECT - Sail commands
./vendor/bin/sail artisan route:list
./vendor/bin/sail composer install
./vendor/bin/sail npm run dev
```

#### **Why Sail is Required**
- **Containerized Environment**: All PHP, Composer, npm, and Artisan commands run inside Docker containers
- **Consistent Dependencies**: Ensures all development operations use the same PHP version, extensions, and dependencies
- **Database Access**: MariaDB, Redis, and other services are only accessible through the Sail network
- **Proper Environment**: Environment variables and configuration are loaded correctly within containers

#### **Common Sail Commands**
```bash
# Laravel Artisan commands
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan tinker
./vendor/bin/sail artisan queue:work
./vendor/bin/sail artisan test

# Composer operations
./vendor/bin/sail composer install
./vendor/bin/sail composer update
./vendor/bin/sail composer require package-name

# NPM/Frontend operations
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
./vendor/bin/sail npm run build

# Container management
./vendor/bin/sail up -d          # Start containers
./vendor/bin/sail down           # Stop containers
./vendor/bin/sail ps             # Check container status
```

**Rule of thumb**: If it's a Laravel, Composer, or NPM command, it needs `./vendor/bin/sail` prefix.

---

#### **Questions to Ask:**
1. **Feature Requirements**:
   - "What specific functionality are you trying to add/fix?"
   - "What should the user experience be?"
   - "Are there any edge cases I should consider?"

2. **Technical Approach**:
   - "Do you have a preferred approach for this implementation?"
   - "Should this follow existing patterns in the codebase?"
   - "Are there any specific files/components I should focus on?"

3. **Scope & Boundaries**:
   - "Is this a new feature or a modification to existing functionality?"
   - "What parts of the system does this affect?"
   - "Are there any performance or security considerations?"

4. **Testing Requirements**:
   - "What level of testing do you expect for this?"
   - "Are there specific scenarios I need to cover?"
   - "Should I write tests before or after implementation?"

5. **Integration Points**:
   - "How does this integrate with existing features?"
   - "Are there any dependencies or prerequisites?"
   - "Will this require database changes or migrations?"

#### **When in Doubt:**
- **Ask first, implement second**
- **Use MCP servers to verify assumptions**
- **Check console errors with Playwright**
- **Verify database structure with Laravel Boost**
- **Clarify requirements before writing code**
- **Check for similar existing implementations**

**Remember**: It's better to ask 5 clarifying questions than to spend 2 hours implementing the wrong thing!

---

## 🏗️ **Architecture Overview**

### Backend Architecture (Laravel 12)

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        LARAVEL 12 BACKEND                              │
│                                                                         │
│  ├── Models (9 total)                                                   │
│  │   ├── Monitor.php (EXTENDED Spatie model)                           │
│  │   ├── Website.php (Team-aware website management)                   │
│  │   ├── User.php (2FA enabled, team relationships)                    │
│  │   ├── Team.php (Team management)                                     │
│  │   └── TeamMember.php (Role-based permissions)                       │
│  │                                                                       │
│  ├── Controllers                                                        │
│  │   ├── SSL/WebsiteController.php (CRUD operations)                    │
│  │   ├── Settings/TeamController.php (Team management)                 │
│  │   ├── Auth/ (2FA, login, registration)                             │
│  │   └── DashboardController.php (Main dashboard)                      │
│  │                                                                       │
│  ├── Jobs                                                               │
│  │   ├── ImmediateWebsiteCheckJob.php (Manual "Check Now")              │
│  │   └── Uses extended Monitor model for enhanced data collection       │
│  │                                                                       │
│  ├── Queue System                                                       │
│  │   ├── Redis queues for optimal performance                           │
│  │   ├── Horizon for queue monitoring                                   │
│  │   └── Scheduler for automated monitoring (every 5 min)               │
│  │                                                                       │
│  └── Middleware                                                         │
│      ├── HandleInertiaRequests (Shares flash messages to frontend)      │
│      ├── TeamMembership (Role-based access control)                     │
│      └── 2FA middleware                                                 │
└─────────────────────────────────────────────────────────────────────────┘
```

### Frontend Architecture (Vue 3 + TypeScript)

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        VUE 3 FRONTEND                                  │
│                                                                         │
│  ├── Pages (Inertia.js)                                                 │
│  │   ├── Dashboard.vue (Main overview)                                 │
│  │   ├── SSL/Websites.vue (Website management)                         │
│  │   ├── Settings/Team.vue (Team management)                           │
│  │   └── Auth/ (Login, registration, 2FA)                             │
│  │                                                                       │
│  ├── Components (190 total)                                             │
│  │   ├── ThemeCustomizer.vue (Advanced theming system)                  │
│  │   ├── Toast/ (Toast notification system)                            │
│  │   ├── ui/ (Reusable UI components)                                  │
│  │   └── layouts/ (DashboardLayout, AppHeader)                         │
│  │                                                                       │
│  ├── State Management                                                   │
│  │   ├── stores/theme.ts (Theme persistence)                            │
│  │   ├── composables/useToast.ts (Toast system)                        │
│  │   └── Pinia for complex state                                        │
│  │                                                                       │
│  └── Configuration                                                      │
│      ├── config/navigation.ts (Centralized navigation)                 │
│      └── app.ts (Inertia setup, event listeners)                       │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 🔧 **Development Flow & Where to Start**

### 1. **Starting Development Environment**

#### **Primary Development Command (Recommended)**
```bash
# Start complete development environment with one command
composer run dev
```
**This command starts:**
- **server**: Laravel development server (php artisan serve)
- **horizon**: Queue worker for background jobs
- **logs**: Real-time log monitoring (php artisan pail)
- **scheduler**: Laravel scheduler for automated monitoring
- **vite**: Frontend development server with hot reload

#### **Individual Services (If Needed)**
```bash
# Start containers
./vendor/bin/sail up -d

# Individual services
./vendor/bin/sail npm run dev              # Frontend dev server
./vendor/bin/sail artisan schedule:work    # Automated monitoring
./vendor/bin/sail artisan queue:work redis --queue=immediate,notifications
```

**CRITICAL**: After any CSS/UI changes, always clear caches:
```bash
./vendor/bin/sail artisan cache:clear && \
./vendor/bin/sail artisan config:clear && \
./vendor/bin/sail artisan view:clear && \
./vendor/bin/sail artisan route:clear && \
./vendor/bin/sail npm run dev
```

### 2. **Key Files to Understand**

#### **Core Models**

- **`app/Models/Monitor.php` - MOST IMPORTANT: Extended Spatie model with JavaScript content validation**
  ```php
  // ⚠️ CRITICAL: This is a CUSTOM model that extends Spatie's base model!
  class Monitor extends Spatie\UptimeMonitor\Models\Monitor
  {
      // Enhanced functionality:
      // - Response time tracking
      // - Content validation with JavaScript rendering
      // - Custom uptime intervals
      // - Extended casting for JSON fields
  }
  ```

  **🚨 ALWAYS REMEMBER**: Use `App\Models\Monitor` in tests and application code, **NEVER** use `Spatie\UptimeMonitor\Models\Monitor` directly!

- `app/Models/Website.php` - Team-aware website management
- `app/Models/User.php` - User with 2FA and team relationships

#### **Frontend Entry Points**
- `resources/js/pages/Dashboard.vue` - Main dashboard
- `resources/js/config/navigation.ts` - **Navigation structure**
- `resources/js/stores/theme.ts` - Theme management
- `resources/js/composables/useToast.ts` - Toast notifications

#### **Controllers**
- `app/Http/Controllers/SSL/WebsiteController.php` - Website CRUD
- `app/Http/Controllers/Settings/TeamController.php` - Team management
- `app/Http/Controllers/DashboardController.php` - Dashboard data

#### **Configuration**
- `routes/console.php` - **Scheduler tasks** (automated monitoring)
- `config/horizon.php` - Queue worker configuration
- `.env` - Environment variables (prioritize these over hardcoded values)

### 3. **Data Flow Understanding**

#### **Website Creation Flow**
```
User submits form → WebsiteController@store →
Website model created → ImmediateWebsiteCheckJob dispatched →
Queue worker processes job → Extended Monitor checks website →
Results saved to monitors table → UI updated via Inertia
```

#### **Automated Monitoring Flow**
```
Cron triggers scheduler → Laravel scheduler runs monitor:check-uptime →
Extended Monitor processes ALL websites in batch →
Results saved to monitors table → Events fired → Notifications sent
```

#### **Team Management Flow**
```
Team owner invites member → TeamInvitation model created →
Email sent → User accepts invitation → TeamMember created with role →
Permissions enforced via middleware
```

---

## 🐛 **Debugging Approach**

### 1. **Start Here: Common Issues**

#### **Frontend Issues**
1. **Use Playwright MCP to check console errors**:
   ```bash
   # Navigate to the page and check for errors
   mcp__playwright-extension__browser_navigate + mcp__playwright-extension__browser_console_messages
   ```
2. **Verify Network Tab** - Check Inertia requests/responses
3. **Clear Caches** - Always try this first for UI issues
4. **Check Toast Notifications** - Use `useToast()` for debugging info
5. **Take screenshots** - `mcp__playwright-extension__browser_take_screenshot` for visual verification

#### **Backend Issues**
1. **Check Laravel Logs**: `./vendor/bin/sail artisan log:tail`
2. **Use Laravel Boost MCP for backend inspection**:
   ```bash
   # Check recent errors
   mcp__laravel-boost__last-error

   # Inspect database structure
   mcp__laravel-boost__database-schema

   # See available routes
   mcp__laravel-boost__list-routes
   ```
3. **Check Queue Status**: `./vendor/bin/sail artisan horizon:status`
4. **Verify Scheduler**: `./vendor/bin/sail artisan schedule:list`
5. **Test PHP code**: `mcp__laravel-boost__tinker` to test code snippets

#### **Testing Issues**
1. **Run Specific Test**: `./vendor/bin/sail artisan test --filter=TestName`
2. **Browser Tests**: Check for screenshots in `tests/Browser/screenshots/`
3. **Database State**: Tests use separate database, check test migrations
4. **Use Playwright MCP** to manually test UI interactions before writing automated tests

### 2. **Essential Debugging Tools**

#### **Laravel Debugging (Always Use Sail)**
```bash
# Application information
./vendor/bin/sail artisan about

# Check routes
./vendor/bin/sail artisan route:list

# Monitor status
./vendor/bin/sail artisan monitor:list

# Queue status
./vendor/bin/sail artisan queue:monitor

# Check logs
./vendor/bin/sail artisan log:tail

# Test code snippets
./vendor/bin/sail artisan tinker

# Cache management
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan view:clear
```

#### **Frontend Debugging**
- **Vue DevTools** - Essential for Vue 3 debugging
- **Network Tab** - Check Inertia XHR requests
- **Console** - Look for toast system messages

#### **Database Debugging**
- **Laravel Boost MCP** - Use for schema inspection (database: "mariadb")
- **Tinker**: `./vendor/bin/sail artisan tinker`
- **Query Debugging**: Use `->dump()` or `->dd()` on queries

#### **Important Debugging Note**
**Never use direct PHP commands** - always prefix with `./vendor/bin/sail`:
```bash
# ❌ WRONG - These won't work in this Sail environment
php artisan tinker
composer dump-autoload
npm run build

# ✅ CORRECT - Use Sail for everything
./vendor/bin/sail artisan tinker
./vendor/bin/sail composer dump-autoload
./vendor/bin/sail npm run build
```

---

## 🧪 **Testing Strategy**

### **Test Structure (534 total tests)**
```
tests/
├── Unit/           - Unit tests for services and utilities
├── Feature/        - Feature tests for application flows
└── Browser/        - Playwright browser tests for UI interactions
```

### **Common Test Patterns**
```bash
# Run specific test
./vendor/bin/sail artisan test --filter=WebsiteTest

# Run browser tests
./vendor/bin/sail artisan test --testsuite=Browser

# Test with coverage
./vendor/bin/sail artisan test --coverage
```

### **Test Database**
- Tests use separate SQLite database
- Uses factories for data creation

### **🚀 Performance Testing Best Practices**

#### **Parallel Testing (77% faster)**
```bash
# Sequential: ~70 seconds
./vendor/bin/sail artisan test

# Parallel: ~16 seconds (RECOMMENDED)
./vendor/bin/sail artisan test --parallel
```

#### **Test Data Management**
- **Centralized Setup**: All test data created in `tests/Pest.php` with cleanup functions
- **Conditional Creation**: Only create data that tests actually need
- **Lazy Loading**: Config and services loaded on-demand to avoid context issues

### **🔧 Common Testing Issues & Solutions**

#### **Type Casting Problems**
```php
// URL Objects vs Strings
expect((string) $monitor->url)->toBe('https://example.com');

// JSON Arrays vs Strings
expect(getArrayValue($monitor->content_expected_strings))->toBe(['string1', 'string2']);
```

#### **Observer Testing**
```php
// Remember: updateOrCreate creates NEW records when key changes
$website->update(['url' => 'https://newdomain.com']);
$newMonitor = Monitor::where('url', 'https://newdomain.com')->first(); // Not old one
```

#### **Test Data Count Mismatches**
```php
// Ensure minimum data, create additional if needed
$websites = $this->realWebsites->take(4);
if ($websites->count() < 4) {
    Website::factory()->count(4 - $websites->count())->create(['user_id' => $this->user->id]);
}
```

### **📊 Test Suite Health**
- **Current Status**: 494 passing, 10 failing (98.0% success rate)
- **Recent Achievement**: Fixed 479 failing tests (96% reduction) + Performance optimizations
- **Key Fixes**: Database setup, type casting, observer logic, data count issues, Monitor model imports
- **Performance**: Individual tests under 1 second, full suite in 17.0s parallel

### **📚 Testing Documentation**
- **Comprehensive Guide**: See `docs/TESTING_INSIGHTS.md` for detailed patterns
- **Performance Analysis**: Parallel testing impact and optimization strategies
- **Debugging Strategies**: Common failure patterns and solutions
- **Test Architecture**: Modern Pest 4 setup with centralized data management

---

## 🚀 **Adding New Features**

### 1. **Feature Development Workflow**

#### **Step 0: CLARIFY REQUIREMENTS (Critical!)**
**Before writing any code, ask:**
- "What specific problem are we solving?"
- "What should the user experience be?"
- "Are there existing similar features I should reference?"
- "What are the acceptance criteria?"
- "Any performance/security considerations?"

#### **Step 1: Understand the Existing Pattern**
1. **Find similar existing feature** - Look at controllers, models, components
2. **Check navigation config** - Add new routes to `navigation.ts`
3. **Understand the data flow** - Follow similar existing patterns
4. **Verify your understanding** - "Based on [existing feature], should this work similarly?"

#### **Step 2: Backend Implementation**
1. **Model/Migration** - Create or extend models
2. **Controller** - Add CRUD operations following existing patterns
3. **Routes** - Add to `routes/web.php`
4. **Policies** - Add authorization if needed
5. **Validate approach** - "Should I follow the same pattern as [similar feature]?"

#### **Step 3: Frontend Implementation**
1. **Page Component** - Create Vue page component
2. **API Integration** - Use Inertia.js for data fetching
3. **UI Components** - Reuse existing components from `components/ui/`
4. **Navigation** - Add to `navigation.ts`
5. **Confirm UI approach** - "Should this look and feel like [existing component]?"

#### **Step 4: Testing**
1. **Unit Tests** - Test business logic
2. **Feature Tests** - Test user flows
3. **Browser Tests** - Test UI interactions
4. **Run All Tests**: `./vendor/bin/sail artisan test`
5. **Verify test coverage** - "Are there edge cases I should test?"

### 2. **Code Patterns to Follow**

#### **Controllers**
```php
// Use proper return types
public function index(): Response
{
    // Use policy for authorization
    $this->authorize('viewAny', Website::class);

    // Return Inertia response with proper data
    return inertia('SSL/Websites', [
        'websites' => Website::with('team')->get(),
    ]);
}
```

#### **Vue Components**
```vue
<script setup lang="ts">
// Use proper TypeScript types
import { useForm } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';

// Use composables for functionality
const { success, error } = useToast();
const form = useForm({
    name: '',
    url: '',
});
</script>
```

#### **Models**
```php
// Use proper typed properties
class Website extends Model
{
    protected $fillable = [
        'name',
        'url',
        'team_id',
    ];

    // Define relationships
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
```

---

## 🔍 **Where to Find Things**

### **Common Locations**
| What You Need | Where to Look |
|---------------|---------------|
| **Navigation menus** | `resources/js/config/navigation.ts` |
| **Theme functionality** | `resources/js/components/ThemeCustomizer.vue` |
| **Toast notifications** | `resources/js/composables/useToast.ts` |
| **Website management** | `app/Http/Controllers/SSL/WebsiteController.php` |
| **Team management** | `app/Http/Controllers/Settings/TeamController.php` |
| **Monitoring logic** | `app/Models/Monitor.php` (CUSTOM extended Spatie model) |
| **Scheduler tasks** | `routes/console.php` |
| **Queue configuration** | `config/horizon.php` |
| **Authentication** | `app/Http/Controllers/Auth/` |
| **2FA functionality** | `app/Http/Controllers/Auth/TwoFactorChallengeController.php` |

### **Database Schema**
- Use **Laravel Boost MCP**: `mcp__laravel-boost__database-schema` with `database: "mariadb"`
- Key tables: `websites`, `monitors`, `teams`, `team_members`, `users`
- **Quick schema check**: `mcp__laravel-boost__database-schema(database: "mariadb", filter: "table_name")`

### **Frontend Routes**
- Use **Laravel Boost MCP**: `mcp__laravel-boost__list-routes` to see all available routes
- Inertia.js handles routing automatically
- Routes defined in `routes/web.php`
- Navigation structure in `navigation.ts`

### **Self-Verification with MCP Servers**
**Before asking questions, try these MCP calls:**

```bash
# Check if a route exists
mcp__laravel-boost__list-routes

# Inspect a database table (IMPORTANT: Use correct database connection name)
mcp__laravel-boost__database-schema(database: "mariadb", filter: "table_name")

# Check for recent errors
mcp__laravel-boost__last-error

# Test page manually in browser
mcp__playwright-extension__browser_navigate
mcp__playwright-extension__browser_console_messages
mcp__playwright-extension__browser_snapshot
```

---

## 🚀 **Production Deployment & Server Access**

### **Server Configuration**
**Production Server**: `monitor.intermedien.at`
- **Root Access**: SSH as `root` via `ssh arm002`
- **Web Directory**: `/var/www/monitor.intermedien.at/web`
- **HTTP User**: `web6` (for file operations)
- **Deploy User**: `default_deploy` (maps to web6:client0)

### **Deployment Solution: Deployer**
**Configuration File**: `deploy.php` - Complete deployment automation

#### **Repository Setup**
- **Primary Repository**: `git@github.com:dabonzo/sslmonitor.git` (github)
- **Secondary Repository**: `gitea:bonzo/ssl-monitor.git` (origin)
- **Main Branch**: `main`
- **Deployment Path**: `/var/www/monitor.intermedien.at/web`

#### **Git Workflow - Push to Both Repositories**
```bash
# Push to BOTH repositories (REQUIRED)
git push github main && git push origin main

# Check current remotes
git remote -v

# Individual pushes (if needed)
git push github main      # Primary: GitHub
git push origin main      # Secondary: Gitea
```

#### **Deployer Commands**
```bash
# Deploy to production
./vendor/bin/dep deploy production -v

# Rollback deployment
./vendor/bin/dep rollback production

# List deployments
./vendor/bin/dep releases production

# Check deployment status
./vendor/bin/dep status production
```

#### **Server Access Methods**

**Method 1: Root Access (Full System Control)**
```bash
# SSH as root
ssh arm002

# Navigate to web directory
cd /var/www/monitor.intermedien.at/web

# Full system access available
```

**Method 2: Deploy User Access (Application-Level)**
```bash
# SSH as deploy user
ssh default_deploy@monitor.intermedien.at

# Application-level operations
cd /var/www/monitor.intermedien.at/web/current
php artisan horizon:status
php artisan cache:clear
```

### **Production Server Configuration**
From `deploy.php`:
- **PHP Version**: PHP 8.4 (`/usr/bin/php8.4`)
- **HTTP User/Group**: `web6:client0`
- **Shared Directories**: `storage`, `.playwright` (persistent browsers)
- **Service Management**: systemd (Horizon, PHP-FPM, Redis, Apache)

### **Git Workflow**
1. **Feature Development**: Create new branches for features
2. **Atomic Commits**: Make small, focused commits
3. **Push to Both Repositories**: `git push github main && git push origin main`
4. **Deploy**: Use Deployer to deploy to production
5. **Rollback**: Use Deployer rollback if needed

### **Direct Server Operations**
**When you need to make direct changes:**

```bash
# As root (full access)
ssh arm002
cd /var/www/monitor.intermedien.at/web
# Edit files directly, restart services, etc.

# As deploy user (application operations)
ssh default_deploy@monitor.intermedien.at
cd current
php artisan migrate
php artisan horizon:terminate
```

### **SSL Monitor Systemd Services**
**The application runs two main systemd services:**

#### **1. ssl-monitor-horizon.service**
- **Purpose**: Queue worker for background job processing
- **Status**: Always running (Type=simple)
- **User**: web6 (application user)
- **Log File**: `/var/www/monitor.intermedien.at/web/shared/storage/logs/horizon.log`

```bash
# Horizon service management
sudo systemctl status ssl-monitor-horizon
sudo systemctl restart ssl-monitor-horizon
sudo systemctl start ssl-monitor-horizon
sudo systemctl stop ssl-monitor-horizon
sudo systemctl is-active ssl-monitor-horizon

# View Horizon logs
tail -f /var/www/monitor.intermedien.at/web/shared/storage/logs/horizon.log
```

#### **2. ssl-monitor-scheduler.timer + ssl-monitor-scheduler.service**
- **Purpose**: Runs Laravel scheduler every minute for automated monitoring
- **Type**: Timer triggers service (OnCalendar=*:0/1)
- **Service**: Oneshot - runs `artisan schedule:run` then exits
- **User**: web6 (application user)
- **Log File**: `/var/www/monitor.intermedien.at/web/shared/storage/logs/scheduler.log`

```bash
# Scheduler timer management
sudo systemctl status ssl-monitor-scheduler.timer
sudo systemctl restart ssl-monitor-scheduler.timer

# Check when scheduler will run next
sudo systemctl list-timers ssl-monitor-scheduler.timer

# View scheduler logs
tail -f /var/www/monitor.intermedien.at/web/shared/storage/logs/scheduler.log
```

### **Service Management (default_deploy user)**
**The default_deploy user has passwordless sudo access for specific service commands:**

```bash
# Horizon service management
sudo systemctl status ssl-monitor-horizon
sudo systemctl restart ssl-monitor-horizon
sudo systemctl start ssl-monitor-horizon
sudo systemctl stop ssl-monitor-horizon
sudo systemctl is-active ssl-monitor-horizon

# Scheduler timer management
sudo systemctl status ssl-monitor-scheduler.timer
sudo systemctl restart ssl-monitor-scheduler.timer

# Restart other services
sudo systemctl restart php8.4-fpm
sudo systemctl restart redis
sudo systemctl restart apache2

# View logs
tail -f /var/www/monitor.intermedien.at/web/shared/storage/logs/laravel.log
```

### **SSH Access to Production Server**
```bash
# As deploy user (preferred for most operations)
ssh -i ~/.ssh/ssl-monitor-deploy default_deploy@monitor.intermedien.at

# As root (only when absolutely necessary)
ssh arm002
```

**Important**: Use Deployer for deployments when possible. Direct server access is for debugging, emergency fixes, and configuration changes. The default_deploy user has sufficient sudo permissions for most service management tasks.

**default_deploy User Sudo Permissions**:
- ✅ Horizon service management (start, stop, restart, status, is-active)
- ✅ PHP-FPM restart
- ✅ Redis restart
- ✅ Apache2 restart
- ❌ Limited to these specific commands only (no full sudo access)

**Current Service Status (as of last check)**:
- ✅ `ssl-monitor-horizon.service` - Active and running
- ✅ `ssl-monitor-scheduler.timer` - Active, triggers every minute
- ✅ `ssl-monitor-scheduler.service` - Runs on timer trigger (oneshot)

---

## ⚡ **Quick Development Commands**

### **Essential Commands (Copy-Paste Ready)**
```bash
# Start complete development environment (RECOMMENDED)
composer run dev

# Alternative: Individual service startup
./vendor/bin/sail up -d && ./vendor/bin/sail npm run dev

# Start queue workers
./vendor/bin/sail artisan queue:work redis --queue=immediate,notifications

# Start scheduler (if not using composer run dev)
./vendor/bin/sail artisan schedule:work

# Clear caches (CRITICAL after CSS changes)
./vendor/bin/sail artisan cache:clear && \
./vendor/bin/sail artisan config:clear && \
./vendor/bin/sail artisan view:clear && \
./vendor/bin/sail artisan route:clear

# Run tests
./vendor/bin/sail artisan test

# Check application status
./vendor/bin/sail artisan about
./vendor/bin/sail artisan monitor:list
./vendor/bin/sail artisan horizon:status

# Code quality
./vendor/bin/sail exec laravel.test ./vendor/bin/pint

# Database operations
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan tinker
./vendor/bin/sail artisan db:seed

# Package management
./vendor/bin/sail composer install
./vendor/bin/sail composer update
./vendor/bin/sail composer require package-name
```

### **MCP Server Quick Start**
```bash
# Laravel Boost for backend questions
mcp__laravel-boost__application-info
mcp__laravel-boost__list-routes
mcp__laravel-boost__database-schema(database: "mariadb", filter: "table_name")

# Playwright for frontend testing
mcp__playwright-extension__browser_navigate?url=http://localhost
mcp__playwright-extension__browser_console_messages?onlyErrors=true
mcp__playwright-extension__browser_snapshot
```

### **Deployment Quick Start**
```bash
# Deploy to production
./vendor/bin/dep deploy production -v

# Quick rollback if needed
./vendor/bin/dep rollback production

# Check deployment status
./vendor/bin/dep status production
```

### **Development Workflow**
1. **Start environment** → `composer run dev` (starts all services)
2. **Make changes** → Test → Clear caches → Verify
3. **Use TDD** → Write tests first, implement second
4. **Check documentation** → Use Laravel Boost MCP for up-to-date info
5. **Follow conventions** → Use existing patterns and naming conventions
6. **Self-verify with MCP** → Use Laravel Boost for backend, Playwright for frontend
7. **Test manually** → Use Playwright MCP to verify UI before automated tests
8. **Deploy changes** → Use Deployer for production deployment
9. **Feature branches** → Create new branches for features, atomic commits

**Note**: `composer run dev` uses concurrently to run all development services in color-coded terminals:
- 🔵 **server**: Laravel dev server
- 🟣 **horizon**: Queue worker
- 🔴 **logs**: Real-time log monitoring
- 🟡 **scheduler**: Automated monitoring
- 🟢 **vite**: Frontend hot reload

---

## 🎯 **Key Architecture Decisions**

### **Why This Architecture Works**
1. **Extended Monitor Model** - **CUSTOM Monitor model extending Spatie's with enhanced features**
2. **Hybrid Queue System** - Scheduler for efficiency, queues for responsiveness
3. **Vue 3 + TypeScript** - Modern, type-safe frontend development
4. **Team-Based Permissions** - Scalable collaboration model
5. **Toast System** - Elegant user feedback without page refreshes

### **🚨 Critical Architecture Pattern: Custom Monitor Model**
```php
// ALWAYS use this pattern in application code and tests:
use App\Models\Monitor; // ✅ CORRECT - Custom extended model

// NEVER use this:
use Spatie\UptimeMonitor\Models\Monitor; // ❌ WRONG - Base Spatie model
```

**Why This Matters:**
- Custom Monitor includes response time tracking, content validation, JavaScript rendering
- Type compatibility issues when using wrong Monitor class
- Service methods expect custom Monitor, not base Spatie model
- Testing failures when models don't match expected custom functionality

### **Performance Considerations**
- **Redis queues** for low-latency job processing
- **Batch processing** for automated monitoring (efficiency)
- **Individual jobs** for user actions (responsiveness)
- **Theme persistence** in localStorage for better UX
- **Lazy loading** for components like ThemeCustomizer

---

## 📞 **When in Doubt**

### **Debugging Checklist**
1. ✅ Check browser console for JavaScript errors
2. ✅ Check Laravel logs: `./vendor/bin/sail artisan log:tail`
3. ✅ Verify queues are running: `./vendor/bin/sail artisan horizon:status`
4. ✅ Clear all caches (especially after CSS changes)
5. ✅ Check environment variables in `.env`
6. ✅ Verify database connections
7. ✅ Run tests to check for regressions

### **Development Best Practices**
1. **ASK CLARIFYING QUESTIONS FIRST** - Never assume requirements
2. **Use existing patterns** - Don't reinvent what already works
3. **Test-first development** - Write tests before implementation
4. **Clear caches religiously** - Especially after frontend changes
5. **Use Laravel Boost MCP** - Get up-to-date Laravel documentation
6. **Follow naming conventions** - Consistency is key
7. **Prioritize environment variables** - Don't hardcode values
8. **Follow Laravel & PHP Guidelines** - Reference `/home/bonzo/.claude/laravel-php-guidelines.md` for coding standards
9. **🚨 CRITICAL: Monitor Model Usage** - Always use `App\Models\Monitor`, never Spatie's directly!

### **Questions to Ask When Stuck**
- "Can you help me understand the specific requirements here?"
- "Are there existing examples in the codebase I should follow?"
- "What should the user experience be for this feature?"
- "Are there any constraints or limitations I should be aware of?"
- "Should I prioritize performance, security, or user experience here?"

### **Remember:**
- **It's better to ask questions than to implement the wrong thing**
- **Clarify requirements before writing any code**
- **Verify your understanding with examples**
- **Check for similar implementations first**

---

**This primer should provide everything needed to understand the codebase structure, development flow, and where to start when adding features or debugging. Keep it handy for every new development session!**