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

    <div
      v-if="isLoadingAnalytics"
      class="mb-4 flex items-center gap-2 rounded-md border border-[#A7F3D0] bg-[#ECFDF5] px-4 py-3 text-sm font-medium text-[#0F5C5C]"
    >
      <Loader2 class="h-4 w-4 animate-spin" />
      Loading analytics data...
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
            @click="openGenerateTableModal"
          >
            <FileText class="h-4 w-4" />
            Generate Table
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
              <div class="grid grid-cols-1 gap-8 md:grid-cols-3 md:gap-0 md:divide-x md:divide-gray-200">

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

      <!-- SQD and Demographic Section -->
      <div>
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 items-start">
          <div class="mb-4">
            <h2 class="text-xl font-semibold">
              SQD Results
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
            <SQDResultsCard
              :service-type="selectedServiceType"
              :date-range="selectedDateRange"
              :filter-params="apiFilterParams"
            />
          </div>

          <div class="space-y-3">
            <DemographicProfileCard
              :service-type="selectedServiceType"
              :date-range="selectedDateRange"
              :filter-params="apiFilterParams"
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
          :filter-params="apiFilterParams"
        />
      </div>
    </div>

    <!-- Generate Table Modal -->
    <div v-if="showGenerateTableModal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-lg p-6 max-w-3xl w-full mx-4 max-h-[85vh] overflow-y-auto">
        <div class="flex justify-end mb-2">
          <button
            type="button"
            @click="closeGenerateTableModal"
            class="text-gray-400 hover:text-gray-600 transition-colors"
            aria-label="Close generate table modal"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <div class="mb-4">
          <h3 class="text-lg font-semibold mb-2">Generate Table</h3>
          <p class="text-gray-600 text-sm leading-relaxed">
            Select the data dimensions you wish to download as tables. The system will compile a structured data table based on your selection and will be exported as an excel file.
          </p>
        </div>

        <div class="mb-4 rounded-md border border-[#BFDBFE] bg-[#EFF6FF] px-4 py-3 text-sm text-[#1E3A8A]">
          Selected tables: <span class="font-semibold">{{ selectedTableKeys.length }}</span>
        </div>

        <div class="space-y-4 mb-6">
          <div
            v-for="group in generateTableGroups"
            :key="group.key"
            class="rounded-xl border border-gray-200 bg-white p-4"
          >
            <div class="flex items-center justify-between gap-3 mb-3">
              <h4 class="text-sm font-semibold text-[#1F2937]">{{ group.title }}</h4>
              <div class="flex items-center gap-2">
                <button
                  type="button"
                  class="text-xs font-medium text-[#0F5C5C] hover:text-[#167D7F]"
                  @click="selectAllInGroup(group.options)"
                >
                  Select All
                </button>
                <span class="text-gray-300">|</span>
                <button
                  type="button"
                  class="text-xs font-medium text-[#6B7280] hover:text-[#374151]"
                  @click="clearAllInGroup(group.options)"
                >
                  Clear All
                </button>
                <span class="text-xs text-[#6B7280] ml-2">{{ selectedCountByGroup(group.options) }} selected</span>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
              <label
                v-for="option in group.options"
                :key="option.key"
                class="flex items-center gap-3 rounded-md border border-gray-200 bg-[#F9FAFB] px-3 py-2 cursor-pointer hover:bg-gray-100 transition"
              >
                <input
                  type="checkbox"
                  class="h-4 w-4 rounded border-gray-300 text-[#0F5C5C] focus:ring-[#0F5C5C]"
                  :checked="isTableSelected(option.key)"
                  @change="toggleTableSelection(option.key)"
                >
                <span class="text-sm text-[#374151]">{{ option.label }}</span>
              </label>
            </div>
          </div>
        </div>

        <div class="flex gap-2 justify-end">
          <button
            @click="closeGenerateTableModal"
            class="px-4 py-2 border rounded-md hover:bg-gray-100"
          >
            Cancel
          </button>
          <button
            @click="handleGenerateTable"
            class="px-4 py-2 bg-[#0F5C5C] text-white rounded-md hover:bg-[#167D7F] disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="selectedTableKeys.length === 0 || isGeneratingTable"
          >
            {{ isGeneratingTable ? 'Generating...' : 'Generate Table' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import api from '@/services/api'
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
  BarChart3,
  Loader2,
  X,
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
import SQDResultsCard from '@/components/CSM/SQDResultsCard.vue'
import DemographicProfileCard from '@/components/CSM/DemographicProfileCard.vue'
import OverallScorePerServiceCard from '@/components/CSM/OverallScorePerServiceCard.vue'

const selectedServiceType = ref('external')
const selectedDateRange = ref('daily')
const isDateFilterOpen = ref(false)
const isLoadingAnalytics = ref(false)
const isApplyingDateFilter = ref(false)
const showGenerateTableModal = ref(false)
const isGeneratingTable = ref(false)

const generateTableGroups = [
  {
    key: 'general-reporting',
    title: 'General Reporting',
    options: [
      { key: 'overview', label: 'Overview' },
      { key: 'surveyed-services', label: 'Surveyed Services' },
      { key: 'citizens-charter-count', label: "Citizen's Charter Count" },
      { key: 'overall-score-per-service', label: 'Overall Score Per Service' },
    ],
  },
  {
    key: 'demographics',
    title: 'Demographics',
    options: [
      { key: 'age', label: 'Age' },
      { key: 'sex', label: 'Sex' },
      { key: 'customer-type', label: 'Customer Type' },
    ],
  },
  {
    key: 'sqd-results',
    title: 'SQD Results',
    options: [
      { key: 'external-sqd-results', label: 'External SQD Results' },
      { key: 'internal-sqd-results', label: 'Internal SQD Results' },
    ],
  },
]

const selectedTableKeys = ref([
  'overview',
])

const currentlySupportedExportTables = [
  'overview',
  'surveyed-services',
  'citizens-charter-count',
  'overall-score-per-service',
  'age',
  'sex',
  'customer-type',
  'external-sqd-results',
  'internal-sqd-results',
]

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

const getServiceTypeLabel = computed(() => {
  switch (selectedServiceType.value) {
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

const stats = ref({
  totalTransactions: 0,
  ccAwareness: 0,
  ccVisibility: 0,
  ccHelpfulness: 0,
  overallScore: 0,
})

const getDefaultCcData = () => ({
  awareness: [
    { option: 1, label: 'Option 1', description: 'Option 1', count: 0, percentage: 0 },
    { option: 2, label: 'Option 2', description: 'Option 2', count: 0, percentage: 0 },
    { option: 3, label: 'Option 3', description: 'Option 3', count: 0, percentage: 0 },
    { option: 4, label: 'Option 4', description: 'Option 4', count: 0, percentage: 0 },
  ],
  visibility: [
    { option: 1, label: 'Option 1', description: 'Option 1', count: 0, percentage: 0 },
    { option: 2, label: 'Option 2', description: 'Option 2', count: 0, percentage: 0 },
    { option: 3, label: 'Option 3', description: 'Option 3', count: 0, percentage: 0 },
    { option: 4, label: 'Option 4', description: 'Option 4', count: 0, percentage: 0 },
    { option: 5, label: 'Option 5', description: 'Option 5', count: 0, percentage: 0 },
  ],
  helpfulness: [
    { option: 1, label: 'Option 1', description: 'Option 1', count: 0, percentage: 0 },
    { option: 2, label: 'Option 2', description: 'Option 2', count: 0, percentage: 0 },
    { option: 3, label: 'Option 3', description: 'Option 3', count: 0, percentage: 0 },
    { option: 4, label: 'Option 4', description: 'Option 4', count: 0, percentage: 0 },
  ],
})

const ccData = ref(getDefaultCcData())

const ccShadeMap = {
  awareness: ['bg-[#DC2626]', 'bg-[#E55353]', 'bg-[#EF8080]', 'bg-[#F7B3B3]'],
  visibility: ['bg-[#F5700B]', 'bg-[#F78A36]', 'bg-[#F9A461]', 'bg-[#FBC08D]', 'bg-[#FDE2CE]'],
  helpfulness: ['bg-[#9626DC]', 'bg-[#AB53E3]', 'bg-[#C080EA]', 'bg-[#D9B3F3]'],
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

const formatDate = (date) => {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const apiFilterParams = computed(() => {
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
      year: Number(selectedYear.value),
    }
  }

  return {
    period: 'daily',
    date: formatDate(selectedDate.value),
  }
})

const buildCsmParams = () => ({
  ...apiFilterParams.value,
  service_type: selectedServiceType.value,
})

const fetchOverviewStats = async () => {
  const response = await api.get('/frontdesk/analytics/csm/overview', {
    params: buildCsmParams(),
  })

  const payload = response?.data?.data || {}
  stats.value = {
    totalTransactions: payload.total_transactions ?? 0,
    ccAwareness: payload.cc_awareness ?? 0,
    ccVisibility: payload.cc_visibility ?? 0,
    ccHelpfulness: payload.cc_helpfulness ?? 0,
    overallScore: payload.overall_score ?? 0,
  }
}

const fetchCitizenCharter = async () => {
  const response = await api.get('/frontdesk/analytics/csm/citizen-charter', {
    params: buildCsmParams(),
  })

  const payload = response?.data?.data
  ccData.value = payload
    ? {
        awareness: payload.awareness || getDefaultCcData().awareness,
        visibility: payload.visibility || getDefaultCcData().visibility,
        helpfulness: payload.helpfulness || getDefaultCcData().helpfulness,
      }
    : getDefaultCcData()
}

const fetchCsmAnalytics = async () => {
  isLoadingAnalytics.value = true

  try {
    await Promise.all([
      fetchOverviewStats(),
      fetchCitizenCharter(),
    ])
  } catch (error) {
    console.error('Error fetching CSM analytics:', error)
    stats.value = {
      totalTransactions: 0,
      ccAwareness: 0,
      ccVisibility: 0,
      ccHelpfulness: 0,
      overallScore: 0,
    }
    ccData.value = getDefaultCcData()
  } finally {
    isLoadingAnalytics.value = false
  }
}

const openGenerateTableModal = () => {
  showGenerateTableModal.value = true
}

const closeGenerateTableModal = () => {
  showGenerateTableModal.value = false
}

const isTableSelected = (tableKey) => {
  return selectedTableKeys.value.includes(tableKey)
}

const toggleTableSelection = (tableKey) => {
  if (isTableSelected(tableKey)) {
    selectedTableKeys.value = selectedTableKeys.value.filter((item) => item !== tableKey)
    return
  }

  selectedTableKeys.value = [...selectedTableKeys.value, tableKey]
}

const selectedCountByGroup = (options) => {
  return options.filter((option) => isTableSelected(option.key)).length
}

const selectAllInGroup = (options) => {
  const optionKeys = options.map((option) => option.key)
  const mergedKeys = [...selectedTableKeys.value]

  optionKeys.forEach((key) => {
    if (!mergedKeys.includes(key)) {
      mergedKeys.push(key)
    }
  })

  selectedTableKeys.value = mergedKeys
}

const clearAllInGroup = (options) => {
  const optionKeys = options.map((option) => option.key)
  selectedTableKeys.value = selectedTableKeys.value.filter((key) => !optionKeys.includes(key))
}

const buildFallbackExportFileName = () => {
  let dateLabel = selectedYear.value

  if (selectedDateRange.value === 'daily') {
    dateLabel = selectedDate.value.toLocaleDateString('en-US', {
      month: 'long',
      day: 'numeric',
      year: 'numeric',
    })
  } else if (selectedDateRange.value === 'monthly') {
    dateLabel = `${monthNames[selectedMonthIndex.value]} ${selectedMonthYear.value}`
  }

  return `Client Satisfaction Measurement (CSM) Report - ${dateLabel}.xlsx`
}

const parseContentDispositionFileName = (contentDisposition) => {
  if (!contentDisposition) {
    return null
  }

  const utf8Match = contentDisposition.match(/filename\*=UTF-8''([^;]+)/i)
  if (utf8Match && utf8Match[1]) {
    return decodeURIComponent(utf8Match[1])
  }

  const simpleMatch = contentDisposition.match(/filename="?([^";]+)"?/i)
  if (simpleMatch && simpleMatch[1]) {
    return simpleMatch[1]
  }

  return null
}

const triggerBlobDownload = (blobData, fileName) => {
  const blob = new Blob([blobData], {
    type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
  })
  const downloadUrl = window.URL.createObjectURL(blob)
  const anchor = document.createElement('a')

  anchor.href = downloadUrl
  anchor.download = fileName
  document.body.appendChild(anchor)
  anchor.click()
  anchor.remove()

  window.URL.revokeObjectURL(downloadUrl)
}

const handleGenerateTable = async () => {
  if (selectedTableKeys.value.length === 0 || isGeneratingTable.value) {
    return
  }

  const unsupportedSelections = selectedTableKeys.value.filter(
    (tableKey) => !currentlySupportedExportTables.includes(tableKey)
  )

  if (unsupportedSelections.length > 0) {
    window.alert("For now, only Overview, Surveyed Services, Citizen's Charter Count, Overall Score Per Service, Demographic Profile, and SQD Results can be exported.")
    return
  }

  isGeneratingTable.value = true

  try {
    const response = await api.post(
      '/frontdesk/analytics/csm/export',
      {
        tables: selectedTableKeys.value,
        ...buildCsmParams(),
      },
      {
        responseType: 'blob',
      }
    )

    const contentDisposition = response?.headers?.['content-disposition']
    const fileName = parseContentDispositionFileName(contentDisposition) || buildFallbackExportFileName()

    triggerBlobDownload(response.data, fileName)
    closeGenerateTableModal()
  } catch (error) {
    console.error('Error exporting CSM analytics table:', error)
    window.alert('Unable to generate table export right now. Please try again.')
  } finally {
    isGeneratingTable.value = false
  }
}

const applyDateFilterAndReload = async () => {
  isApplyingDateFilter.value = true
  await fetchCsmAnalytics()
  isApplyingDateFilter.value = false
}

watch(selectedServiceType, () => {
  fetchCsmAnalytics()
})

watch(selectedDateRange, () => {
  applyDateFilterAndReload()
})

watch(selectedDate, () => {
  if (selectedDateRange.value !== 'daily') return
  applyDateFilterAndReload()
})

watch([selectedMonthIndex, selectedMonthYear], () => {
  if (selectedDateRange.value !== 'monthly') return
  applyDateFilterAndReload()
})

watch(selectedYear, () => {
  if (selectedDateRange.value !== 'yearly') return
  applyDateFilterAndReload()
})

onMounted(() => {
  fetchCsmAnalytics()
})
</script>


