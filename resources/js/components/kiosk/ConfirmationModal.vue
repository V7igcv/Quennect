<template>
  <!-- Modal Overlay -->
  <div 
    v-if="show"
    class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4"
    @click.self="$emit('close')"
  >
    <!-- Modal Container -->
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
      
      <!-- Modal Header -->
      <div class="p-6 border-b border-gray-200">
        <h2 class="text-2xl font-bold text-gray-800">Tama at kumpleto ba ang lahat ng impormasyong inilagay at pinili mo?</h2>
        <p class="text-gray-600 mt-2">Pakisuri ang lahat ng impormasyong inilagay mo bago magpatuloy.</p>
      </div>

      <!-- Modal Body - Summary of Information -->
      <div class="p-6 space-y-4">
        
        <!-- Office -->
        <div class="flex border-b border-gray-100 pb-3">
          <span class="font-semibold text-gray-700 w-32">Opisina:</span>
          <span class="text-gray-900 flex-1">{{ details.office.name }} ({{ details.office.acronym }})</span>
        </div>

        <!-- Services -->
        <div class="flex border-b border-gray-100 pb-3">
          <span class="font-semibold text-gray-700 w-32">Serbisyo:</span>
          <div class="text-gray-900 flex-1">
            <div v-for="(service, index) in details.services" :key="service.id">
              {{ service.name }} ({{ service.code }})<span v-if="index < details.services.length - 1">,</span>
            </div>
          </div>
        </div>

        <!-- Name -->
        <div class="flex border-b border-gray-100 pb-3">
          <span class="font-semibold text-gray-700 w-32">Pangalan:</span>
          <span class="text-gray-900 flex-1">{{ details.client.full_name }}</span>
        </div>

        <!-- Contact Number -->
        <div class="flex border-b border-gray-100 pb-3">
          <span class="font-semibold text-gray-700 w-32">Contact Number:</span>
          <span class="text-gray-900 flex-1">{{ details.client.contact_number }}</span>
        </div>

        <!-- Barangay -->
        <div class="flex border-b border-gray-100 pb-3">
          <span class="font-semibold text-gray-700 w-32">Barangay:</span>
          <span class="text-gray-900 flex-1">{{ getBarangayName(details.client.barangay_id) }}</span>
        </div>

        <!-- Lane Type -->
        <div class="flex border-b border-gray-100 pb-3">
          <span class="font-semibold text-gray-700 w-32">Uri ng Lane:</span>
          <span class="text-gray-900 flex-1 capitalize">{{ details.client.lane_type }}</span>
        </div>

        <!-- Priority Sectors (if applicable) -->
        <div v-if="details.client.lane_type === 'priority' && details.client.priority_sectors.length > 0" class="flex border-b border-gray-100 pb-3">
          <span class="font-semibold text-gray-700 w-32">Priority Sector:</span>
          <div class="text-gray-900 flex-1">
            <span v-for="(sector, index) in getPrioritySectorNames(details.client.priority_sectors)" :key="index">
              {{ sector }}<span v-if="index < details.client.priority_sectors.length - 1">, </span>
            </span>
          </div>
        </div>
      </div>

      <!-- Modal Footer - Actions -->
      <div class="p-6 border-t border-gray-200 flex justify-end gap-4">
        <button 
          @click="$emit('close')"
          class="px-6 py-3 rounded-lg border-2 border-[#0F5C5C] text-[#0F5C5C] font-semibold hover:bg-gray-50 transition"
        >
          Kanselahin
        </button>
        <button 
          @click="$emit('confirm')"
          class="px-6 py-3 rounded-lg bg-[#0F5C5C] text-white font-semibold hover:bg-[#0a4a4a] transition"
        >
          Kumpirmahin
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  details: {
    type: Object,
    required: true
  },
  barangays: {
    type: Array,
    default: () => []
  },
  prioritySectors: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['close', 'confirm'])

// Helper function to get barangay name by ID
const getBarangayName = (barangayId) => {
  const barangay = props.barangays.find(b => b.id === barangayId)
  return barangay ? (barangay.barangay_name || barangay.name) : 'Unknown'
}

// Helper function to get priority sector names by IDs
const getPrioritySectorNames = (sectorIds) => {
  return sectorIds.map(id => {
    const sector = props.prioritySectors.find(s => s.id === id)
    return sector ? (sector.sector_name || sector.name) : 'Unknown'
  })
}
</script>