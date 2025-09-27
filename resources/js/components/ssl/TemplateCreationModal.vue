<template>
  <Dialog :open="isOpen" @update:open="handleClose">
    <DialogContent class="max-w-md">
      <DialogHeader>
        <DialogTitle class="flex items-center space-x-3">
          <div class="rounded-lg bg-blue-100 dark:bg-blue-900/30 p-2">
            <Bookmark class="h-5 w-5 text-blue-600 dark:text-blue-400" />
          </div>
          <div>
            <div class="text-xl font-bold text-gray-900 dark:text-gray-100">
              Create Operation Template
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
              Save this configuration for future use
            </div>
          </div>
        </DialogTitle>
      </DialogHeader>

      <div class="space-y-4 py-4">
        <div class="space-y-2">
          <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Template Name</label>
          <input
            v-model="templateData.name"
            type="text"
            placeholder="My Operation Template"
            class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
          />
        </div>

        <div class="space-y-2">
          <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
          <textarea
            v-model="templateData.description"
            rows="2"
            placeholder="Describe when to use this template..."
            class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
          ></textarea>
        </div>

        <div class="space-y-2">
          <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Operations Included</label>
          <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <div class="text-sm text-gray-600 dark:text-gray-400">
              {{ operations.length }} operation{{ operations.length !== 1 ? 's' : '' }} selected
            </div>
          </div>
        </div>

        <div class="space-y-2">
          <div class="flex items-center space-x-2">
            <Checkbox
              :checked="templateData.includeNotifications"
              @update:checked="templateData.includeNotifications = $event"
            />
            <label class="text-sm text-gray-700 dark:text-gray-300">
              Include notification settings
            </label>
          </div>
        </div>
      </div>

      <div class="flex justify-end space-x-3 pt-4 border-t">
        <Button @click="handleClose" variant="outline">
          Cancel
        </Button>
        <Button @click="createTemplate" :disabled="!isFormValid">
          <Bookmark class="h-4 w-4 mr-2" />
          Create Template
        </Button>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Bookmark } from 'lucide-vue-next';

interface Props {
  isOpen: boolean;
  operations: string[];
  notificationSettings: {
    email: boolean;
    slack: boolean;
    dashboard: boolean;
  };
}

const props = defineProps<Props>();

const emit = defineEmits<{
  close: [];
  create: [templateData: any];
}>();

const templateData = ref({
  name: '',
  description: '',
  includeNotifications: true
});

const isFormValid = computed(() => {
  return templateData.value.name.trim().length > 0 && props.operations.length > 0;
});

const handleClose = () => {
  emit('close');
  // Reset form
  templateData.value = {
    name: '',
    description: '',
    includeNotifications: true
  };
};

const createTemplate = () => {
  if (!isFormValid.value) return;

  const template = {
    ...templateData.value,
    operations: props.operations,
    notificationSettings: templateData.value.includeNotifications ? props.notificationSettings : null
  };

  emit('create', template);

  // Reset form
  templateData.value = {
    name: '',
    description: '',
    includeNotifications: true
  };
};
</script>