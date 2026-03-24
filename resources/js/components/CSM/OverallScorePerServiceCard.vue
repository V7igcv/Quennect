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
                    <p class="font-semibold text-gray-900">{{ entry.service_name || entry.name }}</p>
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
import { ref, watch } from 'vue'
import api from '@/services/api'
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

const props = defineProps({
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

const chartData = ref([])
const serviceTotalLabel = ref('Service Total')
const serviceTotalPercentage = ref(0)

const getScoreColor = (percentage) => {
  if (percentage >= 95) return '#22C55E'
  if (percentage >= 90) return '#3B82F6'
  if (percentage >= 80) return '#EAB308'
  if (percentage >= 60) return '#F97316'
  return '#EF4444'
}

const getBarHeight = (percentage) => {
  return Math.max(percentage, 6)
}

const getScoreColorClass = (percentage) => {
  if (percentage >= 95) return 'text-green-600'
  if (percentage >= 90) return 'text-blue-600'
  if (percentage >= 80) return 'text-yellow-600'
  if (percentage >= 60) return 'text-orange-600'
  return 'text-red-600'
}

const getRatingBadgeClass = (percentage) => {
  if (percentage >= 95) return 'bg-green-100 text-green-700'
  if (percentage >= 90) return 'bg-blue-100 text-blue-700'
  if (percentage >= 80) return 'bg-yellow-100 text-yellow-700'
  if (percentage >= 60) return 'bg-orange-100 text-orange-700'
  return 'bg-red-100 text-red-700'
}

const getRatingText = (percentage) => {
  if (percentage >= 95) return 'Outstanding'
  if (percentage >= 90) return 'Very Satisfactory'
  if (percentage >= 80) return 'Satisfactory'
  if (percentage >= 60) return 'Fair'
  return 'Poor'
}

const fetchOverallScore = async () => {
  try {
    const response = await api.get('/frontdesk/analytics/csm/overall-score-per-service', {
      params: {
        ...props.filterParams,
        service_type: props.serviceType,
      },
    })

    const payload = response?.data?.data || {}
    chartData.value = payload.chart_data || []
    serviceTotalLabel.value = payload.service_total_label || 'Service Total'
    serviceTotalPercentage.value = payload.service_total_percentage ?? 0
  } catch (error) {
    console.error('Error fetching overall score per service:', error)
    chartData.value = []
    serviceTotalLabel.value = 'Service Total'
    serviceTotalPercentage.value = 0
  }
}

watch(
  [() => props.serviceType, () => props.filterParams.period, () => props.filterParams.date, () => props.filterParams.month, () => props.filterParams.year],
  () => {
    fetchOverallScore()
  },
  { immediate: true }
)
</script>