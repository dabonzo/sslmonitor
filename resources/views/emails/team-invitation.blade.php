<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Invitation - SSL Monitor</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
        }
        .invitation-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .team-info {
            display: flex;
            align-items: center;
            margin: 15px 0;
        }
        .role-badge {
            background: #e3f2fd;
            color: #1565c0;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            margin-left: 10px;
        }
        .cta-button {
            display: inline-block;
            background: #2563eb;
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 500;
            margin: 20px 0;
            text-align: center;
        }
        .cta-button:hover {
            background: #1d4ed8;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            font-size: 14px;
            color: #6c757d;
            text-align: center;
        }
        .expires-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 12px;
            border-radius: 4px;
            margin: 15px 0;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🔒 SSL Monitor</div>
            <h1>You're invited to join a team!</h1>
        </div>

        <p>Hello,</p>

        <p><strong>{{ $invitation->invitedBy->name }}</strong> has invited you to join the <strong>{{ $invitation->team->name }}</strong> team on SSL Monitor.</p>

        <div class="invitation-details">
            <div class="team-info">
                <strong>Team:</strong> {{ $invitation->team->name }}
                <span class="role-badge">{{ $invitation->role }}</span>
            </div>
            @if($invitation->team->description)
            <div style="margin: 10px 0;">
                <strong>About this team:</strong><br>
                {{ $invitation->team->description }}
            </div>
            @endif
            <div style="margin: 10px 0;">
                <strong>Your role:</strong> {{ $invitation->role }}
            </div>
            <div style="margin: 10px 0;">
                <strong>Invited by:</strong> {{ $invitation->invitedBy->name }}
            </div>
        </div>

        <div class="expires-warning">
            ⏰ This invitation expires on <strong>{{ $invitation->expires_at->format('F j, Y \a\t g:i A') }}</strong>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $invitation->getInvitationUrl() }}" class="cta-button">
                Accept Invitation
            </a>
        </div>

        <h3>What is SSL Monitor?</h3>
        <p>SSL Monitor helps teams track SSL certificate expiration dates, ensuring your websites stay secure and accessible. With team collaboration, you can:</p>
        <ul>
            <li>Monitor SSL certificates across multiple websites</li>
            <li>Receive alerts before certificates expire</li>
            <li>Collaborate with team members on certificate management</li>
            <li>Get detailed certificate information and security analysis</li>
        </ul>

        <h3>Your Role: {{ $invitation->role }}</h3>
        <p>
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
        </p>

        <div class="footer">
            <p>If you didn't expect this invitation, you can safely ignore this email.</p>
            <p>This invitation was sent to {{ $invitation->email }} by {{ $invitation->invitedBy->name }}.</p>
            <p>
                <a href="{{ config('app.url') }}">SSL Monitor</a> -
                Keeping your certificates secure and up to date.
            </p>
        </div>
    </div>
</body>
</html>