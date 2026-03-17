import api from './api'

export const officeManagementService = {
  async getOffices() {
    const response = await api.get('/superadmin/office-management/offices')
    return response.data
  },

  async createOffice(formData) {
    const response = await api.post('/superadmin/office-management/offices', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    return response.data
  },

  async updateOffice(id, formData) {
    // Laravel's PUT doesn't support multipart, so we spoof it with POST + _method
    formData.append('_method', 'PUT')
    const response = await api.post(`/superadmin/office-management/offices/${id}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    return response.data
  },

  async deleteOffice(id) {
    const response = await api.delete(`/superadmin/office-management/offices/${id}`)
    return response.data
  }
}
