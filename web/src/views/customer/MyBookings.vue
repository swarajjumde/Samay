<script setup>
import { api } from '@/lib/api';
import { money, prettyDate, prettyTime, statusOf, toISODate } from '@/lib/format';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, ref } from 'vue';

const toast = useToast();
const confirm = useConfirm();

const bookings = ref([]);
const loading = ref(true);
const loadError = ref('');
const busyId = ref(null);

const todayISO = toISODate(new Date());
const isActive = (booking) => ['pending', 'confirmed'].includes(booking.status);

// Upcoming = aaj ya aage ki, aur abhi chalu (pending/confirmed). Baaki sab history.
const upcoming = computed(() => bookings.value.filter((b) => isActive(b) && b.booking_date >= todayISO).sort((a, b) => (a.booking_date + a.start_time).localeCompare(b.booking_date + b.start_time)));
const history = computed(() => bookings.value.filter((b) => !(isActive(b) && b.booking_date >= todayISO)));

async function load() {
    loading.value = true;
    loadError.value = '';
    try {
        bookings.value = await api.get('/bookings');
    } catch (e) {
        loadError.value = e.message;
    } finally {
        loading.value = false;
    }
}

function askCancel(booking) {
    confirm.require({
        header: 'Cancel this booking?',
        message: `${booking.service?.name} on ${prettyDate(booking.booking_date)} at ${prettyTime(booking.start_time)}`,
        icon: 'pi pi-exclamation-triangle',
        rejectProps: { label: 'Keep it', severity: 'secondary', text: true },
        acceptProps: { label: 'Cancel booking', severity: 'danger' },
        accept: () => cancel(booking)
    });
}

async function cancel(booking) {
    busyId.value = booking.id;
    try {
        const updated = await api.patch(`/bookings/${booking.id}/cancel`);
        bookings.value = bookings.value.map((b) => (b.id === updated.id ? updated : b));
        toast.add({ severity: 'success', summary: 'Booking cancelled', life: 3000 });
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Could not cancel', detail: e.message, life: 5000 });
    } finally {
        busyId.value = null;
    }
}

onMounted(load);
</script>

<template>
    <div>
        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-3xl font-bold m-0">My bookings</h1>
                <p class="text-muted-color mt-1 mb-0">Everything you have booked, newest first.</p>
            </div>
            <Button as="router-link" :to="{ name: 'book' }" label="New booking" icon="pi pi-plus" />
        </div>

        <Message v-if="loadError" severity="error" :closable="false" class="mb-6">
            {{ loadError }}
            <Button label="Try again" link class="ml-2 p-0" @click="load" />
        </Message>

        <div v-if="loading" class="card"><Skeleton height="6rem" class="mb-4" /><Skeleton height="6rem" /></div>

        <div v-else-if="!loadError && !bookings.length" class="card text-center py-12">
            <i class="pi pi-calendar text-muted-color" style="font-size: 2.5rem"></i>
            <p class="text-xl font-medium mt-4 mb-1">No bookings yet</p>
            <p class="text-muted-color mt-0 mb-6">Book your first service. It takes less than a minute.</p>
            <Button as="router-link" :to="{ name: 'book' }" label="Book a service" icon="pi pi-calendar-plus" />
        </div>

        <template v-else-if="!loadError">
            <section v-for="group in [{ title: 'Upcoming', items: upcoming, empty: 'Nothing coming up.' }, { title: 'History', items: history, empty: 'No past bookings yet.' }]" :key="group.title" class="mb-8">
                <h2 class="text-xl font-bold mt-0 mb-4">{{ group.title }} <span class="text-muted-color font-normal">({{ group.items.length }})</span></h2>
                <p v-if="!group.items.length" class="text-muted-color m-0">{{ group.empty }}</p>

                <div v-for="booking in group.items" :key="booking.id" class="card mb-4 booking">
                    <div class="flex items-start gap-4 grow min-w-0">
                        <div class="booking-icon"><i :class="booking.service?.icon || 'pi pi-star'"></i></div>
                        <div class="min-w-0">
                            <div class="text-lg font-bold">{{ booking.service?.name }}</div>
                            <div class="mt-1"><i class="pi pi-calendar mr-2 text-muted-color text-sm"></i>{{ prettyDate(booking.booking_date) }}</div>
                            <div class="mt-1"><i class="pi pi-clock mr-2 text-muted-color text-sm"></i>{{ prettyTime(booking.start_time) }} – {{ prettyTime(booking.end_time) }}</div>
                            <div v-if="booking.notes" class="text-muted-color mt-2">Your note: {{ booking.notes }}</div>
                            <div v-if="booking.admin_note" class="mt-2"><span class="font-medium">Owner says:</span> {{ booking.admin_note }}</div>
                        </div>
                    </div>
                    <div class="booking-side">
                        <Tag :value="statusOf(booking.status).label" :severity="statusOf(booking.status).severity" :icon="statusOf(booking.status).icon" />
                        <span class="text-xl font-bold">{{ money(booking.price) }}</span>
                        <Button v-if="isActive(booking)" label="Cancel" severity="danger" outlined size="small" :loading="busyId === booking.id" @click="askCancel(booking)" />
                    </div>
                </div>
            </section>
        </template>
    </div>
</template>

<style scoped>
.booking {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 1.25rem;
    padding: 1.25rem 1.5rem;
}

.booking-icon {
    flex: none;
    width: 2.75rem;
    height: 2.75rem;
    border-radius: var(--content-border-radius);
    display: grid;
    place-items: center;
    background: color-mix(in srgb, var(--primary-color), transparent 85%);
    color: var(--primary-color);
}

.booking-side {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}
</style>
