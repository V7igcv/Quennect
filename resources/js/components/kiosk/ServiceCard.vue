<template>
  <div 
    class="bg-white rounded-xl shadow-md overflow-hidden transition-all duration-300 cursor-pointer h-full flex flex-col"
    :class="[
      isSelected ? 'ring-2 ring-[#0F5C5C] bg-[#0F5C5C] bg-opacity-5' : 'hover:shadow-lg'
    ]"
    @click="toggleSelect"
  >
    <div class="p-4 sm:p-5 flex flex-col flex-grow">
      <!-- Service Name with Code -->
      <h2 class="text-base sm:text-lg font-bold text-[#1F4E79] mb-2">
        {{ service.name }} ({{ service.code }})
      </h2>
      
      <!-- Service Description -->
      <p class="text-gray-600 text-xs sm:text-sm mb-4 leading-relaxed line-clamp-3">
        {{ service.description }}
      </p>
      
      <!-- Select/Selected Indicator -->
      <div class="mt-auto flex items-center justify-between">
        <span class="text-sm text-gray-500">I-click para pumili</span>
        <div 
          class="w-6 h-6 rounded border-2 flex items-center justify-center transition-all duration-300"
          :class="isSelected ? 'bg-[#0F5C5C] border-[#0F5C5C]' : 'border-gray-300'"
        >
          <svg v-if="isSelected" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
          </svg>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue'

const props = defineProps({
  service: {
    type: Object,
    required: true
  },
  isSelected: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['toggle'])

const toggleSelect = () => {
  emit('toggle', props.service)
}
</script>