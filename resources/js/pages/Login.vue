<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { Radar, LogIn } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage();
const flash = computed(() => page.props.flash as { error?: string });

const form = useForm({
    username: '',
    password: '',
});

function submit() {
    form.post('/login', {
        onFinish: () => { form.password = ''; },
    });
}
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-zinc-950 flex items-center justify-center px-4">
        <div class="w-full max-w-sm">
            <div class="text-center mb-8">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center mx-auto mb-4">
                    <Radar class="w-7 h-7 text-white" :stroke-width="2" />
                </div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">StreamRadar</h1>
                <p class="text-sm text-gray-500 dark:text-zinc-500 mt-1">Sign in to continue</p>
            </div>

            <form @submit.prevent="submit" class="bg-white dark:bg-zinc-900 rounded-xl p-6 shadow-sm dark:shadow-none space-y-4">
                <div v-if="flash.error" class="text-sm text-red-500 text-center">{{ flash.error }}</div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-zinc-400 mb-1">Username</label>
                    <input v-model="form.username" autocomplete="username" type="text" autofocus
                        class="w-full px-3 py-2.5 text-sm rounded-lg bg-gray-50 dark:bg-zinc-800 text-gray-900 dark:text-white outline-none focus:ring-2 focus:ring-purple-500" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-zinc-400 mb-1">Password</label>
                    <input v-model="form.password" type="password" autocomplete="current-password"
                        class="w-full px-3 py-2.5 text-sm rounded-lg bg-gray-50 dark:bg-zinc-800 text-gray-900 dark:text-white outline-none focus:ring-2 focus:ring-purple-500" />
                </div>
                <button type="submit" :disabled="form.processing || !form.username || !form.password"
                    class="w-full py-2.5 text-sm font-medium bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 transition-colors flex items-center justify-center gap-2">
                    <LogIn class="w-4 h-4" />
                    Sign in
                </button>
            </form>
        </div>
    </div>
</template>
