<script setup>
import AuthShell from '@/components/AuthShell.vue';
import { homeRoute, register } from '@/lib/auth';
import { reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const router = useRouter();
const route = useRoute();

const form = reactive({ name: '', email: '', phone: '', password: '', password_confirmation: '' });
const errors = ref({});
const error = ref('');
const loading = ref(false);

const fieldError = (field) => errors.value?.[field]?.[0];

async function submit() {
    error.value = '';
    errors.value = {};

    if (form.password !== form.password_confirmation) {
        errors.value = { password_confirmation: ['Both passwords must match.'] };
        return;
    }

    loading.value = true;
    try {
        await register({ ...form, name: form.name.trim(), email: form.email.trim(), phone: form.phone.trim() || null });
        const redirect = typeof route.query.redirect === 'string' && route.query.redirect.startsWith('/app') ? route.query.redirect : null;
        router.push(redirect || homeRoute());
    } catch (e) {
        errors.value = e.errors || {};
        if (!Object.keys(errors.value).length) error.value = e.message;
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <AuthShell title="Create your account" subtitle="It takes less than a minute">
        <form @submit.prevent="submit" novalidate>
            <Message v-if="error" severity="error" class="mb-6" :closable="false">{{ error }}</Message>

            <div class="mb-5">
                <label for="name" class="block text-surface-900 dark:text-surface-0 font-medium mb-2">Full name</label>
                <InputText id="name" v-model="form.name" autocomplete="name" placeholder="Your name" class="w-full" :invalid="!!fieldError('name')" required />
                <small v-if="fieldError('name')" class="text-red-500">{{ fieldError('name') }}</small>
            </div>

            <div class="mb-5">
                <label for="email" class="block text-surface-900 dark:text-surface-0 font-medium mb-2">Email</label>
                <InputText id="email" v-model="form.email" type="email" autocomplete="email" placeholder="you@example.com" class="w-full" :invalid="!!fieldError('email')" required />
                <small v-if="fieldError('email')" class="text-red-500">{{ fieldError('email') }}</small>
            </div>

            <div class="mb-5">
                <label for="phone" class="block text-surface-900 dark:text-surface-0 font-medium mb-2">Phone <span class="text-muted-color font-normal">(optional)</span></label>
                <InputText id="phone" v-model="form.phone" type="tel" autocomplete="tel" placeholder="So the owner can reach you" class="w-full" :invalid="!!fieldError('phone')" />
                <small v-if="fieldError('phone')" class="text-red-500">{{ fieldError('phone') }}</small>
            </div>

            <div class="mb-5">
                <label for="password" class="block text-surface-900 dark:text-surface-0 font-medium mb-2">Password</label>
                <Password id="password" v-model="form.password" placeholder="At least 8 characters" :toggleMask="true" :feedback="false" :inputProps="{ autocomplete: 'new-password' }" :invalid="!!fieldError('password')" fluid required />
                <small v-if="fieldError('password')" class="text-red-500">{{ fieldError('password') }}</small>
            </div>

            <div class="mb-8">
                <label for="password2" class="block text-surface-900 dark:text-surface-0 font-medium mb-2">Confirm password</label>
                <Password id="password2" v-model="form.password_confirmation" placeholder="Type it again" :toggleMask="true" :feedback="false" :inputProps="{ autocomplete: 'new-password' }" :invalid="!!fieldError('password_confirmation')" fluid required />
                <small v-if="fieldError('password_confirmation')" class="text-red-500">{{ fieldError('password_confirmation') }}</small>
            </div>

            <Button type="submit" label="Create account" icon="pi pi-user-plus" class="w-full" :loading="loading" :disabled="!form.name || !form.email || form.password.length < 8" />
        </form>

        <p class="text-center text-muted-color mt-8 mb-0">
            Already have an account?
            <router-link :to="{ name: 'login', query: route.query }" class="text-primary font-medium">Log in</router-link>
        </p>
    </AuthShell>
</template>
