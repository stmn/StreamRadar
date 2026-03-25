<script setup lang="ts">
import { LayoutGrid, Radio, Ban } from 'lucide-vue-next';
import type { Stats } from '@/types';

defineProps<{ stats: Stats }>();

const cards = [
    { key: 'categories_count' as const, label: 'Tracked Categories', icon: LayoutGrid, gradient: 'from-purple-500/10 to-indigo-500/5 dark:from-purple-500/5 dark:to-indigo-500/[0.02]' },
    { key: 'streams_count' as const, label: 'Active Streams', icon: Radio, gradient: 'from-rose-500/10 to-pink-500/5 dark:from-rose-500/5 dark:to-pink-500/[0.02]' },
    { key: 'ignored_count' as const, label: 'Ignored Streamers', icon: Ban, gradient: 'from-amber-500/10 to-orange-500/5 dark:from-amber-500/5 dark:to-orange-500/[0.02]' },
];
</script>

<template>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex gap-3 overflow-x-auto snap-x snap-mandatory scrollbar-hide sm:grid sm:grid-cols-3 sm:overflow-visible">
            <div
                v-for="card in cards"
                :key="card.key"
                class="relative overflow-hidden rounded-xl bg-white dark:bg-zinc-900 p-4 shadow-sm dark:shadow-none transition-all hover:shadow-md min-w-[200px] snap-center sm:min-w-0"
            >
                <div :class="['absolute inset-0 bg-gradient-to-br', card.gradient]"></div>

                <div class="absolute right-3 top-1/2 -translate-y-1/2 opacity-[0.06] dark:opacity-[0.04]">
                    <component :is="card.icon" class="w-14 h-14" :stroke-width="1.5" />
                </div>

                <div class="relative">
                    <p class="text-sm font-medium text-gray-500 dark:text-zinc-400">{{ card.label }}</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                        {{ stats[card.key]?.toLocaleString() ?? 0 }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
