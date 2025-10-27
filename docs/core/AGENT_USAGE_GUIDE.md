# SSL Monitor v4 - Agent Usage Guide

**A comprehensive guide to using specialized AI agents for efficient development workflows**

## Table of Contents
- [What Are Agents?](#what-are-agents)
- [Available Agents](#available-agents)
- [How to Use Agents](#how-to-use-agents)
- [Agent Selection Guide](#agent-selection-guide)
- [Common Workflows](#common-workflows)
- [Best Practices](#best-practices)
- [Troubleshooting](#troubleshooting)

---

## What Are Agents?

Agents are specialized AI assistants configured with:
- **Focused expertise** in specific areas (testing, Vue, database, etc.)
- **Optimized context** by disabling unnecessary MCP tools
- **Domain knowledge** from project documentation
- **Best practices** built into their instructions

### Benefits of Using Agents

1. **Reduced Context Usage** - Main conversations use ~46% less context
2. **Specialized Knowledge** - Each agent is an expert in their domain
3. **Consistent Patterns** - Agents follow project standards automatically
4. **Faster Responses** - Less context = faster processing
5. **Parallel Work** - Multiple agents can work simultaneously

### How Agents Work

```
┌─────────────────────────────────────────────────────────┐
│              Main Conversation                          │
│  (Playwright disabled, full context available)         │
│                                                          │
│  "Use the testing-specialist agent to fix SSL tests"   │
└────────────────────┬────────────────────────────────────┘
                     │
                     ↓
┌─────────────────────────────────────────────────────────┐
│          testing-specialist Agent                       │
│  - Laravel Boost enabled                                │
│  - Testing documentation loaded                         │
│  - Mocking patterns configured                         │
│  - Performance standards enforced                       │
│                                                          │
│  Fixes SSL tests using MocksSslCertificateAnalysis     │
└─────────────────────────────────────────────────────────┘
```

---

## Available Agents

### 1. **browser-tester**
**Expertise**: Browser testing, UI verification, console error detection

**MCP Tools**: Playwright (enabled), Laravel Boost (enabled)

**Use When**:
- Testing UI interactions
- Checking for JavaScript errors
- Verifying page rendering
- Taking screenshots for debugging
- Analyzing console output

**Example**:
```
Use the browser-tester agent to check for console errors on the login page
```

---

### 2. **testing-specialist**
**Expertise**: Pest test writing, performance optimization, mocking strategies

**MCP Tools**: Laravel Boost (enabled)

**Critical Knowledge**:
- ALWAYS use `MocksSslCertificateAnalysis` for SSL tests
- ALWAYS use `MocksJavaScriptContentFetcher` for JS content tests
- ALWAYS use `App\Models\Monitor` (never Spatie's base model)
- Individual tests MUST complete in < 1 second
- NEVER make real network calls

**Performance Standards**:
- SSL Certificate Tests: < 1 second total
- JavaScript Content Tests: < 1 second total
- Full Suite (Parallel): < 20 seconds

**Use When**:
- Writing new tests
- Fixing failing tests
- Optimizing slow tests
- Implementing proper mocking
- Debugging parallel testing issues

**Example**:
```
Use the testing-specialist agent to write tests for the new alert configuration feature

Task the testing-specialist agent with optimizing the slow SSL certificate tests
```

---

### 3. **vue-component-builder**
**Expertise**: Vue 3 + TypeScript components, Inertia.js patterns, design system

**MCP Tools**: Laravel Boost (enabled), Context7 (enabled)

**Critical Knowledge**:
- ALWAYS use semantic color classes (`text-foreground`, `bg-card`)
- NEVER use manual dark mode classes (`dark:text-white`)
- Follow standard page template from CODING_GUIDE.md
- Use `DashboardLayout` for all pages
- Include TypeScript interfaces for all props

**Styling Rules**:
- `glass-card-strong` for cards
- `status-badge-*` for status indicators
- `input-styled` for form inputs
- Typography hierarchy for headings

**Use When**:
- Creating new Vue pages
- Building reusable components
- Implementing forms
- Adding new UI features
- Refactoring frontend code

**Example**:
```
Use the vue-component-builder agent to create a new analytics dashboard page

Task the vue-component-builder agent with building a modal component for bulk website transfer
```

---

### 4. **database-analyzer**
**Expertise**: Schema analysis, migrations, query optimization, model relationships

**MCP Tools**: Laravel Boost (enabled)

**Critical Settings**:
- ALWAYS use `database: "mariadb"` for schema inspection
- Production: MariaDB
- Testing: SQLite in-memory

**Available MCP Tools**:
- `mcp__laravel-boost__database-schema` - Inspect tables
- `mcp__laravel-boost__database-query` - Execute read-only SQL
- `mcp__laravel-boost__database-connections` - List connections
- `mcp__laravel-boost__tinker` - Test queries

**Use When**:
- Analyzing database schema
- Creating migrations
- Debugging database queries
- Optimizing query performance
- Inspecting model relationships
- Adding database indexes

**Example**:
```
Use the database-analyzer agent to inspect the monitors table and suggest performance improvements

Task the database-analyzer agent with creating a migration for the new alert_history table
```

---

### 5. **laravel-backend**
**Expertise**: Controllers, models, services, jobs, API development

**MCP Tools**: Laravel Boost (enabled), Context7 (enabled)

**Critical Rules**:
- ALWAYS use `./vendor/bin/sail` prefix for Laravel commands
- Follow Laravel & PHP Guidelines (Spatie standards)
- Use `App\Models\Monitor` (custom model), NEVER Spatie's base
- Prioritize `.env` variables over hardcoded values

**Code Standards**:
- Use typed properties (not docblocks)
- Use short nullable syntax: `?string`
- Always specify `void` return types
- Happy path last (handle errors first)
- Avoid `else` statements (use early returns)

**Use When**:
- Creating controllers
- Building service classes
- Implementing jobs
- Creating API endpoints
- Adding model methods
- Writing observers

**Example**:
```
Use the laravel-backend agent to create a new controller for managing alert configurations

Task the laravel-backend agent with implementing a service for bulk website imports
```

---

### 6. **styling-expert**
**Expertise**: CSS, design system adherence, semantic styling

**MCP Tools**: None (focused on styling only)

**Core Principles**:
- ALWAYS use semantic color classes
- NEVER use manual dark mode classes
- NEVER use hardcoded colors
- Test in both light and dark modes

**Semantic Color System**:
- `text-foreground` - Primary text
- `text-muted-foreground` - Secondary text
- `bg-card` - Card backgrounds
- `border-border` - Borders
- `bg-primary` - Primary/selected states

**Use When**:
- Fixing styling issues
- Ensuring design consistency
- Converting hardcoded colors to semantic classes
- Debugging dark mode issues
- Creating new component styles

**Example**:
```
Use the styling-expert agent to fix the dark mode issues on the alerts configuration page

Task the styling-expert agent with converting all hardcoded colors in the dashboard to semantic classes
```

---

### 7. **performance-optimizer**
**Expertise**: Performance analysis, test optimization, query optimization

**MCP Tools**: Laravel Boost (enabled)

**Performance Standards**:
- Individual Tests: < 1 second
- Full Test Suite (Parallel): < 20 seconds
- NO external service calls in tests

**Optimization Strategies**:
- Mock external services (99% faster for SSL tests)
- Use parallel testing
- Mock service dependencies for observers
- Use eager loading to prevent N+1 queries
- Add database indexes

**Use When**:
- Speeding up slow tests
- Optimizing database queries
- Analyzing application performance
- Reducing test suite execution time
- Profiling slow routes

**Example**:
```
Use the performance-optimizer agent to analyze why the test suite is taking 30 seconds

Task the performance-optimizer agent with optimizing the dashboard query performance
```

---

### 8. **deployment-helper**
**Expertise**: Production deployment, server management, CI/CD

**MCP Tools**: Laravel Boost (enabled)

**Production Environment**:
- Server: monitor.intermedien.at
- Deploy User: default_deploy@monitor.intermedien.at
- Web Directory: /var/www/monitor.intermedien.at/web
- PHP Version: 8.4

**Git Repositories**:
- Primary: git@github.com:dabonzo/sslmonitor.git (github)
- Secondary: gitea:bonzo/ssl-monitor.git (origin)

**Use When**:
- Deploying to production
- Managing systemd services
- Debugging server issues
- Checking deployment logs
- Rolling back deployments

**Example**:
```
Use the deployment-helper agent to deploy the latest changes to production

Task the deployment-helper agent with checking the status of the Horizon service on production
```

---

### 9. **documentation-writer**
**Expertise**: Technical documentation, inline code documentation, guide maintenance

**MCP Tools**: None (documentation focused)

**Core Responsibilities**:
- Create and maintain developer-focused documentation
- Write inline code comments following Laravel/PHP standards
- Document architectural decisions and patterns
- Provide practical, working code examples
- Maintain consistency with existing documentation structure

**Documentation Standards**:
- **Clarity First**: Active voice, concise, developer-friendly
- **Show, Don't Tell**: Complete working code examples
- **Context Matters**: Explain "why" behind decisions
- **Practical Focus**: Real-world usage over theory
- **Maintainability**: Structure for easy updates
- **NEVER create new docs** unless explicitly requested
- **ALWAYS update existing docs** when adding patterns

**Code Example Format**:
```markdown
❌ **Avoid:**
```[language]
// What NOT to do
```

✅ **Recommended:**
```[language]
// Correct approach
```

**Why this matters:** [Explain implications]
```

**Inline Documentation Rules** (Laravel/PHP):
- Use typed properties over docblocks when possible
- Always import classnames in docblocks (never fully qualified)
- Document iterables with generics: `@return Collection<int, User>`
- Include `@throws` tags for exceptions
- Document performance implications when relevant

**Quality Checklist**:
- ✅ All code examples are tested and working
- ✅ Formatting matches existing docs
- ✅ Technical accuracy verified
- ✅ Performance metrics are current
- ✅ Cross-references are valid
- ✅ "Last Updated" date included

**Use When**:
- Updating documentation after features/bug fixes
- Writing inline code comments and docblocks
- Creating API documentation
- Documenting new patterns and architectural decisions
- Adding performance metrics and optimization guides
- Updating README files
- Creating troubleshooting guides

**Example**:
```
Use the documentation-writer agent to update TESTING_INSIGHTS.md with the new mocking patterns we discovered

Task the documentation-writer agent with adding Laravel-standard docblocks to the MonitorIntegrationService

Use the documentation-writer agent to document the alert system refactoring in ALERT_SYSTEM_ARCHITECTURE.md
```

---

## How to Use Agents

### Basic Syntax

```
Use the [agent-name] agent to [task description]

Task the [agent-name] agent with [task description]

Ask the [agent-name] agent to [task description]
```

### Single Agent Usage

```
Use the testing-specialist agent to fix the failing SSL certificate tests
```

### Multiple Agents (Sequential)

```
First, use the database-analyzer agent to inspect the monitors table schema.
Then, use the laravel-backend agent to create a migration based on the analysis.
```

### Multiple Agents (Parallel)

**Note**: You can request multiple agents work in parallel for independent tasks

```
Use these agents in parallel:
1. testing-specialist: Write tests for the alert system
2. vue-component-builder: Create the alerts configuration page
3. styling-expert: Ensure all components use semantic color classes
```

---

## Agent Selection Guide

### Quick Reference Table

| Task Type | Best Agent | Alternative Agent |
|-----------|-----------|-------------------|
| **Testing** |
| Write new tests | testing-specialist | - |
| Fix failing tests | testing-specialist | - |
| Optimize test performance | performance-optimizer | testing-specialist |
| Browser testing | browser-tester | - |
| **Frontend** |
| Create Vue page | vue-component-builder | - |
| Build component | vue-component-builder | - |
| Fix styling | styling-expert | vue-component-builder |
| Check JavaScript errors | browser-tester | - |
| **Backend** |
| Create controller | laravel-backend | - |
| Create service | laravel-backend | - |
| Create job | laravel-backend | - |
| API development | laravel-backend | - |
| **Database** |
| Create migration | database-analyzer | laravel-backend |
| Optimize queries | database-analyzer | performance-optimizer |
| Inspect schema | database-analyzer | - |
| Debug relationships | database-analyzer | laravel-backend |
| **Performance** |
| Speed up tests | performance-optimizer | testing-specialist |
| Optimize queries | performance-optimizer | database-analyzer |
| Analyze bottlenecks | performance-optimizer | - |
| **Deployment** |
| Deploy to production | deployment-helper | - |
| Manage services | deployment-helper | - |
| Check logs | deployment-helper | - |
| **Documentation** |
| Update docs | documentation-writer | - |
| Write comments | documentation-writer | - |
| Create guides | documentation-writer | - |

---

## Common Workflows

### Workflow 1: Create a New Feature

```
Step 1: Use the vue-component-builder agent to create the frontend page

Step 2: Use the laravel-backend agent to create the controller and routes

Step 3: Use the database-analyzer agent to create any necessary migrations

Step 4: Use the testing-specialist agent to write comprehensive tests

Step 5: Use the styling-expert agent to ensure design system compliance

Step 6: Use the documentation-writer agent to update relevant documentation
```

### Workflow 2: Fix a Bug

```
Step 1: Use the browser-tester agent to reproduce the issue and capture console errors

Step 2: Use the laravel-backend agent to investigate and fix the backend logic

Step 3: Use the testing-specialist agent to write a regression test

Step 4: Use the performance-optimizer agent to ensure the fix doesn't impact performance
```

### Workflow 3: Optimize Performance

```
Step 1: Use the performance-optimizer agent to identify slow tests and routes

Step 2: Use the database-analyzer agent to optimize database queries

Step 3: Use the testing-specialist agent to implement proper mocking

Step 4: Use the performance-optimizer agent to verify improvements
```

### Workflow 4: Deploy New Release

```
Step 1: Use the testing-specialist agent to ensure all tests pass

Step 2: Use the documentation-writer agent to update the CHANGELOG

Step 3: Use the deployment-helper agent to deploy to production

Step 4: Use the browser-tester agent to verify production deployment
```

### Workflow 5: Refactor Code

```
Step 1: Use the laravel-backend agent to refactor backend code

Step 2: Use the vue-component-builder agent to refactor frontend components

Step 3: Use the styling-expert agent to ensure consistent styling

Step 4: Use the testing-specialist agent to update tests

Step 5: Use the performance-optimizer agent to verify performance improvements
```

---

## Best Practices

### 1. **Choose the Right Agent**

✅ **Good**:
```
Use the testing-specialist agent to write tests for the new SSL monitoring feature
```

❌ **Bad**:
```
Use the laravel-backend agent to write tests (not their specialty)
```

### 2. **Provide Clear Task Descriptions**

✅ **Good**:
```
Use the vue-component-builder agent to create a new dashboard page that displays:
- SSL certificate expiry statistics
- Uptime monitoring charts
- Recent alert notifications
Follow the existing dashboard pattern and use glass-card-strong components
```

❌ **Bad**:
```
Use the vue-component-builder agent to make a page
```

### 3. **Use Multiple Agents for Complex Tasks**

✅ **Good**:
```
Step 1: Use the database-analyzer agent to inspect the current schema
Step 2: Use the laravel-backend agent to create the migration based on the analysis
Step 3: Use the testing-specialist agent to write tests for the new functionality
```

❌ **Bad**:
```
Use the laravel-backend agent to analyze the database, create a migration, and write tests
(Each agent should focus on their expertise)
```

### 4. **Trust Agent Expertise**

Agents are configured with project knowledge and best practices. When an agent makes a recommendation, it's based on:
- Project documentation
- Established patterns
- Performance standards
- Coding guidelines

### 5. **Combine Agents for Quality**

```
1. Use the vue-component-builder agent to create the component
2. Use the styling-expert agent to review and ensure semantic class usage
3. Use the browser-tester agent to verify it works correctly
4. Use the testing-specialist agent to add automated tests
```

### 6. **Leverage Agent Memory**

Within a single agent session, the agent remembers context:

```
Use the database-analyzer agent to:
1. Inspect the monitors table
2. Identify performance bottlenecks
3. Suggest indexes based on your analysis
4. Create a migration with the recommended changes
```

---

## Troubleshooting

### Problem: Agent doesn't have access to needed tools

**Solution**: Check the agent's MCP configuration in `.claude/agents/[agent-name].json`

Example: If you need database access, use `database-analyzer` or `laravel-backend` (both have Laravel Boost enabled)

### Problem: Agent isn't following project standards

**Solution**: Agents are configured with project standards. If they're not following them:
1. Check if the task matches the agent's expertise
2. Provide more specific instructions
3. Reference specific documentation sections

Example:
```
Use the vue-component-builder agent to create a new page following the pattern in docs/CODING_GUIDE.md section 2 (Standard Page Template)
```

### Problem: Agent response is too slow

**Solution**: Agents with fewer MCP tools respond faster:
- `styling-expert` (no MCP tools) - fastest
- `documentation-writer` (no MCP tools) - fastest
- `browser-tester` (Playwright only) - fast
- `laravel-backend` (Laravel Boost + Context7) - moderate

### Problem: Need to use Playwright but it's disabled in main conversation

**Solution**: That's exactly why we have the `browser-tester` agent!

```
Use the browser-tester agent to navigate to the alerts page and check for console errors
```

### Problem: Agent creates files I didn't ask for

**Solution**: Be explicit in your instructions:

✅ **Good**:
```
Use the testing-specialist agent to write tests for the AlertConfiguration model.
Do NOT create any new files besides the test file.
```

❌ **Bad**:
```
Use the testing-specialist agent to work on AlertConfiguration
(Too vague, agent might create multiple files)
```

---

## Context Savings

### Main Conversation Context Usage

**Before Agents**: ~25,553 tokens from MCP tools
- Playwright: 13,708 tokens
- Laravel Boost: 10,136 tokens
- Context7: 1,709 tokens

**After Agents**: ~11,845 tokens in main conversation
- Playwright: ❌ Disabled (13,708 tokens saved)
- Laravel Boost: ✅ Enabled (10,136 tokens)
- Context7: ✅ Enabled (1,709 tokens)

**Savings**: ~46% reduction in MCP tool overhead

### Agent-Specific Context

Each agent only loads the MCP tools it needs:

| Agent | MCP Tools Loaded | Context Used |
|-------|------------------|--------------|
| browser-tester | Playwright, Laravel Boost | High |
| testing-specialist | Laravel Boost | Medium |
| vue-component-builder | Laravel Boost, Context7 | Medium |
| database-analyzer | Laravel Boost | Medium |
| laravel-backend | Laravel Boost, Context7 | Medium |
| styling-expert | None | Low |
| performance-optimizer | Laravel Boost | Medium |
| deployment-helper | Laravel Boost | Medium |
| documentation-writer | None | Low |

---

## Tips and Tricks

### 1. Chain Agents for Complex Workflows

```
Use the database-analyzer agent to inspect the alerts table and identify missing indexes.
Based on those findings, use the laravel-backend agent to create a migration.
Then use the testing-specialist agent to write tests verifying the performance improvement.
Finally, use the documentation-writer agent to document the optimization in TESTING_INSIGHTS.md.
```

### 2. Use Agents for Code Review

```
Use the styling-expert agent to review the new dashboard components and ensure they follow the design system
```

### 3. Parallel Testing and Development

```
While I work on the backend, use the vue-component-builder agent to create the frontend page
```

### 4. Quick Quality Checks

```
Use the testing-specialist agent to verify all tests pass before I commit
```

### 5. Documentation Maintenance

```
Use the documentation-writer agent to update all documentation files with the new alert system patterns we just implemented
```

---

## Advanced Usage

### Custom Agent Workflows

You can create your own agent workflows by combining agents:

**Example: Full Feature Development**
```
I need to implement a new bulk SSL certificate check feature. Please coordinate these agents:

1. database-analyzer: Design the database schema for bulk operations
2. laravel-backend: Create the job and service layer
3. vue-component-builder: Build the UI for triggering bulk checks
4. testing-specialist: Write comprehensive tests
5. styling-expert: Ensure UI follows design system
6. documentation-writer: Document the new feature

Please have them work in sequence, with each agent building on the previous agent's work.
```

### Agent Collaboration

Agents can reference each other's work:

```
Use the database-analyzer agent to analyze the monitors table.
Then use the performance-optimizer agent to review the analysis and suggest query optimizations.
Finally, use the laravel-backend agent to implement the optimizations.
```

---

## Agent Configuration

All agents are configured in `.claude/agents/[agent-name].json`

### Agent Configuration Structure

```json
{
  "name": "agent-name",
  "description": "What the agent specializes in",
  "mcpServers": {
    "playwright-extension": {
      "disabled": true  // or false
    },
    "laravel-boost": {
      "disabled": false  // or true
    },
    "context7": {
      "disabled": true  // or false
    }
  },
  "instructions": "Detailed instructions for the agent..."
}
```

### Customizing Agents

You can edit agent configurations to:
- Enable/disable MCP tools
- Add specific instructions
- Adjust focus areas
- Add custom patterns

**Example**: Enable Context7 for documentation-writer:
```json
{
  "name": "documentation-writer",
  "mcpServers": {
    "context7": {
      "disabled": false  // Enable for library docs lookup
    }
  }
}
```

---

## Summary

### Quick Command Reference

```bash
# Frontend Development
Use the vue-component-builder agent to [create page/component]
Use the styling-expert agent to [fix styling/ensure consistency]
Use the browser-tester agent to [test UI/check console errors]

# Backend Development
Use the laravel-backend agent to [create controller/service/job]
Use the database-analyzer agent to [inspect schema/create migration]

# Testing
Use the testing-specialist agent to [write tests/fix tests/optimize performance]
Use the browser-tester agent to [test browser interactions]

# Performance
Use the performance-optimizer agent to [analyze/optimize performance]

# Deployment
Use the deployment-helper agent to [deploy/manage services/check logs]

# Documentation
Use the documentation-writer agent to [update docs/write comments]
```

### Remember

1. **Choose the right agent** for the task
2. **Provide clear instructions** with context
3. **Chain agents** for complex workflows
4. **Trust agent expertise** - they know project standards
5. **Use multiple agents** for quality assurance

---

**Last Updated**: 2025-10-17
**Project**: SSL Monitor v4
**Total Agents**: 9
**Context Savings**: ~46% in main conversation

For more information on project architecture and patterns, see:
- `docs/DEVELOPMENT_PRIMER.md` - Development overview
- `docs/CODING_GUIDE.md` - Vue/TypeScript patterns
- `docs/TESTING_INSIGHTS.md` - Testing best practices
- `docs/STYLING_GUIDE.md` - Design system guidelines
