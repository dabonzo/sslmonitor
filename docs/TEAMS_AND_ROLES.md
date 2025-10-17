# Teams and Roles Documentation

## Table of Contents
- [Quick Reference](#quick-reference) ⚡ **Start Here**
- [Overview](#overview)
- [Roles](#roles)
- [Permissions Matrix](#permissions-matrix)
- [Team Management](#team-management)
- [Website Management](#website-management)
- [API Endpoints](#api-endpoints)
- [Common Workflows](#common-workflows)

---

## Quick Reference

### 🎯 Quick Role Overview

| Role | Key Abilities | Cannot Do |
|------|---------------|-----------|
| **OWNER** | Everything | Change own role without another owner |
| **ADMIN** | Manage websites, invite members, change VIEWER/ADMIN roles | Touch OWNER role, delete team |
| **VIEWER** | View everything | Modify anything |

### 🔑 Permission Cheatsheet

#### Team Management
```
Create team          → Anyone (becomes OWNER)
Update team name     → OWNER only
Delete team          → OWNER only
Transfer ownership   → OWNER only
```

#### Member Management
```
Invite members       → OWNER, ADMIN
Remove members       → OWNER only
Change roles         → See table below
```

#### Website Management
```
View websites        → OWNER, ADMIN, VIEWER
Add/Edit/Delete      → OWNER, ADMIN
Transfer to/from     → OWNER, ADMIN
```

### 👥 Who Can Change Roles?

| From → To | OWNER | ADMIN | VIEWER |
|-----------|-------|-------|--------|
| **VIEWER → ADMIN** | ✓ | ✓ | ✗ |
| **VIEWER → OWNER** | ✓ | ✗ | ✗ |
| **ADMIN → VIEWER** | ✓ | ✓ | ✗ |
| **ADMIN → OWNER** | ✓ | ✗ | ✗ |
| **OWNER → ADMIN** | ✓ | ✗ | ✗ |
| **OWNER → VIEWER** | ✓ | ✗ | ✗ |

**Golden Rule:** ✗ Nobody can change their own role

### ⚡ Quick Scenarios

#### Scenario: New Team Member
```javascript
// 1. Owner invites as VIEWER first
POST /settings/team/1/invite { email: "...", role: "VIEWER" }

// 2. Member accepts invitation (via email link)

// 3. Later, Owner or Admin promotes to ADMIN
PATCH /settings/team/1/members/10/role { role: "ADMIN" }
```

#### Scenario: Transfer Leadership
```javascript
// Old owner transfers to new owner
POST /settings/team/1/transfer-ownership { new_owner_id: 42 }

// Result: New owner is OWNER, old owner becomes ADMIN
```

#### Scenario: Share Website with Team
```javascript
// Transfer personal website to team
POST /ssl/websites/100/transfer-to-team { team_id: 1 }

// All team members can now see it
// OWNER and ADMIN can edit it
```

### 💡 Pro Tips

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

### 🚀 Getting Started Checklist

- [ ] Create team with descriptive name
- [ ] Add team description
- [ ] Invite initial members (start with VIEWER)
- [ ] Promote trusted members to ADMIN
- [ ] Assign second OWNER for redundancy
- [ ] Transfer relevant websites to team
- [ ] Configure team alert settings
- [ ] Set up email notifications

---

## Overview

SSL Monitor v4 supports **team-based collaboration** allowing multiple users to work together on monitoring websites. The system uses a **3-tier role hierarchy**:

- **OWNER** - Full team control
- **ADMIN** - Manage websites and members
- **VIEWER** - Read-only access

### Key Features
✅ Multiple teams per user
✅ Shared website monitoring
✅ Role-based access control
✅ Team ownership transfer
✅ Invite members via email

---

## Roles

### OWNER
**Description:** Full access - manage team, websites, settings, can transfer/delete team

**Capabilities:**
- Full control over team settings
- Transfer team ownership
- Delete the team
- Promote/demote any member (including to/from OWNER)
- Manage all websites
- Manage email/alert settings
- Invite and remove members

**Limitations:**
- Cannot change their own role (must ask another owner or transfer ownership first)
- Cannot delete themselves if they're the last owner

**Count per Team:** One or more (recommended to have at least 2 for redundancy)

---

### ADMIN
**Description:** Manage websites, email settings, and invite members

**Capabilities:**
- Add, edit, delete team websites
- Manage alert/email configurations
- Invite new members
- Change roles between VIEWER ↔ ADMIN
- View all team websites and settings

**Limitations:**
- Cannot manage the team itself (can't delete team or change team name)
- Cannot touch OWNER role (can't promote to OWNER or demote from OWNER)
- Cannot transfer team ownership
- Cannot change their own role
- Cannot remove members

**Count per Team:** Unlimited

---

### VIEWER
**Description:** View-only access to websites and settings

**Capabilities:**
- View all team websites
- View monitoring status and alerts
- View team settings (read-only)
- View team members list

**Limitations:**
- Cannot modify anything
- Cannot add/edit/delete websites
- Cannot change settings
- Cannot invite members
- Cannot change anyone's role

**Count per Team:** Unlimited

---

## Permissions Matrix

| Permission | OWNER | ADMIN | VIEWER |
|-----------|-------|-------|--------|
| **Team Management** | | | |
| View team details | ✓ | ✓ | ✓ |
| Update team name/description | ✓ | ✗ | ✗ |
| Delete team | ✓ | ✗ | ✗ |
| Transfer ownership | ✓ | ✗ | ✗ |
| **Member Management** | | | |
| View members | ✓ | ✓ | ✓ |
| Invite members | ✓ | ✓ | ✗ |
| Remove members | ✓ | ✗ | ✗ |
| Change VIEWER ↔ ADMIN | ✓ | ✓ | ✗ |
| Promote to OWNER | ✓ | ✗ | ✗ |
| Demote from OWNER | ✓ | ✗ | ✗ |
| Change own role | ✗ | ✗ | ✗ |
| **Website Management** | | | |
| View websites | ✓ | ✓ | ✓ |
| Add websites | ✓ | ✓ | ✗ |
| Edit websites | ✓ | ✓ | ✗ |
| Delete websites | ✓ | ✓ | ✗ |
| Transfer websites to/from team | ✓ | ✓ | ✗ |
| **Settings** | | | |
| View alert settings | ✓ | ✓ | ✓ |
| Manage alert settings | ✓ | ✓ | ✗ |
| Manage email notifications | ✓ | ✓ | ✗ |

---

## Team Management

### Creating a Team

```http
POST /settings/team
```

**Parameters:**
- `name` (required, string, max:255)
- `description` (optional, string, max:1000)

**Result:** Creator automatically becomes the OWNER

**Example:**
```javascript
router.post('/settings/team', {
  name: 'Production Team',
  description: 'Monitors all production websites'
});
```

---

### Inviting Members

```http
POST /settings/team/{team}/invite
```

**Who Can Invite:** OWNER, ADMIN

**Parameters:**
- `email` (required, email)
- `role` (required, one of: OWNER, ADMIN, VIEWER)

**Process:**
1. System sends email invitation
2. User clicks link and accepts/declines
3. If accepted, user is added with specified role

**Notes:**
- Cannot invite existing team members
- Cannot have duplicate pending invitations
- Invitations expire after 7 days

**Example:**
```javascript
router.post(`/settings/team/${teamId}/invite`, {
  email: 'colleague@example.com',
  role: 'ADMIN'
});
```

---

### Changing Member Roles

```http
PATCH /settings/team/{team}/members/{user}/role
```

**Who Can Change Roles:**

| Current Role → New Role | OWNER | ADMIN | VIEWER |
|------------------------|-------|-------|--------|
| VIEWER → ADMIN | ✓ | ✓ | ✗ |
| VIEWER → OWNER | ✓ | ✗ | ✗ |
| ADMIN → VIEWER | ✓ | ✓ | ✗ |
| ADMIN → OWNER | ✓ | ✗ | ✗ |
| OWNER → ADMIN | ✓ | ✗ | ✗ |
| OWNER → VIEWER | ✓ | ✗ | ✗ |

**Parameters:**
- `role` (required, one of: OWNER, ADMIN, VIEWER)

**Rules:**
- ✗ Cannot change your own role (ask another member)
- ✗ Cannot demote the last OWNER
- ✗ ADMIN cannot touch OWNER role

**Example:**
```javascript
router.patch(`/settings/team/${teamId}/members/${userId}/role`, {
  role: 'ADMIN'
});
```

---

### Removing Members

```http
DELETE /settings/team/{team}/members/{user}
```

**Who Can Remove:** OWNER only

**Rules:**
- Cannot remove yourself if you're the last OWNER
- Websites assigned by removed member are transferred back to personal ownership

**Example:**
```javascript
router.delete(`/settings/team/${teamId}/members/${userId}`);
```

---

### Transferring Ownership

```http
POST /settings/team/{team}/transfer-ownership
```

**Who Can Transfer:** OWNER only

**Parameters:**
- `new_owner_id` (required, user ID)

**Process:**
1. New owner gets OWNER role
2. Old owner is downgraded to ADMIN role
3. Old owner stays on the team (not removed)

**Rules:**
- New owner must be an existing team member
- Cannot transfer to yourself
- New owner can be any role (will be promoted to OWNER)

**Example:**
```javascript
router.post(`/settings/team/${teamId}/transfer-ownership`, {
  new_owner_id: 42
});
```

---

### Deleting a Team

```http
DELETE /settings/team/{team}
```

**Who Can Delete:** OWNER only

**Process:**
1. All team websites are transferred back to their original owners
2. If no `assigned_by_user_id`, websites go to team creator
3. Team members are removed
4. Pending invitations are cancelled
5. Team is permanently deleted

**Warning:** This action is irreversible!

**Example:**
```javascript
router.delete(`/settings/team/${teamId}`);
```

---

## Website Management

### Personal vs Team Websites

| Type | Owned By | Visible To | Editable By |
|------|----------|------------|-------------|
| **Personal** | Individual user | Only the owner | Only the owner |
| **Team** | Team (shared) | All team members | OWNER, ADMIN |

---

### Transferring Website to Team

```http
POST /ssl/websites/{website}/transfer-to-team
```

**Who Can Transfer:** OWNER, ADMIN (of target team)

**Parameters:**
- `team_id` (required, team ID)

**Rules:**
- You must be a member of the target team with OWNER or ADMIN role
- Website becomes accessible to all team members

**Example:**
```javascript
router.post(`/ssl/websites/${websiteId}/transfer-to-team`, {
  team_id: 5
});
```

---

### Transferring Website to Personal

```http
POST /ssl/websites/{website}/transfer-to-personal
```

**Who Can Transfer:** OWNER, ADMIN (of the team)

**Rules:**
- Website transfers to your personal account
- Other team members lose access
- Useful for removing websites from team scope

**Example:**
```javascript
router.post(`/ssl/websites/${websiteId}/transfer-to-personal`);
```

---

### Website Access Control

**Policy Rules:**

1. **Personal Website:**
   - Only the owner can view/edit/delete

2. **Team Website:**
   - All team members (OWNER, ADMIN, VIEWER) can **view**
   - Only OWNER and ADMIN can **edit/delete**

**Example Scenario:**
```
Team: "Production Team"
Members:
  - Alice (OWNER)
  - Bob (ADMIN)
  - Charlie (VIEWER)

Website: "example.com" (assigned to team)

Permissions:
  - Alice: ✓ view, ✓ edit, ✓ delete
  - Bob: ✓ view, ✓ edit, ✓ delete
  - Charlie: ✓ view, ✗ edit, ✗ delete
```

---

## API Endpoints

### Team Endpoints

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/settings/team` | List all user's teams | Auth |
| POST | `/settings/team` | Create new team | Auth |
| GET | `/settings/team/{team}` | View team details | Member |
| PUT | `/settings/team/{team}` | Update team info | Owner |
| DELETE | `/settings/team/{team}` | Delete team | Owner |

### Member Management Endpoints

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | `/settings/team/{team}/invite` | Invite member | Owner, Admin |
| DELETE | `/settings/team/{team}/members/{user}` | Remove member | Owner |
| PATCH | `/settings/team/{team}/members/{user}/role` | Change role | Owner, Admin* |
| POST | `/settings/team/{team}/transfer-ownership` | Transfer ownership | Owner |

\* *ADMIN can only change between VIEWER and ADMIN roles*

### Invitation Endpoints

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/team/invitations/{token}` | View invitation | Public |
| POST | `/team/invitations/{token}/accept` | Accept invitation | Public |
| POST | `/team/invitations/{token}/decline` | Decline invitation | Public |
| DELETE | `/settings/team/{team}/invitations/{invitation}` | Cancel invitation | Owner |
| POST | `/settings/team/{team}/invitations/{invitation}/resend` | Resend invitation | Owner |

### Website Transfer Endpoints

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | `/ssl/websites/{website}/transfer-to-team` | Transfer to team | Owner, Admin |
| POST | `/ssl/websites/{website}/transfer-to-personal` | Transfer to personal | Owner, Admin |
| POST | `/ssl/websites/bulk-transfer-to-team` | Bulk transfer to team | Owner, Admin |
| POST | `/ssl/websites/bulk-transfer-to-personal` | Bulk transfer to personal | Owner, Admin |

---

## Common Workflows

### Workflow 1: Setting Up a New Team

```javascript
// 1. Create team
router.post('/settings/team', {
  name: 'DevOps Team',
  description: 'Monitoring all infrastructure'
});

// 2. Invite team members
router.post('/settings/team/1/invite', {
  email: 'admin@example.com',
  role: 'ADMIN'
});

router.post('/settings/team/1/invite', {
  email: 'developer@example.com',
  role: 'VIEWER'
});

// 3. Transfer existing websites to team
router.post('/ssl/websites/5/transfer-to-team', {
  team_id: 1
});
```

---

### Workflow 2: Changing Team Leadership

```javascript
// Scenario: Alice (OWNER) is leaving, wants Bob to take over

// 1. Alice promotes Bob to OWNER
router.post('/settings/team/1/transfer-ownership', {
  new_owner_id: 42 // Bob's user ID
});

// Result:
// - Bob is now OWNER
// - Alice is now ADMIN (still on team)

// 2. Bob removes Alice (if needed)
router.delete('/settings/team/1/members/10'); // Alice's user ID
```

---

### Workflow 3: Managing Team Member Access

```javascript
// Promote trusted viewer to admin
router.patch('/settings/team/1/members/15/role', {
  role: 'ADMIN'
});

// Demote inactive admin to viewer
router.patch('/settings/team/1/members/20/role', {
  role: 'VIEWER'
});

// Remove member who left company
router.delete('/settings/team/1/members/25');
```

---

### Workflow 4: Temporary Website Sharing

```javascript
// Share personal website with team temporarily

// 1. Transfer to team
router.post('/ssl/websites/100/transfer-to-team', {
  team_id: 1
});

// Team members can now view/edit (if ADMIN)

// 2. Later, transfer back to personal
router.post('/ssl/websites/100/transfer-to-personal');

// Website is now private again
```

---

## Best Practices

### Team Setup

1. **Always have at least 2 OWNERs** - Prevents lockout if primary owner is unavailable
2. **Use descriptive team names** - "Production Monitoring" not "Team 1"
3. **Add team description** - Helps members understand team purpose

### Role Assignment

1. **Start with VIEWER** - Give new members VIEWER first, upgrade as needed
2. **Limit OWNERs** - Too many can cause confusion about who manages the team
3. **Use ADMIN wisely** - For trusted members who actively manage websites

### Website Organization

1. **Group by environment** - Production team, Staging team, etc.
2. **Transfer strategically** - Only move websites to team if multiple people need access
3. **Regular cleanup** - Remove unused websites from teams

### Security

1. **Review members regularly** - Remove members who left the organization
2. **Audit role assignments** - Ensure appropriate access levels
3. **Use email verification** - Team invitations require valid email addresses
4. **Monitor ownership transfers** - Track when team ownership changes

---

## Troubleshooting

### "You do not have permission to manage this team"
- You must be the team OWNER
- Check your role: `/settings/team/{team}`

### "Cannot demote the last owner"
- Promote another member to OWNER first
- Or use transfer ownership feature

### "You cannot change your own role"
- Ask another team member (OWNER or ADMIN depending on role change)
- This prevents accidental permission loss

### "The new owner must be a team member"
- Add the user to the team first before transferring ownership
- Use invite feature: `/settings/team/{team}/invite`

### "Admins can only change roles between VIEWER and ADMIN"
- Ask a team OWNER to make OWNER role changes
- ADMINs cannot promote/demote to/from OWNER

---

## Data Model

### Teams Table
```
id, name, description, created_by_user_id, created_at, updated_at
```

### Team Members Table
```
id, team_id, user_id, role, joined_at, invited_by_user_id
```

### Websites Table (Team-related fields)
```
team_id (nullable), assigned_by_user_id (nullable), assigned_at (nullable)
```

### Team Invitations Table
```
id, team_id, email, role, token, expires_at, accepted_at, invited_by_user_id
```

---

## Migration from MANAGER Role

**Previous Roles:** OWNER, ADMIN, MANAGER, VIEWER
**Current Roles:** OWNER, ADMIN, VIEWER

### What Changed?
- **MANAGER role removed** - Consolidated into ADMIN
- **All existing MANAGERs converted to ADMIN** - Automatic migration
- **ADMIN permissions expanded** - Can now change VIEWER/ADMIN roles

### Migration
```sql
-- Automatic migration (already applied)
UPDATE team_members SET role = 'ADMIN' WHERE role = 'MANAGER';
```

### New ADMIN Permissions
- Can manage websites (same as before)
- Can invite members (upgraded from MANAGER)
- Can change VIEWER ↔ ADMIN roles (new)
- Can manage email settings (upgraded from MANAGER)

---

## Support

For additional help:
- Check the main [README.md](../README.md)
- Review [API documentation](../V4_TECHNICAL_SPECIFICATIONS.md)
- See [implementation plan](../SSL_MONITOR_V4_IMPLEMENTATION_PLAN.md)

---

**Last Updated:** October 7, 2025
**Version:** 4.0
**Role Structure:** OWNER, ADMIN, VIEWER
