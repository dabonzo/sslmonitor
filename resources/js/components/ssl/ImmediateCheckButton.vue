<script setup lang="ts">
import { computed } from 'vue';
import { Zap, Loader2, CheckCircle, AlertCircle, Clock } from 'lucide-vue-next';
import { useImmediateCheck } from '@/composables/useImmediateCheck';

interface Props {
  websiteId: number;
  websiteName: string;
  sslEnabled: boolean;
  uptimeEnabled: boolean;
  size?: 'sm' | 'md' | 'lg';
  variant?: 'default' | 'outline' | 'compact';
}

const props = withDefaults(defineProps<Props>(), {
  size: 'sm',
  variant: 'default'
});

const { triggerImmediateCheck, getWebsiteState } = useImmediateCheck();

// Get current state for this website
const checkState = computed(() => getWebsiteState(props.websiteId));

// Computed classes based on size and variant
const buttonClasses = computed(() => {
  const baseClasses = 'inline-flex items-center font-medium transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed';

  let sizeClasses = '';
  switch (props.size) {
    case 'sm':
      sizeClasses = 'px-2 py-1 text-xs';
      break;
    case 'md':
      sizeClasses = 'px-3 py-1.5 text-sm';
      break;
    case 'lg':
      sizeClasses = 'px-4 py-2 text-sm';
      break;
  }

  let variantClasses = '';
  let stateClasses = '';

  if (checkState.value.isChecking) {
    stateClasses = 'text-blue-700 bg-blue-50 border border-blue-200 cursor-not-allowed';
  } else if (checkState.value.status === 'completed') {
    stateClasses = 'text-green-700 bg-green-50 border border-green-200 hover:bg-green-100';
  } else if (checkState.value.status === 'error' || checkState.value.status === 'timeout') {
    stateClasses = 'text-red-700 bg-red-50 border border-red-200 hover:bg-red-100';
  } else {
    switch (props.variant) {
      case 'outline':
        variantClasses = 'text-purple-700 bg-transparent border border-purple-200 hover:bg-purple-50';
        break;
      case 'compact':
        variantClasses = 'text-purple-600 bg-purple-50 border border-purple-200 hover:bg-purple-100';
        break;
      default:
        variantClasses = 'text-purple-700 bg-purple-50 border border-purple-200 hover:bg-purple-100';
        break;
    }
  }

  return `${baseClasses} ${sizeClasses} ${stateClasses || variantClasses} rounded-md`;
});

const iconClasses = computed(() => {
  const baseSize = props.size === 'lg' ? 'h-4 w-4' : 'h-3 w-3';
  const margin = props.variant === 'compact' ? 'mr-1' : 'mr-1';
  return `${baseSize} ${margin}`;
});

// Button text based on state
const buttonText = computed(() => {
  if (checkState.value.isChecking) {
    const progress = Math.round(checkState.value.progress);
    return `Checking... ${progress}%`;
  }

  if (checkState.value.status === 'completed') {
    return 'Check Complete';
  }

  if (checkState.value.status === 'error') {
    return 'Check Failed';
  }

  if (checkState.value.status === 'timeout') {
    return 'Check Timeout';
  }

  return props.variant === 'compact' ? 'Check' : 'Run Check';
});

// Icon based on state
const currentIcon = computed(() => {
  if (checkState.value.isChecking) {
    return Loader2;
  }

  if (checkState.value.status === 'completed') {
    return CheckCircle;
  }

  if (checkState.value.status === 'error' || checkState.value.status === 'timeout') {
    return AlertCircle;
  }

  return Zap;
});

// Check if monitoring is enabled
const isMonitoringEnabled = computed(() => {
  return props.sslEnabled || props.uptimeEnabled;
});

// Tooltip text
const tooltipText = computed(() => {
  if (!isMonitoringEnabled.value) {
    return 'No monitoring enabled for this website';
  }

  if (checkState.value.isChecking) {
    return `Running immediate check for ${props.websiteName}...`;
  }

  if (checkState.value.status === 'completed') {
    return `Check completed for ${props.websiteName}`;
  }

  if (checkState.value.status === 'error') {
    return `Check failed: ${checkState.value.error}`;
  }

  if (checkState.value.status === 'timeout') {
    return `Check timed out for ${props.websiteName}`;
  }

  const enabledTypes = [];
  if (props.sslEnabled) enabledTypes.push('SSL');
  if (props.uptimeEnabled) enabledTypes.push('uptime');

  return `Run immediate ${enabledTypes.join(' and ')} check for ${props.websiteName}`;
});

// Handle click
const handleClick = async () => {
  if (!isMonitoringEnabled.value || checkState.value.isChecking) {
    return;
  }

  console.log(`[ImmediateCheckButton] Triggering check for website ${props.websiteId}`);

  const success = await triggerImmediateCheck(props.websiteId);

  if (success) {
    console.log(`[ImmediateCheckButton] Check started successfully for website ${props.websiteId}`);
  } else {
    console.error(`[ImmediateCheckButton] Failed to start check for website ${props.websiteId}`);
  }
};
</script>

<template>
  <button
    @click="handleClick"
    :disabled="!isMonitoringEnabled || checkState.isChecking"
    :class="buttonClasses"
    :title="tooltipText"
  >
    <component
      :is="currentIcon"
      :class="[
        iconClasses,
        checkState.isChecking ? 'animate-spin' : '',
        checkState.status === 'completed' ? 'text-green-600' : '',
        checkState.status === 'error' || checkState.status === 'timeout' ? 'text-destructive' : ''
      ]"
    />
    {{ buttonText }}
  </button>

  <!-- Progress bar for checking state -->
  <div
    v-if="checkState.isChecking"
    class="mt-1 w-full bg-blue-100 rounded-full h-1.5 overflow-hidden"
  >
    <div
      class="bg-blue-500 h-1.5 rounded-full transition-all duration-300 ease-out"
      :style="{ width: `${checkState.progress}%` }"
    />
  </div>

  <!-- Error message -->
  <div
    v-if="checkState.error && (checkState.status === 'error' || checkState.status === 'timeout')"
    class="mt-1 text-xs text-destructive"
  >
    {{ checkState.error }}
  </div>
</template>