<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { CityOption, EventFilters, EventStatus } from '@/types';

const props = defineProps<{
    cities: CityOption[];
    initial?: Partial<EventFilters>;
}>();

const emit = defineEmits<{
    'update:filters': [filters: EventFilters];
}>();

// reka-ui's SelectItem rejects an empty-string value, so the "All" option uses
// a sentinel that is normalized back to '' in the emitted filter payload.
const ALL_VALUE = 'all';

const statuses: EventStatus[] = ['draft', 'published', 'cancelled', 'sold_out'];

const from = ref(props.initial?.from ?? '');
const to = ref(props.initial?.to ?? '');
const city = ref(props.initial?.city || ALL_VALUE);
const status = ref(props.initial?.status || ALL_VALUE);

function normalize(value: string): string {
    return value === ALL_VALUE ? '' : value;
}

function applyFilters(): void {
    emit('update:filters', {
        from: from.value,
        to: to.value,
        city: normalize(city.value),
        status: normalize(status.value),
    });
}

function resetFilters(): void {
    from.value = '';
    to.value = '';
    city.value = ALL_VALUE;
    status.value = ALL_VALUE;
    emit('update:filters', {
        from: '',
        to: '',
        city: '',
        status: '',
    });
}
</script>

<template>
    <form class="flex flex-wrap items-end gap-3" @submit.prevent="applyFilters">
        <div class="flex flex-col gap-1.5">
            <Label for="filter-from">From</Label>
            <Input
                id="filter-from"
                v-model="from"
                type="date"
                class="h-9 w-36"
            />
        </div>

        <div class="flex flex-col gap-1.5">
            <Label for="filter-to">To</Label>
            <Input id="filter-to" v-model="to" type="date" class="h-9 w-36" />
        </div>

        <div class="flex flex-col gap-1.5">
            <Label for="filter-city">City</Label>
            <Select v-model="city">
                <SelectTrigger id="filter-city" class="w-48">
                    <SelectValue placeholder="All cities" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="ALL_VALUE">All cities</SelectItem>
                    <SelectItem
                        v-for="(c, index) in cities"
                        :key="index"
                        :value="String(index)"
                    >
                        {{ c.name }}, {{ c.country }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div class="flex flex-col gap-1.5">
            <Label for="filter-status">Status</Label>
            <Select v-model="status">
                <SelectTrigger id="filter-status" class="w-40">
                    <SelectValue placeholder="All statuses" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="ALL_VALUE">All statuses</SelectItem>
                    <SelectItem v-for="s in statuses" :key="s" :value="s">
                        {{ s.replace('_', ' ') }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div class="flex gap-2">
            <Button type="submit">Filter</Button>
            <Button type="button" variant="outline" @click="resetFilters">
                Reset
            </Button>
        </div>
    </form>
</template>
