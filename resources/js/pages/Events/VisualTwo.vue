<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import EventCard from '@/components/EventCard.vue';
import EventFilters from '@/components/EventFilters.vue';
import type {
    CityOption,
    EventFilters as EventFiltersType,
    EventRow,
} from '@/types';

// ---------------------------------------------------------------------------
// Props (provided by EventController@visualTwo)
// ---------------------------------------------------------------------------
const props = defineProps<{
    cities: CityOption[];
    statuses: string[];
    filters: EventFiltersType;
}>();

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------
interface CalendarCounts {
    [date: string]: number; // 'YYYY-MM-DD' -> count
}

interface CalendarResponse {
    month: string;
    counts: CalendarCounts;
    stats: { ms: number; bytes: number };
}

interface EventsDataResponse {
    data: EventRow[];
    current_page: number;
    last_page: number;
    total: number;
    stats: { ms: number; bytes: number };
}

// ---------------------------------------------------------------------------
// State — shared filters
// ---------------------------------------------------------------------------
const activeFilters = ref<EventFiltersType>({ ...props.filters });

// ---------------------------------------------------------------------------
// State — month navigation
// ---------------------------------------------------------------------------
function todayMonth(): string {
    const d = new Date();
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');

    return `${y}-${m}`;
}

function monthFromDate(dateStr: string | null | undefined): string | null {
    if (!dateStr) {
        return null;
    }

    const match = /^(\d{4}-\d{2})/.exec(dateStr);

    return match ? match[1] : null;
}

const currentMonth = ref<string>(
    monthFromDate(props.filters.from) ?? todayMonth(),
);

// ---------------------------------------------------------------------------
// Calendar math helpers
// ---------------------------------------------------------------------------

/** Day of week for the 1st of the month (0 = Sunday … 6 = Saturday). */
function firstDayOfWeek(month: string): number {
    return new Date(`${month}-01T00:00:00`).getDay();
}

/** Number of days in a given YYYY-MM month. */
function daysInMonth(month: string): number {
    const [y, m] = month.split('-').map(Number);

    // Day 0 of next month is the last day of this month
    return new Date(y, m, 0).getDate();
}

/** Build all cells for the month grid: null = blank leading/trailing. */
function buildGridDays(month: string): Array<string | null> {
    const offset = firstDayOfWeek(month);
    const total = daysInMonth(month);
    const cells: Array<string | null> = [];

    for (let i = 0; i < offset; i++) {
        cells.push(null);
    }

    for (let d = 1; d <= total; d++) {
        const dd = String(d).padStart(2, '0');
        cells.push(`${month}-${dd}`);
    }

    // Pad to complete last week row
    while (cells.length % 7 !== 0) {
        cells.push(null);
    }

    return cells;
}

/** Format YYYY-MM for display, e.g. "June 2026". */
function formatMonthLabel(month: string): string {
    const [y, m] = month.split('-').map(Number);

    return new Date(y, m - 1, 1).toLocaleDateString(undefined, {
        month: 'long',
        year: 'numeric',
    });
}

/** Navigate by ±1 month. Returns a new YYYY-MM string. */
function offsetMonth(month: string, delta: -1 | 1): string {
    const [y, m] = month.split('-').map(Number);
    const d = new Date(y, m - 1 + delta, 1);
    const ny = d.getFullYear();
    const nm = String(d.getMonth() + 1).padStart(2, '0');

    return `${ny}-${nm}`;
}

// Precomputed grid
const gridDays = computed(() => buildGridDays(currentMonth.value));

// ---------------------------------------------------------------------------
// Calendar counts fetch
// ---------------------------------------------------------------------------
const calendarCounts = ref<CalendarCounts>({});
const calendarStats = ref<{ ms: number; bytes: number } | null>(null);
const calendarLoading = ref(false);
const calendarError = ref<string | null>(null);

async function fetchCalendar(): Promise<void> {
    calendarLoading.value = true;
    calendarError.value = null;

    const params = new URLSearchParams();
    params.set('month', currentMonth.value);

    if (activeFilters.value.city) {
        params.set('city', activeFilters.value.city);
    }

    if (activeFilters.value.status) {
        params.set('status', activeFilters.value.status);
    }

    try {
        const res = await fetch(`/events/calendar?${params.toString()}`, {
            headers: { Accept: 'application/json' },
        });

        if (!res.ok) {
            throw new Error(`HTTP ${res.status}`);
        }

        const payload = (await res.json()) as CalendarResponse;
        calendarCounts.value = payload.counts;
        calendarStats.value = payload.stats;
    } catch (e) {
        calendarError.value =
            e instanceof Error ? e.message : 'Failed to load calendar';
    } finally {
        calendarLoading.value = false;
    }
}

// ---------------------------------------------------------------------------
// Day-panel fetch
// ---------------------------------------------------------------------------
const selectedDay = ref<string | null>(null);
const dayEvents = ref<EventRow[]>([]);
const dayTotal = ref(0);
const dayStats = ref<{ ms: number; bytes: number } | null>(null);
const dayLoading = ref(false);
const dayError = ref<string | null>(null);

async function fetchDayEvents(day: string): Promise<void> {
    dayLoading.value = true;
    dayError.value = null;
    dayEvents.value = [];
    dayTotal.value = 0;

    const params = new URLSearchParams();
    // Use full datetime range so created_time (unix) captures the entire day.
    // Note: strtotime on the PHP side converts these strings using the server
    // timezone, while the calendar's from_unixtime bucketing uses DB timezone.
    // Differences at day edges are acceptable.
    params.set('from', `${day} 00:00:00`);
    params.set('to', `${day} 23:59:59`);

    if (activeFilters.value.city) {
        params.set('city', activeFilters.value.city);
    }

    if (activeFilters.value.status) {
        params.set('status', activeFilters.value.status);
    }

    try {
        const res = await fetch(`/events/data?${params.toString()}`, {
            headers: { Accept: 'application/json' },
        });

        if (!res.ok) {
            throw new Error(`HTTP ${res.status}`);
        }

        const payload = (await res.json()) as EventsDataResponse;
        dayEvents.value = payload.data;
        dayTotal.value = payload.total;
        dayStats.value = payload.stats;
    } catch (e) {
        dayError.value =
            e instanceof Error ? e.message : 'Failed to load events';
    } finally {
        dayLoading.value = false;
    }
}

function selectDay(day: string | null): void {
    if (!day) {
        return;
    }

    const count = calendarCounts.value[day] ?? 0;

    if (count === 0) {
        selectedDay.value = null;

        return;
    }

    selectedDay.value = day;
    void fetchDayEvents(day);
}

function closePanel(): void {
    selectedDay.value = null;
}

// ---------------------------------------------------------------------------
// Navigation
// ---------------------------------------------------------------------------
function prevMonth(): void {
    currentMonth.value = offsetMonth(currentMonth.value, -1);
    closePanel();
}

function nextMonth(): void {
    currentMonth.value = offsetMonth(currentMonth.value, 1);
    closePanel();
}

// ---------------------------------------------------------------------------
// Filters handler
// ---------------------------------------------------------------------------
function onFilters(updated: EventFiltersType): void {
    activeFilters.value = updated;

    // If the filter carries a `from` date, navigate to that month
    const fromMonth = monthFromDate(updated.from);

    if (fromMonth && fromMonth !== currentMonth.value) {
        currentMonth.value = fromMonth;
        closePanel();

        return; // watch will trigger fetchCalendar
    }

    void fetchCalendar();

    // Refresh open panel with new filters
    if (selectedDay.value) {
        void fetchDayEvents(selectedDay.value);
    }
}

// ---------------------------------------------------------------------------
// Watchers — fetch calendar on month change
// ---------------------------------------------------------------------------
watch(
    currentMonth,
    () => {
        void fetchCalendar();
    },
    { immediate: true },
);

// ---------------------------------------------------------------------------
// Intensity badge helpers
// ---------------------------------------------------------------------------
const WEEKDAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

function countBadgeClass(count: number): string {
    if (count === 0) {
        return '';
    }

    if (count < 5) {
        return 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300';
    }

    if (count < 20) {
        return 'bg-blue-300 text-blue-800 dark:bg-blue-700/60 dark:text-blue-100';
    }

    if (count < 100) {
        return 'bg-blue-500 text-white dark:bg-blue-600';
    }

    return 'bg-blue-700 text-white dark:bg-blue-500';
}

function formatBytes(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
}

/** Format "YYYY-MM-DD" as "June 19, 2026" for the panel heading. */
function formatDayLabel(day: string): string {
    const [y, m, d] = day.split('-').map(Number);

    return new Date(y, m - 1, d).toLocaleDateString(undefined, {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    });
}
</script>

<template>
    <Head title="Events — Monthly Calendar" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <!-- Filters bar -->
        <EventFilters
            :cities="cities"
            :statuses="statuses"
            :initial="filters"
            @update:filters="onFilters"
        />

        <!-- Calendar header: nav + label + stats -->
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-input bg-background text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground disabled:opacity-50"
                    :disabled="calendarLoading"
                    aria-label="Previous month"
                    @click="prevMonth"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="16"
                        height="16"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <path d="m15 18-6-6 6-6" />
                    </svg>
                </button>

                <Transition name="month-label" mode="out-in">
                    <h2
                        :key="currentMonth"
                        class="min-w-[12rem] text-center text-lg font-semibold"
                    >
                        {{ formatMonthLabel(currentMonth) }}
                    </h2>
                </Transition>

                <button
                    type="button"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-input bg-background text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground disabled:opacity-50"
                    :disabled="calendarLoading"
                    aria-label="Next month"
                    @click="nextMonth"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="16"
                        height="16"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <path d="m9 18 6-6-6-6" />
                    </svg>
                </button>
            </div>

            <!-- Perf readout -->
            <div
                v-if="calendarStats"
                class="font-mono text-xs text-muted-foreground"
            >
                {{ calendarStats.ms.toFixed(1) }} ms &middot;
                {{ formatBytes(calendarStats.bytes) }}
            </div>
            <div v-if="calendarLoading" class="text-xs text-muted-foreground">
                Loading&hellip;
            </div>
        </div>

        <!-- Calendar error -->
        <p v-if="calendarError" class="text-sm text-red-600 dark:text-red-400">
            {{ calendarError }}
        </p>

        <!-- Calendar grid -->
        <Transition name="calendar-grid" mode="out-in">
            <div
                :key="currentMonth"
                class="w-full overflow-hidden rounded-lg border border-border"
            >
                <!-- Weekday header -->
                <div
                    class="grid grid-cols-7 border-b border-border bg-muted/50"
                >
                    <div
                        v-for="wd in WEEKDAYS"
                        :key="wd"
                        class="py-2 text-center text-xs font-medium text-muted-foreground"
                    >
                        {{ wd }}
                    </div>
                </div>

                <!-- Day cells -->
                <div class="grid grid-cols-7 divide-x divide-y divide-border">
                    <div
                        v-for="(day, idx) in gridDays"
                        :key="day ?? `blank-${idx}`"
                        class="relative min-h-[4rem] p-1"
                        :class="[
                            day === null ? 'bg-muted/20' : 'bg-background',
                            day !== null && (calendarCounts[day] ?? 0) > 0
                                ? 'cursor-pointer transition-colors hover:bg-accent/50'
                                : '',
                            day !== null && day === selectedDay
                                ? 'ring-2 ring-primary ring-inset'
                                : '',
                        ]"
                        @click="selectDay(day)"
                    >
                        <template v-if="day !== null">
                            <!-- Day number -->
                            <span
                                class="text-sm leading-none font-medium"
                                :class="
                                    (calendarCounts[day] ?? 0) > 0
                                        ? 'text-foreground'
                                        : 'text-muted-foreground/60'
                                "
                            >
                                {{ Number(day.split('-')[2]) }}
                            </span>

                            <!-- Count badge -->
                            <span
                                v-if="(calendarCounts[day] ?? 0) > 0"
                                class="absolute right-1 bottom-1 inline-flex items-center rounded-full px-1.5 py-0.5 text-xs leading-none font-semibold"
                                :class="countBadgeClass(calendarCounts[day])"
                            >
                                {{ calendarCounts[day] }}
                            </span>
                        </template>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Day panel -->
        <Transition name="day-panel">
            <div
                v-if="selectedDay !== null"
                class="flex flex-col gap-4 rounded-lg border border-border bg-background p-4"
            >
                <!-- Panel header -->
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <h3 class="text-base font-semibold">
                            {{ formatDayLabel(selectedDay) }}
                        </h3>
                        <p
                            v-if="!dayLoading && !dayError"
                            class="text-sm text-muted-foreground"
                        >
                            {{ dayTotal }} event{{ dayTotal !== 1 ? 's' : '' }}
                            <template v-if="dayStats">
                                &middot;
                                <span class="font-mono"
                                    >{{ dayStats.ms.toFixed(1) }} ms &middot;
                                    {{ formatBytes(dayStats.bytes) }}</span
                                >
                            </template>
                        </p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground"
                        aria-label="Close panel"
                        @click="closePanel"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="14"
                            height="14"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                        >
                            <path d="M18 6 6 18M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Loading / error states -->
                <p v-if="dayLoading" class="text-sm text-muted-foreground">
                    Loading events&hellip;
                </p>
                <p
                    v-else-if="dayError"
                    class="text-sm text-red-600 dark:text-red-400"
                >
                    {{ dayError }}
                </p>

                <!-- Event cards -->
                <TransitionGroup
                    v-else-if="dayEvents.length > 0"
                    tag="div"
                    name="event-list"
                    class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <EventCard
                        v-for="event in dayEvents"
                        :key="event.id"
                        :event="event"
                    />
                </TransitionGroup>

                <p v-else class="text-sm text-muted-foreground">
                    No events found for this day.
                </p>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
/* Month label crossfade */
.month-label-enter-active,
.month-label-leave-active {
    transition:
        opacity 0.2s ease,
        transform 0.2s ease;
}
.month-label-enter-from {
    opacity: 0;
    transform: translateX(8px);
}
.month-label-leave-to {
    opacity: 0;
    transform: translateX(-8px);
}

/* Calendar grid fade */
.calendar-grid-enter-active,
.calendar-grid-leave-active {
    transition: opacity 0.25s ease;
}
.calendar-grid-enter-from,
.calendar-grid-leave-to {
    opacity: 0;
}

/* Day panel slide-down */
.day-panel-enter-active,
.day-panel-leave-active {
    transition:
        opacity 0.25s ease,
        transform 0.25s ease,
        max-height 0.3s ease;
    overflow: hidden;
    max-height: 2000px;
}
.day-panel-enter-from,
.day-panel-leave-to {
    opacity: 0;
    transform: translateY(-8px);
    max-height: 0;
}

/* Event list stagger */
.event-list-move,
.event-list-enter-active,
.event-list-leave-active {
    transition: all 0.2s ease;
}
.event-list-enter-from,
.event-list-leave-to {
    opacity: 0;
    transform: scale(0.97);
}
.event-list-leave-active {
    position: absolute;
}

/* Respect reduced-motion */
@media (prefers-reduced-motion: reduce) {
    .month-label-enter-active,
    .month-label-leave-active,
    .calendar-grid-enter-active,
    .calendar-grid-leave-active,
    .day-panel-enter-active,
    .day-panel-leave-active,
    .event-list-move,
    .event-list-enter-active,
    .event-list-leave-active {
        transition: none !important;
    }
}
</style>
