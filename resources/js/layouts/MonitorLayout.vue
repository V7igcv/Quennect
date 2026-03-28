<template>
  <div class="min-h-screen bg-[#F4FAF8] text-gray-900 flex flex-col">
    <!-- Loading Overlay -->
    <div v-if="isLoading" class="fixed inset-0 bg-white bg-opacity-90 flex items-center justify-center z-50">
      <div class="text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-[#0F5C5C] mx-auto mb-4"></div>
        <p class="text-gray-600">Loading monitor data...</p>
      </div>
    </div>

    <!-- Error Message -->
    <div v-if="error && !isLoading" class="fixed inset-0 bg-red-50 flex items-center justify-center z-50">
      <div class="text-center max-w-md mx-4">
        <div class="text-red-500 text-6xl mb-4">⚠️</div>
        <h3 class="text-xl font-semibold text-red-700 mb-2">Connection Error</h3>
        <p class="text-red-600 mb-4">{{ error }}</p>
        <button @click="fetchMonitorData" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
          Retry
        </button>
      </div>
    </div>
    <!-- Header -->
    <header class="bg-[#0F5C5C] text-white px-4 sm:px-6 lg:px-8 py-4 shadow-lg">
      <div class="max-w-screen-2xl mx-auto flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center gap-3">
          <img src="/storage/logos/Ligao City Seal.png" alt="Quennect Logo" class="h-12 w-12 rounded-full bg-white/10 p-1" />
          <div>
            <h1 class="text-xl sm:text-2xl font-bold leading-tight">Quennect</h1>
            <p class="text-xs sm:text-sm text-white/85">LGU Ligao General Queuing System</p>
          </div>
        </div>

        <div class="flex items-center gap-3 sm:gap-4 bg-white/10 rounded-xl px-3 sm:px-4 py-2 sm:py-3">
          <div class="h-12 w-12 sm:h-14 sm:w-14 rounded-full bg-white flex items-center justify-center overflow-hidden shadow-inner">
            <img
              v-if="officeLogo"
              :src="officeLogo"
              :alt="office?.name + ' Logo'"
              class="h-full w-full object-contain"
            />
            <span v-else class="text-[#0F5C5C] font-bold text-lg">{{ officeInitials }}</span>
          </div>

          <div>
            <p class="text-[11px] uppercase tracking-wider text-white/80">Office Monitor</p>
            <h2 class="text-lg sm:text-2xl font-bold leading-tight">{{ office?.name || 'Loading...' }}</h2>
            <p class="text-xs sm:text-sm text-white/85">{{ office?.acronym || '---' }}</p>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 px-4 sm:px-6 lg:px-8 py-6">
      <div class="max-w-screen-2xl mx-auto grid grid-cols-1 xl:grid-cols-12 gap-6">
        <!-- Left: Waiting Table -->
        <section class="xl:col-span-6 bg-white rounded-2xl shadow-lg overflow-hidden">
          <div class="bg-[#0F5C5C] text-center text-white px-5 py-4">
            <h3 class="text-xl sm:text-2xl font-bold">Waiting</h3>
          </div>

          <div class="p-4 sm:p-5">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
              <div
                v-for="(waiting, index) in waitingQueues"
                :key="waiting.queue_number + '-' + index"
                class="bg-gray-100 p-3 sm:p-4 text-center font-semibold text-[#0F5C5C] rounded-lg shadow-sm text-lg sm:text-xl"
              >
                {{ waiting.queue_number }}
              </div>
              <div v-if="waitingQueues.length === 0" class="col-span-full text-center py-8 text-gray-400">
                No queues waiting
              </div>
            </div>
          </div>
        </section>

        <!-- Right: Now Serving + Current Serving Per Counter -->
        <section class="xl:col-span-6 flex flex-col gap-6">
          <div class="bg-white rounded-2xl shadow-lg p-5 sm:p-6">
            <h3 class="text-center text-xl sm:text-2xl font-bold text-gray-700">Now Serving</h3>

            <div v-if="nowServing" class="text-center mt-4">
              <p class="text-5xl sm:text-7xl font-extrabold text-[#1F4E79] tracking-wide monitor-pulse">
                {{ nowServing.queue_number }}
              </p>
              <p class="mt-3 text-base sm:text-lg text-gray-700">
                Please proceed to
                <span class="font-bold text-[#0F5C5C]">Counter {{ nowServing.counter }}</span>
              </p>
            </div>

            <div v-else class="text-center py-10 text-gray-400 text-sm sm:text-base">
              No queue being served
            </div>
          </div>

          <div class="bg-white rounded-2xl shadow-lg p-5 sm:p-6">
            <h3 class="text-center text-xl sm:text-2xl font-bold text-gray-700">Current Serving Per Counter</h3>

            <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div
                v-for="counter in countersForDisplay"
                :key="counter.id"
                class="rounded-xl p-4 shadow-sm"
                :class="counter.is_enabled ? 'bg-[#EAF8F4]' : 'bg-gray-100'"
              >
                <p class="text-xs uppercase tracking-wide font-semibold" :class="counter.is_enabled ? 'text-[#0F5C5C]' : 'text-gray-500'">
                  Counter {{ counter.counter_number }}
                </p>

                <p
                  v-if="!counter.is_enabled"
                  class="mt-2 text-lg sm:text-xl font-bold text-gray-500"
                >
                  Not Available
                </p>

                <p
                  v-else-if="counter.queue_number"
                  class="mt-2 text-2xl sm:text-3xl font-bold text-[#1F4E79]"
                >
                  {{ counter.queue_number }}
                </p>

                <p
                  v-else
                  class="mt-2 text-lg sm:text-xl font-semibold italic text-gray-500"
                >
                  Idle
                </p>
              </div>

              <div v-if="countersForDisplay.length === 0" class="sm:col-span-2 text-center py-8 text-gray-400">
                No counters available
              </div>
            </div>
          </div>
        </section>
      </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 px-4 sm:px-6 lg:px-8 py-4">
      <div class="max-w-screen-2xl mx-auto flex flex-col sm:flex-row justify-between gap-2 sm:gap-4 sm:items-center">
        <p class="text-gray-700 font-semibold text-sm sm:text-base">{{ formattedDate }}</p>
        <p class="text-xl sm:text-2xl font-bold text-teal-700">{{ currentTime }}</p>
        <p class="text-sm sm:text-lg font-semibold text-gray-700">Thank you for Waiting</p>
      </div>
    </footer>
  </div>
</template>

<script>
import monitorService from '@/services/monitor'

export default {
  name: 'MonitorLayout',
  props: {
    officeId: {
      type: [String, Number],
      required: true
    }
  },
  data() {
    return {
      currentTime: new Date().toLocaleTimeString(),
      formattedDate: new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }),
      
      // Monitor data
      office: null,
      currentServing: [],
      nowServing: null,
      counters: [],
      waitingQueues: [],
      
      // Loading states
      isLoading: true,
      error: null,
      
      // Realtime
      monitorChannelName: null
    }
  },
  computed: {
    officeLogo() {
      return this.office?.logo_url || null
    },
    officeInitials() {
      const name = this.office?.acronym || this.office?.name || 'Q'
      return name.slice(0, 2).toUpperCase()
    },
    countersForDisplay() {
      return Array.isArray(this.counters)
        ? [...this.counters].sort((a, b) => Number(a.counter_number) - Number(b.counter_number))
        : []
    }
  },
  async mounted() {
    // Update time every second
    this.timer = setInterval(() => {
      this.currentTime = new Date().toLocaleTimeString()
    }, 1000)
    
    // Initial data fetch
    await this.fetchMonitorData()

    // Subscribe to websocket updates after initial payload is loaded.
    this.subscribeToMonitorUpdates()
  },
  beforeUnmount() {
    clearInterval(this.timer)

    if (this.monitorChannelName && window.Echo) {
      window.Echo.leave(this.monitorChannelName)
    }
  },
  methods: {
    applyMonitorData(payload) {
      this.office = payload.office
      this.currentServing = payload.current_serving || []
      this.nowServing = payload.now_serving
      this.counters = payload.counters || []
      this.waitingQueues = payload.waiting_list || []
      this.error = null
      this.isLoading = false
    },
    subscribeToMonitorUpdates() {
      if (!window.Echo) {
        console.warn('Echo is not available. Falling back to API-only monitor updates.')
        return
      }

      this.monitorChannelName = `monitor.office.${this.officeId}`

      window.Echo
        .channel(this.monitorChannelName)
        .listen('.monitor.updated', (event) => {
          if (event?.data) {
            this.applyMonitorData(event.data)
            return
          }

          // Keep monitor usable even if payload changes unexpectedly.
          this.fetchMonitorData()
        })
        .error((socketError) => {
          console.error('Monitor websocket error:', socketError)
        })
    },
    async fetchMonitorData() {
      try {
        this.error = null
        const response = await monitorService.getMonitorData(this.officeId)
        
        if (response.success) {
          this.applyMonitorData(response.data)
        } else {
          this.error = 'Failed to load monitor data'
        }
      } catch (error) {
        console.error('Error fetching monitor data:', error)
        this.error = 'Error loading monitor data'
      } finally {
        this.isLoading = false
      }
    }
  }
}
</script>

<style scoped>
.monitor-pulse {
  animation: monitorPulse 1.2s ease-in-out infinite;
}

@keyframes monitorPulse {
  0%,
  100% {
    transform: scale(1);
    opacity: 1;
  }
  50% {
    transform: scale(1.04);
    opacity: 0.85;
  }
}
</style>
