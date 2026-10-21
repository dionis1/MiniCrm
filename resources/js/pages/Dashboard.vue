<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { Doughnut } from 'vue-chartjs';
import { ArcElement, Chart as ChartJS, Legend, Tooltip } from 'chart.js';
import { api } from '../lib/api';
ChartJS.register(ArcElement, Legend, Tooltip);

const data = ref(null);
const error = ref('');
onMounted(async () => {
  try { data.value = (await api.get('/api/v1/dashboard/metrics')).data.data; }
  catch { error.value = 'Unable to load dashboard metrics.'; }
});
const stages = ['new', 'contacted', 'proposal', 'closed_won', 'closed_lost'];
</script>
<template>
  <main class="mx-auto max-w-7xl p-6 lg:p-10">
    <div class="mb-8 flex flex-wrap items-center justify-between gap-3">
      <div><p class="text-sm font-semibold uppercase tracking-wider text-indigo-600">Sales overview</p>
        <h1 class="mt-1 text-3xl font-bold">Pipeline at a glance</h1></div>
      <RouterLink to="/leads" class="rounded-lg bg-indigo-600 px-5 py-3 font-semibold text-white">Open pipeline →</RouterLink>
    </div>
    <p v-if="error" role="alert" class="mb-5 text-rose-700">{{ error }}</p>
    <div v-if="data" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="card in [
          { title: 'Active leads', value: data.leads },
          { title: 'Customers', value: data.customers },
          { title: 'Pipeline value', value: '$' + Number(data.open_pipeline_value).toLocaleString() },
          { title: 'Won revenue', value: '$' + Number(data.won_value).toLocaleString() },
        ]" :key="card.title" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-sm text-slate-500">{{ card.title }}</p><p class="mt-2 text-3xl font-bold">{{ card.value }}</p>
      </div>
      <RouterLink to="/tasks" class="rounded-xl border border-orange-200 bg-orange-50 p-6">
        <p class="text-sm text-orange-900">Overdue follow-ups</p><p class="mt-2 text-3xl font-bold text-orange-950">{{ data.tasks_overdue }}</p>
      </RouterLink>
      <RouterLink to="/tasks" class="rounded-xl border border-indigo-200 bg-indigo-50 p-6">
        <p class="text-sm text-indigo-900">Due today</p><p class="mt-2 text-3xl font-bold text-indigo-950">{{ data.tasks_today }}</p>
      </RouterLink>
      <div class="rounded-xl border border-slate-200 bg-white p-6 sm:col-span-2">
        <h2 class="mb-5 text-lg font-semibold">Pipeline by stage</h2>
        <div class="mx-auto max-w-60">
          <Doughnut :data="{ labels: stages.map(s => s.replaceAll('_', ' ')), datasets: [{ data: stages.map(s => data.by_stage[s] || 0), backgroundColor: ['#6366f1','#818cf8','#fbbf24','#10b981','#f43f5e'] }] }" />
        </div>
      </div>
    </div>
    <p v-else-if="!error" class="text-slate-500">Loading your dashboard…</p>
  </main>
</template>
