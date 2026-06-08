<script setup>
defineProps({
  user: {
    type: Object,
    default: null
  },
  dropdownOpen: {
    type: Boolean,
    default: false
  }
})

defineEmits(['toggle-dropdown', 'logout'])
</script>

<template>
  <nav class="bg-white border-b border-slate-100 shadow-sm px-6 py-3 fixed top-0 left-0 w-full z-50">
    <div class="w-full flex items-center">
      <div class="flex items-center gap-4">
        <div style="width: 36px; height: 36px; margin-right: 12px; background-color: #000000; color: #ffffff; border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.25); transform: rotate(3deg); flex-shrink: 0;">
          <svg width="24" height="24" fill="none" stroke="#ffffff" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
          </svg>
        </div>
        <span style="font-size: 1.4rem; font-weight: 900;" class="tracking-tight text-slate-800">To do App</span>
      </div>

      <div class="relative ml-auto pl-4" id="profile-dropdown">
        <button
          @click="$emit('toggle-dropdown')"
          class="flex items-center justify-center px-5 py-2.5 bg-white hover:bg-slate-50 text-black font-black rounded-[16px] transition-all duration-200 border border-slate-200 hover:border-slate-300 focus:outline-none shadow-sm cursor-pointer tracking-wide"
          style="font-size: 1.15rem;"
        >
          Profile
        </button>

        <div
          v-show="dropdownOpen"
          class="absolute right-0 mt-2 w-52 bg-white border border-slate-100 rounded-2xl shadow-xl py-2 z-50 animate-in fade-in slide-in-from-top-2 duration-200"
        >
          <div class="px-4 py-3 border-b border-slate-50 mb-1">
            <p style="font-size: 0.8rem;" class="text-slate-400 font-medium uppercase tracking-wider mb-0.5">Signed in as</p>
            <p style="font-size: 1rem;" class="font-bold text-slate-800 truncate">{{ user?.name || 'Loading...' }}</p>
            <p style="font-size: 0.9rem;" class="text-slate-500 truncate">{{ user?.email || '' }}</p>
          </div>
          <div class="px-2">
            <button
              @click="$emit('logout')"
              class="w-full text-left px-3 py-2.5 text-red-600 font-semibold hover:bg-red-50 rounded-xl transition-colors flex items-center gap-3 group cursor-pointer" style="font-size: 1rem;"
            >
              <div class="w-8 h-8 rounded-lg bg-red-50 flex items-center justify-center group-hover:bg-red-100 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
              </div>
              Logout
            </button>
          </div>
        </div>
      </div>
    </div>
  </nav>
</template>
