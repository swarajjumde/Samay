import { computed } from 'vue';
import { api } from './api';
import { clearSession, session, setSession } from './session';

export const isLoggedIn = computed(() => !!session.user);
export const isAdmin = computed(() => session.user?.role === 'admin');

/** Role ke hisaab se login ke baad kahan bhejna hai. */
export function homeRoute() {
    return session.user?.role === 'admin' ? { name: 'admin-dashboard' } : { name: 'book' };
}

export async function login(credentials) {
    const data = await api.post('/login', credentials);
    setSession(data.token, data.user);
}

export async function register(details) {
    const data = await api.post('/register', details);
    setSession(data.token, data.user);
}

/** Page refresh ke baad: token hai par user ki jaankari nahi, to server se le aao. */
export async function restoreUser() {
    if (!session.token || session.user) return;
    try {
        const data = await api.get('/me');
        session.user = data.user;
    } catch {
        clearSession();
    }
}

export async function logout() {
    try {
        await api.post('/logout');
    } catch {
        /* server tak na pahunche to bhi yahan se logout kar do */
    }
    clearSession();
}
