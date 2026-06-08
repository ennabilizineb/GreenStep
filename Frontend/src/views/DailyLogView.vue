<template>
  <main class="mobile-page">
    <header class="mobile-header">
      <div>
        <h2 style="margin: 0">Daily Activity Log</h2>
        <p class="subtitle">Record today’s eco activities.</p>
      </div>
    </header>

    <section class="card">
      <h3>Transport</h3>
      <div class="option-grid">
        <div class="option-card">🚶 Walk</div>
        <div class="option-card">🚲 Bike</div>
        <div class="option-card active">🚌 Bus</div>
        <div class="option-card">🚗 Car</div>
      </div>

      <label class="label">Distance (km)</label>
      <input class="input" type="number" value="10" />
    </section>

    <section class="card">
      <h3>Meal Type</h3>
      <div class="option-grid">
        <div class="option-card">🥦 Vegetarian</div>
        <div class="option-card active">🍱 Mixed</div>
        <div class="option-card">🥩 Red Meat</div>
        <div class="option-card">🥤 Drink</div>
      </div>
    </section>

    <section class="card">
      <h3>Electricity Usage</h3>
      <input class="input" type="number" value="5" />
    </section>

    <section class="card">
      <h3>Recycling</h3>
      <input class="input" type="number" value="3" />
    </section>

    <button class="btn">Calculate & Save</button>

    <nav class="bottom-nav">
      <RouterLink to="/dashboard">Home</RouterLink>
      <RouterLink to="/log">Log</RouterLink>
      <RouterLink to="/challenges">Challenges</RouterLink>
      <RouterLink to="/admin">Admin</RouterLink>
    </nav>
  </main>
</template>