<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SSL Certificate Error</title>
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
        .error-details {
            background: #fff1f2;
            border: 1px solid #fecaca;
            padding: 15px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            color: #991b1b;
            margin: 15px 0;
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
        <h1 style="margin: 0;">🚫 SSL Certificate Error</h1>
        <p style="margin: 5px 0 0 0;">Issue detected with {{ $website->name }}</p>
    </div>

    <div class="content">
        <div class="alert">
            <strong>⚠️ Critical:</strong> SSL certificate check failed for your website!
        </div>

        <div class="website-info">
            <h2 style="margin-top: 0;">Website Details</h2>
            <p><strong>Website:</strong> {{ $website->name }}</p>
            <p><strong>URL:</strong> <a href="{{ $website->url }}">{{ $website->url }}</a></p>
            <p><strong>Check Time:</strong> {{ $sslCheck->checked_at->format('M j, Y \a\t g:i A') }}</p>
        </div>

        @if($errorMessage)
        <div class="error-details">
            <strong>Error Details:</strong><br>
            {{ $errorMessage }}
        </div>
        @endif

        <h3>What this means:</h3>
        <ul>
            <li><strong>Certificate may be expired</strong> - Visitors will see security warnings</li>
            <li><strong>Configuration issue</strong> - SSL/TLS setup may need attention</li>
            <li><strong>Domain mismatch</strong> - Certificate may not match your domain</li>
            <li><strong>Connection problem</strong> - Server may be unreachable</li>
        </ul>

        <h3>Immediate action required:</h3>
        <ol>
            <li>Check if your website is accessible via HTTPS</li>
            <li>Verify your SSL certificate hasn't expired</li>
            <li>Contact your hosting provider if needed</li>
            <li>Monitor for resolution and run another check</li>
        </ol>

        <p>
            <strong>Impact:</strong> Visitors to your website may see security warnings or be unable 
            to access your site securely until this issue is resolved.
        </p>

        <div style="text-align: center;">
            <a href="{{ $website->url }}" class="button">Check Website</a>
        </div>
    </div>

    <div class="footer">
        <p>This is an automated error alert from SSL Monitor</p>
        <p>You received this email because SSL error alerts are enabled for {{ $website->name }}</p>
    </div>
</body>
</html>