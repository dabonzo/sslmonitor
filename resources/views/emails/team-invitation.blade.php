<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Invitation - SSL Monitor</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #122c4f 0%, #1e3a5f 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }
        .logo {
            width: 48px;
            height: 48px;
            margin: 0 auto 16px;
            background: #a0cc3a;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 24px;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        .header p {
            margin: 8px 0 0;
            opacity: 0.9;
            font-size: 16px;
        }
        .content {
            padding: 40px 30px;
        }
        .invitation-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 24px;
            margin: 24px 0;
            text-align: center;
        }
        .team-name {
            font-size: 24px;
            font-weight: 700;
            color: #122c4f;
            margin-bottom: 8px;
        }
        .role-badge {
            display: inline-block;
            background: #a0cc3a;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .cta-button {
            display: inline-block;
            background: #a0cc3a;
            color: white;
            padding: 16px 32px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            margin: 24px 0;
            transition: background-color 0.2s;
        }
        .cta-button:hover {
            background: #8fb832;
        }
        .details {
            background: #fef3f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 16px;
            margin: 24px 0;
        }
        .details h3 {
            margin: 0 0 8px;
            color: #991b1b;
            font-size: 16px;
        }
        .details p {
            margin: 4px 0;
            color: #7f1d1d;
            font-size: 14px;
        }
        .footer {
            background: #f8fafc;
            padding: 24px 30px;
            text-align: center;
            color: #64748b;
            font-size: 14px;
            border-top: 1px solid #e2e8f0;
        }
        .footer a {
            color: #122c4f;
            text-decoration: none;
        }
        @media (max-width: 600px) {
            .email-container {
                margin: 0;
                border-radius: 0;
            }
            .content, .header, .footer {
                padding-left: 20px;
                padding-right: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="logo">SSL</div>
            <h1>You're Invited!</h1>
            <p>Join your team on SSL Monitor</p>
        </div>
        
        <div class="content">
            <p>Hi there,</p>
            
            <p><strong>{{ $invitation->invitedBy->name }}</strong> has invited you to join their team on SSL Monitor, a professional SSL certificate monitoring platform.</p>
            
            <div class="invitation-card">
                <div class="team-name">{{ $invitation->team->name }}</div>
                <div class="role-badge">{{ ucfirst($invitation->role) }}</div>
            </div>
            
            <p>As a <strong>{{ ucfirst($invitation->role) }}</strong>, you'll be able to:</p>
            <ul>
                @if($invitation->role === 'admin')
                    <li>View and manage all team websites</li>
                    <li>Configure email notification settings</li>
                    <li>Monitor SSL certificate status and expiry dates</li>
                @elseif($invitation->role === 'manager')
                    <li>Add and manage team websites</li>
                    <li>View team notification settings</li>
                    <li>Monitor SSL certificate status and expiry dates</li>
                @else
                    <li>View team websites and their SSL status</li>
                    <li>Access SSL monitoring dashboard</li>
                    <li>View team notification settings</li>
                @endif
            </ul>
            
            <div style="text-align: center;">
                <a href="{{ route('invitations.show', $invitation->token) }}" class="cta-button">
                    Accept Invitation & Set Up Account
                </a>
            </div>
            
            <div class="details">
                <h3>⚠️ Important Details</h3>
                <p><strong>Expires:</strong> {{ $invitation->expires_at->format('F j, Y \a\t g:i A') }}</p>
                <p><strong>Security:</strong> This invitation link is unique and secure</p>
                <p><strong>Team:</strong> {{ $invitation->team->name }}</p>
            </div>
            
            <p>If you have any questions about SSL Monitor or this invitation, feel free to reach out to {{ $invitation->invitedBy->name }}.</p>
            
            <p>Welcome to professional SSL monitoring!</p>
            
            <p style="margin-top: 32px;">
                <strong>The SSL Monitor Team</strong><br>
                <span style="color: #64748b;">Powered by INTERMEDIEN</span>
            </p>
        </div>
        
        <div class="footer">
            <p>
                This invitation was sent to {{ $invitation->email }} by {{ $invitation->invitedBy->name }}.
                <br>
                If you didn't expect this invitation, you can safely ignore this email.
            </p>
            <p style="margin-top: 16px;">
                <a href="{{ config('app.url') }}">SSL Monitor</a> | Professional SSL Certificate Monitoring
            </p>
        </div>
    </div>
</body>
</html>