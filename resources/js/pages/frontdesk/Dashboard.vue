<template>
  <div class="max-w-7xl mx-auto px-2 sm:px-2 lg:px-2 py-2">
    <h1 class="text-2xl font-semibold mb-6">Queue Dashboard</h1>

    <div
      v-if="isLoadingDashboardData"
      class="mb-4 flex items-center gap-2 rounded-md border border-[#A7F3D0] bg-[#ECFDF5] px-4 py-3 text-sm font-medium text-[#0F5C5C]"
    >
      <Loader2 class="h-4 w-4 animate-spin" />
      Loading Dashboard Data...
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-6">

      <StatCard class="border-l-4 border-orange-300"
        title="Waiting"
        :value="stats.waiting"
        :icon="Clock"
        iconBg="bg-orange-100"
        iconColor="text-orange-500"
        numberColor="text-orange-500"
      />

      <StatCard class="border-l-4 border-green-400"
        title="Serving"
        :value="stats.serving"
        :icon="User"
        iconBg="bg-green-100"
        iconColor="text-green-600"
        numberColor="text-green-600"
      />

      <StatCard class="border-l-4 border-blue-400"
        title="Completed"
        :value="stats.completed"
        :icon="CheckCircle"
        iconBg="bg-blue-100"
        iconColor="text-blue-600"
        numberColor="text-blue-600"
      />

      <StatCard class="border-l-4 border-red-400"
        title="Skipped"
        :value="stats.skipped"
        :icon="XCircle"
        iconBg="bg-red-100"
        iconColor="text-red-600"
        numberColor="text-red-600"
      />

    </div>

    <!-- Queue Management Table -->
    <div class="mt-6 flex flex-col xl:flex-row gap-6">

      <div class="w-full xl:flex-2">
        <h2 class="text-xl font-semibold mb-4">
          Queue Management
        </h2>

        <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6">
          <div class="overflow-x-auto">
            <Table class="w-full min-w-max">
              <TableHeader>
                <TableRow>
                  <TableHead>Queue No.</TableHead>
                  <TableHead>Client Name</TableHead>
                  <TableHead>Service</TableHead>
                  <TableHead>Lane Type</TableHead>
                  <TableHead>Time</TableHead>
                  <TableHead>Actions</TableHead>
                </TableRow>
              </TableHeader>

              <TableBody>
                <TableRow v-for="queue in filteredQueueEntries" :key="queue.id">
                  <TableCell>{{ queue.queue_number }}</TableCell>
                  <TableCell>{{ queue.client_name }}</TableCell>
                  <TableCell>{{ queue.services }}</TableCell>
                  <TableCell>{{ queue.lane_type }}</TableCell>
                  <TableCell>{{ queue.time }}</TableCell>

                  <TableCell class="flex gap-2">
                    <Button 
                      size="sm" 
                      class="bg-[#16A34A] hover:bg-[#15803D] text-white"
                      @click="openCounterDropdown(queue)"
                    >
                      <Megaphone class="w-4 h-4" />
                      Call
                    </Button>

                    <Button 
                      size="sm" 
                      variant="destructive" 
                      class="bg-[#DC2626] hover:bg-[#B91C1C] text-white"
                      @click="skipQueue(queue.id)"
                    >
                      <X class="w-4 h-4" />
                      Skip
                    </Button>
                  </TableCell>
                </TableRow>

                <!-- Empty state -->
                <TableRow v-if="!isLoadingQueue && filteredQueueEntries.length === 0">
                  <TableCell colspan="6" class="text-center text-gray-500 py-8">
                    No waiting queues at this time
                  </TableCell>
                </TableRow>

                <TableRow v-if="isLoadingQueue">
                  <TableCell colspan="6" class="text-center text-gray-500 py-8">
                    Loading queue data...
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>

          <!-- Pagination -->
          <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 mt-3">

            <!-- LEFT SIDE -->
            <p class="text-sm text-gray-500">
              {{ (currentPage - 1) * rowsPerPage + 1 }}–{{ Math.min(currentPage * rowsPerPage, totalRows) }} of {{ totalRows }} row(s) shown.
            </p>

            <!-- RIGHT SIDE -->
            <div class="flex flex-wrap items-center gap-4 sm:gap-6">

              <!-- Rows per page -->
              <div class="flex items-center gap-2 text-sm text-gray-600 whitespace-nowrap">
                <span>Rows per page</span>

                <div class="relative">
                  <select 
                    class="appearance-none border rounded-md pl-2 pr-8 py-1 text-sm"
                    :value="rowsPerPage"
                    @change="changeRowsPerPage($event.target.value)"
                  >
                    <option value="5">5</option>
                    <option value="10">10</option>
                    <option value="20">20</option>
                  </select>
                  <ChevronDown class="w-4 h-4 text-gray-500 absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none" />
                </div>
              </div>

              <!-- Page indicator -->
              <p class="text-sm text-gray-600 whitespace-nowrap">
                Page {{ currentPage }} of {{ totalPages() }}
              </p>

              <!-- Pagination Buttons -->
              <Pagination>
                <PaginationContent>

                  <PaginationItem>
                    <PaginationFirst @click="firstPage" :disabled="currentPage === 1" />
                  </PaginationItem>

                  <PaginationItem>
                    <PaginationPrevious @click="previousPage" :disabled="currentPage === 1" />
                  </PaginationItem>

                  <PaginationItem>
                    <PaginationNext @click="nextPage" :disabled="currentPage === totalPages()" />
                  </PaginationItem>

                  <PaginationItem>
                    <PaginationLast @click="lastPage" :disabled="currentPage === totalPages()" />
                  </PaginationItem>

                </PaginationContent>
              </Pagination>

            </div>
          </div>

        </div>

        <!-- Counter Selection Dropdown/Modal -->
        <div v-if="showCounterDropdown && selectedQueueForCall" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50">
          <div class="bg-white rounded-lg shadow-lg p-6 max-w-md w-full">
            <h3 class="text-lg font-semibold mb-4">
              Select Counter for {{ selectedQueueForCall.queue_number }}
            </h3>

            <div v-if="isLoadingCounters" class="text-center py-4">
              Loading available counters...
            </div>

            <div v-else-if="availableCounters.length === 0" class="text-center py-4 text-gray-500">
              No available counters at the moment
            </div>

            <div v-else class="space-y-2 max-h-96 overflow-y-auto">
              <button
                v-for="counter in availableCounters"
                :key="counter.id"
                @click="callQueue(counter.id)"
                class="w-full p-3 text-left border rounded-md hover:bg-gray-100 transition"
              >
                <span class="font-semibold">Counter {{ counter.counter_number }}</span>
                <span class="text-sm text-gray-600"> - Available</span>
              </button>
            </div>

            <button
              @click="showCounterDropdown = false"
              class="w-full mt-4 px-4 py-2 text-sm border rounded-md hover:bg-gray-100"
            >
              Cancel
            </button>
          </div>
        </div>

      </div>

      <div class="w-full xl:flex-1">
        <div class="flex items-center justify-between gap-3">
          <h2 class="text-xl font-semibold mb-4">
            Counters
          </h2> 
          <Button 
            class="h-8 p-3 bg-[#0F5C5C] hover:bg-[#167D7F] text-white inline-flex items-center gap-2"
            @click="showAddCounterModal = true"
          >
            <Plus class="w-4 h-4" />
            Add Counter
          </Button> 
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 flex flex-col gap-4">
          <!-- Counter Cards -->
          <div 
            v-for="counter in counters" 
            :key="counter.id"
            :class="[
              'rounded-md border p-4 transition-colors',
              counter.is_enabled
                ? (counter.current_queue ? 'bg-blue-50' : 'bg-[#dbf1ed]')
                : 'bg-gray-200'
            ]"
          >
            <div class="flex justify-between">
              <div class="flex gap-3">
                <p class="text-sm text-[#2E2E2E] font-semibold">
                  Counter {{ counter.counter_number }}
                </p>
                <p 
                  :class="[
                    'text-xs font-regular italic',
                    counter.is_enabled ? 'text-green-600' : 'text-gray-600'
                  ]"
                >
                  {{ counter.status }}
                </p>              
              </div>

              <!-- Counter Menu -->
              <div class="relative">
                <button 
                  @click="showCounterMenu = showCounterMenu === counter.id ? null : counter.id"
                  class="text-gray-600 hover:text-gray-800 p-1 rounded-md hover:bg-gray-200"
                >
                  <MoreHorizontal class="w-4 h-4" />
                </button>

                <!-- Dropdown Menu -->
                <div 
                  v-if="showCounterMenu === counter.id"
                  class="absolute right-0 mt-2 w-32 bg-white border rounded-md shadow-lg z-10"
                >
                  <button
                    @click="toggleCounterStatus(counter)"
                    :disabled="counter.current_queue && counter.is_enabled"
                    :class="counter.current_queue && counter.is_enabled ? 'opacity-50 cursor-not-allowed' : ''"
                    class="w-full text-left px-4 py-2 text-sm hover:bg-gray-100 transition"
                  >
                    {{ counter.is_enabled ? 'Disable' : 'Enable' }}
                  </button>
                  <button
                    @click="openDeleteConfirmModal(counter)"
                    :disabled="counter.current_queue || counters.length <= 1"
                    :class="(counter.current_queue || counters.length <= 1) ? 'opacity-50 cursor-not-allowed' : ''"
                    class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition"
                  >
                    Delete
                  </button>
                </div>
              </div>
            </div>
            
            <p :class="[
              'text-2xl font-bold py-3',
              counter.current_queue 
                ? 'text-[#1F4E79]' 
                : 'italic text-[#6B7280] font-medium'
            ]">
              {{ counter.current_queue ? counter.current_queue.queue_number : 'Idle' }}
            </p>

            <!-- Buttons (only show if serving a queue) -->
            <div v-if="counter.current_queue" class="flex gap-2">
              <Button 
                size="sm" 
                class="flex-1 bg-[#2563EB] hover:bg-[#1D4ED8] text-white"
                @click="openEvaluationModal(counter.current_queue)"
              >
                <Check class="w-4 h-4" />
                Complete
              </Button>

              <Button 
                size="sm" 
                variant="destructive" 
                class="flex-1 bg-[#DC2626] hover:bg-[#B91C1C] text-white"
                @click="skipFromCounter(counter.current_queue.id)"
              >
                <X class="w-4 h-4" />
                Skip
              </Button>
            </div>
          </div>

          <!-- Loading state -->
          <div v-if="isLoadingCounters_countersSection" class="text-center py-8 text-gray-500">
            Loading counters...
          </div>

          <!-- Empty state -->
          <div v-else-if="counters.length === 0" class="text-center py-8 text-gray-500">
            No counters added yet. Click "Add Counter" to create one.
          </div>
        </div>

        <!-- Add Counter Modal -->
        <div v-if="showAddCounterModal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50">
          <div class="bg-white rounded-lg shadow-lg p-6 max-w-sm w-full">
            <h3 class="text-lg font-semibold mb-4">Add a new counter?</h3>

            <div class="flex gap-2">
              <button
                @click="addCounter"
                class="flex-1 px-4 py-2 bg-[#0F5C5C] text-white rounded-md hover:bg-[#167D7F]"
              >
                Confirm
              </button>
              <button
                @click="showAddCounterModal = false"
                class="flex-1 px-4 py-2 border rounded-md hover:bg-gray-100"
              >
                Cancel
              </button>
            </div>
          </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteConfirmModal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50">
          <div class="bg-white rounded-lg shadow-lg p-6 max-w-sm w-full">
            <h3 class="text-lg font-semibold mb-4">Delete Counter?</h3>
            <p class="text-gray-600 mb-4">
              Are you sure you want to delete Counter {{ counterToDelete?.counter_number }}? This action cannot be undone.
            </p>

            <div class="flex gap-2">
              <button
                @click="deleteCounter"
                class="flex-1 px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
              >
                Delete
              </button>
              <button
                @click="showDeleteConfirmModal = false"
                class="flex-1 px-4 py-2 border rounded-md hover:bg-gray-100"
              >
                Cancel
              </button>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- Alert Modal -->
    <div v-if="showAlertModal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-[60]">
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold mb-2">{{ alertTitle }}</h3>
        <p class="text-gray-600 mb-4">{{ alertMessage }}</p>
        <div class="flex justify-end">
          <Button class="bg-[#0F5C5C] hover:bg-[#167D7F] text-white" @click="showAlertModal = false">OK</Button>
        </div>
      </div>
    </div>

    <EvaluationModal
      v-model="showEvaluationModal"
      :queue-number="selectedQueueNumber"
      :customer-name="selectedCustomerName"
      :contact-number="selectedContactNumber"
      :barangay="selectedBarangay"
      :multiple-choice-questions="multipleChoiceQuestions"
      :likert-questions="likertQuestions"
      @submit="handleEvaluationSubmit"
      @alert="handleAlert"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'
import StatCard from '@/components/common/StatCard.vue'
import EvaluationModal from '@/components/modals/EvaluationModal.vue'

import { Clock, User, CheckCircle, XCircle, MoreHorizontal, X, Check, Megaphone, ChevronDown, Loader2, Plus } from 'lucide-vue-next'

import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow
} from '@/components/ui/table'

import { Button } from '@/components/ui/button'

import {
  Pagination,
  PaginationContent,
  PaginationItem,
  PaginationNext,
  PaginationPrevious,
  PaginationFirst,
  PaginationLast
} from '@/components/ui/pagination'

// Reactive data
const stats = ref({
  waiting: 0,
  serving: 0,
  completed: 0,
  skipped: 0
})

const queueEntries = ref([])
const filteredQueueEntries = ref([])
const isLoadingQueue = ref(false)
const isLoadingDashboardData = ref(false)

// Pagination state
const currentPage = ref(1)
const rowsPerPage = ref(10)
const totalRows = ref(0)

// Counter dropdown state
const showCounterDropdown = ref(false)
const selectedQueueForCall = ref(null)
const availableCounters = ref([])
const isLoadingCounters = ref(false)

// Counters state
const counters = ref([])
const isLoadingCounters_countersSection = ref(false)
const showAddCounterModal = ref(false)
const showDeleteConfirmModal = ref(false)
const counterToDelete = ref(null)
const showCounterMenu = ref(null)

// Modal state
const showEvaluationModal = ref(false)
const selectedQueueNumber = ref('')
const selectedCustomerName = ref('')
const selectedContactNumber = ref('')
const selectedBarangay = ref('')
const selectedQueueId = ref(null)
const multipleChoiceQuestions = ref([])
const likertQuestions = ref([])

// Alert modal state
const showAlertModal = ref(false)
const alertTitle = ref('')
const alertMessage = ref('')

// Methods
const handleAlert = ({ title, message }) => {
  alertTitle.value = title
  alertMessage.value = message
  showAlertModal.value = true
}

const fetchEvaluationQuestions = async () => {
  try {
    const response = await api.get('/frontdesk/evaluation/questions')
    if (response.data.success) {
      multipleChoiceQuestions.value = response.data.data.multiple_choice || []
      likertQuestions.value = response.data.data.likert || []
    }
  } catch (error) {
    console.error('Error fetching evaluation questions:', error)
  }
}

const fetchDashboardStats = async () => {
  try {
    const response = await api.get('/frontdesk/dashboard-stats')
    if (response.data.success) {
      stats.value = response.data.data
    }
  } catch (error) {
    console.error('Error fetching dashboard stats:', error)
  }
}

const fetchQueueTable = async () => {
  isLoadingQueue.value = true
  try {
    const response = await api.get('/frontdesk/queue-table')
    if (response.data.success) {
      queueEntries.value = response.data.data
      totalRows.value = queueEntries.value.length
      updatePaginatedQueue()
    }
  } catch (error) {
    console.error('Error fetching queue table:', error)
  } finally {
    isLoadingQueue.value = false
  }
}

const updatePaginatedQueue = () => {
  const startIdx = (currentPage.value - 1) * rowsPerPage.value
  const endIdx = startIdx + rowsPerPage.value
  filteredQueueEntries.value = queueEntries.value.slice(startIdx, endIdx)
}

const totalPages = () => {
  return Math.ceil(totalRows.value / rowsPerPage.value)
}

const fetchAvailableCounters = async () => {
  isLoadingCounters.value = true
  try {
    const response = await api.get('/frontdesk/counters/available')
    if (response.data.success) {
      availableCounters.value = response.data.data
    }
  } catch (error) {
    console.error('Error fetching available counters:', error)
  } finally {
    isLoadingCounters.value = false
  }
}

const openCounterDropdown = async (queue) => {
  selectedQueueForCall.value = queue
  showCounterDropdown.value = true
  await fetchAvailableCounters()
}

const callQueue = async (counterId) => {
  if (!selectedQueueForCall.value) return

  try {
    const response = await api.post(`/frontdesk/queue/call/${selectedQueueForCall.value.id}`, {
      counter_id: counterId
    })

    if (response.data.message) {
      // Remove the queue from the list and refresh
      queueEntries.value = queueEntries.value.filter(q => q.id !== selectedQueueForCall.value.id)
      totalRows.value = queueEntries.value.length
      
      // Reset pagination if current page is now empty
      if (filteredQueueEntries.value.length === 0 && currentPage.value > 1) {
        currentPage.value -= 1
      }
      
      updatePaginatedQueue()
      showCounterDropdown.value = false
      selectedQueueForCall.value = null
      
      // Refresh stats and counters
      await fetchDashboardStats()
      await fetchCounters()
    }
  } catch (error) {
    console.error('Error calling queue:', error)
    alert(error.response?.data?.message || 'Error calling queue')
  }
}

const skipQueue = async (queueId) => {
  try {
    const response = await api.post(`/frontdesk/queue/skip-from-table/${queueId}`)

    if (response.data.message) {
      // Remove the queue from the list
      queueEntries.value = queueEntries.value.filter(q => q.id !== queueId)
      totalRows.value = queueEntries.value.length
      
      // Reset pagination if current page is now empty
      if (filteredQueueEntries.value.length === 0 && currentPage.value > 1) {
        currentPage.value -= 1
      }
      
      updatePaginatedQueue()
      
      // Refresh stats
      await fetchDashboardStats()
    }
  } catch (error) {
    console.error('Error skipping queue:', error)
    alert(error.response?.data?.message || 'Error skipping queue')
  }
}

const previousPage = () => {
  if (currentPage.value > 1) {
    currentPage.value -= 1
    updatePaginatedQueue()
  }
}

const nextPage = () => {
  if (currentPage.value < totalPages()) {
    currentPage.value += 1
    updatePaginatedQueue()
  }
}

const firstPage = () => {
  currentPage.value = 1
  updatePaginatedQueue()
}

const lastPage = () => {
  currentPage.value = totalPages()
  updatePaginatedQueue()
}

const changeRowsPerPage = (value) => {
  rowsPerPage.value = parseInt(value)
  currentPage.value = 1
  updatePaginatedQueue()
}

const fetchCounters = async () => {
  isLoadingCounters_countersSection.value = true
  try {
    const response = await api.get('/frontdesk/counters')
    if (response.data.success) {
      counters.value = response.data.data
    }
  } catch (error) {
    console.error('Error fetching counters:', error)
  } finally {
    isLoadingCounters_countersSection.value = false
  }
}

const addCounter = async () => {
  // Calculate next counter number
  const nextCounterNumber = counters.value.length > 0
    ? Math.max(...counters.value.map(c => c.counter_number)) + 1
    : 1

  try {
    const response = await api.post('/frontdesk/counters', {
      counter_number: nextCounterNumber
    })

    if (response.data.data) {
      // Add the new counter to the list
      counters.value.push(response.data.data)
      showAddCounterModal.value = false
    }
  } catch (error) {
    console.error('Error adding counter:', error)
    alert(error.response?.data?.message || 'Error adding counter')
  }
}

const toggleCounterStatus = async (counter) => {
  // Check if counter is currently serving
  if (!counter.current_queue && counter.is_enabled) {
    // Disable
    try {
      const response = await api.put(`/frontdesk/counters/${counter.id}/status`, {
        is_enabled: false
      })
      if (response.data.data) {
        counter.is_enabled = false
        counter.status = 'Disabled'
      }
    } catch (error) {
      console.error('Error updating counter status:', error)
      alert(error.response?.data?.message || 'Error updating counter')
    }
  } else if (!counter.is_enabled) {
    // Enable
    try {
      const response = await api.put(`/frontdesk/counters/${counter.id}/status`, {
        is_enabled: true
      })
      if (response.data.data) {
        counter.is_enabled = true
        counter.status = 'Available'
      }
    } catch (error) {
      console.error('Error updating counter status:', error)
      alert(error.response?.data?.message || 'Error updating counter')
    }
  } else {
    alert('Cannot disable counter while it is serving a queue.')
  }
  showCounterMenu.value = null
}

const openDeleteConfirmModal = (counter) => {
  if (counters.value.length <= 1) {
    alert('Each office must have at least 1 counter. You cannot delete the last counter.')
    return
  }

  // Check if counter is currently serving
  if (counter.current_queue) {
    alert('Cannot delete counter while it is serving a queue.')
    return
  }
  counterToDelete.value = counter
  showDeleteConfirmModal.value = true
  showCounterMenu.value = null
}

const deleteCounter = async () => {
  if (!counterToDelete.value) return

  try {
    await api.delete(`/frontdesk/counters/${counterToDelete.value.id}`)
    // Remove the counter from the list
    counters.value = counters.value.filter(c => c.id !== counterToDelete.value.id)
    showDeleteConfirmModal.value = false
    counterToDelete.value = null
  } catch (error) {
    console.error('Error deleting counter:', error)
    alert(error.response?.data?.message || 'Error deleting counter')
  }
}

const skipFromCounter = async (queueId) => {
  try {
    const response = await api.post(`/frontdesk/queue/skip-from-counter/${queueId}`)

    if (response.data.message) {
      // Refresh counters to remove the queue from the counter
      await fetchCounters()
      // Refresh stats
      await fetchDashboardStats()
    }
  } catch (error) {
    console.error('Error skipping queue from counter:', error)
    alert(error.response?.data?.message || 'Error skipping queue')
  }
}

const openEvaluationModal = async (queueData) => {
  try {
    // Fetch transaction details for evaluation
    const response = await api.get(`/frontdesk/evaluation/transaction/${queueData.id}`)
    if (response.data.success) {
      const transaction = response.data.data
      selectedQueueNumber.value = transaction.queue_number
      selectedCustomerName.value = transaction.client_name
      selectedContactNumber.value = transaction.contact_number
      selectedBarangay.value = transaction.barangay_name || ''
      selectedQueueId.value = queueData.id
      showEvaluationModal.value = true
    }
  } catch (error) {
    console.error('Error fetching transaction details:', error)
    alertTitle.value = 'Error'
    alertMessage.value = error.response?.data?.message || 'Error opening evaluation modal'
    showAlertModal.value = true
  }
}

const handleEvaluationSubmit = async (formData) => {
  if (!selectedQueueId.value) return

  try {
    const multipleChoiceAnswers = formData.multipleChoiceAnswers || {}
    const likertAnswers = formData.likertRatings || {}

    // Format the data for the backend API
    const evaluationData = {
      session: {
        client_type: formData.client_type || null,
        sex: formData.sex || null,
        age: formData.age ?? null
      },
      responses: {
        multiple_choice: Object.fromEntries(
          Object.entries(multipleChoiceAnswers)
            .filter(([, value]) => value !== '' && value !== null && value !== undefined)
            .map(([questionId, value]) => [questionId, String(value)])
        ),
        likert: Object.fromEntries(
          Object.entries(likertAnswers)
            .filter(([, value]) => value !== '' && value !== null && value !== undefined)
            .map(([questionId, value]) => [questionId, String(value)])
        )
      }
    }

    const response = await api.post(`/frontdesk/evaluation/submit/${selectedQueueId.value}`, evaluationData)

    if (response.data.message) {
      // Success - refresh counters and stats
      await fetchCounters()
      await fetchDashboardStats()
      
      // Reset modal state
      selectedQueueId.value = null
      selectedQueueNumber.value = ''
      selectedCustomerName.value = ''
      selectedContactNumber.value = ''
      selectedBarangay.value = ''
      
      alertTitle.value = 'Success'
      alertMessage.value = 'Evaluation submitted successfully!'
      showAlertModal.value = true
    }
  } catch (error) {
    console.error('Error submitting evaluation:', error)
    alertTitle.value = 'Error'
    alertMessage.value = error.response?.data?.message || 'Error submitting evaluation'
    showAlertModal.value = true
  }
}

// Lifecycle
onMounted(() => {
  const loadDashboard = async () => {
    isLoadingDashboardData.value = true
    try {
      await Promise.all([
        fetchEvaluationQuestions(),
        fetchDashboardStats(),
        fetchQueueTable(),
        fetchCounters(),
      ])
    } finally {
      isLoadingDashboardData.value = false
    }
  }

  loadDashboard()
})
</script>