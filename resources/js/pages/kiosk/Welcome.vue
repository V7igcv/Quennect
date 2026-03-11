<template>
  <!-- Main container with relative positioning -->
  <div class="relative min-h-screen overflow-hidden">
    
   <div class="absolute inset-0">
  <img 
    src="/storage/images/LGU-Ligao.jpg" 
    class="w-full h-full block" 
    style="opacity: 1; position: absolute; top: 0; left: 0;"
    alt="background"
  />
</div>

    <!-- Optional overlay para hindi masyadong bright -->
    <div class="absolute inset-0 bg-black bg-opacity-30 -z-5"></div>

    <!-- Content Container - lahat ng content naka-wrap dito -->
    <div class="relative z-10 flex flex-col items-center justify-between min-h-screen py-8 px-4">
      
      
      <!-- Header spacer -->
      <div></div>

      <!-- Main Content -->
      <div class="text-center space-y-8">
        <!-- System Title -->
       <div>
          <h1 class="text-4xl font-bold" style="color: #1F4E79;">Quennect</h1>
        </div>

        <!-- Government Seal -->
     <div class="flex flex-col items-center space-y-2">
  <div class="w-50 h-50 bg-gray-200 rounded-full flex items-center justify-center shadow-lg animate-float">
    
    <img 
        src="/storage/logos/Ligao City Seal.png"
        alt="Ligao City Seal"
        class="w-56 h-56 object-contain glow-blink"
      />

  </div>
</div>

        <!-- Welcome Message -->
        <div>
      <p class="text-2xl" style="color: #000000;">MALIGAYANG PAGDATING SA</p>
      <p class="text-3xl font-bold" style="color: #000000;">LIGAO CITY HALL!</p>
    </div>

        <!-- Get Number Button -->
        <button 
          @click="goToOfficeSelection"
          class="text-white text-xl font-semibold py-4 px-12 rounded-lg shadow-lg transition duration-300 transform hover:scale-105"
          :style="{ backgroundColor: '#0F5C5C' }"
          @mouseover="hover = true"
          @mouseleave="hover = false"
          :class="{ 'opacity-90': hover }"
        >
          KUMUHA NG NUMERO
        </button>
      </div>

      <!-- Footer Spacer -->
      <div class="opacity-0">Footer</div>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'

const router = useRouter()

const goToOfficeSelection = () => {
  router.push('/kiosk/office-selection')
}
</script>

<style scoped>
/* For kiosk touch screen - larger tap targets */
button {
  min-height: 64px;
  min-width: 200px;
}

@keyframes testMove {
  0% {
    transform: translateX(0px);
  }
  50% {
    transform: translateX(50px);
  }
  100% {
    transform: translateX(0px);
  }
}

/* Logo animation */
@keyframes float {
  0% {
    transform: translateY(0px);
  }
  50% {
    transform: translateY(-20px);
  }
  100% {
    transform: translateY(0px);
  }
}

.animate-float {
  animation: float 6s ease-in-out infinite;
  /* Add this para sure na kita */
 
}

@keyframes glowBlink {
  0%, 100% {
    filter: drop-shadow(0 0 10px #6edada);
  }
  50% {
    filter: drop-shadow(0 0 45px #82f3f3);
  }
}

.glow-blink {
  animation: glowBlink 1.8s infinite ease-in-out;
}
</style>

