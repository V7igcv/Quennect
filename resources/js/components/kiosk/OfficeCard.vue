<template>
  <div 
    class="bg-white rounded-xl shadow-md overflow-hidden transition-all duration-300 cursor-pointer hover:shadow-lg h-full flex flex-col"
    @click="selectOffice"
  >
    <!-- Logo Section -->
    <div class="pt-6 sm:pt-8 pb-2 flex justify-center">
      <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-full bg-gray-100 flex items-center justify-center overflow-hidden border-2 border-gray-200">
        <img 
          v-if="office.logo"
          :src="office.logo" 
          :alt="office.name"
          class="w-full h-full object-cover"
          @error="handleImageError"
        >
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
      <h2 class="text-base sm:text-lg font-bold text-[#1F4E79] text-center mb-1 sm:mb-2 line-clamp-2">
        {{ office.name }} ({{ office.acronym }})
      </h2>
      
      <p class="text-gray-600 text-xs sm:text-sm text-center mb-3 sm:mb-4 leading-relaxed line-clamp-2 sm:line-clamp-3">
        {{ office.description }}
      </p>
      
      <button 
        class="w-full bg-[#0F5C5C] hover:bg-[#0a4a4a] text-white font-semibold py-2 sm:py-3 px-3 sm:px-4 rounded-lg transition duration-300 text-sm sm:text-base mt-auto"
      >
        Piliin
      </button>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'

const props = defineProps({
  office: {
    type: Object,
    required: true
  }
})

const router = useRouter()

const selectOffice = () => {
  // I-save ang buong office object kasama ang map_image
  const officeToSave = {
    id: props.office.id,
    name: props.office.name,
    acronym: props.office.acronym,
    description: props.office.description,
    logo: props.office.logo,
    map_image: props.office.map_image || null
  }
  
  console.log('Saving office with map_image:', officeToSave)
  localStorage.setItem('selectedOffice', JSON.stringify(officeToSave))
  router.push(`/kiosk/service-selection?officeId=${props.office.id}`)
}

const handleImageError = (e) => {
  console.error('Failed to load logo for office:', props.office.name)
  e.target.style.display = 'none'
  const parent = e.target.parentElement
  const fallback = document.createElement('div')
  fallback.className = 'w-full h-full bg-[#0F5C5C] bg-opacity-10 flex items-center justify-center'
  fallback.innerHTML = `<span class="text-2xl sm:text-3xl font-bold text-[#0F5C5C]">${props.office.acronym?.charAt(0) || props.office.name?.charAt(0)}</span>`
  parent.appendChild(fallback)
}
</script>