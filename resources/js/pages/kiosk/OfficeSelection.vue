<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    
    <!-- Header -->
    <KioskHeader bgColor="#0F5C5C" textColor="#FFFFFF" />

    <!-- Content -->
    <div class="flex-grow max-w-7xl mx-auto px-3 sm:px-4 py-4 sm:py-8 w-full">
      
      <!-- Description Section -->
      <div class="rounded-lg p-4 sm:p-6 mb-4 sm:mb-8" style="background-color: #5DD2BE;">
        <h2 class="text-xl sm:text-2xl font-bold text-[#2E2E2E] mb-1 sm:mb-2">Ano ang Quennect?</h2>
        <p class="text-sm sm:text-base leading-relaxed" style="color: #000000;">
          Ang Quennect ay ang opisyal na digital queuing system ng Ligao City Hall. 
          Layunin nito na gawing mabilis, maayos, at komportable ang iyong pakikipag-transaksyon 
          sa aming mga opisina. Sa pamamagitan ng Quennect, hindi mo na kailangang tumayo 
          nang matagal sa mahabang pila. Pindutin lamang ang serbisyong kailangan at hintayin 
          ang iyong numero sa monitor.
        </p>
      </div>

      <!-- Office Selection Title -->
      <h1 class="text-2xl sm:text-3xl font-bold text-center text-gray-800 mb-4 sm:mb-8">Pumili ng Opisina</h1>

      <!-- Loading State -->
      <div v-if="loading" class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 lg:gap-6">
        <div v-for="n in 6" :key="n" class="bg-white rounded-xl shadow-md p-4 sm:p-5 animate-pulse">
          <div class="h-5 sm:h-6 bg-gray-200 rounded w-3/4 mb-2"></div>
          <div class="h-3 sm:h-4 bg-gray-200 rounded w-full mb-2"></div>
          <div class="h-3 sm:h-4 bg-gray-200 rounded w-5/6 mb-3 sm:mb-4"></div>
          <div class="h-8 sm:h-10 bg-gray-200 rounded w-full"></div>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="text-center py-8 sm:py-12">
        <p class="text-red-600 text-sm sm:text-base mb-4">{{ error }}</p>
        <button 
          @click="fetchOffices"
          class="bg-[#0F5C5C] text-white px-4 sm:px-6 py-2 rounded-lg hover:bg-[#0a4a4a] transition text-sm sm:text-base"
        >
          Subukan Muli
        </button>
      </div>

      <!-- Offices Grid -->
      <div v-else class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 lg:gap-6">
        <OfficeCard 
          v-for="office in offices" 
          :key="office.id"
          :office="office"
          @select="handleOfficeSelect"
        />
      </div>
    </div>

    <!-- Simple Footer na may Bumalik lang -->
    <div class="w-full bg-gray-200 border-t border-gray-300 px-4 sm:px-6 py-3 sm:py-4">
      <div class="max-w-7xl mx-auto">
        <button 
          @click="goBack" 
          class="flex items-center gap-1 sm:gap-2 text-[#0F5C5C] font-semibold text-sm sm:text-base hover:opacity-80 transition"
        >
          <span class="text-lg sm:text-xl">◀</span> Bumalik
        </button>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import KioskHeader from '../../components/kiosk/KioskHeader.vue'
import OfficeCard from '../../components/kiosk/OfficeCard.vue'
import kioskApi from '../../services/kioskApi'

const router = useRouter()
const offices = ref([])
const loading = ref(true)
const error = ref(null)

// Fetch offices from API
const fetchOffices = async () => {
  loading.value = true
  error.value = null
  
  try {
    const response = await kioskApi.get('/offices')
    offices.value = response.data.data
    console.log('Offices loaded:', offices.value)
  } catch (err) {
    console.error('Error fetching offices:', err)
    error.value = 'Hindi makakuha ng listahan ng opisina. Pakisubukan muli.'
  } finally {
    loading.value = false
  }
}

// Handle office selection - dire-diretso na sa services page
const handleOfficeSelect = (office) => {
  console.log('Selected office:', office)
  // Hindi na kailangan i-save dito kasi sa OfficeCard na mismo
  // Ang OfficeCard na ang magse-save at magna-navigate
}

// Go back to welcome page
const goBack = () => {
  console.log('Going back to welcome page')
  router.push('/kiosk/welcome')
}

onMounted(() => {
  fetchOffices()
})
</script>