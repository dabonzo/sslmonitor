# /prime - SSL Monitor v3 Project Primer

**Purpose**: Quickly onboard developers to SSL Monitor v3 with essential context, current progress, and next steps.

## 📊 Current Development Status

**📋 Live Status**: Check `DEVELOPMENT_PROGRESS.md` for real-time project status and detailed progress tracking.

### Quick Status Check Commands (⚠️ Always use Sail)
```bash
# Check current git branch and recent commits
git branch --show-current
git log --oneline -5

# View current development phase and tasks
head -50 DEVELOPMENT_PROGRESS.md

# Check current build status and dependencies
./vendor/bin/sail npm run build  # Test current build status
./vendor/bin/sail npm run dev    # Start development server
git status                       # See current working state
```

### 🐳 **CRITICAL: Always Use Laravel Sail**
**All Laravel/PHP/Node commands MUST be run through Sail:**
```bash
./vendor/bin/sail artisan [command]    # NOT: php artisan
./vendor/bin/sail npm [command]        # NOT: npm
./vendor/bin/sail composer [command]   # NOT: composer
./vendor/bin/sail exec laravel.test [command]  # For direct container access
```

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
SSL Monitor v3 is a professional SSL certificate and uptime monitoring platform built with:
- **Backend**: Laravel 12 + PHP 8.4 + MySQL 8.0 + Redis
- **Frontend**: Vue 3 + Inertia.js + TailwindCSS v4 (VRISTO as visual reference only)
- **Testing**: Pest v4 + Playwright browser testing
- **Development**: Laravel Sail + Git Flow + 4-MCP server ecosystem

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

### SSL Monitor Core Features
1. **SSL Certificate Monitoring**: Automated checks, expiry alerts, validation
2. **Uptime Monitoring**: HTTP/HTTPS checks, response time tracking
3. **Team Collaboration**: Role-based permissions, shared dashboards
4. **Professional UI**: VRISTO-inspired design with pure Vue.js + TailwindCSS
5. **Real-time Notifications**: WebSocket updates, email alerts

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
- **[v3/CLAUDE.md](v3/CLAUDE.md)** - Master AI development reference
- **[v3/PROJECT_PLAN.md](v3/PROJECT_PLAN.md)** - Development phases and milestones
- **[v3/VRISTO_INTEGRATION.md](v3/VRISTO_INTEGRATION.md)** - Template integration guide
- **[v3/DEVELOPMENT_WORKFLOW.md](v3/DEVELOPMENT_WORKFLOW.md)** - TDD process and workflows
- **[v3/GIT_WORKFLOW.md](v3/GIT_WORKFLOW.md)** - Git Flow branching strategy

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

### Current Development Context
- **Working on**: Clean Vue.js layout with VRISTO-inspired design
- **Approach**: Pure Vue 3 + Inertia.js + TailwindCSS (no external UI dependencies)
- **Files involved**:
  - `resources/js/layouts/AppLayout.vue` (main layout component)
  - `resources/js/pages/Dashboard.vue` (dashboard page)
  - `resources/css/app.css` (TailwindCSS v4 configuration)
  - `VRISTO_APPROACH.md` (visual reference guidelines)

### Testing Status
- **Current tests**: Basic structure in place
- **Next testing**: Console error verification and dashboard functionality
- **Console errors**: Recently fixed SidebarContext injection errors

### VRISTO Reference Files (Visual Only)
- `vristo-html-starter/` - Base template for color schemes and layouts
- `vristo-html-main/` - Page templates for design inspiration
- Use for colors: `#4361ee` (primary), `#805dca` (secondary)

## Next Steps After Priming
1. **Verify current clean implementation**: Check dashboard loads without console errors
2. **Review VRISTO_APPROACH.md**: Understand visual reference vs technology integration
3. **Continue UI development**: Build components inspired by VRISTO design
4. **Follow TDD workflow**: Write tests for each new component
5. **Use trunk-based development**: Small, frequent commits to main branch

**Ready to continue SSL Monitor v3 development!** 🚀