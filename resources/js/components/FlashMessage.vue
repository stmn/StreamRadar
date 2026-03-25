<script setup lang="ts">
import { ref, watch } from 'vue';
import { CheckCircle, AlertCircle, X } from 'lucide-vue-next';
import type { Flash } from '@/types';

const props = defineProps<{ flash: Flash }>();
const visible = ref(false);
const message = ref('');
const type = ref<'success' | 'error'>('success');

watch(() => props.flash, (newFlash) => {
    if (newFlash.success) {
        message.value = newFlash.success;
        type.value = 'success';
        show();
    } else if (newFlash.error) {
        message.value = newFlash.error;
        type.value = 'error';
        show();
    }
}, { immediate: true, deep: true });

let timeout: ReturnType<typeof setTimeout>;
function show() {
    visible.value = true;
    clearTimeout(timeout);
    timeout = setTimeout(() => { visible.value = false; }, 5000);
}
</script>

<template>
    <Transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="translate-y-2 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-2 opacity-0"
    >
        <div v-if="visible" class="fixed bottom-6 right-6 z-50 max-w-sm">
            <div
                :class="[
                    'rounded-xl px-4 py-3 shadow-lg text-sm font-medium flex items-center gap-2',
                    type === 'success'
                        ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300'
                        : 'bg-red-50 text-red-800 dark:bg-red-500/10 dark:text-red-300'
                ]"
            >
                <CheckCircle v-if="type === 'success'" class="w-5 h-5 shrink-0" />
                <AlertCircle v-else class="w-5 h-5 shrink-0" />
                <span>{{ message }}</span>
                <button @click="visible = false" class="ml-auto p-0.5 hover:opacity-70">
                    <X class="w-4 h-4" />
                </button>
            </div>
        </div>
    </Transition>
</template>
