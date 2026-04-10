import api from './api'

export const serviceManagementService = {
  async getOfficeServices(officeId) {
    const response = await api.get(`/superadmin/office-management/offices/${officeId}/services`)
    return response.data
  },

  async createService(officeId, payload) {
    const response = await api.post(`/superadmin/office-management/offices/${officeId}/services`, payload)
    return response.data
  },

  async updateService(officeId, serviceId, payload) {
    const response = await api.put(`/superadmin/office-management/offices/${officeId}/services/${serviceId}`, payload)
    return response.data
  },

  async deleteService(officeId, serviceId) {
    const response = await api.delete(`/superadmin/office-management/offices/${officeId}/services/${serviceId}`)
    return response.data
  },

  async toggleIsFree(officeId, serviceId) {
    const response = await api.patch(`/superadmin/office-management/offices/${officeId}/services/${serviceId}/toggle-is-free`)
    return response.data
  },

  async toggleStatus(officeId, serviceId) {
    const response = await api.patch(`/superadmin/office-management/offices/${officeId}/services/${serviceId}/toggle-status`)
    return response.data
  },

  async toggleProvidesAssistance(officeId, serviceId) {
    const response = await api.patch(`/superadmin/office-management/offices/${officeId}/services/${serviceId}/toggle-provides-assistance`)
    return response.data
  }
}
