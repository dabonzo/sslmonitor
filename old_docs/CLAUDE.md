# SSL Monitor v3 - AI Development Guidelines

## 📖 Master Documentation Reference

This file serves as the central reference for AI-assisted development of SSL Monitor v3. All other documentation files provide detailed guidance for specific aspects of the project.

### 📋 Complete Documentation Index
- **[README.md](README.md)** - Comprehensive SSL Monitor application overview and features
- **[PROJECT_PLAN.md](PROJECT_PLAN.md)** - Detailed development phases, tasks, and milestones
- **[DEVELOPMENT_WORKFLOW.md](DEVELOPMENT_WORKFLOW.md)** - Step-by-step development process and TDD methodology
- **[GIT_WORKFLOW.md](GIT_WORKFLOW.md)** - Git Flow branching strategy and commands
- **[VRISTO_INTEGRATION.md](VRISTO_INTEGRATION.md)** - Complete VRISTO template integration guide
- **[TECH_STACK.md](TECH_STACK.md)** - Technology stack decisions and architecture
- **[TESTING_STRATEGY.md](TESTING_STRATEGY.md)** - TDD approach with Pest v4 + Playwright
- **[API_SPECIFICATION.md](API_SPECIFICATION.md)** - Backend API design and endpoints
- **[UI_SPECIFICATIONS.md](UI_SPECIFICATIONS.md)** - Frontend pages and component requirements

### ⚡ Claude Code Slash Commands
- **[.claude/commands/](.claude/commands/)** - Professional development slash commands
  - **`/prime`** - Project primer and quick setup
  - **`/ssl-feature`** - SSL monitoring feature development with TDD
  - **`/vristo-ui`** - VRISTO template integration workflows
  - **`/debug-ssl`** - Comprehensive SSL debugging assistant

---

## 🎯 Laravel and Vue.js Full-Stack Development Principles

### Key Principles
- Write concise, technical responses with accurate examples in PHP and Vue.js
- Follow Laravel and Vue.js best practices and conventions
- Use object-oriented programming with a focus on SOLID principles
- Favor iteration and modularization over duplication
- Use descriptive and meaningful names for variables, methods, and files
- Adhere to Laravel's directory structure conventions
- Prioritize dependency injection and service containers

### Laravel Principles
#### PHP and Framework Best Practices
- Leverage PHP 8.4+ features (readonly properties, match expressions)
- Apply strict typing: `declare(strict_types=1)`
- Follow PSR-12 coding standards
- Use Laravel's built-in features and helpers
- Implement robust error handling and logging
- Use Laravel's request validation and middleware effectively
- Implement Eloquent ORM for database modeling

#### Error Handling
- Use Laravel's exception handling tools
- Create custom exceptions when necessary
- Apply try-catch blocks for predictable errors

### Vue.js Principles
- Utilize Vite for modern development
- Organize components under `src/components`
- Use lazy loading for routes
- Apply Vue Router for SPA navigation
- Implement Pinia for state management
- Validate forms using Vue 3 composition API
- Enhance UI with professional component libraries

### Dependencies
- Laravel 12 (latest stable version)
- Vue 3 with Composition API
- Inertia.js for SPA experience
- TailwindCSS for styling
- Vite for asset compilation

### Best Practices
- Use Eloquent ORM and Service patterns
- Secure APIs with Laravel authentication
- Leverage caching mechanisms
- Use testing tools (Pest v4, Playwright)
- Apply Git Flow versioning
- Ensure database integrity
- Use localization features
- Optimize with TailwindCSS and modern build tools

### Key Conventions
1. Follow Laravel's MVC architecture
2. Use clean routing with Inertia.js
3. Implement request validation
4. Build reusable Vue components
5. Use Inertia responses for API resources
6. Manage database relationships properly
7. Ensure code decoupling
8. Implement job queues for background tasks

---

## 🏗️ SSL Monitor v3 Specific Guidelines

### Project Overview
**SSL Monitor v3** is a professional SSL certificate and uptime monitoring platform built with modern Laravel + Vue.js stack, using the VRISTO admin template for a polished UI.

**Core Mission**: Provide businesses with reliable, automated SSL certificate monitoring, advanced uptime checking, and team collaboration features.

### Development Approach
1. **Documentation-First**: All features planned and documented before implementation
2. **UI-First Development**: Complete VRISTO template integration before backend functionality
3. **TDD Methodology**: Write tests first, implement features second
4. **Git Flow Workflow**: Professional branching strategy with feature branches
5. **Quality Focus**: Comprehensive testing with Playwright browser automation

### Technology Stack (v3)
- **Backend**: Laravel 12 + PHP 8.4 + MySQL 8.0 + Redis
- **Frontend**: Vue 3 + Inertia.js + VRISTO Template + TailwindCSS
- **Testing**: Pest v4 + Playwright (browser testing with screenshots)
- **Development**: Laravel Sail + Git Flow + Laravel Pint

### Essential Commands Reference
```bash
# Development setup
./vendor/bin/sail up -d
./vendor/bin/sail npm run dev

# Testing
./vendor/bin/sail artisan test
./vendor/bin/sail artisan test --filter=TestName

# Code quality
./vendor/bin/sail exec laravel.test ./vendor/bin/pint

# Git Flow
git checkout develop
git checkout -b feature/feature-name
# ... development work ...
# Create PR: feature → develop
```

### Critical Development Rules
1. **Always read referenced documentation** before starting any feature
2. **Follow VRISTO_INTEGRATION.md** for all UI development
3. **Use PROJECT_PLAN.md** for understanding feature requirements
4. **Follow GIT_WORKFLOW.md** for all version control operations
5. **Implement TESTING_STRATEGY.md** for all new features
6. **Reference API_SPECIFICATION.md** for backend development

### SSL Monitor Domain Knowledge
- **SSL Certificate Monitoring**: Automated daily checks, expiry alerts, certificate validation
- **Uptime Monitoring**: HTTP/HTTPS checks, JavaScript-enabled content validation, response time monitoring
- **Team Collaboration**: Role-based permissions (Owner/Admin/Viewer), team website sharing
- **Notification System**: Professional email alerts, granular preferences, real-time updates
- **Advanced Features**: JavaScript content checking, custom validation rules, incident tracking

### VRISTO Template Integration
- **Professional Admin UI**: Enterprise-grade dashboard and forms
- **Responsive Design**: Mobile-optimized layouts with dark/light themes
- **Rich Components**: Charts, data tables, modals, forms
- **Alpine.js Integration**: Maintain VRISTO's interactive elements alongside Vue components

### Quality Standards
- **Test Coverage**: Every feature must have comprehensive tests
- **Browser Testing**: Playwright tests with visual validation
- **Code Style**: Laravel Pint formatting enforced
- **Documentation**: Update relevant docs for every feature
- **Performance**: Optimized queries, caching, asset compilation

---

## 🛠️ MCP Server Integration Strategy

SSL Monitor v3 uses **four complementary MCP servers** for comprehensive development support:

### 📋 MCP Server Selection Guide

**🚀 Laravel Boost**: Laravel ecosystem & application context
**🌐 Context7**: General-purpose documentation & third-party libraries
**📁 Filesystem MCP**: File operations & VRISTO template management
**🔀 Git MCP**: Repository management & Git Flow workflow

### When to Use Which Server

#### Laravel Boost (PRIMARY for Laravel development):
- ✅ **Laravel ecosystem**: Laravel, Inertia.js, TailwindCSS, Pest, Spatie packages
- ✅ **Application context**: database-schema, application-info, list-routes
- ✅ **Development & debugging**: tinker, last-error, read-log-entries
- ✅ **Laravel-specific patterns**: Eloquent, authentication, queues

#### Context7 (COMPLEMENTARY for general documentation):
- ✅ **VRISTO template**: HTML/CSS/JS integration patterns
- ✅ **Frontend frameworks**: Vue.js, TypeScript, browser APIs
- ✅ **Testing tools**: Playwright browser automation
- ✅ **Third-party services**: External APIs, monitoring tools
- ✅ **Non-Laravel technologies**: Real-time docs for any library

#### Filesystem MCP (ESSENTIAL for file operations):
- ✅ **VRISTO assets**: Extract, organize, and manage template files
- ✅ **Log analysis**: Read SSL monitoring logs, error files, debug output
- ✅ **Configuration**: Read/write config files, environment settings
- ✅ **Asset management**: Organize CSS, JS, images for VRISTO integration
- ✅ **Development files**: Manage documentation, scripts, deployment files

#### Git MCP (CRITICAL for Git Flow workflow):
- ✅ **Repository management**: Create/switch/merge feature branches
- ✅ **Git Flow operations**: Release management, hotfix branches
- ✅ **Code history**: Analyze SSL monitoring feature evolution
- ✅ **Collaboration**: PR creation, code review assistance
- ✅ **Release management**: Tag creation, changelog generation

---

## 🛠️ Laravel Boost MCP Integration

### Overview
**Laravel Boost** is the official Laravel MCP server that accelerates AI-assisted development by providing essential context and specialized tools. **ALWAYS prefer Laravel Boost tools over manual alternatives** when available.

**Key Benefits**:
- 17,000+ pieces of version-specific Laravel ecosystem documentation
- 15+ specialized tools for Laravel development
- Real-time application context and debugging
- Direct integration with installed packages and versions

### 🔥 Priority Usage Guidelines

**MANDATORY**: Use Laravel Boost tools for these scenarios instead of manual commands:

#### Application Discovery (ALWAYS use these first)
```bash
# ❌ Don't: Manual investigation
# ✅ Do: Use Laravel Boost
application-info           # Get comprehensive app info (PHP, Laravel, packages, models)
list-artisan-commands     # List available Artisan commands
list-routes              # List application routes with filtering
list-available-config-keys # List all configuration keys
```

#### Documentation & Learning (CRITICAL for SSL Monitor v3)
```bash
# ❌ Don't: Generic web search or outdated docs
# ✅ Do: Use version-specific documentation
search-docs              # Search 17,000+ Laravel ecosystem docs specific to your versions
                        # Perfect for Laravel, Livewire, Inertia, Pest, TailwindCSS, etc.
```

#### Database Operations (Preferred over raw queries)
```bash
# ❌ Don't: Manual SQL or tinker for simple queries
# ✅ Do: Use Boost database tools
database-schema          # Read complete database structure
database-query          # Execute read-only SQL queries
database-connections    # List available database connections
```

#### Development & Debugging (Essential for troubleshooting)
```bash
# ❌ Don't: Manual log parsing or file inspection
# ✅ Do: Use Boost debugging tools
tinker                  # Execute PHP code in Laravel context (better than artisan tinker)
last-error             # Get details of most recent application error
read-log-entries       # Read application log entries with proper parsing
browser-logs           # Read browser console logs for frontend debugging
```

#### Configuration Management
```bash
# ❌ Don't: Manual .env file inspection
# ✅ Do: Use Boost configuration tools
get-config             # Get config values using dot notation
list-available-env-vars # List environment variable names
get-absolute-url       # Get absolute URLs for paths or named routes
```

### 🎯 Tool Categories & Use Cases

#### 1. Application Context & Discovery
**When starting any development task:**
- `application-info`: First command to run - understand the complete application context
- `list-routes`: Before creating new routes or understanding existing API structure
- `list-artisan-commands`: Before running any artisan command to see available options

#### 2. Database Operations
**For SSL Monitor v3 database work:**
- `database-schema`: Before creating migrations - understand existing table structure
- `database-query`: For investigating data issues or testing queries
- Perfect for SSL certificate and website monitoring data analysis

#### 3. Development & Debugging
**During TDD and feature development:**
- `tinker`: Test code snippets, debug Eloquent relationships, verify SSL certificate parsing
- `last-error`: When tests fail or application errors occur
- `browser-logs`: Debug VRISTO template integration and Vue.js components
- `read-log-entries`: Investigate SSL monitoring job failures or uptime check issues

#### 4. Documentation & Learning (SSL Monitor v3 Focus)
**Essential for our technology stack:**
```bash
# Search for SSL Monitor v3 technologies
search-docs ["ssl certificate validation", "laravel jobs"]
search-docs ["vue inertia", "livewire integration"]
search-docs ["pest testing", "browser testing"]
search-docs ["tailwind components", "responsive design"]
search-docs ["laravel sanctum", "spa authentication"]
```

#### 5. Configuration & Environment
**For SSL Monitor v3 setup and deployment:**
- `get-config`: Verify SSL monitoring settings, notification preferences
- `list-available-env-vars`: Ensure all required environment variables are documented
- `get-absolute-url`: Generate proper URLs for SSL certificate validation endpoints

### 🔄 Integration with Development Workflows

#### TDD Development Process
```bash
1. application-info          # Understand current application state
2. search-docs ["pest testing", "feature testing"]  # Get testing guidance
3. database-schema          # Understand data structure for new features
4. tinker                   # Test code logic before writing tests
5. last-error              # Debug failing tests
6. browser-logs            # Debug frontend integration issues
```

#### SSL Certificate Monitoring Feature Development
```bash
1. search-docs ["ssl certificate", "guzzle http", "job queues"]
2. database-schema         # Review websites and ssl_certificates tables
3. tinker                  # Test SSL certificate parsing logic
4. list-routes            # Understand existing API endpoints
5. read-log-entries       # Monitor SSL check job execution
```

#### VRISTO Template Integration
```bash
1. search-docs ["vue components", "inertia layouts"]
2. browser-logs           # Debug Alpine.js + Vue integration issues
3. get-absolute-url       # Generate proper asset URLs for VRISTO components
4. tinker                 # Test data preparation for Vue components
```

### 🚨 Critical Usage Rules

1. **Documentation First**: ALWAYS use `search-docs` before implementing any Laravel ecosystem feature
2. **Context Before Coding**: Run `application-info` at the start of each development session
3. **Debug with Boost**: Use `last-error`, `read-log-entries`, and `browser-logs` instead of manual log inspection
4. **Database Discovery**: Use `database-schema` before creating migrations or understanding relationships
5. **Tinker Over Artisan**: Prefer `tinker` tool over `./vendor/bin/sail artisan tinker` for better integration

### 📚 Example Usage Scenarios

#### Starting SSL Certificate Feature Development
```bash
# 1. Get application context
application-info

# 2. Search for SSL-related documentation
search-docs ["ssl certificate validation", "spatie ssl certificate", "laravel jobs"]

# 3. Understand database structure
database-schema

# 4. Test SSL certificate parsing logic
tinker
# Code: $cert = \Spatie\SslCertificate\SslCertificate::createForHostName('github.com')

# 5. Check existing routes
list-routes --path=ssl
```

#### Debugging Failed Tests
```bash
# 1. Get last error details
last-error

# 2. Read recent log entries
read-log-entries --entries=20

# 3. Check browser console for frontend issues
browser-logs --entries=10

# 4. Test problematic code in isolation
tinker
```

#### VRISTO Integration Issues
```bash
# 1. Search for Vue/Inertia documentation
search-docs ["inertia vue", "vue components", "alpine vue"]

# 2. Check browser console for JavaScript errors
browser-logs --entries=20

# 3. Get proper URLs for assets
get-absolute-url --path="/assets/vristo/css/app.css"

# 4. Test component data preparation
tinker
```

---

## 🌐 Context7 MCP Integration

### Overview
**Context7** provides real-time, up-to-date documentation for any library or framework. It complements Laravel Boost by covering technologies outside the Laravel ecosystem.

**Key Benefits**:
- Real-time documentation fetching from official sources
- Clean, version-specific code examples
- Semantic search with proprietary ranking algorithm
- Covers ALL programming libraries and frameworks

### 🎯 Context7 Usage Guidelines

**CRITICAL for SSL Monitor v3**: Use Context7 for non-Laravel technologies

#### VRISTO Template Integration (PRIMARY Use Case)
```bash
# VRISTO-specific documentation (not covered by Laravel Boost)
use context7: "VRISTO admin template Alpine.js integration with Vue.js"
use context7: "VRISTO dashboard layout responsive design patterns"
use context7: "VRISTO sidebar navigation mobile optimization"
use context7: "VRISTO dark mode toggle implementation"
use context7: "VRISTO chart components integration with Vue 3"
```

#### Frontend Development Enhancement
```bash
# Vue.js comprehensive documentation
use context7: "Vue 3 composition API with TypeScript best practices"
use context7: "Vue component testing modern testing strategies"
use context7: "Vue reactivity system performance optimization"

# Browser APIs for SSL monitoring
use context7: "JavaScript SSL certificate validation browser APIs"
use context7: "WebSocket real-time notifications implementation"
use context7: "Web Workers background SSL certificate checking"
```

#### Testing & Browser Automation
```bash
# Playwright for comprehensive browser testing
use context7: "Playwright screenshot testing responsive layouts"
use context7: "Playwright SSL certificate validation testing"
use context7: "Playwright visual regression testing setup"
use context7: "Playwright mobile device testing automation"
```

#### Third-Party Integrations
```bash
# External service integrations
use context7: "Email service API integration Node.js"
use context7: "SMS notification service webhook implementation"
use context7: "Monitoring service API real-time data fetching"
use context7: "Redis pub/sub real-time notifications"
```

### 🔄 Context7 + Laravel Boost Workflow

#### Combined Development Process
```bash
# 1. Start with Laravel Boost for application context
application-info
database-schema
list-routes

# 2. Use Laravel Boost for Laravel ecosystem features
search-docs ["inertia forms", "laravel jobs", "pest testing"]

# 3. Use Context7 for non-Laravel technologies
use context7: "VRISTO template Vue.js integration patterns"
use context7: "Playwright browser testing SSL certificates"

# 4. Debug with both servers
# Laravel Boost for backend issues:
last-error
tinker

# Context7 for frontend/integration issues:
use context7: "Vue.js debugging reactive data issues"
use context7: "Alpine.js Vue component integration troubleshooting"
```

### 🎨 SSL Monitor v3 Specific Use Cases

#### VRISTO Dashboard Development
```bash
# Step 1: Laravel Boost for backend data
database-query "SELECT * FROM websites WHERE ssl_status = 'expiring'"

# Step 2: Context7 for VRISTO UI implementation
use context7: "VRISTO dashboard cards layout responsive grid"
use context7: "VRISTO chart components SSL certificate expiry visualization"

# Step 3: Context7 for Vue integration
use context7: "Vue 3 props data binding VRISTO components"
```

#### SSL Certificate Monitoring Feature
```bash
# Step 1: Laravel Boost for Laravel-specific SSL package
search-docs ["spatie ssl certificate", "laravel jobs background processing"]

# Step 2: Context7 for browser-side SSL validation
use context7: "JavaScript SSL certificate chain validation"
use context7: "WebCrypto API certificate verification browser"

# Step 3: Context7 for real-time notifications
use context7: "WebSocket real-time SSL certificate alerts"
```

#### Mobile-Responsive Testing
```bash
# Step 1: Laravel Boost for backend API testing
tinker
# Test API endpoints: \App\Models\Website::first()->toJson()

# Step 2: Context7 for responsive testing
use context7: "Playwright mobile device SSL monitoring dashboard testing"
use context7: "VRISTO responsive breakpoints mobile navigation"
```

### 🔧 Context7 Installation & Setup

#### Installation
```bash
# Install Context7 MCP server
npm install -g @upstash/context7-mcp

# Optional: Get API key for enhanced rate limits
# Visit: https://context7.com/dashboard
```

#### Configuration
```json
{
  "mcpServers": {
    "context7": {
      "command": "npx",
      "args": ["@upstash/context7-mcp"],
      "env": {
        "CONTEXT7_API_KEY": "your-api-key-here"
      }
    }
  }
}
```

### 🚨 Context7 Best Practices

1. **Use Specific Library Names**: Always specify exact library/framework names
2. **Version-Specific Queries**: Include version numbers when possible
3. **Combine with Laravel Boost**: Use both servers in the same development session
4. **VRISTO Priority**: Always use Context7 for VRISTO template questions
5. **Real-time Updates**: Context7 provides current documentation, avoiding outdated examples

### 📚 Example Combined Workflow

#### Implementing SSL Certificate Dashboard Widget
```bash
# 1. Laravel Boost: Understand current data structure
database-schema
application-info

# 2. Laravel Boost: Get SSL-related Laravel ecosystem docs
search-docs ["spatie ssl certificate", "laravel carbon dates"]

# 3. Context7: Get VRISTO widget implementation
use context7: "VRISTO dashboard widget card SSL certificate status display"

# 4. Context7: Vue.js integration patterns
use context7: "Vue 3 computed properties SSL certificate expiry calculations"

# 5. Laravel Boost: Debug backend logic
tinker
# Test: \Spatie\SslCertificate\SslCertificate::createForHostName('example.com')

# 6. Context7: Frontend debugging
use context7: "Vue.js reactive data debugging SSL certificate status"
```

---

## 📁 Filesystem MCP Integration

### Overview
**Filesystem MCP** provides secure file operations with configurable access controls. Essential for VRISTO template integration, log analysis, and configuration management.

**Key Benefits**:
- Secure file operations with permission controls
- Direct file manipulation for VRISTO assets
- Log file analysis for SSL monitoring debugging
- Configuration file management

### 🎯 Filesystem MCP Usage Guidelines

**CRITICAL for SSL Monitor v3**: Use Filesystem MCP for all file operations

#### VRISTO Template Integration (PRIMARY Use Case)
```bash
# Extract and organize VRISTO template assets
filesystem-mcp: read-directory vristo-html-starter/
filesystem-mcp: copy-file vristo-html-starter/assets/css/app.css resources/css/vristo-app.css
filesystem-mcp: create-directory resources/js/vristo/
filesystem-mcp: copy-files vristo-html-starter/assets/js/ resources/js/vristo/

# Organize VRISTO components
filesystem-mcp: read-file vristo-html-main/dashboard.html
filesystem-mcp: extract-html-sections vristo-html-main/dashboard.html resources/views/vristo/
```

#### SSL Monitoring Log Analysis
```bash
# Read SSL monitoring logs
filesystem-mcp: read-file storage/logs/ssl-monitoring.log
filesystem-mcp: tail-file storage/logs/laravel.log 100
filesystem-mcp: search-in-file storage/logs/ "SSL certificate error"

# Analyze error patterns
filesystem-mcp: grep-files storage/logs/ "certificate.*expired"
filesystem-mcp: count-lines-matching storage/logs/ssl-monitoring.log "failed"
```

#### Configuration Management
```bash
# Configuration file operations
filesystem-mcp: read-file .env
filesystem-mcp: read-file config/ssl-monitoring.php
filesystem-mcp: backup-file .env .env.backup
filesystem-mcp: create-file config/vristo.php "<?php return [];"

# Environment management
filesystem-mcp: append-to-file .env "SSL_CHECK_INTERVAL=3600"
filesystem-mcp: update-env-variable .env "APP_NAME" "SSL Monitor v3"
```

#### Development Asset Management
```bash
# Asset compilation and organization
filesystem-mcp: list-files resources/css/
filesystem-mcp: concat-files resources/css/ public/css/app.css
filesystem-mcp: optimize-images resources/images/

# Documentation management
filesystem-mcp: create-directory docs/ssl-monitoring/
filesystem-mcp: write-file docs/ssl-monitoring/api.md "# SSL Monitoring API"
```

### 🔧 Filesystem MCP Installation & Setup (Docker/Sail Aware)

#### Installation
```bash
# Install Filesystem MCP server on HOST system
npm install -g @modelcontextprotocol/server-filesystem
```

#### Configuration (Docker/Sail Project)
```json
{
  "mcpServers": {
    "filesystem": {
      "command": "npx",
      "args": [
        "-y",
        "@modelcontextprotocol/server-filesystem",
        "/absolute/path/to/ssl-monitor-v3"
      ],
      "env": {
        "FILESYSTEM_ALLOWED_PATHS": "/absolute/path/to/ssl-monitor-v3"
      }
    }
  }
}
```

#### Docker/Sail Considerations
```bash
# Important: Filesystem MCP runs on HOST system
# - Can access project files directly (same directory where you run ./vendor/bin/sail)
# - Paths should be absolute paths to your project directory
# - Works with Docker volumes since project files are mounted

# For SSL Monitor v3 file operations:
filesystem-mcp: read-file .env                    # Reads from HOST project directory
filesystem-mcp: tail-file storage/logs/laravel.log # Accesses Docker volume-mounted logs
filesystem-mcp: copy-files vristo-html-starter/   # Copies template files on HOST

# Note: Container-specific files (inside Docker) won't be accessible
# But Laravel project files are volume-mounted and accessible
```

---

## 🔀 Git MCP Integration

### Overview
**Git MCP** provides comprehensive Git repository management tools. Essential for Git Flow workflow, feature development, and release management.

**Key Benefits**:
- Direct Git operations from AI context
- Git Flow workflow automation
- Repository analysis and history tracking
- Collaborative development support

### 🎯 Git MCP Usage Guidelines

**CRITICAL for SSL Monitor v3**: Use Git MCP for all Git Flow operations

#### Git Flow Workflow Management
```bash
# Feature branch management
git-mcp: create-branch feature/ssl-monitoring-enhancement
git-mcp: switch-branch develop
git-mcp: merge-branch feature/ssl-monitoring-enhancement develop

# Release management
git-mcp: create-branch release/v3.1.0 develop
git-mcp: tag-release v3.1.0 "SSL Monitor v3.1.0 - Enhanced certificate validation"
git-mcp: merge-branch release/v3.1.0 main

# Hotfix management
git-mcp: create-branch hotfix/ssl-validation-bug main
git-mcp: merge-branch hotfix/ssl-validation-bug main
git-mcp: merge-branch hotfix/ssl-validation-bug develop
```

#### SSL Monitoring Feature Development
```bash
# Feature development workflow
git-mcp: status
git-mcp: add-files app/Services/SslCertificateChecker.php
git-mcp: commit "Implement enhanced SSL certificate validation logic"
git-mcp: push origin feature/ssl-monitoring-enhancement

# Code review and analysis
git-mcp: diff feature/ssl-monitoring-enhancement develop
git-mcp: log --oneline feature/ssl-monitoring-enhancement
git-mcp: show-changes HEAD~3..HEAD
```

#### Repository Analysis
```bash
# SSL monitoring feature history
git-mcp: log --grep="SSL" --oneline
git-mcp: blame app/Services/SslCertificateChecker.php
git-mcp: search-commits "certificate validation"

# Release preparation
git-mcp: generate-changelog v3.0.0..HEAD
git-mcp: list-contributors --since="2024-01-01"
git-mcp: statistics --since="last-month"
```

#### Collaboration Support
```bash
# Team collaboration
git-mcp: fetch-all
git-mcp: list-remote-branches
git-mcp: sync-branch develop origin/develop

# Conflict resolution
git-mcp: merge-conflicts
git-mcp: resolve-conflict app/Models/Website.php
git-mcp: continue-merge
```

### 🔧 Git MCP Installation & Setup (Docker/Sail Aware)

#### Installation
```bash
# Install Git MCP server on HOST system
npm install -g @modelcontextprotocol/server-git
```

#### Configuration (Docker/Sail Project)
```json
{
  "mcpServers": {
    "git": {
      "command": "npx",
      "args": [
        "-y",
        "@modelcontextprotocol/server-git",
        "/absolute/path/to/ssl-monitor-v3"
      ]
    }
  }
}
```

#### Docker/Sail Considerations
```bash
# Important: Git MCP runs on HOST system
# - Git repository exists on HOST (where you cloned it)
# - Git operations happen on HOST system, not inside containers
# - This is correct since Git repo is managed outside Docker

# For SSL Monitor v3 Git operations:
git-mcp: status                           # Checks HOST Git repository status
git-mcp: create-branch feature/new-ssl    # Creates branch on HOST
git-mcp: commit "Add SSL feature"         # Commits to HOST repository

# Note: Container doesn't need Git access for this setup
# All Git operations are performed on the HOST repository
# Laravel/PHP development happens inside container
# Version control happens outside container (standard practice)
```

#### Alternative: Container-based Git (if needed)
```bash
# If you prefer Git operations inside container:
# 1. Ensure Git is configured inside container
docker exec -it ssl-monitor-laravel.test-1 git config --global user.name "Your Name"
docker exec -it ssl-monitor-laravel.test-1 git config --global user.email "your@email.com"

# 2. Use container-based Git MCP (more complex setup)
# This is generally NOT recommended - HOST Git is simpler
```

## 🐳 Docker/Sail MCP Architecture Summary

### MCP Server Deployment Strategy
```
┌─────────────────────────────────────────────────────────────┐
│                    HOST SYSTEM                              │
│                                                             │
│  ┌─────────────────┐ ┌─────────────────┐ ┌─────────────────┐│
│  │   Context7 MCP  │ │ Filesystem MCP  │ │    Git MCP      ││
│  │  (External docs)│ │  (Project files)│ │ (Repository)    ││
│  └─────────────────┘ └─────────────────┘ └─────────────────┘│
│                                                             │
│  ┌─────────────────────────────────────────────────────────┐│
│  │             Project Directory                           ││
│  │  ├── app/          ← Filesystem MCP can access         ││
│  │  ├── resources/    ← VRISTO template integration       ││
│  │  ├── storage/      ← Log file analysis                 ││
│  │  ├── .git/         ← Git MCP operations                ││
│  │  └── .env          ← Configuration management          ││
│  └─────────────────────────────────────────────────────────┘│
│                            │                               │
│                            │ Docker Volume Mount           │
│                            ▼                               │
├─────────────────────────────────────────────────────────────┤
│                    DOCKER CONTAINER                        │
│                                                             │
│  ┌─────────────────────────────────────────────────────────┐│
│  │         Laravel Application                             ││
│  │  ┌─────────────────┐                                   ││
│  │  │  Laravel Boost  │ ← Runs inside container           ││
│  │  │    MCP Server   │   (via docker exec command)      ││
│  │  └─────────────────┘                                   ││
│  │                                                         ││
│  │  /var/www/html/ ← Volume mounted from HOST             ││
│  └─────────────────────────────────────────────────────────┘│
└─────────────────────────────────────────────────────────────┘
```

### MCP Server Execution Context
| MCP Server | Execution Location | Access Method | Configuration Notes |
|------------|-------------------|---------------|-------------------|
| **Laravel Boost** | Docker Container | `docker exec` command | Requires container execution |
| **Context7** | Host System | Direct npm install | External API access |
| **Filesystem MCP** | Host System | Volume-mounted files | Access project directory |
| **Git MCP** | Host System | Direct repository access | Standard Git workflow |

### Docker/Sail Setup Commands
```bash
# 1. Laravel Boost (CRITICAL: Must run in container)
./vendor/bin/sail composer require laravel/boost --dev
./vendor/bin/sail artisan boost:install
claude mcp add laravel-boost --scope local -- docker exec -i ssl-monitor-laravel.test-1 php /var/www/html/artisan boost:mcp .

# 2. Context7 (HOST system - external documentation)
npm install -g @upstash/context7-mcp

# 3. Filesystem MCP (HOST system - project file access)
npm install -g @modelcontextprotocol/server-filesystem

# 4. Git MCP (HOST system - repository management)
npm install -g @modelcontextprotocol/server-git
```

### 🔄 Four-Server Combined Workflow

#### Complete SSL Feature Development
```bash
# 1. Git MCP: Create feature branch
git-mcp: create-branch feature/advanced-ssl-validation develop

# 2. Laravel Boost: Understand application context
application-info
database-schema

# 3. Context7: Research implementation approaches
use context7: "SSL certificate chain validation JavaScript browser"

# 4. Filesystem MCP: Organize VRISTO assets for new UI
filesystem-mcp: copy-files vristo-html-main/forms/ resources/views/ssl/

# 5. Laravel Boost: Development and debugging
search-docs ["spatie ssl certificate", "laravel jobs"]
tinker

# 6. Filesystem MCP: Analyze logs during development
filesystem-mcp: tail-file storage/logs/ssl-monitoring.log 50

# 7. Git MCP: Commit and push changes
git-mcp: add-all
git-mcp: commit "Implement advanced SSL certificate chain validation with VRISTO UI"
git-mcp: push origin feature/advanced-ssl-validation
```

---

## 🚀 Getting Started Checklist

When starting development:

1. **Setup MCP Servers**: Install and configure all four MCP servers (Docker/Sail aware)
   ```bash
   # Laravel Boost (Laravel ecosystem) - INSIDE container
   composer require laravel/boost --dev
   ./vendor/bin/sail artisan boost:install
   claude mcp add laravel-boost --scope local -- docker exec -i ssl-monitor-laravel.test-1 php /var/www/html/artisan boost:mcp .

   # Context7 (General documentation) - HOST system
   npm install -g @upstash/context7-mcp

   # Filesystem MCP (File operations) - HOST system with volume mapping
   npm install -g @modelcontextprotocol/server-filesystem

   # Git MCP (Repository management) - HOST system (Git repo accessible)
   npm install -g @modelcontextprotocol/server-git
   ```
2. **Initialize Application Context**: Run `application-info` to understand the current state
3. **Read Documentation**: Start with [README.md](README.md) for project overview
4. **Understand Architecture**: Review [TECH_STACK.md](TECH_STACK.md) for technical decisions
5. **Plan Development**: Follow [PROJECT_PLAN.md](PROJECT_PLAN.md) phases
6. **Setup Workflow**: Implement [GIT_WORKFLOW.md](GIT_WORKFLOW.md) branching
7. **UI Development**: Follow [VRISTO_INTEGRATION.md](VRISTO_INTEGRATION.md) guidelines
8. **Backend Development**: Reference [API_SPECIFICATION.md](API_SPECIFICATION.md)
9. **Testing Implementation**: Use [TESTING_STRATEGY.md](TESTING_STRATEGY.md) approach

### Next Steps
1. Create new Laravel 12 project with Vue 3 + Inertia.js
2. Integrate VRISTO template assets and components
3. Implement authentication system with VRISTO login pages
4. Build dashboard UI using VRISTO layout components
5. Develop SSL monitoring features with comprehensive testing

---

---

## ⚡ Claude Code Best Practices Integration

### Slash Commands for SSL Monitor v3
The project includes professional development slash commands in `.claude/commands/`:

**Quick Access Commands**:
- **`/prime`** - Essential project onboarding and setup guide
- **`/ssl-feature [name] [description]`** - Complete SSL feature development workflow
- **`/vristo-ui [component] [page]`** - VRISTO template integration assistant
- **`/debug-ssl [issue]`** - Comprehensive SSL debugging workflow

### Development Workflow Patterns
Following Claude Code best practices for SSL Monitor v3:

#### 1. **Explore, Plan, Code, Commit**
```bash
# Explore: Use MCP servers for context discovery
application-info && database-schema && git-mcp: status

# Plan: Use /prime or specific feature commands
/ssl-feature certificate-validation "Enhanced SSL certificate validation"

# Code: Follow TDD methodology with comprehensive testing
# Commit: Use Git MCP for professional Git Flow workflow
```

#### 2. **Test-Driven Development**
```bash
# Red: Write failing tests first
./vendor/bin/sail artisan make:test --pest SslFeatureTest
./vendor/bin/sail artisan test --filter=SslFeatureTest  # Should fail

# Green: Implement minimal code to pass tests
# Refactor: Improve code while maintaining test coverage
```

#### 3. **Visual Iteration with VRISTO**
```bash
# Take screenshots of current UI state
# Use /vristo-ui command for template integration
# Implement VRISTO components with Vue.js
# Iterate until design matches VRISTO standards
```

### File Organization (Claude Code Standards)
```
ssl-monitor-v3/
├── .claude/
│   └── commands/           # Custom slash commands
├── v3/                     # Comprehensive documentation
├── CLAUDE.md              # This master reference file
├── app/                   # Laravel application
├── resources/             # Vue.js + VRISTO assets
└── tests/                 # Comprehensive test suite
```

### Quality Standards Integration
- **Specific Instructions**: All commands provide detailed, step-by-step workflows
- **Context Awareness**: Commands leverage all four MCP servers for comprehensive understanding
- **Iterative Development**: Commands support refinement and iteration
- **Professional Standards**: Enterprise-grade SSL monitoring with VRISTO UI

### MCP Server Best Practices
- **Laravel Boost**: Always start with `application-info` for context
- **Context7**: Use for VRISTO and non-Laravel technology research
- **Filesystem MCP**: Leverage for log analysis and asset management
- **Git MCP**: Professional Git Flow workflow automation

---

**Remember**: This project combines professional-grade SSL monitoring functionality with a modern, polished UI. Every feature should meet enterprise standards for reliability, performance, and user experience.