<template>
  <div class="max-w-7xl mx-auto px-2 sm:px-2 lg:px-2 py-2">
    <!-- Header with title and controls -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
      <h1 class="text-2xl font-semibold">Queue Analytics</h1>

      <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full sm:w-auto">
        <!-- Date Filter Dropdown -->
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
          <!-- Daily -->
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

          <!-- Monthly -->
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

          <!-- Yearly -->
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

        <Button
          class="px-4 py-2 bg-[#0F5C5C] hover:bg-[#0D4A4A] text-white whitespace-nowrap flex items-center gap-2"
          type="button"
          @click="openExportModal"
        >
          <BarChart3 class="h-4 w-4" />
          Generate Graph
        </Button>
      </div>
    </div>

    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="showExportModal" class="fixed inset-0 z-50 flex items-center justify-center">
          <div class="absolute inset-0 bg-black/60" @click="closeExportModal"></div>
          <div class="relative bg-white rounded-lg shadow-2xl w-full max-w-lg p-6 z-10 mx-4">
            <button
              class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 transition-colors cursor-pointer"
              type="button"
              @click="closeExportModal"
            >
              <span class="sr-only">Close</span>
              ×
            </button>

            <h2 class="text-xl font-semibold text-gray-900 mb-3">Export Analytics Graphs</h2>
            <p class="text-sm text-gray-600 mb-6">
              This will generate a PDF report containing the current queue analytics stat cards and graphs
              for
              <span class="font-semibold">{{ exportOfficeDisplayName }}</span>
              for
              <span class="font-semibold">{{ dateFilterLabel }}</span>.
            </p>

            <div class="flex justify-end gap-3">
              <button
                class="px-4 py-2 rounded-sm border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-medium cursor-pointer"
                type="button"
                :disabled="isExportingGraphs"
                @click="closeExportModal"
              >
                Cancel
              </button>
              <Button
                class="px-4 py-2 bg-[#0F5C5C] hover:bg-[#0D4A4A] text-white text-sm font-medium"
                type="button"
                :disabled="isExportingGraphs"
                @click="confirmExportGraphs"
              >
                <span v-if="!isExportingGraphs">Generate PDF</span>
                <span v-else>Generating PDF...</span>
              </Button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <div
      v-if="isLoadingAnalytics"
      class="mb-4 flex items-center gap-2 rounded-md border border-[#A7F3D0] bg-[#ECFDF5] px-4 py-3 text-sm font-medium text-[#0F5C5C]"
    >
      <Loader2 class="h-4 w-4 animate-spin" />
      Loading analytics data...
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5">
      <StatCard
        class="border-l-4 border-orange-300"
        title="Total Clients"
        :value="stats.totalClients"
        :icon="Users"
        iconBg="bg-orange-100"
        iconColor="text-orange-500"
        numberColor="text-orange-500"
      />

      <StatCard
        class="border-l-4 border-green-400"
        title="Total Served"
        :value="stats.totalServed"
        :icon="UserCheck"
        iconBg="bg-green-100"
        iconColor="text-green-600"
        numberColor="text-green-600"
      />

      <StatCard
        class="border-l-4 border-red-400"
        title="Total Skipped"
        :value="stats.totalSkipped"
        :icon="XCircle"
        iconBg="bg-red-100"
        iconColor="text-red-600"
        numberColor="text-red-600"
      />

      <StatCard
        class="border-l-4 border-blue-400"
        title="Average Waiting Time"
        :value="stats.averageWaitingTime"
        :icon="Clock3"
        iconBg="bg-blue-100"
        iconColor="text-blue-600"
        numberColor="text-blue-600"
        suffix=" min"
      />

      <StatCard
        class="border-l-4 border-purple-400"
        title="Average Service Time"
        :value="stats.averageServiceTime"
        :icon="Hourglass"
        iconBg="bg-purple-100"
        iconColor="text-purple-600"
        numberColor="text-purple-600"
        suffix=" min"
      />
    </div>

    <!-- Barangay Distribution Row -->
    <div class="mt-6">
      <div class="mb-3 flex items-center gap-1">
        <h2 class="text-lg font-semibold">Barangay Distribution</h2>
        <CsmMetricExplanation
          :title="queueMetricExplanations.clientSatisfaction.title"
          :meaning="queueMetricExplanations.clientSatisfaction.meaning"
          :computation="queueMetricExplanations.clientSatisfaction.computation"
          :formula="queueMetricExplanations.clientSatisfaction.formula"
          :interpretation="queueMetricExplanations.clientSatisfaction.interpretation"
        />
      </div>
      <Card class="w-full">
        <CardContent class="pt-6">
          <div class="h-[320px] w-full">
            <div class="h-full rounded-lg border border-gray-100 bg-gray-50 p-4 flex flex-col">
              <div class="flex-1 flex items-end gap-3 border-b border-gray-200 pb-3 overflow-x-auto">
                <div
                  v-for="(segment, index) in barangayChartData"
                  :key="`barangay-bar-${index}`"
                  class="flex min-w-0 flex-1 flex-col items-center"
                >
                  <TooltipProvider>
                    <Tooltip>
                      <TooltipTrigger as-child>
                        <div class="flex h-48 w-full max-w-12 items-end pt-2 cursor-help">
                          <div
                            class="w-full rounded-t-md transition-all duration-300"
                            :style="{
                              height: `${getBarangayBarHeight(segment.value)}%`,
                              backgroundColor: getBarangayBarColor(index),
                            }"
                          ></div>
                        </div>
                      </TooltipTrigger>
                      <TooltipContent class="min-w-40 rounded-md border border-gray-200 bg-white px-3 py-2 text-xs shadow-lg">
                        <p class="font-semibold text-gray-900 truncate" :title="segment.name">{{ segment.name }}</p>
                        <p class="mt-1 text-gray-600">
                          Clients: <span class="font-semibold">{{ segment.value }}</span>
                        </p>
                        <p class="text-gray-600">
                          Percentage: <span class="font-semibold">{{ segment.percentage }}%</span>
                        </p>
                      </TooltipContent>
                    </Tooltip>
                  </TooltipProvider>
                  <span
                    class="mt-2 h-10 px-1 text-[11px] font-medium leading-tight text-center text-gray-600 flex items-start justify-center truncate w-full"
                    :title="segment.name"
                  >
                    {{ segment.name }}
                  </span>
                  <span class="mt-1 text-xs font-semibold text-gray-900">{{ segment.value }}</span>
                </div>
              </div>

              <div class="mt-4 flex items-center gap-2">
                <span class="text-sm font-medium text-gray-500">Total Clients:</span>
                <span class="text-lg font-semibold text-gray-900">{{ barangayTotalClients }}</span>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Lane Type Distribution Row -->
    <div class="mt-6">
      <div class="mb-3 flex items-center gap-1">
        <h2 class="text-lg font-semibold">Lane Type Distribution</h2>
        <CsmMetricExplanation
          :title="queueMetricExplanations.laneType.title"
          :meaning="queueMetricExplanations.laneType.meaning"
          :computation="queueMetricExplanations.laneType.computation"
          :formula="queueMetricExplanations.laneType.formula"
          :interpretation="queueMetricExplanations.laneType.interpretation"
        />
      </div>
      <Card class="w-full">
        <CardContent>
          <div class="h-[300px] w-full mt-2 flex items-center justify-center">
            <div
              class="relative h-52 w-52"
              @mousemove="handleLaneDonutMouseMove"
              @mouseleave="clearLaneHoverSegment"
            >
              <div class="h-full w-full rounded-full" :style="{ background: lanePieGradient }"></div>
              <div class="absolute inset-6 rounded-full bg-white flex flex-col items-center justify-center">
                <span class="text-2xl font-bold text-gray-900">{{ laneTotalClients }}</span>
                <span class="text-xs text-gray-500">Total Clients</span>
              </div>

              <div
                v-if="hoveredLaneSegment"
                class="pointer-events-none absolute z-20 min-w-36 rounded-md border border-gray-200 bg-white px-3 py-2 text-xs shadow-lg"
                :style="{ left: `${laneTooltipPosition.x}px`, top: `${laneTooltipPosition.y}px`, transform: 'translate(8px, -110%)' }"
              >
                <p class="font-semibold text-gray-900">{{ hoveredLaneSegment.name }}</p>
                <p class="mt-1 text-gray-600">Clients: <span class="font-semibold">{{ hoveredLaneSegment.value }}</span></p>
                <p class="text-gray-600">Percentage: <span class="font-semibold">{{ hoveredLaneSegment.percentage }}%</span></p>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 mt-4 pt-4 border-t border-gray-100">
            <div
              v-for="(segment, index) in laneTypeChartData"
              :key="`lane-segment-${index}`"
              class="flex items-center gap-2 text-sm"
            >
              <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: getLaneSegmentColor(index) }"></div>
              <span class="text-gray-600">{{ segment.name }}</span>
              <span class="text-gray-900 font-medium ml-auto">{{ segment.percentage }}%</span>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Queue Summary Table -->
    <div class="mt-8">
      <h2 class="text-xl font-semibold mb-4">Queue Summary</h2>

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
              <TableHead class="w-[11.11%]">Queue Number</TableHead>
              <TableHead class="w-[11.11%]">Client Name</TableHead>
              <TableHead class="w-[11.11%]">Barangay</TableHead>
              <TableHead class="w-[11.11%]">Contact Number</TableHead>
              <TableHead class="w-[11.11%]">Service</TableHead>
              <TableHead class="w-[11.11%]">Lane Type</TableHead>
              <TableHead class="w-[11.11%]">Status</TableHead>
              <TableHead class="w-[11.11%]">Completion Time</TableHead>
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
              <TableCell>{{ entry.completionTime }}</TableCell>
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

            <TableRow v-if="isLoadingQueueSummary">
              <TableCell colspan="9" class="text-center text-gray-500 py-8">
                Loading queue summary...
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
              <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Completion Time</p>
              <p class="mt-1 text-sm font-semibold text-gray-900">{{ selectedQueueEntry.completionTime || 'N/A' }}</p>
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

          <div v-else-if="formattedAssistanceProvided" class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-700">Assistance Provided</p>
            <p class="mt-1 text-sm font-semibold text-emerald-900">{{ formattedAssistanceProvided }}</p>
            <p v-if="selectedQueueEntry.assistanceProvidedAt" class="mt-0.5 text-xs text-emerald-800">
              Recorded on: {{ selectedQueueEntry.assistanceProvidedAt }}
            </p>
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
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import api from '@/services/api'
import StatCard from '@/components/common/StatCard.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import {
  Calendar,
  ChevronLeft,
  ChevronRight,
  Users,
  UserCheck,
  XCircle,
  Clock3,
  Hourglass,
  Loader2,
  BarChart3,
  MoreHorizontal,
} from 'lucide-vue-next'
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover'
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip'
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
import CsmMetricExplanation from '@/components/CSM/CsmMetricExplanation.vue'

const selectedDateRange = ref('daily')
const isDateFilterOpen = ref(false)
const isLoadingAnalytics = ref(false)
const isLoadingQueueSummary = ref(false)
const isApplyingDateFilter = ref(false)
const showExportModal = ref(false)
const isExportingGraphs = ref(false)
const showQueueDetailsModal = ref(false)
const selectedQueueEntry = ref(null)

const stats = ref({
  totalClients: 0,
  totalServed: 0,
  totalSkipped: 0,
  averageWaitingTime: 0,
  averageServiceTime: 0,
})

const queueMetricExplanations = {
  clientSatisfaction: {
    title: 'Barangay Distribution',
    meaning: 'This bar chart shows how clients are distributed across barangays for the selected date range.',
    computation: 'Each bar represents the number of queue transactions per barangay (based on barangay_id) for the selected date range, limited to external services.',
    formula: 'Barangay Percentage = (Barangay Client Count / Total Clients) * 100',
    interpretation: [
      'Taller bars indicate barangays with more clients served.',
      'Use this to identify where most clients are coming from.',
    ],
  },
  laneType: {
    title: 'Lane Type Distribution',
    meaning: 'This chart shows how total clients are distributed across lane types such as Regular, Senior Citizen, Pregnant, PWD, and IP members.',
    computation: 'Each lane type has a count and percentage based on total clients for the selected date range.',
    formula: 'Lane Type Percentage = (Lane Type Client Count / Total Clients) * 100',
    interpretation: [
      'Bigger donut slices indicate higher client share for that lane type.',
      'Use this to monitor if lane demand is balanced or skewed toward specific client groups.',
    ],
  },
}

const queueSummaryRowsPerPage = 10
const currentQueueSummaryPage = ref(1)
const queueSummarySearch = ref('')
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

const queueSummaryTotalPages = computed(() => {
  return Math.max(1, queueSummaryPagination.value.totalPages)
})

const paginatedQueueSummaryRows = computed(() => {
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

const queueSummaryStartRow = computed(() => queueSummaryPagination.value.startRow)

const queueSummaryEndRow = computed(() => queueSummaryPagination.value.endRow)

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

const clientSatisfactionData = ref([
  { label: 'Strongly Disagree', value: 0 },
  { label: 'Disagree', value: 0 },
  { label: 'Neither', value: 0 },
  { label: 'Agree', value: 0 },
  { label: 'Strongly Agree', value: 0 },
  { label: 'Not Applicable', value: 0 },
])
const clientSatisfactionTotalResponsesValue = ref(0)

const laneTypeData = ref([
  { name: 'Regular', value: 0, percentage: 0 },
  { name: 'Senior Citizen', value: 0, percentage: 0 },
  { name: 'Pregnant', value: 0, percentage: 0 },
  { name: 'PWD', value: 0, percentage: 0 },
  { name: 'Member of Indigenous People', value: 0, percentage: 0 },
])
const laneTotalClientsValue = ref(0)

const hoveredLaneSegment = ref(null)
const laneTooltipPosition = ref({ x: 0, y: 0 })

const laneColorPalette = ['#2563EB', '#16A34A', '#F59E0B', '#DC2626', '#7C3AED']

const maxClientSatisfactionValue = computed(() => {
  const values = clientSatisfactionData.value.map(item => item.value)
  return values.length ? Math.max(...values) : 1
})

const clientSatisfactionTotalResponses = computed(() => {
  return clientSatisfactionTotalResponsesValue.value
})

const laneTotalClients = computed(() => {
  return laneTotalClientsValue.value
})

const laneTypeChartData = computed(() => {
  return laneTypeData.value
})

const lanePieGradient = computed(() => {
  let current = 0
  const slices = laneTypeChartData.value.map((segment, index) => {
    const start = current
    const end = current + segment.percentage
    current = end
    return `${getLaneSegmentColor(index)} ${start}% ${end}%`
  })
  return `conic-gradient(${slices.join(', ')})`
})

const barangayData = ref([])
const barangayTotalClientsValue = ref(0)
const barangayTotalClients = computed(() => barangayTotalClientsValue.value)
const barangayChartData = computed(() => barangayData.value)

const maxBarangayValue = computed(() => {
  const values = barangayChartData.value.map((item) => item.value || 0)
  return values.length ? Math.max(...values) : 1
})

const getBarangayBarColor = (index) => laneColorPalette[index % laneColorPalette.length]

const getBarangayBarHeight = (value) => {
  if (!maxBarangayValue.value) return 6
  const normalized = (value / maxBarangayValue.value) * 80
  return Math.max(normalized, 6)
}

const getClientSatisfactionBarColor = (label) => {
  const colors = {
    'Strongly Disagree': '#EF4444',
    Disagree: '#F97316',
    Neither: '#EAB308',
    Agree: '#22C55E',
    'Strongly Agree': '#10B981',
    'Not Applicable': '#6B7280',
  }
  return colors[label] || '#3B82F6'
}

const getClientSatisfactionBarHeight = (value) => {
  if (!maxClientSatisfactionValue.value) return 6
  return Math.max((value / maxClientSatisfactionValue.value) * 100, 6)
}

const getLaneSegmentColor = (index) => {
  return laneColorPalette[index % laneColorPalette.length]
}

const getLaneSegmentByPercent = (percent) => {
  let cumulative = 0

  for (const segment of laneTypeChartData.value) {
    cumulative += segment.percentage
    if (percent <= cumulative) {
      return segment
    }
  }

  return laneTypeChartData.value[laneTypeChartData.value.length - 1] || null
}

const handleLaneDonutMouseMove = (event) => {
  const rect = event.currentTarget.getBoundingClientRect()
  const x = event.clientX - rect.left
  const y = event.clientY - rect.top

  const center = 104
  const dx = x - center
  const dy = y - center
  const distance = Math.sqrt((dx * dx) + (dy * dy))

  const outerRadius = 104
  const innerRadius = 80

  if (distance > outerRadius || distance < innerRadius) {
    hoveredLaneSegment.value = null
    return
  }

  const angle = (Math.atan2(dy, dx) * (180 / Math.PI) + 90 + 360) % 360
  const percent = (angle / 360) * 100

  hoveredLaneSegment.value = getLaneSegmentByPercent(percent)
  laneTooltipPosition.value = { x, y }
}

const clearLaneHoverSegment = () => {
  hoveredLaneSegment.value = null
}

const bodyOriginalOverflow = ref('')
const htmlOriginalOverflow = ref('')

const lockPageScroll = () => {
  if (typeof document === 'undefined') {
    return
  }

  bodyOriginalOverflow.value = document.body.style.overflow
  htmlOriginalOverflow.value = document.documentElement.style.overflow
  document.body.style.overflow = 'hidden'
  document.documentElement.style.overflow = 'hidden'
}

const unlockPageScroll = () => {
  if (typeof document === 'undefined') {
    return
  }

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

const exportOfficeDisplayName = computed(() => {
  const storedUser = localStorage.getItem('user')
  if (!storedUser) return 'this office'

  try {
    const parsed = JSON.parse(storedUser)
    const office = parsed?.office

    if (office?.name && office?.acronym) {
      return `${office.name} (${office.acronym})`
    }

    if (office?.name) {
      return office.name
    }
  } catch (error) {
    console.error('Failed to parse stored user for exportOfficeDisplayName', error)
  }

  return 'this office'
})

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

const openExportModal = () => {
  showExportModal.value = true
}

const closeExportModal = () => {
  if (isExportingGraphs.value) return
  showExportModal.value = false
}

const confirmExportGraphs = async () => {
  if (isExportingGraphs.value) return

  isExportingGraphs.value = true

  try {
    const response = await api.get('/frontdesk/analytics/export-graphs', {
      params: getDateFilterParams(),
      responseType: 'blob',
    })

    const blob = new Blob([response.data], { type: 'application/pdf' })

    const safeOfficeName = exportOfficeDisplayName.value.replace(/[\\/]/g, '-')
    const fileName = `${safeOfficeName} Queue Analytics Graph - ${dateFilterLabel.value}.pdf`

    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = fileName
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)

    showExportModal.value = false
  } catch (error) {
    console.error('Error exporting queue analytics graphs:', error)
    window.alert('Failed to generate PDF report. Please try again.')
  } finally {
    isExportingGraphs.value = false
  }
}

const formatDate = (date) => {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
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
  const response = await api.get('/frontdesk/analytics/cards', {
    params: getDateFilterParams(),
  })

  const payload = response?.data?.data || {}
  stats.value = {
    totalClients: payload.total_clients ?? 0,
    totalServed: payload.total_served ?? 0,
    totalSkipped: payload.total_skipped ?? 0,
    averageWaitingTime: payload.average_waiting_time ?? 0,
    averageServiceTime: payload.average_service_time ?? 0,
  }
}

const getDefaultClientSatisfactionDistribution = () => ([
  { label: 'Strongly Disagree', value: 0 },
  { label: 'Disagree', value: 0 },
  { label: 'Neither', value: 0 },
  { label: 'Agree', value: 0 },
  { label: 'Strongly Agree', value: 0 },
  { label: 'Not Applicable', value: 0 },
])

const fetchClientSatisfaction = async () => {
  const response = await api.get('/frontdesk/analytics/client-satisfaction', {
    params: getDateFilterParams(),
  })

  const payload = response?.data?.data || {}
  clientSatisfactionData.value = payload.distribution?.length
    ? payload.distribution
    : getDefaultClientSatisfactionDistribution()
  clientSatisfactionTotalResponsesValue.value = payload.total_responses ?? 0
}

const getDefaultLaneTypeDistribution = () => ([
  { name: 'Regular', value: 0, percentage: 0 },
  { name: 'Senior Citizen', value: 0, percentage: 0 },
  { name: 'Pregnant', value: 0, percentage: 0 },
  { name: 'PWD', value: 0, percentage: 0 },
  { name: 'Member of Indigenous People', value: 0, percentage: 0 },
])

const fetchLaneTypeDistribution = async () => {
  const response = await api.get('/frontdesk/analytics/lane-type', {
    params: getDateFilterParams(),
  })

  const payload = response?.data?.data || {}
  laneTypeData.value = payload.distribution?.length
    ? payload.distribution
    : getDefaultLaneTypeDistribution()
  laneTotalClientsValue.value = payload.total_clients ?? 0
}

const fetchBarangayDistribution = async () => {
  const response = await api.get('/frontdesk/analytics/barangay-distribution', {
    params: getDateFilterParams(),
  })

  const payload = response?.data?.data || {}
  barangayData.value = payload.distribution?.length ? payload.distribution : []
  barangayTotalClientsValue.value = payload.total_clients ?? 0
}

const fetchQueueSummary = async ({ showLoading = true } = {}) => {
  if (showLoading) {
    isLoadingQueueSummary.value = true
  }
  try {
    const response = await api.get('/frontdesk/analytics/queue-summary', {
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
      assistanceProvided: row.assistance_provided,
      assistanceProvidedAt: row.assistance_provided_at,
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
    if (showLoading) {
      isLoadingQueueSummary.value = false
    }
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

const formattedAssistanceProvided = computed(() => {
  if (!selectedQueueEntry.value || selectedQueueEntry.value.assistanceProvided == null) {
    return null
  }

  const value = Number(selectedQueueEntry.value.assistanceProvided)
  if (Number.isNaN(value)) return null

  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
  }).format(value)
})

const openQueueDetails = (entry) => {
  selectedQueueEntry.value = entry
  showQueueDetailsModal.value = true
}

const closeQueueDetails = () => {
  showQueueDetailsModal.value = false
  selectedQueueEntry.value = null
}

const fetchAnalyticsData = async () => {
  isLoadingAnalytics.value = true
  try {
    await Promise.all([
      fetchCardStats(),
      fetchBarangayDistribution(),
      fetchLaneTypeDistribution(),
      fetchQueueSummary(),
    ])
  } catch (error) {
    console.error('Error fetching analytics data:', error)
  } finally {
    isLoadingAnalytics.value = false
  }
}

watch(currentQueueSummaryPage, () => {
  if (isApplyingDateFilter.value) return
  fetchQueueSummary({ showLoading: false })
})

const applyDateFilterAndReload = async () => {
  isApplyingDateFilter.value = true
  currentQueueSummaryPage.value = 1
  await fetchAnalyticsData()
  isApplyingDateFilter.value = false
}

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

onMounted(() => {
  fetchAnalyticsData()
})
</script>