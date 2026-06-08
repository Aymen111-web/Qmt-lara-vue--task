<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

const emit = defineEmits(['logout'])

const navItems = [
  {
    key: 'home',
    label: 'Home',
    path: '/todos',
    icon: `<svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
    </svg>`
  },
  {
    key: 'create',
    label: 'Create Task',
    path: '/todos/create',
    icon: `<svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
    </svg>`
  },
  {
    key: 'view',
    label: 'View Tasks',
    path: '/todos/list',
    icon: `<svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
    </svg>`
  }
]

const isActive = (path) => route.path === path
</script>
<template>
  <aside
    class="fixed top-0 left-0 h-screen w-60 bg-gradient-to-b from-slate-800 to-slate-950 flex flex-col z-50 shadow-2xl"
  >
    <!-- Brand -->
    <div class="flex items-center gap-3 px-5 py-6 border-b border-white/10">
      <div
        class="w-9 h-9 bg-blue-500 rounded-lg flex items-center justify-center text-white font-black"
      >
        ✓
      </div>

      <span class="text-xl font-bold text-white tracking-wide">
        TaskFlow
      </span>
    </div>

    <!-- Nav -->
    <nav class="flex-1 px-3 py-5 overflow-y-auto">
      <p
        class="text-[11px] font-bold tracking-widest text-white/40 uppercase px-2 mb-3"
      >
        Main Menu
      </p>

      <router-link
        v-for="item in navItems"
        :key="item.key"
        :to="item.path"
        class="relative flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200 mb-1"
        :class="
          isActive(item.path)
            ? 'bg-blue-500/20 text-white'
            : 'text-white/70 hover:bg-white/10 hover:text-white'
        "
      >
        <span
          class="flex items-center shrink-0"
          v-html="item.icon"
        ></span>

        <span>
          {{ item.label }}
        </span>

        <span
          v-if="isActive(item.path)"
          class="absolute right-0 top-1/2 -translate-y-1/2 w-1 h-6 bg-blue-500 rounded-l"
        ></span>
      </router-link>
    </nav>

    <!-- Footer -->
    <div class="p-4 border-t border-white/10">
      <button
        @click="$emit('logout')"
        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl bg-red-500/10 text-red-300 hover:bg-red-500/20 hover:text-white transition"
      >
        <svg
          width="18"
          height="18"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
          />
        </svg>

        <span>Logout</span>
      </button>
    </div>
  </aside>
</template>
