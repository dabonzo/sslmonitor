<template>
  <!-- Desktop Table Skeleton -->
  <div class="hidden lg:block">
    <div class="animate-pulse">
      <table class="w-full">
        <thead>
          <tr class="border-b border-border">
            <th class="text-left p-4 w-12">
              <div class="w-5 h-5 bg-muted rounded"></div>
            </th>
            <th class="text-left p-4">
              <div class="h-4 bg-muted rounded w-20"></div>
            </th>
            <th class="text-left p-4">
              <div class="h-4 bg-muted rounded w-16"></div>
            </th>
            <th class="text-left p-4">
              <div class="h-4 bg-muted rounded w-20"></div>
            </th>
            <th class="text-left p-4">
              <div class="h-4 bg-muted rounded w-24"></div>
            </th>
            <th class="text-left p-4">
              <div class="h-4 bg-muted rounded w-16"></div>
            </th>
            <th class="text-left p-4">
              <div class="h-4 bg-muted rounded w-20"></div>
            </th>
            <th class="text-left p-4">
              <div class="h-4 bg-muted rounded w-16"></div>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="i in count" :key="i" class="border-b border-border">
            <td class="p-4">
              <div class="w-5 h-5 bg-muted rounded"></div>
            </td>
            <td class="p-4">
              <div class="space-y-2">
                <div class="h-4 bg-muted rounded w-32"></div>
                <div class="h-3 bg-muted/60 rounded w-48"></div>
              </div>
            </td>
            <td class="p-4">
              <div class="h-6 bg-muted rounded-full w-16"></div>
            </td>
            <td class="p-4">
              <div class="h-6 bg-muted rounded-full w-16"></div>
            </td>
            <td class="p-4">
              <div class="h-4 bg-muted rounded w-16"></div>
            </td>
            <td class="p-4">
              <div class="h-6 bg-muted rounded-full w-20"></div>
            </td>
            <td class="p-4">
              <div class="flex gap-2">
                <div class="h-7 bg-muted rounded w-12"></div>
                <div class="h-7 bg-muted rounded w-16"></div>
              </div>
            </td>
            <td class="p-4">
              <div class="flex gap-2">
                <div class="h-7 bg-muted rounded w-12"></div>
                <div class="h-7 bg-muted rounded w-12"></div>
                <div class="h-7 bg-muted rounded w-16"></div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Mobile Cards Skeleton -->
  <div class="block lg:hidden space-y-4">
    <div class="animate-pulse">
      <!-- Mobile Select All Skeleton -->
      <div class="flex items-center justify-between pb-4 border-b border-border">
        <div class="flex items-center gap-3">
          <div class="w-5 h-5 bg-muted rounded"></div>
          <div class="h-4 bg-muted rounded w-20"></div>
        </div>
      </div>

      <!-- Mobile Cards Skeleton -->
      <div v-for="i in count" :key="i" class="bg-background rounded-lg border border-border p-4 shadow-sm space-y-4">
        <!-- Card Header -->
        <div class="flex items-start gap-3">
          <div class="w-5 h-5 bg-muted rounded mt-1 flex-shrink-0"></div>
          <div class="flex-1 space-y-2">
            <div class="flex items-center gap-2">
              <div class="h-5 bg-muted rounded w-32"></div>
              <div class="h-5 bg-muted rounded-full w-16 flex-shrink-0"></div>
            </div>
            <div class="h-3 bg-muted/60 rounded w-48"></div>
          </div>
        </div>

        <!-- Status Row -->
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-2">
            <div class="h-3 bg-muted/60 rounded w-16"></div>
            <div class="h-6 bg-muted rounded-full w-12"></div>
          </div>
          <div class="space-y-2">
            <div class="h-3 bg-muted/60 rounded w-20"></div>
            <div class="h-6 bg-muted rounded-full w-14"></div>
          </div>
        </div>

        <!-- Days Remaining -->
        <div class="space-y-2">
          <div class="h-3 bg-muted/60 rounded w-24"></div>
          <div class="h-4 bg-muted rounded w-16"></div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap gap-2">
          <div class="h-8 bg-muted rounded w-20"></div>
          <div class="h-8 bg-muted rounded w-24"></div>
          <div class="h-8 bg-muted rounded w-14"></div>
          <div class="h-8 bg-muted rounded w-12"></div>
          <div class="h-8 bg-muted rounded w-16"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  count?: number;
}

const props = withDefaults(defineProps<Props>(), {
  count: 3
});
</script>