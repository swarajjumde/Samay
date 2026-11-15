<script setup>
import { api } from '@/lib/api';
import { money, prettyDuration } from '@/lib/format';
import { useIsMobile } from '@/lib/useIsMobile';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { onMounted, reactive, ref } from 'vue';

const toast = useToast();
const confirm = useConfirm();

const isMobile = useIsMobile();
const services = ref([]);
const loading = ref(true);
const loadError = ref('');

const ICONS = ['pi pi-star', 'pi pi-comments', 'pi pi-crown', 'pi pi-home', 'pi pi-wrench', 'pi pi-car', 'pi pi-heart', 'pi pi-bolt', 'pi pi-camera', 'pi pi-book', 'pi pi-desktop', 'pi pi-shopping-bag'];
const blank = () => ({ id: null, name: '', description: '', duration_minutes: 60, price: 0, icon: 'pi pi-star', is_active: true });

const dialog = reactive({ open: false, saving: false, errors: {}, form: blank() });
const fieldError = (field) => dialog.errors?.[field]?.[0];

async function load() {
    loading.value = true;
    loadError.value = '';
    try {
        services.value = await api.get('/admin/services');
    } catch (e) {
        loadError.value = e.message;
    } finally {
        loading.value = false;
    }
}

function openNew() {
    dialog.form = blank();
    dialog.errors = {};
    dialog.open = true;
}

function openEdit(service) {
    dialog.form = { ...blank(), ...service, description: service.description ?? '', icon: service.icon || 'pi pi-star' };
    dialog.errors = {};
    dialog.open = true;
}

async function save() {
    dialog.saving = true;
    dialog.errors = {};
    const { id, name, description, duration_minutes, price, icon, is_active } = dialog.form;
    const payload = { name: name.trim(), description: description.trim() || null, duration_minutes, price, icon, is_active };
    try {
        if (id) await api.put(`/admin/services/${id}`, payload);
        else await api.post('/admin/services', payload);
        dialog.open = false;
        toast.add({ severity: 'success', summary: id ? 'Service updated' : 'Service added', life: 3000 });
        await load();
    } catch (e) {
        dialog.errors = e.errors || {};
        if (!Object.keys(dialog.errors).length) toast.add({ severity: 'error', summary: 'Could not save', detail: e.message, life: 5000 });
    } finally {
        dialog.saving = false;
    }
}

// Table ke switch se seedha on/off.
async function toggleActive(service) {
    try {
        await api.put(`/admin/services/${service.id}`, { ...service, is_active: !service.is_active });
        service.is_active = !service.is_active;
        toast.add({ severity: 'success', summary: service.is_active ? 'Service is now bookable' : 'Service hidden from customers', life: 3000 });
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Could not update', detail: e.message, life: 5000 });
    }
}

function askDelete(service) {
    confirm.require({
        header: 'Delete this service?',
        message: `"${service.name}" will be removed for good.`,
        icon: 'pi pi-exclamation-triangle',
        rejectProps: { label: 'Keep it', severity: 'secondary', text: true },
        acceptProps: { label: 'Delete', severity: 'danger' },
        accept: async () => {
            try {
                await api.delete(`/admin/services/${service.id}`);
                services.value = services.value.filter((s) => s.id !== service.id);
                toast.add({ severity: 'success', summary: 'Service deleted', life: 3000 });
            } catch (e) {
                toast.add({ severity: 'error', summary: 'Could not delete', detail: e.message, life: 6000 });
            }
        }
    });
}

onMounted(load);
</script>

<template>
    <div>
        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-3xl font-bold m-0">Services</h1>
                <p class="text-muted-color mt-1 mb-0">What customers can book, how long it takes and what it costs.</p>
            </div>
            <Button label="Add service" icon="pi pi-plus" @click="openNew" />
        </div>

        <div class="card">
            <Message v-if="loadError" severity="error" :closable="false" class="mb-4">{{ loadError }}</Message>

            <!-- Phone: cards -->
            <div v-if="isMobile">
                <Skeleton v-if="loading" height="8rem" />
                <div v-else-if="!services.length" class="text-center text-muted-color py-8">No services yet. Add your first one.</div>
                <div v-for="service in services" v-else :key="service.id" class="m-card">
                    <div class="flex items-start gap-3">
                        <i :class="service.icon || 'pi pi-star'" class="text-primary mt-1" style="font-size: 1.25rem"></i>
                        <div class="min-w-0 grow">
                            <div class="font-bold">{{ service.name }}</div>
                            <div class="text-muted-color">{{ prettyDuration(service.duration_minutes) }} · {{ money(service.price) }} · {{ service.bookings_count }} {{ service.bookings_count === 1 ? "booking" : "bookings" }}</div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-3 mt-4">
                        <label class="flex items-center gap-2">
                            <ToggleSwitch :modelValue="service.is_active" :aria-label="`${service.name} bookable`" @update:modelValue="toggleActive(service)" />
                            <span>{{ service.is_active ? 'Bookable' : 'Hidden' }}</span>
                        </label>
                        <div class="flex gap-2">
                            <Button icon="pi pi-pencil" severity="secondary" outlined rounded aria-label="Edit" @click="openEdit(service)" />
                            <Button icon="pi pi-trash" severity="danger" outlined rounded aria-label="Delete" @click="askDelete(service)" />
                        </div>
                    </div>
                </div>
            </div>

            <DataTable v-else :value="services" :loading="loading" dataKey="id" scrollable stripedRows>
                <template #empty>
                    <div class="text-center text-muted-color py-8">No services yet. Add your first one.</div>
                </template>

                <Column header="Service" style="min-width: 16rem">
                    <template #body="{ data }">
                        <div class="flex items-center gap-3">
                            <i :class="data.icon || 'pi pi-star'" class="text-primary" style="font-size: 1.25rem"></i>
                            <div class="min-w-0">
                                <div class="font-medium">{{ data.name }}</div>
                                <div class="text-muted-color desc" :title="data.description">{{ data.description }}</div>
                            </div>
                        </div>
                    </template>
                </Column>
                <Column header="Duration" style="min-width: 8rem">
                    <template #body="{ data }">{{ prettyDuration(data.duration_minutes) }}</template>
                </Column>
                <Column header="Price" style="min-width: 7rem">
                    <template #body="{ data }">{{ money(data.price) }}</template>
                </Column>
                <Column field="bookings_count" header="Bookings" style="min-width: 7rem" />
                <Column header="Bookable" style="min-width: 7rem">
                    <template #body="{ data }">
                        <ToggleSwitch :modelValue="data.is_active" :aria-label="`${data.name} bookable`" @update:modelValue="toggleActive(data)" />
                    </template>
                </Column>
                <Column header="" style="min-width: 8rem">
                    <template #body="{ data }">
                        <div class="flex gap-2">
                            <Button icon="pi pi-pencil" severity="secondary" outlined rounded aria-label="Edit" @click="openEdit(data)" />
                            <Button icon="pi pi-trash" severity="danger" outlined rounded aria-label="Delete" @click="askDelete(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <Dialog v-model:visible="dialog.open" modal :draggable="false" :header="dialog.form.id ? 'Edit service' : 'Add service'" :style="{ width: '34rem' }" :breakpoints="{ '640px': '95vw' }">
            <form id="service-form" class="flex flex-col gap-5" @submit.prevent="save" novalidate>
                <div>
                    <label for="service-name" class="block font-medium mb-2">Name</label>
                    <InputText id="service-name" v-model="dialog.form.name" class="w-full" maxlength="100" :invalid="!!fieldError('name')" autofocus />
                    <small v-if="fieldError('name')" class="text-red-500">{{ fieldError('name') }}</small>
                </div>
                <div>
                    <label for="service-desc" class="block font-medium mb-2">Description</label>
                    <Textarea id="service-desc" v-model="dialog.form.description" rows="3" maxlength="1000" class="w-full" />
                    <small v-if="fieldError('description')" class="text-red-500">{{ fieldError('description') }}</small>
                </div>
                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-6">
                        <label for="service-duration" class="block font-medium mb-2">Duration (minutes)</label>
                        <InputNumber inputId="service-duration" v-model="dialog.form.duration_minutes" :min="5" :max="480" :step="5" showButtons fluid :invalid="!!fieldError('duration_minutes')" />
                        <small v-if="fieldError('duration_minutes')" class="text-red-500">{{ fieldError('duration_minutes') }}</small>
                    </div>
                    <div class="col-span-6">
                        <label for="service-price" class="block font-medium mb-2">Price</label>
                        <InputNumber inputId="service-price" v-model="dialog.form.price" :min="0" mode="currency" currency="INR" locale="en-IN" :maxFractionDigits="0" fluid :invalid="!!fieldError('price')" />
                        <small v-if="fieldError('price')" class="text-red-500">{{ fieldError('price') }}</small>
                    </div>
                </div>
                <div>
                    <div class="font-medium mb-2">Icon</div>
                    <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Icon">
                        <button v-for="icon in ICONS" :key="icon" type="button" class="icon-choice" :class="{ active: dialog.form.icon === icon }" role="radio" :aria-checked="dialog.form.icon === icon" :aria-label="icon.replace('pi pi-', '')" @click="dialog.form.icon = icon">
                            <i :class="icon"></i>
                        </button>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <ToggleSwitch inputId="service-active" v-model="dialog.form.is_active" />
                    <label for="service-active">Customers can book this service</label>
                </div>
            </form>
            <template #footer>
                <Button label="Cancel" severity="secondary" text @click="dialog.open = false" />
                <Button type="submit" form="service-form" label="Save" icon="pi pi-check" :loading="dialog.saving" :disabled="!dialog.form.name.trim()" />
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

.desc {
    max-width: 22rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.icon-choice {
    width: 2.75rem;
    height: 2.75rem;
    display: grid;
    place-items: center;
    border: 1px solid var(--surface-border);
    border-radius: var(--content-border-radius);
    background: transparent;
    color: var(--text-color);
    cursor: pointer;
}

.icon-choice:focus-visible {
    outline: 2px solid var(--primary-color);
    outline-offset: 2px;
}

.icon-choice.active {
    border-color: var(--primary-color);
    background: color-mix(in srgb, var(--primary-color), transparent 85%);
    color: var(--primary-color);
}
</style>
