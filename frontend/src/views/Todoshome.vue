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
  try { await authService.logout() } catch {}
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

      <div class="flex-1 p-8 mt-16 max-w-[1200px] w-full   mx-auto">

        <!-- Header -->
        <div class="flex justify-between items-start mb-7">
          <div>
            <h1 class="text-2xl font-extrabold text-slate-900">Dashboard</h1>
            <p class="text-lg text-slate-800 mt-1">
              Welcome back{{ user ? ', ' + user.name : '' }} 👋
            </p>
          </div>

          <button
            class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg font-semibold"
            @click="$router.push('/todos/create')"
          >
            ➕ New Task
          </button>
        </div>

        <!-- Stats -->
        <div v-if="!loading" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-7">
          <div class="bg-white p-5 rounded-xl shadow border border-slate-100 border-l-4 border-l-blue-500 flex items-center gap-3">
             <div>
               <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold">Total</p>
               <p class="text-2xl font-extrabold text-blue-600">{{ stats.total }}</p>
             </div>
          </div>

          <div class="bg-white p-5 rounded-xl shadow border border-slate-100 border-l-4 border-l-slate-400 cursor-pointer hover:bg-slate-50 transition-colors" @click="$router.push('/todos/list?status=todo')">
             <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold">Todo</p>
             <p class="text-2xl font-extrabold text-slate-400">{{ stats.todo }}</p>
          </div>

          <div class="bg-white p-5 rounded-xl shadow border border-slate-100 border-l-4 border-l-yellow-400 cursor-pointer hover:bg-slate-50 transition-colors" @click="$router.push('/todos/list?status=pending')">
             <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold">Pending</p>
             <p class="text-2xl font-extrabold text-yellow-600">{{ stats.pending }}</p>
          </div>

          <div class="bg-white p-5 rounded-xl shadow border border-slate-100 border-l-4 border-l-red-500 cursor-pointer hover:bg-slate-50 transition-colors" @click="$router.push('/todos/list?status=overdue')">
             <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold">Overdue</p>
             <p class="text-2xl font-extrabold text-red-600">{{ stats.overdue }}</p>
          </div>

          <div class="bg-white p-5 rounded-xl shadow border border-slate-100 border-l-4 border-l-green-500 cursor-pointer hover:bg-slate-50 transition-colors" @click="$router.push('/todos/list?status=completed')">
             <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold">Completed</p>
             <p class="text-2xl font-extrabold text-green-600">{{ stats.completed }}</p>
          </div>
        </div>

        <div v-else class="text-center text-slate-400 p-10">Loading dashboard...</div>

        <!-- Bottom Grid -->
        <div v-if="!loading && stats.total > 0" class="grid grid-cols-1 lg:grid-cols-3 gap-5">

          <!-- Donut -->
          <div class="bg-white p-6 rounded-xl shadow border">
            <h2 class="font-bold mb-4">Task Distribution</h2>

            <div class="flex items-center gap-5">
              <svg viewBox="0 0 120 120" class="w-[120px] h-[120px]">
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

                <text x="60" y="56" text-anchor="middle" class="font-bold text-lg fill-slate-800">
                  {{ stats.total }}
                </text>
                <text x="60" y="70" text-anchor="middle" class="text-xs fill-slate-400">
                  TOTAL
                </text>
              </svg>

              <div class="space-y-2">
                <div v-for="seg in donutSegments" :key="seg.label" class="flex items-center gap-2">
                  <span class="w-3 h-3 rounded-full" :style="{ background: seg.color }"></span>
                  <span class="text-sm flex-1">{{ seg.label }}</span>
                  <span class="text-sm font-bold">{{ seg.value }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Progress -->
          <div class="bg-white p-6 rounded-xl shadow border">
            <h2 class="font-bold mb-4">Progress</h2>

            <div v-for="seg in donutSegments" :key="seg.label" class="mb-4">
              <div class="flex justify-between text-sm mb-1">
                <span>{{ seg.label }}</span>
                <span>{{ seg.value }} / {{ stats.total }}</span>
              </div>

              <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                <div class="h-full" :style="{ width: seg.pct + '%', background: seg.color }"></div>
              </div>

              <div class="text-xs text-right text-slate-400 mt-1">
                {{ seg.pct.toFixed(0) }}%
              </div>
            </div>
          </div>

          <!-- Recent -->
          <div class="bg-white p-6 rounded-xl shadow border">
            <div class="flex justify-between mb-4">
              <h2 class="font-bold">Recent Tasks</h2>
              <router-link to="/todos/list" class="text-blue-600 text-sm">View all →</router-link>
            </div>

            <div v-for="todo in recentTodos" :key="todo.id" class="flex items-center gap-3 py-2 border-b last:border-0">
              <span class="w-2.5 h-2.5 rounded-full" :class="statusConfig[todo.computedStatus]?.dot"></span>

              <div class="flex-1 min-w-0">
                <p class="truncate font-medium" :class="todo.computedStatus === 'completed' ? 'line-through text-slate-400' : ''">
                  {{ todo.title }}
                </p>
                <p class="text-xs text-slate-400">Due: {{ todo.due_date || 'No date' }}</p>
              </div>

              <span class="text-xs px-2 py-1 rounded-full" :class="statusConfig[todo.computedStatus]?.badge">
                {{ statusConfig[todo.computedStatus]?.label }}
              </span>
            </div>

            <p v-if="recentTodos.length === 0" class="text-center text-slate-400 py-5">
              No tasks yet
            </p>
          </div>
        </div>

        <!-- Empty -->
        <div v-if="!loading && stats.total === 0" class="text-center py-16">
          <div class="text-5xl">📭</div>
          <p class="text-slate-500 mt-3 mb-6">No tasks yet. Create your first task!</p>
          <button
            class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg"
            @click="$router.push('/todos/create')"
          >
            Create Task
          </button>
        </div>

      </div>
    </div>
  </div>
</template>
