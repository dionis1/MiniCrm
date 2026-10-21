<script setup>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useToastStore } from '../stores/toasts';
import { errorText } from '../lib/api';

const auth = useAuthStore();
const toast = useToastStore();
const route = useRoute();
const router = useRouter();
const registering = ref(false);
const submitting = ref(false);
const error = ref('');
const form = ref({ name: '', email: '', password: '', password_confirmation: '' });

async function submit() {
  submitting.value = true;
  error.value = '';
  try {
    if (registering.value) await auth.register(form.value);
    else await auth.login({ email: form.value.email, password: form.value.password });
    toast.push(registering.value ? 'Account created.' : 'Welcome back!');
    await router.replace(typeof route.query.redirect === 'string' && route.query.redirect.startsWith('/') ? route.query.redirect : '/dashboard');
  } catch (reason) {
    error.value = errorText(reason);
  } finally { submitting.value = false; }
}
</script>
<template>
  <main class="flex min-h-screen items-center justify-center px-5 py-12">
    <section class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
      <h1 class="text-3xl font-bold">◈ MiniCRM</h1>
      <p class="mt-2 text-slate-600">{{ registering ? 'Create your account' : 'Sign in to your workspace' }}</p>
      <form class="mt-8 grid gap-4" @submit.prevent="submit">
        <label v-if="registering" class="grid gap-1 text-sm font-medium">Name
          <input v-model="form.name" required maxlength="120" class="rounded-lg border border-slate-300 p-3"></label>
        <label class="grid gap-1 text-sm font-medium">Email
          <input v-model="form.email" type="email" required autocomplete="email" class="rounded-lg border border-slate-300 p-3"></label>
        <label class="grid gap-1 text-sm font-medium">Password
          <input v-model="form.password" type="password" required :minlength="registering ? 12 : 1"
            :autocomplete="registering ? 'new-password' : 'current-password'" class="rounded-lg border border-slate-300 p-3"></label>
        <label v-if="registering" class="grid gap-1 text-sm font-medium">Confirm password
          <input v-model="form.password_confirmation" type="password" required minlength="12" autocomplete="new-password"
            class="rounded-lg border border-slate-300 p-3"></label>
        <p v-if="error" role="alert" class="rounded-lg bg-rose-50 p-3 text-sm text-rose-800">{{ error }}</p>
        <button :disabled="submitting" class="rounded-lg bg-indigo-600 p-3 font-semibold text-white disabled:opacity-50">
          {{ submitting ? 'Please wait…' : registering ? 'Create account' : 'Sign in' }}
        </button>
      </form>
      <button type="button" class="mt-6 text-sm font-medium text-indigo-700 hover:underline" @click="registering = !registering; error = ''">
        {{ registering ? 'Already have an account? Sign in' : 'New here? Create an account' }}
      </button>
    </section>
  </main>
</template>
