<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import Sortable from 'sortablejs';

const props = defineProps({
  leads: { type: Array, required: true },
});
const emit = defineEmits(['stage', 'edit']);
const stages = [
  ['new', 'New lead'],
  ['contacted', 'Contacted'],
  ['proposal', 'Proposal sent'],
  ['closed_won', 'Closed won'],
  ['closed_lost', 'Closed lost'],
];
const columns = ref([]);
const sortables = [];
const money = (amount) => Number(amount || 0).toLocaleString(undefined, { style: 'currency', currency: 'EUR' });

onMounted(() => {
  for (const column of columns.value) {
    sortables.push(Sortable.create(column, {
      group: 'leads',
      animation: 120,
      draggable: '[data-lead-id]',
      onEnd(event) {
        const id = Number(event.item.dataset.leadId);
        const stage = event.to.dataset.stage;
        if (stage) emit('stage', { id, stage });
      },
    }));
  }
});
onUnmounted(() => sortables.forEach((instance) => instance.destroy()));
</script>

<template>
  <div class="grid gap-4 overflow-x-auto pb-4 lg:grid-cols-5" aria-label="Lead pipeline">
    <section v-for="([stage, label], index) in stages" :key="stage" class="min-w-48 rounded-xl bg-slate-100 p-3">
      <header class="mb-3 flex items-center justify-between gap-2">
        <h2 class="text-sm font-bold text-slate-700">{{ label }}</h2>
        <span class="rounded-full bg-white px-2 py-0.5 text-xs text-slate-600">{{ leads.filter(lead => lead.stage === stage).length }}</span>
      </header>
      <div :ref="el => { columns[index] = el; }" :data-stage="stage" class="min-h-36 space-y-2">
        <article v-for="lead in leads.filter(item => item.stage === stage)" :key="lead.id" :data-lead-id="lead.id" class="cursor-grab rounded-lg border border-slate-200 bg-white p-3 shadow-sm active:cursor-grabbing">
          <button type="button" @click="emit('edit', lead)" class="text-left font-semibold text-indigo-700 hover:underline">{{ lead.name }}</button>
          <p class="mt-1 truncate text-xs text-slate-500">{{ lead.company_name || lead.email || 'No company' }}</p>
          <div class="mt-3 flex items-center justify-between gap-2 text-xs text-slate-600">
            <span>{{ money(lead.deal_value) }}</span>
            <span>{{ lead.probability }}%</span>
          </div>
        </article>
      </div>
    </section>
  </div>
</template>
