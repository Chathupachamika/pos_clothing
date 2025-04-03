<script setup>
import { onMounted, ref, computed } from 'vue'
import Header from './Header.vue'
import Sidebar from './Sidebar.vue'
import { 
    MagnifyingGlassIcon, 
    UserPlusIcon, 
    PencilIcon, 
    TrashIcon, 
    XMarkIcon, 
    UserIcon, 
    PhoneIcon, 
    EnvelopeIcon,
    CheckIcon,
    EyeIcon,
    BuildingOfficeIcon
} from '@heroicons/vue/24/outline'
import Swal from 'sweetalert2'
import { connection } from '@/api/axios'
import axios from 'axios'

const isSidebarVisible = ref(false)
const showSidebar = () => {
    isSidebarVisible.value = true
}

const closeSidebar = () => {
    isSidebarVisible.value = false
}

const toggleSidebar = () => {
    isSidebarVisible.value = !isSidebarVisible.value
}

const showModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const showViewModal = ref(false)

const newEmployee = ref({
    name: '',
    email: '',
    password: '',
    image: null
})

const editingEmployee = ref({})
const employeeToDelete = ref(null)
const viewingEmployee = ref({})

const employees = ref([])
const isLoading = ref(true)

const fetchEmployees = async () => {
    isLoading.value = true
    try {
        const response = await connection.get('/cashiers')
        employees.value = response.data.data
    } catch (error) {
        console.error('Error fetching employees:', error)
        Swal.fire({
            icon: "error",
            title: "Error!",
            text: "Failed to load employees",
            background: '#1e293b',
            color: '#ffffff'
        })
    } finally {
        isLoading.value = false
    }
}

onMounted(() => {
    fetchEmployees()
})

const isAddingEmployee = ref(false)
const isUpdatingEmployee = ref(false)

const handleAddEmployee = async () => {
    if (!validateForm(newEmployee.value)) return
    isAddingEmployee.value = true

    try {
        const formData = new FormData()
        formData.append('name', newEmployee.value.name)
        formData.append('email', newEmployee.value.email)
        formData.append('password', newEmployee.value.password)
        
        if (newEmployee.value.image) {
            formData.append('image', newEmployee.value.image)
        }

        const response = await connection.post('/cashiers', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })

        employees.value.push(response.data.data)

        showModal.value = false
        
        newEmployee.value = { 
            name: '', 
            email: '', 
            password: '',
            image: null
        }
        imagePreview.value = ''

        Swal.fire({
            position: "center",
            icon: "success",
            title: "Employee Added Successfully!",
            showConfirmButton: false,
            timer: 1500,
            background: '#1e293b',
            color: '#ffffff'
        })
    } catch (error) {
        let errorMessage = 'Failed to add employee'
        if (error.response?.data?.message) {
            errorMessage = error.response.data.message
        }
        console.error('Error adding employee:', error)
        Swal.fire({
            icon: "error",
            title: "Error!",
            text: errorMessage,
            background: '#1e293b',
            color: '#ffffff'
        })
    } finally {
        isAddingEmployee.value = false
    }
}

const handleEditEmployee = async () => {
    if (!validateForm(editingEmployee.value)) return
    isUpdatingEmployee.value = true

    try {
        const updateData = {
            name: editingEmployee.value.name,
            email: editingEmployee.value.email,
            password: editingEmployee.value.password 
        }

        await connection.put(`/cashiers/${editingEmployee.value.id}`, updateData)

        if (editingEmployee.value.image && typeof editingEmployee.value.image !== 'string') {
            const imageFormData = new FormData()
            imageFormData.append('image', editingEmployee.value.image)
            await connection.post(`/cashiers/${editingEmployee.value.id}/image`, imageFormData)
        }

        showEditModal.value = false
        await fetchEmployees()

        Swal.fire({
            position: "center",
            icon: "success",
            title: "Employee Updated Successfully!",
            showConfirmButton: false,
            timer: 1500,
            background: '#1e293b',
            color: '#ffffff'
        })
    } catch (error) {
        console.error('Error updating employee:', error)
        Swal.fire({
            icon: "error",
            title: "Error!",
            text: "Failed to update employee",
            background: '#1e293b',
            color: '#ffffff'
        })
    } finally {
        isUpdatingEmployee.value = false
    }
}

const handleDeleteEmployee = async (employee) => {
    try {
        await connection.delete(`/cashiers/${employee.id}`)
        
        employees.value = employees.value.filter(e => e.id !== employee.id)

        Swal.fire({
            position: "center",
            icon: "success",
            title: "Employee Deleted Successfully!",
            showConfirmButton: false,
            timer: 1500,
            background: '#1e293b',
            color: '#ffffff'
        })
    } catch (error) {
        console.error('Error deleting employee:', error)
        Swal.fire({
            icon: "error",
            title: "Error!",
            text: "Failed to delete employee",
            background: '#1e293b',
            color: '#ffffff'
        })
    }
}

const validateForm = (employee) => {
    if (!employee.name || !employee.email) {
        const missingField = !employee.name ? 'name' : 
                           !employee.email ? 'email' : ''
        
        Swal.fire({
            icon: "error",
            title: "Error!",
            text: `Employee ${missingField} is required`,
            background: '#1e293b',
            color: '#ffffff'
        })
        return false
    }
    return true
}

const handleAddSubmit = () => {
    if (validateForm(newEmployee.value)) {
        handleAddEmployee()
    }
}

const handleEditSubmit = () => {
    if (validateForm(editingEmployee.value)) {
        handleEditEmployee()
    }
}

const openEditModal = (employee) => {
    editingEmployee.value = { 
        ...employee,
        password: employee.password || ''
    }
    showEditModal.value = true
    showPassword.value = false  
}

const openViewModal = (employee) => {
    viewingEmployee.value = employee
    showViewModal.value = true
}

const closeViewModal = () => {
    showViewModal.value = false
    viewingEmployee.value = {}
}

const openDeleteModal = (employee) => {
    Swal.fire({
        title: "Are you sure?",
        text: `Do you want to delete employee "${employee.name}"?`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, delete it!",
        background: '#1e293b',
        color: '#ffffff'
    }).then((result) => {
        if (result.isConfirmed) {
            handleDeleteEmployee(employee)
        }
    })
}

const searchQuery = ref('')

const filteredEmployees = computed(() => {
    const searchTerms = searchQuery.value.toLowerCase().split(' ')
    return employees.value.filter(employee => {
        const searchableText = `${employee.name} ${employee.email}`.toLowerCase()
        return searchTerms.every(term => searchableText.includes(term))
    })
})

const formErrors = ref({
    name: '',
    email: '',
    password: ''
})

const validateInput = (field, value) => {
    switch (field) {
        case 'name':
            if (!value.trim()) {
                formErrors.value.name = 'Employee name is required'
            } else if (value.trim().length < 2) {
                formErrors.value.name = 'Name must be at least 2 characters'
            } else {
                formErrors.value.name = ''
            }
            break
        case 'password':
            if (!value?.trim()) {
                formErrors.value.password = 'Password is required'
            } else if (value.trim().length < 8) {
                formErrors.value.password = 'Password must be at least 8 characters'
            } else {
                formErrors.value.password = ''
            }
            break
        case 'email':
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
            if (!value.trim()) {
                formErrors.value.email = 'Email is required'
            } else if (!emailRegex.test(value.trim())) {
                formErrors.value.email = 'Invalid email format'
            } else {
                formErrors.value.email = ''
            }
            break
    }
}

const isValidNewEmployee = computed(() => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

    return newEmployee.value.name.trim() !== '' &&
           emailRegex.test(newEmployee.value.email.trim()) &&
           newEmployee.value.password.trim().length >= 8
})

const isValidEditEmployee = computed(() => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

    return editingEmployee.value.name?.trim() !== '' &&
           emailRegex.test(editingEmployee.value.email?.trim())
})

const imagePreview = ref('')
const editImagePreview = ref('')

const handleImageSelect = (event, type) => {
    const file = event.target.files[0]
    if (file) {
        const reader = new FileReader()
        reader.onload = (e) => {
            if (type === 'add') {
                imagePreview.value = e.target.result
                newEmployee.value.image = file
            } else {
                editImagePreview.value = e.target.result
                editingEmployee.value.image = file
            }
        }
        reader.readAsDataURL(file)
    }
}

const showPassword = ref(false)  
</script>

<template>
    <div>
        <div class="fixed left-0 top-0 w-2 h-full z-[55] hover-trigger" @mouseenter="showSidebar"></div>

        <Sidebar :isVisible="isSidebarVisible" @closeSidebar="closeSidebar" />
        <Header @toggleSidebar="toggleSidebar" />
        <div class="pt-20">
            <div class="bg-gray-900 text-white">
                <div class="h-screen">
                    <div class="w-full h-full flex flex-col p-6">
                        <div class="flex justify-between items-center mb-6">
                            <div class="flex items-center space-x-4">
                                <h1 class="text-2xl font-bold text-white">
                                    <span class="bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 bg-clip-text text-transparent">
                                        Employee Management
                                    </span>
                                </h1>
                            </div>
                            <div class="flex items-center space-x-4">
                                <div class="relative">
                                    <input type="text" 
                                        v-model="searchQuery"
                                        placeholder="Search employees..."
                                        class="w-80 px-4 py-2 bg-gray-800 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 border border-gray-700 pr-16 transition-all duration-200 hover:border-blue-400">
                                    <div class="absolute right-0 top-0 h-full flex items-center px-2">
                                        <button 
                                            v-if="searchQuery"
                                            @click="searchQuery = ''" 
                                            class="p-1 hover:bg-gray-700 rounded-full text-gray-400 hover:text-white transition-all duration-300 mr-1">
                                            <XMarkIcon class="w-4 h-4" />
                                        </button>
                                        <MagnifyingGlassIcon class="w-5 h-5 text-gray-400 hover:text-blue-400 transition-all duration-300" />
                                    </div>
                                </div>
                                <button @click="showModal = true"
                                    class="px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-800 rounded-md font-medium inline-flex items-center transition-all duration-300 hover:shadow-lg hover:shadow-blue-500/30">
                                    <UserPlusIcon class="w-5 h-5 mr-2" />
                                    Add Employee
                                </button>
                            </div>
                        </div>
                        <div class="flex-1 bg-gray-800 rounded-lg overflow-hidden shadow-xl border border-gray-700">
                            <div class="h-full overflow-auto">
                                <table class="w-full table-auto">
                                    <thead class="sticky top-0">
                                        <tr class="bg-gray-800 border-b border-gray-700">
                                            <th class="w-1/6 px-6 py-4 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                                Employee ID
                                            </th>
                                            <th class="w-1/6 px-6 py-4 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                                Image
                                            </th>
                                            <th class="w-1/4 px-6 py-4 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                                Employee Name
                                            </th>
                                            <th class="w-1/4 px-6 py-4 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                                Email
                                            </th>
                                            <th class="w-1/6 px-6 py-4 text-right text-xs font-medium text-gray-300 uppercase tracking-wider">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-700">
                                        <tr v-if="isLoading" class="hover:bg-gray-700">
                                            <td colspan="5" class="h-[400px] relative">
                                                <div class="absolute inset-0 flex flex-col items-center justify-center space-y-4">
                                                    <div class="loader-container">
                                                        <div class="loader">
                                                            <svg class="circular" viewBox="25 25 50 50">
                                                                <defs>
                                                                    <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                                                        <stop offset="0%" stop-color="#3b82f6" />
                                                                        <stop offset="50%" stop-color="#8b5cf6" />
                                                                        <stop offset="100%" stop-color="#ec4899" />
                                                                    </linearGradient>
                                                                </defs>
                                                                <circle class="path" cx="50" cy="50" r="20" fill="none" stroke="url(#gradient)" stroke-width="3" stroke-miterlimit="10"/>
                                                            </svg>
                                                        </div>
                                                    </div>
                                                    <div class="text-base font-medium text-transparent bg-clip-text bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 animate-pulse">
                                                        Loading employees...
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-else-if="employees.length === 0" class="hover:bg-gray-700">
                                            <td colspan="5" class="px-6 py-8 text-center text-gray-400">
                                                No employees found
                                            </td>
                                        </tr>
                                        <tr v-else-if="filteredEmployees.length === 0" class="hover:bg-gray-700">
                                            <td colspan="5" class="px-6 py-8 text-center text-gray-400">
                                                <div class="flex flex-col items-center justify-center space-y-2">
                                                    <MagnifyingGlassIcon class="w-6 h-6 text-gray-500" />
                                                    <span>No results found for "{{ searchQuery }}"</span>
                                                </div>
                                            </td>
                                        </tr>
                                        <template v-else>
                                            <tr v-for="employee in filteredEmployees" :key="employee.id" class="hover:bg-gray-750 transition-colors duration-200 group">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-200">{{ employee.id }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-200">
                                                    <div v-if="employee.image_url" class="w-10 h-10 rounded-full overflow-hidden border-2 border-gray-700 hover:border-blue-500 transition-colors duration-300">
                                                        <img 
                                                            :src="employee.image_url" 
                                                            :alt="employee.name"
                                                            class="w-full h-full object-cover"
                                                            @error="(e) => {
                                                                e.target.src = ''; // Clear the broken image
                                                                console.error('Failed to load image:', employee.image_url);
                                                            }"
                                                        >
                                                    </div>
                                                    <div v-else class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold">
                                                        {{ employee.name.charAt(0).toUpperCase() }}
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-200">
                                                    <div class="flex items-center">
                                                        <UserIcon class="w-4 h-4 text-gray-400 mr-2" />
                                                        {{ employee.name }}
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                                    <div class="flex items-center">
                                                        <EnvelopeIcon class="w-4 h-4 text-gray-400 mr-2" />
                                                        {{ employee.email }}
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                                    <div class="flex justify-end space-x-1 opacity-80 group-hover:opacity-100">
                                                        <button @click="openViewModal(employee)"
                                                            class="p-1.5 bg-gray-700 hover:bg-blue-900 text-cyan-400 rounded-md transition-all duration-300">
                                                            <EyeIcon class="w-4 h-4" />
                                                        </button>
                                                        <button @click="openEditModal(employee)" 
                                                            class="p-1.5 bg-gray-700 hover:bg-purple-900 text-purple-400 rounded-md transition-all duration-300">
                                                            <PencilIcon class="w-4 h-4" />
                                                        </button>
                                                        <button @click="openDeleteModal(employee)" 
                                                            class="p-1.5 bg-gray-700 hover:bg-rose-900 text-rose-500 rounded-md transition-all duration-300">
                                                            <TrashIcon class="w-4 h-4" />
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div v-if="showModal"
                    class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center z-50">
                    <div
                        class="bg-gray-800 rounded-lg w-[600px] p-6 shadow-xl transform transition-all duration-300 scale-100 border border-gray-700/50">
                        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
                            <div class="flex items-center space-x-2">
                                <div class="p-2 bg-gradient-to-r from-blue-500 to-purple-600 rounded-md">
                                    <UserPlusIcon class="w-5 h-5 text-white" />
                                </div>
                                <h2 class="text-xl font-semibold">Add New Employee</h2>
                            </div>
                            <button @click="showModal = false"
                                class="text-gray-400 hover:text-gray-200 hover:bg-gray-700 p-2 rounded-full transition-colors">
                                <XMarkIcon class="w-5 h-5" />
                            </button>
                        </div>
                        <form @submit.prevent="handleAddSubmit" class="space-y-6">
                            <div class="grid grid-cols-2 gap-6">
                                <div class="col-span-2 bg-gray-750 p-4 rounded-lg space-y-4">
                                    <h3 class="text-sm font-medium text-blue-400 uppercase tracking-wider mb-3">Employee
                                        Details</h3>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Employee Name</label>
                                        <input v-model="newEmployee.name" 
                                            @input="validateInput('name', newEmployee.name)"
                                            type="text"
                                            class="w-full px-4 py-2.5 bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 border"
                                            :class="[
                                                formErrors.name ? 'border-red-500' : 'border-gray-600',
                                                !formErrors.name && newEmployee.name ? 'border-blue-500' : ''
                                            ]"
                                            placeholder="Enter employee name" 
                                            required>
                                        <p v-if="formErrors.name" class="mt-1 text-sm text-red-500">{{ formErrors.name }}</p>
                                    </div>
                                </div>

                                <div class="bg-gray-750 p-4 rounded-lg space-y-4">
                                    <h3 class="text-sm font-medium text-blue-400 uppercase tracking-wider mb-3">Email
                                    </h3>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Email Address</label>
                                        <div class="relative">
                                            <span
                                                class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                                <EnvelopeIcon class="w-5 h-5" />
                                            </span>
                                            <input v-model="newEmployee.email" 
                                                @input="validateInput('email', newEmployee.email)"
                                                type="email"
                                                class="w-full pl-10 pr-4 py-2.5 bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 border"
                                                :class="[
                                                    formErrors.email ? 'border-red-500' : 'border-gray-600',
                                                    !formErrors.email && newEmployee.email ? 'border-blue-500' : ''
                                                ]"
                                                placeholder="example@company.com" 
                                                required>
                                        </div>
                                        <p v-if="formErrors.email" class="mt-1 text-sm text-red-500">{{ formErrors.email }}</p>
                                    </div>
                                </div>
                                
                                <div class="bg-gray-750 p-4 rounded-lg space-y-4">
                                    <h3 class="text-sm font-medium text-blue-400 uppercase tracking-wider mb-3">Password
                                    </h3>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Password</label>
                                        <div class="relative">
                                            <input v-model="newEmployee.password" 
                                                @input="validateInput('password', newEmployee.password)"
                                                type="password"
                                                class="w-full px-4 py-2.5 bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 border"
                                                :class="[
                                                    formErrors.password ? 'border-red-500' : 'border-gray-600',
                                                    !formErrors.password && newEmployee.password ? 'border-blue-500' : ''
                                                ]"
                                                placeholder="Enter password" 
                                                required>
                                        </div>
                                        <p v-if="formErrors.password" class="mt-1 text-sm text-red-500">{{ formErrors.password }}</p>
                                    </div>
                                </div>

                                <div class="col-span-2 bg-gray-750 p-4 rounded-lg space-y-4">
                                    <h3 class="text-sm font-medium text-blue-400 uppercase tracking-wider mb-3">Profile Image
                                    </h3>
                                    <div class="flex items-center space-x-6">
                                        <div class="shrink-0">
                                            <div v-if="imagePreview" class="relative w-24 h-24">
                                                <img :src="imagePreview" class="w-24 h-24 rounded-lg object-cover border-2 border-blue-500/50">
                                                <button @click="imagePreview = ''; newEmployee.image = null" 
                                                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 transition-colors">
                                                    <XMarkIcon class="w-4 h-4" />
                                                </button>
                                            </div>
                                            <div v-else class="w-24 h-24 rounded-lg bg-gray-700 border-2 border-dashed border-gray-500 flex items-center justify-center">
                                                <UserIcon class="w-8 h-8 text-gray-400" />
                                            </div>
                                        </div>
                                        <div class="flex-grow">
                                            <label class="flex flex-col items-center justify-center w-full h-32 bg-gray-700 rounded-lg border-2 border-dashed border-gray-500 cursor-pointer hover:bg-gray-600 hover:border-blue-500 transition-all duration-300">
                                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                                    <svg class="w-8 h-8 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                                    </svg>
                                                    <p class="mb-2 text-sm text-gray-400"><span class="font-semibold">Click to upload</span> or drag and drop</p>
                                                    <p class="text-xs text-gray-500">PNG, JPG or GIF (MAX. 800x400px)</p>
                                                </div>
                                                <input type="file" class="hidden" accept="image/*" @change="(e) => handleImageSelect(e, 'add')">
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-end space-x-3 mt-6 pt-4 border-t border-gray-700">
                                <button type="button" @click="showModal = false"
                                    class="px-4 py-2.5 text-gray-300 hover:text-white bg-gray-700 rounded-lg hover:bg-gray-600 transition-colors duration-200 flex items-center">
                                    <XMarkIcon class="w-5 h-5 mr-2" />
                                    Cancel
                                </button>
                                <button type="submit"
                                    :disabled="!isValidNewEmployee || isAddingEmployee"
                                    :class="[
                                        'px-4 py-2.5 rounded-lg transition-all duration-300 flex items-center space-x-2',
                                        isValidNewEmployee && !isAddingEmployee
                                            ? 'bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-800 hover:shadow-lg hover:shadow-blue-500/30 text-white' 
                                            : 'bg-gray-600 cursor-not-allowed text-gray-400'
                                    ]">
                                    <template v-if="isAddingEmployee">
                                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span>Adding Employee...</span>
                                    </template>
                                    <template v-else>
                                        <CheckIcon class="w-5 h-5" />
                                        <span>Add Employee</span>
                                    </template>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div v-if="showEditModal"
                    class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center z-50">
                    <div
                        class="bg-gray-800 rounded-lg w-[600px] p-6 shadow-xl transform transition-all duration-300 scale-100 border border-gray-700/50">
                        <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
                            <div class="flex items-center space-x-2">
                                <div class="p-2 bg-gradient-to-r from-purple-500 to-pink-600 rounded-md">
                                    <PencilIcon class="w-5 h-5 text-white" />
                                </div>
                                <h2 class="text-xl font-semibold">Edit Employee Details</h2>
                            </div>
                            <button @click="showEditModal = false"
                                class="text-gray-400 hover:text-gray-200 hover:bg-gray-700 p-2 rounded-full transition-colors">
                                <XMarkIcon class="w-5 h-5" />
                            </button>
                        </div>

                        <form @submit.prevent="handleEditSubmit" class="space-y-6">
                            <div class="grid grid-cols-2 gap-6">
                                <div class="col-span-2 bg-gray-750 p-4 rounded-lg space-y-4">
                                    <h3 class="text-sm font-medium text-purple-400 uppercase tracking-wider mb-3">Employee
                                        Details</h3>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Employee Name</label>
                                        <input v-model="editingEmployee.name" 
                                            @input="validateInput('name', editingEmployee.name)"
                                            type="text"
                                            class="w-full px-4 py-2.5 bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 border"
                                            :class="[
                                                formErrors.name ? 'border-red-500' : 'border-gray-600',
                                                !formErrors.name && editingEmployee.name ? 'border-purple-500' : ''
                                            ]"
                                            placeholder="Enter employee name" 
                                            required>
                                        <p v-if="formErrors.name" class="mt-1 text-sm text-red-500">{{ formErrors.name }}</p>
                                    </div>
                                </div>

                                <div class="bg-gray-750 p-4 rounded-lg space-y-4">
                                    <h3 class="text-sm font-medium text-purple-400 uppercase tracking-wider mb-3">Email
                                    </h3>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Email Address</label>
                                        <div class="relative">
                                            <span
                                                class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                                <EnvelopeIcon class="w-5 h-5" />
                                            </span>
                                            <input v-model="editingEmployee.email" 
                                                @input="validateInput('email', editingEmployee.email)"
                                                type="email"
                                                class="w-full pl-10 pr-4 py-2.5 bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 border"
                                                :class="[
                                                    formErrors.email ? 'border-red-500' : 'border-gray-600',
                                                    !formErrors.email && editingEmployee.email ? 'border-purple-500' : ''
                                                ]"
                                                placeholder="example@company.com" 
                                                required>
                                        </div>
                                        <p v-if="formErrors.email" class="mt-1 text-sm text-red-500">{{ formErrors.email }}</p>
                                    </div>
                                </div>
                                
                                <div class="bg-gray-750 p-4 rounded-lg space-y-4">
                                    <h3 class="text-sm font-medium text-purple-400 uppercase tracking-wider mb-3">Password
                                    </h3>
                                    <div class="relative">
                                        <input 
                                            v-model="editingEmployee.password" 
                                            type="password"
                                            class="w-full px-4 py-2.5 bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 border"
                                            :class="[
                                                formErrors.password ? 'border-red-500' : 'border-gray-600',
                                                !formErrors.password && editingEmployee.password ? 'border-purple-500' : ''
                                            ]"
                                            placeholder="Enter new password (leave empty to keep current)" 
                                        >
                                    </div>
                                    <p v-if="formErrors.password" class="mt-1 text-sm text-red-500">{{ formErrors.password }}</p>
                                </div>

                                <div class="col-span-2 bg-gray-750 p-4 rounded-lg space-y-4">
                                    <h3 class="text-sm font-medium text-purple-400 uppercase tracking-wider mb-3">Profile Image
                                    </h3>
                                    <div class="flex items-center space-x-6">
                                        <div class="shrink-0">
                                            <div v-if="editImagePreview || editingEmployee.image_url" class="relative w-24 h-24">
                                                <img :src="editImagePreview || editingEmployee.image_url" 
                                                    class="w-24 h-24 rounded-lg object-cover border-2 border-purple-500/50">
                                                <button @click="editImagePreview = ''; editingEmployee.image = null" 
                                                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 transition-colors">
                                                    <XMarkIcon class="w-4 h-4" />
                                                </button>
                                            </div>
                                            <div v-else class="w-24 h-24 rounded-lg bg-gray-700 border-2 border-dashed border-gray-500 flex items-center justify-center">
                                                <UserIcon class="w-8 h-8 text-gray-400" />
                                            </div>
                                        </div>
                                        <div class="flex-grow">
                                            <label class="flex flex-col items-center justify-center w-full h-32 bg-gray-700 rounded-lg border-2 border-dashed border-gray-500 cursor-pointer hover:bg-gray-600 hover:border-purple-500 transition-all duration-300">
                                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                                    <svg class="w-8 h-8 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                                    </svg>
                                                    <p class="mb-2 text-sm text-gray-400"><span class="font-semibold">Click to upload</span> or drag and drop</p>
                                                    <p class="text-xs text-gray-500">PNG, JPG or GIF (MAX. 800x400px)</p>
                                                </div>
                                                <input type="file" class="hidden" accept="image/*" @change="(e) => handleImageSelect(e, 'edit')">
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-end space-x-3 mt-6 pt-4 border-t border-gray-700">
                                <button type="button" @click="showEditModal = false"
                                    class="px-4 py-2.5 text-gray-300 hover:text-white bg-gray-700 rounded-lg hover:bg-gray-600 transition-colors duration-200 flex items-center">
                                    <XMarkIcon class="w-5 h-5 mr-2" />
                                    Cancel
                                </button>
                                <button type="submit"
                                    :disabled="!isValidEditEmployee || isUpdatingEmployee"
                                    :class="[
                                        'px-4 py-2.5 rounded-lg transition-all duration-300 flex items-center space-x-2',
                                        isValidEditEmployee && !isUpdatingEmployee
                                            ? 'bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-500 hover:to-pink-500 hover:shadow-lg hover:shadow-purple-500/30 text-white' 
                                            : 'bg-gray-600 cursor-not-allowed text-gray-400'
                                    ]">
                                    <template v-if="isUpdatingEmployee">
                                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span>Updating Employee...</span>
                                    </template>
                                    <template v-else>
                                        <CheckIcon class="w-5 h-5" />
                                        <span>Update Employee</span>
                                    </template>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div v-if="showViewModal" class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center z-50">
                    <div class="bg-gradient-to-b from-gray-800 to-gray-900 rounded-lg w-[500px] p-6 shadow-xl border border-gray-700/50">
                        <div class="flex justify-between items-center mb-6 border-b border-gray-700/50 pb-4">
                            <div class="flex items-center space-x-2">
                                <div class="p-2 bg-gradient-to-r from-blue-500 to-cyan-600 rounded-md">
                                    <EyeIcon class="w-5 h-5 text-white" />
                                </div>
                                <h2 class="text-xl font-semibold text-cyan-400">Employee Details</h2>
                            </div>
                            <button @click="closeViewModal"
                                class="text-gray-400 hover:text-gray-200 hover:bg-gray-700 p-2 rounded-full transition-colors">
                                <XMarkIcon class="w-5 h-5" />
                            </button>
                        </div>

                        <div class="space-y-4" v-if="viewingEmployee">
                            <div class="flex justify-center mb-6">
                                <div v-if="viewingEmployee.image_url" class="w-32 h-32 rounded-full overflow-hidden border-4 border-cyan-500/30">
                                    <img 
                                        :src="viewingEmployee.image_url" 
                                        :alt="viewingEmployee.name"
                                        class="w-full h-full object-cover"
                                        @error="(e) => e.target.src = ''"
                                    >
                                </div>
                                <div v-else class="w-32 h-32 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white text-4xl font-bold border-4 border-cyan-500/30">
                                    {{ viewingEmployee.name?.charAt(0).toUpperCase() }}
                                </div>
                            </div>
                            
                            <div class="bg-gray-800/50 backdrop-blur-sm p-4 rounded-lg border border-gray-700/30 hover:border-blue-500/50 transition-colors duration-300">
                                <h3 class="text-sm font-medium text-blue-400 uppercase mb-2">Employee ID</h3>
                                <p class="text-white flex items-center">
                                    <span class="inline-block w-6 h-6 bg-gray-700 rounded-full mr-2 flex items-center justify-center text-xs text-blue-400">#</span>
                                    {{ viewingEmployee.id }}
                                </p>
                            </div>
                            
                            <div class="bg-gray-800/50 backdrop-blur-sm p-4 rounded-lg border border-gray-700/30 hover:border-blue-500/50 transition-colors duration-300">
                                <h3 class="text-sm font-medium text-blue-400 uppercase mb-2">Employee Name</h3>
                                <p class="text-white flex items-center">
                                    <UserIcon class="w-5 h-5 text-gray-400 mr-2" />
                                    {{ viewingEmployee.name }}
                                </p>
                            </div>
                            
                            <div class="bg-gray-800/50 backdrop-blur-sm p-4 rounded-lg border border-gray-700/30 hover:border-blue-500/50 transition-colors duration-300">
                                <h3 class="text-sm font-medium text-blue-400 uppercase mb-2">Email Address</h3>
                                <p class="text-white flex items-center">
                                    <EnvelopeIcon class="w-5 h-5 text-gray-400 mr-2" />
                                    {{ viewingEmployee.email }}
                                </p>
                            </div>
                        </div>

                        <div class="flex justify-end mt-6 pt-4 border-t border-gray-700">
                            <button @click="closeViewModal"
                                class="px-4 py-2 bg-gradient-to-r from-gray-700 to-gray-800 hover:from-gray-600 hover:to-gray-700 text-white rounded-md transition-all duration-300 hover:shadow-lg hover:shadow-gray-500/30">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.loader-container {
    position: relative;
    width: 100px;
    height: 100px;
}

.loader {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 70px;
    height: 70px;
}

.circular {
    animation: rotate 2s linear infinite;
    height: 100%;
    width: 100%;
    transform-origin: center center;
}

.path {
    stroke-dasharray: 89, 200;
    stroke-dashoffset: 0;
    stroke-linecap: round;
    animation: dash 1.5s ease-in-out infinite;
}

@keyframes rotate {
    100% {
        transform: rotate(360deg);
    }
}

@keyframes dash {
    0% {
        stroke-dasharray: 1, 200;
        stroke-dashoffset: 0;
    }
    50% {
        stroke-dasharray: 89, 200;
        stroke-dashoffset: -35;
    }
    100% {
        stroke-dasharray: 89, 200;
        stroke-dashoffset: -124;
    }
}

.hover-trigger {
  transition: background-color 0.3s;
}

.hover-trigger:hover {
  background-color: rgba(59, 130, 246, 0.5);
}

/* Table styles */
tbody tr {
  transition: all 0.2s ease;
}

tbody tr:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.bg-gray-750 {
  background-color: rgba(31, 41, 55, 0.5);
}

.modal-enter-active, .modal-leave-active {
  transition: opacity 0.3s, transform 0.3s;
}

.modal-enter-from, .modal-leave-to {
  opacity: 0;
  transform: scale(0.95);
}
</style>