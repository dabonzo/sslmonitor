import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const fetchJson = async <T>(url: string): Promise<T> => {
    const response = await fetch(url, {
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        throw new Error(`Failed to fetch: ${response.status}`);
    }

    return response.json();
};

const qrCodeSvg = ref<string | null>(null);
const manualSetupKey = ref<string | null>(null);
const recoveryCodesList = ref<string[]>([]);

const hasSetupData = computed<boolean>(() => {
    const page = usePage();
    return page.props.qrCodeSvg !== null || qrCodeSvg.value !== null;
});

export const useTwoFactorAuth = () => {
    const getQrCodeFromProps = (): void => {
        const page = usePage();
        qrCodeSvg.value = page.props.qrCodeSvg as string || null;
    };

    const fetchRecoveryCodes = async (): Promise<void> => {
        try {
            const response = await fetchJson<{ recovery_codes: string[] }>('/settings/two-factor/recovery-codes');
            recoveryCodesList.value = response.recovery_codes;
        } catch (error) {
            console.error('Failed to fetch recovery codes:', error);
            recoveryCodesList.value = [];
        }
    };

    const clearSetupData = (): void => {
        manualSetupKey.value = null;
        qrCodeSvg.value = null;
    };

    const clearTwoFactorAuthData = (): void => {
        clearSetupData();
        recoveryCodesList.value = [];
    };

    const fetchSetupData = (): void => {
        getQrCodeFromProps();
        // Manual setup key is not exposed in our implementation for security
        // Users must scan the QR code
    };

    return {
        qrCodeSvg,
        manualSetupKey,
        recoveryCodesList,
        hasSetupData,
        clearSetupData,
        clearTwoFactorAuthData,
        fetchSetupData,
        fetchRecoveryCodes,
        getQrCodeFromProps,
    };
};
