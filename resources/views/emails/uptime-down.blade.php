<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Website Down Alert</title>
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
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
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
            background-color: #7f1d1d;
        }
        .content {
            background: white;
            padding: 30px;
            border: 1px solid #e5e7eb;
            border-top: none;
        }
        .website-info {
            background: #fef2f2;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #dc2626;
        }
        .status-badge {
            font-size: 48px;
            font-weight: bold;
            color: #dc2626;
            text-align: center;
            margin: 20px 0;
        }
        .action-box {
            background: #fef2f2;
            border: 1px solid #dc2626;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .btn {
            display: inline-block;
            background: #dc2626;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 10px 0;
        }
        .btn:hover {
            background: #b91c1c;
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
        <div class="alert-level">
            {{ $urgencyLevel }} ALERT
        </div>
        <h1>⚠️ Website Down</h1>
        <p>{{ $website->name }}</p>
    </div>

    <div class="content">
        <div class="website-info">
            <h2>🌐 Website Information</h2>
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
                    <th>Status</th>
                    <td style="color: #dc2626; font-weight: bold;">⛔ DOWN</td>
                </tr>
            </table>
        </div>

        <div class="status-badge">
            ⛔ OFFLINE
        </div>
        <p style="text-align: center; color: #dc2626; font-weight: bold; font-size: 18px;">
            Your website is currently unreachable
        </p>

        <div class="action-box">
            <h3>📋 Immediate Action Required</h3>
            <p><strong>Your website is down and needs immediate attention.</strong></p>

            <h4>Troubleshooting Steps:</h4>
            <ul>
                <li>Check if your server is running and accessible</li>
                <li>Verify DNS configuration is correct</li>
                <li>Check web server logs for errors (Apache/Nginx)</li>
                <li>Ensure firewall rules allow HTTP/HTTPS traffic</li>
                <li>Verify SSL certificate if using HTTPS</li>
                <li>Check server resources (CPU, memory, disk space)</li>
            </ul>

            <h4>Failure Details:</h4>
            <p><strong>Reason:</strong> {{ $failureReason }}</p>
            @if($statusCode)
            <p><strong>Status Code:</strong> {{ $statusCode }}</p>
            @endif
            <p><strong>Last Checked:</strong> {{ \Carbon\Carbon::parse($lastChecked)->format('F j, Y \a\t g:i A') }}</p>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $dashboardUrl }}" class="btn">
                🔍 View in SSL Monitor Dashboard
            </a>
        </div>

        <h3>📊 Alert Details</h3>
        <table class="details-table">
            <tr>
                <th>Alert Type</th>
                <td>Website Down</td>
            </tr>
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
