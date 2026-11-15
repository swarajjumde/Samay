<script setup>
import { homeRoute } from '@/lib/auth';
</script>

<template>
    <div class="bg-surface-50 dark:bg-surface-950 flex items-center justify-center min-h-screen px-4">
        <div class="text-center">
            <div class="inline-flex justify-center items-center border-2 border-orange-500 rounded-full mb-6" style="width: 3.5rem; height: 3.5rem">
                <i class="text-orange-500 pi pi-lock" style="font-size: 1.5rem"></i>
            </div>
            <h1 class="text-surface-900 dark:text-surface-0 font-bold text-4xl mb-2">Access denied</h1>
            <p class="text-muted-color mb-8">This page is only for the service owner.</p>
            <Button as="router-link" label="Go to my page" icon="pi pi-arrow-left" :to="homeRoute()" />
        </div>
    </div>
</template>
