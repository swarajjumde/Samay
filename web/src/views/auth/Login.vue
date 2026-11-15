<script setup>
import AuthShell from '@/components/AuthShell.vue';
import { homeRoute, login } from '@/lib/auth';
import { reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const router = useRouter();
const route = useRoute();

const form = reactive({ email: '', password: '' });
const error = ref('');
const loading = ref(false);

async function submit() {
    error.value = '';
    loading.value = true;
    try {
        await login({ email: form.email.trim(), password: form.password });
        // Agar koi page kholne ki koshish mein login pe aaya tha, to wapas wahin bhejo.
        const redirect = typeof route.query.redirect === 'string' && route.query.redirect.startsWith('/app') ? route.query.redirect : null;
        router.push(redirect || homeRoute());
    } catch (e) {
        error.value = e.first?.('email') || e.message;
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <AuthShell title="Welcome back" subtitle="Log in to book or manage services">
        <form @submit.prevent="submit" novalidate>
            <Message v-if="error" severity="error" class="mb-6" :closable="false">{{ error }}</Message>

            <label for="email" class="block text-surface-900 dark:text-surface-0 font-medium mb-2">Email</label>
            <InputText id="email" v-model="form.email" type="email" autocomplete="email" placeholder="you@example.com" class="w-full mb-6" required />

            <label for="password" class="block text-surface-900 dark:text-surface-0 font-medium mb-2">Password</label>
            <Password id="password" v-model="form.password" placeholder="Your password" :toggleMask="true" :feedback="false" :inputProps="{ autocomplete: 'current-password' }" fluid class="mb-8" required />

            <Button type="submit" label="Log in" icon="pi pi-sign-in" class="w-full" :loading="loading" :disabled="!form.email || !form.password" />
        </form>

        <p class="text-center text-muted-color mt-8 mb-0">
            New here?
            <router-link :to="{ name: 'register', query: route.query }" class="text-primary font-medium">Create an account</router-link>
        </p>
    </AuthShell>
</template>
