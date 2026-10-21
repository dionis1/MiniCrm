<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';
import Sortable from 'sortablejs';
import { api, errorText } from '../lib/api';
import { filterLeads, totalDealValue } from '../lib/pipeline';
import { useToastStore } from '../stores/toasts';

const toasts = useToastStore();
const stages = [
  { id: 'new', label: 'New lead', accent: 'border-slate-400' },
  { id: 'contacted', label: 'Contacted', accent: 'border-blue-400' },
  { id: 'proposal', label: 'Proposal sent', accent: 'border-amber-400' },
  { id: 'closed_won', label: 'Closed won', accent: 'border-emerald-400' },
  { id: 'closed_lost', label: 'Closed lost', accent: 'border-rose-400' },
];
const leads = ref([]);
const search = ref('');
const busy = ref(false);
const saving = ref(false);
const error = ref('');
const modal = ref(false);
const editing = ref(null);
const form = ref(empty());
const stageElements = ref({});
let instances = [];
function empty() {
  return { name: '', company_name: '', email: '', phone: '', stage: 'new', deal_value: 0, probability: 10 };
}
const filtered = computed(() => filterLeads(leads.value, search.value));
const byStage = id => filtered.value.filter(l => l.stage === id);
const total = id => totalDealValue(byStage(id));
function setStageEl(el, id) { if (el) stageElements.value[id] = el; }
async function load() {
  busy.value = true;
  try {
    const { data } = await api.get('/api/v1/leads', { params: { per_page: 100 } });
    leads.value = data.data;
    await nextTick();
    instances.forEach(instance => instance.destroy());
    instances = stages.map(stage => Sortable.create(stageElements.value[stage.id], {
      group: 'pipeline',
      animation: 180,
      draggable: '[data-lead-id]',
      ghostClass: 'opacity-50',
      onEnd: event => moved(event),
    }));
  } catch (e) {
    error.value = errorText(e);
  } finally { busy.value = false; }
}
async function moved(evt) {
  if (evt.from === evt.to) return;
  const lead = leads.value.find(x => String(x.id) === evt.item.dataset.leadId);
  const nextStage = evt.to.dataset.stage;
  // Restore Sortable's DOM mutation before Vue updates its keyed children.
  const sibling = evt.from.children[evt.oldIndex] || null;
  evt.from.insertBefore(evt.item, sibling);
  if (!lead || !nextStage || lead.converted_customer_id) return;
  const oldStage = lead.stage;
  lead.stage = nextStage;
  try {
    const { data } = await api.patch('/api/v1/leads/' + lead.id + '/stage', { stage: nextStage, version: lead.version });
    Object.assign(lead, data.data);
    toasts.push('Lead moved to ' + nextStage.replace('_', ' '));
  } catch (e) {
    lead.stage = oldStage;
    toasts.push(errorText(e), 'error');
    if (e.response?.status === 409) await load();
  }
}
function create() { editing.value = null; form.value = empty(); modal.value = true; }
function edit(lead) {
  editing.value = lead;
  form.value = { ...lead };
  modal.value = true;
}
async function save() {
  saving.value = true;
  error.value = '';
  try {
    if (editing.value) {
      await api.patch('/api/v1/leads/' + editing.value.id, form.value);
    } else {
      await api.post('/api/v1/leads', form.value);
    }
    modal.value = false;
    toasts.push(editing.value ? 'Lead updated' : 'Lead created');
    await load();
  } catch (e) { error.value = errorText(e); }
  finally { saving.value = false; }
}
async function convert(lead) {
  if (!window.confirm('Convert this lead into a customer?')) return;
  try {
    const { data } = await api.post('/api/v1/leads/' + lead.id + '/convert', { version: lead.version });
    toasts.push('Customer created: ' + data.data.name);
    await load();
  } catch (e) {
    toasts.push(errorText(e), 'error');
    if (e.response?.status === 409) await load();
  }
}
onMounted(load);
onUnmounted(() => instances.forEach(instance => instance.destroy()));
</script>

<template>
  <main class="mx-auto max-w-[1600px] p-6 lg:p-10">
    <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
      <div><p class="text-xs font-bold uppercase tracking-wider text-indigo-600">Opportunity management</p>
        <h1 class="mt-1 text-3xl font-bold">Sales pipeline</h1>
        <p class="mt-2 text-sm text-slate-500">Drag cards between stages. Failed changes are rolled back automatically.</p></div>
      <button type="button" class="rounded-lg bg-indigo-600 px-5 py-3 font-semibold text-white" @click="create">+ New lead</button>
    </div>
    <label class="mb-6 block max-w-md text-sm font-medium">
      <span class="sr-only">Filter leads</span>
      <input v-model="search" type="search" placeholder="Find leads by name, email or company"
        class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3">
    </label>
    <p v-if="error && !modal" role="alert" class="mb-4 text-rose-700">{{ error }}</p>
    <p v-if="busy" class="mb-4 text-slate-500">Loading pipeline…</p>
    <div class="flex gap-4 overflow-x-auto pb-6" aria-label="Lead pipeline">
      <section v-for="stage in stages" :key="stage.id" class="w-64 shrink-0 rounded-xl bg-slate-100 p-3">
        <header class="mb-3 rounded-lg border-t-4 bg-white p-3 shadow-sm" :class="stage.accent">
          <div class="flex justify-between gap-1 text-sm font-semibold"><h2>{{ stage.label }}</h2><span>{{ byStage(stage.id).length }}</span></div>
          <p class="mt-2 text-xs text-slate-500">${{ total(stage.id).toLocaleString() }}</p>
        </header>
        <div :ref="el => setStageEl(el, stage.id)" :data-stage="stage.id" class="min-h-36 space-y-2">
          <article v-for="lead in byStage(stage.id)" :key="lead.id" :data-lead-id="lead.id"
            class="lead-item cursor-grab rounded-lg border border-slate-200 bg-white p-4 shadow-sm active:cursor-grabbing">
            <button type="button" class="text-left font-semibold text-slate-900 hover:text-indigo-700" @click="edit(lead)">{{ lead.name }}</button>
            <p class="mt-1 text-xs text-slate-500">{{ lead.company_name || 'Individual' }}</p>
            <p class="mt-3 text-sm font-semibold">${{ Number(lead.deal_value).toLocaleString() }}
              <span class="ml-1 font-normal text-slate-500">· {{ lead.probability }}%</span></p>
            <p class="text-xs text-slate-500">Expected: ${{ Number(lead.expected_value).toLocaleString() }}</p>
            <button v-if="stage.id === 'closed_won' && !lead.converted_customer_id" type="button"
              class="mt-3 text-xs font-semibold text-emerald-700 hover:underline" @click="convert(lead)">Convert to customer →</button>
            <span v-if="lead.converted_customer_id" class="mt-3 block text-xs text-emerald-700">Converted ✓</span>
          </article>
        </div>
      </section>
    </div>
    <p class="text-xs text-slate-500">Showing the latest {{ leads.length }} leads. Use search and filters for larger pipelines.</p>

    <div v-if="modal" class="fixed inset-0 z-30 flex items-center justify-center bg-slate-950/60 px-4" @click.self="modal = false">
      <section role="dialog" aria-modal="true" aria-labelledby="lead-modal-title" class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
        <div class="flex justify-between"><h2 id="lead-modal-title" class="text-xl font-bold">{{ editing ? 'Edit lead' : 'New lead' }}</h2>
          <button type="button" aria-label="Close" @click="modal = false">✕</button></div>
        <form class="mt-5 grid gap-4" @submit.prevent="save">
          <label class="grid gap-1 text-sm font-medium">Name <input v-model="form.name" required maxlength="160" class="rounded-lg border border-slate-300 p-2"></label>
          <label class="grid gap-1 text-sm font-medium">Company <input v-model="form.company_name" maxlength="160" class="rounded-lg border border-slate-300 p-2"></label>
          <div class="grid gap-3 sm:grid-cols-2">
            <label class="grid gap-1 text-sm font-medium">Email <input v-model="form.email" type="email" class="rounded-lg border border-slate-300 p-2"></label>
            <label class="grid gap-1 text-sm font-medium">Phone <input v-model="form.phone" class="rounded-lg border border-slate-300 p-2"></label>
            <label class="grid gap-1 text-sm font-medium">Deal value <input v-model.number="form.deal_value" type="number" min="0" step=".01" class="rounded-lg border border-slate-300 p-2"></label>
            <label class="grid gap-1 text-sm font-medium">Probability (%) <input v-model.number="form.probability" type="number" min="0" max="100" class="rounded-lg border border-slate-300 p-2"></label>
          </div>
          <label class="grid gap-1 text-sm font-medium">Stage <select v-model="form.stage" class="rounded-lg border border-slate-300 p-2">
            <option v-for="stage in stages" :key="stage.id" :value="stage.id">{{ stage.label }}</option>
          </select></label>
          <p v-if="error" role="alert" class="text-sm text-rose-700">{{ error }}</p>
          <div class="flex justify-end gap-2">
            <button type="button" class="rounded-lg border border-slate-300 px-4 py-2" @click="modal = false">Cancel</button>
            <button type="submit" :disabled="saving" class="rounded-lg bg-indigo-600 px-5 py-2 font-semibold text-white disabled:opacity-50">{{ saving ? 'Saving…' : 'Save lead' }}</button>
          </div>
        </form>
      </section>
    </div>
  </main>
</template>
