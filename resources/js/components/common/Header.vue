<template>
  <header class="bg-[#0F5C5C] text-white px-2 py-2 sticky top-0 z-50">
    <div class="flex justify-end gap-10 p-4">
      <!-- Left side: Date -->
      <div class="flex items-center gap-3">
        <!-- Calendar Icon -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <span class="text-md font-normal">{{ currentDate }}</span>
      </div>
      
      <!-- Right side: Time -->
      <div class="flex items-center gap-3 justify-end">
        <!-- Clock Icon -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span class="text-md font-normal tabular-nums min-w-25 text-left">{{ currentTime }}</span>

        <!-- Notifications (Frontdesk only) -->
        <div v-if="isFrontdeskUser" ref="notificationRootRef" class="relative mr-4">
          <button
            type="button"
            class="relative p-2 bg-white/15 hover:bg-white/30 rounded-full transition-colors focus:outline-none shadow-sm shrink-0 cursor-pointer"
            @click="toggleDropdown"
          >
            <Bell class="w-5 h-5 text-white" />
            <!-- Notification Badge -->
            <span
              v-if="unreadCount > 0"
              class="absolute top-0 right-0 flex h-2.5 w-2.5"
            >
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-red-500"></span>
            </span>
          </button>

          <!-- Notifications dropdown -->
          <div
            v-if="showDropdown"
            class="absolute right-0 mt-2 w-80 bg-white text-gray-900 rounded-lg shadow-lg border border-gray-200 z-50"
          >
            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
              <div>
                <p class="text-sm font-semibold text-gray-900">Notifications</p>
                <p v-if="unreadCount > 0" class="text-xs text-gray-500">{{ unreadCount }} unread</p>
                <p v-else class="text-xs text-gray-500">No unread notifications</p>
              </div>
              <button
                v-if="unreadCount > 0"
                type="button"
                class="text-xs text-[#0F5C5C] hover:underline"
                @click.stop="markAllAsRead"
              >
                Mark all as read
              </button>
            </div>

            <div class="max-h-80 overflow-y-auto">
              <div
                v-if="isLoadingNotifications"
                class="px-4 py-6 text-sm text-gray-500 text-center"
              >
                Loading notifications...
              </div>

              <template v-else>
                <button
                  v-for="notification in notifications"
                  :key="notification.id"
                  type="button"
                  class="w-full text-left px-4 py-3 hover:bg-gray-50 flex items-start gap-3 border-b border-gray-50 last:border-b-0"
                  @click.stop="openNotification(notification)"
                >
                  <span class="mt-1">
                    <span
                      v-if="!notification.is_read"
                      class="inline-flex h-2 w-2 rounded-full bg-[#DC2626]"
                    ></span>
                  </span>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900 truncate">
                      {{ notification.title }}
                    </p>
                    <p class="text-xs text-gray-600 mt-0.5 line-clamp-2">
                      {{ notification.message }}
                    </p>
                    <p class="text-[11px] text-gray-400 mt-1">
                      {{ notification.created_at_formatted || notification.created_at }}
                    </p>
                  </div>
                </button>

                <div
                  v-if="!notifications.length"
                  class="px-4 py-6 text-sm text-gray-500 text-center"
                >
                  No notifications yet.
                </div>
              </template>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Notification detail modal -->
    <div
      v-if="showModal && selectedNotification"
      class="fixed inset-0 z-[60] bg-black/40 flex items-center justify-center px-4"
      @click.self="closeModal"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-start justify-between">
          <div>
            <h3 class="text-base font-semibold text-gray-900 mt-1">
              {{ selectedNotification.title }}
            </h3>
          </div>
          <button
            type="button"
            class="text-gray-400 hover:text-gray-600"
            @click="closeModal"
          >
            <span class="sr-only">Close</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
          </button>
        </div>
        <div class="px-5 py-4">
          <p class="text-sm text-gray-700 whitespace-pre-line">
            {{ selectedNotification.message }}
          </p>
          <p
            v-if="selectedNotification.created_at || selectedNotification.created_at_formatted"
            class="text-xs text-gray-400 mt-3"
          >
            Sent {{ selectedNotification.created_at_formatted || selectedNotification.created_at }}
          </p>
        </div>
        <div class="px-5 py-3 bg-gray-50 flex justify-end">
          <button
            type="button"
            class="px-4 py-2 text-sm font-medium text-white bg-[#0F5C5C] rounded-md hover:bg-[#0C4747]"
            @click="closeModal"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </header>
</template>

<script>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { Bell } from 'lucide-vue-next';
import api from '../../services/api'

export default {
  name: 'Header',
  components: {
    Bell
  },
  props: {
    user: {
      type: Object,
      default: null
    }
  },
  setup(props) {
    const currentDate = ref('')
    const currentTime = ref('')
    let timerInterval = null
    let unreadPollingInterval = null

    const notifications = ref([])
    const unreadCount = ref(0)
    const isLoadingNotifications = ref(false)
    const showDropdown = ref(false)
    const showModal = ref(false)
    const selectedNotification = ref(null)
    const notificationRootRef = ref(null)

    const isFrontdeskUser = computed(() => {
      return props.user && props.user.role === 'OFFICE FRONTDESK'
    })

    const notificationChannelName = ref(null)
    
    const updateDateTime = () => {
      const now = new Date()
      
      // Format date: May 7, 2025 | Wednesday
      const options = { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        weekday: 'long'
      }
      const dateStr = now.toLocaleDateString('en-US', options)
      // Replace comma with " | " between date and weekday
      currentDate.value = dateStr.replace(', ', ' | ')
      
      // Format time: HH:MM:SS AM/PM
      currentTime.value = now.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true
      })
    }

    const fetchUnreadCount = async () => {
      if (!isFrontdeskUser.value) return
      try {
        const response = await api.get('/frontdesk/internal-transactions/notifications/unread-count')
        unreadCount.value = response.data?.data?.count ?? 0
      } catch (error) {
        console.error('Failed to fetch unread notifications count:', error)
      }
    }

    const fetchNotifications = async ({ showLoading = true } = {}) => {
      if (!isFrontdeskUser.value) return
      if (showLoading) {
        isLoadingNotifications.value = true
      }
      try {
        const response = await api.get('/frontdesk/internal-transactions/notifications', {
          params: { per_page: 10 }
        })
        const payload = response.data?.data
        const paginator = payload?.notifications
        const items = Array.isArray(paginator?.data) ? paginator.data : Array.isArray(paginator) ? paginator : []
        notifications.value = items
        unreadCount.value = payload?.unread_count ?? unreadCount.value
      } catch (error) {
        console.error('Failed to fetch notifications:', error)
      } finally {
        if (showLoading) {
          isLoadingNotifications.value = false
        }
      }
    }

    const toggleDropdown = async () => {
      if (!showDropdown.value) {
        await fetchNotifications({ showLoading: true })
      }
      showDropdown.value = !showDropdown.value
    }

    const markAsRead = async (notification) => {
      if (!isFrontdeskUser.value || !notification) return
      try {
        await api.patch(`/frontdesk/internal-transactions/notifications/${notification.id}/read`)
        // After marking as read, refresh notifications and unread count without showing loader
        await fetchNotifications({ showLoading: false })
        await fetchUnreadCount()
      } catch (error) {
        console.error('Failed to mark notification as read:', error)
      }
    }

    const openNotification = async (notification) => {
      // Optimistically update UI so the red dot and badge
      // disappear immediately when the user clicks
      if (!notification.is_read) {
        notification.is_read = true
        if (unreadCount.value > 0) {
          unreadCount.value -= 1
        }
      }

      showDropdown.value = false
      selectedNotification.value = notification
      showModal.value = true
      await markAsRead(notification)
    }

    const closeModal = () => {
      showModal.value = false
      selectedNotification.value = null
    }

    const handleDocumentClick = (event) => {
      if (!showDropdown.value) {
        return
      }

      const root = notificationRootRef.value
      if (root && !root.contains(event.target)) {
        showDropdown.value = false
      }
    }

    const markAllAsRead = async () => {
      if (!isFrontdeskUser.value || unreadCount.value === 0) return
      try {
        await api.patch('/frontdesk/internal-transactions/notifications/read-all')
        // Refresh list and unread count to reflect the changes without showing loader
        await fetchNotifications({ showLoading: false })
        await fetchUnreadCount()
      } catch (error) {
        console.error('Failed to mark all notifications as read:', error)
      }
    }

    const subscribeToNotificationChannel = () => {
      if (!window.Echo || !props.user || !isFrontdeskUser.value) {
        return
      }

      const rawUser = props.user || {}
      const officeId =
        rawUser.office_id ??
        rawUser.officeId ??
        rawUser.office?.id ??
        null

      if (!officeId) {
        console.warn('Header: Unable to resolve office_id for notifications subscription.')
        return
      }

      const channelName = `internal.notifications.office.${officeId}`
      notificationChannelName.value = channelName

      window.Echo
        .channel(channelName)
        .listen('.internal.notifications.created', (event) => {
          const payload = event || {}
          const newNotification = payload.notification
          const newUnreadCount = payload.unread_count

          if (typeof window !== 'undefined') {
            window.dispatchEvent(new CustomEvent('internal-notification-created', { detail: payload }))
          }

          if (newNotification) {
            notifications.value.unshift(newNotification)
            if (notifications.value.length > 10) {
              notifications.value.pop()
            }
          }

          if (typeof newUnreadCount === 'number') {
            unreadCount.value = newUnreadCount
          } else {
            unreadCount.value += 1
          }
        })
        .error((socketError) => {
          console.error('Notification websocket error:', socketError)
        })
    }

    const unsubscribeFromNotificationChannel = () => {
      if (notificationChannelName.value && window.Echo) {
        window.Echo.leave(notificationChannelName.value)
        notificationChannelName.value = null
      }
    }
    
    onMounted(() => {
      updateDateTime()
      // Update every second
      timerInterval = setInterval(updateDateTime, 1000)
      document.addEventListener('click', handleDocumentClick)

       if (isFrontdeskUser.value) {
         fetchUnreadCount()
         unreadPollingInterval = setInterval(fetchUnreadCount, 60000)
         subscribeToNotificationChannel()
       }
    })
    
    onUnmounted(() => {
      if (timerInterval) {
        clearInterval(timerInterval)
      }
      if (unreadPollingInterval) {
        clearInterval(unreadPollingInterval)
      }
      document.removeEventListener('click', handleDocumentClick)
      unsubscribeFromNotificationChannel()
    })
    
    return {
      currentDate,
      currentTime,
      notifications,
      unreadCount,
      isLoadingNotifications,
      showDropdown,
      showModal,
      selectedNotification,
      notificationRootRef,
      isFrontdeskUser,
      subscribeToNotificationChannel,
      toggleDropdown,
      openNotification,
      closeModal,
      markAllAsRead
    }
  }
}
</script>