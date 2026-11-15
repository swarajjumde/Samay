<script setup>
import { api } from '@/lib/api';
import { money, prettyDate, prettyDuration, prettyTime, toISODate } from '@/lib/format';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';

const toast = useToast();
const router = useRouter();

const services = ref([]);
const loading = ref(true);
const loadError = ref('');
const business = ref({ closed_days: [], max_days_ahead: 30 });

// Booking dialog ki state
const dialogOpen = ref(false);
const selected = ref(null); // jo service chuni
const date = ref(null); // Date object
const slots = ref([]);
const slotsLoading = ref(false);
const slotsError = ref('');
const time = ref(null); // '10:00'
const notes = ref('');
const saving = ref(false);

const today = new Date();
today.setHours(0, 0, 0, 0);

const maxDate = computed(() => {
    const limit = new Date(today);
    limit.setDate(limit.getDate() + (business.value.max_days_ahead ?? 30));
    return limit;
});

const chosenSlot = computed(() => slots.value.find((slot) => slot.time === time.value) ?? null);
const hasFreeSlot = computed(() => slots.value.some((slot) => slot.available));

async function load() {
    loading.value = true;
    loadError.value = '';
    try {
        const [serviceList, info] = await Promise.all([api.get('/services'), api.get('/business').catch(() => null)]);
        services.value = serviceList;
        if (info) business.value = info;
    } catch (e) {
        loadError.value = e.message;
    } finally {
        loading.value = false;
    }
}

function openDialog(service) {
    selected.value = service;
    date.value = null;
    slots.value = [];
    time.value = null;
    notes.value = '';
    slotsError.value = '';
    dialogOpen.value = true;
}

// Date badalte hi us din ke slots server se mangwao.
watch(date, async (value) => {
    time.value = null;
    slots.value = [];
    slotsError.value = '';
    if (!value || !selected.value) return;

    slotsLoading.value = true;
    const requested = toISODate(value);
    try {
        const data = await api.get(`/services/${selected.value.id}/slots?date=${requested}`);
        // Agar user ne beech mein date badal di ho to purana jawab ignore karo.
        if (date.value && toISODate(date.value) === requested) slots.value = data.slots;
    } catch (e) {
        slotsError.value = e.message;
    } finally {
        slotsLoading.value = false;
    }
});

async function confirmBooking() {
    if (!chosenSlot.value || saving.value) return;
    saving.value = true;
    try {
        await api.post('/bookings', {
            service_id: selected.value.id,
            date: toISODate(date.value),
            time: time.value,
            notes: notes.value.trim() || null
        });
        dialogOpen.value = false;
        toast.add({ severity: 'success', summary: 'Booking sent', detail: 'The owner will confirm it soon.', life: 4000 });
        router.push({ name: 'my-bookings' });
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Could not book', detail: e.first?.('time') || e.message, life: 5000 });
        // Slot shayad abhi-abhi kisi aur ne le liya: list taaza karo.
        if (e.status === 422 && date.value) date.value = new Date(date.value);
    } finally {
        saving.value = false;
    }
}

onMounted(load);
</script>

<template>
    <div>
        <div class="mb-6">
            <h1 class="text-3xl font-bold m-0">Book a service</h1>
            <p class="text-muted-color mt-1 mb-0">Pick a service, then choose a day and a free time.</p>
        </div>

        <Message v-if="loadError" severity="error" :closable="false" class="mb-6">
            {{ loadError }}
            <Button label="Try again" link class="ml-2 p-0" @click="load" />
        </Message>

        <div v-if="loading" class="grid grid-cols-12 gap-6">
            <div v-for="n in 3" :key="n" class="col-span-12 md:col-span-6 xl:col-span-4">
                <div class="card mb-0"><Skeleton height="9rem" /></div>
            </div>
        </div>

        <div v-else-if="!loadError && !services.length" class="card text-center py-12">
            <i class="pi pi-briefcase text-muted-color" style="font-size: 2.5rem"></i>
            <p class="text-xl font-medium mt-4 mb-1">No services yet</p>
            <p class="text-muted-color m-0">The owner has not added any services. Please check back soon.</p>
        </div>

        <div v-else class="grid grid-cols-12 gap-6">
            <div v-for="service in services" :key="service.id" class="col-span-12 md:col-span-6 xl:col-span-4">
                <div class="card mb-0 h-full flex flex-col">
                    <div class="flex items-start gap-4 mb-4">
                        <div class="service-icon"><i :class="service.icon || 'pi pi-star'"></i></div>
                        <div class="min-w-0">
                            <div class="text-xl font-bold leading-tight">{{ service.name }}</div>
                            <div class="text-muted-color mt-1"><i class="pi pi-clock mr-1 text-sm"></i>{{ prettyDuration(service.duration_minutes) }}</div>
                        </div>
                    </div>
                    <p class="text-muted-color grow mt-0 mb-6">{{ service.description }}</p>
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-2xl font-bold">{{ money(service.price) }}</span>
                        <Button label="Book" icon="pi pi-calendar-plus" @click="openDialog(service)" />
                    </div>
                </div>
            </div>
        </div>

        <Dialog v-model:visible="dialogOpen" modal :draggable="false" :header="selected ? `Book: ${selected.name}` : 'Book'" :style="{ width: '52rem' }" :breakpoints="{ '960px': '95vw' }">
            <div v-if="selected" class="grid grid-cols-12 gap-6">
                <div class="col-span-12 md:col-span-6">
                    <div class="font-medium mb-3">1. Choose a day</div>
                    <DatePicker v-model="date" inline :minDate="today" :maxDate="maxDate" :disabledDays="business.closed_days" class="w-full" />
                </div>

                <div class="col-span-12 md:col-span-6">
                    <div class="font-medium mb-3">2. Choose a time</div>

                    <p v-if="!date" class="text-muted-color m-0">Select a day first to see free times.</p>
                    <div v-else-if="slotsLoading" class="grid grid-cols-3 gap-2">
                        <Skeleton v-for="n in 9" :key="n" height="2.5rem" />
                    </div>
                    <Message v-else-if="slotsError" severity="error" :closable="false">{{ slotsError }}</Message>
                    <Message v-else-if="!hasFreeSlot" severity="warn" :closable="false">No free time on {{ prettyDate(toISODate(date)) }}. Please try another day.</Message>
                    <div v-else class="grid grid-cols-3 gap-2" role="listbox" aria-label="Available times">
                        <button
                            v-for="slot in slots"
                            :key="slot.time"
                            type="button"
                            class="slot"
                            :class="{ 'slot-selected': slot.time === time }"
                            :disabled="!slot.available"
                            role="option"
                            :aria-selected="slot.time === time"
                            @click="time = slot.time"
                        >
                            {{ prettyTime(slot.time) }}
                        </button>
                    </div>

                    <div class="font-medium mt-6 mb-3">3. Anything we should know? <span class="text-muted-color font-normal">(optional)</span></div>
                    <Textarea v-model="notes" rows="3" maxlength="500" class="w-full" placeholder="Address, special request..." />
                </div>
            </div>

            <template #footer>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 w-full">
                    <div class="text-left">
                        <template v-if="chosenSlot">
                            <div class="font-bold">{{ prettyDate(toISODate(date)) }} · {{ prettyTime(chosenSlot.time) }} – {{ prettyTime(chosenSlot.end) }}</div>
                            <div class="text-muted-color">{{ selected.name }} · {{ money(selected.price) }}</div>
                        </template>
                        <span v-else class="text-muted-color">Choose a day and time to continue.</span>
                    </div>
                    <div class="flex gap-2 justify-end">
                        <Button label="Close" severity="secondary" text @click="dialogOpen = false" />
                        <Button label="Confirm booking" icon="pi pi-check" :disabled="!chosenSlot" :loading="saving" @click="confirmBooking" />
                    </div>
                </div>
            </template>
        </Dialog>
    </div>
</template>

<style scoped>
.service-icon {
    flex: none;
    width: 3rem;
    height: 3rem;
    border-radius: var(--content-border-radius);
    display: grid;
    place-items: center;
    background: color-mix(in srgb, var(--primary-color), transparent 85%);
    color: var(--primary-color);
}

.service-icon i {
    font-size: 1.35rem;
}

.slot {
    padding: 0.6rem 0.25rem;
    border: 1px solid var(--surface-border);
    border-radius: var(--content-border-radius);
    background: transparent;
    color: var(--text-color);
    font-weight: 700;
    cursor: pointer;
    transition:
        background-color 0.15s,
        border-color 0.15s;
}

.slot:hover:not(:disabled) {
    border-color: var(--primary-color);
}

.slot:focus-visible {
    outline: 2px solid var(--primary-color);
    outline-offset: 2px;
}

.slot:disabled {
    cursor: not-allowed;
    opacity: 0.4;
    text-decoration: line-through;
}

.slot-selected {
    background: var(--primary-color);
    border-color: var(--primary-color);
    color: var(--primary-contrast-color);
}
</style>
