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
  try { await authService.logout() } catch {}
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

      <div class="flex-1 p-8 mt-16 max-w-[900px] w-full mx-auto">

        <!-- Header -->
        <div class="flex justify-between items-start mb-7">
          <div>
            <h1 class="text-2xl font-extrabold text-slate-900">Create Task</h1>
            <p class="text-sm text-slate-500 mt-1">Add a new task to your list</p>
          </div>

          <button
            class="px-4 py-2 rounded-lg border bg-white text-slate-600 hover:bg-slate-50 font-semibold"
            @click="$router.push('/todos/list')"
          >
            ← View Tasks
          </button>
        </div>

        <!-- Form Card -->
        <div class="bg-white rounded-2xl shadow border border-slate-100 p-8 max-w-[900px]">

          <form @submit.prevent="handleCreateTodo" class="flex flex-col gap-6">

            <!-- Title -->
            <div class="flex flex-col gap-1">
              <label class="text-sm font-bold text-slate-700">
                Task Title <span class="text-red-500">*</span>
              </label>
              <input
                v-model="newTodo.title"
                class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none"
                placeholder="What needs to be done?"
              />
            </div>

            <!-- Description -->
            <div class="flex flex-col gap-1">
              <label class="text-sm font-bold text-slate-700">Description</label>
              <textarea
                v-model="newTodo.description"
                rows="4"
                class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none resize-none"
                placeholder="Add more details..."
              ></textarea>
            </div>

            <!-- Dates -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

              <div class="flex flex-col gap-1">
                <label class="text-sm font-bold text-slate-700">Start Date</label>
                <input
                  type="date"
                  v-model="newTodo.start_date"
                  class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none"
                />
                <p class="text-xs text-slate-400">When does this start?</p>
              </div>

              <div class="flex flex-col gap-1">
                <label class="text-sm font-bold text-slate-700">Due Date</label>
                <input
                  type="date"
                  v-model="newTodo.due_date"
                  class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none"
                />
                <p class="text-xs text-slate-400">Deadline</p>
              </div>

            </div>



            <!-- Actions -->
            <div class="flex justify-end gap-3 pt-2">

              <button
                type="button"
                class="px-5 py-2 rounded-lg border bg-white hover:bg-slate-50 text-slate-600 font-semibold"
                @click="$router.push('/todos/list')"
              >
                Cancel
              </button>

              <button
                type="submit"
                :disabled="actionLoading"
                class="flex items-center gap-2 px-6 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white font-bold"
              >
                <span v-if="actionLoading" class="w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
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
      class="fixed bottom-6 right-6 px-5 py-3 rounded-xl shadow-lg font-semibold text-sm"
      :class="toastType === 'success'
        ? 'bg-slate-900 text-white'
        : 'bg-red-50 text-red-700 border border-red-200'"
    >
      {{ toastMessage }}
    </div>

  </div>
</template>
