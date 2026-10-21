<script setup>
import { reactive, ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { validationMessage } from '../lib/api';

const router = useRouter();
const route = useRoute();
const auth = useAuthStore();
const registering = ref(false);
const busy = ref(false);
const error = ref('');
const form = reactive({ name: '', email: '', password: '', password_confirmation: '' });

async function submit() {
  busy.value = true;
  error.value = '';
  try {
    if (registering.value) await auth.register(form);
    else await auth.login(form.email, form.password);
    const destination = typeof route.query.redirect === 'string' && route.query.redirect.startsWith('/') && !route.query.redirect.startsWith('//')
      ? route.query.redirect : '/dashboard';
    await router.replace(destination);
  } catch (exception) {
    error.value = validationMessage(exception);
  } finally {
    busy.value = false;
  }
}
</script>

<template>
  <main class="mx-auto max-w-md px-6 py-16">
    <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
      <h1 class="text-2xl font-bold">{{ registering ? 'Create your MiniCRM account' : 'Sign in to MiniCRM' }}</h1>
      <p class="mt-2 text-sm text-slate-600">Your sales workspace, securely managed with Laravel Sanctum.</p>
      <div v-if="error" role="alert" class="mt-5 rounded-lg bg-red-50 p-3 text-sm text-red-800">{{ error }}</div>
      <form class="mt-6 space-y-4" @submit.prevent="submit">
        <label v-if="registering" class="block text-sm font-medium">Name
          <input v-model.trim="form.name" required maxlength="120" autocomplete="name" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2">
        </label>
        <label class="block text-sm font-medium">Email
          <input v-model.trim="form.email" type="email" required autocomplete="email" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2">
        </label>
        <label class="block text-sm font-medium">Password
          <input v-model="form.password" type="password" required :minlength="registering ? 12 : undefined" :autocomplete="registering ? 'new-password' : 'current-password'" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2">
        </label>
        <label v-if="registering" class="block text-sm font-medium">Confirm password
          <input v-model="form.password_confirmation" type="password" required minlength="12" autocomplete="new-password" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2">
        </label>
        <button :disabled="busy" type="submit" class="w-full rounded-lg bg-indigo-700 px-4 py-2.5 font-semibold text-white hover:bg-indigo-800 disabled:opacity-50">{{ busy ? 'Please wait…' : registering ? 'Create account' : 'Sign in' }}</button>
      </form>
      <button type="button" @click="registering = !registering; error = ''" class="mt-5 text-sm font-medium text-indigo-700">
        {{ registering ? 'Already have an account? Sign in' : 'New here? Create an account' }}
      </button>
    </div>
  </main>
</template>
