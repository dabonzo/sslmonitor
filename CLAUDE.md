# SSL Monitor v4 - AI Development Guidelines

## 📖 Master Documentation Reference

This file serves as the central navigation hub for AI-assisted development of SSL Monitor v4. All comprehensive implementation guidance is organized below for optimal development workflow.

### 📋 SSL Monitor v4 Documentation Index

**🚀 Core Implementation Documents:**
- **[SSL_MONITOR_V4_IMPLEMENTATION_PLAN.md](SSL_MONITOR_V4_IMPLEMENTATION_PLAN.md)** - Complete 8-week development plan with proven backend reuse
- **[MIGRATION_FROM_V3.md](MIGRATION_FROM_V3.md)** - Detailed strategy for reusing 90% of backend from old_docs
- **[V4_TECHNICAL_SPECIFICATIONS.md](V4_TECHNICAL_SPECIFICATIONS.md)** - Models, services, API endpoints, and database schema
- **[V4_DEVELOPMENT_WORKFLOW.md](V4_DEVELOPMENT_WORKFLOW.md)** - TDD process with Pest v4 + VRISTO integration
- **[DEVELOPMENT_PROGRESS.md](DEVELOPMENT_PROGRESS.md)** - Real-time phase tracking and milestone completion

**📚 Supporting Documentation:**
- **[README.md](README.md)** - SSL Monitor application overview and features
- **[PROJECT_PLAN.md](PROJECT_PLAN.md)** - Original development phases and milestones
- **[TECH_STACK.md](TECH_STACK.md)** - Technology stack decisions and architecture
- **[GIT_WORKFLOW.md](GIT_WORKFLOW.md)** - Git Flow branching strategy

**🔍 Complete Reference Implementation:**
- **[old_docs/](old_docs/)** - **COMPLETE SSL Monitor v3 Application** (full Laravel app)
  - **[old_docs/app/](old_docs/app/)** - Complete application source with proven Services, Models, Jobs
  - **[old_docs/tests/](old_docs/tests/)** - 115+ comprehensive tests (Feature/Unit/Browser)
  - **[old_docs/database/](old_docs/database/)** - Production-tested migrations, factories, seeders
  - **[old_docs/docs/](old_docs/docs/)** - Complete documentation (user guide, admin guide, developer guide)
  - **All Services Available**: SslCertificateChecker, Jobs, Models, and complete architecture

### ⚡ Claude Code Slash Commands
- **[.claude/commands/](.claude/commands/)** - Professional development slash commands
  - **`/prime`** - Project primer and quick setup
  - **`/ssl-feature`** - SSL monitoring feature development with TDD
  - **`/vristo-ui`** - VRISTO template integration workflows
  - **`/debug-ssl`** - Comprehensive SSL debugging assistant

---

## 🎯 SSL Monitor v4 Overview

### Project Vision
**SSL Monitor v4** combines proven backend architecture from v3 (enhanced from `old_docs/`) with a modern Vue 3 + Inertia.js + professional frontend. **Phase 1 Complete** with hybrid Spatie monitoring integration. **Phase 2 Ready** for SSL functionality implementation.

**Core Mission**: Enterprise-grade SSL certificate monitoring with automated checks, professional notifications, and team collaboration features.

### Architecture Strategy (✅ Phase 1 Complete, 🚀 Phase 2 Ready)
- ✅ **Enhanced Backend Foundation**: Models, Services, Jobs from old_docs + Spatie integration
- ✅ **Hybrid Monitoring**: Website model + Spatie Laravel Uptime Monitor seamless integration
- ✅ **Plugin-Ready Design**: Enhanced models with v4 plugin architecture
- ✅ **Modern Frontend Foundation**: Vue 3 + Inertia.js + TypeScript + shadcn/ui components
- ✅ **Production Testing**: Pest v4 with comprehensive SSL monitoring validation
- 🚀 **SSL Functionality (Phase 2)**: Connect real SSL backend to professional frontend

### Current Status: Phase 2 Ready - SSL Implementation 🚀
- ✅ **Complete Backend**: Production-ready SSL monitoring with Spatie integration
- ✅ **Professional Frontend**: Modern Vue 3 + TypeScript + responsive UI components
- ✅ **Authentication System**: Complete auth pages, 2FA, settings, user management
- ✅ **Dashboard Framework**: Professional layout with theme management and navigation
- 🎯 **Phase 2 Focus**: Implement SSL functionality using existing excellent foundation

### Technology Stack
- **Backend**: Laravel 12 + PHP 8.4 + MariaDB + Redis + Proven Services
- **Frontend**: Vue 3 + Inertia.js + VRISTO Template + TailwindCSS v4
- **Testing**: Pest v4 Browser Tests (115+ tests adapted from old_docs)
- **Development**: Laravel Sail + Git Flow + Laravel Pint

### Essential Commands
```bash
# Development
./vendor/bin/sail up -d && ./vendor/bin/sail npm run dev

# Testing
./vendor/bin/sail artisan test --filter=TestName

# Code quality
./vendor/bin/sail exec laravel.test ./vendor/bin/pint

# 🚨 CRITICAL: After ANY CSS changes - ALWAYS run:
./vendor/bin/sail artisan cache:clear && ./vendor/bin/sail artisan config:clear && ./vendor/bin/sail artisan view:clear && ./vendor/bin/sail artisan route:clear
# Then restart Vite: ./vendor/bin/sail npm run dev
```

### Critical Development Rules
1. **Always read referenced documentation** before starting any feature
2. **Follow MCP integration strategy** for comprehensive development support
3. **Use TDD methodology** - write tests first, implement features second
4. **Follow Git Flow workflow** for professional version control
5. **NO CLAUDE BRANDING** in commits, comments, or code

---

## 🛠️ MCP Server Integration (Quick Reference)

**Four-Server Ecosystem:**
- **🚀 Laravel Boost** (Container): Laravel ecosystem, debugging, application context
- **🌐 Context7** (Host): VRISTO template, Vue.js, general documentation
- **📁 Filesystem MCP** (Host): File operations, log analysis, asset management
- **🔀 Git MCP** (Host): Repository management, Git Flow workflow

**Quick Setup:**
```bash
# Laravel Boost (CRITICAL: Must run in container)
./vendor/bin/sail composer require laravel/boost --dev
./vendor/bin/sail artisan boost:install

# Host system MCP servers
npm install -g @upstash/context7-mcp @modelcontextprotocol/server-filesystem @modelcontextprotocol/server-git
```

**Essential Workflow:**
```bash
# 1. Context discovery
application-info && database-schema

# 2. Research documentation
search-docs ["ssl certificate", "laravel jobs"]    # Laravel Boost
use context7: "VRISTO dashboard patterns"          # Context7

# 3. Development with debugging
tinker                                             # Laravel Boost
filesystem-mcp: tail-file storage/logs/laravel.log # Filesystem MCP

# 4. Version control
git-mcp: create-branch feature/ssl-enhancement    # Git MCP
```

**Detailed Usage**: See [docs/MCP_INTEGRATION.md](docs/MCP_INTEGRATION.md) and [docs/LARAVEL_BOOST_GUIDE.md](docs/LARAVEL_BOOST_GUIDE.md)

---

## 🚀 SSL Monitor v4 Getting Started Workflow

1. **Read Implementation Plan**: Start with [SSL_MONITOR_V4_IMPLEMENTATION_PLAN.md](SSL_MONITOR_V4_IMPLEMENTATION_PLAN.md) for complete 8-week roadmap
2. **Study Migration Strategy**: Review [MIGRATION_FROM_V3.md](MIGRATION_FROM_V3.md) to understand 90% backend reuse approach
3. **MCP Setup**: Configure all four MCP servers for comprehensive development support
4. **Context Discovery**: Run `application-info` and `database-schema` to understand current state
5. **Development Workflow**: Follow [V4_DEVELOPMENT_WORKFLOW.md](V4_DEVELOPMENT_WORKFLOW.md) for TDD + VRISTO integration
6. **Technical Reference**: Use [V4_TECHNICAL_SPECIFICATIONS.md](V4_TECHNICAL_SPECIFICATIONS.md) for models, services, and API details

### Current Development Phase
- **Status**: Ready to begin Phase 1 - Backend Foundation
- **Next Steps**: Database schema migration and model implementation from old_docs
- **Approach**: Copy proven components from old_docs, adapt for Vue.js frontend

### Development Priorities
1. **Phase 1 (Week 1-2)**: Database schema + Models + Services from old_docs (90% reusable)
2. **Phase 2 (Week 3)**: Inertia.js API controllers replacing Livewire components
3. **Phase 3 (Week 4-5)**: Vue 3 + VRISTO frontend with professional UI components
4. **Phase 4 (Week 6)**: Email configuration and advanced features
5. **Phase 5 (Week 7-8)**: Comprehensive testing and production preparation

**For implementation details, always reference the detailed SSL Monitor v4 documentation above.**

---

## 🚀 Phase 2: SSL Functionality Implementation Plan

### **Strategic Approach: Efficient Development with Slash Commands**
**Current Discovery**: Frontend already exceeds expectations with professional Vue 3 + TypeScript + shadcn/ui components. Phase 2 = **Connect real SSL backend to existing professional frontend**.

### **Development Workflow: Git + TDD with Pest**
```bash
# Standard workflow for each SSL feature:
1. git checkout -b feature/ssl-[feature-name]
2. Write failing Pest tests first (TDD approach)
3. Implement feature to pass tests
4. ./vendor/bin/sail exec laravel.test ./vendor/bin/pint (format)
5. git commit with descriptive message
6. Repeat until feature complete
```

### **🛠️ Strategic Slash Commands for SSL Development**

- **`/ssl-feature`** - Complete SSL feature development (TDD + implementation + testing)
- **`/ssl-controller`** - Create Inertia.js controllers with SSL-specific patterns
- **`/ssl-page`** - Create Vue.js pages using existing component patterns
- **`/ssl-test`** - SSL testing workflows and validation

### **📋 Phase 2 Implementation Timeline**

#### **Week 1: Core SSL API Layer**
**Target**: Replace Dashboard.vue mock data with real SSL monitoring

- **Day 1-2**: SSL Dashboard Controller - Real statistics and activity feed
- **Day 3-4**: Website Management Controller - CRUD with SSL integration
- **Day 5**: Integration Testing - Validate all API endpoints

#### **Week 2: SSL Management Interface**
**Target**: Complete SSL website management using existing UI patterns

- **Day 1-2**: SSL Website List Page - Table with real SSL status data
- **Day 3-4**: Dashboard Enhancement - Connect to real SslDashboardController
- **Day 5**: Website Details Page - Certificate history and manual checks

#### **Week 3: Advanced Features & Polish**
**Target**: Production-ready SSL monitoring application

- **Day 1-2**: Notifications & Alerts - Email configuration and real-time updates
- **Day 3-4**: Bulk Operations & Reporting - Multi-select and export functionality
- **Day 5**: Performance & Polish - Loading states and mobile optimization

### **Expected Deliverables**
- ✅ **Real SSL Dashboard**: Connected to backend monitoring service
- ✅ **Complete Website Management**: CRUD with SSL integration
- ✅ **Professional UI**: Consistent with existing component patterns
- ✅ **Comprehensive Testing**: Pest coverage for all SSL features
- ✅ **Production Ready**: Performant, responsive, error-handled

---

## Laravel Boost Guidelines Integration

The following Laravel Boost guidelines are embedded for immediate reference:

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4.12
- inertiajs/inertia-laravel (INERTIA) - v2
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/wayfinder (WAYFINDER) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- @inertiajs/vue3 (INERTIA) - v2
- tailwindcss (TAILWINDCSS) - v4
- vue (VUE) - v3
- @laravel/vite-plugin-wayfinder (WAYFINDER) - v0
- eslint (ESLINT) - v9
- prettier (PRETTIER) - v3


## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure - don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.


=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double check the available parameters.

## URLs
- Whenever you share a project URL with the user you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain / IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The 'search-docs' tool is perfect for all Laravel related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel-ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries - package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll" - Words must be adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms


=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over comments. Never use comments within the code itself unless there is something _very_ complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.


=== inertia-laravel/core rules ===

## Inertia Core

- Inertia.js components should be placed in the `resources/js/Pages` directory unless specified differently in the JS bundler (vite.config.js).
- Use `Inertia::render()` for server-side routing instead of traditional Blade views.
- Use `search-docs` for accurate guidance on all things Inertia.

<code-snippet lang="php" name="Inertia::render Example">
// routes/web.php example
Route::get('/users', function () {
    return Inertia::render('Users/Index', [
        'users' => User::all()
    ]);
});
</code-snippet>


=== inertia-laravel/v2 rules ===

## Inertia v2

- Make use of all Inertia features from v1 & v2. Check the documentation before making any changes to ensure we are taking the correct approach.

### Inertia v2 New Features
- Polling
- Prefetching
- Deferred props
- Infinite scrolling using merging props and `WhenVisible`
- Lazy loading data on scroll

### Deferred Props & Empty States
- When using deferred props on the frontend, you should add a nice empty state with pulsing / animated skeleton.

### Inertia Form General Guidance
- The recommended way to build forms when using Inertia is with the `<Form>` component - a useful example is below. Use `search-docs` with a query of `form component` for guidance.
- Forms can also be built using the `useForm` helper for more programmatic control, or to follow existing conventions. Use `search-docs` with a query of `useForm helper` for guidance.
- `resetOnError`, `resetOnSuccess`, and `setDefaultsOnSuccess` are available on the `<Form>` component. Use `search-docs` with a query of 'form component resetting' for guidance.


=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] <name>` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.


=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure
- No middleware files in `app/Http/Middleware/`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- **No app\Console\Kernel.php** - use `bootstrap/app.php` or `routes/console.php` for console configuration.
- **Commands auto-register** - files in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.


=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.


=== pest/core rules ===

## Pest

### Testing
- If you need to verify a feature is working, write or update a Unit / Feature test.

### Pest Tests
- All tests must be written using Pest. Use `php artisan make:test --pest <name>`.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files - these are core to the application.
- Tests should test all of the happy paths, failure paths, and weird paths.
- Tests live in the `tests/Feature` and `tests/Unit` directories.
- Pest tests look and behave like this:
<code-snippet name="Basic Pest Test Example" lang="php">
it('is true', function () {
    expect(true)->toBeTrue();
});
</code-snippet>

### Running Tests
- Run the minimal number of tests using an appropriate filter before finalizing code edits.
- To run all tests: `php artisan test`.
- To run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --filter=testName` (recommended after making a change to a related file).
- When the tests relating to your changes are passing, ask the user if they would like to run the entire test suite to ensure everything is still passing.

### Pest Assertions
- When asserting status codes on a response, use the specific method like `assertForbidden` and `assertNotFound` instead of using `assertStatus(403)` or similar, e.g.:
<code-snippet name="Pest Example Asserting postJson Response" lang="php">
it('returns all', function () {
    $response = $this->postJson('/api/docs', []);

    $response->assertSuccessful();
});
</code-snippet>

### Mocking
- Mocking can be very helpful when appropriate.
- When mocking, you can use the `Pest\Laravel\mock` Pest function, but always import it via `use function Pest\Laravel\mock;` before using it. Alternatively, you can use `$this->mock()` if existing tests do.
- You can also create partial mocks using the same import or self method.

### Datasets
- Use datasets in Pest to simplify tests which have a lot of duplicated data. This is often the case when testing validation rules, so consider going with this solution when writing tests for validation rules.

<code-snippet name="Pest Dataset Example" lang="php">
it('has emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james' => 'james@laravel.com',
    'taylor' => 'taylor@laravel.com',
]);
</code-snippet>


=== pest/v4 rules ===

## Pest 4

- Pest v4 is a huge upgrade to Pest and offers: browser testing, smoke testing, visual regression testing, test sharding, and faster type coverage.
- Browser testing is incredibly powerful and useful for this project.
- Browser tests should live in `tests/Browser/`.
- Use the `search-docs` tool for detailed guidance on utilizing these features.

### Browser Testing
- You can use Laravel features like `Event::fake()`, `assertAuthenticated()`, and model factories within Pest v4 browser tests, as well as `RefreshDatabase` (when needed) to ensure a clean state for each test.
- Interact with the page (click, type, scroll, select, submit, drag-and-drop, touch gestures, etc.) when appropriate to complete the test.
- If requested, test on multiple browsers (Chrome, Firefox, Safari).
- If requested, test on different devices and viewports (like iPhone 14 Pro, tablets, or custom breakpoints).
- Switch color schemes (light/dark mode) when appropriate.
- Take screenshots or pause tests for debugging when appropriate.

### Example Tests

<code-snippet name="Pest Browser Test Example" lang="php">
it('may reset the password', function () {
    Notification::fake();

    $this->actingAs(User::factory()->create());

    $page = visit('/sign-in'); // Visit on a real browser...

    $page->assertSee('Sign In')
        ->assertNoJavascriptErrors() // or ->assertNoConsoleLogs()
        ->click('Forgot Password?')
        ->fill('email', 'nuno@laravel.com')
        ->click('Send Reset Link')
        ->assertSee('We have emailed your password reset link!')

    Notification::assertSent(ResetPassword::class);
});
</code-snippet>

<code-snippet name="Pest Smoke Testing Example" lang="php">
$pages = visit(['/', '/about', '/contact']);

$pages->assertNoJavascriptErrors()->assertNoConsoleLogs();
</code-snippet>


=== inertia-vue/core rules ===

## Inertia + Vue

- Vue components must have a single root element.
- Use `router.visit()` or `<Link>` for navigation instead of traditional links.

<code-snippet name="Inertia Client Navigation" lang="vue">

    import { Link } from '@inertiajs/vue3'
    <Link href="/">Home</Link>

</code-snippet>


=== inertia-vue/v2/forms rules ===

## Inertia + Vue Forms

<code-snippet name="`<Form>` Component Example" lang="vue">

<Form
    action="/users"
    method="post"
    #default="{
        errors,
        hasErrors,
        processing,
        progress,
        wasSuccessful,
        recentlySuccessful,
        setError,
        clearErrors,
        resetAndClearErrors,
        defaults,
        isDirty,
        reset,
        submit,
  }"
>
    <input type="text" name="name" />

    <div v-if="errors.name">
        {{ errors.name }}
    </div>

    <button type="submit" :disabled="processing">
        {{ processing ? 'Creating...' : 'Create User' }}
    </button>

    <div v-if="wasSuccessful">User created successfully!</div>
</Form>

</code-snippet>


=== tailwindcss/core rules ===

## Tailwind Core

- Use Tailwind CSS classes to style HTML, check and use existing tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc..)
- Think through class placement, order, priority, and defaults - remove redundant classes, add classes to parent or child carefully to limit repetition, group elements logically
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing, don't use margins.

    <code-snippet name="Valid Flex Gap Spacing Example" lang="html">
        <div class="flex gap-8">
            <div>Superior</div>
            <div>Michigan</div>
            <div>Erie</div>
        </div>
    </code-snippet>


### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.


=== tailwindcss/v4 rules ===

## Tailwind 4

- Always use Tailwind CSS v4 - do not use the deprecated utilities.
- `corePlugins` is not supported in Tailwind v4.
- In Tailwind v4, you import Tailwind using a regular CSS `@import` statement, not using the `@tailwind` directives used in v3:

<code-snippet name="Tailwind v4 Import Tailwind Diff" lang="diff"
   - @tailwind base;
   - @tailwind components;
   - @tailwind utilities;
   + @import "tailwindcss";
</code-snippet>


### Replaced Utilities
- Tailwind v4 removed deprecated utilities. Do not use the deprecated option - use the replacement.
- Opacity values are still numeric.

| Deprecated |	Replacement |
|------------+--------------|
| bg-opacity-* | bg-black/* |
| text-opacity-* | text-black/* |
| border-opacity-* | border-black/* |
| divide-opacity-* | divide-black/* |
| ring-opacity-* | ring-black/* |
| placeholder-opacity-* | placeholder-black/* |
| flex-shrink-* | shrink-* |
| flex-grow-* | grow-* |
| overflow-ellipsis | text-ellipsis |
| decoration-slice | box-decoration-slice |
| decoration-clone | box-decoration-clone |


=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test` with a specific filename or filter.
</laravel-boost-guidelines>