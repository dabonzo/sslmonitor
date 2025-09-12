# Team Management Guide

SSL Monitor supports team collaboration, allowing you to share SSL certificate monitoring with colleagues, supervisors, or clients. This guide covers everything you need to know about creating and managing teams.

## Overview

### Individual vs Team Mode

**Individual Mode (Default)**
- You manage SSL certificates privately
- Only you can see and manage your websites
- Personal email settings for notifications

**Team Mode** 
- Share SSL monitoring with team members
- Collaborative website management
- Team-wide email settings and notifications
- Role-based access control

## Getting Started with Teams

### Creating Your First Team

1. **Navigate to Team Settings**
   - Go to Settings → Team Management
   - You'll see "Individual Mode" status initially

2. **Create Team**
   - Enter a team name (e.g., "SSL Monitor Team")
   - Optionally transfer existing personal websites to the team
   - Click "Create Team"

3. **You're now the Team Owner**
   - Automatic owner permissions
   - Can invite members and manage settings
   - Team badge appears throughout the application

### Inviting Team Members

1. **Access Team Management**
   - Settings → Team Management
   - Look for "Invite Team Member" section

2. **Send Invitation**
   - Enter colleague's email address
   - Select their role (Admin, Manager, or Viewer)
   - Click "Invite Member"

3. **Professional Email Invitation**
   - Invited person receives a professional email with INTERMEDIEN branding
   - Email includes secure magic link for passwordless account setup
   - Role permissions clearly explained in the invitation
   - Invitation expires automatically after 7 days

4. **Passwordless Account Setup**
   - Invited person clicks the secure link in their email
   - Sets up their account with name and password
   - Immediately gains team access with assigned role
   - No complex registration process required

5. **Invitation Management**
   - Track invitation status (Pending, Accepted, Expired)
   - Resend invitations if needed
   - Cancel pending invitations
   - View invitation expiry dates

## Team Roles & Permissions

### 🔑 Owner
**Full Control** - The team creator with complete access

- ✅ Manage team (invite, remove members, change roles)
- ✅ Manage websites (add, edit, delete)
- ✅ Configure team email settings
- ✅ View all websites and settings
- ✅ Delete team

### 👨‍💼 Admin  
**Management Access** - Can handle day-to-day operations

- ❌ Manage team membership
- ✅ Manage websites (add, edit, delete)
- ✅ Configure team email settings
- ✅ View all websites and settings
- ❌ Delete team

### 📊 Manager
**Website Management** - Can add and manage websites

- ❌ Manage team membership
- ✅ Add and edit websites
- ✅ View team email settings (read-only)
- ✅ View all websites
- ❌ Delete websites or configure email

### 👁️ Viewer
**Read-Only Access** - Can monitor SSL status

- ❌ Manage team membership
- ❌ Manage websites
- ✅ View websites and SSL status
- ✅ View team settings (read-only)
- ❌ Make any changes

## Team Features

### Dashboard Integration
- **Team Badge**: Shows your team name in the header
- **Website Counts**: Displays personal vs team website statistics
- **Quick Access**: Direct link to team management

### Website Management
- **Auto-Assignment**: New websites automatically assigned to your team
- **Clear Indicators**: Team vs personal website badges
- **Attribution**: Shows who added each team website

### Email Settings
- **Team-Specific SMTP**: Configure email settings for the entire team
- **Centralized Notifications**: All team SSL alerts use team email configuration
- **Override Personal Settings**: Team settings take priority over personal ones

## Common Use Cases

### Business Owner + Employee
```
Owner: business-owner@company.com (Owner role)
Employee: employee@company.com (Admin role)
```
- Owner creates team and invites employee as Admin
- Both can manage websites and email settings
- Employee cannot invite other members

### Agency + Client
```
Agency Lead: lead@agency.com (Owner role)
Agency Member: dev@agency.com (Manager role)  
Client: client@company.com (Viewer role)
```
- Agency manages client's SSL certificates
- Client can view status but not make changes
- Clear role separation

### IT Department
```
IT Manager: manager@company.com (Owner role)
IT Admin: admin@company.com (Admin role)
IT Staff: staff@company.com (Manager role)
Executive: ceo@company.com (Viewer role)
```
- Hierarchical access with appropriate permissions
- Executives can monitor without operational access

## Managing Your Team

### Changing Member Roles
1. Go to Settings → Team Management
2. Find the team member in the list
3. Use the dropdown to select new role
4. Changes take effect immediately

### Removing Team Members
1. Go to Settings → Team Management  
2. Find the team member in the list
3. Click "Remove" button
4. Confirm the removal

⚠️ **Note**: Team owners cannot remove themselves

### Website Transfer
When creating a team, you can transfer existing personal websites:
- Select websites to transfer during team creation
- Websites become team assets
- Original owner remains in the system as "added_by"

## Team Email Configuration

### Setting Up Team Email
1. **Navigate to Email Settings** (Settings → Email Configuration)
2. **Team Context**: You'll see "Team Email Settings: [Team Name]"
3. **Configure SMTP**: Enter your team's email server details
4. **Test Configuration**: Use the test button to verify settings

### Email Priority
- **Team Members**: Team email settings override personal settings
- **Individual Users**: Use their personal email settings
- **No Settings**: System defaults (if configured)

## Best Practices

### Team Organization
- **Clear Naming**: Use descriptive team names like "Company SSL Team"
- **Appropriate Roles**: Assign minimum necessary permissions
- **Regular Review**: Periodically review team membership

### Website Management  
- **Logical Grouping**: Use teams to group related websites
- **Documentation**: Use clear website names and descriptions
- **Ownership Tracking**: The "added by" feature helps track responsibility

### Security
- **Role Assignment**: Only give management roles to trusted individuals
- **Regular Audits**: Review team members and their access levels
- **Email Security**: Use secure SMTP settings for team notifications

## Troubleshooting

### Common Issues

**Can't create team**
- Ensure you're not already a member of another team
- Check that team name is unique and valid

**Invitation not working**
- Verify email address is correct
- Check if user already exists in another team
- Ensure you have "manage_team" permissions

**Email settings not applying**
- Team settings override personal settings for team members
- Verify SMTP configuration is correct
- Test email configuration after saving

**Role changes not taking effect**
- Refresh the page or re-login
- Check that you have owner permissions to change roles
- Verify the user is actually a team member

### Getting Help

- **Dashboard Issues**: Check team badge and website counts
- **Permission Errors**: Verify your role and required permissions  
- **Email Problems**: Test team email configuration
- **Access Issues**: Confirm team membership status

---

**Next Steps**: Learn about [Email Configuration](email-configuration.md) for team-wide notifications or return to [Getting Started](getting-started.md) for basic setup.