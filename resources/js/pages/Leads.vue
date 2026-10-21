<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import api, { validationMessage } from '../lib/api';
import KanbanBoard from '../components/KanbanBoard.vue';
import LeadForm from '../components/LeadForm.vue';

const items = ref([]);
const boardItems = ref([]);
const boardRevision = ref(0);
const view = ref('list');
const query = ref('');
const stage = ref('');
const page = ref(1);
const lastPage = ref(1);
const busy = ref(false);
const saving = ref(false);
const error = ref('');
const success = ref('');
const editing = ref(undefined);
const stages = ['new', 'contacted', 'proposal', 'closed_won', 'closed_lost'];
const money = (value) => Number(value || 0).toLocaleString(undefined, { style: 'currency', currency: 'EUR' });
const hasPrev = computed(() => page.value > 1);

async function load() {
  busy.value = true;
  error.value = '';
  try {
    if (view.value === 'board') {
      const results = [];
      let cursor = 1;
      let totalPages = 1;
      do {
        const { data } = await api.get('/api/v1/leads', { params: { page: cursor, q: query.value || undefined, stage: stage.value || undefined } });
        results.push(...data.data);
        totalPages = data.meta?.last_page ?? 1;
        cursor += 1;
      } while (cursor <= totalPages);
      boardItems.value = results;
      boardRevision.value += 1;
    } else {
      const { data } = await api.get('/api/v1/leads', { params: { page: page.value, q: query.value || undefined, stage: stage.value || undefined } });
      items.value = data.data;
      lastPage.value = data.meta?.last_page || 1;
    }
  } catch (exception) {
    error.value = validationMessage(exception);
  } finally {
    busy.value = false;
  }
}
watch([view, stage], () => { page.value = 1; load(); });
onMounted(load);

async function save(values) {
  saving.value = true;
  error.value = '';
  try {
    if (editing.value) await api.patch('/api/v1/leads/' + editing.value.id, values);
    else { delete values.version; await api.post('/api/v1/leads', values); }
    editing.value = undefined;
    success.value = 'Lead saved successfully.';
    await load();
  } catch (exception) { error.value = validationMessage(exception); }
  finally { saving.value = false; }
}
async function move({ id, stage: nextStage }) {
  const old = boardItems.value.find((lead) => lead.id === id);
  if (!old || old.stage === nextStage) { boardRevision.value += 1; return; }
  const before = { ...old };
  boardItems.value = boardItems.value.map((lead) => lead.id === id ? { ...lead, stage: nextStage } : lead);
  boardRevision.value += 1;
  try {
    const { data } = await api.patch('/api/v1/leads/' + id + '/stage', { stage: nextStage, version: before.version });
    boardItems.value = boardItems.value.map((lead) => lead.id === id ? data.data : lead);
    success.value = 'Pipeline updated.';
  } catch (exception) {
    boardItems.value = boardItems.value.map((lead) => lead.id === id ? before : lead);
    error.value = validationMessage(exception);
  } finally { boardRevision.value += 1; }
}
async function convert(lead) {
  if (!confirm('Convert this lead into a customer and contact?')) return;
  try {
    await api.post('/api/v1/leads/' + lead.id + '/convert', { version: lead.version });
    success.value = 'Customer created from lead.';
    await load();
  } catch (exception) { error.value = validationMessage(exception); }
}
async function remove(lead) {
  if (!confirm('Delete this lead permanently?')) return;
  try {
    await api.delete('/api/v1/leads/' + lead.id);
    success.value = 'Lead deleted.';
    await load();
  } catch (exception) { error.value = validationMessage(exception); }
}
</script>

<template>
  <main class="mx-auto max-w-7xl space-y-6 px-6 py-10">
    <header class="flex flex-wrap items-center justify-between gap-4">
      <div><h1 class="text-3xl font-bold">Leads & pipeline</h1><p class="mt-1 text-slate-600">Track opportunities and convert them into customers.</p></div>
      <button type="button" @click="editing = null" class="rounded-lg bg-indigo-700 px-4 py-2 font-semibold text-white">+ New lead</button>
    </header>
    <p v-if="error" role="alert" class="rounded-lg bg-red-50 p-3 text-red-800">{{ error }}</p>
    <p v-if="success" role="status" class="rounded-lg bg-green-50 p-3 text-green-800">{{ success }}</p>

    <section v-if="editing !== undefined" class="rounded-xl border bg-white p-6">
      <h2 class="mb-4 text-xl font-bold">{{ editing ? 'Edit lead' : 'New lead' }}</h2>
      <LeadForm :lead="editing" :saving="saving" @save="save" @cancel="editing = undefined" />
    </section>

    <div class="flex flex-wrap items-end gap-3">
      <label class="text-sm font-medium">Search leads<input v-model="query" @keyup.enter="page = 1; load()" maxlength="100" placeholder="Name, company or email" class="mt-1 block rounded-lg border bg-white px-3 py-2"></label>
      <label class="text-sm font-medium">Stage<select v-model="stage" class="mt-1 block rounded-lg border bg-white px-3 py-2"><option value="">All stages</option><option v-for="s in stages" :key="s" :value="s">{{ s.replaceAll('_', ' ') }}</option></select></label>
      <button @click="page = 1; load()" class="rounded-lg border bg-white px-4 py-2">Search</button>
      <div class="ml-auto flex gap-2">
        <button @click="view = 'list'" :aria-pressed="view === 'list'" class="rounded-lg border px-4 py-2" :class="view === 'list' ? 'bg-indigo-700 text-white' : 'bg-white'">List</button>
        <button @click="view = 'board'" :aria-pressed="view === 'board'" class="rounded-lg border px-4 py-2" :class="view === 'board' ? 'bg-indigo-700 text-white' : 'bg-white'">Kanban</button>
      </div>
    </div>
    <p v-if="busy" role="status" class="text-sm text-slate-600">Loading leads…</p>
    <KanbanBoard v-if="view === 'board' && !busy" :key="boardRevision" :leads="boardItems" @stage="move" @edit="editing = $event" />
    <section v-if="view === 'list' && !busy" class="overflow-x-auto rounded-xl border bg-white">
      <table class="w-full min-w-[720px] text-left text-sm">
        <thead class="border-b bg-slate-50 text-slate-600"><tr><th class="p-4">Name / company</th><th>Stage</th><th>Deal value</th><th>Expected value</th><th>Actions</th></tr></thead>
        <tbody>
          <tr v-for="lead in items" :key="lead.id" class="border-b last:border-0">
            <td class="p-4"><strong>{{ lead.name }}</strong><p class="text-slate-500">{{ lead.company_name || lead.email }}</p></td>
            <td>{{ lead.stage.replaceAll('_', ' ') }}</td><td>{{ money(lead.deal_value) }}</td><td>{{ money(lead.expected_value) }}</td>
            <td class="space-x-3"><button class="text-indigo-700" @click="editing = lead">Edit</button><button v-if="!lead.converted_customer_id" class="text-green-700" @click="convert(lead)">Convert</button><button class="text-red-700" @click="remove(lead)">Delete</button></td>
          </tr>
          <tr v-if="!items.length"><td colspan="5" class="p-8 text-center text-slate-500">No leads found.</td></tr>
        </tbody>
      </table>
      <footer class="flex items-center justify-between p-4"><button :disabled="!hasPrev" @click="page--; load()" class="rounded border px-3 py-1 disabled:opacity-40">Previous</button><span>Page {{ page }} / {{ lastPage }}</span><button :disabled="page >= lastPage" @click="page++; load()" class="rounded border px-3 py-1 disabled:opacity-40">Next</button></footer>
    </section>
  </main>
</template>
