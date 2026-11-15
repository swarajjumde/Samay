import { reactive } from 'vue';

// Login ke baad mila token browser mein yaad rakhte hain, taaki page refresh pe logout na ho.
const STORAGE_KEY = 'samay.token';

function readToken() {
    try {
        return window.localStorage.getItem(STORAGE_KEY);
    } catch {
        return null; // private mode ya storage band ho to bhi app chale
    }
}

export const session = reactive({
    token: readToken(),
    user: null
});

export function setSession(token, user) {
    session.token = token;
    session.user = user;
    try {
        window.localStorage.setItem(STORAGE_KEY, token);
    } catch {
        /* storage nahi hai to sirf memory mein rahega */
    }
}

export function clearSession() {
    session.token = null;
    session.user = null;
    try {
        window.localStorage.removeItem(STORAGE_KEY);
    } catch {
        /* ignore */
    }
}
