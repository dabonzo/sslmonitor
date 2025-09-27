<template>
  <div class="space-y-6">
    <!-- Stats Cards Skeleton -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
      <div v-for="i in 4" :key="i" class="relative group cursor-pointer">
        <div class="animate-pulse">
          <!-- Card Background -->
          <div class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-gray-50 to-slate-100 dark:from-gray-800 dark:to-slate-800 p-6 shadow-xl border border-gray-100 dark:border-gray-800 hover:shadow-2xl transition-all duration-300">
            <!-- Decorative elements -->
            <div class="absolute top-0 right-0 w-32 h-32 bg-gray-100/20 dark:bg-gray-700/20 rounded-full -translate-y-16 translate-x-16 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="absolute inset-0 bg-gray-50/50 dark:bg-gray-700/50 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

            <div class="relative flex items-center justify-between">
              <div class="flex-1">
                <!-- Title -->
                <div class="h-4 bg-muted rounded w-24 mb-3 opacity-70"></div>
                <!-- Value -->
                <div class="h-8 bg-muted rounded w-16 mb-2"></div>
                <!-- Change indicator -->
                <div class="flex items-center">
                  <div class="w-4 h-4 bg-muted rounded mr-2"></div>
                  <div class="h-4 bg-muted rounded w-20"></div>
                </div>
              </div>
              <!-- Icon -->
              <div class="bg-gray-100 dark:bg-gray-700 p-3 rounded-xl group-hover:scale-110 transition-transform duration-300">
                <div class="h-6 w-6 bg-muted rounded"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- SSL & Uptime Overview -->
      <div class="lg:col-span-2 rounded-2xl bg-gradient-to-br from-slate-50 via-white to-gray-50 dark:from-slate-900 dark:via-slate-800 dark:to-gray-900 p-6 shadow-xl border border-gray-100 dark:border-gray-800">
        <div class="animate-pulse space-y-6">
          <!-- Header -->
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
              <div class="rounded-xl bg-gray-100 dark:bg-gray-800 p-2.5">
                <div class="h-6 w-6 bg-muted rounded"></div>
              </div>
              <div class="h-6 bg-muted rounded w-48"></div>
            </div>
            <div class="h-8 bg-muted rounded w-20"></div>
          </div>

          <!-- Chart Containers -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- SSL Certificates -->
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <div class="h-5 bg-muted rounded w-28"></div>
                <div class="w-6 h-6 bg-muted rounded"></div>
              </div>
              <div class="space-y-4">
                <div class="flex justify-between items-center">
                  <div class="h-4 bg-muted rounded w-12"></div>
                  <div class="flex items-center">
                    <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 mr-3"></div>
                    <div class="h-4 bg-muted rounded w-6"></div>
                  </div>
                </div>
                <div class="flex justify-between items-center">
                  <div class="h-4 bg-muted rounded w-16"></div>
                  <div class="flex items-center">
                    <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 mr-3"></div>
                    <div class="h-4 bg-muted rounded w-6"></div>
                  </div>
                </div>
                <div class="flex justify-between items-center">
                  <div class="h-4 bg-muted rounded w-14"></div>
                  <div class="flex items-center">
                    <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 mr-3"></div>
                    <div class="h-4 bg-muted rounded w-6"></div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Uptime Monitors -->
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <div class="h-5 bg-muted rounded w-32"></div>
                <div class="w-6 h-6 bg-muted rounded"></div>
              </div>
              <div class="space-y-4">
                <div class="flex justify-between items-center">
                  <div class="h-4 bg-muted rounded w-14"></div>
                  <div class="flex items-center">
                    <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 mr-3"></div>
                    <div class="h-4 bg-muted rounded w-6"></div>
                  </div>
                </div>
                <div class="flex justify-between items-center">
                  <div class="h-4 bg-muted rounded w-10"></div>
                  <div class="flex items-center">
                    <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 mr-3"></div>
                    <div class="h-4 bg-muted rounded w-6"></div>
                  </div>
                </div>
                <div class="flex justify-between items-center">
                  <div class="h-4 bg-muted rounded w-12"></div>
                  <div class="flex items-center">
                    <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 mr-3"></div>
                    <div class="h-4 bg-muted rounded w-8"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Activity -->
      <div class="rounded-2xl bg-gradient-to-br from-gray-50 to-slate-100 dark:from-gray-800 dark:to-slate-800 p-6 shadow-xl border border-gray-200 dark:border-gray-700">
        <div class="animate-pulse space-y-6">
          <!-- Header -->
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
              <div class="rounded-xl bg-gray-100 dark:bg-gray-800 p-2.5">
                <div class="h-6 w-6 bg-muted rounded"></div>
              </div>
              <div class="h-6 bg-muted rounded w-32"></div>
            </div>
            <div class="h-8 bg-muted rounded w-20"></div>
          </div>

          <!-- Activity Items -->
          <div class="space-y-3">
            <div v-for="i in 6" :key="i" class="flex items-start space-x-4 p-3 rounded-xl bg-white/50 dark:bg-white/5 border border-white/60 dark:border-white/10">
              <div class="flex items-center mt-1 space-x-2">
                <div class="h-3 w-3 bg-muted rounded-full"></div>
                <div class="h-3 w-3 bg-muted rounded-full"></div>
              </div>
              <div class="flex-1 space-y-1">
                <div class="h-4 bg-muted rounded w-32"></div>
                <div class="h-3 bg-muted/60 rounded w-48"></div>
                <div class="h-3 bg-muted/60 rounded w-20"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Critical Alerts -->
      <div class="rounded-2xl bg-gradient-to-br from-rose-50 via-red-50 to-pink-50 dark:from-rose-900/30 dark:via-red-900/30 dark:to-pink-900/30 p-6 shadow-xl border border-rose-100 dark:border-rose-800">
        <div class="animate-pulse space-y-6">
          <!-- Header -->
          <div class="flex items-center space-x-3">
            <div class="rounded-xl bg-gradient-to-br from-red-500 to-pink-600 p-2.5">
              <div class="h-6 w-6 bg-white rounded"></div>
            </div>
            <div class="h-6 bg-muted rounded w-28"></div>
          </div>

          <!-- Alert Items -->
          <div class="space-y-4">
            <div v-for="i in 2" :key="i" class="flex items-start space-x-4 rounded-xl bg-gradient-to-r from-red-100 via-rose-100 to-pink-100 dark:from-red-900/40 dark:via-rose-900/40 dark:to-pink-900/40 p-4 border border-red-200 dark:border-red-700">
              <div class="rounded-lg bg-red-500/10 p-2">
                <div class="h-5 w-5 bg-red-600/20 rounded"></div>
              </div>
              <div class="flex-1 space-y-2">
                <div class="h-4 bg-red-200 dark:bg-red-800/60 rounded w-32"></div>
                <div class="h-3 bg-red-200/60 dark:bg-red-800/40 rounded w-48"></div>
                <div class="h-3 bg-red-200/60 dark:bg-red-800/40 rounded w-28"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="rounded-2xl bg-gradient-to-br from-gray-50 via-slate-50 to-zinc-50 dark:from-gray-900/50 dark:via-slate-900/50 dark:to-zinc-900/50 p-6 shadow-xl border border-gray-100 dark:border-gray-800">
        <div class="animate-pulse space-y-6">
          <!-- Header -->
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
              <div class="rounded-xl bg-gradient-to-br from-gray-700 to-slate-800 p-2.5">
                <div class="h-6 w-6 bg-white rounded"></div>
              </div>
              <div class="h-6 bg-muted rounded w-28"></div>
            </div>
            <div class="h-5 bg-muted rounded w-32"></div>
          </div>

          <!-- Transfer Banner -->
          <div class="p-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-3">
                <div class="rounded-lg bg-blue-100 dark:bg-blue-900/30 p-2">
                  <div class="h-4 w-4 bg-blue-600/20 rounded"></div>
                </div>
                <div class="space-y-1">
                  <div class="h-4 bg-blue-200 dark:bg-blue-800/60 rounded w-48"></div>
                  <div class="h-3 bg-blue-200/60 dark:bg-blue-800/40 rounded w-32"></div>
                </div>
              </div>
              <div class="h-7 bg-blue-200 dark:bg-blue-800/60 rounded w-16"></div>
            </div>
          </div>

          <!-- Quick Action Buttons -->
          <div class="grid grid-cols-2 gap-4">
            <div v-for="i in 4" :key="i" class="rounded-lg bg-white/50 dark:bg-white/5 p-4 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-300">
              <div class="space-y-3">
                <div class="rounded-lg bg-purple-500/10 p-3 inline-block">
                  <div class="h-6 w-6 bg-purple-600/20 rounded"></div>
                </div>
                <div class="h-4 bg-muted rounded w-24"></div>
              </div>
            </div>
          </div>

          <!-- Team Access -->
          <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-3">
              <div class="h-4 bg-muted rounded w-32"></div>
              <div class="h-6 bg-muted rounded w-16"></div>
            </div>
            <div class="flex flex-wrap gap-2">
              <div v-for="i in 2" :key="i" class="h-7 bg-muted rounded-full w-28"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
// Dashboard skeleton component for loading states
</script>