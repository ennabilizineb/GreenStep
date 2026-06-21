<script setup>
import { computed,onMounted, ref } from 'vue'
import { createLog,getActivityTypes } from '@/services/api';

const activityTypes=ref ([])
const selectedTransport = ref('Bus')
const transportDistance= ref(10)
const selectedMeal = ref('Mixed')
const mealAmount =ref(1)
const electricityAmount= ref(5)
const recyclingAmount=ref(3)

const loggedOn= ref(new Date().toISOString().slice(0,10))

const loading=ref(true)
const saving=ref(false)
const error=ref('')
const successMessage=ref('')

const transportOptions = [
  { label: 'Walk', icon: '🚶' },
  { label: 'Bike', icon: '🚲' },
  { label: 'Bus', icon: '🚌' },
  { label: 'Car', icon: '🚗' },
]

const mealOptions = [
  { label: 'Vegetarian', icon: '🥦' },
  { label: 'Mixed', icon: '🍱' },
  { label: 'Red Meat', icon: '🥩' },
]

function findActivityType(keyword, categoryKeyword=''){
  return activityTypes.value.find((activity) => {
    const name=String(activity.activity_name ||'').toLowerCase()
    const category=String(activity.category || '').toLowerCase()

    return(
      name.includes(keyword.toLowerCase()) &&
      (!categoryKeyword || category.includes(categoryKeyword.toLowerCase()))
  )
  })
}

const selectedTransportActivity = computed(() => {
  return findActivityType(selectedTransport.value, 'transport')
})

const selectedMealActivity = computed(() => {
  return findActivityType(selectedMeal.value, 'meal')
})

const electricityActivity = computed(() => {
  return findActivityType('electricity')
})

const recyclingActivity = computed(() => {
  return findActivityType('recycling')
})

onMounted(async () =>{
  try {
    activityTypes.value = await getActivityTypes()
  }catch(err){
    error.value=err.message
  }finally{
    loading.value=false
  }
})

async function submitOneLog(activityType,amount) {
  if (!activityType || !amount || Number(amount) <= 0){
    return
  }
  
  await createLog({
    activity_type_id:activityType.id,
    amount: Number(amount),
    logged_on: loggedOn.value,
  })
}

async function submitLog() {
  error.value = ''
  successMessage.value = ''
  saving.value = true

  try {
    await submitOneLog(selectedTransportActivity.value, transportDistance.value)
    await submitOneLog(selectedMealActivity.value, mealAmount.value)
    await submitOneLog(electricityActivity.value, electricityAmount.value)
    await submitOneLog(recyclingActivity.value, recyclingAmount.value)

    successMessage.value='Activity log saved successfully.'
  }catch(err){
    error.value=err.message
  }finally{
    saving.value=false
  }
}

</script>

<template>
  <main class="mobile-page">
    <header class="mobile-header">
      <div>
        <h2 style="margin: 0">Daily Activity Log</h2>
        <p class="subtitle">Record today's eco activities.</p>
      </div>
    </header>
    
    <p v-if="loading">Loading activity types...</p>
    <p v-if="error" style="color:red">{{ error }}</p>

    <section class="card">
      <h3>Transport</h3>
      <div class="option-grid">
        <div
        v-for="option in transportOptions"
        :key="option.label"
        class="activity-option"
        :class="{ active: selectedTransport===option.label}"
        @click="selectedTransport=option.label"
        >
        <div class="icon-bubble">{{ option.icon }}</div>
        <span>{{ option.label }}</span>
      </div>
    </div>

      <label class="label">Distance (km)</label>
      <input v-model="transportDistance" class="input" type="number" />
    </section>

    <section class="card">
      <h3>Meal Type</h3>

      <div class="option-grid">
        <div
        v-for="option in mealOptions"
        :key="option.label"
        class="activity-option"
        :class="{active: selectedMeal === option.label}"
        @click="selectedMeal = option.label"
        >
        <div class="icon-bubble">{{ option.icon }}</div>
        <span>{{ option.label }}</span>
        </div>
      </div>
      <label class="label">Meal Amount</label>
      <input v-model="mealAmount" class="input" type="number" />
    </section>

    <section class="card">
      <h3>Electricity Usage</h3>
      <label class="label">Electricity Used (KWh)</label>
      <input v-model="electricityAmount" class="input" type="number" />
    </section>

    <section class="card">
      <h3>Recycling</h3>
      <label class="label">Recycled Items</label> 
      <input v-model="recyclingAmount" class="input" type="number" />
    </section>

    <section class="card">
      <h3>Date</h3>
      <input v-model="loggedOn" class="input" type="date" />
    </section>

    <button class="btn" @click="submitLog">
      {{saving ? 'Saving...' :'Calculate & Save'}}
    </button>

    <section v-if="successMessage" class="card green-card" style="margin-top: 16px">
      <h3>{{ successMessage }}</h3>
      <p>Your activity records have been submitted to the backend.</p>
    </section>

    <nav class="bottom-nav">
      <RouterLink to="/dashboard">Home</RouterLink>
      <RouterLink to="/log">Log</RouterLink>
      <RouterLink to="/challenges">Challenges</RouterLink>
      <RouterLink to="/badges">Badges</RouterLink>
    </nav>
  </main>
</template>