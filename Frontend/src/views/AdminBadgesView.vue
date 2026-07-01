<template>
  <main class="admin-page">
    <div class="admin-layout">
      <AdminSidebar />
      
      <section class="admin-main">
        <div class="admin-top">
          <div>
            <h1 style="margin: 0; color: #1f7a36;">Gamification Badges Console</h1>
            <p class="subtitle">Design milestone credentials and reward user participation metrics.</p>
          </div>
        </div>

        <p v-if="feedbackMsg" :style="{ padding: '12px', borderRadius: '10px', marginTop: '16px', fontWeight: 'bold', backgroundColor: isError ? '#fde8e8' : '#eafaf1', color: isError ? '#c62828' : '#2f8f46' }">
          {{ feedbackMsg }}
        </p>

        <div class="admin-content-grid" style="margin-top: 18px;">
          
          <section class="admin-panel">
            <h2 style="margin-top: 0; color: #1f7a36;">Mint New Platform Badge</h2>
            <form @submit.prevent="handleCreateBadge">
              <div style="margin-bottom: 12px;">
                <label class="label">Badge Name</label>
                <input v-model="form.name" type="text" placeholder="e.g., Energy Saver Master" required class="input" style="margin-bottom: 0;" />
              </div>

              <div style="margin-bottom: 12px;">
                <label class="label">Accomplishment Criteria / Requirement Description</label>
                <input v-model="form.description" type="text" placeholder="e.g., Logged 10 energy-saving activities" required class="input" style="margin-bottom: 0;" />
              </div>

              <div style="display: flex; gap: 12px; margin-bottom: 16px;">
                <div style="flex: 1;">
                  <label class="label">Visual Glyph Symbol</label>
                  <select v-model="form.icon" required class="input" style="margin-bottom: 0; height: 48px;">
                    <option value="🌱">🌱 Sprout</option>
                    <option value="🌿">🌿 Branch</option>
                    <option value="🏆">🏆 Trophy</option>
                    <option value="♻️">♻️ Recycle</option>
                    <option value="⚡">⚡ Bolt</option>
                    <option value="🚗">🚗 Electric Vehicle</option>
                    <option value="🚲">🚲 Bicycle</option>
                    <option value="💧">💧 Water Droplet</option>
                  </select>
                </div>
                <div style="flex: 1;">
                  <label class="label">Required Logs Count</label>
                  <input v-model.number="form.rule_logs_count" type="number" min="1" placeholder="e.g., 5" required class="input" style="margin-bottom: 0;" />
                </div>
              </div>

              <button type="submit" :disabled="submitting" class="btn" style="width: auto; padding: 12px 28px;">
                {{ submitting ? 'Minting Badge...' : 'Deploy Badge Asset' }}
              </button>
            </form>
          </section>

          <section class="admin-panel">
            <h2 style="margin-top: 0; color: #1f7a36;">Gamification Scope</h2>
            <div style="margin-top: 16px;">
              <p style="margin: 8px 0; font-size: 14px; color: #6d7a6b;">Total Badges Loaded:</p>
              <div style="font-size: 28px; font-weight: 800; color: #1f2a1f;">{{ badges.length }}</div>
            </div>
            <div style="margin-top: 16px;">
              <p style="margin: 8px 0; font-size: 14px; color: #6d7a6b;">Automation Status:</p>
              <span class="success-text" style="font-size: 14px;">✓ Trigger Rules Active</span>
            </div>
          </section>
        </div>

        <section class="admin-panel" style="margin-top: 18px;">
          <h2 style="margin-top: 0; color: #1f7a36;">Active System Badges Registry</h2>
          
          <div v-if="loading" style="padding: 24px; text-align: center; color: #6d7a6b;">
            Fetching system reward structures...
          </div>

          <table v-else class="admin-table">
            <thead>
              <tr>
                <th>Glyph</th>
                <th>Credential Name / Criteria</th>
                <th>Rule Multiplier Target</th>
                <th style="text-align: right;">System Operations</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="badge in badges" :key="badge.id">
                <td style="font-size: 24px; width: 60px; text-align: center;">{{ badge.icon || '🏅' }}</td>
                <td>
                  <strong>{{ badge.name }}</strong>
                  <div style="font-size: 12px; color: #6d7a6b; margin-top: 4px;">{{ badge.description }}</div>
                </td>
                <td>
                  <span class="success-text">Requires {{ badge.rule_logs_count || 1 }} Log Entry</span>
                </td>
                <td style="text-align: right;">
                  <button 
                    @click="handleDeleteBadge(badge.id)" 
                    :disabled="deletingId === badge.id"
                    style="background: transparent; border: none; color: #c62828; font-weight: bold; cursor: pointer; font-size: 13px;"
                  >
                    {{ deletingId === badge.id ? 'Dropping...' : 'Remove' }}
                  </button>
                </td>
              </tr>
              <tr v-if="badges.length === 0">
                <td colspan="4" style="text-align: center; color: #6d7a6b; padding: 24px;">
                  No achievement tier modules configured yet. Use the tool configuration form above to register an asset.
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
import { ref, onMounted } from 'vue'
import { getBadges, createBadge, deleteBadge } from '@/services/api'
import AdminSidebar from '@/components/AdminSidebar.vue'

const badges = ref([])
const loading = ref(true)
const submitting = ref(false)
const deletingId = ref(null)

const feedbackMsg = ref('')
const isError = ref(false)

const form = ref({
  name: '',
  description: '',
  icon: '🌱',
  rule_logs_count: 5
})

async function fetchBadges() {
  loading.value = true
  try {
    badges.value = await getBadges()
  } catch (err) {
    console.error('Badge data synchronization failure:', err)
  } finally {
    loading.value = false
  }
}

async function handleCreateBadge() {
  submitting.value = true
  feedbackMsg.value = ''
  isError.value = false

  try {
    await createBadge({
      name: form.value.name.trim(),
      description: form.value.description.trim(),
      icon: form.value.icon,
      rule_logs_count: parseInt(form.value.rule_logs_count)
    })
    
    feedbackMsg.value = 'Success: Automated achievement tier deployed cleanly.'
    form.value = { name: '', description: '', icon: '🌱', rule_logs_count: 5 }
    await fetchBadges()
  } catch (err) {
    isError.value = true
    feedbackMsg.value = `Asset Registration Fault: ${err.message}`
  } finally {
    submitting.value = false
  }
}

async function handleDeleteBadge(id) {
  if (!confirm('Are you certain you want to purge this badge milestone? Active users will lose this item.')) return
  
  deletingId.value = id
  feedbackMsg.value = ''
  isError.value = false

  try {
    await deleteBadge(id)
    feedbackMsg.value = 'Success: Badge configuration wiped out.'
    await fetchBadges()
  } catch (err) {
    isError.value = true
    feedbackMsg.value = `Deletion Interrupted: ${err.message}`
  } finally {
    deletingId.value = null
  }
}

onMounted(fetchBadges)
</script>