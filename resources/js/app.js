import { createApp } from 'vue';
import { createPinia } from 'pinia';
import { createRouter, createWebHistory } from 'vue-router';
import App from './App.vue';
import Login from './pages/Login.vue';
import Dashboard from './pages/Dashboard.vue';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', redirect: '/dashboard' },
        { path: '/login', component: Login, meta: { guest: true } },
        { path: '/dashboard', component: Dashboard, meta: { auth: true } },
        { path: '/:pathMatch(.*)*', redirect: '/dashboard' },
    ],
});
const pinia = createPinia();

createApp(App).use(pinia).use(router).mount('#app');
