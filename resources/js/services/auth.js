import api from './api'

export const authService = {
  async login(credentials) {
    const response = await api.post('/login', credentials)
    if (response.data.success) {
      localStorage.setItem('token', response.data.data.token)
      localStorage.setItem('user', JSON.stringify(response.data.data.user))
    }
    return response.data
  },
  
  async logout() {
    const response = await api.post('/logout')
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    return response.data
  },
  
  async getUser() {
    const response = await api.get('/user')
    return response.data
  },
  
  async verify() {
    const response = await api.get('/verify')
    return response.data
  }
}