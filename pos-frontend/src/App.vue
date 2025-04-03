<script setup>
import Sidebar from './components/Sidebar.vue'
import SidebarCashier from './components/Sidebar-cashier.vue'
import { ref, onMounted } from 'vue'

const showSidebar = ref(false)
const isAdmin = ref(false)

onMounted(() => {
  isAdmin.value = localStorage.getItem('isAdmin') === 'true' 
})

const toggleSidebar = () => {
  showSidebar.value = !showSidebar.value
}

const closeSidebar = () => {
  showSidebar.value = false
}
</script>

<template>
  <div>
    <Sidebar
      v-if="isAdmin"
      :is-visible="showSidebar"
      @close-sidebar="closeSidebar"
    />
    
    <SidebarCashier
      v-if="!isAdmin"
      :is-visible="showSidebar"
      @close-sidebar="closeSidebar"
    />

    <router-view @toggle-sidebar="toggleSidebar" />
  </div>
</template>

<style scoped>
.hass-status {
  position: fixed;
  top: 10px;
  right: 10px;
  padding: 5px 10px;
  border-radius: 4px;
  z-index: 1000;
}
.connected {
  background-color: #4caf50;
  color: white;
}
.disconnected {
  background-color: #f44336;
  color: white;
}
.error-msg {
  font-size: 0.8em;
  margin-left: 5px;
}
.retry-btn {
  margin-left: 10px;
  padding: 2px 8px;
  background: white;
  border: none;
  border-radius: 3px;
  cursor: pointer;
}
</style>