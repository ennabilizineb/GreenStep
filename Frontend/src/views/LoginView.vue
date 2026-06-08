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
      <input class="input" type="email" placeholder="you@example.com" />

      <label class="label">Password</label>
      <input class="input" type="password" placeholder="Enter your password" />

      <button class="btn" @click="$router.push('/dashboard')">Login</button>

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