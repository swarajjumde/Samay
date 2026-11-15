<script setup>
import { brand } from '@/config/brand';
</script>

<template>
    <div class="layout-footer">
        {{ brand.name }} · Laravel API + Vue panel on
        <a href="https://sakai.primevue.org" target="_blank" rel="noopener noreferrer" class="text-primary font-bold hover:underline">Sakai</a>
    </div>
</template>
