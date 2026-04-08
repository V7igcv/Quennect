<template>
  <aside 
    class="bg-[#FFFFFF] text-white h-screen fixed left-0 top-0 z-40 flex flex-col overflow-visible border border-[rgba(107,114,128,0.25)] transition-[width] duration-300 ease-in-out"
    :class="isCollapsed ? 'w-20' : 'w-64'"
  >
    <!-- Logo and System Name Area -->
    <div
      class="flex items-center h-20 transition-all duration-200 ease-in-out"
      :class="isCollapsed ? 'justify-center px-0 gap-0' : 'justify-center gap-5 p-4'"
    >
      <div
        class="flex items-center gap-2 overflow-hidden transition-all duration-200 ease-in-out"
        :class="isCollapsed ? 'max-w-0 opacity-0' : 'max-w-40 opacity-100'"
      >
        <img 
          src="/storage/logos/Ligao City Seal.png" 
          class="w-12 h-12 rounded-full object-contain shrink-0"
          alt="Ligao Logo"
        >
        <h2 class="font-bold text-xl whitespace-nowrap text-[#1F4E79]">Quennect</h2>
      </div>
      
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
    
    <!-- Navigation Menu -->
    <nav class="flex-1 px-4 py-1">
      <ul class="space-y-2">
        <!-- Queue Dashboard Button -->
        <li class="relative group">
          <button 
            @click="navigateTo('/frontdesk')"
            class="w-full flex items-center gap-3 p-3 rounded-md transition-colors cursor-pointer"
            :class="{
              'bg-[#0F5C5C] text-white': isActive('/frontdesk'),
              'hover:bg-[#F5F5F5] text-[#474C55]': !isActive('/frontdesk')
            }"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            <span
              class="overflow-hidden text-sm whitespace-nowrap transition-all duration-200 ease-in-out"
              :class="isCollapsed ? 'max-w-0 opacity-0' : 'max-w-40 opacity-100'"
            >Queue Dashboard</span>
          </button>
          <div
            v-if="isCollapsed"
            class="absolute left-16 top-1/2 -translate-y-1/2 whitespace-nowrap 
                  bg-[#0F5C5C] text-white text-sm px-3 py-1 rounded-md shadow-md
                  opacity-0 group-hover:opacity-100 transition pointer-events-none z-50"
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
              'bg-[#0F5C5C] text-white': isActive('/frontdesk/analytics'),
              'hover:bg-[#F5F5F5] text-[#474C55]': !isActive('/frontdesk/analytics')
            }"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <span
              class="overflow-hidden text-sm whitespace-nowrap transition-all duration-200 ease-in-out"
              :class="isCollapsed ? 'max-w-0 opacity-0' : 'max-w-40 opacity-100'"
            >Queue Analytics</span>
          </button>
          <div
            v-if="isCollapsed"
            class="absolute left-16 top-1/2 -translate-y-1/2 whitespace-nowrap 
                  bg-[#0F5C5C] text-white text-sm px-3 py-1 rounded-md shadow-md
                  opacity-0 group-hover:opacity-100 transition pointer-events-none z-50"
          >
            Queue Analytics
          </div>
        </li>

        <!-- CSM Analytics Button -->
        <li class="relative group">
          <button
            @click="navigateTo('/frontdesk/csm-analytics')"
            class="w-full flex items-center gap-3 p-3 rounded-md transition-colors cursor-pointer"
            :class="{
              'bg-[#0F5C5C] text-white': isActive('/frontdesk/csm-analytics'),
              'hover:bg-[#F5F5F5] text-[#474C55]': !isActive('/frontdesk/csm-analytics')
            }"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-4l-4 4v-4z" />
            </svg>
            <span
              class="overflow-hidden text-sm whitespace-nowrap transition-all duration-200 ease-in-out"
              :class="isCollapsed ? 'max-w-0 opacity-0' : 'max-w-40 opacity-100'"
            >CSM Analytics</span>
          </button>
          <div
            v-if="isCollapsed"
            class="absolute left-16 top-1/2 -translate-y-1/2 whitespace-nowrap 
                  bg-[#0F5C5C] text-white text-sm px-3 py-1 rounded-md shadow-md
                  opacity-0 group-hover:opacity-100 transition pointer-events-none z-50"
          >
            CSM Analytics
          </div>
        </li>

        <!-- Internal Transactions Button -->
        <li class="relative group">
          <button
            @click="navigateTo('/frontdesk/internal-transactions')"
            class="w-full flex items-center gap-3 p-3 rounded-md transition-colors cursor-pointer"
            :class="{
              'bg-[#0F5C5C] text-white': isActive('/frontdesk/internal-transactions'),
              'hover:bg-[#F5F5F5] text-[#474C55]': !isActive('/frontdesk/internal-transactions')
            }"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6-4v12m0 0l4-4m-4 4l-4-4" />
            </svg>
            <span
              class="overflow-hidden text-sm whitespace-nowrap transition-all duration-200 ease-in-out"
              :class="isCollapsed ? 'max-w-0 opacity-0' : 'max-w-40 opacity-100'"
            >Internal Transactions</span>
          </button>
          <div
            v-if="isCollapsed"
            class="absolute left-16 top-1/2 -translate-y-1/2 whitespace-nowrap 
                  bg-[#0F5C5C] text-white text-sm px-3 py-1 rounded-md shadow-md
                  opacity-0 group-hover:opacity-100 transition pointer-events-none z-50"
          >
            Internal Transactions
          </div>
        </li>

        <!-- Divider before Chat -->
        <li class="pt-2">
          <div class="border-t border-gray-200 w-full"></div>
        </li>

        <!-- CHAT MODULE BUTTON WITH RED DOT NOTIFICATION -->
        <li class="relative group">
          <button 
            @click="navigateTo('/frontdesk/chat')"
            class="w-full flex items-center gap-3 p-3 rounded-md transition-colors cursor-pointer"
            :class="{
              'bg-[#0F5C5C] text-white': isActive('/frontdesk/chat'),
              'hover:bg-[#F5F5F5] text-[#474C55]': !isActive('/frontdesk/chat')
            }"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>

            <span
              class="overflow-hidden text-sm whitespace-nowrap transition-all duration-200 ease-in-out"
              :class="isCollapsed ? 'max-w-0 opacity-0' : 'max-w-40 opacity-100'"
            >Chat</span>

            <!-- RED DOT NOTIFICATION BADGE -->
            <span 
              v-if="chatUnreadCount > 0"
              class="absolute right-2 top-1/2 -translate-y-1/2 bg-red-500 text-white text-xs rounded-full min-w-[20px] h-5 flex items-center justify-center px-1.5 font-bold"
              :class="{ 'right-2': !isCollapsed, 'right-0': isCollapsed }"
            >
              {{ chatUnreadCount > 99 ? '99+' : chatUnreadCount }}
            </span>
          </button>

          <!-- Tooltip -->
          <div
            v-if="isCollapsed"
            class="absolute left-16 top-1/2 -translate-y-1/2 whitespace-nowrap 
                  bg-[#0F5C5C] text-white text-sm px-3 py-1 rounded-md shadow-md
                  opacity-0 group-hover:opacity-100 transition pointer-events-none z-50"
          >
            Chat
            <span v-if="chatUnreadCount > 0" class="ml-1 bg-red-500 rounded-full px-1.5">
              {{ chatUnreadCount }}
            </span>
          </div>
        </li>
      </ul>
    </nav>
    
    <!-- User Info and Logout Section -->
    <div class="p-4">
      <div class="mb-3 overflow-hidden transition-all duration-200 ease-in-out">
        <div
          class="px-2 overflow-hidden transition-all duration-200 ease-in-out"
          :class="isCollapsed ? 'max-h-0 max-w-0 opacity-0 px-0' : 'max-h-10 max-w-40 opacity-100'"
        >
          <p class="text-md font-medium text-[#474C55] truncate">{{ userData?.username || 'Loading...' }}</p>
        </div>
      </div>
      
      <div
        class="relative group overflow-hidden transition-all duration-200 ease-in-out"
        :class="isCollapsed ? 'max-h-0 opacity-0 mb-0 px-0' : 'max-h-24 opacity-100 mb-4 px-2'"
      >
        <p class="text-xs text-[#1F4E79]">Office</p>
        <p class="text-sm text-[#474C55] truncate">{{ userData?.office?.name || 'No Office Assigned' }}</p>
        <div
          class="absolute left-0 top-full mt-1 max-w-55 whitespace-normal
                bg-[#0F5C5C] text-white text-sm px-3 py-1 rounded-md shadow-md
                opacity-0 group-hover:opacity-100 transition pointer-events-none z-50"
        >
          {{ userData?.office?.name || 'No Office Assigned' }}
        </div>
      </div>

      <div class="border-t border-gray-200 w-11/12 mx-auto mb-4"></div>
      
      <button 
        @click="handleLogout"
        class="w-full flex items-center gap-3 p-3 rounded-lg hover:bg-[#F5F5F5] transition-colors text-[#474C55] cursor-pointer relative group"
        :class="{ 'justify-center': isCollapsed}"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
        </svg>
        <span
          class="overflow-hidden text-sm whitespace-nowrap transition-all duration-200 ease-in-out"
          :class="isCollapsed ? 'max-w-0 opacity-0' : 'max-w-40 opacity-100'"
        >Log Out</span>
        <div
          v-if="isCollapsed"
          class="absolute left-16 top-1/2 -translate-y-1/2 whitespace-nowrap 
                bg-[#0F5C5C] text-white text-sm px-3 py-1 rounded-md shadow-md
                opacity-0 group-hover:opacity-100 transition pointer-events-none z-50"
        >
          Log Out
        </div>
      </button>
    </div>
  </aside>
</template>

<script>
import { useRouter, useRoute } from 'vue-router'
import { ref, onMounted, onUnmounted } from 'vue'

export default {
  name: 'FrontdeskSidebar',
  props: {
    isCollapsed: {
      type: Boolean,
      default: false
    },
    userData: {
      type: Object,
      default: () => null
    }
  },
  emits: ['toggle-collapse', 'logout'],
  setup(props, { emit }) {
    const router = useRouter()
    const route = useRoute()
    
    // Chat unread count state
    const chatUnreadCount = ref(0)
    
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
    
    // Fetch unread count from API
    const fetchUnreadCount = async () => {
      try {
        const token = localStorage.getItem('token')
        if (!token) return
        
        const response = await fetch('/api/chat/unread-count', {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
          }
        })
        
        if (response.ok) {
          const data = await response.json()
          if (data.success) {
            chatUnreadCount.value = data.unread_count || 0
          }
        }
      } catch (error) {
        console.error('Failed to fetch unread count:', error)
      }
    }
    
    // Listen for new messages
    const handleNewMessage = () => {
      // Only increment if not on chat page
      if (route.path !== '/frontdesk/chat') {
        chatUnreadCount.value++
      }
    }
    
    // Reset unread count when on chat page
    const checkAndResetUnread = () => {
      if (route.path === '/frontdesk/chat' && chatUnreadCount.value > 0) {
        chatUnreadCount.value = 0
      }
    }
    
    onMounted(() => {
      fetchUnreadCount()
      
      // Listen for new message events
      window.addEventListener('new-chat-message', handleNewMessage)
      
      // Check every second if on chat page to reset count
      const interval = setInterval(checkAndResetUnread, 1000)
      
      onUnmounted(() => {
        window.removeEventListener('new-chat-message', handleNewMessage)
        clearInterval(interval)
      })
    })
    
    return {
      isActive,
      navigateTo,
      handleLogout,
      chatUnreadCount
    }
  }
}
</script>