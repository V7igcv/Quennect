<template>
  <Card class="w-full">
    <CardHeader class="flex flex-row items-start justify-between space-y-0 pb-2">
      <div>
        <CardTitle class="text-lg font-semibold">{{ selectedSDQ }}</CardTitle>
        <CardDescription class="text-sm text-gray-500 mt-1">
          {{ getSDQDescription(selectedSDQ) }}
        </CardDescription>
      </div>
      <Select v-model="selectedSDQ">
        <SelectTrigger class="w-[180px]">
          <SelectValue placeholder="Select SDQ" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem v-for="sdq in sdqOptions" :key="sdq" :value="sdq">
            {{ sdq }}
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

// Props
const props = defineProps({
  serviceType: {
    type: String,
    default: 'external'
  },
  dateRange: {
    type: String,
    default: 'monthly'
  }
})

// State
const selectedSDQ = ref('SDQ0')

// SDQ Options
const sdqOptions = ['SDQ0', 'SDQ1', 'SDQ2', 'SDQ3', 'SDQ4', 'SDQ5', 'SDQ6', 'SDQ7', 'SDQ8']

// SDQ Descriptions mapping
const sdqDescriptions = {
  'SDQ0': 'I am satisfied with the service that I availed.',
  'SDQ1': 'I spent a reasonable amount of time for my transaction.',
  'SDQ2': 'The office followed the transactions requirements and steps based on the information provided',
  'SDQ3': 'The steps (including payment) I needed to do for my transaction were easy and simple.',
  'SDQ4': 'I easily found information about my transaction from the office or its website.',
  'SDQ5': 'I paid a reasonable amount of fees for my transaction.',
  'SDQ6': 'I feel the office was fair to everyone, or "walang palakasan", during my transaction.',
  'SDQ7': 'I was treated courteously by the staff, and (if asked for help) the staff was helpful.',
  'SDQ8': 'I got what I needed from the government office, or (if denied) denial of request was sufficiently explained to me.'
}

// Mock data for the chart (replace with actual API data)
const mockChartData = {
  'SDQ0': [
    { criteria: 'Strongly Disagree', value: 45 },
    { criteria: 'Disagree', value: 62 },
    { criteria: 'Neither', value: 88 },
    { criteria: 'Agree', value: 124 },
    { criteria: 'Strongly Agree', value: 156 },
    { criteria: 'N/A', value: 23 }
  ],
  'SDQ1': [
    { criteria: 'Strongly Disagree', value: 32 },
    { criteria: 'Disagree', value: 54 },
    { criteria: 'Neither', value: 76 },
    { criteria: 'Agree', value: 145 },
    { criteria: 'Strongly Agree', value: 134 },
    { criteria: 'N/A', value: 18 }
  ]
  // Add more SDQ data as needed
}

// Computed property for chart data based on selected SDQ
const chartData = computed(() => {
  return mockChartData[selectedSDQ.value] || mockChartData['SDQ0']
})


const maxChartValue = computed(() => {
  const values = chartData.value.map(item => item.value)
  return values.length ? Math.max(...values) : 1
})

const getBarHeight = (value) => {
  if (!maxChartValue.value) return 6
  return Math.max((value / maxChartValue.value) * 100, 6)
}

// Computed property for total responses
const totalResponses = computed(() => {
  return chartData.value.reduce((sum, item) => sum + item.value, 0)
})

// Computed property for overall percentage
const overallPercentage = computed(() => {
  const data = chartData.value
  const stronglyAgree = data.find(d => d.criteria === 'Strongly Agree')?.value || 0
  const agree = data.find(d => d.criteria === 'Agree')?.value || 0
  const na = data.find(d => d.criteria === 'N/A')?.value || 0
  
  if (totalResponses.value - na === 0) return 0
  
  return Math.round(((stronglyAgree + agree) / (totalResponses.value - na)) * 100)
})

// Get SDQ description
const getSDQDescription = (sdq) => {
  return sdqDescriptions[sdq] || 'No description available'
}

// Get bar color based on criteria
const getBarColor = (criteria) => {
  const colors = {
    'Strongly Disagree': '#EF4444', // Red
    'Disagree': '#F97316', // Orange
    'Neither': '#EAB308', // Yellow
    'Agree': '#22C55E', // Green
    'Strongly Agree': '#10B981', // Emerald
    'N/A': '#6B7280' // Gray
  }
  return colors[criteria] || '#3B82F6'
}

// Get color class for percentage based on performance scale
const getPercentageColorClass = (percentage) => {
  if (percentage >= 95) return 'text-green-600'
  if (percentage >= 90) return 'text-blue-600'
  if (percentage >= 80) return 'text-yellow-600'
  if (percentage >= 60) return 'text-orange-600'
  return 'text-red-600'
}

// Watch for changes in service type or date range to fetch new data
watch([() => props.serviceType, () => props.dateRange, selectedSDQ], () => {
  // In a real implementation, you would fetch new data here based on the filters
  console.log('Fetching SDQ data for:', {
    serviceType: props.serviceType,
    dateRange: props.dateRange,
    sdq: selectedSDQ.value
  })
}, { immediate: true })
</script>


