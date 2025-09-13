# SSL Monitor Documentation

Welcome to the SSL Monitor documentation. This Laravel application provides comprehensive SSL certificate monitoring with an intuitive web interface and automated email notifications.

## 📖 Documentation Structure

### 👥 **User Guide**
Documentation for end users of the SSL Monitor application.

- **[Getting Started](user-guide/getting-started.md)** - First-time setup and overview
- **[Dashboard Overview](user-guide/dashboard-overview.md)** - Understanding your SSL status dashboard
- **[Website Management](user-guide/website-management.md)** - Adding and managing monitored websites
- **[Uptime Monitoring](user-guide/uptime-monitoring.md)** - Advanced uptime validation and content checking ⭐ NEW
- **[Notification Settings](user-guide/notification-settings.md)** - Configuring email alerts and preferences ⭐ NEW
- **[Team Management](user-guide/team-management.md)** - Collaborating with teams and colleagues
- **[Email Configuration](user-guide/email-configuration.md)** - Setting up SMTP for notifications
- **[SSL Status Guide](user-guide/ssl-status-guide.md)** - Understanding certificate statuses and alerts
- **[Troubleshooting](user-guide/troubleshooting.md)** - Common issues and solutions

### 🔧 **Admin Guide**
Documentation for system administrators and deployers.

- **[Installation](admin-guide/installation.md)** - Complete installation guide
- **[Deployment](admin-guide/deployment.md)** - Production deployment checklist
- **[Environment Configuration](admin-guide/environment-config.md)** - .env configuration reference
- **[Queue Management](admin-guide/queue-management.md)** - Setting up and monitoring background jobs
- **[Artisan Commands](admin-guide/artisan-commands.md)** - Complete command reference
- **[System Monitoring](admin-guide/monitoring.md)** - Monitoring application health
- **[Maintenance](admin-guide/maintenance.md)** - Updates, backups, and cleanup
- **[Security](admin-guide/security.md)** - Security best practices

### 💻 **Developer Guide**
Documentation for developers working on the SSL Monitor codebase.

- **[Architecture](developer-guide/architecture.md)** - Technology stack and system design
- **[Testing](developer-guide/testing.md)** - TDD methodology and comprehensive testing
- **[Contributing](developer-guide/contributing.md)** - Development setup and guidelines
- **[API Reference](developer-guide/api-reference.md)** - Models, services, and jobs (planned)

## 🚀 Quick Start

### For Users
1. **Login** to your SSL Monitor instance
2. **Configure email settings** in Settings → Email Settings
3. **Create or join a team** (optional) in Settings → Team Management ⭐ NEW
4. **Add websites** to monitor via the Websites page
5. **View your dashboard** for SSL status overview

### For Administrators
1. **Install** following the [installation guide](admin-guide/installation.md)
2. **Configure** environment variables
3. **Set up** background queue workers
4. **Deploy** using the production checklist

## 🎯 Key Features

- **Automated SSL Monitoring** - Daily checks of all monitored websites
- **Advanced Uptime Monitoring** - Multi-level validation beyond simple HTTP 200 checks ⭐ NEW
- **Content Validation** - Detect hosting company default pages and maintenance screens ⭐ NEW
- **Comprehensive Notifications** - Professional email alerts for SSL and uptime issues ⭐ NEW
- **Granular Alert Preferences** - Control exactly which notifications you receive ⭐ NEW
- **Real-time Dashboard** - Comprehensive overview of SSL certificate and uptime status
- **Live WebSocket Updates** - Real-time SSL and uptime status changes via Laravel Reverb 🚀 NEW
- **Team Collaboration** - Share SSL monitoring with colleagues and teams with real-time sync
- **Role-based Permissions** - Owner, Admin, Manager, and Viewer roles
- **In-app Email Configuration** - Configure SMTP settings without server access
- **Advanced Monitoring** - Laravel Horizon + Pulse dashboards for queue management and performance insights
- **Beautiful Interface** - Modern, responsive design with dark mode and live updates
- **Comprehensive Testing** - 335+ tests ensuring reliability including uptime notifications ⭐ NEW

## 🏗️ Technology Stack

### Core Framework
- **Laravel 12** - Modern PHP framework with latest features
- **PHP 8.2+** - Latest PHP with performance improvements
- **MySQL 8.0+ / MariaDB 10.3+** - Relational database
- **Redis** - Caching and queue management
- **Laravel Reverb** - Native WebSocket server for real-time updates 🚀 NEW

### Frontend
- **Livewire 3** - Full-stack reactive components
- **Alpine.js** - Minimal JavaScript framework
- **Tailwind CSS v4** - Utility-first CSS framework
- **Flux UI Free** - Professional UI component library
- **Vite** - Modern frontend build tool

### Development & Testing
- **Pest PHP 4** - Modern testing framework with TDD methodology
- **Laravel Pint** - Code formatting and style enforcement
- **Laravel Sail** - Docker development environment
- **335+ comprehensive tests** - Full TDD coverage including notifications and uptime monitoring

## 📊 System Requirements

- **PHP**: 8.2 or higher
- **Laravel**: 12.x
- **Database**: MySQL 8.0+ or MariaDB 10.3+
- **Queue Driver**: Redis (recommended) or Database
- **Web Server**: Apache or Nginx

## 🆘 Support

- **User Issues**: Check the [User Troubleshooting Guide](user-guide/troubleshooting.md)
- **Admin Issues**: See [System Monitoring](admin-guide/monitoring.md)
- **Development**: Review [Contributing Guidelines](developer-guide/contributing.md)

---

**SSL Monitor** - Professional SSL certificate monitoring made simple.