<template>
  <header class="bg-[#0F5C5C] text-white px-2 py-3 ">
    <div class="flex justify-end gap-10 p-4">
      <!-- Left side: Date -->
      <div class="flex items-center gap-3">
        <!-- Calendar Icon -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <span class="text-sm font-normal">{{ currentDate }}</span>
      </div>
      
      <!-- Right side: Time -->
      <div class="flex items-center gap-3 w-[120px] justify-end">
        <!-- Clock Icon -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span class="text-sm font-normal w-[100px]">{{ currentTime }}</span>
      </div>
    </div>
  </header>
</template>

<script>
import { ref, onMounted, onUnmounted } from 'vue'

export default {
  name: 'Header',
  setup() {
    const currentDate = ref('')
    const currentTime = ref('')
    let timerInterval = null
    
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
    
    onMounted(() => {
      updateDateTime()
      // Update every second
      timerInterval = setInterval(updateDateTime, 1000)
    })
    
    onUnmounted(() => {
      if (timerInterval) {
        clearInterval(timerInterval)
      }
    })
    
    return {
      currentDate,
      currentTime
    }
  }
}
</script>