<template>
  <aside
    class="bg-[#FFFFFF] text-white h-screen fixed left-0 top-0 z-40 flex flex-col overflow-visible border border-[rgba(107,114,128,0.25)] transition-[width] duration-300 ease-in-out"
    :class="isCollapsed ? 'w-20' : 'w-64'"
  >
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
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="#0F5C5C">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>
    </div>

    <nav class="flex-1 px-4 py-1">
      <ul class="space-y-2">
        <template v-for="item in navItems" :key="item.path">
          <li v-if="role === 'frontdesk' && item.path === '/frontdesk/chat'" class="pt-2">
            <div class="border-t border-gray-200 w-full"></div>
          </li>

          <li class="relative group">
          <button
            @click="navigateTo(item.path)"
            class="w-full flex items-center gap-3 p-3 rounded-md transition-colors cursor-pointer"
            :class="{
              'bg-[#0F5C5C] text-white': isActive(item),
              'hover:bg-[#F5F5F5] text-[#474C55]': !isActive(item)
            }"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.iconPath" />
            </svg>

            <span
              class="overflow-hidden text-sm whitespace-nowrap transition-all duration-200 ease-in-out"
              :class="isCollapsed ? 'max-w-0 opacity-0' : 'max-w-40 opacity-100'"
            >{{ item.label }}</span>

            <span
              v-if="item.path === '/frontdesk/chat' && chatUnreadCount > 0"
              class="absolute right-2 top-1/2 -translate-y-1/2 bg-red-500 text-white text-xs rounded-full min-w-[20px] h-5 flex items-center justify-center px-1.5 font-bold"
              :class="{ 'right-2': !isCollapsed, 'right-0': isCollapsed }"
            >
              {{ chatUnreadCount > 99 ? '99+' : chatUnreadCount }}
            </span>
          </button>

          <div
            v-if="isCollapsed"
            class="absolute left-16 top-1/2 -translate-y-1/2 whitespace-nowrap bg-[#0F5C5C] text-white text-sm px-3 py-1 rounded-md shadow-md opacity-0 group-hover:opacity-100 transition pointer-events-none z-50"
          >
            {{ item.label }}
            <span v-if="item.path === '/frontdesk/chat' && chatUnreadCount > 0" class="ml-1 bg-red-500 rounded-full px-1.5">
              {{ chatUnreadCount }}
            </span>
          </div>
          </li>
        </template>
      </ul>
    </nav>

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
        <template v-if="role === 'frontdesk'">
          <p class="text-xs text-[#1F4E79]">Office</p>
          <p class="text-sm text-[#474C55] truncate">{{ userData?.office?.name || 'No Office Assigned' }}</p>
          <div
            class="absolute left-0 top-full mt-1 max-w-55 whitespace-normal bg-[#0F5C5C] text-white text-sm px-3 py-1 rounded-md shadow-md opacity-0 group-hover:opacity-100 transition pointer-events-none z-50"
          >
            {{ userData?.office?.name || 'No Office Assigned' }}
          </div>
        </template>

        <template v-else-if="role === 'superadmin'">
          <p class="text-sm text-[#474C55] truncate">Super Admin</p>
        </template>

        <template v-else-if="role === 'cswdo'">
          <p class="text-sm text-[#474C55] truncate">CSWDO Focal</p>
        </template>

        <template v-else-if="role === 'hrmo'">
          <p class="text-sm text-[#474C55] truncate">HRMO Focal</p>
        </template>

        <template v-else>
          <p class="text-sm text-[#474C55] truncate">City Mayor</p>
        </template>
      </div>

      <div class="border-t border-gray-200 w-11/12 mx-auto mb-4"></div>

      <button
        @click="handleLogout"
        class="w-full flex items-center gap-3 p-3 rounded-lg hover:bg-[#F5F5F5] transition-colors text-[#474C55] cursor-pointer relative group"
        :class="{ 'justify-center': isCollapsed }"
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
          class="absolute left-16 top-1/2 -translate-y-1/2 whitespace-nowrap bg-[#0F5C5C] text-white text-sm px-3 py-1 rounded-md shadow-md opacity-0 group-hover:opacity-100 transition pointer-events-none z-50"
        >
          Log Out
        </div>
      </button>
    </div>
  </aside>
</template>

<script>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '../../services/api'

export default {
  name: 'Sidebar',
  props: {
    isCollapsed: {
      type: Boolean,
      default: false,
    },
    userData: {
      type: Object,
      default: () => null,
    },
    role: {
      type: String,
      default: 'frontdesk',
      validator: (value) => ['frontdesk', 'superadmin', 'city_mayor', 'cswdo', 'hrmo'].includes(value),
    },
  },
  emits: ['toggle-collapse', 'logout'],
  setup(props, { emit }) {
    const router = useRouter()
    const route = useRoute()
    const chatUnreadCount = ref(0)
    let resetUnreadInterval = null
    let unreadSyncInterval = null
    const chatChannelName = ref(null)

    const navItems = computed(() => {
      if (props.role === 'hrmo') {
        return [
          {
            label: 'CSM Analytics',
            path: '/hrmo-focal',
            iconPath: 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-4l-4 4v-4z',
          }
        ]
      }
      if (props.role === 'cswdo') {
        return [
          {
            label: 'AICS Analytics',
            path: '/cswdo-focal',
            iconPath: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
          }
        ]
      }
      if (props.role === 'superadmin') {
        return [
          {
            label: 'Queue Analytics',
            path: '/superadmin',
            iconPath: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
          },
          {
            label: 'Office Management',
            path: '/superadmin/offices',
            iconPath: 'M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2',
            startsWith: true,
          },
          {
            label: 'User Management',
            path: '/superadmin/users',
            iconPath: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
          },
        ]
      }

      if (props.role === 'city_mayor') {
        return [
          {
            label: 'Queue Analytics',
            path: '/city-mayor',
            iconPath: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
          },
          {
            label: 'Office Efficiency',
            path: '/city-mayor/office-service-efficiency',
            iconPath: 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-4l-4 4v-4z',
          },
        ]
      }

      return [
        {
          label: 'Queue Dashboard',
          path: '/frontdesk',
          iconPath: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
        },
        {
          label: 'Queue Analytics',
          path: '/frontdesk/analytics',
          iconPath: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        },
        {
          label: 'CSM Analytics',
          path: '/frontdesk/csm-analytics',
          iconPath: 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-4l-4 4v-4z',
        },
        {
          label: 'Internal Transactions',
          path: '/frontdesk/internal-transactions',
          iconPath: 'M7 16V4m0 0L3 8m4-4l4 4m6-4v12m0 0l4-4m-4 4l-4-4',
        },
        {
          label: 'Backlog',
          path: '/frontdesk/backlog',
          iconPath: 'M5 3v18l7-5 7 5V3H5z',
        },
        {
          label: 'Chat',
          path: '/frontdesk/chat',
          iconPath: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
        },
      ]
    })

    const isActive = (item) => {
      if (item.startsWith) {
        return route.path.startsWith(item.path)
      }
      return route.path === item.path
    }

    const navigateTo = (path) => {
      router.push(path)
    }

    const handleLogout = () => {
      emit('logout')
    }

    const fetchUnreadCount = async () => {
      if (props.role !== 'frontdesk') return

      try {
        const response = await api.get('/chat/unread-count')
        const payload = response.data || {}

        if (payload.success) {
          chatUnreadCount.value = payload.unread_count || 0
        }
      } catch (error) {
        console.error('Failed to fetch unread count:', error)
      }
    }

    const getFrontdeskOfficeId = () => {
      const officeFromProps = Number(
        props.userData?.office_id
        ?? props.userData?.officeId
        ?? props.userData?.office?.id
      )

      if (Number.isFinite(officeFromProps) && officeFromProps > 0) {
        return officeFromProps
      }

      try {
        const rawUser = localStorage.getItem('user')
        if (!rawUser) return null

        const parsedUser = JSON.parse(rawUser)
        const officeFromStorage = Number(
          parsedUser?.office_id
          ?? parsedUser?.officeId
          ?? parsedUser?.office?.id
        )

        return Number.isFinite(officeFromStorage) && officeFromStorage > 0
          ? officeFromStorage
          : null
      } catch (error) {
        console.error('Failed to parse user for chat office id:', error)
        return null
      }
    }

    const subscribeToChatRealtime = (resolvedOfficeId = null) => {
      if (props.role !== 'frontdesk' || !window.Echo) {
        return
      }

      const officeId = Number(resolvedOfficeId ?? getFrontdeskOfficeId())
      if (!officeId) {
        return
      }

      const nextChannel = `chat.office.${officeId}`

      if (chatChannelName.value === nextChannel) {
        return
      }

      unsubscribeFromChatRealtime()
      chatChannelName.value = nextChannel

      const handleIncomingChat = (event) => {
        const payload = event || {}
        const receiverOfficeId = Number(payload.receiver_office_id)

        if (receiverOfficeId !== officeId) {
          return
        }

        // Optimistic increment so badge updates instantly even
        // before unread-count API response resolves.
        if (route.path !== '/frontdesk/chat') {
          chatUnreadCount.value += 1
        }

        fetchUnreadCount()
      }

      window.Echo
        .channel(chatChannelName.value)
        .listen('.chat.message.sent', handleIncomingChat)
        .listen('chat.message.sent', handleIncomingChat)
        .error((socketError) => {
          console.error('Sidebar chat websocket error:', socketError)
        })
    }

    const unsubscribeFromChatRealtime = () => {
      if (chatChannelName.value && window.Echo) {
        window.Echo.leave(chatChannelName.value)
        chatChannelName.value = null
      }
    }

    const handleNewMessage = () => {
      if (props.role !== 'frontdesk') return
      if (route.path !== '/frontdesk/chat') {
        chatUnreadCount.value += 1
      }
    }

    const checkAndResetUnread = () => {
      if (props.role !== 'frontdesk') return
      if (route.path === '/frontdesk/chat' && chatUnreadCount.value > 0) {
        chatUnreadCount.value = 0
      }
    }

    watch(
      () => [props.role, props.userData?.office_id, props.userData?.officeId, props.userData?.office?.id],
      () => {
        if (props.role !== 'frontdesk') {
          unsubscribeFromChatRealtime()
          return
        }

        const officeId = getFrontdeskOfficeId()
        if (!officeId) {
          return
        }

        subscribeToChatRealtime(officeId)
        fetchUnreadCount()
      },
      { immediate: true }
    )

    onMounted(() => {
      if (props.role !== 'frontdesk') return

      fetchUnreadCount()
      window.addEventListener('new-chat-message', handleNewMessage)
      window.addEventListener('reset-chat-unread', fetchUnreadCount)
      subscribeToChatRealtime()
      resetUnreadInterval = setInterval(checkAndResetUnread, 1000)
      unreadSyncInterval = setInterval(fetchUnreadCount, 15000)
    })

    onUnmounted(() => {
      window.removeEventListener('new-chat-message', handleNewMessage)
      window.removeEventListener('reset-chat-unread', fetchUnreadCount)
      unsubscribeFromChatRealtime()
      if (resetUnreadInterval) {
        clearInterval(resetUnreadInterval)
      }
      if (unreadSyncInterval) {
        clearInterval(unreadSyncInterval)
      }
    })

    return {
      navItems,
      isActive,
      navigateTo,
      handleLogout,
      chatUnreadCount,
    }
  },
}
</script>