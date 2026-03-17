<template>
  <div class="max-w-7xl mx-auto px-2 sm:px-2 lg:px-2 py-2">
    <!-- ==================== PAGE HEADER ==================== -->
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-gray-800">User Management</h2>
      <Button class="px-4 py-2 rounded-sm bg-[#0F5C5C] hover:bg-[#0D4A4A] text-white" @click="showAddModal = true">Add User</Button>
    </div>

    <!-- ==================== USERS TABLE ==================== -->
    <div class="bg-white rounded-lg shadow border border-gray-100 overflow-hidden">
      <Table>
        <TableHeader>
          <TableRow class="bg-gray-50">
            <TableHead class="font-semibold text-gray-600">Username</TableHead>
            <TableHead class="font-semibold text-gray-600">Role</TableHead>
            <TableHead class="font-semibold text-gray-600">Assigned Office</TableHead>
            <TableHead class="font-semibold text-gray-600 text-center">Actions</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-for="user in paginatedUsers" :key="user.id" class="hover:bg-gray-50 transition-colors">
            <TableCell class="font-medium text-gray-800">{{ user.username }}</TableCell>
            <TableCell>
              <span
                class="px-2 py-0.5 rounded-sm text-xs font-semibold"
                :class="user.role === 'SUPERADMIN' ? 'bg-[#BCEDE4] text-[#0F5C5C]' : 'bg-blue-100 text-blue-700'"
              >
                {{ user.role === 'SUPERADMIN' ? 'Superadmin' : 'Office Admin' }}
              </span>
            </TableCell>
            <TableCell class="text-gray-600">
              {{ user.role === 'SUPERADMIN' ? '—' : getOfficeLabel(user.officeId) }}
            </TableCell>
            <TableCell class="text-center">
              <button
                @click.stop="toggleDropdown(user, $event)"
                class="p-1 rounded hover:bg-gray-100 transition-colors cursor-pointer"
              >
                <MoreHorizontal class="w-5 h-5 text-gray-500" />
              </button>
            </TableCell>
          </TableRow>
          <TableRow v-if="paginatedUsers.length === 0">
            <TableCell colspan="4" class="text-center py-12 text-gray-400">No users found.</TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>

    <!-- ==================== PAGINATION ==================== -->
    <div class="flex items-center justify-between mt-4">
      <span class="text-sm text-gray-500">
        Showing {{ (currentPage - 1) * pageSize + 1 }}–{{ Math.min(currentPage * pageSize, users.length) }} of {{ users.length }} users
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

    <!-- Click outside to close dropdown -->
    <div v-if="activeDropdown" class="fixed inset-0 z-10" @click="activeDropdown = null"></div>

    <!-- ==================== TELEPORTED DROPDOWN ==================== -->
    <Teleport to="body">
      <div
        v-if="activeDropdown && dropdownPos"
        class="fixed z-50 w-36 bg-white rounded-lg shadow-xl border border-gray-100 overflow-hidden"
        :style="{ top: dropdownPos.top + 'px', left: dropdownPos.left + 'px' }"
      >
        <button
          @click="openEditModal(activeUser)"
          class="w-full flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer"
        >
          <SquarePen class="w-4 h-4" />
          Edit
        </button>
        <button
          @click="openDeleteModal(activeUser)"
          class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-500 hover:bg-red-50 transition-colors cursor-pointer"
        >
          <Trash2 class="w-4 h-4" />
          Delete
        </button>
      </div>
    </Teleport>

    <!-- ==================== ADD USER MODAL ==================== -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center">
          <div class="absolute inset-0 bg-black/60" @click="closeAddModal"></div>
          <div class="relative bg-white rounded-lg shadow-2xl w-full max-w-md p-8 z-10 mx-4">
            <button @click="closeAddModal" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 cursor-pointer">
              <X class="w-5 h-5" />
            </button>
            <h2 class="text-2xl font-bold text-[#0F5C5C] mb-6">Add User</h2>

            <div class="space-y-4 mb-8">
              <div>
                <label class="block text-sm text-gray-700 mb-1">Username:</label>
                <input v-model="newUser.username" type="text" placeholder="Enter Username"
                  class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#164980] focus:border-transparent transition" />
              </div>
              <div>
                <label class="block text-sm text-gray-700 mb-1">Password:</label>
                <div class="relative">
                  <input v-model="newUser.password" :type="showNewPassword ? 'text' : 'password'" placeholder="Enter Password"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-[#164980] focus:border-transparent transition" />
                  <button type="button" @click="showNewPassword = !showNewPassword"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 cursor-pointer">
                    <Eye v-if="!showNewPassword" class="w-4 h-4" />
                    <EyeOff v-else class="w-4 h-4" />
                  </button>
                </div>
              </div>
              <div>
                <label class="block text-sm text-gray-700 mb-1">Role:</label>
                  <div class="relative">
                    <select v-model="newUser.role"
                      class="w-full border appearance-none border-gray-300 rounded-lg px-4 py-2 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-[#164980] focus:border-transparent transition">
                      <option value="SUPERADMIN">Superadmin</option>
                      <option value="OFFICE ADMIN">Office Admin</option>
                    </select>
                    <!-- Custom arrow -->
                    <ChevronDown
                      class="w-4 h-4 text-gray-500 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"
                    />
                  </div>
              </div>
              <div v-if="newUser.role === 'OFFICE ADMIN'">
                <label class="block text-sm text-gray-700 mb-1">Office:</label>
                <div class="relative">
                  <select v-model="newUser.officeId"
                    class="w-full border appearance-none border-gray-300 rounded-lg px-4 py-2 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-[#164980] focus:border-transparent transition">
                    <option :value="null" disabled>Select an office</option>
                    <option v-for="office in offices" :key="office.id" :value="office.id">{{ formatOfficeOption(office) }}</option>
                  </select>
                  <!-- Custom arrow -->
                  <ChevronDown
                    class="w-4 h-4 text-gray-500 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"
                  />
                </div>
              </div>
            </div>

            <div class="flex justify-end gap-3">
              <button @click="closeAddModal" class="px-5 py-2 rounded-sm border border-gray-300 text-gray-600 hover:bg-gray-50 text-sm font-medium cursor-pointer">Cancel</button>
              <Button class="px-5 py-2 rounded-sm bg-[#0F5C5C] hover:bg-[#0D4A4A] text-white" @click="handleAddUser">Add User</Button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ==================== EDIT USER MODAL ==================== -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center">
          <div class="absolute inset-0 bg-black/60" @click="closeEditModal"></div>
          <div class="relative bg-white rounded-lg shadow-2xl w-full max-w-md p-8 z-10 mx-4">
            <button @click="closeEditModal" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 cursor-pointer">
              <X class="w-5 h-5" />
            </button>
            <h2 class="text-2xl font-bold text-[#0F5C5C] mb-6">Edit User</h2>

            <div class="space-y-4 mb-8">
              <div>
                <label class="block text-sm text-gray-700 mb-1">Username:</label>
                <input v-model="editUser.username" type="text" placeholder="Enter Username"
                  class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#164980] focus:border-transparent transition" />
              </div>
              <div>
                <label class="block text-sm text-gray-700 mb-1">Password:</label>
                <div class="relative">
                  <input v-model="editUser.password" :type="showEditPassword ? 'text' : 'password'" placeholder="Leave blank to keep current"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-[#164980] focus:border-transparent transition" />
                  <button type="button" @click="showEditPassword = !showEditPassword"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 cursor-pointer">
                    <Eye v-if="!showEditPassword" class="w-4 h-4" />
                    <EyeOff v-else class="w-4 h-4" />
                  </button>
                </div>
              </div>
              <div>
                <label class="block text-sm text-gray-700 mb-1">Role:</label>
                <div class="relative">
                  <select v-model="editUser.role"
                    class="w-full border appearance-none border-gray-300 rounded-lg px-4 py-2 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-[#164980] focus:border-transparent transition">
                    <option value="SUPERADMIN">Superadmin</option>
                    <option value="OFFICE ADMIN">Office Admin</option>
                  </select>
                  <!-- Custom arrow -->
                  <ChevronDown
                    class="w-4 h-4 text-gray-500 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"
                  />
                </div>
              </div>
              <div v-if="editUser.role === 'OFFICE ADMIN'">
                <label class="block text-sm text-gray-700 mb-1">Office:</label>
                <div class="relative">
                  <select v-model="editUser.officeId"
                    class="w-full border appearance-none border-gray-300 rounded-lg px-4 py-2 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-[#164980] focus:border-transparent transition">
                    <option :value="null" disabled>Select an office</option>
                    <option v-for="office in offices" :key="office.id" :value="office.id">{{ formatOfficeOption(office) }}</option>
                  </select>
                  <!-- Custom arrow -->
                  <ChevronDown
                    class="w-4 h-4 text-gray-500 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"
                  />
                </div>
              </div>
            </div>

            <div class="flex justify-end gap-3">
              <button @click="closeEditModal" class="px-5 py-2 rounded-sm border border-gray-300 text-gray-600 hover:bg-gray-50 text-sm font-medium cursor-pointer">Cancel</button>
              <Button class="px-5 py-2 rounded-sm bg-[#0F5C5C] hover:bg-[#0D4A4A] text-white" @click="handleSaveUser">Save</Button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ==================== DELETE USER MODAL ==================== -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center">
          <div class="absolute inset-0 bg-black/60" @click="closeDeleteModal"></div>
          <div class="relative bg-white rounded-lg shadow-2xl w-full max-w-sm p-8 z-10 mx-4">
            <button @click="closeDeleteModal" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 cursor-pointer">
              <X class="w-5 h-5" />
            </button>
            <div class="flex flex-col items-center text-center mb-6">
              <div class="w-14 h-14 rounded-full bg-red-100 flex items-center justify-center mb-4">
                <Trash2 class="w-7 h-7 text-red-500" />
              </div>
              <h2 class="text-xl font-bold text-gray-800 mb-2">Delete User</h2>
              <p class="text-sm text-gray-500">
                Are you sure you want to delete
                <span class="font-semibold text-gray-700">{{ userToDelete?.username }}</span>?
                This action cannot be undone.
              </p>
            </div>
            <div class="flex justify-end gap-3">
              <button @click="closeDeleteModal" class="px-5 py-2 rounded-sm border border-gray-300 text-gray-600 hover:bg-gray-50 text-sm font-medium cursor-pointer">Cancel</button>
              <button @click="handleDeleteUser" class="px-5 py-2 rounded-sm bg-red-500 hover:bg-red-600 text-white text-sm font-medium cursor-pointer">Delete</button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Button } from '@/components/ui/button'
import {
  Table, TableBody, TableCell, TableHead, TableHeader, TableRow
} from '@/components/ui/table'
import {
  SquarePen, Trash2, X, MoreHorizontal, Eye, EyeOff, ChevronDown
} from 'lucide-vue-next'

const offices = [
  { id: 1, name: 'Office of the City Mayor', acronym: 'CMO' },
  { id: 2, name: 'Office of the City Mayor-Library Services', acronym: 'CMO-LS' },
  { id: 3, name: 'Office of the City Mayor-Ligao Community College', acronym: 'CMO-LCC' },
  { id: 4, name: 'City General Services Office', acronym: 'CGSO' },
  { id: 5, name: 'Office of the City Local Civil Registrar', acronym: 'CLCR' },
  { id: 6, name: "City Treasurer's Office", acronym: 'CTO' },
  { id: 7, name: "Office of the City Treasurer's Office-Operation Economic Enterprise", acronym: 'CTO-OEE' },
  { id: 8, name: "City Assessor's Office", acronym: 'CAO' },
  { id: 9, name: 'Business Processing Licensing Office', acronym: 'BPLO' }
]

const users = ref([
  { id: 1, username: 'superadmin', role: 'SUPERADMIN', officeId: null },
  { id: 2, username: 'cmo_admin', role: 'OFFICE ADMIN', officeId: 1 },
  { id: 3, username: 'cgso_admin', role: 'OFFICE ADMIN', officeId: 4 },
  { id: 4, username: 'clcr_admin', role: 'OFFICE ADMIN', officeId: 5 },
  { id: 5, username: 'cto_admin', role: 'OFFICE ADMIN', officeId: 6 },
  { id: 6, username: 'cao_admin', role: 'OFFICE ADMIN', officeId: 8 },
  { id: 7, username: 'bplo_admin', role: 'OFFICE ADMIN', officeId: 9 }
])

const formatOfficeOption = (office) => `${office.name} (${office.acronym})`

const getOfficeLabel = (officeId) => {
  const office = offices.find((item) => item.id === officeId)
  return office ? formatOfficeOption(office) : '—'
}

const currentPage = ref(1)
const pageSize = 5

const totalPages = computed(() => Math.max(1, Math.ceil(users.value.length / pageSize)))
const paginatedUsers = computed(() =>
  users.value.slice((currentPage.value - 1) * pageSize, currentPage.value * pageSize)
)

const activeDropdown = ref(null)
const activeUser = ref(null)
const dropdownPos = ref(null)

const toggleDropdown = (user, event) => {
  if (activeDropdown.value === user.id) {
    activeDropdown.value = null
    activeUser.value = null
    dropdownPos.value = null
    return
  }

  const btn = event.currentTarget
  const rect = btn.getBoundingClientRect()
  dropdownPos.value = {
    top: rect.bottom + window.scrollY + 4,
    left: rect.right + window.scrollX - 144
  }
  activeDropdown.value = user.id
  activeUser.value = user
}

const showAddModal = ref(false)
const showNewPassword = ref(false)
const newUser = ref({ username: '', password: '', role: 'OFFICE ADMIN', officeId: null })

const closeAddModal = () => {
  showAddModal.value = false
  showNewPassword.value = false
  newUser.value = { username: '', password: '', role: 'OFFICE ADMIN', officeId: null }
}

const handleAddUser = () => {
  if (!newUser.value.username.trim() || !newUser.value.password.trim()) return
  closeAddModal()
}

const showEditModal = ref(false)
const showEditPassword = ref(false)
const editUser = ref({ username: '', password: '', role: 'OFFICE ADMIN', officeId: null })
const userToEdit = ref(null)

const openEditModal = (user) => {
  userToEdit.value = user
  editUser.value = { username: user.username, password: '', role: user.role, officeId: user.officeId ?? null }
  showEditModal.value = true
  activeDropdown.value = null
}

const closeEditModal = () => {
  showEditModal.value = false
  showEditPassword.value = false
  userToEdit.value = null
}

const handleSaveUser = () => {
  if (!editUser.value.username.trim()) return
  closeEditModal()
}

const showDeleteModal = ref(false)
const userToDelete = ref(null)

const openDeleteModal = (user) => {
  userToDelete.value = user
  showDeleteModal.value = true
  activeDropdown.value = null
}

const closeDeleteModal = () => {
  showDeleteModal.value = false
  userToDelete.value = null
}

const handleDeleteUser = () => {
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