# /prime - SSL Monitor v4 Project Primer

**Purpose**: Quickly onboard developers to SSL Monitor v4 with essential context, current progress, and next steps.

## 📊 Current Development Status: Production Ready + Authentication Migration Complete! 🎉

**✅ MAJOR MILESTONE**: SSL Monitor v4 **PRODUCTION READY + AUTHENTICATION ENHANCED**
**📋 Live Status**: Laravel Fortify → PragmaRX Google2FA migration complete, 271 tests passing, 1,444 assertions.

**📋 For Complete Current State Overview**: See [CURRENT_STATE_DOCUMENTATION.md](../CURRENT_STATE_DOCUMENTATION.md)

### **🚀 What's Been Built (Phases 1 & 2 + UX Enhancement Complete)**
- ✅ **Complete Backend Foundation**: Database schema, models, services, testing
- ✅ **Hybrid Spatie Integration**: Website ↔ Monitor synchronization working
- ✅ **SSL Monitoring**: Real SSL certificate monitoring with professional dashboard
- ✅ **Production Testing**: End-to-end SSL certificate validation with Laravel Dusk
- ✅ **Database Architecture**: Cleaned up redundant tables, unified monitoring system
- ✅ **Integration Commands**: Sync and monitoring management tools
- ✅ **Professional Frontend**: Vue 3 + Inertia.js + TypeScript + shadcn/ui components
- ✅ **User Authentication**: Real user data displayed throughout application
- ✅ **Critical Bug Fixes**: Resolved sslCertificates relationship error completely
- ✅ **Theme Support**: Dark/light mode dashboard with comprehensive browser testing
- ✅ **UX Enhancement Phase**: Reactive filtering, bulk transfer fixes, settings consolidation
- ✅ **Robust Testing Framework**: 226 feature tests with browser testing infrastructure
- ✅ **Alert Management System**: Comprehensive alert configuration with default alerts
- ✅ **Team Management**: Full team functionality with roles and permissions

### **🎯 Current Status: Production Ready + UX Polished - Ready for Feature Enhancement**

### Quick Status Check Commands (⚠️ Always use Sail)
```bash
# Check current git branch and recent commits
git branch --show-current
git log --oneline -5

# Verify SSL Monitor v4 functionality
./vendor/bin/sail artisan monitor:list               # Check Spatie integration
./vendor/bin/sail artisan monitors:sync-websites     # Test hybrid sync

# Run comprehensive testing suite (226 feature tests)
./vendor/bin/sail artisan test tests/Feature/ --stop-on-failure  # All feature tests
./vendor/bin/sail artisan test --filter=SslCertificate           # SSL monitoring tests
./vendor/bin/sail artisan test --filter=ReactiveFilter           # UX enhancement tests
./vendor/bin/sail artisan test --filter=AlertsController         # Alert system tests
./vendor/bin/sail artisan test --filter=TeamTransfer             # Team management tests

# Run browser testing (verify UI functionality)
./vendor/bin/sail artisan dusk Tests\Browser\SimpleScreenshotTest # Browser tests

# Check current build status and dependencies
./vendor/bin/sail npm run build  # Build production assets
./vendor/bin/sail npm run dev    # Start development server
git status                       # See current working state

# Critical cache clearing after frontend changes
./vendor/bin/sail artisan cache:clear && ./vendor/bin/sail artisan config:clear && ./vendor/bin/sail artisan view:clear && ./vendor/bin/sail artisan route:clear
```

### 🐳 **CRITICAL: Always Use Laravel Sail**
**All Laravel/PHP/Node commands MUST be run through Sail:**
```bash
./vendor/bin/sail artisan [command]    # NOT: php artisan
./vendor/bin/sail npm [command]        # NOT: npm
./vendor/bin/sail composer [command]   # NOT: composer
./vendor/bin/sail exec laravel.test [command]  # For direct container access
```

## ⚡ Available Slash Commands
- **`/prime`** - This project primer (current command)
- **`/test`** - Comprehensive testing framework and execution guide
- **`/ssl-feature`** - SSL monitoring feature development with TDD
- **`/vristo-ui`** - VRISTO template integration workflows
- **`/debug-ssl`** - Comprehensive SSL debugging assistant

### Project Context Discovery
```bash
# Laravel Boost - Get current application state (runs in Sail container)
application-info
database-schema
list-routes

# Essential development commands
./vendor/bin/sail up -d              # Start Sail containers
./vendor/bin/sail artisan test       # Run tests
./vendor/bin/sail artisan tinker     # Laravel REPL
```

## Project Context
SSL Monitor v4 is a professional SSL certificate and uptime monitoring platform built with:
- **Backend**: Laravel 12 + PHP 8.4 + MariaDB + Redis + Spatie Uptime Monitor ✅ **COMPLETE**
- **Frontend**: Vue 3 + Inertia.js + TailwindCSS v4 + shadcn/ui components ✅ **COMPLETE**
- **Testing**: Pest v4 + Laravel Dusk with comprehensive SSL monitoring validation ✅ **WORKING**
- **Development**: Laravel Sail + Git Flow + 4-MCP server ecosystem ✅ **COMPLETE**

## 🚨 Recent Critical Fixes & UX Enhancements (September 2025)
**Major Bug Resolution:**
- ✅ **Fixed**: `Call to undefined relationship [sslCertificates] on model [App\Models\Website]`
- ✅ **Removed**: Redundant SSL models (`SslCertificate`, `SslCheck`) and database tables
- ✅ **Unified**: Single monitoring system using Spatie Laravel Uptime Monitor
- ✅ **Updated**: All controllers to use `getSpatieMonitor()` method for SSL data

**UX Enhancement Phase (September 2025):**
- ✅ **Reactive Website Filtering**: Implemented debounced reactive search (500ms) with immediate filter updates
- ✅ **Bulk Transfer UX Fixes**: Resolved Inertia.js JSON response errors, proper redirect patterns
- ✅ **Settings Page Consolidation**: Unified all settings under ModernSettingsLayout, removed Laravel starter kit conflicts
- ✅ **Alert Configuration System**: Comprehensive alert management with default configurations and custom alert creation
- ✅ **Team Management Restoration**: Restored full team functionality from git history with member roles and permissions

**Testing Framework Establishment:**
- ✅ **226 Feature Tests**: Complete test suite using development database with real seeded data
- ✅ **Browser Testing Infrastructure**: Selenium Docker integration with Pest v4 for comprehensive UI testing
- ✅ **Test Documentation**: Complete testing guide in `.claude/commands/test.md`

**Application Status:**
- ✅ **Working**: Complete SSL monitoring dashboard with real data
- ✅ **Working**: Website CRUD operations with SSL integration and reactive filtering
- ✅ **Working**: Dark/light mode theme switching
- ✅ **Working**: User authentication and profile display
- ✅ **Working**: Team management with roles and permissions
- ✅ **Working**: Alert configuration and management system
- ✅ **Working**: Bulk website transfer operations

## 🎨 **CRITICAL: VRISTO Usage Approach**
**VRISTO is VISUAL REFERENCE ONLY - NOT technology integration:**

✅ **What TO DO with VRISTO:**
- Use as design inspiration for layouts, colors, component styles
- Extract color values: `#4361ee` (primary), `#805dca` (secondary)
- Reference layout patterns: sidebar + main content structure
- Study component styling: cards, buttons, forms, tables
- Use screenshots/HTML as visual guides for Vue.js components

❌ **What NOT TO DO with VRISTO:**
- Never integrate VRISTO JavaScript/Alpine.js files
- Never import VRISTO components directly
- Never add VRISTO dependencies to package.json
- Never copy VRISTO HTML structures directly

**Technology Stack**: Vue 3 + Inertia.js + TailwindCSS v4 (pure stack)

## Quick Start Workflow

### 1. MCP Server Setup (Docker/Sail Aware)
```bash
# Laravel Boost (Container execution)
./vendor/bin/sail composer require laravel/boost --dev
./vendor/bin/sail artisan boost:install
claude mcp add laravel-boost --scope local -- docker exec -i ssl-monitor-laravel.test-1 php /var/www/html/artisan boost:mcp .

# Context7, Filesystem, Git MCP (Host system)
npm install -g @upstash/context7-mcp @modelcontextprotocol/server-filesystem @modelcontextprotocol/server-git
```

### 2. Development Environment
```bash
# Start Sail containers
./vendor/bin/sail up -d

# Install dependencies
./vendor/bin/sail composer install
./vendor/bin/sail npm install

# Start development servers
./vendor/bin/sail npm run dev
```

### 3. Essential Context Discovery
```bash
# Laravel Boost - Application context
application-info
database-schema
list-routes

# Filesystem MCP - Project structure
filesystem-mcp: list-directory app/
filesystem-mcp: read-file .env.example

# Git MCP - Repository state
git-mcp: status
git-mcp: log --oneline -10
```

### 4. Feature Development Pattern
```bash
# 1. Start Sail environment
./vendor/bin/sail up -d

# 2. Create feature branch (Git MCP)
git-mcp: create-branch feature/ssl-enhancement develop

# 3. Research documentation
search-docs ["ssl certificate", "laravel jobs"]  # Laravel Boost
use context7: "Vue 3 composition API patterns"    # Context7

# 4. TDD Development (ALL commands use Sail)
./vendor/bin/sail artisan make:test --pest SslFeatureTest
./vendor/bin/sail artisan test --filter=SslFeature
# Write failing tests → Implement → Refactor

# 5. VRISTO-Inspired UI Development
# Look at VRISTO HTML for visual reference only
# Create Vue.js components with VRISTO-inspired styling
# Use TailwindCSS classes to replicate VRISTO design patterns

# 6. Build and test
./vendor/bin/sail npm run build
./vendor/bin/sail artisan test

# 7. Code formatting and commit
./vendor/bin/sail exec laravel.test ./vendor/bin/pint
git add . && git commit -m "Implement SSL feature with VRISTO UI"
```

## Project Architecture Overview

### MCP Server Ecosystem
- **🚀 Laravel Boost** (Container): Laravel ecosystem, debugging, application context
- **🌐 Context7** (Host): Universal docs, VRISTO template, Vue.js patterns
- **📁 Filesystem MCP** (Host): File operations, log analysis, asset management
- **🔀 Git MCP** (Host): Repository management, Git Flow workflow

### Key Directories
```
ssl-monitor-v3/
├── app/                    # Laravel application logic
├── resources/
│   ├── js/                # Vue.js components and Inertia pages
│   ├── css/               # TailwindCSS and VRISTO styles
│   └── views/             # Blade layouts and VRISTO templates
├── tests/
│   ├── Feature/           # Feature tests (TDD)
│   ├── Unit/              # Unit tests
│   └── Browser/           # Playwright browser tests
├── vristo-html-starter/   # VRISTO base template
├── vristo-html-main/      # VRISTO page templates
└── v3/                    # Comprehensive documentation
```

### SSL Monitor v4 Features Status
1. **✅ SSL Certificate Monitoring**: Enhanced SslCertificateChecker with plugin metrics (COMPLETE)
2. **✅ Hybrid Uptime Monitoring**: Spatie Laravel Uptime Monitor integration (COMPLETE)
3. **✅ Database Architecture**: Plugin-ready schema with comprehensive relationships (COMPLETE)
4. **✅ Professional UI**: VRISTO-inspired design with Vue.js + TailwindCSS + reactive filtering (COMPLETE)
5. **✅ Team Collaboration**: Role-based permissions, member management, team dashboards (COMPLETE)
6. **✅ Alert Management**: Comprehensive alert configuration with default alerts and custom creation (COMPLETE)
7. **✅ UX Enhancements**: Reactive filtering, bulk operations, settings consolidation (COMPLETE)
8. **✅ Testing Infrastructure**: 226 feature tests + browser testing with Selenium integration (COMPLETE)
9. **⏳ Real-time Notifications**: WebSocket updates, email alerts (FUTURE ENHANCEMENT)
10. **⏳ Advanced Analytics**: Historical data analysis, SSL certificate trends, reporting (FUTURE ENHANCEMENT)

## Essential Commands Reference

### 🐳 Laravel Sail Commands (ALWAYS USE THESE)
```bash
# Container Management
./vendor/bin/sail up -d              # Start containers
./vendor/bin/sail down               # Stop containers
./vendor/bin/sail shell              # Access container shell

# Laravel Development
./vendor/bin/sail artisan test       # Run tests
./vendor/bin/sail artisan tinker     # Laravel REPL
./vendor/bin/sail artisan make:*     # Create files (controllers, models, etc.)

# Frontend Development
./vendor/bin/sail npm run dev        # Start development server
./vendor/bin/sail npm run build      # Build for production
./vendor/bin/sail npm install        # Install dependencies

# Code Quality
./vendor/bin/sail exec laravel.test ./vendor/bin/pint  # Format code
./vendor/bin/sail composer install   # Install PHP dependencies
```

### Git Flow Commands (via Git MCP)
```bash
git-mcp: create-branch feature/name develop
git-mcp: merge-branch feature/name develop
git-mcp: create-branch release/v3.1.0 develop
git-mcp: tag-release v3.1.0 "Release notes"
```

### File Operations (via Filesystem MCP)
```bash
filesystem-mcp: read-file storage/logs/laravel.log
filesystem-mcp: copy-files vristo-html-starter/assets/ resources/
filesystem-mcp: tail-file storage/logs/ssl-monitoring.log 50
```

## Documentation Navigation
- **[CLAUDE.md](CLAUDE.md)** - Master AI development reference with Phase 1 status
- **[DEVELOPMENT_PROGRESS.md](DEVELOPMENT_PROGRESS.md)** - Real-time progress tracking
- **[PHASE_1_COMPLETION_REPORT.md](PHASE_1_COMPLETION_REPORT.md)** - Phase 1 achievements summary
- **[SSL_MONITOR_V4_IMPLEMENTATION_PLAN.md](SSL_MONITOR_V4_IMPLEMENTATION_PLAN.md)** - 8-week roadmap
- **[V4_TECHNICAL_SPECIFICATIONS.md](V4_TECHNICAL_SPECIFICATIONS.md)** - Models, services, APIs
- **[PROJECT_PLAN.md](PROJECT_PLAN.md)** - Original development phases
- **[TECH_STACK.md](TECH_STACK.md)** - Technology decisions and architecture
- **[GIT_WORKFLOW.md](GIT_WORKFLOW.md)** - Git Flow branching strategy

## 🎯 Continue Current Work

### Immediate Next Steps (Current Session)
```bash
# 1. Start Sail environment
./vendor/bin/sail up -d

# 2. Ensure we're on the correct feature branch
git checkout [current-feature-branch]

# 3. Check current project status
head -50 DEVELOPMENT_PROGRESS.md

# 4. Start development server
./vendor/bin/sail npm run dev

# 5. Check current todo status with TodoWrite tool if applicable
```

### Current Development Context (Phases 1 & 2 + UX Enhancement ✅ Complete)
- **✅ Completed**: Complete backend foundation with hybrid Spatie integration
- **✅ Working**: SSL monitoring, database schema, testing suite, professional frontend
- **✅ Enhanced**: Reactive filtering, bulk operations, team management, alert configuration
- **✅ Verified**: Production-ready application with 226 feature tests + browser testing
- **✅ Testing Infrastructure**: Robust testing framework with development database integration
- **🎯 Ready For**: Advanced features, performance optimization, additional monitoring capabilities

### Key Phase 1 Files (Backend Complete)
- **Models**: `app/Models/Website.php`, `app/Models/SslCertificate.php`, `app/Models/SslCheck.php`
- **Services**: `app/Services/SslCertificateChecker.php`, `app/Services/MonitorIntegrationService.php`
- **Integration**: `app/Observers/WebsiteObserver.php` (Website ↔ Monitor sync)
- **Commands**: `app/Console/Commands/SyncWebsitesWithMonitors.php`
- **Tests**: `tests/Feature/Services/SslCertificateCheckerTest.php` (14 passing tests)

### Phase 1 Backend Testing Status ✅
- **SSL Certificate Tests**: 14/14 passing - comprehensive SSL monitoring validation
- **Hybrid Integration**: Working Website ↔ Monitor synchronization
- **End-to-End Testing**: Real SSL certificate checking with GitHub.com
- **Command Interface**: `monitor:list`, `monitors:sync-websites`, `monitor:check-uptime`

### VRISTO Reference Files (Phase 2 Preparation)
- `vristo-html-starter/` - Base template for color schemes and layouts
- `vristo-html-main/` - Page templates for design inspiration
- Use for colors: `#4361ee` (primary), `#805dca` (secondary)

## Next Steps After Priming (Production Ready + UX Enhanced - Advanced Feature Phase)
1. **Verify Application**: Run comprehensive test suite (226 feature tests + browser tests)
2. **Performance Optimization**: Database query optimization, caching strategies, asset optimization
3. **Advanced Monitoring**: Response time trends, uptime analytics, SSL certificate lifecycle tracking
4. **Real-time Notifications**: WebSocket updates, email alerts, webhook integrations, Slack notifications
5. **Advanced Analytics**: Historical data analysis, SSL certificate trends, custom reporting dashboards
6. **API Development**: RESTful API for third-party integrations and mobile applications
7. **Advanced Team Features**: Team-specific dashboards, granular permissions, audit logging

## Comprehensive Testing Verification (Critical for Onboarding)
```bash
# Run complete feature test suite (226 tests)
./vendor/bin/sail artisan test tests/Feature/ --stop-on-failure

# Run browser testing with Selenium integration
./vendor/bin/sail artisan dusk Tests\Browser\SimpleScreenshotTest

# Verify specific functionality
./vendor/bin/sail artisan test --filter=ReactiveFilterTest        # Reactive filtering
./vendor/bin/sail artisan test --filter=BulkTransferTest          # Bulk operations
./vendor/bin/sail artisan test --filter=AlertsControllerTest       # Alert management
./vendor/bin/sail artisan test --filter=TeamTransferWorkflowTest   # Team management

# Check screenshots to verify UI functionality
# Screenshots saved in: tests/Browser/screenshots/
# - homepage-simple.png (authentication flow)
# - login-form-filled.png (login process)
# - after-login.png (dashboard with real user data)
```

## Testing Documentation Reference
**Use `/test` slash command for complete testing guide:**
- Development database configuration (`laravel` database)
- Real test user: `bonzo@konjscina.com` (password: `to16ro12`)
- Test websites: `www.redgas.at`, `www.fairnando.at`, `omp.office-manager-pro.com`
- Browser testing with Selenium Docker service
- Complete test execution workflows

**SSL Monitor v4 is PRODUCTION READY with full SSL monitoring functionality + UX enhancements!** 🎉