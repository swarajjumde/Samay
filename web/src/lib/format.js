import { brand } from '@/config/brand';

const pad = (n) => String(n).padStart(2, '0');

/** 1499 -> ₹1,499 */
export const money = (value) => new Intl.NumberFormat(brand.locale, { style: 'currency', currency: brand.currency, maximumFractionDigits: 0 }).format(value ?? 0);

/** Date object -> '2026-11-20' (local date, UTC mein badle bina) */
export const toISODate = (date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;

/** '2026-11-20' -> Date object (local) */
export const parseISODate = (value) => {
    const [year, month, day] = String(value).slice(0, 10).split('-').map(Number);
    return new Date(year, month - 1, day);
};

/** '2026-11-20' -> 'Fri, 20 Nov 2026' */
export const prettyDate = (value) => (value ? parseISODate(value).toLocaleDateString(brand.locale, { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' }) : '');

/** '14:30' -> '2:30 PM' */
export const prettyTime = (value) => {
    if (!value) return '';
    const [hours, minutes] = value.split(':').map(Number);
    return `${hours % 12 || 12}:${pad(minutes)} ${hours >= 12 ? 'PM' : 'AM'}`;
};

/** 90 -> '1 hr 30 min' */
export const prettyDuration = (minutes) => {
    if (minutes < 60) return `${minutes} min`;
    const hours = Math.floor(minutes / 60);
    return minutes % 60 ? `${hours} hr ${minutes % 60} min` : `${hours} hr`;
};

/** Booking status -> label + PrimeVue Tag ka rang */
export const STATUS = {
    pending: { label: 'Pending', severity: 'warn', icon: 'pi pi-clock' },
    confirmed: { label: 'Confirmed', severity: 'info', icon: 'pi pi-check' },
    completed: { label: 'Completed', severity: 'success', icon: 'pi pi-check-circle' },
    rejected: { label: 'Rejected', severity: 'danger', icon: 'pi pi-times' },
    cancelled: { label: 'Cancelled', severity: 'secondary', icon: 'pi pi-ban' }
};

export const statusOf = (status) => STATUS[status] ?? { label: status, severity: 'secondary', icon: 'pi pi-circle' };
