<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Website {{ ucfirst($status) }} Alert</title>
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
        .status-info {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            padding: 15px;
            border-radius: 6px;
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
        <h1 style="margin: 0;">
            @if($status === 'down')
                🚫 Website Down
            @elseif($status === 'slow')
                🐌 Slow Response
            @elseif($status === 'content_mismatch')
                ⚠️ Content Issue
            @else
                ❌ Website Issue
            @endif
        </h1>
        <p style="margin: 5px 0 0 0;">Issue detected with {{ $website->name }}</p>
    </div>

    <div class="content">
        <div class="alert">
            <strong>⚠️ Alert:</strong> 
            @if($status === 'down')
                Your website is currently down or unreachable!
            @elseif($status === 'slow')
                Your website is responding slowly!
            @elseif($status === 'content_mismatch')
                Your website content doesn't match expected patterns!
            @else
                Your website has encountered an issue!
            @endif
        </div>

        <div class="website-info">
            <h2 style="margin-top: 0;">Website Details</h2>
            <p><strong>Website:</strong> {{ $website->name }}</p>
            <p><strong>URL:</strong> <a href="{{ $website->url }}">{{ $website->url }}</a></p>
            <p><strong>Check Time:</strong> {{ $checkedAt->format('M j, Y \a\t g:i A') }}</p>
            <p><strong>Status:</strong> {{ ucfirst($status) }}</p>
        </div>

        @if($statusCode)
        <div class="status-info">
            <strong>HTTP Status Code:</strong> {{ $statusCode }}
            @if($responseTime)
                <br><strong>Response Time:</strong> {{ $responseTime }}ms
            @endif
        </div>
        @endif

        @if($errorMessage)
        <div class="error-details">
            <strong>Error Details:</strong><br>
            {{ $errorMessage }}
        </div>
        @endif

        @if($status === 'down')
        <h3>What this means:</h3>
        <ul>
            <li><strong>Server may be down</strong> - Your hosting server might be offline</li>
            <li><strong>Network issue</strong> - Connectivity problems may be occurring</li>
            <li><strong>DNS problems</strong> - Domain name resolution might have failed</li>
            <li><strong>Configuration error</strong> - Server configuration may need attention</li>
        </ul>

        <h3>Immediate action required:</h3>
        <ol>
            <li>Check if your website loads in a browser</li>
            <li>Contact your hosting provider if the site is truly down</li>
            <li>Check your domain name settings (DNS)</li>
            <li>Monitor for resolution and automatic recovery</li>
        </ol>
        @elseif($status === 'slow')
        <h3>What this means:</h3>
        <ul>
            <li><strong>Performance issue</strong> - Your website is loading slower than expected</li>
            <li><strong>Server load</strong> - High traffic or resource usage may be occurring</li>
            <li><strong>Database problems</strong> - Database queries might be taking too long</li>
            <li><strong>Network congestion</strong> - Network latency may be higher than usual</li>
        </ul>

        <h3>Consider these actions:</h3>
        <ol>
            <li>Check your website's loading speed in a browser</li>
            <li>Monitor server resource usage</li>
            <li>Review recent changes or traffic spikes</li>
            <li>Consider optimizing database queries or caching</li>
        </ol>
        @elseif($status === 'content_mismatch')
        <h3>What this means:</h3>
        <ul>
            <li><strong>Content changed</strong> - Expected content is no longer found on your website</li>
            <li><strong>Possible takeover</strong> - Your hosting might have been compromised</li>
            <li><strong>Configuration issue</strong> - Website may be serving wrong content</li>
            <li><strong>Maintenance page</strong> - Site might be in maintenance mode</li>
        </ul>

        <h3>Immediate action required:</h3>
        <ol>
            <li>Check your website content immediately</li>
            <li>Verify you still have control of your hosting account</li>
            <li>Contact your hosting provider if content was changed unexpectedly</li>
            <li>Review your website's security and access logs</li>
        </ol>
        @endif

        <p>
            <strong>Impact:</strong> Your website visitors may be experiencing issues accessing or using your site until this is resolved.
        </p>

        <div style="text-align: center;">
            <a href="{{ $website->url }}" class="button">Check Website</a>
        </div>
    </div>

    <div class="footer">
        <p>This is an automated uptime alert from SSL Monitor</p>
        <p>You received this email because uptime error alerts are enabled for {{ $website->name }}</p>
    </div>
</body>
</html>