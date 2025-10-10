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
  HelpCircle,
  Plus,
  X,
  FileText,
  Code,
  Zap
} from 'lucide-vue-next';
import ssl from '@/routes/ssl';

interface MonitoringConfig {
  timeout: number;
  description?: string;
  check_interval?: number;
  content_expected_strings?: string[];
  content_forbidden_strings?: string[];
  content_regex_patterns?: string[];
  javascript_enabled?: boolean;
  javascript_wait_seconds?: number;
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
    timeout: props.website.monitoring_config?.timeout || 30,
    description: props.website.monitoring_config?.description || '',
    check_interval: props.website.monitoring_config?.check_interval || 300,
    content_expected_strings: props.website.monitoring_config?.content_expected_strings || [],
    content_forbidden_strings: props.website.monitoring_config?.content_forbidden_strings || [],
    content_regex_patterns: props.website.monitoring_config?.content_regex_patterns || [],
    javascript_enabled: props.website.monitoring_config?.javascript_enabled || false,
    javascript_wait_seconds: props.website.monitoring_config?.javascript_wait_seconds || 5
  }
});

// Auto-expand content validation if already configured
const showContentValidation = ref(
  (props.website.monitoring_config?.content_expected_strings?.length ?? 0) > 0 ||
  (props.website.monitoring_config?.content_forbidden_strings?.length ?? 0) > 0 ||
  (props.website.monitoring_config?.content_regex_patterns?.length ?? 0) > 0 ||
  props.website.monitoring_config?.javascript_enabled === true
);

// Timeout options in seconds
const timeoutOptions = [
  { label: '10 seconds', value: 10 },
  { label: '15 seconds', value: 15 },
  { label: '30 seconds', value: 30 },
  { label: '60 seconds', value: 60 },
  { label: '90 seconds', value: 90 }
];

const selectedTimeoutLabel = computed(() => {
  const option = timeoutOptions.find(opt => opt.value === form.monitoring_config.timeout);
  return option?.label || 'Custom';
});

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
}

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

              <div v-if="form.ssl_monitoring_enabled" class="space-y-4">
                <div class="pl-8 space-y-2">
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

                <!-- SSL Monitoring Schedule Info -->
                <div class="ml-8 rounded-lg border border-border/50 bg-blue-500/5 p-3">
                  <div class="flex items-start space-x-2">
                    <Clock class="h-4 w-4 text-blue-500 mt-0.5 flex-shrink-0" />
                    <div class="space-y-1.5 text-xs text-muted-foreground">
                      <div class="flex items-center space-x-2">
                        <CheckCircle class="h-3 w-3 text-green-500" />
                        <span>SSL checks: Twice daily (6 AM & 6 PM)</span>
                      </div>
                      <div class="flex items-center space-x-2">
                        <HelpCircle class="h-3 w-3 text-blue-500" />
                        <span>Automatic scheduling by the system</span>
                      </div>
                    </div>
                  </div>
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

              <div v-if="form.uptime_monitoring_enabled" class="space-y-4">
                <!-- Basic Features -->
                <div class="pl-8 space-y-2">
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

                <!-- Check Interval Configuration -->
                <div class="ml-8 space-y-2">
                  <label class="block text-xs font-medium text-foreground">
                    Check Interval
                  </label>
                  <select
                    v-model.number="form.monitoring_config.check_interval"
                    class="w-full rounded-md border border-border bg-background px-3 py-2 text-sm text-foreground focus:border-primary focus:ring-1 focus:ring-primary"
                  >
                    <option :value="60">Every minute</option>
                    <option :value="120">Every 2 minutes</option>
                    <option :value="300">Every 5 minutes (recommended)</option>
                    <option :value="600">Every 10 minutes</option>
                    <option :value="900">Every 15 minutes</option>
                    <option :value="1800">Every 30 minutes</option>
                    <option :value="3600">Every hour</option>
                  </select>
                  <p class="text-xs text-muted-foreground">
                    How often should we check if this website is up
                  </p>
                </div>

                <!-- Content Validation Section -->
                <div class="ml-8 rounded-lg border border-border/50 bg-muted/20 p-4">
                  <button
                    type="button"
                    @click="showContentValidation = !showContentValidation"
                    class="flex items-center justify-between w-full text-left"
                  >
                    <div class="flex items-center space-x-3">
                      <FileText class="h-4 w-4 text-orange-500" />
                      <div>
                        <h4 class="text-sm font-medium text-foreground">Content Validation</h4>
                        <p class="text-xs text-muted-foreground">Detect error pages and unexpected content</p>
                      </div>
                    </div>
                    <Settings class="h-4 w-4 text-muted-foreground transition-transform duration-200" :class="{ 'rotate-90': showContentValidation }" />
                  </button>

                  <div v-if="showContentValidation" class="mt-4 space-y-4">
                    <!-- Expected Content -->
                    <div class="space-y-3">
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
                    <div class="space-y-3">
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
                    <div class="space-y-3">
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

                    <!-- JavaScript Rendering (inside Content Validation) -->
                    <div class="pt-4 border-t border-border/50">
                      <div class="flex items-start space-x-3 mb-3">
                        <Code class="h-4 w-4 text-purple-500 mt-0.5" />
                        <div class="flex-1">
                          <h5 class="text-xs font-medium text-foreground mb-1">JavaScript Rendering</h5>
                          <p class="text-xs text-muted-foreground">Enable for dynamic websites that load content via JavaScript</p>
                        </div>
                      </div>

                      <div class="space-y-3">
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

                <!-- Monitoring Schedule Info -->
                <div class="ml-8 rounded-lg border border-border/50 bg-blue-500/5 p-3">
                  <div class="flex items-start space-x-2">
                    <Clock class="h-4 w-4 text-blue-500 mt-0.5 flex-shrink-0" />
                    <div class="space-y-1.5 text-xs text-muted-foreground">
                      <div class="flex items-center space-x-2">
                        <CheckCircle class="h-3 w-3 text-green-500" />
                        <span>Uptime checks: Every 5 minutes</span>
                      </div>
                      <div class="flex items-center space-x-2">
                        <HelpCircle class="h-3 w-3 text-blue-500" />
                        <span>Automatic scheduling by the system</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Advanced Options -->
        <div class="rounded-lg bg-card text-card-foreground p-6 shadow-sm">
          <h2 class="text-lg font-semibold text-foreground mb-4">Advanced Options</h2>

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