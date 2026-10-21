import { defineStore } from 'pinia';
import api, { csrf } from '../lib/api';

export const useAuthStore = defineStore('auth', {
  state: () => ({ user: null, ready: false }),
  getters: { authenticated: (state) => Boolean(state.user) },
  actions: {
    async load() {
      if (this.ready) return;
      try {
        const { data } = await api.get('/api/v1/me');
        this.user = data.data;
      } catch (error) {
        if (error.response?.status !== 401) throw error;
        this.user = null;
      } finally {
        this.ready = true;
      }
    },
    async login(email, password) {
      await csrf();
      await api.post('/login', { email, password });
      this.ready = false;
      await this.load();
    },
    async register(values) {
      await csrf();
      await api.post('/register', values);
      this.ready = false;
      await this.load();
    },
    async logout() {
      try {
        await api.post('/logout');
      } finally {
        this.user = null;
        this.ready = true;
      }
    },
  },
});
