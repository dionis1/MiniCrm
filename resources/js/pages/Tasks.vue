<script setup>
import { onMounted, ref, watch } from 'vue';
import { api, errorText } from '../lib/api';
import { useToastStore } from '../stores/toasts';

const toast = useToastStore();
const tasks = ref([]);
const filter = ref('open');
const priority = ref('');
const error = ref('');
const page = ref(1);
const lastPage = ref(1);
const editing = ref(null);
const modal = ref(false);
const saving = ref(false);
const leads = ref([]);
const customers = ref([]);
const empty = () => ({ title: '', description: '', priority: 'normal', due_at: '', lead_id: '', company_id: '' });
const form = ref(empty());

async function load() {
  try {
    const { data } = await api.get('/api/v1/tasks', { params: { filter: filter.value, priority: priority.value, page: page.value } });
    tasks.value = data.data;
    lastPage.value = data.meta.last_page;
    error.value = '';
  } catch (e) { error.value = errorText(e); }
}
async function open(task = null) {
  editing.value = task;
  form.value = task ? {
    title: task.title, description: task.description || '', priority: task.priority,
    due_at: task.due_at ? new Date(task.due_at).toISOString().slice(0, 16) : '',
    lead_id: task.lead_id || '', company_id: task.customer_id || '',
  } : empty();
  try {
    const [l, c] = await Promise.all([api.get('/api/v1/leads', { params: { per_page: 100 } }), api.get('/api/v1/customers')]);
    leads.value = l.data.data;
    customers.value = c.data.data;
  } catch (e) { error.value = errorText(e); }
  modal.value = true;
}
async function save() {
  saving.value = true;
  try {
    const payload = { ...form.value, lead_id: form.value.lead_id || null, company_id: form.value.company_id || null,
      due_at: form.value.due_at ? new Date(form.value.due_at).toISOString() : null };
    if (editing.value) await api.patch('/api/v1/tasks/' + editing.value.id, payload);
    else await api.post('/api/v1/tasks', payload);
    modal.value = false; toast.push('Follow-up saved'); await load();
  } catch (e) { error.value = errorText(e); }
  finally { saving.value = false; }
}
async function toggle(task) {
  const old = task.completed;
  task.completed = !old;
  try {
    const { data } = await api.patch('/api/v1/tasks/' + task.id + '/complete', { completed: !old });
    Object.assign(task, data.data);
    toast.push(old ? 'Task reopened' : 'Task completed');
    await load();
  } catch (e) { task.completed = old; toast.push(errorText(e), 'error'); }
}
async function remove(task) {
  if (!confirm('Delete this follow-up?')) return;
  try { await api.delete('/api/v1/tasks/' + task.id); toast.push('Task deleted'); await load(); }
  catch (e) { toast.push(errorText(e), 'error'); }
}
watch([filter, priority], () => { page.value = 1; load(); });
watch(page, load);
onMounted(load);
</script>
<template>
  <main class="mx-auto max-w-6xl p-6 lg:p-10">
    <div class="mb-7 flex flex-wrap items-center justify-between gap-3">
      <div><p class="text-xs font-bold uppercase tracking-wider text-indigo-600">Follow-up workflow</p>
        <h1 class="mt-1 text-3xl font-bold">Tasks</h1></div>
      <button class="rounded-lg bg-indigo-600 px-5 py-3 font-semibold text-white" @click="open()">+ New follow-up</button>
    </div>
    <div class="mb-5 flex flex-wrap gap-2">
      <button v-for="state in ['open','today','overdue','completed','all']" :key="state" type="button"
        class="rounded-lg px-4 py-2 text-sm capitalize" :class="filter === state ? 'bg-indigo-600 text-white' : 'border bg-white'"
        @click="filter = state">{{ state }}</button>
      <label class="ml-auto text-sm">Priority
        <select v-model="priority" class="ml-2 rounded-lg border bg-white px-3 py-2">
          <option value="">All</option><option value="high">High</option><option value="normal">Normal</option><option value="low">Low</option>
        </select></label>
    </div>
    <p v-if="error" role="alert" class="mb-4 text-rose-700">{{ error }}</p>
    <div class="rounded-xl border border-slate-200 bg-white">
      <article v-for="task in tasks" :key="task.id" class="flex flex-wrap justify-between gap-4 border-b border-slate-100 p-5">
        <div class="flex items-start gap-3">
          <input type="checkbox" :checked="task.completed" :aria-label="'Complete ' + task.title" class="mt-1 size-5"
            @change="toggle(task)">
          <div><h2 class="font-semibold" :class="task.completed ? 'text-slate-400 line-through' : ''">{{ task.title }}</h2>
            <p class="text-sm text-slate-500">{{ task.description }}</p>
            <p class="mt-2 text-xs text-slate-500"><span class="mr-3 capitalize">{{ task.priority }} priority</span>
              <time v-if="task.due_at">{{ new Date(task.due_at).toLocaleString() }}</time><span v-else>No due date</span></p>
          </div>
        </div>
        <div class="flex gap-3 text-sm"><button class="text-indigo-700" @click="open(task)">Edit</button>
          <button class="text-rose-700" @click="remove(task)">Delete</button></div>
      </article>
      <p v-if="!tasks.length" class="p-10 text-center text-slate-500">No matching tasks.</p>
    </div>
    <div class="mt-5 flex justify-between text-sm"><span>Page {{ page }} / {{ lastPage }}</span><div class="flex gap-4">
      <button :disabled="page === 1" @click="page--">Previous</button>
      <button :disabled="page === lastPage" @click="page++">Next</button>
    </div></div>
    <div v-if="modal" class="fixed inset-0 z-30 flex items-center justify-center bg-slate-950/60 p-4" @click.self="modal = false">
      <section role="dialog" aria-modal="true" aria-labelledby="task-title" class="w-full max-w-lg rounded-xl bg-white p-6">
        <div class="flex justify-between"><h2 id="task-title" class="text-xl font-bold">{{ editing ? 'Edit task' : 'New follow-up' }}</h2>
          <button aria-label="Close" @click="modal = false">✕</button></div>
        <form class="mt-5 grid gap-3" @submit.prevent="save">
          <label class="grid gap-1 text-sm">Title <input v-model="form.title" required maxlength="200" class="rounded-lg border p-2"></label>
          <label class="grid gap-1 text-sm">Description <textarea v-model="form.description" maxlength="3000" class="rounded-lg border p-2"></textarea></label>
          <div class="grid gap-3 sm:grid-cols-2">
            <label class="grid gap-1 text-sm">Priority
              <select v-model="form.priority" class="rounded-lg border p-2"><option value="low">Low</option><option value="normal">Normal</option><option value="high">High</option></select></label>
            <label class="grid gap-1 text-sm">Due date
              <input v-model="form.due_at" type="datetime-local" class="rounded-lg border p-2"></label>
          </div>
          <label class="grid gap-1 text-sm">Related lead
            <select v-model="form.lead_id" class="rounded-lg border p-2"><option value="">None</option>
              <option v-for="lead in leads" :key="lead.id" :value="lead.id">{{ lead.name }}</option></select></label>
          <label class="grid gap-1 text-sm">Related customer
            <select v-model="form.company_id" class="rounded-lg border p-2"><option value="">None</option>
              <option v-for="customer in customers" :key="customer.id" :value="customer.id">{{ customer.name }}</option></select></label>
          <p v-if="error" role="alert" class="text-sm text-rose-700">{{ error }}</p>
          <button :disabled="saving" class="rounded-lg bg-indigo-600 p-3 font-semibold text-white">Save task</button>
        </form>
      </section>
    </div>
  </main>
</template>
