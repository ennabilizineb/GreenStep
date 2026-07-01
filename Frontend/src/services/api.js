const API_BASE_URL = 'http://localhost:8080'
function getToken() {
  return localStorage.getItem('token')
}
async function request(endpoint, options = {}) {
  const token = getToken()

  const response = await fetch(`${API_BASE_URL}${endpoint}`, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...options.headers,
    },
  })

  if (response.status === 401) {
    localStorage.clear()
    window.location.href = '/login'
    return
  }

  const text = await response.text()
  const result = text ? JSON.parse(text) : {}

  //const result =  await response.json()

  if (!response.ok || result.success == false) {
    throw new Error(result.error?.message || 'API request failed')
  }
  return result.data
}

export async function login(email, password) {
  return request('/api/auth/login', {
    method: 'POST',
    body: JSON.stringify({ email, password }),
  })
}

export async function register(name, email, password) {
  return request('/api/auth/register', {
    method: 'POST',
    body: JSON.stringify({ name, email, password }),
  })
}

export async function getDashboard() {
  return request('/api/dashboard', { method: 'GET' })
}

export async function getActivityTypes() {
  return request('/api/activity-types', { method: 'GET' })
}

export async function getLogs() {
  return request('/api/logs', { method: 'GET' })
}

export async function createLog(payload) {
  return request('/api/logs', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export async function getChallenges() {
  return request('/api/challenges', { method: 'GET' })
}

export async function joinChallenge(id) {
  return request(`/api/challenges/${id}/join`, {
    method: 'POST',
  })
}

export async function createChallenge(payload) {
  return request('/api/admin/challenges', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export async function updateChallenge(id, payload) {
  return request(`/api/admin/challenges/${id}`, {
    method: 'PUT',
    body: JSON.stringify(payload),
  })
}

export async function deleteChallenge(id) {
  return request(`/api/admin/challenges/${id}`, {
    method: 'DELETE',
  })
}

export async function getBadges() {
  return request('/api/badges', { method: 'GET' })
}

export async function createBadge(payload) {
  return request('/api/admin/badges', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export async function deleteBadge(id) {
  return request(`/api/admin/badges/${id}`, { method: 'DELETE' })
}

export async function getFactors() {
  return request('/api/admin/factors', { method: 'GET' })
}

export async function updateFactor(id, payload) {
  return request(`/api/admin/factors/${id}`, {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export async function createTip(payload) {
  return request('/api/admin/tips', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export async function getAdminStats() {
  return request('/api/admin/stats')
}