<script setup lang="ts">
import { Head, usePage, Form, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Separator } from '@/components/ui/separator';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import HeadingSmall from '@/components/HeadingSmall.vue';
import ModernSettingsLayout from '@/layouts/ModernSettingsLayout.vue';
import AdvancedAlertRuleBuilder from '@/components/alerts/AdvancedAlertRuleBuilder.vue';
import AlertDashboard from '@/components/alerts/AlertDashboard.vue';
import { Plus, Settings, Trash2 } from 'lucide-vue-next';

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

// Component state
const showAddAlertDialog = ref(false);
const showConfigureDialog = ref(false);
const configuringAlert = ref<AlertConfiguration | null>(null);

// Form data for editing
const editForm = ref({
    enabled: true,
    alert_level: '',
    threshold_days: null,
    threshold_response_time: null,
    notification_channels: [] as string[],
    custom_message: ''
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

// Group SSL expiry alerts by level for simple checkbox interface
const sslExpiryAlerts = computed(() => {
    const levels = ['info', 'warning', 'urgent', 'critical'];
    const thresholds = { info: 30, warning: 14, urgent: 7, critical: 3 };

    return levels.map(level => {
        const alert = props.alertConfigurations.find(
            a => a.alert_type === 'ssl_expiry' && a.alert_level === level
        );
        return {
            level,
            days: thresholds[level],
            alert,
            enabled: alert?.enabled || false
        };
    });
});

// Toggle SSL expiry alert level
const toggleSslExpiryLevel = (alertId: number, currentEnabled: boolean) => {
    router.put(`/settings/alerts/${alertId}`, {
        enabled: !currentEnabled
    }, {
        preserveScroll: true
    });
};

// Configure alert functionality
const openConfigureDialog = (alert: AlertConfiguration) => {
    console.log('Opening configure dialog for alert:', alert);

    configuringAlert.value = alert;

    // Populate form with existing data
    editForm.value = {
        enabled: alert.enabled,
        alert_level: alert.alert_level,
        threshold_days: alert.threshold_days,
        threshold_response_time: alert.threshold_response_time,
        notification_channels: alert.notification_channels ? [...alert.notification_channels] : [],
        custom_message: alert.custom_message || ''
    };

    console.log('Form populated with data:', editForm.value);

    showConfigureDialog.value = true;
};

const closeConfigureDialog = () => {
    showConfigureDialog.value = false;
    configuringAlert.value = null;
};

const deleteAlert = (alertId: number) => {
    console.log('Delete button clicked for alert ID:', alertId);

    if (confirm('Are you sure you want to delete this alert configuration?')) {
        console.log('User confirmed deletion, sending request...');

        router.delete(`/settings/alerts/${alertId}`, {
            onSuccess: () => {
                console.log('Delete successful');
                // Page will automatically refresh via Inertia
            },
            onError: (errors) => {
                console.error('Delete failed:', errors);
                alert('Failed to delete alert configuration: ' + JSON.stringify(errors));
            }
        });
    } else {
        console.log('User cancelled deletion');
    }
};

const submitConfigureForm = () => {
    if (!configuringAlert.value) return;

    console.log('Submitting form with data:', editForm.value);
    console.log('Alert ID:', configuringAlert.value.id);

    router.put(`/settings/alerts/${configuringAlert.value.id}`, editForm.value, {
        onSuccess: () => {
            console.log('Form submission successful');
            showConfigureDialog.value = false;
            configuringAlert.value = null;
        },
        onError: (errors) => {
            console.error('Update failed:', errors);
            alert('Failed to update alert configuration: ' + JSON.stringify(errors));
        }
    });
};

// Advanced Alert System Event Handlers
const handleAlertAcknowledged = (alert) => {
    console.log('Alert acknowledged:', alert);
    // In production, this would send an API request to acknowledge the alert
};

const handleAlertDismissed = (alert) => {
    console.log('Alert dismissed:', alert);
    // In production, this would send an API request to dismiss the alert
};

const handleCreateRuleFromAlert = (alert) => {
    console.log('Creating rule from alert:', alert);
    // This would pre-populate the rule builder with data from the alert
};

const handleRuleCreated = (rule) => {
    console.log('New alert rule created:', rule);
    // In production, this would send an API request to save the rule
};

const handleRuleUpdated = (rule) => {
    console.log('Alert rule updated:', rule);
    // In production, this would send an API request to update the rule
};

const handleRuleDeleted = (ruleId) => {
    console.log('Alert rule deleted:', ruleId);
    // In production, this would send an API request to delete the rule
};
</script>

<template>
    <Head title="Alert Settings" />

    <ModernSettingsLayout title="Alert Settings">
            <div class="space-y-6">
                <!-- SSL Certificate Alert Levels - Simple Checkboxes -->
                <Card class="p-6">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-foreground mb-2">SSL Certificate Expiry Alerts</h3>
                        <p class="text-sm text-muted-foreground">
                            Choose which alert levels you want to receive. Alerts are sent when certificates are approaching expiration.
                        </p>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="item in sslExpiryAlerts"
                            :key="item.level"
                            class="flex items-center justify-between p-4 rounded-lg border border-border hover:bg-muted/50 transition-colors"
                        >
                            <div class="flex items-center space-x-4">
                                <Checkbox
                                    :checked="item.enabled"
                                    @update:checked="() => item.alert && toggleSslExpiryLevel(item.alert.id, item.enabled)"
                                    :disabled="!item.alert"
                                />
                                <div>
                                    <div class="flex items-center gap-2">
                                        <Badge :class="getAlertLevelColor(item.level)">
                                            {{ item.level.toUpperCase() }}
                                        </Badge>
                                        <span class="font-medium text-foreground">{{ item.days }} days before expiry</span>
                                    </div>
                                    <p class="text-sm text-muted-foreground mt-1">
                                        {{ item.level === 'info' ? 'Early warning - certificate will expire in a month' :
                                           item.level === 'warning' ? 'Moderate urgency - certificate expires in 2 weeks' :
                                           item.level === 'urgent' ? 'High priority - certificate expires in 1 week' :
                                           'Critical - certificate expires in 3 days' }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-sm text-muted-foreground">
                                {{ item.enabled ? 'Enabled' : 'Disabled' }}
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                        <p class="text-sm text-blue-800 dark:text-blue-300">
                            <strong>Tip:</strong> Most users enable only <strong>Urgent (7 days)</strong> and <strong>Critical (3 days)</strong> alerts to avoid notification fatigue while staying informed of important certificate expirations.
                        </p>
                    </div>
                </Card>

                <!-- Real-time Alert Dashboard -->
                <AlertDashboard
                    @alert-acknowledged="handleAlertAcknowledged"
                    @alert-dismissed="handleAlertDismissed"
                    @create-rule="handleCreateRuleFromAlert"
                />

                <!-- Advanced Alert Rule Builder -->
                <AdvancedAlertRuleBuilder
                    :available-websites="websites"
                    @rule-created="handleRuleCreated"
                    @rule-updated="handleRuleUpdated"
                    @rule-deleted="handleRuleDeleted"
                />

                <!-- Alert Configurations Overview -->
                <Card class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <HeadingSmall title="Alert Configurations" />
                            <p class="text-gray-600 dark:text-gray-400">
                                Manage your SSL certificate and website monitoring alerts.
                            </p>
                        </div>
                        <Dialog v-model:open="showAddAlertDialog">
                            <DialogTrigger as-child>
                                <Button class="h-11 px-6">
                                    <Plus class="h-4 w-4 mr-2" />
                                    Add Alert
                                </Button>
                            </DialogTrigger>
                            <DialogContent class="sm:max-w-md">
                                <DialogHeader>
                                    <DialogTitle>Create New Alert</DialogTitle>
                                </DialogHeader>
                                <Form action="/settings/alerts" method="post" class="space-y-4" #default="{ errors, processing }">
                                    <div class="space-y-2">
                                        <Label for="alert_type">Alert Type</Label>
                                        <select
                                            id="alert_type"
                                            name="alert_type"
                                            required
                                            class="w-full px-3 py-2 border border-input bg-background rounded-md text-sm"
                                        >
                                            <option value="">Select alert type...</option>
                                            <option v-for="(label, type) in alertTypes" :key="type" :value="type">
                                                {{ label }}
                                            </option>
                                        </select>
                                        <p v-if="errors.alert_type" class="text-sm text-red-600">{{ errors.alert_type }}</p>
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="alert_level">Alert Level</Label>
                                        <select
                                            id="alert_level"
                                            name="alert_level"
                                            required
                                            class="w-full px-3 py-2 border border-input bg-background rounded-md text-sm"
                                        >
                                            <option value="">Select alert level...</option>
                                            <option v-for="(label, level) in alertLevels" :key="level" :value="level">
                                                {{ label }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="threshold_days">Threshold Days (optional)</Label>
                                        <Input
                                            id="threshold_days"
                                            name="threshold_days"
                                            type="number"
                                            placeholder="e.g., 7 days before expiry"
                                            class="w-full"
                                        />
                                    </div>
                                    <div class="flex justify-end gap-2">
                                        <Button type="button" variant="outline" @click="showAddAlertDialog = false">
                                            Cancel
                                        </Button>
                                        <Button type="submit" :disabled="processing">
                                            {{ processing ? 'Creating...' : 'Create Alert' }}
                                        </Button>
                                    </div>
                                </Form>
                            </DialogContent>
                        </Dialog>

                        <!-- Configure Alert Dialog -->
                        <Dialog v-model:open="showConfigureDialog">
                            <DialogContent class="sm:max-w-lg">
                                <DialogHeader>
                                    <DialogTitle>Configure Alert: {{ configuringAlert?.alert_type_label }}</DialogTitle>
                                </DialogHeader>
                                <form
                                    v-if="configuringAlert"
                                    @submit.prevent="submitConfigureForm"
                                    class="space-y-4"
                                >
                                    <div class="space-y-2">
                                        <Label class="flex items-center space-x-2">
                                            <Checkbox
                                                v-model:checked="editForm.enabled"
                                                name="enabled"
                                                :value="1"
                                            />
                                            <span>Enable this alert</span>
                                        </Label>
                                        <input type="hidden" name="enabled" :value="editForm.enabled ? 1 : 0" />
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="alert_level">Alert Level</Label>
                                        <select
                                            id="alert_level"
                                            name="alert_level"
                                            v-model="editForm.alert_level"
                                            required
                                            class="w-full px-3 py-2 border border-input bg-background rounded-md text-sm"
                                        >
                                            <option v-for="(label, level) in alertLevels" :key="level" :value="level">
                                                {{ label }}
                                            </option>
                                        </select>
                                    </div>

                                    <div v-if="configuringAlert.alert_type !== 'uptime_down' && configuringAlert.alert_type !== 'ssl_invalid'" class="space-y-2">
                                        <Label for="threshold_days">Threshold Days</Label>
                                        <Input
                                            id="threshold_days"
                                            name="threshold_days"
                                            type="number"
                                            v-model="editForm.threshold_days"
                                            placeholder="e.g., 7 days before expiry"
                                            class="w-full"
                                        />
                                        <p class="text-xs text-gray-500">Days before expiry to trigger alert</p>
                                    </div>

                                    <div v-if="configuringAlert.alert_type === 'response_time'" class="space-y-2">
                                        <Label for="threshold_response_time">Response Time Threshold (ms)</Label>
                                        <Input
                                            id="threshold_response_time"
                                            name="threshold_response_time"
                                            type="number"
                                            v-model="editForm.threshold_response_time"
                                            placeholder="e.g., 5000 (5 seconds)"
                                            class="w-full"
                                        />
                                        <p class="text-xs text-gray-500">Trigger alert when response time exceeds this value</p>
                                    </div>

                                    <div class="space-y-2">
                                        <Label>Notification Channels</Label>
                                        <div class="space-y-2">
                                            <Label v-for="(label, channel) in notificationChannels" :key="channel" class="flex items-center space-x-2">
                                                <Checkbox
                                                    :checked="editForm.notification_channels.includes(channel)"
                                                    @change="(checked) => {
                                                        if (checked) {
                                                            if (!editForm.notification_channels.includes(channel)) {
                                                                editForm.notification_channels.push(channel);
                                                            }
                                                        } else {
                                                            const index = editForm.notification_channels.indexOf(channel);
                                                            if (index > -1) {
                                                                editForm.notification_channels.splice(index, 1);
                                                            }
                                                        }
                                                    }"
                                                    :name="`notification_channels[]`"
                                                    :value="channel"
                                                />
                                                <span>{{ label }}</span>
                                            </Label>
                                        </div>
                                        <!-- Hidden inputs for notification channels -->
                                        <input
                                            v-for="channel in editForm.notification_channels"
                                            :key="channel"
                                            type="hidden"
                                            name="notification_channels[]"
                                            :value="channel"
                                        />
                                    </div>

                                    <div class="space-y-2">
                                        <Label for="custom_message">Custom Message (optional)</Label>
                                        <Input
                                            id="custom_message"
                                            name="custom_message"
                                            v-model="editForm.custom_message"
                                            placeholder="Custom alert message..."
                                            class="w-full"
                                        />
                                        <p class="text-xs text-gray-500">Use {website} and {days} as placeholders</p>
                                    </div>

                                    <div class="flex justify-end gap-2">
                                        <Button type="button" variant="outline" @click="closeConfigureDialog">
                                            Cancel
                                        </Button>
                                        <Button type="submit">
                                            Save Changes
                                        </Button>
                                    </div>
                                </form>
                            </DialogContent>
                        </Dialog>
                    </div>

                    <!-- Alert Statistics -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ alertConfigurations.length }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Total Alerts</div>
                        </div>

                        <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-green-700 dark:text-green-400">
                                {{ alertConfigurations.filter(a => a.enabled).length }}
                            </div>
                            <div class="text-sm text-green-600 dark:text-green-400">Active Alerts</div>
                        </div>

                        <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-blue-700 dark:text-blue-400">
                                {{ websites.length }}
                            </div>
                            <div class="text-sm text-blue-600 dark:text-blue-400">Monitored Websites</div>
                        </div>

                        <div class="bg-orange-50 dark:bg-orange-900/20 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-orange-700 dark:text-orange-400">
                                {{ Object.keys(alertTypes).length }}
                            </div>
                            <div class="text-sm text-orange-600 dark:text-orange-400">Alert Types</div>
                        </div>
                    </div>

                    <!-- Help Section -->
                    <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                        <h3 class="text-sm font-medium text-blue-900 dark:text-blue-200 mb-2">How Alerts Work</h3>
                        <div class="text-sm text-blue-800 dark:text-blue-300 space-y-1">
                            <p><strong>Default Alerts:</strong> Pre-configured alert templates that come with SSL Monitor</p>
                            <p><strong>Custom Alerts:</strong> Your personalized configurations that override the defaults</p>
                            <p><strong>Monitoring:</strong> Once created, alerts automatically monitor ALL your websites</p>
                            <p><strong>Cooldown:</strong> Alerts have a 24-hour cooldown period to prevent spam</p>
                        </div>
                    </div>

                    <!-- Default Configurations Display -->
                    <div v-if="defaultConfigurations.length" class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Available Default Alert Types</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">These are preset alert configurations you can enable. Creating a custom alert will override the default.</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div
                                v-for="config in defaultConfigurations"
                                :key="config.alert_type"
                                class="border border-gray-200 dark:border-gray-700 rounded-lg p-4"
                            >
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="font-medium text-gray-900 dark:text-white">
                                        {{ config.alert_type_label || alertTypes[config.alert_type] || config.alert_type }}
                                    </h4>
                                    <Badge :class="getAlertLevelColor(config.alert_level)">
                                        {{ config.alert_level }}
                                    </Badge>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    <span v-if="config.threshold_days">{{ config.threshold_days }} days threshold</span>
                                    <span v-else>Immediate</span>
                                </p>
                                <div class="flex flex-wrap gap-1 mt-2">
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
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Your Custom Alert Configurations</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">These are your personalized alerts that override the defaults and monitor all your websites.</p>
                        </div>
                        <div class="space-y-2">
                            <div
                                v-for="alert in alertConfigurations"
                                :key="alert.id"
                                class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg"
                            >
                                <div class="flex items-center space-x-4">
                                    <Checkbox :checked="alert.enabled" disabled />
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            {{ alert.alert_type_label }}
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            <span v-if="alert.threshold_days">{{ alert.threshold_days }} days threshold</span>
                                            <span v-if="alert.threshold_response_time"> • {{ alert.threshold_response_time }}ms max response</span>
                                            <span v-if="alert.notification_channels?.length"> • {{ alert.notification_channels.length }} channel{{ alert.notification_channels.length !== 1 ? 's' : '' }}</span>
                                            <span v-if="alert.last_triggered_at" class="text-orange-600 dark:text-orange-400"> • Last: {{ new Date(alert.last_triggered_at).toLocaleDateString() }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <Badge :class="getAlertLevelColor(alert.alert_level)">
                                        {{ alert.alert_level }}
                                    </Badge>
                                    <div class="flex gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            @click="openConfigureDialog(alert)"
                                        >
                                            <Settings class="h-4 w-4 mr-1" />
                                            Configure
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            class="text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20"
                                            @click="deleteAlert(alert.id)"
                                        >
                                            Delete
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="text-center py-12">
                        <div class="text-gray-400 text-6xl mb-4">🔔</div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No Alert Configurations</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            Start monitoring your websites by adding some alert configurations.
                        </p>
                        <Button @click="showAddAlertDialog = true">
                            <Plus class="h-4 w-4 mr-2" />
                            Add First Alert
                        </Button>
                    </div>
                </Card>
            </div>
    </ModernSettingsLayout>
</template>