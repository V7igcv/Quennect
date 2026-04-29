<template>
  <div class="max-w-7xl mx-auto px-2 sm:px-2 lg:px-2 py-2">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
      <h1 class="text-2xl font-semibold">Backlog</h1>

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
                  !cell && 'cursor-default bg-transparent',
                  cell && isSelectedDay(cell)
                    ? 'bg-[#0F5C5C] text-white'
                    : 'bg-white text-gray-700 hover:bg-gray-50',
                ]"
                @click="cell && selectDailyDate(cell)"
              >
                <span v-if="cell">{{ cell }}</span>
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
                :class="monthIndex === selectedMonthIndex ? 'border-[#0F5C5C] bg-[#0F5C5C] text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'"
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
    </div>

    <!-- Loading -->
    <div
      v-if="isLoadingBacklog"
      class="mb-4 flex items-center gap-2 rounded-md border border-[#A7F3D0] bg-[#ECFDF5] px-4 py-3 text-sm font-medium text-[#0F5C5C]"
    >
      <Loader2 class="h-4 w-4 animate-spin" />
      Loading Backlog...
    </div>

    <!-- Tabs -->
    <div class="flex gap-4 border-b mb-4">
      <button 
        v-for="tab in tabs" 
        :key="tab.value"
        @click="activeTab = tab.value; currentPage = 1"
        class="pb-2 px-1 text-sm transition"
        :class="activeTab === tab.value ? 'border-b-2 border-[#0F5C5C] text-[#0F5C5C] font-medium' : 'text-gray-500'"
      >
        {{ tab.label }}
        <span class="ml-1 text-xs px-1.5 py-0.5 rounded-full bg-gray-100">
          {{ getCount(tab.value) }}
        </span>
      </button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm p-4 sm:p-6">

      <!-- Search Bar -->
      <div class="mb-4 relative">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" />
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search by queue no., client name, or service..."
          class="w-full pl-9 pr-4 py-2 border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-[#0F5C5C]/30 focus:border-[#0F5C5C]"
        />
      </div>

      <div class="overflow-x-auto">
        <Table class="w-full min-w-max">
          <TableHeader>
            <TableRow>
              <TableHead>Queue No.</TableHead>
              <TableHead>Client Name</TableHead>
              <TableHead>Service</TableHead>
              <TableHead>Lane Type</TableHead>
              <TableHead>Backlog Date & Time</TableHead>
              <TableHead>Status</TableHead>
              <TableHead v-if="activeTab === 'pending'">Actions</TableHead>
            </TableRow>
          </TableHeader>

          <TableBody>
            <TableRow v-for="transaction in paginatedEntries" :key="transaction.id" :class="{ 'bg-gray-50 opacity-75': transaction.status !== 'BACKLOG' && transaction.status !== undefined }">
              <TableCell>{{ transaction.queue_number }}</TableCell>
              <TableCell>{{ transaction.client_name }}</TableCell>

              <TableCell>
                <span
                  :title="transaction.service_names"
                  class="cursor-help underline decoration-dotted decoration-gray-400"
                >
                  {{ transaction.service_codes }}
                </span>
              </TableCell>

              <TableCell>{{ transaction.lane_type }}</TableCell>
              <TableCell>
                <div class="space-y-1">
                  <div>{{ transaction.backlog_time || 'N/A' }}</div>
                  <div v-if="transaction.backlog_time && formatRelativeTime(transaction.backlog_time)" class="text-xs italic text-gray-500">
                    {{ formatRelativeTime(transaction.backlog_time) }}
                  </div>
                </div>
              </TableCell>

              <TableCell>
                <span 
                  :class="{
                    'px-2 py-1 rounded-full text-xs font-semibold': true,
                    'bg-green-100 text-green-800': transaction.status === 'COMPLETED',
                    'bg-yellow-100 text-yellow-800': transaction.status === 'BACKLOG',
                    'bg-blue-100 text-blue-800': transaction.status === 'SERVING',
                    'bg-red-100 text-red-800': transaction.status === 'SKIPPED'
                  }"
                >
                  {{ transaction.status || 'BACKLOG' }}
                </span>
              </TableCell>

              <TableCell v-if="activeTab === 'pending'" class="flex gap-2">
                <Button
                  v-if="transaction.status === 'BACKLOG' || !transaction.status"
                  size="sm"
                  class="bg-[#2563EB] hover:bg-[#1D4ED8] text-white"
                  :disabled="isProcessing"
                  @click="openEvaluationModal(transaction)"
                >
                  <Check class="w-4 h-4" />
                  Complete
                </Button>

                <Button
                  v-if="transaction.status === 'BACKLOG' || !transaction.status"
                  size="sm"
                  variant="destructive"
                  class="bg-[#DC2626] hover:bg-[#B91C1C] text-white"
                  :disabled="isProcessing"
                  @click="openSkipConfirmModal(transaction)"
                >
                  <X class="w-4 h-4" />
                  Skip
                </Button>
              </TableCell>
            </TableRow>

            <!-- Empty state -->
            <TableRow v-if="!isLoadingBacklog && filteredEntries.length === 0">
              <TableCell colspan="7" class="text-center text-gray-500 py-8">
                {{ searchQuery ? 'No results found.' : 'No backlog transactions found.' }}
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>

      <!-- Pagination -->
      <div class="flex items-center justify-between mt-3">
        <p class="text-sm text-gray-500">
          {{ filteredEntries.length === 0 ? '0' : (currentPage - 1) * rowsPerPage + 1 }}–{{ Math.min(currentPage * rowsPerPage, filteredEntries.length) }} of {{ filteredEntries.length }} row(s) shown.
        </p>

        <div class="flex items-center gap-4">
          <div class="flex items-center gap-2 text-sm text-gray-600">
            <span class="whitespace-nowrap">Rows per page</span>
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

          <p class="text-sm text-gray-600 whitespace-nowrap">
            Page {{ currentPage }} of {{ totalPages }}
          </p>

          <div class="flex items-center gap-1">
            <button @click="firstPage" :disabled="currentPage === 1" class="p-1 rounded hover:bg-gray-100 disabled:opacity-40">
              <ChevronsLeft class="w-4 h-4" />
            </button>
            <button @click="previousPage" :disabled="currentPage === 1" class="p-1 rounded hover:bg-gray-100 disabled:opacity-40">
              <ChevronLeft class="w-4 h-4" />
            </button>
            <button @click="nextPage" :disabled="currentPage === totalPages" class="p-1 rounded hover:bg-gray-100 disabled:opacity-40">
              <ChevronRight class="w-4 h-4" />
            </button>
            <button @click="lastPage" :disabled="currentPage === totalPages" class="p-1 rounded hover:bg-gray-100 disabled:opacity-40">
              <ChevronsRight class="w-4 h-4" />
            </button>
          </div>

        </div>
      </div>

    </div>

    <!-- Skip Confirmation Modal -->
    <div v-if="showSkipConfirmModal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold mb-2">Skip Queue?</h3>
        <p class="text-gray-600 mb-4">
          Skip <span class="font-semibold">{{ skipTarget?.queue_number }}</span>? This action cannot be undone.
        </p>
        <div class="flex justify-end gap-2">
          <Button
            variant="outline"
            :disabled="isProcessing"
            @click="showSkipConfirmModal = false"
          >
            Cancel
          </Button>
          <Button
            class="text-white bg-[#DC2626] hover:bg-[#B91C1C]"
            :disabled="isProcessing"
            @click="confirmSkip"
          >
            {{ isProcessing ? 'Processing...' : 'Skip' }}
          </Button>
        </div>
      </div>
    </div>

    <!-- Alert Modal -->
    <div v-if="showAlertModal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-60">
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold mb-2">{{ alertTitle }}</h3>
        <p class="text-gray-600 mb-4">{{ alertMessage }}</p>
        <div class="flex justify-end">
          <Button class="bg-[#0F5C5C] hover:bg-[#167D7F] text-white" @click="closeAlertModal">OK</Button>
        </div>
      </div>
    </div>

    <!-- Evaluation Modal -->
    <EvaluationModal
      v-model="showEvaluationModal"
      :queue-number="selectedQueueNumber"
      :customer-name="selectedCustomerName"
      :contact-number="selectedContactNumber"
      :barangay="selectedBarangay"
      :services="selectedServices"
      :multiple-choice-questions="multipleChoiceQuestions"
      :likert-questions="likertQuestions"
      @submit="handleEvaluationSubmit"
      @alert="handleAlert"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import api from '@/services/api'
import EvaluationModal from '@/components/modals/EvaluationModal.vue'

import { Loader2, Check, X, Search, ChevronDown, ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight, Calendar } from 'lucide-vue-next'

import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover'

import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow
} from '@/components/ui/table'

import { Button } from '@/components/ui/button'

// Backlog state
const backlogEntries = ref([])
const isLoadingBacklog = ref(false)

// Date filter state
const isDateFilterOpen = ref(false)
const selectedDateRange = ref('monthly')
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

const selectDailyDate = (day) => {
  selectedDate.value = new Date(dailyViewYear.value, dailyViewMonth.value, day)
}

const isSelectedDay = (day) => {
  return selectedDate.value.getFullYear() === dailyViewYear.value
    && selectedDate.value.getMonth() === dailyViewMonth.value
    && selectedDate.value.getDate() === day
}

const formatDate = (date) => {
  const d = new Date(date)
  let month = '' + (d.getMonth() + 1)
  let day = '' + d.getDate()
  const year = d.getFullYear()

  if (month.length < 2) month = '0' + month
  if (day.length < 2) day = '0' + day

  return [year, month, day].join('-')
}

const formatRelativeTime = (time) => {
  const tDate = new Date(time)
  if (Number.isNaN(tDate.getTime())) return null

  const nowDate = new Date()
  const diffMs = nowDate.getTime() - tDate.getTime()

  if (diffMs < 0) return '0 minutes ago'

  const diffMinutes = Math.floor(diffMs / (1000 * 60))
  const diffHours = Math.floor(diffMs / (1000 * 60 * 60))
  const diffDaysTotal = Math.floor(diffMs / (1000 * 60 * 60 * 24))
  const startOfToday = new Date(nowDate.getFullYear(), nowDate.getMonth(), nowDate.getDate())
  const startOfTDay = new Date(tDate.getFullYear(), tDate.getMonth(), tDate.getDate())
  const diffDays = Math.floor((startOfToday.getTime() - startOfTDay.getTime()) / (1000 * 60 * 60 * 24))
  const diffMonths = Math.floor(diffDaysTotal / 30)

  const isSameCalendarDay = nowDate.getFullYear() === tDate.getFullYear()
    && nowDate.getMonth() === tDate.getMonth()
    && nowDate.getDate() === tDate.getDate()

  if (isSameCalendarDay) {
    if (diffHours < 1) {
      return `${Math.max(1, diffMinutes)} minute${Math.max(1, diffMinutes) === 1 ? '' : 's'} ago`
    }
    return `${Math.max(1, diffHours)} hour${Math.max(1, diffHours) === 1 ? '' : 's'} ago`
  }

  if (diffMonths < 1) {
    return `${Math.max(1, diffDays)} day${Math.max(1, diffDays) === 1 ? '' : 's'} ago`
  }

  return `${Math.max(1, diffMonths)} month${Math.max(1, diffMonths) === 1 ? '' : 's'} ago`
}

const getDateFilterParams = () => {
  if (selectedDateRange.value === 'monthly') {
    return {
      period: 'monthly',
      month: selectedMonthIndex.value + 1,
      year: selectedMonthYear.value,
    }
  }

  if (selectedDateRange.value === 'yearly') {
    return {
      period: 'yearly',
      year: selectedYear.value,
    }
  }

  return {
    period: 'daily',
    date: formatDate(selectedDate.value),
  }
}

// Scroll locking
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
    return
  }
  unlockPageScroll()
})

onBeforeUnmount(() => {
  unlockPageScroll()
})

watch(
  [selectedDateRange, selectedDate, selectedMonthIndex, selectedMonthYear, selectedYear],
  () => {
    fetchBacklog()
  }
)

// Tabs state
const tabs = [
  { value: 'pending', label: 'Pending' },
  { value: 'completed', label: 'Completed' },
  { value: 'skipped', label: 'Skipped' }
]
const activeTab = ref('pending')

const getCount = (tab) => {
  if (tab === 'pending') return backlogEntries.value.filter(t => t.status === 'BACKLOG' || !t.status).length
  if (tab === 'completed') return backlogEntries.value.filter(t => t.status === 'COMPLETED').length
  if (tab === 'skipped') return backlogEntries.value.filter(t => t.status === 'SKIPPED').length
  return 0
}

// Search state
const searchQuery = ref('')

// Pagination state
const currentPage = ref(1)
const rowsPerPage = ref(10)

// Filtered entries based on search and tabs
const filteredEntries = computed(() => {
  let entries = backlogEntries.value

  if (activeTab.value === 'pending') {
    entries = entries.filter(t => t.status === 'BACKLOG' || !t.status)
  } else if (activeTab.value === 'completed') {
    entries = entries.filter(t => t.status === 'COMPLETED')
  } else if (activeTab.value === 'skipped') {
    entries = entries.filter(t => t.status === 'SKIPPED')
  }

  if (!searchQuery.value.trim()) return entries

  const query = searchQuery.value.toLowerCase()
  return entries.filter(t =>
    t.queue_number?.toLowerCase().includes(query) ||
    t.client_name?.toLowerCase().includes(query) ||
    t.service_codes?.toLowerCase().includes(query) ||
    t.service_names?.toLowerCase().includes(query)
  )
})

// Total pages
const totalPages = computed(() => Math.max(1, Math.ceil(filteredEntries.value.length / rowsPerPage.value)))

// Paginated slice
const paginatedEntries = computed(() => {
  const start = (currentPage.value - 1) * rowsPerPage.value
  return filteredEntries.value.slice(start, start + rowsPerPage.value)
})

// Reset to page 1 on search
watch(searchQuery, () => { currentPage.value = 1 })

// Skip modal state
const showSkipConfirmModal = ref(false)
const skipTarget = ref(null)
const isProcessing = ref(false)

// Alert modal state
const showAlertModal = ref(false)
const alertTitle = ref('')
const alertMessage = ref('')

// Evaluation modal state
const showEvaluationModal = ref(false)
const selectedQueueNumber = ref('')
const selectedCustomerName = ref('')
const selectedContactNumber = ref('')
const selectedBarangay = ref('')
const selectedQueueId = ref(null)
const selectedServices = ref([])
const multipleChoiceQuestions = ref([])
const likertQuestions = ref([])

// Pagination methods
const previousPage = () => { if (currentPage.value > 1) currentPage.value-- }
const nextPage = () => { if (currentPage.value < totalPages.value) currentPage.value++ }
const firstPage = () => { currentPage.value = 1 }
const lastPage = () => { currentPage.value = totalPages.value }
const changeRowsPerPage = (value) => {
  rowsPerPage.value = parseInt(value)
  currentPage.value = 1
}

// Methods
const handleAlert = ({ title, message }) => {
  alertTitle.value = title
  alertMessage.value = message
  showAlertModal.value = true
}

const closeAlertModal = () => {
  showAlertModal.value = false
  // Don't refetch - keep the updated list with COMPLETED status
}

const fetchBacklog = async () => {
  isLoadingBacklog.value = true
  try {
    const response = await api.get('/frontdesk/backlog', { params: getDateFilterParams() })
    if (response.data.success) {
      backlogEntries.value = response.data.data
    }
  } catch (error) {
    console.error('Error fetching backlog:', error)
  } finally {
    isLoadingBacklog.value = false
  }
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

const openSkipConfirmModal = (transaction) => {
  if (transaction.status !== 'BACKLOG' && transaction.status !== undefined) return
  skipTarget.value = transaction
  showSkipConfirmModal.value = true
}

const confirmSkip = async () => {
  if (!skipTarget.value || isProcessing.value) return

  isProcessing.value = true
  try {
    const response = await api.post(`/frontdesk/backlog/skip/${skipTarget.value.id}`)
    if (response.data.message) {
      // Update the status to SKIPPED instead of removing
      const index = backlogEntries.value.findIndex(t => t.id === skipTarget.value.id)
      if (index !== -1) {
        backlogEntries.value[index] = {
          ...backlogEntries.value[index],
          status: 'SKIPPED',
          skipped_at: new Date().toISOString()
        }
      }
      showSkipConfirmModal.value = false
      skipTarget.value = null
      
      alertTitle.value = 'Success'
      alertMessage.value = 'Transaction skipped successfully!'
      showAlertModal.value = true
    }
  } catch (error) {
    console.error('Error skipping backlog transaction:', error)
    alertTitle.value = 'Error'
    alertMessage.value = error.response?.data?.message || 'Failed to skip transaction.'
    showAlertModal.value = true
  } finally {
    isProcessing.value = false
  }
}

const openEvaluationModal = async (transaction) => {
  if (transaction.status !== 'BACKLOG' && transaction.status !== undefined) return
  
  try {
    const response = await api.get(`/frontdesk/evaluation/transaction/${transaction.id}`)
    if (response.data.success) {
      const data = response.data.data
      selectedQueueNumber.value = data.queue_number
      selectedCustomerName.value = data.client_name
      selectedContactNumber.value = data.contact_number
      selectedBarangay.value = data.barangay_name || ''
      selectedQueueId.value = transaction.id
      selectedServices.value = data.services || []
      showEvaluationModal.value = true
    }
  } catch (error) {
    console.error('Error fetching transaction details:', error)
    alertTitle.value = 'Error'
    alertMessage.value = error.response?.data?.message || 'Error opening evaluation modal.'
    showAlertModal.value = true
  }
}

const handleEvaluationSubmit = async (formData) => {
  if (!selectedQueueId.value) return

  isProcessing.value = true

  try {
    const multipleChoiceAnswers = formData.multipleChoiceAnswers || {}
    const likertAnswers = formData.likertRatings || {}
    const assistancePerService = formData.assistance_per_queue_transaction_service || []

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
      },
      ...(assistancePerService.length > 0 && { assistance_per_queue_transaction_service: assistancePerService })
    }

    // Submit evaluation - this will automatically complete the transaction
    const response = await api.post(`/frontdesk/evaluation/submit/${selectedQueueId.value}`, evaluationData)

    if (response.data.message) {
      const smsSent = Boolean(response.data?.data?.sms_sent)

      // Update the specific transaction in the list to show COMPLETED status
      const index = backlogEntries.value.findIndex(t => t.id === selectedQueueId.value)
      if (index !== -1) {
        backlogEntries.value[index] = {
          ...backlogEntries.value[index],
          status: 'COMPLETED',
          completed_at: new Date().toISOString()
        }
      }

      // Reset form
      selectedQueueId.value = null
      selectedQueueNumber.value = ''
      selectedCustomerName.value = ''
      selectedContactNumber.value = ''
      selectedBarangay.value = ''
      selectedServices.value = []

      alertTitle.value = 'Success'
      alertMessage.value = smsSent
        ? 'Evaluation submitted successfully. Transaction marked as completed. SMS sent to client.'
        : 'Evaluation submitted successfully. Transaction marked as completed, but SMS was not sent.'
      showAlertModal.value = true
      
      // Close the evaluation modal
      showEvaluationModal.value = false
    }
  } catch (error) {
    console.error('Error submitting evaluation:', error)
    alertTitle.value = 'Error'
    alertMessage.value = error.response?.data?.message || 'Error submitting evaluation.'
    showAlertModal.value = true
  } finally {
    isProcessing.value = false
  }
}

onMounted(async () => {
  await Promise.all([
    fetchBacklog(),
    fetchEvaluationQuestions(),
  ])
})
</script>