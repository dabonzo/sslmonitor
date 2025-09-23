<script setup lang="ts">
import { Head, usePage, Form } from '@inertiajs/vue3';
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
import { Switch } from '@/components/ui/switch';
import HeadingSmall from '@/components/HeadingSmall.vue';
import ModernSettingsLayout from '@/layouts/ModernSettingsLayout.vue';
import { Plus, Settings } from 'lucide-vue-next';

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
            <div class="space-y-6">
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
                                        <p v-if="errors.alert_level" class="text-sm text-red-600">{{ errors.alert_level }}</p>
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
                                        <p v-if="errors.threshold_days" class="text-sm text-red-600">{{ errors.threshold_days }}</p>
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

                    <!-- Default Configurations Display -->
                    <div v-if="defaultConfigurations.length" class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Default Alert Types</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div
                                v-for="config in defaultConfigurations"
                                :key="config.alert_type"
                                class="border border-gray-200 dark:border-gray-700 rounded-lg p-4"
                            >
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="font-medium text-gray-900 dark:text-white">
                                        {{ alertTypes[config.alert_type] || config.alert_type }}
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
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Your Alert Configurations</h3>
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
                                            <span v-if="alert.threshold_days">{{ alert.threshold_days }} days</span>
                                            <span v-if="alert.threshold_response_time"> • {{ alert.threshold_response_time }}ms</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <Badge :class="getAlertLevelColor(alert.alert_level)">
                                        {{ alert.alert_level }}
                                    </Badge>
                                    <Button variant="outline" size="sm">
                                        Configure
                                    </Button>
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