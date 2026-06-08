<template>
  <main class="mobile-page">
    <section style="text-align: center; margin-top: 40px; margin-bottom: 24px">
      <h1 class="brand-title">🌱 GreenStep</h1>
      <p class="subtitle">Create your account</p>
    </section>

    <section class="card">
      <h2>Sign Up</h2>

      <label class="label">Full Name</label>
      <input class="input" type="text" placeholder="Your name" />

      <label class="label">Email</label>
      <input class="input" type="email" placeholder="you@example.com" />

      <label class="label">Password</label>
      <input class="input" type="password" placeholder="Create a password" />

      <label class="label">Confirm Password</label>
      <input class="input" type="password" placeholder="Confirm your password" />

      <button class="btn" @click="$router.push('/dashboard')">Register</button>

      <p style="text-align: center; margin-top: 18px">
        Already have an account?
        <RouterLink to="/login" class="success-text">Login</RouterLink>
      </p>
    </section>
  </main>
</template>