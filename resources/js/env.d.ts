/// <reference types="vite/client" />

import type { AxiosInstance } from 'axios';

declare global {
    interface Window {
        axios: AxiosInstance;
    }
}

declare module '*.vue' {
    import type { DefineComponent } from 'vue';
    const component: DefineComponent<{}, {}, any>;
    export default component;
}
