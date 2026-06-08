<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { authService } from '../services/api'
import AuthNavbar from '../components/AuthNavbar.vue'

const router = useRouter()

const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

const handleLogin = async () => {
  if (!email.value || !password.value) {
    error.value = 'Please fill in all fields'
    return
  }

  loading.value = true
  error.value = ''

  try {
    await authService.login(email.value, password.value)
    router.push('/todos')
  } catch (err) {
    console.error(err)
    if (!err.response) {
      error.value = 'Cannot connect to the API server. Start the project with npm run dev from the project root.'
    } else if (err.response.data && err.response.data.message) {
      error.value = err.response.data.message
    } else {
      error.value = 'Invalid email or password. Please try again.'
    }
  } finally {
    loading.value = false
  }
}

const showPassword = ref(false)
</script>

<template>
  <div class="min-h-screen bg-[#f3f4f6] flex flex-col items-center justify-center px-4 relative">
    <AuthNavbar />

    <!-- Login Container -->
    <div class="max-w-[440px] w-full min-h-[400px] bg-white border border-slate-100/60 rounded-[24px] p-9 shadow-lg shadow-slate-200/40 mt-14 transition-all duration-300">
      <div class="text-center mb-8">
        <h2 class="text-[25px] font-extrabold text-[#0f172a] tracking-tight">Welcome Back!</h2>
      </div>

      <!-- Errors Alert -->
      <div v-if="error" class="mb-5 p-4 bg-rose-50 border-l-4 border-rose-500 rounded-xl text-rose-700 text-sm font-medium animate-in fade-in slide-in-from-top-2 duration-200">
        <span>{{ error }}</span>
      </div>

      <form @submit.prevent="handleLogin" class="space-y-4">
        <div>
          <input
            id="email"
            type="email"
            v-model="email"
            placeholder="Email Address"
            class="bg-white border border-slate-200 rounded-xl px-4 py-3.5 text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 transition-all w-full text-md"
            required
            autocomplete="email"
          />
        </div>

        <div class="relative w-full">

    <input
      id="password"
      :type="showPassword ? 'text' : 'password'"
      v-model="password"
      placeholder="Password"
      class="bg-white border border-slate-200 rounded-xl px-4 py-3.5 pr-12 text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 transition-all w-full text-sm"
      required
      autocomplete="current-password"
    />


    <button
      type="button"
      @click="showPassword = !showPassword"
      class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-700"
    >
      {{ showPassword ? '🙈' : '👀' }}
    </button>

  </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full bg-[#2563eb] hover:bg-blue-700 disabled:opacity-50 text-white font-bold py-3.5 px-6 rounded-xl transition-all active:scale-[0.98] cursor-pointer flex justify-center items-center gap-2 text-sm mt-2"
        >
          <span v-if="loading" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
          <span>Login</span>
        </button>
      </form>

      <div class="text-center mt-6">
        <p class="text-sm text-slate-500">
          <router-link to="/register" class="text-[#2563eb] hover:underline font-medium transition-colors">Don't have an account? Register</router-link>
        </p>
      </div>
    </div>
  </div>
</template>
