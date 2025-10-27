# SSL Monitor v4 Documentation

**Complete documentation index for SSL Monitor v4 - Laravel 12 + Vue 3 + Inertia.js**

**Last Updated**: 2025-10-17
**Test Suite**: 530 tests passing, 13 skipped (100% success rate) ✅

---

## 🚀 Quick Start

**New to the project?** Start here:
1. **[DEVELOPMENT_PRIMER.md](DEVELOPMENT_PRIMER.md)** - Essential guide for development
2. **[CODING_GUIDE.md](CODING_GUIDE.md)** - Coding standards and patterns
3. **[TESTING_INSIGHTS.md](TESTING_INSIGHTS.md)** - Testing patterns and best practices

**Deploying to production?**
- **[DEPLOYMENT.md](DEPLOYMENT.md)** - GitHub Actions CI/CD deployment
- **[DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md)** - Deployer.org deployment

---

## 📚 Documentation Categories

### Core Development Guides

#### **[DEVELOPMENT_PRIMER.md](DEVELOPMENT_PRIMER.md)** (1,009 lines)
**The essential starting point for all development work.**

- Application overview and architecture
- Development environment setup
- Key files and data flow
- Debugging approach with MCP servers
- Quick development commands
- Production deployment overview

**When to use**: First stop for any development task, debugging, or understanding the codebase.

---

#### **[CODING_GUIDE.md](CODING_GUIDE.md)** (1,056 lines)
**Comprehensive coding standards and patterns specific to this project.**

- Laravel & PHP best practices
- Vue 3 + TypeScript patterns
- Naming conventions
- Controller patterns
- Component structure
- Code organization rules

**When to use**: Before writing any code, for code reviews, ensuring consistency.

---

#### **[TESTING_INSIGHTS.md](TESTING_INSIGHTS.md)** (1,307 lines)
**Complete testing guide with patterns, performance optimization, and debugging strategies.**

- Test structure (530 passing tests, 13 skipped)
- Parallel testing (~6.4s execution)
- Mock patterns for external services
- Common testing issues and solutions
- Test data management
- Performance best practices

**When to use**: Writing tests, debugging test failures, optimizing test performance.

---

#### **[STYLING_GUIDE.md](STYLING_GUIDE.md)** (405 lines)
**Frontend styling standards and component patterns.**

- Component styling patterns
- Theme system integration
- Design system conventions
- Responsive design patterns
- Cross-references Tailwind v4 technical guide

**When to use**: Understanding component patterns, design system conventions.

---

### Frontend Styling & Tailwind v4

#### **[TAILWIND_V4_STYLING_GUIDE.md](TAILWIND_V4_STYLING_GUIDE.md)** (967 lines)
**Complete technical reference for Tailwind CSS v4 in this project.**

- Semantic token system (no numeric scales like `bg-gray-300`)
- **Critical @apply limitation** in scoped styles
- All available tokens with use cases
- 5 complete code examples (cards, alerts, modals, tables)
- Comprehensive troubleshooting guide
- Migration guide from Tailwind v3

**When to use**: Before writing any CSS, understanding v4 changes, debugging styling issues.

---

#### **[TAILWIND_V4_QUICK_REFERENCE.md](TAILWIND_V4_QUICK_REFERENCE.md)** (295 lines)
**One-page cheat sheet for Tailwind v4 - print and keep handy!**

- Golden rules (do's and don'ts)
- Token reference table
- Common patterns (buttons, cards, badges, alerts)
- Quick troubleshooting
- v3 → v4 migration cheat sheet

**When to use**: Daily reference while coding, quick lookups, onboarding new developers.

---

#### **[TAILWIND_V4_CONVERSION_SUMMARY.md](TAILWIND_V4_CONVERSION_SUMMARY.md)** (402 lines)
**Summary of Tailwind v3 → v4 conversion for this project.**

- What changed during conversion
- Three critical discoveries (@apply limitation, no numeric scales, gradient limitations)
- Usage patterns specific to this project
- Testing checklist
- Best practices established

**When to use**: Understanding project-specific Tailwind decisions, migration context.

---

#### **[PERFORMANCE_WORKFLOW.md](PERFORMANCE_WORKFLOW.md)** (288 lines)
**Performance optimization workflow and best practices.**

- Performance testing workflow
- Optimization strategies
- Monitoring performance
- Database query optimization
- Frontend performance

**When to use**: Optimizing slow features, maintaining performance standards.

---

#### **[AGENT_USAGE_GUIDE.md](AGENT_USAGE_GUIDE.md)** (857 lines)
**Guide for using Claude Code agents effectively in this project.**

- Available agent types (9 specialized agents)
- When to use agents
- Agent prompting patterns
- Task delegation strategies
- Multi-agent workflows
- Documentation-writer agent with comprehensive standards

**When to use**: Complex multi-step tasks, codebase exploration, parallel development.

---

### Architecture Documentation

#### **[ALERT_SYSTEM_ARCHITECTURE.md](ALERT_SYSTEM_ARCHITECTURE.md)** (465 lines)
**Complete alert system architecture - RECENTLY UPDATED (Oct 2025)**

- 5 alert types (ssl_expiry, ssl_invalid, uptime_down, uptime_up, response_time)
- Multi-level alerting (INFO → WARNING → URGENT → CRITICAL)
- Website-specific alert fetching (duplicate bug fixed)
- Alert configuration management
- Test coverage: 530/530 passing

**Recent Fix**: Alert system was sending 6x duplicate emails due to fetching both global templates and website-specific configs. Fixed by using ONLY website-specific configurations.

**When to use**: Understanding alert system, debugging alert issues, configuring alerts.

---

#### **[ALERT_TESTING_FIX_DOCUMENTATION.md](ALERT_TESTING_FIX_DOCUMENTATION.md)** (383 lines)
**Recent alert system bug fix documentation (Oct 2025)**

- Duplicate alert bug analysis and fix
- Let's Encrypt feature removal
- Test results (530/530 passing)
- Before/after code comparisons
- Files modified and solutions

**Status**: ✅ ALL ISSUES RESOLVED - 100% test pass rate

**When to use**: Understanding recent alert fixes, reference for similar bug patterns.

---

#### **[QUEUE_AND_SCHEDULER_ARCHITECTURE.md](QUEUE_AND_SCHEDULER_ARCHITECTURE.md)** (368 lines)
**Hybrid queue/scheduler architecture for monitoring.**

- Laravel Scheduler for automated monitoring
- Redis queues for manual actions
- Horizon queue monitoring
- Job patterns and best practices
- Extended Monitor model usage

**When to use**: Understanding monitoring execution, debugging queue issues, adding new jobs.

---

#### **[EXTERNAL_SERVICE_PATTERNS.md](EXTERNAL_SERVICE_PATTERNS.md)** (473 lines)
**Patterns for integrating external services and APIs.**

- Service integration patterns
- Mock strategies for testing
- Rate limiting handling
- Error handling patterns
- API client patterns

**When to use**: Integrating new external services, testing external integrations.

---

### Feature Documentation

#### **[TEAMS_AND_ROLES.md](TEAMS_AND_ROLES.md)** (616 lines + Quick Reference)
**Complete team management and role-based permissions documentation.**

- Quick reference guide (roles, permissions, scenarios)
- 3-tier role hierarchy (OWNER, ADMIN, VIEWER)
- Permissions matrix
- Team management workflows
- API endpoints
- Common scenarios and use cases

**When to use**: Understanding team permissions, debugging access issues, implementing team features.

---

#### **[TOAST_NOTIFICATIONS.md](TOAST_NOTIFICATIONS.md)** (319 lines)
**Toast notification system documentation.**

- Toast system architecture
- useToast composable
- Notification patterns
- Success/error/info messages
- Flash message integration

**When to use**: Adding user feedback, implementing notifications, debugging toast issues.

---

#### **[DEBUG_LOGGING_ENHANCEMENT.md](DEBUG_LOGGING_ENHANCEMENT.md)** (290 lines)
**Debug logging system and enhancement patterns.**

- AutomationLogger usage
- Debug logging patterns
- Log levels and formatting
- Testing with logs
- Production logging

**When to use**: Adding logging to features, debugging production issues.

---

### Deployment Documentation

#### **[DEPLOYMENT.md](DEPLOYMENT.md)** (1,812 lines)
**Complete production deployment guide using GitHub Actions CI/CD.**

- GitHub Actions workflow
- Zero-downtime deployment strategy
- ISPConfig integration
- Server setup and configuration
- Deployment architecture
- Troubleshooting guide

**When to use**: Setting up CI/CD, deploying to production, understanding deployment flow.

---

#### **[DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md)** (497 lines)
**Deployer.org deployment guide with systemd services.**

- Deployer.org configuration
- SSH access and server setup
- Systemd service management (Horizon, Scheduler)
- Manual deployment commands
- Rollback procedures

**When to use**: Manual deployments, service management, debugging deployment issues.

---

### Completed Implementations

#### **[implementation-finished/](implementation-finished/)** - Successfully Implemented Features

**2 features completed** as of October 27, 2025:

##### **[Dynamic SSL Thresholds](implementation-finished/DYNAMIC_SSL_THRESHOLDS.md)** ✅ (Oct 27, 2025)
**Intelligent percentage-based SSL certificate expiration detection.**

- Adapts to certificate validity periods (33% threshold + 30-day minimum)
- Let's Encrypt (90-day): 73 days remaining = Valid ✅
- 1-Year Commercial: 73 days remaining = Expires Soon ⚠️
- Backward compatible with existing monitoring data
- **Implementation Time**: 2.5 hours (5 phases)
- **Test Coverage**: 12 comprehensive tests
- **Documentation**: `docs/SSL_CERTIFICATE_MONITORING.md`

**When to use**: Reference for similar percentage-based algorithm implementations.

---

##### **[Certificate Data Architecture](implementation-finished/CERTIFICATE_DATA_ARCHITECTURE.md)** ✅ (Oct 18, 2025)
**Comprehensive SSL certificate data extraction and storage.**

- Extracts certificate subject (CN + SANs) from OpenSSL parsing
- Stores certificate validity dates for dynamic threshold calculations
- Makes Certificate Analysis the "source of truth" for SSL data
- **Implementation Time**: 4-6 hours
- **Database Changes**: Added certificate_valid_from_date column

**When to use**: Understanding certificate data architecture and storage patterns.

---

See **[implementation-finished/README.md](implementation-finished/README.md)** for complete details on all completed features.

---

### Planning Documentation (Future Features)

#### **[HISTORICAL_DATA_MASTER_PLAN.md](HISTORICAL_DATA_MASTER_PLAN.md)** (2,800+ lines) ⭐ **MASTER PLAN**
**Comprehensive consolidated plan for historical monitoring data tracking.**

- **Complete implementation guide** (consolidates 2 previous plans)
- **4 optimized database tables** (monitoring_results, summaries, alerts, events)
- **Event-driven Laravel architecture** with queue strategies
- **6-week implementation timeline** (5 phases with detailed checklists)
- **Performance targets**: Dashboard 15-30s → < 2s
- **Storage projections**: ~23 MB/day for 50 websites
- **Complete code examples**: Models, events, listeners, migrations
- **Testing strategy**: Maintain < 20s test suite

**Implementation Status**: 84% Complete (Phase 1-3: 100%, Phase 4: 60%, Phase 5: 40%)

**Quick Start**: See [HISTORICAL_DATA_QUICK_START.md](HISTORICAL_DATA_QUICK_START.md) (one-page guide)

**Completion Guides**:
- **[PHASE4_COMPLETION_PROMPT.md](PHASE4_COMPLETION_PROMPT.md)** - Complete Phase 4 (remaining 40%)
- **[PHASE5_IMPLEMENTATION_PROMPT.md](PHASE5_IMPLEMENTATION_PROMPT.md)** - Complete Phase 5 (remaining 60%)

**Status**: 🚧 **84% COMPLETE** - Phase 4 & 5 remaining before production deployment
**When to use**: THE definitive guide for implementing historical data tracking

**Supersedes**:
- ~~MONITORING_HISTORY_PLAN.md~~ (archived - 6 table approach)
- ~~MONITORING_DATA_TRACKING_PLAN.md~~ (archived - claimed completion without evidence)

---

#### **[PHASE4_COMPLETION_PROMPT.md](PHASE4_COMPLETION_PROMPT.md)** (664 lines)
**Implementation guide for completing Phase 4 of historical data tracking.**

- Complete `UpdateMonitoringSummaries` listener (real-time summary aggregation)
- Complete `CheckAlertConditions` listener (automated alert checking)
- Configure Laravel scheduler (5 automated jobs)
- Add 8 comprehensive tests
- **Estimated time**: 1-2 hours with `laravel-backend-specialist` agent
- **Current progress**: 60% → Target: 100%

**When to use**: When ready to complete Phase 4 before deployment

---

#### **[PHASE5_IMPLEMENTATION_PROMPT.md](PHASE5_IMPLEMENTATION_PROMPT.md)** (750+ lines)
**Implementation guide for Phase 5 production optimization.**

- Advanced caching implementation (MonitoringCacheService)
- Query optimization and profiling tools
- Load testing infrastructure (50+ websites, 72,000 checks/day)
- Production monitoring setup (Horizon health checks)
- Complete deployment checklist
- **Estimated time**: 2-3 hours with specialized agents
- **Current progress**: 40% → Target: 100%

**When to use**: After Phase 4 completion, before production deployment

---

#### **[DASHBOARD_IMPROVEMENT_PLAN.md](DASHBOARD_IMPROVEMENT_PLAN.md)** (342 lines + Implementation Prompt)
**Planned dashboard improvements with implementation prompt.**

- Phase 1: Information consolidation & cleanup
- Phase 2: Advanced features (charts, trends)
- Phase 3: Interactive features
- Copy-paste implementation prompt included

**Status**: 🔮 Not yet implemented
**When to use**: Implementing dashboard improvements, understanding planned features.

---

## 🗂️ Archived Documentation

Completed implementation plans and historical documentation are preserved in **[archive/](archive/)** for reference:

- **Queue implementation** (Oct 2025) - Now in QUEUE_AND_SCHEDULER_ARCHITECTURE.md
- **Debug menu implementation** - Feature complete
- **Session prompts** - Historical reference
- **Theme customizer** - Phase 2 complete

See **[archive/README.md](archive/README.md)** for complete archive index.

---

## 📊 Documentation Statistics

- **Active Docs**: 28 files (including implementation-finished/)
- **Completed Implementations**: 2 features (Dynamic SSL Thresholds, Certificate Data Architecture)
- **Total Lines**: ~18,000+ lines of active documentation
- **Archived**: 7 files in archive/ + 2 superseded plans
- **Test Coverage**: 669 tests passing, 12 skipped (100% pass rate)
- **Last Major Update**: October 27, 2025 (Dynamic SSL Thresholds completion + Documentation reorganization plan)

---

## 🔍 Finding What You Need

### By Task Type

| Task | Documentation |
|------|---------------|
| **Getting started** | DEVELOPMENT_PRIMER.md |
| **Writing code** | CODING_GUIDE.md |
| **Writing tests** | TESTING_INSIGHTS.md |
| **Styling components** | TAILWIND_V4_QUICK_REFERENCE.md, STYLING_GUIDE.md |
| **Understanding Tailwind v4** | TAILWIND_V4_STYLING_GUIDE.md |
| **Migrating to Tailwind v4** | TAILWIND_V4_CONVERSION_SUMMARY.md |
| **Understanding alerts** | ALERT_SYSTEM_ARCHITECTURE.md |
| **Understanding teams** | TEAMS_AND_ROLES.md |
| **Understanding SSL monitoring** | SSL_CERTIFICATE_MONITORING.md |
| **Viewing completed features** | implementation-finished/README.md |
| **Deploying** | DEPLOYMENT.md, DEPLOYMENT_GUIDE.md |
| **Using agents** | AGENT_USAGE_GUIDE.md |
| **Performance tuning** | PERFORMANCE_WORKFLOW.md |
| **Completing historical data** | PHASE4_COMPLETION_PROMPT.md, PHASE5_IMPLEMENTATION_PROMPT.md |

### By Role

#### New Developer
1. DEVELOPMENT_PRIMER.md
2. CODING_GUIDE.md
3. TAILWIND_V4_QUICK_REFERENCE.md
4. TESTING_INSIGHTS.md

#### DevOps/Deployment
1. DEPLOYMENT.md
2. DEPLOYMENT_GUIDE.md
3. QUEUE_AND_SCHEDULER_ARCHITECTURE.md

#### Feature Development
1. DEVELOPMENT_PRIMER.md
2. CODING_GUIDE.md
3. Relevant feature docs (TEAMS_AND_ROLES.md, ALERT_SYSTEM_ARCHITECTURE.md)

#### Bug Fixing
1. DEVELOPMENT_PRIMER.md (debugging section)
2. TESTING_INSIGHTS.md
3. ALERT_TESTING_FIX_DOCUMENTATION.md (for similar patterns)

---

## 🚀 Common Workflows

### Adding a New Feature
1. Read **DEVELOPMENT_PRIMER.md** (Where to Find Things)
2. Review **CODING_GUIDE.md** (patterns)
3. Check **TESTING_INSIGHTS.md** (test patterns)
4. Implement with tests
5. Update relevant feature documentation

### Fixing a Bug
1. Check **DEVELOPMENT_PRIMER.md** (debugging section)
2. Review **TESTING_INSIGHTS.md** (test debugging)
3. Check feature-specific docs for architecture
4. Write failing test
5. Fix bug
6. Document if needed

### Deploying to Production
1. Review **DEPLOYMENT.md** (GitHub Actions) or **DEPLOYMENT_GUIDE.md** (Deployer)
2. Ensure all tests pass (530 passing)
3. Follow deployment checklist
4. Monitor systemd services

### Optimizing Performance
1. Read **PERFORMANCE_WORKFLOW.md**
2. Profile and identify bottlenecks
3. Apply optimization patterns
4. Verify with tests
5. Document improvements

---

## 💡 Documentation Maintenance

### When to Update Documentation

- **After major features**: Update architecture docs
- **After bug fixes**: Update relevant feature docs
- **After refactoring**: Update CODING_GUIDE.md patterns
- **After test improvements**: Update TESTING_INSIGHTS.md
- **After deployment changes**: Update DEPLOYMENT*.md

### Documentation Standards

- Use clear, descriptive headers
- Include code examples
- Add "When to use" sections
- Keep line counts reasonable (< 1,500 lines)
- Update "Last Updated" dates
- Link to related documentation

---

## 🔗 External Resources

- **Laravel 12 Docs**: Available via laravel-boost MCP
- **Vue 3 Docs**: [vuejs.org](https://vuejs.org)
- **Inertia.js Docs**: [inertiajs.com](https://inertiajs.com)
- **TailwindCSS v4**: [tailwindcss.com](https://tailwindcss.com)
- **Project Guidelines**: `~/.claude/laravel-php-guidelines.md`

---

## ✅ Quality Metrics

- ✅ **Test Coverage**: 530 tests passing, 13 skipped (100%)
- ✅ **Test Performance**: ~6.4s parallel execution
- ✅ **Documentation**: 20+ active guides
- ✅ **Alert System**: Fixed and production-ready
- ✅ **Deployment**: Zero-downtime CI/CD
- ✅ **Architecture**: Hybrid queue/scheduler system

---

**For questions or clarifications, refer to DEVELOPMENT_PRIMER.md or use the laravel-boost MCP server for up-to-date Laravel documentation.**
