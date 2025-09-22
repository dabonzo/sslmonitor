<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSL Certificate Alert</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
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
            border-radius: 8px 8px 0 0;
            text-align: center;
        }
        .alert-level {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .critical { background-color: #dc2626; }
        .urgent { background-color: #ea580c; }
        .warning { background-color: #d97706; }
        .info { background-color: #2563eb; }
        .expired { background-color: #7f1d1d; }

        .content {
            background: white;
            padding: 30px;
            border: 1px solid #e5e7eb;
            border-top: none;
        }
        .website-info {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #3b82f6;
        }
        .days-remaining {
            font-size: 48px;
            font-weight: bold;
            color: #dc2626;
            text-align: center;
            margin: 20px 0;
        }
        .action-box {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .action-box.critical {
            background: #fef2f2;
            border-color: #dc2626;
        }
        .action-box.urgent {
            background: #fff7ed;
            border-color: #ea580c;
        }
        .btn {
            display: inline-block;
            background: #3b82f6;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 10px 0;
        }
        .btn:hover {
            background: #2563eb;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .details-table th,
        .details-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        .details-table th {
            background: #f9fafb;
            font-weight: 600;
        }
        .lets-encrypt-badge {
            background: #dbeafe;
            color: #1e40af;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .footer {
            background: #f9fafb;
            padding: 20px;
            border-radius: 0 0 8px 8px;
            border: 1px solid #e5e7eb;
            border-top: none;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="alert-level {{ strtolower($urgencyLevel) }}">
            {{ $urgencyLevel }} ALERT
        </div>
        <h1>SSL Certificate Alert</h1>
        <p>{{ $website->name }}</p>
    </div>

    <div class="content">
        <div class="website-info">
            <h2>🔒 Website Information</h2>
            <table class="details-table">
                <tr>
                    <th>Website</th>
                    <td>{{ $website->name }}</td>
                </tr>
                <tr>
                    <th>URL</th>
                    <td>{{ $website->url }}</td>
                </tr>
                <tr>
                    <th>Certificate Type</th>
                    <td>
                        @if($isLetsEncrypt)
                            <span class="lets-encrypt-badge">Let's Encrypt</span>
                        @else
                            Commercial Certificate
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>SSL Status</th>
                    <td>{{ ucfirst($certificateData['ssl_status'] ?? 'Unknown') }}</td>
                </tr>
            </table>
        </div>

        @if($daysRemaining <= 0)
            <div class="days-remaining" style="color: #7f1d1d;">
                EXPIRED
            </div>
            <p style="text-align: center; color: #dc2626; font-weight: bold;">
                ⚠️ This certificate has already expired!
            </p>
        @else
            <div class="days-remaining">
                {{ $daysRemaining }}
            </div>
            <p style="text-align: center; font-size: 18px; margin-top: -10px;">
                {{ $daysRemaining === 1 ? 'day' : 'days' }} remaining until expiry
            </p>
        @endif

        <div class="action-box {{ $daysRemaining <= 3 ? 'critical' : ($daysRemaining <= 7 ? 'urgent' : '') }}">
            <h3>📋 Action Required</h3>
            <p><strong>{{ $actionRequired }}</strong></p>

            @if($isLetsEncrypt)
                <h4>Let's Encrypt Specific Steps:</h4>
                <ul>
                    <li>Check auto-renewal service status: <code>sudo systemctl status certbot.timer</code></li>
                    <li>Test renewal: <code>sudo certbot renew --dry-run</code></li>
                    <li>Manual renewal if needed: <code>sudo certbot renew</code></li>
                    <li>Verify new certificate: <code>sudo certbot certificates</code></li>
                </ul>
            @else
                <h4>Commercial Certificate Steps:</h4>
                <ul>
                    <li>Contact your certificate provider to renew</li>
                    <li>Generate new CSR if required</li>
                    <li>Install new certificate on your server</li>
                    <li>Update SSL configuration and restart web server</li>
                </ul>
            @endif
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $dashboardUrl }}" class="btn">
                🔍 View in SSL Monitor Dashboard
            </a>
        </div>

        <h3>📊 Certificate Details</h3>
        <table class="details-table">
            @if(isset($certificateData['issuer']))
            <tr>
                <th>Issuer</th>
                <td>{{ $certificateData['issuer'] }}</td>
            </tr>
            @endif
            @if(isset($certificateData['expires_at']))
            <tr>
                <th>Expires On</th>
                <td>{{ \Carbon\Carbon::parse($certificateData['expires_at'])->format('F j, Y \a\t g:i A') }}</td>
            </tr>
            @endif
            <tr>
                <th>Alert Level</th>
                <td>{{ ucfirst($alertConfig->alert_level) }}</td>
            </tr>
            <tr>
                <th>Notification Triggered</th>
                <td>{{ now()->format('F j, Y \a\t g:i A') }}</td>
            </tr>
        </table>

        @if($alertConfig->custom_message)
        <div style="background: #f0f9ff; border: 1px solid #0ea5e9; padding: 15px; border-radius: 6px; margin: 20px 0;">
            <h4 style="margin: 0 0 10px 0; color: #0369a1;">📝 Custom Note</h4>
            <p style="margin: 0;">{{ $alertConfig->custom_message }}</p>
        </div>
        @endif
    </div>

    <div class="footer">
        <p>This alert was generated automatically by SSL Monitor v4</p>
        <p>You can configure alert settings in your <a href="{{ route('dashboard') }}">dashboard</a></p>
        <p style="font-size: 12px; margin-top: 15px;">
            Alert ID: {{ $alertConfig->id }} |
            Website ID: {{ $website->id }} |
            Triggered: {{ now()->toISOString() }}
        </p>
    </div>
</body>
</html>