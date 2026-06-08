<template>
  <main class="mobile-page">
    <header class="mobile-header">
      <div>
        <h2 style="margin: 0">Hello, Lucy! 👋</h2>
        <p class="subtitle">Let’s make today greener.</p>
      </div>
      <div>🔔</div>
    </header>

    <section class="card green-card">
      <p>Today’s Carbon Footprint</p>
      <div class="stat-value">12.4 kg CO₂</div>
      <p class="success-text">↓ 15% vs yesterday</p>
    </section>

    <section class="card">
      <h3>Weekly Summary</h3>
      <div class="chart-bars">
        <div
          v-for="item in weeklyBars"
          :key="item.day"
          class="bar"
          :style="{ height: item.height + 'px' }"
          :title="item.day"
        ></div>
      </div>
      <div style="display: flex; justify-content: space-between; font-size: 12px; color: #71806e">
        <span v-for="item in weeklyBars" :key="item.day + '-label'">{{ item.day }}</span>
      </div>
    </section>

    <section class="card">
      <h3>Eco Tip 🌿</h3>
      <p>Use public transport instead of driving to reduce your daily carbon footprint.</p>
    </section>

    <section class="card">
      <h3>Your Progress</h3>
      <p>🔥 5-day streak</p>
      <p>🏅 Eco Beginner Badge</p>
      <p>🌱 Joined 3 challenges</p>
    </section>

    <nav class="bottom-nav">
      <RouterLink to="/dashboard">Home</RouterLink>
      <RouterLink to="/log">Log</RouterLink>
      <RouterLink to="/challenges">Challenges</RouterLink>
      <RouterLink to="/admin">Admin</RouterLink>
    </nav>
  </main>
</template>

<script setup>
const weeklyBars = [
  { day: 'Mon', height: 65 },
  { day: 'Tue', height: 92 },
  { day: 'Wed', height: 78 },
  { day: 'Thu', height: 110 },
  { day: 'Fri', height: 85 },
  { day: 'Sat', height: 58 },
  { day: 'Sun', height: 72 },
]
</script>