<template>
  <Card class="w-full">
    <CardHeader class="flex flex-row items-start justify-between space-y-0 pb-2">
      <div>
        <CardTitle class="text-lg font-semibold">{{ selectedSQD }}</CardTitle>
        <CardDescription class="text-sm text-gray-500 mt-1">
          {{ getSQDDescription(selectedSQD) }}
        </CardDescription>
      </div>
      <Select v-model="selectedSQD">
        <SelectTrigger class="w-[180px]">
          <SelectValue placeholder="Select SQD" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem v-for="sqd in sqdOptions" :key="sqd" :value="sqd">
            {{ sqd }}
          </SelectItem>
        </SelectContent>
      </Select>
    </CardHeader>

    <CardContent>
      <!-- Bar Chart -->
      <div class="h-[300px] w-full mt-4">
        <div class="h-full rounded-lg border border-gray-100 bg-gray-50 p-4">
          <div class="flex h-full items-end gap-3 border-b border-gray-200 pb-3">
            <div
              v-for="(entry, index) in chartData"
              :key="'bar-' + index"
              class="flex min-w-0 flex-1 flex-col items-center"
            >
              <TooltipProvider>
                <Tooltip>
                  <TooltipTrigger as-child>
                    <div class="flex h-48 w-full max-w-12 items-end cursor-help">
                      <div
                        class="w-full rounded-t-md transition-all duration-300"
                        :style="{
                          height: `${getBarHeight(entry.value)}%`,
                          backgroundColor: getBarColor(entry.criteria)
                        }"
                      ></div>
                    </div>
                  </TooltipTrigger>
                  <TooltipContent class="min-w-36 rounded-md border border-gray-200 bg-white px-3 py-2 text-xs shadow-lg">
                    <p class="font-semibold text-gray-900">{{ entry.criteria }}</p>
                    <p class="mt-1 text-gray-600">Responses: <span class="font-semibold">{{ entry.value }}</span></p>
                  </TooltipContent>
                </Tooltip>
              </TooltipProvider>
              <span class="mt-2 h-10 px-1 text-[11px] font-medium leading-tight text-center text-gray-600 flex items-start justify-center">
                {{ entry.criteria }}
              </span>
              <span class="mt-1 text-xs font-semibold text-gray-900">{{ entry.value }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer with Total Responses and Overall Percentage -->
      <div class="flex items-center justify-between mt-6 pt-4 border-t border-gray-100">
        <div class="flex items-center gap-2">
          <span class="text-sm font-medium text-gray-500">Total Responses:</span>
          <span class="text-lg font-semibold text-gray-900">{{ totalResponses }}</span>
        </div>
        <div class="flex items-center gap-2">
          <span class="text-sm font-medium text-gray-500">Overall Percentage:</span>
          <span class="text-lg font-semibold" :class="getPercentageColorClass(overallPercentage)">
            {{ overallPercentage }}%
          </span>
        </div>
      </div>
    </CardContent>
  </Card>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import api from '@/services/api'
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card'
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

const props = defineProps({
  apiBasePath: {
    type: String,
    default: '/frontdesk/analytics/csm',
  },
  serviceType: {
    type: String,
    default: 'external',
  },
  dateRange: {
    type: String,
    default: 'monthly',
  },
  filterParams: {
    type: Object,
    default: () => ({}),
  },
})

const selectedSQD = ref('SQD0')
const sqdDescription = ref('')
const chartData = ref([])
const totalResponses = ref(0)
const overallPercentage = ref(0)

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

const defaultDistribution = () => ([
  { criteria: 'Strongly Disagree', value: 0 },
  { criteria: 'Disagree', value: 0 },
  { criteria: 'Neither Agree nor Disagree', value: 0 },
  { criteria: 'Agree', value: 0 },
  { criteria: 'Strongly Agree', value: 0 },
  { criteria: 'Not Applicable', value: 0 },
])

const maxChartValue = computed(() => {
  const values = chartData.value.map((item) => item.value)
  return values.length ? Math.max(...values) : 1
})

const getBarHeight = (value) => {
  if (!maxChartValue.value) return 6
  return Math.max((value / maxChartValue.value) * 100, 6)
}

const getSQDDescription = (sqd) => {
  if (sqdDescription.value) {
    return sqdDescription.value
  }

  return sqdDescriptions[sqd] || 'No description available'
}

const getBarColor = (criteria) => {
  const colors = {
    'Strongly Disagree': '#EF4444',
    Disagree: '#F97316',
    'Neither Agree nor Disagree': '#EAB308',
    Agree: '#22C55E',
    'Strongly Agree': '#10B981',
    'Not Applicable': '#6B7280',
  }
  return colors[criteria] || '#3B82F6'
}

const getPercentageColorClass = (percentage) => {
  if (percentage >= 95) return 'text-green-600'
  if (percentage >= 90) return 'text-blue-600'
  if (percentage >= 80) return 'text-yellow-600'
  if (percentage >= 60) return 'text-orange-600'
  return 'text-red-600'
}

const fetchSqdData = async () => {
  try {
    const response = await api.get(`${props.apiBasePath}/sqd-results`, {
      params: {
        ...props.filterParams,
        service_type: props.serviceType,
        sqd: selectedSQD.value,
      },
    })

    const payload = response?.data?.data || {}
    chartData.value = payload.distribution?.length
      ? payload.distribution.map((item) => ({
          criteria: item.criteria,
          value: item.value ?? 0,
        }))
      : defaultDistribution()
    totalResponses.value = payload.total_responses ?? 0
    overallPercentage.value = payload.overall_percentage ?? 0
    sqdDescription.value = payload.description || ''
  } catch (error) {
    console.error('Error fetching SQD data:', error)
    chartData.value = defaultDistribution()
    totalResponses.value = 0
    overallPercentage.value = 0
    sqdDescription.value = ''
  }
}

watch(
  [() => props.serviceType, () => props.filterParams.period, () => props.filterParams.date, () => props.filterParams.month, () => props.filterParams.year, selectedSQD],
  () => {
    fetchSqdData()
  },
  { immediate: true }
)
</script>


