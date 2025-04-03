<script setup>
import { 
  HomeIcon, ShoppingCartIcon, UserGroupIcon, UsersIcon, 
  ClipboardDocumentListIcon, ArchiveBoxIcon, ArrowRightOnRectangleIcon, 
  XMarkIcon, ChartBarIcon, ViewColumnsIcon, UserCircleIcon, 
  ShoppingBagIcon, Squares2X2Icon
} from '@heroicons/vue/24/outline'
import { RotateCcw } from 'lucide-vue-next'
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import Swal from 'sweetalert2' 

const router = useRouter()
const emit = defineEmits(['closeSidebar'])
const hoveredSection = ref(null)

const props = defineProps({
  isVisible: {
    type: Boolean,
    default: false
  }
})

const menuSections = [
  {
    title: 'Overview',
    icon: Squares2X2Icon,
    items: [
      { name: 'Home', icon: HomeIcon, route: '/dashboard' },
    ]
  },
  {
    title: 'Inventory',
    icon: ShoppingBagIcon,
    items: [
      { name: 'Products', icon: ShoppingCartIcon, route: '/products' },
      { name: 'Inventory Management', icon: ArchiveBoxIcon, route: '/inventory-management' },
    ]
  },
  {
    title: 'Stakeholders',
    icon: UserCircleIcon,
    items: [
      { name: 'Suppliers', icon: UserGroupIcon, route: '/suppliers' },
      { name: 'Customers', icon: UsersIcon, route: '/customers' },
      { name: 'Employees', icon: UsersIcon, route: '/employees' },
    ]
  },
  {
    title: 'Orders',
    icon: ViewColumnsIcon,
    items: [
      { name: 'Place Order', icon: ClipboardDocumentListIcon, route: '/place-order' },
      { name: 'Order List', icon: ClipboardDocumentListIcon, route: '/order-management' },
      { name: 'Return Items', icon: RotateCcw, route: '/return-items' },
    ]
  }
]

const currentRoute = computed(() => router.currentRoute.value.path)

const navigateTo = (route) => {
  router.push(route)
  emit('closeSidebar')
}

const handleLogout = () => {
  Swal.fire({
    title: "Are you sure?",
    text: "You will be logged out of the system",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "Yes, logout",
    background: '#1e293b',
    color: '#ffffff'
  }).then((result) => {
    if (result.isConfirmed) {
      router.push('/')
      emit('closeSidebar')
      Swal.fire({
        position: "center",
        icon: "success",
        title: "Logged Out Successfully!",
        showConfirmButton: false,
        timer: 1500,
        background: '#1e293b',
        color: '#ffffff'
      })
    }
  })
}

const setHoveredSection = (index) => {
  hoveredSection.value = index
}

const clearHoveredSection = () => {
  hoveredSection.value = null
}
</script>

<template>
  <div class="fixed top-0 left-0 h-full transform transition-all duration-300 ease-in-out z-[60]"
       :class="[isVisible ? 'translate-x-0 shadow-2xl' : '-translate-x-full']"
       @mouseleave="emit('closeSidebar')">
    <div class="w-80 h-full bg-gradient-to-b from-gray-900 to-gray-800 flex flex-col border-r border-blue-900/30">
      <div class="px-6 py-6 flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <div class="relative">
            <div class="absolute -inset-0.5 bg-gradient-to-r from-blue-400 to-cyan-400 rounded-lg blur opacity-30"></div>
            <div class="relative bg-gray-800 p-1.5 rounded-lg">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
              </svg>
            </div>
          </div>
          <div class="flex flex-col">
            <span class="text-xl font-bold text-white">
              Layos
              <span class="bg-clip-text text-transparent bg-gradient-to-r from-blue-400 to-cyan-400 font-extrabold">Clothing</span>
            </span>
            <span class="text-xs text-gray-400">Inventory Management</span>
          </div>
        </div>
        <button @click="emit('closeSidebar')" 
                class="lg:hidden p-2 text-gray-400 hover:text-white transition-colors rounded-lg hover:bg-gray-700/50">
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>
      
      <div class="flex-grow overflow-y-auto custom-scrollbar px-4">
        <div v-for="(section, index) in menuSections" 
             :key="section.title" 
             class="mb-6"
             @mouseenter="setHoveredSection(index)"
             @mouseleave="clearHoveredSection()">
          <div class="flex items-center space-x-2 px-2 mb-3">
            <div class="w-6 h-6 flex items-center justify-center">
              <component :is="section.icon" class="w-4 h-4 text-gray-400" />
            </div>
            <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider">
              {{ section.title }}
            </div>
            <div class="flex-grow h-px bg-gradient-to-r from-gray-700/50 to-transparent"></div>
          </div>
          
          <div v-for="item in section.items" 
               :key="item.name"
               class="relative group"
               :class="{ 'mb-1': true }">
            <div v-if="currentRoute === item.route" 
                 class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-blue-400 to-cyan-400 rounded-r-full"></div>
            
            <div class="flex items-center px-4 py-2.5 rounded-lg cursor-pointer transition-all duration-200"
                 :class="{ 
                   'bg-gradient-to-r from-blue-500/10 to-cyan-500/10 text-white': currentRoute === item.route,
                   'hover:bg-white/5 active:bg-white/10 text-gray-300 hover:text-white': currentRoute !== item.route
                 }"
                 @click="navigateTo(item.route)">
              <div class="w-6 h-6 flex items-center justify-center mr-3">
                <component :is="item.icon" 
                          :class="[ 
                            'w-5 h-5 transition-all duration-200',
                            currentRoute === item.route ? 'text-blue-400' : 'text-gray-400 group-hover:text-blue-400'
                          ]" />
              </div>
              <span class="font-medium text-sm">{{ item.name }}</span>
              
              <div v-if="item.name === 'Low Stocks'" class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full ring-2 ring-gray-900">
                3
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-auto border-t border-blue-900/30 p-4">
        <button @click="navigateTo('/manage-account')" 
                class="w-full flex items-center px-4 py-3 rounded-lg transition-all duration-200
                       bg-gradient-to-r from-blue-500/20 to-cyan-500/20
                       hover:from-blue-500/30 hover:to-cyan-500/30
                       border-l-4 border-blue-400 group">
          <div class="flex items-center space-x-3">
            <div class="p-2 rounded-lg bg-blue-500/20">
              <Wallet class="w-5 h-5 text-blue-400" />
            </div>
            <div class="flex flex-col">
              <span class="font-medium text-sm text-white">Financial Details</span>
              <span class="text-xs text-gray-400">Manage your finances</span>
            </div>
          </div>
          <div class="ml-auto">
            <ChevronRightIcon class="w-5 h-5 text-gray-400 group-hover:text-blue-400" />
          </div>
        </button>

        <button @click="handleLogout" 
                class="w-full flex items-center justify-center p-2.5 rounded-lg transition-all duration-200 mt-2
                      text-gray-300 hover:text-white border border-gray-700/50 hover:border-red-500/30 hover:bg-red-500/10 group">
          <ArrowRightOnRectangleIcon class="w-5 h-5 mr-2 text-red-400 group-hover:text-red-400" />
          <span class="font-medium text-sm">Log out</span>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.custom-scrollbar {
  scrollbar-width: thin;
  scrollbar-color: rgba(75, 85, 99, 0.3) transparent;
}

.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: rgba(75, 85, 99, 0.3);
  border-radius: 20px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background-color: rgba(75, 85, 99, 0.5);
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.8;
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
</style>