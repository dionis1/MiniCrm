<script setup>
import { RouterLink, RouterView, useRouter } from 'vue-router';
import { useAuthStore } from './stores/auth';

const auth = useAuthStore();
const router = useRouter();
async function signOut() {
  await auth.logout();
  await router.push({ name: 'login' });
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 text-slate-900">
    <header class="border-b border-slate-200 bg-white">
      <nav class="mx-auto flex max-w-7xl flex-wrap items-center gap-4 px-6 py-4" aria-label="Main navigation">
        <RouterLink to="/dashboard" class="text-xl font-extrabold tracking-tight text-indigo-700">MiniCRM</RouterLink>
        <span class="ml-auto text-sm text-slate-600" v-if="auth.user">{{ auth.user.name }}</span>
        <button v-if="auth.authenticated" type="button" @click="signOut" class="rounded-lg border px-3 py-2 text-sm hover:bg-slate-100">Sign out</button>
      </nav>
    </header>
    <RouterView />
  </div>
</template>
