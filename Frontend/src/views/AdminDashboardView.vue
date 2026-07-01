<template>
  <main class="admin-page">
    <div class="admin-layout">
      <AdminSidebar />

      <section class="admin-main">
        <div class="admin-top">
          <div>
            <h1 style="margin: 0">Dashboard Overview</h1>
            <p class="subtitle">Monitor GreenStep platform activities.</p>
          </div>
          <input class="admin-search" placeholder="Search..." />
        </div>

        <div class="admin-grid">
          <div class="admin-card">
            <p>Total Users</p>
            <h2>1,042</h2>
            <p class="success-text">↑ 12.5%</p>
          </div>

          <div class="admin-card">
            <p>Total Logs</p>
            <h2>6,241</h2>
            <p class="success-text">↑ 18.7%</p>
          </div>

          <div class="admin-card">
            <p>Challenges</p>
            <h2>31</h2>
            <p class="success-text">7 active</p>
          </div>

          <div class="admin-card">
            <p>Badges Issued</p>
            <h2>2,651</h2>
            <p class="success-text">↑ 15.3%</p>
          </div>
        </div>

        <div class="admin-content-grid">
          <section class="admin-panel">
            <h2>Carbon Footprint Saved</h2>
            <div class="chart-bars" style="height: 240px">
              <div
                v-for="item in chartBars"
                :key="item"
                class="bar"
                :style="{ height: item + 'px' }"
              ></div>
            </div>
          </section>

          <section class="admin-panel">
            <h2>Recent Activities</h2>
            <p>👤 New user registered</p>
            <p>🏆 Challenge created</p>
            <p>🌿 Eco tip added</p>
            <p>⚙️ Emission factor updated</p>
          </section>
        </div>

        <section class="admin-panel" style="margin-top: 18px">
          <h2>Top Users by CO₂ Saved</h2>
          <table class="admin-table">
            <thead>
              <tr>
                <th>Rank</th>
                <th>User</th>
                <th>CO₂ Saved</th>
                <th>Badges</th>
              </tr>
            </thead>

            <tbody>
              <tr>
                <td>1</td>
                <td>alex.green@example.com</td>
                <td>125.4 kg</td>
                <td>🌱 🏆</td>
              </tr>
              <tr>
                <td>2</td>
                <td>sarah.wong@example.com</td>
                <td>98.7 kg</td>
                <td>🌿 ♻️</td>
              </tr>
              <tr>
                <td>3</td>
                <td>john.doe@example.com</td>
                <td>87.2 kg</td>
                <td>🌱</td>
              </tr>
            </tbody>
          </table>
        </section>
      </section>
    </div>
  </main>
</template>

<script setup>
import AdminSidebar from '@/components/AdminSidebar.vue'

const chartBars = [80, 110, 90, 145, 180, 130, 165]
</script>
