<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { authService, todoService } from '../services/api'
import TodosSidebar from '../components/TodosSidebar.vue'
import TodosNavbar from '../components/TodosNavbar.vue'

const router = useRouter()
const route = useRoute()

const user = ref(null)
const todos = ref([])
const loading = ref(true)
const dropdownOpen = ref(false)
const viewStyle = ref('card')

const showFilterDropdown = ref(false)
const filterSections = ref({
  status: true,
  date: true
})

const selectedStatuses = ref(['all'])
const dateFilterType = ref('all') // 'all', 'today', 'this_week', 'this_month', 'overdue', 'custom'
const dateRangeStart = ref('')
const dateRangeEnd = ref('')

const filterContainer = ref(null)

const editingTodo = ref(null)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const todoToDeleteId = ref(null)

const cardToasts = ref({})

const triggerToast = (msg, type = 'success', todoId = 'global') => {
  cardToasts.value[todoId] = { message: msg, type }
  setTimeout(() => {
    delete cardToasts.value[todoId]
  }, 3000)
}

const fetchData = async () => {
  try {
    user.value = authService.getUser() || await authService.getCurrentUser()
    todos.value = await todoService.getAll()
  } catch {
    triggerToast('Failed to load tasks', 'error', 'global')
  } finally {
    loading.value = false
  }
}

const toggleStatusFilter = (status) => {
  if (status === 'all') {
    selectedStatuses.value = ['all']
  } else {
    const indexAll = selectedStatuses.value.indexOf('all')
    if (indexAll !== -1) {
      selectedStatuses.value.splice(indexAll, 1)
    }

    const index = selectedStatuses.value.indexOf(status)
    if (index !== -1) {
      selectedStatuses.value.splice(index, 1)
    } else {
      selectedStatuses.value.push(status)
    }

    if (selectedStatuses.value.length === 0) {
      selectedStatuses.value = ['all']
    } else if (
      selectedStatuses.value.includes('todo') &&
      selectedStatuses.value.includes('pending') &&
      selectedStatuses.value.includes('overdue') &&
      selectedStatuses.value.includes('completed')
    ) {
      selectedStatuses.value = ['all']
    }
  }
}

const activeFiltersCount = computed(() => {
  let count = 0
  if (!selectedStatuses.value.includes('all')) {
    count += selectedStatuses.value.length
  }
  if (dateFilterType.value !== 'all') {
    count += 1
  }
  return count
})

const clearAllFilters = () => {
  selectedStatuses.value = ['all']
  dateFilterType.value = 'all'
  dateRangeStart.value = ''
  dateRangeEnd.value = ''
}

const getTodayStr = () => {
  const d = new Date()
  const year = d.getFullYear()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const getWeekRange = () => {
  const now = new Date()
  const day = now.getDay()
  const diff = now.getDate() - day + (day === 0 ? -6 : 1)
  const monday = new Date(now.setDate(diff))
  monday.setHours(0, 0, 0, 0)
  
  const sunday = new Date(monday)
  sunday.setDate(monday.getDate() + 6)
  sunday.setHours(23, 59, 59, 999)
  
  return { start: monday, end: sunday }
}

const isInThisWeek = (dateStr) => {
  if (!dateStr) return false
  const d = new Date(dateStr)
  d.setHours(0, 0, 0, 0)
  const { start, end } = getWeekRange()
  return d >= start && d <= end
}

const isInThisMonth = (dateStr) => {
  if (!dateStr) return false
  const d = new Date(dateStr)
  const now = new Date()
  return d.getFullYear() === now.getFullYear() && d.getMonth() === now.getMonth()
}

const isOverdue = (todo) => {
  if (todo.status === 'overdue') return true
  if (todo.status === 'completed') return false
  if (!todo.due_date) return false
  const todayStr = getTodayStr()
  return todo.due_date < todayStr
}

const isInCustomRange = (dateStr) => {
  if (!dateStr) return false
  if (dateRangeStart.value && dateStr < dateRangeStart.value) return false
  if (dateRangeEnd.value && dateStr > dateRangeEnd.value) return false
  return true
}

const handleClickOutside = (event) => {
  if (filterContainer.value && !filterContainer.value.contains(event.target)) {
    showFilterDropdown.value = false
  }
}

onMounted(() => {
  if (!authService.isAuthenticated()) router.push('/')
  if (route.query.status) {
    selectedStatuses.value = [route.query.status]
  }
  document.addEventListener('click', handleClickOutside)
  fetchData()
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

const filteredTodos = computed(() => {
  let list = todos.value.map(t => ({ ...t, computedStatus: t.status }))
  
  // 1. Status Filter
  if (!selectedStatuses.value.includes('all')) {
    list = list.filter(t => selectedStatuses.value.includes(t.computedStatus))
  }
  
  // 2. Date Filter
  const todayStr = getTodayStr()
  if (dateFilterType.value === 'today') {
    list = list.filter(t => t.due_date && t.due_date === todayStr)
  } else if (dateFilterType.value === 'this_week') {
    list = list.filter(t => isInThisWeek(t.due_date))
  } else if (dateFilterType.value === 'this_month') {
    list = list.filter(t => isInThisMonth(t.due_date))
  } else if (dateFilterType.value === 'overdue') {
    list = list.filter(t => isOverdue(t))
  } else if (dateFilterType.value === 'custom') {
    list = list.filter(t => isInCustomRange(t.due_date))
  }
  
  return list
})

const stats = computed(() => {
  let todo = 0, pending = 0, overdue = 0, completed = 0
  todos.value.forEach(t => {
    const s = t.status
    if (s === 'todo') todo++
    if (s === 'pending') pending++
    if (s === 'overdue') overdue++
    if (s === 'completed') completed++
  })
  return { total: todos.value.length, todo, pending, overdue, completed }
})

const handleStatusChange = async (todo, newStatus) => {
  try {
    const updated = await todoService.updateStatus(todo.id, newStatus)
    const i = todos.value.findIndex(t => t.id === todo.id)
    if (i !== -1) {
      todos.value[i] = updated
    }
    triggerToast('Status updated 🎉', 'success', todo.id)
  } catch {
    triggerToast('Failed to update status', 'error', todo.id)
  }
}

const openEditModal = (todo) => {
  editingTodo.value = { ...todo }
  showEditModal.value = true
}

const handleSaveEdit = async () => {
  if (!editingTodo.value.title.trim()) {
    triggerToast('Title required', 'error', editingTodo.value.id)
    return
  }

  try {
    const updated = await todoService.update(editingTodo.value.id, {
      title: editingTodo.value.title,
      description: editingTodo.value.description,
      start_date: editingTodo.value.start_date || null,
      due_date: editingTodo.value.due_date || null
    })

    const i = todos.value.findIndex(t => t.id === editingTodo.value.id)
    if (i !== -1) todos.value[i] = updated

    showEditModal.value = false
    triggerToast('Updated successfully', 'success', editingTodo.value.id)
  } catch {
    triggerToast('Update failed', 'error', editingTodo.value.id)
  }
}

const handleDeleteTodo = (id) => {
  todoToDeleteId.value = id
  showDeleteModal.value = true
}

const confirmDelete = async () => {
  if (!todoToDeleteId.value) return
  const targetId = todoToDeleteId.value
  try {
    await todoService.delete(targetId)
    todos.value = todos.value.filter(t => t.id !== targetId)
    triggerToast('Deleted successfully', 'success', 'global')
  } catch {
    triggerToast('Delete failed', 'error', 'global')
  } finally {
    showDeleteModal.value = false
    todoToDeleteId.value = null
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

const statusConfig = {
  completed: { label: 'Completed', badge: 'bg-green-100 text-green-700', dot: 'bg-green-500' },
  pending:   { label: 'Pending',   badge: 'bg-yellow-100 text-yellow-700', dot: 'bg-yellow-400' },
  overdue:   { label: 'Overdue',   badge: 'bg-red-100 text-red-700', dot: 'bg-red-500' },
  todo:      { label: 'Todo',      badge: 'bg-slate-100 text-slate-600', dot: 'bg-slate-400' }
}

</script>

<template>
<div class="flex min-h-screen bg-slate-100">

  <TodosSidebar @logout="handleLogout" />

  <div class="flex-1 ml-[240px] flex flex-col">

    <TodosNavbar
      :user="user"
      :dropdown-open="dropdownOpen"
      @toggle-dropdown="dropdownOpen=!dropdownOpen"
      @logout="handleLogout"
    />

    <div class="flex-1 p-10 mt-16 max-w-[1400px] w-full mx-auto">

      <!-- HEADER -->
      <div class="flex justify-between items-center mb-10">
        <div>
          <h1 class="text-4xl font-black text-slate-900 tracking-tight">My Tasks</h1>
          <p class="text-slate-500 text-base mt-2">{{ stats.total }} tasks total</p>
        </div>

        <div class="flex gap-4 items-center">
          <div class="flex bg-white border rounded-xl overflow-hidden shadow-sm">
            <button class="px-5 py-3 text-base font-bold transition-all cursor-pointer" :class="viewStyle==='card'?'bg-slate-900 text-white':'hover:bg-slate-50'" @click="viewStyle='card'">Cards</button>
            <button class="px-5 py-3 text-base font-bold transition-all cursor-pointer" :class="viewStyle==='table'?'bg-slate-900 text-white':'hover:bg-slate-50'" @click="viewStyle='table'">Table</button>
          </div>

          <button class="bg-blue-600 hover:bg-blue-700 text-white px-7 py-3 rounded-xl font-bold text-lg shadow-md hover:shadow-lg transition-all cursor-pointer"
            @click="$router.push('/todos/create')">
            + New Task
          </button>
        </div>
      </div>

      <!-- FILTERS -->
      <div class="flex justify-between items-center mb-8">
        <div ref="filterContainer" class="relative">
          <button 
            @click="showFilterDropdown = !showFilterDropdown"
            class="flex items-center gap-2 bg-white border border-slate-200 hover:border-slate-300 text-slate-800 px-5.5 py-3 rounded-xl font-bold text-base shadow-sm hover:shadow transition-all cursor-pointer relative"
          >
            <span>🔍</span> Filter
            <span v-if="activeFiltersCount > 0" class="flex items-center justify-center bg-blue-600 text-white text-xs w-5 h-5 rounded-full font-black animate-scale-in">
              {{ activeFiltersCount }}
            </span>
            <span class="text-xs text-slate-400 transition-transform duration-200" :class="showFilterDropdown ? 'rotate-180' : ''">▼</span>
          </button>

          <!-- Dropdown Panel -->
          <Transition name="dropdown">
            <div v-if="showFilterDropdown" class="absolute left-0 mt-3 w-80 bg-white border border-slate-200 rounded-2xl shadow-2xl p-6 z-30 origin-top-left">
              
              <!-- Section 1: Status -->
              <div class="border-b border-slate-100 pb-4 mb-4">
                <button 
                  @click="filterSections.status = !filterSections.status"
                  class="flex justify-between items-center w-full text-left font-black text-slate-800 text-base mb-3 cursor-pointer"
                >
                  <span>Filter by Status</span>
                  <span class="text-slate-400 transition-transform duration-200" :class="filterSections.status ? 'rotate-180' : ''">▼</span>
                </button>
                
                <Transition name="expand">
                  <div v-show="filterSections.status" class="space-y-2.5 overflow-hidden">
                    <label class="flex items-center gap-3 text-slate-700 font-semibold cursor-pointer select-none">
                      <input 
                        type="checkbox" 
                        :checked="selectedStatuses.includes('all')"
                        @change="toggleStatusFilter('all')"
                        class="w-4.5 h-4.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                      />
                      <span>All Statuses ({{ stats.total }})</span>
                    </label>
                    <label v-for="(cfg, key) in statusConfig" :key="key" class="flex items-center gap-3 text-slate-700 font-semibold cursor-pointer select-none">
                      <input 
                        type="checkbox" 
                        :checked="selectedStatuses.includes(key)"
                        @change="toggleStatusFilter(key)"
                        class="w-4.5 h-4.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                      />
                      <span class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full" :class="cfg.dot"></span>
                        {{ cfg.label }} ({{ stats[key] }})
                      </span>
                    </label>
                  </div>
                </Transition>
              </div>

              <!-- Section 2: Date -->
              <div class="mb-5">
                <button 
                  @click="filterSections.date = !filterSections.date"
                  class="flex justify-between items-center w-full text-left font-black text-slate-800 text-base mb-3 cursor-pointer"
                >
                  <span>Filter by Date</span>
                  <span class="text-slate-400 transition-transform duration-200" :class="filterSections.date ? 'rotate-180' : ''">▼</span>
                </button>
                
                <Transition name="expand">
                  <div v-show="filterSections.date" class="space-y-3.5 overflow-hidden">
                    <select 
                      v-model="dateFilterType"
                      class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-slate-700 font-bold outline-none cursor-pointer focus:bg-white focus:border-blue-500 transition-all"
                    >
                      <option value="all">All Dates</option>
                      <option value="today">Today</option>
                      <option value="this_week">This Week</option>
                      <option value="this_month">This Month</option>
                      <option value="overdue">Overdue</option>
                      <option value="custom">Custom Range</option>
                    </select>

                    <div v-if="dateFilterType === 'custom'" class="space-y-2.5 pt-1.5 animate-in fade-in slide-in-from-top-1 duration-200">
                      <div>
                        <label class="text-[10px] uppercase font-black text-slate-400 block mb-1">Start Date</label>
                        <input 
                          type="date" 
                          v-model="dateRangeStart"
                          class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4.5 py-2 text-slate-700 font-medium text-sm outline-none focus:bg-white focus:border-blue-500 transition-all"
                        />
                      </div>
                      <div>
                        <label class="text-[10px] uppercase font-black text-slate-400 block mb-1">End Date</label>
                        <input 
                          type="date" 
                          v-model="dateRangeEnd"
                          class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4.5 py-2 text-slate-700 font-medium text-sm outline-none focus:bg-white focus:border-blue-500 transition-all"
                        />
                      </div>
                    </div>
                  </div>
                </Transition>
              </div>

              <!-- Actions -->
              <div v-if="activeFiltersCount > 0" class="flex justify-end border-t border-slate-100 pt-4">
                <button 
                  @click="clearAllFilters"
                  class="text-sm font-bold text-red-500 hover:text-red-600 hover:underline cursor-pointer transition-all"
                >
                  Clear all filters
                </button>
              </div>

            </div>
          </Transition>
        </div>
      </div>

      <!-- LOADING -->
      <div v-if="loading" class="text-center py-24 text-slate-400 text-xl font-medium animate-pulse">
        Loading tasks...
      </div>

      <!-- EMPTY -->
      <div v-else-if="filteredTodos.length===0" class="text-center py-24">
        <div class="text-7xl">📭</div>
        <p class="text-slate-500 text-xl mt-4 mb-8 font-medium">No tasks found</p>
        <button class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3.5 rounded-xl text-lg font-bold shadow-md hover:shadow-lg transition-all cursor-pointer"
          @click="$router.push('/todos/create')">
          Create Task
        </button>
      </div>

      <!-- CARD VIEW -->
      <div v-else-if="viewStyle==='card'" class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">

        <div v-for="t in filteredTodos" :key="t.id"
          class="relative overflow-hidden bg-white rounded-2xl border shadow p-6 flex flex-col gap-3 transition-all hover:shadow-md">

          <!-- Card Toast Overlay -->
          <Transition name="toast-slide">
            <div v-if="cardToasts[t.id]" 
                 class="absolute inset-x-0 top-0 p-3 text-center text-sm font-bold flex items-center justify-center gap-2 border-b z-10"
                 :class="cardToasts[t.id].type === 'success' 
                   ? 'bg-green-50 text-green-700 border-green-200' 
                   : 'bg-red-50 text-red-700 border-red-200'">
              <span>{{ cardToasts[t.id].type === 'success' ? '✅' : '❌' }}</span>
              <span>{{ cardToasts[t.id].message }}</span>
            </div>
          </Transition>

          <div class="flex justify-between items-center">
            <select
              :value="t.computedStatus"
              @change="handleStatusChange(t, $event.target.value)"
              class="px-4 py-2 text-sm rounded-full font-bold outline-none border-none cursor-pointer appearance-none bg-no-repeat pr-8 shadow-sm hover:brightness-95 transition-all"
              :class="statusConfig[t.computedStatus].badge"
              style="background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23475569%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E'); background-position: right 10px center; background-size: 10px auto;"
            >
              <option value="todo" class="bg-white text-slate-800 font-medium">Todo</option>
              <option value="pending" class="bg-white text-slate-800 font-medium">Pending</option>
              <option value="completed" class="bg-white text-slate-800 font-medium">Completed</option>
              <option value="overdue" class="bg-white text-slate-800 font-medium">Overdue</option>
            </select>

            <div class="flex gap-3 text-lg">
              <button @click="openEditModal(t)" class="hover:scale-120 transition-all cursor-pointer">✏️</button>
              <button @click="handleDeleteTodo(t.id)" class="hover:scale-120 transition-all cursor-pointer">🗑️</button>
            </div>
          </div>

          <h2 class="font-bold text-xl mt-3 text-slate-800" :class="t.computedStatus==='completed'?'line-through text-slate-400':''">
            {{ t.title }}
          </h2>

          <p class="text-base text-slate-600 mb-2 leading-relaxed">{{ t.description || 'No description provided' }}</p>

        </div>
      </div>

      <!-- TABLE VIEW -->
      <div v-else class="bg-white border rounded-2xl overflow-hidden shadow">
        <table class="w-full text-base">
          <thead class="bg-slate-50 text-left">
            <tr>
              <th class="p-4.5 font-bold text-slate-900">Title</th>
              <th class="p-4.5 font-bold text-slate-900">Description</th>
              <th class="p-4.5 font-bold text-slate-900">Status</th>
              <th class="p-4.5 font-bold text-slate-900">Start</th>
              <th class="p-4.5 font-bold text-slate-900">Due</th>
              <th class="p-4.5 font-bold text-slate-900 text-right">Actions</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="t in filteredTodos" :key="t.id" class="border-t hover:bg-slate-50/50 transition-colors">
              <td class="p-4.5">
                <div class="font-bold text-slate-800">{{ t.title }}</div>
              </td>

              <td class="p-4.5">
                <div class="max-w-[250px] truncate text-slate-600" :title="t.description">
                  {{ t.description || '—' }}
                </div>
              </td>

              <td class="p-4.5">
                <div class="relative inline-flex items-center">
                  <select
                    :value="t.computedStatus"
                    @change="handleStatusChange(t, $event.target.value)"
                    class="px-4 py-2 text-sm rounded-full font-bold outline-none border-none cursor-pointer appearance-none bg-no-repeat pr-8 shadow-sm hover:brightness-95 transition-all"
                    :class="statusConfig[t.computedStatus].badge"
                    style="background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23475569%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E'); background-position: right 10px center; background-size: 10px auto;"
                  >
                    <option value="todo" class="bg-white text-slate-800 font-medium">Todo</option>
                    <option value="pending" class="bg-white text-slate-800 font-medium">Pending</option>
                    <option value="completed" class="bg-white text-slate-800 font-medium">Completed</option>
                    <option value="overdue" class="bg-white text-slate-800 font-medium">Overdue</option>
                  </select>
                  <Transition name="fade">
                    <div v-if="cardToasts[t.id]" 
                         class="absolute left-full ml-3 px-3 py-1 rounded-lg text-xs font-bold whitespace-nowrap shadow border flex items-center gap-1.5 z-10"
                         :class="cardToasts[t.id].type === 'success' 
                           ? 'bg-green-50 text-green-700 border-green-200' 
                           : 'bg-red-50 text-red-700 border-red-200'">
                      <span>{{ cardToasts[t.id].type === 'success' ? '✅' : '❌' }}</span>
                      <span>{{ cardToasts[t.id].message }}</span>
                    </div>
                  </Transition>
                </div>
              </td>

              <td class="p-4.5 text-slate-600">{{ t.start_date || '—' }}</td>
              <td class="p-4.5 text-slate-600">{{ t.due_date || '—' }}</td>

              <td class="p-4.5 text-right">
                <div class="flex justify-end gap-3">
                  <button
                    @click="openEditModal(t)"
                    class="inline-flex items-center gap-1.5 px-4.5 py-2.5 text-sm font-bold rounded-xl bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors cursor-pointer"
                  >
                    ✏️ Edit
                  </button>
                  <button
                    @click="handleDeleteTodo(t.id)"
                    class="inline-flex items-center gap-1.5 px-4.5 py-2.5 text-sm font-bold rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 transition-colors cursor-pointer"
                  >
                    🗑️ Delete
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>
  </div>

  <!-- MODAL -->
  <div v-if="showEditModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 animate-in fade-in duration-200">
    <div class="bg-white p-8 rounded-2xl w-[500px] shadow-2xl">

      <h2 class="text-2xl font-black text-slate-900 mb-5">Edit Task</h2>

      <input
        v-model="editingTodo.title"
        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-slate-800 placeholder-slate-400 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-lg mb-3 transition-all"
        placeholder="Task Title"
      />
      <textarea
        v-model="editingTodo.description"
        rows="4"
        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-slate-800 placeholder-slate-400 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-lg mb-3 resize-none transition-all"
        placeholder="Description"
      ></textarea>

      <div class="flex gap-4 mb-4">
        <div class="flex-1">
          <label class="text-xs uppercase font-extrabold text-slate-500 block mb-1.5">Start Date</label>
          <input
            type="date"
            v-model="editingTodo.start_date"
            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-lg transition-all"
          />
        </div>
        <div class="flex-1">
          <label class="text-xs uppercase font-extrabold text-slate-500 block mb-1.5">Due Date</label>
          <input
            type="date"
            v-model="editingTodo.due_date"
            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-slate-800 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none text-lg transition-all"
          />
        </div>
      </div>

      <div class="flex justify-end gap-3 mt-6">
        <button
          class="px-6 py-3 rounded-xl border bg-white text-slate-600 hover:bg-slate-50 font-bold text-base cursor-pointer transition-all"
          @click="showEditModal=false"
        >
          Cancel
        </button>
        <button
          class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-bold text-base cursor-pointer shadow-md hover:shadow-lg transition-all"
          @click="handleSaveEdit"
        >
          Save
        </button>
      </div>

    </div>
  </div>

  <!-- DELETE CONFIRMATION MODAL -->
  <div v-if="showDeleteModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 animate-in fade-in duration-200">
    <div class="bg-white p-8 rounded-2xl w-[450px] shadow-2xl text-center transform scale-100 transition-all">
      <div class="text-rose-500 text-5xl mb-3">⚠️</div>
      <h3 class="font-bold text-2xl text-slate-900 mb-2">Delete Task?</h3>
      <p class="text-base text-slate-500 mb-6">Are you sure you want to delete this task?</p>
      <div class="flex justify-center gap-4">
        <button
          class="px-6 py-3 rounded-xl border bg-white text-slate-600 hover:bg-slate-50 font-bold text-base cursor-pointer transition-all"
          @click="showDeleteModal = false"
        >
          Cancel
        </button>
        <button
          class="bg-rose-600 hover:bg-rose-700 text-white px-7 py-3 rounded-xl font-bold text-base cursor-pointer shadow-md hover:shadow-lg transition-all"
          @click="confirmDelete"
        >
          Delete
        </button>
      </div>
    </div>
  </div>

  <!-- GLOBAL TOAST -->
  <Transition name="global-toast">
    <div v-if="cardToasts['global']"
      class="fixed top-24 right-8 px-6 py-4 rounded-2xl shadow-xl font-bold text-base z-50 flex items-center gap-2 border"
      :class="cardToasts['global'].type === 'success' 
        ? 'bg-green-50 text-green-700 border-green-200' 
        : 'bg-red-50 text-red-700 border-red-200'">
      <span>{{ cardToasts['global'].type === 'success' ? '✅' : '❌' }}</span>
      <span>{{ cardToasts['global'].message }}</span>
    </div>
  </Transition>

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

/* Fade transition for table view inline toast */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Global toast transitions */
.global-toast-enter-active,
.global-toast-leave-active {
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.global-toast-enter-from {
  transform: translateX(100%);
  opacity: 0;
}
.global-toast-leave-to {
  transform: translateX(100%);
  opacity: 0;
}

/* Dropdown transition */
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
.dropdown-enter-from,
.dropdown-leave-to {
  transform: scale(0.95);
  opacity: 0;
}

/* Expand transition (basic vertical height collapse/expand) */
.expand-enter-active,
.expand-leave-active {
  transition: max-height 0.25s ease-in-out, opacity 0.2s ease;
  max-height: 350px;
}
.expand-enter-from,
.expand-leave-to {
  max-height: 0;
  opacity: 0;
}
</style>
