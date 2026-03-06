<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100 relative">
    <!-- Background Illustration -->
    <div class="absolute inset-0">
      <img src="/storage/images/LGU-Ligao.jpg" 
          class="w-full h-full object-cover opacity-90 brightness-90" 
          alt="background">
    </div>
    
    <!-- Login card -->
    <div class="relative z-10 bg-white p-15 rounded-xl shadow-xl w-150">
      <!-- Header with Logo -->
      <div class="text-center mb-8">
        <div class="h-20 w-20 bg-gray-200 rounded-full mx-auto mb-4 flex items-center justify-center">
          <img src="/storage/logos/Ligao City Seal.png" 
          class="h-20 w-20 rounded-full object-contain"
          alt="Ligao Logo">
        </div>
        <h2 class="text-3xl font-extrabold text-[#1F4E79]">Quennect</h2>
        <p class="text-sm text-[#1F4E79]">Ligao General Queuing Management System</p>
        <p class="text-md text-[#474C55] mt-5">Login to continue</p>
      </div>
      
      <!-- Login Form -->
      <form @submit.prevent="handleLogin">
        <!-- Username Field -->
        <div class="mb-4">
          <label class="block text-[#474C55] text-sm font-medium mb-2">
            Username
          </label>
          <input
            v-model="form.username"
            type="text"
            placeholder="Enter your Username"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#474C55] focus:border-transparent"
            required
          />
        </div>
        
        <!-- Password Field with Eye Icon (Font Awesome version) -->
        <div class="mb-6">
          <label class="block text-[#474C55] text-sm font-medium mb-2">
            Password
          </label>
          <div class="relative">
            <input
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              placeholder="Enter your Password"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#474C55] focus:border-transparent pr-10"
              required
            />
            <!-- Eye icon button -->
            <button 
              type="button"
              @click="togglePasswordVisibility"
              class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-600 hover:text-[#474C55] focus:outline-none cursor-pointer"
            >
              <font-awesome-icon :icon="['far', showPassword ? 'eye-slash' : 'eye']" class="w-5 h-5" />
            </button>
          </div>
        </div>
        
        <!-- Error Message -->
        <div v-if="error" class="mb-4 p-3 bg-red-50 border border-red-200 text-red-600 rounded-lg text-sm">
          {{ error }}
        </div>
        
        <!-- Login Button -->
        <button
          type="submit"
          :disabled="loading"
          class="w-full bg-[#0F5C5C] text-white py-2 px-4 rounded-lg hover:bg-[#167D7F] disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200 font-medium cursor-pointer"
        >
          <span v-if="loading">Logging in...</span>
          <span v-else>Login</span>
        </button>
      </form>
      
      <!-- Footer Note -->
      <p class="mt-4 text-xs text-center text-gray-500">
        This system is for authorized personnel only
      </p>
    </div>
  </div>
</template>

<script>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { authService } from '../../services/auth'

export default {
  name: 'Login',
  setup() {
    const router = useRouter()
    
    const form = ref({
      username: '',
      password: ''
    })
    
    const loading = ref(false)
    const error = ref(null)
    const showPassword = ref(false)
    
    const togglePasswordVisibility = () => {
      showPassword.value = !showPassword.value
    }
    
    const handleLogin = async () => {
      loading.value = true
      error.value = null
      
      try {
        const response = await authService.login(form.value)
        console.log('Login response:', response)
        
        if (response.success) {
          const user = response.data.user
          console.log('User data:', user)
          
          // Redirect based on role
          if (user.role === 'SUPERADMIN') {
            router.push('/superadmin')
          } else if (user.role === 'OFFICE FRONTDESK') {
            router.push('/frontdesk')
          } else {
            error.value = 'Invalid user role'
          }
        }
      } catch (err) {
        console.error('Login error:', err)
        
        // Handle different error scenarios
        if (err.response) {
          // The request was made and the server responded with a status code
          // that falls out of the range of 2xx
          if (err.response.status === 422) {
            error.value = err.response.data.message || 'Validation error'
          } else if (err.response.status === 401) {
            error.value = err.response.data.message || 'Invalid credentials'
          } else if (err.response.status === 403) {
            error.value = err.response.data.message || 'Access denied'
          } else {
            error.value = err.response.data.message || 'Login failed'
          }
        } else if (err.request) {
          // The request was made but no response was received
          error.value = 'No response from server. Please check your connection.'
        } else {
          // Something happened in setting up the request that triggered an Error
          error.value = 'Error: ' + err.message
        }
      } finally {
        loading.value = false
      }
    }
    
    return {
      form,
      loading,
      error,
      showPassword,
      togglePasswordVisibility,
      handleLogin
    }
  }
}
</script>