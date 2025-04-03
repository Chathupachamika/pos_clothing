<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import Button from './ui/Button.vue'
import { connection } from '../api/axios'
import Swal from 'sweetalert2'

const loading = ref(false)
const error = ref('')
const credentials = ref({
  name: '',
  email: '',
  password: '',
  confirmPassword: '',
  agreeToTerms: false
})
const showPassword = ref(false)
const passwordsMatch = ref(true)
const formIsValid = ref(false)

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

const validateForm = () => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  const passwordRegex = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/ 

  const isNameValid = credentials.value.name.length >= 3
  const isEmailValid = emailRegex.test(credentials.value.email)
  const isPasswordValid = passwordRegex.test(credentials.value.password)
  const isConfirmPasswordValid = credentials.value.password === credentials.value.confirmPassword
  const isTermsAccepted = credentials.value.agreeToTerms

  formIsValid.value = isNameValid && isEmailValid && isPasswordValid && isConfirmPasswordValid && isTermsAccepted
}

const validatePasswords = () => {
  if (credentials.value.password && credentials.value.confirmPassword) {
    passwordsMatch.value = credentials.value.password === credentials.value.confirmPassword
  } else {
    passwordsMatch.value = true
  }
  validateForm()
}

watch(credentials.value, () => {
  validateForm()
}, { deep: true })

const handleSignUp = async () => {
  if (!passwordsMatch.value) return
  if (!credentials.value.agreeToTerms) {
    error.value = 'Please agree to the Terms of Service'
    return
  }
  
  try {
    loading.value = true
    error.value = ''
    
    const response = await connection.post('/register', {
      name: credentials.value.name,
      email: credentials.value.email,
      password: credentials.value.password,
      password_confirmation: credentials.value.confirmPassword
    })

    if (response.data.token) {
      localStorage.setItem('token', response.data.token)
      await Swal.fire({
        position: "center",
        icon: "success",
        title: "Registration Successful!",
        text: "Please sign in with your new account",
        showConfirmButton: false,
        timer: 1500,
        background: '#1e293b',
        color: '#ffffff'
      })
      router.push({ name: 'signin' })
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to create account'
    await Swal.fire({
      position: "center",
      icon: "error",
      title: "Registration Failed!",
      text: error.value,
      showConfirmButton: true,
      background: '#1e293b',
      color: '#ffffff'
    })
  } finally {
    loading.value = false
  }
}

const navigateToSignin = () => {
  router.push('/')  
}
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
                Join us today.
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
              <h2 class="text-2xl font-bold text-white mb-2">Create your account</h2>
              <p class="text-gray-400">Sign up to get started with our POS system</p>
            </div>

            <form @submit.prevent="handleSignUp" class="space-y-6">
              <div v-if="error" class="p-4 bg-red-500/10 border border-red-500/20 rounded-lg text-red-500">
                {{ error }}
              </div>

              <div>
                <label for="name" class="block text-sm font-medium text-gray-300 mb-2">
                  Full Name
                </label>
                <input
                  id="name"
                  type="text"
                  v-model="credentials.name"
                  required
                  @input="validateForm"
                  class="w-full px-4 py-3 rounded-lg bg-gray-800 border border-gray-700 text-white focus:outline-none"
                  :class="credentials.name.length >= 3 ? 'focus:border-blue-500' : 'focus:border-red-500'"
                  placeholder="Enter your full name"
                  :disabled="loading"
                />
                <div v-if="credentials.name && credentials.name.length < 3" class="text-red-500 text-sm mt-1">
                  Name must be at least 3 characters
                </div>
              </div>

              <div>
                <label for="email" class="block text-sm font-medium text-gray-300 mb-2">
                  Email
                </label>
                <input
                  id="email"
                  type="email"
                  v-model="credentials.email"
                  required
                  @input="validateForm"
                  class="w-full px-4 py-3 rounded-lg bg-gray-800 border border-gray-700 text-white focus:outline-none"
                  :class="credentials.email && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(credentials.email) ? 'focus:border-blue-500' : 'focus:border-red-500'"
                  placeholder="Enter your email"
                  :disabled="loading"
                />
                <div v-if="credentials.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(credentials.email)" class="text-red-500 text-sm mt-1">
                  Please enter a valid email address
                </div>
              </div>

              <div>
                <label for="password" class="block text-sm font-medium text-gray-300 mb-2">
                  Password
                </label>
                <div class="relative">
                  <input
                    id="password"
                    :type="showPassword ? 'text' : 'password'"
                    v-model="credentials.password"
                    required
                    @input="validatePasswords"
                    class="w-full px-4 py-3 rounded-lg bg-gray-800 border border-gray-700 text-white focus:outline-none"
                    :class="credentials.password && /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/.test(credentials.password) ? 'focus:border-blue-500' : 'focus:border-red-500'"
                    placeholder="Create a password"
                    :disabled="loading"
                  />
                  <button
                    type="button"
                    @click="showPassword = !showPassword"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white"
                    :disabled="loading"
                  >
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      class="h-5 w-5"
                      :class="{ 'hidden': showPassword }"
                      viewBox="0 0 20 20"
                      fill="currentColor"
                    >
                      <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                      <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                    </svg>
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      class="h-5 w-5"
                      :class="{ 'hidden': !showPassword }"
                      viewBox="0 0 20 20"
                      fill="currentColor"
                    >
                      <path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd" />
                      <path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z" />
                    </svg>
                  </button>
                  <div v-if="credentials.password && !/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/.test(credentials.password)" class="text-red-500 text-sm mt-1">
                    Password must be at least 8 characters, including one letter and one number
                  </div>
                </div>
              </div>

              <div>
                <label for="confirmPassword" class="block text-sm font-medium text-gray-300 mb-2">
                  Confirm Password
                </label>
                <div class="relative">
                  <input
                    id="confirmPassword"
                    :type="showPassword ? 'text' : 'password'"
                    v-model="credentials.confirmPassword"
                    required
                    @input="validatePasswords"
                    class="w-full px-4 py-3 rounded-lg bg-gray-800 border text-white"
                    :class="[
                      passwordsMatch ? 'border-gray-700' : 'border-red-500',
                      passwordsMatch ? 'focus:border-blue-500' : 'focus:border-red-500'
                    ]"
                    placeholder="Confirm your password"
                    :disabled="loading"
                  />
                  <button
                    type="button"
                    @click="showPassword = !showPassword"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white"
                    :disabled="loading"
                  >
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      class="h-5 w-5"
                      :class="{ 'hidden': showPassword }"
                      viewBox="0 0 20 20"
                      fill="currentColor"
                    >
                      <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                      <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                    </svg>
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      class="h-5 w-5"
                      :class="{ 'hidden': !showPassword }"
                      viewBox="0 0 20 20"
                      fill="currentColor"
                    >
                      <path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd" />
                      <path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z" />
                    </svg>
                  </button>
                  <div v-if="!passwordsMatch && credentials.confirmPassword" class="text-red-500 text-sm mt-1">
                    Passwords do not match
                  </div>
                </div>
              </div>

              <div class="flex items-center">
                <input
                  id="agreeToTerms"
                  type="checkbox"
                  v-model="credentials.agreeToTerms"
                  required
                  class="h-4 w-4 rounded bg-gray-800 border-gray-700 text-blue-600 focus:ring-blue-500"
                  :disabled="loading"
                />
                <label for="agreeToTerms" class="ml-2 block text-sm text-gray-300">
                  I agree to the <a href="#" class="text-blue-400 hover:text-blue-300">Terms of Service</a> and <a href="#" class="text-blue-400 hover:text-blue-300">Privacy Policy</a>
                </label>
              </div>

              <button
                type="submit"
                :disabled="loading || !formIsValid"
                class="w-full px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {{ loading ? 'Creating Account...' : 'Create Account' }}
              </button>
            </form>

            <p class="mt-6 text-center text-sm text-gray-400">
              Already have an account?
              <a 
                @click.prevent="navigateToSignin" 
                href="#" 
                class="text-blue-400 hover:text-blue-300 font-medium cursor-pointer">
                Sign in
              </a>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.blur-3xl {
  --tw-blur: blur(64px);
  filter: var(--tw-blur);
}
</style>