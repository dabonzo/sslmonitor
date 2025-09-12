# Feature Plan: Passwordless Team Invitations

**Status**: Planning Phase  
**Branch**: `feature/passwordless-team-invitations`  
**Priority**: Critical (blocks team collaboration)

## Problem Statement

Currently, team invitations are broken:
- Invited users get random passwords and cannot log in
- No email notifications sent to invited users
- No way for invited users to access their accounts
- Team owners assume invitations work but they're actually broken

## Solution Overview

Implement a secure, professional team invitation system using magic links for passwordless authentication, similar to modern collaboration tools like Slack, Discord, or GitHub.

---

## Implementation Plan

### Phase 1: Database Schema (Foundation)

#### 1.1 Create Team Invitations Migration
```bash
./vendor/bin/sail artisan make:migration create_team_invitations_table
```

**Schema**:
```sql
- id (bigint, primary key)
- team_id (bigint, foreign key)
- email (string)
- role (enum: admin, manager, viewer)
- token (string, unique, indexed)
- invited_by (bigint, foreign key to users)
- status (enum: pending, accepted, expired, cancelled)
- expires_at (datetime)
- accepted_at (datetime, nullable)
- created_at, updated_at (timestamps)
```

#### 1.2 Update User Model
- Add `email_verified_at` handling for invited users
- Add relationship to team invitations

### Phase 2: Core Invitation System

#### 2.1 TeamInvitation Model
```php
class TeamInvitation extends Model
{
    protected $fillable = [
        'team_id', 'email', 'role', 'token', 
        'invited_by', 'status', 'expires_at'
    ];
    
    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];
    
    // Relationships
    // Status management methods
    // Token generation
}
```

#### 2.2 Invitation Service
```php
class TeamInvitationService
{
    public function inviteUser(Team $team, string $email, string $role, User $invitedBy)
    public function generateInvitationToken(): string
    public function sendInvitationEmail(TeamInvitation $invitation)
    public function acceptInvitation(string $token, array $userData): User
    public function cancelInvitation(TeamInvitation $invitation)
}
```

### Phase 3: Email System

#### 3.1 Invitation Mail Class
```php
class TeamInvitationMail extends Mailable
{
    // Professional email template
    // Magic link generation
    // Team context and branding
}
```

#### 3.2 Email Template
- Professional HTML template matching app branding
- Clear call-to-action button
- Team context (team name, invited by)
- Expiration notice (24-48 hours)

### Phase 4: Web Routes & Controllers

#### 4.1 Invitation Routes
```php
Route::get('/invitations/{token}', [InvitationController::class, 'show'])
    ->name('invitations.show');
Route::post('/invitations/{token}/accept', [InvitationController::class, 'accept'])
    ->name('invitations.accept');
```

#### 4.2 Invitation Controller
- Show invitation details page
- Handle invitation acceptance
- Password setup for new users
- Account linking for existing users

### Phase 5: Frontend Components

#### 5.1 Invitation Acceptance Page
- Clean, professional design matching app style
- Team information display
- User registration form (name, password)
- Account setup workflow

#### 5.2 Updated Team Management Component
```php
// Replace current inviteUser() method
public function inviteUser(): void
{
    // Use TeamInvitationService
    // Send proper email
    // Update UI to show pending status
}

public function resendInvitation(int $invitationId): void
public function cancelInvitation(int $invitationId): void
```

### Phase 6: UX Improvements

#### 6.1 Invitation Status Indicators
- Pending invitation badges
- Accepted vs pending member distinction
- Invitation expiry warnings
- Resend invitation buttons

#### 6.2 Team Management Enhancements
- "Import from Personal Settings" for email settings
- Invitation history and management
- Bulk invitation capability (future)

---

## Technical Implementation Details

### Security Considerations
- **Secure Tokens**: Use `Str::random(64)` for invitation tokens
- **Token Expiration**: 48-hour expiry for invitations
- **Email Verification**: Require email verification for invited users
- **Rate Limiting**: Prevent invitation spam
- **HTTPS Only**: All invitation links must use HTTPS

### Database Considerations
- **Soft Deletes**: Keep invitation history for auditing
- **Indexes**: Add indexes on token, email, and team_id
- **Cleanup**: Scheduled job to clean expired invitations

### Email Considerations
- **Template Design**: Match app branding (INTERMEDIEN colors)
- **Deliverability**: Proper SPF/DKIM setup
- **Testing**: Use Mailpit for local testing
- **Production**: Ensure proper SMTP configuration

---

## Testing Strategy

### Unit Tests
- [ ] TeamInvitation model tests
- [ ] TeamInvitationService tests
- [ ] Email template rendering tests
- [ ] Token generation and validation tests

### Feature Tests
- [ ] Full invitation flow (send → receive → accept)
- [ ] Email delivery verification
- [ ] Permission-based invitation restrictions
- [ ] Invitation expiry handling
- [ ] Duplicate invitation prevention

### Browser Tests (Pest v4)
- [ ] Complete user journey from invitation email to team access
- [ ] Multi-browser testing (Chrome, Firefox)
- [ ] Mobile-responsive invitation acceptance
- [ ] Error handling and edge cases

---

## Success Criteria

### Functional Requirements
- [x] Invited users receive professional email invitations
- [x] Magic links work securely for account setup
- [x] Role-based permissions work correctly after invitation acceptance
- [x] Team collaboration features work end-to-end
- [x] Invitation status is clearly visible to team owners

### UX Requirements
- [x] Professional, branded invitation emails
- [x] Smooth onboarding experience for invited users
- [x] Clear invitation status indicators
- [x] Intuitive team management interface
- [x] Mobile-friendly invitation acceptance

### Technical Requirements
- [x] Secure token generation and validation
- [x] Proper email deliverability
- [x] Comprehensive test coverage
- [x] Performance optimization for team operations
- [x] Clean database schema and relationships

---

## Rollout Plan

### Phase 1: Foundation (Database & Models)
- Create migrations and models
- Set up basic relationships
- Unit tests for core functionality

### Phase 2: Core Logic (Service & Email)
- Implement invitation service
- Create email templates
- Set up mail configuration

### Phase 3: Web Interface (Controllers & Views)
- Build invitation acceptance pages
- Update team management component
- Add status indicators

### Phase 4: Testing & Polish
- Comprehensive test suite
- UX refinements
- Documentation updates

### Phase 5: Production Deploy
- Database migrations
- Email configuration verification
- Feature flag rollout (if needed)

---

## Future Enhancements

### Advanced Features (Post-MVP)
- Bulk invitation capability
- Custom invitation messages
- Invitation templates per team
- Advanced role management
- Team invitation analytics

### Integration Opportunities
- SAML/SSO integration
- Slack/Discord notifications
- API endpoints for programmatic invitations
- Webhook notifications for team changes

---

This comprehensive plan addresses the critical UX gap while establishing a robust foundation for team collaboration features.