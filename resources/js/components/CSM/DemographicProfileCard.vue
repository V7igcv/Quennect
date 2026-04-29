<template>
  <Card class="w-full">
    <CardHeader class="flex flex-row items-start justify-between space-y-0 pb-2">
      <CardTitle class="text-lg font-semibold">{{ selectedCategory }}</CardTitle>
      <Select v-model="selectedCategory">
        <SelectTrigger class="w-[140px]">
          <SelectValue placeholder="Select category" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="Age">Age</SelectItem>
          <SelectItem value="Sex">Sex</SelectItem>
          <SelectItem value="Client Type">Client Type</SelectItem>
        </SelectContent>
      </Select>
    </CardHeader>

    <CardContent>
      <div class="h-[300px] w-full mt-2 flex items-center justify-center">
        <div
          class="relative h-52 w-52"
          @mousemove="handleDonutMouseMove"
          @mouseleave="clearHoverSegment"
        >
          <div class="h-full w-full rounded-full" :style="{ background: pieGradient }"></div>
          <div class="absolute inset-6 rounded-full bg-white flex flex-col items-center justify-center">
            <span class="text-2xl font-bold text-gray-900">{{ totalResponses }}</span>
            <span class="text-xs text-gray-500">Responses</span>
          </div>

          <div
            v-if="hoveredSegment"
            class="pointer-events-none absolute z-20 min-w-36 rounded-md border border-gray-200 bg-white px-3 py-2 text-xs shadow-lg"
            :style="{ left: `${tooltipPosition.x}px`, top: `${tooltipPosition.y}px`, transform: 'translate(8px, -110%)' }"
          >
            <p class="font-semibold text-gray-900">{{ hoveredSegment.name }}</p>
            <p class="mt-1 text-gray-600">Responses: <span class="font-semibold">{{ hoveredSegment.value }}</span></p>
            <p class="text-gray-600">Percentage: <span class="font-semibold">{{ hoveredSegment.percentage }}%</span></p>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-x-8 gap-y-2 mt-4 pt-4 border-t border-gray-100">
        <div
          v-for="(segment, index) in chartData"
          :key="index"
          class="flex items-center gap-2 text-sm"
        >
          <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: getSegmentColor(index) }"></div>
          <span class="text-gray-600">{{ segment.name }}</span>
          <span class="text-gray-900 font-medium ml-auto">{{ segment.percentage }}%</span>
        </div>
      </div>
    </CardContent>
  </Card>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import api from '@/services/api'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

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

const selectedCategory = ref('Age')
const hoveredSegment = ref(null)
const tooltipPosition = ref({ x: 0, y: 0 })
const chartData = ref([])
const totalResponses = ref(0)

const colorPalette = [
  '#2563EB',
  '#DC2626',
  '#F5700B',
  '#9626DC',
  '#16A34A',
  '#6B7280',
]

const defaultData = {
  Age: [
    { name: '19 or lower', value: 0, percentage: 0 },
    { name: '20-34', value: 0, percentage: 0 },
    { name: '35-49', value: 0, percentage: 0 },
    { name: '50-64', value: 0, percentage: 0 },
    { name: '65-Higher', value: 0, percentage: 0 },
    { name: 'Did not specify', value: 0, percentage: 0 },
  ],
  Sex: [
    { name: 'Male', value: 0, percentage: 0 },
    { name: 'Female', value: 0, percentage: 0 },
    { name: 'Did not specify', value: 0, percentage: 0 },
  ],
  'Client Type': [
    { name: 'Citizen', value: 0, percentage: 0 },
    { name: 'Business', value: 0, percentage: 0 },
    { name: 'Government', value: 0, percentage: 0 },
    { name: 'Did not specify', value: 0, percentage: 0 },
  ],
}

const getSegmentColor = (index) => {
  return colorPalette[index % colorPalette.length]
}

const pieGradient = computed(() => {
  let current = 0
  const slices = chartData.value.map((segment, index) => {
    const start = current
    const end = current + segment.percentage
    current = end
    return `${getSegmentColor(index)} ${start}% ${end}%`
  })
  return `conic-gradient(${slices.join(', ')})`
})

const getSegmentByPercent = (percent) => {
  let cumulative = 0

  for (const segment of chartData.value) {
    cumulative += segment.percentage
    if (percent <= cumulative) {
      return segment
    }
  }

  return chartData.value[chartData.value.length - 1] || null
}

const handleDonutMouseMove = (event) => {
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
    hoveredSegment.value = null
    return
  }

  const angle = (Math.atan2(dy, dx) * (180 / Math.PI) + 90 + 360) % 360
  const percent = (angle / 360) * 100

  hoveredSegment.value = getSegmentByPercent(percent)
  tooltipPosition.value = { x, y }
}

const clearHoverSegment = () => {
  hoveredSegment.value = null
}

const normalizeCategoryForApi = (category) => {
  if (category === 'Client Type') {
    return 'client_type'
  }

  return category.toLowerCase()
}

const fetchDemographicData = async () => {
  if (('office_id' in props.filterParams || props.filterParams.office_id === null) && !props.filterParams.office_id) {
    chartData.value = []
    totalResponses.value = 0
    selectedCategory.value = 'Age'
    return
  }

  try {
    const response = await api.get(`${props.apiBasePath}/demographic-profile`, {
      params: {
        ...props.filterParams,
        service_type: props.serviceType,
        category: normalizeCategoryForApi(selectedCategory.value),
      },
    })

    const payload = response?.data?.data || {}
    chartData.value = payload.distribution?.length
      ? payload.distribution.map((item) => ({
          name: item.name,
          value: item.value ?? 0,
          percentage: item.percentage ?? 0,
        }))
      : defaultData[selectedCategory.value]

    totalResponses.value = payload.total_responses ?? 0
  } catch (error) {
    console.error('Error fetching demographic data:', error)
    chartData.value = defaultData[selectedCategory.value]
    totalResponses.value = 0
  }
}

watch(
  [
    () => props.serviceType,
    () => props.filterParams.period,
    () => props.filterParams.date,
    () => props.filterParams.month,
    () => props.filterParams.year,
    () => props.filterParams.office_id,
    selectedCategory
  ],
  () => {
    fetchDemographicData()
  },
  { immediate: true }
)
</script>
