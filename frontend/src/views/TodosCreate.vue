<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { authService, todoService } from '../services/api'
import TodosSidebar from '../components/TodosSidebar.vue'
import TodosNavbar from '../components/TodosNavbar.vue'

const router = useRouter()
const user = ref(null)
const dropdownOpen = ref(false)
const actionLoading = ref(false)

const newTodo = ref({ title: '', description: '', start_date: '', due_date: '' })

const toastMessage = ref('')
const toastType = ref('success')
const showToast = ref(false)

const triggerToast = (msg, type = 'success') => {
  toastMessage.value = msg
  toastType.value = type
  showToast.value = true
  setTimeout(() => showToast.value = false, 3000)
}

const handleCreateTodo = async () => {
  if (!newTodo.value.title.trim()) {
    triggerToast('Title is required', 'error')
    return
  }

  actionLoading.value = true
  try {
    const payload = {
      ...newTodo.value,
      start_date: newTodo.value.start_date || null,
      due_date: newTodo.value.due_date || null
    }

    await todoService.create(payload)

    triggerToast('Task created successfully! 🎉')
    newTodo.value = { title: '', description: '', start_date: '', due_date: '' }
    setTimeout(() => router.push('/todos/list'), 1200)

  } catch (err) {
    triggerToast('Failed to create task', 'error')
  } finally {
    actionLoading.value = false
  }
}

const handleLogout = async () => {
  try {
    await authService.logout()
  } catch (error) {
    console.error('Logout failed:', error)
  }
  router.push('/')
}

onMounted(async () => {
  if (!authService.isAuthenticated()) { router.push('/'); return }
  user.value = authService.getUser() || await authService.getCurrentUser()
})
</script>

<template>
  <div class="flex min-h-screen bg-slate-100">

    <TodosSidebar @logout="handleLogout" />

    <div class="flex-1 ml-[240px] flex flex-col">

      <TodosNavbar
        :user="user"
        :dropdown-open="dropdownOpen"
        @toggle-dropdown="dropdownOpen = !dropdownOpen"
        @logout="handleLogout"
      />

      <div class="flex-1 p-10 mt-16 max-w-[1100px] w-full mx-auto">

        <!-- Header -->
        <div class="flex justify-between items-center mb-10">
          <div>
            <h1 class="text-4xl font-black text-slate-900 tracking-tight">Create Task</h1>
            <p class="text-base text-slate-500 mt-2">Add a new task to your list</p>
          </div>

          <button
            class="px-6 py-3 rounded-xl border bg-white text-slate-600 hover:bg-slate-50 font-bold text-base shadow-sm hover:shadow transition-all"
            @click="$router.push('/todos/list')"
          >
            ← View Tasks
          </button>
        </div>

        <!-- Form Card -->
        <div class="bg-white rounded-2xl shadow border border-slate-100 p-10 max-w-[1100px]">

          <form @submit.prevent="handleCreateTodo" class="flex flex-col gap-8">

            <!-- Title -->
            <div class="flex flex-col gap-2">
              <label class="text-base font-extrabold text-slate-800">
                Task Title <span class="text-red-500">*</span>
              </label>
              <input
                v-model="newTodo.title"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all"
                placeholder="What needs to be done?"
              />
            </div>

            <!-- Description -->
            <div class="flex flex-col gap-2">
              <label class="text-base font-extrabold text-slate-800">Description</label>
              <textarea
                v-model="newTodo.description"
                rows="5"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none resize-none transition-all"
                placeholder="Add more details..."
              ></textarea>
            </div>

            <!-- Dates -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

              <div class="flex flex-col gap-2">
                <label class="text-base font-extrabold text-slate-800">Start Date</label>
                <input
                  type="date"
                  v-model="newTodo.start_date"
                  class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all"
                />
                <p class="text-sm text-slate-400 mt-1">When does this start?</p>
              </div>

              <div class="flex flex-col gap-2">
                <label class="text-base font-extrabold text-slate-800">Due Date</label>
                <input
                  type="date"
                  v-model="newTodo.due_date"
                  class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all"
                />
                <p class="text-sm text-slate-400 mt-1">Deadline</p>
              </div>

            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-4 pt-4">

              <button
                type="button"
                class="px-7 py-3 rounded-xl border bg-white hover:bg-slate-50 text-slate-600 font-bold text-lg transition-all"
                @click="$router.push('/todos/list')"
              >
                Cancel
              </button>

              <button
                type="submit"
                :disabled="actionLoading"
                class="flex items-center gap-3 px-8 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white font-black text-lg shadow-md hover:shadow-lg transition-all cursor-pointer"
              >
                <span v-if="actionLoading" class="w-5 h-5 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                {{ actionLoading ? 'Creating...' : 'Create Task' }}
              </button>

            </div>

          </form>
        </div>
      </div>
    </div>

    <!-- Toast -->
    <div
      v-if="showToast"
      class="fixed bottom-8 right-8 px-6 py-4 rounded-2xl shadow-xl font-bold text-base"
      :class="toastType === 'success'
        ? 'bg-slate-900 text-white'
        : 'bg-red-50 text-red-700 border border-red-200'"
    >
      {{ toastMessage }}
    </div>

  </div>
</template>
