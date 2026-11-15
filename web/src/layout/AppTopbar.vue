<script setup>
import BrandLogo from '@/components/BrandLogo.vue';
import { brand } from '@/config/brand';
import { useLayout } from '@/layout/composables/layout';
import { isAdmin, logout } from '@/lib/auth';
import { session } from '@/lib/session';
import { useRouter } from 'vue-router';
import AppConfigurator from './AppConfigurator.vue';

const { toggleMenu, toggleDarkMode, isDarkTheme } = useLayout();
const router = useRouter();

async function signOut() {
    await logout();
    router.push({ name: 'landing' });
}
</script>

<template>
    <div class="layout-topbar">
        <div class="layout-topbar-logo-container">
            <button class="layout-menu-button layout-topbar-action" aria-label="Toggle menu" @click="toggleMenu">
                <i class="pi pi-bars"></i>
            </button>
            <router-link to="/app" class="layout-topbar-logo">
                <BrandLogo :size="30" class="text-primary" style="width: 30px" />
                <span>{{ brand.name }}</span>
            </router-link>
        </div>

        <div class="layout-topbar-actions">
            <div class="layout-config-menu">
                <button type="button" class="layout-topbar-action" aria-label="Toggle dark mode" @click="toggleDarkMode">
                    <i :class="['pi', { 'pi-moon': isDarkTheme, 'pi-sun': !isDarkTheme }]"></i>
                </button>
                <div class="relative">
                    <button
                        v-styleclass="{ selector: '@next', enterFromClass: 'hidden', enterActiveClass: 'p-anchored-overlay-enter-active', leaveToClass: 'hidden', leaveActiveClass: 'p-anchored-overlay-leave-active', hideOnOutsideClick: true }"
                        type="button"
                        class="layout-topbar-action layout-topbar-action-highlight"
                        aria-label="Theme settings"
                    >
                        <i class="pi pi-palette"></i>
                    </button>
                    <AppConfigurator />
                </div>
            </div>

            <button
                class="layout-topbar-menu-button layout-topbar-action"
                aria-label="Account menu"
                v-styleclass="{ selector: '@next', enterFromClass: 'hidden', enterActiveClass: 'p-anchored-overlay-enter-active', leaveToClass: 'hidden', leaveActiveClass: 'p-anchored-overlay-leave-active', hideOnOutsideClick: true }"
            >
                <i class="pi pi-ellipsis-v"></i>
            </button>

            <div class="layout-topbar-menu hidden lg:block">
                <div class="layout-topbar-menu-content">
                    <div class="topbar-user">
                        <i class="pi pi-user"></i>
                        <span>
                            {{ session.user?.name }}
                            <small>{{ isAdmin ? 'Service owner' : 'Customer' }}</small>
                        </span>
                    </div>
                    <router-link to="/" class="layout-topbar-action">
                        <i class="pi pi-globe"></i>
                        <span>Website</span>
                    </router-link>
                    <button type="button" class="layout-topbar-action" @click="signOut">
                        <i class="pi pi-sign-out"></i>
                        <span>Log out</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.topbar-user {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0 0.75rem;
    color: var(--text-color);
    line-height: 1.15;
    white-space: nowrap;
}

.topbar-user > span {
    display: flex;
    flex-direction: column;
    font-weight: 700;
}

.topbar-user small {
    font-weight: 400;
    color: var(--text-color-secondary);
}

@media (max-width: 991px) {
    .topbar-user {
        padding: 0.5rem 1rem 0.75rem;
        border-bottom: 1px solid var(--surface-border);
        margin-bottom: 0.25rem;
    }
}
</style>
