# Teams & Roles - Quick Reference

## 🎯 Quick Role Overview

| Role | Key Abilities | Cannot Do |
|------|---------------|-----------|
| **OWNER** | Everything | Change own role without another owner |
| **ADMIN** | Manage websites, invite members, change VIEWER/ADMIN roles | Touch OWNER role, delete team |
| **VIEWER** | View everything | Modify anything |

---

## 🔑 Permission Cheatsheet

### Team Management
```
Create team          → Anyone (becomes OWNER)
Update team name     → OWNER only
Delete team          → OWNER only
Transfer ownership   → OWNER only
```

### Member Management
```
Invite members       → OWNER, ADMIN
Remove members       → OWNER only
Change roles         → See table below
```

### Website Management
```
View websites        → OWNER, ADMIN, VIEWER
Add/Edit/Delete      → OWNER, ADMIN
Transfer to/from     → OWNER, ADMIN
```

---

## 👥 Who Can Change Roles?

| From → To | OWNER | ADMIN | VIEWER |
|-----------|-------|-------|--------|
| **VIEWER → ADMIN** | ✓ | ✓ | ✗ |
| **VIEWER → OWNER** | ✓ | ✗ | ✗ |
| **ADMIN → VIEWER** | ✓ | ✓ | ✗ |
| **ADMIN → OWNER** | ✓ | ✗ | ✗ |
| **OWNER → ADMIN** | ✓ | ✗ | ✗ |
| **OWNER → VIEWER** | ✓ | ✗ | ✗ |

**Golden Rule:** ✗ Nobody can change their own role

---

## 📋 Common API Calls

### Create Team
```http
POST /settings/team
{
  "name": "Team Name",
  "description": "Optional description"
}
```

### Invite Member
```http
POST /settings/team/{team_id}/invite
{
  "email": "user@example.com",
  "role": "ADMIN"  // or "OWNER", "VIEWER"
}
```

### Change Member Role
```http
PATCH /settings/team/{team_id}/members/{user_id}/role
{
  "role": "ADMIN"
}
```

### Transfer Ownership
```http
POST /settings/team/{team_id}/transfer-ownership
{
  "new_owner_id": 42
}
```

### Transfer Website to Team
```http
POST /ssl/websites/{website_id}/transfer-to-team
{
  "team_id": 5
}
```

### Transfer Website to Personal
```http
POST /ssl/websites/{website_id}/transfer-to-personal
```

---

## ⚡ Quick Scenarios

### Scenario: New Team Member
```javascript
// 1. Owner invites as VIEWER first
POST /settings/team/1/invite { email: "...", role: "VIEWER" }

// 2. Member accepts invitation (via email link)

// 3. Later, Owner or Admin promotes to ADMIN
PATCH /settings/team/1/members/10/role { role: "ADMIN" }
```

### Scenario: Transfer Leadership
```javascript
// Old owner transfers to new owner
POST /settings/team/1/transfer-ownership { new_owner_id: 42 }

// Result: New owner is OWNER, old owner becomes ADMIN
```

### Scenario: Share Website with Team
```javascript
// Transfer personal website to team
POST /ssl/websites/100/transfer-to-team { team_id: 1 }

// All team members can now see it
// OWNER and ADMIN can edit it
```

### Scenario: Remove Team Member
```javascript
// Only OWNER can do this
DELETE /settings/team/1/members/25
```

---

## ⚠️ Common Errors & Fixes

| Error | Fix |
|-------|-----|
| "You do not have permission" | Check your role - you may need OWNER |
| "Cannot demote the last owner" | Promote another member to OWNER first |
| "You cannot change your own role" | Ask another team member to change it |
| "Only team owners can change OWNER role" | Get a team OWNER to make the change |
| "The new owner must be a team member" | Invite them to the team first |

---

## 🎨 Role Use Cases

### OWNER
**When to use:**
- Team creators
- Team leads / managers
- People who need full control
- **Recommended:** Have 2+ owners for redundancy

### ADMIN
**When to use:**
- Senior developers
- DevOps engineers
- People who actively manage monitoring
- Team members who onboard new members

### VIEWER
**When to use:**
- Junior team members
- External consultants
- Stakeholders who just need visibility
- Trial period for new members

---

## 🔄 Lifecycle of a Team Website

```
Personal Website
    ↓ (Owner/Admin transfers to team)
Team Website
    ↓ (Visible to all members)
    ↓ (Editable by Owner/Admin only)
    ↓ (Owner/Admin transfers back)
Personal Website
```

---

## 💡 Pro Tips

1. **Start restrictive, upgrade later**
   - New members → VIEWER
   - Proven contributors → ADMIN
   - Team leads → OWNER

2. **Always have 2+ OWNERs**
   - Prevents lockout if primary owner unavailable
   - Enables ownership transfer if needed

3. **ADMIN is the sweet spot**
   - Can manage day-to-day operations
   - Can't accidentally delete the team
   - Can handle most tasks independently

4. **Use teams for shared responsibility**
   - Production monitoring → Team
   - Personal projects → Personal
   - Client sites → Separate teams per client

5. **Regular role audits**
   - Review members quarterly
   - Remove departed team members
   - Adjust roles as responsibilities change

---

## 📊 Decision Matrix

**Should I create a team?**

| Scenario | Team? |
|----------|-------|
| Multiple people need access | ✓ Yes |
| Only you monitor sites | ✗ No, keep personal |
| Shared responsibility | ✓ Yes |
| Different permission levels needed | ✓ Yes |
| One-person project | ✗ No, keep personal |

**Which role should I assign?**

| Need | Role |
|------|------|
| Just visibility | VIEWER |
| Manage websites daily | ADMIN |
| Full team control | OWNER |
| Trial/temporary access | VIEWER (upgrade later) |

---

## 🚀 Getting Started Checklist

- [ ] Create team with descriptive name
- [ ] Add team description
- [ ] Invite initial members (start with VIEWER)
- [ ] Promote trusted members to ADMIN
- [ ] Assign second OWNER for redundancy
- [ ] Transfer relevant websites to team
- [ ] Configure team alert settings
- [ ] Set up email notifications

---

For comprehensive documentation, see [TEAMS_AND_ROLES.md](TEAMS_AND_ROLES.md)

**Last Updated:** October 7, 2025
