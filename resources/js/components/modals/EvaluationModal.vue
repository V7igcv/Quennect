<!-- src/components/modals/EvaluationModal.vue -->
<template>
  <div v-if="modelValue" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50">
    <div  ref="modalContent" class="bg-white rounded-lg w-full max-w-5xl mx-4 py-8 px-12 flex flex-col max-h-[90vh] overflow-y-auto">
      <div class="flex justify-end items-center">
        <button 
          @click="closeModal"
          class="text-gray-400 hover:text-gray-600 transition-colors"
        >
          <X class="w-5 h-5 cursor-pointer" />
        </button>
      </div>
      
      <!-- Modal Header (same for both pages) -->
      <div class="flex justify-center items-center pb-4">
        <!-- System Logo -->
        <img 
          src="/storage/logos/Ligao City Seal.png" 
          class="w-14 h-14 rounded-full object-contain flex-shrink-0"
          alt="Ligao Logo"
        >
        <div class="pl-3">
          <h2 class="font-bold text-2xl whitespace-nowrap text-[#1F4E79]">Quennect</h2>
          <h4 class="font-semibold text-gray-900">Client Satisfaction Survey</h4>
        </div>
      </div>

      <div class="border-t border-gray-200 w-11/11 mx-auto mb-4"></div>

      <!-- Customer Info -->
      <div class="rounded-lg border border-gray-200 bg-gray-50/70 p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div class="rounded-md border border-gray-200 bg-white px-3 py-2">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Queue Number</p>
            <p class="mt-1 text-sm font-semibold text-[#1F2937]">{{ queueNumber || 'N/A' }}</p>
          </div>

          <div class="rounded-md border border-gray-200 bg-white px-3 py-2">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Name</p>
            <p class="mt-1 text-sm font-semibold text-[#1F2937]">{{ customerName || 'N/A' }}</p>
          </div>

          <div class="rounded-md border border-gray-200 bg-white px-3 py-2">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Contact Number</p>
            <p class="mt-1 text-sm font-semibold text-[#1F2937]">{{ contactNumber || 'N/A' }}</p>
          </div>

          <div class="rounded-md border border-gray-200 bg-white px-3 py-2">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Barangay</p>
            <p class="mt-1 text-sm font-semibold text-[#1F2937]">{{ barangay || 'N/A' }}</p>
          </div>

          <div class="rounded-md border border-gray-200 bg-white px-3 py-2 sm:col-span-2">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Service(s)</p>
            <p class="mt-1 text-sm font-semibold text-[#1F2937]">{{ servicesDisplay }}</p>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
        <div>
          <label class="block text-sm font-medium text-[#2E2E2E] mb-1">Client Type</label>
          <div class="relative">
            <select
              v-model="clientType"
              class="w-full appearance-none border border-gray-300 rounded-md px-3 pr-8 py-2 text-sm focus:ring-2 focus:ring-[#0F5C5C] focus:border-[#0F5C5C]"
            >
              <option value="">Select client type</option>
              <option value="Citizen">Citizen</option>
              <option value="Business">Business</option>
              <option value="Government">Government</option>
            </select>
            <ChevronDown class="w-4 h-4 text-gray-500 absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none" />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-[#2E2E2E] mb-1">Sex</label>
          <div class="relative">
            <select
              v-model="sex"
              class="w-full appearance-none border border-gray-300 rounded-md px-3 pr-8 py-2 text-sm focus:ring-2 focus:ring-[#0F5C5C] focus:border-[#0F5C5C]"
            >
              <option value="">Select sex</option>
              <option value="Male">Male</option>
              <option value="Female">Female</option>
            </select>
            <ChevronDown class="w-4 h-4 text-gray-500 absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none" />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-[#2E2E2E] mb-1">Age</label>
          <input
            v-model.number="age"
            type="number"
            min="0"
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-[#0F5C5C] focus:border-[#0F5C5C]"
            placeholder="Enter age"
          >
        </div>
      </div>

      <!-- Assistance Fields per Service -->
      <div v-if="servicesWithAssistance.length > 0" class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-4">
        <div v-for="service in servicesWithAssistance" :key="service.id">
          
          <!-- Traditional Service (no assistance types) - Show input field only -->
          <template v-if="!hasAssistanceTypes(service.id)">
            <label class="block text-sm font-medium text-[#2E2E2E] mb-1">
              {{ service.service_name }} - Assistance (₱)
            </label>
            <input
              v-model.number="assistanceAmounts[service.id]"
              type="number"
              min="0"
              step="0.01"
              class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-[#0F5C5C] focus:border-[#0F5C5C]"
              placeholder="Enter assistance amount"
            >

            <div class="mt-2">
              <label class="block text-sm font-medium text-[#2E2E2E] mb-1">Indicator (Optional)</label>
              <div class="flex items-center gap-4">
                <button
                  type="button"
                  @click="toggleAssistanceIndicator(service.id, 1)"
                  class="inline-flex items-center gap-2 text-sm text-[#474C55] cursor-pointer"
                >
                  <span
                    class="h-4 w-4 border border-gray-400 rounded-sm flex items-center justify-center text-[10px] leading-none"
                    :class="isAssistanceIndicatorSelected(service.id, 1) ? 'bg-[#0F5C5C] border-[#0F5C5C] text-white' : 'bg-white text-transparent'"
                  >
                    ✓
                  </span>
                  <span>1</span>
                </button>
                <button
                  type="button"
                  @click="toggleAssistanceIndicator(service.id, 2)"
                  class="inline-flex items-center gap-2 text-sm text-[#474C55] cursor-pointer"
                >
                  <span
                    class="h-4 w-4 border border-gray-400 rounded-sm flex items-center justify-center text-[10px] leading-none"
                    :class="isAssistanceIndicatorSelected(service.id, 2) ? 'bg-[#0F5C5C] border-[#0F5C5C] text-white' : 'bg-white text-transparent'"
                  >
                    ✓
                  </span>
                  <span>2</span>
                </button>
              </div>
            </div>
          </template>

          <!-- Categorized Service (AICS-like) - Show dropdown + input field -->
          <template v-else>
            <label class="block text-sm font-medium text-[#2E2E2E] mb-1">
              {{ service.service_name }} - Select Assistance Type
            </label>
            <div class="relative mb-2">
              <select
                v-model="selectedAssistanceTypes[getQueueTransactionServiceId(service.id)]"
                class="w-full appearance-none border border-gray-300 rounded-md px-3 pr-8 py-2 text-sm focus:ring-2 focus:ring-[#0F5C5C] focus:border-[#0F5C5C]"
              >
                <option value="">-- Select Assistance Type --</option>
                <option 
                  v-for="type in assistanceTypes[service.id]"
                  :key="type.id"
                  :value="type.id"
                >
                  {{ type.assistance_name }}
                </option>
              </select>
              <ChevronDown class="w-4 h-4 text-gray-500 absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none" />
            </div>

            <!-- Amount input appears only if assistance type selected -->
            <div v-if="selectedAssistanceTypes[getQueueTransactionServiceId(service.id)]">
              <label class="block text-sm font-medium text-[#2E2E2E] mb-1">
                Amount (₱)
              </label>
              <input
                v-model.number="assistanceAmounts[service.id]"
                type="number"
                min="0"
                step="0.01"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-[#0F5C5C] focus:border-[#0F5C5C]"
                placeholder="Enter assistance amount"
              >

              <div class="mt-2">
                <label class="block text-sm font-medium text-[#2E2E2E] mb-1">Indicator (Optional)</label>
                <div class="flex items-center gap-4">
                  <button
                    type="button"
                    @click="toggleAssistanceIndicator(service.id, 1)"
                    class="inline-flex items-center gap-2 text-sm text-[#474C55] cursor-pointer"
                  >
                    <span
                      class="h-4 w-4 border border-gray-400 rounded-sm flex items-center justify-center text-[10px] leading-none"
                      :class="isAssistanceIndicatorSelected(service.id, 1) ? 'bg-[#0F5C5C] border-[#0F5C5C] text-white' : 'bg-white text-transparent'"
                    >
                      ✓
                    </span>
                    <span>1</span>
                  </button>
                  <button
                    type="button"
                    @click="toggleAssistanceIndicator(service.id, 2)"
                    class="inline-flex items-center gap-2 text-sm text-[#474C55] cursor-pointer"
                  >
                    <span
                      class="h-4 w-4 border border-gray-400 rounded-sm flex items-center justify-center text-[10px] leading-none"
                      :class="isAssistanceIndicatorSelected(service.id, 2) ? 'bg-[#0F5C5C] border-[#0F5C5C] text-white' : 'bg-white text-transparent'"
                    >
                      ✓
                    </span>
                    <span>2</span>
                  </button>
                </div>
              </div>
            </div>
          </template>

        </div>
      </div>

      <div class="border-t border-gray-200 w-11/11 mx-auto my-4"></div>

      <!-- Page 1: CC Survey Questions -->
      <div v-if="currentPage === 1" class="space-y-6">
        <div v-if="multipleChoiceQuestions.length === 0" class="text-sm text-gray-500">
          Loading questions...
        </div>

        <div v-for="question in visibleMultipleChoiceQuestions" :key="question.id">
          <h3 class="font-semibold text-[#2E2E2E] mb-3">
            # {{ question.question_code }} {{ question.question_text }}
          </h3>
          <Table>
            <TableBody>
              <TableRow v-for="option in question.options" :key="`${question.id}-${option.value}`" class="hover:bg-gray-50 p-0">
                <TableCell colspan="2" class="p-0">
                  <label class="flex items-center gap-3 w-full h-full px-4 py-2 cursor-pointer">
                    <input
                      type="radio"
                      :name="`mc-${question.id}`"
                      :value="option.value"
                      v-model="multipleChoiceAnswers[question.id]"
                      class="cursor-pointer"
                    >
                    <span class="text-[#474C55]">{{ option.label }}</span>
                  </label>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
      </div>

      <!-- Page 2: Evaluation Form (Coming Soon) -->
    <div v-else-if="currentPage === 2" class="space-y-6">
        <h3 class="font-semibold text-[#2E2E2E] mb-3">Service Quality Dimensions</h3>
    <p class="text-sm text-[#474C55] mb-4">Please rate your satisfaction with the following statements:</p>
    
    <div class="overflow-x-auto">
        <Table class="min-w-[800px]">
        <TableHeader>
            <TableRow class="bg-[#0F5C5C]">
            <TableHead class="w-[250px] text-white">Criteria</TableHead>
            <TableHead class="text-center text-white">Strongly Disagree</TableHead>
            <TableHead class="text-center text-white">Disagree</TableHead>
            <TableHead class="text-center text-white">Neither Agree nor Disagree</TableHead>
            <TableHead class="text-center text-white">Agree</TableHead>
            <TableHead class="text-center text-white">Strongly Agree</TableHead>
            <TableHead class="text-center text-white">Not Applicable</TableHead>
            </TableRow>
        </TableHeader>
        <TableBody>
            <TableRow v-if="likertQuestions.length === 0" class="hover:bg-gray-50">
              <TableCell colspan="7" class="text-center py-8 text-gray-500">
                Loading questions...
              </TableCell>
            </TableRow>
            <TableRow v-for="question in likertQuestions" :key="question.id" v-else class="hover:bg-gray-50">
            <TableCell class="font-medium text-[#2E2E2E]">{{ question.label }}</TableCell>
            <TableCell class="text-center p-1">
                <label class="flex items-center justify-center w-full h-full min-h-[50px] cursor-pointer hover:bg-gray-100 rounded">
                <input 
                    type="radio" 
                    :name="question.id" 
                    :value="1" 
                v-model="likertRatings[question.id]"
                    class="w-4 h-4 cursor-pointer"
                >
                </label>
            </TableCell>
            <TableCell class="text-center p-1">
                <label class="flex items-center justify-center w-full h-full min-h-[50px] cursor-pointer hover:bg-gray-100 rounded">
                <input 
                    type="radio" 
                    :name="question.id" 
                    :value="2" 
                    v-model="likertRatings[question.id]"
                    class="w-4 h-4 cursor-pointer"
                >
                </label>
            </TableCell>
            <TableCell class="text-center p-1">
                <label class="flex items-center justify-center w-full h-full min-h-[50px] cursor-pointer hover:bg-gray-100 rounded">
                <input 
                    type="radio" 
                    :name="question.id" 
                    :value="3" 
                    v-model="likertRatings[question.id]"
                    class="w-4 h-4 cursor-pointer"
                >
                </label>
            </TableCell>
            <TableCell class="text-center p-1">
                <label class="flex items-center justify-center w-full h-full min-h-[50px] cursor-pointer hover:bg-gray-100 rounded">
                <input 
                    type="radio" 
                    :name="question.id" 
                    :value="4" 
                    v-model="likertRatings[question.id]"
                    class="w-4 h-4 cursor-pointer"
                >
                </label>
            </TableCell>
            <TableCell class="text-center p-1">
                <label class="flex items-center justify-center w-full h-full min-h-[50px] cursor-pointer hover:bg-gray-100 rounded">
                <input 
                    type="radio" 
                    :name="question.id" 
                    :value="5" 
                    v-model="likertRatings[question.id]"
                    class="w-4 h-4 cursor-pointer"
                >
                </label>
            </TableCell>
            <TableCell class="text-center p-1">
                <label class="flex items-center justify-center w-full h-full min-h-[50px] cursor-pointer hover:bg-gray-100 rounded">
                <input 
                    type="radio" 
                    :name="question.id" 
                    value="NA" 
                    v-model="likertRatings[question.id]"
                    class="w-4 h-4 cursor-pointer"
                >
                </label>
            </TableCell>
            </TableRow>
        </TableBody>
        </Table>
    </div>
    </div>
      
      <!-- Modal Footer with dynamic buttons -->
      <div class="flex justify-end gap-3 pt-6">
        <Button 
          variant="outline" 
          @click="closeModal"
          class="px-4"
        >
          Cancel
        </Button>
        
        <!-- Page 1: Next button -->
        <Button 
          v-if="currentPage === 1"
          class="bg-[#0F5C5C] hover:bg-[#167D7F] text-white px-4"
          @click="goToNextPage"
        >
          Next
        </Button>

        <!-- Page 2: Back and Submit buttons -->
        <template v-else-if="currentPage === 2">
          <Button 
            variant="outline" 
            @click="goToPreviousPage"
            class="px-4"
          >
            Back
          </Button>
          <Button 
            class="bg-[#0F5C5C] hover:bg-[#167D7F] text-white px-4"
            @click="handleSubmit"
          >
            Submit
          </Button>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { X, ChevronDown } from 'lucide-vue-next'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow
} from '@/components/ui/table'
import { Button } from '@/components/ui/button'

// Props
const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  queueNumber: {
    type: String,
    default: 'CPDO-P002'
  },
  customerName: {
    type: String,
    default: 'John Doe'
  },
  contactNumber: {
    type: String,
    default: '09999999999'
  },
  barangay: {
    type: String,
    default: ''
  },
  likertQuestions: {
    type: Array,
    default: () => []
  },
  multipleChoiceQuestions: {
    type: Array,
    default: () => []
  },
  services: {
    type: Array,
    default: () => []
  }
})

// Emits
const emit = defineEmits(['update:modelValue', 'submit', 'alert'])

// Refs
const modalContent = ref(null)

// Page state
const currentPage = ref(1)

// Form data
const clientType = ref('')
const sex = ref('')
const age = ref('')

const multipleChoiceAnswers = ref({})

// Form data for Page 2 - Likert ratings
const likertRatings = ref({})

// Form data for assistance - keyed by service id
const assistanceAmounts = ref({})

// Optional assistance indicator - keyed by service id (values: 1 or 2)
const assistanceIndicators = ref({})

// Assistance types - keyed by service id
const assistanceTypes = ref({})

// Selected assistance types - keyed by queue_transaction_service_id
const selectedAssistanceTypes = ref({})

// Map of service_id to queue_transaction_service_id
const queueTransactionServices = ref({})

const getOptionValueAndLabel = (optionText) => {
  const raw = String(optionText ?? '').trim()
  const match = raw.match(/^(\d+)\s*[-.)]\s*(.+)$/)
  if (match) {
    return {
      value: match[1],
      label: `${match[1]}. ${match[2]}`,
    }
  }

  return {
    value: raw,
    label: raw,
  }
}

const multipleChoiceQuestions = computed(() => {
  return props.multipleChoiceQuestions.map((q) => ({
    id: String(q.id),
    question_code: q.question_code || '',
    question_text: q.question_text || '',
    options: (q.options || []).map(getOptionValueAndLabel),
  }))
})

const normalizeQuestionCode = (value) => {
  return String(value || '').toUpperCase().replace(/[^A-Z0-9]/g, '')
}

// Likert questions
const likertQuestions = computed(() => 
  props.likertQuestions.map((q) => ({ 
    id: String(q.id),
    label: `${q.question_code ? `${q.question_code}. ` : ''}${q.question_text}`
  }))
)

const cc1Question = computed(() => multipleChoiceQuestions.value.find((q) => normalizeQuestionCode(q.question_code) === 'CC1'))
const cc2Question = computed(() => multipleChoiceQuestions.value.find((q) => normalizeQuestionCode(q.question_code) === 'CC2'))
const cc3Question = computed(() => multipleChoiceQuestions.value.find((q) => normalizeQuestionCode(q.question_code) === 'CC3'))
const cc1Value = computed(() => (cc1Question.value ? multipleChoiceAnswers.value[cc1Question.value.id] : ''))

const getNaOptionValue = (question, fallback = 'NA') => {
  if (!question || !Array.isArray(question.options)) return fallback

  const naOption = question.options.find((option) => String(option.label).toUpperCase().includes('N/A'))
  return naOption ? String(naOption.value) : fallback
}

const shouldShowCc2Cc3 = computed(() => ['1', '2', '3'].includes(String(cc1Value.value)))

const visibleMultipleChoiceQuestions = computed(() => {
  return multipleChoiceQuestions.value.filter((question) => {
    const code = normalizeQuestionCode(question.question_code)
    if (code === 'CC1') return true
    if (code === 'CC2' || code === 'CC3') return shouldShowCc2Cc3.value
    return true
  })
})

const servicesWithAssistance = computed(() => {
  return Array.isArray(props.services)
    ? props.services.filter((service) => service.provides_assistance === true)
    : []
})

const servicesDisplay = computed(() => {
  if (!Array.isArray(props.services) || props.services.length === 0) {
    return 'N/A'
  }
  return props.services
    .map((service) => service.service_name || service.service_code || 'Unknown')
    .join(', ')
})

watch(multipleChoiceQuestions, (questions) => {
  const next = {}
  questions.forEach((question) => {
    next[question.id] = multipleChoiceAnswers.value[question.id] ?? ''
  })
  multipleChoiceAnswers.value = next
}, { immediate: true })

watch(likertQuestions, (questions) => {
  const next = {}
  questions.forEach((question) => {
    next[question.id] = likertRatings.value[question.id] ?? ''
  })
  likertRatings.value = next
}, { immediate: true })

watch(servicesWithAssistance, (services) => {
  const newAmounts = {}
  const newIndicators = {}
  services.forEach((service) => {
    newAmounts[service.id] = assistanceAmounts.value[service.id] ?? ''
    newIndicators[service.id] = assistanceIndicators.value[service.id] ?? ''
  })
  assistanceAmounts.value = newAmounts
  assistanceIndicators.value = newIndicators
}, { immediate: true })

// Extract assistance types from services response
watch(() => props.services, (services) => {
  const types = {}
  const queueTxServices = {}
  
  if (Array.isArray(services)) {
    services.forEach((service) => {
      // Store assistance types keyed by service id
      if (service.assistance_types && Array.isArray(service.assistance_types)) {
        types[service.id] = service.assistance_types
      } else {
        types[service.id] = []
      }
      
      // Store queue_transaction_service_id mapping
      queueTxServices[service.id] = {
        queue_transaction_service_id: service.queue_transaction_service_id,
        service_name: service.service_name
      }
    })
  }
  
  assistanceTypes.value = types
  queueTransactionServices.value = queueTxServices
}, { immediate: true })

watch(cc1Value, (value) => {
  if (value !== '4') return

  if (cc2Question.value) {
    multipleChoiceAnswers.value[cc2Question.value.id] = getNaOptionValue(cc2Question.value, '5')
  }

  if (cc3Question.value) {
    multipleChoiceAnswers.value[cc3Question.value.id] = getNaOptionValue(cc3Question.value, '4')
  }
})

// Watch for page changes and scroll to top
watch(currentPage, () => {
  if (modalContent.value) {
    modalContent.value.scrollTop = 0
  }
})

// Methods
const closeModal = () => {
  emit('update:modelValue', false)
  // Reset form and page
  resetForm()
}

const resetForm = () => {
  currentPage.value = 1
  clientType.value = ''
  sex.value = ''
  age.value = ''
  assistanceAmounts.value = {}
  assistanceIndicators.value = {}
  selectedAssistanceTypes.value = {}
  multipleChoiceAnswers.value = Object.fromEntries(
    multipleChoiceQuestions.value.map((question) => [question.id, ''])
  )
  likertRatings.value = Object.fromEntries(
    likertQuestions.value.map((question) => [question.id, ''])
  )
}

const goToNextPage = () => {
  const selectedCc1Value = cc1Question.value ? multipleChoiceAnswers.value[cc1Question.value.id] : ''
  const cc2Value = cc2Question.value ? multipleChoiceAnswers.value[cc2Question.value.id] : ''
  const cc3Value = cc3Question.value ? multipleChoiceAnswers.value[cc3Question.value.id] : ''

  // Validate CC questions before proceeding
  if (!selectedCc1Value) {
    emit('alert', { title: 'Validation Error', message: 'Please answer CC1 question.' })
    return
  }

  // Check if CC2 and CC3 are required based on CC1 answer
  if (shouldShowCc2Cc3.value && !cc2Value) {
    emit('alert', { title: 'Validation Error', message: 'Please answer CC2 question.' })
    return
  }

  if (shouldShowCc2Cc3.value && !cc3Value) {
    emit('alert', { title: 'Validation Error', message: 'Please answer CC3 question.' })
    return
  }

  currentPage.value = 2
}

const goToPreviousPage = () => {
  currentPage.value = 1
}

const isAssistanceIndicatorSelected = (serviceId, value) => {
  return Number(assistanceIndicators.value[serviceId]) === value
}

const toggleAssistanceIndicator = (serviceId, value) => {
  if (isAssistanceIndicatorSelected(serviceId, value)) {
    assistanceIndicators.value[serviceId] = ''
    return
  }

  assistanceIndicators.value[serviceId] = value
}

const handleSubmit = () => {
  const selectedCc1Value = cc1Question.value ? multipleChoiceAnswers.value[cc1Question.value.id] : ''
  const cc2Value = cc2Question.value ? multipleChoiceAnswers.value[cc2Question.value.id] : ''
  const cc3Value = cc3Question.value ? multipleChoiceAnswers.value[cc3Question.value.id] : ''

  // Basic validation
  if (!selectedCc1Value) {
    emit('alert', { title: 'Validation Error', message: 'Please answer CC1 question.' })
    currentPage.value = 1
    return
  }

  // Check if CC2 and CC3 are required based on CC1 answer
  if (shouldShowCc2Cc3.value && !cc2Value) {
    emit('alert', { title: 'Validation Error', message: 'Please answer CC2 question.' })
    currentPage.value = 1
    return
  }

  if (shouldShowCc2Cc3.value && !cc3Value) {
    emit('alert', { title: 'Validation Error', message: 'Please answer CC3 question.' })
    currentPage.value = 1
    return
  }

  if (!clientType.value) {
    emit('alert', { title: 'Validation Error', message: 'Please select a client type.' })
    return
  }

  if (!sex.value) {
    emit('alert', { title: 'Validation Error', message: 'Please select sex.' })
    return
  }

  if (age.value === '' || age.value === null || age.value === undefined) {
    emit('alert', { title: 'Validation Error', message: 'Please enter age.' })
    return
  }

  const normalizedAge = Number(age.value)
  if (!Number.isInteger(normalizedAge) || normalizedAge < 1 || normalizedAge > 120) {
    emit('alert', { title: 'Validation Error', message: 'Age must be a whole number from 1 to 120.' })
    return
  }

  // Check if all likert questions are answered
  const unansweredLikert = Object.values(likertRatings.value).some(rating => !rating)
  if (unansweredLikert) {
    emit('alert', { title: 'Validation Error', message: 'Please answer all service quality questions.' })
    currentPage.value = 2
    return
  }

  // Validate assistance amounts for services that provide assistance
  if (servicesWithAssistance.value.length > 0) {
    for (const service of servicesWithAssistance.value) {
      const queueTxServiceId = getQueueTransactionServiceId(service.id)
      
      // If service has assistance types (categorized), check that one is selected
      if (hasAssistanceTypes(service.id)) {
        if (!selectedAssistanceTypes.value[queueTxServiceId]) {
          emit('alert', { 
            title: 'Validation Error', 
            message: `Please select an assistance type for ${service.service_name}.` 
          })
          currentPage.value = 1
          return
        }
      }
      
      // Check that amount is entered
      const amount = assistanceAmounts.value[service.id]
      if (amount === '' || amount === null || amount === undefined) {
        emit('alert', { 
          title: 'Validation Error', 
          message: `Please enter assistance amount for ${service.service_name}.` 
        })
        currentPage.value = 1
        return
      }

      const assistanceValue = Number(amount)
      if (isNaN(assistanceValue) || assistanceValue < 0) {
        emit('alert', { 
          title: 'Validation Error', 
          message: `Assistance amount for ${service.service_name} must be a valid positive number.` 
        })
        currentPage.value = 1
        return
      }
    }
  }

  // Build assistance per queue_transaction_service data
  const assistancePerQueueTxService = servicesWithAssistance.value.map((service) => {
    const queueTxServiceId = getQueueTransactionServiceId(service.id)
    const assistanceTypeId = selectedAssistanceTypes.value[queueTxServiceId] || null
    const indicatorValue = Number(assistanceIndicators.value[service.id])
    
    return {
      queue_transaction_service_id: queueTxServiceId,
      assistance_type_id: assistanceTypeId,  // null for traditional, id for categorized
      amount: Number(assistanceAmounts.value[service.id]),
      indicator: [1, 2].includes(indicatorValue) ? indicatorValue : null
    }
  })

  // Combine all form data
  const formData = {
    // Page 1 data
    multipleChoiceAnswers: { ...multipleChoiceAnswers.value },
    client_type: clientType.value,
    sex: sex.value,
    age: normalizedAge,
    // Page 2 data
    likertRatings: likertRatings.value,
    // Assistance data (if applicable)
    ...(assistancePerQueueTxService.length > 0 && { assistance_per_queue_transaction_service: assistancePerQueueTxService }),
    // Customer info
    queueNumber: props.queueNumber,
    customerName: props.customerName,
    contactNumber: props.contactNumber,
    barangay: props.barangay
  }
  
  // Emit the form data
  emit('submit', formData)
  closeModal()
}

// Helper method to check if a service has assistance types (categorized service)
const hasAssistanceTypes = (serviceId) => {
  return assistanceTypes.value[serviceId]?.length > 0
}

// Helper method to get queue_transaction_service_id from service
const getQueueTransactionServiceId = (serviceId) => {
  return queueTransactionServices.value[serviceId]?.queue_transaction_service_id
}

// Helper method to get service name from service
const getServiceName = (serviceId) => {
  return queueTransactionServices.value[serviceId]?.service_name || 'Unknown Service'
}
</script>