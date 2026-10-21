import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from './stores/auth';
import Login from './pages/Login.vue';
import Dashboard from './pages/Dashboard.vue';

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', redirect: '/dashboard' },
    { path: '/login', name: 'login', component: Login, meta: { guest: true } },
    { path: '/dashboard', name: 'dashboard', component: Dashboard, meta: { auth: true } },
    { path: '/:pathMatch(.*)*', redirect: '/dashboard' },
  ],
});

router.beforeEach(async (to) => {
  const auth = useAuthStore();
  await auth.load();
  if (to.meta.auth && !auth.authenticated) return { name: 'login', query: { redirect: to.fullPath } };
  if (to.meta.guest && auth.authenticated) return { name: 'dashboard' };
});

export default router;
