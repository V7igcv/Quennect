<template>
  <div class="max-w-7xl mx-auto px-2 sm:px-2 lg:px-2 py-2">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-gray-800">User Management</h2>
      <Button class="px-4 py-2 rounded-sm bg-[#0F5C5C] hover:bg-[#0D4A4A] text-white" :disabled="isLoading || isSubmitting" @click="showAddModal = true">Add User</Button>
    </div>

    <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      {{ errorMessage }}
    </div>

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
          <TableRow v-if="isLoading">
            <TableCell colspan="4" class="text-center py-12 text-gray-400">Loading users...</TableCell>
          </TableRow>
          <TableRow v-for="user in paginatedUsers" v-else :key="user.id" class="hover:bg-gray-50 transition-colors">
            <TableCell class="font-medium text-gray-800">{{ user.username }}</TableCell>
            <TableCell>
              <span
                class="px-2 py-0.5 rounded-sm text-xs font-semibold"
                :class="user.role === 'SUPERADMIN' ? 'bg-[#BCEDE4] text-[#0F5C5C]' : 'bg-blue-100 text-blue-700'"
              >
                {{ user.roleLabel }}
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
          <TableRow v-if="!isLoading && paginatedUsers.length === 0">
            <TableCell colspan="4" class="text-center py-12 text-gray-400">No users found.</TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>

    <div class="flex items-center justify-between mt-4">
      <span class="text-sm text-gray-500">
        Showing {{ summaryStart }}–{{ summaryEnd }} of {{ users.length }} users
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

    <div v-if="activeDropdown" class="fixed inset-0 z-10" @click="closeDropdown"></div>

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
      </div>
    </Teleport>

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
                  <select v-model="newUser.role" @change="handleRoleChange(newUser)"
                    class="w-full border appearance-none border-gray-300 rounded-lg px-4 py-2 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-[#164980] focus:border-transparent transition">
                    <option value="SUPERADMIN">Superadmin</option>
                    <option value="OFFICE FRONTDESK">Office Frontdesk</option>
                  </select>
                  <ChevronDown
                    class="w-4 h-4 text-gray-500 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"
                  />
                </div>
              </div>
              <div v-if="newUser.role === 'OFFICE FRONTDESK'">
                <label class="block text-sm text-gray-700 mb-1">Office:</label>
                <div class="relative">
                  <select v-model="newUser.officeId"
                    class="w-full border appearance-none border-gray-300 rounded-lg px-4 py-2 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-[#164980] focus:border-transparent transition">
                    <option :value="null" disabled>Select an office</option>
                    <option v-for="office in offices" :key="office.id" :value="office.id">{{ formatOfficeOption(office) }}</option>
                  </select>
                  <ChevronDown
                    class="w-4 h-4 text-gray-500 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"
                  />
                </div>
              </div>
            </div>

            <p v-if="formError" class="mb-4 text-sm text-red-600">{{ formError }}</p>

            <div class="flex justify-end gap-3">
              <button @click="closeAddModal" class="px-5 py-2 rounded-sm border border-gray-300 text-gray-600 hover:bg-gray-50 text-sm font-medium cursor-pointer">Cancel</button>
              <Button class="px-5 py-2 rounded-sm bg-[#0F5C5C] hover:bg-[#0D4A4A] text-white" :disabled="isSubmitting" @click="handleAddUser">{{ isSubmitting ? 'Adding...' : 'Add User' }}</Button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="showSuccessModal" class="fixed inset-0 z-50 flex items-center justify-center">
          <div class="absolute inset-0 bg-black/60" @click="showSuccessModal = false"></div>
          <div class="relative bg-white rounded-lg shadow-2xl w-full max-w-sm p-8 z-10 mx-4 text-center">
            <button @click="showSuccessModal = false" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 cursor-pointer">
              <X class="w-5 h-5" />
            </button>
            <div class="flex justify-center mb-4">
              <CheckCircle class="w-14 h-14 text-[#0F5C5C]" />
            </div>
            <h2 class="text-xl font-bold text-gray-800 mb-2">Success</h2>
            <p class="text-sm text-gray-600 mb-8">{{ successMessage }}</p>
            <Button class="px-8 py-2 rounded-sm bg-[#0F5C5C] hover:bg-[#0D4A4A] text-white" @click="showSuccessModal = false">OK</Button>
          </div>
        </div>
      </Transition>
    </Teleport>

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
                  <select v-model="editUser.role" @change="handleRoleChange(editUser)"
                    class="w-full border appearance-none border-gray-300 rounded-lg px-4 py-2 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-[#164980] focus:border-transparent transition">
                    <option value="SUPERADMIN">Superadmin</option>
                    <option value="OFFICE FRONTDESK">Office Frontdesk</option>
                  </select>
                  <ChevronDown
                    class="w-4 h-4 text-gray-500 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"
                  />
                </div>
              </div>
              <div v-if="editUser.role === 'OFFICE FRONTDESK'">
                <label class="block text-sm text-gray-700 mb-1">Office:</label>
                <div class="relative">
                  <select v-model="editUser.officeId"
                    class="w-full border appearance-none border-gray-300 rounded-lg px-4 py-2 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-[#164980] focus:border-transparent transition">
                    <option :value="null" disabled>Select an office</option>
                    <option v-for="office in offices" :key="office.id" :value="office.id">{{ formatOfficeOption(office) }}</option>
                  </select>
                  <ChevronDown
                    class="w-4 h-4 text-gray-500 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"
                  />
                </div>
              </div>
            </div>

            <p v-if="formError" class="mb-4 text-sm text-red-600">{{ formError }}</p>

            <div class="flex justify-end gap-3">
              <button @click="closeEditModal" class="px-5 py-2 rounded-sm border border-gray-300 text-gray-600 hover:bg-gray-50 text-sm font-medium cursor-pointer">Cancel</button>
              <Button class="px-5 py-2 rounded-sm bg-[#0F5C5C] hover:bg-[#0D4A4A] text-white" :disabled="isSubmitting" @click="handleSaveUser">{{ isSubmitting ? 'Saving...' : 'Save' }}</Button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { Button } from '@/components/ui/button'
import {
  Table, TableBody, TableCell, TableHead, TableHeader, TableRow
} from '@/components/ui/table'
import {
  SquarePen, X, MoreHorizontal, Eye, EyeOff, ChevronDown, CheckCircle
} from 'lucide-vue-next'
import { userManagementService } from '@/services/userManagement'

const offices = ref([])
const users = ref([])
const isLoading = ref(false)
const isSubmitting = ref(false)
const errorMessage = ref('')
const formError = ref('')

const showSuccessModal = ref(false)
const successMessage = ref('')

const showSuccess = (message) => {
  successMessage.value = message
  showSuccessModal.value = true
}

const formatOfficeOption = (office) => `${office.name} (${office.acronym})`

const normalizeUser = (user) => ({
  id: user.id,
  username: user.username,
  role: user.role?.name ?? 'SUPERADMIN',
  roleLabel: user.role?.label ?? 'Superadmin',
  officeId: user.office_id ?? null,
  office: user.office ?? null
})

const getOfficeLabel = (officeId) => {
  const office = offices.value.find((item) => item.id === officeId)
  return office ? formatOfficeOption(office) : '—'
}

const currentPage = ref(1)
const pageSize = 5
const summaryStart = computed(() => (users.value.length === 0 ? 0 : (currentPage.value - 1) * pageSize + 1))
const summaryEnd = computed(() => (users.value.length === 0 ? 0 : Math.min(currentPage.value * pageSize, users.value.length)))

const totalPages = computed(() => Math.max(1, Math.ceil(users.value.length / pageSize)))
const paginatedUsers = computed(() =>
  users.value.slice((currentPage.value - 1) * pageSize, currentPage.value * pageSize)
)

const activeDropdown = ref(null)
const activeUser = ref(null)
const dropdownPos = ref(null)

const closeDropdown = () => {
  activeDropdown.value = null
  activeUser.value = null
  dropdownPos.value = null
}

const toggleDropdown = (user, event) => {
  if (activeDropdown.value === user.id) {
    closeDropdown()
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
const newUser = ref({ username: '', password: '', role: 'OFFICE FRONTDESK', officeId: null })

const handleRoleChange = (form) => {
  if (form.role === 'SUPERADMIN') {
    form.officeId = null
  }
}

const extractErrorMessage = (error, fallback) => {
  if (error.response?.data?.errors) {
    const firstFieldErrors = Object.values(error.response.data.errors)[0]
    if (Array.isArray(firstFieldErrors) && firstFieldErrors.length > 0) {
      return firstFieldErrors[0]
    }
  }

  return error.response?.data?.message || fallback
}

const fetchUserManagementData = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    const [usersResponse, officesResponse] = await Promise.all([
      userManagementService.getUsers(),
      userManagementService.getOffices()
    ])

    users.value = usersResponse.data.map(normalizeUser)
    offices.value = officesResponse.data

    if (currentPage.value > totalPages.value) {
      currentPage.value = totalPages.value
    }
  } catch (error) {
    console.error('Failed to load user management data:', error)
    errorMessage.value = extractErrorMessage(error, 'Unable to load user management data.')
  } finally {
    isLoading.value = false
  }
}

const closeAddModal = () => {
  showAddModal.value = false
  showNewPassword.value = false
  formError.value = ''
  newUser.value = { username: '', password: '', role: 'OFFICE FRONTDESK', officeId: null }
}

const handleAddUser = async () => {
  if (!newUser.value.username.trim() || !newUser.value.password.trim()) {
    formError.value = 'Username and password are required.'
    return
  }

  if (newUser.value.role === 'OFFICE FRONTDESK' && !newUser.value.officeId) {
    formError.value = 'Office is required for Office Frontdesk users.'
    return
  }

  isSubmitting.value = true
  formError.value = ''

  try {
    const response = await userManagementService.createUser({
      username: newUser.value.username.trim(),
      password: newUser.value.password,
      role: newUser.value.role,
      office_id: newUser.value.role === 'OFFICE FRONTDESK' ? newUser.value.officeId : null
    })

    users.value = [...users.value, normalizeUser(response.data)].sort((left, right) =>
      left.username.localeCompare(right.username)
    )
    currentPage.value = totalPages.value
    closeAddModal()
    showSuccess('User added successfully.')
  } catch (error) {
    console.error('Failed to add user:', error)
    formError.value = extractErrorMessage(error, 'Unable to add user.')
  } finally {
    isSubmitting.value = false
  }
}

const showEditModal = ref(false)
const showEditPassword = ref(false)
const editUser = ref({ username: '', password: '', role: 'OFFICE FRONTDESK', officeId: null })
const userToEdit = ref(null)

const openEditModal = (user) => {
  userToEdit.value = user
  editUser.value = { username: user.username, password: '', role: user.role, officeId: user.officeId ?? null }
  showEditModal.value = true
  formError.value = ''
  closeDropdown()
}

const closeEditModal = () => {
  showEditModal.value = false
  showEditPassword.value = false
  formError.value = ''
  userToEdit.value = null
}

const handleSaveUser = async () => {
  if (!editUser.value.username.trim()) {
    formError.value = 'Username is required.'
    return
  }

  if (editUser.value.role === 'OFFICE FRONTDESK' && !editUser.value.officeId) {
    formError.value = 'Office is required for Office Frontdesk users.'
    return
  }

  isSubmitting.value = true
  formError.value = ''

  try {
    const payload = {
      username: editUser.value.username.trim(),
      role: editUser.value.role,
      office_id: editUser.value.role === 'OFFICE FRONTDESK' ? editUser.value.officeId : null
    }

    if (editUser.value.password.trim()) {
      payload.password = editUser.value.password
    }

    const response = await userManagementService.updateUser(userToEdit.value.id, payload)
    const updatedUser = normalizeUser(response.data)

    users.value = users.value
      .map((user) => (user.id === updatedUser.id ? updatedUser : user))
      .sort((left, right) => left.username.localeCompare(right.username))

    closeEditModal()
    showSuccess('User updated successfully.')
  } catch (error) {
    console.error('Failed to update user:', error)
    formError.value = extractErrorMessage(error, 'Unable to update user.')
  } finally {
    isSubmitting.value = false
  }
}

onMounted(() => {
  fetchUserManagementData()
})
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