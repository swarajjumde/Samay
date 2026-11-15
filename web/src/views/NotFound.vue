<template>
    <div class="bg-surface-50 dark:bg-surface-950 flex items-center justify-center min-h-screen px-4">
        <div class="text-center">
            <span class="text-primary font-bold text-6xl">404</span>
            <h1 class="text-surface-900 dark:text-surface-0 font-bold text-4xl mb-2">Page not found</h1>
            <p class="text-muted-color mb-8">The page you are looking for does not exist or was moved.</p>
            <Button as="router-link" label="Back to home" icon="pi pi-home" to="/" />
        </div>
    </div>
</template>
