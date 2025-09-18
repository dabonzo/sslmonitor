<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Head } from '@inertiajs/vue3';
import {
  Shield,
  CheckCircle,
  AlertTriangle,
  Clock,
  TrendingUp,
  TrendingDown
} from 'lucide-vue-next';

// Mock dashboard data
const stats = [
  {
    title: 'Total Certificates',
    value: '24',
    change: '+2',
    trend: 'up',
    icon: Shield,
    color: 'text-blue-600'
  },
  {
    title: 'Valid Certificates',
    value: '22',
    change: '91.7%',
    trend: 'up',
    icon: CheckCircle,
    color: 'text-green-600'
  },
  {
    title: 'Expiring Soon',
    value: '2',
    change: '8.3%',
    trend: 'down',
    icon: AlertTriangle,
    color: 'text-yellow-600'
  },
  {
    title: 'Avg Response Time',
    value: '245ms',
    change: '-12ms',
    trend: 'up',
    icon: Clock,
    color: 'text-purple-600'
  }
];

const recentActivity = [
  {
    title: 'SSL Certificate Renewed',
    description: 'example.com certificate has been renewed',
    time: '2 minutes ago',
    type: 'success'
  },
  {
    title: 'Monitor Added',
    description: 'New monitor created for api.example.com',
    time: '1 hour ago',
    type: 'info'
  },
  {
    title: 'Certificate Expiring',
    description: 'shop.example.com expires in 7 days',
    time: '3 hours ago',
    type: 'warning'
  }
];
</script>

<template>
    <Head title="Dashboard" />

    <DashboardLayout title="Dashboard">
        <!-- Stats Cards -->
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div
                v-for="stat in stats"
                :key="stat.title"
                class="rounded-lg bg-card text-card-foreground p-6 shadow-sm"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">
                            {{ stat.title }}
                        </p>
                        <p class="text-2xl font-bold text-foreground">
                            {{ stat.value }}
                        </p>
                        <p class="flex items-center text-sm" :class="{
                            'text-green-600': stat.trend === 'up',
                            'text-red-600': stat.trend === 'down'
                        }">
                            <TrendingUp v-if="stat.trend === 'up'" class="mr-1 h-4 w-4" />
                            <TrendingDown v-else class="mr-1 h-4 w-4" />
                            {{ stat.change }}
                        </p>
                    </div>
                    <div class="rounded-lg bg-muted p-3">
                        <component :is="stat.icon" class="h-6 w-6" :class="stat.color" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            <!-- SSL Status Chart -->
            <div class="lg:col-span-2 rounded-lg bg-card text-card-foreground p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-foreground">
                        Certificate Status Overview
                    </h3>
                    <button class="text-sm text-primary hover:text-primary/80">
                        View All
                    </button>
                </div>

                <!-- Placeholder for chart -->
                <div class="flex h-64 items-center justify-center rounded-lg bg-muted">
                    <div class="text-center">
                        <Shield class="mx-auto h-12 w-12 text-muted-foreground" />
                        <p class="mt-2 text-sm text-muted-foreground">
                            SSL Certificate Status Chart
                        </p>
                        <p class="text-xs text-muted-foreground/80">Chart visualization will be implemented</p>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="rounded-lg bg-card text-card-foreground p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-foreground">
                        Recent Activity
                    </h3>
                    <button class="text-sm text-primary hover:text-primary/80">
                        View All
                    </button>
                </div>

                <div class="space-y-4">
                    <div
                        v-for="activity in recentActivity"
                        :key="activity.title"
                        class="flex items-start space-x-3"
                    >
                        <div
                            class="mt-1 h-2 w-2 rounded-full"
                            :class="{
                                'bg-green-400': activity.type === 'success',
                                'bg-blue-400': activity.type === 'info',
                                'bg-yellow-400': activity.type === 'warning'
                            }"
                        />
                        <div class="flex-1">
                            <p class="text-sm font-medium text-foreground">
                                {{ activity.title }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{ activity.description }}
                            </p>
                            <p class="text-xs text-muted-foreground/80">
                                {{ activity.time }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Content Row -->
        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">

            <!-- Critical Alerts -->
            <div class="rounded-lg bg-card text-card-foreground p-6 shadow-sm">
                <h3 class="mb-4 text-lg font-semibold text-foreground">
                    Critical Alerts
                </h3>

                <div class="space-y-3">
                    <div class="flex items-center space-x-3 rounded-lg bg-red-50 p-3 dark:bg-red-900/20">
                        <AlertTriangle class="h-5 w-5 text-red-600" />
                        <div class="flex-1">
                            <p class="text-sm font-medium text-red-800 dark:text-red-200">
                                Certificate Expired
                            </p>
                            <p class="text-sm text-red-600 dark:text-red-300">
                                old.example.com certificate expired 2 days ago
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center space-x-3 rounded-lg bg-yellow-50 p-3 dark:bg-yellow-900/20">
                        <Clock class="h-5 w-5 text-yellow-600" />
                        <div class="flex-1">
                            <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                Monitor Timeout
                            </p>
                            <p class="text-sm text-yellow-600 dark:text-yellow-300">
                                api.example.com taking longer than usual to respond
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="rounded-lg bg-card text-card-foreground p-6 shadow-sm">
                <h3 class="mb-4 text-lg font-semibold text-foreground">
                    Quick Actions
                </h3>

                <div class="grid grid-cols-2 gap-3">
                    <button class="rounded-lg border border-border p-3 text-center hover:bg-muted">
                        <Shield class="mx-auto h-6 w-6 text-primary" />
                        <p class="mt-1 text-sm font-medium text-foreground">Add Certificate</p>
                    </button>

                    <button class="rounded-lg border border-border p-3 text-center hover:bg-muted">
                        <Clock class="mx-auto h-6 w-6 text-primary" />
                        <p class="mt-1 text-sm font-medium text-foreground">Create Monitor</p>
                    </button>

                    <button class="rounded-lg border border-border p-3 text-center hover:bg-muted">
                        <TrendingUp class="mx-auto h-6 w-6 text-primary" />
                        <p class="mt-1 text-sm font-medium text-foreground">View Reports</p>
                    </button>

                    <button class="rounded-lg border border-border p-3 text-center hover:bg-muted">
                        <AlertTriangle class="mx-auto h-6 w-6 text-primary" />
                        <p class="mt-1 text-sm font-medium text-foreground">Alert Rules</p>
                    </button>
                </div>
            </div>
        </div>

    </DashboardLayout>
</template>
