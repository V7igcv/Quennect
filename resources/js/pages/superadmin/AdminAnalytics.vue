<template>
  <div class="max-w-7xl mx-auto px-2 sm:px-2 lg:px-2 py-2">
    <!-- Header with title and controls -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
      <h1 class="text-2xl font-semibold">Queue Analytics</h1>

      <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full sm:w-auto">
        <!-- Office Dropdown -->
        <Select v-model="selectedOffice">
          <SelectTrigger class="w-full sm:w-[220px] bg-white cursor-pointer">
            <span class="flex items-center gap-2">
              <Building2 class="h-4 w-4 text-gray-500 shrink-0" />
              <SelectValue placeholder="Select Office" />
            </span>
          </SelectTrigger>
          <SelectContent>
            <SelectItem
              v-for="office in officeOptions"
              :key="office.value"
              :value="office.value"
            >
              {{ office.label }}
            </SelectItem>
          </SelectContent>
        </Select>

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
                :variant="selectedDateRange === 'daily' ? 'default' : 'secondary'"
                @click="selectedDateRange = 'daily'"
              >
                Daily
              </Button>
              <Button
                type="button"
                size="sm"
                :variant="selectedDateRange === 'monthly' ? 'default' : 'secondary'"
                @click="selectedDateRange = 'monthly'"
              >
                Monthly
              </Button>
              <Button
                type="button"
                size="sm"
                :variant="selectedDateRange === 'yearly' ? 'default' : 'secondary'"
                @click="selectedDateRange = 'yearly'"
              >
                Yearly
              </Button>
            </div>
          </PopoverContent>
        </Popover>
      </div>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5">
      <StatCard
        class="border-l-4 border-orange-300"
        title="Total Clients"
        :value="stats.totalClients"
        :icon="Users"
        iconBg="bg-orange-100"
        iconColor="text-orange-500"
        numberColor="text-orange-500"
      />

      <StatCard
        class="border-l-4 border-green-400"
        title="Total Served"
        :value="stats.totalServed"
        :icon="UserCheck"
        iconBg="bg-green-100"
        iconColor="text-green-600"
        numberColor="text-green-600"
      />

      <StatCard
        class="border-l-4 border-red-400"
        title="Total Skipped"
        :value="stats.totalSkipped"
        :icon="XCircle"
        iconBg="bg-red-100"
        iconColor="text-red-600"
        numberColor="text-red-600"
      />

      <StatCard
        class="border-l-4 border-blue-400"
        title="Average Waiting Time"
        :value="stats.averageWaitingTime"
        :icon="Clock3"
        iconBg="bg-blue-100"
        iconColor="text-blue-600"
        numberColor="text-blue-600"
        suffix=" min"
      />

      <StatCard
        class="border-l-4 border-purple-400"
        title="Average Service Time"
        :value="stats.averageServiceTime"
        :icon="Hourglass"
        iconBg="bg-purple-100"
        iconColor="text-purple-600"
        numberColor="text-purple-600"
        suffix=" min"
      />
    </div>

    <!-- Charts Row -->
    <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div>
        <h2 class="mb-3 text-lg font-semibold">Client Satisfaction Distribution</h2>
        <Card class="w-full">
          <CardContent class="pt-6">
            <div class="h-[300px] w-full">
              <div class="h-full rounded-lg border border-gray-100 bg-gray-50 p-4">
                <div class="flex h-full items-end gap-3 border-b border-gray-200 pb-3">
                  <div
                    v-for="(entry, index) in clientSatisfactionData"
                    :key="`client-satisfaction-bar-${index}`"
                    class="flex min-w-0 flex-1 flex-col items-center"
                  >
                    <TooltipProvider>
                      <Tooltip>
                        <TooltipTrigger as-child>
                          <div class="flex h-48 w-full max-w-12 items-end cursor-help">
                            <div
                              class="w-full rounded-t-md transition-all duration-300"
                              :style="{
                                height: `${getClientSatisfactionBarHeight(entry.value)}%`,
                                backgroundColor: getClientSatisfactionBarColor(entry.label)
                              }"
                            ></div>
                          </div>
                        </TooltipTrigger>
                        <TooltipContent class="min-w-36 rounded-md border border-gray-200 bg-white px-3 py-2 text-xs shadow-lg">
                          <p class="font-semibold text-gray-900">{{ entry.label }}</p>
                          <p class="mt-1 text-gray-600">Clients: <span class="font-semibold">{{ entry.value }}</span></p>
                        </TooltipContent>
                      </Tooltip>
                    </TooltipProvider>
                    <span class="mt-2 h-10 px-1 text-[11px] font-medium leading-tight text-center text-gray-600 flex items-start justify-center">
                      {{ entry.label }}
                    </span>
                    <span class="mt-1 text-xs font-semibold text-gray-900">{{ entry.value }}</span>
                  </div>
                </div>
              </div>
            </div>

            <div class="mt-6 flex items-center gap-2 border-t border-gray-100 pt-4">
              <span class="text-sm font-medium text-gray-500">Total Responses:</span>
              <span class="text-lg font-semibold text-gray-900">{{ clientSatisfactionTotalResponses }}</span>
            </div>
          </CardContent>
        </Card>
      </div>

      <div>
        <h2 class="mb-3 text-lg font-semibold">Lane Type Distribution</h2>
        <Card class="w-full">
          <CardContent>
            <div class="h-[300px] w-full mt-2 flex items-center justify-center">
              <div
                class="relative h-52 w-52"
                @mousemove="handleLaneDonutMouseMove"
                @mouseleave="clearLaneHoverSegment"
              >
                <div class="h-full w-full rounded-full" :style="{ background: lanePieGradient }"></div>
                <div class="absolute inset-6 rounded-full bg-white flex flex-col items-center justify-center">
                  <span class="text-2xl font-bold text-gray-900">{{ laneTotalClients }}</span>
                  <span class="text-xs text-gray-500">Total Clients</span>
                </div>

                <div
                  v-if="hoveredLaneSegment"
                  class="pointer-events-none absolute z-20 min-w-36 rounded-md border border-gray-200 bg-white px-3 py-2 text-xs shadow-lg"
                  :style="{ left: `${laneTooltipPosition.x}px`, top: `${laneTooltipPosition.y}px`, transform: 'translate(8px, -110%)' }"
                >
                  <p class="font-semibold text-gray-900">{{ hoveredLaneSegment.name }}</p>
                  <p class="mt-1 text-gray-600">Clients: <span class="font-semibold">{{ hoveredLaneSegment.value }}</span></p>
                  <p class="text-gray-600">Percentage: <span class="font-semibold">{{ hoveredLaneSegment.percentage }}%</span></p>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 mt-4 pt-4 border-t border-gray-100">
              <div
                v-for="(segment, index) in laneTypeChartData"
                :key="`lane-segment-${index}`"
                class="flex items-center gap-2 text-sm"
              >
                <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: getLaneSegmentColor(index) }"></div>
                <span class="text-gray-600">{{ segment.name }}</span>
                <span class="text-gray-900 font-medium ml-auto">{{ segment.percentage }}%</span>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>

    <!-- Queue Summary Table -->
    <div class="mt-8">
      <h2 class="text-xl font-semibold mb-4">Queue Summary</h2>

      <div class="bg-white rounded-xl shadow-sm p-6">
        <Table class="w-full table-fixed">
          <TableHeader>
            <TableRow>
              <TableHead class="w-[11.11%]">Queue Number</TableHead>
              <TableHead class="w-[11.11%]">Client Name</TableHead>
              <TableHead class="w-[11.11%]">Service</TableHead>
              <TableHead class="w-[11.11%]">Lane Type</TableHead>
              <TableHead class="w-[11.11%]">Status</TableHead>
              <TableHead class="w-[11.11%]">Completion Time</TableHead>
              <TableHead class="w-[11.11%]">Average Waiting Time</TableHead>
              <TableHead class="w-[11.11%]">Average Serving Time</TableHead>
              <TableHead class="w-[11.11%]">Average Satisfaction Rating</TableHead>
            </TableRow>
          </TableHeader>

          <TableBody>
            <TableRow v-for="entry in paginatedQueueSummaryRows" :key="entry.id">
              <TableCell class="font-medium">{{ entry.queueNumber }}</TableCell>
              <TableCell>{{ entry.clientName }}</TableCell>
              <TableCell>{{ entry.serviceCode }}</TableCell>
              <TableCell>{{ entry.laneType }}</TableCell>
              <TableCell>
                <span
                  class="rounded-full px-2 py-1 text-xs font-medium"
                  :class="entry.status === 'Completed' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                >
                  {{ entry.status }}
                </span>
              </TableCell>
              <TableCell>{{ entry.completionTime }}</TableCell>
              <TableCell>{{ entry.averageWaitingTime }} min</TableCell>
              <TableCell>{{ entry.averageServingTime }} min</TableCell>
              <TableCell>{{ entry.averageSatisfactionRating }}</TableCell>
            </TableRow>

            <TableRow v-if="paginatedQueueSummaryRows.length === 0">
              <TableCell colspan="9" class="text-center text-gray-500 py-8">
                No queue summary records found.
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>

        <div class="flex items-center justify-between mt-3">
          <p class="text-sm text-gray-500">
            {{ queueSummaryStartRow }}-{{ queueSummaryEndRow }} of {{ queueSummaryTotalRows }} row(s) shown.
          </p>

          <div class="flex items-center gap-6">
            <p class="text-sm text-gray-600 whitespace-nowrap">
              Page {{ currentQueueSummaryPage }} of {{ queueSummaryTotalPages }}
            </p>

            <Pagination>
              <PaginationContent>
                <PaginationItem>
                  <PaginationFirst @click="firstQueueSummaryPage" :disabled="currentQueueSummaryPage === 1" />
                </PaginationItem>

                <PaginationItem>
                  <PaginationPrevious @click="previousQueueSummaryPage" :disabled="currentQueueSummaryPage === 1" />
                </PaginationItem>

                <PaginationItem>
                  <PaginationNext @click="nextQueueSummaryPage" :disabled="currentQueueSummaryPage === queueSummaryTotalPages" />
                </PaginationItem>

                <PaginationItem>
                  <PaginationLast @click="lastQueueSummaryPage" :disabled="currentQueueSummaryPage === queueSummaryTotalPages" />
                </PaginationItem>
              </PaginationContent>
            </Pagination>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import StatCard from '@/components/common/StatCard.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import {
  Calendar,
  ChevronLeft,
  ChevronRight,
  Users,
  UserCheck,
  XCircle,
  Clock3,
  Hourglass,
  Building2,
} from 'lucide-vue-next'
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover'
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  Pagination,
  PaginationContent,
  PaginationItem,
  PaginationNext,
  PaginationPrevious,
  PaginationFirst,
  PaginationLast,
} from '@/components/ui/pagination'

const selectedOffice = ref('bpso')
const officeOptions = ref([
  { value: 'bpso', label: 'Business Permit and Licensing Office' },
  { value: 'assessor', label: 'City Assessor Office' },
  { value: 'treasury', label: 'City Treasury Office' },
  { value: 'civil-registry', label: 'Civil Registry Office' },
])

const selectedDateRange = ref('daily')
const isDateFilterOpen = ref(false)

const stats = ref({
  totalClients: 0,
  totalServed: 0,
  totalSkipped: 0,
  averageWaitingTime: 0,
  averageServiceTime: 0,
})

const queueSummaryRowsPerPage = 10
const currentQueueSummaryPage = ref(1)

const queueSummaryRows = ref([
  {
    id: 1,
    queueNumber: 'A-001',
    clientName: 'Juan Dela Cruz',
    serviceCode: 'BP-101',
    laneType: 'Regular',
    status: 'Completed',
    completionTime: '08:41 AM',
    averageWaitingTime: 12,
    averageServingTime: 7,
    averageSatisfactionRating: 'Strongly Agree',
  },
  {
    id: 2,
    queueNumber: 'A-002',
    clientName: 'Maria Santos',
    serviceCode: 'HC-203',
    laneType: 'Priority',
    status: 'Completed',
    completionTime: '08:50 AM',
    averageWaitingTime: 9,
    averageServingTime: 6,
    averageSatisfactionRating: 'Agree',
  },
  {
    id: 3,
    queueNumber: 'A-003',
    clientName: 'Pedro Reyes',
    serviceCode: 'TC-118',
    laneType: 'Regular',
    status: 'Skipped',
    completionTime: '08:58 AM',
    averageWaitingTime: 21,
    averageServingTime: 0,
    averageSatisfactionRating: '-',
  },
  {
    id: 4,
    queueNumber: 'A-004',
    clientName: 'Ana Villanueva',
    serviceCode: 'CD-012',
    laneType: 'Regular',
    status: 'Completed',
    completionTime: '09:06 AM',
    averageWaitingTime: 15,
    averageServingTime: 8,
    averageSatisfactionRating: 'Agree',
  },
  {
    id: 5,
    queueNumber: 'A-005',
    clientName: 'Jose Garcia',
    serviceCode: 'BC-087',
    laneType: 'Priority',
    status: 'Completed',
    completionTime: '09:15 AM',
    averageWaitingTime: 11,
    averageServingTime: 5,
    averageSatisfactionRating: 'Strongly Agree',
  },
  {
    id: 6,
    queueNumber: 'A-006',
    clientName: 'Liza Romero',
    serviceCode: 'BR-122',
    laneType: 'Regular',
    status: 'Completed',
    completionTime: '09:23 AM',
    averageWaitingTime: 14,
    averageServingTime: 9,
    averageSatisfactionRating: 'Neither',
  },
  {
    id: 7,
    queueNumber: 'A-007',
    clientName: 'Carlos Medina',
    serviceCode: 'PV-066',
    laneType: 'Regular',
    status: 'Skipped',
    completionTime: '09:31 AM',
    averageWaitingTime: 24,
    averageServingTime: 0,
    averageSatisfactionRating: '-',
  },
  {
    id: 8,
    queueNumber: 'A-008',
    clientName: 'Ramon Flores',
    serviceCode: 'TA-144',
    laneType: 'Priority',
    status: 'Completed',
    completionTime: '09:42 AM',
    averageWaitingTime: 10,
    averageServingTime: 6,
    averageSatisfactionRating: 'Agree',
  },
  {
    id: 9,
    queueNumber: 'A-009',
    clientName: 'Sofia Lim',
    serviceCode: 'DR-031',
    laneType: 'Regular',
    status: 'Completed',
    completionTime: '09:50 AM',
    averageWaitingTime: 13,
    averageServingTime: 7,
    averageSatisfactionRating: 'Strongly Agree',
  },
  {
    id: 10,
    queueNumber: 'A-010',
    clientName: 'Daniel Cruz',
    serviceCode: 'RU-095',
    laneType: 'Regular',
    status: 'Completed',
    completionTime: '09:57 AM',
    averageWaitingTime: 16,
    averageServingTime: 8,
    averageSatisfactionRating: 'Disagree',
  },
  {
    id: 11,
    queueNumber: 'A-011',
    clientName: 'Patricia Ong',
    serviceCode: 'SP-211',
    laneType: 'Priority',
    status: 'Completed',
    completionTime: '10:05 AM',
    averageWaitingTime: 8,
    averageServingTime: 5,
    averageSatisfactionRating: 'Strongly Agree',
  },
  {
    id: 12,
    queueNumber: 'A-012',
    clientName: 'Mark Salazar',
    serviceCode: 'CF-050',
    laneType: 'Regular',
    status: 'Skipped',
    completionTime: '10:12 AM',
    averageWaitingTime: 19,
    averageServingTime: 0,
    averageSatisfactionRating: '-',
  },
])

const queueSummaryTotalRows = computed(() => queueSummaryRows.value.length)

const queueSummaryTotalPages = computed(() => {
  return Math.max(1, Math.ceil(queueSummaryTotalRows.value / queueSummaryRowsPerPage))
})

const paginatedQueueSummaryRows = computed(() => {
  const start = (currentQueueSummaryPage.value - 1) * queueSummaryRowsPerPage
  const end = start + queueSummaryRowsPerPage
  return queueSummaryRows.value.slice(start, end)
})

const queueSummaryStartRow = computed(() => {
  if (queueSummaryTotalRows.value === 0) return 0
  return (currentQueueSummaryPage.value - 1) * queueSummaryRowsPerPage + 1
})

const queueSummaryEndRow = computed(() => {
  if (queueSummaryTotalRows.value === 0) return 0
  return Math.min(currentQueueSummaryPage.value * queueSummaryRowsPerPage, queueSummaryTotalRows.value)
})

const firstQueueSummaryPage = () => {
  currentQueueSummaryPage.value = 1
}

const previousQueueSummaryPage = () => {
  if (currentQueueSummaryPage.value > 1) {
    currentQueueSummaryPage.value -= 1
  }
}

const nextQueueSummaryPage = () => {
  if (currentQueueSummaryPage.value < queueSummaryTotalPages.value) {
    currentQueueSummaryPage.value += 1
  }
}

const lastQueueSummaryPage = () => {
  currentQueueSummaryPage.value = queueSummaryTotalPages.value
}

const clientSatisfactionData = ref([
  { label: 'Strongly Disagree', value: 8 },
  { label: 'Disagree', value: 14 },
  { label: 'Neither', value: 23 },
  { label: 'Agree', value: 51 },
  { label: 'Strongly Agree', value: 67 },
  { label: 'Not Applicable', value: 5 },
])

const laneTypeData = ref([
  { name: 'Regular', value: 92 },
  { name: 'Senior Citizen', value: 31 },
  { name: 'Pregnant', value: 8 },
  { name: 'PWD', value: 17 },
  { name: 'Member of Indigenous People', value: 6 },
])

const hoveredLaneSegment = ref(null)
const laneTooltipPosition = ref({ x: 0, y: 0 })

const laneColorPalette = ['#2563EB', '#16A34A', '#F59E0B', '#DC2626', '#7C3AED']

const maxClientSatisfactionValue = computed(() => {
  const values = clientSatisfactionData.value.map(item => item.value)
  return values.length ? Math.max(...values) : 1
})

const clientSatisfactionTotalResponses = computed(() => {
  return clientSatisfactionData.value.reduce((sum, item) => sum + item.value, 0)
})

const laneTotalClients = computed(() => {
  return laneTypeData.value.reduce((sum, item) => sum + item.value, 0)
})

const laneTypeChartData = computed(() => {
  const total = laneTotalClients.value
  return laneTypeData.value.map(item => ({
    ...item,
    percentage: total === 0 ? 0 : Math.round((item.value / total) * 100),
  }))
})

const lanePieGradient = computed(() => {
  let current = 0
  const slices = laneTypeChartData.value.map((segment, index) => {
    const start = current
    const end = current + segment.percentage
    current = end
    return `${getLaneSegmentColor(index)} ${start}% ${end}%`
  })
  return `conic-gradient(${slices.join(', ')})`
})

const getClientSatisfactionBarColor = (label) => {
  const colors = {
    'Strongly Disagree': '#EF4444',
    Disagree: '#F97316',
    Neither: '#EAB308',
    Agree: '#22C55E',
    'Strongly Agree': '#10B981',
    'Not Applicable': '#6B7280',
  }
  return colors[label] || '#3B82F6'
}

const getClientSatisfactionBarHeight = (value) => {
  if (!maxClientSatisfactionValue.value) return 6
  return Math.max((value / maxClientSatisfactionValue.value) * 100, 6)
}

const getLaneSegmentColor = (index) => {
  return laneColorPalette[index % laneColorPalette.length]
}

const getLaneSegmentByPercent = (percent) => {
  let cumulative = 0

  for (const segment of laneTypeChartData.value) {
    cumulative += segment.percentage
    if (percent <= cumulative) {
      return segment
    }
  }

  return laneTypeChartData.value[laneTypeChartData.value.length - 1] || null
}

const handleLaneDonutMouseMove = (event) => {
  const rect = event.currentTarget.getBoundingClientRect()
  const x = event.clientX - rect.left
  const y = event.clientY - rect.top

  const center = 104
  const dx = x - center
  const dy = y - center
  const distance = Math.sqrt((dx * dx) + (dy * dy))

  const outerRadius = 104
  const innerRadius = 80

  if (distance > outerRadius || distance < innerRadius) {
    hoveredLaneSegment.value = null
    return
  }

  const angle = (Math.atan2(dy, dx) * (180 / Math.PI) + 90 + 360) % 360
  const percent = (angle / 360) * 100

  hoveredLaneSegment.value = getLaneSegmentByPercent(percent)
  laneTooltipPosition.value = { x, y }
}

const clearLaneHoverSegment = () => {
  hoveredLaneSegment.value = null
}

const bodyOriginalOverflow = ref('')
const htmlOriginalOverflow = ref('')

const lockPageScroll = () => {
  if (typeof document === 'undefined') {
    return
  }

  bodyOriginalOverflow.value = document.body.style.overflow
  htmlOriginalOverflow.value = document.documentElement.style.overflow
  document.body.style.overflow = 'hidden'
  document.documentElement.style.overflow = 'hidden'
}

const unlockPageScroll = () => {
  if (typeof document === 'undefined') {
    return
  }

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
</script>