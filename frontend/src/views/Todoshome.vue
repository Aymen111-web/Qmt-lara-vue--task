<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { authService, todoService } from '../services/api'
import TodosSidebar from '../components/TodosSidebar.vue'
import TodosNavbar from '../components/TodosNavbar.vue'

const router = useRouter()
const user = ref(null)
const todos = ref([])
const loading = ref(true)
const dropdownOpen = ref(false)

const stats = computed(() => {
  const total = todos.value.length
  let todo = 0, pending = 0, overdue = 0, completed = 0
  todos.value.forEach(t => {
    const s = t.status
    if (s === 'todo') todo++
    if (s === 'pending') pending++
    if (s === 'overdue') overdue++
    if (s === 'completed') completed++
  })
  return { total, todo, pending, overdue, completed }
})

const recentTodos = computed(() =>
  [...todos.value]
    .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
    .slice(0, 5)
    .map(t => ({ ...t, computedStatus: t.status }))
)

const donutSegments = computed(() => {
  const s = stats.value
  const data = [
    { label: 'Completed', value: s.completed, color: '#22c55e' },
    { label: 'Pending', value: s.pending, color: '#facc15' },
    { label: 'Todo', value: s.todo, color: '#94a3b8' },
    { label: 'Overdue', value: s.overdue, color: '#ef4444' },
  ]
  const total = s.total || 1
  let offset = 0
  return data.map(d => {
    const pct = (d.value / total) * 100
    const seg = { ...d, pct, offset }
    offset += pct
    return seg
  })
})

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
  try {
    todos.value = await todoService.getAll()
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
})

const statusConfig = {
  completed: { label: 'Completed', dot: 'bg-green-500', badge: 'bg-green-100 text-green-700' },
  pending:   { label: 'Pending',   dot: 'bg-yellow-400', badge: 'bg-yellow-100 text-yellow-700' },
  overdue:   { label: 'Overdue',   dot: 'bg-red-500', badge: 'bg-red-100 text-red-700' },
  todo:      { label: 'Todo',      dot: 'bg-slate-400', badge: 'bg-slate-100 text-slate-600' },
}

const RADIUS = 54
const CIRCUMFERENCE = 2 * Math.PI * RADIUS

const strokeDasharray = (pct) => {
  const dash = (pct / 100) * CIRCUMFERENCE
  return `${dash} ${CIRCUMFERENCE - dash}`
}
const strokeDashoffset = (offset) => {
  return -((offset / 100) * CIRCUMFERENCE)
}
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

      <div class="flex-1 p-10 mt-16 max-w-[1400px] w-full mx-auto">

        <!-- Header -->
        <div class="flex justify-between items-center mb-10">
          <div>
            <h1 class="text-4xl font-black text-slate-900 tracking-tight">Dashboard</h1>
            <p class="text-xl text-slate-700 mt-2">
              Welcome back{{ user ? ', ' + user.name : '' }} 👋
            </p>
          </div>

          <button
            class="flex items-center gap-3 bg-blue-600 hover:bg-blue-700 text-white px-7 py-3.5 rounded-xl font-bold text-lg shadow-md hover:shadow-lg transition-all"
            @click="$router.push('/todos/create')"
          >
            ➕ New Task
          </button>
        </div>

        <!-- Stats -->
        <div v-if="!loading" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6 mb-10">
          <div class="bg-white p-6 rounded-2xl shadow border border-slate-100 border-l-[6px] border-l-blue-500 flex items-center gap-4">
             <div>
                <p class="text-sm text-slate-500 uppercase tracking-wider font-bold">Total</p>
                <p class="text-4xl font-black text-blue-600 mt-1">{{ stats.total }}</p>
             </div>
          </div>

          <div class="bg-white p-6 rounded-2xl shadow border border-slate-100 border-l-[6px] border-l-slate-400 cursor-pointer hover:bg-slate-50 transition-all hover:scale-[1.02]" @click="$router.push('/todos/list?status=todo')">
             <p class="text-sm text-slate-500 uppercase tracking-wider font-bold">Todo</p>
             <p class="text-4xl font-black text-slate-400 mt-1">{{ stats.todo }}</p>
          </div>

          <div class="bg-white p-6 rounded-2xl shadow border border-slate-100 border-l-[6px] border-l-yellow-400 cursor-pointer hover:bg-slate-50 transition-all hover:scale-[1.02]" @click="$router.push('/todos/list?status=pending')">
             <p class="text-sm text-slate-500 uppercase tracking-wider font-bold">Pending</p>
             <p class="text-4xl font-black text-yellow-600 mt-1">{{ stats.pending }}</p>
          </div>

          <div class="bg-white p-6 rounded-2xl shadow border border-slate-100 border-l-[6px] border-l-red-500 cursor-pointer hover:bg-slate-50 transition-all hover:scale-[1.02]" @click="$router.push('/todos/list?status=overdue')">
             <p class="text-sm text-slate-500 uppercase tracking-wider font-bold">Overdue</p>
             <p class="text-4xl font-black text-red-600 mt-1">{{ stats.overdue }}</p>
          </div>

          <div class="bg-white p-6 rounded-2xl shadow border border-slate-100 border-l-[6px] border-l-green-500 cursor-pointer hover:bg-slate-50 transition-all hover:scale-[1.02]" @click="$router.push('/todos/list?status=completed')">
             <p class="text-sm text-slate-500 uppercase tracking-wider font-bold">Completed</p>
             <p class="text-4xl font-black text-green-600 mt-1">{{ stats.completed }}</p>
          </div>
        </div>

        <div v-else class="text-center text-slate-400 p-10 text-xl font-medium animate-pulse">Loading dashboard...</div>

        <!-- Bottom Grid -->
        <div v-if="!loading && stats.total > 0" class="grid grid-cols-1 lg:grid-cols-3 gap-8">

          <!-- Donut -->
          <div class="bg-white p-8 rounded-2xl shadow border">
            <h2 class="text-2xl font-extrabold text-slate-900 mb-6">Task Distribution</h2>

            <div class="flex items-center gap-8">
              <svg viewBox="0 0 120 120" class="w-[160px] h-[160px] flex-shrink-0">
                <circle cx="60" cy="60" :r="RADIUS" fill="none" stroke="#e2e8f0" stroke-width="14" />

                <circle
                  v-for="seg in donutSegments"
                  :key="seg.label"
                  cx="60" cy="60"
                  :r="RADIUS"
                  fill="none"
                  :stroke="seg.color"
                  stroke-width="14"
                  :stroke-dasharray="strokeDasharray(seg.pct)"
                  :stroke-dashoffset="strokeDashoffset(seg.offset)"
                  transform="rotate(-90 60 60)"
                />

                <text x="60" y="55" text-anchor="middle" class="font-black text-2xl fill-slate-800">
                  {{ stats.total }}
                </text>
                <text x="60" y="72" text-anchor="middle" class="text-[10px] tracking-widest font-bold fill-slate-400">
                  TOTAL
                </text>
              </svg>

              <div class="space-y-3.5 flex-1">
                <div v-for="seg in donutSegments" :key="seg.label" class="flex items-center gap-3">
                  <span class="w-4 h-4 rounded-full flex-shrink-0" :style="{ background: seg.color }"></span>
                  <span class="text-base text-slate-700 flex-1 font-medium">{{ seg.label }}</span>
                  <span class="text-base font-bold text-slate-900">{{ seg.value }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Progress -->
          <div class="bg-white p-8 rounded-2xl shadow border">
            <h2 class="text-2xl font-extrabold text-slate-900 mb-6">Progress</h2>

            <div v-for="seg in donutSegments" :key="seg.label" class="mb-5 last:mb-0">
              <div class="flex justify-between text-base font-semibold text-slate-700 mb-2">
                <span>{{ seg.label }}</span>
                <span>{{ seg.value }} / {{ stats.total }}</span>
              </div>

              <div class="h-3.5 bg-slate-100 rounded-full overflow-hidden">
                <div class="h-full rounded-full" :style="{ width: seg.pct + '%', background: seg.color }"></div>
              </div>

              <div class="text-sm font-bold text-right text-slate-400 mt-1.5">
                {{ seg.pct.toFixed(0) }}%
              </div>
            </div>
          </div>

          <!-- Recent -->
          <div class="bg-white p-8 rounded-2xl shadow border">
            <div class="flex justify-between items-center mb-6">
              <h2 class="text-2xl font-extrabold text-slate-900">Recent Tasks</h2>
              <router-link to="/todos/list" class="text-blue-600 text-base font-bold hover:underline">View all →</router-link>
            </div>

            <div v-for="todo in recentTodos" :key="todo.id" class="flex items-center gap-4 py-3.5 border-b last:border-0 border-slate-100">
              <span class="w-3.5 h-3.5 rounded-full flex-shrink-0" :class="statusConfig[todo.computedStatus]?.dot"></span>

              <div class="flex-1 min-w-0">
                <p class="truncate font-semibold text-lg text-slate-800" :class="todo.computedStatus === 'completed' ? 'line-through text-slate-400' : ''">
                  {{ todo.title }}
                </p>
                <p class="text-sm text-slate-500 mt-0.5">Due: {{ todo.due_date || 'No date' }}</p>
              </div>

              <span class="text-sm px-3 py-1.5 rounded-full font-bold shadow-sm" :class="statusConfig[todo.computedStatus]?.badge">
                {{ statusConfig[todo.computedStatus]?.label }}
              </span>
            </div>

            <p v-if="recentTodos.length === 0" class="text-center text-slate-400 py-8 text-lg">
              No tasks yet
            </p>
          </div>
        </div>

        <!-- Empty -->
        <div v-if="!loading && stats.total === 0" class="text-center py-24">
          <div class="text-7xl">📭</div>
          <p class="text-slate-500 text-xl mt-4 mb-8 font-medium">No tasks yet. Create your first task!</p>
          <button
            class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3.5 rounded-xl text-lg font-bold shadow-md hover:shadow-lg transition-all"
            @click="$router.push('/todos/create')"
          >
            Create Task
          </button>
        </div>

      </div>
    </div>
  </div>
</template>
