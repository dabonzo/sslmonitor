<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SSL Certificate Expiring Soon</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: #dc2626;
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background: #f9fafb;
            padding: 30px;
            border-radius: 0 0 8px 8px;
            border: 1px solid #e5e7eb;
        }
        .website-info {
            background: white;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
            border-left: 4px solid #dc2626;
        }
        .alert {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            background: #dc2626;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
        }
        .details {
            background: white;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #6b7280;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0;">🔒 SSL Certificate Expiring Soon</h1>
        <p style="margin: 5px 0 0 0;">Action required for {{ $website->name }}</p>
    </div>

    <div class="content">
        <div class="alert">
            <strong>⚠️ Urgent:</strong> Your SSL certificate will expire in {{ $daysLeft }} {{ Str::plural('day', $daysLeft) }}!
        </div>

        <div class="website-info">
            <h2 style="margin-top: 0;">Website Details</h2>
            <p><strong>Website:</strong> {{ $website->name }}</p>
            <p><strong>URL:</strong> <a href="{{ $website->url }}">{{ $website->url }}</a></p>
            <p><strong>Expires:</strong> {{ $expiresAt->format('M j, Y \a\t g:i A') }}</p>
            <p><strong>Days remaining:</strong> {{ $daysLeft }}</p>
        </div>

        <h3>What you need to do:</h3>
        <ol>
            <li>Contact your hosting provider or SSL certificate authority</li>
            <li>Renew your SSL certificate before it expires</li>
            <li>Install the new certificate on your server</li>
            <li>Verify the installation is working correctly</li>
        </ol>

        @if($sslCheck->issuer)
        <div class="details">
            <h3>Current Certificate Details</h3>
            <p><strong>Issued by:</strong> {{ $sslCheck->issuer }}</p>
            @if($sslCheck->subject)
                <p><strong>Subject:</strong> {{ $sslCheck->subject }}</p>
            @endif
        </div>
        @endif

        <p>
            <strong>Why this matters:</strong> When SSL certificates expire, visitors will see security warnings 
            and may not be able to access your website securely. This can harm your reputation and affect your business.
        </p>

        <div style="text-align: center;">
            <a href="{{ $website->url }}" class="button">Check Website</a>
        </div>
    </div>

    <div class="footer">
        <p>This is an automated notification from SSL Monitor</p>
        <p>You received this email because you have SSL monitoring enabled for {{ $website->name }}</p>
    </div>
</body>
</html>