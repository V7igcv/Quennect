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
          {{ value }}{{ suffix }}
        </p>
      </div>

    </CardContent>
  </Card>
</template>

<script setup>
import { Card, CardContent } from '@/components/ui/card'

defineProps({
  title: String,
  value: Number,
  icon: Object,
  iconBg: String,
  iconColor: String,
  numberColor: String,
  suffix: {
    type: String,
    default: ''
  }
})
</script>