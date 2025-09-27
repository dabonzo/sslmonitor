<template>
  <div class="space-y-6">
    <!-- Security Insights Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
      <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Security Insights</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">SSL security analysis and recommendations</p>
      </div>

      <div class="flex space-x-4">
        <Button @click="scanAllCertificates" size="sm" :disabled="isScanning">
          <Shield class="h-4 w-4 mr-2" :class="{ 'animate-pulse': isScanning }" />
          {{ isScanning ? 'Scanning...' : 'Security Scan' }}
        </Button>
        <Button @click="generateSecurityReport" size="sm" variant="outline">
          <FileText class="h-4 w-4 mr-2" />
          Generate Report
        </Button>
      </div>
    </div>

    <!-- Security Score Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <Card>
        <CardContent class="p-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Overall Security Score</p>
              <p class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                {{ securityOverview.overallScore }}<span class="text-lg text-gray-500">/100</span>
              </p>
            </div>
            <div class="relative w-16 h-16">
              <svg class="w-16 h-16 transform -rotate-90" viewBox="0 0 36 36">
                <path
                  class="text-gray-300 dark:text-gray-700"
                  stroke="currentColor"
                  stroke-width="3"
                  fill="none"
                  d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                />
                <path
                  :class="getScoreColor(securityOverview.overallScore)"
                  stroke="currentColor"
                  stroke-width="3"
                  stroke-linecap="round"
                  fill="none"
                  :stroke-dasharray="`${securityOverview.overallScore}, 100`"
                  d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                />
              </svg>
            </div>
          </div>
          <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">
            {{ getScoreDescription(securityOverview.overallScore) }}
          </p>
        </CardContent>
      </Card>

      <Card>
        <CardContent class="p-4">
          <div class="flex items-center space-x-3">
            <div class="rounded-lg bg-green-100 dark:bg-green-900/30 p-2">
              <CheckCircle class="h-5 w-5 text-green-600 dark:text-green-400" />
            </div>
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Secure Certificates</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                {{ securityOverview.secureCerts }}
              </p>
              <p class="text-xs text-gray-600 dark:text-gray-400">
                {{ ((securityOverview.secureCerts / securityOverview.totalCerts) * 100).toFixed(1) }}% of total
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardContent class="p-4">
          <div class="flex items-center space-x-3">
            <div class="rounded-lg bg-orange-100 dark:bg-orange-900/30 p-2">
              <AlertTriangle class="h-5 w-5 text-orange-600 dark:text-orange-400" />
            </div>
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Vulnerabilities</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                {{ securityOverview.vulnerabilities }}
              </p>
              <p class="text-xs text-gray-600 dark:text-gray-400">
                {{ securityOverview.criticalVulns }} critical
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardContent class="p-4">
          <div class="flex items-center space-x-3">
            <div class="rounded-lg bg-red-100 dark:bg-red-900/30 p-2">
              <XCircle class="h-5 w-5 text-red-600 dark:text-red-400" />
            </div>
            <div>
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Expired/Invalid</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                {{ securityOverview.expiredCerts }}
              </p>
              <p class="text-xs text-gray-600 dark:text-gray-400">
                Immediate attention needed
              </p>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Security Issues Priority List -->
    <Card>
      <CardHeader>
        <CardTitle class="flex items-center space-x-2">
          <AlertTriangle class="h-5 w-5 text-red-600 dark:text-red-400" />
          <span>Priority Security Issues</span>
        </CardTitle>
        <CardDescription>
          Critical security issues that require immediate attention
        </CardDescription>
      </CardHeader>
      <CardContent>
        <div v-if="priorityIssues.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
          <CheckCircle class="h-12 w-12 mx-auto mb-2 text-green-500" />
          <p class="font-medium">No critical security issues found</p>
          <p class="text-sm mt-1">All certificates meet security standards</p>
        </div>
        <div v-else class="space-y-4">
          <div
            v-for="issue in priorityIssues"
            :key="issue.id"
            class="p-4 rounded-lg border-l-4"
            :class="getIssueBorderClass(issue.severity)"
          >
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <div class="flex items-center space-x-2 mb-2">
                  <Badge :variant="getSeverityVariant(issue.severity)">
                    {{ issue.severity.toUpperCase() }}
                  </Badge>
                  <h4 class="font-medium text-gray-900 dark:text-gray-100">{{ issue.title }}</h4>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ issue.description }}</p>
                <div class="text-xs text-gray-500 dark:text-gray-500">
                  Affected: {{ issue.affectedSites.length }} website{{ issue.affectedSites.length !== 1 ? 's' : '' }}
                  | Impact: {{ issue.impact }}
                </div>
              </div>
              <Button @click="fixIssue(issue)" size="sm" :variant="issue.severity === 'critical' ? 'destructive' : 'outline'">
                <Wrench class="h-4 w-4 mr-2" />
                {{ issue.autoFixable ? 'Auto Fix' : 'Review' }}
              </Button>
            </div>

            <!-- Affected Sites -->
            <div v-if="issue.affectedSites.length > 0" class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
              <div class="flex flex-wrap gap-2">
                <Badge
                  v-for="site in issue.affectedSites.slice(0, 3)"
                  :key="site"
                  variant="outline"
                  class="text-xs"
                >
                  {{ site }}
                </Badge>
                <Badge v-if="issue.affectedSites.length > 3" variant="outline" class="text-xs">
                  +{{ issue.affectedSites.length - 3 }} more
                </Badge>
              </div>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Security Metrics Analysis -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Certificate Authority Distribution -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center space-x-2">
            <Building class="h-5 w-5" />
            <span>Certificate Authorities</span>
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <div v-for="ca in certificateAuthorities" :key="ca.name">
              <div class="flex items-center justify-between mb-2">
                <div class="flex items-center space-x-2">
                  <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: ca.color }"></div>
                  <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ ca.name }}</span>
                </div>
                <div class="flex items-center space-x-2">
                  <span class="text-sm text-gray-600 dark:text-gray-400">{{ ca.count }}</span>
                  <Badge :variant="ca.trustScore >= 90 ? 'default' : ca.trustScore >= 80 ? 'secondary' : 'destructive'" class="text-xs">
                    Trust: {{ ca.trustScore }}%
                  </Badge>
                </div>
              </div>
              <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div
                  class="h-2 rounded-full transition-all duration-300"
                  :style="{
                    width: `${ca.percentage}%`,
                    backgroundColor: ca.color
                  }"
                ></div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Encryption Strength Analysis -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center space-x-2">
            <Lock class="h-5 w-5" />
            <span>Encryption Strength</span>
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <div v-for="encryption in encryptionAnalysis" :key="encryption.type">
              <div class="flex items-center justify-between mb-2">
                <div class="flex items-center space-x-2">
                  <component :is="getEncryptionIcon(encryption.strength)" class="h-4 w-4" :class="getEncryptionIconClass(encryption.strength)" />
                  <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ encryption.type }}</span>
                </div>
                <div class="flex items-center space-x-2">
                  <span class="text-sm text-gray-600 dark:text-gray-400">{{ encryption.count }} certs</span>
                  <Badge :variant="getStrengthVariant(encryption.strength)">
                    {{ encryption.strength }}
                  </Badge>
                </div>
              </div>
              <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div
                  class="h-2 rounded-full transition-all duration-300"
                  :class="getStrengthBarClass(encryption.strength)"
                  :style="{ width: `${encryption.percentage}%` }"
                ></div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Compliance Status -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center space-x-2">
            <Award class="h-5 w-5" />
            <span>Compliance Status</span>
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <div v-for="compliance in complianceStatus" :key="compliance.standard">
              <div class="flex items-center justify-between">
                <div>
                  <h4 class="font-medium text-gray-900 dark:text-gray-100">{{ compliance.standard }}</h4>
                  <p class="text-sm text-gray-600 dark:text-gray-400">{{ compliance.description }}</p>
                </div>
                <Badge :variant="compliance.compliant ? 'default' : 'destructive'">
                  {{ compliance.compliant ? 'Compliant' : 'Non-Compliant' }}
                </Badge>
              </div>
              <div class="mt-2 text-xs text-gray-500 dark:text-gray-500">
                {{ compliance.compliantSites }}/{{ compliance.totalSites }} websites compliant
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Recommendations -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center space-x-2">
            <Lightbulb class="h-5 w-5" />
            <span>Security Recommendations</span>
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <div v-for="recommendation in securityRecommendations" :key="recommendation.id" class="p-3 rounded-lg border">
              <div class="flex items-start space-x-3">
                <div class="rounded-full p-1" :class="getRecommendationBgClass(recommendation.priority)">
                  <component :is="getRecommendationIcon(recommendation.type)" class="h-4 w-4" :class="getRecommendationIconClass(recommendation.priority)" />
                </div>
                <div class="flex-1">
                  <h4 class="font-medium text-gray-900 dark:text-gray-100">{{ recommendation.title }}</h4>
                  <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ recommendation.description }}</p>
                  <div class="flex items-center space-x-2 mt-2">
                    <Badge :variant="getPriorityVariant(recommendation.priority)" class="text-xs">
                      {{ recommendation.priority }}
                    </Badge>
                    <span class="text-xs text-gray-500 dark:text-gray-500">
                      Impact: {{ recommendation.impact }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Shield,
  FileText,
  CheckCircle,
  AlertTriangle,
  XCircle,
  Wrench,
  Building,
  Lock,
  Award,
  Lightbulb,
  ShieldCheck,
  ShieldAlert,
  ShieldX,
  Settings,
  Zap,
  Target
} from 'lucide-vue-next';

interface SecurityOverview {
  overallScore: number;
  secureCerts: number;
  totalCerts: number;
  vulnerabilities: number;
  criticalVulns: number;
  expiredCerts: number;
}

interface PriorityIssue {
  id: string;
  title: string;
  description: string;
  severity: 'critical' | 'high' | 'medium' | 'low';
  affectedSites: string[];
  impact: string;
  autoFixable: boolean;
}

interface CertificateAuthority {
  name: string;
  count: number;
  percentage: number;
  trustScore: number;
  color: string;
}

interface EncryptionAnalysis {
  type: string;
  count: number;
  percentage: number;
  strength: 'strong' | 'moderate' | 'weak';
}

interface ComplianceStatus {
  standard: string;
  description: string;
  compliant: boolean;
  compliantSites: number;
  totalSites: number;
}

interface SecurityRecommendation {
  id: string;
  type: string;
  title: string;
  description: string;
  priority: 'high' | 'medium' | 'low';
  impact: string;
}

const isScanning = ref(false);

const securityOverview = ref<SecurityOverview>({
  overallScore: 87,
  secureCerts: 28,
  totalCerts: 32,
  vulnerabilities: 5,
  criticalVulns: 1,
  expiredCerts: 2
});

const priorityIssues = ref<PriorityIssue[]>([
  {
    id: '1',
    title: 'Weak Encryption Cipher Suite',
    description: 'SSL certificate using deprecated TLS 1.0 protocol and weak cipher suites',
    severity: 'critical',
    affectedSites: ['legacy.example.com', 'old-api.example.com'],
    impact: 'High security risk',
    autoFixable: false
  },
  {
    id: '2',
    title: 'Certificate Chain Issues',
    description: 'Incomplete certificate chain missing intermediate certificates',
    severity: 'high',
    affectedSites: ['sub.example.com'],
    impact: 'Browser warnings',
    autoFixable: true
  },
  {
    id: '3',
    title: 'Expiring Certificates',
    description: 'Certificates will expire within the next 30 days',
    severity: 'medium',
    affectedSites: ['www.example.com', 'api.example.com', 'cdn.example.com'],
    impact: 'Service disruption risk',
    autoFixable: true
  }
]);

const certificateAuthorities = ref<CertificateAuthority[]>([
  { name: 'Let\'s Encrypt', count: 18, percentage: 56.25, trustScore: 95, color: '#22c55e' },
  { name: 'DigiCert', count: 8, percentage: 25, trustScore: 98, color: '#3b82f6' },
  { name: 'GlobalSign', count: 4, percentage: 12.5, trustScore: 92, color: '#f59e0b' },
  { name: 'Other', count: 2, percentage: 6.25, trustScore: 85, color: '#6b7280' }
]);

const encryptionAnalysis = ref<EncryptionAnalysis[]>([
  { type: 'RSA 2048-bit', count: 20, percentage: 62.5, strength: 'strong' },
  { type: 'RSA 4096-bit', count: 8, percentage: 25, strength: 'strong' },
  { type: 'ECC P-256', count: 3, percentage: 9.375, strength: 'strong' },
  { type: 'RSA 1024-bit', count: 1, percentage: 3.125, strength: 'weak' }
]);

const complianceStatus = ref<ComplianceStatus[]>([
  {
    standard: 'PCI DSS',
    description: 'Payment Card Industry Data Security Standard',
    compliant: true,
    compliantSites: 30,
    totalSites: 32
  },
  {
    standard: 'GDPR',
    description: 'General Data Protection Regulation encryption requirements',
    compliant: true,
    compliantSites: 32,
    totalSites: 32
  },
  {
    standard: 'HIPAA',
    description: 'Health Insurance Portability and Accountability Act',
    compliant: false,
    compliantSites: 28,
    totalSites: 32
  }
]);

const securityRecommendations = ref<SecurityRecommendation[]>([
  {
    id: '1',
    type: 'encryption',
    title: 'Upgrade to TLS 1.3',
    description: 'Migrate from older TLS versions to TLS 1.3 for enhanced security and performance',
    priority: 'high',
    impact: 'Improved security posture'
  },
  {
    id: '2',
    type: 'monitoring',
    title: 'Enable Certificate Transparency Monitoring',
    description: 'Monitor Certificate Transparency logs to detect unauthorized certificate issuance',
    priority: 'medium',
    impact: 'Early threat detection'
  },
  {
    id: '3',
    type: 'automation',
    title: 'Implement Automated Certificate Renewal',
    description: 'Set up automated renewal for all certificates to prevent expiration',
    priority: 'high',
    impact: 'Reduced downtime risk'
  },
  {
    id: '4',
    type: 'performance',
    title: 'Optimize Certificate Chain',
    description: 'Remove unnecessary intermediate certificates to improve handshake performance',
    priority: 'low',
    impact: 'Better performance'
  }
]);

const getScoreColor = (score: number): string => {
  if (score >= 90) return 'text-green-500';
  if (score >= 80) return 'text-yellow-500';
  if (score >= 70) return 'text-orange-500';
  return 'text-red-500';
};

const getScoreDescription = (score: number): string => {
  if (score >= 90) return 'Excellent security posture';
  if (score >= 80) return 'Good security with room for improvement';
  if (score >= 70) return 'Moderate security, needs attention';
  return 'Poor security, immediate action required';
};

const getSeverityVariant = (severity: string) => {
  switch (severity) {
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
};

const getIssueBorderClass = (severity: string): string => {
  switch (severity) {
    case 'critical':
      return 'border-l-red-500 bg-red-50 dark:bg-red-900/20';
    case 'high':
      return 'border-l-orange-500 bg-orange-50 dark:bg-orange-900/20';
    case 'medium':
      return 'border-l-yellow-500 bg-yellow-50 dark:bg-yellow-900/20';
    case 'low':
      return 'border-l-blue-500 bg-blue-50 dark:bg-blue-900/20';
    default:
      return 'border-l-gray-500 bg-gray-50 dark:bg-gray-900/20';
  }
};

const getEncryptionIcon = (strength: string) => {
  switch (strength) {
    case 'strong':
      return ShieldCheck;
    case 'moderate':
      return ShieldAlert;
    case 'weak':
      return ShieldX;
    default:
      return Shield;
  }
};

const getEncryptionIconClass = (strength: string): string => {
  switch (strength) {
    case 'strong':
      return 'text-green-600 dark:text-green-400';
    case 'moderate':
      return 'text-yellow-600 dark:text-yellow-400';
    case 'weak':
      return 'text-red-600 dark:text-red-400';
    default:
      return 'text-gray-600 dark:text-gray-400';
  }
};

const getStrengthVariant = (strength: string) => {
  switch (strength) {
    case 'strong':
      return 'default';
    case 'moderate':
      return 'secondary';
    case 'weak':
      return 'destructive';
    default:
      return 'outline';
  }
};

const getStrengthBarClass = (strength: string): string => {
  switch (strength) {
    case 'strong':
      return 'bg-green-500';
    case 'moderate':
      return 'bg-yellow-500';
    case 'weak':
      return 'bg-red-500';
    default:
      return 'bg-gray-500';
  }
};

const getRecommendationIcon = (type: string) => {
  switch (type) {
    case 'encryption':
      return Lock;
    case 'monitoring':
      return Target;
    case 'automation':
      return Settings;
    case 'performance':
      return Zap;
    default:
      return Lightbulb;
  }
};

const getRecommendationBgClass = (priority: string): string => {
  switch (priority) {
    case 'high':
      return 'bg-red-100 dark:bg-red-900/30';
    case 'medium':
      return 'bg-yellow-100 dark:bg-yellow-900/30';
    case 'low':
      return 'bg-blue-100 dark:bg-blue-900/30';
    default:
      return 'bg-gray-100 dark:bg-gray-800';
  }
};

const getRecommendationIconClass = (priority: string): string => {
  switch (priority) {
    case 'high':
      return 'text-red-600 dark:text-red-400';
    case 'medium':
      return 'text-yellow-600 dark:text-yellow-400';
    case 'low':
      return 'text-blue-600 dark:text-blue-400';
    default:
      return 'text-gray-600 dark:text-gray-400';
  }
};

const getPriorityVariant = (priority: string) => {
  switch (priority) {
    case 'high':
      return 'destructive';
    case 'medium':
      return 'secondary';
    case 'low':
      return 'outline';
    default:
      return 'outline';
  }
};

const scanAllCertificates = async () => {
  isScanning.value = true;

  // Simulate security scan
  await new Promise(resolve => setTimeout(resolve, 3000));

  // Update security overview with scan results
  securityOverview.value.overallScore = Math.min(100, securityOverview.value.overallScore + 2);
  securityOverview.value.vulnerabilities = Math.max(0, securityOverview.value.vulnerabilities - 1);

  isScanning.value = false;
};

const fixIssue = (issue: PriorityIssue) => {
  if (issue.autoFixable) {
    // Simulate auto-fix
    priorityIssues.value = priorityIssues.value.filter(i => i.id !== issue.id);
    securityOverview.value.overallScore = Math.min(100, securityOverview.value.overallScore + 5);
  } else {
    // Redirect to detailed issue page
    console.log('Redirecting to issue details:', issue.id);
  }
};

const generateSecurityReport = () => {
  // Simulate report generation
  console.log('Generating comprehensive security report...');
};

onMounted(() => {
  // Component initialization
});
</script>