<script setup lang="ts">
import { Sun, Moon, Monitor } from 'lucide-vue-next';
import { useTheme } from '@/composables/useTheme';

const { theme, setTheme } = useTheme();

function cycle() {
    const order = ['light', 'dark', 'system'] as const;
    const idx = order.indexOf(theme.value);
    setTheme(order[(idx + 1) % order.length]);
}
</script>

<template>
    <button
        @click="cycle"
        class="p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-zinc-400 dark:hover:text-zinc-200 dark:hover:bg-zinc-800 transition-colors"
        v-tooltip="`Theme: ${theme}`"
    >
        <Sun v-if="theme === 'light'" class="w-5 h-5" />
        <Moon v-else-if="theme === 'dark'" class="w-5 h-5" />
        <Monitor v-else class="w-5 h-5" />
    </button>
</template>
