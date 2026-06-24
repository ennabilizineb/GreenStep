<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authoStore = useAuthStore()
const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref('')

async function handleLogin() {
  error.value = ''
  loading.value = true

  try {
    await authoStore.loginUser(email.value, password.value)
    router.push('/dashboard')
  } catch (err) {
    error.value = err.message
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <main class="mobile-page">
    <section style="text-align: center; margin-top: 70px; margin-bottom: 30px">
      <h1 class="brand-title">🌱 GreenStep</h1>
      <p class="subtitle">Track. Reduce. Inspire.</p>
    </section>

    <section class="card">
      <h2>Welcome Back</h2>
      <p class="subtitle">Log in to continue your eco journey.</p>

      <label class="label">Email</label>
      <input v-model="email" class="input" type="email" placeholder="you@example.com" />

      <label class="label">Password</label>
      <input v-model="password" class="input" type="password" placeholder="Enter your password" />

      <p v-if="error" style="color: red">{{ error }}</p>

      <button class="btn" @click="handleLogin">
        {{ loading ? 'Logging in...' : 'Login' }}
      </button>

      <p style="text-align: center; margin-top: 18px">
        New user?
        <RouterLink to="/register" class="success-text">Create account</RouterLink>
      </p>
    </section>

    <section class="card green-card">
      <h3>Small steps, measurable impact.</h3>
      <p>Record daily activities and understand your carbon footprint better.</p>
    </section>
  </main>
</template>
