<template>
  <div class="min-h-screen bg-[#FCFCFC]">
    <div class="hidden lg:block">
      <Sidebar
        :is-collapsed="sidebarCollapsed"
        :user-data="currentUser"
        role="city_mayor"
        @toggle-collapse="toggleSidebar"
        @logout="openLogoutConfirmModal"
      />
    </div>

    <div class="transition-all duration-300" :class="sidebarCollapsed ? 'lg:ml-20' : 'lg:ml-64'">
      <button
        class="lg:hidden fixed top-3 left-3 z-60 bg-[#0F5C5C] text-white p-2 rounded-md shadow-md"
        @click="toggleMobileSidebar"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>

      <Header :user="currentUser" />

      <Transition name="mobile-overlay-fade">
        <div
          v-if="mobileSidebarOpen"
          class="fixed inset-0 bg-black/50 z-50 lg:hidden"
          @click="mobileSidebarOpen = false"
        ></div>
      </Transition>

      <Transition name="mobile-sidebar-slide">
        <div
          v-if="mobileSidebarOpen"
          class="fixed inset-y-0 left-0 z-60 lg:hidden"
        >
          <Sidebar
            :is-collapsed="false"
            :user-data="currentUser"
            role="city_mayor"
            @toggle-collapse="mobileSidebarOpen = false"
            @logout="openLogoutConfirmModal"
          />
        </div>
      </Transition>

      <main class="p-6">
        <router-view />
      </main>

      <SessionExpiredModal :visible="showSessionExpiredModal" @login="handleLogout" />

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
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import Header from '../components/common/Header.vue'
import Sidebar from '../components/common/Sidebar.vue'
import SessionExpiredModal from '../components/modals/SessionExpiredModal.vue'
import { authService } from '../services/auth'
import { useIdleSessionTimeout } from '../composables/useIdleSessionTimeout'

export default {
  name: 'CityMayorLayout',
  components: {
    Header,
    Sidebar,
    SessionExpiredModal,
  },
  setup() {
    const router = useRouter()
    const sidebarCollapsed = ref(false)
    const mobileSidebarOpen = ref(false)
    const isRefreshing = ref(false)
    const showLogoutConfirmModal = ref(false)
    const showSessionExpiredModal = ref(false)
    const isLoggingOut = ref(false)

    const currentUser = ref(authService.getCurrentUser())

    const fetchUserData = async () => {
      if (!authService.isAuthenticated()) {
        return
      }

      isRefreshing.value = true
      try {
        const freshUserData = await authService.getUser()
        localStorage.setItem('user', JSON.stringify(freshUserData))
        currentUser.value = freshUserData
      } catch (error) {
        console.error('Failed to refresh user data:', error)

        if (error.response?.status === 401) {
          router.push('/login')
        }
      } finally {
        isRefreshing.value = false
      }
    }

    onMounted(() => {
      fetchUserData()
    })

    useIdleSessionTimeout({
      timeout: 30 * 60 * 1000,
      onExpire: () => {
        showSessionExpiredModal.value = true
      },
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
        showSessionExpiredModal.value = false
      }
    }

    return {
      sidebarCollapsed,
      mobileSidebarOpen,
      isRefreshing,
      showLogoutConfirmModal,
      showSessionExpiredModal,
      isLoggingOut,
      currentUser,
      toggleSidebar,
      toggleMobileSidebar,
      openLogoutConfirmModal,
      handleLogout,
    }
  },
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
