# Documentation Structure Reorganization

## Overview

Reorganize SSL Monitor v4 documentation to eliminate temporary files from the root directory, establish clear category-based folder structure, and create a single source of truth for all documentation types.

**Current Problem**:
- 13 temporary/outdated markdown files cluttering the root directory
- Inconsistent organization in docs/ folder
- Duplicate/scattered testing documentation
- Historical data documentation spread across multiple files
- No clear structure for completed vs. planned implementations

**Goal**: Clean, organized, category-based documentation structure with clear purpose for each file.

---

## Current State Analysis

### Root Directory Files (Should Stay)
✅ `CLAUDE.md` - AI assistant instructions (KEEP IN ROOT)
✅ `README.md` - Project README (KEEP IN ROOT)

### Root Directory Files (Need Action)
❌ `API_TESTING_GUIDE.md` - 289 lines - Historical data API testing
❌ `BULK_OPTIMIZATION_PHASE_2_FINAL_REPORT.md` - Temporary report
❌ `BULK_TEST_OPTIMIZATION_REPORT.md` - Temporary report
❌ `HISTORICAL_DATA_IMPLEMENTATION.md` - Implementation notes
❌ `PHASE2_CONTINUATION_PROMPT.md` - Session prompt
❌ `RESPONSE_TIME_CHART_FIX.md` - Temporary fix documentation
❌ `SESSION_CONTEXT_monitoring_data_tracking_phase2.md` - Session context
❌ `SESSION_START_GUIDE.md` - Session guide
❌ `TEST_OPTIMIZATION_REPORT.md` - Temporary report
❌ `TEST_PERFORMANCE_ANALYSIS_REPORT.md` - Temporary report
❌ `TEST_PERFORMANCE_FIX_REPORT.md` - Temporary report
❌ `TEST_SUMMARY_CERTIFICATE_DATA_ARCHITECTURE.md` - Temporary report
❌ `WEBSITE_POLICY_TEST_OPTIMIZATION.md` - Temporary report

### Docs Directory Issues
- Multiple `PHASE*_IMPLEMENTATION_*.md` files (12 files)
- Multiple `HISTORICAL_DATA_*.md` files (5 files)
- No clear testing documentation folder
- No clear features documentation folder
- `implementation-plans/` folder exists but underutilized
- `implementation-finished/` folder exists but has only 1 file

---

## Target Documentation Structure

```
ssl-monitor-v4/
├── CLAUDE.md                           ✅ (AI instructions - stays in root)
├── README.md                           ✅ (Project README - stays in root)
│
└── docs/
    ├── README.md                       📝 (Updated documentation index)
    │
    ├── core/                          📁 NEW - Core development guides
    │   ├── DEVELOPMENT_PRIMER.md      (Main development guide)
    │   ├── CODING_GUIDE.md            (Coding standards)
    │   ├── AGENT_USAGE_GUIDE.md       (Claude Code agents)
    │   └── PERFORMANCE_WORKFLOW.md    (Performance optimization)
    │
    ├── testing/                       📁 NEW - All testing documentation
    │   ├── TESTING_INSIGHTS.md        (Main testing guide)
    │   ├── API_TESTING_GUIDE.md       (API endpoint testing)
    │   └── EXTERNAL_SERVICE_PATTERNS.md (Mock patterns)
    │
    ├── styling/                       📁 NEW - Frontend styling
    │   ├── TAILWIND_V4_STYLING_GUIDE.md
    │   ├── TAILWIND_V4_QUICK_REFERENCE.md
    │   ├── TAILWIND_V4_CONVERSION_SUMMARY.md
    │   └── STYLING_GUIDE.md
    │
    ├── architecture/                  📁 NEW - System architecture
    │   ├── ALERT_SYSTEM_ARCHITECTURE.md
    │   ├── QUEUE_AND_SCHEDULER_ARCHITECTURE.md
    │   ├── HISTORICAL_DATA_BACKEND_ARCHITECTURE.md
    │   ├── OPTIMIZED_MONITORING_SCHEMA.md
    │   └── SCHEMA_OPTIMIZATION_SUMMARY.md
    │
    ├── features/                      📁 NEW - Feature documentation
    │   ├── TEAMS_AND_ROLES.md
    │   ├── TOAST_NOTIFICATIONS.md
    │   ├── SSL_CERTIFICATE_MONITORING.md
    │   ├── DEBUG_LOGGING_ENHANCEMENT.md
    │   └── ALERT_TESTING_FIX_DOCUMENTATION.md
    │
    ├── deployment/                    📁 NEW - Deployment guides
    │   ├── DEPLOYMENT.md              (GitHub Actions CI/CD)
    │   └── DEPLOYMENT_GUIDE.md        (Deployer.org)
    │
    ├── implementation-plans/          📁 EXISTS - Active implementation plans
    │   ├── README.md                  (Index of planned features)
    │   ├── DYNAMIC_SSL_THRESHOLDS.md  ✅ (Recently completed)
    │   ├── PHASE5_PRODUCTION_OPTIMIZATION.md
    │   ├── HISTORICAL_DATA_MASTER_PLAN.md (Consolidated master plan)
    │   ├── PHASE4_COMPLETION_PROMPT.md
    │   ├── DASHBOARD_IMPROVEMENT_PLAN.md
    │   └── MONITORING_PLANS_COMPARISON.md
    │
    ├── implementation-finished/       📁 EXISTS - Completed implementations
    │   ├── README.md                  📝 NEW (Index of completed features)
    │   ├── CERTIFICATE_DATA_ARCHITECTURE.md
    │   ├── DYNAMIC_SSL_THRESHOLDS.md  (Move from implementation-plans)
    │   ├── PHASE1_IMPLEMENTATION.md   (Consolidated)
    │   ├── PHASE2_IMPLEMENTATION.md   (Consolidated)
    │   ├── PHASE3_IMPLEMENTATION.md   (Consolidated)
    │   └── PHASE4_IMPLEMENTATION.md   (Consolidated)
    │
    ├── historical-data/               📁 NEW - Historical data project docs
    │   ├── HISTORICAL_DATA_MASTER_PLAN.md (Master plan - link only)
    │   ├── HISTORICAL_DATA_QUICK_START.md
    │   ├── HISTORICAL_DATA_IMPLEMENTATION_SUMMARY.md
    │   └── API_TESTING_GUIDE.md       (Historical data APIs)
    │
    └── archive/                       📁 EXISTS - Completed/superseded docs
        ├── README.md                  (Archive index)
        ├── reports/                   📁 NEW - All temporary reports
        │   ├── BULK_OPTIMIZATION_PHASE_2_FINAL_REPORT.md
        │   ├── BULK_TEST_OPTIMIZATION_REPORT.md
        │   ├── TEST_OPTIMIZATION_REPORT.md
        │   ├── TEST_PERFORMANCE_ANALYSIS_REPORT.md
        │   ├── TEST_PERFORMANCE_FIX_REPORT.md
        │   ├── TEST_SUMMARY_CERTIFICATE_DATA_ARCHITECTURE.md
        │   ├── WEBSITE_POLICY_TEST_OPTIMIZATION.md
        │   └── RESPONSE_TIME_CHART_FIX.md
        │
        ├── sessions/                  📁 NEW - Session prompts/context
        │   ├── PHASE2_CONTINUATION_PROMPT.md
        │   ├── SESSION_CONTEXT_monitoring_data_tracking_phase2.md
        │   ├── SESSION_START_GUIDE.md
        │   └── HISTORICAL_DATA_IMPLEMENTATION.md
        │
        ├── debug-menu/                ✅ EXISTS
        ├── prompts/                   ✅ EXISTS
        ├── queue/                     ✅ EXISTS
        └── theme/                     ✅ EXISTS
```

---

## Implementation Phases

### Phase 1: Create New Folder Structure
**Agent**: None needed - simple folder creation
**Estimated Time**: 5 minutes

**Tasks**:
1. Create `docs/core/` folder
2. Create `docs/testing/` folder
3. Create `docs/styling/` folder
4. Create `docs/architecture/` folder
5. Create `docs/features/` folder
6. Create `docs/deployment/` folder
7. Create `docs/historical-data/` folder
8. Create `docs/archive/reports/` folder
9. Create `docs/archive/sessions/` folder

**Verification**:
```bash
ls -la docs/core docs/testing docs/styling docs/architecture docs/features docs/deployment docs/historical-data docs/archive/reports docs/archive/sessions
```

---

### Phase 2: Move Core Documentation
**Agent**: None needed - file moves
**Estimated Time**: 10 minutes

**Tasks**:
1. Move `docs/DEVELOPMENT_PRIMER.md` → `docs/core/DEVELOPMENT_PRIMER.md`
2. Move `docs/CODING_GUIDE.md` → `docs/core/CODING_GUIDE.md`
3. Move `docs/AGENT_USAGE_GUIDE.md` → `docs/core/AGENT_USAGE_GUIDE.md`
4. Move `docs/PERFORMANCE_WORKFLOW.md` → `docs/core/PERFORMANCE_WORKFLOW.md`

**Bash Commands**:
```bash
mv docs/DEVELOPMENT_PRIMER.md docs/core/
mv docs/CODING_GUIDE.md docs/core/
mv docs/AGENT_USAGE_GUIDE.md docs/core/
mv docs/PERFORMANCE_WORKFLOW.md docs/core/
```

---

### Phase 3: Move Testing Documentation
**Agent**: None needed - file moves + merge consideration
**Estimated Time**: 15 minutes

**Tasks**:
1. Move `docs/TESTING_INSIGHTS.md` → `docs/testing/TESTING_INSIGHTS.md`
2. Move `API_TESTING_GUIDE.md` (root) → `docs/testing/API_TESTING_GUIDE.md`
3. Move `docs/EXTERNAL_SERVICE_PATTERNS.md` → `docs/testing/EXTERNAL_SERVICE_PATTERNS.md`

**Decision Point**: Should `API_TESTING_GUIDE.md` be merged into `TESTING_INSIGHTS.md`?

**Analysis**:
- `API_TESTING_GUIDE.md`: 289 lines - Specific to historical data API endpoints
- `TESTING_INSIGHTS.md`: 1,307 lines - General testing patterns

**Recommendation**: Keep separate but reference from TESTING_INSIGHTS.md
- API_TESTING_GUIDE.md is specific to a feature (historical data)
- TESTING_INSIGHTS.md is general patterns
- Add cross-reference section in TESTING_INSIGHTS.md

**Bash Commands**:
```bash
mv docs/TESTING_INSIGHTS.md docs/testing/
mv API_TESTING_GUIDE.md docs/testing/
mv docs/EXTERNAL_SERVICE_PATTERNS.md docs/testing/
```

---

### Phase 4: Move Styling Documentation
**Agent**: None needed - file moves
**Estimated Time**: 5 minutes

**Tasks**:
1. Move `docs/TAILWIND_V4_STYLING_GUIDE.md` → `docs/styling/`
2. Move `docs/TAILWIND_V4_QUICK_REFERENCE.md` → `docs/styling/`
3. Move `docs/TAILWIND_V4_CONVERSION_SUMMARY.md` → `docs/styling/`
4. Move `docs/STYLING_GUIDE.md` → `docs/styling/`

**Bash Commands**:
```bash
mv docs/TAILWIND_V4_STYLING_GUIDE.md docs/styling/
mv docs/TAILWIND_V4_QUICK_REFERENCE.md docs/styling/
mv docs/TAILWIND_V4_CONVERSION_SUMMARY.md docs/styling/
mv docs/STYLING_GUIDE.md docs/styling/
```

---

### Phase 5: Move Architecture Documentation
**Agent**: None needed - file moves
**Estimated Time**: 5 minutes

**Tasks**:
1. Move `docs/ALERT_SYSTEM_ARCHITECTURE.md` → `docs/architecture/`
2. Move `docs/QUEUE_AND_SCHEDULER_ARCHITECTURE.md` → `docs/architecture/`
3. Move `docs/HISTORICAL_DATA_BACKEND_ARCHITECTURE.md` → `docs/architecture/`
4. Move `docs/OPTIMIZED_MONITORING_SCHEMA.md` → `docs/architecture/`
5. Move `docs/SCHEMA_OPTIMIZATION_SUMMARY.md` → `docs/architecture/`

**Bash Commands**:
```bash
mv docs/ALERT_SYSTEM_ARCHITECTURE.md docs/architecture/
mv docs/QUEUE_AND_SCHEDULER_ARCHITECTURE.md docs/architecture/
mv docs/HISTORICAL_DATA_BACKEND_ARCHITECTURE.md docs/architecture/
mv docs/OPTIMIZED_MONITORING_SCHEMA.md docs/architecture/
mv docs/SCHEMA_OPTIMIZATION_SUMMARY.md docs/architecture/
```

---

### Phase 6: Move Feature Documentation
**Agent**: None needed - file moves
**Estimated Time**: 5 minutes

**Tasks**:
1. Move `docs/TEAMS_AND_ROLES.md` → `docs/features/`
2. Move `docs/TOAST_NOTIFICATIONS.md` → `docs/features/`
3. Move `docs/SSL_CERTIFICATE_MONITORING.md` → `docs/features/`
4. Move `docs/DEBUG_LOGGING_ENHANCEMENT.md` → `docs/features/`
5. Move `docs/ALERT_TESTING_FIX_DOCUMENTATION.md` → `docs/features/`

**Bash Commands**:
```bash
mv docs/TEAMS_AND_ROLES.md docs/features/
mv docs/TOAST_NOTIFICATIONS.md docs/features/
mv docs/SSL_CERTIFICATE_MONITORING.md docs/features/
mv docs/DEBUG_LOGGING_ENHANCEMENT.md docs/features/
mv docs/ALERT_TESTING_FIX_DOCUMENTATION.md docs/features/
```

---

### Phase 7: Move Deployment Documentation
**Agent**: None needed - file moves
**Estimated Time**: 2 minutes

**Tasks**:
1. Move `docs/DEPLOYMENT.md` → `docs/deployment/`
2. Move `docs/DEPLOYMENT_GUIDE.md` → `docs/deployment/`

**Bash Commands**:
```bash
mv docs/DEPLOYMENT.md docs/deployment/
mv docs/DEPLOYMENT_GUIDE.md docs/deployment/
```

---

### Phase 8: Organize Historical Data Documentation
**Agent**: None needed - file moves + symlink
**Estimated Time**: 10 minutes

**Tasks**:
1. Move `docs/HISTORICAL_DATA_QUICK_START.md` → `docs/historical-data/`
2. Move `docs/HISTORICAL_DATA_IMPLEMENTATION_SUMMARY.md` → `docs/historical-data/`
3. Create symlink: `docs/historical-data/HISTORICAL_DATA_MASTER_PLAN.md` → `../implementation-plans/HISTORICAL_DATA_MASTER_PLAN.md`
4. Move `docs/testing/API_TESTING_GUIDE.md` → `docs/historical-data/API_TESTING_GUIDE.md`

**Reasoning**:
- API_TESTING_GUIDE.md is specifically about historical data API endpoints
- Keep HISTORICAL_DATA_MASTER_PLAN.md in implementation-plans (still active)
- Create symlink for easy reference from historical-data folder

**Bash Commands**:
```bash
mv docs/HISTORICAL_DATA_QUICK_START.md docs/historical-data/
mv docs/HISTORICAL_DATA_IMPLEMENTATION_SUMMARY.md docs/historical-data/
mv docs/testing/API_TESTING_GUIDE.md docs/historical-data/
ln -s ../implementation-plans/HISTORICAL_DATA_MASTER_PLAN.md docs/historical-data/HISTORICAL_DATA_MASTER_PLAN.md
```

---

### Phase 9: Consolidate Phase Implementation Docs
**Agent**: `documentation-writer`
**Estimated Time**: 30 minutes

**Tasks**:
1. Consolidate Phase 1-4 implementation prompts into completed documentation
2. Create `docs/implementation-finished/README.md` index
3. Move completed implementations from `implementation-plans/` to `implementation-finished/`

**Files to Consolidate**:
- `docs/PHASE1_IMPLEMENTATION_PROMPT.md` (390 lines)
- `docs/PHASE2_IMPLEMENTATION_PROMPT.md` (654 lines)
- `docs/PHASE3_IMPLEMENTATION_COMPLETE.md` (461 lines)
- `docs/PHASE3_IMPLEMENTATION_PROMPT.md` (458 lines)
- `docs/PHASE4_IMPLEMENTATION_COMPLETE.md` (1,143 lines)
- `docs/PHASE4_PART1_PART3_IMPLEMENTATION_COMPLETE.md` (856 lines)

**Agent Prompt**:
```markdown
Review and consolidate Phase 1-4 implementation documentation:

1. Create `docs/implementation-finished/README.md` - Index of completed implementations
2. Consolidate Phase 1 docs into `docs/implementation-finished/PHASE1_HISTORICAL_DATA.md`
3. Consolidate Phase 2 docs into `docs/implementation-finished/PHASE2_HISTORICAL_DATA.md`
4. Consolidate Phase 3 docs into `docs/implementation-finished/PHASE3_HISTORICAL_DATA.md`
5. Consolidate Phase 4 docs into `docs/implementation-finished/PHASE4_HISTORICAL_DATA.md`

Each consolidated document should include:
- Implementation summary
- Features implemented
- Files modified/created
- Test coverage
- Status: ✅ Complete

Extract key information from completion reports, remove duplicates, and create clean historical records.
```

---

### Phase 10: Archive Temporary Files from Root
**Agent**: None needed - file moves
**Estimated Time**: 10 minutes

**Tasks**:
1. Move all temporary reports to `docs/archive/reports/`
2. Move all session/prompt files to `docs/archive/sessions/`

**Bash Commands**:
```bash
# Move temporary reports
mv BULK_OPTIMIZATION_PHASE_2_FINAL_REPORT.md docs/archive/reports/
mv BULK_TEST_OPTIMIZATION_REPORT.md docs/archive/reports/
mv TEST_OPTIMIZATION_REPORT.md docs/archive/reports/
mv TEST_PERFORMANCE_ANALYSIS_REPORT.md docs/archive/reports/
mv TEST_PERFORMANCE_FIX_REPORT.md docs/archive/reports/
mv TEST_SUMMARY_CERTIFICATE_DATA_ARCHITECTURE.md docs/archive/reports/
mv WEBSITE_POLICY_TEST_OPTIMIZATION.md docs/archive/reports/
mv RESPONSE_TIME_CHART_FIX.md docs/archive/reports/

# Move session/prompt files
mv PHASE2_CONTINUATION_PROMPT.md docs/archive/sessions/
mv SESSION_CONTEXT_monitoring_data_tracking_phase2.md docs/archive/sessions/
mv SESSION_START_GUIDE.md docs/archive/sessions/
mv HISTORICAL_DATA_IMPLEMENTATION.md docs/archive/sessions/
```

---

### Phase 11: Update Archive README
**Agent**: `documentation-writer`
**Estimated Time**: 10 minutes

**Agent Prompt**:
```markdown
Update `docs/archive/README.md` to include:

1. New archive/reports/ folder with list of performance/optimization reports
2. New archive/sessions/ folder with list of session prompts and context
3. Update index with all archived content
4. Add "Why Archived" explanations for each category
5. Link to current active documentation

Keep existing archive content (debug-menu, prompts, queue, theme folders).
```

---

### Phase 12: Update Main Documentation README
**Agent**: `documentation-writer`
**Estimated Time**: 30 minutes

**Agent Prompt**:
```markdown
Completely rewrite `docs/README.md` with the new folder structure:

1. Update test suite status: 669 tests passing, 12 skipped
2. Update "Last Updated" date to October 27, 2025
3. Update documentation count (24+ active files)
4. Reorganize by new folder structure:
   - Core Development Guides (docs/core/)
   - Testing Documentation (docs/testing/)
   - Styling & Frontend (docs/styling/)
   - Architecture Documentation (docs/architecture/)
   - Feature Documentation (docs/features/)
   - Deployment Guides (docs/deployment/)
   - Implementation Plans (docs/implementation-plans/)
   - Completed Implementations (docs/implementation-finished/)
   - Historical Data Project (docs/historical-data/)
   - Archived Documentation (docs/archive/)

5. Update "Finding What You Need" tables with new paths
6. Add SSL Certificate Monitoring (Dynamic Thresholds) to recent updates
7. Keep all existing "By Task" and "By Role" sections, update paths
8. Update quality metrics

Follow the same structure and style as the current README.md but reflect new organization.
```

---

### Phase 13: Update Cross-References
**Agent**: `documentation-writer`
**Estimated Time**: 20 minutes

**Agent Prompt**:
```markdown
Update cross-references in key documentation files to reflect new folder structure:

Files to update:
1. `docs/core/DEVELOPMENT_PRIMER.md` - Update all docs references
2. `docs/testing/TESTING_INSIGHTS.md` - Add reference to API_TESTING_GUIDE.md
3. `docs/implementation-plans/README.md` - Update with current plans
4. `docs/implementation-finished/README.md` - List all completed implementations
5. `CLAUDE.md` - Update documentation references if needed

Search for broken links and update paths from root:
- `docs/DEVELOPMENT_PRIMER.md` → `docs/core/DEVELOPMENT_PRIMER.md`
- `docs/TESTING_INSIGHTS.md` → `docs/testing/TESTING_INSIGHTS.md`
- etc.

Ensure all internal documentation links work correctly.
```

---

### Phase 14: Verify and Test
**Agent**: None needed - manual verification
**Estimated Time**: 15 minutes

**Verification Checklist**:
```bash
# Check root directory is clean (only CLAUDE.md and README.md)
ls -la *.md

# Check new folder structure exists
ls -la docs/core docs/testing docs/styling docs/architecture docs/features docs/deployment docs/historical-data

# Check archive folders
ls -la docs/archive/reports docs/archive/sessions

# Verify no broken symlinks
find docs/ -type l -exec test ! -e {} \; -print

# Count documentation files by category
find docs/core -name "*.md" | wc -l
find docs/testing -name "*.md" | wc -l
find docs/styling -name "*.md" | wc -l
find docs/architecture -name "*.md" | wc -l
find docs/features -name "*.md" | wc -l
find docs/deployment -name "*.md" | wc -l
find docs/historical-data -name "*.md" | wc -l
find docs/implementation-plans -name "*.md" | wc -l
find docs/implementation-finished -name "*.md" | wc -l
find docs/archive -name "*.md" | wc -l
```

**Expected Results**:
- Root: 2 files (CLAUDE.md, README.md)
- docs/core: 4 files
- docs/testing: 3 files
- docs/styling: 4 files
- docs/architecture: 5 files
- docs/features: 5 files
- docs/deployment: 2 files
- docs/historical-data: 4 files (including symlink)
- docs/implementation-plans: 7 files
- docs/implementation-finished: 6 files (including README)
- docs/archive: Existing + new reports/sessions folders

---

## Agent Usage Strategy

### Phase 9: Consolidate Phase Docs
**Agent**: `documentation-writer`
**Why**: Needs to extract, consolidate, and format historical implementation documentation

### Phase 11: Update Archive README
**Agent**: `documentation-writer`
**Why**: Creating comprehensive archive index with explanations

### Phase 12: Update Main README
**Agent**: `documentation-writer`
**Why**: Complete rewrite of documentation index with new structure

### Phase 13: Update Cross-References
**Agent**: `documentation-writer`
**Why**: Finding and updating all internal documentation links

### All Other Phases
**Manual Execution**: Simple file/folder operations with bash commands

---

## Benefits of New Structure

### 1. **Clean Root Directory**
- Only essential files (CLAUDE.md, README.md)
- No temporary/outdated documentation clutter
- Professional appearance

### 2. **Category-Based Organization**
- Clear purpose for each folder
- Easy to find relevant documentation
- Logical grouping by concern

### 3. **Separation of Active vs. Completed**
- `implementation-plans/` = Active planning
- `implementation-finished/` = Historical record
- `archive/` = Obsolete/temporary

### 4. **Single Source of Truth**
- No duplicate documentation
- Clear where to look for each topic
- Reduced confusion

### 5. **Improved Maintainability**
- Easy to add new documentation
- Clear where new docs belong
- Simpler to update related docs

### 6. **Better Onboarding**
- New developers can navigate easily
- Clear learning path (core → testing → features)
- Comprehensive but organized

---

## Success Criteria

- ✅ Root directory contains only CLAUDE.md and README.md
- ✅ All documentation organized into logical folders
- ✅ No duplicate documentation
- ✅ All internal links working
- ✅ Archive folder contains all temporary/obsolete docs
- ✅ Implementation plans clearly separated from completed work
- ✅ Updated docs/README.md reflects new structure
- ✅ Cross-references updated in key documents
- ✅ All 669 tests still passing (no functional code changes)

---

## Rollback Plan

If issues arise during reorganization:

1. **Backup first**:
   ```bash
   cp -r docs docs.backup
   cp *.md root_md_backup/
   ```

2. **Git provides safety**: All changes tracked in version control

3. **Incremental approach**: Test each phase before proceeding

4. **Documentation links**: Use find/replace to bulk update if needed

---

## Estimated Total Time

- Phase 1-8 (Folder creation + moves): 1 hour
- Phase 9 (Consolidation): 30 minutes
- Phase 10 (Archive): 10 minutes
- Phase 11-13 (Documentation updates): 1 hour
- Phase 14 (Verification): 15 minutes

**Total: ~2.5-3 hours**

---

## Post-Reorganization Maintenance

### Documentation Update Guidelines

When creating new documentation:
1. **Core guides**: Place in `docs/core/`
2. **Testing docs**: Place in `docs/testing/`
3. **Feature docs**: Place in `docs/features/`
4. **Implementation plans**: Place in `docs/implementation-plans/`
5. **Completed implementations**: Move to `docs/implementation-finished/`
6. **Temporary reports**: Place in `docs/archive/reports/`

### Quarterly Cleanup

Every 3 months:
1. Review `docs/implementation-plans/` - Move completed to `implementation-finished/`
2. Review `docs/archive/reports/` - Delete very old temporary reports (> 6 months)
3. Update `docs/README.md` with latest stats
4. Verify all cross-references still valid

---

## Related Documentation

- **Current docs/README.md**: See line counts and current organization
- **docs/DOCUMENTATION_REORGANIZATION_PROPOSAL.md**: Previous reorganization proposal (if exists)
- **CLAUDE.md**: AI assistant instructions (no changes needed)

---

## Notes

- This reorganization is purely structural - no code changes
- All 669 tests will continue to pass
- Git history preserved for all files
- Can be done incrementally over multiple sessions
- Safe to interrupt and resume at any phase

---

**Ready to execute when approved. Start with Phase 1 and proceed sequentially.**
