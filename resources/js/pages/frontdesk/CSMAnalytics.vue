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

        <!-- Date Dropdown -->
        <Select v-model="selectedDateRange">
          <SelectTrigger class="w-full sm:w-[180px] bg-white cursor-pointer">
            <span class="flex items-center gap-2">
              <Calendar class="h-4 w-4 text-gray-500 shrink-0" />
              <SelectValue placeholder="Date Range" />
            </span>
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="daily">Daily</SelectItem>
            <SelectItem value="monthly">Monthly</SelectItem>
            <SelectItem value="yearly">Yearly</SelectItem>
          </SelectContent>
        </Select>
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
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
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
import SDQResultsCard from '@/components/CSM/SDQResultsCard.vue'
import DemographicProfileCard from '@/components/CSM/DemographicProfileCard.vue'

// State for dropdowns
const selectedServiceType = ref('external')
const selectedDateRange = ref('monthly')

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


