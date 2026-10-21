<script setup>
import { computed, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { api, errorText } from '../lib/api';
const route = useRoute();
const resetting = computed(() => Boolean(route.params.token));
const email = ref(typeof route.query.email === 'string' ? route.query.email : '');
const password = ref('');
const confirmation = ref('');
const busy = ref(false);
const message = ref('');
const error = ref('');
async function submit() {
  busy.value = true; error.value = ''; message.value = '';
  try {
    await api.get('/sanctum/csrf-cookie');
    const { data } = await api.post(resetting.value ? '/reset-password' : '/forgot-password', {
      email: email.value, ...(resetting.value ? { token: route.params.token, password: password.value, password_confirmation: confirmation.value } : {}),
    });
    message.value = data.message;
    password.value = ''; confirmation.value = '';
  } catch (reason) { error.value = errorText(reason); }
  finally { busy.value = false; }
}
</script>
<template>
  <main class="flex min-h-screen items-center justify-center px-5 py-12">
    <section class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
      <h1 class="text-2xl font-bold">{{ resetting ? 'Choose a new password' : 'Reset your password' }}</h1>
      <p class="mt-2 text-sm text-slate-600">{{ resetting ? 'Use at least 12 characters.' : 'We will email a link if your account exists.' }}</p>
      <form @submit.prevent="submit" class="mt-6 grid gap-4">
        <label class="grid gap-1 text-sm">Email<input v-model.trim="email" required type="email" autocomplete="email" class="rounded-lg border p-3"></label>
        <template v-if="resetting">
          <label class="grid gap-1 text-sm">New password<input v-model="password" required type="password" minlength="12" autocomplete="new-password" class="rounded-lg border p-3"></label>
          <label class="grid gap-1 text-sm">Confirm password<input v-model="confirmation" required type="password" minlength="12" autocomplete="new-password" class="rounded-lg border p-3"></label>
        </template>
        <p v-if="error" role="alert" class="text-sm text-rose-700">{{ error }}</p>
        <p v-if="message" role="status" class="text-sm text-emerald-800">{{ message }}</p>
        <button :disabled="busy" class="rounded-lg bg-indigo-700 p-3 font-semibold text-white disabled:opacity-50">{{ busy ? 'Please wait…' : resetting ? 'Save new password' : 'Send reset link' }}</button>
      </form>
      <RouterLink to="/login" class="mt-6 block text-sm font-semibold text-indigo-700">Back to sign in</RouterLink>
    </section>
  </main>
</template>
