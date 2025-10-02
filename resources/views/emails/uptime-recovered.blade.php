<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Website Recovered Alert</title>
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
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
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
            background-color: #065f46;
        }
        .content {
            background: white;
            padding: 30px;
            border: 1px solid #e5e7eb;
            border-top: none;
        }
        .website-info {
            background: #f0fdf4;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #10b981;
        }
        .status-badge {
            font-size: 48px;
            font-weight: bold;
            color: #059669;
            text-align: center;
            margin: 20px 0;
        }
        .info-box {
            background: #f0fdf4;
            border: 1px solid #10b981;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .btn {
            display: inline-block;
            background: #059669;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 10px 0;
        }
        .btn:hover {
            background: #047857;
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
            RECOVERED
        </div>
        <h1>✅ Website Back Online</h1>
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
                    <td style="color: #059669; font-weight: bold;">✅ ONLINE</td>
                </tr>
            </table>
        </div>

        <div class="status-badge">
            ✅ ONLINE
        </div>
        <p style="text-align: center; color: #059669; font-weight: bold; font-size: 18px;">
            Your website is back online and accessible
        </p>

        <div class="info-box">
            <h3>📊 Recovery Information</h3>

            @if($downtime)
            <p><strong>Downtime Duration:</strong> {{ $downtime }}</p>
            @endif

            @if($responseTime)
            <p><strong>Current Response Time:</strong> {{ $responseTime }}ms</p>
            @endif

            @if($statusCode)
            <p><strong>HTTP Status:</strong> {{ $statusCode }}</p>
            @endif

            <p><strong>Recovered At:</strong> {{ \Carbon\Carbon::parse($recoveredAt)->format('F j, Y \a\t g:i A') }}</p>

            <h4>✅ Recommended Next Steps:</h4>
            <ul>
                <li>Review server logs to understand what caused the downtime</li>
                <li>Check for any remaining errors or warnings</li>
                <li>Verify all website functionality is working correctly</li>
                <li>Consider implementing additional monitoring or redundancy</li>
                <li>Document the incident and resolution steps</li>
            </ul>
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
                <td>Website Recovered</td>
            </tr>
            <tr>
                <th>Previous Status</th>
                <td style="color: #dc2626;">Down</td>
            </tr>
            <tr>
                <th>Current Status</th>
                <td style="color: #059669;">Online</td>
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
