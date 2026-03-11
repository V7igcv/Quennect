<template>
  <div 
    class="bg-white rounded-xl shadow-md overflow-hidden transition-all duration-300 cursor-pointer hover:shadow-lg h-full flex flex-col"
    @click="selectOffice"
  >
    <!-- Logo Section - nasa itaas bago ang content -->
    <div class="pt-6 sm:pt-8 pb-2 flex justify-center">
      <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-full bg-gray-100 flex items-center justify-center overflow-hidden border-2 border-gray-200">
        <!-- Actual logo kung meron -->
        <img 
          v-if="office.logo"
          :src="office.logo" 
          :alt="office.name"
          class="w-full h-full object-cover"
          @error="handleImageError"
        >
        <!-- Fallback kung walang logo -->
        <div 
          v-else
          class="w-full h-full bg-[#0F5C5C] bg-opacity-10 flex items-center justify-center"
        >
          <span class="text-2xl sm:text-3xl font-bold text-[#0F5C5C]">
            {{ office.acronym?.charAt(0) || office.name?.charAt(0) }}
          </span>
        </div>
      </div>
    </div>

    <!-- Content Section -->
    <div class="p-4 sm:p-5 pt-2 flex flex-col flex-grow">
      <!-- Office Name with Acronym - centered na para bagay sa logo -->
      <h2 class="text-base sm:text-lg font-bold text-[#1F4E79] text-center mb-1 sm:mb-2 line-clamp-2">
        {{ office.name }} ({{ office.acronym }})
      </h2>
      
      <!-- Office Description - centered text -->
      <p class="text-gray-600 text-xs sm:text-sm text-center mb-3 sm:mb-4 leading-relaxed line-clamp-2 sm:line-clamp-3">
        {{ office.description }}
      </p>
      
      <!-- Pillín Button -->
      <button 
        class="w-full bg-[#0F5C5C] hover:bg-[#0a4a4a] text-white font-semibold py-2 sm:py-3 px-3 sm:px-4 rounded-lg transition duration-300 text-sm sm:text-base mt-auto"
      >
        Piliin
      </button>
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue'
import { useRouter } from 'vue-router'  // ✅ I-add ito

const props = defineProps({
  office: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['select'])
const router = useRouter()  // ✅ I-initialize ito

const selectOffice = () => {
  // I-save sa localStorage
  localStorage.setItem('selectedOffice', JSON.stringify(props.office))
  
  // Diretso sa service selection page
  router.push(`/kiosk/service-selection?officeId=${props.office.id}`)
}

const handleImageError = (e) => {
  console.error('Failed to load logo for office:', props.office.name)
  // Itago ang image at ipakita ang fallback
  e.target.style.display = 'none'
  // I-add ang fallback div
  const parent = e.target.parentElement
  const fallback = document.createElement('div')
  fallback.className = 'w-full h-full bg-[#0F5C5C] bg-opacity-10 flex items-center justify-center'
  fallback.innerHTML = `<span class="text-2xl sm:text-3xl font-bold text-[#0F5C5C]">${props.office.acronym?.charAt(0) || props.office.name?.charAt(0)}</span>`
  parent.appendChild(fallback)
}
</script>