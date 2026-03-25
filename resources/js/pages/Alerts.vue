<script setup lang="ts">
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { Plus, Bell, BellOff, Trash2, CheckCircle, XCircle, X } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import type { AlertRule, Category } from '@/types';

const props = defineProps<{ alertRules: AlertRule[]; categories: Category[] }>();
const showForm = ref(false);
const form = useForm({
    name: '', streamer_login: '', category_id: null as number | null,
    match_mode: 'always' as 'first_time' | 'always', min_viewers: null as number | null,
    language: '', keywords: [] as string[], notify_email: true, notify_discord: true,
});

const kwInput = ref('');
function addKw() { const v = kwInput.value.trim(); if (v && !form.keywords.includes(v)) form.keywords.push(v); kwInput.value = ''; }
function removeKw(kw: string) { form.keywords = form.keywords.filter(k => k !== kw); }
function submit() { form.post('/alerts', { preserveScroll: true, onSuccess: () => { form.reset(); showForm.value = false; } }); }
function toggleActive(rule: AlertRule) { router.put(`/alerts/${rule.id}`, { is_active: !rule.is_active }, { preserveScroll: true }); }
function deleteRule(rule: AlertRule) { router.delete(`/alerts/${rule.id}`, { preserveScroll: true }); }
</script>

<template>
    <AppLayout>
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Alert Rules</h2>
            <button @click="showForm = !showForm"
                class="px-4 py-2 text-sm font-medium bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors flex items-center gap-1.5">
                <Plus v-if="!showForm" class="w-4 h-4" />
                <X v-else class="w-4 h-4" />
                {{ showForm ? 'Cancel' : 'New Alert' }}
            </button>
        </div>

        <!-- New Alert Form -->
        <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0 -translate-y-2">
            <div v-if="showForm" class="mb-6 bg-white dark:bg-zinc-900 rounded-xl p-5 shadow-sm dark:shadow-none">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-zinc-400 mb-1">Rule Name *</label>
                        <input v-model="form.name" autocomplete="off" type="text" placeholder="e.g. My favorite channel"
                            class="w-full px-3 py-2 text-sm rounded-lg bg-gray-50 dark:bg-zinc-800 text-gray-900 dark:text-white outline-none focus:ring-2 focus:ring-purple-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-zinc-400 mb-1">Channel Login (optional)</label>
                        <input v-model="form.streamer_login" autocomplete="off" type="text" placeholder="Leave empty for all channels"
                            class="w-full px-3 py-2 text-sm rounded-lg bg-gray-50 dark:bg-zinc-800 text-gray-900 dark:text-white outline-none focus:ring-2 focus:ring-purple-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-zinc-400 mb-1">Category</label>
                        <select v-model="form.category_id" class="w-full px-3 py-2 text-sm rounded-lg bg-gray-50 dark:bg-zinc-800 text-gray-700 dark:text-zinc-300 outline-none focus:ring-2 focus:ring-purple-500">
                            <option :value="null">Any category</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-zinc-400 mb-1">Match Mode</label>
                        <select v-model="form.match_mode" class="w-full px-3 py-2 text-sm rounded-lg bg-gray-50 dark:bg-zinc-800 text-gray-700 dark:text-zinc-300 outline-none focus:ring-2 focus:ring-purple-500">
                            <option value="always">Every stream</option>
                            <option value="first_time">First stream of channel in category</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-zinc-400 mb-1">Min Viewers</label>
                        <input v-model.number="form.min_viewers" type="number" min="0" placeholder="0"
                            class="w-full px-3 py-2 text-sm rounded-lg bg-gray-50 dark:bg-zinc-800 text-gray-900 dark:text-white outline-none focus:ring-2 focus:ring-purple-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-zinc-400 mb-1">Language</label>
                        <input v-model="form.language" autocomplete="off" type="text" placeholder="e.g. en"
                            class="w-full px-3 py-2 text-sm rounded-lg bg-gray-50 dark:bg-zinc-800 text-gray-900 dark:text-white outline-none focus:ring-2 focus:ring-purple-500" />
                    </div>
                </div>
                <div class="mt-4">
                    <label class="block text-xs font-medium text-gray-600 dark:text-zinc-400 mb-1">Keywords</label>
                    <div class="flex flex-wrap gap-1 mb-2" v-if="form.keywords.length">
                        <span v-for="kw in form.keywords" :key="kw" class="inline-flex items-center gap-1 px-2 py-0.5 text-xs bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-300 rounded-full">
                            {{ kw }} <button @click="removeKw(kw)" class="hover:text-red-500"><X class="w-3 h-3" /></button>
                        </span>
                    </div>
                    <div class="flex gap-2">
                        <input v-model="kwInput" @keydown.enter.prevent="addKw" placeholder="Add keyword"
                            class="flex-1 px-3 py-2 text-sm rounded-lg bg-gray-50 dark:bg-zinc-800 text-gray-900 dark:text-white outline-none" />
                        <button @click="addKw" class="px-3 py-2 text-sm bg-gray-100 dark:bg-zinc-700 text-gray-700 dark:text-zinc-300 rounded-lg hover:bg-gray-200 dark:hover:bg-zinc-600">Add</button>
                    </div>
                </div>
                <div class="flex items-center gap-6 mt-4">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" v-model="form.notify_email" class="rounded text-purple-600 focus:ring-purple-500 bg-white dark:bg-zinc-800" />
                        <span class="text-sm text-gray-700 dark:text-zinc-300">Email</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" v-model="form.notify_discord" class="rounded text-purple-600 focus:ring-purple-500 bg-white dark:bg-zinc-800" />
                        <span class="text-sm text-gray-700 dark:text-zinc-300">Discord</span>
                    </label>
                </div>
                <div class="flex gap-2 mt-4">
                    <button @click="submit" :disabled="form.processing || !form.name"
                        class="px-4 py-2 text-sm font-medium bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 transition-colors">Create Alert</button>
                </div>
            </div>
        </Transition>

        <!-- Empty -->
        <div v-if="alertRules.length === 0 && !showForm" class="text-center py-16">
            <Bell class="w-16 h-16 mx-auto text-gray-300 dark:text-zinc-700" :stroke-width="1" />
            <h3 class="mt-4 text-lg font-semibold text-gray-700 dark:text-zinc-300">No alert rules</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-zinc-500">Create a rule to get notified when streams match your criteria.</p>
        </div>

        <!-- Rules List -->
        <TransitionGroup v-else name="list" tag="div" class="space-y-3">
            <div v-for="rule in alertRules" :key="rule.id"
                :class="['bg-white dark:bg-zinc-900 rounded-xl p-4 shadow-sm dark:shadow-none transition-all', !rule.is_active && 'opacity-50']">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <h3 class="font-semibold text-gray-900 dark:text-white">{{ rule.name }}</h3>
                            <span :class="['px-2 py-0.5 text-[10px] font-bold uppercase rounded',
                                rule.match_mode === 'first_time' ? 'bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-300' : 'bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-zinc-400']">
                                {{ rule.match_mode === 'first_time' ? 'First in category' : 'Every stream' }}
                            </span>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 mt-1.5 text-xs text-gray-500 dark:text-zinc-500">
                            <span v-if="rule.streamer_login" class="px-2 py-0.5 bg-purple-50 dark:bg-purple-500/10 text-purple-600 dark:text-purple-400 rounded-full">@{{ rule.streamer_login }}</span>
                            <span v-if="rule.category">{{ rule.category.name }}</span>
                            <span v-else>Any category</span>
                            <span v-if="rule.min_viewers">&ge; {{ rule.min_viewers }} viewers</span>
                            <span v-if="rule.language">{{ rule.language }}</span>
                            <span v-if="rule.notify_email" class="text-blue-500">Email</span>
                            <span v-if="rule.notify_discord" class="text-indigo-500">Discord</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        <button @click="toggleActive(rule)" :class="['p-2 rounded-lg transition-colors', rule.is_active ? 'text-green-500 hover:bg-green-50 dark:hover:bg-green-500/10' : 'text-gray-400 hover:bg-gray-50 dark:hover:bg-zinc-800']">
                            <CheckCircle v-if="rule.is_active" class="w-4 h-4" />
                            <XCircle v-else class="w-4 h-4" />
                        </button>
                        <button @click="deleteRule(rule)" class="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-800 transition-colors">
                            <Trash2 class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>
        </TransitionGroup>
    </AppLayout>
</template>
