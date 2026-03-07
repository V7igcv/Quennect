<template>
  <div class="min-h-screen bg-[#FCFCFC]">
    <!-- Sidebar -->
    <SuperadminSidebar 
      :is-collapsed="sidebarCollapsed"
      :user-data="currentUser"
      @toggle-collapse="toggleSidebar"
      @logout="handleLogout"
    />
    
    <!-- Main content -->
    <div class="transition-all duration-300" :class="sidebarCollapsed ? 'lg:ml-20' : 'lg:ml-64'">
      <!-- Header -->
      <Header />
      
      <!-- Mobile sidebar overlay (for small screens) -->
      <div 
        v-if="mobileSidebarOpen" 
        class="fixed inset-0 bg-black bg-opacity-50 z-30 lg:hidden"
        @click="mobileSidebarOpen = false"
      ></div>
      
      <!-- Mobile sidebar (for small screens) -->
      <div 
        v-if="mobileSidebarOpen" 
        class="fixed inset-y-0 left-0 z-40 lg:hidden"
      >
        <SuperadminSidebar 
          :is-collapsed="false"
          :user-data="currentUser"
          @toggle-collapse="mobileSidebarOpen = false"
          @logout="handleLogout"
        />
      </div>
      
      <!-- Page Content -->
      <main class="p-6">
        <router-view />
      </main>
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
    
    const handleLogout = async () => {
      try {
        await authService.logout()
        router.push('/login')
      } catch (error) {
        console.error('Logout failed:', error)
        router.push('/login')
      }
    }
    
    return {
      sidebarCollapsed,
      mobileSidebarOpen,
      currentUser,
      toggleSidebar,
      toggleMobileSidebar,
      handleLogout
    }
  }
}
</script>