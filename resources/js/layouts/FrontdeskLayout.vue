<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <!-- Logo -->
            <div class="flex-shrink-0 flex items-center">
              <h1 class="text-xl font-bold text-gray-900">Quennect - {{ officeName }}</h1>
            </div>
            <!-- Navigation Links -->
            <div class="ml-10 flex items-center space-x-4">
              <router-link 
                to="/frontdesk" 
                class="px-3 py-2 rounded-md text-sm font-medium"
                :class="[$route.path === '/frontdesk' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100']"
              >
                Dashboard
              </router-link>
              <router-link 
                to="/frontdesk/analytics" 
                class="px-3 py-2 rounded-md text-sm font-medium"
                :class="[$route.path === '/frontdesk/analytics' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100']"
              >
                Analytics
              </router-link>
            </div>
          </div>
          
          <!-- User Menu -->
          <div class="flex items-center">
            <span class="text-sm text-gray-700 mr-4">{{ username }}</span>
            <button 
              @click="logout" 
              class="bg-red-600 text-white px-4 py-2 rounded-md text-sm hover:bg-red-700"
            >
              Logout
            </button>
          </div>
        </div>
      </div>
    </nav>

    <!-- Page Content -->
    <main class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
      <slot />
    </main>
  </div>
</template>

<script>
import { authService } from '../services/auth'

export default {
  name: 'FrontdeskLayout',
  data() {
    return {
      username: '',
      officeName: ''
    }
  },
  mounted() {
    const user = JSON.parse(localStorage.getItem('user') || '{}')
    this.username = user.username || ''
    this.officeName = user.office?.name || ''
  },
  methods: {
    async logout() {
      try {
        await authService.logout()
        this.$router.push('/login')
      } catch (error) {
        console.error('Logout failed:', error)
      }
    }
  }
}
</script>