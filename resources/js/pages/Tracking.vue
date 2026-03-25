<script setup lang="ts">
import { ref, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { Search, Pencil, Trash2, X, Package, Loader2, Plus, Check, ArrowUpDown, RefreshCw, LayoutGrid, User } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Category } from '@/types';

interface TrackedChannel {
    id: number;
    twitch_user_id: string;
    user_login: string;
    user_name: string;
    profile_image_url: string | null;
    is_active: boolean;
}

const props = defineProps<{
    categories: Category[];
    channels: TrackedChannel[];
    sort: string;
    tab: string;
}>();

const activeTab = ref(props.tab || 'categories');

// ---- Categories ----
const searchQuery = ref('');
const searchResults = ref<any[]>([]);
const searching = ref(false);
const searchError = ref('');
const highlightedIndex = ref(-1);

let searchTimeout: ReturnType<typeof setTimeout>;
function onSearch() {
    clearTimeout(searchTimeout);
    searchError.value = '';
    highlightedIndex.value = -1;
    if (searchQuery.value.length < 2) { searchResults.value = []; return; }
    searchTimeout = setTimeout(async () => {
        searching.value = true;
        try {
            const { data } = await window.axios.get('/tracking/search', { params: { query: searchQuery.value } });
            searchResults.value = Array.isArray(data) ? data : [];
        } catch (e: any) { searchError.value = e.response?.data?.error || e.message || 'Search failed'; }
        finally { searching.value = false; }
    }, 400);
}

function onKeydown(e: KeyboardEvent) {
    if (searchResults.value.length === 0) return;
    if (e.key === 'ArrowDown') { e.preventDefault(); highlightedIndex.value = Math.min(highlightedIndex.value + 1, searchResults.value.length - 1); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); highlightedIndex.value = Math.max(highlightedIndex.value - 1, 0); }
    else if (e.key === 'Enter') {
        e.preventDefault();
        const idx = highlightedIndex.value >= 0 ? highlightedIndex.value : 0;
        const result = searchResults.value[idx];
        if (result && !result.is_tracked) trackCategory(result);
    }
}

const tracking = ref<string | null>(null);
function trackCategory(cat: any) {
    tracking.value = cat.id;
    router.post('/tracking/categories', { twitch_id: cat.id, name: cat.name, box_art_url: cat.box_art_url || null }, {
        preserveScroll: true,
        onSuccess: () => { searchResults.value = searchResults.value.map(r => r.id === cat.id ? { ...r, is_tracked: true } : r); },
        onFinish: () => { tracking.value = null; },
    });
}

function changeSort(sort: string) { router.get('/tracking', { sort, tab: activeTab.value }, { preserveState: true, preserveScroll: true }); }

const editingId = ref<number | null>(null);
const editForm = useForm({ is_active: true, notifications_enabled: true, use_global_filters: true, min_viewers: null as number | null, languages: [] as string[], keywords: [] as string[] });
function startEdit(cat: Category) { editingId.value = cat.id; editForm.is_active = cat.is_active; editForm.notifications_enabled = cat.notifications_enabled; editForm.use_global_filters = cat.use_global_filters; editForm.min_viewers = cat.min_viewers; editForm.languages = cat.languages || []; editForm.keywords = cat.keywords || []; }
function saveEdit(cat: Category) { editForm.put(`/tracking/categories/${cat.id}`, { preserveScroll: true, onSuccess: () => { editingId.value = null; } }); }
function deleteCategory(cat: Category) { router.delete(`/tracking/categories/${cat.id}`, { preserveScroll: true }); }

const syncingCat = ref<number | null>(null);
function syncCat(cat: Category) { syncingCat.value = cat.id; router.post(`/tracking/categories/${cat.id}/sync`, {}, { preserveScroll: true, onFinish: () => { syncingCat.value = null; } }); }

const langInput = ref(''); const kwInput = ref('');
function addLang() { const v = langInput.value.trim().toLowerCase(); if (v && !editForm.languages.includes(v)) editForm.languages.push(v); langInput.value = ''; }
function removeLang(l: string) { editForm.languages = editForm.languages.filter(x => x !== l); }
function addKw() { const v = kwInput.value.trim(); if (v && !editForm.keywords.includes(v)) editForm.keywords.push(v); kwInput.value = ''; }
function removeKw(k: string) { editForm.keywords = editForm.keywords.filter(x => x !== k); }

function boxArt(url: string | null, w = 80, h = 107): string { if (!url) return ''; return url.replace('{width}', String(w)).replace('{height}', String(h)); }
const totalViewers = (cat: any) => cat.streams_sum_viewer_count ?? 0;

// ---- Channels ----
const channelInput = ref('');
const addingChannel = ref(false);

function trackChannel() {
    const login = channelInput.value.trim();
    if (!login) return;
    addingChannel.value = true;
    router.post('/tracking/channels', { user_login: login }, {
        preserveScroll: true,
        onSuccess: () => { channelInput.value = ''; },
        onFinish: () => { addingChannel.value = false; },
    });
}

function deleteChannel(ch: TrackedChannel) { router.delete(`/tracking/channels/${ch.id}`, { preserveScroll: true }); }
</script>

<template>
    <AppLayout>
        <!-- Tab switcher -->
        <div class="flex items-center gap-2 mb-6">
            <button @click="activeTab = 'categories'" :class="['flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-colors', activeTab === 'categories' ? 'bg-purple-50 dark:bg-purple-500/10 text-purple-600 dark:text-purple-400' : 'text-gray-500 dark:text-zinc-400 hover:bg-gray-100 dark:hover:bg-zinc-800']">
                <LayoutGrid class="w-4 h-4" /> Categories
                <span v-if="categories.length" class="px-1.5 py-0.5 text-[10px] font-semibold rounded-full bg-gray-100 dark:bg-zinc-800 text-gray-500 dark:text-zinc-400">{{ categories.length }}</span>
            </button>
            <button @click="activeTab = 'channels'" :class="['flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-colors', activeTab === 'channels' ? 'bg-purple-50 dark:bg-purple-500/10 text-purple-600 dark:text-purple-400' : 'text-gray-500 dark:text-zinc-400 hover:bg-gray-100 dark:hover:bg-zinc-800']">
                <User class="w-4 h-4" /> Channels
                <span v-if="channels.length" class="px-1.5 py-0.5 text-[10px] font-semibold rounded-full bg-gray-100 dark:bg-zinc-800 text-gray-500 dark:text-zinc-400">{{ channels.length }}</span>
            </button>
        </div>

        <!-- ==================== CATEGORIES TAB ==================== -->
        <div v-if="activeTab === 'categories'">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Search Panel -->
                <div class="lg:col-span-1">
                    <div class="bg-white dark:bg-zinc-900 rounded-xl p-5 shadow-sm dark:shadow-none">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Track Category</h2>
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                            <input v-model="searchQuery" @input="onSearch" @keydown="onKeydown" autocomplete="off" type="text" placeholder="Search Twitch categories..."
                                class="w-full pl-9 pr-10 py-2.5 text-sm rounded-lg bg-gray-50 dark:bg-zinc-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-zinc-500 focus:ring-2 focus:ring-purple-500 outline-none" />
                            <Loader2 v-if="searching" class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-purple-500 animate-spin" />
                        </div>
                        <div v-if="searchError" class="mt-4 text-sm text-red-500">{{ searchError }}</div>
                        <div v-if="searchResults.length > 0" class="mt-3 space-y-1 max-h-96 overflow-y-auto">
                            <div v-for="(result, idx) in searchResults" :key="result.id"
                                :class="['flex items-center gap-3 p-2.5 rounded-lg transition-colors', idx === highlightedIndex ? 'bg-purple-50 dark:bg-purple-500/10' : 'hover:bg-gray-50 dark:hover:bg-zinc-800/60']"
                                @mouseenter="highlightedIndex = idx">
                                <img v-if="result.box_art_url" :src="boxArt(result.box_art_url, 40, 54)" :alt="result.name" class="w-8 h-10 rounded object-cover shrink-0" />
                                <div v-else class="w-8 h-10 bg-gray-200 dark:bg-zinc-700 rounded shrink-0"></div>
                                <span class="flex-1 text-sm font-medium text-gray-900 dark:text-white truncate">{{ result.name }}</span>
                                <button v-if="!result.is_tracked" @click="trackCategory(result)" :disabled="tracking === result.id"
                                    class="shrink-0 px-3 py-1 text-xs font-medium bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors flex items-center gap-1 disabled:opacity-50">
                                    <Loader2 v-if="tracking === result.id" class="w-3 h-3 animate-spin" />
                                    <Plus v-else class="w-3 h-3" /> Track
                                </button>
                                <span v-else class="shrink-0 px-3 py-1 text-xs font-medium text-green-600 dark:text-green-400 flex items-center gap-1"><Check class="w-3 h-3" /> Tracked</span>
                            </div>
                        </div>
                        <div v-else-if="searchQuery.length >= 2 && !searching && !searchError" class="mt-4 text-center text-sm text-gray-400 dark:text-zinc-500">No categories found.</div>
                        <p v-if="searchResults.length > 0" class="mt-2 text-[11px] text-gray-400 dark:text-zinc-600">
                            <kbd class="px-1 py-0.5 rounded bg-gray-100 dark:bg-zinc-800 text-[10px]">↑↓</kbd> navigate, <kbd class="px-1 py-0.5 rounded bg-gray-100 dark:bg-zinc-800 text-[10px]">Enter</kbd> track
                        </p>
                    </div>
                </div>

                <!-- Categories List -->
                <div class="lg:col-span-2">
                    <div v-if="categories.length > 0" class="flex items-center gap-2 mb-4">
                        <ArrowUpDown class="w-4 h-4 text-gray-400" />
                        <span class="text-xs text-gray-500 dark:text-zinc-500 mr-1">Sort:</span>
                        <button v-for="s in [{ key: 'name', label: 'Name' }, { key: 'streams', label: 'Streams' }, { key: 'viewers', label: 'Viewers' }]"
                            :key="s.key" @click="changeSort(s.key)"
                            :class="['px-2.5 py-1 text-xs font-medium rounded-lg transition-colors', sort === s.key ? 'bg-purple-50 dark:bg-purple-500/10 text-purple-600 dark:text-purple-400' : 'text-gray-500 dark:text-zinc-400 hover:bg-gray-100 dark:hover:bg-zinc-800']">
                            {{ s.label }}
                        </button>
                    </div>
                    <div v-if="categories.length === 0" class="text-center py-16">
                        <Package class="w-16 h-16 mx-auto text-gray-300 dark:text-zinc-700" :stroke-width="1" />
                        <h3 class="mt-4 text-lg font-semibold text-gray-700 dark:text-zinc-300">No categories tracked</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-zinc-500">Search for a Twitch category to start tracking.</p>
                    </div>
                    <TransitionGroup v-else name="list" tag="div" class="space-y-3">
                        <div v-for="cat in categories" :key="cat.id" class="bg-white dark:bg-zinc-900 rounded-xl shadow-sm dark:shadow-none overflow-hidden">
                            <div class="flex items-center gap-4 p-4">
                                <img v-if="cat.box_art_url" :src="boxArt(cat.box_art_url)" :alt="cat.name" class="w-14 h-18 rounded-lg object-cover shrink-0" />
                                <div v-else class="w-14 h-18 bg-gray-200 dark:bg-zinc-700 rounded-lg shrink-0"></div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-semibold text-gray-900 dark:text-white truncate">{{ cat.name }}</h3>
                                        <span v-if="!cat.is_active" class="px-1.5 py-0.5 text-[10px] font-medium bg-gray-100 dark:bg-zinc-800 text-gray-500 dark:text-zinc-400 rounded">PAUSED</span>
                                    </div>
                                    <div class="flex items-center gap-3 mt-1 text-xs text-gray-500 dark:text-zinc-500">
                                        <span>{{ cat.streams_count ?? 0 }} streams</span>
                                        <span v-if="totalViewers(cat) > 0">{{ totalViewers(cat).toLocaleString() }} viewers</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    <button @click="syncCat(cat)" :disabled="syncingCat === cat.id" class="p-2 text-gray-400 hover:text-purple-600 dark:hover:text-purple-400 transition-colors rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-800 disabled:opacity-50" v-tooltip="'Sync'">
                                        <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': syncingCat === cat.id }" />
                                    </button>
                                    <button @click="editingId === cat.id ? editingId = null : startEdit(cat)" class="p-2 text-gray-400 hover:text-purple-600 dark:hover:text-purple-400 transition-colors rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-800">
                                        <Pencil class="w-4 h-4" />
                                    </button>
                                    <button @click="deleteCategory(cat)" class="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-800">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                            <!-- Edit panel (same as before) -->
                            <div v-if="editingId === cat.id" class="p-4 bg-gray-50 dark:bg-zinc-950/50">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <label class="flex items-center gap-2"><input type="checkbox" v-model="editForm.is_active" class="rounded text-purple-600 focus:ring-purple-500 bg-white dark:bg-zinc-800" /><span class="text-sm text-gray-700 dark:text-zinc-300">Active</span></label>
                                    <label class="flex items-center gap-2"><input type="checkbox" v-model="editForm.notifications_enabled" class="rounded text-purple-600 focus:ring-purple-500 bg-white dark:bg-zinc-800" /><span class="text-sm text-gray-700 dark:text-zinc-300">Notifications</span></label>
                                    <label class="flex items-center gap-2"><input type="checkbox" v-model="editForm.use_global_filters" class="rounded text-purple-600 focus:ring-purple-500 bg-white dark:bg-zinc-800" /><span class="text-sm text-gray-700 dark:text-zinc-300">Use global filters</span></label>
                                </div>
                                <div v-if="!editForm.use_global_filters" class="mt-4 space-y-3">
                                    <div>
                                        <label class="text-xs font-medium text-gray-600 dark:text-zinc-400">Min Viewers</label>
                                        <input autocomplete="off" type="number" v-model.number="editForm.min_viewers" min="0" placeholder="0" class="mt-1 w-full px-3 py-2 text-sm rounded-lg bg-white dark:bg-zinc-800 text-gray-900 dark:text-white outline-none focus:ring-2 focus:ring-purple-500" />
                                    </div>
                                    <div>
                                        <label class="text-xs font-medium text-gray-600 dark:text-zinc-400">Languages</label>
                                        <div class="flex flex-wrap gap-1 mt-1">
                                            <span v-for="lang in editForm.languages" :key="lang" class="inline-flex items-center gap-1 px-2 py-0.5 text-xs bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-300 rounded-full">{{ lang }} <button @click="removeLang(lang)" class="hover:text-red-500"><X class="w-3 h-3" /></button></span>
                                        </div>
                                        <div class="flex gap-2 mt-1">
                                            <input v-model="langInput" @keydown.enter.prevent="addLang" autocomplete="off" type="text" placeholder="e.g. en" class="flex-1 px-3 py-1.5 text-sm rounded-lg bg-white dark:bg-zinc-800 text-gray-900 dark:text-white outline-none" />
                                            <button @click="addLang" class="px-3 py-1.5 text-xs font-medium bg-gray-100 dark:bg-zinc-700 text-gray-700 dark:text-zinc-300 rounded-lg hover:bg-gray-200 dark:hover:bg-zinc-600">Add</button>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="text-xs font-medium text-gray-600 dark:text-zinc-400">Keywords</label>
                                        <div class="flex flex-wrap gap-1 mt-1">
                                            <span v-for="kw in editForm.keywords" :key="kw" class="inline-flex items-center gap-1 px-2 py-0.5 text-xs bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-300 rounded-full">{{ kw }} <button @click="removeKw(kw)" class="hover:text-red-500"><X class="w-3 h-3" /></button></span>
                                        </div>
                                        <div class="flex gap-2 mt-1">
                                            <input v-model="kwInput" @keydown.enter.prevent="addKw" autocomplete="off" type="text" placeholder="keyword" class="flex-1 px-3 py-1.5 text-sm rounded-lg bg-white dark:bg-zinc-800 text-gray-900 dark:text-white outline-none" />
                                            <button @click="addKw" class="px-3 py-1.5 text-xs font-medium bg-gray-100 dark:bg-zinc-700 text-gray-700 dark:text-zinc-300 rounded-lg hover:bg-gray-200 dark:hover:bg-zinc-600">Add</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex gap-2 mt-4">
                                    <button @click="saveEdit(cat)" :disabled="editForm.processing" class="px-4 py-2 text-sm font-medium bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 transition-colors">Save</button>
                                    <button @click="editingId = null" class="px-4 py-2 text-sm font-medium text-gray-600 dark:text-zinc-400 hover:bg-gray-100 dark:hover:bg-zinc-800 rounded-lg transition-colors">Cancel</button>
                                </div>
                            </div>
                        </div>
                    </TransitionGroup>
                </div>
            </div>
        </div>

        <!-- ==================== CHANNELS TAB ==================== -->
        <div v-if="activeTab === 'channels'">
            <!-- Add channel -->
            <form @submit.prevent="trackChannel" class="flex gap-2 mb-6">
                <input v-model="channelInput" autocomplete="off" type="text" placeholder="Channel login (e.g. shroud)..."
                    class="flex-1 max-w-sm px-3 py-2 text-sm rounded-lg bg-white dark:bg-zinc-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-zinc-500 outline-none focus:ring-2 focus:ring-purple-500" />
                <button type="submit" :disabled="addingChannel || !channelInput.trim()"
                    class="px-4 py-2 text-sm font-medium bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 transition-colors flex items-center gap-1.5">
                    <Loader2 v-if="addingChannel" class="w-4 h-4 animate-spin" />
                    <Plus v-else class="w-4 h-4" />
                    Track
                </button>
            </form>

            <div v-if="channels.length === 0" class="text-center py-16">
                <User class="w-16 h-16 mx-auto text-gray-300 dark:text-zinc-700" :stroke-width="1" />
                <h3 class="mt-4 text-lg font-semibold text-gray-700 dark:text-zinc-300">No channels tracked</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-zinc-500">Add a Twitch channel login to track their streams.</p>
            </div>

            <TransitionGroup v-else name="list" tag="div" class="bg-white dark:bg-zinc-900 rounded-xl shadow-sm dark:shadow-none overflow-hidden divide-y divide-gray-100/60 dark:divide-zinc-800/60">
                <div v-for="ch in channels" :key="ch.id" class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-zinc-800/60 transition-colors">
                    <img v-if="ch.profile_image_url" :src="ch.profile_image_url" :alt="ch.user_name" class="w-9 h-9 rounded-full shrink-0" />
                    <div v-else class="w-9 h-9 rounded-full bg-gray-200 dark:bg-zinc-700 shrink-0"></div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ ch.user_name }}</p>
                        <p class="text-xs text-gray-500 dark:text-zinc-500">@{{ ch.user_login }}</p>
                    </div>
                    <button @click="deleteChannel(ch)" class="shrink-0 p-1.5 text-gray-400 hover:text-red-500 dark:hover:text-red-400 rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-800 transition-colors">
                        <Trash2 class="w-4 h-4" />
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </AppLayout>
</template>
