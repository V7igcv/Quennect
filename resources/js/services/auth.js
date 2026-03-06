import api from './api'

export const authService = {
  async login(credentials) {
    try {
      const response = await api.post('/login', credentials)
      if (response.data.success) {
        localStorage.setItem('token', response.data.data.token)
        localStorage.setItem('user', JSON.stringify(response.data.data.user))
      }
      return response.data
    } catch (error) {
      // Clear any partial data if login fails
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      throw error
    }
  },
  
  async logout() {
    try {
      const response = await api.post('/logout')
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      return response.data
    } catch (error) {
      // Even if API fails, clear local storage
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      throw error
    }
  },
  
  async getUser() {
    const response = await api.get('/user')
    return response.data
  },
  
  async verify() {
    try {
      const response = await api.get('/verify')
      return response.data
    } catch (error) {
      // If verification fails, clear local storage
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      throw error
    }
  },
  
  // Helper method to get current user from localStorage
  getCurrentUser() {
    const userStr = localStorage.getItem('user')
    return userStr ? JSON.parse(userStr) : null
  },
  
  // Helper method to check if user is authenticated
  isAuthenticated() {
    return !!localStorage.getItem('token')
  }
}