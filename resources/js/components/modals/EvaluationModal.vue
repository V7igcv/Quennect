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

      <!-- Customer Info (same for both pages) -->
      <div class="flex justify-start gap-2">
        <p class="text-[#2E2E2E] font-medium">Queue Number:</p>
        <p class="text-[#474C55]">{{ queueNumber }}</p>
      </div>
      <div class="flex justify-start gap-2">
        <p class="text-[#2E2E2E] font-medium">Name:</p>
        <p class="text-[#474C55]">{{ customerName }}</p>
      </div>
      <div class="flex justify-start gap-2">
        <p class="text-[#2E2E2E] font-medium">Contact Number:</p>
        <p class="text-[#474C55]">{{ contactNumber }}</p>
      </div>

      <div class="border-t border-gray-200 w-11/11 mx-auto my-4"></div>

      <!-- Page 1: CC Survey Questions -->
      <div v-if="currentPage === 1" class="space-y-6">
        <!-- CC1 Question -->
        <div>
          <h3 class="font-semibold text-[#2E2E2E] mb-3"># CC1 Which of the following best describes your awareness of a CC?</h3>
          <Table>
            <TableBody>
              <TableRow v-for="option in cc1Options" :key="option.value" class="hover:bg-gray-50 p-0">
                <TableCell colspan="2" class="p-0">
                  <label class="flex items-center gap-3 w-full h-full px-4 py-2 cursor-pointer">
                    <input type="radio" name="cc1" :value="option.value" v-model="cc1" class="cursor-pointer">
                    <span class="text-[#474C55]">{{ option.label }}</span>
                  </label>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>

        <!-- CC2 Question -->
        <div>
          <h3 class="font-semibold text-[#2E2E2E] mb-3"># CC2 If aware of CC (1–3 in CC1), would you say that the CC of this office was...?</h3>
          <Table>
            <TableBody>
              <TableRow v-for="option in cc2Options" :key="option.value" class="hover:bg-gray-50 p-0">
                <TableCell colspan="2" class="p-0">
                  <label class="flex items-center gap-3 w-full h-full px-4 py-2 cursor-pointer">
                    <input type="radio" name="cc2" :value="option.value" v-model="cc2" class="cursor-pointer">
                    <span class="text-[#474C55]">{{ option.label }}</span>
                  </label>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>

        <!-- CC3 Question -->
        <div>
          <h3 class="font-semibold text-[#2E2E2E] mb-3"># CC3 If aware of CC (1–3 in CC1), how much did the CC help you in your transaction?</h3>
          <Table>
            <TableBody>
              <TableRow v-for="option in cc3Options" :key="option.value" class="hover:bg-gray-50 p-0">
                <TableCell colspan="2" class="p-0">
                  <label class="flex items-center gap-3 w-full h-full px-4 py-2 cursor-pointer">
                    <input type="radio" name="cc3" :value="option.value" v-model="cc3" class="cursor-pointer">
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
            <!-- SQD0 -->
            <TableRow v-for="question in likertQuestions" :key="question.id" class="hover:bg-gray-50">
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
                    :value="6" 
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
import { ref, watch } from 'vue'
import { X } from 'lucide-vue-next'
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
  }
})

// Emits
const emit = defineEmits(['update:modelValue', 'submit'])

// Refs
const modalContent = ref(null)

// Page state
const currentPage = ref(1)

// Form data
const cc1 = ref('')
const cc2 = ref('')
const cc3 = ref('')

// Form data for Page 2 - Likert ratings
const likertRatings = ref({
  sqd0: '',
  sqd1: '',
  sqd2: '',
  sqd3: '',
  sqd4: '',
  sqd5: '',
  sqd6: '',
  sqd7: '',
  sqd8: ''
})

// Likert questions
const likertQuestions = [
  { id: 'sqd0', label: 'SQD0. I am satisfied with the service that I availed' },
  { id: 'sqd1', label: 'SQD1. I am satisfied with the service that I availed' },
  { id: 'sqd2', label: 'SQD2. I am satisfied with the service that I availed' },
  { id: 'sqd3', label: 'SQD3. I am satisfied with the service that I availed' },
  { id: 'sqd4', label: 'SQD4. I am satisfied with the service that I availed' },
  { id: 'sqd5', label: 'SQD5. I am satisfied with the service that I availed' },
  { id: 'sqd6', label: 'SQD6. I am satisfied with the service that I availed' },
  { id: 'sqd7', label: 'SQD7. I am satisfied with the service that I availed' },
  { id: 'sqd8', label: 'SQD8. I am satisfied with the service that I availed' }
]

// Options for CC1
const cc1Options = [
  { value: '1', label: '1. I know what a CC is and I saw this office\'s CC.' },
  { value: '2', label: '2. I know what a CC is but I did NOT see this office\'s CC.' },
  { value: '3', label: '3. I learned the CC only when I saw this office\'s CC.' },
  { value: '4', label: '4. I do not know what a CC is and I did not see one in this office (N/A on CC2 & CC3)' }
]

// Options for CC2
const cc2Options = [
  { value: '1', label: '1. Easy to see' },
  { value: '2', label: '2. Somewhat easy to see' },
  { value: '3', label: '3. Difficult to see' },
  { value: '4', label: '4. Not visible at all' },
  { value: '5', label: '5. N/A' }
]

// Options for CC3
const cc3Options = [
  { value: '1', label: '1. Helped very much' },
  { value: '2', label: '2. Somewhat helped' },
  { value: '3', label: '3. Did not help' },
  { value: '4', label: '4. N/A' }
]

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
  cc1.value = ''
  cc2.value = ''
  cc3.value = ''
  likertRatings.value = {
    sqd0: '', sqd1: '', sqd2: '', sqd3: '', sqd4: '', sqd5: '', sqd6: '', sqd7: '', sqd8: ''
  }
}

const goToNextPage = () => {
  // Optional: Add validation here to ensure CC questions are answered
  currentPage.value = 2
}

const goToPreviousPage = () => {
  currentPage.value = 1
}

const handleSubmit = () => {
  // Combine all form data
  const formData = {
    // Page 1 data
    cc1: cc1.value,
    cc2: cc2.value,
    cc3: cc3.value,
    // Page 2 data
    likertRatings: likertRatings.value,
    // Customer info
    queueNumber: props.queueNumber,
    customerName: props.customerName,
    contactNumber: props.contactNumber
  }
  
  // Emit the form data
  emit('submit', formData)
  closeModal()
}
</script>