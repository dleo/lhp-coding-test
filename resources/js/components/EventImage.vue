<script setup lang="ts">
import { ref } from 'vue';

const props = defineProps<{
    images: string[];
    alt: string;
}>();

const current = ref(0);

function prev(): void {
    current.value =
        (current.value - 1 + props.images.length) % props.images.length;
}

function next(): void {
    current.value = (current.value + 1) % props.images.length;
}

function goTo(index: number): void {
    current.value = index;
}
</script>

<template>
    <div
        class="relative w-full overflow-hidden rounded-lg bg-muted"
        role="region"
        :aria-label="`${alt} image carousel`"
    >
        <div class="relative aspect-video w-full">
            <img
                v-for="(src, i) in images"
                :key="src"
                :src="src"
                :alt="`${alt} — image ${i + 1} of ${images.length}`"
                loading="lazy"
                class="absolute inset-0 h-full w-full object-cover transition-opacity duration-300"
                :class="i === current ? 'opacity-100' : 'opacity-0'"
            />
        </div>

        <template v-if="images.length > 1">
            <button
                type="button"
                class="absolute top-1/2 left-2 -translate-y-1/2 rounded-full bg-black/40 p-1.5 text-white hover:bg-black/60 focus-visible:outline focus-visible:outline-2 focus-visible:outline-ring"
                aria-label="Previous image"
                @click="prev"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="size-4"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <polyline points="15 18 9 12 15 6" />
                </svg>
            </button>

            <button
                type="button"
                class="absolute top-1/2 right-2 -translate-y-1/2 rounded-full bg-black/40 p-1.5 text-white hover:bg-black/60 focus-visible:outline focus-visible:outline-2 focus-visible:outline-ring"
                aria-label="Next image"
                @click="next"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="size-4"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <polyline points="9 18 15 12 9 6" />
                </svg>
            </button>

            <div
                class="absolute bottom-2 left-1/2 flex -translate-x-1/2 gap-1.5"
                role="tablist"
                :aria-label="`Image ${current + 1} of ${images.length}`"
            >
                <button
                    v-for="(_, i) in images"
                    :key="i"
                    type="button"
                    role="tab"
                    :aria-selected="i === current"
                    :aria-label="`Go to image ${i + 1}`"
                    class="size-2 rounded-full transition-colors"
                    :class="
                        i === current
                            ? 'bg-white'
                            : 'bg-white/50 hover:bg-white/75'
                    "
                    @click="goTo(i)"
                />
            </div>
        </template>
    </div>
</template>
