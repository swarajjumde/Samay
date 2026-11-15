<script setup>
import BrandLogo from '@/components/BrandLogo.vue';
import FloatingConfigurator from '@/components/FloatingConfigurator.vue';
import { brand } from '@/config/brand';

defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' }
});
</script>

<template>
    <FloatingConfigurator />
    <div class="bg-surface-50 dark:bg-surface-950 flex items-center justify-center min-h-screen px-4 py-24">
        <div class="w-full" style="max-width: 30rem">
            <div style="border-radius: 2.5rem; padding: 0.3rem; background: linear-gradient(180deg, var(--primary-color) 10%, rgba(33, 150, 243, 0) 30%)">
                <div class="bg-surface-0 dark:bg-surface-900 py-12 px-6 sm:px-12" style="border-radius: 2.3rem">
                    <div class="text-center mb-8">
                        <router-link to="/" class="inline-flex items-center gap-2 text-primary mb-6" :aria-label="brand.name + ' home'">
                            <BrandLogo :size="40" />
                            <span class="text-2xl font-bold text-surface-900 dark:text-surface-0">{{ brand.name }}</span>
                        </router-link>
                        <div class="text-surface-900 dark:text-surface-0 text-3xl font-medium mb-2">{{ title }}</div>
                        <span class="text-muted-color font-medium">{{ subtitle }}</span>
                    </div>
                    <slot />
                </div>
            </div>
            <div class="text-center mt-6">
                <router-link to="/" class="text-muted-color hover:text-primary"><i class="pi pi-arrow-left mr-2 text-sm"></i>Back to website</router-link>
            </div>
        </div>
    </div>
</template>
