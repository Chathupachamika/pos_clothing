<script setup>
import Button from './ui/Button.vue'
import { useRouter } from 'vue-router'
import { ref, onUnmounted, computed, onMounted } from 'vue'
import { connection } from '@/api/axios'
import Swal from 'sweetalert2'

const router = useRouter()

const circles = ref([
  { 
    x: 80, 
    y: 30, 
    size: 32, 
    speedX: 0.05, 
    speedY: 0.03, 
    directionX: 1, 
    directionY: 1,
    gradient: 'linear-gradient(135deg, rgba(37, 99, 235, 0.7), rgba(79, 70, 229, 0.7))',
    shadow: '0 0 30px rgba(37, 99, 235, 0.5)',
    scale: 1
  },
  { 
    x: 65, 
    y: 45, 
    size: 28, 
    speedX: 0.03, 
    speedY: 0.04, 
    directionX: -1, 
    directionY: 1,
    gradient: 'linear-gradient(135deg, rgba(59, 130, 246, 0.6), rgba(16, 185, 129, 0.6))',
    shadow: '0 0 20px rgba(59, 130, 246, 0.4)',
    scale: 1
  },
  
  { 
    x: 70, 
    y: 60, 
    size: 22, 
    speedX: 0.04, 
    speedY: 0.02, 
    directionX: 1, 
    directionY: -1,
    gradient: 'linear-gradient(135deg, rgba(124, 58, 237, 0.5), rgba(236, 72, 153, 0.5))',
    shadow: '0 0 15px rgba(124, 58, 237, 0.3)',
    scale: 1
  },
  { 
    x: 75, 
    y: 20, 
    size: 18, 
    speedX: 0.02, 
    speedY: 0.05, 
    directionX: -1, 
    directionY: -1,
    gradient: 'linear-gradient(135deg, rgba(6, 182, 212, 0.5), rgba(59, 130, 246, 0.5))',
    shadow: '0 0 15px rgba(6, 182, 212, 0.3)',
    scale: 1
  },
  
  { 
    x: 40, 
    y: 35, 
    size: 14, 
    speedX: 0.06, 
    speedY: 0.03, 
    directionX: 1, 
    directionY: 1,
    gradient: 'linear-gradient(135deg, rgba(14, 165, 233, 0.6), rgba(79, 70, 229, 0.6))',
    shadow: '0 0 15px rgba(14, 165, 233, 0.4)',
    scale: 1
  },
  { 
    x: 85, 
    y: 70, 
    size: 12, 
    speedX: 0.04, 
    speedY: 0.06, 
    directionX: -1, 
    directionY: -1,
    gradient: 'linear-gradient(135deg, rgba(236, 72, 153, 0.5), rgba(217, 70, 239, 0.5))',
    shadow: '0 0 12px rgba(236, 72, 153, 0.3)',
    scale: 1
  },
  
  { 
    x: 30, 
    y: 80, 
    size: 8, 
    speedX: 0.07, 
    speedY: 0.04, 
    directionX: -1, 
    directionY: 1,
    gradient: 'linear-gradient(135deg, rgba(16, 185, 129, 0.5), rgba(6, 182, 212, 0.5))',
    shadow: '0 0 10px rgba(16, 185, 129, 0.3)',
    scale: 1
  },
  { 
    x: 60, 
    y: 15, 
    size: 6, 
    speedX: 0.05, 
    speedY: 0.07, 
    directionX: 1, 
    directionY: -1,
    gradient: 'linear-gradient(135deg, rgba(245, 158, 11, 0.5), rgba(249, 115, 22, 0.5))',
    shadow: '0 0 8px rgba(245, 158, 11, 0.3)',
    scale: 1
  },
  { 
    x: 25, 
    y: 50, 
    size: 10, 
    speedX: 0.03, 
    speedY: 0.05, 
    directionX: -1, 
    directionY: 1,
    gradient: 'linear-gradient(135deg, rgba(139, 92, 246, 0.5), rgba(168, 85, 247, 0.5))',
    shadow: '0 0 10px rgba(139, 92, 246, 0.3)',
    scale: 1
  },
  { 
    x: 90, 
    y: 40, 
    size: 7, 
    speedX: 0.06, 
    speedY: 0.02, 
    directionX: 1, 
    directionY: -1,
    gradient: 'linear-gradient(135deg, rgba(20, 184, 166, 0.5), rgba(56, 189, 248, 0.5))',
    shadow: '0 0 8px rgba(20, 184, 166, 0.3)',
    scale: 1
  }
])

const animationActive = ref(true)
const animationFrame = ref(null)

onMounted(() => {
  startAnimation()
})

onUnmounted(() => {
  if (alertTimeout.value) clearTimeout(alertTimeout.value)
  animationActive.value = false
  if (animationFrame.value) cancelAnimationFrame(animationFrame.value)
})

const startAnimation = () => {
  const animate = () => {
    if (!animationActive.value) return
    
    circles.value.forEach((circle, index) => {
      circle.x += circle.speedX * circle.directionX
      circle.y += circle.speedY * circle.directionY
      
      if (Math.random() < 0.005) {
        circle.directionX *= -1
      }
      if (Math.random() < 0.005) {
        circle.directionY *= -1
      }
      
      if (Math.random() < 0.01) {
        circle.speedX = 0.02 + Math.random() * 0.05
        circle.speedY = 0.02 + Math.random() * 0.05
      }
      
      if (Math.random() < 0.02) {
        circle.scale = 0.95 + Math.random() * 0.1
      }
      
      if (circle.x > 95) {
        circle.directionX = -1
      } else if (circle.x < 5) {
        circle.directionX = 1
      }
      
      if (circle.y > 90) {
        circle.directionY = -1
      } else if (circle.y < 10) {
        circle.directionY = 1
      }
    })
    
    animationFrame.value = requestAnimationFrame(animate)
  }
  
  animate()
}

const showResetPopup = ref(false)
const resetStep = ref(1) 
const resetEmail = ref('')
const resetOTP = ref('')
const newPassword = ref('')
const confirmPassword = ref('')
const isLoading = ref(false)
const otpDigits = ref(['', '', '', '', '', ''])

const showAlert = ref(false)
const alertTimeout = ref(null)

const showSuccessAlert = () => {
  Swal.fire({
    icon: 'success',
    title: 'Success!',
    text: 'Your password has been reset successfully',
    confirmButtonColor: '#3085d6',
    confirmButtonText: 'OK',
    background: '#1a1a1a',
    color: '#fff',
  })
}

const handleSignup = () => {
  router.push({ name: 'signup' })
}

const email = ref('')
const password = ref('')
const loginError = ref('')
const emailError = ref('')
const passwordError = ref('')

const validateEmail = () => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!email.value) {
    emailError.value = 'Email is required'
    return false
  }
  if (!emailRegex.test(email.value)) {
    emailError.value = 'Please enter a valid email address'
    return false
  }
  emailError.value = ''
  return true
}

const validatePassword = () => {
  if (!password.value) {
    passwordError.value = 'Password is required'
    return false
  }
  if (password.value.length < 6) {
    passwordError.value = 'Password must be at least 6 characters'
    return false
  }
  passwordError.value = ''
  return true
}

const isFormValid = computed(() => {
  return email.value && password.value && !emailError.value && !passwordError.value
})

const handleLogin = async (event) => {
  event.preventDefault()
  loginError.value = ''
  
  const isEmailValid = validateEmail()
  const isPasswordValid = validatePassword()
  
  if (!isEmailValid || !isPasswordValid) {
    return
  }
  
  isLoading.value = true

  try {
    const response = await connection.post('/login', {
      email: email.value,
      password: password.value
    })

    if (response.data.status === 'success') {
      localStorage.setItem('token', response.data.token)
      localStorage.setItem('user', JSON.stringify(response.data.user))
      
      localStorage.setItem('isAdmin', String(response.data['is-admin']))
      
      if (response.data['is-admin']) {
        router.push({ name: 'dashboard' })
      } else {
        router.push({ name: 'place-order' }) 
      }
    }
  } catch (error) {
    if (error.response?.data) {
      loginError.value = error.response.data.message || 'Invalid credentials'
    } else {
      loginError.value = 'Login failed. Please try again.'
    }
    password.value = ''
  } finally {
    isLoading.value = false
  }
}

const openResetPopup = () => {
  showResetPopup.value = true
  resetStep.value = 1
}

const closeResetPopup = () => {
  showResetPopup.value = false
  resetStep.value = 1
  resetEmail.value = ''
  resetOTP.value = ''
  newPassword.value = ''
  confirmPassword.value = ''
  otpDigits.value = ['', '', '', '', '', '']
}

const resetError = ref('')

const handleEmailSubmit = async () => {
  if (!resetEmail.value) return
  
  isLoading.value = true
  resetError.value = ''
  
  try {
    const response = await connection.post('/forgot-password', {
      email: resetEmail.value
    })
    
    if (response.data.message) {
      resetStep.value = 2
    }
  } catch (error) {
    resetError.value = error.response?.data?.error || 'Failed to send reset email'
  } finally {
    isLoading.value = false
  }
}

const handleOTPSubmit = async () => {
  const enteredOTP = otpDigits.value.join('')
  
  if (enteredOTP.length !== 6) {
    resetError.value = 'Please enter all 6 digits'
    return
  }
  
  isLoading.value = true
  resetError.value = ''
  
  try {
    const response = await connection.post('/verify-otp', {
      email: resetEmail.value,
      otp: enteredOTP
    })
    
    if (response.data.message) {
      localStorage.setItem('reset_otp', enteredOTP)
      resetStep.value = 3
      resetError.value = ''
    }
  } catch (error) {
    resetError.value = error.response?.data?.error || 'Invalid OTP'
    otpDigits.value = ['', '', '', '', '', '']
    document.getElementById('otp-0')?.focus()
  } finally {
    isLoading.value = false
  }
}

const handlePasswordReset = async () => {
  if (newPassword.value !== confirmPassword.value) {
    resetError.value = 'Passwords do not match'
    return
  }
  
  if (newPassword.value.length < 8) {
    resetError.value = 'Password must be at least 8 characters'
    return
  }
  
  isLoading.value = true
  resetError.value = ''
  
  try {
    const storedOTP = localStorage.getItem('reset_otp')
    const response = await connection.post('/reset-password', {
      email: resetEmail.value,
      token: storedOTP,
      password: newPassword.value,
      password_confirmation: confirmPassword.value
    })
    
    if (response.data.message) {
      localStorage.removeItem('reset_otp')
      closeResetPopup()
      showSuccessAlert()
    }
  } catch (error) {
    resetError.value = error.response?.data?.error || 'Failed to reset password'
  } finally {
    isLoading.value = false
  }
}

const focusNextInput = (index) => {
  if (index < 5 && otpDigits.value[index]) {
    const nextInput = document.getElementById(`otp-${index + 1}`)
    if (nextInput) nextInput.focus()
  }
}

const handleOtpKeydown = (e, index) => {
  if (e.key === 'Backspace') {
    if (!otpDigits.value[index] && index > 0) {
      otpDigits.value[index - 1] = ''
      const prevInput = document.getElementById(`otp-${index - 1}`)
      if (prevInput) prevInput.focus()
    }
  }
}

const handleOtpPaste = (e) => {
  e.preventDefault()
  const pastedData = e.clipboardData.getData('text')
  const digits = pastedData.replace(/\D/g, '').split('').slice(0, 6)
  
  digits.forEach((digit, index) => {
    if (index < 6) {
      otpDigits.value[index] = digit
    }
  })
  
  const nextEmptyIndex = otpDigits.value.findIndex(d => !d)
  if (nextEmptyIndex !== -1) {
    document.getElementById(`otp-${nextEmptyIndex}`).focus()
  } else if (digits.length > 0) {
    document.getElementById('otp-5').focus()
  }
}

const showNewPassword = ref(false)
const showConfirmPassword = ref(false)
const showPassword = ref(false)
</script>

<template>
  <div class="min-h-screen flex items-center justify-center" style="background-color: #242424;">
    <div class="grid lg:grid-cols-2 w-full h-screen">
      <div class="hidden lg:flex flex-col justify-between p-12 relative overflow-hidden h-screen" style="background-color: #050A24;">
        <div class="relative z-10 flex flex-col">
          <div>
            <h1 class="text-3xl font-bold text-white mb-8">Layos Clothing</h1>
            <img 
              src="@/assets/Layos_clothing.jpg" 
              alt="POS System"
              class="w-full h-[32rem] object-cover rounded-lg mb-12"
            />
            <div class="space-y-4">
              <h2 class="text-5xl font-bold text-white tracking-tight">
                Welcome.
              </h2>
              <p class="text-3xl font-light text-blue-300 leading-relaxed">
                Start your journey now with our
                <span class="font-medium text-blue-200">management system!</span>
              </p>
            </div>
          </div>
        </div>
        <div class="absolute inset-0 bg-gradient-to-br from-blue-600/20 to-transparent"></div>
      </div>

      <div class="flex items-center justify-center w-full px-8 relative overflow-hidden">
        <div 
          v-for="(circle, index) in circles" 
          :key="index"
          class="absolute rounded-full shadow-lg"
          :style="{
            width: `${circle.size}px`,
            height: `${circle.size}px`,
            left: `${circle.x}%`,
            top: `${circle.y}%`,
            transform: `translate(-50%, -50%) scale(${circle.scale})`,
            background: circle.gradient,
            boxShadow: circle.shadow,
            transition: 'transform 0.5s ease-out'
          }"
        ></div>
        
        <div class="absolute top-0 right-0 w-96 h-96 bg-blue-600/10 rounded-full blur-3xl transform translate-x-1/2 -translate-y-1/2"></div>
        <div class="absolute bottom-0 right-1/4 w-80 h-80 bg-indigo-600/10 rounded-full blur-3xl"></div>
        
        <div class="w-full max-w-md rounded-2xl overflow-hidden relative z-10" style="background-color: #131316;">
          <div class="p-8">
            <div class="mb-8">
              <h2 class="text-2xl font-bold text-white mb-2">Login to your account</h2>
              <p class="text-gray-400">Please enter your credentials to continue</p>
            </div>

            <form @submit.prevent="handleLogin" class="space-y-6">
              <div>
                <label for="email" class="block text-sm font-medium text-gray-300 mb-2">
                  Email
                </label>
                <input
                  id="email"
                  v-model="email"
                  type="email"
                  required
                  @blur="validateEmail"
                  @input="validateEmail"
                  class="w-full px-4 py-3 rounded-lg bg-gray-800 border border-gray-700 text-white focus:outline-none focus:border-blue-500"
                  :class="{ 'border-red-500': emailError }"
                  placeholder="Enter your email"
                />
                <p v-if="emailError" class="mt-1 text-sm text-red-500">{{ emailError }}</p>
              </div>

              <div>
                <label for="password" class="block text-sm font-medium text-gray-300 mb-2">
                  Password
                </label>
                <div class="relative">
                  <input
                    id="password"
                    v-model="password"
                    :type="showPassword ? 'text' : 'password'"
                    required
                    @blur="validatePassword"
                    @input="validatePassword"
                    class="w-full px-4 py-3 rounded-lg bg-gray-800 border border-gray-700 text-white focus:outline-none focus:border-blue-500"
                    :class="{ 'border-red-500': passwordError }"
                    placeholder="Enter your password"
                  />
                  <button
                    type="button"
                    @click="showPassword = !showPassword"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center"
                  >
                    <svg
                      v-if="showPassword"
                      xmlns="http://www.w3.org/2000/svg"
                      class="h-5 w-5 text-gray-400 hover:text-gray-300"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="2"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    >
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                      <circle cx="12" cy="12" r="3" />
                    </svg>
                    <svg
                      v-else
                      xmlns="http://www.w3.org/2000/svg"
                      class="h-5 w-5 text-gray-400 hover:text-gray-300"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="2"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    >
                      <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
                      <line x1="1" y1="1" x2="23" y2="23" />
                    </svg>
                  </button>
                </div>
                <p v-if="passwordError" class="mt-1 text-sm text-red-500">{{ passwordError }}</p>
              </div>

              <div v-if="loginError" class="text-red-500 text-sm">
                {{ loginError }}
              </div>

              <div class="flex items-center justify-between">
                <div class="flex items-center">
                  <input
                    id="remember"
                    type="checkbox"
                    class="h-4 w-4 rounded bg-gray-800 border-gray-700 text-blue-600 focus:ring-blue-500"
                  />
                  <label for="remember" class="ml-2 block text-sm text-gray-300">
                    Remember me
                  </label>
                </div>
                <a 
                  @click.prevent="openResetPopup" 
                  href="#" 
                  class="text-sm text-blue-400 hover:text-blue-300"
                >
                  Forgot password?
                </a>
              </div>

              <Button
                type="submit"
                variant="primary"
                size="lg"
                class="w-full"
                :disabled="isLoading || !isFormValid"
              >
                {{ isLoading ? 'Signing in...' : 'Sign in' }}
              </Button>
            </form>

            <p class="mt-6 text-center text-sm text-gray-400">
              Don't have an account?
              <a 
                @click.prevent="handleSignup" 
                href="#" 
                class="text-blue-400 hover:text-blue-300 font-medium"
              >
                Sign up
              </a>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <transition name="fade">
    <div v-if="showResetPopup" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div class="bg-gray-900 w-full max-w-md rounded-2xl shadow-2xl overflow-hidden transform transition-all">
        <div class="flex items-center justify-between p-6 border-b border-gray-800">
          <div class="flex items-center space-x-3">
            <div class="bg-gradient-to-br from-purple-500 to-blue-600 w-10 h-10 rounded-full flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
              </svg>
            </div>
            <h2 class="text-xl font-bold text-white">
              {{ resetStep === 1 ? 'Reset Password' : resetStep === 2 ? 'Verification' : 'Create New Password' }}
            </h2>
          </div>
          <button @click="closeResetPopup" class="text-gray-500 hover:text-gray-300 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="p-6">
          <transition name="slide-fade" mode="out-in">
            <div v-if="resetStep === 1" class="space-y-5">
              <p class="text-gray-400">Enter your email address and we'll send you a verification code to reset your password.</p>
              
              <div class="space-y-2">
                <label for="reset-email" class="block text-sm font-medium text-gray-300">
                  Email Address
                </label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                      <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                  </div>
                  <input
                    id="reset-email"
                    v-model="resetEmail"
                    type="email"
                    class="w-full pl-10 pr-4 py-3 rounded-lg bg-gray-800 border border-gray-700 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="your-email@example.com"
                    required
                  />
                </div>
              </div>
              
              <button
                @click="handleEmailSubmit"
                :disabled="isLoading || !resetEmail"
                class="w-full flex items-center justify-center px-4 py-3 rounded-lg font-medium transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-900 focus:ring-blue-500"
                :class="[
                  isLoading || !resetEmail 
                    ? 'bg-gray-700 text-gray-400 cursor-not-allowed' 
                    : 'bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 text-white'
                ]"
              >
                <svg v-if="isLoading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ isLoading ? 'Sending...' : 'Send Verification Code' }}
              </button>
            </div>

            <div v-else-if="resetStep === 2" class="space-y-5">
              <p class="text-gray-400">
                We've sent a 6-digit verification code to <span class="text-blue-400">{{ resetEmail }}</span>. 
                Enter the code below to continue.
              </p>
              
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-300">
                  Verification Code
                </label>
                <div class="flex justify-between gap-2" @paste="handleOtpPaste">
                  <template v-for="(digit, index) in otpDigits" :key="index">
                    <input
                      :id="`otp-${index}`"
                      v-model="otpDigits[index]"
                      type="text"
                      maxlength="1"
                      class="w-12 h-12 text-center rounded-lg bg-gray-800 border border-gray-700 text-white text-xl font-bold focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      @input="focusNextInput(index)"
                      @keydown="handleOtpKeydown($event, index)"
                    />
                  </template>
                </div>
              </div>
              
              <div class="flex justify-between items-center">
                <button
                  @click="resetStep = 1"
                  class="text-sm text-gray-400 hover:text-white transition-colors"
                >
                  <span class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                    Back
                  </span>
                </button>
                <button class="text-sm text-blue-400 hover:text-blue-300 transition-colors">
                  Resend Code
                </button>
              </div>
              
              <button
                @click="handleOTPSubmit"
                :disabled="isLoading || otpDigits.some(d => !d)"
                class="w-full flex items-center justify-center px-4 py-3 rounded-lg font-medium transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-900 focus:ring-blue-500"
                :class="[
                  isLoading || otpDigits.some(d => !d)
                    ? 'bg-gray-700 text-gray-400 cursor-not-allowed' 
                    : 'bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 text-white'
                ]"
              >
                <svg v-if="isLoading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ isLoading ? 'Verifying...' : 'Verify Code' }}
              </button>
            </div>

            <div v-else-if="resetStep === 3" class="space-y-5">
              <p class="text-gray-400">Create a new password for your account. Make sure it's strong and secure.</p>
              
              <div class="space-y-4">
                <div class="space-y-2">
                  <label for="new-password" class="block text-sm font-medium text-gray-300">
                    New Password
                  </label>
                  <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                      </svg>
                    </div>
                    <input
                      id="new-password"
                      v-model="newPassword"
                      :type="showNewPassword ? 'text' : 'password'"
                      class="w-full pl-10 pr-12 py-3 rounded-lg bg-gray-800 border border-gray-700 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      placeholder="Enter new password"
                      required
                    />
                    <button
                      type="button"
                      @click="showNewPassword = !showNewPassword"
                      class="absolute inset-y-0 right-0 pr-3 flex items-center"
                    >
                      <svg
                        v-if="showNewPassword"
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5 text-gray-400 hover:text-gray-300"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                      >
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                        <circle cx="12" cy="12" r="3" />
                      </svg>
                      <svg
                        v-else
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5 text-gray-400 hover:text-gray-300"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                      >
                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
                        <line x1="1" y1="1" x2="23" y2="23" />
                      </svg>
                    </button>
                  </div>
                </div>
                
                <div class="space-y-2">
                  <label for="confirm-password" class="block text-sm font-medium text-gray-300">
                    Confirm Password
                  </label>
                  <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                      </svg>
                    </div>
                    <input
                      id="confirm-password"
                      v-model="confirmPassword"
                      :type="showConfirmPassword ? 'text' : 'password'"
                      class="w-full pl-10 pr-12 py-3 rounded-lg bg-gray-800 border border-gray-700 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      placeholder="Confirm new password"
                      required
                    />
                    <button
                      type="button"
                      @click="showConfirmPassword = !showConfirmPassword"
                      class="absolute inset-y-0 right-0 pr-3 flex items-center"
                    >
                      <svg
                        v-if="showConfirmPassword"
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5 text-gray-400 hover:text-gray-300"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                      >
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                        <circle cx="12" cy="12" r="3" />
                      </svg>
                      <svg
                        v-else
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5 text-gray-400 hover:text-gray-300"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                      >
                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
                        <line x1="1" y1="1" x2="23" y2="23" />
                      </svg>
                    </button>
                  </div>
                </div>
                
                <div class="space-y-2">
                  <p class="text-sm font-medium text-gray-300">Password Requirements:</p>
                  <ul class="space-y-1 text-sm text-gray-400">
                    <li class="flex items-center">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-green-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                      </svg>
                      At least 8 characters
                    </li>
                    <li class="flex items-center">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-green-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                      </svg>
                      Include uppercase & lowercase letters
                    </li>
                    <li class="flex items-center">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-green-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                      </svg>
                      Include at least one number
                    </li>
                  </ul>
                </div>
              </div>
              
              <div class="flex justify-between items-center">
                <button
                  @click="resetStep = 2"
                  class="text-sm text-gray-400 hover:text-white transition-colors"
                >
                  <span class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                    Back
                  </span>
                </button>
              </div>
              
              <button
                @click="handlePasswordReset"
                :disabled="isLoading || !newPassword || !confirmPassword || newPassword !== confirmPassword"
                class="w-full flex items-center justify-center px-4 py-3 rounded-lg font-medium transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-900 focus:ring-blue-500"
                :class="[
                  isLoading || !newPassword || !confirmPassword || newPassword !== confirmPassword
                    ? 'bg-gray-700 text-gray-400 cursor-not-allowed' 
                    : 'bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 text-white'
                ]"
              >
                <svg v-if="isLoading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ isLoading ? 'Updating...' : 'Reset Password' }}
              </button>
            </div>
          </transition>
          <div v-if="resetError" class="mt-4 text-red-500 text-sm">
            {{ resetError }}
          </div>
        </div>
      </div>
    </div>
  </transition>
</template>

<style scoped>
.blur-3xl {
  --tw-blur: blur(64px);
  filter: var(--tw-blur);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-fade-enter-active,
.slide-fade-leave-active {
  transition: all 0.3s ease;
}

.slide-fade-enter-from {
  opacity: 0;
  transform: translateY(20px);
}

.slide-fade-leave-to {
  opacity: 0;
  transform: translateY(-20px);
}

.alert-enter-active,
.alert-leave-active {
  transition: all 0.3s ease;
}

.alert-enter-from {
  opacity: 0;
  transform: translateX(20px);
}

.alert-leave-to {
  opacity: 0;
  transform: translateX(20px);
}

@keyframes float {
  0% {
    transform: translateY(0px) translateX(0px);
  }
  50% {
    transform: translateY(-10px) translateX(5px);
  }
  100% {
    transform: translateY(0px) translateX(0px);
  }
}
</style>