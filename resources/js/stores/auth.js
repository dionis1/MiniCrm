import { defineStore } from 'pinia';
import { ref } from 'vue';
import { api } from '../lib/api';

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null);
  const loaded = ref(false);

  async function load(force = false) {
    if (loaded.value && !force) return;
    try {
      const { data } = await api.get('/api/v1/me');
      user.value = data.data;
    } catch (error) {
      if (error.response?.status !== 401) throw error;
      user.value = null;
    } finally {
      loaded.value = true;
    }
  }

  async function login(credentials) {
    await api.get('/sanctum/csrf-cookie');
    await api.post('/login', credentials);
    await load(true);
  }

  async function register(payload) {
    await api.get('/sanctum/csrf-cookie');
    await api.post('/register', payload);
    await load(true);
  }

  async function logout() {
    await api.post('/logout');
    user.value = null;
    loaded.value = true;
  }

  return { user, loaded, load, login, register, logout };
});
