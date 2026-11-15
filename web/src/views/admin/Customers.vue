<script setup>
import { api } from '@/lib/api';
import { computed, onMounted, ref } from 'vue';

const customers = ref([]);
const loading = ref(true);
const loadError = ref('');
const search = ref('');

const filtered = computed(() => {
    const term = search.value.trim().toLowerCase();
    if (!term) return customers.value;
    return customers.value.filter((c) => [c.name, c.email, c.phone].some((value) => String(value ?? '').toLowerCase().includes(term)));
});

const joined = (value) => new Date(value).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });

async function load() {
    loading.value = true;
    loadError.value = '';
    try {
        customers.value = await api.get('/admin/customers');
    } catch (e) {
        loadError.value = e.message;
    } finally {
        loading.value = false;
    }
}

onMounted(load);
</script>

<template>
    <div>
        <div class="mb-6">
            <h1 class="text-3xl font-bold m-0">Customers</h1>
            <p class="text-muted-color mt-1 mb-0">Everyone who has created an account.</p>
        </div>

        <div class="card">
            <div class="mb-6" style="max-width: 22rem">
                <IconField>
                    <InputIcon class="pi pi-search" />
                    <InputText v-model="search" placeholder="Search name, email or phone" class="w-full" aria-label="Search customers" />
                </IconField>
            </div>

            <Message v-if="loadError" severity="error" :closable="false" class="mb-4">{{ loadError }}</Message>

            <DataTable :value="filtered" :loading="loading" dataKey="id" paginator :rows="10" :rowsPerPageOptions="[10, 25, 50]" scrollable stripedRows>
                <template #empty>
                    <div class="text-center text-muted-color py-8">{{ search ? 'No customer matches your search.' : 'No customers yet.' }}</div>
                </template>
                <Column field="name" header="Name" sortable style="min-width: 12rem" />
                <Column header="Email" style="min-width: 14rem">
                    <template #body="{ data }"><a :href="`mailto:${data.email}`" class="text-primary">{{ data.email }}</a></template>
                </Column>
                <Column header="Phone" style="min-width: 10rem">
                    <template #body="{ data }">
                        <a v-if="data.phone" :href="`tel:${data.phone}`" class="text-primary">{{ data.phone }}</a>
                        <span v-else class="text-muted-color">—</span>
                    </template>
                </Column>
                <Column field="bookings_count" header="Bookings" sortable style="min-width: 8rem" />
                <Column header="Joined" style="min-width: 9rem">
                    <template #body="{ data }">{{ joined(data.created_at) }}</template>
                </Column>
            </DataTable>
        </div>
    </div>
</template>
