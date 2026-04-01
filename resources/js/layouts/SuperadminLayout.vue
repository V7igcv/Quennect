<template>
  <div class="min-h-screen bg-[#FCFCFC]">
    <!-- Desktop Sidebar -->
    <div class="hidden lg:block">
      <SuperadminSidebar 
        :is-collapsed="sidebarCollapsed"
        :user-data="currentUser"
        @toggle-collapse="toggleSidebar"
        @logout="openLogoutConfirmModal"
      />
    </div>
    
    <!-- Main content -->
    <div class="transition-all duration-300" :class="sidebarCollapsed ? 'lg:ml-20' : 'lg:ml-64'">
      <!-- Mobile menu button -->
      <button
        class="lg:hidden fixed top-3 left-3 z-60 bg-[#0F5C5C] text-white p-2 rounded-md shadow-md"
        @click="toggleMobileSidebar"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>

      <!-- Header -->
      <Header :user="currentUser" />
      
      <!-- Mobile sidebar overlay (for small screens) -->
      <Transition name="mobile-overlay-fade">
        <div 
          v-if="mobileSidebarOpen" 
          class="fixed inset-0 bg-black/50 z-50 lg:hidden"
          @click="mobileSidebarOpen = false"
        ></div>
      </Transition>
      
      <!-- Mobile sidebar (for small screens) -->
      <Transition name="mobile-sidebar-slide">
        <div 
          v-if="mobileSidebarOpen" 
          class="fixed inset-y-0 left-0 z-60 lg:hidden"
        >
          <SuperadminSidebar 
            :is-collapsed="false"
            :user-data="currentUser"
            @toggle-collapse="mobileSidebarOpen = false"
            @logout="openLogoutConfirmModal"
          />
        </div>
      </Transition>
      
      <!-- Page Content -->
      <main class="p-6">
        <router-view />
      </main>

      <div v-if="showLogoutConfirmModal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-60 px-4">
        <div class="bg-white rounded-lg shadow-lg p-6 max-w-sm w-full">
          <h3 class="text-lg font-semibold mb-2">Log out?</h3>
          <p class="text-gray-600 mb-4">Are you sure you want to log out of your account?</p>

          <div class="flex justify-end gap-2">
            <button
              type="button"
              class="px-4 py-2 border rounded-md hover:bg-gray-100"
              :disabled="isLoggingOut"
              @click="showLogoutConfirmModal = false"
            >
              Cancel
            </button>
            <button
              type="button"
              class="px-4 py-2 bg-[#DC2626] text-white rounded-md hover:bg-[#B91C1C]"
              :disabled="isLoggingOut"
              @click="handleLogout"
            >
              {{ isLoggingOut ? 'Logging out...' : 'Log out' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue' // Add onMounted
import { useRouter } from 'vue-router'
import Header from '../components/common/Header.vue'
import SuperadminSidebar from '../components/superadmin/SuperadminSidebar.vue'
import { authService } from '../services/auth'

export default {
  name: 'SuperadminLayout',
  components: {
    Header,
    SuperadminSidebar
  },
  setup() {
    const router = useRouter()
    const sidebarCollapsed = ref(false)
    const mobileSidebarOpen = ref(false)
    const isRefreshing = ref(false)
    const showLogoutConfirmModal = ref(false)
    const isLoggingOut = ref(false)

    // Initialize with stored user data immediately - this prevents "Loading..." flash
    const currentUser = ref(authService.getCurrentUser())

    const fetchUserData = async () => {
      // Only fetch if we have a token (user is authenticated)
      if (!authService.isAuthenticated()) {
        return
      }

      isRefreshing.value = true
      try {
        // This will get fresh data from the server
        const freshUserData = await authService.getUser()
        
        // Update localStorage with fresh data
        localStorage.setItem('user', JSON.stringify(freshUserData))
        
        // Update the reactive reference
        currentUser.value = freshUserData
        
        console.log('User data refreshed:', freshUserData) // For debugging
      } catch (error) {
        console.error('Failed to refresh user data:', error)
        
        // If we get a 401, redirect to login
        if (error.response?.status === 401) {
          router.push('/login')
        }
      } finally {
        isRefreshing.value = false
      }
    }

    // Fetch fresh data on mount (but the UI already has the stored data)
    onMounted(() => {
      fetchUserData()
    })
    
    const toggleSidebar = () => {
      sidebarCollapsed.value = !sidebarCollapsed.value
    }
    
    const toggleMobileSidebar = () => {
      mobileSidebarOpen.value = !mobileSidebarOpen.value
    }

    const openLogoutConfirmModal = () => {
      mobileSidebarOpen.value = false
      showLogoutConfirmModal.value = true
    }
    
    const handleLogout = async () => {
      if (isLoggingOut.value) {
        return
      }

      isLoggingOut.value = true

      try {
        await authService.logout()
        router.push('/login')
      } catch (error) {
        console.error('Logout failed:', error)
        router.push('/login')
      } finally {
        isLoggingOut.value = false
        showLogoutConfirmModal.value = false
      }
    }
    
    return {
      sidebarCollapsed,
      mobileSidebarOpen,
      isRefreshing,
      showLogoutConfirmModal,
      isLoggingOut,
      currentUser,
      toggleSidebar,
      toggleMobileSidebar,
      openLogoutConfirmModal,
      handleLogout
    }
  }
}
</script>

<style scoped>
.mobile-overlay-fade-enter-active,
.mobile-overlay-fade-leave-active {
  transition: opacity 0.2s ease;
}

.mobile-overlay-fade-enter-from,
.mobile-overlay-fade-leave-to {
  opacity: 0;
}

.mobile-sidebar-slide-enter-active,
.mobile-sidebar-slide-leave-active {
  transition: transform 0.25s ease;
}

.mobile-sidebar-slide-enter-from,
.mobile-sidebar-slide-leave-to {
  transform: translateX(-100%);
}
</style>