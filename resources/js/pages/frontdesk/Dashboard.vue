<template>
  <div>
    <h1 class="text-2xl font-semibold mb-6">Queue Dashboard</h1>

    <!-- Stat Cards -->
    <div class="grid grid-cols-4 gap-6">

      <StatCard class="border-l-4 border-orange-300"
        title="Waiting"
        :value="24"
        :icon="Clock"
        iconBg="bg-orange-100"
        iconColor="text-orange-500"
        numberColor="text-orange-500"
      />

      <StatCard class="border-l-4 border-green-400"
        title="Serving"
        :value="2"
        :icon="User"
        iconBg="bg-green-100"
        iconColor="text-green-600"
        numberColor="text-green-600"
      />

      <StatCard class="border-l-4 border-blue-400"
        title="Completed"
        :value="36"
        :icon="CheckCircle"
        iconBg="bg-blue-100"
        iconColor="text-blue-600"
        numberColor="text-blue-600"
      />

      <StatCard class="border-l-4 border-red-400"
        title="Skipped"
        :value="1"
        :icon="XCircle"
        iconBg="bg-red-100"
        iconColor="text-red-600"
        numberColor="text-red-600"
      />

    </div>

    <!-- Queue Management Table -->
    <div class="mt-6 flex gap-6">

      <div class="flex-2">
        <h2 class="text-xl font-semibold mb-4">
          Queue Management
        </h2>

        <div class="bg-white rounded-xl shadow-sm p-6">
          <Table class="w-full">
            <TableHeader>
              <TableRow>
                <TableHead>Queue No.</TableHead>
                <TableHead>Service</TableHead>
                <TableHead>Lane Type</TableHead>
                <TableHead>Time</TableHead>
                <TableHead>Counter</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow>
            </TableHeader>

            <TableBody>

              <!-- Row 1 -->
              <TableRow>
                <TableCell>CPDO - 001</TableCell>
                <TableCell>SN, ST, IM</TableCell>
                <TableCell>Regular</TableCell>
                <TableCell>5:00 am</TableCell>
                <TableCell>1</TableCell>

                <TableCell class="flex gap-2">
                  <Button size="sm" class="bg-[#16A34A] hover:bg-[#15803D] text-white">
                    <Megaphone />
                    Call
                  </Button>

                  <Button size="sm" variant="destructive" class="bg-[#DC2626] hover:bg-[#B91C1C] text-white">
                    <X />
                    Skip
                  </Button>
                </TableCell>
              </TableRow>

              <!-- Row 2 -->
              <TableRow>
                <TableCell>CPDO - 002</TableCell>
                <TableCell>SN, ST, IM</TableCell>
                <TableCell>Regular</TableCell>
                <TableCell>5:00 am</TableCell>
                <TableCell>2</TableCell>

                <TableCell class="flex gap-2">
                  <Button size="sm" class="bg-[#16A34A] hover:bg-[#15803D] text-white">
                    <Megaphone />
                    Call
                  </Button>

                  <Button size="sm" variant="destructive" class="bg-[#DC2626] hover:bg-[#B91C1C] text-white">
                    <X />
                    Skip
                  </Button>
                </TableCell>
              </TableRow>

              <!-- Row 3 -->
              <TableRow>
                <TableCell>CPDO - P001</TableCell>
                <TableCell>SN, ST, IM</TableCell>
                <TableCell>Priority</TableCell>
                <TableCell>5:00 am</TableCell>
                <TableCell>-</TableCell>

                <TableCell class="flex gap-2">
                  <Button size="sm" class="bg-[#16A34A] hover:bg-[#15803D] text-white">
                    <Megaphone />
                    Call
                  </Button>

                  <Button size="sm" variant="destructive" class="bg-[#DC2626] hover:bg-[#B91C1C] text-white">
                    <X />
                    Skip
                  </Button>
                </TableCell>
              </TableRow>

              <!-- Row 4 -->
              <TableRow>
                <TableCell>CPDO - 003</TableCell>
                <TableCell>SN, ST, IM</TableCell>
                <TableCell>Regular</TableCell>
                <TableCell>5:00 am</TableCell>
                <TableCell>-</TableCell>

                <TableCell class="flex gap-2">
                  <Button size="sm" class="bg-[#16A34A] hover:bg-[#15803D] text-white">
                    <Megaphone />
                    Call
                  </Button>

                  <Button size="sm" variant="destructive" class="bg-[#DC2626] hover:bg-[#B91C1C] text-white">
                    <X />
                    Skip
                  </Button>
                </TableCell>
              </TableRow>

              <!-- Row 5 -->
              <TableRow>
                <TableCell>CPDO - 004</TableCell>
                <TableCell>SN, ST, IM</TableCell>
                <TableCell>Regular</TableCell>
                <TableCell>5:00 am</TableCell>
                <TableCell>-</TableCell>

                <TableCell class="flex gap-2">
                  <Button size="sm" class="bg-[#16A34A] hover:bg-[#15803D] text-white">
                    <Megaphone />
                    Call
                  </Button>

                  <Button size="sm" variant="destructive" class="bg-[#DC2626] hover:bg-[#B91C1C] text-white">
                    <X />
                    Skip
                  </Button>
                </TableCell>
              </TableRow>

            </TableBody>
          </Table>

          <!-- Pagination -->
          <div class="flex items-center justify-between mt-3">

            <!-- LEFT SIDE -->
            <p class="text-sm text-gray-500">
              1–5 of 5 row(s) shown.
            </p>

            <!-- RIGHT SIDE -->
            <div class="flex items-center gap-6">

              <!-- Rows per page -->
              <div class="flex items-center gap-2 text-sm text-gray-600 whitespace-nowrap">
                <span>Rows per page</span>

                <select class="border rounded-md px-2 py-1 text-sm">
                  <option>5</option>
                  <option>10</option>
                  <option>20</option>
                </select>
              </div>

              <!-- Page indicator -->
              <p class="text-sm text-gray-600 whitespace-nowrap">
                Page 1 of 1
              </p>

              <!-- Pagination Buttons -->
              <Pagination>
                <PaginationContent>

                  <PaginationItem>
                    <PaginationFirst />
                  </PaginationItem>

                  <PaginationItem>
                    <PaginationPrevious />
                  </PaginationItem>

                  <PaginationItem>
                    <PaginationNext />
                  </PaginationItem>

                  <PaginationItem>
                    <PaginationLast />
                  </PaginationItem>

                </PaginationContent>
              </Pagination>

            </div>
          </div>

        </div>

      </div>

      <div class="flex-1">
        <div class="flex justify-between">
          <h2 class="text-xl font-semibold mb-4">
            Counters
          </h2> 
          <Button class="h-8 p-3 bg-[#0F5C5C] hover:bg-[#167D7F] text-white">
            Add Counter
          </Button> 
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 flex flex-col gap-4">
          <!-- Counter Card -->
          <div class="bg-[#dbf1ed] rounded-md border border-[#66d1bf] p-4">
            <div class="flex justify-between">
              <div class="flex gap-3">
              <p class="text-sm text-[#2E2E2E] font-semibold">
                Counter 1
              </p>
              <p class="text-xs text-[#474C55] font-regular italic">
                Available
              </p>              
              </div>

              <button class="text-gray-600 hover:text-gray-800 p-1 rounded-md hover:bg-gray-200">
                <MoreHorizontal class="w-4 h-4" />
              </button>
            </div>
            
            <p class="text-2xl text-[#1F4E79] font-bold py-3">
              CPDO - 001
            </p>

            <div class="flex gap-2">
              <Button size="sm" class="flex-1 bg-[#2563EB] hover:bg-[#1D4ED8] text-white">
                <Check />
                Complete
              </Button>

              <Button size="sm" variant="destructive" class="flex-1 bg-[#DC2626] hover:bg-[#B91C1C] text-white">
                <X />
                Skip
              </Button>
            </div>
          </div>
          <div class="bg-[#D7F2ED] rounded-md border border-[#5DD2BE] p-4">
            <div class="flex justify-between">
              <div class="flex gap-3">
              <p class="text-sm text-[#2E2E2E] font-semibold">
                Counter 2
              </p>
              <p class="text-xs text-[#474C55] font-regular italic">
                Available
              </p>              
              </div>

              <button class="text-gray-600 hover:text-gray-800 p-1 rounded-md hover:bg-gray-200">
                <MoreHorizontal class="w-4 h-4" />
              </button>
            </div>
            
            <p class="text-2xl text-[#1F4E79] font-bold py-3">
              CPDO - 001
            </p>

            <div class="flex gap-2">
              <Button size="sm" class="flex-1 bg-[#2563EB] hover:bg-[#1D4ED8] text-white">
                <Check />
                Complete
              </Button>

              <Button size="sm" variant="destructive" class="flex-1 bg-[#DC2626] hover:bg-[#B91C1C] text-white">
                <X />
                Skip
              </Button>
            </div>
          </div>
          <div class="bg-[#D7F2ED] rounded-md border border-[#5DD2BE] p-4">
            <div class="flex justify-between">
              <div class="flex gap-3">
              <p class="text-sm text-[#2E2E2E] font-semibold">
                Counter 3
              </p>
              <p class="text-xs text-[#474C55] font-regular italic">
                Available
              </p>              
              </div>

              <button class="text-gray-600 hover:text-gray-800 p-1 rounded-md hover:bg-gray-200">
                <MoreHorizontal class="w-4 h-4" />
              </button>
            </div>
            
            <p class="text-2xl italic text-[#6B7280] font-medium py-3">
              Idle
            </p>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import StatCard from '@/components/common/StatCard.vue'

import { Clock, User, CheckCircle, XCircle, MoreHorizontal, X, Check, Megaphone } from 'lucide-vue-next'

import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow
} from '@/components/ui/table'

import { Button } from '@/components/ui/button'

import {
  Pagination,
  PaginationContent,
  PaginationItem,
  PaginationNext,
  PaginationPrevious,
  PaginationFirst,
  PaginationLast
} from '@/components/ui/pagination'
</script>