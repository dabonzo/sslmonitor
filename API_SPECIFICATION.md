# SSL Monitor v3 - API Specification

## 🔗 Overview

SSL Monitor v3 follows RESTful API principles with Laravel 12 backend and Inertia.js frontend integration. All API endpoints are secured with Laravel Sanctum and follow strict validation rules.

**Base URL**: `/api/v1`
**Authentication**: Laravel Sanctum with SPA authentication
**Content Type**: `application/json`
**Error Format**: JSON with standardized error codes

---

## 🔐 Authentication System

### Authentication Endpoints

#### Register User
```http
POST /api/v1/auth/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "secure_password",
  "password_confirmation": "secure_password",
  "company_name": "Acme Corp",
  "timezone": "America/New_York"
}
```

**Response (201 Created)**:
```json
{
  "success": true,
  "message": "Registration successful. Please verify your email.",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "company_name": "Acme Corp",
      "timezone": "America/New_York",
      "email_verified_at": null,
      "created_at": "2024-01-15T10:30:00Z"
    }
  }
}
```

#### Login User
```http
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "secure_password",
  "remember_me": true
}
```

**Response (200 OK)**:
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "role": "owner",
      "team": {
        "id": 1,
        "name": "Acme Corp Team",
        "plan": "pro"
      }
    },
    "token": "sanctum_token_here",
    "expires_at": "2024-02-15T10:30:00Z"
  }
}
```

#### Logout
```http
POST /api/v1/auth/logout
Authorization: Bearer {token}
```

**Response (200 OK)**:
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

---

## 🌐 Website Management

### Website Endpoints

#### List Websites
```http
GET /api/v1/websites
Authorization: Bearer {token}
```

**Query Parameters**:
- `page` (int): Page number for pagination
- `per_page` (int): Items per page (max 100)
- `search` (string): Search by domain or name
- `status` (string): Filter by status (active, inactive, warning, error)
- `sort_by` (string): Sort by field (domain, status, next_check, created_at)
- `sort_direction` (string): Sort direction (asc, desc)

**Response (200 OK)**:
```json
{
  "success": true,
  "data": {
    "websites": [
      {
        "id": 1,
        "domain": "example.com",
        "protocol": "https",
        "port": 443,
        "name": "Main Website",
        "status": "active",
        "ssl_certificate": {
          "issuer": "Let's Encrypt",
          "expires_at": "2024-06-15T00:00:00Z",
          "days_until_expiry": 45,
          "is_valid": true,
          "last_checked_at": "2024-01-15T10:00:00Z"
        },
        "uptime": {
          "status": "up",
          "response_time": 245,
          "last_checked_at": "2024-01-15T10:25:00Z",
          "uptime_percentage": 99.95
        },
        "notifications_enabled": true,
        "created_at": "2024-01-01T00:00:00Z",
        "updated_at": "2024-01-15T10:25:00Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 3,
      "per_page": 25,
      "total": 67,
      "from": 1,
      "to": 25
    }
  }
}
```

#### Create Website
```http
POST /api/v1/websites
Authorization: Bearer {token}
Content-Type: application/json

{
  "domain": "newsite.com",
  "protocol": "https",
  "port": 443,
  "name": "New Website",
  "check_ssl": true,
  "check_uptime": true,
  "uptime_check_interval": 300,
  "notifications_enabled": true,
  "notification_emails": ["admin@newsite.com"],
  "advanced_options": {
    "check_javascript": true,
    "expected_content": "Welcome to our site",
    "timeout": 30,
    "user_agent": "SSL Monitor v3"
  }
}
```

**Response (201 Created)**:
```json
{
  "success": true,
  "message": "Website added successfully",
  "data": {
    "website": {
      "id": 2,
      "domain": "newsite.com",
      "protocol": "https",
      "port": 443,
      "name": "New Website",
      "status": "pending",
      "first_check_scheduled_at": "2024-01-15T10:35:00Z",
      "created_at": "2024-01-15T10:30:00Z"
    }
  }
}
```

#### Get Website Details
```http
GET /api/v1/websites/{id}
Authorization: Bearer {token}
```

**Response (200 OK)**:
```json
{
  "success": true,
  "data": {
    "website": {
      "id": 1,
      "domain": "example.com",
      "protocol": "https",
      "port": 443,
      "name": "Main Website",
      "status": "active",
      "ssl_certificate": {
        "issuer": "Let's Encrypt",
        "subject": "CN=example.com",
        "expires_at": "2024-06-15T00:00:00Z",
        "issued_at": "2024-03-15T00:00:00Z",
        "days_until_expiry": 45,
        "is_valid": true,
        "validation_errors": [],
        "certificate_chain": [
          {
            "subject": "CN=example.com",
            "issuer": "CN=Let's Encrypt Authority X3"
          }
        ],
        "last_checked_at": "2024-01-15T10:00:00Z"
      },
      "uptime": {
        "status": "up",
        "response_time": 245,
        "last_checked_at": "2024-01-15T10:25:00Z",
        "uptime_percentage_24h": 100,
        "uptime_percentage_7d": 99.95,
        "uptime_percentage_30d": 99.87,
        "recent_downtime": []
      },
      "check_history": [
        {
          "checked_at": "2024-01-15T10:25:00Z",
          "type": "uptime",
          "status": "success",
          "response_time": 245,
          "status_code": 200
        }
      ],
      "notifications": {
        "enabled": true,
        "email_addresses": ["admin@example.com"],
        "notification_preferences": {
          "ssl_expiry": {
            "enabled": true,
            "days_before": [30, 14, 7, 1]
          },
          "uptime": {
            "enabled": true,
            "failure_threshold": 3
          }
        }
      },
      "created_at": "2024-01-01T00:00:00Z",
      "updated_at": "2024-01-15T10:25:00Z"
    }
  }
}
```

#### Update Website
```http
PUT /api/v1/websites/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Updated Website Name",
  "check_ssl": true,
  "check_uptime": true,
  "uptime_check_interval": 600,
  "notifications_enabled": false
}
```

#### Delete Website
```http
DELETE /api/v1/websites/{id}
Authorization: Bearer {token}
```

**Response (200 OK)**:
```json
{
  "success": true,
  "message": "Website deleted successfully"
}
```

---

## 📊 Monitoring Data

### SSL Certificate Endpoints

#### Trigger Manual SSL Check
```http
POST /api/v1/websites/{id}/check-ssl
Authorization: Bearer {token}
```

**Response (200 OK)**:
```json
{
  "success": true,
  "message": "SSL check initiated",
  "data": {
    "check_id": "uuid-here",
    "estimated_completion": "2024-01-15T10:32:00Z"
  }
}
```

#### Get SSL Certificate History
```http
GET /api/v1/websites/{id}/ssl-history
Authorization: Bearer {token}
```

**Query Parameters**:
- `from_date` (date): Start date for history
- `to_date` (date): End date for history
- `limit` (int): Maximum records to return

### Uptime Monitoring Endpoints

#### Trigger Manual Uptime Check
```http
POST /api/v1/websites/{id}/check-uptime
Authorization: Bearer {token}
```

#### Get Uptime Statistics
```http
GET /api/v1/websites/{id}/uptime-stats
Authorization: Bearer {token}
```

**Query Parameters**:
- `period` (string): Time period (24h, 7d, 30d, 90d)

**Response (200 OK)**:
```json
{
  "success": true,
  "data": {
    "period": "30d",
    "uptime_percentage": 99.87,
    "total_checks": 8640,
    "successful_checks": 8629,
    "failed_checks": 11,
    "average_response_time": 234,
    "incidents": [
      {
        "started_at": "2024-01-10T14:30:00Z",
        "ended_at": "2024-01-10T14:45:00Z",
        "duration_minutes": 15,
        "reason": "HTTP 500 Error"
      }
    ]
  }
}
```

---

## 👥 Team Management

### Team Endpoints

#### Get Team Information
```http
GET /api/v1/team
Authorization: Bearer {token}
```

**Response (200 OK)**:
```json
{
  "success": true,
  "data": {
    "team": {
      "id": 1,
      "name": "Acme Corp Team",
      "plan": "pro",
      "websites_limit": 50,
      "websites_count": 23,
      "members": [
        {
          "id": 1,
          "name": "John Doe",
          "email": "john@example.com",
          "role": "owner",
          "joined_at": "2024-01-01T00:00:00Z"
        },
        {
          "id": 2,
          "name": "Jane Smith",
          "email": "jane@example.com",
          "role": "admin",
          "joined_at": "2024-01-05T00:00:00Z"
        }
      ],
      "created_at": "2024-01-01T00:00:00Z"
    }
  }
}
```

#### Invite Team Member
```http
POST /api/v1/team/invite
Authorization: Bearer {token}
Content-Type: application/json

{
  "email": "newuser@example.com",
  "role": "viewer",
  "message": "Join our SSL monitoring team!"
}
```

#### Update Team Member Role
```http
PUT /api/v1/team/members/{user_id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "role": "admin"
}
```

#### Remove Team Member
```http
DELETE /api/v1/team/members/{user_id}
Authorization: Bearer {token}
```

---

## 🔔 Notification Management

### Notification Endpoints

#### Get Notification Settings
```http
GET /api/v1/notifications/settings
Authorization: Bearer {token}
```

#### Update Notification Settings
```http
PUT /api/v1/notifications/settings
Authorization: Bearer {token}
Content-Type: application/json

{
  "global_notifications": true,
  "email_notifications": {
    "ssl_expiry": {
      "enabled": true,
      "days_before": [30, 14, 7, 1]
    },
    "uptime_alerts": {
      "enabled": true,
      "failure_threshold": 3,
      "recovery_notification": true
    },
    "weekly_summary": {
      "enabled": true,
      "day_of_week": "monday"
    }
  }
}
```

#### Get Notification History
```http
GET /api/v1/notifications/history
Authorization: Bearer {token}
```

---

## 📈 Analytics and Reporting

### Analytics Endpoints

#### Dashboard Overview
```http
GET /api/v1/dashboard
Authorization: Bearer {token}
```

**Response (200 OK)**:
```json
{
  "success": true,
  "data": {
    "summary": {
      "total_websites": 23,
      "active_websites": 21,
      "ssl_expiring_soon": 3,
      "uptime_issues": 1,
      "average_uptime": 99.87,
      "average_response_time": 245
    },
    "recent_activity": [
      {
        "type": "ssl_check",
        "website": "example.com",
        "status": "success",
        "timestamp": "2024-01-15T10:25:00Z"
      }
    ],
    "alerts": [
      {
        "id": 1,
        "type": "ssl_expiry",
        "website": "oldsite.com",
        "message": "SSL certificate expires in 7 days",
        "severity": "warning",
        "created_at": "2024-01-15T09:00:00Z"
      }
    ]
  }
}
```

#### Export Data
```http
POST /api/v1/export
Authorization: Bearer {token}
Content-Type: application/json

{
  "type": "csv",
  "data_type": "websites",
  "filters": {
    "date_range": {
      "from": "2024-01-01",
      "to": "2024-01-15"
    },
    "websites": [1, 2, 3]
  }
}
```

---

## ⚠️ Error Handling

### Standard Error Responses

#### Validation Error (422)
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "domain": ["The domain field is required."],
    "email": ["The email must be a valid email address."]
  }
}
```

#### Authentication Error (401)
```json
{
  "success": false,
  "message": "Unauthenticated.",
  "error_code": "UNAUTHENTICATED"
}
```

#### Authorization Error (403)
```json
{
  "success": false,
  "message": "This action is unauthorized.",
  "error_code": "FORBIDDEN"
}
```

#### Not Found Error (404)
```json
{
  "success": false,
  "message": "The requested resource was not found.",
  "error_code": "NOT_FOUND"
}
```

#### Rate Limit Error (429)
```json
{
  "success": false,
  "message": "Too many requests. Please try again later.",
  "error_code": "RATE_LIMIT_EXCEEDED",
  "retry_after": 60
}
```

#### Server Error (500)
```json
{
  "success": false,
  "message": "An internal server error occurred.",
  "error_code": "INTERNAL_ERROR",
  "reference_id": "uuid-here"
}
```

---

## 🔄 API Versioning

### Version Strategy
- **Current Version**: v1
- **Header-based versioning**: `Accept: application/vnd.sslmonitor.v1+json`
- **URL-based versioning**: `/api/v1/` (fallback)
- **Backward compatibility**: Maintained for at least 12 months

### Version Migration
When API changes occur:
1. New version released with breaking changes
2. Previous version marked as deprecated
3. 6-month deprecation notice provided
4. Migration guide published

---

## 🚀 Rate Limiting

### Rate Limit Rules
- **General API**: 1000 requests per hour per user
- **Authentication**: 5 failed login attempts per 15 minutes
- **SSL Checks**: 10 manual checks per hour per website
- **Uptime Checks**: 10 manual checks per hour per website

### Rate Limit Headers
```http
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 987
X-RateLimit-Reset: 1642248000
```

---

## 📝 Testing the API

### Development Environment
- **Base URL**: `http://localhost/api/v1`
- **Authentication**: Session-based for Inertia.js SPA

### Production Environment
- **Base URL**: `https://sslmonitor.com/api/v1`
- **Authentication**: Laravel Sanctum tokens

### Example API Client Setup
```javascript
// Laravel Inertia.js setup
import { router } from '@inertiajs/vue3'

// Making API calls through Inertia
router.post('/websites', {
  domain: 'example.com',
  protocol: 'https',
  port: 443
}, {
  preserveState: true,
  onSuccess: (page) => {
    console.log('Website created:', page.props.flash.success)
  }
})
```

---

This API specification provides the complete backend interface for SSL Monitor v3, designed to work seamlessly with the Vue.js + Inertia.js frontend while maintaining REST principles and Laravel best practices.