<script setup>
import { isAdmin } from '@/lib/auth';
import { computed } from 'vue';
import AppMenuItem from './AppMenuItem.vue';

// Role ke hisaab se alag menu: customer ko booking wale pages, admin ko manage wale pages.
const customerMenu = [
    {
        label: 'Bookings',
        items: [
            { label: 'Book a service', icon: 'pi pi-fw pi-calendar-plus', to: '/app/services' },
            { label: 'My bookings', icon: 'pi pi-fw pi-list-check', to: '/app/bookings' }
        ]
    }
];

const adminMenu = [
    {
        label: 'Overview',
        items: [{ label: 'Dashboard', icon: 'pi pi-fw pi-home', to: '/app/admin' }]
    },
    {
        label: 'Manage',
        items: [
            { label: 'Bookings', icon: 'pi pi-fw pi-calendar', to: '/app/admin/bookings' },
            { label: 'Services', icon: 'pi pi-fw pi-briefcase', to: '/app/admin/services' },
            { label: 'Customers', icon: 'pi pi-fw pi-users', to: '/app/admin/customers' }
        ]
    }
];

const model = computed(() => (isAdmin.value ? adminMenu : customerMenu));
</script>

<template>
    <ul class="layout-menu">
        <template v-for="(item, i) in model" :key="item.label">
            <app-menu-item v-if="!item.separator" :item="item" :index="i"></app-menu-item>
            <li v-if="item.separator" class="menu-separator"></li>
        </template>
    </ul>
</template>
