<template>
  <Dialog :open="isOpen" @update:open="handleClose">
    <DialogContent class="max-w-md">
      <DialogHeader>
        <DialogTitle class="flex items-center space-x-3">
          <div class="rounded-lg bg-blue-100 dark:bg-blue-900/30 p-2">
            <Clock class="h-5 w-5 text-blue-600 dark:text-blue-400" />
          </div>
          <div>
            <div class="text-xl font-bold text-gray-900 dark:text-gray-100">
              Schedule Report
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
              Set up automated report generation
            </div>
          </div>
        </DialogTitle>
      </DialogHeader>

      <div class="space-y-4 py-4">
        <div class="space-y-2">
          <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Report Name</label>
          <input
            v-model="scheduleConfig.name"
            type="text"
            placeholder="Weekly SSL Summary"
            class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
          />
        </div>

        <div class="space-y-2">
          <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
          <textarea
            v-model="scheduleConfig.description"
            rows="2"
            placeholder="Automated weekly SSL certificate summary"
            class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
          ></textarea>
        </div>

        <div class="space-y-2">
          <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Frequency</label>
          <select
            v-model="scheduleConfig.frequency"
            class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
          >
            <option value="daily">Daily</option>
            <option value="weekly">Weekly</option>
            <option value="monthly">Monthly</option>
            <option value="quarterly">Quarterly</option>
          </select>
        </div>

        <div class="space-y-2">
          <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Next Run</label>
          <input
            v-model="scheduleConfig.nextRun"
            type="datetime-local"
            class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
          />
        </div>
      </div>

      <div class="flex justify-end space-x-3 pt-4 border-t">
        <Button @click="handleClose" variant="outline">
          Cancel
        </Button>
        <Button @click="scheduleReport" :disabled="!isFormValid">
          <Clock class="h-4 w-4 mr-2" />
          Schedule
        </Button>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Clock } from 'lucide-vue-next';

interface Props {
  isOpen: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  close: [];
  schedule: [config: any];
}>();

const scheduleConfig = ref({
  name: '',
  description: '',
  frequency: 'weekly',
  nextRun: ''
});

const isFormValid = computed(() => {
  return scheduleConfig.value.name.trim().length > 0 &&
         scheduleConfig.value.nextRun.length > 0;
});

const scheduleReport = () => {
  if (!isFormValid.value) return;

  emit('schedule', { ...scheduleConfig.value });

  // Reset form
  scheduleConfig.value = {
    name: '',
    description: '',
    frequency: 'weekly',
    nextRun: ''
  };
};

const handleClose = () => {
  emit('close');
  // Reset form
  scheduleConfig.value = {
    name: '',
    description: '',
    frequency: 'weekly',
    nextRun: ''
  };
};
</script>