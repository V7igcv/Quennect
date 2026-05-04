<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    
    <!-- Header -->
    <KioskHeader bgColor="#0F5C5C" textColor="#FFFFFF" />

    <!-- Content -->
    <div class="flex-grow flex items-center justify-center px-6 sm:px-8 py-4 sm:py-8">
      <div class="max-w-2xl w-full text-center">
        
        <!-- System Title -->
      

        <!-- Success Message Container -->
        <div class="rounded-xl shadow-md p-6 sm:p-8 mb-8" style="background-color: #5DD2BE;">
          <div class="space-y-4">
            <p class="text-2xl font-semibold text-gray-800">
              Na-print na ang iyong numero.
            </p>
            
            <p class="text-gray-600">
              Pakikuha ang ticket at dalhin ito sa opisina na iyong pupuntahan.
            </p>
            
            <p class="text-gray-600">
              Huwag kalimutang ihanda ang mga kinakailangang dokumento para sa iyong transaksyon (kung mayroon man).
            </p>
          </div>
        </div>

        <!-- City Seal -->
        <div class="flex flex-col items-center justify-center mb-8">
          <div class="w-24 h-24 mb-2">
            <img 
              src="/storage/images/Ligao City Seal.png" 
              alt="Ligao City Seal" 
              class="w-full h-full object-contain"
            >
          </div>
          <p class="text-sm text-gray-500">CITY OF LIGAO</p>
          <p class="text-sm text-gray-500">OFFICIAL SEAL</p>
        </div>

        <!-- Thank You Message -->
        <p class="text-xl text-[#0F5C5C] font-semibold mb-8">
          Salamat sa paggamit ng Quennect!
        </p>

        <!-- Manual Return Button -->
        <button 
          @click="returnToWelcome"
          type="button"
          class="bg-[#0F5C5C] text-white font-semibold py-3 px-8 rounded-lg hover:bg-[#0a4a4a] transition text-lg cursor-pointer"
        >
          Bumalik sa Main Page
        </button>

        <!-- Timer -->
        <p class="text-sm text-gray-400 mt-4">
          Awtomatikong babalik sa main page sa <span class="font-bold text-[#0F5C5C]">{{ timer }}</span> segundo...
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import KioskHeader from '../../components/kiosk/KioskHeader.vue'

const router = useRouter()
const timer = ref(15)
const timerInterval = ref(null)

// Return to welcome page and clear all data
const returnToWelcome = () => {
  console.log('Returning to welcome page...') // Debug log
  
  // Clear interval if still running
  if (timerInterval.value) {
    clearInterval(timerInterval.value)
    timerInterval.value = null
  }
  
  // Clear all stored data
  localStorage.removeItem('selectedOffice')
  localStorage.removeItem('selectedServices')
  localStorage.removeItem('clientDetails')
  
  // Navigate to welcome page
  router.push('/kiosk/welcome')
}

// Start countdown timer
const startTimer = () => {
  // Clear existing interval if any
  if (timerInterval.value) {
    clearInterval(timerInterval.value)
  }
  
  timerInterval.value = setInterval(() => {
    console.log('Timer:', timer.value) // Debug log
    timer.value -= 1
    
    if (timer.value <= 0) {
      console.log('Timer finished, redirecting...')
      clearInterval(timerInterval.value)
      timerInterval.value = null
      returnToWelcome()
    }
  }, 1000)
}

// Start timer when component mounts
onMounted(() => {
  console.log('Closing page mounted')
  startTimer()
})

// Clean up interval when component unmounts
onUnmounted(() => {
  console.log('Closing page unmounted')
  if (timerInterval.value) {
    clearInterval(timerInterval.value)
    timerInterval.value = null
  }
})
</script>