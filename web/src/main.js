import { createApp } from 'vue';
import App from './App.vue';
import router from './router';

import Aura from '@primeuix/themes/aura';
import PrimeVue from 'primevue/config';
import ConfirmationService from 'primevue/confirmationservice';
import ToastService from 'primevue/toastservice';

// Fonts project ke andar hi hain (kisi bahar ki website pe depend nahi).
import '@fontsource-variable/sora';
import '@fontsource/lato/400.css';
import '@fontsource/lato/700.css';

import '@/assets/tailwind.css';
import '@/assets/styles.scss';

import { setUnauthorizedHandler } from '@/lib/api';

const app = createApp(App);

app.use(router);
app.use(PrimeVue, {
    theme: {
        preset: Aura,
        options: {
            darkModeSelector: '.app-dark'
        }
    }
});
app.use(ToastService);
app.use(ConfirmationService);

// Token purana/galat nikle to login page pe bhej do.
setUnauthorizedHandler(() => {
    if (router.currentRoute.value.matched.some((record) => record.meta.requiresAuth)) {
        router.push({ name: 'login', query: { redirect: router.currentRoute.value.fullPath } });
    }
});

app.mount('#app');
