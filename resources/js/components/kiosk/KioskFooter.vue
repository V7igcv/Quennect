<template>
  <div class="w-full bg-gray-200 border-t border-gray-300 px-8 py-4 flex justify-between items-center sticky bottom-0 z-50">
    
    <!-- Back -->
    <button 
      @click="$emit('back')" 
      class="flex items-center gap-2 text-[#0F5C5C] font-semibold cursor-pointer"
    >
      <Triangle class="w-6 h-6 rotate-270 fill-current" />
      Bumalik
    </button>

    <!-- Next -->
    <button 
      v-if="nextVisible"
      @click="$emit('next')" 
      class="flex items-center gap-2 text-[#0F5C5C] font-semibold cursor-pointer"
    >
      Magpatuloy
      <Triangle class="w-6 h-6 rotate-90 fill-current" />
    </button>

  </div>
</template>

<script setup>
import { Triangle } from 'lucide-vue-next'

defineProps({
  nextVisible: {
    type: Boolean,
    default: true
  }
})

defineEmits(['back','next'])
</script>