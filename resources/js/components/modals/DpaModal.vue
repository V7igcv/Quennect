<!-- components/kiosk/DpaModal.vue -->
<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg mx-auto overflow-hidden">
          
          <!-- Modal Header -->
          <div class="bg-[#0F5C5C] px-6 py-4">
            <div class="flex items-center gap-3">
              <!-- Shield Icon -->
              <svg class="w-7 h-7 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
              <div>
                <h2 class="text-white font-bold text-lg leading-tight">Pahintulot sa Pagkalap ng Datos</h2>
                <p class="text-teal-200 text-xs">Data Privacy Act of 2012 (RA 10173)</p>
              </div>
            </div>
          </div>

          <!-- Modal Body -->
          <div class="px-6 py-5 space-y-4 max-h-[60vh] overflow-y-auto">

            <p class="text-gray-700 text-sm leading-relaxed">
              Alinsunod sa <span class="font-semibold">Batas Republika Blg. 10173</span>, 
              o ang <span class="font-semibold">Data Privacy Act of 2012</span>, 
              ipinapaalam namin sa inyo ang sumusunod:
            </p>

            <!-- Info Boxes -->
            <div class="bg-teal-50 border border-teal-200 rounded-lg p-4 space-y-2">
              <h3 class="text-sm font-semibold text-teal-800">Anong impormasyon ang kinokolekta?</h3>
              <ul class="text-sm text-teal-700 space-y-1 list-disc list-inside">
                <li>Buong pangalan</li>
                <li>Numero ng cellphone</li>
                <li>Barangay</li>
                <li>Uri ng pila (Regular o Priority)</li>
              </ul>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 space-y-2">
              <h3 class="text-sm font-semibold text-blue-800">Para saan gagamitin ang inyong datos?</h3>
              <p class="text-sm text-blue-700">
                Gagamitin lamang ang inyong personal na impormasyon para sa layunin ng 
                <span class="font-semibold">queuing system</span> at pagproseso ng inyong 
                transaksyon sa opisina. Hindi ito ibibigay sa mga third party nang walang 
                inyong pahintulot.
              </p>
            </div>

            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 space-y-2">
              <h3 class="text-sm font-semibold text-amber-800">Ang inyong mga karapatan</h3>
              <p class="text-sm text-amber-700">
                Mayroon kayong karapatang mag-access, mag-correct, o humiling ng pagbura 
                ng inyong personal na datos ayon sa Data Privacy Act of 2012.
              </p>
            </div>

            <!-- Consent Statement -->
            <div class="border-t border-gray-200 pt-4">
              <p class="text-gray-800 text-sm leading-relaxed font-medium text-center">
                Sa pag-click ng <span class="text-[#0F5C5C] font-bold">"Sumasang-ayon Ako"</span>, 
                kayo ay nagbibigay ng inyong pahintulot sa pagkalap at paggamit ng inyong 
                personal na impormasyon para sa mga nabanggit na layunin, alinsunod sa 
                Data Privacy Act of 2012.
              </p>
            </div>

          </div>

          <!-- Modal Footer -->
          <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex flex-col sm:flex-row gap-3">
            <!-- Decline -->
            <button 
              @click="$emit('close')"
              class="flex-1 px-4 py-3 rounded-lg border border-gray-300 text-gray-700 font-semibold text-sm hover:bg-gray-100 transition"
            >
              Hindi Ako Pumapayag
            </button>
            <!-- Agree -->
            <button 
              @click="$emit('agree')"
              class="flex-1 px-4 py-3 rounded-lg bg-[#0F5C5C] text-white font-semibold text-sm hover:bg-[#0a4444] transition"
            >
              Sumasang-ayon Ako
            </button>
          </div>

        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
defineProps({ show: Boolean })
defineEmits(['close', 'agree'])
</script>

<style scoped>
.modal-enter-active, .modal-leave-active {
  transition: opacity 0.2s ease;
}
.modal-enter-from, .modal-leave-to {
  opacity: 0;
}
</style>