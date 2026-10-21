import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from './stores/auth';
import Login from './pages/Login.vue';
import Dashboard from './pages/Dashboard.vue';
import Leads from './pages/Leads.vue';
import Leads from './pages/Leads.vue';

export const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', redirect: '/dashboard' },
    { path: '/login', component: Login, meta: { guest: true } },
    { path: '/dashboard', component: Dashboard, meta: { auth: true } },
    { path: '/leads', component: Leads, meta: { auth: true } },
    { path: '/:pathMatch(.*)*', redirect: '/dashboard' },
  ],
});

router.beforeEach(async to => {
  const auth = useAuthStore();
  await auth.load();
  if (to.meta.auth && !auth.user) return { path: '/login', query: { redirect: to.fullPath } };
  if (to.meta.guest && auth.user) return '/dashboard';
});
