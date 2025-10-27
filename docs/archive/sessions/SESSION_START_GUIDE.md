# SSL Monitor v4 - Session Start Guide

**Purpose**: Quick prompts to start new development sessions with Claude Code.

---

## 🚀 **Initial Session Prompts**

### **Standard Start (Recommended)**
```
I'm starting a new development session for SSL Monitor v4. Please read the DEVELOPMENT_PRIMER.md file to understand the codebase structure, development workflow, and where to start when adding features or debugging. Also review the project's current status and available MCP servers (Laravel Boost for backend tasks, Playwright for frontend verification). I'll provide specific tasks or questions, but first familiarize yourself with the application architecture and development environment.
```

### **Quick Task Start**
```
Read DEVELOPMENT_PRIMER.md and let me know when you're ready. I need to [brief task description].
```

### **Feature Development**
```
New session for SSL Monitor v4. Review DEVELOPMENT_PRIMER.md and the current codebase. I'm planning to work on [feature area]. What should I clarify before we start?
```

### **Debugging Session**
```
I need to debug an issue in SSL Monitor v4. Please read DEVELOPMENT_PRIMER.md and let me know what information you need about the problem I'm experiencing.
```

### **Production Issues**
```
Production server issue with SSL Monitor v4. Review the deployment and server access sections in DEVELOPMENT_PRIMER.md. I'm experiencing [problem description].
```

### **Testing & Quality Assurance**
```
New session focusing on testing SSL Monitor v4. Review DEVELOPMENT_PRIMER.md and the testing insights in docs/TESTING_INSIGHTS.md. Current test suite status: 487 passing, 17 failing. I need to [testing task].
```

### **Test Suite Maintenance**
```
Test suite health check for SSL Monitor v4. Review docs/TESTING_INSIGHTS.md and run tests to identify any regressions. I need to verify test suite integrity and fix any new failures.
```

### **Performance Investigation**
```
Performance investigation for SSL Monitor v4. Review the testing performance insights and run tests with --parallel flag. I'm investigating [performance concern] and need to [specific investigation steps].
```

---

## 📋 **What Claude Reads Automatically**

✅ **CLAUDE.md** - Project-specific instructions (read automatically by Claude)
✅ **Laravel Guidelines** - `/home/bonzo/.claude/laravel-php-guidelines.md` (available globally)

---

## 🎯 **Best Practices**

1. **Always start with orientation** - Use one of the prompts above
2. **Add specific context** after initial orientation
3. **Reference sections** if needed: "Focus on production deployment section"
4. **Include error details** immediately when debugging
5. **Mention MCP servers** when you want me to self-verify

## 🧪 **Testing-Specific Best Practices**

6. **For testing tasks**: Always check current test suite status first
   ```bash
   ./vendor/bin/sail artisan test --parallel  # 16 seconds vs 70 seconds sequential
   ```

7. **Use parallel testing** for performance: Add `--parallel` flag to all test commands
8. **Reference testing insights**: Consult `docs/TESTING_INSIGHTS.md` for common patterns
9. **Test data management**: Understand centralized setup in `tests/Pest.php`
10. **Debugging tests**: Start with `--stop-on-failure` for faster feedback loops

---

## 💡 **Example Complete Session Start**

```
New session for SSL Monitor v4. Review DEVELOPMENT_PRIMER.md and the current codebase. I'm planning to work on adding a new notification feature. What should I clarify before we start?

Specifically, I want to add email notifications when SSL certificates are expiring within 7 days. This should integrate with the existing monitoring system and use Laravel's mail functionality.
```

---

---

## 🧪 **Testing-Specific Example Prompts**

### **Test Debugging Session**
```
I need to debug failing tests in SSL Monitor v4. Current test suite status: 487 passing, 17 failing. I'm seeing [specific test failure] and need to identify the root cause.

Specifically, tests are failing with [error message]. Please help me debug this by checking the test logic, examining the affected code, and determining if we should fix the implementation or update test expectations.
```

### **Performance Testing Session**
```
Performance testing session for SSL Monitor v4. I want to optimize the test suite performance and identify any bottlenecks.

Review docs/TESTING_INSIGHTS.md for performance patterns and run tests with profiling. Focus on:
- Slowest individual tests
- Database cleanup efficiency
- Test data setup optimization
- Parallel testing effectiveness
```

### **New Feature Testing**
```
New feature testing for SSL Monitor v4. I need to write comprehensive tests for [feature description].

Review the existing test patterns in docs/TESTING_INSIGHTS.md and ensure we follow the established conventions:
- Centralized test data setup
- Proper type casting assertions
- Observer testing patterns
- Performance-aware test design
```

### **Test Suite Maintenance**
```
Test suite maintenance for SSL Monitor v4. Recent code changes may have introduced regressions.

Run full test suite with ./vendor/bin/sail artisan test --parallel and address any new failures:
- Check if existing patterns still work
- Update test expectations for new functionality
- Maintain 97%+ test pass rate
- Document any new testing patterns discovered
```

**Remember**: Start each new session with one of these prompts for efficient development!