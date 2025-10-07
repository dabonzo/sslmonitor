# Toast Notification System

## Overview

The toast notification system provides an elegant way to show temporary notifications to users. It's built using Reka UI's Toast components and integrates seamlessly with both Vue components and Laravel's flash messages.

## Features

- ✅ Multiple variants: success, error, warning, info, default
- ✅ Auto-dismiss with configurable duration
- ✅ Manual dismiss option
- ✅ Automatic Laravel flash message integration
- ✅ Dark mode support
- ✅ Accessible (built on Reka UI)
- ✅ Icon indicators for each variant
- ✅ Simple API

## Usage in Vue Components

### Basic Usage

```vue
<script setup lang="ts">
import { useToast } from '@/composables/useToast';

const { success, error, warning, info, toast } = useToast();

const handleSuccess = () => {
    success('Operation completed successfully!');
};

const handleError = () => {
    error('Something went wrong!');
};
</script>

<template>
    <button @click="handleSuccess">Show Success</button>
    <button @click="handleError">Show Error</button>
</template>
```

### With Description

```typescript
import { useToast } from '@/composables/useToast';

const { success } = useToast();

success({
    title: 'Website added',
    description: 'Your website is now being monitored for SSL certificate expiry.',
    duration: 5000, // Optional, defaults to 5000ms
});
```

### Available Methods

```typescript
const { toast, success, error, warning, info, removeToast } = useToast();

// Simple string
success('Great job!');
error('Oops!');
warning('Be careful!');
info('Just so you know...');
toast('Generic notification');

// With options
success({
    title: 'Success!',
    description: 'Optional detailed message',
    duration: 7000, // milliseconds, 0 for no auto-dismiss
});

// Remove specific toast
const toastId = success('This will be removed');
setTimeout(() => removeToast(toastId), 2000);
```

## Usage in Laravel Controllers

The toast system automatically listens for Laravel flash messages. Simply flash messages from your controllers:

```php
// Success notification
return redirect()->route('dashboard')
    ->with('success', 'Website added successfully!');

// Error notification
return redirect()->back()
    ->with('error', 'Failed to add website.');

// Warning notification
return redirect()->route('settings')
    ->with('warning', 'Your trial is about to expire.');

// Info notification
return redirect()->route('profile')
    ->with('info', 'Please complete your profile.');

// Generic notification
return redirect()->route('home')
    ->with('message', 'Welcome back!');
```

## API Reference

### useToast()

Returns an object with the following properties and methods:

#### Properties

- **toasts**: `Ref<Toast[]>` - Reactive array of current toasts

#### Methods

- **toast(options: ToastOptions | string)**: Show default toast
- **success(options: ToastOptions | string)**: Show success toast
- **error(options: ToastOptions | string)**: Show error toast
- **warning(options: ToastOptions | string)**: Show warning toast
- **info(options: ToastOptions | string)**: Show info toast
- **removeToast(id: string)**: Remove specific toast

### Toast Interface

```typescript
interface Toast {
    id: string;
    title: string;
    description?: string;
    variant: 'default' | 'success' | 'error' | 'warning' | 'info';
    duration?: number; // milliseconds, 0 for persistent
}
```

### ToastOptions Interface

```typescript
interface ToastOptions {
    title: string;
    description?: string;
    duration?: number; // default: 5000ms
}
```

## Styling

The toast notifications automatically adapt to your app's theme:

- **Light mode**: Clean white backgrounds with colored accents
- **Dark mode**: Dark backgrounds with appropriate contrast

Each variant has its own color scheme:
- **Success**: Green
- **Error**: Red
- **Warning**: Yellow
- **Info**: Blue
- **Default**: Gray

## Examples

### Form Submission

```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';

const { success, error } = useToast();
const form = useForm({
    name: '',
    email: '',
});

const submit = () => {
    form.post('/users', {
        onSuccess: () => {
            success({
                title: 'User created',
                description: `${form.name} has been added to your team.`,
            });
        },
        onError: () => {
            error({
                title: 'Failed to create user',
                description: 'Please check the form and try again.',
            });
        },
    });
};
</script>
```

### Copy to Clipboard

```typescript
import { useToast } from '@/composables/useToast';

const { success, error } = useToast();

const copyToClipboard = async (text: string) => {
    try {
        await navigator.clipboard.writeText(text);
        success('Copied to clipboard!');
    } catch (err) {
        error('Failed to copy to clipboard');
    }
};
```

### Delete Confirmation

```vue
<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';

const { success, error } = useToast();

const deleteWebsite = (id: number) => {
    if (confirm('Are you sure?')) {
        router.delete(`/websites/${id}`, {
            onSuccess: () => {
                success({
                    title: 'Website deleted',
                    description: 'The website has been removed from monitoring.',
                });
            },
            onError: () => {
                error('Failed to delete website');
            },
        });
    }
};
</script>
```

## Best Practices

1. **Use appropriate variants**: Choose the variant that matches the message type
2. **Keep titles short**: 3-5 words maximum
3. **Add descriptions for context**: Provide additional details when needed
4. **Don't overuse**: Only show notifications for important events
5. **Consistent language**: Use similar phrasing for similar actions
6. **Test both themes**: Ensure notifications look good in light and dark modes

## Implementation Details

### Toast System Architecture

The toast notification system consists of three main parts:

1. **useToast Composable** (`/resources/js/composables/useToast.ts`): Manages toast state using Vue's reactive system
2. **ToastContainer Component** (`/resources/js/components/ui/toast/ToastContainer.vue`): Renders toasts with animations
3. **Inertia Event Integration** (`/resources/js/app.ts`): Listens for successful page navigations and displays flash messages

### How Flash Messages Work

1. Laravel controller sets a flash message: `->with('success', 'Team created successfully!')`
2. HandleInertiaRequests middleware shares flash messages to the frontend via `page.props.flash`
3. Inertia's `success` event fires after a successful page visit
4. The event listener in `app.ts` checks for flash messages in `event.detail.page.props.flash`
5. If flash messages exist, the corresponding toast method is called
6. ToastContainer displays the toast with a smooth slide-in animation
7. Toast auto-dismisses after 5 seconds (configurable)

**Important:** The system uses Inertia's `success` event, not `finish` event, because:
- `success` event provides page data in `event.detail.page`
- `finish` event fires before page data is available (`router.page` may be null)

## Troubleshooting

### Toasts not appearing

1. Verify ToastContainer is in DashboardLayout.vue (or your active layout)
2. Check browser console for errors
3. Verify flash messages are set in your controller
4. Ensure HandleInertiaRequests middleware is sharing flash messages
5. Check that the Inertia `success` event listener is registered in app.ts

### Styling issues

1. Clear cache: `./vendor/bin/sail artisan cache:clear`
2. Rebuild frontend: `./vendor/bin/sail npm run dev`
3. Check dark mode toggle is working

### Multiple toasts stacking

This is expected behavior. Each toast will appear stacked in the top-right corner. They auto-dismiss after 5 seconds by default.

### Toast appears then immediately disappears

This happens if you're using the `finish` event instead of the `success` event. Make sure you're using:

```typescript
router.on('success', (event) => {
    const page = event.detail.page as any;
    // ... rest of the code
});
```

Not:

```typescript
router.on('finish', () => {
    const page = router.page as any; // This may be null!
    // ...
});
```
