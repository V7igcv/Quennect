<template>
  <div class="max-w-7xl mx-auto px-2 sm:px-2 lg:px-2 py-2">
    <!-- Page Header: Office Efficiency -->
    <div class="mb-6 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
      <div class="flex items-center gap-1">
        <h1 class="text-2xl font-semibold">Office Efficiency</h1>
      </div>

      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
        <Select v-model="selectedOffice">
          <SelectTrigger class="w-full cursor-pointer bg-white sm:w-55">
            <span class="flex items-center gap-2">
              <Building2 class="h-4 w-4 shrink-0 text-gray-500" />
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
            <Button variant="outline" class="w-full justify-start bg-white sm:w-55">
              <span class="flex items-center gap-2">
                <Calendar class="h-4 w-4 shrink-0 text-gray-500" />
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

        <Button
          class="flex items-center gap-2 whitespace-nowrap bg-[#0F5C5C] px-4 py-2 text-white hover:bg-[#0D4A4A]"
          type="button"
          :disabled="!selectedOffice"
          @click="openExportModal"
        >
          <BarChart3 class="h-4 w-4" />
          Generate Graph
        </Button>
      </div>
    </div>

    <div v-if="isLoadingAnalytics" class="mb-4 flex items-center gap-2 rounded-md border border-[#A7F3D0] bg-[#ECFDF5] px-4 py-3 text-sm font-medium text-[#0F5C5C]">
      <Loader2 class="h-4 w-4 animate-spin" />
      Loading analytics data...
    </div>

    <!-- Office Performance Ranking Card -->
    <div class="mt-6">
      <div class="mb-3 flex items-center gap-1">
        <h2 class="text-lg font-semibold text-slate-900">Office Performance Ranking</h2>
        <CsmMetricExplanation
          :title="efficiencyMetricExplanations.officePerformanceRanking.title"
          :meaning="efficiencyMetricExplanations.officePerformanceRanking.meaning"
          :computation="efficiencyMetricExplanations.officePerformanceRanking.computation"
          :formula="efficiencyMetricExplanations.officePerformanceRanking.formula"
          :interpretation="efficiencyMetricExplanations.officePerformanceRanking.interpretation"
        />
      </div>

      <Card class="w-full overflow-hidden border border-slate-200 shadow-sm">
        <CardContent class="pt-6">
          <div class="rounded-2xl border border-slate-100 bg-linear-to-b from-slate-50 to-white p-5">
            <div v-if="officePerformanceRankingData.length === 0" class="flex items-center justify-center rounded-xl border border-dashed border-slate-200 bg-white px-6 py-10 text-sm text-slate-500">
              No ranking data available for the selected date filter.
            </div>

            <div v-else class="grid grid-cols-1 gap-4 lg:grid-cols-2">
              <div
                v-for="office in officePerformanceRankingData"
                :key="`ranking-office-${office.officeId}`"
                class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition-shadow duration-200 hover:shadow-md"
              >
                <div class="flex items-start gap-3">
                  <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#0F5C5C] text-sm font-semibold text-white shadow-sm">
                    {{ office.rank }}
                  </div>

                  <div class="min-w-0 flex-1">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Rank {{ office.rank }}</p>
                    <h3 class="mt-1 text-sm font-semibold leading-snug text-slate-900">
                      {{ office.displayName }}
                    </h3>
                  </div>
                </div>

                <div class="mt-4">
                  <div class="h-3 w-full overflow-hidden rounded-full bg-slate-200">
                    <div
                      class="h-full rounded-full transition-all duration-500"
                      :style="{
                        width: `${clampPercentage(office.percentage)}%`,
                        backgroundColor: office.color,
                      }"
                    ></div>
                  </div>

                  <div class="mt-2 flex items-center justify-between gap-3 text-xs">
                    <span class="text-slate-500">
                      Percentage:
                      <span class="font-semibold text-slate-900">{{ formatPercentage(office.percentage) }}%</span>
                    </span>

                    <span
                      class="rounded-full px-2.5 py-1 text-[11px] font-semibold"
                      :style="{ color: office.color, backgroundColor: `${office.color}14` }"
                    >
                      {{ office.rating }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Export Modal -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="showExportModal" class="fixed inset-0 z-50 flex items-center justify-center">
          <div class="absolute inset-0 bg-black/60" @click="closeExportModal"></div>
          <div class="relative bg-white rounded-lg shadow-2xl w-full max-w-lg p-6 z-10 mx-4">
            <button
              class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 transition-colors cursor-pointer"
              type="button"
              @click="closeExportModal"
            >
              <span class="sr-only">Close</span>
              ×
            </button>

            <h2 class="text-xl font-semibold text-gray-900 mb-3">Export Office Performance Analytics</h2>
            <p class="text-sm text-gray-600 mb-6">
              This will generate an HTML report containing the current Office Performance analytics
              for
              <span class="font-semibold">{{ selectedOfficeDisplayName }}</span>
              for
              <span class="font-semibold">{{ dateFilterLabel }}</span>.
              You can save it as PDF from the report page.
            </p>

            <div class="flex justify-end gap-3">
              <button
                class="px-4 py-2 rounded-sm border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-medium cursor-pointer"
                type="button"
                :disabled="isExportingGraphs"
                @click="closeExportModal"
              >
                Cancel
              </button>
              <Button
                class="px-4 py-2 bg-[#0F5C5C] hover:bg-[#0D4A4A] text-white text-sm font-medium"
                type="button"
                :disabled="isExportingGraphs || !selectedOffice"
                @click="confirmExportGraphs"
              >
                <span v-if="!isExportingGraphs">Generate PDF</span>
                <span v-else>Generating report...</span>
              </Button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Office Performance Graph Section -->
    <div class="mt-6">
      <div class="mb-3 flex items-center gap-1">
        <h2 class="text-lg font-semibold">Office Performance</h2>
        <CsmMetricExplanation
          :title="efficiencyMetricExplanations.officeEfficiency.title"
          :meaning="efficiencyMetricExplanations.officeEfficiency.meaning"
          :computation="efficiencyMetricExplanations.officeEfficiency.computation"
          :formula="efficiencyMetricExplanations.officeEfficiency.formula"
          :interpretation="efficiencyMetricExplanations.officeEfficiency.interpretation"
        />
      </div>
      <Card class="w-full">
        <CardContent class="pt-6">
          <div class="h-80 w-full">
            <div class="h-full rounded-lg border border-gray-100 bg-gray-50 p-4">
              <div class="grid h-57.5 grid-cols-[40px_1fr] gap-3">
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

              <div class="relative mt-2 ml-10.75 h-5">
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

    <!-- Client Service Satisfaction and Assistance Indicator sections -->
    <div class="mt-6 space-y-6">
      <!-- Client Service Satisfaction - Full Width -->
      <div>
        <div class="mb-3 flex items-center gap-1">
          <h2 class="text-lg font-semibold">Client Service Satisfaction</h2>
          <CsmMetricExplanation
            :title="efficiencyMetricExplanations.clientServiceSatisfaction.title"
            :meaning="efficiencyMetricExplanations.clientServiceSatisfaction.meaning"
            :computation="efficiencyMetricExplanations.clientServiceSatisfaction.computation"
            :formula="efficiencyMetricExplanations.clientServiceSatisfaction.formula"
            :interpretation="efficiencyMetricExplanations.clientServiceSatisfaction.interpretation"
          />
        </div>
        <Card class="w-full">
          <CardContent class="pt-6">
            <div class="rounded-lg border border-gray-100 bg-gray-50 p-4 space-y-6">
              <!-- SQD0 - Single Row -->
              <div>
                <div class="mb-3">
                  <h3 class="text-sm font-semibold text-gray-900">SQD0</h3>
                  <p class="text-xs text-gray-500 mt-1">{{ getSqdDescription('SQD0') }}</p>
                </div>
                <div class="h-5 w-full rounded-full bg-gray-200 overflow-hidden">
                  <div
                    class="h-full rounded-full transition-all duration-300"
                    :class="getExperienceBarClass(sqdPercentagesByCode['SQD0'])"
                    :style="{ width: `${clampPercentage(sqdPercentagesByCode['SQD0'])}%` }"
                  ></div>
                </div>
                <div class="mt-2 flex items-center justify-between text-xs">
                  <span class="text-gray-600">Percentage: <span class="font-semibold text-gray-900">{{ formatPercentage(sqdPercentagesByCode['SQD0']) }}%</span></span>
                  <span class="font-semibold" :class="getExperienceTextClass(sqdPercentagesByCode['SQD0'])">
                    {{ getExperienceRating(sqdPercentagesByCode['SQD0']) }}
                  </span>
                </div>
              </div>

              <div class="border-t border-gray-200"></div>

              <!-- SQD1 to SQD8 - 2 Column Grid with Better Alignment -->
              <div class="grid grid-cols-2 gap-6">
                <div v-for="sqdCode in ['SQD1', 'SQD2', 'SQD3', 'SQD4', 'SQD5', 'SQD6', 'SQD7', 'SQD8']" :key="sqdCode" class="flex flex-col">
                  <div class="mb-2">
                    <h3 class="text-sm font-semibold text-gray-900">{{ sqdCode }}</h3>
                    <p class="text-xs text-gray-500 mt-0.5">{{ getSqdDescription(sqdCode) }}</p>
                  </div>
                  <div class="h-4 w-full rounded-full bg-gray-200 overflow-hidden">
                    <div
                      class="h-full rounded-full transition-all duration-300"
                      :class="getExperienceBarClass(sqdPercentagesByCode[sqdCode])"
                      :style="{ width: `${clampPercentage(sqdPercentagesByCode[sqdCode])}%` }"
                    ></div>
                  </div>
                  <div class="mt-1 flex items-center justify-between text-xs">
                    <span class="text-gray-600">{{ formatPercentage(sqdPercentagesByCode[sqdCode]) }}%</span>
                    <span class="font-semibold" :class="getExperienceTextClass(sqdPercentagesByCode[sqdCode])">
                      {{ getExperienceRating(sqdPercentagesByCode[sqdCode]) }}
                    </span>
                  </div>
                </div>
              </div>

              <div class="border-t border-gray-200"></div>

              <!-- Overall Summary -->
              <div>
                <div class="text-sm font-medium text-gray-500">Overall Percentage</div>
                <div class="mt-1 text-3xl font-bold" :class="getExperienceTextClass(overallSqdAveragePercentage)">
                  {{ formatPercentage(overallSqdAveragePercentage) }}%
                </div>
                <p class="mt-2 text-sm text-gray-600">
                  {{ overallSqdExperienceDescription }}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Assistance Indicator - Full Width (Conditional) -->
      <div v-if="officeHasAssistanceServices">
        <div class="mb-3 flex items-center gap-1">
          <h2 class="text-lg font-semibold">Assistance Indicator</h2>
        </div>
        <Card class="w-full">
          <CardHeader class="flex flex-row items-start justify-end space-y-0 pt-4 px-4">
            <Select v-model="selectedBarangayIdForIndicator">
              <SelectTrigger class="w-45 bg-white">
                <SelectValue placeholder="All Barangay" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Barangay</SelectItem>
                <SelectItem
                  v-for="barangay in availableBarangaysForIndicator"
                  :key="`indicator-barangay-${barangay.id}`"
                  :value="barangay.id"
                >
                  {{ barangay.name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </CardHeader>
          <CardContent>
            <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
              <div class="space-y-4">
                <div
                  v-for="item in assistanceIndicatorChartData"
                  :key="`indicator-row-${item.indicator}`"
                  class="grid grid-cols-[24px_1fr_auto] items-center gap-3"
                >
                  <span class="text-sm font-semibold text-gray-700">{{ item.label }}</span>

                  <TooltipProvider>
                    <Tooltip>
                      <TooltipTrigger as-child>
                        <div class="h-6 w-full rounded-md bg-gray-200 overflow-hidden cursor-help">
                          <div
                            class="h-full rounded-md bg-[#0F5C5C] transition-all duration-300"
                            :style="{ width: `${getAssistanceIndicatorBarWidth(item.totalClients)}%` }"
                          ></div>
                        </div>
                      </TooltipTrigger>
                      <TooltipContent class="min-w-32 rounded-md border border-gray-200 bg-white px-3 py-2 text-xs shadow-lg">
                        <p class="font-semibold text-gray-900">Indicator {{ item.label }}</p>
                        <p class="text-gray-600">Number of clients: <span class="font-semibold">{{ item.totalClients }}</span></p>
                      </TooltipContent>
                    </Tooltip>
                  </TooltipProvider>

                  <span class="text-sm font-semibold text-gray-900">{{ item.totalClients }}</span>
                </div>
              </div>

              <div class="mt-4 ml-6.75 border-t border-gray-200 pt-2">
                <div class="flex items-center justify-between text-[11px] text-gray-500">
                  <span>0</span>
                  <span>{{ Math.ceil(maxAssistanceIndicatorCount / 2) }}</span>
                  <span>{{ maxAssistanceIndicatorCount }}</span>
                </div>
                <p class="mt-1 text-[11px] font-medium text-gray-500">X-axis: Number of Clients</p>
              </div>
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
  BarChart3,
} from 'lucide-vue-next'
import { Card, CardContent, CardHeader } from '@/components/ui/card'
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
  SelectValue,
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
const isLoadingPerformanceRanking = ref(false)
const isInitializing = ref(true)
const showExportModal = ref(false)
const isExportingGraphs = ref(false)

const officeEfficiencyMonthlyScores = ref([])
const overallSqdAveragePercentage = ref(0)
const sqdPercentagesByCode = ref({})
const officePerformanceRankingData = ref([])
const selectedBarangayIdForIndicator = ref('all')
const availableBarangaysForIndicator = ref([])
const assistanceIndicatorCounts = ref({ 1: 0, 2: 0 })
const officeHasAssistanceServices = ref(false)
const latestLoadRequestId = ref(0)
const latestRankingRequestId = ref(0)
const latestThirdGraphRequestId = ref(0)

const sqdOptions = ['SQD0', 'SQD1', 'SQD2', 'SQD3', 'SQD4', 'SQD5', 'SQD6', 'SQD7', 'SQD8']
const sqdDescriptions = {
  SQD0: 'I am satisfied with the service that I availed.',
  SQD1: 'I spent a reasonable amount of time for my transaction.',
  SQD2: 'The office followed the transactions requirements and steps based on the information provided',
  SQD3: 'The steps (including payment) I needed to do for my transaction were easy and simple.',
  SQD4: 'I easily found information about my transaction from the office or its website.',
  SQD5: 'I paid a reasonable amount of fees for my transaction.',
  SQD6: 'I feel the office was fair to everyone, or "walang palakasan", during my transaction.',
  SQD7: 'I was treated courteously by the staff, and (if asked for help) the staff was helpful.',
  SQD8: 'I got what I needed from the government office, or (if denied) denial of request was sufficiently explained to me.',
}

const efficiencyMetricExplanations = {
  officeEfficiency: {
    title: 'Office Performance',
    meaning: 'This line graph shows the monthly Overall Service Total percentage for the selected office within the active date filter.',
    computation: 'Each monthly value uses the same computation as CSM Overall Score Per Service with service type All (external + internal). The month value corresponds to the Service Total Percentage for that month.',
    formula: 'Monthly Overall Service Total (%) = the service_total_percentage returned by the CSM Overall Score Per Service graph for service type All.',
    interpretation: [
      'Higher points indicate stronger overall service performance in that month.',
      'Changing office updates the monthly line for the selected date context.',
      'Date filter changes update this graph.',
    ],
  },
  officePerformanceRanking: {
    title: 'Office Performance Ranking',
    meaning: 'This ranking compares all offices by their Overall Service Total percentage for the selected date filter.',
    computation: 'Each office uses the same Overall Score Per Service computation with service type All. Offices are sorted from the highest service_total_percentage to the lowest.',
    formula: 'Ranking (%) = service_total_percentage from the CSM Overall Score Per Service graph for each office.',
    interpretation: [
      'This ranking is not affected by the Office filter.',
      'Only the selected date filter changes the order and values.',
      'The performance label and bar color reflect the percentage band.',
    ],
  },
  clientServiceSatisfaction: {
    title: 'Client Service Satisfaction',
    meaning: 'This section shows satisfaction levels from SQD responses for the selected office and date filter.',
    computation: 'Top section shows the selected SQD percentage using Agree + Strongly Agree among valid responses (excluding N/A) for all services (external + internal). Bottom section shows the average of SQD0 to SQD8 percentages.',
    formula: 'Selected SQD (%) = ((Agree + Strongly Agree) / (Total Responses - N/A)) * 100\nOverall Percentage (%) = (Sum of SQD0 to SQD8 Percentages) / 9',
    interpretation: [
      'Higher values indicate better client-reported service experience.',
      'Rating bands are: Poor, Bad, Fair, Good, and Great.',
      'Top section changes with SQD dropdown; bottom section always reflects all SQDs.',
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

const selectedOfficeDisplayName = computed(() => {
  const selected = officeOptions.value.find((office) => office.value === selectedOffice.value)
  return selected?.label || 'Selected Office'
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

const openExportModal = () => {
  if (!selectedOffice.value) return
  showExportModal.value = true
}

const closeExportModal = () => {
  if (isExportingGraphs.value) return
  showExportModal.value = false
}

const getExportFilterParams = () => {
  const params = {
    office_id: Number(selectedOffice.value),
  }

  if (selectedDateRange.value === 'monthly') {
    params.period = 'monthly'
    params.month = selectedMonthIndex.value + 1
    params.year = Number(selectedMonthYear.value)
    return params
  }

  if (selectedDateRange.value === 'yearly') {
    params.period = 'yearly'
    params.year = Number(selectedYear.value)
    return params
  }

  const y = selectedDate.value.getFullYear()
  const m = String(selectedDate.value.getMonth() + 1).padStart(2, '0')
  const d = String(selectedDate.value.getDate()).padStart(2, '0')

  params.period = 'daily'
  params.date = `${y}-${m}-${d}`

  return params
}

const buildOfficePerformanceRankingParams = () => {
  const params = {}

  if (selectedDateRange.value === 'monthly') {
    params.period = 'monthly'
    params.month = selectedMonthIndex.value + 1
    params.year = Number(selectedMonthYear.value)
    return params
  }

  if (selectedDateRange.value === 'yearly') {
    params.period = 'yearly'
    params.year = Number(selectedYear.value)
    return params
  }

  const y = selectedDate.value.getFullYear()
  const m = String(selectedDate.value.getMonth() + 1).padStart(2, '0')
  const d = String(selectedDate.value.getDate()).padStart(2, '0')

  params.period = 'daily'
  params.date = `${y}-${m}-${d}`

  return params
}

const confirmExportGraphs = async () => {
  if (isExportingGraphs.value || !selectedOffice.value) return

  isExportingGraphs.value = true

  try {
    const response = await api.get('/city-mayor/analytics/office-efficiency/export-graphs', {
      params: getExportFilterParams(),
      responseType: 'blob',
    })

    const blob = new Blob([response.data], { type: 'text/html;charset=utf-8' })
    const url = window.URL.createObjectURL(blob)

    const link = document.createElement('a')
    link.href = url
    link.target = '_blank'
    link.rel = 'noopener noreferrer'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)

    window.setTimeout(() => {
      window.URL.revokeObjectURL(url)
    }, 60000)

    showExportModal.value = false
  } catch (error) {
    console.error('Error exporting office efficiency graphs:', error)
    window.alert('Failed to generate report. Please try again.')
  } finally {
    isExportingGraphs.value = false
  }
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
const clampPercentage = (value) => Math.max(0, Math.min(100, Number(value || 0)))

const getSqdDescription = (sqdCode) => sqdDescriptions[sqdCode] || 'No description available'

const getExperienceRating = (percentage) => {
  const value = Number(percentage || 0)
  if (value >= 81) return 'Great'
  if (value >= 61) return 'Good'
  if (value >= 41) return 'Fair'
  if (value >= 21) return 'Bad'
  return 'Poor'
}

const getExperienceTextClass = (percentage) => {
  const value = Number(percentage || 0)
  if (value >= 81) return 'text-green-600'
  if (value >= 61) return 'text-blue-600'
  if (value >= 41) return 'text-yellow-600'
  if (value >= 21) return 'text-orange-600'
  return 'text-red-600'
}

const getExperienceBarClass = (percentage) => {
  const value = Number(percentage || 0)
  if (value >= 81) return 'bg-green-500'
  if (value >= 61) return 'bg-blue-500'
  if (value >= 41) return 'bg-yellow-500'
  if (value >= 21) return 'bg-orange-500'
  return 'bg-red-500'
}

const overallSqdExperienceDescription = computed(() => {
  const rating = getExperienceRating(overallSqdAveragePercentage.value)
  if (rating === 'Great') return 'Clients generally report a great experience with this office\'s services.'
  if (rating === 'Good') return 'Clients generally report a good experience with this office\'s services.'
  if (rating === 'Fair') return 'Clients generally report a fair experience with this office\'s services.'
  if (rating === 'Bad') return 'Clients generally report a bad experience with this office\'s services.'
  return 'Clients generally report a poor experience with this office\'s services.'
})

const assistanceIndicatorChartData = computed(() => {
  return [
    {
      indicator: 1,
      label: '1',
      totalClients: Number(assistanceIndicatorCounts.value?.[1] ?? 0),
    },
    {
      indicator: 2,
      label: '2',
      totalClients: Number(assistanceIndicatorCounts.value?.[2] ?? 0),
    },
  ]
})

const maxAssistanceIndicatorCount = computed(() => {
  const values = assistanceIndicatorChartData.value.map((item) => item.totalClients)
  const max = values.length ? Math.max(...values) : 0
  return Math.max(max, 1)
})

const getAssistanceIndicatorBarWidth = (value) => {
  const numeric = Number(value || 0)
  if (numeric <= 0) return 0
  return Math.max((numeric / maxAssistanceIndicatorCount.value) * 100, 3)
}

const buildSqdFilterParams = () => {
  const params = {
    office_id: Number(selectedOffice.value),
    service_type: 'all',
  }

  if (selectedDateRange.value === 'monthly') {
    return {
      ...params,
      period: 'monthly',
      month: selectedMonthIndex.value + 1,
      year: Number(selectedMonthYear.value),
    }
  }

  if (selectedDateRange.value === 'yearly') {
    return {
      ...params,
      period: 'yearly',
      year: Number(selectedYear.value),
    }
  }

  const y = selectedDate.value.getFullYear()
  const m = String(selectedDate.value.getMonth() + 1).padStart(2, '0')
  const d = String(selectedDate.value.getDate()).padStart(2, '0')

  return {
    ...params,
    period: 'daily',
    date: `${y}-${m}-${d}`,
  }
}

const sleep = (ms) => new Promise((resolve) => {
  window.setTimeout(resolve, ms)
})

const requestWithRateLimitRetry = async (requestFactory, retries = 2) => {
  let attempt = 0

  while (attempt <= retries) {
    try {
      return await requestFactory()
    } catch (error) {
      const status = error?.response?.status
      const shouldRetry = status === 429 && attempt < retries

      if (!shouldRetry) {
        throw error
      }

      const backoff = 300 * (attempt + 1)
      await sleep(backoff)
      attempt += 1
    }
  }

  return requestFactory()
}

const fetchSqdPercentage = async (sqdCode) => {
  const response = await requestWithRateLimitRetry(() => api.get('/city-mayor/analytics/csm/sqd-results', {
    params: {
      ...buildSqdFilterParams(),
      sqd: sqdCode,
    },
  }))

  const payload = response?.data?.data || {}
  return Number(payload.overall_percentage ?? 0)
}

const mapInChunks = async (items, chunkSize, mapper) => {
  const results = []

  for (let i = 0; i < items.length; i += chunkSize) {
    const chunk = items.slice(i, i + chunkSize)
    const chunkResults = await Promise.all(chunk.map((item, chunkIndex) => mapper(item, i + chunkIndex)))
    results.push(...chunkResults)
  }

  return results
}

const fetchAllSqdPercentages = async () => {
  const values = await mapInChunks(sqdOptions, 2, (code) => fetchSqdPercentage(code))
  return sqdOptions.reduce((accumulator, code, index) => {
    accumulator[code] = Number(values[index] ?? 0)
    return accumulator
  }, {})
}

const fetchOfficeEfficiencyGraphData = async () => {
  if (!selectedOffice.value) {
    return Array.from({ length: 12 }, () => 0)
  }

  const targetYear = activeYearForOfficeEfficiency.value
  const officeId = Number(selectedOffice.value)

  const monthIndexes = monthShortNames.map((_, monthIndex) => monthIndex)
  const responses = await mapInChunks(monthIndexes, 3, (monthIndex) => {
    return requestWithRateLimitRetry(() => api.get('/city-mayor/analytics/csm/overall-score-per-service', {
      params: {
        office_id: officeId,
        service_type: 'all',
        period: 'monthly',
        month: monthIndex + 1,
        year: targetYear,
      },
    }))
  })

  return responses.map((response) => {
    const payload = response?.data?.data || {}
    return Number(payload.service_total_percentage ?? 0)
  })
}

const fetchSecondGraphData = async () => {
  if (!selectedOffice.value) {
    return {
      sqdMap: {},
      overallAverage: 0,
    }
  }

  const sqdMap = await fetchAllSqdPercentages()
  const percentages = Object.values(sqdMap)
  const total = percentages.reduce((sum, value) => sum + Number(value || 0), 0)
  const overallAverage = percentages.length ? Number((total / percentages.length).toFixed(2)) : 0

  return {
    sqdMap,
    overallAverage,
  }
}

const fetchThirdGraphData = async () => {
  if (!selectedOffice.value) {
    return {
      counts: { 1: 0, 2: 0 },
      barangays: [],
      selectedStillAvailable: true,
    }
  }

  const params = {
    office_id: Number(selectedOffice.value),
  }

  if (selectedDateRange.value === 'monthly') {
    params.period = 'monthly'
    params.month = selectedMonthIndex.value + 1
    params.year = Number(selectedMonthYear.value)
  } else if (selectedDateRange.value === 'yearly') {
    params.period = 'yearly'
    params.year = Number(selectedYear.value)
  } else {
    params.period = 'daily'
    const y = selectedDate.value.getFullYear()
    const m = String(selectedDate.value.getMonth() + 1).padStart(2, '0')
    const d = String(selectedDate.value.getDate()).padStart(2, '0')
    params.date = `${y}-${m}-${d}`
  }

  if (selectedBarangayIdForIndicator.value !== 'all') {
    params.barangay_id = Number(selectedBarangayIdForIndicator.value)
  }

  const response = await requestWithRateLimitRetry(() => api.get('/city-mayor/analytics/assistance-indicator-graph', { params }))
  const payload = response?.data?.data || {}
  const distribution = Array.isArray(payload.distribution) ? payload.distribution : []

  const indicator1 = distribution.find((item) => Number(item.indicator) === 1)
  const indicator2 = distribution.find((item) => Number(item.indicator) === 2)

  const counts = {
    1: Number(indicator1?.total_clients ?? 0),
    2: Number(indicator2?.total_clients ?? 0),
  }

  const barangays = Array.isArray(payload.available_barangays)
    ? payload.available_barangays.map((item) => ({
        id: String(item.barangay_id),
        name: item.barangay_name,
      }))
    : []

  const selectedStillAvailable = selectedBarangayIdForIndicator.value === 'all'
    || barangays.some((item) => item.id === selectedBarangayIdForIndicator.value)

  return {
    counts,
    barangays,
    selectedStillAvailable,
  }
}

const buildOfficeEfficiencyDashboardParams = () => {
  const params = {
    office_id: Number(selectedOffice.value),
  }

  if (selectedDateRange.value === 'monthly') {
    params.period = 'monthly'
    params.month = selectedMonthIndex.value + 1
    params.year = Number(selectedMonthYear.value)
  } else if (selectedDateRange.value === 'yearly') {
    params.period = 'yearly'
    params.year = Number(selectedYear.value)
  } else {
    params.period = 'daily'
    const y = selectedDate.value.getFullYear()
    const m = String(selectedDate.value.getMonth() + 1).padStart(2, '0')
    const d = String(selectedDate.value.getDate()).padStart(2, '0')
    params.date = `${y}-${m}-${d}`
  }

  if (selectedBarangayIdForIndicator.value !== 'all') {
    params.barangay_id = Number(selectedBarangayIdForIndicator.value)
  }

  return params
}

const fetchOfficeEfficiencyDashboardData = async () => {
  if (!selectedOffice.value) {
    return {
      monthlyScores: Array.from({ length: 12 }, () => 0),
      sqdMap: {},
      overallAverage: 0,
      counts: { 1: 0, 2: 0 },
      barangays: [],
      selectedStillAvailable: true,
      hasAssistanceServices: false,
    }
  }

  const response = await api.get('/city-mayor/analytics/office-efficiency/dashboard-data', {
    params: buildOfficeEfficiencyDashboardParams(),
  })

  const payload = response?.data?.data || {}
  const assistancePayload = payload.assistance_indicator || {}
  const rawCounts = assistancePayload.counts || {}

  return {
    monthlyScores: Array.isArray(payload.monthly_scores)
      ? payload.monthly_scores.map((value) => Number(value ?? 0))
      : Array.from({ length: 12 }, () => 0),
    sqdMap: payload.sqd_percentages || {},
    overallAverage: Number(payload.overall_sqd_average ?? 0),
    counts: {
      1: Number(rawCounts[1] ?? rawCounts['1'] ?? 0),
      2: Number(rawCounts[2] ?? rawCounts['2'] ?? 0),
    },
    barangays: Array.isArray(assistancePayload.available_barangays)
      ? assistancePayload.available_barangays.map((item) => ({
          id: String(item.barangay_id),
          name: item.barangay_name,
        }))
      : [],
    selectedStillAvailable: assistancePayload.selected_still_available !== false,
    hasAssistanceServices: Boolean(payload.has_assistance_services),
  }
}

const fetchOfficePerformanceRankingData = async () => {
  const response = await api.get('/city-mayor/analytics/office-efficiency/performance-ranking', {
    params: buildOfficePerformanceRankingParams(),
  })

  const payload = response?.data?.data || {}
  const rankingRows = Array.isArray(payload.ranking) ? payload.ranking : []

  return rankingRows.map((item) => ({
    rank: Number(item.rank ?? 0),
    officeId: Number(item.office_id ?? 0),
    officeName: String(item.office_name ?? ''),
    officeAcronym: String(item.office_acronym ?? ''),
    displayName: String(item.display_name ?? item.office_name ?? ''),
    percentage: Number(item.percentage ?? 0),
    rating: String(item.rating ?? 'Poor'),
    color: String(item.color ?? '#EF4444'),
  }))
}

const loadOfficePerformanceRanking = async () => {
  const requestId = ++latestRankingRequestId.value

  isLoadingPerformanceRanking.value = true

  try {
    const rankingData = await fetchOfficePerformanceRankingData()

    if (requestId !== latestRankingRequestId.value) {
      return
    }

    officePerformanceRankingData.value = rankingData
  } catch (error) {
    console.error('Error loading office performance ranking:', error)
    if (requestId !== latestRankingRequestId.value) {
      return
    }
    officePerformanceRankingData.value = []
  } finally {
    if (requestId === latestRankingRequestId.value) {
      isLoadingPerformanceRanking.value = false
    }
  }
}

const loadThirdGraphOnly = async () => {
  const requestId = ++latestThirdGraphRequestId.value

  try {
    const thirdData = await fetchThirdGraphData()

    if (requestId !== latestThirdGraphRequestId.value) {
      return
    }

    assistanceIndicatorCounts.value = thirdData.counts
    availableBarangaysForIndicator.value = thirdData.barangays

    if (!thirdData.selectedStillAvailable) {
      selectedBarangayIdForIndicator.value = 'all'
    }
  } catch (error) {
    console.error('Error loading assistance indicator graph:', error)
    if (requestId !== latestThirdGraphRequestId.value) {
      return
    }
    assistanceIndicatorCounts.value = { 1: 0, 2: 0 }
    availableBarangaysForIndicator.value = []
  }
}


const loadAllGraphs = async () => {
  const requestId = ++latestLoadRequestId.value
  latestThirdGraphRequestId.value += 1

  if (!selectedOffice.value) {
    officeEfficiencyMonthlyScores.value = Array.from({ length: 12 }, () => 0)
    sqdPercentagesByCode.value = {}
    overallSqdAveragePercentage.value = 0
    selectedBarangayIdForIndicator.value = 'all'
    availableBarangaysForIndicator.value = []
    assistanceIndicatorCounts.value = { 1: 0, 2: 0 }
    officeHasAssistanceServices.value = false
    return
  }

  isLoadingAnalytics.value = true

  try {
    const dashboardData = await fetchOfficeEfficiencyDashboardData()

    if (requestId !== latestLoadRequestId.value) {
      return
    }

    officeEfficiencyMonthlyScores.value = dashboardData.monthlyScores
    sqdPercentagesByCode.value = dashboardData.sqdMap
    overallSqdAveragePercentage.value = dashboardData.overallAverage
    assistanceIndicatorCounts.value = dashboardData.counts
    availableBarangaysForIndicator.value = dashboardData.barangays
    officeHasAssistanceServices.value = dashboardData.hasAssistanceServices

    if (!dashboardData.selectedStillAvailable) {
      selectedBarangayIdForIndicator.value = 'all'
    }
  } catch (error) {
    console.error('Error loading office efficiency analytics:', error)
    if (requestId !== latestLoadRequestId.value) {
      return
    }
    officeEfficiencyMonthlyScores.value = Array.from({ length: 12 }, () => 0)
    sqdPercentagesByCode.value = {}
    overallSqdAveragePercentage.value = 0
    assistanceIndicatorCounts.value = { 1: 0, 2: 0 }
    availableBarangaysForIndicator.value = []
    officeHasAssistanceServices.value = false
  } finally {
    if (requestId === latestLoadRequestId.value) {
      isLoadingAnalytics.value = false
    }
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

watch(selectedDateRange, () => {
  if (isInitializing.value) return
  loadAllGraphs()
  loadOfficePerformanceRanking()
})

watch(selectedDate, () => {
  if (isInitializing.value) return
  if (selectedDateRange.value !== 'daily') return
  loadAllGraphs()
  loadOfficePerformanceRanking()
})

watch([selectedMonthIndex, selectedMonthYear], () => {
  if (isInitializing.value) return
  if (selectedDateRange.value !== 'monthly') return
  loadAllGraphs()
  loadOfficePerformanceRanking()
})

watch(selectedYear, () => {
  if (isInitializing.value) return
  if (selectedDateRange.value !== 'yearly') return
  loadAllGraphs()
  loadOfficePerformanceRanking()
})

watch(selectedBarangayIdForIndicator, () => {
  if (isInitializing.value) return
  loadThirdGraphOnly()
})

onMounted(async () => {
  await fetchOfficeOptions()
  await Promise.all([
    loadAllGraphs(),
    loadOfficePerformanceRanking(),
  ])
  isInitializing.value = false
})
</script>
