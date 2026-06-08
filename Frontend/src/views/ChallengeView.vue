<template>
  <main class="mobile-page">
    <header class="mobile-header">
      <div>
        <h2 style="margin: 0">Challenges</h2>
        <p class="subtitle">Join community sustainability goals.</p>
      </div>
    </header>

    <section v-for="challenge in challenges" :key="challenge.title" class="card">
      <h3>{{ challenge.title }}</h3>
      <p>{{ challenge.description }}</p>

      <div class="progress-bar">
        <div class="progress-fill" :style="{ width: challenge.progress + '%' }"></div>
      </div>

      <p class="success-text">{{ challenge.progress }}% completed</p>
      <button class="btn secondary-btn">View Details</button>
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
const challenges = [
  {
    title: 'Reduce 20% CO₂ This Week',
    description: 'Log your daily activities and reduce emissions compared to last week.',
    progress: 70,
  },
  {
    title: 'No Plastic Week',
    description: 'Avoid single-use plastic items for a whole week.',
    progress: 40,
  },
  {
    title: 'Energy Saver Challenge',
    description: 'Save electricity at home by switching off unused devices.',
    progress: 55,
  },
]
</script>