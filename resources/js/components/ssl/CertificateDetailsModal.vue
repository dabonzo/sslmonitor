<template>
  <Dialog :open="isOpen" @update:open="handleClose">
    <DialogContent class="max-w-4xl max-h-[90vh] overflow-hidden">
      <DialogHeader>
        <DialogTitle class="flex items-center space-x-3">
          <div class="rounded-lg bg-blue-100 dark:bg-blue-900/30 p-2">
            <Shield class="h-5 w-5 text-primary dark:text-blue-400" />
          </div>
          <div>
            <div class="text-xl font-bold text-foreground dark:text-foreground">
              Certificate Details
            </div>
            <div class="text-sm text-foreground dark:text-muted-foreground">
              {{ websiteName || 'Loading...' }}
            </div>
          </div>
        </DialogTitle>
      </DialogHeader>

      <div class="overflow-y-auto pr-2" style="max-height: calc(90vh - 120px);">
        <!-- Loading State -->
        <div v-if="isLoading" class="flex items-center justify-center py-12">
          <div class="text-center space-y-4">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
            <p class="text-sm text-foreground dark:text-muted-foreground">Analyzing certificate...</p>
          </div>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="text-center py-12">
          <div class="rounded-lg bg-red-50 dark:bg-red-900/20 p-6 border border-red-200 dark:border-red-800">
            <AlertTriangle class="h-12 w-12 text-destructive dark:text-red-400 mx-auto mb-4" />
            <h3 class="text-lg font-semibold text-red-900 dark:text-red-100 mb-2">Analysis Failed</h3>
            <p class="text-sm text-red-700 dark:text-red-300">{{ error }}</p>
            <Button @click="performAnalysis" variant="outline" class="mt-4">
              <RefreshCw class="h-4 w-4 mr-2" />
              Retry Analysis
            </Button>
          </div>
        </div>

        <!-- Certificate Analysis Results -->
        <div v-else-if="analysis" class="space-y-6">
          <!-- Risk Assessment Banner -->
          <div class="p-4 rounded-lg border" :class="riskBannerClass">
            <div class="flex items-start space-x-3">
              <component :is="riskIcon" :class="riskIconClass" />
              <div class="flex-1">
                <div class="flex items-center justify-between">
                  <h3 class="font-semibold capitalize">{{ analysis.risk_assessment.level }} Risk</h3>
                  <Badge :variant="riskBadgeVariant" class="font-semibold">
                    Security Score: {{ analysis.risk_assessment.score }}/100
                  </Badge>
                </div>
                <div v-if="analysis.risk_assessment.issues.length > 0" class="mt-2 space-y-1">
                  <p v-for="issue in analysis.risk_assessment.issues" :key="issue" class="text-sm">
                    • {{ issue }}
                  </p>
                </div>
                <div v-if="analysis.risk_assessment.recommendations.length > 0" class="mt-3">
                  <p class="text-sm font-medium mb-1">Recommendations:</p>
                  <div class="space-y-1">
                    <p v-for="rec in analysis.risk_assessment.recommendations" :key="rec" class="text-sm">
                      • {{ rec }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Certificate Information Grid -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Basic Information -->
            <Card>
              <CardHeader>
                <CardTitle class="flex items-center space-x-2">
                  <FileText class="h-5 w-5" />
                  <span>Basic Information</span>
                </CardTitle>
              </CardHeader>
              <CardContent class="space-y-3">
                <div class="grid grid-cols-3 gap-2">
                  <span class="text-sm font-medium text-foreground dark:text-muted-foreground">Subject:</span>
                  <span class="col-span-2 text-sm break-all">{{ analysis.basic_info.subject }}</span>
                </div>
                <div class="grid grid-cols-3 gap-2">
                  <span class="text-sm font-medium text-foreground dark:text-muted-foreground">Issuer:</span>
                  <span class="col-span-2 text-sm break-all">{{ analysis.basic_info.issuer }}</span>
                </div>
                <div class="grid grid-cols-3 gap-2">
                  <span class="text-sm font-medium text-foreground dark:text-muted-foreground">Serial:</span>
                  <span class="col-span-2 text-sm font-mono">{{ analysis.basic_info.serial_number }}</span>
                </div>
                <div class="grid grid-cols-3 gap-2">
                  <span class="text-sm font-medium text-foreground dark:text-muted-foreground">Algorithm:</span>
                  <span class="col-span-2 text-sm">{{ analysis.basic_info.signature_algorithm }}</span>
                </div>
                <div class="grid grid-cols-3 gap-2">
                  <span class="text-sm font-medium text-foreground dark:text-muted-foreground">Version:</span>
                  <span class="col-span-2 text-sm">{{ analysis.basic_info.version }}</span>
                </div>
              </CardContent>
            </Card>

            <!-- Validity Information -->
            <Card>
              <CardHeader>
                <CardTitle class="flex items-center space-x-2">
                  <Calendar class="h-5 w-5" />
                  <span>Validity Period</span>
                </CardTitle>
              </CardHeader>
              <CardContent class="space-y-3">
                <div class="grid grid-cols-3 gap-2">
                  <span class="text-sm font-medium text-foreground dark:text-muted-foreground">Valid From:</span>
                  <span class="col-span-2 text-sm">{{ formatDate(analysis.validity.valid_from) }}</span>
                </div>
                <div class="grid grid-cols-3 gap-2">
                  <span class="text-sm font-medium text-foreground dark:text-muted-foreground">Valid Until:</span>
                  <span class="col-span-2 text-sm">{{ formatDate(analysis.validity.valid_until) }}</span>
                </div>
                <div class="grid grid-cols-3 gap-2">
                  <span class="text-sm font-medium text-foreground dark:text-muted-foreground">Days Remaining:</span>
                  <span class="col-span-2">
                    <Badge :variant="daysRemainingVariant" class="text-sm">
                      {{ analysis.validity.days_remaining }} days
                    </Badge>
                  </span>
                </div>
                <div class="grid grid-cols-3 gap-2">
                  <span class="text-sm font-medium text-foreground dark:text-muted-foreground">Status:</span>
                  <span class="col-span-2">
                    <Badge :variant="validityVariant">
                      {{ analysis.validity.is_expired ? 'Expired' : 'Valid' }}
                    </Badge>
                  </span>
                </div>
              </CardContent>
            </Card>

            <!-- Domain Coverage -->
            <Card>
              <CardHeader>
                <CardTitle class="flex items-center space-x-2">
                  <Globe class="h-5 w-5" />
                  <span>Domain Coverage</span>
                </CardTitle>
              </CardHeader>
              <CardContent class="space-y-3">
                <div class="grid grid-cols-3 gap-2">
                  <span class="text-sm font-medium text-foreground dark:text-muted-foreground">Primary:</span>
                  <span class="col-span-2 text-sm break-all">{{ analysis.domains.primary_domain }}</span>
                </div>
                <div v-if="analysis.domains.wildcard_cert" class="grid grid-cols-3 gap-2">
                  <span class="text-sm font-medium text-foreground dark:text-muted-foreground">Type:</span>
                  <span class="col-span-2">
                    <Badge variant="secondary">Wildcard Certificate</Badge>
                  </span>
                </div>
                <div class="grid grid-cols-3 gap-2">
                  <span class="text-sm font-medium text-foreground dark:text-muted-foreground">Coverage:</span>
                  <span class="col-span-2">
                    <Badge :variant="analysis.domains.covers_requested_domain ? 'default' : 'destructive'">
                      {{ analysis.domains.covers_requested_domain ? 'Covers Domain' : 'Domain Mismatch' }}
                    </Badge>
                  </span>
                </div>
                <div v-if="analysis.domains.subject_alt_names.length > 0">
                  <span class="text-sm font-medium text-foreground dark:text-muted-foreground block mb-2">Alternative Names:</span>
                  <div class="flex flex-wrap gap-1">
                    <Badge
                      v-for="san in analysis.domains.subject_alt_names"
                      :key="san"
                      variant="outline"
                      class="text-xs"
                    >
                      {{ san }}
                    </Badge>
                  </div>
                </div>
              </CardContent>
            </Card>

            <!-- Security Details -->
            <Card>
              <CardHeader>
                <CardTitle class="flex items-center space-x-2">
                  <Lock class="h-5 w-5" />
                  <span>Security Details</span>
                </CardTitle>
              </CardHeader>
              <CardContent class="space-y-3">
                <div class="grid grid-cols-3 gap-2">
                  <span class="text-sm font-medium text-foreground dark:text-muted-foreground">Key Algorithm:</span>
                  <span class="col-span-2 text-sm">{{ analysis.security.key_algorithm }}</span>
                </div>
                <div class="grid grid-cols-3 gap-2">
                  <span class="text-sm font-medium text-foreground dark:text-muted-foreground">Key Size:</span>
                  <span class="col-span-2">
                    <span class="text-sm">{{ analysis.security.key_size }} bits</span>
                    <Badge v-if="analysis.security.weak_key" variant="destructive" class="ml-2">Weak</Badge>
                  </span>
                </div>
                <div class="grid grid-cols-3 gap-2">
                  <span class="text-sm font-medium text-foreground dark:text-muted-foreground">Signature:</span>
                  <span class="col-span-2">
                    <span class="text-sm">{{ analysis.security.signature_algorithm }}</span>
                    <Badge v-if="analysis.security.weak_signature" variant="destructive" class="ml-2">Weak</Badge>
                  </span>
                </div>
                <div class="grid grid-cols-3 gap-2">
                  <span class="text-sm font-medium text-foreground dark:text-muted-foreground">Security Score:</span>
                  <span class="col-span-2">
                    <div class="flex items-center space-x-2">
                      <div class="w-20 bg-muted dark:bg-muted rounded-full h-2">
                        <div
                          class="h-2 rounded-full"
                          :class="securityScoreClass"
                          :style="{ width: `${analysis.security.security_score}%` }"
                        ></div>
                      </div>
                      <span class="text-sm font-medium">{{ analysis.security.security_score }}/100</span>
                    </div>
                  </span>
                </div>
              </CardContent>
            </Card>

            <!-- Certificate Authority -->
            <Card>
              <CardHeader>
                <CardTitle class="flex items-center space-x-2">
                  <Building2 class="h-5 w-5" />
                  <span>Certificate Authority</span>
                </CardTitle>
              </CardHeader>
              <CardContent class="space-y-3">
                <div class="grid grid-cols-3 gap-2">
                  <span class="text-sm font-medium text-foreground dark:text-muted-foreground">CA Name:</span>
                  <span class="col-span-2 text-sm">{{ analysis.certificate_authority.ca_name }}</span>
                </div>
                <div class="grid grid-cols-3 gap-2">
                  <span class="text-sm font-medium text-foreground dark:text-muted-foreground">Organization:</span>
                  <span class="col-span-2 text-sm">{{ analysis.certificate_authority.ca_organization }}</span>
                </div>
                <div class="grid grid-cols-3 gap-2">
                  <span class="text-sm font-medium text-foreground dark:text-muted-foreground">Type:</span>
                  <span class="col-span-2">
                    <Badge :variant="analysis.certificate_authority.is_lets_encrypt ? 'secondary' : 'outline'">
                      {{ analysis.certificate_authority.is_lets_encrypt ? 'Let\'s Encrypt (Free)' : 'Commercial CA' }}
                    </Badge>
                  </span>
                </div>
              </CardContent>
            </Card>

            <!-- Chain Information -->
            <Card>
              <CardHeader>
                <CardTitle class="flex items-center space-x-2">
                  <Link2 class="h-5 w-5" />
                  <span>Certificate Chain</span>
                </CardTitle>
              </CardHeader>
              <CardContent class="space-y-3">
                <div class="grid grid-cols-3 gap-2">
                  <span class="text-sm font-medium text-foreground dark:text-muted-foreground">Chain Length:</span>
                  <span class="col-span-2 text-sm">{{ analysis.chain_info.length }}</span>
                </div>
                <div class="grid grid-cols-3 gap-2">
                  <span class="text-sm font-medium text-foreground dark:text-muted-foreground">Trusted Root:</span>
                  <span class="col-span-2">
                    <Badge :variant="analysis.chain_info.trusted_root ? 'default' : 'destructive'">
                      {{ analysis.chain_info.trusted_root ? 'Trusted' : 'Untrusted' }}
                    </Badge>
                  </span>
                </div>
                <div class="grid grid-cols-3 gap-2">
                  <span class="text-sm font-medium text-foreground dark:text-muted-foreground">Chain Valid:</span>
                  <span class="col-span-2">
                    <Badge :variant="analysis.chain_info.chain_valid ? 'default' : 'destructive'">
                      {{ analysis.chain_info.chain_valid ? 'Valid' : 'Invalid' }}
                    </Badge>
                  </span>
                </div>
              </CardContent>
            </Card>
          </div>

          <!-- Action Buttons -->
          <div class="flex justify-end space-x-3 pt-4 border-t">
            <Button @click="downloadReport" variant="outline" class="flex items-center space-x-2">
              <Download class="h-4 w-4" />
              <span>Download Report</span>
            </Button>
            <Button @click="performAnalysis" variant="outline" class="flex items-center space-x-2">
              <RefreshCw class="h-4 w-4" />
              <span>Reanalyze</span>
            </Button>
          </div>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Shield,
  AlertTriangle,
  CheckCircle,
  RefreshCw,
  FileText,
  Calendar,
  Globe,
  Lock,
  Building2,
  Link2,
  Download,
  AlertCircle,
  XCircle
} from 'lucide-vue-next';

interface CertificateAnalysis {
  basic_info: {
    subject: string;
    issuer: string;
    serial_number: string;
    signature_algorithm: string;
    version: number;
  };
  validity: {
    valid_from: string;
    valid_until: string;
    days_remaining: number;
    is_expired: boolean;
    expires_soon: boolean;
  };
  domains: {
    primary_domain: string;
    subject_alt_names: string[];
    wildcard_cert: boolean;
    covers_requested_domain: boolean;
  };
  security: {
    key_algorithm: string;
    key_size: number;
    signature_algorithm: string;
    weak_signature: boolean;
    weak_key: boolean;
    security_score: number;
  };
  certificate_authority: {
    is_lets_encrypt: boolean;
    ca_name: string;
    ca_organization: string;
    ca_country: string;
  };
  chain_info: {
    length: number;
    trusted_root: boolean;
    intermediate_cas: string[];
    chain_valid: boolean;
  };
  risk_assessment: {
    level: 'low' | 'medium' | 'high' | 'critical';
    score: number;
    issues: string[];
    recommendations: string[];
  };
}

interface Props {
  isOpen: boolean;
  websiteId?: number | null;
  websiteName?: string;
  websiteUrl?: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  close: [];
}>();

const isLoading = ref(false);
const analysis = ref<CertificateAnalysis | null>(null);
const error = ref<string>('');

// Watch for modal opening
watch(() => props.isOpen, (newValue) => {
  if (newValue && props.websiteId) {
    performAnalysis();
  }
});

const performAnalysis = async () => {
  if (!props.websiteId) {
    error.value = 'Website ID is required for analysis';
    return;
  }

  isLoading.value = true;
  error.value = '';
  analysis.value = null;

  try {
    const response = await fetch(`/ssl/websites/${props.websiteId}/certificate-analysis`);

    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(errorData.message || 'Analysis failed');
    }

    const data = await response.json();
    analysis.value = data.analysis;
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Analysis failed';
  } finally {
    isLoading.value = false;
  }
};

const handleClose = () => {
  emit('close');
  // Reset state when closing
  setTimeout(() => {
    analysis.value = null;
    error.value = '';
    isLoading.value = false;
  }, 200);
};

// Risk assessment styling
const riskBannerClass = computed(() => {
  if (!analysis.value) return '';

  switch (analysis.value.risk_assessment.level) {
    case 'critical':
      return 'bg-red-50 border-red-200 text-red-900 dark:bg-red-900/20 dark:border-red-800 dark:text-red-100';
    case 'high':
      return 'bg-orange-50 border-orange-200 text-orange-900 dark:bg-orange-900/20 dark:border-orange-800 dark:text-orange-100';
    case 'medium':
      return 'bg-yellow-50 border-yellow-200 text-yellow-900 dark:bg-yellow-900/20 dark:border-yellow-800 dark:text-yellow-100';
    default:
      return 'bg-green-50 border-green-200 text-green-900 dark:bg-green-900/20 dark:border-green-800 dark:text-green-100';
  }
});

const riskIcon = computed(() => {
  if (!analysis.value) return AlertCircle;

  switch (analysis.value.risk_assessment.level) {
    case 'critical':
      return XCircle;
    case 'high':
      return AlertTriangle;
    case 'medium':
      return AlertCircle;
    default:
      return CheckCircle;
  }
});

const riskIconClass = computed(() => {
  if (!analysis.value) return 'h-6 w-6';

  switch (analysis.value.risk_assessment.level) {
    case 'critical':
      return 'h-6 w-6 text-destructive dark:text-red-400';
    case 'high':
      return 'h-6 w-6 text-orange-600 dark:text-orange-400';
    case 'medium':
      return 'h-6 w-6 text-yellow-600 dark:text-yellow-400';
    default:
      return 'h-6 w-6 text-green-600 dark:text-green-400';
  }
});

const riskBadgeVariant = computed(() => {
  if (!analysis.value) return 'secondary';

  switch (analysis.value.risk_assessment.level) {
    case 'critical':
      return 'destructive';
    case 'high':
      return 'destructive';
    case 'medium':
      return 'secondary';
    default:
      return 'default';
  }
});

const daysRemainingVariant = computed(() => {
  if (!analysis.value) return 'secondary';

  const days = analysis.value.validity.days_remaining;
  if (days <= 0) return 'destructive';
  if (days <= 7) return 'destructive';
  if (days <= 30) return 'secondary';
  return 'default';
});

const validityVariant = computed(() => {
  return analysis.value?.validity.is_expired ? 'destructive' : 'default';
});

const securityScoreClass = computed(() => {
  if (!analysis.value) return 'bg-gray-400';

  const score = analysis.value.security.security_score;
  if (score >= 80) return 'bg-green-500';
  if (score >= 60) return 'bg-yellow-500';
  return 'bg-red-500';
});

const formatDate = (isoString: string) => {
  return new Date(isoString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const downloadReport = () => {
  if (!analysis.value) return;

  const reportData = {
    website: {
      name: props.websiteName,
      url: props.websiteUrl
    },
    analysis: analysis.value,
    generated_at: new Date().toISOString()
  };

  const blob = new Blob([JSON.stringify(reportData, null, 2)], {
    type: 'application/json'
  });

  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `certificate-analysis-${props.websiteName || 'report'}.json`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
};
</script>