# 🚨 CRITICAL CSS/STYLING WORKFLOW - ALWAYS FOLLOW

## ⚠️ MANDATORY STEPS AFTER ANY CSS/STYLING CHANGES

**NEVER SKIP THESE STEPS WHEN MAKING CSS CHANGES!**

### 1. Clear ALL Laravel Caches
```bash
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan view:clear
./vendor/bin/sail artisan route:clear
```

### 2. Restart Vite Development Server
```bash
# Kill existing Vite process
./vendor/bin/sail npm run dev
# Run in background
./vendor/bin/sail npm run dev --run_in_background=true
```

### 3. Wait for Vite to Fully Load
- Check for "VITE ready" message
- Verify HMR is working
- Look for any build errors

### 4. Only THEN Test Changes
- Browser tests
- Manual verification
- Screenshots

## 🔥 COMMON MISTAKE TO AVOID

**DO NOT** assume CSS changes work without:
1. Cache clearing
2. Vite restart
3. Asset rebuilding

## 📝 Why This Matters

- **Laravel caches** can hold old compiled views/config
- **Vite dev server** needs restart to pick up CSS variable changes
- **Browser cache** may serve stale assets
- **HMR (Hot Module Replacement)** doesn't always catch CSS variable updates

## 🎯 Remember This Pattern

```
CSS Change → Clear Caches → Restart Vite → Wait for Ready → Test
```

**NEVER test CSS changes without completing the full workflow!**

---

**This workflow is MANDATORY for all CSS/styling work!**