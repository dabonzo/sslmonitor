import { ref } from 'vue';

export interface Toast {
    id: string;
    title: string;
    description?: string;
    variant: 'default' | 'success' | 'error' | 'warning' | 'info';
    duration?: number;
}

interface ToastOptions {
    title: string;
    description?: string;
    duration?: number;
}

const toasts = ref<Toast[]>([]);
let toastCounter = 0;

export function useToast() {
    const addToast = (toast: Omit<Toast, 'id'>) => {
        const id = `toast-${Date.now()}-${toastCounter++}`;
        const newToast = { ...toast, id };
        toasts.value.push(newToast);

        if (toast.duration !== 0) {
            setTimeout(() => {
                removeToast(id);
            }, toast.duration || 5000);
        }

        return id;
    };

    const removeToast = (id: string) => {
        const index = toasts.value.findIndex(t => t.id === id);
        if (index !== -1) {
            toasts.value.splice(index, 1);
        }
    };

    const success = (options: ToastOptions | string) => {
        const config = typeof options === 'string'
            ? { title: options }
            : options;

        return addToast({
            ...config,
            variant: 'success',
        });
    };

    const error = (options: ToastOptions | string) => {
        const config = typeof options === 'string'
            ? { title: options }
            : options;

        return addToast({
            ...config,
            variant: 'error',
        });
    };

    const warning = (options: ToastOptions | string) => {
        const config = typeof options === 'string'
            ? { title: options }
            : options;

        return addToast({
            ...config,
            variant: 'warning',
        });
    };

    const info = (options: ToastOptions | string) => {
        const config = typeof options === 'string'
            ? { title: options }
            : options;

        return addToast({
            ...config,
            variant: 'info',
        });
    };

    const toast = (options: ToastOptions | string) => {
        const config = typeof options === 'string'
            ? { title: options }
            : options;

        return addToast({
            ...config,
            variant: 'default',
        });
    };

    return {
        toasts,
        toast,
        success,
        error,
        warning,
        info,
        removeToast,
    };
}
