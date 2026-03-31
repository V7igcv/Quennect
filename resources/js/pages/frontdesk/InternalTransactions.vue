<template>
  <div class="max-w-7xl mx-auto px-2 py-2">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
      <h2 class="text-2xl font-semibold">Internal Transactions</h2>

      <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full sm:w-auto">
        <!-- Date Filter Dropdown -->
        <Popover v-model:open="isDateFilterOpen">
          <PopoverTrigger as-child>
            <Button variant="outline" class="w-full sm:w-[220px] justify-start bg-white">
              <span class="flex items-center gap-2">
                <Calendar class="h-4 w-4 text-gray-500 shrink-0" />
                <span class="truncate">{{ dateFilterLabel }}</span>
              </span>
            </Button>
          </PopoverTrigger>

          <PopoverContent align="end" class="w-[320px] p-3">
            <!-- Daily -->
            <div v-if="selectedDateRange === 'daily'" class="space-y-3">
              <div class="flex items-center justify-between">
                <Button variant="ghost" size="icon" class="h-8 w-8" @click="goToPrevDailyMonth">
                  <ChevronLeft class="h-4 w-4" />
                </Button>
                <p class="text-sm font-semibold">{{ dailyHeaderLabel }}</p>
                <Button variant="ghost" size="icon" class="h-8 w-8" @click="goToNextDailyMonth">
                  <ChevronRight class="h-4 w-4" />
                </Button>
              </div>

              <div class="grid grid-cols-7 gap-1 text-center text-xs text-gray-500">
                <span v-for="dayName in weekDayLabels" :key="dayName">{{ dayName }}</span>
              </div>

              <div class="grid grid-cols-7 gap-1">
                <button
                  v-for="(cell, index) in dailyCalendarCells"
                  :key="`day-cell-${index}`"
                  type="button"
                  :disabled="!cell"
                  class="h-9 rounded-md text-sm transition-colors"
                  :class="[
                    !cell ? 'cursor-default' : 'hover:bg-gray-100',
                    cell && isSelectedDay(cell) ? 'bg-[#111827] text-white hover:bg-[#111827]' : 'text-gray-800'
                  ]"
                  @click="cell && selectDailyDate(cell)"
                >
                  {{ cell ?? '' }}
                </button>
              </div>
            </div>

            <!-- Monthly -->
            <div v-else-if="selectedDateRange === 'monthly'" class="space-y-3">
              <div class="flex items-center justify-between">
                <Button variant="ghost" size="icon" class="h-8 w-8" @click="selectedMonthYear -= 1">
                  <ChevronLeft class="h-4 w-4" />
                </Button>
                <p class="text-sm font-semibold">{{ monthNames[selectedMonthIndex] }} {{ selectedMonthYear }}</p>
                <Button variant="ghost" size="icon" class="h-8 w-8" @click="selectedMonthYear += 1">
                  <ChevronRight class="h-4 w-4" />
                </Button>
              </div>

              <div class="grid grid-cols-3 gap-2">
                <button
                  v-for="(monthName, monthIndex) in monthNames"
                  :key="monthName"
                  type="button"
                  class="h-9 rounded-md border text-xs font-medium transition-colors"
                  :class="monthIndex === selectedMonthIndex ? 'border-[#111827] bg-[#111827] text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'"
                  @click="selectedMonthIndex = monthIndex"
                >
                  {{ monthName.slice(0, 3).toUpperCase() }}
                </button>
              </div>
            </div>

            <!-- Yearly -->
            <div v-else class="space-y-3">
              <p class="text-center text-sm font-semibold">{{ selectedYear }}</p>

              <div class="max-h-56 space-y-1 overflow-y-auto pr-1">
                <button
                  v-for="yearOption in yearOptions"
                  :key="yearOption"
                  type="button"
                  class="flex h-9 w-full items-center justify-between rounded-md px-3 text-sm transition-colors"
                  :class="String(yearOption) === selectedYear ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50'"
                  @click="selectedYear = String(yearOption)"
                >
                  <span>{{ yearOption }}</span>
                  <span v-if="String(yearOption) === selectedYear" class="text-xs">✓</span>
                </button>
              </div>
            </div>

            <div class="mt-3 grid grid-cols-3 gap-2">
              <Button
                type="button"
                size="sm"
                variant="outline"
                class="font-medium"
                :class="selectedDateRange === 'daily' ? 'border-[#0F5C5C] bg-[#0F5C5C] text-white hover:bg-[#0C4B4B] hover:text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'"
                @click="selectedDateRange = 'daily'"
              >
                Daily
              </Button>
              <Button
                type="button"
                size="sm"
                variant="outline"
                class="font-medium"
                :class="selectedDateRange === 'monthly' ? 'border-[#0F5C5C] bg-[#0F5C5C] text-white hover:bg-[#0C4B4B] hover:text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'"
                @click="selectedDateRange = 'monthly'"
              >
                Monthly
              </Button>
              <Button
                type="button"
                size="sm"
                variant="outline"
                class="font-medium"
                :class="selectedDateRange === 'yearly' ? 'border-[#0F5C5C] bg-[#0F5C5C] text-white hover:bg-[#0C4B4B] hover:text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'"
                @click="selectedDateRange = 'yearly'"
              >
                Yearly
              </Button>
            </div>
          </PopoverContent>
        </Popover>

        <button 
          @click="goToCreateRequest"
          class="bg-[#0F5C5C] hover:bg-[#0a4a4a] text-white px-4 py-2 rounded-sm transition flex items-center justify-center gap-2 text-sm h-10 w-full sm:w-auto"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          New Request
        </button>
      </div>
    </div>

    <!-- Dashboard Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-6 mb-6">
      <StatCard class="border-l-4 border-yellow-500"
        title="Pending"
        :value="stats.received.pending"
        :icon="Clock"
        iconBg="bg-yellow-100"
        iconColor="text-yellow-600"
        numberColor="text-yellow-600"
      />
      <StatCard class="border-l-4 border-blue-500"
        title="On Process"
        :value="stats.received.on_process"
        :icon="RefreshCw"
        iconBg="bg-blue-100"
        iconColor="text-blue-600"
        numberColor="text-blue-600"
      />
      <StatCard class="border-l-4 border-green-500"
        title="Completed"
        :value="stats.received.completed"
        :icon="CheckCircle"
        iconBg="bg-green-100"
        iconColor="text-green-600"
        numberColor="text-green-600"
      />
      <StatCard class="border-l-4 border-red-500"
        title="Denied"
        :value="stats.received.denied"
        :icon="XCircle"
        iconBg="bg-red-100"
        iconColor="text-red-600"
        numberColor="text-red-600"
      />
    </div>

    <!-- Tabs -->
    <div class="flex gap-4 border-b mb-4">
      <button 
        v-for="tab in tabs" 
        :key="tab.value"
        @click="switchTab(tab.value)"
        class="pb-2 px-1 text-sm transition"
        :class="activeTab === tab.value ? 'border-b-2 border-[#0F5C5C] text-[#0F5C5C] font-medium' : 'text-gray-500'"
      >
        {{ tab.label }}
        <span class="ml-1 text-xs px-1.5 py-0.5 rounded-full bg-gray-100">
          {{ getCount(tab.value) }}
        </span>
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="bg-white rounded-lg shadow p-8 text-center">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#0F5C5C] mx-auto"></div>
    </div>

    <!-- Empty -->
    <div v-else-if="requests.length === 0" class="bg-white rounded-lg shadow p-8 text-center">
      <p class="text-gray-500">No requests found</p>
    </div>

    <!-- Table -->
    <div v-else class="bg-white rounded-lg shadow overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Transaction ID</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">{{ activeTab === 'received' ? 'From' : 'To' }}</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Services</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Client</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Status</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Requirement Link</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Deadline</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Date</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="req in requests" :key="req.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 text-sm font-mono">{{ req.transaction_id || req.id }}</td>
              <td class="px-4 py-3 text-sm">{{ activeTab === 'received' ? req.from_office : req.to_office }}</td>
              <td class="px-4 py-3 text-sm">
                <div class="max-w-xs truncate" :title="req.services">{{ req.services }}</div>
              </td>
              <td class="px-4 py-3 text-sm">
                {{ req.full_name }}<br>
                <span class="text-xs text-gray-400">{{ req.contact_number }}</span>
              </td>
              <td class="px-4 py-3">
                <span class="px-2 py-1 text-xs rounded-full" :class="getStatusClass(req.status)">
                  {{ getStatusLabel(req.status) }}
                </span>
              </td>
              <td class="px-4 py-3 text-sm">
                <a 
                  v-if="req.requirement_link" 
                  :href="req.requirement_link" 
                  target="_blank" 
                  class="text-blue-600 hover:text-blue-800 underline truncate block max-w-[150px]"
                  :title="req.requirement_link"
                >
                  View Link
                </a>
                <span v-else class="text-gray-400">No link</span>
              </td>
              <td class="px-4 py-3 text-sm">
                <div v-if="req.status !== 'COMPLETED' && req.status !== 'DENIED' && req.expected_completion_date">
                  <span :class="getDeadlineClass(req)">
                    {{ getDeadlineDisplay(req) }}
                  </span>
                </div>
                <span v-else class="text-xs text-gray-400">—</span>
              </td>
              <td class="px-4 py-3 text-sm">{{ formatDate(req.created_at) }}</td>
              <td class="px-4 py-3 text-right">
                <div class="flex justify-end gap-2">
                  <button 
                    v-if="activeTab === 'received' && req.can_accept" 
                    @click="acceptRequest(req)" 
                    class="text-green-600 hover:text-green-800" 
                    title="Accept"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                  </button>
                  <button 
                    v-if="activeTab === 'received' && req.can_deny" 
                    @click="openDenyModal(req)" 
                    class="text-red-600 hover:text-red-800" 
                    title="Deny"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                  <button 
                    v-if="activeTab === 'received' && req.can_complete" 
                    @click="openCompleteModal(req)" 
                    class="text-blue-600 hover:text-blue-800" 
                    title="Complete"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </button>
                  <button 
                    v-if="activeTab === 'received' && req.can_evaluate" 
                    @click="openEvaluationModal(req)" 
                    class="text-purple-600 hover:text-purple-800" 
                    title="Evaluate"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="px-4 py-3 border-t flex justify-between items-center">
        <span class="text-sm text-gray-500">Page {{ pagination.current_page }} of {{ pagination.last_page }}</span>
        <div class="flex gap-2">
          <button 
            @click="changePage(pagination.current_page - 1)" 
            :disabled="pagination.current_page === 1" 
            class="px-3 py-1 text-sm border rounded disabled:opacity-50"
          >
            Prev
          </button>
          <button 
            @click="changePage(pagination.current_page + 1)" 
            :disabled="pagination.current_page === pagination.last_page" 
            class="px-3 py-1 text-sm border rounded disabled:opacity-50"
          >
            Next
          </button>
        </div>
      </div>
    </div>

    <!-- Deny Modal with Blur Background -->
    <div v-if="showDenyModal" class="fixed inset-0 backdrop-blur-md bg-black/20 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl w-full max-w-md p-6 shadow-xl transform transition-all">
        <h3 class="text-xl font-semibold text-gray-800 mb-2">Deny Request</h3>
        <p class="text-sm text-gray-500 mb-4">Select the reason(s) for denying this request:</p>
        
        <div class="space-y-3 mb-4">
          <label class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox" v-model="denyOptions" value="Missing Requirements" class="mt-0.5 w-4 h-4 text-red-600 rounded border-gray-300 focus:ring-red-500">
            <span class="text-sm text-gray-700">Missing Requirements</span>
          </label>
          <label class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox" v-model="denyOptions" value="Incomplete Details" class="mt-0.5 w-4 h-4 text-red-600 rounded border-gray-300 focus:ring-red-500">
            <span class="text-sm text-gray-700">Incomplete Details</span>
          </label>
          <label class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox" v-model="denyOptions" value="Wrong Information" class="mt-0.5 w-4 h-4 text-red-600 rounded border-gray-300 focus:ring-red-500">
            <span class="text-sm text-gray-700">Wrong Information</span>
          </label>
          <label class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox" v-model="denyOptions" value="Invalid Documents" class="mt-0.5 w-4 h-4 text-red-600 rounded border-gray-300 focus:ring-red-500">
            <span class="text-sm text-gray-700">Invalid Documents</span>
          </label>
        </div>
        
        <textarea 
          v-model="denyReason" 
          class="w-full border border-gray-200 rounded-lg p-2 text-sm mb-4 focus:ring-2 focus:ring-red-500 focus:border-transparent transition"
          rows="3"
          placeholder="Additional reason (optional)..."
        ></textarea>
        
        <div class="flex gap-3">
          <button @click="showDenyModal = false" class="flex-1 py-2 px-4 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">Cancel</button>
          <button @click="confirmDenyWithOptions" class="flex-1 py-2 px-4 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">Confirm Deny</button>
        </div>
      </div>
    </div>

    <!-- Complete Modal with Blur Background -->
    <div v-if="showCompleteModal" class="fixed inset-0 backdrop-blur-md bg-black/20 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl w-full max-w-md p-6 shadow-xl transform transition-all">
        <h3 class="text-xl font-semibold text-gray-800 mb-2">Complete Request</h3>
        <p class="text-sm text-gray-500 mb-4">Select completion notes or add your own message:</p>
        
        <div class="space-y-3 mb-4">
          <label class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox" v-model="completeOptions" value="Your request has been processed successfully." class="mt-0.5 w-4 h-4 text-green-600 rounded border-gray-300 focus:ring-green-500">
            <span class="text-sm text-gray-700">Your request has been processed successfully.</span>
          </label>
          <label class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox" v-model="completeOptions" value="You may now claim your documents at the office." class="mt-0.5 w-4 h-4 text-green-600 rounded border-gray-300 focus:ring-green-500">
            <span class="text-sm text-gray-700">You may now claim your documents at the office.</span>
          </label>
          <label class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox" v-model="completeOptions" value="Please submit the hard copy of your documents to our office." class="mt-0.5 w-4 h-4 text-green-600 rounded border-gray-300 focus:ring-green-500">
            <span class="text-sm text-gray-700">Please submit the hard copy of your documents to our office.</span>
          </label>
        </div>
        
        <textarea 
          v-model="completionNotes" 
          class="w-full border border-gray-200 rounded-lg p-2 text-sm mb-4 focus:ring-2 focus:ring-green-500 focus:border-transparent transition"
          rows="3"
          placeholder="Additional notes (optional)..."
        ></textarea>
        
        <div class="flex gap-3">
          <button @click="showCompleteModal = false" class="flex-1 py-2 px-4 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">Cancel</button>
          <button @click="confirmCompleteWithOptions" class="flex-1 py-2 px-4 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">Confirm Complete</button>
        </div>
      </div>
    </div>

    <!-- Evaluation Modal with Blur Background -->
    <div v-if="showEvaluationModal" class="fixed inset-0 backdrop-blur-md bg-black/20 flex items-center justify-center z-50 p-4 overflow-y-auto">
      <div class="bg-white rounded-xl w-full max-w-2xl p-6 shadow-xl max-h-[90vh] overflow-y-auto transform transition-all">
        <h3 class="text-xl font-semibold text-gray-800 mb-4">Client Satisfaction Evaluation</h3>
        
        <div v-if="evaluationLoading" class="text-center py-8">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#0F5C5C] mx-auto"></div>
        </div>
        
        <form v-else @submit.prevent="submitEvaluation">
          <div v-for="question in evaluationQuestions" :key="question.id" class="mb-6 pb-4 border-b border-gray-100 last:border-0">
            <label class="block text-sm font-medium text-gray-700 mb-3">
              {{ question.text }}
            </label>
            
            <!-- Multiple Choice -->
            <div v-if="question.type === 'MULTIPLE_CHOICE'" class="space-y-2">
              <label v-for="option in question.options" :key="option" class="flex items-start gap-3 cursor-pointer p-2 rounded-lg hover:bg-gray-50 transition">
                <input 
                  type="radio" 
                  :name="`question_${question.id}`" 
                  :value="option"
                  v-model="evaluationResponses[question.id]"
                  class="mt-0.5 w-4 h-4 text-[#0F5C5C] focus:ring-[#0F5C5C]"
                  required
                />
                <span class="text-sm text-gray-700">{{ option }}</span>
              </label>
            </div>
            
            <!-- Likert Scale -->
            <div v-if="question.type === 'LIKERT'" class="flex justify-between gap-2">
              <label v-for="rating in [1,2,3,4,5]" :key="rating" class="flex-1 text-center cursor-pointer p-2 rounded-lg hover:bg-gray-50 transition">
                <input 
                  type="radio" 
                  :name="`question_${question.id}`" 
                  :value="rating"
                  v-model="evaluationResponses[question.id]"
                  class="w-4 h-4 mx-auto text-[#0F5C5C] focus:ring-[#0F5C5C]"
                  required
                />
                <span class="text-xs text-gray-600 block mt-1">{{ rating }}</span>
              </label>
            </div>
          </div>
          
          <div class="flex justify-end gap-3 mt-6">
            <button type="button" @click="showEvaluationModal = false" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">Cancel</button>
            <button type="submit" :disabled="evaluationSubmitting" class="px-4 py-2 bg-[#0F5C5C] text-white rounded-lg hover:bg-[#0a4a4a] transition disabled:opacity-50">
              {{ evaluationSubmitting ? 'Submitting...' : 'Submit Evaluation' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { useRouter } from 'vue-router'
import kioskApi from '../../services/kioskApi'
import { Button } from '@/components/ui/button'
import StatCard from '@/components/common/StatCard.vue'
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover'
import { 
  Clock, 
  RefreshCw, 
  CheckCircle, 
  XCircle, 
  Calendar,
  ChevronLeft,
  ChevronRight
} from 'lucide-vue-next'

const router = useRouter()

const loading = ref(false)
const stats = ref({
  received: { pending: 0, on_process: 0, completed: 0, denied: 0, overdue: 0 },
  sent: { pending: 0, on_process: 0, completed: 0, denied: 0, overdue: 0 }
})
const requests = ref([])
const pagination = ref({ current_page: 1, last_page: 1 })
const activeTab = ref('received')
const showDenyModal = ref(false)
const showCompleteModal = ref(false)
const showEvaluationModal = ref(false)
const selectedRequest = ref(null)
const denyReason = ref('')
const denyOptions = ref([])
const completionNotes = ref('')
const completeOptions = ref([])
const evaluationQuestions = ref([])
const evaluationLoading = ref(false)
const evaluationSubmitting = ref(false)
const evaluationResponses = ref({})

// Date filtering logic
const selectedDateRange = ref('daily')
const isDateFilterOpen = ref(false)

const bodyOriginalOverflow = ref('')
const htmlOriginalOverflow = ref('')

const lockPageScroll = () => {
  if (typeof document === 'undefined') return
  bodyOriginalOverflow.value = document.body.style.overflow
  htmlOriginalOverflow.value = document.documentElement.style.overflow
  document.body.style.overflow = 'hidden'
  document.documentElement.style.overflow = 'hidden'
}

const unlockPageScroll = () => {
  if (typeof document === 'undefined') return
  document.body.style.overflow = bodyOriginalOverflow.value
  document.documentElement.style.overflow = htmlOriginalOverflow.value
}

watch(isDateFilterOpen, (isOpen) => {
  if (isOpen) {
    lockPageScroll()
  } else {
    unlockPageScroll()
  }
})

onBeforeUnmount(() => {
  unlockPageScroll()
})

const now = new Date()
const selectedDate = ref(new Date(now.getFullYear(), now.getMonth(), now.getDate()))
const selectedMonthIndex = ref(now.getMonth())
const selectedMonthYear = ref(now.getFullYear())
const selectedYear = ref(String(now.getFullYear()))
const dailyViewMonth = ref(now.getMonth())
const dailyViewYear = ref(now.getFullYear())

const monthNames = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December',
]
const weekDayLabels = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa']

const dateFilterLabel = computed(() => {
  if (selectedDateRange.value === 'daily') {
    return selectedDate.value.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
    })
  }

  if (selectedDateRange.value === 'monthly') {
    return `${monthNames[selectedMonthIndex.value]} ${selectedMonthYear.value}`
  }

  return selectedYear.value
})

const dailyHeaderLabel = computed(() => `${monthNames[dailyViewMonth.value]} ${dailyViewYear.value}`)

const yearOptions = computed(() => {
  const years = []
  const currentYear = new Date().getFullYear()
  for (let year = currentYear + 2; year >= currentYear - 60; year -= 1) {
    years.push(year)
  }
  return years
})

const dailyCalendarCells = computed(() => {
  const firstDayOfMonth = new Date(dailyViewYear.value, dailyViewMonth.value, 1).getDay()
  const totalDays = new Date(dailyViewYear.value, dailyViewMonth.value + 1, 0).getDate()

  const cells = []
  for (let i = 0; i < firstDayOfMonth; i += 1) {
    cells.push(null)
  }
  for (let day = 1; day <= totalDays; day += 1) {
    cells.push(day)
  }
  while (cells.length % 7 !== 0) {
    cells.push(null)
  }

  return cells
})

const goToPrevDailyMonth = () => {
  if (dailyViewMonth.value === 0) {
    dailyViewMonth.value = 11
    dailyViewYear.value -= 1
    return
  }
  dailyViewMonth.value -= 1
}

const goToNextDailyMonth = () => {
  if (dailyViewMonth.value === 11) {
    dailyViewMonth.value = 0
    dailyViewYear.value += 1
    return
  }
  dailyViewMonth.value += 1
}

const isSelectedDay = (day) => {
  return selectedDate.value.getDate() === day &&
    selectedDate.value.getMonth() === dailyViewMonth.value &&
    selectedDate.value.getFullYear() === dailyViewYear.value
}

const selectDailyDate = (day) => {
  selectedDate.value = new Date(dailyViewYear.value, dailyViewMonth.value, day)
}

const apiFilterParams = computed(() => {
  let startDate = ''
  let endDate = ''

  if (selectedDateRange.value === 'daily') {
    const year = selectedDate.value.getFullYear()
    const month = String(selectedDate.value.getMonth() + 1).padStart(2, '0')
    const day = String(selectedDate.value.getDate()).padStart(2, '0')
    startDate = `${year}-${month}-${day}`
    endDate = startDate
  } else if (selectedDateRange.value === 'monthly') {
    const year = selectedMonthYear.value
    const month = String(selectedMonthIndex.value + 1).padStart(2, '0')
    const lastDay = new Date(year, selectedMonthIndex.value + 1, 0).getDate()
    startDate = `${year}-${month}-01`
    endDate = `${year}-${month}-${String(lastDay).padStart(2, '0')}`
  } else {
    // yearly
    startDate = `${selectedYear.value}-01-01`
    endDate = `${selectedYear.value}-12-31`
  }

  return { start_date: startDate, end_date: endDate }
})

watch([selectedDateRange, selectedDate, selectedMonthIndex, selectedMonthYear, selectedYear], () => {
  fetchDashboard()
  fetchRequests()
})

const tabs = [
  { value: 'received', label: 'Received' },
  { value: 'sent', label: 'Sent' }
]

const getCount = (tab) => {
  const counts = {
    received: stats.value.received.pending + stats.value.received.on_process + stats.value.received.completed + stats.value.received.denied,
    sent: stats.value.sent.pending + stats.value.sent.on_process + stats.value.sent.completed + stats.value.sent.denied
  }
  return counts[tab] || 0
}

const getStatusClass = (status) => {
  const classes = {
    PENDING: 'bg-yellow-100 text-yellow-800',
    'ON-PROCESS': 'bg-blue-100 text-blue-800',
    COMPLETED: 'bg-green-100 text-green-800',
    DENIED: 'bg-red-100 text-red-800',
    OVERDUE: 'bg-orange-100 text-orange-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getStatusLabel = (status) => {
  const labels = {
    PENDING: 'Pending',
    'ON-PROCESS': 'On Process',
    COMPLETED: 'Completed',
    DENIED: 'Denied',
    OVERDUE: 'Overdue'
  }
  return labels[status] || status
}

const formatDate = (date) => {
  if (!date) return ''
  const d = new Date(date)
  return d.toLocaleDateString()
}

// Calculate remaining days from expected_completion_date
const getRemainingDays = (completionDate) => {
  if (!completionDate) return null
  const today = new Date()
  const deadline = new Date(completionDate)
  const diffTime = deadline - today
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  return diffDays
}

// Get deadline display text
const getDeadlineDisplay = (req) => {
  if (req.status === 'COMPLETED' || req.status === 'DENIED') {
    return ''
  }
  
  if (!req.expected_completion_date) return 'No deadline'
  
  const remainingDays = getRemainingDays(req.expected_completion_date)
  
  if (remainingDays < 0) return 'Overdue'
  if (remainingDays === 0) return 'Today'
  if (remainingDays === 1) return '1 day left'
  return `${remainingDays} days left`
}

// Get deadline class for styling
const getDeadlineClass = (req) => {
  if (req.status === 'COMPLETED' || req.status === 'DENIED') {
    return 'text-gray-400'
  }
  
  if (!req.expected_completion_date) return 'text-gray-400'
  
  const remainingDays = getRemainingDays(req.expected_completion_date)
  
  if (remainingDays < 0) return 'text-red-600 font-bold'
  if (remainingDays === 0) return 'text-orange-600 font-semibold'
  if (remainingDays <= 3) return 'text-yellow-600'
  return 'text-green-600'
}

const fetchDashboard = async () => {
  try {
    const { start_date, end_date } = apiFilterParams.value
    const res = await kioskApi.get(`/frontdesk/internal-transactions/dashboard?start_date=${start_date}&end_date=${end_date}`)
    stats.value = res.data.data
  } catch (err) {
    console.error(err)
  }
}

const fetchRequests = async () => {
  loading.value = true
  try {
    const { start_date, end_date } = apiFilterParams.value
    const res = await kioskApi.get(`/frontdesk/internal-transactions/requests?type=${activeTab.value}&page=${pagination.value.current_page}&start_date=${start_date}&end_date=${end_date}`)
    requests.value = res.data.data.requests.data
    pagination.value = res.data.data.pagination
  } catch (err) {
    console.error(err)
  } finally {
    loading.value = false
  }
}

const switchTab = (tab) => {
  activeTab.value = tab
  pagination.value.current_page = 1
  fetchRequests()
}

const changePage = (page) => {
  if (page < 1 || page > pagination.value.last_page) return
  pagination.value.current_page = page
  fetchRequests()
}

const goToCreateRequest = () => {
  router.push('/frontdesk/create')
}

const acceptRequest = async (req) => {
  try {
    await kioskApi.post(`/frontdesk/internal-transactions/requests/${req.id}/accept`)
    await Promise.all([fetchDashboard(), fetchRequests()])
  } catch (err) {
    console.error(err)
    alert('Failed to accept request')
  }
}

const openDenyModal = (req) => {
  selectedRequest.value = req
  denyReason.value = ''
  denyOptions.value = []
  showDenyModal.value = true
}

const confirmDenyWithOptions = async () => {
  let fullReason = ''
  
  if (denyOptions.value.length > 0) {
    fullReason = denyOptions.value.join(', ')
  }
  
  if (denyReason.value.trim()) {
    fullReason = fullReason ? `${fullReason}. ${denyReason.value.trim()}` : denyReason.value.trim()
  }
  
  if (!fullReason) {
    alert('Please provide a reason for denying this request')
    return
  }
  
  try {
    await kioskApi.post(`/frontdesk/internal-transactions/requests/${selectedRequest.value.id}/deny`, { 
      denial_reason: fullReason 
    })
    showDenyModal.value = false
    await Promise.all([fetchDashboard(), fetchRequests()])
  } catch (err) {
    console.error(err)
    alert('Failed to deny request')
  }
}

const openCompleteModal = (req) => {
  selectedRequest.value = req
  completionNotes.value = ''
  completeOptions.value = []
  showCompleteModal.value = true
}

const confirmCompleteWithOptions = async () => {
  let fullMessage = ''
  
  if (completeOptions.value.length > 0) {
    fullMessage = completeOptions.value.join(' ')
  }
  
  if (completionNotes.value.trim()) {
    fullMessage = fullMessage ? `${fullMessage} ${completionNotes.value.trim()}` : completionNotes.value.trim()
  }
  
  if (!fullMessage) {
    alert('Please add completion notes')
    return
  }
  
  try {
    await kioskApi.post(`/frontdesk/internal-transactions/requests/${selectedRequest.value.id}/complete`, { 
      completion_notes: fullMessage 
    })
    showCompleteModal.value = false
    await Promise.all([fetchDashboard(), fetchRequests()])
  } catch (err) {
    console.error(err)
    alert('Failed to complete request')
  }
}

const openEvaluationModal = async (request) => {
  selectedRequest.value = request
  showEvaluationModal.value = true
  evaluationLoading.value = true
  evaluationResponses.value = {}
  
  try {
    const res = await kioskApi.get('/frontdesk/internal-transactions/evaluation/questions')
    evaluationQuestions.value = res.data.data
    console.log('Evaluation questions loaded:', evaluationQuestions.value)
  } catch (err) {
    console.error('Failed to load evaluation questions:', err)
    alert('Failed to load evaluation form. Please try again.')
  } finally {
    evaluationLoading.value = false
  }
}

const submitEvaluation = async () => {
  evaluationSubmitting.value = true
  
  const responses = Object.entries(evaluationResponses.value).map(([questionId, value]) => {
    const question = evaluationQuestions.value.find(q => q.id == parseInt(questionId))
    return {
      question_id: parseInt(questionId),
      answer_value: value.toString(),
      rating_value: question?.type === 'LIKERT' ? parseInt(value) : null
    }
  })
  
  try {
    await kioskApi.post(`/frontdesk/internal-transactions/evaluation/submit/${selectedRequest.value.id}`, {
      responses
    })
    
    showEvaluationModal.value = false
    alert('Evaluation submitted successfully!')
    await fetchRequests()
    
  } catch (err) {
    console.error('Failed to submit evaluation:', err.response?.data || err.message)
    alert(err.response?.data?.message || 'Failed to submit evaluation. Please try again.')
  } finally {
    evaluationSubmitting.value = false
  }
}

onMounted(() => {
  fetchDashboard()
  fetchRequests()
})
</script>