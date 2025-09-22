# 🏢 Team System Implementation Plan
## SSL Monitor v4 - Phase 4: Team Management & Role-Based Access

### 📋 **Vision Summary**
Transform SSL Monitor from personal-only to **Personal + Team hybrid system** where:
- Users start with personal websites
- Can create teams and transfer websites to team ownership
- Team members get role-based permissions
- Alert system distributes to all team members with email management
- Visual badges distinguish "Personal" vs "Team" websites

---

## 🎯 **Phase 4A: Core Team Infrastructure**
*Estimated: 3-4 days*

### **Task 4A.1: Database Schema & Models**
- [ ] Create `teams` table with fields:
  - `id`, `name`, `description`, `created_by_user_id`, `created_at`, `updated_at`
- [ ] Create `team_members` pivot table:
  - `id`, `team_id`, `user_id`, `role`, `joined_at`, `invited_by_user_id`
- [ ] Create `team_invitations` table:
  - `id`, `team_id`, `email`, `role`, `token`, `expires_at`, `accepted_at`, `invited_by_user_id`
- [ ] Update `websites` table:
  - Add `team_id` (nullable), `assigned_by_user_id`, `assigned_at`
- [ ] Update `alert_configurations` table:
  - Add `team_id` (nullable) for team-wide alerts

### **Task 4A.2: Models & Relationships**
- [ ] Create `Team` model with relationships:
  - `belongsToMany(User::class)` through team_members
  - `hasMany(Website::class)`
  - `hasMany(AlertConfiguration::class)`
- [ ] Create `TeamMember` model with:
  - Role constants: `OWNER`, `ADMIN`, `MANAGER`, `VIEWER`
  - Permission checking methods
- [ ] Create `TeamInvitation` model with:
  - Token generation and validation
  - Email sending logic
- [ ] Update `User` model:
  - `belongsToMany(Team::class)` through team_members
  - `hasMany(Website::class)` for personal sites
- [ ] Update `Website` model:
  - `belongsTo(Team::class)`
  - `isPersonal()` and `isTeam()` helper methods

### **Task 4A.3: Role-Based Permissions System**
Based on screenshot roles:
- [ ] **Owner**: Full access - manage team, websites, and settings
- [ ] **Admin**: Manage websites and email settings (cannot manage team)
- [ ] **Manager**: Add/edit websites and view settings
- [ ] **Viewer**: View-only access to websites and settings

### **Task 4A.4: Authorization Policies**
- [ ] `TeamPolicy` - who can view/edit/delete teams
- [ ] `TeamMemberPolicy` - who can invite/remove members
- [ ] Update `WebsitePolicy` - check team membership and roles
- [ ] Update `AlertConfigurationPolicy` - team alert permissions

---

## 🎯 **Phase 4B: Team Management Interface**
*Estimated: 4-5 days*

### **Task 4B.1: Team Settings Page** *(Based on your screenshot)*
- [ ] Create `/settings/team` route and controller
- [ ] Team creation form with name/description
- [ ] "Team Mode" toggle for team owners
- [ ] Current team members list with roles and status
- [ ] Invite team member form with email + role dropdown
- [ ] Role permissions display panel (Owner/Admin/Manager/Viewer)

### **Task 4B.2: Team Member Invitation System**
- [ ] Email invitation with secure token
- [ ] **Existing Users**: Direct team join (no OTP needed)
- [ ] **New Users**: OTP system for account creation + team join
- [ ] Invitation acceptance flow
- [ ] Invitation expiry and resend functionality

### **Task 4B.3: Website Transfer System**
- [ ] "Transfer to Team" functionality in website management
- [ ] Bulk website transfer to teams
- [ ] "Transfer back to Personal" for team owners
- [ ] Transfer history and audit logging

---

## 🎯 **Phase 4C: Visual Badge System**
*Estimated: 2-3 days*

### **Task 4C.1: Website List Badges** *(Based on your screenshot)*
- [ ] "Team" badge for team-owned websites
- [ ] "Personal" badge (or no badge) for personal websites
- [ ] Status badges integration ("Down", "Expiring", etc.)
- [ ] Color-coded team badges by team name/type
- [ ] Mobile-responsive badge design

### **Task 4C.2: Dashboard Integration**
- [ ] Team statistics on dashboard
- [ ] "Personal vs Team" website counts
- [ ] Team activity feed
- [ ] Role-based dashboard sections

### **Task 4C.3: Navigation & Context**
- [ ] Team switcher in navigation
- [ ] "Currently viewing: Personal/Team Name" context
- [ ] Team settings link in navigation (for team members)

---

## 🎯 **Phase 4D: Enhanced Alert System for Teams**
*Estimated: 3-4 days*

### **Task 4D.1: Team Alert Distribution**
- [ ] Alert system sends to **all team members** for team websites
- [ ] Personal website alerts remain individual
- [ ] Role-based alert filtering (if needed)

### **Task 4D.2: Team Alert Email Management**
- [ ] **Team Owners/Admins** can:
  - View all team member alert emails
  - Add additional alert emails for the team
  - Remove/disable member alert emails (with member consent)
- [ ] **Individual Members** can:
  - Manage their own alert email preferences
  - Opt-out of specific alert types

### **Task 4D.3: Enhanced Alert Templates**
- [ ] Update email templates to show team context
- [ ] "This alert is for [Team Name] website: [Website]"
- [ ] Team member list in alert emails (optional)
- [ ] Team-specific alert customization

---

## 🎯 **Phase 4E: Advanced Team Features**
*Estimated: 2-3 days*

### **Task 4E.1: Team Analytics & Reporting**
- [ ] Team-wide SSL certificate overview
- [ ] Team member activity logs
- [ ] Team website health summary
- [ ] Export team reports

### **Task 4E.2: Team Security & Audit**
- [ ] Team access logs
- [ ] Website transfer audit trail
- [ ] Member invitation/removal history
- [ ] Security event notifications

---

## 📋 **Implementation Order & Dependencies**

### **Week 1**: Foundation
1. **Day 1-2**: Phase 4A.1 & 4A.2 (Database & Models)
2. **Day 3-4**: Phase 4A.3 & 4A.4 (Permissions & Policies)

### **Week 2**: Core Features
1. **Day 5-6**: Phase 4B.1 (Team Settings Interface)
2. **Day 7-8**: Phase 4B.2 (Invitation System)
3. **Day 9**: Phase 4B.3 (Website Transfer)

### **Week 3**: UI & Alerts
1. **Day 10-11**: Phase 4C (Badge System)
2. **Day 12-14**: Phase 4D (Enhanced Alerts)

### **Week 4**: Polish & Advanced
1. **Day 15-16**: Phase 4E (Advanced Features)
2. **Day 17**: Testing & Polish

---

## 🔧 **Technical Considerations**

### **Database Performance**
- Proper indexing on `team_id`, `user_id`, `role` fields
- Eager loading for team relationships
- Efficient querying for mixed personal/team websites

### **Security**
- Secure token generation for invitations
- Role-based middleware for all team operations
- CSRF protection for team management actions

### **Email System**
- Queue-based invitation emails
- Mailpit testing for team invitations
- Unsubscribe links for team alerts

### **User Experience**
- Smooth personal → team transition
- Clear role permission explanations
- Intuitive badge design following your screenshots

---

## ✅ **Success Criteria**

1. **Team Creation**: Users can create teams and become owners
2. **Member Management**: Invite users with roles, manage permissions
3. **Website Transfer**: Move sites between personal/team ownership
4. **Visual Distinction**: Clear badges showing "Team" vs "Personal"
5. **Alert Distribution**: Team alerts reach all members appropriately
6. **Email Management**: Team admins can manage alert email lists
7. **Role Enforcement**: Permissions work correctly for all roles

This plan transforms SSL Monitor into a powerful **Personal + Team collaboration platform** while maintaining the clean, professional interface shown in your screenshots.