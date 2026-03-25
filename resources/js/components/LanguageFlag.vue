<script setup lang="ts">
defineProps<{ code: string; size?: number }>();

const langToCountry: Record<string, string> = {
    en: 'gb', pl: 'pl', de: 'de', fr: 'fr', es: 'es', pt: 'br', it: 'it',
    ru: 'ru', ja: 'jp', ko: 'kr', zh: 'cn', tr: 'tr', nl: 'nl', sv: 'se',
    no: 'no', da: 'dk', fi: 'fi', cs: 'cz', hu: 'hu', ro: 'ro', bg: 'bg',
    el: 'gr', uk: 'ua', th: 'th', vi: 'vn', id: 'id', ms: 'my', ar: 'sa',
    he: 'il', hi: 'in', sk: 'sk', hr: 'hr', lt: 'lt', lv: 'lv', et: 'ee',
    sl: 'si', sr: 'rs', ca: 'es', tl: 'ph', asl: 'us',
};

function flagUrl(lang: string): string | null {
    const country = langToCountry[lang.toLowerCase()];
    if (!country) return null;
    return `https://flagcdn.com/w40/${country}.png`;
}
</script>

<template>
    <img
        v-if="flagUrl(code)"
        :src="flagUrl(code)!"
        :alt="code"
        :style="{ width: `${size || 16}px`, height: `${Math.round((size || 16) * 0.75)}px` }"
        class="inline-block rounded-sm object-cover"
        loading="lazy"
    />
    <span v-else class="text-xs uppercase text-gray-400 dark:text-zinc-600">{{ code }}</span>
</template>
