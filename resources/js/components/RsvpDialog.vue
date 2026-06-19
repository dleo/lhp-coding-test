<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import { store } from '@/actions/App/Http/Controllers/AttendeeController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    eventId: string;
    eventName: string;
}>();

const isOpen = defineModel<boolean>('isOpen');

const succeeded = ref(false);

function onSuccess(): void {
    succeeded.value = true;
}

function handleOpenChange(open: boolean): void {
    isOpen.value = open;

    if (!open) {
        succeeded.value = false;
    }
}
</script>

<template>
    <Dialog :open="isOpen" @update:open="handleOpenChange">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>RSVP for {{ props.eventName }}</DialogTitle>
                <DialogDescription>
                    Register your spot for this event.
                </DialogDescription>
            </DialogHeader>

            <div v-if="succeeded" class="space-y-4 py-2">
                <p class="text-sm text-green-600 dark:text-green-400">
                    You're registered! A confirmation email is on its way.
                </p>
                <Button class="w-full" @click="handleOpenChange(false)">
                    Close
                </Button>
            </div>

            <Form
                v-else
                v-bind="store(props.eventId)"
                class="space-y-4 py-2"
                v-slot="{ errors, processing }"
                @success="onSuccess"
            >
                <div class="grid gap-2">
                    <Label for="rsvp-name">Name</Label>
                    <Input
                        id="rsvp-name"
                        name="name"
                        type="text"
                        required
                        maxlength="255"
                        autocomplete="name"
                        placeholder="Your full name"
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="rsvp-email">Email</Label>
                    <Input
                        id="rsvp-email"
                        name="email"
                        type="email"
                        required
                        maxlength="255"
                        autocomplete="email"
                        placeholder="you@example.com"
                    />
                    <InputError :message="errors.email" />
                </div>

                <Button type="submit" class="w-full" :disabled="processing">
                    {{ processing ? 'Registering…' : 'Register' }}
                </Button>
            </Form>
        </DialogContent>
    </Dialog>
</template>
