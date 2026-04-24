<template>
  <div class="max-w-7xl mx-auto px-2 sm:px-2 lg:px-2 py-2">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
      <div class="flex items-center gap-1">
        <h1 class="text-2xl font-semibold">Office Efficiency</h1>
        <CsmMetricExplanation
          :title="efficiencyMetricExplanations.officeEfficiency.title"
          :meaning="efficiencyMetricExplanations.officeEfficiency.meaning"
          :computation="efficiencyMetricExplanations.officeEfficiency.computation"
          :formula="efficiencyMetricExplanations.officeEfficiency.formula"
          :interpretation="efficiencyMetricExplanations.officeEfficiency.interpretation"
        />
      </div>

      <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full sm:w-auto">
        <Select v-model="selectedOffice">
          <SelectTrigger class="w-full sm:w-[220px] bg-white cursor-pointer">
            <span class="flex items-center gap-2">
              <Building2 class="h-4 w-4 text-gray-500 shrink-0" />
              <span class="truncate">{{ selectedOfficeAcronym || 'Select Office' }}</span>
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

    <div
      v-if="isLoadingAnalytics"
      class="mb-4 flex items-center gap-2 rounded-md border border-[#A7F3D0] bg-[#ECFDF5] px-4 py-3 text-sm font-medium text-[#0F5C5C]"
    >
      <Loader2 class="h-4 w-4 animate-spin" />
      Loading analytics data...
    </div>

    <div class="mt-2">
      <Card class="w-full">
        <CardContent class="pt-6">
          <div class="h-[320px] w-full">
            <div class="h-full rounded-lg border border-gray-100 bg-gray-50 p-4">
              <div class="h-[230px] grid grid-cols-[40px_1fr] gap-3">
                <div class="relative">
                  <span
                    v-for="gridLabel in [100, 80, 60, 40, 20, 0]"
                    :key="`y-label-${gridLabel}`"
                    class="absolute right-0 -translate-y-1/2 text-[11px] text-gray-500"
                    :style="{ top: `${mapYFromPercentage(gridLabel)}%` }"
                  >
                    {{ gridLabel }}%
                  </span>
                </div>

                <div class="relative">
                  <div
                    v-for="gridLabel in [100, 80, 60, 40, 20, 0]"
                    :key="`y-grid-${gridLabel}`"
                    class="absolute left-0 right-0 border-t border-gray-200"
                    :style="{ top: `${mapYFromPercentage(gridLabel)}%` }"
                  ></div>

                  <svg class="absolute inset-0 h-full w-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <polyline
                      :points="officeEfficiencyLinePoints"
                      fill="none"
                      stroke="#0F5C5C"
                      stroke-width="2"
                      vector-effect="non-scaling-stroke"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    />
                  </svg>

                  <div
                    v-for="(point, index) in officeEfficiencyChartData"
                    :key="`efficiency-point-${point.month}-${index}`"
                    class="absolute"
                    :style="{
                      left: `${getPointX(index)}%`,
                      top: `${mapYFromPercentage(point.percentage)}%`,
                      transform: 'translate(-50%, -50%)',
                    }"
                  >
                    <TooltipProvider>
                      <Tooltip>
                        <TooltipTrigger as-child>
                          <button
                            type="button"
                            class="h-3.5 w-3.5 rounded-full border-2 border-white bg-[#0F5C5C] shadow-sm"
                            :aria-label="`${point.month}: ${formatPercentage(point.percentage)}%`"
                          ></button>
                        </TooltipTrigger>
                        <TooltipContent class="min-w-32 rounded-md border border-gray-200 bg-white px-3 py-2 text-xs shadow-lg">
                          <p class="font-semibold text-gray-900">{{ point.fullMonth }}</p>
                          <p class="text-gray-600">
                            Percentage (All Service Total):
                            <span class="font-semibold">{{ formatPercentage(point.percentage) }}%</span>
                          </p>
                        </TooltipContent>
                      </Tooltip>
                    </TooltipProvider>
                  </div>
                </div>
              </div>

              <div class="mt-2 ml-[43px] relative h-5">
                <span
                  v-for="(point, index) in officeEfficiencyChartData"
                  :key="`month-label-${point.month}`"
                  class="absolute top-0 -translate-x-1/2 text-[11px] font-medium text-gray-600"
                  :style="{ left: `${getPointX(index)}%` }"
                >{{ point.month }}</span>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
      <div>
        <div class="mb-3 flex items-center gap-1">
          <h2 class="text-lg font-semibold">Graph 2</h2>
        </div>
        <Card class="w-full">
          <CardContent class="pt-6">
            <div class="h-[220px] rounded-lg border border-gray-100 bg-gray-50 p-4 flex items-center justify-center">
              <p class="text-sm text-gray-500">Second graph will be added next.</p>
            </div>
          </CardContent>
        </Card>
      </div>

      <div>
        <div class="mb-3 flex items-center gap-1">
          <h2 class="text-lg font-semibold">Graph 3</h2>
        </div>
        <Card class="w-full">
          <CardContent class="pt-6">
            <div class="h-[220px] rounded-lg border border-gray-100 bg-gray-50 p-4 flex items-center justify-center">
              <p class="text-sm text-gray-500">Third graph will be added next.</p>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import api from '@/services/api'
import { Button } from '@/components/ui/button'
import {
  Calendar,
  ChevronLeft,
  ChevronRight,
  Building2,
  Loader2,
} from 'lucide-vue-next'
import { Card, CardContent } from '@/components/ui/card'
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
} from '@/components/ui/select'
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip'
import CsmMetricExplanation from '@/components/CSM/CsmMetricExplanation.vue'

const selectedOffice = ref('')
const officeOptions = ref([])
const selectedDateRange = ref('daily')
const isDateFilterOpen = ref(false)
const isLoadingAnalytics = ref(false)
const isInitializing = ref(true)

const officeEfficiencyMonthlyScores = ref([])

const efficiencyMetricExplanations = {
  officeEfficiency: {
    title: 'Office Efficiency',
    meaning: 'This line graph shows the monthly All Service Total percentage for the selected office within the active year.',
    computation: 'Each monthly value uses the same computation as CSM Overall Score Per Service using service type All (external + internal). The month value corresponds to the Service Total Percentage for that month.',
    formula: 'Monthly All Service Total (%) = ((Agree + Strongly Agree) / (Total SQD Answers - N/A)) * 100, averaged per service by the existing CSM service-total logic.',
    interpretation: [
      'Higher points indicate stronger overall service efficiency in that month.',
      'Changing office updates the monthly line for the currently active year.',
      'Year changes in Daily, Monthly, or Yearly date filters update this graph.',
    ],
  },
}

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
const monthShortNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
const weekDayLabels = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa']

const selectedOfficeAcronym = computed(() => {
  const selected = officeOptions.value.find((office) => office.value === selectedOffice.value)
  return selected?.acronym || ''
})

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

const activeYearForOfficeEfficiency = computed(() => {
  if (selectedDateRange.value === 'yearly') {
    return Number(selectedYear.value)
  }

  if (selectedDateRange.value === 'monthly') {
    return Number(selectedMonthYear.value)
  }

  return Number(dailyViewYear.value)
})

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

const officeEfficiencyChartData = computed(() => {
  return monthShortNames.map((month, index) => {
    const monthValue = officeEfficiencyMonthlyScores.value[index] ?? 0
    return {
      month,
      fullMonth: monthNames[index],
      percentage: Number(monthValue),
    }
  })
})

const getPointX = (index) => {
  return ((index + 0.5) / monthShortNames.length) * 100
}

const mapYFromPercentage = (percentage) => {
  const clamped = Math.max(0, Math.min(100, Number(percentage || 0)))
  const top = 6
  const bottom = 92
  return top + (((100 - clamped) / 100) * (bottom - top))
}

const officeEfficiencyLinePoints = computed(() => {
  return officeEfficiencyChartData.value
    .map((point, index) => `${getPointX(index)},${mapYFromPercentage(point.percentage)}`)
    .join(' ')
})

const formatPercentage = (value) => Number(value || 0).toFixed(2)

const fetchOfficeEfficiencyGraph = async () => {
  if (!selectedOffice.value) {
    officeEfficiencyMonthlyScores.value = Array.from({ length: 12 }, () => 0)
    return
  }

  const targetYear = activeYearForOfficeEfficiency.value
  const officeId = Number(selectedOffice.value)

  const requests = monthShortNames.map((_, monthIndex) => {
    return api.get('/city-mayor/analytics/csm/overall-score-per-service', {
      params: {
        office_id: officeId,
        service_type: 'all',
        period: 'monthly',
        month: monthIndex + 1,
        year: targetYear,
      },
    })
  })

  const responses = await Promise.all(requests)
  officeEfficiencyMonthlyScores.value = responses.map((response) => {
    const payload = response?.data?.data || {}
    return Number(payload.service_total_percentage ?? 0)
  })
}

const fetchSecondGraph = async () => {
  return Promise.resolve()
}

const fetchThirdGraph = async () => {
  return Promise.resolve()
}

const loadAllGraphs = async () => {
  if (!selectedOffice.value) {
    officeEfficiencyMonthlyScores.value = Array.from({ length: 12 }, () => 0)
    return
  }

  isLoadingAnalytics.value = true

  try {
    await Promise.all([
      fetchOfficeEfficiencyGraph(),
      fetchSecondGraph(),
      fetchThirdGraph(),
    ])
  } catch (error) {
    console.error('Error loading office efficiency analytics:', error)
    officeEfficiencyMonthlyScores.value = Array.from({ length: 12 }, () => 0)
  } finally {
    isLoadingAnalytics.value = false
  }
}

const fetchOfficeOptions = async () => {
  const response = await api.get('/city-mayor/user-management/offices')
  const data = response?.data?.data || []

  const extractAcronymFromDisplayName = (displayName) => {
    const match = String(displayName || '').match(/\(([^)]+)\)\s*$/)
    return match?.[1] || ''
  }

  officeOptions.value = data.map((office) => {
    const acronym = office.acronym || extractAcronymFromDisplayName(office.display_name)
    const baseName = office.name || String(office.display_name || '').replace(/\s*\([^)]*\)\s*$/, '')
    const label = acronym ? `${baseName} (${acronym})` : baseName

    return {
      value: String(office.id),
      label,
      acronym,
    }
  })

  if (!selectedOffice.value && officeOptions.value.length > 0) {
    selectedOffice.value = officeOptions.value[0].value
  }
}

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

watch(selectedOffice, () => {
  if (isInitializing.value) return
  loadAllGraphs()
})

watch(activeYearForOfficeEfficiency, () => {
  if (isInitializing.value) return
  loadAllGraphs()
})

onMounted(async () => {
  await fetchOfficeOptions()
  await loadAllGraphs()
  isInitializing.value = false
})
</script>
