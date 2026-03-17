<template>
  <div class="min-h-screen bg-white text-gray-900 flex flex-col">
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
    <header class="bg-[#0F5C5C] text-white py-4 px-8">
      <div class="flex items-center justify-center gap-3 mb-2">
        <img src="/storage/logos/Ligao City Seal.png" alt="Quennect Logo" class="h-12" />
        <div>
          <h1 class="text-2xl font-bold">Quennect</h1>
          <p class="text-sm">LGU Ligao General Queuing System</p>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 px-8 py-6">
      <!-- Office Name -->
      <div class="mb-6 flex items-center justify-center">
        <h2 class="text-4xl font-bold text-[#1F4E79] flex items-center gap-2 py-2">
          <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10.5 1.5H3a1.5 1.5 0 00-1.5 1.5v14a1.5 1.5 0 001.5 1.5h14a1.5 1.5 0 001.5-1.5V9.5m-1-8h-8m8 0v8m0-8L10.5 9.5"></path>
          </svg>
          {{ officeName }}
        </h2>
      </div>

      <!-- Main Content Grid -->
      <div class="grid grid-cols-5 gap-8">
        <!-- Left Column: Current Serving per Counter -->
        <div class="col-span-2 flex flex-col gap-4">
          <div class="border-4 border-teal-700 rounded-lg overflow-hidden">
            <div class="bg-[#0F5C5C] text-white p-4 font-bold text-2xl text-center">
              CURRENT SERVING PER COUNTER
            </div>
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead>
                  <tr class="border-b border-gray-300">
                    <th class="px-4 py-6 text-gray-700 text-center text-3xl font-bold">QUEUE NUMBER</th>
                    <th class="px-4 py-6 text-gray-700 text-center text-3xl font-bold">COUNTER</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(serving, index) in currentServing" :key="index" class="border-b border-gray-200">
                    <td class="px-4 py-8 text-3xl text-center font-semibold text-teal-700">{{ serving.queue_number }}</td>
                    <td class="px-4 py-8 text-3xl text-center font-semibold">{{ serving.counter }}</td>
                  </tr>
                  <tr v-if="currentServing.length === 0">
                    <td colspan="2" class="px-4 py-8 text-center text-gray-400">No queue currently serving</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Right Column: Now Serving & Waiting -->
        <div class="col-span-3 flex flex-col gap-6">
          <!-- Now Serving Section -->
          <div class="border-4 border-[#0F5C5C] rounded-lg">
            <div class="bg-white p-6 rounded-lg">
              <h1 class="text-center text-2xl font-bold text-gray-700">Now Serving</h1>
              <div v-if="nowServing" class="text-center">
                <p class="text-7xl font-bold text-[#1F4E79] my-5">{{ nowServing.queue_number }}</p>
                <p class="text-xl text-gray-700">
                  <span class="mr-2">→</span>Please proceed to <span class="font-bold">counter {{ nowServing.counter }}</span>
                </p>
              </div>
              <div v-else class="text-center py-8 text-gray-400">
                No queue being served
              </div>
            </div>
          </div>
          <!-- Waiting Section -->
          <div class="border-4 border-teal-700 rounded-lg overflow-hidden">
            <div class="bg-[#0F5C5C] text-white p-3 font-bold text-2xl text-center">
              Waiting
            </div>
            <div class="grid grid-cols-3 gap-2 p-4">
              <div v-for="(waiting, index) in waitingQueues" :key="index" class="bg-gray-100 p-3 text-center font-semibold text-teal-700 rounded">
                {{ waiting.queue_number }}
              </div>
              <div v-if="waitingQueues.length === 0" class="col-span-3 text-center py-8 text-gray-400">
                No queues waiting
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-300 px-8 py-4">
      <div class="flex justify-between items-center">
        <p class="text-gray-700 font-semibold">{{ formattedDate }}</p>
        <p class="text-2xl font-bold text-teal-700">{{ currentTime }}</p>
        <p class="text-lg font-semibold text-gray-700">Thank you for Waiting</p>
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
      waitingQueues: [],
      
      // Loading states
      isLoading: true,
      error: null,
      
      // Polling
      pollInterval: null
    }
  },
  computed: {
    officeName() {
      return this.office ? `${this.office.name} (${this.office.acronym})` : 'Loading...'
    }
  },
  async mounted() {
    // Update time every second
    this.timer = setInterval(() => {
      this.currentTime = new Date().toLocaleTimeString()
    }, 1000)
    
    // Initial data fetch
    await this.fetchMonitorData()
    
    // Set up polling for real-time updates (every 5 seconds)
    this.pollInterval = setInterval(async () => {
      await this.fetchMonitorData()
    }, 5000)
  },
  beforeUnmount() {
    clearInterval(this.timer)
    if (this.pollInterval) {
      clearInterval(this.pollInterval)
    }
  },
  methods: {
    async fetchMonitorData() {
      try {
        this.error = null
        const response = await monitorService.getMonitorData(this.officeId)
        
        if (response.success) {
          this.office = response.data.office
          this.currentServing = response.data.current_serving
          this.nowServing = response.data.now_serving
          this.waitingQueues = response.data.waiting_list
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
