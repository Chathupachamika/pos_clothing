<script setup>
import { ref, computed, onMounted } from 'vue'
import { 
  XMarkIcon, 
  BellIcon, 
  ChatBubbleLeftRightIcon, 
  UserIcon,
  ArrowRightOnRectangleIcon,
  PencilIcon,
  CheckIcon,
  Cog6ToothIcon,
  ClockIcon,
  ChevronDownIcon,
  ShieldExclamationIcon, 
  InformationCircleIcon,
  ExclamationTriangleIcon,
  ArrowPathIcon
} from '@heroicons/vue/24/outline'
import { connection } from '@/api/axios'

const showProfilePopup = ref(false)
const showNotifications = ref(false)

const userProfile = ref({
    name: '',
    email: '',
    role: '',
    location: 'Colombo, Sri Lanka', 
    phone: '',
    status: 'Active'
})

const loadUserProfile = () => {
    const userData = JSON.parse(localStorage.getItem('user'))
    if (userData) {
        userProfile.value = {
            name: userData.name || '',
            email: userData.email || '',
            role: userData.role || 'User',
            location: userData.location || 'Colombo, Sri Lanka',
            phone: userData.phone || '',
            status: userData.status || 'Active'
        }
    }
}

const handleSignOut = () => {
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    window.location.href = '/login'
}

const notifications = ref([])
const lowStockItems = ref([])

const fetchLowStockItems = async () => {
    try {
        const response = await connection.get('/inventory/low-stock')
        const items = response.data.map(item => ({
            id: `low-stock-${item.product_id}`,
            title: 'Low Stock Alert',
            message: `${item.product_name} is running low (${item.quantity} units remaining)`,
            details: {
                supplierId: item.supplier_id,
                supplierName: item.supplier_name,
                supplierEmail: item.supplier_email,
                productId: item.product_id,
                productName: item.product_name,
                quantity: item.quantity,
                location: item.location,
                status: item.status
            },
            severity: item.quantity <= 5 ? 'critical' : 'warning',
            time: new Date().toLocaleString(),
            read: false,
            type: 'low-stock'
        }))
        
        items.sort((a, b) => {
            if (a.severity === b.severity) {
                return a.details.quantity - b.details.quantity;
            }
            return a.severity === 'critical' ? -1 : 1;
        });
        
        notifications.value = [...items, ...notifications.value]
    } catch (error) {
        console.error('Failed to fetch low stock items:', error)
    }
}

onMounted(() => {
    loadUserProfile()
    fetchLowStockItems()
})

const toggleProfile = () => {
    showProfilePopup.value = !showProfilePopup.value
    if (showProfilePopup.value) showNotifications.value = false
}

const toggleNotifications = () => {
    showNotifications.value = !showNotifications.value
    if (showNotifications.value) showProfilePopup.value = false
}

const markAsRead = (id) => {
    const notification = notifications.value.find(n => n.id === id)
    if (notification) {
        notification.read = true
    }
}

const markAllAsRead = () => {
    notifications.value.forEach(notification => {
        notification.read = true
    })
}

const deleteNotification = (id) => {
    notifications.value = notifications.value.filter(n => n.id !== id)
}

const clearAllNotifications = () => {
    notifications.value = []
}

const getSeverityIcon = (severity) => {
    switch (severity) {
        case 'critical':
            return ExclamationTriangleIcon
        case 'warning':
            return ShieldExclamationIcon
        default:
            return InformationCircleIcon
    }
}

const refreshNotifications = async () => {
    await fetchLowStockItems()
}

const isEditing = ref(false)
const activeTab = ref('profile')
const editedProfile = ref({ ...userProfile.value })

const startEditing = () => {
    editedProfile.value = { ...userProfile.value }
    isEditing.value = true
}

const formErrors = ref({})
const isSaving = ref(false)

const fieldTypes = {
  name: { type: 'text', icon: UserIcon },
  email: { type: 'email', icon: ChatBubbleLeftRightIcon },
  phone: { type: 'tel', icon: ChatBubbleLeftRightIcon },
  role: { type: 'text', icon: UserIcon, disabled: true },
  location: { type: 'text', icon: UserIcon },
  status: { type: 'select', options: ['Active', 'Away', 'Busy'], icon: UserIcon }
}

const validateForm = () => {
  const errors = {}
  if (!editedProfile.value.name.trim()) {
    errors.name = 'Name is required'
  }
  if (!editedProfile.value.email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
    errors.email = 'Invalid email format'
  }
  if (!editedProfile.value.phone.match(/^\+?[\d\s-]{8,}$/)) {
    errors.phone = 'Invalid phone number'
  }
  formErrors.value = errors
  return Object.keys(errors).length === 0
}

const saveChanges = async () => {
  if (!validateForm()) return

  isSaving.value = true
  try {
    await connection.put('/user/profile', editedProfile.value)
    userProfile.value = { ...editedProfile.value }
    const userData = JSON.parse(localStorage.getItem('user'))
    localStorage.setItem('user', JSON.stringify({
        ...userData,
        ...editedProfile.value
    }))
    isEditing.value = false
  } catch (error) {
    console.error('Failed to update profile:', error)
  } finally {
    isSaving.value = false
  }
}

const hasChanges = computed(() => {
  return JSON.stringify(userProfile.value) !== JSON.stringify(editedProfile.value)
})

const cancelEditing = () => {
    editedProfile.value = { ...userProfile.value }
    isEditing.value = false
    formErrors.value = {}
}

const isProfileHovered = ref(false)
const unreadCount = computed(() => notifications.value.filter(n => !n.read).length)
</script>

<template>
    <header class="fixed top-0 left-0 right-0 z-50 bg-gradient-to-r from-gray-900 to-gray-800 backdrop-blur-sm border-b border-blue-900/30">
        <div class="max-w-[2000px] mx-auto px-6">
            <div class="flex justify-between items-center h-20">
                <div class="flex items-center space-x-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                    </svg>
                    <h1 class="text-2xl font-bold text-white tracking-tight">
                        Layos
                        <span class="bg-clip-text text-transparent bg-gradient-to-r from-blue-400 to-cyan-400 font-extrabold">Clothing</span>
                    </h1>
                </div>

                <div class="flex items-center space-x-6">
                    <div class="relative notifications-container">
                        <button @click="toggleNotifications"
                            class="group p-2.5 rounded-lg hover:bg-white/5 active:bg-white/10 transition-all duration-200 relative">
                            <div class="relative">
                                <BellIcon class="w-6 h-6 text-gray-100 group-hover:text-blue-400 transition-colors" />
                                <span v-if="unreadCount > 0"
                                    class="notification-badge absolute -top-2 -right-2 bg-gradient-to-r from-blue-500 to-cyan-500 text-xs font-semibold text-white w-5 h-5 flex items-center justify-center rounded-full ring-2 ring-gray-900">
                                    {{ unreadCount }}
                                </span>
                            </div>
                        </button>

                        <div v-if="showNotifications" class="notification-dropdown absolute right-0 top-14 w-[480px] bg-gray-800 rounded-xl shadow-2xl border border-blue-900/50 overflow-hidden">
                            <div class="p-4 border-b border-gray-700 flex justify-between items-center bg-gradient-to-r from-gray-800 to-gray-900">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-blue-500/10 rounded-lg">
                                        <BellIcon class="w-5 h-5 text-blue-400" />
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-white">Notifications</h3>
                                        <p class="text-xs text-gray-400">You have {{ unreadCount }} unread alerts</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button @click="refreshNotifications" 
                                        class="p-2 hover:bg-gray-700/50 rounded-lg text-gray-400 hover:text-blue-400 transition-colors">
                                        <ArrowPathIcon class="w-5 h-5" />
                                    </button>
                                    <button @click="markAllAsRead" v-if="unreadCount > 0"
                                        class="text-xs font-medium px-2 py-1 rounded bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 transition-colors">
                                        Mark all read
                                    </button>
                                    <button @click="clearAllNotifications" v-if="notifications.length > 0"
                                        class="text-xs font-medium px-2 py-1 rounded bg-gray-700 text-gray-300 hover:bg-gray-600 transition-colors">
                                        Clear all
                                    </button>
                                    <button @click="toggleNotifications" class="p-1 hover:bg-gray-700 rounded-lg transition-colors">
                                        <XMarkIcon class="w-5 h-5 text-gray-400 hover:text-white" />
                                    </button>
                                </div>
                            </div>

                            <div class="max-h-[480px] overflow-y-auto">
                                <div v-if="notifications.length === 0" class="p-8 text-center">
                                    <div class="mx-auto w-16 h-16 rounded-full bg-gray-700/50 flex items-center justify-center mb-3">
                                        <BellIcon class="w-8 h-8 text-gray-500" />
                                    </div>
                                    <h4 class="text-gray-400 font-medium">No notifications</h4>
                                    <p class="text-gray-500 text-sm mt-1">You're all caught up!</p>
                                </div>
                                
                                <div v-for="notification in notifications" :key="notification.id"
                                    class="group border-b border-gray-700/50 hover:bg-gray-700/50 transition-all duration-200"
                                    :class="{ 'bg-gray-700/30': !notification.read }">
                                    <div class="p-4">
                                        <div class="flex items-start gap-4">
                                            <div class="flex-shrink-0 p-2 rounded-lg"
                                                :class="{
                                                    'bg-red-500/10': notification.severity === 'critical',
                                                    'bg-yellow-500/10': notification.severity === 'warning',
                                                    'bg-blue-500/10': !notification.severity
                                                }">
                                                <component :is="getSeverityIcon(notification.severity)" 
                                                    class="w-5 h-5"
                                                    :class="{
                                                        'text-red-400': notification.severity === 'critical',
                                                        'text-yellow-400': notification.severity === 'warning',
                                                        'text-blue-400': !notification.severity
                                                    }" />
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-start justify-between">
                                                    <h4 class="font-semibold text-white mb-1 flex items-center gap-2">
                                                        {{ notification.title }}
                                                        <span v-if="notification.severity === 'critical'"
                                                            class="px-2 py-0.5 text-xs font-medium rounded-full bg-red-500/20 text-red-400">
                                                            Critical
                                                        </span>
                                                    </h4>
                                                    <span class="text-xs text-gray-400">{{ notification.time }}</span>
                                                </div>
                                                <p class="text-sm text-gray-300">{{ notification.message }}</p>
                                                
                                                <div v-if="notification.type === 'low-stock'" 
                                                    class="mt-3 bg-gray-900/50 rounded-xl overflow-hidden border border-gray-700/50">
                                                    <div class="px-4 py-2 bg-gray-800/50 border-b border-gray-700/50 flex items-center justify-between">
                                                        <div class="flex items-center gap-2">
                                                            <div class="w-2 h-2 rounded-full animate-pulse"
                                                                :class="{
                                                                    'bg-red-500': notification.severity === 'critical',
                                                                    'bg-yellow-500': notification.severity === 'warning'
                                                                }">
                                                            </div>
                                                            <span class="text-xs font-medium" 
                                                                :class="{
                                                                    'text-red-400': notification.severity === 'critical',
                                                                    'text-yellow-400': notification.severity === 'warning'
                                                                }">
                                                                {{ notification.severity === 'critical' ? 'Critical Low Stock' : 'Low Stock Warning' }}
                                                            </span>
                                                        </div>
                                                        <span class="text-xs text-gray-500">ID: #{{ notification.details.productId }}</span>
                                                    </div>

                                                    <div class="p-4">
                                                        <div class="flex items-start justify-between mb-3">
                                                            <div>
                                                                <h4 class="text-sm font-semibold text-white">{{ notification.details.productName }}</h4>
                                                                <p class="text-xs text-gray-400 mt-0.5">Location: {{ notification.details.location }}</p>
                                                            </div>
                                                            <span class="px-2 py-1 text-xs font-medium rounded-full"
                                                                :class="{
                                                                    'bg-red-500/10 text-red-400': notification.details.status === 'Out Of Stock',
                                                                    'bg-yellow-500/10 text-yellow-400': notification.details.status === 'Low Stock',
                                                                    'bg-green-500/10 text-green-400': notification.details.status === 'In Stock'
                                                                }">
                                                                {{ notification.details.status }}
                                                            </span>
                                                        </div>

                                                        <div class="flex items-center gap-4 p-3 bg-gray-800/50 rounded-lg mb-3">
                                                            <div class="flex-1">
                                                                <span class="text-xs text-gray-400 block mb-1">Current Stock</span>
                                                                <span class="text-lg font-semibold" 
                                                                    :class="{
                                                                        'text-red-400': notification.severity === 'critical',
                                                                        'text-yellow-400': notification.severity === 'warning'
                                                                    }">
                                                                    {{ notification.details.quantity }}
                                                                </span>
                                                                <span class="text-xs text-gray-500 ml-1">units</span>
                                                            </div>
                                                            <div class="w-px h-8 bg-gray-700"></div>
                                                            <div class="flex-1">
                                                                <span class="text-xs text-gray-400 block mb-1">Supplier</span>
                                                                <span class="text-sm text-white">{{ notification.details.supplierName }}</span>
                                                            </div>
                                                        </div>

                                                        <div class="flex items-center gap-2 mb-4">
                                                            <a :href="'mailto:' + notification.details.supplierEmail" 
                                                                class="flex items-center gap-2 px-3 py-1.5 bg-blue-500/10 text-blue-400 rounded-lg hover:bg-blue-500/20 transition-colors text-xs">
                                                                <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                                                                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                                                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                                                                </svg>
                                                                Contact Supplier
                                                            </a>
                                                            <span class="text-xs text-gray-500">{{ notification.details.supplierEmail }}</span>
                                                        </div>

                                                        <div class="flex items-center justify-between gap-2 mt-4">
                                                            <div class="flex items-center gap-2 flex-1">
                                                                <a :href="'mailto:' + notification.details.supplierEmail" 
                                                                    class="px-3 py-2 bg-blue-500/10 hover:bg-blue-500/20 text-blue-400 rounded-lg text-xs font-medium transition-colors inline-flex items-center gap-2">
                                                                    <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                                                                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                                                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                                                                    </svg>
                                                                    Contact Supplier
                                                                </a>
                                                            </div>
                                                            <div class="flex items-center gap-2">
                                                                <button v-if="!notification.read"
                                                                    @click.stop="markAsRead(notification.id)"
                                                                    class="px-3 py-2 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg text-xs font-medium transition-colors">
                                                                    Mark as Read
                                                                </button>
                                                                <button @click.stop="deleteNotification(notification.id)"
                                                                    class="p-2 hover:bg-gray-700/50 text-gray-400 hover:text-red-400 rounded-lg transition-colors">
                                                                    <XMarkIcon class="w-4 h-4" />
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="flex items-center gap-3 mt-3">
                                                    <button @click.stop="markAsRead(notification.id)" 
                                                        v-if="!notification.read"
                                                        class="text-xs text-blue-400 hover:text-blue-300 transition-colors">
                                                        Mark as read
                                                    </button>
                                                    <button class="text-xs text-gray-400 hover:text-gray-300 transition-colors">
                                                        View Details
                                                    </button>
                                                </div>
                                            </div>
                                            <button @click.stop="deleteNotification(notification.id)"
                                                class="opacity-0 group-hover:opacity-100 p-1 hover:bg-gray-600 rounded transition-all">
                                                <XMarkIcon class="w-4 h-4 text-gray-400 hover:text-white" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center relative profile-container">
                        <div class="mr-3 hidden sm:block">
                            <p class="text-sm font-medium text-white">{{ userProfile.name }}</p>
                            <p class="text-xs text-gray-400 flex items-center gap-1">
                                <span class="w-2 h-2 rounded-full bg-green-500"></span>
                                {{ userProfile.role }}
                            </p>
                        </div>
                        <button @click="toggleProfile" 
                            @mouseenter="isProfileHovered = true"
                            @mouseleave="isProfileHovered = false"
                            class="relative flex items-center justify-center h-11 w-11 rounded-xl bg-gray-800/80 transition-all duration-300 hover:bg-gray-700 group"
                            :class="{ 'ring-2 ring-blue-500/50': showProfilePopup || isProfileHovered }">
                            <div class="relative">
                                <img src="https://api.dicebear.com/7.x/adventurer/svg?seed=Felix" alt="avatar"
                                    class="w-9 h-9 rounded-lg transition-transform duration-300 group-hover:scale-105"
                                    :class="{ 'scale-95': showProfilePopup }">
                                <span class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 rounded-full ring-2 ring-gray-900"></span>
                            </div>
                            <ChevronDownIcon class="w-4 h-4 text-gray-400 absolute -right-5 hidden sm:block" 
                                :class="{ 'rotate-180 text-blue-400': showProfilePopup }" />
                        </button>

                        <div v-if="showProfilePopup"
                            class="absolute right-0 top-14 w-[420px] bg-gradient-to-b from-gray-900 via-gray-800 to-gray-900 rounded-xl shadow-2xl z-50 border border-blue-900/30 overflow-hidden">
                            <div class="relative p-6">
                                <div class="absolute inset-0 bg-gradient-to-br from-blue-500/10 via-purple-500/10 to-cyan-500/10 opacity-50"></div>
                                
                                <div class="relative flex items-start justify-between">
                                    <div class="flex items-start gap-4">
                                        <div class="relative group">
                                            <div class="absolute inset-0 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-xl opacity-0 group-hover:opacity-20 transition-all duration-300"></div>
                                            <div class="absolute -inset-0.5 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-xl opacity-20 blur group-hover:opacity-30 transition-all duration-300"></div>
                                            <img src="https://api.dicebear.com/7.x/adventurer/svg?seed=Felix" alt="avatar"
                                                class="relative w-20 h-20 rounded-xl ring-2 ring-white/10 shadow-xl transition-transform duration-300 group-hover:scale-105">
                                            <span class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 rounded-full ring-2 ring-gray-900"></span>
                                        </div>

                                        <div class="flex-1 min-w-0">
                                            <div>
                                                <h3 class="text-xl font-bold text-white">{{ userProfile.name }}</h3>
                                                <p class="text-sm text-gray-300/80">{{ userProfile.email }}</p>
                                            </div>

                                            <div class="flex gap-2 mt-3">
                                                <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-gradient-to-r from-blue-500/10 to-cyan-500/10 text-blue-300 border border-blue-500/20">
                                                    {{ userProfile.role }}
                                                </span>
                                                <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-gradient-to-r from-green-500/10 to-emerald-500/10 text-green-300 border border-green-500/20 flex items-center gap-1.5">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-green-400"></span>
                                                    {{ userProfile.status }}
                                                </span>
                                            </div>
                                        </div>
                                    
                                        <button @click="toggleProfile" 
                                            class="absolute top-0 right-0 p-1.5 hover:bg-white/5 rounded-lg transition-colors">
                                            <XMarkIcon class="w-5 h-5 text-gray-400 hover:text-white" />
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="p-6 space-y-6 bg-gradient-to-b from-gray-800/50 to-gray-900/50 border-t border-white/5">
                                <div class="space-y-4">
                                    <div class="relative p-4 rounded-lg bg-gray-800/50 overflow-hidden group transition-all duration-300 hover:bg-gray-800/70">
                                        <div class="absolute inset-0 bg-gradient-to-r from-blue-500/10 via-purple-500/10 to-cyan-500/10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                        <div class="relative">
                                            <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Basic Information</h4>
                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="space-y-1">
                                                    <p class="text-xs text-gray-400">Full Name</p>
                                                    <p class="text-sm text-white font-medium">{{ userProfile.name }}</p>
                                                </div>
                                                <div class="space-y-1">
                                                    <p class="text-xs text-gray-400">Email</p>
                                                    <p class="text-sm text-white">{{ userProfile.email }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="relative p-4 rounded-lg bg-gray-800/50 overflow-hidden group transition-all duration-300 hover:bg-gray-800/70">
                                        <div class="absolute inset-0 bg-gradient-to-r from-cyan-500/10 via-purple-500/10 to-blue-500/10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                        <div class="relative">
                                            <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Role & Status</h4>
                                            <div class="grid grid-cols-2 gap-4">
                                                <div class="space-y-2">
                                                    <p class="text-xs text-gray-400">Role</p>
                                                    <span class="inline-flex px-3 py-1 text-xs font-medium rounded-full bg-blue-500/10 text-blue-300 border border-blue-500/20">
                                                        {{ userProfile.role }}
                                                    </span>
                                                </div>
                                                <div class="space-y-2">
                                                    <p class="text-xs text-gray-400">Status</p>
                                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium rounded-full bg-green-500/10 text-green-300 border border-green-500/20">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-green-400"></span>
                                                        {{ userProfile.status }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>

<style scoped>
.notifications-container .max-h\[400px\] {
    scrollbar-width: thin;
    scrollbar-color: #4B5563 #1F2937;
}

.notifications-container .max-h\[400px\]::-webkit-scrollbar {
    width: 4px;
}

.notifications-container .max-h\[400px\]::-webkit-scrollbar-track {
    background: #1F2937;
}

.notifications-container [class*="max-h-[400px]"]::-webkit-scrollbar-thumb {
    background-color: #4B5563;
    border-radius: 2px;
}

.notification-dropdown {
    animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.notification-badge {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: .5;
    }
}

.profile-dropdown {
    animation: profileDropdown 0.3s ease-out;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1),
                0 2px 4px -1px rgba(0, 0, 0, 0.06),
                0 20px 25px -5px rgba(0, 0, 0, 0.1),
                0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

@keyframes profileDropdown {
    from {
        opacity: 0;
        transform: translateY(-8px) scale(0.98);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

input[type="text"], input[type="email"], input[type="tel"], select {
    background-color: rgba(55, 65, 81, 0.5);
    border-color: rgb(75, 85, 99);
    border-radius: 0.5rem;
    color: white;
    font-size: 0.875rem;
}

input:focus, select:focus {
    border-color: rgb(59, 130, 246);
    outline: none;
    box-shadow: 0 0 0 1px rgb(59, 130, 246);
}

input, select, button {
    transition-property: all;
    transition-duration: 200ms;
}

.profile-container button:active {
    transform: scale(0.95);
}

.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>