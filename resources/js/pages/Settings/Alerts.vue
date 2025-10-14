<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import HeadingSmall from '@/components/HeadingSmall.vue';
import ModernSettingsLayout from '@/layouts/ModernSettingsLayout.vue';
import { Shield, Info, ExternalLink, Zap, Clock } from 'lucide-vue-next';

interface AlertConfiguration {
    id: number;
    alert_type: string;
    alert_type_label: string;
    enabled: boolean;
    alert_level: string;
    threshold_days: number | null;
    threshold_response_time: number | null;
    notification_channels: string[];
    custom_message: string | null;
}

interface Props {
    globalAlerts: {
        sslExpiryAlerts: AlertConfiguration[];
        uptimeAlerts: AlertConfiguration[];
        responseTimeAlerts: AlertConfiguration[];
    };
}

const props = defineProps<Props>();

// Group SSL expiry alerts by threshold days for simple checkbox interface
const sslExpiryAlerts = computed(() => {
    const alerts = props.globalAlerts?.sslExpiryAlerts || [];

    // Group by threshold days - these come from the user's saved database records
    const grouped = alerts.reduce((acc, alert) => {
        if (alert.threshold_days !== null) {
            const existing = acc.find(item => item.threshold_days === alert.threshold_days);
            if (existing) {
                existing.alerts.push(alert);
            } else {
                acc.push({
                    threshold_days: alert.threshold_days,
                    alerts: [alert]
                });
            }
        }
        return acc;
    }, []);

    // Always show all SSL expiry periods, using database values
    // This ensures consistent ordering: 30, 14, 7, 3, 0 days
    const allPeriods = [30, 14, 7, 3, 0];
    return allPeriods.map(days => {
        const userAlert = grouped.find(g => g.threshold_days === days)?.alerts[0];

        // These levels should match the backend defaults
        const level = days === 30 ? 'info' :
                    days === 14 ? 'warning' :
                    days === 7 ? 'urgent' : 'critical';

        return {
            threshold_days: days,
            label: days === 0 ? 'EXPIRED' : `${days} days before expiry`,
            level,
            enabled: userAlert?.enabled ?? false, // Default to false, will be populated from database
            id: userAlert?.id
        };
    });
});

// Other alert types - use database values directly
const uptimeAlerts = computed(() => {
    const alerts = props.globalAlerts?.uptimeAlerts || [];
    // Sort by alert_type to ensure consistent ordering (uptime_down first, uptime_up second)
    return alerts.sort((a, b) => (a.alert_type).localeCompare(b.alert_type));
});

const responseTimeAlerts = computed(() => {
    const alerts = props.globalAlerts?.responseTimeAlerts || [];
    // Sort by threshold_response_time to ensure consistent ordering (5000ms first, 10000ms second)
    return alerts.sort((a, b) => (a.threshold_response_time || 0) - (b.threshold_response_time || 0));
});

// Get alert level color
const getAlertLevelColor = (level: string) => {
    const colors = {
        'critical': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        'urgent': 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
        'warning': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        'info': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
    };
    return colors[level] || colors['info'];
};

// Toggle global alert template
const toggleGlobalAlert = (alertType: string, thresholdDays: number | null, currentEnabled: boolean, alertId: number | null = null) => {
    const formData = new FormData();
    formData.append('alert_type', alertType);
    if (thresholdDays !== null) {
        formData.append('threshold_days', thresholdDays.toString());
    }
    formData.append('enabled', (!currentEnabled) ? '1' : '0');
    if (alertId) {
        formData.append('alert_id', alertId.toString());
    }

    router.post('/settings/alerts/global/update', formData, {
        preserveScroll: true,
        onSuccess: () => {
            // Page will reload with updated data
        },
        onError: (errors) => {
            console.error('Failed to update global alert template:', errors);
        }
    });
};
</script>

<template>
    <Head title="Global Alert Templates" />

    <ModernSettingsLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="space-y-1">
                <HeadingSmall title="Global Alert Templates" />
                <p class="text-sm text-muted-foreground">
                    Configure default alert settings that will be applied to new websites
                </p>
            </div>

            <!-- Info Card -->
            <Card class="bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800">
                <div class="p-6">
                    <div class="flex items-start gap-3">
                        <Info class="h-5 w-5 text-blue-600 dark:text-blue-400 mt-0.5" />
                        <div class="text-sm text-blue-800 dark:text-blue-200">
                            <h3 class="font-medium mb-1">Template Settings</h3>
                            <p class="text-blue-700 dark:text-blue-300">
                                These settings are templates only. When you add a new website, it will inherit these default alert configurations.
                                Existing websites keep their current alert settings and can be configured individually via the websites page.
                            </p>
                        </div>
                    </div>
                </div>
            </Card>

            <!-- SSL Certificate Expiry Alerts -->
            <Card>
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-9 h-9 bg-emerald-50 dark:bg-emerald-900/30 rounded-lg flex items-center justify-center">
                            <Shield class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-foreground">
                                SSL Certificate Expiry Alerts
                            </h3>
                            <p class="text-sm text-muted-foreground">
                                Configure when to be notified about certificate expiration
                            </p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div
                            v-for="alert in sslExpiryAlerts"
                            :key="`ssl-expiry-${alert.threshold_days}`"
                            :class="[
                                'group relative p-4 rounded-lg border transition-colors duration-200 cursor-pointer',
                                alert.enabled
                                    ? 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-800'
                                    : 'bg-card border-border hover:bg-accent/50'
                            ]"
                            @click="toggleGlobalAlert('ssl_expiry', alert.threshold_days, alert.enabled, alert.id)"
                        >
                            <!-- Enabled indicator line -->
                            <div
                                v-if="alert.enabled"
                                class="absolute left-0 top-0 bottom-0 w-1 bg-emerald-500 rounded-l-xl"
                            ></div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div :class="[
                                        'w-10 h-10 rounded-lg flex items-center justify-center transition-colors duration-200',
                                        alert.enabled
                                            ? 'bg-emerald-500 text-white'
                                            : getAlertLevelColor(alert.level)
                                    ]">
                                        <Shield class="h-5 w-5" />
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-foreground flex items-center gap-2">
                                            {{ alert.label }}
                                            <span v-if="alert.enabled" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-800 dark:text-emerald-100">
                                                Active
                                            </span>
                                        </h4>
                                        <p class="text-sm text-muted-foreground">
                                            {{ alert.level.charAt(0).toUpperCase() + alert.level.slice(1) }} priority
                                        </p>
                                    </div>
                                </div>

                                <!-- Toggle Switch -->
                                <div class="flex items-center gap-2">
                                    <span :class="[
                                        'text-sm font-medium',
                                        alert.enabled ? 'text-emerald-600 dark:text-emerald-400' : 'text-muted-foreground'
                                    ]">
                                        {{ alert.enabled ? 'On' : 'Off' }}
                                    </span>
                                    <button
                                        :class="[
                                            'relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 focus:outline-none',
                                            alert.enabled
                                                ? 'bg-emerald-500'
                                                : 'bg-gray-300 dark:bg-gray-600'
                                        ]"
                                        role="switch"
                                        :aria-checked="alert.enabled"
                                    >
                                        <span
                                            :class="[
                                                'inline-block h-4 w-4 transform rounded-full bg-white transition-transform duration-200',
                                                alert.enabled ? 'translate-x-6' : 'translate-x-1'
                                            ]"
                                        />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Card>

            <!-- Uptime Alerts -->
            <Card>
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-9 h-9 bg-blue-50 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                            <Zap class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-foreground">
                                Uptime Monitoring Alerts
                            </h3>
                            <p class="text-sm text-muted-foreground">
                                Get notified when websites go down or recover
                            </p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div
                            v-for="alert in uptimeAlerts"
                            :key="`uptime-${alert.alert_type}-${alert.id}`"
                            :class="[
                                'group relative p-4 rounded-lg border transition-colors duration-200 cursor-pointer',
                                alert.enabled
                                    ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800'
                                    : 'bg-card border-border hover:bg-accent/50'
                            ]"
                            @click="toggleGlobalAlert(alert.alert_type, alert.threshold_days, alert.enabled, alert.id)"
                        >
                            <!-- Enabled indicator line -->
                            <div
                                v-if="alert.enabled"
                                class="absolute left-0 top-0 bottom-0 w-1 bg-blue-500 rounded-l-xl"
                            ></div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div :class="[
                                        'w-10 h-10 rounded-lg flex items-center justify-center transition-colors duration-200',
                                        alert.enabled
                                            ? 'bg-blue-500 text-white'
                                            : getAlertLevelColor(alert.alert_level)
                                    ]">
                                        <Zap class="h-5 w-5" />
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-foreground flex items-center gap-2">
                                            {{ alert.alert_type_label }}
                                            <span v-if="alert.enabled" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                                Active
                                            </span>
                                        </h4>
                                        <p class="text-sm text-muted-foreground">
                                            {{ alert.alert_level.charAt(0).toUpperCase() + alert.alert_level.slice(1) }} priority
                                        </p>
                                    </div>
                                </div>

                                <!-- Toggle Switch -->
                                <div class="flex items-center gap-2">
                                    <span :class="[
                                        'text-sm font-medium',
                                        alert.enabled ? 'text-blue-600 dark:text-blue-400' : 'text-muted-foreground'
                                    ]">
                                        {{ alert.enabled ? 'On' : 'Off' }}
                                    </span>
                                    <button
                                        :class="[
                                            'relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 focus:outline-none',
                                            alert.enabled
                                                ? 'bg-blue-500'
                                                : 'bg-gray-300 dark:bg-gray-600'
                                        ]"
                                        role="switch"
                                        :aria-checked="alert.enabled"
                                    >
                                        <span
                                            :class="[
                                                'inline-block h-4 w-4 transform rounded-full bg-white transition-transform duration-200',
                                                alert.enabled ? 'translate-x-6' : 'translate-x-1'
                                            ]"
                                        />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Card>

            <!-- Response Time Alerts -->
            <Card v-if="responseTimeAlerts.length > 0">
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-9 h-9 bg-orange-50 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                            <Clock class="h-4 w-4 text-orange-600 dark:text-orange-400" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-foreground">
                                Response Time Alerts
                            </h3>
                            <p class="text-sm text-muted-foreground">
                                Get notified when response times exceed thresholds
                            </p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div
                            v-for="alert in responseTimeAlerts"
                            :key="`response-time-${alert.threshold_response_time}-${alert.id}`"
                            :class="[
                                'group relative p-4 rounded-lg border transition-colors duration-200 cursor-pointer',
                                alert.enabled
                                    ? 'bg-orange-50 dark:bg-orange-900/20 border-orange-200 dark:border-orange-800'
                                    : 'bg-card border-border hover:bg-accent/50'
                            ]"
                            @click="toggleGlobalAlert(alert.alert_type, alert.threshold_days, alert.enabled, alert.id)"
                        >
                            <!-- Enabled indicator line -->
                            <div
                                v-if="alert.enabled"
                                class="absolute left-0 top-0 bottom-0 w-1 bg-orange-500 rounded-l-xl"
                            ></div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div :class="[
                                        'w-10 h-10 rounded-lg flex items-center justify-center transition-colors duration-200',
                                        alert.enabled
                                            ? 'bg-orange-500 text-white'
                                            : getAlertLevelColor(alert.alert_level)
                                    ]">
                                        <Clock class="h-5 w-5" />
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-foreground flex items-center gap-2">
                                            {{ alert.alert_type_label }}
                                            <span v-if="alert.enabled" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-800 dark:text-orange-100">
                                                Active
                                            </span>
                                        </h4>
                                        <p class="text-sm text-muted-foreground">
                                            Threshold: {{ alert.threshold_response_time }}ms
                                        </p>
                                    </div>
                                </div>

                                <!-- Toggle Switch -->
                                <div class="flex items-center gap-2">
                                    <span :class="[
                                        'text-sm font-medium',
                                        alert.enabled ? 'text-orange-600 dark:text-orange-400' : 'text-muted-foreground'
                                    ]">
                                        {{ alert.enabled ? 'On' : 'Off' }}
                                    </span>
                                    <button
                                        :class="[
                                            'relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 focus:outline-none',
                                            alert.enabled
                                                ? 'bg-orange-500'
                                                : 'bg-gray-300 dark:bg-gray-600'
                                        ]"
                                        role="switch"
                                        :aria-checked="alert.enabled"
                                    >
                                        <span
                                            :class="[
                                                'inline-block h-4 w-4 transform rounded-full bg-white transition-transform duration-200',
                                                alert.enabled ? 'translate-x-6' : 'translate-x-1'
                                            ]"
                                        />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Card>

            <!-- Next Steps Card -->
            <Card class="bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700">
                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <!-- Simple icon -->
                        <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 012 2v10a2 2 0 01-2 2H9a2 2 0 01-2-2V5z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 13h8M3 17h8" />
                            </svg>
                        </div>

                        <!-- Content -->
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-foreground mb-2">
                                Ready to Configure Specific Websites?
                            </h3>
                            <p class="text-muted-foreground mb-4">
                                Override these global templates for individual websites with custom alert settings. Each website can have its own unique notification preferences while inheriting the defaults you've configured here.
                            </p>

                            <!-- Simple CTA Button -->
                            <Button
                                @click="router.visit('/ssl/websites')"
                                variant="default"
                                size="lg"
                                class="inline-flex items-center gap-2"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                                Configure Website Alerts
                            </Button>
                        </div>
                    </div>
                </div>
            </Card>
        </div>
    </ModernSettingsLayout>
</template>