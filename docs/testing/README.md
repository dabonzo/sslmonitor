# Testing Documentation - Quick Reference

This directory contains comprehensive testing documentation for SSL Monitor v4. Use this guide to navigate the documentation effectively.

## Quick Navigation

### For Testing Agents (AI/Human)

**Before Starting Any Test**:
1. Read [EXPECTED_BEHAVIOR.md](./EXPECTED_BEHAVIOR.md) - Know what should happen
2. Review [MONITORING_GUIDE.md](./MONITORING_GUIDE.md) - Set up monitoring tools
3. Prepare [PHASE6_LOG_ANALYSIS.md](./PHASE6_LOG_ANALYSIS.md) - Template ready for findings

**During Testing**:
- Monitor logs in real-time (see MONITORING_GUIDE.md)
- Compare actual behavior to EXPECTED_BEHAVIOR.md
- Note any discrepancies immediately

**After Testing**:
- Fill out log analysis report (PHASE6_LOG_ANALYSIS.md)
- Document issues with evidence
- Track resolution status

### For Developers

**Understanding Test Patterns**:
- [TESTING_INSIGHTS.md](./TESTING_INSIGHTS.md) - Comprehensive testing patterns (112KB, historical)
- [EXTERNAL_SERVICE_PATTERNS.md](./EXTERNAL_SERVICE_PATTERNS.md) - Mocking patterns for external services

**Phase 6 Implementation**:
- [PHASE6_PART4_SUMMARY.md](./PHASE6_PART4_SUMMARY.md) - Complete overview of monitoring framework

---

## Document Overview

### EXPECTED_BEHAVIOR.md (587 lines, 18KB)
**Purpose**: Reference for what SHOULD happen during monitoring operations

**Key Sections**:
- Monitor Creation Flow (7 steps)
- SSL Certificate Analysis Flow (3 phases)
- Scheduled Monitoring Flow (4 stages)
- Alert System Flow (3 steps)
- Historical Data Recording Flow (event-driven)
- Expected Logs by Operation (5 scenarios)
- Queue Assignment (3 queues)
- Verification Checklist (30+ items)
- Common Issues & Debugging

**When to Use**:
- Before writing tests (understand expected behavior)
- During test failures (compare actual vs expected)
- When debugging (reference expected logs)
- When documenting issues (cite expected behavior)

---

### MONITORING_GUIDE.md (804 lines, 19KB)
**Purpose**: Practical guide for monitoring logs, queues, and system health

**Key Sections**:
- Laravel Boost MCP Tools (8 tools)
- Real-Time Log Monitoring (3 methods)
- Browser Console Monitoring
- Queue Health Monitoring (Horizon + commands)
- Database Inspection (MCP + Tinker + MySQL)
- Network Request Monitoring
- Issue Detection Patterns (7 patterns)
- Monitoring Checklist
- Quick Reference Commands

**When to Use**:
- Setting up monitoring environment
- Investigating failures
- Checking queue health
- Analyzing database state
- Tracking network requests

---

### PHASE6_LOG_ANALYSIS.md (406 lines, 11KB)
**Purpose**: Template for documenting log analysis findings

**Key Sections**:
- Test Session Information
- Summary (metrics, findings)
- Issues Found (detailed documentation)
- Expected vs Actual Comparison Table
- Log Analysis Details (Laravel, browser, queue)
- Database State Analysis
- Performance Metrics
- Network Request Analysis
- Recommendations (prioritized)
- Resolution Tracking
- Follow-Up Items

**When to Use**:
- After each test session
- When investigating specific issues
- Before/after major changes
- For historical reference
- Tracking issue resolution

---

### PHASE6_PART4_SUMMARY.md (17KB)
**Purpose**: Complete overview of monitoring framework implementation

**Key Sections**:
- Deliverables Summary
- Architecture Insights from Code Review
- Key Configuration Values
- Performance Benchmarks
- Monitoring Architecture
- Critical Success Criteria
- Integration with Testing Phases
- Usage Instructions for Testing Agents
- Common Patterns & Anti-Patterns

**When to Use**:
- Understanding overall architecture
- Planning testing strategy
- Onboarding new team members
- Documenting implementation decisions

---

### TESTING_INSIGHTS.md (112KB) - Historical Reference
**Purpose**: Comprehensive testing patterns and insights (historical)

**Contents**:
- Parallel testing patterns
- Mock trait implementations
- Test performance optimization
- Database testing strategies
- Observer testing patterns

**When to Use**:
- Understanding existing test patterns
- Learning test optimization techniques
- Reference for mock implementations
- Historical testing decisions

---

### EXTERNAL_SERVICE_PATTERNS.md (15KB)
**Purpose**: Mocking patterns for external services

**Contents**:
- MocksSslCertificateAnalysis trait
- MocksJavaScriptContentFetcher trait
- Performance requirements (< 1s per test)
- Network isolation patterns

**When to Use**:
- Writing tests that interact with external services
- Ensuring test performance
- Avoiding real network calls

---

## Testing Workflow

### 1. Pre-Test Phase
```bash
# Setup
./vendor/bin/sail up -d
./vendor/bin/sail artisan horizon
./vendor/bin/sail npm run dev

# Read documentation
cat docs/testing/EXPECTED_BEHAVIOR.md
cat docs/testing/MONITORING_GUIDE.md
```

### 2. Test Execution Phase
```bash
# Terminal 1: Monitor logs
./vendor/bin/sail artisan tail

# Terminal 2: Run tests
./vendor/bin/sail artisan test --parallel
```

### 3. Post-Test Analysis
```bash
# Read logs via MCP
mcp__laravel-boost__read-log-entries({ entries: 100 })

# Check failed jobs
./vendor/bin/sail artisan queue:failed

# Query database
mcp__laravel-boost__database-query({ query: "SELECT ..." })

# Fill out analysis report
vim docs/testing/PHASE6_LOG_ANALYSIS.md
```

---

## Issue Investigation Workflow

### Step 1: Identify Issue
- Review logs for ERROR or WARNING entries
- Check Horizon for failed jobs
- Note browser console errors

### Step 2: Compare to Expected Behavior
- Open EXPECTED_BEHAVIOR.md
- Find relevant operation section
- Compare expected logs to actual logs

### Step 3: Use Monitoring Tools
- Use MCP tools from MONITORING_GUIDE.md
- Query database for state
- Check queue health

### Step 4: Document Issue
- Fill out issue section in PHASE6_LOG_ANALYSIS.md
- Include evidence (logs, errors, stack traces)
- Cite expected behavior
- Propose resolution

### Step 5: Fix and Verify
- Implement fix
- Re-run tests
- Verify logs match expected behavior
- Update resolution status

---

## Quick Command Reference

### Monitoring Commands
```bash
# Logs
./vendor/bin/sail artisan tail
tail -f storage/logs/laravel.log | grep ERROR

# Queue Health
./vendor/bin/sail artisan horizon:status
./vendor/bin/sail artisan queue:failed

# Database
./vendor/bin/sail mysql
./vendor/bin/sail artisan tinker
```

### MCP Tools
```javascript
// Logs
mcp__laravel-boost__read-log-entries({ entries: 50 })
mcp__laravel-boost__browser-logs({ entries: 20 })
mcp__laravel-boost__last-error()

// Database
mcp__laravel-boost__database-query({ query: "SELECT ..." })
mcp__laravel-boost__database-schema({ filter: "monitoring" })

// Application
mcp__laravel-boost__list-artisan-commands()
mcp__laravel-boost__list-routes({ path: "monitoring" })
```

### Testing Commands
```bash
# Run all tests (parallel)
./vendor/bin/sail artisan test --parallel

# Run specific test
./vendor/bin/sail artisan test --filter=TestName

# Profile slow tests
./vendor/bin/sail artisan test --profile

# List tests
./vendor/bin/sail artisan test --list-tests
```

---

## Documentation Maintenance

### When to Update

**EXPECTED_BEHAVIOR.md**:
- New monitoring features added
- Workflow changes
- New queue types introduced
- Performance benchmarks change

**MONITORING_GUIDE.md**:
- New MCP tools available
- New monitoring commands added
- New issue patterns identified
- Tool usage changes

**PHASE6_LOG_ANALYSIS.md**:
- Template structure improvements
- New analysis categories needed
- Better documentation patterns discovered

### How to Update

1. Identify what changed
2. Update relevant sections
3. Add examples if needed
4. Update cross-references
5. Test with real scenarios
6. Commit with descriptive message

---

## Support & Questions

### Common Questions

**Q: Where do I start?**
A: Read EXPECTED_BEHAVIOR.md first, then set up monitoring using MONITORING_GUIDE.md

**Q: How do I know if a test failure is expected?**
A: Compare actual logs to expected logs in EXPECTED_BEHAVIOR.md

**Q: What MCP tools should I use?**
A: Start with `read-log-entries`, `last-error`, and `database-query` from MONITORING_GUIDE.md

**Q: How do I document issues?**
A: Use the template in PHASE6_LOG_ANALYSIS.md

**Q: Where can I find test patterns?**
A: See TESTING_INSIGHTS.md for comprehensive patterns

### Getting Help

1. Search this README for keywords
2. Check relevant documentation file
3. Review examples in documentation
4. Consult code comments in `/app` and `/tests`
5. Check git history for context

---

## Summary

This documentation provides:
- ✅ Expected behavior reference (EXPECTED_BEHAVIOR.md)
- ✅ Practical monitoring guide (MONITORING_GUIDE.md)
- ✅ Structured analysis template (PHASE6_LOG_ANALYSIS.md)
- ✅ Implementation overview (PHASE6_PART4_SUMMARY.md)
- ✅ Historical patterns (TESTING_INSIGHTS.md)
- ✅ Mocking patterns (EXTERNAL_SERVICE_PATTERNS.md)

Total: 1,797+ lines of testing documentation (excluding historical references)

**Use this framework continuously throughout ALL testing phases for maximum effectiveness.**
