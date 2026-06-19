<script setup lang="ts">
import { ref } from 'vue';
import EventImage from '@/components/EventImage.vue';
import RsvpDialog from '@/components/RsvpDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatEventDate } from '@/composables/useEventDate';
import type { EventRow } from '@/types';

defineProps<{
    event: EventRow;
}>();

const rsvpOpen = ref(false);

const statusVariant = (
    status: EventRow['status'],
): 'default' | 'destructive' | 'secondary' | 'outline' => {
    switch (status) {
        case 'published':
            return 'default';
        case 'cancelled':
            return 'destructive';
        case 'sold_out':
            return 'secondary';
        default:
            return 'outline';
    }
};

const formattedPrice = (currency: string, minPrice: number): string => {
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency,
        maximumFractionDigits: 0,
    }).format(minPrice);
};
</script>

<template>
    <article
        class="flex flex-col gap-3 rounded-xl border bg-card p-4 shadow-xs"
    >
        <EventImage
            v-if="event.images.length > 0"
            :images="event.images"
            :alt="event.payload.name"
        />

        <div class="flex flex-col gap-1">
            <div class="flex items-start justify-between gap-2">
                <h2 class="text-base leading-tight font-semibold">
                    {{ event.payload.name }}
                </h2>
                <Badge :variant="statusVariant(event.status)" class="shrink-0">
                    {{ event.status.replace('_', ' ') }}
                </Badge>
            </div>

            <p class="line-clamp-2 text-sm text-muted-foreground">
                {{ event.payload.description }}
            </p>
        </div>

        <div class="flex flex-col gap-1 text-sm">
            <div class="flex items-center gap-1.5 text-muted-foreground">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="size-4 shrink-0"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" />
                    <circle cx="12" cy="10" r="3" />
                </svg>
                <span>{{ event.location_name }}</span>
            </div>

            <div class="flex items-center gap-1.5 text-muted-foreground">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="size-4 shrink-0"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <rect width="18" height="18" x="3" y="4" rx="2" ry="2" />
                    <line x1="16" x2="16" y1="2" y2="6" />
                    <line x1="8" x2="8" y1="2" y2="6" />
                    <line x1="3" x2="21" y1="10" y2="10" />
                </svg>
                <span>{{
                    formatEventDate(event.payload.schedule.starts_at)
                }}</span>
            </div>

            <div class="flex items-center gap-1.5 font-medium">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="size-4 shrink-0 text-muted-foreground"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <circle cx="12" cy="12" r="10" />
                    <path d="M12 6v6l4 2" />
                </svg>
                <span>
                    From
                    {{
                        formattedPrice(
                            event.payload.pricing.currency,
                            event.payload.pricing.min_price,
                        )
                    }}
                </span>
            </div>
        </div>

        <div class="flex flex-wrap gap-1.5">
            <Badge variant="outline">{{ event.type }}</Badge>
            <Badge
                v-for="tag in event.payload.tags"
                :key="tag"
                variant="secondary"
                class="text-xs"
            >
                {{ tag }}
            </Badge>
        </div>

        <Button
            class="mt-auto w-full"
            :disabled="
                event.status === 'cancelled' || event.status === 'sold_out'
            "
            @click="rsvpOpen = true"
        >
            {{
                event.status === 'sold_out'
                    ? 'Sold Out'
                    : event.status === 'cancelled'
                      ? 'Cancelled'
                      : 'RSVP'
            }}
        </Button>

        <RsvpDialog
            v-model:is-open="rsvpOpen"
            :event-id="event.id"
            :event-name="event.payload.name"
        />
    </article>
</template>
