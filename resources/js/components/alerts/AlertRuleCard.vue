<template>
  <div class="p-4 rounded-lg border transition-all duration-200 hover:shadow-md" :class="cardClass">
    <div class="flex items-start justify-between">
      <!-- Rule Info -->
      <div class="flex-1 min-w-0">
        <div class="flex items-center space-x-3 mb-2">
          <h4 class="font-semibold text-foreground dark:text-foreground truncate">
            {{ rule.name }}
          </h4>
          <Badge :variant="priorityVariant" class="flex-shrink-0">
            {{ priorityLabel }}
          </Badge>
          <Badge :variant="rule.enabled ? 'default' : 'secondary'" class="flex-shrink-0">
            {{ rule.enabled ? 'Active' : 'Disabled' }}
          </Badge>
        </div>

        <!-- Rule Summary -->
        <p class="text-sm text-foreground dark:text-muted-foreground mb-3 line-clamp-2">
          {{ ruleSummary }}
        </p>

        <!-- Rule Metrics -->
        <div class="flex flex-wrap items-center gap-4 text-xs text-muted-foreground dark:text-muted-foreground">
          <div class="flex items-center space-x-1">
            <component :is="LogicIcon" class="h-3 w-3" />
            <span>{{ rule.logic_operator.toUpperCase() }} logic</span>
          </div>
          <div class="flex items-center space-x-1">
            <Filter class="h-3 w-3" />
            <span>{{ rule.conditions.length }} condition{{ rule.conditions.length !== 1 ? 's' : '' }}</span>
          </div>
          <div class="flex items-center space-x-1">
            <Bell class="h-3 w-3" />
            <span>{{ rule.notification_channels.length }} channel{{ rule.notification_channels.length !== 1 ? 's' : '' }}</span>
          </div>
          <div v-if="rule.website_scope !== 'all'" class="flex items-center space-x-1">
            <Target class="h-3 w-3" />
            <span>{{ websiteScopeLabel }}</span>
          </div>
        </div>

        <!-- Last Triggered -->
        <div v-if="rule.last_triggered" class="mt-2 text-xs text-muted-foreground dark:text-muted-foreground">
          Last triggered: {{ formatLastTriggered(rule.last_triggered) }}
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center space-x-2 ml-4">
        <!-- Status Toggle -->
        <Button
          @click="$emit('toggle', rule)"
          :variant="rule.enabled ? 'destructive' : 'default'"
          size="sm"
          class="flex items-center space-x-1"
        >
          <component :is="rule.enabled ? Pause : Play" class="h-3 w-3" />
          <span class="sr-only">{{ rule.enabled ? 'Disable' : 'Enable' }}</span>
        </Button>

        <!-- Test Button -->
        <Button
          @click="$emit('test', rule)"
          variant="outline"
          size="sm"
          class="flex items-center space-x-1"
          title="Test this rule against current data"
        >
          <Zap class="h-3 w-3" />
          <span class="sr-only">Test</span>
        </Button>

        <!-- More Actions Dropdown -->
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="ghost" size="sm">
              <MoreVertical class="h-4 w-4" />
              <span class="sr-only">More actions</span>
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem @click="showDetails = !showDetails">
              <Eye class="h-4 w-4 mr-2" />
              {{ showDetails ? 'Hide Details' : 'View Details' }}
            </DropdownMenuItem>
            <DropdownMenuItem @click="$emit('edit', rule)">
              <Edit class="h-4 w-4 mr-2" />
              Edit Rule
            </DropdownMenuItem>
            <DropdownMenuItem @click="duplicateRule">
              <Copy class="h-4 w-4 mr-2" />
              Duplicate
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem @click="$emit('delete', rule.id)" class="text-destructive dark:text-red-400">
              <Trash2 class="h-4 w-4 mr-2" />
              Delete Rule
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </div>

    <!-- Expanded Details -->
    <Collapsible :open="showDetails">
      <CollapsibleContent>
        <div class="mt-4 pt-4 border-t space-y-4">
          <!-- Conditions Detail -->
          <div>
            <h5 class="text-sm font-medium text-foreground dark:text-foreground mb-2">
              Rule Conditions ({{ rule.logic_operator.toUpperCase() }} logic)
            </h5>
            <div class="space-y-2">
              <div
                v-for="(condition, index) in rule.conditions"
                :key="index"
                class="p-2 bg-muted dark:bg-card rounded text-sm font-mono"
              >
                {{ getConditionText(condition) }}
              </div>
            </div>
          </div>

          <!-- Notification Settings -->
          <div>
            <h5 class="text-sm font-medium text-foreground dark:text-foreground mb-2">
              Notification Settings
            </h5>
            <div class="grid grid-cols-2 gap-4 text-sm">
              <div>
                <span class="text-foreground dark:text-muted-foreground">Channels:</span>
                <div class="flex flex-wrap gap-1 mt-1">
                  <Badge
                    v-for="channel in rule.notification_channels"
                    :key="channel"
                    variant="outline"
                    class="text-xs"
                  >
                    {{ getChannelLabel(channel) }}
                  </Badge>
                </div>
              </div>
              <div>
                <div class="text-foreground dark:text-muted-foreground">
                  Cooldown: <span class="font-medium">{{ getCooldownText(rule.cooldown_minutes) }}</span>
                </div>
                <div class="text-foreground dark:text-muted-foreground">
                  Max per day: <span class="font-medium">{{ rule.max_alerts_per_day }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Custom Message -->
          <div v-if="rule.message_template">
            <h5 class="text-sm font-medium text-foreground dark:text-foreground mb-2">
              Custom Message Template
            </h5>
            <div class="p-2 bg-muted dark:bg-card rounded text-sm">
              {{ rule.message_template }}
            </div>
          </div>

          <!-- Website Scope -->
          <div v-if="rule.website_scope !== 'all'">
            <h5 class="text-sm font-medium text-foreground dark:text-foreground mb-2">
              Website Scope
            </h5>
            <div class="text-sm text-foreground dark:text-muted-foreground">
              {{ getWebsiteScopeDescription() }}
            </div>
          </div>
        </div>
      </CollapsibleContent>
    </Collapsible>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Collapsible, CollapsibleContent } from '@/components/ui/collapsible';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
  Bell,
  Filter,
  Target,
  Play,
  Pause,
  Zap,
  MoreVertical,
  Eye,
  Edit,
  Copy,
  Trash2,
  GitBranch,
  Layers
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

interface Props {
  rule: AlertRule;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  edit: [rule: AlertRule];
  delete: [ruleId: string];
  toggle: [rule: AlertRule];
  test: [rule: AlertRule];
  duplicate: [rule: AlertRule];
}>();

const showDetails = ref(false);

const cardClass = computed(() => {
  const baseClasses = 'bg-background dark:bg-card border-border dark:border-border';

  if (!props.rule.enabled) {
    return `${baseClasses} border-border dark:border-border opacity-75`;
  }

  switch (props.rule.priority) {
    case 'critical':
      return `${baseClasses} border-l-4 border-l-red-500 bg-red-50/50 dark:bg-red-900/10`;
    case 'high':
      return `${baseClasses} border-l-4 border-l-orange-500 bg-orange-50/50 dark:bg-orange-900/10`;
    case 'medium':
      return `${baseClasses} border-l-4 border-l-yellow-500 bg-yellow-50/50 dark:bg-yellow-900/10`;
    case 'low':
      return `${baseClasses} border-l-4 border-l-blue-500 bg-blue-50/50 dark:bg-blue-900/10`;
    default:
      return baseClasses;
  }
});

const priorityVariant = computed(() => {
  switch (props.rule.priority) {
    case 'critical':
      return 'destructive';
    case 'high':
      return 'destructive';
    case 'medium':
      return 'secondary';
    case 'low':
      return 'outline';
    default:
      return 'outline';
  }
});

const priorityLabel = computed(() => {
  const labels = {
    critical: '🔴 Critical',
    high: '🟠 High',
    medium: '🟡 Medium',
    low: '🔵 Low'
  };
  return labels[props.rule.priority] || props.rule.priority;
});

const LogicIcon = computed(() => {
  return props.rule.logic_operator === 'and' ? GitBranch : Layers;
});

const ruleSummary = computed(() => {
  if (props.rule.conditions.length === 0) {
    return 'No conditions configured';
  }

  const firstCondition = props.rule.conditions[0];
  const conditionText = getConditionText(firstCondition);

  if (props.rule.conditions.length === 1) {
    return `Triggers when ${conditionText}`;
  }

  return `Triggers when ${conditionText} ${props.rule.logic_operator === 'and' ? 'AND' : 'OR'} ${props.rule.conditions.length - 1} other condition${props.rule.conditions.length > 2 ? 's' : ''}`;
});

const websiteScopeLabel = computed(() => {
  switch (props.rule.website_scope) {
    case 'team':
      return 'Team sites';
    case 'personal':
      return 'Personal sites';
    case 'specific':
      return `${props.rule.target_websites.length} specific sites`;
    default:
      return 'All sites';
  }
});

const getConditionText = (condition: AlertCondition): string => {
  const fieldLabels = {
    ssl_status: 'SSL Status',
    ssl_days_remaining: 'Days Until SSL Expiry',
    ssl_issuer: 'Certificate Authority',
    ssl_key_size: 'Key Size',
    ssl_security_score: 'Security Score',
    uptime_status: 'Uptime Status',
    response_time: 'Response Time',
    uptime_percentage: 'Uptime Percentage',
    consecutive_failures: 'Consecutive Failures',
    website_name: 'Website Name',
    website_url: 'Website URL',
    monitoring_enabled: 'Monitoring Enabled',
    team_assignment: 'Team Assignment'
  };

  const operatorLabels = {
    equals: 'equals',
    not_equals: 'does not equal',
    greater_than: '>',
    less_than: '<',
    greater_equal: '>=',
    less_equal: '<=',
    contains: 'contains',
    not_contains: 'does not contain',
    starts_with: 'starts with',
    ends_with: 'ends with'
  };

  const fieldName = fieldLabels[condition.field] || condition.field;
  const operator = operatorLabels[condition.operator] || condition.operator;

  return `${fieldName} ${operator} "${condition.value}"`;
};

const getChannelLabel = (channel: string): string => {
  const labels = {
    email: 'Email',
    dashboard: 'Dashboard',
    slack: 'Slack',
    webhook: 'Webhook'
  };
  return labels[channel] || channel;
};

const getCooldownText = (minutes: number): string => {
  if (minutes === 0) return 'No cooldown';
  if (minutes < 60) return `${minutes} minutes`;
  if (minutes < 1440) return `${Math.round(minutes / 60)} hours`;
  return `${Math.round(minutes / 1440)} days`;
};

const getWebsiteScopeDescription = (): string => {
  switch (props.rule.website_scope) {
    case 'team':
      return 'Applied to all team websites only';
    case 'personal':
      return 'Applied to personal websites only';
    case 'specific':
      return `Applied to ${props.rule.target_websites.length} specifically selected websites`;
    default:
      return 'Applied to all websites';
  }
};

const formatLastTriggered = (timestamp: string): string => {
  const date = new Date(timestamp);
  const now = new Date();
  const diffInMinutes = Math.floor((now.getTime() - date.getTime()) / (1000 * 60));

  if (diffInMinutes < 1) return 'Just now';
  if (diffInMinutes < 60) return `${diffInMinutes} minutes ago`;
  if (diffInMinutes < 1440) return `${Math.floor(diffInMinutes / 60)} hours ago`;
  return `${Math.floor(diffInMinutes / 1440)} days ago`;
};

const duplicateRule = () => {
  const duplicatedRule: AlertRule = {
    ...props.rule,
    id: undefined,
    name: `${props.rule.name} (Copy)`,
    created_at: undefined,
    last_triggered: undefined
  };
  emit('duplicate', duplicatedRule);
};
</script>