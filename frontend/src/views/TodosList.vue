<script setup>
import { ref, computed, onMounted } from 'vue'
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
const activeFilter = ref('')
const editingTodo = ref(null)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const todoToDeleteId = ref(null)

const toastMessage = ref('')
const toastType = ref('success')
const showToast = ref(false)

const triggerToast = (msg, type = 'success') => {
  toastMessage.value = msg
  toastType.value = type
  showToast.value = true
  setTimeout(() => (showToast.value = false), 3000)
}

const fetchData = async () => {
  try {
    user.value = authService.getUser() || await authService.getCurrentUser()
    todos.value = await todoService.getAll()
  } catch {
    triggerToast('Failed to load tasks', 'error')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  if (!authService.isAuthenticated()) router.push('/')
  if (route.query.status) activeFilter.value = route.query.status
  fetchData()
})

const filteredTodos = computed(() => {
  const list = todos.value.map(t => ({ ...t, computedStatus: t.status }))
  if (!activeFilter.value) return list
  return list.filter(t => t.computedStatus === activeFilter.value)
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
    triggerToast('Status updated 🎉')
  } catch {
    triggerToast('Failed to update status', 'error')
  }
}

const openEditModal = (todo) => {
  editingTodo.value = { ...todo }
  showEditModal.value = true
}

const handleSaveEdit = async () => {
  if (!editingTodo.value.title.trim()) {
    triggerToast('Title required', 'error')
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
    triggerToast('Updated successfully')
  } catch {
    triggerToast('Update failed', 'error')
  }
}

const handleDeleteTodo = (id) => {
  todoToDeleteId.value = id
  showDeleteModal.value = true
}

const confirmDelete = async () => {
  if (!todoToDeleteId.value) return
  try {
    await todoService.delete(todoToDeleteId.value)
    todos.value = todos.value.filter(t => t.id !== todoToDeleteId.value)
    triggerToast('Deleted')
  } catch {
    triggerToast('Delete failed', 'error')
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

const filterLabels = [
  { key:'', label:'All', count:()=>stats.value.total },
  { key:'todo', label:'Todo', count:()=>stats.value.todo },
  { key:'pending', label:'Pending', count:()=>stats.value.pending },
  { key:'overdue', label:'Overdue', count:()=>stats.value.overdue },
  { key:'completed', label:'Completed', count:()=>stats.value.completed }
]
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
      <div class="flex flex-wrap gap-3 mb-8">
        <button
          v-for="f in filterLabels"
          :key="f.key"
          class="px-6 py-3 rounded-full border text-base font-bold shadow-sm cursor-pointer transition-all hover:scale-[1.03]"
          :class="activeFilter===f.key ? 'bg-slate-900 text-white border-slate-900' : 'bg-white hover:bg-slate-50'"
          @click="activeFilter=f.key"
        >
          {{ f.label }} ({{ f.count() }})
        </button>
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
          class="bg-white rounded-2xl border shadow p-6 flex flex-col gap-3 transition-all hover:shadow-md">

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
      <p class="text-base text-slate-500 mb-6">Are you sure you want to delete this task? This action cannot be undone.</p>
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

  <!-- TOAST -->
  <div v-if="showToast"
    class="fixed bottom-8 right-8 px-6 py-4 rounded-2xl shadow-xl font-bold text-base bg-slate-900 text-white">
    {{ toastMessage }}
  </div>

</div>
</template>
