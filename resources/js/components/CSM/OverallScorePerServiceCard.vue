<template>
  <Card class="w-full pt-5">
    <!-- <CardHeader>
      <CardTitle class="text-lg font-semibold">
        Overall Score Per Service
        <span class="italic text-[#6B7280]">({{ getServiceTypeLabel }})</span>
      </CardTitle>
    </CardHeader> -->

    <CardContent>
      <!-- Bar Chart -->
      <div class="h-[300px] w-full">
        <div class="h-full rounded-lg border border-gray-100 bg-gray-50 p-4">
          <div class="flex h-full items-end gap-3 border-b border-gray-200 pb-3">
            <div
              v-for="(entry, index) in chartData"
              :key="'score-bar-' + index"
              class="flex min-w-0 flex-1 flex-col items-center"
            >
              <TooltipProvider>
                <Tooltip>
                  <TooltipTrigger as-child>
                    <div class="flex h-48 w-full max-w-12 items-end cursor-help">
                      <div
                        class="w-full rounded-t-md transition-all duration-300"
                        :style="{
                          height: `${getBarHeight(entry.percentage)}%`,
                          backgroundColor: getScoreColor(entry.percentage)
                        }"
                      ></div>
                    </div>
                  </TooltipTrigger>
                  <TooltipContent class="min-w-36 rounded-md border border-gray-200 bg-white px-3 py-2 text-xs shadow-lg">
                    <p class="font-semibold text-gray-900">{{ entry.name }}</p>
                    <p class="mt-1 text-gray-600">{{ entry.description }}</p>
                    <p class="text-gray-600">Score: <span class="font-semibold">{{ entry.percentage }}%</span></p>
                    <p class="text-gray-600">Rating: <span class="font-semibold">{{ getRatingText(entry.percentage) }}</span></p>
                  </TooltipContent>
                </Tooltip>
              </TooltipProvider>
              <span class="mt-2 h-10 px-1 text-[11px] font-medium leading-tight text-center text-gray-600 flex items-start justify-center">
                {{ entry.name }}
              </span>
              <span class="mt-1 text-xs font-semibold text-gray-900">{{ entry.percentage }}%</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer with Service Total and Rating -->
      <div class="flex items-center justify-between mt-6 pt-4 border-t border-gray-100">
        <div class="flex items-center gap-3">
          <span class="text-sm font-medium text-gray-500">{{ serviceTotalLabel }}:</span>
          <span class="text-lg font-semibold" :class="getScoreColorClass(serviceTotalPercentage)">
            {{ serviceTotalPercentage }}%
          </span>
          <span 
            class="text-sm px-2 py-1 rounded-full font-medium"
            :class="getRatingBadgeClass(serviceTotalPercentage)"
          >
            {{ getRatingText(serviceTotalPercentage) }}
          </span>
        </div>

        <!-- Info Icon with Popover -->
        <Popover>
          <PopoverTrigger as-child>
            <Button variant="ghost" size="sm" class="h-8 w-8 p-0 rounded-full">
              <Info class="h-4 w-4 text-gray-500" />
            </Button>
          </PopoverTrigger>
          <PopoverContent class="w-64">
            <div class="space-y-2">
              <h4 class="font-medium text-sm">Performance Scale</h4>
              <div class="space-y-1">
                <div class="flex items-center justify-between text-xs">
                  <span class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-green-500"></span>
                    95% - 100%
                  </span>
                  <span class="font-medium">Outstanding</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                  <span class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                    90% - 94.9%
                  </span>
                  <span class="font-medium">Very Satisfactory</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                  <span class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-yellow-500"></span>
                    80% - 89.9%
                  </span>
                  <span class="font-medium">Satisfactory</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                  <span class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                    60% - 79.9%
                  </span>
                  <span class="font-medium">Fair</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                  <span class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-red-500"></span>
                    Below 60%
                  </span>
                  <span class="font-medium">Poor</span>
                </div>
              </div>
            </div>
          </PopoverContent>
        </Popover>
      </div>
    </CardContent>
  </Card>
</template>

<script setup>
import { computed, watch } from 'vue'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
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
import { Info } from 'lucide-vue-next'

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

// Mock data for different service types
const mockData = {
  external: [
    { name: 'Service A', percentage: 95.5, description: 'Front Desk Services' },
    { name: 'Service B', percentage: 88.2, description: 'Document Processing' },
    { name: 'Service C', percentage: 92.8, description: 'Payment Services' },
    { name: 'Service D', percentage: 76.4, description: 'Inquiry Services' },
    { name: 'Service E', percentage: 83.1, description: 'Complaint Handling' }
  ],
  internal: [
    { name: 'Service X', percentage: 91.2, description: 'HR Services' },
    { name: 'Service Y', percentage: 84.7, description: 'IT Support' },
    { name: 'Service Z', percentage: 78.9, description: 'Administrative' }
  ],
  all: [
    { name: 'Service A', percentage: 95.5, description: 'Front Desk Services' },
    { name: 'Service B', percentage: 88.2, description: 'Document Processing' },
    { name: 'Service C', percentage: 92.8, description: 'Payment Services' },
    { name: 'Service D', percentage: 76.4, description: 'Inquiry Services' },
    { name: 'Service E', percentage: 83.1, description: 'Complaint Handling' },
    { name: 'Service X', percentage: 91.2, description: 'HR Services' },
    { name: 'Service Y', percentage: 84.7, description: 'IT Support' },
    { name: 'Service Z', percentage: 78.9, description: 'Administrative' }
  ]
}

// Computed property for service type label
const getServiceTypeLabel = computed(() => {
  switch(props.serviceType) {
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

// Computed property for chart data based on service type
const chartData = computed(() => {
  return mockData[props.serviceType] || mockData['external']
})

// Computed property for service total label
const serviceTotalLabel = computed(() => {
  switch(props.serviceType) {
    case 'external':
      return 'External Service Total'
    case 'internal':
      return 'Internal Service Total'
    case 'all':
      return 'Overall Service Total'
    default:
      return 'Service Total'
  }
})

// Computed property for service total percentage (average of all services)
const serviceTotalPercentage = computed(() => {
  const data = chartData.value
  if (data.length === 0) return 0
  const sum = data.reduce((acc, curr) => acc + curr.percentage, 0)
  return Math.round((sum / data.length) * 10) / 10 // Round to 1 decimal
})

// Get color for bar based on percentage
const getScoreColor = (percentage) => {
  if (percentage >= 95) return '#22C55E' // Green
  if (percentage >= 90) return '#3B82F6' // Blue
  if (percentage >= 80) return '#EAB308' // Yellow
  if (percentage >= 60) return '#F97316' // Orange
  return '#EF4444' // Red
}

const getBarHeight = (percentage) => {
  return Math.max(percentage, 6)
}

// Get color class for text based on percentage
const getScoreColorClass = (percentage) => {
  if (percentage >= 95) return 'text-green-600'
  if (percentage >= 90) return 'text-blue-600'
  if (percentage >= 80) return 'text-yellow-600'
  if (percentage >= 60) return 'text-orange-600'
  return 'text-red-600'
}

// Get rating badge class
const getRatingBadgeClass = (percentage) => {
  if (percentage >= 95) return 'bg-green-100 text-green-700'
  if (percentage >= 90) return 'bg-blue-100 text-blue-700'
  if (percentage >= 80) return 'bg-yellow-100 text-yellow-700'
  if (percentage >= 60) return 'bg-orange-100 text-orange-700'
  return 'bg-red-100 text-red-700'
}

// Get rating text
const getRatingText = (percentage) => {
  if (percentage >= 95) return 'Outstanding'
  if (percentage >= 90) return 'Very Satisfactory'
  if (percentage >= 80) return 'Satisfactory'
  if (percentage >= 60) return 'Fair'
  return 'Poor'
}

// Watch for changes in service type or date range to fetch new data
watch(
  [() => props.serviceType, () => props.dateRange], 
  () => {
    // In a real implementation, you would fetch new data here based on the filters
    console.log('Fetching overall score data for:', {
      serviceType: props.serviceType,
      dateRange: props.dateRange
    })
  }, 
  { immediate: true }
)
</script>