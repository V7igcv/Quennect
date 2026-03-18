<template>
  <div class="max-w-7xl mx-auto px-2 sm:px-2 lg:px-2 py-2">
    <!-- Header with title and controls -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
      <h1 class="text-2xl font-semibold">Client Satisfaction Measurement Analytics</h1>
      
      <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full sm:w-auto">

        <!-- Service Type Dropdown -->
        <Select v-model="selectedServiceType">
          <SelectTrigger class="w-full sm:w-[180px] bg-white cursor-pointer">
            <span class="flex items-center gap-2">
              <Building2 class="h-4 w-4 text-gray-500 shrink-0" />
              <SelectValue placeholder="Service Type" />
            </span>
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="external">External</SelectItem>
            <SelectItem value="internal">Internal</SelectItem>
            <SelectItem value="all">All</SelectItem>
          </SelectContent>
        </Select>

        <!-- Date Dropdown -->
        <Select v-model="selectedDateRange">
          <SelectTrigger class="w-full sm:w-[180px] bg-white cursor-pointer">
            <span class="flex items-center gap-2">
              <Calendar class="h-4 w-4 text-gray-500 shrink-0" />
              <SelectValue placeholder="Date Range" />
            </span>
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="daily">Daily</SelectItem>
            <SelectItem value="monthly">Monthly</SelectItem>
            <SelectItem value="yearly">Yearly</SelectItem>
          </SelectContent>
        </Select>
      </div>
    </div>

    <!-- Rest of the dashboard content will go here -->
    <!-- Dashboard Content -->
    <div class="space-y-6">
      <!-- Overview Section -->
      <div>
        <div class="flex items-center justify-between mb-4">
          <!-- Left: Title -->
          <h2 class="text-xl font-semibold">
            Overview 
            <span class="italic text-[#6B7280]">({{ getServiceTypeLabel }})</span>
          </h2>

          <!-- Right: Button -->
          <Button 
            class="h-10 px-4 bg-[#0F5C5C] hover:bg-[#167D7F] text-white flex items-center gap-2"
          >
            <FileText class="h-4 w-4" />
            Generate Report
          </Button>
        </div>

        <!-- 5 Stat Cards Row -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5">
          <StatCard
            class="border-l-4 border-blue-400"
            title="Total Transactions"
            :value="stats.totalTransactions"
            :icon="Users"
            iconBg="bg-[#C2D0F1]"
            iconColor="text-[#2563EB]"
            numberColor="text-[#2563EB]"
          />

          <StatCard
            class="border-l-4 border-red-400"
            title="CC Awareness"
            :value="stats.ccAwareness"
            :icon="Megaphone"
            iconBg="bg-[#F6C5C5]"
            iconColor="text-[#DC2626]"
            numberColor="text-[#DC2626]"
            suffix="%"
          />

          <StatCard
            class="border-l-4 border-orange-300"
            title="CC Visibility"
            :value="stats.ccVisibility"
            :icon="Eye"
            iconBg="bg-[#FFDCC2]"
            iconColor="text-[#F5700B]"
            numberColor="text-[#F5700B]"
            suffix="%"
          />

          <StatCard
            class="border-l-4 border-purple-400"
            title="CC Helpfulness"
            :value="stats.ccHelpfulness"
            :icon="Hand"
            iconBg="bg-[#E8CEF8]"
            iconColor="text-[#9626DC]"
            numberColor="text-[#9626DC]"
            suffix="%"
          />

          <StatCard
            class="border-l-4 border-green-400"
            title="Overall Score"
            :value="stats.overallScore"
            :icon="BarChart3"
            iconBg="bg-[#C1F1D2]"
            iconColor="text-[#16A34A]"
            numberColor="text-[#16A34A]"
            suffix="%"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Button } from '@/components/ui/button'
import StatCard from '@/components/common/StatCard.vue'
import { 
  FileText,
  Building2, 
  Calendar, 
  Users, 
  Megaphone, 
  Eye, 
  Hand, 
  BarChart3 
} from 'lucide-vue-next'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

// State for dropdowns
const selectedServiceType = ref('external')
const selectedDateRange = ref('monthly')

// Computed property for service type label
const getServiceTypeLabel = computed(() => {
  switch(selectedServiceType.value) {
    case 'external':
      return 'External'
    case 'internal':
      return 'Internal'
    case 'all':
      return 'All'
    default:
      return 'External'
  }
})

// Mock data for stats (replace with actual API data later)
const stats = ref({
  totalTransactions: 23455,
  ccAwareness: 82.3,
  ccVisibility: 91.7,
  ccHelpfulness: 88.9,
  overallScore: 85.2
})
</script>