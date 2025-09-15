// SSL Monitoring without WebSockets - using periodic refresh
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing periodic status checks...');

    // Poll for updates every 30 seconds
    setInterval(() => {
        refreshSSLCounts();
        refreshUptimeCounts();
    }, 30000);
});

// Helper function to update SSL status indicators in the UI
function updateSSLStatusIndicator(sslCheck) {
    // Find SSL status elements by website ID and update them
    const websiteElements = document.querySelectorAll(`[data-website-id="${sslCheck.website_id}"]`);
    
    websiteElements.forEach(element => {
        // Update status badge
        const statusBadge = element.querySelector('[data-ssl-status]');
        if (statusBadge) {
            statusBadge.setAttribute('data-ssl-status', sslCheck.status);
            statusBadge.textContent = getSSLStatusText(sslCheck.status);
            statusBadge.className = getSSLStatusClasses(sslCheck.status);
        }
        
        // Update days until expiry
        const expiryElement = element.querySelector('[data-ssl-expiry]');
        if (expiryElement && sslCheck.days_until_expiry) {
            expiryElement.textContent = `${sslCheck.days_until_expiry} days`;
        }
    });
}

// Helper function to update uptime status indicators in the UI
function updateUptimeStatusIndicator(uptimeCheck) {
    // Find uptime status elements by website ID and update them
    const websiteElements = document.querySelectorAll(`[data-website-id="${uptimeCheck.website_id}"]`);
    
    websiteElements.forEach(element => {
        // Update uptime status badge
        const statusBadge = element.querySelector('[data-uptime-status]');
        if (statusBadge) {
            statusBadge.setAttribute('data-uptime-status', uptimeCheck.status);
            statusBadge.textContent = getUptimeStatusText(uptimeCheck.status);
            statusBadge.className = getUptimeStatusClasses(uptimeCheck.status);
        }
        
        // Update response time
        const responseTimeElement = element.querySelector('[data-response-time]');
        if (responseTimeElement && uptimeCheck.response_time) {
            responseTimeElement.textContent = `${uptimeCheck.response_time}ms`;
        }
    });
}

// Helper functions for SSL status text and styling
function getSSLStatusText(status) {
    const statusMap = {
        'valid': 'Valid',
        'expiring_soon': 'Expiring Soon',
        'expired': 'Expired',
        'error': 'Error'
    };
    return statusMap[status] || status;
}

function getSSLStatusClasses(status) {
    const classMap = {
        'valid': 'inline-flex items-center px-2 py-1 text-xs font-medium text-green-700 bg-green-100 rounded-full dark:text-green-400 dark:bg-green-900/20',
        'expiring_soon': 'inline-flex items-center px-2 py-1 text-xs font-medium text-yellow-700 bg-yellow-100 rounded-full dark:text-yellow-400 dark:bg-yellow-900/20',
        'expired': 'inline-flex items-center px-2 py-1 text-xs font-medium text-red-700 bg-red-100 rounded-full dark:text-red-400 dark:bg-red-900/20',
        'error': 'inline-flex items-center px-2 py-1 text-xs font-medium text-red-700 bg-red-100 rounded-full dark:text-red-400 dark:bg-red-900/20'
    };
    return classMap[status] || 'inline-flex items-center px-2 py-1 text-xs font-medium text-gray-700 bg-gray-100 rounded-full dark:text-gray-400 dark:bg-gray-900/20';
}

// Helper functions for uptime status text and styling
function getUptimeStatusText(status) {
    const statusMap = {
        'up': 'Online',
        'down': 'Offline',
        'degraded': 'Degraded'
    };
    return statusMap[status] || status;
}

function getUptimeStatusClasses(status) {
    const classMap = {
        'up': 'inline-flex items-center px-2 py-1 text-xs font-medium text-green-700 bg-green-100 rounded-full dark:text-green-400 dark:bg-green-900/20',
        'down': 'inline-flex items-center px-2 py-1 text-xs font-medium text-red-700 bg-red-100 rounded-full dark:text-red-400 dark:bg-red-900/20',
        'degraded': 'inline-flex items-center px-2 py-1 text-xs font-medium text-yellow-700 bg-yellow-100 rounded-full dark:text-yellow-400 dark:bg-yellow-900/20'
    };
    return classMap[status] || 'inline-flex items-center px-2 py-1 text-xs font-medium text-gray-700 bg-gray-100 rounded-full dark:text-gray-400 dark:bg-gray-900/20';
}

// Show toast notifications for SSL status changes
function showSSLStatusNotification(sslCheck) {
    // Create a temporary toast notification (you can integrate with your existing toast system)
    if (typeof window.showToast === 'function') {
        const message = `SSL status changed for ${sslCheck.website_url}: ${getSSLStatusText(sslCheck.status)}`;
        const type = sslCheck.status === 'valid' ? 'success' : 'warning';
        window.showToast(message, type);
    } else {
        // Fallback console notification
        console.info(`SSL Update: ${sslCheck.website_url} is now ${sslCheck.status}`);
    }
}

// Show toast notifications for uptime status changes  
function showUptimeStatusNotification(uptimeCheck) {
    if (typeof window.showToast === 'function') {
        const message = `Uptime status changed for ${uptimeCheck.website_url}: ${getUptimeStatusText(uptimeCheck.status)}`;
        const type = uptimeCheck.status === 'up' ? 'success' : 'error';
        window.showToast(message, type);
    } else {
        // Fallback console notification
        console.info(`Uptime Update: ${uptimeCheck.website_url} is now ${uptimeCheck.status}`);
    }
}

// Refresh SSL counts in the dashboard (trigger Livewire refresh if needed)
function refreshSSLCounts() {
    // Dispatch custom event that Livewire components can listen to
    window.dispatchEvent(new CustomEvent('ssl-counts-updated'));
}

// Refresh uptime counts in the dashboard  
function refreshUptimeCounts() {
    // Dispatch custom event that Livewire components can listen to
    window.dispatchEvent(new CustomEvent('uptime-counts-updated'));
}