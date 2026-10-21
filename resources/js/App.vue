<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { RouterLink, RouterView, useRouter } from 'vue-router';
import { api } from './lib/api';
import { useAuthStore } from './stores/auth';
import { useToastStore } from './stores/toasts';

const auth = useAuthStore();
const toasts = useToastStore();
const router = useRouter();
const nav = [
  { label: 'Overview', to: '/dashboard', icon: '◫' },
  { label: 'Pipeline', to: '/leads', icon: '▦' },
  { label: 'Customers', to: '/customers', icon: '◉' },
  { label: 'Tasks', to: '/tasks', icon: '✓' },
];
const showPalette = ref(false);
const search = ref('');
const results = ref({ leads: [], customers: [], tasks: [] });
const busy = ref(false);
let searchTimer;
const matches = computed(() => [
  ...results.value.leads.map(r => ({ id: r.id, title: r.name, kind: 'Lead', to: '/leads' })),
  ...results.value.customers.map(r => ({ id: r.id, title: r.name, kind: 'Customer', to: '/customers/' + r.id })),
  ...results.value.tasks.map(r => ({ id: r.id, title: r.title, kind: 'Task', to: '/tasks' })),
]);
watch(search, value => {
  clearTimeout(searchTimer);
  if (value.trim().length < 2) {
    results.value = { leads: [], customers: [], tasks: [] };
    return;
  }
  searchTimer = setTimeout(async () => {
    busy.value = true;
    try { results.value = (await api.get('/api/v1/search', { params: { q: value.trim() } })).data.data; }
    catch { results.value = { leads: [], customers: [], tasks: [] }; }
    finally { busy.value = false; }
  }, 250);
});
function keys(event) {
  if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
    event.preventDefault();
    showPalette.value = !showPalette.value;
  }
  if (event.key === 'Escape') showPalette.value = false;
}
function error(event) { toasts.push(event.detail, 'error'); }
onMounted(() => { window.addEventListener('keydown', keys); window.addEventListener('crm:error', error); });
onUnmounted(() => { window.removeEventListener('keydown', keys); window.removeEventListener('crm:error', error); clearTimeout(searchTimer); });
async function signOut() {
  try {
    await auth.logout();
    toasts.push('Signed out');
    await router.push('/login');
  } catch {
    toasts.push('Unable to sign out. Try again.', 'error');
  }
}
function go(to) {
  showPalette.value = false;
  search.value = '';
  router.push(to);
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 text-slate-900">
    <div v-if="auth.user" class="min-h-screen md:flex">
      <aside class="bg-slate-950 p-5 text-white md:min-h-screen md:w-60 md:shrink-0">
        <RouterLink to="/dashboard" class="text-xl font-bold tracking-tight">◈ MiniCRM</RouterLink>
        <p class="mt-2 text-xs text-slate-400">Your sales workspace</p>
        <nav aria-label="Main navigation" class="mt-8 flex flex-wrap gap-2 md:flex-col">
          <RouterLink v-for="item in nav" :key="item.to" :to="item.to"
            class="rounded-lg px-3 py-2 text-sm hover:bg-slate-800"
            active-class="bg-indigo-600 text-white">
            <span aria-hidden="true" class="mr-2">{{ item.icon }}</span>{{ item.label }}
          </RouterLink>
        </nav>
      </aside>
      <div class="min-w-0 flex-1">
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-white px-6 py-4">
          <button type="button" class="rounded-lg border border-slate-200 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50"
            @click="showPalette = true">Search anything <kbd class="ml-4 text-xs">Ctrl K</kbd></button>
          <div class="flex items-center gap-4 text-sm">
            <span class="text-slate-600">{{ auth.user.name }}</span>
            <button type="button" class="font-medium text-indigo-700 hover:underline" @click="signOut">Sign out</button>
          </div>
        </header>
        <RouterView />
      </div>
    </div>
    <RouterView v-else />
    <div aria-live="polite" class="fixed bottom-5 right-5 z-50 grid max-w-sm gap-2">
      <div v-for="toast in toasts.toasts" :key="toast.id" role="status"
        class="rounded-lg px-5 py-3 text-sm text-white shadow-lg" :class="toast.type === 'error' ? 'bg-rose-700' : 'bg-slate-900'">
        {{ toast.message }}
        <button type="button" class="ml-4" aria-label="Dismiss notification" @click="toasts.remove(toast.id)">×</button>
      </div>
    </div>
    <div v-if="showPalette" class="fixed inset-0 z-40 flex justify-center bg-slate-950/60 px-4 pt-20" @click.self="showPalette = false">
      <div role="dialog" aria-modal="true" aria-label="Quick search" class="h-fit w-full max-w-xl rounded-xl bg-white p-5 shadow-2xl">
        <div class="flex gap-2">
          <input v-model="search" autofocus aria-label="Search CRM records" placeholder="Search leads, customers, tasks…"
            class="min-w-0 flex-1 rounded-lg border border-slate-300 px-4 py-3">
          <button type="button" @click="showPalette = false" aria-label="Close search">✕</button>
        </div>
        <p v-if="busy" class="p-4 text-sm text-slate-500">Searching…</p>
        <button v-for="match in matches" :key="match.kind + match.id" type="button"
          class="block w-full rounded-lg px-3 py-3 text-left text-sm hover:bg-slate-100" @click="go(match.to)">
          <span class="mr-2 text-xs uppercase text-indigo-600">{{ match.kind }}</span>{{ match.title }}
        </button>
        <p v-if="!busy && search.length >= 2 && !matches.length" class="p-4 text-sm text-slate-500">No matches.</p>
      </div>
    </div>
  </div>
</template>
