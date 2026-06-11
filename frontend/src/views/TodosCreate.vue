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
    setTimeout(() => router.push('/todos/list'), 1300)

  } catch (err) {
    console.error('Task creation failed:', err)
    const errorMsg = err.response?.data?.message || err.response?.data?.error || err.message || ''
    triggerToast(`Failed to create task: ${errorMsg}`.trim(), 'error')
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
  <div class="flex h-screen bg-slate-100 overflow-hidden">

    <TodosSidebar @logout="handleLogout" />

    <div class="flex-1 ml-[240px] flex flex-col h-screen overflow-hidden">

      <TodosNavbar
        :user="user"
        :dropdown-open="dropdownOpen"
        @toggle-dropdown="dropdownOpen = !dropdownOpen"
        @logout="handleLogout"
      />

      <div class="flex-1 p-6 mt-16 max-w-[1400px] w-full mx-auto flex flex-col justify-center overflow-y-auto min-h-0">

        <!-- Header -->
        <div class="flex justify-between items-center mb-6 max-w-[900px] w-full mx-auto">
          <div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight">Create Task</h1>
            <p class="text-sm text-slate-500 mt-1">Add a new task to your list</p>
          </div>

          <button
            class="px-4 py-2 rounded-xl border bg-white text-slate-600 hover:bg-slate-50 font-bold text-sm shadow-sm hover:shadow transition-all"
            @click="$router.push('/todos/list')"
          >
            ← View Tasks
          </button>
        </div>

        <!-- Form Card -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow border border-slate-100 p-6 md:p-8 w-full max-w-[900px] mx-auto">

          <!-- Card Toast Overlay -->
          <Transition name="toast-slide">
            <div v-if="showToast"
                 class="absolute inset-x-0 top-0 p-3 text-center text-sm font-bold flex items-center justify-center gap-2 border-b z-10"
                 :class="toastType === 'success'
                   ? 'bg-green-50 text-green-700 border-green-200'
                   : 'bg-red-50 text-red-700 border-red-200'">
              <span>{{ toastType === 'success' ? '✅' : '❌' }}</span>
              <span>{{ toastMessage }}</span>
            </div>
          </Transition>

          <form @submit.prevent="handleCreateTodo" class="flex flex-col gap-4">

            <!-- Title -->
            <div class="flex flex-col gap-1.5">
              <label class="text-sm font-bold text-slate-800">
                Task Title <span class="text-red-500">*</span>
              </label>
              <input
                v-model="newTodo.title"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-base focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all"
                placeholder="What needs to be done?"
              />
            </div>

            <!-- Description -->
            <div class="flex flex-col gap-1.5">
              <label class="text-sm font-bold text-slate-800">Description</label>
              <textarea
                v-model="newTodo.description"
                rows="3"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-base focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none resize-none transition-all"
                placeholder="Add more details..."
              ></textarea>
            </div>

            <!-- Dates -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

              <div class="flex flex-col gap-1.5">
                <label class="text-sm font-bold text-slate-800">Start Date</label>
                <input
                  type="date"
                  v-model="newTodo.start_date"
                  class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-base focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all"
                />
                <p class="text-xs text-slate-400 mt-0.5">When does this start?</p>
              </div>

              <div class="flex flex-col gap-1.5">
                <label class="text-sm font-bold text-slate-800">Due Date</label>
                <input
                  type="date"
                  v-model="newTodo.due_date"
                  class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-base focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all"
                />
                <p class="text-xs text-slate-400 mt-0.5">Deadline</p>
              </div>

            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3 pt-2">

              <button
                type="button"
                class="px-5 py-2.5 rounded-xl border bg-white hover:bg-slate-50 text-slate-600 font-bold text-base transition-all"
                @click="$router.push('/todos/list')"
              >
                Cancel
              </button>

              <button
                type="submit"
                :disabled="actionLoading"
                class="flex items-center gap-2 px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white font-black text-base shadow-md hover:shadow-lg transition-all cursor-pointer"
              >
                <span v-if="actionLoading" class="w-5 h-5 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                {{ actionLoading ? 'Creating...' : 'Create Task' }}
              </button>

            </div>

          </form>
        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
/* Toast transitions */
.toast-slide-enter-active,
.toast-slide-leave-active {
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.toast-slide-enter-from {
  transform: translateY(-100%);
  opacity: 0;
}
.toast-slide-leave-to {
  transform: translateY(-100%);
  opacity: 0;
}
</style>
