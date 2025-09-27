<template>
  <Card class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 border border-purple-200 dark:border-purple-800">
    <CardHeader>
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <div class="rounded-lg bg-purple-100 dark:bg-purple-900/30 p-2">
            <Settings class="h-5 w-5 text-purple-600 dark:text-purple-400" />
          </div>
          <div>
            <CardTitle class="text-xl font-bold text-gray-900 dark:text-gray-100">
              Advanced Alert Rule Builder
            </CardTitle>
            <CardDescription>
              Create sophisticated alert rules with multiple conditions and custom logic
            </CardDescription>
          </div>
        </div>
        <Button @click="showRuleBuilder = !showRuleBuilder" variant="outline" size="sm">
          <Plus class="h-4 w-4 mr-2" />
          {{ showRuleBuilder ? 'Cancel' : 'Create Rule' }}
        </Button>
      </div>
    </CardHeader>

    <CardContent v-if="showRuleBuilder" class="space-y-6">
      <!-- Rule Name and Basic Settings -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-2">
          <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Rule Name</label>
          <input
            v-model="currentRule.name"
            type="text"
            placeholder="e.g., Critical Certificate Expiry"
            class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
          />
        </div>
        <div class="space-y-2">
          <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Alert Priority</label>
          <select
            v-model="currentRule.priority"
            class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
          >
            <option value="critical">🔴 Critical - Immediate action required</option>
            <option value="high">🟠 High - Action required within hours</option>
            <option value="medium">🟡 Medium - Action required within days</option>
            <option value="low">🔵 Low - Information only</option>
          </select>
        </div>
      </div>

      <!-- Rule Logic Builder -->
      <div class="space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Rule Conditions</h3>
          <Badge variant="outline">{{ currentRule.logic_operator.toUpperCase() }} Logic</Badge>
        </div>

        <!-- Logic Operator Selection -->
        <div class="flex space-x-2">
          <Button
            @click="currentRule.logic_operator = 'and'"
            :variant="currentRule.logic_operator === 'and' ? 'default' : 'outline'"
            size="sm"
          >
            AND - All conditions must be true
          </Button>
          <Button
            @click="currentRule.logic_operator = 'or'"
            :variant="currentRule.logic_operator === 'or' ? 'default' : 'outline'"
            size="sm"
          >
            OR - Any condition can be true
          </Button>
        </div>

        <!-- Conditions List -->
        <div class="space-y-3">
          <div
            v-for="(condition, index) in currentRule.conditions"
            :key="index"
            class="p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700"
          >
            <div class="flex items-center justify-between mb-3">
              <span class="font-medium text-sm text-gray-600 dark:text-gray-400">
                Condition {{ index + 1 }}
              </span>
              <Button
                @click="removeCondition(index)"
                variant="ghost"
                size="sm"
                class="text-red-600 hover:text-red-700"
              >
                <X class="h-4 w-4" />
              </Button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
              <!-- Field Selection -->
              <select
                v-model="condition.field"
                class="px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
              >
                <optgroup label="SSL Certificate">
                  <option value="ssl_status">SSL Status</option>
                  <option value="ssl_days_remaining">Days Until Expiry</option>
                  <option value="ssl_issuer">Certificate Authority</option>
                  <option value="ssl_key_size">Key Size</option>
                  <option value="ssl_security_score">Security Score</option>
                </optgroup>
                <optgroup label="Uptime Monitoring">
                  <option value="uptime_status">Uptime Status</option>
                  <option value="response_time">Response Time (ms)</option>
                  <option value="uptime_percentage">Uptime Percentage</option>
                  <option value="consecutive_failures">Consecutive Failures</option>
                </optgroup>
                <optgroup label="Website Properties">
                  <option value="website_name">Website Name</option>
                  <option value="website_url">Website URL</option>
                  <option value="monitoring_enabled">Monitoring Status</option>
                  <option value="team_assignment">Team Assignment</option>
                </optgroup>
              </select>

              <!-- Operator Selection -->
              <select
                v-model="condition.operator"
                class="px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
              >
                <option value="equals">Equals</option>
                <option value="not_equals">Not Equals</option>
                <option value="greater_than">Greater Than</option>
                <option value="less_than">Less Than</option>
                <option value="greater_equal">Greater or Equal</option>
                <option value="less_equal">Less or Equal</option>
                <option value="contains">Contains</option>
                <option value="not_contains">Does Not Contain</option>
                <option value="starts_with">Starts With</option>
                <option value="ends_with">Ends With</option>
              </select>

              <!-- Value Input -->
              <input
                v-model="condition.value"
                type="text"
                :placeholder="getPlaceholderForField(condition.field)"
                class="px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
              />
            </div>

            <!-- Condition Preview -->
            <div class="mt-3 p-2 bg-gray-50 dark:bg-gray-700 rounded text-xs font-mono text-gray-600 dark:text-gray-400">
              {{ getConditionPreview(condition) }}
            </div>
          </div>

          <!-- Add Condition Button -->
          <Button @click="addCondition" variant="outline" size="sm" class="w-full">
            <Plus class="h-4 w-4 mr-2" />
            Add Condition
          </Button>
        </div>

        <!-- Rule Preview -->
        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
          <h4 class="font-medium text-blue-900 dark:text-blue-100 mb-2">Rule Preview</h4>
          <div class="text-sm text-blue-800 dark:text-blue-200 font-mono">
            <span class="font-semibold">{{ currentRule.name || 'Unnamed Rule' }}</span> will trigger when:
            <br />
            {{ getRulePreview() }}
          </div>
        </div>
      </div>

      <!-- Notification Settings -->
      <div class="space-y-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Notification Settings</h3>

        <!-- Notification Channels -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div
            v-for="channel in notificationChannels"
            :key="channel.id"
            class="p-4 border border-border rounded-lg cursor-pointer transition-colors"
            :class="{
              'bg-primary/10 border-primary': currentRule.notification_channels.includes(channel.id),
              'hover:bg-gray-50 dark:hover:bg-gray-800': !currentRule.notification_channels.includes(channel.id)
            }"
            @click="toggleNotificationChannel(channel.id)"
          >
            <div class="flex items-center space-x-3">
              <component :is="channel.icon" class="h-5 w-5" />
              <div>
                <div class="font-medium">{{ channel.name }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">{{ channel.description }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Advanced Notification Options -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Cooldown Period</label>
            <select
              v-model="currentRule.cooldown_minutes"
              class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
            >
              <option value="0">No cooldown</option>
              <option value="15">15 minutes</option>
              <option value="30">30 minutes</option>
              <option value="60">1 hour</option>
              <option value="360">6 hours</option>
              <option value="720">12 hours</option>
              <option value="1440">24 hours</option>
            </select>
          </div>

          <div class="space-y-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Max Alerts per Day</label>
            <input
              v-model="currentRule.max_alerts_per_day"
              type="number"
              min="1"
              max="100"
              placeholder="10"
              class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
            />
          </div>
        </div>

        <!-- Custom Message Template -->
        <div class="space-y-2">
          <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Custom Alert Message</label>
          <textarea
            v-model="currentRule.message_template"
            rows="3"
            placeholder="🚨 Alert: {website_name} - {condition_summary}"
            class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
          ></textarea>
          <div class="text-xs text-gray-600 dark:text-gray-400">
            Available variables: {website_name}, {website_url}, {condition_summary}, {timestamp}
          </div>
        </div>
      </div>

      <!-- Scope and Targeting -->
      <div class="space-y-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Rule Scope</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Apply to Websites</label>
            <select
              v-model="currentRule.website_scope"
              class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
            >
              <option value="all">All my websites</option>
              <option value="team">Team websites only</option>
              <option value="personal">Personal websites only</option>
              <option value="specific">Specific websites</option>
            </select>
          </div>

          <div class="space-y-2" v-if="currentRule.website_scope === 'specific'">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Select Websites</label>
            <select
              v-model="currentRule.target_websites"
              multiple
              class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
            >
              <option
                v-for="website in availableWebsites"
                :key="website.id"
                :value="website.id"
              >
                {{ website.name }}
              </option>
            </select>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex justify-end space-x-3 pt-4 border-t">
        <Button @click="showRuleBuilder = false" variant="outline">
          Cancel
        </Button>
        <Button @click="testRule" variant="outline" class="flex items-center space-x-2">
          <Play class="h-4 w-4" />
          <span>Test Rule</span>
        </Button>
        <Button @click="saveRule" class="flex items-center space-x-2">
          <Save class="h-4 w-4" />
          <span>Save Rule</span>
        </Button>
      </div>
    </CardContent>

    <!-- Existing Advanced Rules -->
    <CardContent v-if="!showRuleBuilder" class="space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Active Alert Rules</h3>
        <Badge variant="outline">{{ advancedRules.length }} rules configured</Badge>
      </div>

      <div v-if="advancedRules.length === 0" class="text-center py-8">
        <div class="rounded-lg bg-gray-100 dark:bg-gray-800 p-6">
          <Zap class="h-12 w-12 text-gray-400 mx-auto mb-4" />
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
            No Advanced Rules Yet
          </h3>
          <p class="text-sm text-gray-600 dark:text-gray-400">
            Create sophisticated alert rules with multiple conditions and custom logic
          </p>
        </div>
      </div>

      <div v-else class="space-y-3">
        <AlertRuleCard
          v-for="rule in advancedRules"
          :key="rule.id"
          :rule="rule"
          @edit="editRule"
          @delete="deleteRule"
          @toggle="toggleRule"
          @test="testRule"
        />
      </div>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import AlertRuleCard from './AlertRuleCard.vue';
import {
  Settings,
  Plus,
  X,
  Play,
  Save,
  Zap,
  Mail,
  MessageSquare,
  Monitor,
  Webhook
} from 'lucide-vue-next';

interface AlertCondition {
  field: string;
  operator: string;
  value: string;
}

interface AlertRule {
  id?: string;
  name: string;
  priority: 'critical' | 'high' | 'medium' | 'low';
  logic_operator: 'and' | 'or';
  conditions: AlertCondition[];
  notification_channels: string[];
  cooldown_minutes: number;
  max_alerts_per_day: number;
  message_template: string;
  website_scope: 'all' | 'team' | 'personal' | 'specific';
  target_websites: number[];
  enabled: boolean;
  created_at?: string;
  last_triggered?: string;
}

interface Website {
  id: number;
  name: string;
  url: string;
}

interface Props {
  availableWebsites: Website[];
  existingRules?: AlertRule[];
}

const props = defineProps<Props>();

const emit = defineEmits<{
  ruleCreated: [rule: AlertRule];
  ruleUpdated: [rule: AlertRule];
  ruleDeleted: [ruleId: string];
}>();

const showRuleBuilder = ref(false);
const advancedRules = ref<AlertRule[]>(props.existingRules || []);

const defaultRule = (): AlertRule => ({
  name: '',
  priority: 'medium',
  logic_operator: 'and',
  conditions: [{
    field: 'ssl_status',
    operator: 'equals',
    value: 'expired'
  }],
  notification_channels: ['email', 'dashboard'],
  cooldown_minutes: 60,
  max_alerts_per_day: 10,
  message_template: '🚨 Alert: {website_name} - {condition_summary}',
  website_scope: 'all',
  target_websites: [],
  enabled: true
});

const currentRule = ref<AlertRule>(defaultRule());

const notificationChannels = [
  {
    id: 'email',
    name: 'Email',
    description: 'Send email notifications',
    icon: Mail
  },
  {
    id: 'dashboard',
    name: 'Dashboard',
    description: 'Show in dashboard alerts',
    icon: Monitor
  },
  {
    id: 'slack',
    name: 'Slack',
    description: 'Send to Slack channel',
    icon: MessageSquare
  },
  {
    id: 'webhook',
    name: 'Webhook',
    description: 'HTTP POST to custom endpoint',
    icon: Webhook
  }
];

const addCondition = () => {
  currentRule.value.conditions.push({
    field: 'ssl_status',
    operator: 'equals',
    value: ''
  });
};

const removeCondition = (index: number) => {
  currentRule.value.conditions.splice(index, 1);
};

const toggleNotificationChannel = (channelId: string) => {
  const channels = currentRule.value.notification_channels;
  const index = channels.indexOf(channelId);

  if (index > -1) {
    channels.splice(index, 1);
  } else {
    channels.push(channelId);
  }
};

const getPlaceholderForField = (field: string): string => {
  const placeholders = {
    ssl_status: 'valid, invalid, expired',
    ssl_days_remaining: '7, 30, 90',
    ssl_issuer: "Let's Encrypt, DigiCert",
    ssl_key_size: '2048, 4096',
    ssl_security_score: '80, 90, 100',
    uptime_status: 'up, down, slow',
    response_time: '1000, 5000',
    uptime_percentage: '99.9, 95.0',
    consecutive_failures: '3, 5',
    website_name: 'My Website',
    website_url: 'example.com',
    monitoring_enabled: 'true, false',
    team_assignment: 'team, personal'
  };

  return placeholders[field] || 'Enter value';
};

const getConditionPreview = (condition: AlertCondition): string => {
  if (!condition.field || !condition.operator) {
    return 'Incomplete condition';
  }

  const fieldName = condition.field.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
  const operatorText = {
    equals: 'equals',
    not_equals: 'does not equal',
    greater_than: 'is greater than',
    less_than: 'is less than',
    greater_equal: 'is greater than or equal to',
    less_equal: 'is less than or equal to',
    contains: 'contains',
    not_contains: 'does not contain',
    starts_with: 'starts with',
    ends_with: 'ends with'
  }[condition.operator] || condition.operator;

  return `${fieldName} ${operatorText} "${condition.value || '(empty)'}"`;
};

const getRulePreview = (): string => {
  if (currentRule.value.conditions.length === 0) {
    return 'No conditions defined';
  }

  const conditionPreviews = currentRule.value.conditions.map(getConditionPreview);
  const operator = currentRule.value.logic_operator === 'and' ? ' AND ' : ' OR ';

  return conditionPreviews.join(operator);
};

const editRule = (rule: AlertRule) => {
  currentRule.value = { ...rule };
  showRuleBuilder.value = true;
};

const deleteRule = (ruleId: string) => {
  if (confirm('Are you sure you want to delete this alert rule?')) {
    const index = advancedRules.value.findIndex(r => r.id === ruleId);
    if (index > -1) {
      advancedRules.value.splice(index, 1);
      emit('ruleDeleted', ruleId);
    }
  }
};

const toggleRule = (rule: AlertRule) => {
  rule.enabled = !rule.enabled;
  emit('ruleUpdated', rule);
};

const testRule = (rule?: AlertRule) => {
  const testRule = rule || currentRule.value;
  alert(`Testing rule: ${testRule.name || 'Unnamed Rule'}\n\nThis would check ${props.availableWebsites.length} websites against the configured conditions.`);
};

const saveRule = () => {
  if (!currentRule.value.name.trim()) {
    alert('Please enter a rule name');
    return;
  }

  if (currentRule.value.conditions.length === 0) {
    alert('Please add at least one condition');
    return;
  }

  if (currentRule.value.notification_channels.length === 0) {
    alert('Please select at least one notification channel');
    return;
  }

  const rule = { ...currentRule.value };

  if (rule.id) {
    // Update existing rule
    const index = advancedRules.value.findIndex(r => r.id === rule.id);
    if (index > -1) {
      advancedRules.value[index] = rule;
      emit('ruleUpdated', rule);
    }
  } else {
    // Create new rule
    rule.id = Date.now().toString();
    rule.created_at = new Date().toISOString();
    advancedRules.value.push(rule);
    emit('ruleCreated', rule);
  }

  // Reset form
  currentRule.value = defaultRule();
  showRuleBuilder.value = false;
};
</script>