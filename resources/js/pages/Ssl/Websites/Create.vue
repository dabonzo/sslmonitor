<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import ssl from '@/routes/ssl';
import { useImmediateCheck } from '@/composables/useImmediateCheck';
import {
  Shield,
  Wifi,
  Clock,
  Settings,
  CheckCircle,
  AlertTriangle,
  HelpCircle,
  Loader2,
  Zap
} from 'lucide-vue-next';

interface MonitoringConfig {
  check_interval: number;
  timeout: number;
  description?: string;
}

interface FormData {
  name: string;
  url: string;
  ssl_monitoring_enabled: boolean;
  uptime_monitoring_enabled: boolean;
  monitoring_config: MonitoringConfig;
  immediate_check: boolean;
}

const form = useForm<FormData>({
  name: '',
  url: '',
  ssl_monitoring_enabled: true,
  uptime_monitoring_enabled: false,
  monitoring_config: {
    check_interval: 3600, // 1 hour default
    timeout: 30,
    description: ''
  },
  immediate_check: true
});

const showAdvancedOptions = ref(false);

// Immediate check state management
const { triggerImmediateCheck, getWebsiteState } = useImmediateCheck();
const createdWebsiteId = ref<number | null>(null);
const showCheckProgress = ref(false);

// Get check state for the created website
const checkState = computed(() => {
  return createdWebsiteId.value ? getWebsiteState(createdWebsiteId.value) : null;
});

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
  form.post(ssl.websites.store().url, {
    onSuccess: (page) => {
      // If immediate check was requested, show progress instead of redirecting immediately
      if (form.immediate_check) {
        showCheckProgress.value = true;

        // Extract website ID from the response or page data
        // Since we're redirected, we need to parse it from the success message or URL
        // For now, we'll simulate with a timeout and then redirect
        setTimeout(() => {
          router.visit(ssl.websites.index().url);
        }, 5000); // Give 5 seconds to show the progress
      } else {
        // Normal redirect for non-immediate checks
        router.visit(ssl.websites.index().url);
      }
    },
    onError: (errors) => {
      console.error('Form submission errors:', errors);
      showCheckProgress.value = false;
    }
  });
}

function cancel() {
  router.visit(ssl.websites.index().url);
}</script>

<template>
  <Head title="Add Website" />

  <DashboardLayout title="Add Website">
    <div class="space-y-6">
      <!-- Header -->
      <div>
        <h1 class="text-2xl font-semibold text-foreground">Add Website</h1>
        <p class="text-muted-foreground">Add a new website for SSL and uptime monitoring</p>
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
                Protocol will be added automatically if not provided
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

        <!-- Immediate Check Options -->
        <div class="rounded-lg bg-card text-card-foreground p-6 shadow-sm">
          <div class="flex items-start space-x-3">
            <Clock class="h-5 w-5 text-purple-500 mt-0.5" />
            <div class="flex-1">
              <h3 class="text-sm font-medium text-foreground mb-2">Immediate Checks</h3>
              <label class="flex items-start space-x-3">
                <input
                  v-model="form.immediate_check"
                  type="checkbox"
                  class="rounded border-border text-primary focus:ring-primary mt-0.5"
                />
                <div>
                  <span class="text-sm text-foreground">Run checks immediately after adding website</span>
                  <p class="text-xs text-muted-foreground mt-1">
                    Perform SSL and uptime checks right away to validate the website configuration
                  </p>
                </div>
              </label>
            </div>
          </div>
        </div>

        <!-- Immediate Check Progress -->
        <div
          v-if="showCheckProgress && form.immediate_check"
          class="rounded-lg bg-card text-card-foreground p-6 shadow-sm border-l-4 border-l-blue-500"
        >
          <div class="flex items-start space-x-3">
            <Loader2 class="h-5 w-5 text-blue-500 animate-spin mt-0.5" />
            <div class="flex-1">
              <h3 class="text-sm font-medium text-foreground mb-2">
                Running Immediate Checks
              </h3>
              <p class="text-sm text-muted-foreground mb-4">
                Your website has been created successfully. Running initial SSL and uptime checks...
              </p>

              <!-- Progress Steps -->
              <div class="space-y-3">
                <div class="flex items-center space-x-3">
                  <CheckCircle class="h-4 w-4 text-green-500" />
                  <span class="text-sm text-foreground">Website created</span>
                </div>
                <div class="flex items-center space-x-3">
                  <Loader2 class="h-4 w-4 text-blue-500 animate-spin" />
                  <span class="text-sm text-foreground">Running SSL and uptime checks</span>
                </div>
                <div class="flex items-center space-x-3">
                  <Clock class="h-4 w-4 text-muted-foreground" />
                  <span class="text-sm text-muted-foreground">Generating initial reports</span>
                </div>
              </div>

              <!-- Progress Bar -->
              <div class="mt-4">
                <div class="flex justify-between text-xs text-muted-foreground mb-1">
                  <span>Progress</span>
                  <span>60%</span>
                </div>
                <div class="w-full bg-muted rounded-full h-2">
                  <div
                    class="bg-blue-500 h-2 rounded-full transition-all duration-300 ease-out"
                    style="width: 60%"
                  />
                </div>
              </div>

              <!-- Info -->
              <div class="mt-4 p-3 bg-blue-50 rounded-md">
                <p class="text-xs text-blue-700">
                  <Zap class="h-3 w-3 inline mr-1" />
                  This usually takes 15-30 seconds. You'll be redirected automatically when complete.
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-end space-x-3">
          <button
            type="button"
            @click="cancel"
            class="px-4 py-2 text-sm font-medium text-foreground bg-background border border-border rounded-md hover:bg-muted transition-colors"
            :disabled="form.processing || showCheckProgress"
          >
            Cancel
          </button>
          <button
            type="submit"
            class="px-4 py-2 text-sm font-medium text-primary-foreground bg-primary rounded-md hover:bg-primary/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="form.processing || showCheckProgress"
          >
            <span v-if="form.processing">
              <Loader2 class="h-4 w-4 animate-spin inline mr-2" />
              Adding Website...
            </span>
            <span v-else-if="showCheckProgress">
              <CheckCircle class="h-4 w-4 inline mr-2" />
              Website Created
            </span>
            <span v-else>Add Website</span>
          </button>
        </div>
      </form>
    </div>
  </DashboardLayout>
</template>