<template>
  <div class="max-w-7xl mx-auto px-2 sm:px-2 lg:px-2 py-2">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
      <h1 class="text-2xl font-semibold">AICS Analytics</h1>

      <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full sm:w-auto">
        <Popover v-model:open="isDateFilterOpen">
          <PopoverTrigger as-child>
            <Button variant="outline" class="w-full sm:w-[220px] justify-start bg-white">
              <span class="flex items-center gap-2">
                <Calendar class="h-4 w-4 text-gray-500 shrink-0" />
                <span class="truncate">{{ dateFilterLabel }}</span>
              </span>
            </Button>
          </PopoverTrigger>

          <PopoverContent align="end" class="w-[320px] p-3">
            <div v-if="selectedDateRange === 'daily'" class="space-y-3">
              <div class="flex items-center justify-between">
                <Button variant="ghost" size="icon" class="h-8 w-8" @click="goToPrevDailyMonth">
                  <ChevronLeft class="h-4 w-4" />
                </Button>
                <p class="text-sm font-semibold">{{ dailyHeaderLabel }}</p>
                <Button variant="ghost" size="icon" class="h-8 w-8" @click="goToNextDailyMonth">
                  <ChevronRight class="h-4 w-4" />
                </Button>
              </div>

              <div class="grid grid-cols-7 gap-1 text-center text-xs text-gray-500">
                <span v-for="dayName in weekDayLabels" :key="dayName">{{ dayName }}</span>
              </div>

              <div class="grid grid-cols-7 gap-1">
                <button
                  v-for="(cell, index) in dailyCalendarCells"
                  :key="`day-cell-${index}`"
                  type="button"
                  :disabled="!cell"
                  class="h-9 rounded-md text-sm transition-colors"
                  :class="[
                    !cell && 'cursor-default bg-transparent',
                    cell && isSelectedDay(cell)
                      ? 'bg-[#0F5C5C] text-white'
                      : 'bg-white text-gray-700 hover:bg-gray-50',
                  ]"
                  @click="cell && selectDailyDate(cell)"
                >
                  <span v-if="cell">{{ cell }}</span>
                </button>
              </div>
            </div>

            <div v-else-if="selectedDateRange === 'monthly'" class="space-y-3">
              <div class="flex items-center justify-between">
                <Button variant="ghost" size="icon" class="h-8 w-8" @click="selectedMonthYear -= 1">
                  <ChevronLeft class="h-4 w-4" />
                </Button>
                <p class="text-sm font-semibold">{{ monthNames[selectedMonthIndex] }} {{ selectedMonthYear }}</p>
                <Button variant="ghost" size="icon" class="h-8 w-8" @click="selectedMonthYear += 1">
                  <ChevronRight class="h-4 w-4" />
                </Button>
              </div>

              <div class="grid grid-cols-3 gap-2">
                <button
                  v-for="(monthName, monthIndex) in monthNames"
                  :key="monthName"
                  type="button"
                  class="h-9 rounded-md border text-xs font-medium transition-colors"
                  :class="monthIndex === selectedMonthIndex ? 'border-[#0F5C5C] bg-[#0F5C5C] text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'"
                  @click="selectedMonthIndex = monthIndex"
                >
                  {{ monthName.slice(0, 3).toUpperCase() }}
                </button>
              </div>
            </div>

            <div v-else class="space-y-3">
              <p class="text-center text-sm font-semibold">{{ selectedYear }}</p>

              <div class="max-h-56 space-y-1 overflow-y-auto pr-1">
                <button
                  v-for="yearOption in yearOptions"
                  :key="yearOption"
                  type="button"
                  class="flex h-9 w-full items-center justify-between rounded-md px-3 text-sm transition-colors"
                  :class="String(yearOption) === selectedYear ? 'bg-gray-100 text-gray-900' : 'text-gray-600 hover:bg-gray-50'"
                  @click="selectedYear = String(yearOption)"
                >
                  <span>{{ yearOption }}</span>
                  <span v-if="String(yearOption) === selectedYear" class="text-xs">✓</span>
                </button>
              </div>
            </div>

            <div class="mt-3 grid grid-cols-3 gap-2">
              <Button
                type="button"
                size="sm"
                variant="outline"
                class="font-medium"
                :class="selectedDateRange === 'daily' ? 'border-[#0F5C5C] bg-[#0F5C5C] text-white hover:bg-[#0C4B4B] hover:text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'"
                @click="selectedDateRange = 'daily'"
              >
                Daily
              </Button>
              <Button
                type="button"
                size="sm"
                variant="outline"
                class="font-medium"
                :class="selectedDateRange === 'monthly' ? 'border-[#0F5C5C] bg-[#0F5C5C] text-white hover:bg-[#0C4B4B] hover:text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'"
                @click="selectedDateRange = 'monthly'"
              >
                Monthly
              </Button>
              <Button
                type="button"
                size="sm"
                variant="outline"
                class="font-medium"
                :class="selectedDateRange === 'yearly' ? 'border-[#0F5C5C] bg-[#0F5C5C] text-white hover:bg-[#0C4B4B] hover:text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'"
                @click="selectedDateRange = 'yearly'"
              >
                Yearly
              </Button>
            </div>
          </PopoverContent>
        </Popover>
      </div>
    </div>

    <div
      v-if="isLoadingAnalytics"
      class="mb-4 flex items-center gap-2 rounded-md border border-[#A7F3D0] bg-[#ECFDF5] px-4 py-3 text-sm font-medium text-[#0F5C5C]"
    >
      <Loader2 class="h-4 w-4 animate-spin" />
      Loading analytics data...
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
      <StatCard
        class="border-l-4 border-teal-400"
        title="Total AICS Transactions"
        :value="stats.totalTransactions"
        :icon="Transactions"
        iconBg="bg-teal-100"
        iconColor="text-teal-600"
        numberColor="text-teal-600"
      />

      <StatCard
        class="border-l-4 border-emerald-400"
        title="Total AICS Assistance Distributed"
        :value="stats.totalAssistanceDistributed"
        :icon="Gift"
        iconBg="bg-emerald-100"
        iconColor="text-emerald-600"
        numberColor="text-emerald-600"
        prefix="PHP "
      />
    </div>

    <!-- AICS Assistance Distribution Row -->
    <div v-if="showAssistanceChart" class="mt-6">
      <div class="mb-3 flex items-center justify-between gap-2">
        <div class="flex items-center gap-1">
          <h2 class="text-lg font-semibold">AICS Assistance Distribution</h2>
          <TooltipProvider>
            <Tooltip>
              <TooltipTrigger as-child>
                <Info class="h-4 w-4 text-gray-400 cursor-help" />
              </TooltipTrigger>
              <TooltipContent class="min-w-56 rounded-md border border-gray-200 bg-white px-3 py-2 text-xs shadow-lg">
                <p class="font-semibold text-gray-900">Assistance Distribution</p>
                <p class="mt-1 text-gray-600">Shows the breakdown of monetary assistance provided to AICS clients by assistance type.</p>
              </TooltipContent>
            </Tooltip>
          </TooltipProvider>
        </div>
      </div>

      <Card class="w-full">
        <CardHeader class="flex flex-row items-start justify-end space-y-0 pt-4 px-4">
          <Select v-model="selectedBarangayId">
            <SelectTrigger class="w-[180px] bg-white">
              <SelectValue placeholder="All Barangay" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All Barangay</SelectItem>
              <SelectItem
                v-for="barangay in availableBarangays"
                :key="`barangay-${barangay.barangay_id}`"
                :value="barangay.barangay_id"
              >
                {{ barangay.barangay_name }}
              </SelectItem>
            </SelectContent>
          </Select>
        </CardHeader>
        <CardContent>
          <div class="h-[300px] w-full mt-2 flex items-center justify-center">
            <div
              class="relative h-52 w-52"
              @mousemove="handleAssistanceDonutMouseMove"
              @mouseleave="clearAssistanceHoverSegment"
            >
              <div class="h-full w-full rounded-full" :style="{ background: assistancePieGradient }"></div>
              <div class="absolute inset-6 rounded-full bg-white flex flex-col items-center justify-center px-2 text-center">
                <span class="text-[10px] font-semibold uppercase tracking-wide text-gray-500">Total Assistance Provided</span>
                <span class="mt-1 text-sm font-semibold text-gray-900">{{ assistanceTotalClients }} clients</span>
                <span class="text-xs font-medium text-emerald-700">{{ formatPesoAmount(assistanceTotalAssistance) }}</span>
              </div>

              <div
                v-if="hoveredAssistanceSegment"
                class="pointer-events-none absolute z-20 min-w-44 rounded-md border border-gray-200 bg-white px-3 py-2 text-xs shadow-lg"
                :style="{ left: `${assistanceTooltipPosition.x}px`, top: `${assistanceTooltipPosition.y}px`, transform: 'translate(8px, -110%)' }"
              >
                <p class="font-semibold text-gray-900">{{ hoveredAssistanceSegment.name }}</p>
                <p class="mt-1 text-gray-600">Clients: <span class="font-semibold">{{ hoveredAssistanceSegment.totalClients }}</span></p>
                <p class="text-gray-600">Total Amount: <span class="font-semibold">{{ formatPesoAmount(hoveredAssistanceSegment.totalAssistance) }}</span></p>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 mt-4 pt-4 border-t border-gray-100">
            <div
              v-for="(segment, index) in assistanceChartData"
              :key="`assistance-segment-${index}`"
              class="flex items-center gap-2 text-sm"
            >
              <div
                class="h-3 w-3 min-h-3 min-w-3 shrink-0 rounded-full"
                :style="{ backgroundColor: getAssistanceSegmentColor(index) }"
              ></div>
              <TooltipProvider>
                <Tooltip>
                  <TooltipTrigger as-child>
                    <span class="text-gray-600 truncate cursor-help">{{ segment.name }}</span>
                  </TooltipTrigger>
                  <TooltipContent class="min-w-44 rounded-md border border-gray-200 bg-white px-3 py-2 text-xs shadow-lg">
                    <p class="font-semibold text-gray-900">{{ segment.name }}</p>
                  </TooltipContent>
                </Tooltip>
              </TooltipProvider>
              <span class="text-gray-900 font-medium ml-auto">{{ formatPesoAmount(segment.totalAssistance) }}</span>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <div class="mt-8">
      <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <h2 class="text-xl font-semibold">AICS Queue Summary</h2>
        <Button
          type="button"
          class="h-10 px-4 bg-[#0F5C5C] hover:bg-[#0D4A4A] text-white flex items-center gap-2 whitespace-nowrap self-start sm:self-auto"
          :disabled="isExportingTable"
          @click="openExportTableModal"
        >
          <FileSpreadsheet class="h-4 w-4" />
          <span v-if="!isExportingTable">Export Table</span>
          <span v-else>Exporting...</span>
        </Button>
      </div>

      <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-4">
          <div class="w-full sm:w-64">
            <input
              v-model="queueSummarySearch"
              type="text"
              placeholder="Search queue summary..."
              class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-[#0F5C5C] focus:ring-1 focus:ring-[#0F5C5C] outline-none"
            >
          </div>
          <p class="text-sm text-gray-600 sm:ml-2">Search by queue number, client name, service, or barangay.</p>
        </div>

        <Table class="w-full table-fixed">
          <TableHeader>
            <TableRow>
              <TableHead class="w-[11.11%]">
                <button type="button" class="inline-flex items-center gap-1 text-left" @click="toggleQueueSummarySort('queueNumber')">
                  Queue Number
                  <ArrowUpDown
                    class="h-4 w-4 shrink-0"
                    :class="queueSummarySortKey === 'queueNumber' ? 'text-[#0F5C5C]' : 'text-gray-400'"
                  />
                </button>
              </TableHead>
              <TableHead class="w-[11.11%]">
                <button type="button" class="inline-flex items-center gap-1 text-left" @click="toggleQueueSummarySort('clientName')">
                  Client Name
                  <ArrowUpDown
                    class="h-4 w-4 shrink-0"
                    :class="queueSummarySortKey === 'clientName' ? 'text-[#0F5C5C]' : 'text-gray-400'"
                  />
                </button>
              </TableHead>
              <TableHead class="w-[11.11%]">
                <button type="button" class="inline-flex items-center gap-1 text-left" @click="toggleQueueSummarySort('barangay')">
                  Barangay
                  <ArrowUpDown
                    class="h-4 w-4 shrink-0"
                    :class="queueSummarySortKey === 'barangay' ? 'text-[#0F5C5C]' : 'text-gray-400'"
                  />
                </button>
              </TableHead>
              <TableHead class="w-[11.11%]">
                <button type="button" class="inline-flex items-center gap-1 text-left" @click="toggleQueueSummarySort('contactNumber')">
                  Contact Number
                  <ArrowUpDown
                    class="h-4 w-4 shrink-0"
                    :class="queueSummarySortKey === 'contactNumber' ? 'text-[#0F5C5C]' : 'text-gray-400'"
                  />
                </button>
              </TableHead>
              <TableHead class="w-[11.11%]">
                <button type="button" class="inline-flex items-center gap-1 text-left" @click="toggleQueueSummarySort('serviceCode')">
                  Service
                  <ArrowUpDown
                    class="h-4 w-4 shrink-0"
                    :class="queueSummarySortKey === 'serviceCode' ? 'text-[#0F5C5C]' : 'text-gray-400'"
                  />
                </button>
              </TableHead>
              <TableHead class="w-[11.11%]">
                <button type="button" class="inline-flex items-center gap-1 text-left" @click="toggleQueueSummarySort('laneType')">
                  Lane Type
                  <ArrowUpDown
                    class="h-4 w-4 shrink-0"
                    :class="queueSummarySortKey === 'laneType' ? 'text-[#0F5C5C]' : 'text-gray-400'"
                  />
                </button>
              </TableHead>
              <TableHead class="w-[11.11%]">
                <button type="button" class="inline-flex items-center gap-1 text-left" @click="toggleQueueSummarySort('status')">
                  Status
                  <ArrowUpDown
                    class="h-4 w-4 shrink-0"
                    :class="queueSummarySortKey === 'status' ? 'text-[#0F5C5C]' : 'text-gray-400'"
                  />
                </button>
              </TableHead>
              <TableHead class="w-[11.11%]">
                <button type="button" class="inline-flex items-center gap-1 text-left" @click="toggleQueueSummarySort('completionTime')">
                  Completion Date and Time
                  <ArrowUpDown
                    class="h-4 w-4 shrink-0"
                    :class="queueSummarySortKey === 'completionTime' ? 'text-[#0F5C5C]' : 'text-gray-400'"
                  />
                </button>
              </TableHead>
              <TableHead class="w-[5%]"></TableHead>
            </TableRow>
          </TableHeader>

          <TableBody>
            <TableRow v-for="entry in paginatedQueueSummaryRows" :key="entry.id">
              <TableCell class="font-medium">{{ entry.queueNumber }}</TableCell>
              <TableCell>{{ entry.clientName }}</TableCell>
              <TableCell>{{ entry.barangay || 'N/A' }}</TableCell>
              <TableCell>{{ entry.contactNumber || 'N/A' }}</TableCell>
              <TableCell>
                <TooltipProvider>
                  <Tooltip>
                    <TooltipTrigger as-child>
                      <span class="cursor-default truncate block">
                        {{ entry.serviceCode || 'N/A' }}
                      </span>
                    </TooltipTrigger>
                    <TooltipContent class="max-w-xs whitespace-pre-line text-white">
                      <p class="font-semibold text-white mb-1">Service name(s)</p>
                      <p v-if="!entry.serviceNames || !entry.serviceNames.length" class="text-gray-100 text-xs">No service names available</p>
                      <ul v-else class="list-disc list-inside text-xs text-gray-100 space-y-0.5">
                        <li v-for="(name, idx) in entry.serviceNames" :key="idx">{{ name }}</li>
                      </ul>
                    </TooltipContent>
                  </Tooltip>
                </TooltipProvider>
              </TableCell>
              <TableCell>{{ entry.laneType }}</TableCell>
              <TableCell>
                <span
                  class="rounded-full px-2 py-1 text-xs font-medium"
                  :class="entry.status === 'Completed' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                >
                  {{ entry.status }}
                </span>
              </TableCell>
              <TableCell>
                <div class="space-y-1">
                  <div>{{ entry.completionTime || 'N/A' }}</div>
                  <div v-if="entry.completionTime && formatRelativeCompletionTime(entry.completionTime)" class="text-xs italic text-gray-500">
                    {{ formatRelativeCompletionTime(entry.completionTime) }}
                  </div>
                </div>
              </TableCell>
              <TableCell class="text-right">
                <button
                  type="button"
                  class="inline-flex items-center justify-center rounded-md p-1 text-gray-500 hover:text-gray-700 hover:bg-gray-100"
                  @click="openQueueDetails(entry)"
                >
                  <MoreHorizontal class="w-4 h-4" />
                </button>
              </TableCell>
            </TableRow>

            <TableRow v-if="!isLoadingQueueSummary && paginatedQueueSummaryRows.length === 0">
              <TableCell colspan="9" class="text-center text-gray-500 py-8">
                No queue summary records found.
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>

        <div class="flex items-center justify-between mt-3">
          <p class="text-sm text-gray-500">
            {{ queueSummaryStartRow }}-{{ queueSummaryEndRow }} of {{ queueSummaryTotalRows }} row(s) shown.
          </p>

          <div class="flex items-center gap-6">
            <p class="text-sm text-gray-600 whitespace-nowrap">
              Page {{ currentQueueSummaryPage }} of {{ queueSummaryTotalPages }}
            </p>

            <Pagination>
              <PaginationContent>
                <PaginationItem>
                  <PaginationFirst @click="firstQueueSummaryPage" :disabled="currentQueueSummaryPage === 1" />
                </PaginationItem>

                <PaginationItem>
                  <PaginationPrevious @click="previousQueueSummaryPage" :disabled="currentQueueSummaryPage === 1" />
                </PaginationItem>

                <PaginationItem>
                  <PaginationNext @click="nextQueueSummaryPage" :disabled="currentQueueSummaryPage === queueSummaryTotalPages" />
                </PaginationItem>

                <PaginationItem>
                  <PaginationLast @click="lastQueueSummaryPage" :disabled="currentQueueSummaryPage === queueSummaryTotalPages" />
                </PaginationItem>
              </PaginationContent>
            </Pagination>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showExportTableModal" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="absolute inset-0 bg-black/60" @click="closeExportTableModal"></div>
      <div class="relative bg-white rounded-lg shadow-2xl w-full max-w-lg p-6 z-10 mx-4">
        <button
          class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 transition-colors cursor-pointer"
          type="button"
          :disabled="isExportingTable"
          @click="closeExportTableModal"
        >
          <span class="sr-only">Close</span>
          ×
        </button>

        <h2 class="text-xl font-semibold text-gray-900 mb-3">Export AICS Queue Summary Table</h2>
        <p class="text-sm text-gray-600 mb-6">
          This will generate an Excel file containing the AICS queue summary table for
          <span class="font-semibold">{{ dateFilterLabel }}</span>.
          Only completed AICS queue transactions will be included in the export.
        </p>

        <div class="flex justify-end gap-3">
          <button
            class="px-4 py-2 rounded-sm border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-medium cursor-pointer"
            type="button"
            :disabled="isExportingTable"
            @click="closeExportTableModal"
          >
            Cancel
          </button>
          <Button
            class="px-4 py-2 bg-[#0F5C5C] hover:bg-[#0D4A4A] text-white text-sm font-medium"
            type="button"
            :disabled="isExportingTable"
            @click="confirmExportTable"
          >
            <span v-if="!isExportingTable">Export Table</span>
            <span v-else>Exporting...</span>
          </Button>
        </div>
      </div>
    </div>

    <div v-if="showQueueDetailsModal" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="absolute inset-0 bg-black/60" @click="closeQueueDetails"></div>
      <div class="relative bg-white rounded-lg w-full max-w-3xl mx-4 py-6 px-6 sm:px-8 shadow-2xl max-h-[90vh] overflow-y-auto z-10">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg sm:text-xl font-semibold text-gray-900">Queue Details</h2>
          <button type="button" class="text-gray-400 hover:text-gray-600" @click="closeQueueDetails">
            <span class="sr-only">Close</span>
            ×
          </button>
        </div>

        <div v-if="selectedQueueEntry" class="space-y-5">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="rounded-md border border-gray-200 bg-gray-50 px-3 py-2">
              <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Queue Number</p>
              <p class="mt-1 text-sm font-semibold text-gray-900">{{ selectedQueueEntry.queueNumber }}</p>
            </div>
            <div class="rounded-md border border-gray-200 bg-gray-50 px-3 py-2">
              <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Client Name</p>
              <p class="mt-1 text-sm font-semibold text-gray-900">{{ selectedQueueEntry.clientName || 'N/A' }}</p>
            </div>
            <div class="rounded-md border border-gray-200 bg-gray-50 px-3 py-2">
              <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Sex</p>
              <p class="mt-1 text-sm font-semibold text-gray-900">{{ selectedQueueEntry.sex || 'N/A' }}</p>
            </div>
            <div class="rounded-md border border-gray-200 bg-gray-50 px-3 py-2">
              <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Age</p>
              <p class="mt-1 text-sm font-semibold text-gray-900">{{ selectedQueueEntry.age ?? 'N/A' }}</p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="rounded-md border border-gray-200 bg-gray-50 px-3 py-2">
              <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Barangay</p>
              <p class="mt-1 text-sm font-semibold text-gray-900">{{ selectedQueueEntry.barangay || 'N/A' }}</p>
            </div>
            <div class="rounded-md border border-gray-200 bg-gray-50 px-3 py-2">
              <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Contact Number</p>
              <p class="mt-1 text-sm font-semibold text-gray-900">{{ selectedQueueEntry.contactNumber || 'N/A' }}</p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="rounded-md border border-gray-200 bg-gray-50 px-3 py-2">
              <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Service</p>
              <p class="mt-1 text-sm font-semibold text-gray-900">{{ selectedQueueEntry.serviceCode || 'N/A' }}</p>
              <p v-if="selectedQueueEntry.serviceNames && selectedQueueEntry.serviceNames.length" class="mt-1 text-xs text-gray-600">
                {{ selectedQueueEntry.serviceNames.join(', ') }}
              </p>
            </div>
            <div class="rounded-md border border-gray-200 bg-gray-50 px-3 py-2">
              <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Lane Type</p>
              <p class="mt-1 text-sm font-semibold text-gray-900">{{ formattedLaneType }}</p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="rounded-md border border-gray-200 bg-gray-50 px-3 py-2">
              <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Status</p>
              <p class="mt-1 text-sm font-semibold" :class="selectedQueueEntry.status === 'Completed' ? 'text-green-700' : 'text-red-700'">
                {{ selectedQueueEntry.status }}
              </p>
            </div>
            <div class="rounded-md border border-gray-200 bg-gray-50 px-3 py-2">
              <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Completion Date and Time</p>
              <div class="mt-1 space-y-1">
                <p class="text-sm font-semibold text-gray-900">{{ selectedQueueEntry.completionTime || 'N/A' }}</p>
                <p v-if="selectedQueueEntry.completionTime && formatRelativeCompletionTime(selectedQueueEntry.completionTime)" class="text-xs italic text-gray-500">
                  {{ formatRelativeCompletionTime(selectedQueueEntry.completionTime) }}
                </p>
              </div>
            </div>
            <div class="rounded-md border border-gray-200 bg-gray-50 px-3 py-2">
              <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Average Satisfaction Rating</p>
              <p class="mt-1 text-sm font-semibold text-gray-900">{{ selectedQueueEntry.averageSatisfactionRating || 'N/A' }}</p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="rounded-md border border-gray-200 bg-gray-50 px-3 py-2">
              <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Waiting Time</p>
              <p class="mt-1 text-sm font-semibold text-gray-900">
                {{ selectedQueueEntry.averageWaitingTime != null ? `${selectedQueueEntry.averageWaitingTime} min` : 'N/A' }}
              </p>
            </div>
            <div class="rounded-md border border-gray-200 bg-gray-50 px-3 py-2">
              <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Service Time</p>
              <p class="mt-1 text-sm font-semibold text-gray-900">
                {{ selectedQueueEntry.averageServingTime != null ? `${selectedQueueEntry.averageServingTime} min` : 'N/A' }}
              </p>
            </div>
          </div>

          <div v-if="selectedQueueEntry.serviceAssistanceDetails && selectedQueueEntry.serviceAssistanceDetails.length > 0" class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-700">Service Assistance Details</p>
            <div class="mt-2 space-y-2">
              <div v-for="assist in selectedQueueEntry.serviceAssistanceDetails" :key="assist.service_id" class="text-sm text-emerald-900">
                <p class="font-medium">{{ assist.service_name }}: ₱{{ Number(assist.assistance_provided).toFixed(2) }}</p>
                <p v-if="assist.assistance_provided_at" class="text-xs text-emerald-800">
                  Recorded on: {{ assist.assistance_provided_at }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-6 flex justify-end">
          <Button type="button" variant="outline" class="px-4" @click="closeQueueDetails">
            Close
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { Undo2 as Transactions, Gift, Info, Calendar, ChevronLeft, ChevronRight, Loader2, MoreHorizontal, ArrowUpDown, FileSpreadsheet } from 'lucide-vue-next'
import StatCard from '@/components/common/StatCard.vue'
import { Button } from '@/components/ui/button'
import {
  Card,
  CardContent,
  CardHeader,
} from '@/components/ui/card'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  Pagination,
  PaginationContent,
  PaginationItem,
  PaginationNext,
  PaginationPrevious,
  PaginationFirst,
  PaginationLast,
} from '@/components/ui/pagination'
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip'
import api from '@/services/api'

const stats = ref({
  totalTransactions: 0,
  totalAssistanceDistributed: 0,
})

const selectedDateRange = ref('daily')
const isDateFilterOpen = ref(false)
const isLoadingAnalytics = ref(false)
const isApplyingDateFilter = ref(false)
const isLoadingQueueSummary = ref(false)
const showExportTableModal = ref(false)
const isExportingTable = ref(false)
const showQueueDetailsModal = ref(false)
const selectedQueueEntry = ref(null)

const bodyOriginalOverflow = ref('')
const htmlOriginalOverflow = ref('')

const lockPageScroll = () => {
  if (typeof document === 'undefined') return

  bodyOriginalOverflow.value = document.body.style.overflow
  htmlOriginalOverflow.value = document.documentElement.style.overflow
  document.body.style.overflow = 'hidden'
  document.documentElement.style.overflow = 'hidden'
}

const unlockPageScroll = () => {
  if (typeof document === 'undefined') return

  document.body.style.overflow = bodyOriginalOverflow.value
  document.documentElement.style.overflow = htmlOriginalOverflow.value
}

watch(isDateFilterOpen, (isOpen) => {
  if (isOpen) {
    lockPageScroll()
    return
  }
  unlockPageScroll()
})

onBeforeUnmount(() => {
  unlockPageScroll()
})

const now = new Date()
const selectedDate = ref(new Date(now.getFullYear(), now.getMonth(), now.getDate()))
const selectedMonthIndex = ref(now.getMonth())
const selectedMonthYear = ref(now.getFullYear())
const selectedYear = ref(String(now.getFullYear()))
const dailyViewMonth = ref(now.getMonth())
const dailyViewYear = ref(now.getFullYear())

const monthNames = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December',
]
const weekDayLabels = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa']

const dateFilterLabel = computed(() => {
  if (selectedDateRange.value === 'daily') {
    return selectedDate.value.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
    })
  }

  if (selectedDateRange.value === 'monthly') {
    return `${monthNames[selectedMonthIndex.value]} ${selectedMonthYear.value}`
  }

  return selectedYear.value
})

const dailyHeaderLabel = computed(() => `${monthNames[dailyViewMonth.value]} ${dailyViewYear.value}`)

const yearOptions = computed(() => {
  const years = []
  const currentYear = new Date().getFullYear()
  for (let year = currentYear + 2; year >= currentYear - 60; year -= 1) {
    years.push(year)
  }
  return years
})

const dailyCalendarCells = computed(() => {
  const firstDayOfMonth = new Date(dailyViewYear.value, dailyViewMonth.value, 1).getDay()
  const totalDays = new Date(dailyViewYear.value, dailyViewMonth.value + 1, 0).getDate()

  const cells = []
  for (let i = 0; i < firstDayOfMonth; i += 1) {
    cells.push(null)
  }
  for (let day = 1; day <= totalDays; day += 1) {
    cells.push(day)
  }
  while (cells.length % 7 !== 0) {
    cells.push(null)
  }

  return cells
})

const goToPrevDailyMonth = () => {
  if (dailyViewMonth.value === 0) {
    dailyViewMonth.value = 11
    dailyViewYear.value -= 1
    return
  }
  dailyViewMonth.value -= 1
}

const goToNextDailyMonth = () => {
  if (dailyViewMonth.value === 11) {
    dailyViewMonth.value = 0
    dailyViewYear.value += 1
    return
  }
  dailyViewMonth.value += 1
}

const selectDailyDate = (day) => {
  selectedDate.value = new Date(dailyViewYear.value, dailyViewMonth.value, day)
}

const isSelectedDay = (day) => {
  return selectedDate.value.getFullYear() === dailyViewYear.value
    && selectedDate.value.getMonth() === dailyViewMonth.value
    && selectedDate.value.getDate() === day
}

const assistanceColorPalette = ['#2563EB', '#16A34A', '#F59E0B', '#DC2626', '#7C3AED', '#0891B2', '#D946EF', '#4F46E5', '#EA580C', '#15803D']

const queueSummaryRowsPerPage = 10
const currentQueueSummaryPage = ref(1)
const queueSummarySearch = ref('')
const queueSummarySortKey = ref('completionTime')
const queueSummarySortDirection = ref('desc')
const queueSummaryPagination = ref({
  currentPage: 1,
  perPage: queueSummaryRowsPerPage,
  totalRows: 0,
  totalPages: 1,
  startRow: 0,
  endRow: 0,
})
const queueSummaryRows = ref([])

const queueSummaryTotalRows = computed(() => queueSummaryPagination.value.totalRows)
const queueSummaryTotalPages = computed(() => Math.max(1, queueSummaryPagination.value.totalPages))
const filteredQueueSummaryRows = computed(() => {
  const query = queueSummarySearch.value.trim().toLowerCase()
  if (!query) return queueSummaryRows.value

  return queueSummaryRows.value.filter((row) => {
    const queueNumber = String(row.queueNumber || '').toLowerCase()
    const clientName = String(row.clientName || '').toLowerCase()
    const serviceCode = String(row.serviceCode || '').toLowerCase()
    const barangay = String(row.barangay || '').toLowerCase()

    return queueNumber.includes(query)
      || clientName.includes(query)
      || serviceCode.includes(query)
      || barangay.includes(query)
  })
})

const getQueueSummarySortValue = (row, key) => {
  if (key === 'completionTime') {
    const parsed = Date.parse(row.completionTime || '')
    return Number.isNaN(parsed) ? -1 : parsed
  }

  return String(row[key] ?? '').toLowerCase()
}

const paginatedQueueSummaryRows = computed(() => {
  const rows = [...filteredQueueSummaryRows.value]

  if (!queueSummarySortKey.value) {
    return rows
  }

  const direction = queueSummarySortDirection.value === 'asc' ? 1 : -1
  const sortKey = queueSummarySortKey.value

  rows.sort((a, b) => {
    const valueA = getQueueSummarySortValue(a, sortKey)
    const valueB = getQueueSummarySortValue(b, sortKey)

    if (valueA < valueB) return -1 * direction
    if (valueA > valueB) return 1 * direction
    return 0
  })

  return rows
})

const queueSummaryStartRow = computed(() => queueSummaryPagination.value.startRow)
const queueSummaryEndRow = computed(() => queueSummaryPagination.value.endRow)

const showAssistanceChart = ref(false)
const availableBarangays = ref([])
const selectedBarangayId = ref('all')
const assistanceDistributionData = ref([])
const assistanceDistributionSummary = ref({ total_clients: 0, total_assistance: 0 })
const hoveredAssistanceSegment = ref(null)
const assistanceTooltipPosition = ref({ x: 0, y: 0 })

const assistanceChartData = computed(() => {
  const totalAssistance = Number(assistanceDistributionSummary.value?.total_assistance || 0)
  if (!assistanceDistributionData.value.length) {
    return []
  }

  return assistanceDistributionData.value.map((segment) => {
    const amount = Number(segment.total_assistance ?? 0)
    const percentage = totalAssistance > 0
      ? Number(((amount / totalAssistance) * 100).toFixed(2))
      : 0

    return {
      serviceId: segment.service_id ?? null,
      assistanceTypeId: segment.assistance_type_id ?? null,
      name: segment.label
        || (segment.assistance_type_name
          ? `${segment.service_name} (${segment.assistance_type_name})`
          : segment.service_name),
      totalClients: Number(segment.total_clients ?? 0),
      totalAssistance: amount,
      percentage,
    }
  })
})

const assistanceTotalClients = computed(() => Number(assistanceDistributionSummary.value?.total_clients || 0))
const assistanceTotalAssistance = computed(() => Number(assistanceDistributionSummary.value?.total_assistance || 0))

const assistancePieGradient = computed(() => {
  if (!assistanceChartData.value.length) {
    return 'conic-gradient(#E5E7EB 0% 100%)'
  }

  let current = 0
  const slices = assistanceChartData.value.map((segment, index) => {
    const start = current
    const end = current + segment.percentage
    current = end
    return `${getAssistanceSegmentColor(index)} ${start}% ${end}%`
  })

  return `conic-gradient(${slices.join(', ')})`
})

const getAssistanceSegmentColor = (index) => assistanceColorPalette[index % assistanceColorPalette.length]

const toggleQueueSummarySort = (key) => {
  if (queueSummarySortKey.value === key) {
    queueSummarySortDirection.value = queueSummarySortDirection.value === 'asc' ? 'desc' : 'asc'
    return
  }

  queueSummarySortKey.value = key
  queueSummarySortDirection.value = 'asc'
}

const getAssistanceSegmentByPercent = (percent) => {
  let cumulative = 0

  for (const segment of assistanceChartData.value) {
    cumulative += segment.percentage
    if (percent <= cumulative) {
      return segment
    }
  }

  return assistanceChartData.value[assistanceChartData.value.length - 1] || null
}

const handleAssistanceDonutMouseMove = (event) => {
  const rect = event.currentTarget.getBoundingClientRect()
  const centerX = rect.width / 2
  const centerY = rect.height / 2
  const x = event.clientX - rect.left
  const y = event.clientY - rect.top

  const dx = x - centerX
  const dy = y - centerY
  const distance = Math.sqrt(dx * dx + dy * dy)

  // Only detect hover within the donut (between 40% and 100% of radius)
  const radius = Math.min(centerX, centerY)
  const normalizedDistance = distance / radius

  if (normalizedDistance >= 0.4 && normalizedDistance <= 1) {
    let angle = Math.atan2(dy, dx) * (180 / Math.PI) + 90
    if (angle < 0) angle += 360

    const percent = angle / 3.6
    hoveredAssistanceSegment.value = getAssistanceSegmentByPercent(percent)
    assistanceTooltipPosition.value = { x: event.clientX - rect.left, y: event.clientY - rect.top }
  } else {
    hoveredAssistanceSegment.value = null
  }
}

const clearAssistanceHoverSegment = () => {
  hoveredAssistanceSegment.value = null
}

const formatPesoAmount = (value) => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
  }).format(Number(value || 0))
}

const formatDate = (date) => {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const formatRelativeCompletionTime = (completionTime) => {
  const completionDate = new Date(completionTime)
  if (Number.isNaN(completionDate.getTime())) {
    return null
  }

  const nowDate = new Date()
  const diffMs = nowDate.getTime() - completionDate.getTime()

  if (diffMs < 0) {
    return '0 minutes ago'
  }

  const diffMinutes = Math.floor(diffMs / (1000 * 60))
  const diffHours = Math.floor(diffMs / (1000 * 60 * 60))
  const diffDaysTotal = Math.floor(diffMs / (1000 * 60 * 60 * 24))
  const startOfToday = new Date(nowDate.getFullYear(), nowDate.getMonth(), nowDate.getDate())
  const startOfCompletionDay = new Date(
    completionDate.getFullYear(),
    completionDate.getMonth(),
    completionDate.getDate(),
  )
  const diffDays = Math.floor((startOfToday.getTime() - startOfCompletionDay.getTime()) / (1000 * 60 * 60 * 24))
  const diffMonths = Math.floor(diffDaysTotal / 30)

  const isSameCalendarDay = nowDate.getFullYear() === completionDate.getFullYear()
    && nowDate.getMonth() === completionDate.getMonth()
    && nowDate.getDate() === completionDate.getDate()

  if (isSameCalendarDay) {
    if (diffHours < 1) {
      return `${Math.max(1, diffMinutes)} minute${Math.max(1, diffMinutes) === 1 ? '' : 's'} ago`
    }
    return `${Math.max(1, diffHours)} hour${Math.max(1, diffHours) === 1 ? '' : 's'} ago`
  }

  if (diffMonths < 1) {
    return `${Math.max(1, diffDays)} day${Math.max(1, diffDays) === 1 ? '' : 's'} ago`
  }

  return `${Math.max(1, diffMonths)} month${Math.max(1, diffMonths) === 1 ? '' : 's'} ago`
}

const getDateFilterParams = () => {
  if (selectedDateRange.value === 'monthly') {
    return {
      period: 'monthly',
      month: selectedMonthIndex.value + 1,
      year: selectedMonthYear.value,
    }
  }

  if (selectedDateRange.value === 'yearly') {
    return {
      period: 'yearly',
      year: Number(selectedYear.value),
    }
  }

  return {
    period: 'daily',
    date: formatDate(selectedDate.value),
  }
}

const fetchCardStats = async () => {
  try {
    const response = await api.get('/cswdo-focal/analytics/cards', {
      params: getDateFilterParams(),
    })

    const payload = response?.data?.data || response?.data || {}
    stats.value = {
      totalTransactions: Number(payload.totalTransactions ?? payload.total_transactions ?? 0),
      totalAssistanceDistributed: Number(payload.totalAssistanceDistributed ?? payload.total_assistance_distributed ?? 0),
    }
  } catch (error) {
    console.error('Failed to fetch card stats:', error)
    stats.value = {
      totalTransactions: 0,
      totalAssistanceDistributed: 0,
    }
  }
}

const fetchAssistanceDistribution = async () => {
  try {
    const response = await api.get('/cswdo-focal/analytics/assistance-distribution', {
      params: {
        ...getDateFilterParams(),
        barangay_id: selectedBarangayId.value === 'all' ? null : selectedBarangayId.value,
      }
    })

    const payload = response?.data?.data || {}
    showAssistanceChart.value = Boolean(payload.has_assistance_services)
    availableBarangays.value = Array.isArray(payload.available_barangays)
      ? payload.available_barangays
      : []
    assistanceDistributionData.value = payload.distribution?.length ? payload.distribution : []
    assistanceDistributionSummary.value = {
      total_clients: Number(payload.summary?.total_clients ?? 0),
      total_assistance: Number(payload.summary?.total_assistance ?? 0),
    }
    hoveredAssistanceSegment.value = null
  } catch (error) {
    console.error('Failed to fetch assistance distribution:', error)
    showAssistanceChart.value = false
    availableBarangays.value = []
    assistanceDistributionData.value = []
    assistanceDistributionSummary.value = { total_clients: 0, total_assistance: 0 }
  }
}

const fetchQueueSummary = async () => {
  isLoadingQueueSummary.value = true
  try {
    const response = await api.get('/cswdo-focal/analytics/queue-summary', {
      params: {
        ...getDateFilterParams(),
        page: currentQueueSummaryPage.value,
        per_page: queueSummaryRowsPerPage,
      },
    })

    const payload = response?.data?.data || {}
    const rows = payload.rows || []

    queueSummaryRows.value = rows.map((row) => ({
      id: row.id,
      queueNumber: row.queue_number,
      clientName: row.client_name,
      barangay: row.barangay_name,
      contactNumber: row.contact_number,
      serviceCode: row.service_code,
      serviceNames: row.service_names || [],
      laneType: row.lane_type,
      prioritySectors: row.priority_sectors || [],
      status: row.status,
      completionTime: row.completion_time,
      averageWaitingTime: row.waiting_time ?? 0,
      averageServingTime: row.service_time ?? 0,
      averageSatisfactionRating: row.average_satisfaction_rating,
      sex: row.sex,
      age: row.age,
      serviceAssistanceDetails: row.service_assistance_details || [],
    }))

    const pagination = payload.pagination || {}
    queueSummaryPagination.value = {
      currentPage: pagination.current_page ?? 1,
      perPage: pagination.per_page ?? queueSummaryRowsPerPage,
      totalRows: pagination.total_rows ?? 0,
      totalPages: pagination.total_pages ?? 1,
      startRow: pagination.start_row ?? 0,
      endRow: pagination.end_row ?? 0,
    }
  } catch (error) {
    console.error('Error fetching queue summary:', error)
    queueSummaryRows.value = []
    queueSummaryPagination.value = {
      currentPage: 1,
      perPage: queueSummaryRowsPerPage,
      totalRows: 0,
      totalPages: 1,
      startRow: 0,
      endRow: 0,
    }
  } finally {
    isLoadingQueueSummary.value = false
  }
}

const formattedLaneType = computed(() => {
  if (!selectedQueueEntry.value) return ''
  if (!selectedQueueEntry.value.laneType || selectedQueueEntry.value.laneType === 'Regular') {
    return 'Regular'
  }

  const sectors = selectedQueueEntry.value.prioritySectors || []
  if (!sectors.length) return 'Priority'

  return `Priority - ${sectors.join(', ')}`
})

const openQueueDetails = (entry) => {
  selectedQueueEntry.value = entry
  showQueueDetailsModal.value = true
}

const openExportTableModal = () => {
  showExportTableModal.value = true
}

const closeExportTableModal = () => {
  if (isExportingTable.value) return
  showExportTableModal.value = false
}

const confirmExportTable = async () => {
  if (isExportingTable.value) return

  isExportingTable.value = true

  try {
    const response = await api.get('/cswdo-focal/analytics/queue-summary/export', {
      params: getDateFilterParams(),
      responseType: 'blob',
    })

    const blob = new Blob([response.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' })
    const safeFileNameDate = dateFilterLabel.value.replace(/[\\/]/g, '-')
    const fileName = `AICS Queue Data Summary - ${safeFileNameDate}.xlsx`

    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = fileName
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)

    showExportTableModal.value = false
  } catch (error) {
    console.error('Error exporting AICS queue summary table:', error)
    window.alert('Failed to generate Excel report. Please try again.')
  } finally {
    isExportingTable.value = false
  }
}

const closeQueueDetails = () => {
  showQueueDetailsModal.value = false
  selectedQueueEntry.value = null
}

const firstQueueSummaryPage = () => {
  if (currentQueueSummaryPage.value === 1) return
  currentQueueSummaryPage.value = 1
}

const previousQueueSummaryPage = () => {
  if (currentQueueSummaryPage.value > 1) {
    currentQueueSummaryPage.value -= 1
  }
}

const nextQueueSummaryPage = () => {
  if (currentQueueSummaryPage.value < queueSummaryTotalPages.value) {
    currentQueueSummaryPage.value += 1
  }
}

const lastQueueSummaryPage = () => {
  if (currentQueueSummaryPage.value === queueSummaryTotalPages.value) return
  currentQueueSummaryPage.value = queueSummaryTotalPages.value
}

const fetchAnalyticsData = async () => {
  isLoadingAnalytics.value = true
  try {
    await Promise.all([
      fetchCardStats(),
      fetchAssistanceDistribution(),
      fetchQueueSummary(),
    ])
  } catch (error) {
    console.error('Error fetching analytics data:', error)
  } finally {
    isLoadingAnalytics.value = false
  }
}

const applyDateFilterAndReload = async () => {
  isApplyingDateFilter.value = true
  await fetchAnalyticsData()
  isApplyingDateFilter.value = false
}

onMounted(() => {
  fetchAnalyticsData()
})

watch(selectedBarangayId, () => {
  if (isLoadingAnalytics.value) return
  fetchAssistanceDistribution()
})

watch(currentQueueSummaryPage, () => {
  if (isApplyingDateFilter.value) return
  fetchQueueSummary()
})

watch(selectedDateRange, () => {
  applyDateFilterAndReload()
})

watch(selectedDate, () => {
  if (selectedDateRange.value !== 'daily') return
  applyDateFilterAndReload()
})

watch([selectedMonthIndex, selectedMonthYear], () => {
  if (selectedDateRange.value !== 'monthly') return
  applyDateFilterAndReload()
})

watch(selectedYear, () => {
  if (selectedDateRange.value !== 'yearly') return
  applyDateFilterAndReload()
})

</script>


