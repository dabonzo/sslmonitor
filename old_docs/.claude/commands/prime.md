# /prime - SSL Monitor v3 Project Primer

**Purpose**: Quickly onboard developers to SSL Monitor v3 with essential context and setup.

## Project Context
SSL Monitor v3 is a professional SSL certificate and uptime monitoring platform built with:
- **Backend**: Laravel 12 + PHP 8.4 + MySQL 8.0 + Redis
- **Frontend**: Vue 3 + Inertia.js + VRISTO Template + TailwindCSS v4
- **Testing**: Pest v4 + Playwright browser testing
- **Development**: Laravel Sail + Git Flow + 4-MCP server ecosystem

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
# 1. Create feature branch (Git MCP)
git-mcp: create-branch feature/ssl-enhancement develop

# 2. Research documentation
search-docs ["ssl certificate", "laravel jobs"]  # Laravel Boost
use context7: "VRISTO admin template patterns"    # Context7

# 3. TDD Development
./vendor/bin/sail artisan make:test --pest SslFeatureTest
# Write failing tests → Implement → Refactor

# 4. VRISTO UI Integration
filesystem-mcp: copy-files vristo-html-main/dashboard.html resources/views/
use context7: "Vue 3 composition API VRISTO integration"

# 5. Commit and review
git-mcp: add-all
git-mcp: commit "Implement SSL feature with VRISTO UI"
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
4. **VRISTO UI**: Professional admin interface with Vue.js integration
5. **Real-time Notifications**: WebSocket updates, email alerts

## Essential Commands Reference

### Sail Commands
```bash
./vendor/bin/sail up -d              # Start containers
./vendor/bin/sail artisan test       # Run tests
./vendor/bin/sail artisan tinker     # Laravel REPL
./vendor/bin/sail npm run dev        # Frontend development
./vendor/bin/sail exec laravel.test ./vendor/bin/pint  # Code formatting
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

## Next Steps After Priming
1. Review specific documentation for your task area
2. Set up MCP servers following the Docker/Sail instructions
3. Start with feature development using the TDD workflow
4. Integrate VRISTO templates for UI components
5. Follow Git Flow for professional development process

**Ready to build professional SSL monitoring with modern Laravel + Vue.js stack!** 🚀