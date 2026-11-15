<script setup>
import { useLayout } from '@/layout/composables/layout';
import { api } from '@/lib/api';
import { money, parseISODate, prettyDate, prettyTime, statusOf } from '@/lib/format';
import { computed, onMounted, ref, watch } from 'vue';

const { layoutConfig, isDarkTheme } = useLayout();

const data = ref(null);
const loading = ref(true);
const loadError = ref('');
const chartData = ref(null);
const chartOptions = ref(null);

const cards = computed(() => {
    const stats = data.value?.stats ?? {};
    return [
        { label: 'Waiting for you', value: stats.pending ?? 0, hint: 'pending bookings', icon: 'pi pi-clock', bg: 'bg-orange-100 dark:bg-orange-400/10', text: 'text-orange-500', to: { name: 'admin-bookings', query: { status: 'pending' } } },
        { label: 'Today', value: stats.today ?? 0, hint: 'bookings today', icon: 'pi pi-calendar', bg: 'bg-blue-100 dark:bg-blue-400/10', text: 'text-blue-500', to: { name: 'admin-bookings' } },
        { label: 'Customers', value: stats.customers ?? 0, hint: 'registered', icon: 'pi pi-users', bg: 'bg-cyan-100 dark:bg-cyan-400/10', text: 'text-cyan-500', to: { name: 'admin-customers' } },
        { label: 'Earned', value: money(stats.revenue ?? 0), hint: 'from completed bookings', icon: 'pi pi-wallet', bg: 'bg-purple-100 dark:bg-purple-400/10', text: 'text-purple-500', to: { name: 'admin-bookings', query: { status: 'completed' } } }
    ];
});

// Chart ke rang theme se lete hain, taaki dark mode aur primary colour badalne par bhi sahi dikhe.
function buildChart() {
    if (!data.value) return;
    const style = getComputedStyle(document.documentElement);
    const textMuted = style.getPropertyValue('--text-color-secondary');
    const border = style.getPropertyValue('--surface-border');

    chartData.value = {
        labels: data.value.week.map((day) => parseISODate(day.date).toLocaleDateString('en-IN', { weekday: 'short', day: 'numeric' })),
        datasets: [
            {
                label: 'Bookings',
                data: data.value.week.map((day) => day.count),
                backgroundColor: style.getPropertyValue('--p-primary-400'),
                borderRadius: 6,
                maxBarThickness: 36
            }
        ]
    };

    chartOptions.value = {
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { ticks: { color: textMuted }, grid: { display: false } },
            y: { beginAtZero: true, ticks: { color: textMuted, precision: 0 }, grid: { color: border } }
        }
    };
}

async function load() {
    loading.value = true;
    loadError.value = '';
    try {
        data.value = await api.get('/admin/dashboard');
        buildChart();
    } catch (e) {
        loadError.value = e.message;
    } finally {
        loading.value = false;
    }
}

watch([() => layoutConfig.primary, () => layoutConfig.surface, isDarkTheme], buildChart);
onMounted(load);
</script>

<template>
    <div>
        <div class="mb-6">
            <h1 class="text-3xl font-bold m-0">Dashboard</h1>
            <p class="text-muted-color mt-1 mb-0">A quick look at how your business is doing.</p>
        </div>

        <Message v-if="loadError" severity="error" :closable="false" class="mb-6">
            {{ loadError }}
            <Button label="Try again" link class="ml-2 p-0" @click="load" />
        </Message>

        <div class="grid grid-cols-12 gap-6">
            <div v-for="card in cards" :key="card.label" class="col-span-12 sm:col-span-6 xl:col-span-3">
                <router-link :to="card.to" class="card mb-0 block stat">
                    <div class="flex justify-between mb-4">
                        <div>
                            <span class="block text-muted-color font-medium mb-3">{{ card.label }}</span>
                            <Skeleton v-if="loading" width="4rem" height="1.75rem" />
                            <div v-else class="text-surface-900 dark:text-surface-0 font-bold text-2xl">{{ card.value }}</div>
                        </div>
                        <div class="flex items-center justify-center rounded-border" :class="card.bg" style="width: 2.5rem; height: 2.5rem">
                            <i :class="[card.icon, card.text]" style="font-size: 1.25rem"></i>
                        </div>
                    </div>
                    <span class="text-muted-color">{{ card.hint }}</span>
                </router-link>
            </div>

            <div class="col-span-12 xl:col-span-7">
                <div class="card mb-0 h-full">
                    <div class="font-semibold text-xl mb-1">Next 7 days</div>
                    <p class="text-muted-color mt-0 mb-4">Pending and confirmed bookings per day</p>
                    <Skeleton v-if="loading" height="18rem" />
                    <Chart v-else-if="chartData" type="bar" :data="chartData" :options="chartOptions" style="height: 18rem" />
                </div>
            </div>

            <div class="col-span-12 xl:col-span-5">
                <div class="card mb-0 h-full">
                    <div class="flex items-center justify-between mb-4">
                        <div class="font-semibold text-xl">Coming up</div>
                        <Button as="router-link" :to="{ name: 'admin-bookings' }" label="See all" link class="p-0" />
                    </div>
                    <Skeleton v-if="loading" height="12rem" />
                    <p v-else-if="!data?.upcoming?.length" class="text-muted-color m-0">No upcoming bookings. New ones will show up here.</p>
                    <ul v-else class="list-none p-0 m-0">
                        <li v-for="booking in data.upcoming" :key="booking.id" class="flex items-center justify-between gap-4 py-3 upcoming">
                            <div class="min-w-0">
                                <div class="font-medium truncate">{{ booking.service?.name }} · {{ booking.user?.name }}</div>
                                <div class="text-muted-color">{{ prettyDate(booking.booking_date) }}, {{ prettyTime(booking.start_time) }}</div>
                            </div>
                            <Tag :value="statusOf(booking.status).label" :severity="statusOf(booking.status).severity" />
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.stat {
    color: inherit;
    transition: box-shadow 0.15s;
}

.stat:hover {
    box-shadow: 0 0 0 1px var(--primary-color);
}

.upcoming + .upcoming {
    border-top: 1px solid var(--surface-border);
}
</style>
