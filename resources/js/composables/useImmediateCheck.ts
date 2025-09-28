import { ref, reactive } from 'vue';
import axios from 'axios';

interface CheckStatus {
  website_id: number;
  last_updated: string;
  ssl_status: string;
  uptime_status: string;
  ssl_monitoring_enabled: boolean;
  uptime_monitoring_enabled: boolean;
  checked_at: string;
}

interface ImmediateCheckResponse {
  success: boolean;
  message: string;
  website_id: number;
  estimated_completion: string;
}

interface WebsiteCheckState {
  isChecking: boolean;
  progress: number;
  status: string;
  lastChecked?: string;
  error?: string;
}

export function useImmediateCheck() {
  // Track checking states for multiple websites
  const checkingStates = reactive<Record<number, WebsiteCheckState>>({});

  // Global loading state
  const isLoading = ref(false);

  // Initialize state for a website
  const initializeWebsiteState = (websiteId: number): WebsiteCheckState => {
    if (!checkingStates[websiteId]) {
      checkingStates[websiteId] = {
        isChecking: false,
        progress: 0,
        status: 'idle',
      };
    }
    return checkingStates[websiteId];
  };

  // Trigger immediate check for a website
  const triggerImmediateCheck = async (websiteId: number): Promise<boolean> => {
    const state = initializeWebsiteState(websiteId);

    try {
      state.isChecking = true;
      state.progress = 0;
      state.status = 'starting';
      state.error = undefined;

      console.log(`[ImmediateCheck] Triggering check for website ${websiteId}`);

      // Start the immediate check
      const response = await axios.post<ImmediateCheckResponse>(
        `/ssl/websites/${websiteId}/immediate-check`
      );

      if (!response.data.success) {
        throw new Error(response.data.message);
      }

      console.log(`[ImmediateCheck] Check started for website ${websiteId}:`, response.data);

      state.status = 'running';
      state.progress = 25;

      // Start polling for status updates
      await pollCheckStatus(websiteId);

      return true;

    } catch (error: any) {
      console.error(`[ImmediateCheck] Failed to trigger check for website ${websiteId}:`, error);

      state.isChecking = false;
      state.status = 'error';
      state.error = error.response?.data?.message || error.message || 'Failed to start check';

      return false;
    }
  };

  // Poll check status with exponential backoff
  const pollCheckStatus = async (websiteId: number, maxAttempts: number = 20): Promise<void> => {
    const state = checkingStates[websiteId];
    let attempts = 0;
    let pollInterval = 2000; // Start with 2 seconds

    console.log(`[ImmediateCheck] Starting polling for website ${websiteId}`);

    const poll = async (): Promise<void> => {
      try {
        attempts++;
        state.progress = Math.min(25 + (attempts * 3), 95); // Progress from 25% to 95%

        console.log(`[ImmediateCheck] Polling attempt ${attempts} for website ${websiteId}`);

        const response = await axios.get<CheckStatus>(
          `/ssl/websites/${websiteId}/check-status`
        );

        const statusData = response.data;
        console.log(`[ImmediateCheck] Status response for website ${websiteId}:`, statusData);

        // Check if the website was recently updated (within last 60 seconds)
        const lastUpdated = new Date(statusData.last_updated);
        const now = new Date();
        const secondsSinceUpdate = (now.getTime() - lastUpdated.getTime()) / 1000;

        // Consider check complete if:
        // 1. Website was updated recently (< 60 seconds)
        // 2. SSL/uptime status is not "not yet checked"
        // 3. We've made enough attempts
        const hasRecentUpdate = secondsSinceUpdate < 60;
        const hasValidStatus = (
          (statusData.ssl_monitoring_enabled && statusData.ssl_status !== 'not yet checked') ||
          (statusData.uptime_monitoring_enabled && statusData.uptime_status !== 'not yet checked')
        );

        if (hasRecentUpdate && hasValidStatus) {
          // Check completed successfully
          state.isChecking = false;
          state.progress = 100;
          state.status = 'completed';
          state.lastChecked = statusData.checked_at;

          console.log(`[ImmediateCheck] Check completed for website ${websiteId}`);
          return;
        }

        // Continue polling if we haven't exceeded max attempts
        if (attempts < maxAttempts) {
          // Exponential backoff with jitter
          pollInterval = Math.min(pollInterval * 1.2, 5000);

          setTimeout(() => {
            poll().catch(console.error);
          }, pollInterval);
        } else {
          // Polling timeout
          state.isChecking = false;
          state.status = 'timeout';
          state.error = 'Check timed out. Please refresh to see latest status.';

          console.warn(`[ImmediateCheck] Polling timeout for website ${websiteId}`);
        }

      } catch (error: any) {
        console.error(`[ImmediateCheck] Polling error for website ${websiteId}:`, error);

        if (attempts < maxAttempts) {
          // Retry on error
          setTimeout(() => {
            poll().catch(console.error);
          }, pollInterval);
        } else {
          state.isChecking = false;
          state.status = 'error';
          state.error = 'Failed to get check status. Please refresh to see latest status.';
        }
      }
    };

    // Start polling
    setTimeout(() => {
      poll().catch(console.error);
    }, 1000); // Wait 1 second before first poll
  };

  // Get state for a specific website
  const getWebsiteState = (websiteId: number): WebsiteCheckState => {
    return initializeWebsiteState(websiteId);
  };

  // Check if any website is currently being checked
  const hasActiveChecks = (): boolean => {
    return Object.values(checkingStates).some(state => state.isChecking);
  };

  // Clear state for a website
  const clearWebsiteState = (websiteId: number): void => {
    if (checkingStates[websiteId]) {
      delete checkingStates[websiteId];
    }
  };

  // Clear all states
  const clearAllStates = (): void => {
    Object.keys(checkingStates).forEach(id => {
      delete checkingStates[parseInt(id)];
    });
  };

  return {
    checkingStates,
    isLoading,
    triggerImmediateCheck,
    getWebsiteState,
    hasActiveChecks,
    clearWebsiteState,
    clearAllStates,
  };
}