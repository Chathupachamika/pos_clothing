<script setup>
import { ref, computed, onMounted, onUnmounted, reactive, watch } from 'vue'
import { connection } from '@/api/axios'
import { 
    MagnifyingGlassIcon, 
    PlusIcon,
    PencilIcon, 
    TrashIcon, 
    XMarkIcon, 
    CheckIcon,
    CubeIcon,
    CurrencyDollarIcon,
    BuildingStorefrontIcon,
    EyeIcon,
    TagIcon,
    TruckIcon,
    BuildingOfficeIcon,
    ArrowsUpDownIcon,
    FunnelIcon,
    ChevronDownIcon,
    ChevronUpIcon,
    ArrowPathIcon,
    PhotoIcon,
    DocumentTextIcon,
    ArrowLeftIcon,
    ArrowRightIcon,
    ClockIcon,
    ArchiveBoxIcon
} from '@heroicons/vue/24/outline'
import Header from './Header.vue'
import Sidebar from './Sidebar.vue'
import Swal from 'sweetalert2'
import GRNDocument from './GRNDocument.vue'

const isSidebarVisible = ref(false)
const toggleSidebar = (visible) => {
    isSidebarVisible.value = visible
}

// Modal states
const showModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const showViewModal = ref(false) 
const showGRN = ref(false)
const grnProduct = ref(null)
const grnNumber = ref('')
const showStockUpdateModal = ref(false)
const selectedProduct = ref(null)

// Table state
const searchQuery = ref('')
const categoryFilter = ref('')
const locationFilter = ref('')
const statusFilter = ref('')
const sortField = ref('id')
const sortDirection = ref('asc')
const currentPage = ref(1)
const itemsPerPage = ref(10)

// Data
const products = ref([])
const productVariations = ref([])
const inventory = ref([])
const isLoading = ref(true)
const isUpdating = ref(false)
const isDeleting = ref(false)


const newStockUpdate = ref({
    product_id: null,
    quantity: 0,
    restock_date_time: new Date().toISOString().slice(0, 16),
    added_stock_amount: 0,
    location: '',
    status: 'In Stock'
})

// Fetch all products
const fetchProducts = async () => {
    try {
        isLoading.value = true;
        const response = await connection.get('/products');
        products.value = response.data.data.map(product => ({
            ...product,
            location: product.location // Ensure location is included
        }));
    } catch (error) {
        console.error('Error fetching products:', error)
        showErrorNotification('Failed to load products')
    } finally {
        isLoading.value = false
    }
}

// Fetch product variations
const fetchProductVariations = async () => {
    try {
        isLoading.value = true
        const response = await connection.get('/product/variations')
        productVariations.value = response.data.data || []
    } catch (error) {
        console.error('Error fetching product variations:', error)
        showErrorNotification('Failed to load product variations')
    } finally {
        isLoading.value = false
    }
}

// Fetch inventory data
const fetchInventory = async () => {
    try {
        isLoading.value = true;
        const response = await connection.get('/inventory');
        inventory.value = response.data.data || []; // Ensure data is properly handled
    } catch (error) {
        console.error('Error fetching inventory:', error);
        if (error.response && error.response.data.message) {
            console.error('Backend error message:', error.response.data.message);
        }
        showErrorNotification('Failed to load inventory data');
    } finally {
        isLoading.value = false;
    }
};

// Fetch all data
const fetchAllData = async () => {
    await Promise.all([
        fetchProducts(),
        fetchProductVariations()
    ])
}

// Computed properties
const mergedData = computed(() => {
    // Combine product and variation data
    const result = []
    
    productVariations.value.forEach(variation => {
        const product = products.value.find(p => p.id === variation.product_id)
        if (product) {
            result.push({
                ...variation,
                product_name: product.name,
                category: product.category,
                brand_name: product.brand_name,
                description: product.description,
                image_url: product.image_url,
                location: product.location // Ensure location is included
            })
        }
    })
    
    return result
})

const filteredData = computed(() => {
    let result = mergedData.value
    
    // Apply search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        result = result.filter(item => 
            item.product_name?.toLowerCase().includes(query) ||
            item.color?.toLowerCase().includes(query) ||
            item.size?.toLowerCase().includes(query) ||
            item.barcode?.toLowerCase().includes(query)
        )
    }
    
    // Apply category filter
    if (categoryFilter.value) {
        result = result.filter(item => item.category === categoryFilter.value)
    }
    
    // Apply location filter
    if (locationFilter.value) {
        result = result.filter(item => item.location === locationFilter.value)
    }
    
    // Apply status filter
    if (statusFilter.value) {
        result = result.filter(item => item.status === statusFilter.value)
    }
    
    // Apply sorting
    result = [...result].sort((a, b) => {
        let fieldA, fieldB
        
        switch (sortField.value) {
            case 'product_name':
                fieldA = a.product_name || ''
                fieldB = b.product_name || ''
                break
            case 'color':
                fieldA = a.color || ''
                fieldB = b.color || ''
                break
            case 'size':
                fieldA = a.size || ''
                fieldB = b.size || ''
                break
            case 'quantity':
                fieldA = a.quantity || 0
                fieldB = b.quantity || 0
                break
            case 'price':
                fieldA = a.price || 0
                fieldB = b.price || 0
                break
            case 'updated_at':
                fieldA = new Date(a.updated_at || 0)
                fieldB = new Date(b.updated_at || 0)
                break
            default:
                fieldA = a[sortField.value]
                fieldB = b[sortField.value]
        }
        
        if (typeof fieldA === 'string' && typeof fieldB === 'string') {
            return sortDirection.value === 'asc' 
                ? fieldA.localeCompare(fieldB) 
                : fieldB.localeCompare(fieldA)
        } else {
            return sortDirection.value === 'asc' 
                ? fieldA - fieldB 
                : fieldB - fieldA
        }
    })
    
    return result
})

const paginatedData = computed(() => {
    const startIndex = (currentPage.value - 1) * itemsPerPage.value
    const endIndex = startIndex + itemsPerPage.value
    return filteredData.value.slice(startIndex, endIndex)
})

const totalPages = computed(() => {
    return Math.ceil(filteredData.value.length / itemsPerPage.value)
})

const uniqueCategories = computed(() => {
    const categories = new Set(products.value.map(p => p.category).filter(Boolean))
    return ['All', ...Array.from(categories)]
})

const uniqueLocations = computed(() => {
    const locations = new Set(inventory.value.map(i => i.location).filter(Boolean))
    return ['All', ...Array.from(locations)]
})

const statusOptions = computed(() => {
    return ['All', 'In Stock', 'Low Stock', 'Out Of Stock']
})

// Methods
const toggleSort = (field) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortField.value = field
        sortDirection.value = 'asc'
    }
}

const getSortIcon = (field) => {
    if (sortField.value !== field) return null
    return sortDirection.value === 'asc' ? ChevronUpIcon : ChevronDownIcon
}

const changePage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page
    }
}

const resetFilters = () => {
    searchQuery.value = ''
    categoryFilter.value = ''
    locationFilter.value = ''
    statusFilter.value = ''
    currentPage.value = 1
}

const formatDate = (dateString) => {
    if (!dateString) return 'N/A'
    return new Date(dateString).toLocaleString()
}

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(value)
}

const determineStatus = (quantity) => {
    if (quantity === 0) {
        return 'Out Of Stock'
    } else if (quantity < 20) {
        return 'Low Stock'
    }
    return 'In Stock'
}

// Stock update modal
const openStockUpdateModal = (item) => {
    selectedProduct.value = item
    newStockUpdate.value = {
        product_id: item.product_id,
        quantity: 0,
        restock_date_time: new Date().toISOString().slice(0, 16),
        added_stock_amount: 0,
        location: item.location || '',
        status: item.status || 'In Stock'
    }
    showStockUpdateModal.value = true
}

const closeStockUpdateModal = () => {
    showStockUpdateModal.value = false
    selectedProduct.value = null
}

const handleStockUpdate = async () => {
    if (!selectedProduct.value) return;

    try {
        isUpdating.value = true;

        // Calculate new quantity
        const newQuantity = Math.max(0, parseInt(selectedProduct.value.quantity) + parseInt(newStockUpdate.value.quantity));

        // Prepare payload
        const payload = {
            quantity: newQuantity,
            restock_date_time: newStockUpdate.value.restock_date_time,
            added_stock_amount: newStockUpdate.value.quantity > 0 ? newStockUpdate.value.quantity : 0,
            location: newStockUpdate.value.location,
            status: determineStatus(newQuantity),
            price: selectedProduct.value.price, // Ensure price is included
            selling_price: selectedProduct.value.selling_price, // Ensure selling_price is included
            color: selectedProduct.value.color, // Ensure color is included
            size: selectedProduct.value.size, // Ensure size is included
            barcode: selectedProduct.value.barcode, // Ensure barcode is included
            discount: selectedProduct.value.discount || 0 // Ensure discount is included
        };

        // Update variation
        const response = await connection.put(`/product/variations/${selectedProduct.value.id}`, payload);

        if (response.data.status === 'success') {
            // Update local data
            await fetchProductVariations();

            // Close modal
            closeStockUpdateModal();

            // Show success notification
            Swal.fire({
                position: "center",
                icon: "success",
                title: "Stock Updated Successfully!",
                showConfirmButton: false,
                timer: 1500,
                background: '#1e293b',
                color: '#ffffff'
            });
        }
    } catch (error) {
        console.error('Error updating stock:', error);
        if (error.response && error.response.data.errors) {
            console.error('Validation errors:', error.response.data.errors);
        }
        showErrorNotification('Failed to update stock');
    } finally {
        isUpdating.value = false;
    }
};

// View modal
const openViewModal = (item) => {
    selectedProduct.value = item
    showViewModal.value = true
}

// Delete modal
const openDeleteModal = (item) => {
    selectedProduct.value = item
    
    Swal.fire({
        title: "Are you sure?",
        text: `Do you want to delete this variation?`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, delete it!",
        background: '#1e293b',
        color: '#ffffff'
    }).then((result) => {
        if (result.isConfirmed) {
            handleDelete()
        }
    })
}

const handleDelete = async () => {
    if (!selectedProduct.value) return
    
    try {
        isDeleting.value = true
        
        // Delete variation
        await connection.delete(`/product/variations/${selectedProduct.value.id}`)
        
        // Update local data
        await fetchProductVariations()
        
        // Show success notification
        Swal.fire({
            position: "center",
            icon: "success",
            title: "Variation Deleted Successfully!",
            showConfirmButton: false,
            timer: 1500,
            background: '#1e293b',
            color: '#ffffff'
        })
    } catch (error) {
        console.error('Error deleting variation:', error)
        showErrorNotification('Failed to delete variation')
    } finally {
        isDeleting.value = false
        selectedProduct.value = null
    }
}

// Notifications
const showErrorNotification = (message) => {
    Swal.fire({
        icon: "error",
        title: "Error!",
        text: message,
        background: '#1e293b',
        color: '#ffffff'
    })
}

// Lifecycle hooks
onMounted(() => {
    fetchAllData()
})

onUnmounted(() => {
    // Clean up if needed
})
</script>

<template>
    <div class="bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 text-white min-h-screen">
        <Header />
        <Sidebar :isVisible="isSidebarVisible" @closeSidebar="toggleSidebar(false)" />
        <div class="fixed top-0 left-0 w-8 h-full z-50" @mouseenter="toggleSidebar(true)"></div>
        <div class="ml-0 pt-20"> 
            <div class="w-full h-full flex flex-col p-4 md:p-6">
                <!-- Header Section -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                    <div class="flex items-center space-x-4">
                        <h1 class="text-2xl font-bold bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 bg-clip-text text-transparent">
                            Inventory Management
                        </h1>
                    </div>
                    <div class="flex flex-col md:flex-row items-start md:items-center gap-4 w-full md:w-auto">
                        <div class="relative w-full md:w-80">
                            <input 
                                v-model="searchQuery" 
                                type="search" 
                                placeholder="Search by name, color, size..."
                                class="w-full px-4 py-2 bg-gray-800 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 border border-gray-700 pl-10"
                            >
                            <MagnifyingGlassIcon class="w-5 h-5 absolute left-3 top-2.5 text-gray-400" />
                        </div>
                        
                        <!-- Filter Dropdown -->
                        <div class="relative w-full md:w-auto">
                            <button 
                                class="px-4 py-2 bg-gray-800 rounded-md hover:bg-gray-700 font-medium inline-flex items-center transition-colors w-full md:w-auto justify-center"
                            >
                                <FunnelIcon class="w-5 h-5 mr-2" />
                                Filters
                                <ChevronDownIcon class="w-4 h-4 ml-2" />
                            </button>
                            
                            <!-- Filter Dropdown Content -->
                            <div class="absolute right-0 mt-2 w-64 bg-gray-800 border border-gray-700 rounded-md shadow-lg z-10 p-4 space-y-3 hidden">
                                <!-- Category Filter -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-1">Category</label>
                                    <select 
                                        v-model="categoryFilter"
                                        class="w-full px-3 py-2 bg-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 border border-gray-600"
                                    >
                                        <option v-for="category in uniqueCategories" :key="category" :value="category === 'All' ? '' : category">
                                            {{ category }}
                                        </option>
                                    </select>
                                </div>
                                
                                <!-- Location Filter -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-1">Location</label>
                                    <select 
                                        v-model="locationFilter"
                                        class="w-full px-3 py-2 bg-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 border border-gray-600"
                                    >
                                        <option v-for="location in uniqueLocations" :key="location" :value="location === 'All' ? '' : location">
                                            {{ location }}
                                        </option>
                                    </select>
                                </div>
                                
                                <!-- Status Filter -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-1">Status</label>
                                    <select 
                                        v-model="statusFilter"
                                        class="w-full px-3 py-2 bg-gray-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 border border-gray-600"
                                    >
                                        <option v-for="status in statusOptions" :key="status" :value="status === 'All' ? '' : status">
                                            {{ status }}
                                        </option>
                                    </select>
                                </div>
                                
                                <!-- Reset Button -->
                                <div class="pt-2 border-t border-gray-700">
                                    <button 
                                        @click="resetFilters"
                                        class="w-full px-3 py-2 bg-gray-700 hover:bg-gray-600 rounded-md text-gray-300 transition-colors"
                                    >
                                        Reset Filters
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Refresh Button -->
                        <button 
                            @click="fetchAllData"
                            class="px-4 py-2 bg-gray-800 rounded-md hover:bg-gray-700 font-medium inline-flex items-center transition-colors w-full md:w-auto justify-center"
                        >
                            <ArrowPathIcon class="w-5 h-5 mr-2" />
                            Refresh
                        </button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <!-- Total Products Card -->
                    <div class="bg-gray-800/50 backdrop-blur-sm rounded-lg p-4 border border-gray-700/50 shadow-lg">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-400 text-sm">Total Products</p>
                                <h3 class="text-2xl font-bold text-white mt-1">{{ products.length }}</h3>
                            </div>
                            <div class="bg-blue-500/20 p-2 rounded-lg">
                                <CubeIcon class="w-6 h-6 text-blue-400" />
                            </div>
                        </div>
                    </div>
                    
                    <!-- Total Variations Card -->
                    <div class="bg-gray-800/50 backdrop-blur-sm rounded-lg p-4 border border-gray-700/50 shadow-lg">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-400 text-sm">Total Variations</p>
                                <h3 class="text-2xl font-bold text-white mt-1">{{ productVariations.length }}</h3>
                            </div>
                            <div class="bg-purple-500/20 p-2 rounded-lg">
                                <TagIcon class="w-6 h-6 text-purple-400" />
                            </div>
                        </div>
                    </div>
                    
                    <!-- Low Stock Card -->
                    <div class="bg-gray-800/50 backdrop-blur-sm rounded-lg p-4 border border-gray-700/50 shadow-lg">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-400 text-sm">Low Stock Items</p>
                                <h3 class="text-2xl font-bold text-white mt-1">
                                    {{ productVariations.filter(v => v.quantity < 20 && v.quantity > 0).length }}
                                </h3>
                            </div>
                            <div class="bg-yellow-500/20 p-2 rounded-lg">
                                <ExclamationTriangleIcon class="w-6 h-6 text-yellow-400" />
                            </div>
                        </div>
                    </div>
                    
                    <!-- Out of Stock Card -->
                    <div class="bg-gray-800/50 backdrop-blur-sm rounded-lg p-4 border border-gray-700/50 shadow-lg">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-400 text-sm">Out of Stock</p>
                                <h3 class="text-2xl font-bold text-white mt-1">
                                    {{ productVariations.filter(v => v.quantity === 0).length }}
                                </h3>
                            </div>
                            <div class="bg-red-500/20 p-2 rounded-lg">
                                <ArchiveBoxIcon class="w-6 h-6 text-red-400" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Table -->
                <div class="flex-1 bg-gray-800/50 backdrop-blur-sm rounded-lg overflow-hidden shadow-xl border border-gray-700/50">
                    <div class="h-full overflow-auto">
                        <table class="w-full table-auto">
                            <thead class="sticky top-0">
                                <tr class="bg-gray-700/90 backdrop-blur-sm">
                                    <th
                                        @click="toggleSort('product_name')"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-600/50 transition-colors"
                                    >
                                        <div class="flex items-center space-x-1">
                                            <span>Product Name</span>
                                            <component :is="getSortIcon('product_name')" class="w-4 h-4" />
                                        </div>
                                    </th>
                                    <th
                                        @click="toggleSort('variation')"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-600/50 transition-colors"
                                    >
                                        <div class="flex items-center space-x-1">
                                            <span>Variation</span>
                                            <component :is="getSortIcon('variation')" class="w-4 h-4" />
                                        </div>
                                    </th>
                                    <th
                                        @click="toggleSort('color')"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-600/50 transition-colors"
                                    >
                                        <div class="flex items-center space-x-1">
                                            <span>Color</span>
                                            <component :is="getSortIcon('color')" class="w-4 h-4" />
                                        </div>
                                    </th>
                                    <th
                                        @click="toggleSort('size')"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-600/50 transition-colors"
                                    >
                                        <div class="flex items-center space-x-1">
                                            <span>Size</span>
                                            <component :is="getSortIcon('size')" class="w-4 h-4" />
                                        </div>
                                    </th>
                                    <th
                                        @click="toggleSort('quantity')"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-600/50 transition-colors"
                                    >
                                        <div class="flex items-center space-x-1">
                                            <span>Quantity in Stock</span>
                                            <component :is="getSortIcon('quantity')" class="w-4 h-4" />
                                        </div>
                                    </th>
                                    <th
                                        @click="toggleSort('updated_at')"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-600/50 transition-colors"
                                    >
                                        <div class="flex items-center space-x-1">
                                            <span>Updated Time</span>
                                            <component :is="getSortIcon('updated_at')" class="w-4 h-4" />
                                        </div>
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-300 uppercase tracking-wider">
                                        Stock Update
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-300 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700/50">
                                <tr v-if="isLoading" class="hover:bg-gray-700">
                                    <td colspan="8" class="h-[400px] relative">
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <div class="flex flex-col items-center">
                                                <div class="loader">
                                                    <div class="loader-inner"></div>
                                                </div>
                                                <div class="mt-4 text-base font-medium text-transparent bg-clip-text bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 animate-pulse">
                                                    Loading inventory...
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <template v-else>
                                    <tr v-if="paginatedData.length === 0" class="hover:bg-gray-700">
                                        <td colspan="8" class="px-6 py-8 text-center text-gray-400">
                                            No inventory items available
                                        </td>
                                    </tr>
                                    <template v-else v-for="item in paginatedData" :key="item.id">
                                        <tr class="hover:bg-gray-700/30 transition-colors duration-200"
                                            :class="{
                                                'bg-red-900/20': item.quantity === 0,
                                                'bg-yellow-900/10': item.quantity > 0 && item.quantity < 20
                                            }">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ item.product_name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <span class="px-2 py-1 text-xs rounded-full bg-purple-500/20 text-purple-300 border border-purple-500/30">
                                                    {{ item.id }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <div class="flex items-center">
                                                    <div class="w-4 h-4 rounded-full mr-2" 
                                                         :style="`background-color: ${item.color.toLowerCase()}`"></div>
                                                    {{ item.color }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ item.size }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <span :class="{
                                                    'font-bold': true,
                                                    'text-red-400': item.quantity === 0,
                                                    'text-yellow-400': item.quantity > 0 && item.quantity < 20,
                                                    'text-green-400': item.quantity >= 20
                                                }">
                                                    {{ item.quantity }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                                <div class="flex items-center">
                                                    <ClockIcon class="w-4 h-4 mr-2 text-gray-400" />
                                                    {{ formatDate(item.updated_at) }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <button 
                                                    @click="openStockUpdateModal(item)"
                                                    class="bg-emerald-500/20 p-1.5 rounded text-emerald-400 duration-200 hover:bg-emerald-500/30 transition-colors" 
                                                    title="Update Stock"
                                                >
                                                    <ArrowPathIcon class="w-5 h-5" />
                                                </button>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <div class="flex items-center justify-center space-x-2">
                                                    <button 
                                                        @click="openViewModal(item)"
                                                        class="text-cyan-400 hover:text-cyan-300 p-1.5 hover:bg-gray-700 rounded-full transition-colors"
                                                        title="View Details"
                                                    >
                                                        <EyeIcon class="w-5 h-5" />
                                                    </button>
                                                    <button 
                                                        @click="openDeleteModal(item)"
                                                        class="text-rose-500 hover:text-rose-400 p-1.5 hover:bg-gray-700 rounded-full transition-colors"
                                                        title="Delete Variation"
                                                    >
                                                        <TrashIcon class="w-5 h-5" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Pagination -->
                <div class="mt-4 text-sm text-gray-400 flex justify-between items-center">
                    <div>
                        Showing {{ Math.min(1 + (currentPage - 1) * itemsPerPage, filteredData.length) }}-{{ Math.min(currentPage * itemsPerPage, filteredData.length) }} of {{ filteredData.length }} items
                    </div>
                    <div class="flex space-x-2">
                        <button 
                            @click="changePage(currentPage - 1)" 
                            :disabled="currentPage === 1"
                            class="px-3 py-1 bg-gray-700 rounded-md hover:bg-gray-600 disabled:bg-gray-800 disabled:text-gray-600 transition-colors"
                        >
                            Previous
                        </button>
                        <template v-if="totalPages <= 5">
                            <button 
                                v-for="page in totalPages" 
                                :key="page"
                                @click="changePage(page)"
                                :class="[
                                    'px-3 py-1 rounded-md transition-colors',
                                    currentPage === page 
                                        ? 'bg-blue-600 text-white' 
                                        : 'bg-gray-700 hover:bg-gray-600 text-gray-300'
                                ]"
                            >
                                {{ page }}
                            </button>
                        </template>
                        <template v-else>
                            <!-- First page -->
                            <button 
                                @click="changePage(1)"
                                :class="[
                                    'px-3 py-1 rounded-md transition-colors',
                                    currentPage === 1 
                                        ? 'bg-blue-600 text-white' 
                                        : 'bg-gray-700 hover:bg-gray-600 text-gray-300'
                                ]"
                            >
                                1
                            </button>
                            
                            <!-- Ellipsis if needed -->
                            <span v-if="currentPage > 3" class="px-3 py-1 text-gray-400">...</span>
                            
                            <!-- Pages around current -->
                            <button 
                                v-for="page in totalPages" 
                                :key="page"
                                v-if="page !== 1 && page !== totalPages && Math.abs(page - currentPage) <= 1"
                                @click="changePage(page)"
                                :class="[
                                    'px-3 py-1 rounded-md transition-colors',
                                    currentPage === page 
                                        ? 'bg-blue-600 text-white' 
                                        : 'bg-gray-700 hover:bg-gray-600 text-gray-300'
                                ]"
                            >
                                {{ page }}
                            </button>
                            
                            <!-- Ellipsis if needed -->
                            <span v-if="currentPage < totalPages - 2" class="px-3 py-1 text-gray-400">...</span>
                            
                            <!-- Last page -->
                            <button 
                                @click="changePage(totalPages)"
                                :class="[
                                    'px-3 py-1 rounded-md transition-colors',
                                    currentPage === totalPages 
                                        ? 'bg-blue-600 text-white' 
                                        : 'bg-gray-700 hover:bg-gray-600 text-gray-300'
                                ]"
                            >
                                {{ totalPages }}
                            </button>
                        </template>
                        <button 
                            @click="changePage(currentPage + 1)" 
                            :disabled="currentPage === totalPages"
                            class="px-3 py-1 bg-gray-700 rounded-md hover:bg-gray-600 disabled:bg-gray-800 disabled:text-gray-600 transition-colors"
                        >
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stock Update Modal -->
        <div v-if="showStockUpdateModal" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50 p-4">
            <div 
                class="bg-gradient-to-b from-gray-800 to-gray-900 rounded-lg w-full max-w-md p-6 shadow-xl border border-gray-700/50 max-h-[90vh] overflow-y-auto"
            >
                <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
                    <div class="flex items-center space-x-2">
                        <ArrowPathIcon class="w-6 h-6 text-emerald-400" />
                        <h2 class="text-xl font-semibold text-emerald-400">Update Stock</h2>
                    </div>
                    <button 
                        @click="closeStockUpdateModal"
                        class="text-gray-400 hover:text-gray-200 hover:bg-gray-700 p-2 rounded-full transition-colors"
                    >
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>

                <div v-if="selectedProduct" class="space-y-6">
                    <!-- Current Stock Info -->
                    <div class="bg-gray-750 p-4 rounded-lg">
                        <div class="flex justify-between items-center mb-2">
                            <h3 class="text-sm font-medium text-gray-300 uppercase tracking-wider">Current Stock Info</h3>
                            <span :class="{
                                'px-2 py-1 text-xs rounded-full': true,
                                'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30': selectedProduct.quantity >= 20,
                                'bg-yellow-500/20 text-yellow-400 border border-yellow-500/30': selectedProduct.quantity > 0 && selectedProduct.quantity < 20,
                                'bg-red-500/20 text-red-400 border border-red-500/30': selectedProduct.quantity === 0
                            }">
                                {{ selectedProduct.quantity >= 20 ? 'In Stock' : selectedProduct.quantity > 0 ? 'Low Stock' : 'Out of Stock' }}
                            </span>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4 mt-4">
                            <div>
                                <span class="text-gray-400 text-sm">Product</span>
                                <p class="text-white font-medium mt-1">{{ selectedProduct.product_name }}</p>
                            </div>
                            <div>
                                <span class="text-gray-400 text-sm">Variation</span>
                                <p class="text-white font-medium mt-1">{{ selectedProduct.color }} / {{ selectedProduct.size }}</p>
                            </div>
                            <div>
                                <span class="text-gray-400 text-sm">Current Quantity</span>
                                <p class="text-white font-bold text-xl mt-1">{{ selectedProduct.quantity }}</p>
                            </div>
                            <div>
                                <span class="text-gray-400 text-sm">Location</span>
                                <p class="text-white font-medium mt-1">{{ selectedProduct.location || 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Stock Adjustment Form -->
                    <div class="bg-gray-750 p-4 rounded-lg">
                        <h3 class="text-sm font-medium text-gray-300 uppercase tracking-wider mb-4">Stock Adjustment</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="flex justify-between items-center mb-2">
                                    <span class="text-gray-300 font-medium">Adjustment Amount</span>
                                    <span :class="{
                                        'text-sm font-medium': true,
                                        'text-emerald-400': newStockUpdate.quantity > 0,
                                        'text-red-400': newStockUpdate.quantity < 0,
                                        'text-gray-400': newStockUpdate.quantity == 0
                                    }">
                                        New Total: {{ Math.max(0, parseInt(selectedProduct.quantity) + parseInt(newStockUpdate.quantity)) }}
                                    </span>
                                </label>
                                <div class="flex gap-3 items-center">
                                    <button 
                                        @click="newStockUpdate.quantity = parseInt(newStockUpdate.quantity) - 1" 
                                        class="bg-red-500/20 p-2.5 rounded-lg text-red-400 hover:bg-red-500/30 transition-colors"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                    
                                    <input 
                                        v-model.number="newStockUpdate.quantity"
                                        type="number"
                                        class="flex-1 bg-gray-700 border border-gray-600 rounded-lg text-center text-white text-xl font-bold px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    />
                                    
                                    <button 
                                        @click="newStockUpdate.quantity = parseInt(newStockUpdate.quantity) + 1" 
                                        class="bg-emerald-500/20 p-2.5 rounded-lg text-emerald-400 hover:bg-emerald-500/30 transition-colors"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-gray-300 font-medium mb-2">Date & Time</label>
                                <input 
                                    v-model="newStockUpdate.restock_date_time"
                                    type="datetime-local"
                                    class="w-full bg-gray-700 border border-gray-600 rounded-lg text-white px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                />
                            </div>
                            
                            <div>
                                <label class="block text-gray-300 font-medium mb-2">Location</label>
                                <input 
                                    v-model="newStockUpdate.location"
                                    type="text"
                                    class="w-full bg-gray-700 border border-gray-600 rounded-lg text-white px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end space-x-3 mt-6 pt-4 border-t border-gray-700">
                        <button 
                            @click="closeStockUpdateModal"
                            class="px-4 py-2.5 text-gray-300 hover:text-white bg-gray-700 rounded-lg hover:bg-gray-600 transition-colors"
                        >
                            Cancel
                        </button>
                        <button 
                            @click="handleStockUpdate"
                            :disabled="newStockUpdate.quantity == 0 || isUpdating"
                            :class="[
                                'px-4 py-2.5 rounded-lg transition-colors flex items-center space-x-2',
                                newStockUpdate.quantity > 0 
                                    ? 'bg-emerald-600 hover:bg-emerald-700 text-white' 
                                    : newStockUpdate.quantity < 0
                                        ? 'bg-red-600 hover:bg-red-700 text-white'
                                        : 'bg-gray-600 text-gray-400 cursor-not-allowed'
                            ]"
                        >
                            <template v-if="isUpdating">
                                <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>Updating...</span>
                            </template>
                            <template v-else>
                                <span>{{ newStockUpdate.quantity > 0 ? 'Add Stock' : 'Remove Stock' }}</span>
                            </template>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Modal -->
        <div v-if="showViewModal" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50 p-4">
            <div 
                class="bg-gradient-to-b from-gray-800 to-gray-900 rounded-lg w-full max-w-xl p-6 shadow-xl border border-gray-700/50 max-h-[90vh] overflow-y-auto"
            >
                <div class="flex justify-between items-center mb-6 border-b border-gray-700 pb-4">
                    <div class="flex items-center space-x-2">
                        <EyeIcon class="w-6 h-6 text-cyan-400" />
                        <h2 class="text-xl font-semibold text-cyan-400">Variation Details</h2>
                    </div>
                    <button 
                        @click="showViewModal = false"
                        class="text-gray-400 hover:text-gray-200 hover:bg-gray-700 p-2 rounded-full transition-colors"
                    >
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>

                <div v-if="selectedProduct" class="space-y-6">
                    <!-- Product Info -->
                    <div class="bg-gray-750 p-4 rounded-lg">
                        <h3 class="text-sm font-medium text-gray-300 uppercase tracking-wider mb-4">Product Information</h3>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="text-gray-400 text-sm">Product Name</span>
                                <p class="text-white font-medium mt-1">{{ selectedProduct.product_name }}</p>
                            </div>
                            <div>
                                <span class="text-gray-400 text-sm">Category</span>
                                <p class="text-white font-medium mt-1">{{ selectedProduct.category }}</p>
                            </div>
                            <div>
                                <span class="text-gray-400 text-sm">Brand</span>
                                <p class="text-white font-medium mt-1">{{ selectedProduct.brand_name }}</p>
                            </div>
                            <div>
                                <span class="text-gray-400 text-sm">Product ID</span>
                                <p class="text-white font-medium mt-1">{{ selectedProduct.product_id }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Variation Info -->
                    <div class="bg-gray-750 p-4 rounded-lg">
                        <h3 class="text-sm font-medium text-gray-300 uppercase tracking-wider mb-4">Variation Details</h3>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="text-gray-400 text-sm">Variation ID</span>
                                <p class="text-white font-medium mt-1">{{ selectedProduct.id }}</p>
                            </div>
                            <div>
                                <span class="text-gray-400 text-sm">Color</span>
                                <div class="flex items-center mt-1">
                                    <div class="w-4 h-4 rounded-full mr-2" 
                                         :style="`background-color: ${selectedProduct.color.toLowerCase()}`"></div>
                                    <p class="text-white font-medium">{{ selectedProduct.color }}</p>
                                </div>
                            </div>
                            <div>
                                <span class="text-gray-400 text-sm">Size</span>
                                <p class="text-white font-medium mt-1">{{ selectedProduct.size }}</p>
                            </div>
                            <div>
                                <span class="text-gray-400 text-sm">Barcode</span>
                                <p class="text-white font-medium mt-1">{{ selectedProduct.barcode }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pricing Info -->
                    <div class="bg-gray-750 p-4 rounded-lg">
                        <h3 class="text-sm font-medium text-gray-300 uppercase tracking-wider mb-4">Pricing Information</h3>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="text-gray-400 text-sm">Price</span>
                                <p class="text-white font-medium mt-1">{{ formatCurrency(selectedProduct.price) }}</p>
                            </div>
                            <div>
                                <span class="text-gray-400 text-sm">Selling Price</span>
                                <p class="text-white font-medium mt-1">{{ formatCurrency(selectedProduct.selling_price) }}</p>
                            </div>
                            <div>
                                <span class="text-gray-400 text-sm">Discount</span>
                                <p class="text-white font-medium mt-1">{{ selectedProduct.discount }}%</p>
                            </div>
                            <div>
                                <span class="text-gray-400 text-sm">Profit</span>
                                <p class="text-white font-medium mt-1">{{ formatCurrency(selectedProduct.selling_price - selectedProduct.price) }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Stock Info -->
                    <div class="bg-gray-750 p-4 rounded-lg">
                        <h3 class="text-sm font-medium text-gray-300 uppercase tracking-wider mb-4">Stock Information</h3>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="text-gray-400 text-sm">Quantity</span>
                                <p class="text-white font-bold text-xl mt-1">{{ selectedProduct.quantity }}</p>
                            </div>
                            <div>
                                <span class="text-gray-400 text-sm">Status</span>
                                <p class="mt-1">
                                    <span :class="{
                                        'px-2 py-1 text-xs rounded-full': true,
                                        'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30': selectedProduct.quantity >= 20,
                                        'bg-yellow-500/20 text-yellow-400 border border-yellow-500/30': selectedProduct.quantity > 0 && selectedProduct.quantity < 20,
                                        'bg-red-500/20 text-red-400 border border-red-500/30': selectedProduct.quantity === 0
                                    }">
                                        {{ selectedProduct.quantity >= 20 ? 'In Stock' : selectedProduct.quantity > 0 ? 'Low Stock' : 'Out of Stock' }}
                                    </span>
                                </p>
                            </div>
                            <div>
                                <span class="text-gray-400 text-sm">Location</span>
                                <p class="text-white font-medium mt-1">{{ selectedProduct.location || 'N/A' }}</p>
                            </div>
                            <div>
                                <span class="text-gray-400 text-sm">Total Value</span>
                                <p class="text-white font-medium mt-1">{{ formatCurrency(selectedProduct.price * selectedProduct.quantity) }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Timestamps -->
                    <div class="bg-gray-750 p-4 rounded-lg">
                        <h3 class="text-sm font-medium text-gray-300 uppercase tracking-wider mb-4">Timestamps</h3>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="text-gray-400 text-sm">Created At</span>
                                <p class="text-white font-medium mt-1">{{ formatDate(selectedProduct.created_at) }}</p>
                            </div>
                            <div>
                                <span class="text-gray-400 text-sm">Updated At</span>
                                <p class="text-white font-medium mt-1">{{ formatDate(selectedProduct.updated_at) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end space-x-3 mt-6 pt-4 border-t border-gray-700">
                        <button 
                            @click="showViewModal = false"
                            class="px-4 py-2.5 text-gray-300 hover:text-white bg-gray-700 rounded-lg hover:bg-gray-600 transition-colors"
                        >
                            Close
                        </button>
                        <button 
                            @click="openStockUpdateModal(selectedProduct); showViewModal = false"
                            class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors flex items-center"
                        >
                            <ArrowPathIcon class="w-5 h-5 mr-2" />
                            Update Stock
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- GRN Document Modal -->
        <GRNDocument 
            v-if="showGRN" 
            :productData="grnProduct" 
            :grnNumber="grnNumber" 
            :showModal="showGRN"
            @close="showGRN = false" 
        />
    </div>
</template>

<style scoped>
.overflow-auto {
    height: calc(100vh - 200px);
}

table {
    border-collapse: collapse;
    width: 100%;
}

thead {
    position: sticky;
    top: 0;
    z-index: 1;
}

tbody tr:last-child td {
    border-bottom: none;
}

.bg-gray-750 {
    background-color: rgba(55, 65, 81, 0.5);
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideIn {
    from { transform: translateY(-20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.animate-fadeIn {
    animation: fadeIn 0.3s ease-out;
}

.animate-slideIn {
    animation: slideIn 0.3s ease-out;
}

::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background: rgba(31, 41, 55, 0.5);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb {
    background: rgba(75, 85, 99, 0.5);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: rgba(107, 114, 128, 0.5);
}

.loader {
    width: 50px;
    height: 50px;
    border: 5px solid #2563eb;
    border-bottom-color: transparent;
    border-radius: 50%;
    display: inline-block;
    box-sizing: border-box;
    animation: rotation 1s linear infinite;
}

@keyframes rotation {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}
</style>