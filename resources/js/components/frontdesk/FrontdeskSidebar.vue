<template>
  <aside 
    class="bg-[#FFFFFF] text-white h-screen fixed left-0 top-0 transition-all duration-300 ease-in-out z-40 flex flex-col border border-[rgba(107,114,128,0.25)]"
    :class="isCollapsed ? 'w-20' : 'w-64'"
  >
    <!-- Logo and System Name Area -->
    <div class="flex items-center justify-center gap-5 p-4 h-[80px]">
      <div v-if="!isCollapsed" class="flex items-center gap-2 overflow-hidden">
        <!-- System Logo -->
        <img 
          src="/storage/logos/Ligao City Seal.png" 
          class="w-12 h-12 rounded-full object-contain flex-shrink-0"
          alt="Ligao Logo"
        >
        <!-- System Name (Quennect) - hidden when collapsed -->
        <h2 v-if="!isCollapsed" class="font-bold text-xl whitespace-nowrap text-[#1F4E79]">Quennect</h2>
      </div>
      
      <!-- Hamburger Icon - Minimize/Maximize button -->
        <button 
        @click="$emit('toggle-collapse')" 
        class="p-1 rounded-lg hover:bg-[#FCFCFC] transition-colors cursor-pointer"
        >
        <svg xmlns="http://www.w3.org/2000/svg" 
            class="h-8 w-8" 
            fill="none" 
            viewBox="0 0 24 24" 
            stroke="#0F5C5C">
            <path stroke-linecap="round" 
                stroke-linejoin="round" 
                stroke-width="2" 
                d="M4 6h16M4 12h16M4 18h16" />
        </svg>
        </button>
    </div>
    
    <!-- Navigation Menu - grows to take available space -->
    <nav class="flex-1 px-4 py-1">
      <ul class="space-y-2">
        <!-- Queue Dashboard Button -->
        <li class="relative group">
        <button 
            @click="navigateTo('/frontdesk')"
            class="w-full flex items-center gap-3 p-3 rounded-md transition-colors cursor-pointer"
            :class="{
            'bg-[#0F5C5C] text-white': isActive('/frontdesk'),        // active page
            'hover:bg-[#F5F5F5] text-[#474C55]': !isActive('/frontdesk') // only hover if not active
            }"
        >
            <!-- Dashboard Icon -->
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>

            <span v-if="!isCollapsed" class="text-sm whitespace-nowrap ">Queue Dashboard</span>
        </button>

        <!-- Tooltip -->
        <div
          v-if="isCollapsed"
          class="absolute left-16 top-1/2 -translate-y-1/2 whitespace-nowrap 
                bg-[#0F5C5C] text-white text-sm px-3 py-1 rounded-md shadow-md
                opacity-0 group-hover:opacity-100 transition pointer-events-none"
        >
          Queue Dashboard
        </div>
        </li>

        <!-- Queue Analytics Button -->
        <li class="relative group">
        <button 
            @click="navigateTo('/frontdesk/analytics')"
            class="w-full flex items-center gap-3 p-3 rounded-md transition-colors cursor-pointer"
            :class="{
            'bg-[#0F5C5C] text-white': isActive('/frontdesk/analytics'),        // active page
            'hover:bg-[#F5F5F5] text-[#474C55]': !isActive('/frontdesk/analytics') // only hover if not active
            }"
        >
            <!-- Analytics Icon -->
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>

            <span v-if="!isCollapsed" class="text-sm whitespace-nowrap">Queue Analytics</span>
        </button>

        <!-- Tooltip -->
        <div
          v-if="isCollapsed"
          class="absolute left-16 top-1/2 -translate-y-1/2 whitespace-nowrap 
                bg-[#0F5C5C] text-white text-sm px-3 py-1 rounded-md shadow-md
                opacity-0 group-hover:opacity-100 transition pointer-events-none"
        >
          Queue Analytics
        </div>
        </li>
      </ul>
    </nav>
    
    <!-- User Info and Logout Section - fixed at bottom -->
    <div class="p-4">
      <!-- Account Username (hardcoded for now) -->
      <div class="flex items-center gap-3 mb-3" :class="{ 'justify-center': isCollapsed }">
        <!-- Username - hidden when collapsed -->
        <div v-if="!isCollapsed" class="px-3 overflow-hidden">
          <p class="text-md font-medium text-[#474C55] truncate">{{ userData?.username || 'Loading...' }}</p>
        </div>
      </div>
      
      <!-- Office Name (hardcoded for now) - hidden when collapsed -->
      <div v-if="!isCollapsed" class="px-3 mb-4 px-2 relative group">
        <p class="text-xs text-[#1F4E79]">Office</p>
        <p class="text-sm text-[#474C55] truncate">{{ userData?.office?.name || 'No Office Assigned' }}</p>
        <!-- Tooltip -->
        <div
          class="absolute left-0 top-full mt-1 max-w-[220px] whitespace-normal
              bg-[#0F5C5C] text-white text-sm px-3 py-1 rounded-md shadow-md
                opacity-0 group-hover:opacity-100 transition pointer-events-none z-50"
        >
          {{ userData?.office?.name || 'No Office Assigned' }}
        </div>
      </div>

      <div class="border-t border-gray-200 w-11/12 mx-auto mb-4"></div>
      
      <!-- Logout Button -->
      <button 
        @click="handleLogout"
        class="w-full flex items-center gap-3 p-3 rounded-lg hover:bg-[#F5F5F5] transition-colors text-[#474C55] cursor-pointer relative group"
        :class="{ 'justify-center': isCollapsed}"
      >
        <!-- Logout Icon -->
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
        </svg>
        <span v-if="!isCollapsed" class="text-sm whitespace-nowrap">Log Out</span>
        <!-- Tooltip -->
        <div
          v-if="isCollapsed"
          class="absolute left-16 top-1/2 -translate-y-1/2 whitespace-nowrap 
                bg-[#0F5C5C] text-white text-sm px-3 py-1 rounded-md shadow-md
                opacity-0 group-hover:opacity-100 transition pointer-events-none"
        >
          Log Out
        </div>
      </button>
    </div>
  </aside>
</template>

<script>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'

export default {
  name: 'FrontdeskSidebar',
  props: {
    isCollapsed: {
      type: Boolean,
      default: false
    },
    // Add this new prop
    userData: {
      type: Object,
      default: () => null
    }
  },
  emits: ['toggle-collapse', 'logout'],
  setup(props, { emit }) {
    const router = useRouter()
    const route = useRoute()
    
    // Check if a path is active
    const isActive = (path) => {
      return route.path === path
    }
    
    // Navigate to a route
    const navigateTo = (path) => {
      router.push(path)
    }
    
    // Handle logout
    const handleLogout = () => {
      emit('logout')
      // The actual logout logic will be handled by the layout
    }
    
    return {
      isActive,
      navigateTo,
      handleLogout
    }
  }
}
</script>