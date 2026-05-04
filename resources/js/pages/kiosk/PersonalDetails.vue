<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    
    <!-- Header -->
    <KioskHeader bgColor="#0F5C5C" textColor="#FFFFFF" />

    <!-- Content -->
    <div class="flex-grow max-w-3xl mx-auto px-6 sm:px-8 py-4 sm:py-8 w-full">
      
      <!-- Office and Services Summary -->
      <div v-if="selectedOffice" class="mb-6 sm:mb-8 pb-4 border-b border-gray-200">
        <h2 class="text-xl sm:text-2xl font-bold text-[#1F4E79] mb-2">
          {{ selectedOffice.name }} ({{ selectedOffice.acronym }})
        </h2>
        <p class="text-gray-600 text-sm sm:text-base">
          <span class="font-semibold">Serbisyo:</span> 
          {{ selectedServicesNames }}
        </p>
      </div>

      <!-- Title -->
      <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-2">Ilagay ang iyong Impormasyon</h1>
      <p class="text-gray-600 text-sm sm:text-base mb-6 sm:mb-8">
        Ilagay ang iyong buong pangalan, numero ng cellphone, at barangay. Piliin kung ikaw ay Regular o kabilang sa Priority sector. Kung Priority, pakipili ang uri ng priority.
      </p>

      <!-- Form Container -->
      <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 mb-6">
        <form @submit.prevent="handleNextClick" class="space-y-6">
          
          <!-- Pangalan -->
          <div>
            <label class="block text-base font-semibold text-gray-700 mb-2">Pangalan:</label>
            <input 
              type="text" 
              v-model="form.full_name"
              placeholder="Ilagay ang Pangalan"
              class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-[#0F5C5C] focus:border-transparent outline-none transition bg-white"
              :class="{ 'border-red-500': errors.full_name }"
            >
            <p v-if="errors.full_name" class="text-red-500 text-xs mt-1">{{ errors.full_name }}</p>
          </div>

          <!-- Contact Number -->
          <div>
            <label class="block text-base font-semibold text-gray-700 mb-2">Contact Number:</label>
            <input 
              type="tel" 
              v-model="form.contact_number"
              placeholder="Ilagay ang Contact Number"
              class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-[#0F5C5C] focus:border-transparent outline-none transition bg-white"
              :class="{ 'border-red-500': errors.contact_number }"
            >
            <p v-if="errors.contact_number" class="text-red-500 text-xs mt-1">{{ errors.contact_number }}</p>
          </div>

          <!-- Barangay Dropdown -->
          <div>
            <label class="block text-base font-semibold text-gray-700 mb-2">Barangay:</label>
            <div class="relative">
              <select 
                v-model="form.barangay_id"
                class="w-full px-4 py-3 pr-10 rounded-lg border border-gray-300 focus:ring-2 focus:ring-[#0F5C5C] focus:border-transparent outline-none transition bg-white appearance-none"
                :class="[
                  form.barangay_id ? 'text-gray-900' : 'text-gray-500',
                  { 'border-red-500': errors.barangay_id }
                ]"
              >
                <option value="" class="text-gray-500">Pumili ng Barangay</option>
                <option v-for="barangay in barangays" :key="barangay.id" :value="barangay.id" class="text-gray-900">
                  {{ getBarangayName(barangay) }}
                </option>
              </select>
              <svg
                class="pointer-events-none absolute right-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-500"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <path d="m6 9 6 6 6-6" />
              </svg>
            </div>
            <p v-if="errors.barangay_id" class="text-red-500 text-xs mt-1">{{ errors.barangay_id }}</p>
          </div>

          <!-- Lane Type -->
          <div>
            <label class="block text-base font-semibold text-gray-700 mb-2">Uri ng Pila:</label>
            <div class="flex gap-6">
              <label class="flex items-center">
                <input 
                  type="radio" 
                  v-model="form.lane_type" 
                  value="regular"
                  class="w-4 h-4 text-[#0F5C5C] focus:ring-[#0F5C5C]"
                >
                <span class="ml-2 text-gray-700">Regular</span>
              </label>
              <label class="flex items-center">
                <input 
                  type="radio" 
                  v-model="form.lane_type" 
                  value="priority"
                  class="w-4 h-4 text-[#0F5C5C] focus:ring-[#0F5C5C]"
                >
                <span class="ml-2 text-gray-700">Priority</span>
              </label>
            </div>
          </div>

          <!-- Priority Sectors -->
          <div v-if="form.lane_type === 'priority'">
            <label class="block text-base font-semibold text-gray-700 mb-3">Priority Sector:</label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <label 
                v-for="sector in prioritySectors" 
                :key="sector.id" 
                class="flex items-center p-3 bg-gray-50 rounded-lg border border-gray-200 hover:bg-gray-100 transition cursor-pointer"
              >
                <input 
                  type="checkbox" 
                  :value="sector.id"
                  v-model="form.priority_sectors"
                  class="w-4 h-4 text-[#0F5C5C] focus:ring-[#0F5C5C] rounded flex-shrink-0"
                >
                <span class="ml-3 text-sm text-gray-700">{{ getSectorName(sector) }}</span>
              </label>
            </div>
            <p v-if="errors.priority_sectors" class="text-red-500 text-xs mt-2">{{ errors.priority_sectors }}</p>
          </div>

        </form>
      </div>
    </div>

    <!-- Footer -->
    <KioskFooter 
      @back="goBack"
      @next="handleNextClick"
      :nextVisible="true"
      :nextDisabled="!isFormValid"
    />

    <!-- ✅ DPA Modal (shows first) -->
    <DpaModal
      :show="showDpaModal"
      @close="showDpaModal = false"
      @agree="onDpaAgreed"
    />

    <!-- ✅ Confirmation Modal (shows after DPA is agreed) -->
    <ConfirmationModal 
      :show="showConfirmation"
      :details="confirmationDetails"
      :barangays="barangays"
      :prioritySectors="prioritySectors"
      @close="showConfirmation = false"
      @confirm="confirmAndProceed"
    />

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import KioskHeader from '../../components/kiosk/KioskHeader.vue'
import KioskFooter from '../../components/kiosk/KioskFooter.vue'
import ConfirmationModal from '../../components/kiosk/ConfirmationModal.vue'
import DpaModal from '../../components/modals/DpaModal.vue'   // ✅ new import
import kioskApi from '../../services/kioskApi'

const router = useRouter()
const barangays = ref([])
const prioritySectors = ref([])
const selectedOffice = ref(null)
const selectedServices = ref([])
const showDpaModal = ref(false)       // ✅ new
const showConfirmation = ref(false)

const form = ref({
  full_name: '',
  contact_number: '',
  barangay_id: '',
  lane_type: 'regular',
  priority_sectors: [],
  dpa_consent: false
})

const errors = ref({})

// ✅ DPA consent no longer required here — it's handled via modal
const isFormValid = computed(() => {
  if (!form.value.full_name || !form.value.full_name.trim()) return false
  if (!form.value.contact_number || !form.value.contact_number.trim()) return false
  if (!form.value.barangay_id) return false
  if (form.value.lane_type === 'priority' && (!form.value.priority_sectors || form.value.priority_sectors.length === 0)) return false
  return true
})

const confirmationDetails = computed(() => ({
  office: selectedOffice.value,
  services: selectedServices.value,
  client: form.value
}))

const getBarangayName = (barangay) => barangay.barangay_name || barangay.name || 'Unknown'
const getSectorName = (sector) => sector.sector_name || sector.name || 'Unknown'

onMounted(() => {
  const office = localStorage.getItem('selectedOffice')
  const services = localStorage.getItem('selectedServices')
  
  if (office) {
    selectedOffice.value = JSON.parse(office)
  } else {
    router.push('/kiosk/office-selection')
  }
  
  if (services) {
    selectedServices.value = JSON.parse(services)
  } else {
    router.push('/kiosk/service-selection')
  }
  
  fetchBarangays()
  fetchPrioritySectors()
})

const fetchBarangays = async () => {
  try {
    const response = await kioskApi.get('/barangays')
    barangays.value = response.data?.data ?? (Array.isArray(response.data) ? response.data : [])
  } catch (error) {
    console.error('Error fetching barangays:', error)
  }
}

const fetchPrioritySectors = async () => {
  try {
    const response = await kioskApi.get('/priority-sectors')
    prioritySectors.value = response.data?.data ?? (Array.isArray(response.data) ? response.data : [])
  } catch (error) {
    console.error('Error fetching priority sectors:', error)
  }
}

const selectedServicesNames = computed(() => {
  if (!selectedServices.value?.length) return ''
  return selectedServices.value.map(s => `${s.name} (${s.code})`).join(', ')
})

const validateForm = () => {
  const newErrors = {}
  
  if (!form.value.full_name?.trim())
    newErrors.full_name = 'Paki-input ang iyong pangalan.'
  
  if (!form.value.contact_number?.trim())
    newErrors.contact_number = 'Paki-input ang iyong contact number.'
  else if (!/^(09|\+639)\d{9}$/.test(form.value.contact_number))
    newErrors.contact_number = 'Gumamit ng tamang format: 09123456789 o +639123456789.'
  
  if (!form.value.barangay_id)
    newErrors.barangay_id = 'Pumili ng iyong barangay.'
  
  if (form.value.lane_type === 'priority' && !form.value.priority_sectors?.length)
    newErrors.priority_sectors = 'Pumili ng kahit isang priority sector.'
  
  errors.value = newErrors
  return Object.keys(newErrors).length === 0
}

// ✅ Step 1: Validate form → show DPA modal
const handleNextClick = () => {
  if (!validateForm()) return
  showDpaModal.value = true
}

// ✅ Step 2: User agrees to DPA → set consent + show Confirmation modal
const onDpaAgreed = () => {
  form.value.dpa_consent = true
  showDpaModal.value = false
  showConfirmation.value = true
}

// ✅ Step 3: User confirms details → save and proceed
const confirmAndProceed = () => {
  localStorage.setItem('clientDetails', JSON.stringify(form.value))
  showConfirmation.value = false
  router.push('/kiosk/print')
}

const goBack = () => {
  form.value = {
    full_name: '',
    contact_number: '',
    barangay_id: '',
    lane_type: 'regular',
    priority_sectors: [],
    dpa_consent: false
  }
  localStorage.removeItem('clientDetails')

  if (selectedOffice.value?.id) {
    router.push({ path: '/kiosk/service-selection', query: { officeId: selectedOffice.value.id } })
  } else {
    router.push('/kiosk/service-selection')
  }
}
</script>