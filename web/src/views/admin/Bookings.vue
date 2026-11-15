<script setup>
import { api } from '@/lib/api';
import { STATUS, money, prettyDate, prettyTime, statusOf, toISODate } from '@/lib/format';
import { useIsMobile } from '@/lib/useIsMobile';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useRoute } from 'vue-router';

const toast = useToast();
const route = useRoute();

const isMobile = useIsMobile();
const bookings = ref([]);
const mobileLimit = ref(15); // phone pe ek baar mein itne cards
const mobileList = computed(() => bookings.value.slice(0, mobileLimit.value));
const loading = ref(true);
const loadError = ref('');

const statusOptions = Object.entries(STATUS).map(([value, meta]) => ({ value, label: meta.label }));
const filters = reactive({
    status: STATUS[route.query.status] ? route.query.status : null,
    date: null
});

// Har status se admin kya-kya kar sakta hai (API mein Booking::ADMIN_TRANSITIONS se milta-julta).
const ACTIONS = {
    pending: [
        { status: 'confirmed', label: 'Confirm', icon: 'pi pi-check', severity: 'success' },
        { status: 'rejected', label: 'Reject', icon: 'pi pi-times', severity: 'danger' }
    ],
    confirmed: [
        { status: 'completed', label: 'Mark done', icon: 'pi pi-check-circle', severity: 'success' },
        { status: 'cancelled', label: 'Cancel', icon: 'pi pi-ban', severity: 'danger' }
    ]
};

// Action dialog
const dialog = reactive({ open: false, booking: null, action: null, note: '', saving: false });

async function load() {
    loading.value = true;
    loadError.value = '';
    mobileLimit.value = 15;
    const params = new URLSearchParams();
    if (filters.status) params.set('status', filters.status);
    if (filters.date) params.set('date', toISODate(filters.date));
    const query = params.toString();
    try {
        bookings.value = await api.get('/admin/bookings' + (query ? `?${query}` : ''));
    } catch (e) {
        loadError.value = e.message;
    } finally {
        loading.value = false;
    }
}

function openAction(booking, action) {
    dialog.booking = booking;
    dialog.action = action;
    dialog.note = '';
    dialog.open = true;
}

async function applyAction() {
    dialog.saving = true;
    try {
        const updated = await api.patch(`/admin/bookings/${dialog.booking.id}/status`, {
            status: dialog.action.status,
            admin_note: dialog.note.trim() || null
        });
        bookings.value = bookings.value.map((b) => (b.id === updated.id ? updated : b));
        dialog.open = false;
        toast.add({ severity: 'success', summary: `Booking ${statusOf(updated.status).label.toLowerCase()}`, life: 3000 });
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Could not update', detail: e.message, life: 5000 });
    } finally {
        dialog.saving = false;
    }
}

function clearFilters() {
    filters.status = null;
    filters.date = null;
}

watch(() => [filters.status, filters.date], load);
onMounted(load);
</script>

<template>
    <div>
        <div class="mb-6">
            <h1 class="text-3xl font-bold m-0">Bookings</h1>
            <p class="text-muted-color mt-1 mb-0">Confirm new requests and keep track of every appointment.</p>
        </div>

        <div class="card">
            <div class="flex flex-wrap items-end gap-4 mb-6">
                <div>
                    <label for="filter-status" class="block font-medium mb-2">Status</label>
                    <Select id="filter-status" v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="All" showClear style="min-width: 11rem" />
                </div>
                <div>
                    <label for="filter-date" class="block font-medium mb-2">Day</label>
                    <DatePicker inputId="filter-date" v-model="filters.date" placeholder="Any day" showIcon showButtonBar dateFormat="dd M yy" />
                </div>
                <Button v-if="filters.status || filters.date" label="Clear filters" icon="pi pi-filter-slash" severity="secondary" text @click="clearFilters" />
                <Button icon="pi pi-refresh" severity="secondary" outlined aria-label="Refresh" class="ml-auto" :loading="loading" @click="load" />
            </div>

            <Message v-if="loadError" severity="error" :closable="false" class="mb-4">{{ loadError }}</Message>

            <!-- Phone: table ki jagah cards, taaki Confirm/Reject button seedha dikhe -->
            <div v-if="isMobile">
                <Skeleton v-if="loading" height="8rem" />
                <div v-else-if="!bookings.length" class="text-center text-muted-color py-8">No bookings match these filters.</div>
                <template v-else>
                    <div v-for="booking in mobileList" :key="booking.id" class="m-card">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="font-bold">{{ booking.service?.name }}</div>
                                <div>{{ prettyDate(booking.booking_date) }}</div>
                                <div class="text-muted-color">{{ prettyTime(booking.start_time) }} – {{ prettyTime(booking.end_time) }} · {{ money(booking.price) }}</div>
                            </div>
                            <Tag :value="statusOf(booking.status).label" :severity="statusOf(booking.status).severity" />
                        </div>
                        <div class="mt-3">
                            <i class="pi pi-user mr-2 text-muted-color text-sm"></i>{{ booking.user?.name }}
                            <a v-if="booking.user?.phone" :href="`tel:${booking.user.phone}`" class="text-primary ml-2">{{ booking.user.phone }}</a>
                        </div>
                        <div v-if="booking.notes" class="text-muted-color mt-1">“{{ booking.notes }}”</div>
                        <div v-if="ACTIONS[booking.status]" class="flex gap-2 mt-4">
                            <Button v-for="action in ACTIONS[booking.status]" :key="action.status" :label="action.label" :icon="action.icon" :severity="action.severity" size="small" outlined class="grow" @click="openAction(booking, action)" />
                        </div>
                    </div>
                    <Button v-if="bookings.length > mobileLimit" label="Show more" severity="secondary" outlined class="w-full mt-2" @click="mobileLimit += 15" />
                </template>
            </div>

            <DataTable v-else :value="bookings" :loading="loading" dataKey="id" paginator :rows="10" :rowsPerPageOptions="[10, 25, 50]" scrollable stripedRows>
                <template #empty>
                    <div class="text-center text-muted-color py-8">No bookings match these filters.</div>
                </template>

                <Column header="When" style="min-width: 12rem">
                    <template #body="{ data }">
                        <div class="font-medium">{{ prettyDate(data.booking_date) }}</div>
                        <div class="text-muted-color">{{ prettyTime(data.start_time) }} – {{ prettyTime(data.end_time) }}</div>
                    </template>
                </Column>
                <Column header="Customer" style="min-width: 12rem">
                    <template #body="{ data }">
                        <div class="font-medium">{{ data.user?.name }}</div>
                        <div class="text-muted-color">{{ data.user?.phone || data.user?.email }}</div>
                    </template>
                </Column>
                <Column header="Service" style="min-width: 12rem">
                    <template #body="{ data }">
                        <div>{{ data.service?.name }}</div>
                        <div v-if="data.notes" class="text-muted-color note" :title="data.notes">“{{ data.notes }}”</div>
                    </template>
                </Column>
                <Column header="Price" style="min-width: 6rem">
                    <template #body="{ data }">{{ money(data.price) }}</template>
                </Column>
                <Column header="Status" style="min-width: 8rem">
                    <template #body="{ data }">
                        <Tag :value="statusOf(data.status).label" :severity="statusOf(data.status).severity" />
                    </template>
                </Column>
                <Column header="Actions" style="min-width: 14rem">
                    <template #body="{ data }">
                        <div class="flex flex-wrap gap-2">
                            <Button v-for="action in ACTIONS[data.status] ?? []" :key="action.status" :label="action.label" :icon="action.icon" :severity="action.severity" size="small" outlined @click="openAction(data, action)" />
                            <span v-if="!ACTIONS[data.status]" class="text-muted-color">—</span>
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="dialog.open" modal :draggable="false" :header="dialog.action ? `${dialog.action.label} booking` : ''" :style="{ width: '30rem' }" :breakpoints="{ '575px': '95vw' }">
            <template v-if="dialog.booking && dialog.action">
                <p class="mt-0">
                    <span class="font-medium">{{ dialog.booking.service?.name }}</span> for <span class="font-medium">{{ dialog.booking.user?.name }}</span
                    ><br />
                    {{ prettyDate(dialog.booking.booking_date) }}, {{ prettyTime(dialog.booking.start_time) }} – {{ prettyTime(dialog.booking.end_time) }}
                </p>
                <label for="admin-note" class="block font-medium mb-2">Message for the customer <span class="text-muted-color font-normal">(optional)</span></label>
                <Textarea id="admin-note" v-model="dialog.note" rows="3" maxlength="500" class="w-full" placeholder="e.g. Please arrive 5 minutes early" />
            </template>
            <template #footer>
                <Button label="Back" severity="secondary" text @click="dialog.open = false" />
                <Button v-if="dialog.action" :label="dialog.action.label" :icon="dialog.action.icon" :severity="dialog.action.severity" :loading="dialog.saving" @click="applyAction" />
            </template>
        </Dialog>
    </div>
</template>

<style scoped>
.m-card {
    border: 1px solid var(--surface-border);
    border-radius: var(--content-border-radius);
    padding: 1rem;
    margin-bottom: 0.75rem;
}

.note {
    max-width: 16rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>
