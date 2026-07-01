import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '../views/LoginView.vue'
import DashboardView from '../views/DashboardView.vue'
import DailyLogView from '../views/DailyLogView.vue'
import ChallengeView from '../views/ChallengeView.vue'
import AdminDashboardView from '../views/AdminDashboardView.vue'
import RegisterView from '../views/RegisterView.vue'
import BadgesView from '../views/BadgesView.vue'
import { useAuthStore } from '@/stores/auth'

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

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()
  const isLoggedIn = !!authStore.token
  const isAdmin = authStore.user?.role === 'admin'

  const protectedPaths = ['/dashboard', '/admin', '/log', '/challenges', '/badges']

  // Block logged-out users from any protected page
  if (protectedPaths.includes(to.path) && !isLoggedIn) {
    return next('/login')
  }

  // Block non-admins from the admin dashboard
  if (to.path === '/admin' && !isAdmin) {
    return next('/dashboard')
  }

  next()
})

export default router
