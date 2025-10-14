<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slow Response Time Alert</title>
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
            background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
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
            background: #ffc107;
            color: #212529;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #e0a800;
        }
        .website-info {
            background: white;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #ffc107;
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
        .response-time {
            font-size: 24px;
            font-weight: bold;
            color: #dc3545;
        }
        .status-indicator {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .warning {
            background: #ffc107;
            color: #212529;
        }
        .critical {
            background: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>⚡ Slow Response Time Detected</h1>
        <p>Performance Alert</p>
    </div>

    <div class="content">
        <div class="alert">
            <strong>{{ $urgencyLevel }} PERFORMANCE ALERT</strong><br>
            Your website is responding slowly and may be affecting user experience.
        </div>

        <div class="website-info">
            <h3>🌐 Website Details</h3>
            <p><strong>Name:</strong> {{ $website->name }}</p>
            <p><strong>URL:</strong> <a href="{{ $website->url }}">{{ $website->url }}</a></p>
            <p><strong>Response Time:</strong> <span class="response-time">{{ $checkData['response_time'] }}ms</span></p>
            <p><strong>Urgency Level:</strong>
                <span class="status-indicator {{ $urgencyLevel === 'CRITICAL' ? 'critical' : 'warning' }}">
                    {{ $urgencyLevel }}
                </span>
            </p>
        </div>

        <div class="website-info">
            <h3>📊 Performance Metrics</h3>
            <p><strong>Current Response Time:</strong> {{ $checkData['response_time'] }}ms</p>
            <p><strong>Checked At:</strong> {{ $checkData['checked_at'] ?? now()->format('Y-m-d H:i:s') }}</p>

            @if(isset($checkData['threshold_exceeded']) && $checkData['threshold_exceeded'])
                <p><strong>Status:</strong> <span style="color: #dc3545; font-weight: bold;">Threshold Exceeded</span></p>
            @endif
        </div>

        <div class="website-info">
            <h3>⚠️ Performance Impact</h3>
            <p>A slow response time of {{ $checkData['response_time'] }}ms can lead to:</p>
            <ul>
                <li>Poor user experience and higher bounce rates</li>
                <li>Reduced conversion rates and engagement</li>
                <li>Negative impact on SEO rankings</li>
                <li>Loss of customer trust and satisfaction</li>
                <li>Increased server resource consumption</li>
            </ul>
        </div>

        @if($urgencyLevel === 'CRITICAL')
        <div class="website-info">
            <h3>🚨 Critical Performance Issues</h3>
            <p>Your website's response time exceeds 10 seconds, which indicates:</p>
            <ul>
                <li>Severe performance degradation</li>
                <li>Potential server or application issues</li>
                <li>Database or network connectivity problems</li>
                <li>Resource exhaustion or configuration issues</li>
            </ul>
        </div>
        @endif

        <div class="website-info">
            <h3>🔧 Recommended Actions</h3>
            <ol>
                <li>Check server load and resource utilization</li>
                <li>Monitor database query performance</li>
                <li>Review application logs for errors</li>
                <li>Check network connectivity and latency</li>
                <li>Optimize images and static assets</li>
                <li>Consider implementing caching strategies</li>
                <li>Monitor third-party service dependencies</li>
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
        <p>Response time thresholds: Warning > 5s, Critical > 10s</p>
    </div>
</body>
</html>