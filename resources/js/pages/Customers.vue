<script setup>
import { onMounted, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { api, errorText } from '../lib/api';
import { useToastStore } from '../stores/toasts';

const toast = useToastStore();
const customers = ref([]);
const page = ref(1);
const lastPage = ref(1);
const search = ref('');
const tag = ref('');
const status = ref('');
const sort = ref('created_at');
const direction = ref('desc');
const loading = ref(false);
const error = ref('');
const modal = ref(false);
const saving = ref(false);
const editing = ref(null);
const form = ref(empty());
let debounce;
function empty() { return { name: '', email: '', phone: '', website_url: '', status: 'active', tags: [] }; }
async function load() {
  loading.value = true;
  try {
    const { data } = await api.get('/api/v1/customers', { params: {
      page: page.value, q: search.value, tag: tag.value,
      status: status.value, sort: sort.value, direction: direction.value,
    } });
    customers.value = data.data;
    lastPage.value = data.meta.last_page;
    error.value = '';
  } catch (e) { error.value = errorText(e); }
  finally { loading.value = false; }
}
watch([search, tag, status, sort, direction], () => {
  page.value = 1;
  clearTimeout(debounce);
  debounce = setTimeout(load, 280);
});
watch(page, load);
onMounted(load);
function create() { editing.value = null; form.value = empty(); modal.value = true; }
function edit(customer) { editing.value = customer; form.value = { ...customer, tags: customer.tags || [] }; modal.value = true; }
async function save() {
  saving.value = true;
  try {
    if (editing.value) await api.patch('/api/v1/customers/' + editing.value.id, form.value);
    else await api.post('/api/v1/customers', form.value);
    modal.value = false;
    toast.push(editing.value ? 'Customer updated' : 'Customer created');
    await load();
  } catch (e) { error.value = errorText(e); }
  finally { saving.value = false; }
}
async function remove(customer) {
  if (!confirm('Delete customer and its contacts?')) return;
  try {
    await api.delete('/api/v1/customers/' + customer.id);
    toast.push('Customer deleted');
    await load();
  } catch (e) { toast.push(errorText(e), 'error'); }
}
</script>
<template>
  <main class="mx-auto max-w-7xl p-6 lg:p-10">
    <div class="mb-7 flex flex-wrap items-center justify-between gap-3">
      <div><p class="text-xs font-semibold uppercase tracking-wider text-indigo-600">Relationships</p>
        <h1 class="mt-1 text-3xl font-bold">Customers</h1></div>
      <button class="rounded-lg bg-indigo-600 px-5 py-3 font-semibold text-white" @click="create">+ Add customer</button>
    </div>
    <div class="mb-5 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:grid-cols-5">
      <label class="grid gap-1 text-sm sm:col-span-2">Search
        <input v-model="search" type="search" placeholder="Company name…" class="rounded-lg border border-slate-300 p-2"></label>
      <label class="grid gap-1 text-sm">Tag
        <input v-model="tag" placeholder="enterprise" class="rounded-lg border border-slate-300 p-2"></label>
      <label class="grid gap-1 text-sm">Status
        <select v-model="status" class="rounded-lg border border-slate-300 p-2"><option value="">All</option><option value="active">Active</option><option value="inactive">Inactive</option></select></label>
      <label class="grid gap-1 text-sm">Sort
        <select v-model="sort" class="rounded-lg border border-slate-300 p-2"><option value="created_at">Recently added</option><option value="name">Name</option></select></label>
    </div>
    <p v-if="error" role="alert" class="mb-4 text-rose-700">{{ error }}</p>
    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
      <table class="w-full text-left text-sm">
        <thead class="border-b border-slate-200 bg-slate-50 text-slate-600"><tr>
          <th class="p-4">Company</th><th class="p-4">Email</th><th class="p-4">Status</th><th class="p-4">Tags</th><th class="p-4">Actions</th>
        </tr></thead>
        <tbody>
          <tr v-for="customer in customers" :key="customer.id" class="border-b border-slate-100">
            <td class="p-4 font-medium"><RouterLink :to="'/customers/' + customer.id" class="text-indigo-700 hover:underline">{{ customer.name }}</RouterLink></td>
            <td class="p-4">{{ customer.email || '—' }}</td>
            <td class="p-4 capitalize">{{ customer.status }}</td>
            <td class="p-4"><span v-for="value in customer.tags" :key="value" class="mr-1 rounded bg-indigo-50 px-2 py-1 text-xs text-indigo-700">{{ value }}</span></td>
            <td class="p-4"><button class="mr-3 text-indigo-700" @click="edit(customer)">Edit</button>
              <button class="text-rose-700" @click="remove(customer)">Delete</button></td>
          </tr>
          <tr v-if="!customers.length && !loading"><td colspan="5" class="p-8 text-center text-slate-500">No customers found.</td></tr>
        </tbody>
      </table>
      <div class="flex items-center justify-between p-4 text-sm">
        <span>Page {{ page }} of {{ lastPage }}</span>
        <div class="flex gap-2">
          <button :disabled="page === 1" class="rounded border px-3 py-2 disabled:opacity-40" @click="page--">Previous</button>
          <button :disabled="page === lastPage" class="rounded border px-3 py-2 disabled:opacity-40" @click="page++">Next</button>
        </div>
      </div>
    </div>
    <div v-if="modal" class="fixed inset-0 z-30 flex items-center justify-center bg-slate-950/60 p-4" @click.self="modal = false">
      <section role="dialog" aria-modal="true" aria-labelledby="customer-modal" class="w-full max-w-lg rounded-xl bg-white p-6">
        <div class="flex justify-between"><h2 id="customer-modal" class="text-xl font-bold">{{ editing ? 'Edit customer' : 'New customer' }}</h2>
          <button aria-label="Close" @click="modal = false">✕</button></div>
        <form class="mt-5 grid gap-3" @submit.prevent="save">
          <label class="grid gap-1 text-sm">Company name <input v-model="form.name" required maxlength="160" class="rounded-lg border p-2"></label>
          <label class="grid gap-1 text-sm">Email <input v-model="form.email" type="email" class="rounded-lg border p-2"></label>
          <label class="grid gap-1 text-sm">Phone <input v-model="form.phone" class="rounded-lg border p-2"></label>
          <label class="grid gap-1 text-sm">Website <input v-model="form.website_url" type="url" class="rounded-lg border p-2"></label>
          <label class="grid gap-1 text-sm">Status <select v-model="form.status" class="rounded-lg border p-2"><option value="active">Active</option><option value="inactive">Inactive</option></select></label>
          <label class="grid gap-1 text-sm">Tags (comma-separated)
            <input :value="form.tags.join(', ')" class="rounded-lg border p-2" @input="form.tags = $event.target.value.split(',').map(s => s.trim()).filter(Boolean)">
          </label>
          <p v-if="error" role="alert" class="text-sm text-rose-700">{{ error }}</p>
          <button :disabled="saving" class="rounded-lg bg-indigo-600 px-4 py-3 font-semibold text-white disabled:opacity-50">Save customer</button>
        </form>
      </section>
    </div>
  </main>
</template>
