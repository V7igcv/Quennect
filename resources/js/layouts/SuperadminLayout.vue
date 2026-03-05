<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <div class="flex-shrink-0 flex items-center">
              <h1 class="text-xl font-bold text-gray-900">Quennect - Super Admin</h1>
            </div>
            <div class="ml-10 flex items-center space-x-4">
              <router-link 
                to="/superadmin" 
                class="px-3 py-2 rounded-md text-sm font-medium"
                :class="[$route.path === '/superadmin' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100']"
              >
                Dashboard
              </router-link>
              <router-link 
                to="/superadmin/offices" 
                class="px-3 py-2 rounded-md text-sm font-medium"
                :class="[$route.path === '/superadmin/offices' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100']"
              >
                Offices
              </router-link>
              <router-link 
                to="/superadmin/users" 
                class="px-3 py-2 rounded-md text-sm font-medium"
                :class="[$route.path === '/superadmin/users' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100']"
              >
                Users
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
  name: 'SuperadminLayout',
  data() {
    return {
      username: ''
    }
  },
  mounted() {
    const user = JSON.parse(localStorage.getItem('user') || '{}')
    this.username = user.username || ''
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