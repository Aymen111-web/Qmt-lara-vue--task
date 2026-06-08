<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { authService } from '../services/api'
import AuthNavbar from '../components/AuthNavbar.vue'

const router = useRouter()

const name = ref('')
const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

const handleRegister = async () => {
  if (!name.value || !email.value || !password.value) {
    error.value = 'Please fill in all fields'
    return
  }

  if (password.value.length < 6) {
    error.value = 'Password must be at least 6 characters'
    return
  }

  loading.value = true
  error.value = ''

  try {
    await authService.register(name.value, email.value, password.value)
    router.push('/todos')
  } catch (err) {
    console.error(err)
    if (err.response && err.response.data && err.response.data.message) {
      error.value = err.response.data.message
    } else if (err.response && err.response.data && err.response.data.errors) {
      const errs = err.response.data.errors
      error.value = Object.values(errs).flat().join(', ')
    } else {
      error.value = 'Registration failed. Email might already be taken.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-[#f3f4f6] flex flex-col items-center justify-center px-4 relative">
    <AuthNavbar />

    <!-- Register Container -->
    <div class="max-w-[400px] w-full bg-white border border-slate-100/60 rounded-[24px] p-9 shadow-lg shadow-slate-200/40 mt-16 transition-all duration-300">
      <div class="text-center mb-8">
        <h2 class="text-[28px] font-extrabold text-[#0f172a] tracking-tight">Create Account</h2>
      </div>

      <!-- Errors Alert -->
      <div v-if="error" class="mb-5 p-4 bg-rose-50 border-l-4 border-rose-500 rounded-xl text-rose-700 text-sm font-medium animate-in fade-in slide-in-from-top-2 duration-200">
        <span>{{ error }}</span>
      </div>

      <form @submit.prevent="handleRegister" class="space-y-4">
        <div>
          <input
            id="name"
            type="text"
            v-model="name"
            placeholder="Full Name"
            class="bg-white border border-slate-200 rounded-xl px-4 py-3.5 text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 transition-all w-full text-sm"
            required
            autocomplete="name"
          />
        </div>

        <div>
          <input
            id="email"
            type="email"
            v-model="email"
            placeholder="Email Address"
            class="bg-white border border-slate-200 rounded-xl px-4 py-3.5 text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 transition-all w-full text-sm"
            required
            autocomplete="email"
          />
        </div>

        <div>
          <input
            id="password"
            type="password"
            v-model="password"
            placeholder="Password"
            class="bg-white border border-slate-200 rounded-xl px-4 py-3.5 text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 transition-all w-full text-sm"
            required
            autocomplete="new-password"
          />
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full bg-[#2563eb] hover:bg-blue-700 disabled:opacity-50 text-white font-bold py-3.5 px-6 rounded-xl transition-all active:scale-[0.98] cursor-pointer flex justify-center items-center gap-2 text-sm mt-2"
        >
          <span v-if="loading" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
          <span>Register</span>
        </button>
      </form>

      <div class="text-center mt-6">
        <p class="text-sm text-slate-500">
          <router-link to="/" class="text-[#2563eb] hover:underline font-medium transition-colors">Already have an account? Login</router-link>
        </p>
      </div>
    </div>
  </div>
</template>
