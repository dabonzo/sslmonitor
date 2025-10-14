<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSL Certificate Invalid Alert</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .alert {
            background: #dc3545;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #bd2130;
        }
        .website-info {
            background: white;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #dc3545;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 14px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚨 SSL Certificate Invalid</h1>
        <p>Critical Security Alert</p>
    </div>

    <div class="content">
        <div class="alert">
            <strong>IMMEDIATE ACTION REQUIRED</strong><br>
            The SSL certificate for your website is invalid or has serious issues.
        </div>

        <div class="website-info">
            <h3>🌐 Website Details</h3>
            <p><strong>Name:</strong> {{ $website->name }}</p>
            <p><strong>URL:</strong> <a href="{{ $website->url }}">{{ $website->url }}</a></p>
            <p><strong>Status:</strong> <span style="color: #dc3545; font-weight: bold;">INVALID CERTIFICATE</span></p>
        </div>

        <div class="website-info">
            <h3>📋 Certificate Information</h3>
            @if(isset($checkData['ssl_error']))
                <p><strong>Error:</strong> {{ $checkData['ssl_error'] }}</p>
            @endif
            @if(isset($checkData['ssl_issuer']))
                <p><strong>Issuer:</strong> {{ $checkData['ssl_issuer'] }}</p>
            @endif
            @if(isset($checkData['ssl_valid_from']))
                <p><strong>Valid From:</strong> {{ $checkData['ssl_valid_from'] }}</p>
            @endif
            @if(isset($checkData['ssl_valid_until']))
                <p><strong>Valid Until:</strong> {{ $checkData['ssl_valid_until'] }}</p>
            @endif
        </div>

        <div class="website-info">
            <h3>⚠️ What This Means</h3>
            <p>An invalid SSL certificate means:</p>
            <ul>
                <li>Visitors will see security warnings when accessing your site</li>
                <li>Your website may be flagged as "Not Secure"</li>
                <li>Trust and credibility may be damaged</li>
                <li>SEO rankings may be negatively affected</li>
            </ul>
        </div>

        <div class="website-info">
            <h3>🔧 Recommended Actions</h3>
            <ol>
                <li>Check your certificate configuration</li>
                <li>Verify certificate chain is complete</li>
                <li>Ensure certificate is properly installed</li>
                <li>Consider renewing or replacing the certificate</li>
                <li>Test the certificate installation</li>
            </ol>
        </div>

        <p>
            <a href="{{ route('ssl.websites.show', $website) }}" class="btn">
                View in Dashboard
            </a>
        </p>
    </div>

    <div class="footer">
        <p>This alert was sent by SSL Monitor v4</p>
        <p>If you believe this is an error, please check your website's SSL configuration.</p>
    </div>
</body>
</html>