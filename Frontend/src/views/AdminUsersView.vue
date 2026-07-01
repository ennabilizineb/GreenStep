<template>
  <main class="admin-page">
    <div class="admin-layout">
      <AdminSidebar />
      <section class="admin-main">
        <div class="admin-top">
          <div>
            <h1 style="margin: 0; color: #1f7a36;">User Matrix Registry</h1>
            <p class="subtitle">Review operational clearance flags and active user identities.</p>
          </div>
        </div>

        <section class="admin-panel" style="margin-top: 22px;">
          <h2 style="margin-top: 0; color: #1f7a36;">Provisioned Accounts Matrix</h2>
          <table class="admin-table">
            <thead>
              <tr>
                <th>Identity ID</th>
                <th>Full Name</th>
                <th>Authorized Communication Node</th>
                <th>Access Level Assignment</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in systemUsers" :key="user.id">
                <td><code style="background: #eef5ee; padding: 4px 8px; border-radius: 6px;">#0{{ user.id }}</code></td>
                <td><strong>{{ user.name }}</strong></td>
                <td>{{ user.email }}</td>
                <td>
                  <span :style="{
                    padding: '4px 10px',
                    borderRadius: '8px',
                    fontSize: '12px',
                    fontWeight: 'bold',
                    backgroundColor: user.role === 'admin' ? '#fde8e8' : (user.role === 'leader' ? '#fff4e5' : '#e8f7ea'),
                    color: user.role === 'admin' ? '#c62828' : (user.role === 'leader' ? '#b76e00' : '#2f8f46')
                  }">
                    {{ user.role.toUpperCase() }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </section>
      </section>
    </div>
  </main>
</template>

<script setup>
import { ref } from 'vue'
import AdminSidebar from '@/components/AdminSidebar.vue'

// Mirrors seed data parameters cleanly
const systemUsers = ref([
  { id: 1, name: 'System Administrator', email: 'admin@greenstep.com', role: 'admin' },
  { id: 2, name: 'System Administrator 2', email: 'admin2@greenstep.com', role: 'admin' },
  { id: 3, name: 'System Leader', email: 'leader@greenstep.com', role: 'leader' },
  { id: 4, name: 'System Member', email: 'member@greenstep.com', role: 'user' }
])
</script>