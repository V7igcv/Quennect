<template>
  <div class="w-full bg-gray-200 border-t border-gray-300 px-6 py-4 flex justify-between items-center">
    
    <button 
      @click="$emit('back')" 
      class="flex items-center gap-2 text-[#0F5C5C] font-semibold"
    >
      ◀ Bumalik
    </button>

    <button 
      v-if="nextVisible"
      @click="$emit('next')" 
      class="flex items-center gap-2 text-[#0F5C5C] font-semibold"
    >
      Magpatuloy ▶
    </button>

  </div>
</template>

<script setup>
defineProps({
  nextVisible: {
    type: Boolean,
    default: true
  }
})

defineEmits(['back','next'])
</script>