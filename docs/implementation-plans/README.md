# Implementation Plans - SSL Monitor v4

This folder contains detailed implementation prompts for features that are designed but not yet implemented.

## Status Overview

| Plan | Status | Priority | Estimated Time | Dependencies |
|------|--------|----------|----------------|--------------|
| [Certificate Data Architecture](CERTIFICATE_DATA_ARCHITECTURE.md) | 🔴 Not Started | **HIGH** | 4-6 hours | None |
| [Dynamic SSL Thresholds](DYNAMIC_SSL_THRESHOLDS.md) | 🔴 Not Started | Medium | 2-3 hours | None |
| [Phase 5 - Production Optimization](PHASE5_PRODUCTION_OPTIMIZATION.md) | 🔴 Not Started | Low | 2-3 hours | Phase 4 Complete ✅ |

## Implementation Order (Recommended)

### 1. Certificate Data Architecture (START HERE) 🎯
**Why First**: Fixes current production issue where certificate subject is empty. Provides foundation for better SSL data management.

**What It Does**:
- Saves full SSL certificate data when Certificate Analysis runs
- Eliminates dependency on monitor checks for certificate details
- Provides immediate SSL data on website creation
- Makes Certificate Analysis the "source of truth" for SSL data

**File**: `CERTIFICATE_DATA_ARCHITECTURE.md`

---

### 2. Dynamic SSL Thresholds
**Why Second**: Enhances the certificate monitoring logic to be more intelligent based on certificate type.

**What It Does**:
- Let's Encrypt (90-day): 73 days remaining = Valid ✅
- 1-year commercial: 73 days remaining = Expires Soon ⚠️
- Uses percentage-based thresholds (33% of validity period)

**File**: `DYNAMIC_SSL_THRESHOLDS.md`

---

### 3. Phase 5 - Production Optimization
**Why Third**: Performance optimizations, not critical functionality.

**What It Does**:
- Advanced caching strategies
- Database query optimization
- Asset optimization
- Performance monitoring

**File**: `PHASE5_PRODUCTION_OPTIMIZATION.md`

---

## How to Use These Plans

### Starting a New Implementation

1. **Choose a plan** from the table above
2. **Open a new Claude Code session**
3. **Use this prompt**:
   ```
   Read @docs/implementation-plans/[PLAN_NAME].md and implement the feature exactly as specified. Use the recommended agents and follow the implementation phases.
   ```
4. **Example**:
   ```
   Read @docs/implementation-plans/CERTIFICATE_DATA_ARCHITECTURE.md and implement the feature exactly as specified. Use the recommended agents and follow the implementation phases.
   ```

### After Implementation

1. Update the status in this README (🔴 → 🟡 → 🟢)
2. Add completion notes to the plan file
3. Update CLAUDE.md with the new feature
4. Move the plan file to `docs/implementation-plans/completed/` folder

---

## Quick Reference

**Current Production Issues**:
- ⚠️ Certificate subject empty in production (fixed by Certificate Data Architecture)
- ⚠️ Let's Encrypt certs show "expires soon" at 73 days (fixed by Dynamic SSL Thresholds)

**Phase 4 Status**: ✅ COMPLETE
- Historical data tracking implemented
- Real-time summaries working
- Alert system operational
- Certificate subject extraction coded (but not populating in production)

**Next Critical Step**: Implement Certificate Data Architecture to fix production certificate data issue.

---

## Plan Files Location

All implementation plan files are in: `/home/bonzo/code/ssl-monitor-v4/docs/implementation-plans/`

When complete, move to: `/home/bonzo/code/ssl-monitor-v4/docs/implementation-plans/completed/`
