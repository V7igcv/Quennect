<template>
  <Card class="shadow-sm rounded-xl bg-white">
    <CardContent class="flex items-center gap-4 p-5 min-h-[100px]">

      <!-- Icon Circle -->
      <div
        class="flex items-center justify-center w-12 h-12 rounded-full shrink-0"
        :class="iconBg"
      >
        <component :is="icon" class="w-6 h-6" :class="iconColor" />
      </div>

      <!-- Text Content -->
      <div>
        <p class="text-sm text-[#474C55]">
          {{ title }}
        </p>

        <p class="text-2xl font-semibold" :class="numberColor">
          <span v-if="prefix">{{ prefix }}</span>{{ formattedValue }}<span v-if="suffix">{{ suffix }}</span>
        </p>
      </div>

    </CardContent>
  </Card>
</template>

<script setup>
import { Card, CardContent } from '@/components/ui/card'
import { computed } from 'vue'

const props = defineProps({
  title: String,
  value: Number,
  icon: Object,
  iconBg: String,
  iconColor: String,
  numberColor: String,
  prefix: {
    type: String,
    default: ''
  },
  suffix: {
    type: String,
    default: ''
  }
})

// Add this computed property
const formattedValue = computed(() => {
  if (props.value === undefined || props.value === null) return '0'
  
  // Just add commas, no decimals
  return props.value.toLocaleString('en-US')
})
</script>