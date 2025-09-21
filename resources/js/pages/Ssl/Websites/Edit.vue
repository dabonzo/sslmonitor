<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
  Shield,
  Wifi,
  Clock,
  Settings,
  CheckCircle,
  AlertTriangle,
  HelpCircle
} from 'lucide-vue-next';
import ssl from '@/routes/ssl';

interface MonitoringConfig {
  check_interval: number;
  timeout: number;
  description?: string;
}

interface Website {
  id: number;
  name: string;
  url: string;
  ssl_monitoring_enabled: boolean;
  uptime_monitoring_enabled: boolean;
  monitoring_config?: MonitoringConfig;
}

interface Props {
  website: Website;
}

const props = defineProps<Props>();

const form = useForm({
  name: props.website.name,
  url: props.website.url,
  ssl_monitoring_enabled: props.website.ssl_monitoring_enabled,
  uptime_monitoring_enabled: props.website.uptime_monitoring_enabled,
  monitoring_config: {
    check_interval: props.website.monitoring_config?.check_interval || 3600,
    timeout: props.website.monitoring_config?.timeout || 30,
    description: props.website.monitoring_config?.description || ''
  }
});

const showAdvancedOptions = ref(false);

// Check interval options in seconds
const checkIntervalOptions = [
  { label: '5 minutes', value: 300 },
  { label: '15 minutes', value: 900 },
  { label: '30 minutes', value: 1800 },
  { label: '1 hour', value: 3600 },
  { label: '2 hours', value: 7200 },
  { label: '6 hours', value: 21600 },
  { label: '12 hours', value: 43200 },
  { label: '24 hours', value: 86400 }
];

// Timeout options in seconds
const timeoutOptions = [
  { label: '10 seconds', value: 10 },
  { label: '15 seconds', value: 15 },
  { label: '30 seconds', value: 30 },
  { label: '60 seconds', value: 60 },
  { label: '90 seconds', value: 90 }
];

const selectedIntervalLabel = computed(() => {
  const option = checkIntervalOptions.find(opt => opt.value === form.monitoring_config.check_interval);
  return option?.label || 'Custom';
});

const selectedTimeoutLabel = computed(() => {
  const option = timeoutOptions.find(opt => opt.value === form.monitoring_config.timeout);
  return option?.label || 'Custom';
});

function handleSubmit() {
  form.put(ssl.websites.update(props.website.id).url, {
    onSuccess: () => {
      // Redirect handled by controller
    },
    onError: (errors) => {
      console.error('Form submission errors:', errors);
    }
  });
}

function cancel() {
  router.visit(ssl.websites.index().url);
}
</script>

<template>
  <Head :title="`Edit ${props.website.name}`" />

  <DashboardLayout :title="`Edit ${props.website.name}`">
    <div class="space-y-6">
      <!-- Header -->
      <div>
        <h1 class="text-2xl font-semibold text-foreground">Edit Website</h1>
        <p class="text-muted-foreground">Update {{ props.website.name }} monitoring settings</p>
      </div>

      <!-- Form -->
      <form @submit.prevent="handleSubmit" class="space-y-6">
        <!-- Basic Information -->
        <div class="rounded-lg bg-card text-card-foreground p-6 shadow-sm">
          <h2 class="text-lg font-semibold text-foreground mb-4">Basic Information</h2>

          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-foreground mb-2">
                Website Name <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.name"
                type="text"
                class="w-full rounded-md border border-border bg-background px-3 py-2 text-foreground placeholder-muted-foreground focus:border-primary focus:ring-1 focus:ring-primary"
                :class="{ 'border-red-500': form.errors.name }"
                placeholder="My Website"
                required
              />
              <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">
                {{ form.errors.name }}
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-foreground mb-2">
                Website URL <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.url"
                type="url"
                class="w-full rounded-md border border-border bg-background px-3 py-2 text-foreground placeholder-muted-foreground focus:border-primary focus:ring-1 focus:ring-primary"
                :class="{ 'border-red-500': form.errors.url }"
                placeholder="https://example.com"
                required
              />
              <div v-if="form.errors.url" class="text-red-500 text-xs mt-1">
                {{ form.errors.url }}
              </div>
              <p class="text-xs text-muted-foreground mt-1">
                Protocol will be normalized automatically
              </p>
            </div>

            <div>
              <label class="block text-sm font-medium text-foreground mb-2">
                Description (Optional)
              </label>
              <textarea
                v-model="form.monitoring_config.description"
                class="w-full rounded-md border border-border bg-background px-3 py-2 text-foreground placeholder-muted-foreground focus:border-primary focus:ring-1 focus:ring-primary"
                rows="2"
                placeholder="Brief description of this website"
              />
            </div>
          </div>
        </div>

        <!-- Monitoring Configuration -->
        <div class="rounded-lg bg-card text-card-foreground p-6 shadow-sm">
          <h2 class="text-lg font-semibold text-foreground mb-4">Monitoring Configuration</h2>

          <div class="space-y-6">
            <!-- SSL Monitoring -->
            <div class="rounded-lg border border-border p-4">
              <div class="flex items-center justify-between mb-3">
                <div class="flex items-center space-x-3">
                  <Shield class="h-5 w-5 text-blue-500" />
                  <div>
                    <h3 class="text-sm font-medium text-foreground">SSL Certificate Monitoring</h3>
                    <p class="text-xs text-muted-foreground">Monitor SSL certificate expiration and validity</p>
                  </div>
                </div>
                <label class="flex items-center">
                  <input
                    v-model="form.ssl_monitoring_enabled"
                    type="checkbox"
                    class="rounded border-border text-primary focus:ring-primary"
                  />
                  <span class="ml-2 text-sm text-foreground">Enable</span>
                </label>
              </div>

              <div v-if="form.ssl_monitoring_enabled" class="pl-8 space-y-2">
                <div class="flex items-center space-x-2 text-xs text-muted-foreground">
                  <CheckCircle class="h-3 w-3 text-green-500" />
                  <span>Certificate expiration monitoring</span>
                </div>
                <div class="flex items-center space-x-2 text-xs text-muted-foreground">
                  <CheckCircle class="h-3 w-3 text-green-500" />
                  <span>Certificate validity checks</span>
                </div>
                <div class="flex items-center space-x-2 text-xs text-muted-foreground">
                  <CheckCircle class="h-3 w-3 text-green-500" />
                  <span>Response time measurement</span>
                </div>
              </div>
            </div>

            <!-- Uptime Monitoring -->
            <div class="rounded-lg border border-border p-4">
              <div class="flex items-center justify-between mb-3">
                <div class="flex items-center space-x-3">
                  <Wifi class="h-5 w-5 text-emerald-500" />
                  <div>
                    <h3 class="text-sm font-medium text-foreground">Uptime Monitoring</h3>
                    <p class="text-xs text-muted-foreground">Monitor website availability and response times</p>
                  </div>
                </div>
                <label class="flex items-center">
                  <input
                    v-model="form.uptime_monitoring_enabled"
                    type="checkbox"
                    class="rounded border-border text-primary focus:ring-primary"
                  />
                  <span class="ml-2 text-sm text-foreground">Enable</span>
                </label>
              </div>

              <div v-if="form.uptime_monitoring_enabled" class="pl-8 space-y-2">
                <div class="flex items-center space-x-2 text-xs text-muted-foreground">
                  <CheckCircle class="h-3 w-3 text-green-500" />
                  <span>HTTP/HTTPS availability checks</span>
                </div>
                <div class="flex items-center space-x-2 text-xs text-muted-foreground">
                  <CheckCircle class="h-3 w-3 text-green-500" />
                  <span>Response time tracking</span>
                </div>
                <div class="flex items-center space-x-2 text-xs text-muted-foreground">
                  <CheckCircle class="h-3 w-3 text-green-500" />
                  <span>Downtime notifications</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Advanced Options -->
        <div class="rounded-lg bg-card text-card-foreground p-6 shadow-sm">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-foreground">Advanced Options</h2>
            <button
              type="button"
              @click="showAdvancedOptions = !showAdvancedOptions"
              class="flex items-center space-x-2 text-sm text-primary hover:text-primary/80"
            >
              <Settings class="h-4 w-4" />
              <span>{{ showAdvancedOptions ? 'Hide' : 'Show' }} Advanced</span>
            </button>
          </div>

          <div v-if="showAdvancedOptions" class="space-y-6">
            <!-- Check Interval -->
            <div>
              <label class="block text-sm font-medium text-foreground mb-2">
                Check Interval
              </label>
              <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                <button
                  v-for="option in checkIntervalOptions"
                  :key="option.value"
                  type="button"
                  @click="form.monitoring_config.check_interval = option.value"
                  class="px-3 py-2 text-xs rounded-md border border-border hover:bg-muted transition-colors"
                  :class="{
                    'bg-primary text-primary-foreground border-primary': form.monitoring_config.check_interval === option.value,
                    'bg-background text-foreground': form.monitoring_config.check_interval !== option.value
                  }"
                >
                  {{ option.label }}
                </button>
              </div>
              <p class="text-xs text-muted-foreground mt-1">
                Current: {{ selectedIntervalLabel }}
              </p>
            </div>

            <!-- Timeout -->
            <div>
              <label class="block text-sm font-medium text-foreground mb-2">
                Request Timeout
              </label>
              <div class="grid grid-cols-2 md:grid-cols-5 gap-2">
                <button
                  v-for="option in timeoutOptions"
                  :key="option.value"
                  type="button"
                  @click="form.monitoring_config.timeout = option.value"
                  class="px-3 py-2 text-xs rounded-md border border-border hover:bg-muted transition-colors"
                  :class="{
                    'bg-primary text-primary-foreground border-primary': form.monitoring_config.timeout === option.value,
                    'bg-background text-foreground': form.monitoring_config.timeout !== option.value
                  }"
                >
                  {{ option.label }}
                </button>
              </div>
              <p class="text-xs text-muted-foreground mt-1">
                Current: {{ selectedTimeoutLabel }}
              </p>
            </div>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-end space-x-3">
          <button
            type="button"
            @click="cancel"
            class="px-4 py-2 text-sm font-medium text-foreground bg-background border border-border rounded-md hover:bg-muted transition-colors"
            :disabled="form.processing"
          >
            Cancel
          </button>
          <button
            type="submit"
            class="px-4 py-2 text-sm font-medium text-primary-foreground bg-primary rounded-md hover:bg-primary/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="form.processing"
          >
            <span v-if="form.processing">Updating Website...</span>
            <span v-else>Update Website</span>
          </button>
        </div>
      </form>
    </div>
  </DashboardLayout>
</template>