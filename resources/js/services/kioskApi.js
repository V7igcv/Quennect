import axios from 'axios'

// Kiosk API - NO AUTH required
const kioskApi = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

// Simple logging lang for kiosk
kioskApi.interceptors.request.use(
  config => {
    console.log('Kiosk API Request:', config.method.toUpperCase(), config.url)
    return config
  },
  error => Promise.reject(error)
)

kioskApi.interceptors.response.use(
  response => {
    console.log('Kiosk API Response:', response.status, response.config.url)
    return response
  },
  error => {
    console.error('Kiosk API Error:', error.response?.status, error.config?.url)
    return Promise.reject(error)
  }
)

export default kioskApi