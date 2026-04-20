<template>
  <div class="chat-page h-full w-full flex flex-col bg-gray-100">
    
    <!-- Main Content: Offices List + Chat Area -->
    <div class="flex flex-1 overflow-hidden">
      
      <!-- Left Panel - Offices List -->
      <div class="w-[350px] bg-white border-r flex flex-col flex-shrink-0">
        <!-- Search Bar -->
        <div class="p-4 border-b">
          <div class="relative">
            <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input 
              type="text" 
              v-model="searchQuery" 
              placeholder="Search offices..."
              class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:border-[#0F5C5C]"
            />
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex-1 flex items-center justify-center">
          <div class="text-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#0F5C5C] mx-auto mb-2"></div>
            <p class="text-gray-500">Loading offices...</p>
          </div>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="flex-1 flex items-center justify-center">
          <div class="text-center p-4">
            <div class="text-red-500 text-lg mb-2">⚠️</div>
            <p class="text-red-600 mb-2">{{ error }}</p>
            <button 
              @click="fetchOffices" 
              class="px-4 py-2 bg-[#0F5C5C] text-white rounded-lg text-sm"
            >
              Retry
            </button>
          </div>
        </div>

        <!-- Offices List -->
        <div v-else class="flex-1 overflow-y-auto">
          <div 
            v-for="office in sortedOffices" 
            :key="office.id"
            @click="selectOffice(office)"
            class="office-item p-4 cursor-pointer hover:bg-gray-50 border-b transition-colors"
            :class="{ 'bg-blue-50 border-l-4 border-l-[#0F5C5C]': selectedOffice && selectedOffice.id === office.id }"
          >
            <div class="flex items-center gap-3">
              <div class="relative">
                <img
                  v-if="getOfficeLogoUrl(office)"
                  :src="getOfficeLogoUrl(office)"
                  :alt="`${office.name || 'Office'} logo`"
                  class="w-12 h-12 rounded-full object-cover border border-gray-200"
                >
                <div v-else class="w-12 h-12 rounded-full bg-gradient-to-r from-[#0F5C5C] to-[#1F4E79] flex items-center justify-center text-white font-bold text-lg">
                  {{ getOfficeInitial(office) }}
                </div>
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex justify-between items-start">
                  <h4 class="font-medium text-gray-800 truncate">{{ office.name || 'Unnamed Office' }}</h4>
                  <span class="text-xs text-gray-400 ml-2 flex-shrink-0">{{ office.lastMessageTime || '' }}</span>
                </div>
                <p class="text-sm text-gray-500 truncate">
                  {{ office.lastMessage || 'No messages yet' }}
                </p>
                <span v-if="office.unreadCount > 0" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-500 text-white mt-1">
                  {{ office.unreadCount }} new
                </span>
              </div>
            </div>
          </div>

          <div v-if="sortedOffices.length === 0" class="p-8 text-center text-gray-500">
            No offices found
          </div>
        </div>
      </div>

      <!-- Right Panel - Chat Area -->
      <div class="flex-1 flex flex-col bg-gray-50 h-full" v-if="selectedOffice">
        
        <!-- Chat Header -->
        <div class="bg-white border-b px-6 py-4 flex-shrink-0">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <img
                v-if="getOfficeLogoUrl(selectedOffice)"
                :src="getOfficeLogoUrl(selectedOffice)"
                :alt="`${selectedOffice.name || 'Office'} logo`"
                class="w-10 h-10 rounded-full object-cover border border-gray-200"
              >
              <div v-else class="w-10 h-10 rounded-full bg-gradient-to-r from-[#0F5C5C] to-[#1F4E79] flex items-center justify-center text-white font-bold">
                {{ getOfficeInitial(selectedOffice) }}
              </div>
              <div>
                <h2 class="font-semibold text-gray-800">{{ selectedOffice.name || 'Office' }}</h2>
              </div>
            </div>
            <button 
              @click="refreshMessages" 
              class="p-2 hover:bg-gray-100 rounded-full transition-colors"
              title="Refresh messages"
            >
              <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
              </svg>
            </button>
          </div>
        </div>

        <!-- Scrollable Messages Area -->
        <div class="flex-1 overflow-y-auto p-6" ref="messagesContainer">
          <div class="max-w-4xl mx-auto">
            
            <div v-if="loadingMessages" class="flex justify-center py-8">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#0F5C5C]"></div>
            </div>

            <div v-else class="space-y-4">
              <!-- Empty state for no messages -->
              <div v-if="!sortedMessages || sortedMessages.length === 0" class="text-center py-8 text-gray-400">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                </svg>
                <p>No messages yet. Start the conversation!</p>
              </div>

              <!-- Messages -->
              <div 
                v-for="message in sortedMessages" 
                :key="message.id"
                class="flex"
                :class="Number(message.sender_id) === Number(currentOfficeId) ? 'justify-end' : 'justify-start'"
              >
                <div class="flex items-end gap-2 max-w-[70%]"
                     :class="Number(message.sender_id) === Number(currentOfficeId) ? 'flex-row-reverse' : 'flex-row'">

                  <template v-if="Number(message.sender_id) === Number(currentOfficeId)">
                    <img
                      v-if="currentOfficeLogoUrl"
                      :src="currentOfficeLogoUrl"
                      :alt="`${currentOfficeName || 'Current office'} logo`"
                      class="w-8 h-8 rounded-full object-cover border border-gray-200 flex-shrink-0"
                    >
                    <div v-else class="w-8 h-8 rounded-full bg-gray-200 border border-gray-300 flex-shrink-0"></div>
                  </template>
                  <template v-else>
                    <img
                      v-if="getOfficeLogoUrl(selectedOffice)"
                      :src="getOfficeLogoUrl(selectedOffice)"
                      :alt="`${selectedOffice.name || 'Office'} logo`"
                      class="w-8 h-8 rounded-full object-cover border border-gray-200 flex-shrink-0"
                    >
                    <div v-else class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center text-xs font-bold flex-shrink-0">
                      {{ getOfficeInitial(selectedOffice) }}
                    </div>
                  </template>

                  <div class="rounded-2xl px-4 py-2"
                       :class="Number(message.sender_id) === Number(currentOfficeId) 
                         ? 'bg-[#0F5C5C] text-white' 
                         : 'bg-white text-gray-800 shadow'">

                    <p v-if="message.type === 'text'" class="text-sm whitespace-pre-wrap break-words">{{ message.content }}</p>

                    <!-- Image preview -->
                    <img 
                      v-if="message.type === 'image'" 
                      :src="message.content" 
                      class="max-w-[200px] max-h-[200px] rounded-lg cursor-pointer mt-2"
                      @click="previewImage(message.content)"
                    />

                    <!-- File link -->
                    <div v-if="message.type === 'file'" class="mt-2">
                      <a :href="message.content" download class="text-sm underline flex items-center gap-1"
                         :class="Number(message.sender_id) === Number(currentOfficeId) ? 'text-white' : 'text-[#0F5C5C]'">
                        📎 {{ message.file_name }}
                      </a>
                    </div>

                    <div class="flex justify-end mt-1">
                      <span class="text-xs opacity-70">{{ formatTime(message.created_at) }}</span>
                      <span v-if="Number(message.sender_id) === Number(currentOfficeId) && message.is_read" class="text-xs ml-1 opacity-70">
                        ✓✓
                      </span>
                      <span v-else-if="Number(message.sender_id) === Number(currentOfficeId) && !message.is_read" class="text-xs ml-1 opacity-70">
                        ✓
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>

        <!-- IMPROVED: Sticky Input Area with better spacing -->
        <div class="bg-white border-t p-3 flex-shrink-0 relative">
          <div v-if="pendingFile" class="mb-2 flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 px-3 py-2">
            <p class="text-xs text-gray-600 truncate pr-2">Selected file: {{ pendingFile.name }}</p>
            <button
              @click="clearPendingFile"
              class="text-xs text-red-600 hover:text-red-700"
              type="button"
            >
              Remove
            </button>
          </div>

          <div class="flex items-center gap-2 md:gap-3">
            <!-- Attachment Button -->
            <button 
              @click="triggerFileUpload" 
              class="p-2.5 hover:bg-gray-100 rounded-full transition-colors flex-shrink-0"
              title="Attach file"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
              </svg>
            </button>
            
            <!-- Emoji Button -->
            <button 
              @click="toggleEmojiPicker" 
              class="p-2.5 hover:bg-gray-100 rounded-full transition-colors flex-shrink-0"
              title="Add emoji"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </button>
            
            <!-- Message Input - now with proper spacing -->
            <div class="flex-1 relative">
              <input 
                type="text" 
                v-model="newMessage" 
                @keyup.enter="sendMessage"
                :disabled="sending"
                placeholder="Type a message..."
                class="w-full px-4 py-2.5 border rounded-full focus:outline-none focus:border-[#0F5C5C] focus:ring-1 focus:ring-[#0F5C5C] disabled:bg-gray-100 text-sm md:text-base transition-all"
              />
            </div>
            
            <!-- Send Button - improved sizing and spacing -->
            <button 
              @click="sendMessage" 
              :disabled="sending || (!newMessage.trim() && !pendingFile)"
              class="p-2.5 bg-[#0F5C5C] text-white rounded-full disabled:opacity-50 disabled:cursor-not-allowed hover:bg-[#0a4a4a] transition-colors flex-shrink-0 shadow-sm"
              title="Send message"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
              </svg>
            </button>
          </div>
          
          <!-- Emoji Picker - repositioned for better visibility -->
          <div v-if="showEmojiPicker" class="absolute bottom-full right-0 mb-2 bg-white border rounded-lg shadow-lg p-2 grid grid-cols-8 gap-1 z-50">
            <button v-for="emoji in emojis" :key="emoji" @click="addEmoji(emoji)" class="text-xl hover:bg-gray-100 p-1 rounded transition-colors">
              {{ emoji }}
            </button>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div class="flex-1 flex items-center justify-center text-gray-500" v-else>
        <div class="text-center">
          <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
          </svg>
          <p>Select an office to start chatting</p>
        </div>
      </div>

    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import axios from 'axios'

export default {
  name: 'ChatModule',
  props: {
    currentOfficeId: {
      type: Number,
      default: null
    }
  },
  setup(props) {
    const searchQuery = ref('')
    const selectedOffice = ref(null)
    const newMessage = ref('')
    const messagesContainer = ref(null)
    const loading = ref(true)
    const loadingMessages = ref(false)
    const sending = ref(false)
    const error = ref(null)
    const showEmojiPicker = ref(false)
    const pendingFile = ref(null)
    
    const offices = ref([])
    const messages = ref({})
    const currentOfficeProfile = ref(null)
    const chatChannelName = ref(null)
    
    const emojis = ['😀', '😂', '😍', '😎', '👍', '❤️', '🎉', '🔥', '👋', '🙏', '💪', '😊']
    
    const getOfficeInitial = (office) => {
      if (!office || !office.name) return '?'
      return office.name.charAt(0).toUpperCase()
    }

    const getOfficeLogoUrl = (office) => {
      const rawUrl = office?.logo_url || office?.logoUrl || office?.logo || ''
      if (!rawUrl) return ''

      return String(rawUrl)
    }

    const currentOfficeName = computed(() => {
      return (
        currentOfficeProfile.value?.office_name
        || currentOfficeProfile.value?.name
        || currentOfficeProfile.value?.office_acronym
        || ''
      )
    })

    const currentOfficeLogoUrl = computed(() => {
      return getOfficeLogoUrl(currentOfficeProfile.value)
    })

    const resolveCurrentOfficeProfile = async () => {
      try {
        const rawUser = localStorage.getItem('user')

        if (rawUser) {
          const parsedUser = JSON.parse(rawUser)
          currentOfficeProfile.value = parsedUser?.office || null
        }

        // Refresh with latest server payload to ensure logo_url is present.
        const response = await axios.get('/api/user')
        const latestUser = response?.data?.data || null

        if (latestUser?.office) {
          currentOfficeProfile.value = latestUser.office
          localStorage.setItem('user', JSON.stringify(latestUser))
        }
      } catch (error) {
        console.error('Failed to resolve current office profile for chat avatar:', error)
      }
    }
    
    const filteredOffices = computed(() => {
      if (!searchQuery.value) return offices.value
      return offices.value.filter(office => 
        office.name && office.name.toLowerCase().includes(searchQuery.value.toLowerCase())
      )
    })
    
    const sortedOffices = computed(() => {
      return [...filteredOffices.value].sort((a, b) => {
        const timeA = a.lastMessageTimestamp || 0
        const timeB = b.lastMessageTimestamp || 0
        return timeB - timeA
      })
    })
    
    const currentMessages = computed(() => {
      if (!selectedOffice.value || !selectedOffice.value.id) return []
      return messages.value[selectedOffice.value.id] || []
    })
    
    const sortedMessages = computed(() => {
      return [...currentMessages.value].sort((a, b) => {
        return new Date(a.created_at) - new Date(b.created_at)
      })
    })

    const resolveCurrentOfficeId = () => {
      const fromProps = Number(props.currentOfficeId)
      if (Number.isFinite(fromProps) && fromProps > 0) {
        return fromProps
      }

      try {
        const rawUser = localStorage.getItem('user')
        if (!rawUser) return null

        const parsedUser = JSON.parse(rawUser)
        const fromStorage = Number(
          parsedUser?.office_id
          ?? parsedUser?.officeId
          ?? parsedUser?.office?.id
        )

        return Number.isFinite(fromStorage) && fromStorage > 0 ? fromStorage : null
      } catch (error) {
        console.error('Failed to resolve current office ID for chat realtime:', error)
        return null
      }
    }

    const normalizeMessagePayload = (payload) => {
      const senderId = Number(payload?.sender_id ?? payload?.sender_office_id)
      const receiverId = Number(payload?.receiver_id ?? payload?.receiver_office_id)
      const messageType = payload?.type || 'text'
      let content = payload?.content || ''

      if ((messageType === 'image' || messageType === 'file') && payload?.file_path) {
        if (String(payload.file_path).startsWith('http://') || String(payload.file_path).startsWith('https://')) {
          content = payload.file_path
        } else {
          content = `/storage/${String(payload.file_path).replace(/^\/+/, '')}`
        }
      }

      return {
        id: Number(payload?.id),
        sender_id: senderId,
        receiver_id: receiverId,
        type: messageType,
        content,
        file_name: payload?.file_name || null,
        is_read: Boolean(payload?.is_read),
        created_at: payload?.created_at || new Date().toISOString(),
      }
    }

    const getMessagePreview = (message) => {
      if (!message) return ''
      if (message.type === 'image' || message.type === 'file') return 'sent an attachment'
      return message.content || ''
    }

    const handleIncomingMessage = async (event) => {
      const currentOfficeId = resolveCurrentOfficeId()
      if (!currentOfficeId) return

      const incomingMessage = normalizeMessagePayload(event)
      const senderId = Number(incomingMessage.sender_id)
      const receiverId = Number(incomingMessage.receiver_id)

      if (!senderId || !receiverId) return
      if (senderId !== currentOfficeId && receiverId !== currentOfficeId) return

      // The sender already renders via optimistic update + API response,
      // so skip self-echo events to avoid duplicate bubbles.
      if (senderId === currentOfficeId) {
        return
      }

      const otherOfficeId = senderId === currentOfficeId ? receiverId : senderId
      if (!otherOfficeId) return

      if (!messages.value[otherOfficeId]) {
        messages.value[otherOfficeId] = []
      }

      const thread = messages.value[otherOfficeId]
      const existingIndex = thread.findIndex((message) => Number(message.id) === Number(incomingMessage.id))

      if (existingIndex === -1) {
        const tempMatchIndex = thread.findIndex((message) => (
          message.temp
          && Number(message.sender_id) === senderId
          && Number(message.receiver_id) === receiverId
          && message.type === incomingMessage.type
          && (message.content || '') === (incomingMessage.content || '')
        ))

        if (tempMatchIndex !== -1) {
          thread[tempMatchIndex] = incomingMessage
        } else {
          thread.push(incomingMessage)
        }
      }

      const officeIndex = offices.value.findIndex((office) => Number(office.id) === Number(otherOfficeId))
      if (officeIndex !== -1) {
        offices.value[officeIndex].lastMessage = getMessagePreview(incomingMessage)
        offices.value[officeIndex].lastMessageTime = 'Just now'
        offices.value[officeIndex].lastMessageTimestamp = Math.floor(Date.now() / 1000)

        if (receiverId === currentOfficeId && Number(selectedOffice.value?.id) !== Number(otherOfficeId)) {
          offices.value[officeIndex].unreadCount = Number(offices.value[officeIndex].unreadCount || 0) + 1
        }
      }

      if (Number(selectedOffice.value?.id) === Number(otherOfficeId)) {
        await scrollToBottom()

        if (receiverId === currentOfficeId) {
          await markMessagesAsRead(otherOfficeId)
          window.dispatchEvent(new CustomEvent('reset-chat-unread'))
        }
      }
    }

    const unsubscribeFromRealtime = () => {
      if (chatChannelName.value && window.Echo) {
        window.Echo.leave(chatChannelName.value)
        chatChannelName.value = null
      }
    }

    const subscribeToRealtime = () => {
      if (!window.Echo) {
        return
      }

      const currentOfficeId = resolveCurrentOfficeId()
      if (!currentOfficeId) {
        return
      }

      const nextChannel = `chat.office.${currentOfficeId}`

      if (chatChannelName.value === nextChannel) {
        return
      }

      unsubscribeFromRealtime()
      chatChannelName.value = nextChannel

      window.Echo
        .channel(chatChannelName.value)
        .listen('.chat.message.sent', handleIncomingMessage)
        .listen('chat.message.sent', handleIncomingMessage)
        .error((socketError) => {
          console.error('Chat websocket error:', socketError)
        })
    }
    
    const fetchOffices = async () => {
      loading.value = true
      error.value = null
      
      try {
        const response = await axios.get('/api/chat/offices')
        
        if (response.data.success) {
          offices.value = response.data.data || []
        } else {
          throw new Error(response.data.message || 'Failed to fetch offices')
        }
      } catch (err) {
        console.error('Failed to fetch offices:', err)
        error.value = err.response?.data?.message || 'Failed to load offices. Please try again.'
        offices.value = []
      } finally {
        loading.value = false
      }
    }
    
    const fetchMessages = async (officeId) => {
      if (!officeId) return
      
      loadingMessages.value = true
      try {
        const response = await axios.get(`/api/chat/messages/${officeId}`)
        
        if (response.data.success) {
          messages.value[officeId] = response.data.data || []
          await scrollToBottom()
          await markMessagesAsRead(officeId)
          
          window.dispatchEvent(new CustomEvent('reset-chat-unread'))
        }
      } catch (err) {
        console.error('Failed to fetch messages:', err)
        messages.value[officeId] = []
      } finally {
        loadingMessages.value = false
      }
    }
    
    const refreshMessages = async () => {
      if (selectedOffice.value) {
        await fetchMessages(selectedOffice.value.id)
      }
    }
    
    const selectOffice = async (office) => {
      if (!office || !office.id) return
      selectedOffice.value = office
      pendingFile.value = null
      await fetchMessages(office.id)
    }
    
    const sendMessage = async () => {
      if (!selectedOffice.value || !selectedOffice.value.id || sending.value) return

      if (pendingFile.value) {
        const fileToUpload = pendingFile.value
        pendingFile.value = null
        await uploadFile(fileToUpload)
        return
      }

      if (!newMessage.value.trim()) return
      
      sending.value = true
      const messageContent = newMessage.value
      const tempId = Date.now()
      const currentOfficeId = resolveCurrentOfficeId()
      
      const tempMessage = {
        id: tempId,
        sender_id: currentOfficeId,
        receiver_id: selectedOffice.value.id,
        type: 'text',
        content: messageContent,
        created_at: new Date().toISOString(),
        is_read: false,
        temp: true
      }
      
      if (!messages.value[selectedOffice.value.id]) {
        messages.value[selectedOffice.value.id] = []
      }
      messages.value[selectedOffice.value.id].push(tempMessage)
      
      newMessage.value = ''
      await scrollToBottom()
      
      try {
        const response = await axios.post('/api/chat/send', {
          receiver_id: selectedOffice.value.id,
          content: messageContent
        })
        
        if (response.data.success) {
          const index = messages.value[selectedOffice.value.id].findIndex(m => m.id === tempId)
          if (index !== -1) {
            messages.value[selectedOffice.value.id][index] = response.data.data
          }
          
          const officeIndex = offices.value.findIndex(o => o.id === selectedOffice.value.id)
          if (officeIndex !== -1) {
            offices.value[officeIndex].lastMessage = messageContent
            offices.value[officeIndex].lastMessageTime = 'Just now'
            offices.value[officeIndex].lastMessageTimestamp = Date.now() / 1000
          }
          
          window.dispatchEvent(new CustomEvent('new-chat-message'))
        }
      } catch (err) {
        console.error('Failed to send message:', err)
        const index = messages.value[selectedOffice.value.id].findIndex(m => m.id === tempId)
        if (index !== -1) {
          messages.value[selectedOffice.value.id][index].content = `[Failed] ${messageContent}`
          messages.value[selectedOffice.value.id][index].failed = true
        }
        alert(err.response?.data?.message || 'Failed to send message')
      } finally {
        sending.value = false
      }
    }
    
    const triggerFileUpload = () => {
      const input = document.createElement('input')
      input.type = 'file'
      input.accept = 'image/*, .pdf, .doc, .docx, .xls, .xlsx'
      input.onchange = (e) => {
        const file = e.target.files[0]
        if (file && selectedOffice.value) {
          pendingFile.value = file
        }
      }
      input.click()
    }

    const clearPendingFile = () => {
      pendingFile.value = null
    }
    
    const uploadFile = async (file) => {
      if (!selectedOffice.value) return
      
      const formData = new FormData()
      formData.append('file', file)
      formData.append('receiver_id', selectedOffice.value.id)
      
      sending.value = true
      const tempId = Date.now()
      const currentOfficeId = resolveCurrentOfficeId()
      
      const tempMessage = {
        id: tempId,
        sender_id: currentOfficeId,
        receiver_id: selectedOffice.value.id,
        type: file.type.startsWith('image/') ? 'image' : 'file',
        content: 'Uploading...',
        file_name: file.name,
        created_at: new Date().toISOString(),
        is_read: false,
        temp: true
      }
      
      if (!messages.value[selectedOffice.value.id]) {
        messages.value[selectedOffice.value.id] = []
      }
      messages.value[selectedOffice.value.id].push(tempMessage)
      await scrollToBottom()
      
      try {
        const response = await axios.post('/api/chat/upload', formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
        
        if (response.data.success) {
          const index = messages.value[selectedOffice.value.id].findIndex(m => m.id === tempId)
          if (index !== -1) {
            messages.value[selectedOffice.value.id][index] = response.data.data
          }
          
          window.dispatchEvent(new CustomEvent('new-chat-message'))
        }
      } catch (err) {
        console.error('Failed to upload file:', err)
        const index = messages.value[selectedOffice.value.id].findIndex(m => m.id === tempId)
        if (index !== -1) {
          messages.value[selectedOffice.value.id][index].content = `[Failed] ${file.name}`
          messages.value[selectedOffice.value.id][index].failed = true
        }
        alert(err.response?.data?.message || 'Failed to upload file')
      } finally {
        sending.value = false
      }
    }
    
    const markMessagesAsRead = async (officeId) => {
      try {
        await axios.post(`/api/chat/read/${officeId}`)
        
        const officeIndex = offices.value.findIndex(o => o.id === officeId)
        if (officeIndex !== -1) {
          offices.value[officeIndex].unreadCount = 0
        }
      } catch (error) {
        console.error('Failed to mark messages as read:', error)
      }
    }
    
    const scrollToBottom = async () => {
      await nextTick()
      if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
      }
    }
    
    const formatTime = (timestamp) => {
      if (!timestamp) return ''
      try {
        const date = new Date(timestamp)
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
      } catch (e) {
        return ''
      }
    }
    
    const previewImage = (imageUrl) => {
      window.open(imageUrl, '_blank')
    }
    
    const toggleEmojiPicker = () => {
      showEmojiPicker.value = !showEmojiPicker.value
    }
    
    const addEmoji = (emoji) => {
      newMessage.value += emoji
      showEmojiPicker.value = false
    }
    
    // Close emoji picker when clicking outside
    const handleClickOutside = (event) => {
      if (showEmojiPicker.value && !event.target.closest('.relative')) {
        showEmojiPicker.value = false
      }
    }
    
    watch(sortedMessages, () => {
      scrollToBottom()
    }, { deep: true })

    watch(
      () => props.currentOfficeId,
      () => {
        subscribeToRealtime()
      }
    )
    
    onMounted(() => {
      resolveCurrentOfficeProfile()
      fetchOffices()
      subscribeToRealtime()
      document.addEventListener('click', handleClickOutside)
    })

    onUnmounted(() => {
      unsubscribeFromRealtime()
      document.removeEventListener('click', handleClickOutside)
    })
    
    return {
      searchQuery,
      selectedOffice,
      newMessage,
      messagesContainer,
      loading,
      loadingMessages,
      sending,
      error,
      offices,
      sortedOffices,
      sortedMessages,
      showEmojiPicker,
      pendingFile,
      emojis,
      currentOfficeName,
      currentOfficeLogoUrl,
      getOfficeInitial,
      getOfficeLogoUrl,
      selectOffice,
      sendMessage,
      triggerFileUpload,
      clearPendingFile,
      refreshMessages,
      formatTime,
      previewImage,
      fetchOffices,
      toggleEmojiPicker,
      addEmoji
    }
  }
}
</script>

<style scoped>
.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

/* Responsive adjustments for mobile */
@media (max-width: 640px) {
  .chat-page .p-3 {
    padding: 0.5rem;
  }
  
  .chat-page .gap-2 {
    gap: 0.25rem;
  }
  
  .chat-page .p-2\.5 {
    padding: 0.5rem;
  }
}
</style>