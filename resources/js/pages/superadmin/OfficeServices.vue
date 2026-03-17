<template>
  <div class="max-w-7xl mx-auto px-2 sm:px-2 lg:px-2 py-2">
    <!-- ==================== BREADCRUMB ==================== -->
    <div class="flex items-center gap-2 text-sm mb-6">
      <button
        @click="$router.push('/superadmin/offices')"
        class="text-[#0F5C5C] hover:underline font-medium cursor-pointer"
      >
        Office Management
      </button>
      <ChevronRight class="w-4 h-4 text-gray-400" />
      <span class="text-gray-600 font-medium">{{ office.name }}</span>
    </div>

    <!-- ==================== TABLE HEADER ==================== -->
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-2xl font-bold text-gray-800">{{ selectedServiceView }} Services</h2>
      <div class="flex items-center gap-2">
        <button
          @click="toggleServiceView"
          class="px-3 py-2 rounded-sm border border-[#BFD5E5] bg-[#EAF3F9] text-[#164980] hover:bg-[#DEECF6] text-sm font-medium transition-colors cursor-pointer flex items-center gap-2"
        >
          {{ selectedServiceView }}
          <Repeat2 class="w-4 h-4" />
        </button>
        <Button class="px-4 py-2 bg-[#0F5C5C] hover:bg-[#0D4A4A] text-white" @click="showAddModal = true">Add Service</Button>
      </div>
    </div>

    <!-- ==================== SERVICES TABLE ==================== -->
    <div class="bg-white rounded-lg shadow border border-gray-100 overflow-hidden">
      <Table>
        <TableHeader>
          <TableRow class="bg-gray-50">
            <TableHead class="font-semibold text-gray-600">Service Name</TableHead>
            <TableHead class="font-semibold text-gray-600">Service Code</TableHead>
            <TableHead class="font-semibold text-gray-600 text-center">Is Free</TableHead>
            <TableHead class="font-semibold text-gray-600 text-center">Used Count</TableHead>
            <TableHead class="font-semibold text-gray-600 text-center">Status</TableHead>
            <TableHead class="font-semibold text-gray-600 text-center">Lock Status</TableHead>
            <TableHead class="font-semibold text-gray-600 text-center">Actions</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-for="service in paginatedServices" :key="service.id" class="hover:bg-gray-50 transition-colors">
            <TableCell class="font-medium text-gray-800">{{ service.name }}</TableCell>
            <TableCell class="text-gray-600 font-mono text-sm">{{ service.code }}</TableCell>
            <TableCell class="text-center">
              <button
                @click.stop="toggleServiceIsFree(service)"
                class="px-2 py-0.5 rounded-sm text-xs font-semibold transition-colors cursor-pointer"
                :class="service.is_free ? 'bg-blue-100 text-blue-700 hover:bg-blue-200' : 'bg-orange-100 text-orange-700 hover:bg-orange-200'"
              >
                {{ service.is_free ? 'Yes' : 'No' }}
              </button>
            </TableCell>
            <TableCell class="text-center text-gray-600">{{ service.used_count }}</TableCell>
            <TableCell class="text-center">
              <button
                @click.stop="toggleServiceStatus(service)"
                class="px-2 py-0.5 rounded-sm text-xs font-semibold transition-colors cursor-pointer"
                :class="service.status === 'Active' ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'"
              >
                {{ service.status }}
              </button>
            </TableCell>
            <TableCell class="text-center">
              <span v-if="service.is_locked" class="flex justify-center">
                <Lock class="w-4 h-4 text-red-400" />
              </span>
              <span v-else class="flex justify-center">
                <LockOpen class="w-4 h-4 text-green-500" />
              </span>
            </TableCell>
            <TableCell class="text-center">
              <!-- 3 dots button -->
              <button
                @click.stop="toggleDropdown(service, $event)"
                class="p-1 rounded hover:bg-gray-100 transition-colors cursor-pointer"
              >
                <MoreHorizontal class="w-5 h-5 text-gray-500" />
              </button>
            </TableCell>
          </TableRow>
          <TableRow v-if="paginatedServices.length === 0">
            <TableCell colspan="7" class="text-center py-12 text-gray-400">No services found.</TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>

    <!-- ==================== PAGINATION ==================== -->
    <div class="flex items-center justify-between mt-4">
      <span class="text-sm text-gray-500">
        Showing {{ filteredServices.length === 0 ? 0 : (currentPage - 1) * pageSize + 1 }}–{{ Math.min(currentPage * pageSize, filteredServices.length) }} of {{ filteredServices.length }} services
      </span>
      <div class="flex items-center gap-1">
        <button
          @click="currentPage--"
          :disabled="currentPage === 1"
          class="px-3 py-1 rounded border border-gray-200 text-sm text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer transition-colors"
        >
          Previous
        </button>
        <button
          v-for="page in totalPages"
          :key="page"
          @click="currentPage = page"
          class="px-3 py-1 rounded border text-sm transition-colors cursor-pointer"
          :class="currentPage === page ? 'bg-[#0F5C5C] text-white border-[#0F5C5C]' : 'border-gray-200 text-gray-600 hover:bg-gray-50'"
        >
          {{ page }}
        </button>
        <button
          @click="currentPage++"
          :disabled="currentPage === totalPages"
          class="px-3 py-1 rounded border border-gray-200 text-sm text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer transition-colors"
        >
          Next
        </button>
      </div>
    </div>

    <!-- ==================== ADD SERVICE MODAL ==================== -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center">
          <div class="absolute inset-0 bg-black/60" @click="closeAddModal"></div>
          <div class="relative bg-white rounded-lg shadow-2xl w-full max-w-md p-8 z-10 mx-4">
            <button @click="closeAddModal" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 cursor-pointer">
              <X class="w-5 h-5" />
            </button>
            <h2 class="text-2xl font-bold text-[#0F5C5C] mb-6">Add Service</h2>

            <div class="space-y-4 mb-8">
              <div>
                <label class="block text-sm text-gray-700 mb-1">Service Name:</label>
                <input v-model="newService.name" type="text" placeholder="Enter Service Name"
                  class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#164980] focus:border-transparent transition" />
              </div>
              <div>
                <label class="block text-sm text-gray-700 mb-1">Service Code:</label>
                <input v-model="newService.code" type="text" placeholder="Enter Service Code"
                  class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#164980] focus:border-transparent transition" />
              </div>
              <div>
                <label class="block text-sm text-gray-700 mb-1">Service Description:</label>
                <textarea
                  v-model="newService.description"
                  rows="3"
                  placeholder="Enter Service Description"
                  class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-[#164980] focus:border-transparent transition"
                ></textarea>
              </div>
              <div>
                <label class="block text-sm text-gray-700 mb-1">Is Free:</label>

                <div class="relative">
                  <select
                    v-model="newService.is_free"
                    class="w-full appearance-none border border-gray-300 rounded-lg px-4 py-2 pr-12 text-sm focus:outline-none focus:ring-2 focus:ring-[#164980] focus:border-transparent transition"
                  >
                    <option :value="true">Yes</option>
                    <option :value="false">No</option>
                  </select>

                  <!-- Custom arrow -->
                  <ChevronDown
                    class="w-4 h-4 text-gray-500 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"
                  />
                </div>
              </div>

              <div>
                <label class="block text-sm text-gray-700 mb-1">Service Type:</label>
                <div class="relative">
                  <select
                    v-model="newService.service_type"
                    class="w-full appearance-none border border-gray-300 rounded-lg px-4 py-2 pr-12 text-sm focus:outline-none focus:ring-2 focus:ring-[#164980] focus:border-transparent transition"
                  >
                    <option value="External">External Service</option>
                    <option value="Internal">Internal Service</option>
                  </select>

                  <ChevronDown
                    class="w-4 h-4 text-gray-500 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"
                  />
                </div>
              </div>
            </div>

            <div class="flex justify-end gap-3">
              <button @click="closeAddModal" class="px-5 py-2 rounded-sm border border-gray-300 text-gray-600 hover:bg-gray-50 text-sm font-medium cursor-pointer">Cancel</button>
              <Button class="px-5 py-2 bg-[#0F5C5C] hover:bg-[#0D4A4A] text-white" @click="handleAddService">Save</Button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ==================== EDIT SERVICE MODAL ==================== -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center">
          <div class="absolute inset-0 bg-black/60" @click="closeEditModal"></div>
          <div class="relative bg-white rounded-lg shadow-2xl w-full max-w-md p-8 z-10 mx-4">
            <button @click="closeEditModal" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 cursor-pointer">
              <X class="w-5 h-5" />
            </button>
            <h2 class="text-2xl font-bold text-[#0F5C5C] mb-6">Edit Service</h2>

            <div class="space-y-4 mb-8">
              <div>
                <label class="block text-sm text-gray-700 mb-1">Service Name:</label>
                <input v-model="editService.name" type="text" placeholder="Enter Service Name"
                  class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#164980] focus:border-transparent transition" />
              </div>
              <div>
                <label class="block text-sm text-gray-700 mb-1">Service Code:</label>
                <input v-model="editService.code" type="text" placeholder="Enter Service Code"
                  class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#164980] focus:border-transparent transition" />
              </div>
              <div>
                <label class="block text-sm text-gray-700 mb-1">Service Description:</label>
                <textarea
                  v-model="editService.description"
                  rows="3"
                  placeholder="Enter Service Description"
                  class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-[#164980] focus:border-transparent transition"
                ></textarea>
              </div>
            </div>

            <div class="flex justify-end gap-3">
              <button @click="closeEditModal" class="px-5 py-2 rounded-sm border border-gray-300 text-gray-600 hover:bg-gray-50 text-sm font-medium cursor-pointer">Cancel</button>
              <Button class="px-5 py-2 bg-[#0F5C5C] hover:bg-[#0D4A4A] text-white" @click="handleSaveService">Save</Button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ==================== DELETE SERVICE MODAL ==================== -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center">
          <div class="absolute inset-0 bg-black/60" @click="closeDeleteModal"></div>
          <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-8 z-10 mx-4">
            <button @click="closeDeleteModal" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 cursor-pointer">
              <X class="w-5 h-5" />
            </button>
            <div class="flex flex-col items-center text-center mb-6">
              <div class="w-14 h-14 rounded-full bg-red-100 flex items-center justify-center mb-4">
                <Trash2 class="w-7 h-7 text-red-500" />
              </div>
              <h2 class="text-xl font-bold text-gray-800 mb-2">Delete Service</h2>
              <p class="text-sm text-gray-500">
                Are you sure you want to delete
                <span class="font-semibold text-gray-700">{{ serviceToDelete?.name }}</span>?
                This action cannot be undone.
              </p>
            </div>
            <div class="flex justify-end gap-3">
              <button @click="closeDeleteModal" class="px-5 py-2 rounded-md border border-gray-300 text-gray-600 hover:bg-gray-50 text-sm font-medium cursor-pointer">Cancel</button>
              <button @click="handleDeleteService" class="px-5 py-2 rounded-md bg-red-500 hover:bg-red-600 text-white text-sm font-medium cursor-pointer">Delete</button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Click outside to close dropdown -->
    <div v-if="activeDropdown" class="fixed inset-0 z-10" @click="activeDropdown = null"></div>

    <!-- Teleported dropdown rendered outside the table -->
    <Teleport to="body">
      <div
        v-if="activeDropdown && dropdownPos"
        class="fixed z-50 w-36 bg-white rounded-lg shadow-xl border border-gray-100 overflow-hidden"
        :style="{ top: dropdownPos.top + 'px', left: dropdownPos.left + 'px' }"
      >
        <button
          @click="openEditModal(activeService)"
          class="w-full flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer"
          :class="{ 'opacity-40 cursor-not-allowed pointer-events-none': activeService?.is_locked }"
        >
          <SquarePen class="w-4 h-4" />
          Edit
        </button>
        <button
          @click="openDeleteModal(activeService)"
          class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-500 hover:bg-red-50 transition-colors cursor-pointer"
          :class="{ 'opacity-40 cursor-not-allowed pointer-events-none': activeService?.is_locked }"
        >
          <Trash2 class="w-4 h-4" />
          Delete
        </button>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { Button } from '@/components/ui/button'
import {
  Table, TableBody, TableCell, TableHead, TableHeader, TableRow
} from '@/components/ui/table'
import {
  ChevronRight, ChevronDown, SquarePen, Trash2, X, MoreHorizontal, Lock, LockOpen, Repeat2
} from 'lucide-vue-next'

const route = useRoute()

// Hardcoded office info (would come from route params / API in real app)
const office = ref({
  id: Number(route.params.id) || 1,
  name: route.params.name ? decodeURIComponent(route.params.name) : 'City Health Office (CHO)'
})

const selectedServiceView = ref('External')

// ---- SERVICES DATA ----
const services = ref([
  { id: 1, name: 'Medical Consultation', code: 'MED-001', service_type: 'External', is_free: true, used_count: 45, status: 'Active', is_locked: true },
  { id: 2, name: 'Dental Check-Up', code: 'DEN-001', service_type: 'External', is_free: true, used_count: 22, status: 'Active', is_locked: true },
  { id: 3, name: 'Laboratory Test', code: 'LAB-001', service_type: 'External', is_free: false, used_count: 10, status: 'Active', is_locked: false },
  { id: 4, name: 'X-Ray', code: 'RAD-001', service_type: 'External', is_free: false, used_count: 0, status: 'Active', is_locked: false },
  { id: 5, name: 'Vaccination', code: 'VAC-001', service_type: 'External', is_free: true, used_count: 80, status: 'Active', is_locked: true },
  { id: 6, name: 'Blood Pressure Monitoring', code: 'BPM-001', service_type: 'Internal', is_free: true, used_count: 0, status: 'Inactive', is_locked: false },
  { id: 7, name: 'Prenatal Check-Up', code: 'PRE-001', service_type: 'Internal', is_free: true, used_count: 33, status: 'Active', is_locked: true },
  { id: 8, name: 'Family Planning Counseling', code: 'FPL-001', service_type: 'Internal', is_free: true, used_count: 5, status: 'Active', is_locked: false }
])

// ---- PAGINATION ----
const currentPage = ref(1)
const pageSize = 5

const filteredServices = computed(() =>
  services.value.filter((service) => service.service_type === selectedServiceView.value)
)

const totalPages = computed(() => Math.max(1, Math.ceil(filteredServices.value.length / pageSize)))
const paginatedServices = computed(() =>
  filteredServices.value.slice((currentPage.value - 1) * pageSize, currentPage.value * pageSize)
)

const toggleServiceView = () => {
  selectedServiceView.value = selectedServiceView.value === 'External' ? 'Internal' : 'External'
  currentPage.value = 1
}

// ---- DROPDOWN ----
const activeDropdown = ref(null)
const activeService = ref(null)
const dropdownPos = ref(null)

const toggleDropdown = (service, event) => {
  if (activeDropdown.value === service.id) {
    activeDropdown.value = null
    activeService.value = null
    dropdownPos.value = null
    return
  }
  const btn = event.currentTarget
  const rect = btn.getBoundingClientRect()
  dropdownPos.value = {
    top: rect.bottom + window.scrollY + 4,
    left: rect.right + window.scrollX - 144 // 144 = dropdown width (w-36)
  }
  activeDropdown.value = service.id
  activeService.value = service
}

const toggleServiceIsFree = (service) => {
  service.is_free = !service.is_free
}

const toggleServiceStatus = (service) => {
  service.status = service.status === 'Active' ? 'Inactive' : 'Active'
}

// ---- ADD MODAL ----
const showAddModal = ref(false)
const newService = ref({ name: '', code: '', description: '', is_free: true, service_type: 'External' })

const closeAddModal = () => {
  showAddModal.value = false
  newService.value = { name: '', code: '', description: '', is_free: true, service_type: 'External' }
}
const handleAddService = () => {
  if (!newService.value.name.trim()) return
  console.log('Adding service:', newService.value)
  closeAddModal()
}

// ---- EDIT MODAL ----
const showEditModal = ref(false)
const editService = ref({ name: '', code: '', description: '', is_free: true, status: 'Active' })
const serviceToEdit = ref(null)

const openEditModal = (service) => {
  if (service.is_locked) return
  serviceToEdit.value = service
  editService.value = {
    name: service.name,
    code: service.code,
    description: service.description || '',
    is_free: service.is_free,
    status: service.status || 'Active'
  }
  showEditModal.value = true
  activeDropdown.value = null
}
const closeEditModal = () => {
  showEditModal.value = false
  serviceToEdit.value = null
}
const handleSaveService = () => {
  if (!editService.value.name.trim()) return
  console.log('Saving service:', editService.value)
  closeEditModal()
}

// ---- DELETE MODAL ----
const showDeleteModal = ref(false)
const serviceToDelete = ref(null)

const openDeleteModal = (service) => {
  if (service.is_locked) return
  serviceToDelete.value = service
  showDeleteModal.value = true
  activeDropdown.value = null
}
const closeDeleteModal = () => {
  showDeleteModal.value = false
  serviceToDelete.value = null
}
const handleDeleteService = () => {
  console.log('Soft-deleting service:', serviceToDelete.value?.name)
  closeDeleteModal()
}
</script>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.2s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}
</style>
