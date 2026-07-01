<template>
  <main class="admin-page">
    <div class="admin-layout">
      <AdminSidebar />
      
      <section class="admin-main">
        <div class="admin-top">
          <div>
            <h1 style="margin: 0; color: #1f7a36;">Eco Challenges Console</h1>
            <p class="subtitle">Deploy community tasks and incentivize sustainability goals.</p>
          </div>
        </div>

        <p v-if="feedbackMsg" :style="{ padding: '12px', borderRadius: '10px', marginTop: '16px', fontWeight: 'bold', backgroundColor: isError ? '#fde8e8' : '#eafaf1', color: isError ? '#c62828' : '#2f8f46' }">
          {{ feedbackMsg }}
        </p>

        <div class="admin-content-grid" style="margin-top: 18px;">
          
          <section class="admin-panel">
            <h2 style="margin-top: 0; color: #1f7a36;">Launch New Challenge</h2>
            <form @submit.prevent="handleCreateChallenge">
              <div style="margin-bottom: 12px;">
                <label class="label">Challenge Title</label>
                <input v-model="form.title" type="text" placeholder="e.g., Carpool Champion" required class="input" style="margin-bottom: 0;" />
              </div>

              <div style="margin-bottom: 12px;">
                <label class="label">Objective Requirements Description</label>
                <textarea v-model="form.description" rows="3" placeholder="Describe the sustainability target clearly..." required class="input" style="margin-bottom: 0; font-family: inherit; resize: vertical; min-height: 70px;"></textarea>
              </div>

              <div style="display: flex; gap: 12px; margin-bottom: 16px;">
                <div style="flex: 1;">
                  <label class="label">Target Points</label>
                  <input v-model.number="form.points" type="number" min="1" required class="input" style="margin-bottom: 0;" />
                </div>
                <div style="flex: 1;">
                  <label class="label">Day Duration</label>
                  <input v-model.number="form.days" type="number" min="1" placeholder="e.g., 7" required class="input" style="margin-bottom: 0;" />
                </div>
              </div>

              <button type="submit" :disabled="submitting" class="btn" style="width: auto; padding: 12px 28px;">
                {{ submitting ? 'Deploying...' : 'Deploy Challenge' }}
              </button>
            </form>
          </section>

          <section class="admin-panel">
            <h2 style="margin-top: 0; color: #1f7a36;">Platform Stats</h2>
            <div style="margin-top: 16px;">
              <p style="margin: 8px 0; font-size: 14px; color: #6d7a6b;">Active Schemes:</p>
              <div style="font-size: 28px; font-weight: 800; color: #1f2a1f;">{{ challenges.length }}</div>
            </div>
            <div style="margin-top: 16px;">
              <p style="margin: 8px 0; font-size: 14px; color: #6d7a6b;">Reward Integrity:</p>
              <span class="success-text" style="font-size: 14px;">✓ Live Accruals Syncing</span>
            </div>
          </section>
        </div>

        <section class="admin-panel" style="margin-top: 18px;">
          <h2 style="margin-top: 0; color: #1f7a36;">Active System Challenges Registry</h2>
          
          <div v-if="loading" style="padding: 24px; text-align: center; color: #6d7a6b;">
            Fetching global challenges ledger...
          </div>

          <table v-else class="admin-table">
            <thead>
              <tr>
                <th>ID Reference</th>
                <th>Challenge Title / Goal</th>
                <th>Target Rewards</th>
                <th>Timeframe Window</th>
                <th style="text-align: right;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in challenges" :key="item.id">
                <td><code style="background: #eef5ee; padding: 4px 8px; border-radius: 6px;">#{{ item.id }}</code></td>
                <td>
                  <strong>{{ item.title }}</strong>
                  <div style="font-size: 12px; color: #6d7a6b; margin-top: 4px;">{{ item.description }}</div>
                </td>
                <td><span class="success-text">+{{ item.points }} pts</span></td>
                <td>{{ item.days || 7 }} Days</td>
                <td style="text-align: right;">
                  <button 
                    @click="handleDeleteChallenge(item.id)" 
                    :disabled="deletingId === item.id"
                    style="background: transparent; border: none; color: #c62828; font-weight: bold; cursor: pointer; font-size: 13px;"
                  >
                    {{ deletingId === item.id ? 'Removing...' : 'Delete' }}
                  </button>
                </td>
              </tr>
              <tr v-if="challenges.length === 0">
                <td colspan="5" style="text-align: center; color: #6d7a6b; padding: 24px;">
                  No active custom tasks deployed. Use the configuration form above to spin up a new challenge.
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
import { getChallenges, createChallenge, deleteChallenge } from '@/services/api'
import AdminSidebar from '@/components/AdminSidebar.vue'

const challenges = ref([])
const loading = ref(true)
const submitting = ref(false)
const deletingId = ref(null)

const feedbackMsg = ref('')
const isError = ref(false)

const form = ref({
  title: '',
  description: '',
  points: 50,
  days: 7
})

async function fetchChallenges() {
  loading.value = true
  try {
    challenges.value = await getChallenges()
  } catch (err) {
    console.error('Challenge population error:', err)
  } finally {
    loading.value = false
  }
}

async function handleCreateChallenge() {
  submitting.value = true
  feedbackMsg.value = ''
  isError.value = false

  try {
    await createChallenge({
      title: form.value.title.trim(),
      description: form.value.description.trim(),
      points: parseInt(form.value.points),
      days: parseInt(form.value.days)
    })
    
    feedbackMsg.value = 'Success: Task deployed directly to client challenge arrays.'
    form.value = { title: '', description: '', points: 50, days: 7 }
    await fetchChallenges()
  } catch (err) {
    isError.value = true
    feedbackMsg.value = `Deployment Interrupted: ${err.message}`
  } finally {
    submitting.value = false
  }
}

async function handleDeleteChallenge(id) {
  if (!confirm('Are you certain you want to scrap this challenge item from the platform database?')) return
  
  deletingId.value = id
  feedbackMsg.value = ''
  isError.value = false

  try {
    await deleteChallenge(id)
    feedbackMsg.value = 'Success: Challenge decommissioned cleanly.'
    await fetchChallenges()
  } catch (err) {
    isError.value = true
    feedbackMsg.value = `Teardown Error: ${err.message}`
  } finally {
    deletingId.value = null
  }
}

onMounted(fetchChallenges)
</script>