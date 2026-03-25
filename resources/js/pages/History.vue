<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Search, Clock, Radio, WifiOff, Bell, RefreshCw, Trash2, Eye } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { useNow } from '@/composables/useNow';
import type { HistoryEvent, PaginatedData } from '@/types';

const props = defineProps<{ events: PaginatedData<HistoryEvent>; filters: { type: string | null; search: string | null } }>();
const searchInput = ref(props.filters.search || '');

let searchTimeout: ReturnType<typeof setTimeout>;
function onSearch(e: Event) {
    const value = (e.target as HTMLInputElement).value;
    searchInput.value = value;
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        const params: Record<string, any> = {};
        if (value) params.search = value;
        if (props.filters.type) params.type = props.filters.type;
        router.get('/history', params, { preserveState: true, preserveScroll: true });
    }, 300);
}

function filterByType(type: string | null) {
    const params: Record<string, any> = {};
    if (type) params.type = type;
    if (searchInput.value) params.search = searchInput.value;
    router.get('/history', params, { preserveState: true, preserveScroll: true });
}

function clearHistory() { router.delete('/history', { preserveScroll: true }); }

const now = useNow();

function formatTime(dateStr: string): string {
    const d = new Date(dateStr);
    const diffMin = Math.floor((now.value - d.getTime()) / 60000);
    if (diffMin < 1) return 'Just now';
    if (diffMin < 60) return `${diffMin}m ago`;
    const diffH = Math.floor(diffMin / 60);
    if (diffH < 24) return `${diffH}h ago`;
    return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

const typeConfig: Record<string, { label: string; icon: any; color: string }> = {
    stream_online: { label: 'Stream Online', icon: Radio, color: 'text-green-500 bg-green-50 dark:bg-green-500/10' },
    stream_offline: { label: 'Stream Offline', icon: WifiOff, color: 'text-gray-500 bg-gray-100 dark:bg-zinc-800' },
    alert_triggered: { label: 'Alert', icon: Bell, color: 'text-amber-500 bg-amber-50 dark:bg-amber-500/10' },
    sync_completed: { label: 'Sync', icon: RefreshCw, color: 'text-blue-500 bg-blue-50 dark:bg-blue-500/10' },
};
const types = Object.keys(typeConfig);
</script>

<template>
    <AppLayout>
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">History</h2>
            <button v-if="events.total > 0" @click="clearHistory"
                class="px-3 py-1.5 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-lg transition-colors flex items-center gap-1.5">
                <Trash2 class="w-4 h-4" /> Clear All
            </button>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 mb-6">
            <div class="relative flex-1">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                <input :value="searchInput" @input="onSearch" autocomplete="off" type="text" placeholder="Search history..."
                    class="w-full pl-9 pr-4 py-2 text-sm rounded-lg bg-white dark:bg-zinc-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-zinc-500 focus:ring-2 focus:ring-purple-500 outline-none" />
            </div>
            <div class="flex gap-1 flex-wrap">
                <button @click="filterByType(null)"
                    :class="['px-3 py-1.5 text-xs font-medium rounded-lg transition-colors', !filters.type ? 'bg-purple-50 dark:bg-purple-500/10 text-purple-700 dark:text-purple-300' : 'text-gray-500 dark:text-zinc-400 hover:bg-gray-100 dark:hover:bg-zinc-800']">
                    All
                </button>
                <button v-for="t in types" :key="t" @click="filterByType(t)"
                    :class="['px-3 py-1.5 text-xs font-medium rounded-lg transition-colors', filters.type === t ? 'bg-purple-50 dark:bg-purple-500/10 text-purple-700 dark:text-purple-300' : 'text-gray-500 dark:text-zinc-400 hover:bg-gray-100 dark:hover:bg-zinc-800']">
                    {{ typeConfig[t]?.label || t }}
                </button>
            </div>
        </div>

        <div v-if="events.data.length === 0" class="text-center py-16">
            <Clock class="w-16 h-16 mx-auto text-gray-300 dark:text-zinc-700" :stroke-width="1" />
            <h3 class="mt-4 text-lg font-semibold text-gray-700 dark:text-zinc-300">No events yet</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-zinc-500">Events will appear here after syncing streams.</p>
        </div>

        <div v-else class="bg-white dark:bg-zinc-900 rounded-xl shadow-sm dark:shadow-none overflow-hidden divide-y divide-gray-100/60 dark:divide-zinc-800/60">
            <div v-for="event in events.data" :key="event.id" class="flex items-center gap-3 px-4 py-3">
                <div :class="['w-8 h-8 rounded-lg flex items-center justify-center shrink-0', typeConfig[event.type]?.color || 'bg-gray-100 dark:bg-zinc-800 text-gray-500']">
                    <component :is="typeConfig[event.type]?.icon || Clock" class="w-4 h-4" />
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span v-if="event.streamer_name" class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ event.streamer_name }}</span>
                        <span class="text-[10px] font-bold uppercase text-gray-400 dark:text-zinc-500">{{ typeConfig[event.type]?.label || event.type }}</span>
                    </div>
                    <p v-if="event.title" class="text-xs text-gray-500 dark:text-zinc-500 truncate mt-0.5">
                        {{ event.title }}
                        <span v-if="event.category_name" class="text-purple-500 dark:text-purple-400"> — {{ event.category_name }}</span>
                    </p>
                </div>
                <div class="text-right shrink-0">
                    <div v-if="event.viewer_count" class="flex items-center gap-1 text-sm font-semibold text-gray-600 dark:text-zinc-300">
                        <Eye class="w-3.5 h-3.5" />
                        {{ event.viewer_count.toLocaleString() }}
                    </div>
                    <p class="text-xs text-gray-400 dark:text-zinc-600">{{ formatTime(event.created_at) }}</p>
                </div>
            </div>
        </div>

        <div v-if="events.last_page > 1" class="flex justify-center gap-1 mt-6">
            <template v-for="link in events.links" :key="link.label">
                <component :is="link.url ? 'a' : 'span'" :href="link.url || undefined"
                    @click.prevent="link.url && router.get(link.url, {}, { preserveState: true })"
                    :class="['px-3 py-1 text-sm rounded-lg transition-colors cursor-pointer',
                        link.active ? 'bg-purple-600 text-white' : link.url ? 'text-gray-600 dark:text-zinc-400 hover:bg-gray-100 dark:hover:bg-zinc-800' : 'text-gray-300 dark:text-zinc-700 cursor-default']"
                    v-html="link.label" />
            </template>
        </div>
    </AppLayout>
</template>
