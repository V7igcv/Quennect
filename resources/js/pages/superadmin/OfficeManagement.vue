<template>
  <div class="max-w-7xl mx-auto px-2 sm:px-2 lg:px-2 py-2">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-gray-800">Office Management</h2>
      <Button class="px-4 py-2 bg-[#0F5C5C] hover:bg-[#0D4A4A] text-white" :disabled="isLoading || isSubmitting" @click="showAddModal = true">
        Add New Office
      </Button>
    </div>

    <div v-if="errorMessage" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      {{ errorMessage }}
    </div>

    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center">
          <div class="absolute inset-0 bg-black/60" @click="closeAddModal"></div>
          <div class="relative bg-white rounded-lg shadow-2xl w-full max-w-2xl p-8 z-10 mx-4">
            <button @click="closeAddModal" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 transition-colors cursor-pointer">
              <X class="w-5 h-5" />
            </button>
            <h2 class="text-2xl font-bold text-[#0F5C5C] mb-6">Add Office</h2>

            <div class="grid grid-cols-1 md:grid-cols-5 gap-5 mb-8">
              <div class="md:col-span-3 space-y-5">
                <div>
                  <label class="block text-sm text-gray-700 mb-2">Office Name:</label>
                  <input
                    v-model="newOfficeName"
                    type="text"
                    placeholder="Enter Office Name"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#164980] focus:border-transparent transition"
                  />
                </div>

                <div>
                  <label class="block text-sm text-gray-700 mb-2">Office Acronym:</label>
                  <input
                    v-model="newOfficeAcronym"
                    type="text"
                    placeholder="Enter Office Acronym"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#164980] focus:border-transparent transition"
                  />
                </div>

                <div>
                  <label class="block text-sm text-gray-700 mb-2">Office Description:</label>
                  <textarea
                    v-model="newOfficeDescription"
                    rows="4"
                    placeholder="Enter Office Description"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-[#164980] focus:border-transparent transition"
                  ></textarea>
                </div>
              </div>

              <div class="md:col-span-2 flex flex-col">
                <label class="block text-sm text-gray-700 mb-2">Office Logo:</label>

                <div
                  class="flex-1 border-2 border-dashed border-gray-300 rounded-lg p-5 flex flex-col items-center justify-center gap-2 cursor-pointer hover:border-[#164980] transition-colors"
                  @click="triggerAddLogoInput"
                >
                  <template v-if="newLogoPreview">
                    <img :src="newLogoPreview" class="w-20 h-20 object-contain rounded-full" alt="Preview" />
                    <span class="text-xs text-gray-500">Click to change</span>
                  </template>

                  <template v-else>
                    <ImagePlus class="w-8 h-8 text-gray-400" />
                    <span class="text-sm text-gray-500">Click to upload logo</span>
                    <span class="text-xs text-gray-400">PNG, JPG up to 2MB</span>
                  </template>
                </div>

                <input ref="addLogoInput" type="file" accept="image/*" class="hidden" @change="onAddLogoChange" />
              </div>
            </div>

            <p v-if="formError" class="mb-4 text-sm text-red-600">{{ formError }}</p>

            <div class="flex justify-end gap-3">
              <button @click="closeAddModal" :disabled="isSubmitting" class="px-5 py-2 rounded-sm border border-gray-300 text-gray-600 hover:bg-gray-50 text-sm font-medium transition-colors cursor-pointer">
                Cancel
              </button>
              <Button class="px-5 py-2 bg-[#0F5C5C] hover:bg-[#0D4A4A] text-white" :disabled="isSubmitting" @click="handleAddOffice">
                {{ isSubmitting ? 'Adding...' : 'Add Office' }}
              </Button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center">
          <div class="absolute inset-0 bg-black/60" @click="closeEditModal"></div>
          <div class="relative bg-white rounded-lg shadow-2xl w-full max-w-2xl p-8 z-10 mx-4">
            <button @click="closeEditModal" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 transition-colors cursor-pointer">
              <X class="w-5 h-5" />
            </button>
            <h2 class="text-2xl font-bold text-[#0F5C5C] mb-6">Edit Office</h2>

            <div class="grid grid-cols-1 md:grid-cols-5 gap-5 mb-8">
              <div class="md:col-span-3 space-y-5">
                <div>
                  <label class="block text-sm text-gray-700 mb-2">Office Name:</label>
                  <input
                    v-model="editOfficeName"
                    type="text"
                    placeholder="Enter Office Name"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#164980] focus:border-transparent transition"
                  />
                </div>

                <div>
                  <label class="block text-sm text-gray-700 mb-2">Office Acronym:</label>
                  <input
                    v-model="editOfficeAcronym"
                    type="text"
                    placeholder="Enter Office Acronym"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#164980] focus:border-transparent transition"
                  />
                </div>

                <div>
                  <label class="block text-sm text-gray-700 mb-2">Office Description:</label>
                  <textarea
                    v-model="editOfficeDescription"
                    rows="4"
                    placeholder="Enter Office Description"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-[#164980] focus:border-transparent transition"
                  ></textarea>
                </div>

                <div>
                  <label class="block text-sm text-gray-700 mb-2">Status:</label>

                  <div class="relative">
                    <select
                      v-model="editOfficeStatus"
                      class="w-full appearance-none border border-gray-300 rounded-lg px-4 py-2 pr-12 text-sm focus:outline-none focus:ring-2 focus:ring-[#164980] focus:border-transparent transition"
                    >
                      <option value="Active">Active</option>
                      <option value="Inactive">Inactive</option>
                    </select>

                    <!-- Custom arrow -->
                    <ChevronDown class="w-4 h-4 text-gray-500 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none" />
                  </div>
                </div>
              </div>

              <div class="md:col-span-2 flex flex-col">
                <label class="block text-sm text-gray-700 mb-2">Office Logo:</label>
                <div
                  class="flex-1 border-2 border-dashed border-gray-300 rounded-lg p-5 flex flex-col items-center justify-center gap-2 cursor-pointer hover:border-[#164980] transition-colors"
                  @click="triggerEditLogoInput"
                >
                  <template v-if="editLogoPreview">
                    <img :src="editLogoPreview" class="w-20 h-20 object-contain rounded-full" alt="Preview" />
                    <span class="text-xs text-gray-500">Click to change</span>
                  </template>
                  <template v-else>
                    <ImagePlus class="w-8 h-8 text-gray-400" />
                    <span class="text-sm text-gray-500">Click to upload logo</span>
                    <span class="text-xs text-gray-400">PNG, JPG up to 2MB</span>
                  </template>
                </div>
                <input ref="editLogoInput" type="file" accept="image/*" class="hidden" @change="onEditLogoChange" />
              </div>
            </div>

            <p v-if="formError" class="mb-4 text-sm text-red-600">{{ formError }}</p>

            <div class="flex justify-end gap-3">
              <button @click="closeEditModal" :disabled="isSubmitting" class="px-5 py-2 rounded-sm border border-gray-300 text-gray-600 hover:bg-gray-50 text-sm font-medium transition-colors cursor-pointer">
                Cancel
              </button>
              <Button class="px-5 py-2 bg-[#0F5C5C] hover:bg-[#0D4A4A] text-white" :disabled="isSubmitting" @click="handleSaveOffice">
                {{ isSubmitting ? 'Saving...' : 'Save' }}
              </Button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <Teleport to="body">
      <Transition name="modal-fade">
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center">
          <div class="absolute inset-0 bg-black/60" @click="closeDeleteModal"></div>
          <div class="relative bg-white rounded-lg shadow-2xl w-full max-w-sm p-8 z-10 mx-4">
            <button @click="closeDeleteModal" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 transition-colors cursor-pointer">
              <X class="w-5 h-5" />
            </button>

            <div class="flex flex-col items-center text-center mb-6">
              <div class="w-14 h-14 rounded-full bg-red-100 flex items-center justify-center mb-4">
                <Trash2 class="w-7 h-7 text-red-500" />
              </div>
              <h2 class="text-xl font-bold text-gray-800 mb-2">Delete Office</h2>
              <p class="text-sm text-gray-500">
                Are you sure you want to delete
                <span class="font-semibold text-gray-700">{{ officeToDelete?.name }} ({{ officeToDelete?.acronym }})</span>?
                This action cannot be undone.
              </p>
            </div>

            <div class="flex justify-end gap-3">
              <button @click="closeDeleteModal" :disabled="isSubmitting" class="px-5 py-2 rounded-sm border border-gray-300 text-gray-600 hover:bg-gray-50 text-sm font-medium transition-colors cursor-pointer">
                Cancel
              </button>
              <button @click="handleDeleteOffice" :disabled="isSubmitting" class="px-5 py-2 rounded-sm bg-red-500 hover:bg-red-600 text-white text-sm font-medium transition-colors cursor-pointer">
                {{ isSubmitting ? 'Deleting...' : 'Delete' }}
              </button>
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

    <div v-if="isLoading" class="flex justify-center items-center py-16 text-gray-400">
      Loading offices...
    </div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="office in offices"
        :key="office.id"
        @click="navigateToServices(office)"
        class="bg-white rounded-lg shadow p-6 flex flex-col items-center justify-center relative hover:shadow-md transition-shadow border border-gray-100 cursor-pointer"
      >
        <div class="absolute top-4 right-4 flex gap-2">
          <button class="text-gray-400 hover:text-[#164980] transition-colors cursor-pointer" @click.stop="openEditModal(office)">
            <SquarePen class="w-4.5 h-4.5" stroke-width="1.5" />
          </button>
          <button class="text-gray-400 hover:text-red-500 transition-colors cursor-pointer" @click.stop="openDeleteModal(office)">
            <Trash2 class="w-4.5 h-4.5" stroke-width="1.5" />
          </button>
        </div>

        <div class="w-16 h-16 rounded-full border-[3px] border-[#0F5C5C] bg-[#BCEDE4] flex items-center justify-center mb-4 mt-2 overflow-hidden shadow-inner p-1">
          <img v-if="office.logo" :src="office.logo" :alt="office.name + ' Logo'" class="w-full h-full object-contain" />
          <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-[#0F5C5C]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
          </svg>
        </div>

        <h3 class="text-center font-bold text-[15px] text-[#164980] leading-snug tracking-wide">
          {{ office.name }}
        </h3>
        <p class="mt-1 text-xs font-semibold tracking-wide text-[#0F5C5C]">
          {{ office.acronym }}
        </p>
        <p class="mt-2 text-xs text-gray-500 text-center line-clamp-2">
          {{ office.description }}
        </p>
      </div>

      <div v-if="offices.length === 0" class="col-span-full text-center py-16 text-gray-400">
        No offices found.
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { Button } from '@/components/ui/button'
import { CheckCircle, ChevronDown, ImagePlus, SquarePen, Trash2, X } from 'lucide-vue-next'
import { officeManagementService } from '@/services/officeManagement'

const router = useRouter()

const addLogoInput = ref(null)
const editLogoInput = ref(null)
const newLogoFile = ref(null)
const editLogoFile = ref(null)

const offices = ref([])
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

const extractErrorMessage = (error, fallback) => {
  if (error.response?.data?.errors) {
    const firstFieldErrors = Object.values(error.response.data.errors)[0]
    if (Array.isArray(firstFieldErrors) && firstFieldErrors.length > 0) {
      return firstFieldErrors[0]
    }
  }
  return error.response?.data?.message || fallback
}

const fetchOffices = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const response = await officeManagementService.getOffices()
    offices.value = response.data
  } catch (error) {
    console.error('Failed to load offices:', error)
    errorMessage.value = extractErrorMessage(error, 'Unable to load offices.')
  } finally {
    isLoading.value = false
  }
}

const navigateToServices = (office) => {
  router.push({ name: 'OfficeServices', params: { id: office.id, name: encodeURIComponent(office.name) } })
}

// ── Add modal ─────────────────────────────────────────
const showAddModal = ref(false)
const newOfficeName = ref('')
const newOfficeAcronym = ref('')
const newOfficeDescription = ref('')
const newLogoPreview = ref(null)

const closeAddModal = () => {
  showAddModal.value = false
  newOfficeName.value = ''
  newOfficeAcronym.value = ''
  newOfficeDescription.value = ''
  newLogoPreview.value = null
  newLogoFile.value = null
  formError.value = ''
  if (addLogoInput.value) addLogoInput.value.value = ''
}

const triggerAddLogoInput = () => { addLogoInput.value?.click() }

const onAddLogoChange = (event) => {
  const file = event.target.files?.[0]
  if (file) {
    newLogoPreview.value = URL.createObjectURL(file)
    newLogoFile.value = file
  }
}

const handleAddOffice = async () => {
  if (!newOfficeName.value.trim() || !newOfficeAcronym.value.trim()) {
    formError.value = 'Office name and acronym are required.'
    return
  }
  if (!newOfficeDescription.value.trim()) {
    formError.value = 'Office description is required.'
    return
  }

  isSubmitting.value = true
  formError.value = ''

  try {
    const formData = new FormData()
    formData.append('name', newOfficeName.value.trim())
    formData.append('acronym', newOfficeAcronym.value.trim())
    formData.append('description', newOfficeDescription.value.trim())
    if (newLogoFile.value) formData.append('logo', newLogoFile.value)

    const response = await officeManagementService.createOffice(formData)
    offices.value = [...offices.value, response.data].sort((a, b) => a.name.localeCompare(b.name))
    closeAddModal()
    showSuccess('Office added successfully.')
  } catch (error) {
    console.error('Failed to add office:', error)
    formError.value = extractErrorMessage(error, 'Unable to add office.')
  } finally {
    isSubmitting.value = false
  }
}

// ── Edit modal ─────────────────────────────────────────
const showEditModal = ref(false)
const editOfficeName = ref('')
const editOfficeAcronym = ref('')
const editOfficeDescription = ref('')
const editOfficeStatus = ref('Active')
const editLogoPreview = ref(null)
const officeToEdit = ref(null)

const openEditModal = (office) => {
  officeToEdit.value = office
  editOfficeName.value = office.name
  editOfficeAcronym.value = office.acronym
  editOfficeDescription.value = office.description
  editOfficeStatus.value = office.status || 'Active'
  editLogoPreview.value = office.logo
  editLogoFile.value = null
  formError.value = ''
  showEditModal.value = true
}

const closeEditModal = () => {
  showEditModal.value = false
  editOfficeName.value = ''
  editOfficeAcronym.value = ''
  editOfficeDescription.value = ''
  editOfficeStatus.value = 'Active'
  editLogoPreview.value = null
  editLogoFile.value = null
  officeToEdit.value = null
  formError.value = ''
  if (editLogoInput.value) editLogoInput.value.value = ''
}

const triggerEditLogoInput = () => { editLogoInput.value?.click() }

const onEditLogoChange = (event) => {
  const file = event.target.files?.[0]
  if (file) {
    editLogoPreview.value = URL.createObjectURL(file)
    editLogoFile.value = file
  }
}

const handleSaveOffice = async () => {
  if (!editOfficeName.value.trim() || !editOfficeAcronym.value.trim()) {
    formError.value = 'Office name and acronym are required.'
    return
  }
  if (!editOfficeDescription.value.trim()) {
    formError.value = 'Office description is required.'
    return
  }

  isSubmitting.value = true
  formError.value = ''

  try {
    const formData = new FormData()
    formData.append('name', editOfficeName.value.trim())
    formData.append('acronym', editOfficeAcronym.value.trim())
    formData.append('description', editOfficeDescription.value.trim())
    formData.append('status', editOfficeStatus.value)
    if (editLogoFile.value) formData.append('logo', editLogoFile.value)

    const response = await officeManagementService.updateOffice(officeToEdit.value.id, formData)
    const updated = response.data
    offices.value = offices.value
      .map((o) => (o.id === updated.id ? updated : o))
      .sort((a, b) => a.name.localeCompare(b.name))
    closeEditModal()
    showSuccess('Office updated successfully.')
  } catch (error) {
    console.error('Failed to update office:', error)
    formError.value = extractErrorMessage(error, 'Unable to update office.')
  } finally {
    isSubmitting.value = false
  }
}

// ── Delete modal ───────────────────────────────────────
const showDeleteModal = ref(false)
const officeToDelete = ref(null)

const openDeleteModal = (office) => {
  officeToDelete.value = office
  showDeleteModal.value = true
}

const closeDeleteModal = () => {
  showDeleteModal.value = false
  officeToDelete.value = null
}

const handleDeleteOffice = async () => {
  isSubmitting.value = true
  try {
    await officeManagementService.deleteOffice(officeToDelete.value.id)
    offices.value = offices.value.filter((o) => o.id !== officeToDelete.value.id)
    closeDeleteModal()
    showSuccess('Office deleted successfully.')
  } catch (error) {
    console.error('Failed to delete office:', error)
    closeDeleteModal()
    errorMessage.value = extractErrorMessage(error, 'Unable to delete office.')
  } finally {
    isSubmitting.value = false
  }
}

onMounted(() => { fetchOffices() })
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