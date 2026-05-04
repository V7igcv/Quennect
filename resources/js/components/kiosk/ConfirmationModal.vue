<template>
  <Teleport to="body">
    <div 
      v-if="show"
     class="fixed inset-0 z-[1000] flex items-center justify-center p-6 bg-black/60 overflow-hidden"
    >
      <div 
        class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full max-h-[85vh] border border-gray-100 flex flex-col overflow-hidden"
        @click.stop
      >
        <div class="p-6 pb-4 text-center">
          <h2 class="text-xl font-bold text-gray-800 leading-tight px-2">
            Tama at kumpleto ba ang impormasyon?
          </h2>
          <div class="border-t border-gray-100 w-full mt-4"></div>
        </div>

        <div class="px-8 py-2 flex-grow overflow-y-auto no-scrollbar">
          <div class="space-y-4">
            <div class="grid grid-cols-12 gap-2">
              <span class="col-span-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Opisina</span>
              <span class="col-span-8 text-sm font-semibold text-gray-700">{{ details.office?.name }}</span>
            </div>

            <div class="grid grid-cols-12 gap-2">
              <span class="col-span-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Serbisyo</span>
              <div class="col-span-8 text-sm text-gray-700 font-medium">
                <p v-for="service in details.services" :key="service.id" class="mb-1 last:mb-0">
                  • {{ service.name }}
                </p>
              </div>
            </div>

            <div class="border-t border-dashed border-gray-100 my-2"></div>

            <div class="grid grid-cols-12 gap-2">
              <span class="col-span-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Pangalan</span>
              <span class="col-span-8 text-sm font-semibold text-gray-700">{{ details.client?.full_name }}</span>
            </div>

            <div class="grid grid-cols-12 gap-2">
              <span class="col-span-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Contact</span>
              <span class="col-span-8 text-sm font-semibold text-gray-700">{{ details.client?.contact_number }}</span>
            </div>

            <div class="grid grid-cols-12 gap-2">
              <span class="col-span-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Barangay</span>
              <span class="col-span-8 text-sm font-semibold text-gray-700">{{ getBarangayName(details.client?.barangay_id) }}</span>
            </div>

            <div class="grid grid-cols-12 gap-2">
              <span class="col-span-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Lane</span>
              <span class="col-span-8 text-sm font-semibold text-gray-700 capitalize">{{ details.client?.lane_type }}</span>
            </div>

            <div v-if="details.client?.lane_type === 'priority'" class="grid grid-cols-12 gap-2">
              <span class="col-span-4 text-xs font-bold text-gray-400 uppercase tracking-wider">Priority</span>
              <span class="col-span-8 text-sm font-semibold text-gray-700 leading-tight">
                {{ getPrioritySectorNames(details.client.priority_sectors).join(', ') }}
              </span>
            </div>
          </div>
        </div>

        <div class="p-6">
          <div class="border-t border-gray-100 w-full mb-6"></div>
          <div class="flex gap-3">
            <button 
              @click="$emit('close')"
              class="flex-1 py-3 px-4 rounded-xl border border-gray-200 text-gray-600 font-bold text-sm hover:bg-gray-50 transition active:scale-95"
            >
              Baguhin
            </button>
            <button 
              @click="$emit('confirm')"
              class="flex-1 py-3 px-4 rounded-xl font-bold text-sm transition shadow-lg active:scale-95 bg-[#135D5D] text-white hover:bg-[#0e4a4a] cursor-pointer"
            >
              Kumpirmahin
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { watch, onUnmounted } from 'vue'

const props = defineProps({
  show: Boolean,
  details: Object,
  barangays: Array,
  prioritySectors: Array
})

const emit = defineEmits(['close', 'confirm'])

// Full Scroll Lock with iOS Support
watch(() => props.show, (isShown) => {
  const scrollValue = isShown ? 'hidden' : '';
  document.documentElement.style.overflow = scrollValue;
  document.body.style.overflow = scrollValue;
  if (isShown) {
    document.body.style.position = 'fixed';
    document.body.style.width = '100%';
  } else {
    document.body.style.position = '';
    document.body.style.width = '';
  }
}, { immediate: true })

onUnmounted(() => {
  document.documentElement.style.overflow = '';
  document.body.style.overflow = '';
  document.body.style.position = '';
})

const getBarangayName = (id) => {
  const b = props.barangays.find(x => x.id === id)
  return b ? (b.barangay_name || b.name) : '---'
}

const getPrioritySectorNames = (ids) => {
  if (!ids) return []
  return ids.map(id => {
    const s = props.prioritySectors.find(x => x.id === id)
    return s ? (s.sector_name || s.name) : 'Unknown'
  })
}
</script>

<style scoped>
/* Hide scrollbar for Chrome, Safari and Opera */
.no-scrollbar::-webkit-scrollbar {
  display: none;
}

/* Hide scrollbar for IE, Edge and Firefox */
.no-scrollbar {
  -ms-overflow-style: none;  /* IE and Edge */
  scrollbar-width: none;  /* Firefox */
}
</style>