<script setup>
import { onMounted, ref } from 'vue'
import { getDashboard } from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const dashboard = ref(null)
const error = ref('')
const loading = ref(true)

onMounted(async () => {
  try {
    dashboard.value = await getDashboard()
  } catch (err) {
    error.value = err.message
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <main class="mobile-page">
    <header class="mobile-header">
      <div>
        <h2 style="margin: 0">Hello, {{ authStore.user?.name }}! 👋</h2>
        <p class="subtitle">Let’s make today greener.</p>
      </div>
      <div>🔔</div>
    </header>

    <p v-if="loading">Loading dashboard...</p>
    <p v-if="error" style="color: red">{{ error }}</p>

    <template v-if="dashboard">
      <section class="card green-card">
        <p>Today’s Carbon Footprint</p>
        <div class="stat-value">{{ dashboard.today_kg_co2 }} kg CO2</div>
        <p class="success-text">Yesterday: {{ dashboard.yesterday_kg_co2 }} kg CO2</p>
      </section>

      <section class="card">
        <h3>Weekly Summary</h3>
        <div class="chart-bars">
          <div
            v-for="item in dashboard.week || []"
            :key="item.date || item.day"
            class="bar"
            :style="{ height: Math.max(20, item.kg_co2 * 8) + 'px' }"
          ></div>
        </div>
      </section>

      <section class="card">
        <h3>Your Progress</h3>
        <p>🔥 {{ dashboard.streak_days }}-day streak</p>
        <p>🌱Joined {{ dashboard.joined_challenges }} challenges</p>
      </section>

      <section class="card">
        <h3>Category Breakdown</h3>
        <p v-for="item in dashboard.by_category">{{ item.category }}:{{ item.kg_co2 }}kg CO2</p>
      </section>
    </template>

    <nav class="bottom-nav">
      <RouterLink to="/dashboard">Home</RouterLink>
      <RouterLink to="/log">Log</RouterLink>
      <RouterLink to="/challenges">Challenges</RouterLink>
      <RouterLink to="/badges">Badges</RouterLink>
    </nav>
  </main>
</template>
