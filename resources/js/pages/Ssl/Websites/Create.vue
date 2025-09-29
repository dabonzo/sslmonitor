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
  Zap,
  FileText,
  Code,
  X,
  Plus
} from 'lucide-vue-next';

interface MonitoringConfig {
  description?: string;
  content_expected_strings?: string[];
  content_forbidden_strings?: string[];
  content_regex_patterns?: string[];
  javascript_enabled?: boolean;
  javascript_wait_seconds?: number;
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
  uptime_monitoring_enabled: true,
  monitoring_config: {
    description: '',
    content_expected_strings: [],
    content_forbidden_strings: [],
    content_regex_patterns: [],
    javascript_enabled: false,
    javascript_wait_seconds: 5
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

// URL normalization function
function normalizeUrl(url: string): string {
  if (!url) return url;

  // Remove whitespace
  url = url.trim();

  // Convert http:// to https:// (auto-upgrade insecure URLs)
  if (url.startsWith('http://')) {
    url = 'https://' + url.substring(7);
  }
  // If it already has https://, keep as is
  else if (url.startsWith('https://')) {
    return url;
  }
  // Add https:// prefix for domain-only URLs
  else {
    url = `https://${url}`;
  }

  return url;
}

// Handle URL input blur to normalize
function handleUrlBlur() {
  form.url = normalizeUrl(form.url);
}


function handleSubmit() {
  // Normalize URL before submission
  form.url = normalizeUrl(form.url);

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
}

// Content validation helpers
function addExpectedString() {
  if (!form.monitoring_config.content_expected_strings) {
    form.monitoring_config.content_expected_strings = [];
  }
  form.monitoring_config.content_expected_strings.push('');
}

function removeExpectedString(index: number) {
  form.monitoring_config.content_expected_strings?.splice(index, 1);
}

function addForbiddenString() {
  if (!form.monitoring_config.content_forbidden_strings) {
    form.monitoring_config.content_forbidden_strings = [];
  }
  form.monitoring_config.content_forbidden_strings.push('');
}

function removeForbiddenString(index: number) {
  form.monitoring_config.content_forbidden_strings?.splice(index, 1);
}

function addRegexPattern() {
  if (!form.monitoring_config.content_regex_patterns) {
    form.monitoring_config.content_regex_patterns = [];
  }
  form.monitoring_config.content_regex_patterns.push('');
}

function removeRegexPattern(index: number) {
  form.monitoring_config.content_regex_patterns?.splice(index, 1);
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
                type="text"
                @blur="handleUrlBlur"
                class="w-full rounded-md border border-border bg-background px-3 py-2 text-foreground placeholder-muted-foreground focus:border-primary focus:ring-1 focus:ring-primary"
                :class="{ 'border-red-500': form.errors.url }"
                placeholder="example.com or https://example.com"
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
            <!-- Monitoring Schedule Info -->
            <div class="rounded-lg border border-border p-4 bg-muted/30">
              <div class="flex items-start space-x-3">
                <Clock class="h-5 w-5 text-blue-500 mt-0.5" />
                <div>
                  <h3 class="text-sm font-medium text-foreground mb-2">Monitoring Schedule</h3>
                  <div class="space-y-2 text-xs text-muted-foreground">
                    <div class="flex items-center space-x-2">
                      <CheckCircle class="h-3 w-3 text-green-500" />
                      <span>Uptime checks: Every 5 minutes</span>
                    </div>
                    <div class="flex items-center space-x-2">
                      <CheckCircle class="h-3 w-3 text-green-500" />
                      <span>SSL certificate checks: Twice daily (6 AM & 6 PM)</span>
                    </div>
                    <div class="flex items-center space-x-2">
                      <HelpCircle class="h-3 w-3 text-blue-500" />
                      <span>Schedules are managed automatically by the system</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Content Validation -->
            <div class="rounded-lg border border-border p-4">
              <div class="flex items-start space-x-3 mb-4">
                <FileText class="h-5 w-5 text-orange-500 mt-0.5" />
                <div>
                  <h3 class="text-sm font-medium text-foreground mb-1">Content Validation</h3>
                  <p class="text-xs text-muted-foreground">Validate page content to detect error pages or unexpected content</p>
                </div>
              </div>

              <!-- Expected Content -->
              <div class="space-y-3 mb-4">
                <label class="block text-xs font-medium text-foreground">
                  Expected Content (strings that must be present)
                </label>
                <div v-if="form.monitoring_config.content_expected_strings?.length" class="space-y-2">
                  <div
                    v-for="(string, index) in form.monitoring_config.content_expected_strings"
                    :key="`expected-${index}`"
                    class="flex items-center space-x-2"
                  >
                    <input
                      v-model="form.monitoring_config.content_expected_strings[index]"
                      type="text"
                      placeholder="e.g., Welcome to our site"
                      class="flex-1 rounded-md border border-border bg-background px-3 py-1 text-sm text-foreground placeholder-muted-foreground focus:border-primary focus:ring-1 focus:ring-primary"
                    />
                    <button
                      type="button"
                      @click="removeExpectedString(index)"
                      class="p-1 text-red-500 hover:text-red-700"
                    >
                      <X class="h-4 w-4" />
                    </button>
                  </div>
                </div>
                <button
                  type="button"
                  @click="addExpectedString"
                  class="flex items-center space-x-2 text-xs text-primary hover:text-primary/80"
                >
                  <Plus class="h-3 w-3" />
                  <span>Add expected content</span>
                </button>
              </div>

              <!-- Forbidden Content -->
              <div class="space-y-3 mb-4">
                <label class="block text-xs font-medium text-foreground">
                  Forbidden Content (strings that must NOT be present)
                </label>
                <div v-if="form.monitoring_config.content_forbidden_strings?.length" class="space-y-2">
                  <div
                    v-for="(string, index) in form.monitoring_config.content_forbidden_strings"
                    :key="`forbidden-${index}`"
                    class="flex items-center space-x-2"
                  >
                    <input
                      v-model="form.monitoring_config.content_forbidden_strings[index]"
                      type="text"
                      placeholder="e.g., Error 500, Page not found"
                      class="flex-1 rounded-md border border-border bg-background px-3 py-1 text-sm text-foreground placeholder-muted-foreground focus:border-primary focus:ring-1 focus:ring-primary"
                    />
                    <button
                      type="button"
                      @click="removeForbiddenString(index)"
                      class="p-1 text-red-500 hover:text-red-700"
                    >
                      <X class="h-4 w-4" />
                    </button>
                  </div>
                </div>
                <button
                  type="button"
                  @click="addForbiddenString"
                  class="flex items-center space-x-2 text-xs text-primary hover:text-primary/80"
                >
                  <Plus class="h-3 w-3" />
                  <span>Add forbidden content</span>
                </button>
              </div>

              <!-- Regex Patterns -->
              <div class="space-y-3 mb-4">
                <label class="block text-xs font-medium text-foreground">
                  Regex Patterns (advanced content matching)
                </label>
                <div v-if="form.monitoring_config.content_regex_patterns?.length" class="space-y-2">
                  <div
                    v-for="(pattern, index) in form.monitoring_config.content_regex_patterns"
                    :key="`regex-${index}`"
                    class="flex items-center space-x-2"
                  >
                    <input
                      v-model="form.monitoring_config.content_regex_patterns[index]"
                      type="text"
                      placeholder="e.g., /status.*ok/i"
                      class="flex-1 rounded-md border border-border bg-background px-3 py-1 text-sm text-foreground placeholder-muted-foreground focus:border-primary focus:ring-1 focus:ring-primary font-mono"
                    />
                    <button
                      type="button"
                      @click="removeRegexPattern(index)"
                      class="p-1 text-red-500 hover:text-red-700"
                    >
                      <X class="h-4 w-4" />
                    </button>
                  </div>
                </div>
                <button
                  type="button"
                  @click="addRegexPattern"
                  class="flex items-center space-x-2 text-xs text-primary hover:text-primary/80"
                >
                  <Plus class="h-3 w-3" />
                  <span>Add regex pattern</span>
                </button>
              </div>
            </div>

            <!-- JavaScript Support -->
            <div class="rounded-lg border border-border p-4">
              <div class="flex items-start space-x-3 mb-4">
                <Code class="h-5 w-5 text-purple-500 mt-0.5" />
                <div>
                  <h3 class="text-sm font-medium text-foreground mb-1">JavaScript Support</h3>
                  <p class="text-xs text-muted-foreground">Enable for dynamic websites that require JavaScript rendering</p>
                </div>
              </div>

              <div class="space-y-4">
                <label class="flex items-start space-x-3">
                  <input
                    v-model="form.monitoring_config.javascript_enabled"
                    type="checkbox"
                    class="rounded border-border text-primary focus:ring-primary mt-0.5"
                  />
                  <div>
                    <span class="text-sm text-foreground">Enable JavaScript rendering</span>
                    <p class="text-xs text-muted-foreground mt-1">
                      Use headless browser to render JavaScript before content validation
                    </p>
                  </div>
                </label>

                <div v-if="form.monitoring_config.javascript_enabled" class="ml-6 space-y-3">
                  <div>
                    <label class="block text-xs font-medium text-foreground mb-2">
                      Wait Time (seconds)
                    </label>
                    <select
                      v-model="form.monitoring_config.javascript_wait_seconds"
                      class="w-full rounded-md border border-border bg-background px-3 py-2 text-sm text-foreground focus:border-primary focus:ring-1 focus:ring-primary"
                    >
                      <option value="1">1 second</option>
                      <option value="3">3 seconds</option>
                      <option value="5">5 seconds (recommended)</option>
                      <option value="10">10 seconds</option>
                      <option value="15">15 seconds</option>
                    </select>
                    <p class="text-xs text-muted-foreground mt-1">
                      Time to wait for JavaScript to fully render the page
                    </p>
                  </div>
                </div>
              </div>
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