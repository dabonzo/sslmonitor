<template>
  <Dialog :open="isOpen" @update:open="handleClose">
    <DialogContent class="max-w-md">
      <DialogHeader>
        <DialogTitle class="flex items-center space-x-3">
          <div class="rounded-lg bg-blue-100 dark:bg-blue-900/30 p-2">
            <Share class="h-5 w-5 text-primary dark:text-blue-400" />
          </div>
          <div>
            <div class="text-xl font-bold text-foreground dark:text-foreground">
              Share Report
            </div>
            <div class="text-sm text-foreground dark:text-muted-foreground mt-1">
              Share "{{ report?.name }}" with others
            </div>
          </div>
        </DialogTitle>
      </DialogHeader>

      <div class="space-y-4 py-4">
        <!-- Share Link -->
        <div class="space-y-2">
          <label class="text-sm font-medium text-foreground dark:text-muted-foreground">Share Link</label>
          <div class="flex space-x-2">
            <input
              :value="shareLink"
              readonly
              class="flex-1 px-3 py-2 border border-border rounded-md bg-muted dark:bg-card text-foreground focus:outline-none"
            />
            <Button @click="copyLink" size="sm">
              <Copy class="h-4 w-4" />
            </Button>
          </div>
          <p class="text-xs text-muted-foreground dark:text-muted-foreground">Link expires in 30 days</p>
        </div>

        <!-- Email Sharing -->
        <div class="space-y-2">
          <label class="text-sm font-medium text-foreground dark:text-muted-foreground">Email Recipients</label>
          <textarea
            v-model="emailRecipients"
            rows="3"
            placeholder="email1@example.com, email2@example.com"
            class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
          ></textarea>
        </div>

        <!-- Share Options -->
        <div class="space-y-3">
          <div class="flex items-center space-x-3">
            <Checkbox
              :checked="shareOptions.allowDownload"
              @update:checked="shareOptions.allowDownload = $event"
            />
            <span class="text-sm text-foreground dark:text-muted-foreground">Allow recipients to download report</span>
          </div>

          <div class="flex items-center space-x-3">
            <Checkbox
              :checked="shareOptions.requirePassword"
              @update:checked="shareOptions.requirePassword = $event"
            />
            <span class="text-sm text-foreground dark:text-muted-foreground">Require password to view</span>
          </div>

          <div v-if="shareOptions.requirePassword" class="ml-6 space-y-2">
            <input
              v-model="shareOptions.password"
              type="password"
              placeholder="Enter password"
              class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-primary"
            />
          </div>

          <div class="flex items-center space-x-3">
            <Checkbox
              :checked="shareOptions.trackViews"
              @update:checked="shareOptions.trackViews = $event"
            />
            <span class="text-sm text-foreground dark:text-muted-foreground">Track who views the report</span>
          </div>
        </div>
      </div>

      <div class="flex justify-end space-x-3 pt-4 border-t">
        <Button @click="handleClose" variant="outline">
          Cancel
        </Button>
        <Button @click="sendEmail" :disabled="!emailRecipients.trim()">
          <Mail class="h-4 w-4 mr-2" />
          Send Email
        </Button>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Share, Copy, Mail } from 'lucide-vue-next';

interface Report {
  id: string;
  name: string;
}

interface Props {
  isOpen: boolean;
  report: Report | null;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  close: [];
}>();

const emailRecipients = ref('');
const shareOptions = ref({
  allowDownload: true,
  requirePassword: false,
  password: '',
  trackViews: true
});

const shareLink = `https://ssl-monitor.example.com/shared-reports/${props.report?.id || 'abc123'}`;

const copyLink = async () => {
  try {
    await navigator.clipboard.writeText(shareLink);
    // You could show a toast notification here
    console.log('Link copied to clipboard');
  } catch (err) {
    console.error('Failed to copy link:', err);
  }
};

const sendEmail = () => {
  console.log('Sending report via email to:', emailRecipients.value);
  console.log('Share options:', shareOptions.value);

  // Simulate email sending
  setTimeout(() => {
    handleClose();
  }, 1000);
};

const handleClose = () => {
  emit('close');
  // Reset form
  emailRecipients.value = '';
  shareOptions.value = {
    allowDownload: true,
    requirePassword: false,
    password: '',
    trackViews: true
  };
};
</script>