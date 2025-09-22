SSL Monitor - Team Invitation

Hello,

{{ $invitation->invitedBy->name }} has invited you to join the {{ $invitation->team->name }} team on SSL Monitor.

INVITATION DETAILS:
Team: {{ $invitation->team->name }}
Your Role: {{ $invitation->role }}
Invited by: {{ $invitation->invitedBy->name }}
@if($invitation->team->description)
About this team: {{ $invitation->team->description }}
@endif

⏰ IMPORTANT: This invitation expires on {{ $invitation->expires_at->format('F j, Y \a\t g:i A') }}

To accept this invitation, visit:
{{ $invitation->getInvitationUrl() }}

WHAT IS SSL MONITOR?

SSL Monitor helps teams track SSL certificate expiration dates, ensuring your websites stay secure and accessible. With team collaboration, you can:

- Monitor SSL certificates across multiple websites
- Receive alerts before certificates expire
- Collaborate with team members on certificate management
- Get detailed certificate information and security analysis

YOUR ROLE: {{ $invitation->role }}

@switch($invitation->role)
@case('ADMIN')
As an Admin, you can manage websites, email settings, and invite other team members.
@break
@case('MANAGER')
As a Manager, you can add and edit websites and view all team settings.
@break
@case('VIEWER')
As a Viewer, you have read-only access to websites and team settings.
@break
@default
You'll have {{ strtolower($invitation->role) }} access to this team's SSL monitoring.
@endswitch

If you didn't expect this invitation, you can safely ignore this email.

This invitation was sent to {{ $invitation->email }} by {{ $invitation->invitedBy->name }}.

SSL Monitor - {{ config('app.url') }}
Keeping your certificates secure and up to date.