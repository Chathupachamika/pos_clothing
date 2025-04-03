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

const newCustomer = ref({
    name: '',
    email: '',
    contact_number: ''
})

const editingCustomer = ref({})
const customerToDelete = ref(null)
const viewingCustomer = ref({})

const customers = ref([])
const isLoading = ref(true)

const fetchCustomers = async () => {
    isLoading.value = true
    try {
        const response = await connection.get('/customers')
        customers.value = response.data.data.map(customer => ({
            id: customer.id,
            name: customer.name,
            email: customer.email,
            contact: customer.contact
        }))
    } catch (error) {
        console.error('Error fetching customers:', error)
        Swal.fire({
            icon: "error",
            title: "Error!",
            text: "Failed to load customers",
            background: '#1e293b',
            color: '#ffffff'
        })
    } finally {
        isLoading.value = false
    }
}

onMounted(() => {
    fetchCustomers()
})

const isAddingCustomer = ref(false)
const isUpdatingCustomer = ref(false)

const handleAddCustomer = async () => {
    if (!validateForm(newCustomer.value)) return
    isAddingCustomer.value = true

    try {
        const response = await connection.post('/customers', {
            name: newCustomer.value.name,
            email: newCustomer.value.email,
            contact_number: newCustomer.value.contact_number
        })

        const newCustomerData = {
            id: response.data.id,
            name: newCustomer.value.name,
            email: newCustomer.value.email,
            contact: [{ contact_number: newCustomer.value.contact_number }]
        }
        customers.value.push(newCustomerData)

        showModal.value = false
        newCustomer.value = { name: '', email: '', contact_number: '' }

        Swal.fire({
            position: "center",
            icon: "success",
            title: "Customer Added Successfully!",
            showConfirmButton: false,
            timer: 1500,
            background: '#1e293b',
            color: '#ffffff'
        })
    } catch (error) {
        let errorMessage = 'Failed to add customer'
        if (error.response?.data?.message) {
            errorMessage = error.response.data.message
        }
        console.error('Error adding customer:', error)
        Swal.fire({
            icon: "error",
            title: "Error!",
            text: errorMessage,
            background: '#1e293b',
            color: '#ffffff'
        })
    } finally {
        isAddingCustomer.value = false
    }
}

const handleEditCustomer = async () => {
    if (!validateForm(editingCustomer.value)) return
    isUpdatingCustomer.value = true

    try {
        await connection.put(`/customers/${editingCustomer.value.id}`, {
            name: editingCustomer.value.name,
            email: editingCustomer.value.email,
            contact_number: editingCustomer.value.contact[0].contact_number
        })

        const index = customers.value.findIndex(c => c.id === editingCustomer.value.id)
        if (index !== -1) {
            customers.value[index] = { ...editingCustomer.value }
        }

        showEditModal.value = false

        Swal.fire({
            position: "center",
            icon: "success",
            title: "Customer Updated Successfully!",
            showConfirmButton: false,
            timer: 1500,
            background: '#1e293b',
            color: '#ffffff'
        })
    } catch (error) {
        console.error('Error updating customer:', error)
        Swal.fire({
            icon: "error",
            title: "Error!",
            text: "Failed to update customer",
            background: '#1e293b',
            color: '#ffffff'
        })
    } finally {
        isUpdatingCustomer.value = false
    }
}

const handleDeleteCustomer = async (customer) => {
    try {
        await connection.delete(`/customers/${customer.id}`)
        
        customers.value = customers.value.filter(c => c.id !== customer.id)

        Swal.fire({
            position: "center",
            icon: "success",
            title: "Customer Deleted Successfully!",
            showConfirmButton: false,
            timer: 1500,
            background: '#1e293b',
            color: '#ffffff'
        })
    } catch (error) {
        console.error('Error deleting customer:', error)
        Swal.fire({
            icon: "error",
            title: "Error!",
            text: "Failed to delete customer",
            background: '#1e293b',
            color: '#ffffff'
        })
    }
}

const validateForm = (customer) => {
    if (!customer.name || !customer.email) {
        const missingField = !customer.name ? 'name' : 
                           !customer.email ? 'email' : ''
        
        Swal.fire({
            icon: "error",
            title: "Error!",
            text: `Customer ${missingField} is required`,
            background: '#1e293b',
            color: '#ffffff'
        })
        return false
    }
    return true
}

const handleAddSubmit = () => {
    if (validateForm(newCustomer.value)) {
        handleAddCustomer()
    }
}

const handleEditSubmit = () => {
    if (validateForm(editingCustomer.value)) {
        handleEditCustomer()
    }
}

const openEditModal = (customer) => {
    editingCustomer.value = { ...customer }
    showEditModal.value = true
}

const openViewModal = (customer) => {
    viewingCustomer.value = customer
    showViewModal.value = true
}

const closeViewModal = () => {
    showViewModal.value = false
    viewingCustomer.value = {}
}

const openDeleteModal = (customer) => {
    Swal.fire({
        title: "Are you sure?",
        text: `Do you want to delete customer "${customer.name}"?`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, delete it!",
        background: '#1e293b',
        color: '#ffffff'
    }).then((result) => {
        if (result.isConfirmed) {
            handleDeleteCustomer(customer)
        }
    })
}

const searchQuery = ref('')

const filteredCustomers = computed(() => {
    const searchTerms = searchQuery.value.toLowerCase().split(' ')
    return customers.value.filter(customer => {
        const searchableText = `${customer.name} ${customer.email} ${customer.contact[0].contact_number}`.toLowerCase()
        return searchTerms.every(term => searchableText.includes(term))
    })
})

const formErrors = ref({
    name: '',
    email: '',
    contact_number: ''
})

const validateInput = (field, value) => {
    switch (field) {
        case 'name':
            if (!value.trim()) {
                formErrors.value.name = 'Customer name is required'
            } else if (value.trim().length < 2) {
                formErrors.value.name = 'Name must be at least 2 characters'
            } else {
                formErrors.value.name = ''
            }
            break
        case 'contact_number':
            const phoneRegex = /^[0-9\s\-\+]{7,15}$/
            if (!value.trim()) {
                formErrors.value.contact_number = 'Contact number is required'
            } else if (!phoneRegex.test(value.trim())) {
                formErrors.value.contact_number = 'Invalid phone number format'
            } else {
                formErrors.value.contact_number = ''
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

const isValidNewCustomer = computed(() => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    const phoneRegex = /^[0-9\s\-\+]{7,15}$/

    return newCustomer.value.name.trim() !== '' &&
           phoneRegex.test(newCustomer.value.contact_number.trim()) &&
           emailRegex.test(newCustomer.value.email.trim())
})

const isValidEditCustomer = computed(() => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    const phoneRegex = /^[0-9\s\-\+]{7,15}$/

    return editingCustomer.value.name?.trim() !== '' &&
           phoneRegex.test(editingCustomer.value.contact?.[0]?.contact_number?.trim()) &&
           emailRegex.test(editingCustomer.value.email?.trim())
})

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
                                        Customer Management
                                    </span>
                                </h1>
                            </div>
                            <div class="flex items-center space-x-4">
                                <div class="relative">
                                    <input type="text" 
                                        v-model="searchQuery"
                                        placeholder="Search customers..."
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
                                    Add Customer
                                </button>
                            </div>
                        </div>
                        <div class="flex-1 bg-gray-800 rounded-lg overflow-hidden shadow-xl border border-gray-700">
                            <div class="h-full overflow-auto">
                                <table class="w-full table-auto">
                                    <thead class="sticky top-0">
                                        <tr class="bg-gray-800 border-b border-gray-700">
                                            <th class="w-1/6 px-6 py-4 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                                Customer ID
                                            </th>
                                            <th class="w-1/4 px-6 py-4 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                                Customer Name
                                            </th>
                                            <th class="w-1/4 px-6 py-4 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                                Email
                                            </th>
                                            <th class="w-1/6 px-6 py-4 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                                Contact Number
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
                                                        Loading customers...
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-else-if="customers.length === 0" class="hover:bg-gray-700">
                                            <td colspan="5" class="px-6 py-8 text-center text-gray-400">
                                                No customers found
                                            </td>
                                        </tr>
                                        <tr v-else-if="filteredCustomers.length === 0" class="hover:bg-gray-700">
                                            <td colspan="5" class="px-6 py-8 text-center text-gray-400">
                                                <div class="flex flex-col items-center justify-center space-y-2">
                                                    <MagnifyingGlassIcon class="w-6 h-6 text-gray-500" />
                                                    <span>No results found for "{{ searchQuery }}"</span>
                                                </div>
                                            </td>
                                        </tr>
                                        <template v-else>
                                            <tr v-for="customer in filteredCustomers" :key="customer.id" class="hover:bg-gray-750 transition-colors duration-200 group">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-200">{{ customer.id }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-200">
                                                    <div class="flex items-center">
                                                        <div class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-md flex items-center justify-center text-white font-bold">
                                                            {{ customer.name.charAt(0).toUpperCase() }}
                                                        </div>
                                                        <div class="ml-3">{{ customer.name }}</div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                                    <div class="flex items-center">
                                                        <EnvelopeIcon class="w-4 h-4 text-gray-400 mr-2" />
                                                        {{ customer.email }}
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                                    <div class="flex items-center">
                                                        <PhoneIcon class="w-4 h-4 text-gray-400 mr-2" />
                                                        {{ customer.contact[0].contact_number }}
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                                    <div class="flex justify-end space-x-1 opacity-80 group-hover:opacity-100">
                                                        <button @click="openViewModal(customer)"
                                                            class="p-1.5 bg-gray-700 hover:bg-blue-900 text-cyan-400 rounded-md transition-all duration-300">
                                                            <EyeIcon class="w-4 h-4" />
                                                        </button>
                                                        <button @click="openEditModal(customer)" 
                                                            class="p-1.5 bg-gray-700 hover:bg-purple-900 text-purple-400 rounded-md transition-all duration-300">
                                                            <PencilIcon class="w-4 h-4" />
                                                        </button>
                                                        <button @click="openDeleteModal(customer)" 
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
                                <h2 class="text-xl font-semibold">Add New Customer</h2>
                            </div>
                            <button @click="showModal = false"
                                class="text-gray-400 hover:text-gray-200 hover:bg-gray-700 p-2 rounded-full transition-colors">
                                <XMarkIcon class="w-5 h-5" />
                            </button>
                        </div>
                        <form @submit.prevent="handleAddSubmit" class="space-y-6">
                            <div class="grid grid-cols-2 gap-6">
                                <div class="col-span-2 bg-gray-750 p-4 rounded-lg space-y-4">
                                    <h3 class="text-sm font-medium text-blue-400 uppercase tracking-wider mb-3">Customer
                                        Details</h3>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Customer Name</label>
                                        <input v-model="newCustomer.name" 
                                            @input="validateInput('name', newCustomer.name)"
                                            type="text"
                                            class="w-full px-4 py-2.5 bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 border"
                                            :class="[
                                                formErrors.name ? 'border-red-500' : 'border-gray-600',
                                                !formErrors.name && newCustomer.name ? 'border-blue-500' : ''
                                            ]"
                                            placeholder="Enter customer name" 
                                            required>
                                        <p v-if="formErrors.name" class="mt-1 text-sm text-red-500">{{ formErrors.name }}</p>
                                    </div>
                                </div>

                                <div class="bg-gray-750 p-4 rounded-lg space-y-4">
                                    <h3 class="text-sm font-medium text-blue-400 uppercase tracking-wider mb-3">Contact
                                    </h3>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Contact
                                            Number</label>
                                        <div class="relative">
                                            <span
                                                class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                                <PhoneIcon class="w-5 h-5" />
                                            </span>
                                            <input v-model="newCustomer.contact_number" 
                                                @input="validateInput('contact_number', newCustomer.contact_number)"
                                                type="text"
                                                class="w-full pl-10 pr-4 py-2.5 bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 border"
                                                :class="[
                                                    formErrors.contact_number ? 'border-red-500' : 'border-gray-600',
                                                    !formErrors.contact_number && newCustomer.contact_number ? 'border-blue-500' : ''
                                                ]"
                                                placeholder="555-123-4567" 
                                                required>
                                        </div>
                                        <p v-if="formErrors.contact_number" class="mt-1 text-sm text-red-500">{{ formErrors.contact_number }}</p>
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
                                            <input v-model="newCustomer.email" 
                                                @input="validateInput('email', newCustomer.email)"
                                                type="email"
                                                class="w-full pl-10 pr-4 py-2.5 bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 border"
                                                :class="[
                                                    formErrors.email ? 'border-red-500' : 'border-gray-600',
                                                    !formErrors.email && newCustomer.email ? 'border-blue-500' : ''
                                                ]"
                                                placeholder="example@company.com" 
                                                required>
                                        </div>
                                        <p v-if="formErrors.email" class="mt-1 text-sm text-red-500">{{ formErrors.email }}</p>
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
                                    :disabled="!isValidNewCustomer || isAddingCustomer"
                                    :class="[
                                        'px-4 py-2.5 rounded-lg transition-all duration-300 flex items-center space-x-2',
                                        isValidNewCustomer && !isAddingCustomer
                                            ? 'bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-800 hover:shadow-lg hover:shadow-blue-500/30 text-white' 
                                            : 'bg-gray-600 cursor-not-allowed text-gray-400'
                                    ]">
                                    <template v-if="isAddingCustomer">
                                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span>Adding Customer...</span>
                                    </template>
                                    <template v-else>
                                        <CheckIcon class="w-5 h-5 mr-2" />
                                        Add Customer
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
                                <h2 class="text-xl font-semibold">Edit Customer Details</h2>
                            </div>
                            <button @click="showEditModal = false"
                                class="text-gray-400 hover:text-gray-200 hover:bg-gray-700 p-2 rounded-full transition-colors">
                                <XMarkIcon class="w-5 h-5" />
                            </button>
                        </div>

                        <form @submit.prevent="handleEditSubmit" class="space-y-6">
                            <div class="grid grid-cols-2 gap-6">
                                <div class="col-span-2 bg-gray-750 p-4 rounded-lg space-y-4">
                                    <h3 class="text-sm font-medium text-purple-400 uppercase tracking-wider mb-3">Customer
                                        Details</h3>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Customer Name</label>
                                        <input v-model="editingCustomer.name" 
                                            @input="validateInput('name', editingCustomer.name)"
                                            type="text"
                                            class="w-full px-4 py-2.5 bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 border"
                                            :class="[
                                                formErrors.name ? 'border-red-500' : 'border-gray-600',
                                                !formErrors.name && editingCustomer.name ? 'border-purple-500' : ''
                                            ]"
                                            placeholder="Enter customer name" 
                                            required>
                                        <p v-if="formErrors.name" class="mt-1 text-sm text-red-500">{{ formErrors.name }}</p>
                                    </div>
                                </div>

                                <div class="bg-gray-750 p-4 rounded-lg space-y-4">
                                    <h3 class="text-sm font-medium text-purple-400 uppercase tracking-wider mb-3">Contact
                                    </h3>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Contact Number</label>
                                        <div class="relative">
                                            <span
                                                class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                                <PhoneIcon class="w-5 h-5" />
                                            </span>
                                            <input v-model="editingCustomer.contact[0].contact_number"
                                                @input="validateInput('contact_number', editingCustomer.contact[0].contact_number)"
                                                type="text"
                                                class="w-full pl-10 pr-4 py-2.5 bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 border"
                                                :class="[
                                                    formErrors.contact_number ? 'border-red-500' : 'border-gray-600',
                                                    !formErrors.contact_number && editingCustomer.contact[0].contact_number ? 'border-purple-500' : ''
                                                ]"
                                                placeholder="555-123-4567" 
                                                required>
                                        </div>
                                        <p v-if="formErrors.contact_number" class="mt-1 text-sm text-red-500">{{ formErrors.contact_number }}</p>
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
                                            <input v-model="editingCustomer.email" 
                                                @input="validateInput('email', editingCustomer.email)"
                                                type="email"
                                                class="w-full pl-10 pr-4 py-2.5 bg-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 border"
                                                :class="[
                                                    formErrors.email ? 'border-red-500' : 'border-gray-600',
                                                    !formErrors.email && editingCustomer.email ? 'border-purple-500' : ''
                                                ]"
                                                placeholder="example@company.com" 
                                                required>
                                        </div>
                                        <p v-if="formErrors.email" class="mt-1 text-sm text-red-500">{{ formErrors.email }}</p>
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
                                    :disabled="!isValidEditCustomer || isUpdatingCustomer"
                                    :class="[
                                        'px-4 py-2.5 rounded-lg transition-all duration-300 flex items-center space-x-2',
                                        isValidEditCustomer && !isUpdatingCustomer
                                            ? 'bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-500 hover:to-pink-500 hover:shadow-lg hover:shadow-purple-500/30 text-white' 
                                            : 'bg-gray-600 cursor-not-allowed text-gray-400'
                                    ]">
                                    <template v-if="isUpdatingCustomer">
                                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span>Updating Customer...</span>
                                    </template>
                                    <template v-else>
                                        <CheckIcon class="w-5 h-5 mr-2" />
                                        Update Customer
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
                                <h2 class="text-xl font-semibold text-cyan-400">Customer Details</h2>
                            </div>
                            <button @click="closeViewModal"
                                class="text-gray-400 hover:text-gray-200 hover:bg-gray-700 p-2 rounded-full transition-colors">
                                <XMarkIcon class="w-5 h-5" />
                            </button>
                        </div>

                        <div class="space-y-4" v-if="viewingCustomer">
                            <div class="bg-gray-800/50 backdrop-blur-sm p-4 rounded-lg border border-gray-700/30 hover:border-blue-500/50 transition-colors duration-300">
                                <h3 class="text-sm font-medium text-blue-400 uppercase mb-2">Customer ID</h3>
                                <p class="text-white flex items-center">
                                    <span class="inline-block w-6 h-6 bg-gray-700 rounded-full mr-2 flex items-center justify-center text-xs text-blue-400">#</span>
                                    {{ viewingCustomer.id }}
                                </p>
                            </div>
                            
                            <div class="bg-gray-800/50 backdrop-blur-sm p-4 rounded-lg border border-gray-700/30 hover:border-blue-500/50 transition-colors duration-300">
                                <h3 class="text-sm font-medium text-blue-400 uppercase mb-2">Customer Name</h3>
                                <p class="text-white flex items-center">
                                    <UserIcon class="w-5 h-5 text-gray-400 mr-2" />
                                    {{ viewingCustomer.name }}
                                </p>
                            </div>
                            
                            <div class="bg-gray-800/50 backdrop-blur-sm p-4 rounded-lg border border-gray-700/30 hover:border-blue-500/50 transition-colors duration-300">
                                <h3 class="text-sm font-medium text-blue-400 uppercase mb-2">Email Address</h3>
                                <p class="text-white flex items-center">
                                    <EnvelopeIcon class="w-5 h-5 text-gray-400 mr-2" />
                                    {{ viewingCustomer.email }}
                                </p>
                            </div>
                            
                            <div class="bg-gray-800/50 backdrop-blur-sm p-4 rounded-lg border border-gray-700/30 hover:border-blue-500/50 transition-colors duration-300">
                                <h3 class="text-sm font-medium text-blue-400 uppercase mb-2">Contact Number</h3>
                                <p class="text-white flex items-center">
                                    <PhoneIcon class="w-5 h-5 text-gray-400 mr-2" />
                                    {{ viewingCustomer.contact[0].contact_number }}
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