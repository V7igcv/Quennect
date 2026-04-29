<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    <KioskHeader bgColor="#0F5C5C" textColor="#FFFFFF" />

    <div class="flex-grow max-w-4xl mx-auto px-6 sm:px-8 py-4 sm:py-6 w-full">
      
      <div v-if="loading" class="text-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-[#0F5C5C] mx-auto mb-4"></div>
        <p class="text-gray-600">Kinukuha ang iyong queue number...</p>
      </div>

      <div v-else-if="error" class="text-center py-12">
        <p class="text-red-600 mb-4">{{ error }}</p>
        <button 
          @click="retry"
          class="bg-[#0F5C5C] text-white px-6 py-2 rounded-lg hover:bg-[#0a4a4a] transition"
        >
          Subukan Muli
        </button>
      </div>

      <div v-else class="flex flex-col min-h-[80vh]">
        
        <div class="flex-grow">
          
          <div class="text-center mb-8">
            <div class="text-7xl sm:text-8xl font-bold text-[#0F5C5C] mb-6 py-4">
              {{ queueNumber }}
            </div>
            <p class="text-gray-700 text-lg">
              Pakikuhanan ng litrato o isulat sa papel ang inyong queue number.
            </p>
          </div>

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

          <!-- Buttons - MAGKATABI NA SILA -->
          <div class="flex flex-row items-center justify-center mt-8 gap-4">
            <!-- Tapos -->
            <button 
              @click="finish"
              class="px-8 py-3 rounded-lg bg-[#0F5C5C] text-white font-medium text-base hover:bg-[#0a4a4a] transition min-w-[150px]"
            >
              Tapos
            </button>

            <!-- View Map -->
            <button
              v-if="mapImageUrl"
              @click="openMap"
              class="px-8 py-3 rounded-lg border border-[#0F5C5C] text-[#0F5C5C] font-medium text-base hover:bg-[#0F5C5C] hover:text-white transition min-w-[150px]"
            >
              Tingnan ang Mapa
            </button>
          </div>
        </div>

        <div class="text-center pt-8 pb-4 border-t border-gray-200 mt-8">
          <p class="text-gray-500 text-sm">
            Magbabalik sa home page sa {{ countdown }} segundo
          </p>
        </div>
      </div>
    </div>

    <!-- MAP MODAL -->
    <div v-if="showMap" class="fixed inset-0 bg-black bg-opacity-80 z-50 flex items-center justify-center p-4" @click.self="closeMap">
      <div class="bg-white rounded-xl w-full max-w-4xl max-h-[90vh] overflow-hidden shadow-xl flex flex-col">
        
        <div class="flex justify-between items-center p-4 border-b">
          <h3 class="text-lg font-semibold text-gray-700">
            Mapa ng {{ selectedOffice?.name }} Office
          </h3>
          <button 
            @click="closeMap"
            class="text-gray-500 hover:text -gray-700 text-xl transition"
          >
            ✕
          </button>
        </div>

        <div class="flex-grow overflow-auto bg-gray-100 flex items-center justify-center p-4">
          <!-- Map image is always mounted so loading starts immediately -->
          <div v-if="mapImageUrl" class="relative flex flex-col items-center w-full">
            <img 
              :src="mapImageUrlWithCache"
              :alt="`Mapa ng ${selectedOffice?.name} Office`"
              class="max-w-full max-h-[70vh] object-contain"
              style="display: block;"
              :class="{ 'opacity-0': mapLoading, 'opacity-100': !mapLoading }"
              @load="onMapLoad"
              @error="onMapError"
            />

            <div v-if="mapLoading" class="absolute inset-0 flex flex-col items-center justify-center bg-gray-100/85">
              <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-[#0F5C5C] mx-auto mb-4"></div>
              <p class="text-gray-600">Naglo-load ng mapa...</p>
            </div>

            <!-- Direct link fallback -->
            <p class="text-xs text-gray-400 mt-3">
              Kung hindi lumalabas ang mapa, 
              <a :href="mapImageUrl" target="_blank" class="text-[#0F5C5C] underline hover:text-[#0a4a4a]">
                i-click dito para buksan sa bagong tab
              </a>
            </p>
          </div>
          
          <!-- Fallback if no map image -->
          <div v-else class="text-center p-8">
            <svg class="w-24 h-24 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
            </svg>
            <p class="text-gray-500">Walang available na mapa</p>
            <p class="text-gray-400 text-sm mt-2">Para sa {{ selectedOffice?.name }}</p>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
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
const countdown = ref(30)
const showMap = ref(false)
const mapLoading = ref(false)

let countdownInterval = null
let timeoutId = null

// Map image URL handler
const mapImageUrl = computed(() => {
  if (!selectedOffice.value?.map_image) return null
  
  let imagePath = selectedOffice.value.map_image
  
  // If it's already a full URL with http
  if (imagePath.startsWith('http://') || imagePath.startsWith('https://')) {
    return imagePath
  }
  
  // If it's an absolute path starting with /
  if (imagePath.startsWith('/')) {
    return window.location.origin + imagePath
  }
  
  // If it starts with storage/ (no leading slash)
  if (imagePath.startsWith('storage/')) {
    return window.location.origin + '/' + imagePath
  }
  
  // Default: assume it's in storage/maps/
  return window.location.origin + '/storage/maps/' + imagePath
})

// Add cache buster to prevent caching issues
const mapImageUrlWithCache = computed(() => {
  if (!mapImageUrl.value) return null
  return mapImageUrl.value + '?t=' + Date.now()
})

onMounted(() => {
  const office = localStorage.getItem('selectedOffice')
  const services = localStorage.getItem('selectedServices')
  const client = localStorage.getItem('clientDetails')
  
  if (office) {
    selectedOffice.value = JSON.parse(office)
    console.log('Office loaded:', selectedOffice.value?.name)
    console.log('Map image:', selectedOffice.value?.map_image)
  }
  
  if (services) selectedServices.value = JSON.parse(services)
  if (client) clientDetails.value = JSON.parse(client)
  
  if (!office || !services || !client) {
    router.push('/kiosk/welcome')
    return
  }
  
  fetchBarangays()
  fetchPrioritySectors()
  generateQueueNumber()
})

const fetchBarangays = async () => {
  try {
    const response = await kioskApi.get('/barangays')
    barangays.value = response.data.data || []
  } catch (error) {
    console.error(error)
  }
}

const fetchPrioritySectors = async () => {
  try {
    const response = await kioskApi.get('/priority-sectors')
    prioritySectors.value = response.data.data || []
  } catch (error) {
    console.error(error)
  }
}

const startCountdown = () => {
  countdownInterval = setInterval(() => {
    if (countdown.value > 1) {
      countdown.value--
    } else {
      finish()
    }
  }, 1000)
}

const generateQueueNumber = async () => {
  loading.value = true
  try {
    const response = await kioskApi.post('/queue', {
      office_id: selectedOffice.value.id,
      client_name: clientDetails.value.full_name,
      contact_number: clientDetails.value.contact_number,
      barangay_id: parseInt(clientDetails.value.barangay_id),
      lane_type: clientDetails.value.lane_type,
      service_ids: selectedServices.value.map(s => s.id)
    })

    queueNumber.value = response.data.data.queue_number
    startCountdown()
  } catch (err) {
    error.value = 'Hindi makakuha ng queue number.'
  } finally {
    loading.value = false
  }
}

const retry = () => generateQueueNumber()

const getBarangayName = (id) => {
  return barangays.value.find(b => b.id === id)?.barangay_name || 'Unknown'
}

const getPrioritySectorNames = (ids) => {
  return ids.map(id => prioritySectors.value.find(s => s.id === id)?.sector_name || 'Unknown')
}

const openMap = () => {
  console.log('Opening map URL:', mapImageUrl.value)
  showMap.value = true
  mapLoading.value = true
  
  // Set timeout to hide loading if stuck
  if (timeoutId) clearTimeout(timeoutId)
  timeoutId = setTimeout(() => {
    if (mapLoading.value === true) {
      console.log('Loading timeout - forcing hide')
      mapLoading.value = false
    }
  }, 8000)
}

const closeMap = () => {
  showMap.value = false
  mapLoading.value = false
  if (timeoutId) clearTimeout(timeoutId)
}

const onMapLoad = () => {
  console.log('Map loaded successfully')
  mapLoading.value = false
  if (timeoutId) clearTimeout(timeoutId)
}

const onMapError = () => {
  console.error('Failed to load map:', mapImageUrl.value)
  mapLoading.value = false
  if (timeoutId) clearTimeout(timeoutId)
}

const finish = () => {
  clearInterval(countdownInterval)
  localStorage.removeItem('selectedOffice')
  localStorage.removeItem('selectedServices')
  localStorage.removeItem('clientDetails')
  router.push('/kiosk/welcome')
}

onUnmounted(() => {
  clearInterval(countdownInterval)
  if (timeoutId) clearTimeout(timeoutId)
})
</script>