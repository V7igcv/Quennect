import axios from 'axios'

// Kiosk API
const kioskApi = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

// Request interceptor - MAG-ADD NG TOKEN SA BAWAT REQUEST
kioskApi.interceptors.request.use(
  config => {
    const token = localStorage.getItem('token')
    
    // Add token to headers if it exists
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
      console.log('✅ Token added to request:', config.method.toUpperCase(), config.url)
    } else {
      console.log('⚠️ No token found for request:', config.method.toUpperCase(), config.url)
    }
    
    return config
  },
  error => {
    console.error('Request error:', error)
    return Promise.reject(error)
  }
)

// Response interceptor - HANDLE ERRORS
kioskApi.interceptors.response.use(
  response => {
    console.log('✅ API Response:', response.status, response.config.url)
    return response
  },
  error => {
    console.error('❌ API Error:', error.response?.status, error.config?.url)
    
    // Handle 401 Unauthorized - token expired or invalid
    if (error.response?.status === 401) {
      // Check if it's not a login request (to avoid infinite loop)
      const isLoginRequest = error.config?.url === '/login'
      
      if (!isLoginRequest) {
        console.log('Token expired or invalid. Redirecting to login...')
        localStorage.removeItem('token')
        localStorage.removeItem('user')
        window.location.href = '/login'
      }
    }
    
    return Promise.reject(error)
  }
)

export default kioskApi