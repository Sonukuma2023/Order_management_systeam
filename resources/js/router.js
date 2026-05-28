import { createRouter, createWebHistory } from 'vue-router';

import LoginComponent from './components/LoginComponent.vue';
import Dashboard from './components/Dashboard.vue';

const routes = [
    { path: '/login', component: LoginComponent },
    { path: '/dashboard', component: Dashboard },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;