# SSL Monitor v4 - Claude Code Slash Commands

## Available Commands

### 🚀 `/prime` - Project Primer
**Purpose**: Quickly onboard developers to SSL Monitor v4 with essential context and setup.
**Usage**: `/prime`
**Best for**: New developers, project setup, quick reference

### 🔐 `/ssl-feature` - SSL Feature Development
**Purpose**: Implement new SSL monitoring features using TDD and TailwindCSS v4 integration.
**Usage**: `/ssl-feature [feature-name] [description]`
**Best for**: Adding SSL monitoring capabilities, certificate validation features

**Examples**:
- `/ssl-feature wildcard-validation "Support for wildcard SSL certificates"`
- `/ssl-feature chain-verification "Validate SSL certificate chains"`
- `/ssl-feature expiry-alerts "Enhanced expiry notification system"`

### 🎨 `/vue-ui` - Vue.js Component Development
**Purpose**: Create Vue.js components with TailwindCSS v4 semantic design system.
**Usage**: `/vue-ui [component-type] [page-name]`
**Best for**: UI development, component creation, responsive design

**Examples**:
- `/vue-ui dashboard "SSL monitoring dashboard"`
- `/vue-ui form "certificate-settings"`
- `/vue-ui table "website-management"`

### 🔍 `/debug-ssl` - SSL Monitoring Debug Assistant
**Purpose**: Debug SSL monitoring issues using all MCP servers and comprehensive logging.
**Usage**: `/debug-ssl [issue-description]`
**Best for**: Troubleshooting, performance issues, certificate validation problems

**Examples**:
- `/debug-ssl "Certificate validation failing for wildcard domains"`
- `/debug-ssl "Queue jobs timing out during SSL checks"`
- `/debug-ssl "Dashboard not updating certificate status"`

### 🌐 `/browser-test` - Comprehensive Browser Testing
**Purpose**: Execute Playwright browser testing with real database integration for complete user flow validation.
**Usage**: `/browser-test [test-type] [device]`
**Best for**: UI validation, responsive design testing, user workflow verification

**Examples**:
- `/browser-test` - Run comprehensive test suite
- `/browser-test visual mobile` - Visual testing on mobile devices
- `/browser-test performance` - Performance testing only
- `/browser-test cross-browser` - Test across multiple browsers

## Command Structure

### Based on Claude Code Best Practices
- Commands use `$ARGUMENTS` parameter passing
- Structured workflows with clear steps
- MCP server integration throughout
- Docker/Sail awareness
- TDD methodology enforcement

### Common Patterns
All commands follow these patterns:
1. **Context Discovery** - Use MCP servers to understand current state
2. **Research** - Use Context7 and Laravel Boost for documentation
3. **Git Flow** - Create feature branches, follow workflow
4. **TDD Implementation** - Write tests first, implement second
5. **VRISTO Integration** - Professional UI with template integration
6. **Quality Assurance** - Testing, code formatting, documentation
7. **Git Completion** - Commit, push, create PRs

### MCP Server Usage
Commands leverage all four MCP servers:
- **🚀 Laravel Boost**: Application context, debugging, Laravel ecosystem
- **🌐 Context7**: Universal documentation, VRISTO patterns, Vue.js help
- **📁 Filesystem MCP**: File operations, log analysis, asset management
- **🔀 Git MCP**: Repository management, Git Flow workflow

## Usage Guidelines

### When to Use Commands
- **Project Setup**: Use `/prime` for initial onboarding
- **Feature Development**: Use `/ssl-feature` for SSL monitoring capabilities
- **UI Development**: Use `/vristo-ui` for template integration
- **Problem Solving**: Use `/debug-ssl` for troubleshooting
- **Quality Assurance**: Use `/browser-test` for comprehensive user flow validation

### Command Customization
Commands are templates that can be customized:
- Modify steps based on specific requirements
- Add project-specific checks and validations
- Extend workflows for complex features
- Integrate additional tools as needed

### Best Practices
- Always run commands in the project root directory
- Ensure MCP servers are properly configured
- Have Docker/Sail containers running
- Follow the complete workflow provided by commands
- Document any deviations or customizations

## Integration with Documentation

### Cross-References
Commands reference the comprehensive documentation:
- **[../CLAUDE.md](../../CLAUDE.md)** - Master AI development reference
- **[docs/README.md](../../docs/README.md)** - Complete documentation index
- **[docs/core/DEVELOPMENT_PRIMER.md](../../docs/core/DEVELOPMENT_PRIMER.md)** - Essential development guide
- **[docs/testing/TESTING_INSIGHTS.md](../../docs/testing/TESTING_INSIGHTS.md)** - Testing patterns and best practices
- **[docs/styling/TAILWIND_V4_STYLING_GUIDE.md](../../docs/styling/TAILWIND_V4_STYLING_GUIDE.md)** - TailwindCSS v4 reference

### Command Evolution
Commands can be updated and improved:
- Add new commands for specific SSL Monitor v4 needs
- Refine existing workflows based on experience
- Integrate new MCP servers or tools
- Update for technology stack changes

## Future Commands (Potential)

### Additional Useful Commands
- `/test-ssl` - Run comprehensive SSL monitoring tests
- `/deploy-ssl` - Deploy SSL monitoring features to production
- `/perf-ssl` - Performance optimization for SSL monitoring
- `/docs-ssl` - Generate/update SSL monitoring documentation
- `/audit-ssl` - Security audit for SSL monitoring system

### Team Collaboration Commands
- `/review-ssl` - Code review assistance for SSL features
- `/onboard-ssl` - Team member onboarding for SSL Monitor v4
- `/standup-ssl` - Generate standup reports for SSL development

**Ready to use professional SSL monitoring development commands!** ⚡