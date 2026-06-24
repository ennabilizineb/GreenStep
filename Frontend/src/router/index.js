import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '../views/LoginView.vue'
import DashboardView from '../views/DashboardView.vue'
import DailyLogView from '../views/DailyLogView.vue'
import ChallengeView from '../views/ChallengeView.vue'
import AdminDashboardView from '../views/AdminDashboardView.vue'
import RegisterView from '../views/RegisterView.vue'
import BadgesView from '../views/BadgesView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: '/', redirect: '/login' },
    { path: '/login', component: LoginView },
    { path: '/dashboard', component: DashboardView },
    { path: '/log', component: DailyLogView },
    { path: '/challenges', component: ChallengeView },
    { path: '/admin', component: AdminDashboardView },
    { path: '/register', component: RegisterView },
    { path: '/badges', component: BadgesView },
  ],
})

export default router
