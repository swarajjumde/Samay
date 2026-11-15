import { clearSession, session } from './session';

// Dev mein Vite '/api' ko Laravel (localhost:8000) pe bhej deta hai (vite.config.mjs).
// Server pe nginx '/api' ko Laravel tak pahunchata hai. Isliye yahan sirf '/api' kaafi hai.
const BASE = import.meta.env.VITE_API_BASE || '/api';

export class ApiError extends Error {
    constructor(status, message, errors = {}) {
        super(message);
        this.status = status;
        this.errors = errors; // Laravel validation errors: { email: ['...'] }
    }

    /** Kisi field ka pehla error, form ke neeche dikhane ke liye. */
    first(field) {
        return this.errors?.[field]?.[0] ?? null;
    }
}

let onUnauthorized = () => {};

/** Token expire/galat ho jaye to kya karna hai (main.js isme login pe bhejna set karta hai). */
export function setUnauthorizedHandler(handler) {
    onUnauthorized = handler;
}

const FALLBACK = {
    0: 'Cannot reach the server. Check your internet and try again.',
    401: 'Please log in again.',
    403: 'You are not allowed to do this.',
    404: 'Not found.',
    422: 'Please check the details and try again.',
    429: 'Too many attempts. Wait a minute and try again.'
};

async function request(method, path, body) {
    const headers = { Accept: 'application/json' };
    if (body !== undefined) headers['Content-Type'] = 'application/json';
    if (session.token) headers.Authorization = `Bearer ${session.token}`;

    let response;
    try {
        response = await fetch(BASE + path, {
            method,
            headers,
            body: body !== undefined ? JSON.stringify(body) : undefined
        });
    } catch {
        throw new ApiError(0, FALLBACK[0]);
    }

    let data = null;
    const text = await response.text();
    if (text) {
        try {
            data = JSON.parse(text);
        } catch {
            data = null;
        }
    }

    if (!response.ok) {
        if (response.status === 401 && session.token) {
            clearSession();
            onUnauthorized();
        }
        throw new ApiError(response.status, data?.message || FALLBACK[response.status] || 'Something went wrong. Please try again.', data?.errors || {});
    }

    return data;
}

export const api = {
    get: (path) => request('GET', path),
    post: (path, body = {}) => request('POST', path, body),
    put: (path, body = {}) => request('PUT', path, body),
    patch: (path, body = {}) => request('PATCH', path, body),
    delete: (path) => request('DELETE', path)
};
