<script setup>
// Samay ka logo: ek ghadi jiska gola poora nahi hai (khaali slot ka ishaara).
defineProps({
    size: { type: [Number, String], default: 32 }
});
</script>

<template>
    <svg :width="size" :height="size" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <circle cx="20" cy="20" r="15" stroke="currentColor" stroke-width="4" stroke-dasharray="72 22" stroke-linecap="round" transform="rotate(-58 20 20)" />
        <path d="M20 12v8l5.5 3.5" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
    </svg>
</template>
