<template>
  <div class="max-w-7xl mx-auto px-2 sm:px-2 lg:px-2 py-2">
    <!-- Header with title and controls -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
      <h1 class="text-2xl font-semibold">Client Satisfaction Measurement Analytics</h1>
      
      <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full sm:w-auto">

        <!-- Service Type Dropdown -->
        <Select v-model="selectedServiceType">
          <SelectTrigger class="w-full sm:w-[180px] bg-white cursor-pointer">
            <span class="flex items-center gap-2">
              <Building2 class="h-4 w-4 text-gray-500 shrink-0" />
              <SelectValue placeholder="Service Type" />
            </span>
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="external">External</SelectItem>
            <SelectItem value="internal">Internal</SelectItem>
            <SelectItem value="all">All</SelectItem>
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
    </div>

    <!-- Rest of the dashboard content will go here -->
    <!-- Dashboard Content -->
    <div class="space-y-6">
      <!-- Overview Section -->
      <div>
        <div class="flex items-center justify-between mb-4">
          <!-- Left: Title -->
          <h2 class="text-xl font-semibold">
            Overview 
            <span class="italic text-[#6B7280]">({{ getServiceTypeLabel }})</span>
          </h2>

          <!-- Right: Button -->
          <Button 
            class="h-10 px-4 bg-[#0F5C5C] hover:bg-[#167D7F] text-white flex items-center gap-2"
          >
            <FileText class="h-4 w-4" />
            Generate Report
          </Button>
        </div>

        <!-- 5 Stat Cards Row -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5">
          <StatCard
            class="border-l-4 border-blue-400"
            title="Total Transactions"
            :value="stats.totalTransactions"
            :icon="Users"
            iconBg="bg-[#C2D0F1]"
            iconColor="text-[#2563EB]"
            numberColor="text-[#2563EB]"
          />

          <StatCard
            class="border-l-4 border-red-400"
            title="CC Awareness"
            :value="stats.ccAwareness"
            :icon="Megaphone"
            iconBg="bg-[#F6C5C5]"
            iconColor="text-[#DC2626]"
            numberColor="text-[#DC2626]"
            suffix="%"
          />

          <StatCard
            class="border-l-4 border-orange-300"
            title="CC Visibility"
            :value="stats.ccVisibility"
            :icon="Eye"
            iconBg="bg-[#FFDCC2]"
            iconColor="text-[#F5700B]"
            numberColor="text-[#F5700B]"
            suffix="%"
          />

          <StatCard
            class="border-l-4 border-purple-400"
            title="CC Helpfulness"
            :value="stats.ccHelpfulness"
            :icon="Hand"
            iconBg="bg-[#E8CEF8]"
            iconColor="text-[#9626DC]"
            numberColor="text-[#9626DC]"
            suffix="%"
          />

          <StatCard
            class="border-l-4 border-green-400"
            title="Overall Score"
            :value="stats.overallScore"
            :icon="BarChart3"
            iconBg="bg-[#C1F1D2]"
            iconColor="text-[#16A34A]"
            numberColor="text-[#16A34A]"
            suffix="%"
          />
        </div>
      </div>
      <div class="space-y-6">
        <!-- Citizen's Charter Count Section -->
        <div>
          <div class="mb-4">
            <h2 class="text-xl font-semibold">
              Citizen's Charter Count 
              <span class="italic text-[#6B7280]">({{ getServiceTypeLabel }})</span>
            </h2>
          </div>

          <!-- Main Card containing all CC charts -->
          <Card class="rounded-2xl border border-gray-200 shadow-sm">
            <CardContent class="p-6 lg:p-8">
              <div class="grid grid-cols-1 md:grid-cols-3 md:divide-x md:divide-gray-200">

                <!-- CC1 - Awareness -->
                <div class="space-y-5 md:pr-10 md:pt-0">
                  <div class="flex items-center justify-between gap-3">
                    <span class="text-lg font-bold tracking-tight text-[#3F3F46]">CC1</span>
                    <span class="rounded-full bg-[#F7D4D4] px-4 py-1 text-sm font-semibold text-[#6B4E4E]">Awareness</span>
                  </div>
                  <p class="text-sm leading-tight text-[#4B5563]">
                    Which of the following best describes your awareness of a CC?
                  </p>
                  <div class="space-y-5">
                    <div v-for="(option, index) in ccData.awareness" :key="`awareness-${index}`" class="grid grid-cols-[auto_1fr_auto] items-center gap-3">
                      <span class="text-sm font-semibold text-[#3F3F46]">Option {{ index + 1 }}</span>
                      <TooltipProvider>
                        <Tooltip>
                          <TooltipTrigger as-child>
                            <div class="relative h-4 w-full cursor-help overflow-hidden rounded-full bg-[#D4D4D8]">
                              <div
                                class="absolute left-0 top-0 h-full rounded-full transition-all duration-300"
                                :class="getBarShade('awareness', ccData.awareness, index)"
                                :style="{ width: `${option.percentage}%` }"
                              ></div>
                            </div>
                          </TooltipTrigger>
                          <TooltipContent class="min-w-36 rounded-md border border-gray-200 bg-white px-3 py-2 text-xs shadow-lg">
                            <p class="font-semibold text-gray-900">{{ option.description }}</p>
                            <p class="mt-1 text-gray-600">Responses: <span class="font-semibold">{{ option.count }}</span></p>
                            <p class="text-gray-600">Percentage: <span class="font-semibold">{{ option.percentage }}%</span></p>
                          </TooltipContent>
                        </Tooltip>
                      </TooltipProvider>
                      <span class="text-sm font-bold text-[#52525B]">{{ option.percentage }}%</span>
                    </div>
                  </div>
                </div>

                <!-- CC2 - Visibility -->
                <div class="space-y-5 md:px-10 md:pt-0">
                  <div class="flex items-center justify-between gap-3">
                    <span class="text-lg font-bold tracking-tight text-[#3F3F46]">CC2</span>
                    <span class="rounded-full bg-[#F2D7BC] px-4 py-1 text-sm font-semibold text-[#6A4A2D]">Visibility</span>
                  </div>

                  <p class="text-sm leading-tight text-[#4B5563]">
                    If aware of CC, would you say that the CC of this office was...?
                  </p>

                  <div class="space-y-5">
                    <div 
                      v-for="(option, index) in ccData.visibility" 
                      :key="`visibility-${index}`" 
                      class="grid grid-cols-[auto_1fr_auto] items-center gap-3"
                    >
                      <span class="text-sm font-semibold text-[#3F3F46]">
                        Option {{ index + 1 }}
                      </span>

                      <TooltipProvider>
                        <Tooltip>
                          <TooltipTrigger as-child>
                            <div class="relative h-4 w-full cursor-help overflow-hidden rounded-full bg-[#D4D4D8]">
                              <div
                                class="absolute left-0 top-0 h-full rounded-full transition-all duration-300"
                                :class="getBarShade('visibility', ccData.visibility, index)"
                                :style="{ width: `${option.percentage}%` }"
                              ></div>
                            </div>
                          </TooltipTrigger>

                          <TooltipContent class="min-w-36 rounded-md border border-gray-200 bg-white px-3 py-2 text-xs shadow-lg">
                            <p class="font-semibold text-gray-900">{{ option.description }}</p>
                            <p class="mt-1 text-gray-600">Responses: <span class="font-semibold">{{ option.count }}</span></p>
                            <p class="text-gray-600">Percentage: <span class="font-semibold">{{ option.percentage }}%</span></p>
                          </TooltipContent>
                        </Tooltip>
                      </TooltipProvider>

                      <span class="text-sm font-bold text-[#52525B]">
                        {{ option.percentage }}%
                      </span>
                    </div>
                  </div>
                </div>

                <!-- CC3 - Helpfulness -->
                <div class="space-y-5 md:pl-10 md:pt-0">
                  <div class="flex items-center justify-between gap-3">
                    <span class="text-lg font-bold tracking-tight text-[#3F3F46]">CC3</span>
                    <span class="rounded-full bg-[#DCC9F3] px-4 py-1 text-sm font-semibold text-[#5C417A]">
                      Helpfulness
                    </span>
                  </div>

                  <p class="text-sm leading-tight text-[#4B5563]">
                    If aware of CC, how much did the CC help you in your transactions?
                  </p>

                  <div class="space-y-5">
                    <div 
                      v-for="(option, index) in ccData.helpfulness" 
                      :key="`helpfulness-${index}`" 
                      class="grid grid-cols-[auto_1fr_auto] items-center gap-3"
                    >
                      <span class="text-sm font-semibold text-[#3F3F46]">
                        Option {{ index + 1 }}
                      </span>

                      <TooltipProvider>
                        <Tooltip>
                          <TooltipTrigger as-child>
                            <div class="relative h-4 w-full cursor-help overflow-hidden rounded-full bg-[#D4D4D8]">
                              <div
                                class="absolute left-0 top-0 h-full rounded-full transition-all duration-300"
                                :class="getBarShade('helpfulness', ccData.helpfulness, index)"
                                :style="{ width: `${option.percentage}%` }"
                              ></div>
                            </div>
                          </TooltipTrigger>

                          <TooltipContent class="min-w-36 rounded-md border border-gray-200 bg-white px-3 py-2 text-xs shadow-lg">
                            <p class="font-semibold text-gray-900">{{ option.description }}</p>
                            <p class="mt-1 text-gray-600">Responses: <span class="font-semibold">{{ option.count }}</span></p>
                            <p class="text-gray-600">Percentage: <span class="font-semibold">{{ option.percentage }}%</span></p>
                          </TooltipContent>
                        </Tooltip>
                      </TooltipProvider>

                      <span class="text-sm font-bold text-[#52525B]">
                        {{ option.percentage }}%
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      <!-- SDQ and Demographic Section -->
      <div>
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 items-start">
          <div class="mb-4">
            <h2 class="text-xl font-semibold">
              SDQ Results
              <span class="italic text-[#6B7280]">({{ getServiceTypeLabel }})</span>
            </h2>
          </div>
          <div class="mb-4">
            <h2 class="text-xl font-semibold">
              Demographic Profile
              <span class="italic text-[#6B7280]">({{ getServiceTypeLabel }})</span>
            </h2>
          </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 items-start">
          <div class="space-y-3">
            <SDQResultsCard
              :service-type="selectedServiceType"
              :date-range="selectedDateRange"
            />
          </div>

          <div class="space-y-3">
            <DemographicProfileCard
              :service-type="selectedServiceType"
              :date-range="selectedDateRange"
            />
          </div>
        </div>
      </div>

      <!-- Overall Score Per Service Section -->
      <div>
        <div class="mb-4">
          <h2 class="text-xl font-semibold">
            Overall Score Per Service 
            <span class="italic text-[#6B7280]">({{ getServiceTypeLabel }})</span>
          </h2>
        </div>
        
        <OverallScorePerServiceCard 
          :service-type="selectedServiceType"
          :date-range="selectedDateRange"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import StatCard from '@/components/common/StatCard.vue'
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip'
import { 
  FileText,
  Building2, 
  Calendar, 
  ChevronLeft,
  ChevronRight,
  Users, 
  Megaphone, 
  Eye, 
  Hand, 
  BarChart3 
} from 'lucide-vue-next'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover'
import SDQResultsCard from '@/components/CSM/SDQResultsCard.vue'
import DemographicProfileCard from '@/components/CSM/DemographicProfileCard.vue'
import OverallScorePerServiceCard from '@/components/CSM/OverallScorePerServiceCard.vue'

// State for dropdowns
const selectedServiceType = ref('external')
const selectedDateRange = ref('daily')
const isDateFilterOpen = ref(false)

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

// Date filter state
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

// Computed property for service type label
const getServiceTypeLabel = computed(() => {
  switch(selectedServiceType.value) {
    case 'external':
      return 'External'
    case 'internal':
      return 'Internal'
    case 'all':
      return 'All'
    default:
      return 'External'
  }
})

// Mock data for stats (replace with actual API data later)
const stats = ref({
  totalTransactions: 23455,
  ccAwareness: 82.3,
  ccVisibility: 91.7,
  ccHelpfulness: 88.9,
  overallScore: 85.2
})

// Mock data for CC charts
const ccData = ref({
  awareness: [
    { percentage: 89, count: 445, description: 'I know what a CC is and I saw this offices CC.' },
    { percentage: 67, count: 335, description: 'I know what a CC is but I did NOT see this offices CC.' },
    { percentage: 45, count: 225, description: 'I have learned of the CC only when I saw this offices CC.' },
    { percentage: 23, count: 115, description: 'I do not know what a CC is and I did not see one in this office.' }
  ],
  visibility: [
    { percentage: 78, count: 390, description: 'Easy to see' },
    { percentage: 56, count: 280, description: 'Somewhat easy to see' },
    { percentage: 34, count: 170, description: 'Difficult to see' },
    { percentage: 34, count: 170, description: 'Not visible at all' },
    { percentage: 34, count: 170, description: 'N/A' }
  ],
  helpfulness: [
    { percentage: 82, count: 410, description: 'Helped very much' },
    { percentage: 64, count: 320, description: 'Somewhat helped' },
    { percentage: 41, count: 205, description: 'Did not help' },
    { percentage: 18, count: 90, description: 'N/A' }
  ]
})

const ccShadeMap = {
  awareness: ['bg-[#DC2626]', 'bg-[#E55353]', 'bg-[#EF8080]', 'bg-[#F7B3B3]'],
  visibility: ['bg-[#F5700B]', 'bg-[#F78A36]', 'bg-[#F9A461]', 'bg-[#FBC08D]'],
  helpfulness: ['bg-[#9626DC]', 'bg-[#AB53E3]', 'bg-[#C080EA]', 'bg-[#D9B3F3]']
}

const getBarShade = (ccType, items, index) => {
  const shades = ccShadeMap[ccType] || ccShadeMap.awareness
  const value = items[index]?.percentage ?? 0

  const sortedPercentages = [...items]
    .map((item) => item.percentage ?? 0)
    .sort((a, b) => b - a)

  const rank = sortedPercentages.findIndex((percentage) => percentage === value)
  const shadeIndex = Math.min(Math.max(rank, 0), shades.length - 1)

  return shades[shadeIndex]
}
</script>


