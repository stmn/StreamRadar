import '../css/app.css';
import './bootstrap';
import 'floating-vue/dist/style.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import FloatingVue from 'floating-vue';

createInertiaApp({
    title: (title) => title ? `${title} — StreamRadar` : 'StreamRadar',
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(FloatingVue, { themes: { tooltip: { delay: { show: 300, hide: 0 } } } })
            .mount(el);
    },
    progress: {
        color: '#9147ff',
    },
});
