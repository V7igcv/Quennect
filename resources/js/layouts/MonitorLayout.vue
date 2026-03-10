<template>
  <div class="min-h-screen bg-white text-gray-900 flex flex-col">
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
          City Planning and Development Office (CPDO)
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
              <div v-if="waitingQueues.length === 0" class="col-span-2 text-center py-8 text-gray-400">
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
export default {
  name: 'MonitorLayout',
  data() {
    return {
      currentTime: new Date().toLocaleTimeString(),
      formattedDate: new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }),
      // Sample data
      currentServing: [
        { queue_number: 'CPDO - 001', counter: '1' },
        { queue_number: 'CPDO - 001', counter: '2' },
        { queue_number: 'CPDO - 001', counter: '3' }
      ],
      nowServing: { queue_number: 'CPDO - P001', counter: '1' },
      waitingQueues: [
        { queue_number: 'CPDO - 001' },
        { queue_number: 'CPDO - 001' },
        { queue_number: 'CPDO - 001' },
        { queue_number: 'CPDO - 001' },
        { queue_number: 'CPDO - 001' },
        { queue_number: 'CPDO - 001' },
        { queue_number: 'CPDO - 001' },
        { queue_number: 'CPDO - 001' },
        { queue_number: 'CPDO - 001' },
        { queue_number: 'CPDO - 001' },
        { queue_number: 'CPDO - 001' },
        { queue_number: 'CPDO - 001' }
      ]
    }
  },
  mounted() {
    // Update time every second
    this.timer = setInterval(() => {
      this.currentTime = new Date().toLocaleTimeString()
    }, 1000)
  },
  beforeUnmount() {
    clearInterval(this.timer)
  }
}
</script>
