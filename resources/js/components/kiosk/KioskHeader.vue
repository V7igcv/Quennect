<template>
  <div class="w-full py-3 px-6 shadow-md" :style="{ backgroundColor: bgColor }">
    <div class="flex items-center">
      <!-- Logo/Seal -->
      <img 
        src="/storage/logos/Ligao City Seal.png" 
        alt="Ligao City Seal" 
        class="h-10 w-auto object-contain mr-3"
      >
      
      <!-- System Name - katabi lang ng logo -->
      <h1 class="text-2xl font-bold" :style="{ color: textColor }">Quennect</h1>
    </div>
  </div>
</template>

<script setup>
import { defineProps } from 'vue'

defineProps({
  bgColor: {
    type: String,
    default: '#0F5C5C'
  },
  textColor: {
    type: String,
    default: '#FFFFFF'
  },
  logoSize: {
    type: String,
    default: 'h-10'
  }
})
</script>