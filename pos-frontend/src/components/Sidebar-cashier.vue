<script setup>
import { 
  ClipboardDocumentListIcon, ArrowRightOnRectangleIcon, 
  XMarkIcon, ViewColumnsIcon
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
    <div class="w-80 h-full bg-slate-900 flex flex-col relative overflow-hidden">
      <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="floating-circle top-20 -left-8 w-16 h-16 bg-blue-500/10"></div>
        <div class="floating-circle top-60 -right-8 w-20 h-20 bg-indigo-500/10"></div>
        <div class="floating-circle bottom-40 left-20 w-12 h-12 bg-cyan-500/10"></div>
      </div>

      <div class="px-6 py-6 bg-slate-800/50 backdrop-blur-sm border-b border-slate-700/50 relative">
        <div class="absolute top-4 right-4 flex items-center space-x-2">
          <span class="animate-pulse w-2 h-2 bg-green-400 rounded-full"></span>
          <span class="text-xs text-green-400">Online</span>
        </div>
        <div class="flex items-center space-x-3">
          <div class="relative">
            <div class="absolute -inset-0.5 bg-gradient-to-r from-indigo-500 to-blue-500 rounded-lg blur opacity-30"></div>
            <div class="relative bg-slate-800 p-2 rounded-lg ring-1 ring-slate-700/50">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
              </svg>
            </div>
          </div>
          <div class="flex flex-col">
            <span class="text-lg font-bold text-white tracking-tight">
              Hardware
              <span class="bg-clip-text text-transparent bg-gradient-to-r from-indigo-400 to-blue-400 font-extrabold">POS</span>
            </span>
            <span class="text-xs font-medium text-slate-400">Cashier Panel</span>
          </div>
        </div>
      </div>
      
      <div class="flex-grow overflow-y-auto custom-scrollbar px-4 py-6 relative">
        <div class="absolute top-0 left-0 w-full h-12 bg-gradient-to-b from-slate-900 to-transparent z-10 pointer-events-none"></div>
        <div v-for="(section, index) in menuSections" 
             :key="section.title" 
             class="mb-8"
             @mouseenter="setHoveredSection(index)"
             @mouseleave="clearHoveredSection()">
          <div class="flex items-center space-x-2 px-2 mb-4">
            <component :is="section.icon" class="w-4 h-4 text-slate-400" />
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">{{ section.title }}</span>
            <div class="flex-grow h-px bg-gradient-to-r from-slate-700/50 to-transparent"></div>
          </div>
          
          <div v-for="item in section.items" 
               :key="item.name"
               class="relative group mb-3">
            <div v-if="currentRoute === item.route" 
                 class="absolute inset-y-0 -left-4 w-1 bg-gradient-to-b from-indigo-500 to-blue-500 rounded-r"></div>
            
            <div class="flex items-center px-3 py-2.5 rounded-lg cursor-pointer transition-all duration-200"
                 :class="{ 
                   'bg-gradient-to-r from-indigo-500/10 to-blue-500/10 text-white': currentRoute === item.route,
                   'hover:bg-slate-800/80 text-slate-300 hover:text-white': currentRoute !== item.route
                 }"
                 @click="navigateTo(item.route)">
              <component :is="item.icon" 
                        :class="[ 
                          'w-5 h-5 mr-3 transition-all duration-200',
                          currentRoute === item.route ? 'text-blue-400' : 'text-slate-400 group-hover:text-blue-400'
                        ]" />
              <span class="font-medium text-sm">{{ item.name }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-auto border-t border-slate-800 p-4 bg-slate-900/50 backdrop-blur-sm">
        <div class="flex items-center justify-between mb-3 px-2">
          <div class="flex items-center space-x-2">
            <span class="relative flex h-2 w-2">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
            </span>
            <span class="text-xs text-slate-400">System Active</span>
          </div>
          <div class="text-xs text-slate-400">v1.0.0</div>
        </div>

        <button @click="handleLogout" 
                class="w-full flex items-center px-4 py-2.5 rounded-lg transition-all duration-200
                       text-slate-300 hover:text-white bg-slate-800/50 hover:bg-red-500/10 
                       border border-slate-700/50 hover:border-red-500/30 group">
          <ArrowRightOnRectangleIcon class="w-5 h-5 mr-3 text-red-400" />
          <span class="font-medium text-sm">Log out</span>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.custom-scrollbar {
  scrollbar-width: thin;
  scrollbar-color: rgba(51, 65, 85, 0.3) transparent;
}

.custom-scrollbar::-webkit-scrollbar {
  width: 3px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: rgba(51, 65, 85, 0.3);
  border-radius: 20px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background-color: rgba(51, 65, 85, 0.5);
}

@keyframes float {
  0% {
    transform: translate(0, 0) rotate(0deg);
    opacity: 0.3;
  }
  50% {
    transform: translate(10px, 10px) rotate(180deg);
    opacity: 0.6;
  }
  100% {
    transform: translate(0, 0) rotate(360deg);
    opacity: 0.3;
  }
}

.floating-circle {
  position: absolute;
  border-radius: 50%;
  filter: blur(20px);
  animation: float 12s infinite ease-in-out;
}

.floating-circle:nth-child(2) {
  animation-delay: -4s;
  animation-duration: 15s;
}

.floating-circle:nth-child(3) {
  animation-delay: -8s;
  animation-duration: 18s;
}
</style>
