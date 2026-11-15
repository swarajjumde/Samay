import AppLayout from '@/layout/AppLayout.vue';
import { homeRoute, restoreUser } from '@/lib/auth';
import { session } from '@/lib/session';
import { createRouter, createWebHistory } from 'vue-router';

/*
 * meta.requiresAuth : login zaroori hai
 * meta.guest        : sirf logged-out log (login/register page)
 * meta.role         : 'customer' ya 'admin'
 *
 * Yaad rakho: ye guards sirf UI ke liye hain. Asli suraksha Laravel API mein hai
 * (auth:sanctum + admin middleware), kyunki browser ka code koi bhi badal sakta hai.
 */
const router = createRouter({
    history: createWebHistory(),
    scrollBehavior(to, from, savedPosition) {
        if (savedPosition) return savedPosition;
        if (to.hash) return { el: to.hash, behavior: 'smooth' };
        return { top: 0 };
    },
    routes: [
        {
            path: '/',
            name: 'landing',
            component: () => import('@/views/Landing.vue'),
            meta: { title: 'Book any service in seconds' }
        },
        {
            path: '/login',
            name: 'login',
            component: () => import('@/views/auth/Login.vue'),
            meta: { guest: true, title: 'Log in' }
        },
        {
            path: '/register',
            name: 'register',
            component: () => import('@/views/auth/Register.vue'),
            meta: { guest: true, title: 'Create account' }
        },
        {
            path: '/app',
            component: AppLayout,
            meta: { requiresAuth: true },
            children: [
                { path: '', name: 'app-home', redirect: () => homeRoute() },

                // Customer
                { path: 'services', name: 'book', component: () => import('@/views/customer/BookService.vue'), meta: { role: 'customer', title: 'Book a service' } },
                { path: 'bookings', name: 'my-bookings', component: () => import('@/views/customer/MyBookings.vue'), meta: { role: 'customer', title: 'My bookings' } },

                // Admin (service owner)
                { path: 'admin', name: 'admin-dashboard', component: () => import('@/views/admin/Dashboard.vue'), meta: { role: 'admin', title: 'Dashboard' } },
                { path: 'admin/bookings', name: 'admin-bookings', component: () => import('@/views/admin/Bookings.vue'), meta: { role: 'admin', title: 'Bookings' } },
                { path: 'admin/services', name: 'admin-services', component: () => import('@/views/admin/Services.vue'), meta: { role: 'admin', title: 'Services' } },
                { path: 'admin/customers', name: 'admin-customers', component: () => import('@/views/admin/Customers.vue'), meta: { role: 'admin', title: 'Customers' } }
            ]
        },
        {
            path: '/denied',
            name: 'denied',
            component: () => import('@/views/AccessDenied.vue'),
            meta: { title: 'Access denied' }
        },
        {
            path: '/:pathMatch(.*)*',
            name: 'notfound',
            component: () => import('@/views/NotFound.vue'),
            meta: { title: 'Page not found' }
        }
    ]
});

router.beforeEach(async (to) => {
    await restoreUser();

    const needsAuth = to.matched.some((record) => record.meta.requiresAuth);

    if (needsAuth && !session.user) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }

    if (to.meta.guest && session.user) {
        return homeRoute();
    }

    if (to.meta.role && session.user && to.meta.role !== session.user.role) {
        // Admin galti se customer page khole to apne dashboard pe bhej do; customer admin page khole to mana kar do.
        return session.user.role === 'admin' ? homeRoute() : { name: 'denied' };
    }

    return true;
});

router.afterEach((to) => {
    document.title = to.meta.title ? `${to.meta.title} · Samay` : 'Samay';
});

export default router;
