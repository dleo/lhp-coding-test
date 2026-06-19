<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import EventCard from '@/components/EventCard.vue';
import EventFilters from '@/components/EventFilters.vue';
import type {
    CityOption,
    EventFilters as EventFiltersType,
    EventRow,
} from '@/types';

// ---------------------------------------------------------------------------
// Props — supplied by EventController@visualOne
// ---------------------------------------------------------------------------
const props = defineProps<{
    cities: CityOption[];
    statuses: string[];
    filters: EventFiltersType;
}>();

// ---------------------------------------------------------------------------
// Filter state — seeded from server-side filters prop
// ---------------------------------------------------------------------------
const form = reactive<EventFiltersType>({
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
    city: props.filters.city ?? '',
    status: props.filters.status ?? '',
});

// ---------------------------------------------------------------------------
// Pagination + data state
// ---------------------------------------------------------------------------
const rows = ref<EventRow[]>([]);
const page = ref(0);
const lastPage = ref<number | null>(null);
const total = ref<number | null>(null);
const loadedBytes = ref(0);
const loadedMs = ref(0);
const loading = ref(false);
const hasLoadedOnce = ref(false);

const sentinel = ref<HTMLElement | null>(null);
let observer: IntersectionObserver | null = null;

// ---------------------------------------------------------------------------
// Derived display values
// ---------------------------------------------------------------------------
const hasMore = computed(
    () => lastPage.value === null || page.value < lastPage.value,
);

const loadedSize = computed(() => {
    const kb = loadedBytes.value / 1024;

    return kb < 1024 ? `${kb.toFixed(1)} KB` : `${(kb / 1024).toFixed(2)} MB`;
});

const loadedSeconds = computed(() => (loadedMs.value / 1000).toFixed(1));

// ---------------------------------------------------------------------------
// Infinite scroll — mirrors Index.vue loadMore exactly
// ---------------------------------------------------------------------------
async function loadMore(): Promise<void> {
    if (loading.value || !hasMore.value) {
        return;
    }

    loading.value = true;

    const params = new URLSearchParams({ page: String(page.value + 1) });

    if (form.status) {
        params.set('status', form.status);
    }

    if (form.from) {
        params.set('from', form.from);
    }

    if (form.to) {
        params.set('to', form.to);
    }

    if (form.city) {
        params.set('city', form.city);
    }

    try {
        const response = await fetch(`/events/data?${params.toString()}`, {
            headers: { Accept: 'application/json' },
        });
        const payload = await response.json();

        rows.value.push(...(payload.data as EventRow[]));
        page.value = payload.current_page as number;
        lastPage.value = payload.last_page as number;
        total.value = payload.total as number;
        loadedBytes.value += payload.stats.bytes as number;
        loadedMs.value += payload.stats.ms as number;
        hasLoadedOnce.value = true;
    } finally {
        loading.value = false;
    }
}

function applyFilters(): void {
    rows.value = [];
    page.value = 0;
    lastPage.value = null;
    total.value = null;
    loadedBytes.value = 0;
    loadedMs.value = 0;
    hasLoadedOnce.value = false;
    loadMore();
}

// ---------------------------------------------------------------------------
// Filter handler — EventFilters emits a new EventFiltersType object
// ---------------------------------------------------------------------------
function onFilters(next: EventFiltersType): void {
    form.from = next.from;
    form.to = next.to;
    form.city = next.city;
    form.status = next.status;
    applyFilters();
}

// ---------------------------------------------------------------------------
// Stagger helpers
// Caps at 12 so cards loaded on page 2+ don't wait forever.
// ---------------------------------------------------------------------------
const STAGGER_CAP = 12;
const STAGGER_MS = 60; // ms per card step

function cardDelay(index: number): string {
    return `${(index % STAGGER_CAP) * STAGGER_MS}ms`;
}

// ---------------------------------------------------------------------------
// Lifecycle
// ---------------------------------------------------------------------------
onMounted(() => {
    observer = new IntersectionObserver(
        (entries) => {
            if (entries[0]?.isIntersecting) {
                loadMore();
            }
        },
        { rootMargin: '400px' },
    );

    if (sentinel.value) {
        observer.observe(sentinel.value);
    }

    loadMore();
});

onBeforeUnmount(() => observer?.disconnect());
</script>

<template>
    <Head title="Events — Visual One" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <!-- Page header -->
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Events</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                {{
                    total !== null
                        ? `${total.toLocaleString()} events`
                        : 'Loading…'
                }}
            </p>
        </div>

        <!-- Shared filter bar -->
        <EventFilters
            :cities="cities"
            :initial="filters"
            @update:filters="onFilters"
        />

        <!-- Animated card grid -->
        <!--
            TransitionGroup handles entrance animation.
            Each card gets a per-index transition-delay (capped) via an inline style.
            The `.card-enter-*` classes live in the <style> block below.
            prefers-reduced-motion is respected via a @media rule that zeros the
            transform and sets a minimal opacity transition only.
        -->
        <TransitionGroup
            tag="div"
            name="card"
            class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
        >
            <div
                v-for="(event, index) in rows"
                :key="event.id"
                class="card-wrapper"
                :style="{ '--stagger-delay': cardDelay(index) }"
            >
                <EventCard :event="event" />
            </div>
        </TransitionGroup>

        <!-- Empty state -->
        <div
            v-if="hasLoadedOnce && !loading && rows.length === 0"
            class="flex flex-col items-center justify-center gap-2 py-16 text-center"
        >
            <p class="text-lg font-medium">No events found</p>
            <p class="text-sm text-muted-foreground">
                Try adjusting your filters.
            </p>
        </div>

        <!-- End-of-list indicator -->
        <p
            v-if="hasLoadedOnce && !hasMore && rows.length > 0"
            class="text-center text-sm text-muted-foreground"
        >
            All {{ total?.toLocaleString() }} events loaded.
        </p>

        <!-- Intersection observer sentinel -->
        <div ref="sentinel" aria-hidden="true"></div>

        <!-- Stats readout -->
        <div class="text-xs text-muted-foreground">
            <span v-if="loading">Loading…</span>
            <span v-else-if="hasLoadedOnce">
                {{ loadedSize }} fetched in {{ loadedSeconds }}s
            </span>
        </div>
    </div>
</template>

<style scoped>
/*
 * Entrance animation for TransitionGroup name="card".
 * Each .card-wrapper reads --stagger-delay set inline by cardDelay().
 */
.card-wrapper {
    transition-delay: var(--stagger-delay, 0ms);
}

/* Entering: start invisible + shifted down */
.card-enter-from {
    opacity: 0;
    transform: translateY(20px) scale(0.97);
}

.card-enter-active {
    transition:
        opacity 0.35s ease,
        transform 0.35s ease;
    transition-delay: var(--stagger-delay, 0ms);
}

.card-enter-to {
    opacity: 1;
    transform: translateY(0) scale(1);
}

/* Hover lift — applied to the wrapper so the full card area is interactive */
.card-wrapper {
    transition:
        transform 0.2s ease,
        box-shadow 0.2s ease;
}

.card-wrapper:hover {
    transform: translateY(-4px) scale(1.015);
    box-shadow:
        0 8px 24px -4px rgb(0 0 0 / 0.12),
        0 4px 8px -2px rgb(0 0 0 / 0.08);
    z-index: 1;
}

/* Respect prefers-reduced-motion — disable all transforms, keep fade */
@media (prefers-reduced-motion: reduce) {
    .card-enter-from {
        opacity: 0;
        transform: none;
    }

    .card-enter-active {
        transition: opacity 0.2s ease;
        transition-delay: 0ms;
    }

    .card-wrapper {
        transition: none;
    }

    .card-wrapper:hover {
        transform: none;
        box-shadow: none;
    }
}
</style>
