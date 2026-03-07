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
        <!-- Queue Analytics Button -->
        <li class="relative group">
        <button 
            @click="navigateTo('/superadmin')"
            class="w-full flex items-center gap-3 p-3 rounded-md transition-colors cursor-pointer"
            :class="{
            'bg-[#0F5C5C] text-white': isActive('/superadmin'),        // active page
            'hover:bg-[#F5F5F5] text-[#474C55]': !isActive('/superadmin') // only hover if not active
            }"
        >
            <!-- Analytics Icon -->
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>

            <span v-if="!isCollapsed" class="text-sm whitespace-nowrap ">Queue Analytics</span>
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

        <!-- Office Management Button -->
        <li class="relative group">
        <button 
            @click="navigateTo('/superadmin/offices')"
            class="w-full flex items-center gap-3 p-3 rounded-md transition-colors cursor-pointer"
            :class="{
            'bg-[#0F5C5C] text-white': isActive('/superadmin/offices'),        // active page
            'hover:bg-[#F5F5F5] text-[#474C55]': !isActive('/superadmin/offices') // only hover if not active
            }"
        >
            <!-- Office Management Icon -->
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/>
            </svg>

            <span v-if="!isCollapsed" class="text-sm whitespace-nowrap ">Office Management</span>
        </button>

        <!-- Tooltip -->
        <div
          v-if="isCollapsed"
          class="absolute left-16 top-1/2 -translate-y-1/2 whitespace-nowrap 
                bg-[#0F5C5C] text-white text-sm px-3 py-1 rounded-md shadow-md
                opacity-0 group-hover:opacity-100 transition pointer-events-none"
        >
          Office Management
        </div>
        </li>

        <!-- User Management Button -->
        <li class="relative group">
        <button 
            @click="navigateTo('/superadmin/users')"
            class="w-full flex items-center gap-3 p-3 rounded-md transition-colors cursor-pointer"
            :class="{
            'bg-[#0F5C5C] text-white': isActive('/superadmin/users'),        // active page
            'hover:bg-[#F5F5F5] text-[#474C55]': !isActive('/superadmin/users') // only hover if not active
            }"
        >
            <!-- User Management Icon -->
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>

            <span v-if="!isCollapsed" class="text-sm whitespace-nowrap">User Management</span>
        </button>

        <!-- Tooltip -->
        <div
          v-if="isCollapsed"
          class="absolute left-16 top-1/2 -translate-y-1/2 whitespace-nowrap 
                bg-[#0F5C5C] text-white text-sm px-3 py-1 rounded-md shadow-md
                opacity-0 group-hover:opacity-100 transition pointer-events-none"
        >
          User Management
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
        <p class="text-sm text-[#474C55] truncate">Super Admin</p>
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
  name: 'SuperadminSidebar',
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