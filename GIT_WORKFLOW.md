# Git Flow Workflow for SSL Monitor v3

## 🔄 Overview

SSL Monitor v3 follows **Git Flow branching model** for professional software development with clear separation between development, features, releases, and production code.

**Git Flow Benefits:**
- Clean separation between stable and development code
- Parallel feature development without conflicts
- Controlled release process with testing phases
- Emergency hotfix capability for production issues
- Clear history and auditable development process

---

## 🌳 Branch Structure

### Core Branches

#### `main` - Production Branch
- **Purpose**: Production-ready releases only
- **Protection**: Requires PR review and passing tests
- **Merges From**: `release/*` and `hotfix/*` branches only
- **Direct Commits**: ❌ Never commit directly to main

#### `develop` - Integration Branch
- **Purpose**: Integration point for all feature development
- **Current State**: Latest development version
- **Merges From**: `feature/*`, `release/*`, and `hotfix/*` branches
- **Direct Commits**: ⚠️ Only for minor fixes and documentation

### Supporting Branches

#### `feature/*` - Feature Development
- **Naming**: `feature/description-of-feature`
- **Branch From**: `develop`
- **Merge To**: `develop` (via Pull Request)
- **Lifecycle**: Created for feature → developed → merged → deleted

#### `release/*` - Release Preparation
- **Naming**: `release/v1.0.0`
- **Branch From**: `develop`
- **Merge To**: `main` and `develop`
- **Purpose**: Final preparation, bug fixes, version bumping

#### `hotfix/*` - Emergency Production Fixes
- **Naming**: `hotfix/critical-issue-description`
- **Branch From**: `main`
- **Merge To**: `main` and `develop`
- **Purpose**: Critical production issues that can't wait for next release

---

## 🚀 Workflow Examples

### 1. Initial Repository Setup

```bash
# Clone repository
git clone <repository-url> ssl-monitor-v3
cd ssl-monitor-v3

# Create and setup develop branch
git checkout -b develop
git push -u origin develop

# Set up branch protection rules (done via GitHub/GitLab interface)
# - main: Require PR review + passing tests
# - develop: Require PR review
```

### 2. Feature Development Workflow

#### Starting a New Feature
```bash
# Ensure you're on latest develop
git checkout develop
git pull origin develop

# Create feature branch
git checkout -b feature/vristo-template-integration
git push -u origin feature/vristo-template-integration

# Verify branch setup
git branch -vv
```

#### Working on Feature
```bash
# Make changes and commit regularly
git add .
git commit -m "Add VRISTO sidebar component with Vue integration"

git add .
git commit -m "Implement responsive mobile sidebar behavior"

git add .
git commit -m "Add Alpine.js store for theme management"

# Push changes regularly
git push origin feature/vristo-template-integration
```

#### Completing Feature
```bash
# Ensure all changes are committed and pushed
git status
git push origin feature/vristo-template-integration

# Create Pull Request via GitHub/GitLab interface
# Title: "Implement VRISTO Template Integration"
# Description: Detailed feature description and testing notes
# Target: feature/vristo-template-integration → develop

# After PR approval and merge, cleanup
git checkout develop
git pull origin develop
git branch -d feature/vristo-template-integration
git push origin --delete feature/vristo-template-integration
```

### 3. Parallel Feature Development

```bash
# Developer A: Working on authentication
git checkout develop
git pull origin develop
git checkout -b feature/authentication-system
# ... work on authentication ...

# Developer B: Working on dashboard (simultaneously)
git checkout develop
git pull origin develop
git checkout -b feature/dashboard-ui
# ... work on dashboard ...

# Both features can be developed in parallel
# Each creates separate PR to develop
# Merge conflicts resolved during PR process
```

### 4. Release Process

#### Preparing Release
```bash
# When develop is ready for release
git checkout develop
git pull origin develop

# Create release branch
git checkout -b release/v1.0.0
git push -u origin release/v1.0.0

# Update version numbers in files
# composer.json, package.json, etc.
git add .
git commit -m "Bump version to v1.0.0"

# Final testing and bug fixes on release branch
git add .
git commit -m "Fix responsive design issue in website table"

git add .
git commit -m "Update changelog for v1.0.0"
```

#### Completing Release
```bash
# Merge to main (production)
git checkout main
git pull origin main
git merge --no-ff release/v1.0.0
git tag -a v1.0.0 -m "SSL Monitor v3 v1.0.0 - Initial release"
git push origin main --tags

# Merge back to develop (sync any release fixes)
git checkout develop
git merge --no-ff release/v1.0.0
git push origin develop

# Cleanup release branch
git branch -d release/v1.0.0
git push origin --delete release/v1.0.0
```

### 5. Hotfix Process

#### Emergency Production Fix
```bash
# Critical issue discovered in production
git checkout main
git pull origin main

# Create hotfix branch
git checkout -b hotfix/ssl-certificate-validation-bug
git push -u origin hotfix/ssl-certificate-validation-bug

# Fix the issue
git add .
git commit -m "Fix SSL certificate validation logic for wildcard certificates"

# Test the fix thoroughly
git add .
git commit -m "Add test coverage for wildcard SSL certificate validation"
```

#### Deploying Hotfix
```bash
# Merge to main
git checkout main
git merge --no-ff hotfix/ssl-certificate-validation-bug
git tag -a v1.0.1 -m "SSL Monitor v3 v1.0.1 - Fix SSL validation bug"
git push origin main --tags

# Merge to develop
git checkout develop
git merge --no-ff hotfix/ssl-certificate-validation-bug
git push origin develop

# Cleanup
git branch -d hotfix/ssl-certificate-validation-bug
git push origin --delete hotfix/ssl-certificate-validation-bug
```

---

## 📋 Branch Naming Conventions

### Feature Branches
```bash
feature/vristo-template-integration
feature/ssl-monitoring-backend
feature/team-collaboration-ui
feature/email-notification-system
feature/uptime-monitoring-advanced
feature/dashboard-real-time-updates
feature/user-authentication-flow
feature/website-management-crud
```

### Release Branches
```bash
release/v1.0.0          # Major release
release/v1.1.0          # Minor release
release/v1.0.1          # Patch release
```

### Hotfix Branches
```bash
hotfix/ssl-validation-error
hotfix/memory-leak-dashboard
hotfix/security-authentication-bypass
hotfix/data-loss-website-deletion
```

---

## 💬 Commit Message Standards

### Format
```
<type>(scope): <description>

[optional body]

[optional footer]
```

### Types
- **feat**: New feature
- **fix**: Bug fix
- **docs**: Documentation changes
- **style**: Code style changes (formatting, missing semicolons, etc.)
- **refactor**: Code refactoring without changing functionality
- **test**: Adding or updating tests
- **chore**: Build process or auxiliary tool changes

### Examples
```bash
feat(auth): add Laravel Breeze integration with VRISTO template

Implement user authentication system using Laravel Breeze
with custom VRISTO-styled login and registration pages.
Includes email verification and password reset flows.

- Add VristoAuthLayout.vue component
- Create custom login form with VRISTO styling
- Implement responsive design for mobile devices
- Add form validation with real-time feedback

Closes #123
```

```bash
fix(ssl): resolve wildcard certificate validation issue

Fix SSL certificate validation logic that was incorrectly
rejecting valid wildcard certificates (*.example.com).

The issue was in the domain matching algorithm which didn't
properly handle wildcard subdomain validation.

- Update SslCertificateValidator::validateDomain()
- Add comprehensive test coverage for wildcard certificates
- Fix edge case with multiple subdomain levels

Fixes #456
```

```bash
docs(api): update API endpoint documentation

- Add examples for website management endpoints
- Update authentication requirements
- Fix typos in response format examples
```

---

## 🔀 Pull Request Process

### 1. Pre-PR Checklist
```bash
# Before creating PR, ensure:
☐ All tests passing locally
☐ Code formatted with Laravel Pint
☐ No merge conflicts with target branch
☐ Feature fully implemented and tested
☐ Documentation updated if needed
```

### 2. PR Creation
**Title Format**: `[Feature/Fix/Docs] Brief description`
**Description Template**:
```markdown
## Description
Brief description of changes made.

## Type of Change
- [ ] Bug fix (non-breaking change which fixes an issue)
- [ ] New feature (non-breaking change which adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Documentation update

## Testing
- [ ] Tests pass locally
- [ ] Added tests for new functionality
- [ ] Tested on multiple browsers/devices (if UI changes)

## Checklist
- [ ] Code follows project style guidelines
- [ ] Self-review completed
- [ ] Documentation updated
- [ ] No breaking changes without migration path

## Screenshots (if applicable)
[Add screenshots for UI changes]
```

### 3. PR Review Process
1. **Automated Checks**: Tests, linting, security scans
2. **Code Review**: At least one reviewer approval required
3. **Testing**: Manual testing of new functionality
4. **Approval**: Final approval from team lead/maintainer
5. **Merge**: Squash and merge to keep clean history

---

## 🛠️ Git Commands Cheat Sheet

### Daily Development
```bash
# Start new feature
git checkout develop && git pull origin develop
git checkout -b feature/my-new-feature

# Work on feature
git add .
git commit -m "Implement feature component"
git push origin feature/my-new-feature

# Update with latest develop changes
git checkout develop && git pull origin develop
git checkout feature/my-new-feature
git rebase develop

# Finish feature (after PR merge)
git checkout develop && git pull origin develop
git branch -d feature/my-new-feature
```

### Release Management
```bash
# Create release
git checkout develop && git pull origin develop
git checkout -b release/v1.1.0

# Finish release
git checkout main && git pull origin main
git merge --no-ff release/v1.1.0
git tag -a v1.1.0 -m "Release v1.1.0"
git push origin main --tags

git checkout develop
git merge --no-ff release/v1.1.0
git push origin develop
```

### Emergency Fixes
```bash
# Create hotfix
git checkout main && git pull origin main
git checkout -b hotfix/critical-fix

# Deploy hotfix
git checkout main
git merge --no-ff hotfix/critical-fix
git tag -a v1.0.1 -m "Hotfix v1.0.1"
git push origin main --tags

git checkout develop
git merge --no-ff hotfix/critical-fix
git push origin develop
```

---

## 🔍 Troubleshooting Common Scenarios

### Merge Conflicts
```bash
# During rebase or merge
git status                          # See conflicted files
# Edit files to resolve conflicts
git add .                          # Mark as resolved
git rebase --continue              # Continue rebase
# OR
git commit                         # Complete merge
```

### Accidentally Committed to Wrong Branch
```bash
# Move commits to correct branch
git reset --soft HEAD~3            # Undo last 3 commits (keep changes)
git stash                          # Stash changes
git checkout correct-branch
git stash pop                      # Apply changes
git add . && git commit -m "Move commits to correct branch"
```

### Need to Update Feature Branch with Latest Develop
```bash
# Interactive rebase (preferred)
git checkout feature/my-feature
git rebase -i develop

# Or merge (creates merge commit)
git checkout feature/my-feature
git merge develop
```

### Squash Multiple Commits Before PR
```bash
# Interactive rebase to squash commits
git rebase -i HEAD~5               # Last 5 commits
# In editor: change 'pick' to 'squash' for commits to combine
# Edit commit message
git push --force-with-lease origin feature/my-feature
```

---

## 📊 Branch Protection Rules

### `main` Branch Protection
```yaml
Required Checks:
  - Tests pass (PHPUnit, Pest)
  - Code quality (Laravel Pint)
  - Security scan
  - Build succeeds

Restrictions:
  - Require pull request reviews (minimum 1)
  - Dismiss stale reviews when new commits pushed
  - Require review from code owners
  - Restrict pushes to matching branches
  - Force push disabled

Branch Rules:
  - Delete head branches automatically after merge
  - Allow squash merging only
```

### `develop` Branch Protection
```yaml
Required Checks:
  - Tests pass
  - Code quality checks

Restrictions:
  - Require pull request reviews (minimum 1)
  - Allow force pushes for maintainers only
  - Allow merge commits and squash merging
```

---

## 🎯 Git Flow Best Practices

### Do's ✅
- **Always** create feature branches from latest `develop`
- **Always** use Pull Requests for merging
- **Keep** commits atomic and well-described
- **Test** thoroughly before creating PR
- **Rebase** feature branches to maintain clean history
- **Delete** feature branches after successful merge
- **Use** conventional commit messages
- **Tag** all releases with semantic versioning

### Don'ts ❌
- **Never** commit directly to `main` or `develop`
- **Never** force push to shared branches
- **Don't** mix multiple features in one branch
- **Don't** commit work-in-progress without clear marking
- **Don't** leave branches abandoned without cleanup
- **Avoid** large, monolithic commits
- **Don't** merge without proper review and testing

---

## 📈 Success Metrics

### Development Efficiency
- **Feature Development Time**: Average time from branch creation to merge
- **Review Cycle Time**: Time from PR creation to approval
- **Bug Fix Time**: Time from issue discovery to production fix
- **Release Frequency**: Regular, predictable release schedule

### Code Quality
- **Test Coverage**: >90% coverage maintained
- **Review Thoroughness**: All PRs reviewed by at least one team member
- **Deployment Success Rate**: >95% successful deployments
- **Rollback Frequency**: <5% of releases require rollback

### Team Collaboration
- **Parallel Development**: Multiple features developed simultaneously without conflicts
- **Knowledge Sharing**: Regular code reviews facilitate knowledge transfer
- **Documentation**: All major features documented before merge
- **Process Compliance**: 100% adherence to Git Flow process

---

This Git Flow workflow ensures professional development practices, clear code history, and reliable release management for SSL Monitor v3.