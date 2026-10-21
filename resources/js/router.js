import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from './stores/auth';
import Login from './pages/Login.vue';
import PasswordRecovery from './pages/PasswordRecovery.vue';
import Dashboard from './pages/Dashboard.vue';
import Leads from './pages/Leads.vue';
import Customers from './pages/Customers.vue';
import CustomerDetail from './pages/CustomerDetail.vue';
import Tasks from './pages/Tasks.vue';

export const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', redirect: '/dashboard' },
    { path: '/login', component: Login, meta: { guest: true } },
    { path: '/forgot-password', component: PasswordRecovery, meta: { guest: true } },
    { path: '/reset-password/:token', component: PasswordRecovery, meta: { guest: true } },
    { path: '/dashboard', component: Dashboard, meta: { auth: true } },
    { path: '/leads', component: Leads, meta: { auth: true } },
    { path: '/customers', component: Customers, meta: { auth: true } },
    { path: '/customers/:id', component: CustomerDetail, meta: { auth: true } },
    { path: '/tasks', component: Tasks, meta: { auth: true } },
    { path: '/:pathMatch(.*)*', redirect: '/dashboard' },
  ],
});

router.beforeEach(async to => {
  const auth = useAuthStore();
  await auth.load();
  if (to.meta.auth && !auth.user) return { path: '/login', query: { redirect: to.fullPath } };
  if (to.meta.guest && auth.user) return '/dashboard';
});
