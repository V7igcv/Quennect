<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    
    <!-- Header -->
    <KioskHeader bgColor="#0F5C5C" textColor="#FFFFFF" />

    <!-- Content -->
    <div class="flex-grow max-w-3xl mx-auto px-6 sm:px-8 py-4 sm:py-8 w-full">
      
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

      <!-- Success State - with queue number -->
      <div v-else>
        <!-- Queue Number Display -->
        <div class="text-center mb-8">
          <p class="text-gray-700 text-lg mb-2">Ito ang iyong numero sa pila.</p>
          <p class="text-gray-600 text-sm mb-4">
            Nais mo bang i-print ang iyong numero? Kung oo, pindutin ang <span class="font-semibold">Print</span> button. 
            Kung hindi, pindutin ang <span class="font-semibold">Tapos</span> upang magpatuloy.
          </p>
          
          <!-- Queue Number - Large Display -->
          <div class="text-6xl sm:text-7xl font-bold text-[#0F5C5C] mb-4">
            {{ queueNumber }}
          </div>
        </div>

        <!-- System Name (separator) -->
        <div class="text-center mb-6">
          <p class="text-lg font-semibold text-gray-700">Quennect</p>
          <p class="text-sm text-gray-500">General Queuing System</p>
        </div>

        <!-- Client Details Summary - Table Format -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-8">
          <table class="w-full">
            <tr class="border-b border-gray-200">
              <td class="font-semibold text-gray-700 py-3 w-1/3">Opisina:</td>
              <td class="text-gray-900 py-3">{{ selectedOffice?.name }} ({{ selectedOffice?.acronym }})</td>
            </tr>
            
            <tr class="border-b border-gray-200">
              <td class="font-semibold text-gray-700 py-3 align-top">Serbisyo:</td>
              <td class="text-gray-900 py-3">
                <div v-for="(service, index) in selectedServices" :key="service.id">
                  {{ service.name }}<span v-if="index < selectedServices.length - 1">,</span>
                </div>
              </td>
            </tr>
            
            <tr class="border-b border-gray-200">
              <td class="font-semibold text-gray-700 py-3">Pangalan:</td>
              <td class="text-gray-900 py-3">{{ clientDetails?.full_name }}</td>
            </tr>
            
            <tr class="border-b border-gray-200">
              <td class="font-semibold text-gray-700 py-3">Contact Number:</td>
              <td class="text-gray-900 py-3">{{ clientDetails?.contact_number }}</td>
            </tr>
            
            <tr class="border-b border-gray-200">
              <td class="font-semibold text-gray-700 py-3">Barangay:</td>
              <td class="text-gray-900 py-3">{{ getBarangayName(clientDetails?.barangay_id) }}</td>
            </tr>
            
            <tr class="border-b border-gray-200">
              <td class="font-semibold text-gray-700 py-3">Uri ng Lane:</td>
              <td class="text-gray-900 py-3 capitalize">{{ clientDetails?.lane_type }}</td>
            </tr>
            
            <tr v-if="clientDetails?.lane_type === 'priority' && clientDetails?.priority_sectors?.length > 0" class="border-b border-gray-200">
              <td class="font-semibold text-gray-700 py-3">Priority Sector:</td>
              <td class="text-gray-900 py-3">
                <span v-for="(sector, index) in getPrioritySectorNames(clientDetails.priority_sectors)" :key="index">
                  {{ sector }}<span v-if="index < clientDetails.priority_sectors.length - 1">, </span>
                </span>
              </td>
            </tr>
          </table>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <button 
            @click="finish"
            class="px-8 py-4 rounded-lg border-2 border-[#0F5C5C] text-[#0F5C5C] font-semibold text-lg hover:bg-gray-50 transition min-w-[200px]"
          >
            Tapusin
          </button>
          <button 
            @click="print"
            class="px-8 py-4 rounded-lg bg-[#0F5C5C] text-white font-semibold text-lg hover:bg-[#0a4a4a] transition min-w-[200px]"
          >
            I-print
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
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

// Generate queue number by calling the API
const generateQueueNumber = async () => {
  loading.value = true
  error.value = null
  
  try {
    // Prepare the request payload
    const payload = {
      office_id: selectedOffice.value.id,
      client_name: clientDetails.value.full_name,
      contact_number: clientDetails.value.contact_number,
      barangay_id: parseInt(clientDetails.value.barangay_id),
      lane_type: clientDetails.value.lane_type,
      service_ids: selectedServices.value.map(s => s.id)
    }
    
    // Add priority sectors if lane_type is priority
    if (clientDetails.value.lane_type === 'priority' && clientDetails.value.priority_sectors?.length > 0) {
      payload.priority_sector_ids = clientDetails.value.priority_sectors
    }
    
    console.log('Sending queue generation request:', payload)
    
    // Call the API
    const response = await kioskApi.post('/queue', payload)
    
    console.log('Queue generation response:', response.data)
    
    // Set the queue number from API response
    if (response.data && response.data.data) {
      queueNumber.value = response.data.data.queue_number
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

// Finish button - go back to welcome
const finish = () => {
  // Clear all stored data
  localStorage.removeItem('selectedOffice')
  localStorage.removeItem('selectedServices')
  localStorage.removeItem('clientDetails')
  
  router.push('/kiosk/welcome')
}

// Print button - show confirmation page
const print = () => {
  // TODO: Trigger thermal printer
  // Navigate to confirmation page with timer
  router.push('/kiosk/closing')
}
</script>