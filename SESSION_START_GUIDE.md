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

---

## 💡 **Example Complete Session Start**

```
New session for SSL Monitor v4. Review DEVELOPMENT_PRIMER.md and the current codebase. I'm planning to work on adding a new notification feature. What should I clarify before we start?

Specifically, I want to add email notifications when SSL certificates are expiring within 7 days. This should integrate with the existing monitoring system and use Laravel's mail functionality.
```

---

**Remember**: Start each new session with one of these prompts for efficient development!