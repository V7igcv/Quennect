import api from './api'

export const userManagementService = {
  async getUsers() {
    const response = await api.get('/superadmin/user-management/users')
    return response.data
  },

  async getOffices() {
    const response = await api.get('/superadmin/user-management/offices')
    return response.data
  },

  async createUser(payload) {
    const response = await api.post('/superadmin/user-management/users', payload)
    return response.data
  },

  async updateUser(userId, payload) {
    const response = await api.put(`/superadmin/user-management/users/${userId}`, payload)
    return response.data
  }
}
