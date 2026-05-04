<template>
  <div class="w-full py-4 px-8 shadow-md sticky top-0 z-50" :style="{ backgroundColor: bgColor }">
    <div class="flex items-center">
      <!-- Logo/Seal -->
      <img 
        src="/storage/images/Quennect_Logo.png"
        alt="Quennect Logo" 
        class="h-10 w-14 h-14 object-contain mr-3"
      >
      
      <!-- System Name - katabi lang ng logo -->
      <div>
        <h1 class="text-2xl font-bold" :style="{ color: textColor }">Quennect</h1>
        <p class="text-md font-regular" :style="{ color: textColor }">LGU Ligao General Queuing System</p>
      </div>
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