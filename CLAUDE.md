# SSL Monitor v4 - AI Development Guidelines

## 📖 Documentation Index

**🚀 Core Documents:**
- **[SSL_MONITOR_V4_IMPLEMENTATION_PLAN.md](SSL_MONITOR_V4_IMPLEMENTATION_PLAN.md)** - Complete development plan
- **[V4_TECHNICAL_SPECIFICATIONS.md](V4_TECHNICAL_SPECIFICATIONS.md)** - Models, services, API endpoints
- **[old_docs/](old_docs/)** - Complete SSL Monitor v3 reference implementation

**⚡ Slash Commands:**
- **`/prime`** - Project primer and quick setup
- **`/ssl-feature`** - SSL feature development with TDD
- **`/test`** - Testing framework and execution guide

---

## 🎯 Current Status: Production Ready! 🎉

**SSL Monitor v4** - Enterprise SSL certificate monitoring with Laravel 12 + Vue 3 + Inertia.js

**✅ Complete Features:**
- SSL monitoring dashboard with real-time data
- Team management with roles and permissions
- Authentication with 2FA (PragmaRX Google2FA)
- Modern Vue 3 + TypeScript UI with dark/light themes
- Comprehensive testing (271 tests with browser testing)
- Database integration with Spatie Laravel Uptime Monitor

### Technology Stack
- **Backend**: Laravel 12 + PHP 8.4 + MariaDB + Redis
- **Frontend**: Vue 3 + Inertia.js + TailwindCSS v4
- **Testing**: Pest v4 (271 tests with browser testing)
- **Development**: Laravel Sail

### Essential Commands
```bash
# Development
./vendor/bin/sail up -d && ./vendor/bin/sail npm run dev

# Testing
./vendor/bin/sail artisan test --filter=TestName

# Code quality
./vendor/bin/sail exec laravel.test ./vendor/bin/pint

# CRITICAL: After CSS changes - clear caches and restart Vite
./vendor/bin/sail artisan cache:clear && ./vendor/bin/sail artisan config:clear && ./vendor/bin/sail artisan view:clear && ./vendor/bin/sail artisan route:clear
./vendor/bin/sail npm run dev
```

### Key Architecture
- **Unified Monitoring**: Single system using Spatie Laravel Uptime Monitor for SSL and uptime
- **Custom Monitor Model**: Extended with response time tracking (`app/Models/Monitor.php`)
- **Authentication**: PragmaRX Google2FA (migrated from Laravel Fortify)
- **Navigation**: Centralized config in `/resources/js/config/navigation.ts`

### Development Rules
1. Use TDD - write tests first, implement features second
2. Clear caches after frontend changes
3. Follow existing code conventions
4. **ALWAYS check documentation with laravel-boost MCP before implementing Laravel features** - Laravel versions change implementation approaches, always verify current version documentation first
5. **ALWAYS prioritize environment variables** - Use `.env` configuration instead of hardcoding values in PHP files where it makes sense
6. **Fix the code, not the expectations** - When tests fail, fix the implementation to meet the test requirements rather than changing the test expectations (unless the test is fundamentally wrong)

---

## 🎯 Application Status: Production Ready! ✅

**Complete Features:**
- SSL monitoring dashboard with real-time data and response time tracking
- Team management with roles, invitations, and permissions
- Authentication with 2FA and user management
- Modern Vue 3 + TypeScript UI with dark/light themes
- Comprehensive testing (271 tests with browser testing)
- Reactive filtering, bulk operations, and settings management

**Ready for new feature development or production deployment.**

**Use `/prime` for project overview and `/test` for testing guidance.**