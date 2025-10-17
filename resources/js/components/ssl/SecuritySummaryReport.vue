<template>
  <div class="space-y-6">
    <!-- Loading State -->
    <div v-if="isLoading" class="flex items-center justify-center py-8">
      <div class="text-center space-y-4">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
        <p class="text-sm text-foreground dark:text-muted-foreground">Generating security summary...</p>
      </div>
    </div>

    <div v-else class="space-y-6">
      <!-- Overall Security Score -->
      <div class="text-center p-6 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg border">
        <div class="flex justify-center mb-4">
          <div class="relative w-32 h-32">
            <svg class="w-32 h-32 transform -rotate-90" viewBox="0 0 36 36">
              <!-- Background circle -->
              <path
                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                class="text-muted-foreground dark:text-foreground"
              />
              <!-- Progress circle -->
              <path
                d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                :stroke-dasharray="`${overallScore}, 100`"
                :class="overallScoreColorClass"
              />
            </svg>
            <div class="absolute inset-0 flex items-center justify-center">
              <div class="text-center">
                <div class="text-3xl font-bold text-foreground dark:text-foreground">{{ overallScore }}</div>
                <div class="text-xs text-foreground dark:text-muted-foreground">Overall</div>
              </div>
            </div>
          </div>
        </div>
        <h3 class="text-xl font-bold text-foreground dark:text-foreground mb-2">Security Summary</h3>
        <p class="text-sm text-foreground dark:text-muted-foreground">
          Analysis of {{ websites.length }} websites
        </p>
      </div>

      <!-- Security Metrics Grid -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <Card>
          <CardContent class="p-4 text-center">
            <Shield class="h-8 w-8 mx-auto mb-2 text-green-600 dark:text-green-400" />
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ securityMetrics.secure }}</div>
            <div class="text-sm text-foreground dark:text-muted-foreground">Secure</div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="p-4 text-center">
            <AlertTriangle class="h-8 w-8 mx-auto mb-2 text-yellow-600 dark:text-yellow-400" />
            <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ securityMetrics.warnings }}</div>
            <div class="text-sm text-foreground dark:text-muted-foreground">Warnings</div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="p-4 text-center">
            <XCircle class="h-8 w-8 mx-auto mb-2 text-destructive dark:text-red-400" />
            <div class="text-2xl font-bold text-destructive dark:text-red-400">{{ securityMetrics.critical }}</div>
            <div class="text-sm text-foreground dark:text-muted-foreground">Critical</div>
          </CardContent>
        </Card>
        <Card>
          <CardContent class="p-4 text-center">
            <Zap class="h-8 w-8 mx-auto mb-2 text-primary dark:text-blue-400" />
            <div class="text-2xl font-bold text-primary dark:text-blue-400">{{ securityMetrics.letsEncrypt }}</div>
            <div class="text-sm text-foreground dark:text-muted-foreground">Let's Encrypt</div>
          </CardContent>
        </Card>
      </div>

      <!-- Risk Categories -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Critical Issues -->
        <Card v-if="criticalIssues.length > 0">
          <CardHeader>
            <CardTitle class="flex items-center space-x-2 text-destructive dark:text-red-400">
              <AlertTriangle class="h-5 w-5" />
              <span>Critical Issues</span>
            </CardTitle>
          </CardHeader>
          <CardContent class="space-y-3">
            <div
              v-for="issue in criticalIssues"
              :key="`${issue.website_id}-${issue.type}`"
              class="p-3 bg-red-50 dark:bg-red-900/20 rounded border border-red-200 dark:border-red-800"
            >
              <div class="font-medium text-red-900 dark:text-red-100">{{ issue.website_name }}</div>
              <div class="text-sm text-red-700 dark:text-red-300">{{ issue.description }}</div>
              <div class="text-xs text-destructive dark:text-red-400 mt-1">{{ issue.recommendation }}</div>
            </div>
          </CardContent>
        </Card>

        <!-- Expiring Soon -->
        <Card v-if="expiringSoon.length > 0">
          <CardHeader>
            <CardTitle class="flex items-center space-x-2 text-yellow-600 dark:text-yellow-400">
              <Clock class="h-5 w-5" />
              <span>Expiring Soon</span>
            </CardTitle>
          </CardHeader>
          <CardContent class="space-y-3">
            <div
              v-for="cert in expiringSoon"
              :key="cert.website_id"
              class="p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded border border-yellow-200 dark:border-yellow-800"
            >
              <div class="flex justify-between items-start">
                <div>
                  <div class="font-medium text-yellow-900 dark:text-yellow-100">{{ cert.website_name }}</div>
                  <div class="text-sm text-yellow-700 dark:text-yellow-300">{{ cert.days_remaining }} days remaining</div>
                </div>
                <Badge :variant="cert.days_remaining <= 7 ? 'destructive' : 'secondary'">
                  {{ cert.urgency_level }}
                </Badge>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Security Recommendations -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center space-x-2 text-primary dark:text-blue-400">
              <Lightbulb class="h-5 w-5" />
              <span>Recommendations</span>
            </CardTitle>
          </CardHeader>
          <CardContent class="space-y-3">
            <div
              v-for="(recommendation, index) in securityRecommendations"
              :key="index"
              class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded border border-blue-200 dark:border-blue-800"
            >
              <div class="font-medium text-blue-900 dark:text-blue-100">{{ recommendation.title }}</div>
              <div class="text-sm text-blue-700 dark:text-blue-300">{{ recommendation.description }}</div>
              <Badge variant="outline" class="mt-2">{{ recommendation.affected_count }} websites affected</Badge>
            </div>
          </CardContent>
        </Card>

        <!-- Certificate Authorities -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center space-x-2 text-purple-600 dark:text-purple-400">
              <Building2 class="h-5 w-5" />
              <span>Certificate Authorities</span>
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-3">
              <div
                v-for="ca in certificateAuthorities"
                :key="ca.name"
                class="flex justify-between items-center p-2 rounded border"
              >
                <div>
                  <div class="font-medium">{{ ca.name }}</div>
                  <div class="text-sm text-foreground dark:text-muted-foreground">{{ ca.type }}</div>
                </div>
                <div class="text-right">
                  <div class="font-semibold">{{ ca.count }}</div>
                  <div class="text-xs text-foreground dark:text-muted-foreground">
                    {{ Math.round((ca.count / websites.length) * 100) }}%
                  </div>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Detailed Website Analysis -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center justify-between">
            <span class="flex items-center space-x-2">
              <List class="h-5 w-5" />
              <span>Detailed Analysis</span>
            </span>
            <Button @click="exportDetailedReport" variant="outline" size="sm">
              <Download class="h-4 w-4 mr-2" />
              Export Detailed Report
            </Button>
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="space-y-3">
            <div
              v-for="website in websites"
              :key="website.id"
              class="flex justify-between items-center p-3 rounded border hover:bg-muted dark:hover:bg-gray-800 transition-colors"
            >
              <div class="flex-1">
                <div class="font-medium">{{ website.name }}</div>
                <div class="text-sm text-foreground dark:text-muted-foreground">{{ website.url }}</div>
              </div>
              <div class="flex items-center space-x-3">
                <Badge :variant="getWebsiteStatusVariant(website)">
                  {{ getWebsiteStatusLabel(website) }}
                </Badge>
                <div class="text-right">
                  <div class="font-semibold">{{ getWebsiteSecurityScore(website) }}/100</div>
                  <div class="text-xs text-foreground dark:text-muted-foreground">Security Score</div>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Action Buttons -->
      <div class="flex justify-end space-x-3 pt-4 border-t">
        <Button @click="$emit('close')" variant="outline">Close</Button>
        <Button @click="generateFullReport" class="flex items-center space-x-2">
          <FileBarChart class="h-4 w-4" />
          <span>Generate Full Report</span>
        </Button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Shield,
  AlertTriangle,
  XCircle,
  Zap,
  Clock,
  Lightbulb,
  Building2,
  List,
  Download,
  FileBarChart
} from 'lucide-vue-next';

interface Website {
  id: number;
  name: string;
  url: string;
  ssl_status?: string;
  ssl_monitoring_enabled: boolean;
  ssl_urgency_level?: string;
  team_id?: number;
}

interface Props {
  websites: Website[];
  isLoading: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  close: [];
}>();

// Mock data - in real implementation, this would come from API
const securityAnalysisData = ref({
  overall_score: 78,
  critical_issues: [
    {
      website_id: 1,
      website_name: 'Example Site 1',
      type: 'expired_certificate',
      description: 'SSL certificate has expired',
      recommendation: 'Renew SSL certificate immediately'
    }
  ],
  expiring_certificates: [
    {
      website_id: 2,
      website_name: 'Example Site 2',
      days_remaining: 5,
      urgency_level: 'critical'
    }
  ],
  certificate_authorities: [
    { name: "Let's Encrypt", type: 'Free', count: 12 },
    { name: 'DigiCert', type: 'Commercial', count: 3 },
    { name: 'Cloudflare', type: 'CDN', count: 2 }
  ],
  security_recommendations: [
    {
      title: 'Enable SSL Monitoring',
      description: 'Several websites do not have SSL monitoring enabled',
      affected_count: 5
    },
    {
      title: 'Update Certificate Authority',
      description: 'Consider migrating to more secure certificate authorities',
      affected_count: 2
    }
  ]
});

onMounted(async () => {
  if (!props.isLoading) {
    // In real implementation, load actual security analysis data
    await loadSecurityAnalysis();
  }
});

const loadSecurityAnalysis = async () => {
  // Mock implementation - would call actual API
  await new Promise(resolve => setTimeout(resolve, 1000));
};

const overallScore = computed(() => {
  return securityAnalysisData.value.overall_score;
});

const overallScoreColorClass = computed(() => {
  const score = overallScore.value;
  if (score >= 80) return 'text-green-500';
  if (score >= 60) return 'text-yellow-500';
  return 'text-red-500';
});

const securityMetrics = computed(() => {
  const secure = props.websites.filter(w =>
    w.ssl_status === 'valid' && w.ssl_urgency_level === 'safe'
  ).length;

  const warnings = props.websites.filter(w =>
    w.ssl_urgency_level === 'warning' || w.ssl_urgency_level === 'urgent'
  ).length;

  const critical = props.websites.filter(w =>
    w.ssl_status === 'expired' || w.ssl_urgency_level === 'critical'
  ).length;

  // Mock Let's Encrypt count
  const letsEncrypt = Math.floor(props.websites.length * 0.7);

  return { secure, warnings, critical, letsEncrypt };
});

const criticalIssues = computed(() => {
  return securityAnalysisData.value.critical_issues;
});

const expiringSoon = computed(() => {
  return securityAnalysisData.value.expiring_certificates;
});

const certificateAuthorities = computed(() => {
  return securityAnalysisData.value.certificate_authorities;
});

const securityRecommendations = computed(() => {
  return securityAnalysisData.value.security_recommendations;
});

const getWebsiteStatusVariant = (website: Website) => {
  if (website.ssl_status === 'expired') return 'destructive';
  if (website.ssl_urgency_level === 'critical') return 'destructive';
  if (website.ssl_urgency_level === 'warning' || website.ssl_urgency_level === 'urgent') return 'secondary';
  if (website.ssl_status === 'valid') return 'default';
  return 'outline';
};

const getWebsiteStatusLabel = (website: Website) => {
  if (website.ssl_status === 'expired') return 'Expired';
  if (website.ssl_urgency_level === 'critical') return 'Critical';
  if (website.ssl_urgency_level === 'urgent') return 'Urgent';
  if (website.ssl_urgency_level === 'warning') return 'Warning';
  if (website.ssl_status === 'valid') return 'Secure';
  return 'Unknown';
};

const getWebsiteSecurityScore = (website: Website) => {
  // Mock security score calculation
  if (website.ssl_status === 'expired') return 0;
  if (website.ssl_urgency_level === 'critical') return 25;
  if (website.ssl_urgency_level === 'urgent') return 50;
  if (website.ssl_urgency_level === 'warning') return 70;
  if (website.ssl_status === 'valid') return 95;
  return 60;
};

const exportDetailedReport = () => {
  const detailedReport = {
    summary: {
      total_websites: props.websites.length,
      overall_security_score: overallScore.value,
      metrics: securityMetrics.value
    },
    websites: props.websites.map(website => ({
      id: website.id,
      name: website.name,
      url: website.url,
      ssl_status: website.ssl_status,
      ssl_monitoring_enabled: website.ssl_monitoring_enabled,
      ssl_urgency_level: website.ssl_urgency_level,
      security_score: getWebsiteSecurityScore(website),
      status_label: getWebsiteStatusLabel(website)
    })),
    critical_issues: criticalIssues.value,
    expiring_certificates: expiringSoon.value,
    certificate_authorities: certificateAuthorities.value,
    recommendations: securityRecommendations.value,
    generated_at: new Date().toISOString()
  };

  const blob = new Blob([JSON.stringify(detailedReport, null, 2)], {
    type: 'application/json'
  });

  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `security-summary-${new Date().toISOString().split('T')[0]}.json`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
};

const generateFullReport = () => {
  // This would generate a comprehensive PDF or detailed HTML report
  alert('Full report generation would be implemented here');
};
</script>