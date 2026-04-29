<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    
    <!-- Header -->
    <KioskHeader bgColor="#0F5C5C" textColor="#FFFFFF" />

    <!-- Content -->
    <div class="flex-grow max-w-7xl mx-auto px-6 sm:px-8 py-4 sm:py-8 w-full">
      
      <!-- Office Info Section -->
      <div v-if="selectedOffice" class="flex items-center gap-4 sm:gap-6 mb-6 sm:mb-8 pb-4 border-b border-gray-200">
        
        <!-- Logo -->
        <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-gray-100 flex items-center justify-center overflow-hidden border-2 border-gray-200 flex-shrink-0">
          <img 
            v-if="selectedOffice.logo"
            :src="selectedOffice.logo" 
            :alt="selectedOffice.name"
            class="w-full h-full object-cover"
          >
          <div 
            v-else
            class="w-full h-full bg-[#0F5C5C] bg-opacity-10 flex items-center justify-center"
          >
            <span class="text-xl sm:text-2xl font-bold text-[#0F5C5C]">
              {{ selectedOffice.acronym?.charAt(0) || selectedOffice.name?.charAt(0) }}
            </span>
          </div>
        </div>
        
        <!-- Office Details -->
        <div class="flex-1">
          <h2 class="text-xl sm:text-2xl font-bold text-[#1F4E79] mb-1">
            {{ selectedOffice.name }} ({{ selectedOffice.acronym }})
          </h2>
          <p class="text-gray-600 text-sm sm:text-base leading-relaxed">
            {{ selectedOffice.description }}
          </p>
        </div>
      </div>

      <!-- Services Title -->
      <h1 class="text-2xl sm:text-3xl font-bold text-center text-gray-800 mb-2">Pumili ng Serbisyo</h1>
      <p class="text-center text-gray-600 text-sm sm:text-base mb-6 sm:mb-8">
        I-click ang mga kahon ng mga serbisyong nais mong kunin sa opisinang ito.
      </p>

      <!-- Loading State -->
      <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 lg:gap-6">
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
          @click="fetchServices"
          class="bg-[#0F5C5C] text-white px-4 sm:px-6 py-2 rounded-lg hover:bg-[#0a4a4a] transition text-sm sm:text-base"
        >
          Subukan Muli
        </button>
      </div>

      <!-- No Services State -->
      <div v-else-if="services.length === 0" class="text-center py-8 sm:py-12">
        <p class="text-gray-600 text-base sm:text-lg">Walang available na serbisyo para sa opisina na ito.</p>
      </div>

      <!-- Services Grid - SORTED BY NAME -->
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 lg:gap-6">
        <ServiceCard 
          v-for="service in sortedServices" 
          :key="service.id"
          :service="service"
          :isSelected="selectedServices.some(s => s.id === service.id)"
          @toggle="toggleService"
        />
      </div>

      <!-- Selected Services Summary -->
      <div v-if="selectedServices.length > 0" class="mt-6 sm:mt-8 p-4 bg-white rounded-lg shadow-md">
        <h3 class="font-bold text-[#1F4E79] mb-2">Napiling mga Serbisyo:</h3>
        <div class="flex flex-wrap gap-2">
          <span 
            v-for="service in selectedServices" 
            :key="service.id"
            class="px-3 py-1 bg-[#0F5C5C] text-white text-sm rounded-full flex items-center"
          >
            {{ service.name }} ({{ service.code }})
            <button @click="removeService(service)" class="ml-2 text-white hover:text-gray-200">
              ×
            </button>
          </span>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="w-full bg-gray-200 border-t border-gray-300 px-4 sm:px-6 py-3 sm:py-4 sticky bottom-0 z-50">
      <div class="max-w-7xl mx-auto flex justify-between items-center">
        <button 
          @click="goBack" 
          class="flex items-center gap-1 sm:gap-2 text-[#0F5C5C] font-semibold text-sm sm:text-base hover:opacity-80 transition cursor-pointer"
        >
          <Triangle class="w-4 h-4 sm:w-5 sm:h-5 rotate-270 fill-current" />Bumalik
        </button>

        <button 
          @click="continueToDetails"
          class="flex items-center gap-1 sm:gap-2 text-[#0F5C5C] font-semibold text-sm sm:text-base hover:opacity-80 transition cursor-pointer"
          :class="{ 'opacity-50 cursor-not-allowed': selectedServices.length === 0 }"
          :disabled="selectedServices.length === 0"
        >
          Magpatuloy <Triangle class="w-4 h-4 sm:w-5 sm:h-5 rotate-90 fill-current" />
        </button>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import KioskHeader from '../../components/kiosk/KioskHeader.vue'
import ServiceCard from '../../components/kiosk/ServiceCard.vue'
import kioskApi from '../../services/kioskApi'
import { Triangle } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const selectedOffice = ref(null)
const services = ref([])
const selectedServices = ref([])
const loading = ref(true)
const error = ref(null)

// Get officeId from URL
const officeId = route.query.officeId

// Sort services by name alphabetically
const sortedServices = computed(() => {
  return [...services.value].sort((a, b) => a.name.localeCompare(b.name))
})

// Fetch services
const fetchServices = async () => {
  let activeOfficeId = officeId

  // Get office from localStorage
  const storedOffice = localStorage.getItem('selectedOffice')
  if (storedOffice) {
    selectedOffice.value = JSON.parse(storedOffice)
    if (!activeOfficeId) {
      activeOfficeId = selectedOffice.value.id
    }
  }

  if (!activeOfficeId) {
    error.value = 'Walang napiling opisina.'
    loading.value = false
    return
  }

  loading.value = true
  error.value = null
  
  try {
    
    // Fetch services for this office
    const response = await kioskApi.get(`/offices/${activeOfficeId}/services`)
    services.value = response.data.data
    console.log('Services loaded:', services.value)
  } catch (err) {
    console.error('Error fetching services:', err)
    error.value = 'Hindi makakuha ng listahan ng serbisyo. Pakisubukan muli.'
  } finally {
    loading.value = false
  }
}

// Toggle service selection
const toggleService = (service) => {
  const index = selectedServices.value.findIndex(s => s.id === service.id)
  if (index === -1) {
    selectedServices.value.push(service)
  } else {
    selectedServices.value.splice(index, 1)
  }
  console.log('Selected services:', selectedServices.value)
}

// Remove specific service
const removeService = (service) => {
  const index = selectedServices.value.findIndex(s => s.id === service.id)
  if (index !== -1) {
    selectedServices.value.splice(index, 1)
  }
}

// Go back to office selection
const goBack = () => {
  router.push('/kiosk/office-selection')
}

// Continue to personal details
const continueToDetails = () => {
  if (selectedServices.value.length === 0) return
  
  // Save selected services to localStorage
  localStorage.setItem('selectedServices', JSON.stringify(selectedServices.value))
  console.log('Proceeding to personal details with services:', selectedServices.value)
  router.push('/kiosk/personal-details')
}

onMounted(() => {
  fetchServices()
})
</script>