<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Header -->
    <KioskHeader bgColor="#0F5C5C" textColor="#FFFFFF" />

    <!-- Content -->
    <div class="flex-grow max-w-4xl mx-auto px-6 sm:px-8 py-4 sm:py-6 w-full">
      
      <!-- Loading State -->
      <div v-if="loading" class="text-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-[#0F5C5C] mx-auto mb-4"></div>
        <p class="text-gray-600">Kinukuha ang iyong queue number...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="text-center py-12">
        <p class="text-red-600 mb-4">{{ error }}</p>
        <button 
          @click="retry"
          class="bg-[#0F5C5C] text-white px-6 py-2 rounded-lg hover:bg-[#0a4a4a] transition"
        >
          Subukan Muli
        </button>
      </div>

      <!-- Success State -->
      <div v-else class="flex flex-col min-h-[80vh]">
        
        <!-- Main Content - Queue Number and Details -->
        <div class="flex-grow">
          <!-- Queue Number Display -->
          <div class="text-center mb-8">
            <div class="text-7xl sm:text-8xl font-bold text-[#0F5C5C] mb-6 py-4">
              {{ queueNumber }}
            </div>
            
            <!-- Instruction Message - Simple and Clean -->
            <p class="text-gray-700 text-lg">
              Pakikuhanan ng litrato o isulat sa papel ang inyong numero.
            </p>
          </div>

          <!-- Client Details Summary -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden max-w-2xl mx-auto">
            <div class="px-6 py-4 border-b border-gray-100">
              <h3 class="font-medium text-gray-700">Mga Detalye ng Transaksyon</h3>
            </div>
            <div class="px-6 py-4">
              <table class="w-full text-sm">
                <tr class="border-b border-gray-50">
                  <td class="text-gray-600 py-2 w-1/3">Opisina:</td>
                  <td class="text-gray-800 py-2">{{ selectedOffice?.name }} ({{ selectedOffice?.acronym }})</td>
                </tr>
                
                <tr class="border-b border-gray-50">
                  <td class="text-gray-600 py-2 align-top">Serbisyo:</td>
                  <td class="text-gray-800 py-2">
                    <span v-for="(service, index) in selectedServices" :key="service.id">
                      {{ service.name }}<span v-if="index < selectedServices.length - 1">, </span>
                    </span>
                  </td>
                </tr>
                
                <tr class="border-b border-gray-50">
                  <td class="text-gray-600 py-2">Pangalan:</td>
                  <td class="text-gray-800 py-2">{{ clientDetails?.full_name }}</td>
                </tr>
                
                <tr class="border-b border-gray-50">
                  <td class="text-gray-600 py-2">Contact Number:</td>
                  <td class="text-gray-800 py-2">{{ clientDetails?.contact_number }}</td>
                </tr>
                
                <tr class="border-b border-gray-50">
                  <td class="text-gray-600 py-2">Barangay:</td>
                  <td class="text-gray-800 py-2">{{ getBarangayName(clientDetails?.barangay_id) }}</td>
                </tr>
                
                <tr class="border-b border-gray-50">
                  <td class="text-gray-600 py-2">Uri ng Lane:</td>
                  <td class="text-gray-800 py-2 capitalize">{{ clientDetails?.lane_type }}</td>
                </tr>
                
                <tr v-if="clientDetails?.lane_type === 'priority' && clientDetails?.priority_sectors?.length > 0">
                  <td class="text-gray-600 py-2">Priority Sector:</td>
                  <td class="text-gray-800 py-2">
                    <span v-for="(sector, index) in getPrioritySectorNames(clientDetails.priority_sectors)" :key="index">
                      {{ sector }}<span v-if="index < clientDetails.priority_sectors.length - 1">, </span>
                    </span>
                  </td>
                </tr>
              </table>
            </div>
          </div>

          <!-- Tapos Button -->
          <div class="flex justify-center mt-8">
            <button 
              @click="finish"
              class="px-8 py-3 rounded-lg bg-[#0F5C5C] text-white font-medium text-base hover:bg-[#0a4a4a] transition min-w-[180px]"
            >
              Tapos
            </button>
          </div>
        </div>

        <!-- Countdown Timer at the Bottom - Minimalist -->
        <div class="text-center pt-8 pb-4 border-t border-gray-200 mt-8">
          <p class="text-gray-500 text-sm">
            Magbabalik sa home page sa {{ countdown }} segundo
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import KioskHeader from '../../components/kiosk/KioskHeader.vue'
import kioskApi from '../../services/kioskApi'

const router = useRouter()
const selectedOffice = ref(null)
const selectedServices = ref([])
const clientDetails = ref(null)
const barangays = ref([])
const prioritySectors = ref([])
const queueNumber = ref('')
const loading = ref(true)
const error = ref(null)
const countdown = ref(30) // Changed from 15 to 30 seconds
let countdownInterval = null

// Get data from localStorage
onMounted(() => {
  const office = localStorage.getItem('selectedOffice')
  const services = localStorage.getItem('selectedServices')
  const client = localStorage.getItem('clientDetails')
  
  if (office) selectedOffice.value = JSON.parse(office)
  if (services) selectedServices.value = JSON.parse(services)
  if (client) clientDetails.value = JSON.parse(client)
  
  // Redirect if missing data
  if (!office || !services || !client) {
    router.push('/kiosk/welcome')
    return
  }
  
  fetchBarangays()
  fetchPrioritySectors()
  generateQueueNumber()
})

// Fetch barangays
const fetchBarangays = async () => {
  try {
    const response = await kioskApi.get('/barangays')
    if (response.data && response.data.data) {
      barangays.value = response.data.data
    }
  } catch (error) {
    console.error('Error fetching barangays:', error)
  }
}

// Fetch priority sectors
const fetchPrioritySectors = async () => {
  try {
    const response = await kioskApi.get('/priority-sectors')
    if (response.data && response.data.data) {
      prioritySectors.value = response.data.data
    }
  } catch (error) {
    console.error('Error fetching priority sectors:', error)
  }
}

// Start countdown timer
const startCountdown = () => {
  if (countdownInterval) clearInterval(countdownInterval)
  
  countdownInterval = setInterval(() => {
    if (countdown.value > 1) {
      countdown.value--
    } else {
      clearInterval(countdownInterval)
      finish()
    }
  }, 1000)
}

// Generate queue number by calling the API
const generateQueueNumber = async () => {
  loading.value = true
  error.value = null
  
  try {
    const payload = {
      office_id: selectedOffice.value.id,
      client_name: clientDetails.value.full_name,
      contact_number: clientDetails.value.contact_number,
      barangay_id: parseInt(clientDetails.value.barangay_id),
      lane_type: clientDetails.value.lane_type,
      service_ids: selectedServices.value.map(s => s.id)
    }
    
    if (clientDetails.value.lane_type === 'priority' && clientDetails.value.priority_sectors?.length > 0) {
      payload.priority_sector_ids = clientDetails.value.priority_sectors
    }
    
    console.log('Sending queue generation request:', payload)
    
    const response = await kioskApi.post('/queue', payload)
    
    console.log('Queue generation response:', response.data)
    
    if (response.data && response.data.data) {
      queueNumber.value = response.data.data.queue_number
      startCountdown()
    } else {
      throw new Error('Invalid response format')
    }
    
  } catch (err) {
    console.error('Error generating queue number:', err)
    error.value = err.response?.data?.message || 'Hindi makakuha ng queue number. Pakisubukan muli.'
  } finally {
    loading.value = false
  }
}

// Retry generating queue number
const retry = () => {
  generateQueueNumber()
}

// Helper: Get barangay name
const getBarangayName = (barangayId) => {
  if (!barangayId) return 'Unknown'
  const barangay = barangays.value.find(b => b.id === barangayId)
  return barangay ? (barangay.barangay_name || barangay.name) : 'Unknown'
}

// Helper: Get priority sector names
const getPrioritySectorNames = (sectorIds) => {
  if (!sectorIds) return []
  return sectorIds.map(id => {
    const sector = prioritySectors.value.find(s => s.id === id)
    return sector ? (sector.sector_name || sector.name) : 'Unknown'
  })
}

// Finish button - clear data and go back to welcome
const finish = () => {
  if (countdownInterval) {
    clearInterval(countdownInterval)
    countdownInterval = null
  }
  
  localStorage.removeItem('selectedOffice')
  localStorage.removeItem('selectedServices')
  localStorage.removeItem('clientDetails')
  
  router.push('/kiosk/welcome')
}

// Clean up interval on component unmount
onUnmounted(() => {
  if (countdownInterval) {
    clearInterval(countdownInterval)
  }
})
</script>

<style scoped>
button {
  transition: all 0.2s ease;
}

button:active {
  transform: scale(0.98);
}

@media (max-width: 640px) {
  .text-7xl {
    font-size: 3.5rem;
  }
}
</style>