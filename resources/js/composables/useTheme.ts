import { ref, onMounted, watch } from 'vue';

type Theme = 'light' | 'dark' | 'system';

const theme = ref<Theme>((localStorage.getItem('theme') as Theme) || 'system');

function applyTheme(value: Theme) {
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const isDark = value === 'dark' || (value === 'system' && prefersDark);

    document.documentElement.classList.toggle('dark', isDark);
}

export function useTheme() {
    function setTheme(value: Theme) {
        theme.value = value;
        localStorage.setItem('theme', value);
        applyTheme(value);
    }

    onMounted(() => {
        applyTheme(theme.value);

        const mq = window.matchMedia('(prefers-color-scheme: dark)');
        mq.addEventListener('change', () => {
            if (theme.value === 'system') {
                applyTheme('system');
            }
        });
    });

    return { theme, setTheme };
}
