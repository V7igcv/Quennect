<template>
  <div class="max-w-4xl mx-auto px-2 py-2">
    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-sm text-gray-500 mb-4">
      <span>Internal Transactions</span>
      <span>/</span>
      <span class="text-gray-800 font-medium">Create Request</span>
    </div>

    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-gray-800">Create New Request</h2>
      <button 
        @click="goBack"
        class="text-gray-500 hover:text-gray-700 flex items-center gap-1"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        Back
      </button>
    </div>

    <!-- Step 1: Select Office -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
      <h3 class="text-lg font-semibold mb-2">Step 1: Select Office</h3>
      <p class="text-sm text-gray-500 mb-4">Choose the office you want to send this request to</p>
      <select 
        v-model="selectedOfficeId"
        class="w-full border rounded-lg p-3 text-sm focus:ring-2 focus:ring-[#0F5C5C] focus:border-transparent"
      >
        <option value="">Select an office</option>
        <option v-for="office in offices" :key="office.id" :value="office.id">
          {{ office.name }} ({{ office.acronym }})
        </option>
      </select>
    </div>

    <!-- Step 2: Select Services -->
    <div v-if="selectedOfficeId" class="bg-white rounded-lg shadow p-6 mb-6">
      <h3 class="text-lg font-semibold mb-2">Step 2: Select Services</h3>
      <p class="text-sm text-gray-500 mb-4">Choose the services you need (you can select multiple)</p>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div 
          v-for="service in services" 
          :key="service.id"
          @click="toggleService(service)"
          class="border rounded-lg p-3 cursor-pointer transition-all duration-200 hover:shadow-md"
         :class="selectedServices.some(s => s.id === service.id) 
  ? 'border-2 border-[#0F5C5C] shadow-sm' 
  : 'border border-gray-200 hover:border-[#0F5C5C]'"
        >
          <div class="flex items-start gap-3">
            <div class="flex-shrink-0 mt-0.5">
              <div 
                class="w-5 h-5 rounded border-2 flex items-center justify-center transition-all"
                :class="selectedServices.some(s => s.id === service.id) ? 'bg-[#0F5C5C] border-[#0F5C5C]' : 'border-gray-300'"
              >
                <svg v-if="selectedServices.some(s => s.id === service.id)" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
              </div>
            </div>
            <div class="flex-1">
              <p class="font-medium text-sm text-gray-800">{{ service.name }}</p>
              <p class="text-xs text-gray-500 mt-0.5">{{ service.code }}</p>
              <p class="text-xs text-gray-400 mt-1 line-clamp-2">{{ service.description }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Selected Services Summary -->
      <div v-if="selectedServices.length > 0" class="mt-4 p-3 bg-gray-50 rounded-lg">
        <p class="text-sm font-medium text-gray-700">Selected Services:</p>
        <div class="flex flex-wrap gap-2 mt-2">
          <span 
            v-for="service in selectedServices" 
            :key="service.id"
            class="px-2 py-1 bg-[#0F5C5C] text-white text-xs rounded-full flex items-center gap-1"
          >
            {{ service.name }}
            <button @click="removeService(service)" class="ml-1 text-white hover:text-gray-200">×</button>
          </span>
        </div>
      </div>
    </div>

    <!-- Step 3: Personal Information -->
    <div v-if="selectedServices.length > 0" class="bg-white rounded-lg shadow p-6 mb-6">
      <h3 class="text-lg font-semibold mb-2">Step 3: Personal Information</h3>
      <p class="text-sm text-gray-500 mb-4">Provide your contact details</p>
      
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
          <input 
            v-model="form.full_name"
            type="text"
            class="w-full border border-gray-200 rounded-lg p-3 text-sm focus:ring-2 focus:ring-[#0F5C5C] focus:border-transparent transition"
            placeholder="Enter your full name"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
          <input 
            v-model="form.contact_number"
            type="text"
            class="w-full border border-gray-200 rounded-lg p-3 text-sm focus:ring-2 focus:ring-[#0F5C5C] focus:border-transparent transition"
            placeholder="e.g., 09123456789"
          />
        </div>
      </div>
    </div>

    <!-- Step 4: Requirements (Optional) -->
    <div v-if="selectedServices.length > 0" class="bg-white rounded-lg shadow p-6 mb-6">
      <h3 class="text-lg font-semibold mb-2">Step 4: Attach Requirements (Optional)</h3>
      <p class="text-sm text-gray-500 mb-4">Upload your requirements to Google Drive and paste the link here</p>
      
      <textarea 
        v-model="form.requirement_link"
        class="w-full border border-gray-200 rounded-lg p-3 text-sm focus:ring-2 focus:ring-[#0F5C5C] focus:border-transparent transition"
        rows="2"
        placeholder="https://drive.google.com/file/d/xxxxx/view"
      ></textarea>
      <p class="text-xs text-gray-400 mt-2">
        Please prepare soft copies of your requirements. Upload them to Google Drive and attach the link here.
      </p>
    </div>

    <!-- Request Notes (Optional) -->
    <div v-if="selectedServices.length > 0" class="bg-white rounded-lg shadow p-6 mb-6">
      <h3 class="text-lg font-semibold mb-2">Request Notes (Optional)</h3>
      <p class="text-sm text-gray-500 mb-4">Add any additional notes or instructions</p>
      
      <textarea 
        v-model="form.request_notes"
        class="w-full border border-gray-200 rounded-lg p-3 text-sm focus:ring-2 focus:ring-[#0F5C5C] focus:border-transparent transition"
        rows="3"
        placeholder="Enter any additional notes..."
      ></textarea>
    </div>

    <!-- Submit Button -->
    <div class="flex justify-end gap-3 mt-6" v-if="canSubmit">
      <button 
        @click="goBack"
        class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition"
      >
        Cancel
      </button>
      <button 
        @click="submitRequest"
        :disabled="submitting"
        class="px-6 py-2 bg-[#0F5C5C] text-white rounded-lg hover:bg-[#0a4a4a] transition disabled:opacity-50"
      >
        {{ submitting ? 'Submitting...' : 'Submit Request' }}
      </button>
    </div>

    <!-- Confirmation Modal with Blur Background -->
    <div v-if="showConfirmModal" class="fixed inset-0 backdrop-blur-md bg-black/20 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl w-full max-w-2xl p-6 shadow-xl transform transition-all">
        <h3 class="text-xl font-semibold text-gray-800 mb-4">Confirm Request</h3>
        <div class="space-y-3 text-sm">
          <div class="flex py-2 border-b border-gray-100">
            <span class="font-medium w-32 text-gray-600">To Office:</span>
            <span class="text-gray-800">{{ getOfficeName(selectedOfficeId) }}</span>
          </div>
          <div class="flex py-2 border-b border-gray-100">
            <span class="font-medium w-32 text-gray-600">Services:</span>
            <span class="text-gray-800">{{ selectedServices.map(s => s.name).join(', ') }}</span>
          </div>
          <div class="flex py-2 border-b border-gray-100">
            <span class="font-medium w-32 text-gray-600">Full Name:</span>
            <span class="text-gray-800">{{ form.full_name }}</span>
          </div>
          <div class="flex py-2 border-b border-gray-100">
            <span class="font-medium w-32 text-gray-600">Contact:</span>
            <span class="text-gray-800">{{ form.contact_number }}</span>
          </div>
          <div v-if="form.requirement_link" class="flex py-2 border-b border-gray-100">
            <span class="font-medium w-32 text-gray-600">Requirement Link:</span>
            <a :href="form.requirement_link" target="_blank" class="text-blue-600 hover:text-blue-800 truncate">{{ form.requirement_link }}</a>
          </div>
          <div v-if="form.request_notes" class="flex py-2">
            <span class="font-medium w-32 text-gray-600">Notes:</span>
            <span class="text-gray-800">{{ form.request_notes }}</span>
          </div>
        </div>
        <div class="flex justify-end gap-3 mt-6">
          <button @click="showConfirmModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">Cancel</button>
          <button @click="confirmSubmit" class="px-4 py-2 bg-[#0F5C5C] text-white rounded-lg hover:bg-[#0a4a4a] transition">Confirm</button>
        </div>
      </div>
    </div>

    <!-- Success Message with Blur Background -->
    <div v-if="showSuccess" class="fixed inset-0 backdrop-blur-md bg-black/20 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl w-full max-w-md p-6 text-center shadow-xl transform transition-all">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-800 mb-2">Request Submitted!</h3>
        <p class="text-sm text-gray-600 mb-4">Your request has been successfully submitted and is now pending.</p>
        <button @click="goToDashboard" class="px-4 py-2 bg-[#0F5C5C] text-white rounded-lg hover:bg-[#0a4a4a] transition">OK</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import kioskApi from '../../services/kioskApi'

const router = useRouter()

const offices = ref([])
const services = ref([])
const selectedOfficeId = ref('')
const selectedServices = ref([])
const submitting = ref(false)
const showConfirmModal = ref(false)
const showSuccess = ref(false)

const form = ref({
  full_name: '',
  contact_number: '',
  requirement_link: '',
  request_notes: ''
})

const canSubmit = computed(() => {
  return selectedOfficeId.value && 
         selectedServices.value.length > 0 && 
         form.value.full_name.trim() && 
         form.value.contact_number.trim()
})

const fetchOffices = async () => {
  try {
    const res = await kioskApi.get('/offices')
    offices.value = res.data.data
  } catch (err) {
    console.error(err)
  }
}

const fetchServices = async () => {
  if (!selectedOfficeId.value) return
  console.log('Fetching services for office ID:', selectedOfficeId.value)
  try {
    const res = await kioskApi.get(`/internal/offices/${selectedOfficeId.value}/services`)
    services.value = res.data.data || []
  } catch (err) {
    console.error('Error fetching internal services:', err)
    services.value = []
  }
}

const getOfficeName = (id) => {
  const office = offices.value.find(o => o.id === id)
  return office ? `${office.name} (${office.acronym})` : ''
}

const toggleService = (service) => {
  const index = selectedServices.value.findIndex(s => s.id === service.id)
  if (index === -1) {
    selectedServices.value.push(service)
  } else {
    selectedServices.value.splice(index, 1)
  }
}

const removeService = (service) => {
  const index = selectedServices.value.findIndex(s => s.id === service.id)
  if (index !== -1) {
    selectedServices.value.splice(index, 1)
  }
}

const submitRequest = () => {
  showConfirmModal.value = true
}

const confirmSubmit = async () => {
  showConfirmModal.value = false
  submitting.value = true

  try {
    const payload = {
      to_office_id: parseInt(selectedOfficeId.value),
      service_ids: selectedServices.value.map(s => s.id),
      full_name: form.value.full_name,
      contact_number: form.value.contact_number,
      requirement_link: form.value.requirement_link || null,
      request_notes: form.value.request_notes || null,
      transaction_date: new Date().toISOString().split('T')[0],
      expected_completion_date: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString()
    }

    console.log('Submitting payload:', payload)

    const response = await kioskApi.post('/frontdesk/internal-transactions/requests', payload)
    console.log('Response:', response.data)
    
    showSuccess.value = true
  } catch (err) {
    console.error('Submit error:', err.response?.data || err.message)
    alert('Failed to submit request. Please try again.')
  } finally {
    submitting.value = false
  }
}

const goToDashboard = () => {
  router.push('/frontdesk/internal-transactions')
}

const goBack = () => {
  router.push('/frontdesk/internal-transactions')
}

watch(selectedOfficeId, () => {
  selectedServices.value = []
  fetchServices()
})

onMounted(() => {
  fetchOffices()
})
</script>