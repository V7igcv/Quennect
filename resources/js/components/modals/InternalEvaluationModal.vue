<!-- src/components/modals/InternalEvaluationModal.vue -->
<template>
  <div v-if="modelValue" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div ref="modalContent" class="bg-white rounded-lg w-full max-w-5xl mx-4 py-8 px-12 flex flex-col max-h-[90vh] overflow-y-auto">
      <div class="flex justify-end items-center">
        <button 
          @click="closeModal"
          class="text-gray-400 hover:text-gray-600 transition-colors"
        >
          <X class="w-5 h-5 cursor-pointer" />
        </button>
      </div>
      
      <!-- Modal Header -->
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
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div class="rounded-md border border-gray-200 bg-white px-3 py-2">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Office</p>
            <p class="mt-1 text-sm font-semibold text-[#1F2937]">{{ officeName || 'N/A' }}</p>
          </div>

          <div class="rounded-md border border-gray-200 bg-white px-3 py-2">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Name</p>
            <p class="mt-1 text-sm font-semibold text-[#1F2937]">{{ customerName || 'N/A' }}</p>
          </div>

          <div class="rounded-md border border-gray-200 bg-white px-3 py-2">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Contact Number</p>
            <p class="mt-1 text-sm font-semibold text-[#1F2937]">{{ contactNumber || 'N/A' }}</p>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
        <div>
          <label class="block text-sm font-medium text-[#2E2E2E] mb-1">Client Type</label>
          <div class="relative opacity-70">
            <select
              disabled
              v-model="clientType"
              class="w-full appearance-none border border-gray-300 bg-gray-100 rounded-md px-3 pr-8 py-2 text-sm focus:outline-none"
            >
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

      <!-- Page 2: Evaluation Form -->
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
                    value="1" 
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
                    value="2" 
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
                    value="3" 
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
                    value="4" 
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
                    value="5" 
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
  officeName: {
    type: String,
    default: ''
  },
  customerName: {
    type: String,
    default: ''
  },
  contactNumber: {
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
  }
})

// Emits
const emit = defineEmits(['update:modelValue', 'submit', 'alert'])

// Refs
const modalContent = ref(null)

// Page state
const currentPage = ref(1)

// Form data
const clientType = ref('Government')
const sex = ref('')
const age = ref('')

const multipleChoiceAnswers = ref({})

// Form data for Page 2 - Likert ratings
const likertRatings = ref({})

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
    question_code: q.question_code || q.code || '',
    question_text: q.question_text || q.text || '',
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
    label: `${q.question_code || q.code ? `${q.question_code || q.code}. ` : ''}${q.question_text || q.text}`
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
  clientType.value = 'Government'
  sex.value = ''
  age.value = ''
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

  // Combine all form data
  const formData = {
    // Page 1 data
    multipleChoiceAnswers: { ...multipleChoiceAnswers.value },
    client_type: clientType.value,
    sex: sex.value,
    age: normalizedAge,
    // Page 2 data
    likertRatings: likertRatings.value,
  }
  
  // Emit the form data
  emit('submit', formData)
  closeModal()
}
</script>
