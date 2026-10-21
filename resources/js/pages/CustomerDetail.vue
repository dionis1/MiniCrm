<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { api, errorText } from '../lib/api';
import { useToastStore } from '../stores/toasts';

const route = useRoute();
const toasts = useToastStore();
const customer = ref(null);
const contacts = ref([]);
const activities = ref([]);
const tab = ref('contacts');
const error = ref('');
const busy = ref(false);
const contactForm = ref(false);
const editing = ref(null);
const contact = reactive({ first_name: '', last_name: '', email: '', phone: '', position: '', tags: '' });
const activity = reactive({ type: 'note', description: '' });
const customerId = computed(() => route.params.id);
const detailUrl = computed(() => '/api/v1/customers/' + customerId.value);

async function load() {
  error.value = '';
  try {
    const [details, people, history] = await Promise.all([
      api.get(detailUrl.value),
      api.get(detailUrl.value + '/contacts'),
      api.get(detailUrl.value + '/activities'),
    ]);
    customer.value = details.data.data;
    contacts.value = people.data.data;
    activities.value = history.data.data;
  } catch (reason) { error.value = errorText(reason); }
}
function openContact(record = null) {
  editing.value = record;
  Object.assign(contact, {
    first_name: record?.first_name || '', last_name: record?.last_name || '',
    email: record?.email || '', phone: record?.phone || '', position: record?.position || '',
    tags: (record?.tags || []).join(', '),
  });
  contactForm.value = true;
  tab.value = 'contacts';
}
async function saveContact() {
  busy.value = true;
  try {
    const payload = { ...contact, tags: contact.tags.split(',').map(t => t.trim()).filter(Boolean) };
    if (editing.value) await api.patch('/api/v1/contacts/' + editing.value.id, payload);
    else await api.post(detailUrl.value + '/contacts', payload);
    toasts.push('Contact saved.');
    contactForm.value = false;
    await load();
  } catch (reason) { error.value = errorText(reason); }
  finally { busy.value = false; }
}
async function deleteContact(record) {
  if (!confirm('Delete this contact?')) return;
  try { await api.delete('/api/v1/contacts/' + record.id); toasts.push('Contact removed.'); await load(); }
  catch (reason) { error.value = errorText(reason); }
}
async function logActivity() {
  busy.value = true;
  try {
    await api.post(detailUrl.value + '/activities', activity);
    activity.description = '';
    toasts.push('Activity added.');
    await load();
    tab.value = 'activity';
  } catch (reason) { error.value = errorText(reason); }
  finally { busy.value = false; }
}
watch(customerId, load);
onMounted(load);
</script>

<template>
  <main class="mx-auto max-w-6xl space-y-6 p-6 lg:p-10">
    <RouterLink to="/customers" class="text-sm font-semibold text-indigo-700">← All customers</RouterLink>
    <p v-if="error" role="alert" class="rounded-lg bg-rose-50 p-4 text-rose-800">{{ error }}</p>
    <template v-if="customer">
      <header class="rounded-xl border bg-white p-6">
        <p class="text-xs font-bold uppercase tracking-wide text-indigo-600">Customer 360°</p>
        <h1 class="mt-2 text-3xl font-bold">{{ customer.name }}</h1>
        <p class="mt-2 text-slate-600">{{ customer.email || 'No email recorded' }} · {{ customer.phone || 'No phone' }}</p>
        <div class="mt-3 flex flex-wrap gap-2">
          <span class="rounded-full bg-green-50 px-3 py-1 text-xs font-semibold text-green-800">{{ customer.status }}</span>
          <span v-for="tag in customer.tags" :key="tag" class="rounded-full bg-indigo-50 px-3 py-1 text-xs text-indigo-700">{{ tag }}</span>
        </div>
      </header>
      <div role="tablist" aria-label="Customer details" class="flex gap-3 border-b">
        <button id="contacts-tab" role="tab" :aria-selected="tab === 'contacts'" @click="tab = 'contacts'" :class="tab === 'contacts' ? 'border-indigo-700 text-indigo-700' : 'border-transparent text-slate-600'" class="border-b-2 px-4 py-3 font-semibold">Contacts ({{ contacts.length }})</button>
        <button id="activity-tab" role="tab" :aria-selected="tab === 'activity'" @click="tab = 'activity'" :class="tab === 'activity' ? 'border-indigo-700 text-indigo-700' : 'border-transparent text-slate-600'" class="border-b-2 px-4 py-3 font-semibold">Activity feed</button>
      </div>
      <section v-if="tab === 'contacts'" role="tabpanel" aria-labelledby="contacts-tab" class="space-y-4">
        <button @click="openContact()" class="rounded-lg bg-indigo-700 px-4 py-2 font-semibold text-white">+ Add contact</button>
        <form v-if="contactForm" @submit.prevent="saveContact" class="grid gap-3 rounded-xl border bg-white p-5 sm:grid-cols-2">
          <h2 class="text-lg font-bold sm:col-span-2">{{ editing ? 'Edit contact' : 'New contact' }}</h2>
          <label class="text-sm">First name *<input v-model.trim="contact.first_name" required maxlength="120" class="mt-1 block w-full rounded border p-2"></label>
          <label class="text-sm">Last name *<input v-model.trim="contact.last_name" required maxlength="120" class="mt-1 block w-full rounded border p-2"></label>
          <label class="text-sm">Email<input v-model.trim="contact.email" type="email" class="mt-1 block w-full rounded border p-2"></label>
          <label class="text-sm">Phone<input v-model.trim="contact.phone" class="mt-1 block w-full rounded border p-2"></label>
          <label class="text-sm">Position<input v-model.trim="contact.position" class="mt-1 block w-full rounded border p-2"></label>
          <label class="text-sm">Tags<input v-model="contact.tags" placeholder="decision-maker, executive" class="mt-1 block w-full rounded border p-2"></label>
          <div class="flex gap-2 sm:col-span-2"><button :disabled="busy" class="rounded-lg bg-indigo-700 px-4 py-2 text-white disabled:opacity-50">Save contact</button><button type="button" @click="contactForm = false" class="rounded-lg border px-4 py-2">Cancel</button></div>
        </form>
        <article v-for="person in contacts" :key="person.id" class="flex flex-wrap items-center justify-between gap-3 rounded-xl border bg-white p-5">
          <div><h3 class="font-bold">{{ person.first_name }} {{ person.last_name }}</h3><p class="text-sm text-slate-600">{{ person.position }} · {{ person.email }}</p><p class="text-xs text-slate-500">{{ person.phone }}</p></div>
          <div class="flex gap-3 text-sm"><button @click="openContact(person)" class="text-indigo-700">Edit</button><button @click="deleteContact(person)" class="text-rose-700">Delete</button></div>
        </article>
        <p v-if="!contacts.length" class="text-slate-500">No contacts have been added yet.</p>
      </section>
      <section v-else role="tabpanel" aria-labelledby="activity-tab" class="space-y-5">
        <form @submit.prevent="logActivity" class="rounded-xl border bg-white p-5">
          <h2 class="text-lg font-semibold">Log an interaction</h2>
          <div class="mt-4 grid gap-3 sm:grid-cols-4">
            <label class="text-sm">Type<select v-model="activity.type" class="mt-1 block w-full rounded border p-2"><option value="call">Call</option><option value="note">Note</option><option value="email">Email</option><option value="meeting">Meeting</option></select></label>
            <label class="text-sm sm:col-span-3">Details<textarea v-model.trim="activity.description" required maxlength="3000" rows="2" class="mt-1 block w-full rounded border p-2"></textarea></label>
          </div>
          <button :disabled="busy" class="mt-4 rounded-lg bg-indigo-700 px-4 py-2 text-white disabled:opacity-50">Save activity</button>
        </form>
        <ol class="space-y-3">
          <li v-for="item in activities" :key="item.id" class="rounded-xl border bg-white p-5">
            <div class="flex items-center justify-between"><strong class="capitalize">{{ item.type.replaceAll('_', ' ') }}</strong><time class="text-sm text-slate-500">{{ new Date(item.created_at).toLocaleString() }}</time></div>
            <p class="mt-2 whitespace-pre-wrap text-sm text-slate-700">{{ item.description }}</p>
          </li>
        </ol>
        <p v-if="!activities.length" class="text-slate-500">No interactions logged yet.</p>
      </section>
    </template>
    <p v-else-if="!error" class="text-slate-500">Loading customer…</p>
  </main>
</template>
