<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { api, errorText } from '../lib/api';
import { useToastStore } from '../stores/toasts';

const route = useRoute();
const toast = useToastStore();
const customer = ref(null);
const activities = ref([]);
const error = ref('');
const tab = ref('contacts');
const contactForm = ref({ first_name: '', last_name: '', email: '', phone: '', position: '' });
const activityForm = ref({ type: 'note', description: '' });
async function load() {
  try {
    const id = route.params.id;
    customer.value = (await api.get('/api/v1/customers/' + id)).data.data;
    activities.value = (await api.get('/api/v1/customers/' + id + '/activities')).data.data;
  } catch (e) { error.value = errorText(e); }
}
async function addContact() {
  try {
    await api.post('/api/v1/customers/' + route.params.id + '/contacts', contactForm.value);
    toast.push('Contact added');
    contactForm.value = { first_name: '', last_name: '', email: '', phone: '', position: '' };
    await load();
  } catch (e) { error.value = errorText(e); }
}
async function logActivity() {
  try {
    await api.post('/api/v1/customers/' + route.params.id + '/activities', activityForm.value);
    toast.push('Activity recorded');
    activityForm.value.description = '';
    await load();
  } catch (e) { error.value = errorText(e); }
}
async function removeContact(id) {
  if (!confirm('Delete this contact?')) return;
  try {
    await api.delete('/api/v1/contacts/' + id);
    await load();
  } catch (e) { error.value = errorText(e); }
}
onMounted(load);
</script>
<template>
  <main class="mx-auto max-w-5xl p-6 lg:p-10">
    <RouterLink to="/customers" class="text-sm font-medium text-indigo-700">← All customers</RouterLink>
    <p v-if="error" role="alert" class="mt-4 text-rose-700">{{ error }}</p>
    <template v-if="customer">
      <header class="my-7 rounded-xl border border-slate-200 bg-white p-6">
        <h1 class="text-3xl font-bold">{{ customer.name }}</h1>
        <p class="mt-3 text-slate-500">{{ customer.email }} · {{ customer.phone }}</p>
        <p class="mt-2 text-sm capitalize text-slate-600">{{ customer.status }}</p>
      </header>
      <nav aria-label="Customer detail tabs" class="mb-5 flex gap-3 border-b border-slate-200">
        <button v-for="name in ['contacts','activity']" :key="name" class="border-b-2 px-3 py-3 text-sm font-semibold capitalize"
          :class="tab === name ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-slate-500'" @click="tab = name">{{ name }}</button>
      </nav>
      <template v-if="tab === 'contacts'">
        <section class="rounded-xl border border-slate-200 bg-white p-6">
          <h2 class="mb-4 text-xl font-semibold">Contacts</h2>
          <ul class="divide-y divide-slate-100">
            <li v-for="person in customer.contacts" :key="person.id" class="flex flex-wrap justify-between gap-3 py-4 text-sm">
              <div><strong>{{ person.first_name }} {{ person.last_name }}</strong>
                <p class="text-slate-500">{{ person.position }} · {{ person.email }}</p></div>
              <button class="text-rose-700" @click="removeContact(person.id)">Delete</button>
            </li>
          </ul>
          <form class="mt-5 grid gap-3 border-t border-slate-200 pt-5 sm:grid-cols-2" @submit.prevent="addContact">
            <label class="grid gap-1 text-sm">First name <input v-model="contactForm.first_name" required class="rounded-lg border p-2"></label>
            <label class="grid gap-1 text-sm">Last name <input v-model="contactForm.last_name" required class="rounded-lg border p-2"></label>
            <label class="grid gap-1 text-sm">Email <input v-model="contactForm.email" type="email" class="rounded-lg border p-2"></label>
            <label class="grid gap-1 text-sm">Phone <input v-model="contactForm.phone" class="rounded-lg border p-2"></label>
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-white sm:col-span-2">Add contact</button>
          </form>
        </section>
      </template>
      <section v-else class="rounded-xl border border-slate-200 bg-white p-6">
        <h2 class="mb-4 text-xl font-semibold">Customer timeline</h2>
        <ol class="space-y-3">
          <li v-for="event in activities" :key="event.id" class="rounded-lg bg-slate-50 p-4">
            <span class="text-xs font-semibold uppercase text-indigo-600">{{ event.type }}</span>
            <p class="mt-1">{{ event.description }}</p>
            <time class="text-xs text-slate-500">{{ new Date(event.created_at).toLocaleString() }}</time>
          </li>
        </ol>
        <form class="mt-6 grid gap-3 border-t pt-5" @submit.prevent="logActivity">
          <label class="grid gap-1 text-sm">Activity type
            <select v-model="activityForm.type" class="rounded-lg border p-2"><option value="note">Note</option><option value="call">Call</option><option value="email">Email</option><option value="meeting">Meeting</option></select></label>
          <label class="grid gap-1 text-sm">Description
            <textarea v-model="activityForm.description" required maxlength="3000" rows="3" class="rounded-lg border p-2"></textarea></label>
          <button class="rounded-lg bg-indigo-600 px-4 py-2 text-white">Save activity</button>
        </form>
      </section>
    </template>
  </main>
</template>
