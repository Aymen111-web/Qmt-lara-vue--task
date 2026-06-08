import axios from 'axios'

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '/api'

const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

// Request interceptor to add bearer token
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
}, (error) => {
  return Promise.reject(error)
})

// Response interceptor to handle unauthenticated state (401)
api.interceptors.response.use((response) => {
  return response
}, (error) => {
  if (error.response && error.response.status === 401) {
    localStorage.removeItem('auth_token')
    localStorage.removeItem('user')
    // Redirect to login if not already there
    if (window.location.pathname !== '/' && window.location.pathname !== '/register') {
      window.location.href = '/'
    }
  }
  return Promise.reject(error)
})

export const authService = {
  async register(name, email, password) {
    const response = await api.post('/register', { name, email, password })
    if (response.data.token) {
      localStorage.setItem('auth_token', response.data.token)
      localStorage.setItem('user', JSON.stringify(response.data.user))
    }
    return response.data
  },

  async login(email, password) {
    const response = await api.post('/login', { email, password })
    if (response.data.token) {
      localStorage.setItem('auth_token', response.data.token)
      localStorage.setItem('user', JSON.stringify(response.data.user))
    }
    return response.data
  },

  async logout() {
    try {
      await api.post('/logout')
    } finally {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('user')
    }
  },

  async getCurrentUser() {
    const response = await api.get('/user')
    localStorage.setItem('user', JSON.stringify(response.data))
    return response.data
  },

  isAuthenticated() {
    return !!localStorage.getItem('auth_token')
  },

  getUser() {
    const userStr = localStorage.getItem('user')
    return userStr ? JSON.parse(userStr) : null
  }
}

export const todoService = {
  async getAll() {
    const response = await api.get('/todos')
    return response.data
  },

  async create(todoData) {
    const response = await api.post('/todos', todoData)
    return response.data
  },

  async update(id, todoData) {
    const response = await api.put(`/todos/${id}`, todoData)
    return response.data
  },

  async updateStatus(id, status) {
    const response = await api.patch(`/todos/${id}/status`, { status })
    return response.data
  },

  async delete(id) {
    const response = await api.delete(`/todos/${id}`)
    return response.data
  }
}

export default api
