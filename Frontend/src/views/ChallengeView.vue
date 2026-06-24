<script setup>
import { onMounted, ref } from 'vue'
import { getChallenges, joinChallenge } from '@/services/api'
const challenges = ref([])
const loading = ref(true)
const error = ref('')

onMounted(async () => {
  await loadChanllenges()
})

async function loadChanllenges() {
  try {
    challenges.value = await getChallenges()
  } catch (err) {
    error.value = err.message
  } finally {
    loading.value = false
  }
}

async function handleJoin(id) {
  try {
    await joinChallenge(id)
    await loadChanllenges()
  } catch (err) {
    error.value = err.message
  }
}
</script>

<template>
  <main class="mobile-page">
    <header class="mobile-header">
      <div>
        <h2 style="margin: 0">Challenges</h2>
        <p class="subtitle">Join community sustainability goals.</p>
      </div>
    </header>

    <p v-if="loading" Loading challenges...></p>
    <p v-if="error" style="color: red">{{ error }}</p>

    <section v-for="challenge in challenges" :key="challenge.id" class="card">
      <h3>{{ challenge.title || challenge.name }}</h3>
      <p>{{ challenge.description }}</p>

      <div class="progress-bar">
        <div class="progress-fill" :style="{ width: challenge.progress_pct || 0 + '%' }"></div>
      </div>

      <p class="success-text">{{ challenge.progress_pct || 0 }}% completed</p>
      <p>{{ challenges.member_count || 0 }} participants</p>
      <p>{{ challenges.days_left || 0 }} day left</p>
      <P>{{ challenges.collective_saved_kg || 0 }}kg CO2 saved by group</P>

      <button
        class="btn"
        :class="{ 'secondary-btn': challenge.is_joined }"
        :disabled="challenge.is_joined"
        @click="handleJoin(challenge.id)"
      >
        {{ challenge.is_joined ? 'Joined √' : 'Join Challenge' }}
      </button>
    </section>

    <nav class="bottom-nav">
      <RouterLink to="/dashboard">Home</RouterLink>
      <RouterLink to="/log">Log</RouterLink>
      <RouterLink to="/challenges">Challenges</RouterLink>
      <RouterLink to="/badges">Badges</RouterLink>
    </nav>
  </main>
</template>
