<script setup>
import { onMounted, onUnmounted, reactive, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { latestRequest } from '../lib/latestRequest';
import { api, errorText } from '../lib/api';
import { useToastStore } from '../stores/toasts';

const toast = useToastStore();
const customers = ref([]);
const page = ref(1);
const lastPage = ref(1);
const q = ref('');
const tag = ref('');
const status = ref('');
const loading = ref(false);
const busy = ref(false);
const formOpen = ref(false);
const editing = ref(null);
const requests = latestRequest();
const error = ref('');
const form = reactive({ name: '', email: '', phone: '', website_url: '', status: 'active', tags: '' });

async function load() {
  const current = requests.start();
  loading.value = true;
  error.value = '';
  try {
    const { data } = await api.get('/api/v1/customers', {
      params: { page: page.value, q: q.value || undefined, tag: tag.value || undefined, status: status.value || undefined, sort: 'name', direction: 'asc' },
    });
    if (!current()) return;
    customers.value = data.data;
    lastPage.value = data.meta?.last_page || 1;
  } catch (reason) { if (current()) error.value = errorText(reason); }
  finally { if (current()) loading.value = false; }
}
function open(customer = null) {
  editing.value = customer;
  Object.assign(form, {
    name: customer?.name || '', email: customer?.email || '', phone: customer?.phone || '',
    website_url: customer?.website_url || '', status: customer?.status || 'active',
    tags: (customer?.tags || []).join(', '),
  });
  formOpen.value = true;
}
async function save() {
  busy.value = true;
  error.value = '';
  const payload = { ...form, tags: form.tags.split(',').map(item => item.trim()).filter(Boolean) };
  try {
    if (editing.value) await api.patch('/api/v1/customers/' + editing.value.id, payload);
    else await api.post('/api/v1/customers', payload);
    formOpen.value = false;
    toast.push('Customer saved.');
    await load();
  } catch (reason) { error.value = errorText(reason); }
  finally { busy.value = false; }
}
async function remove(customer) {
  if (!confirm('Delete ' + customer.name + ' and their linked contacts?')) return;
  try {
    await api.delete('/api/v1/customers/' + customer.id);
    toast.push('Customer removed.');
    await load();
  } catch (reason) { error.value = errorText(reason); }
}
function filter() { page.value = 1; load(); }
onMounted(load);
onUnmounted(() => requests.invalidate());
</script>

<template>
  <main class="mx-auto max-w-7xl space-y-6 p-6 lg:p-10">
    <header class="flex flex-wrap items-center justify-between gap-3">
      <div><h1 class="text-3xl font-bold">Customers</h1><p class="mt-1 text-slate-600">Companies, contacts, and account activity in one place.</p></div>
      <button @click="open()" class="rounded-lg bg-indigo-700 px-4 py-2 font-semibold text-white">+ Add customer</button>
    </header>
    <p v-if="error" role="alert" class="rounded-lg bg-rose-50 p-4 text-rose-800">{{ error }}</p>
    <form v-if="formOpen" @submit.prevent="save" class="grid gap-4 rounded-xl border bg-white p-6 sm:grid-cols-2">
      <h2 class="text-xl font-bold sm:col-span-2">{{ editing ? 'Edit customer' : 'New customer' }}</h2>
      <label class="text-sm font-medium">Company name *<input v-model.trim="form.name" required maxlength="160" class="mt-1 block w-full rounded-lg border p-2"></label>
      <label class="text-sm font-medium">Email<input v-model.trim="form.email" type="email" class="mt-1 block w-full rounded-lg border p-2"></label>
      <label class="text-sm font-medium">Phone<input v-model.trim="form.phone" maxlength="40" class="mt-1 block w-full rounded-lg border p-2"></label>
      <label class="text-sm font-medium">Website<input v-model.trim="form.website_url" type="url" placeholder="https://example.com" class="mt-1 block w-full rounded-lg border p-2"></label>
      <label class="text-sm font-medium">Status<select v-model="form.status" class="mt-1 block w-full rounded-lg border p-2"><option value="active">Active</option><option value="inactive">Inactive</option></select></label>
      <label class="text-sm font-medium">Tags (comma separated)<input v-model="form.tags" maxlength="500" class="mt-1 block w-full rounded-lg border p-2"></label>
      <div class="flex gap-2 sm:col-span-2"><button :disabled="busy" class="rounded-lg bg-indigo-700 px-4 py-2 text-white disabled:opacity-50">{{ busy ? 'Saving…' : 'Save customer' }}</button><button type="button" @click="formOpen = false" class="rounded-lg border px-4 py-2">Cancel</button></div>
    </form>
    <form @submit.prevent="filter" class="flex flex-wrap items-end gap-3">
      <label class="text-sm font-medium">Search<input v-model="q" placeholder="Company name" maxlength="100" class="mt-1 block rounded-lg border bg-white px-3 py-2"></label>
      <label class="text-sm font-medium">Tag<input v-model.trim="tag" maxlength="40" placeholder="enterprise" class="mt-1 block rounded-lg border bg-white px-3 py-2"></label>
      <label class="text-sm font-medium">Status<select v-model="status" class="mt-1 block rounded-lg border bg-white px-3 py-2"><option value="">Any</option><option value="active">Active</option><option value="inactive">Inactive</option></select></label>
      <button class="rounded-lg border bg-white px-4 py-2">Filter</button>
    </form>
    <p v-if="loading" role="status" class="text-slate-600">Loading customers…</p>
    <section v-else class="overflow-x-auto rounded-xl border bg-white">
      <table class="w-full min-w-[620px] text-left text-sm">
        <thead class="border-b bg-slate-50 text-slate-600"><tr><th class="p-4">Company</th><th>Email</th><th>Status / tags</th><th>Actions</th></tr></thead>
        <tbody>
          <tr v-for="customer in customers" :key="customer.id" class="border-b last:border-0">
            <td class="p-4"><RouterLink :to="'/customers/' + customer.id" class="font-bold text-indigo-700 hover:underline">{{ customer.name }}</RouterLink></td>
            <td>{{ customer.email || '—' }}</td>
            <td><span class="font-medium">{{ customer.status }}</span><p class="text-xs text-slate-500">{{ (customer.tags || []).join(', ') }}</p></td>
            <td class="space-x-3"><button class="text-indigo-700" @click="open(customer)">Edit</button><button class="text-rose-700" @click="remove(customer)">Delete</button></td>
          </tr>
          <tr v-if="!customers.length"><td colspan="4" class="p-8 text-center text-slate-500">No customers found. Add your first company.</td></tr>
        </tbody>
      </table>
      <footer class="flex items-center justify-between p-4"><button :disabled="page <= 1" @click="page--; load()" class="rounded border px-3 py-1 disabled:opacity-50">Previous</button><span>Page {{ page }} of {{ lastPage }}</span><button :disabled="page >= lastPage" @click="page++; load()" class="rounded border px-3 py-1 disabled:opacity-50">Next</button></footer>
    </section>
  </main>
</template>
