import { ref, onUnmounted } from 'vue';

export function useNow(intervalMs = 30000) {
    const now = ref(Date.now());
    const timer = setInterval(() => { now.value = Date.now(); }, intervalMs);
    onUnmounted(() => clearInterval(timer));
    return now;
}
