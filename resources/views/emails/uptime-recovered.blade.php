<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Website Recovered</title>
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
            background: #059669;
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
            border-left: 4px solid #059669;
        }
        .success-alert {
            background: #ecfdf5;
            border: 1px solid #86efac;
            color: #065f46;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .performance-info {
            background: #f0fdf4;
            border: 1px solid #86efac;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
        }
        .button {
            display: inline-block;
            background: #059669;
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
        <h1 style="margin: 0;">✅ Website Recovered</h1>
        <p style="margin: 5px 0 0 0;">{{ $website->name }} is back online</p>
    </div>

    <div class="content">
        <div class="success-alert">
            <strong>🎉 Good news:</strong> Your website is now responding normally and is back online!
        </div>

        <div class="website-info">
            <h2 style="margin-top: 0;">Recovery Details</h2>
            <p><strong>Website:</strong> {{ $website->name }}</p>
            <p><strong>URL:</strong> <a href="{{ $website->url }}">{{ $website->url }}</a></p>
            <p><strong>Recovery Time:</strong> {{ $checkedAt->format('M j, Y \a\t g:i A') }}</p>
            <p><strong>Status:</strong> Online ✅</p>
        </div>

        @if($responseTime)
        <div class="performance-info">
            <strong>Current Performance:</strong><br>
            Response Time: {{ $responseTime }}ms
            @if($responseTime < 1000)
                <span style="color: #059669;">⚡ Excellent</span>
            @elseif($responseTime < 3000)
                <span style="color: #d97706;">⚠️ Acceptable</span>
            @else
                <span style="color: #dc2626;">🐌 Slow</span>
            @endif
        </div>
        @endif

        <h3>What happened:</h3>
        <ul>
            <li>Your website was previously experiencing issues</li>
            <li>Our monitoring detected the problem and sent you an alert</li>
            <li>The issue has now been resolved</li>
            <li>Normal monitoring will continue to ensure ongoing availability</li>
        </ul>

        <h3>Next steps:</h3>
        <ol>
            <li>Verify your website is working as expected</li>
            <li>Check if all functionality is restored</li>
            <li>Consider investigating the root cause to prevent future downtime</li>
            <li>Monitor performance and availability over the next few hours</li>
        </ol>

        <p>
            <strong>Note:</strong> We'll continue monitoring your website 24/7 and will alert you immediately 
            if any future issues are detected.
        </p>

        <div style="text-align: center;">
            <a href="{{ $website->url }}" class="button">Visit Website</a>
        </div>
    </div>

    <div class="footer">
        <p>This is an automated recovery notification from SSL Monitor</p>
        <p>You received this email because uptime monitoring is enabled for {{ $website->name }}</p>
    </div>
</body>
</html>