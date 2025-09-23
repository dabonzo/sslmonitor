<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import ModernSettingsLayout from '@/layouts/ModernSettingsLayout.vue';
import { Bell, AlertTriangle, Settings, BarChart3, Plus, Clock, Zap } from 'lucide-vue-next';

interface AlertConfiguration {
    id: number;
    alert_type: string;
    alert_type_label: string;
    enabled: boolean;
    alert_level: string;
    alert_level_color: string;
    threshold_days: number;
    threshold_response_time: number;
    notification_channels: string[];
    custom_message: string;
    last_triggered_at: string | null;
}

interface Website {
    id: number;
    name: string;
    url: string;
}

interface Props {
    alertConfigurations: AlertConfiguration[];
    alertsByWebsite: Array<{
        website: Website;
        alerts: AlertConfiguration[];
    }>;
    websites: Website[];
    defaultConfigurations: Array<{
        alert_type: string;
        enabled: boolean;
        threshold_days: number;
        alert_level: string;
        notification_channels: string[];
    }>;
    alertTypes: Record<string, string>;
    notificationChannels: Record<string, string>;
    alertLevels: Record<string, string>;
}

const props = defineProps<Props>();

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
</script>

<template>
    <Head title="Alert Settings" />

    <ModernSettingsLayout title="Alert Settings">
        <div class="space-y-8">
            <!-- Alert Configuration Section -->
            <div class="rounded-xl bg-gradient-to-r from-gray-50 to-slate-50 dark:from-gray-900 dark:to-slate-900 p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="rounded-lg bg-gray-100 dark:bg-gray-800 p-2">
                        <Bell class="h-5 w-5 text-gray-600 dark:text-gray-400" />
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Alert Configuration</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Manage your SSL certificate and website monitoring alerts</p>
                    </div>
                </div>

                <!-- Alert Statistics -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4 hover:shadow-md transition-all duration-200">
                        <div class="flex items-center space-x-3">
                            <div class="rounded-lg bg-gray-100 dark:bg-gray-700 p-2">
                                <BarChart3 class="h-5 w-5 text-gray-600 dark:text-gray-400" />
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ alertConfigurations.length }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">Total Alerts</div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4 hover:shadow-md transition-all duration-200">
                        <div class="flex items-center space-x-3">
                            <div class="rounded-lg bg-green-100 dark:bg-green-900/30 p-2">
                                <Zap class="h-5 w-5 text-green-600 dark:text-green-400" />
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-green-700 dark:text-green-400">
                                    {{ alertConfigurations.filter(a => a.enabled).length }}
                                </div>
                                <div class="text-sm text-green-600 dark:text-green-400">Active Alerts</div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4 hover:shadow-md transition-all duration-200">
                        <div class="flex items-center space-x-3">
                            <div class="rounded-lg bg-blue-100 dark:bg-blue-900/30 p-2">
                                <Bell class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-blue-700 dark:text-blue-400">
                                    {{ websites.length }}
                                </div>
                                <div class="text-sm text-blue-600 dark:text-blue-400">Monitored Websites</div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4 hover:shadow-md transition-all duration-200">
                        <div class="flex items-center space-x-3">
                            <div class="rounded-lg bg-orange-100 dark:bg-orange-900/30 p-2">
                                <AlertTriangle class="h-5 w-5 text-orange-600 dark:text-orange-400" />
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-orange-700 dark:text-orange-400">
                                    {{ Object.keys(alertTypes).length }}
                                </div>
                                <div class="text-sm text-orange-600 dark:text-orange-400">Alert Types</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Default Configurations Display -->
                <div v-if="defaultConfigurations.length" class="mb-6">
                    <div class="flex items-center space-x-3 mb-4">
                        <Settings class="h-5 w-5 text-gray-600 dark:text-gray-400" />
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Default Alert Types</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div
                            v-for="config in defaultConfigurations"
                            :key="config.alert_type"
                            class="rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4 hover:shadow-md transition-all duration-200"
                        >
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="font-semibold text-gray-900 dark:text-white">
                                    {{ alertTypes[config.alert_type] || config.alert_type }}
                                </h4>
                                <Badge :class="getAlertLevelColor(config.alert_level)">
                                    {{ config.alert_level }}
                                </Badge>
                            </div>
                            <div class="flex items-center space-x-2 mb-3">
                                <Clock class="h-4 w-4 text-gray-500" />
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    <span v-if="config.threshold_days">{{ config.threshold_days }} days threshold</span>
                                    <span v-else>Immediate</span>
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-1">
                                <Badge
                                    v-for="channel in config.notification_channels"
                                    :key="channel"
                                    variant="outline"
                                    class="text-xs"
                                >
                                    {{ notificationChannels[channel] || channel }}
                                </Badge>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Current Alert Configurations -->
                <div v-if="alertConfigurations.length" class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <AlertTriangle class="h-5 w-5 text-gray-600 dark:text-gray-400" />
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Your Alert Configurations</h3>
                        </div>
                        <Button class="h-10 px-4">
                            <Plus class="h-4 w-4 mr-2" />
                            Add Alert
                        </Button>
                    </div>
                    <div class="space-y-3">
                        <div
                            v-for="alert in alertConfigurations"
                            :key="alert.id"
                            class="flex items-center justify-between p-4 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:shadow-md transition-all duration-200"
                        >
                            <div class="flex items-center space-x-4">
                                <Checkbox :checked="alert.enabled" disabled />
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-white">
                                        {{ alert.alert_type_label }}
                                    </div>
                                    <div class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                                        <Clock class="h-3 w-3" />
                                        <span v-if="alert.threshold_days">{{ alert.threshold_days }} days</span>
                                        <span v-if="alert.threshold_response_time"> • {{ alert.threshold_response_time }}ms</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3">
                                <Badge :class="getAlertLevelColor(alert.alert_level)">
                                    {{ alert.alert_level }}
                                </Badge>
                                <Button variant="outline" class="h-9 px-3">
                                    <Settings class="h-4 w-4 mr-2" />
                                    Configure
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-12">
                    <div class="rounded-full bg-gray-100 dark:bg-gray-700 p-4 w-16 h-16 mx-auto mb-4">
                        <Bell class="h-8 w-8 text-gray-600 dark:text-gray-400" />
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">No Alert Configurations</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        Start monitoring your websites by adding some alert configurations.
                    </p>
                    <Button class="h-11 px-6">
                        <Plus class="h-4 w-4 mr-2" />
                        Add First Alert
                    </Button>
                </div>
            </div>
        </div>
    </ModernSettingsLayout>
</template>