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
    ArchiveBoxIcon,
    ExclamationTriangleIcon,
    ChartBarIcon,
    ChartPieIcon
} from '@heroicons/vue/24/outline'
import Header from './Header.vue'
import Sidebar from './Sidebar.vue'
import Swal from 'sweetalert2'
import GRNDocument from './GRNDocument.vue'
// Import chart libraries
import { Bar, Pie } from 'vue-chartjs'
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement } from 'chart.js'

// Register ChartJS components
ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement)

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

// Form data
const newStockUpdate = ref({
    product_id: null,
    quantity: 0,
    restock_date_time: new Date().toISOString().slice(0, 16),
    added_stock_amount: 0,
    location: '',
    status: 'In Stock'
})

// Chart data
const stockStatusData = computed(() => {
    if (!productVariations.value.length) return { labels: [], datasets: [] }
    
    const inStock = productVariations.value.filter(v => v.quantity >= 20).length
    const lowStock = productVariations.value.filter(v => v.quantity < 20 && v.quantity > 0).length
    const outOfStock = productVariations.value.filter(v => v.quantity === 0).length
    
    return {
        labels: ['In Stock', 'Low Stock', 'Out of Stock'],
        datasets: [
            {
                data: [inStock, lowStock, outOfStock],
                backgroundColor: [
                    'rgba(52, 211, 153, 0.8)', // Green
                    'rgba(251, 191, 36, 0.8)', // Yellow
                    'rgba(239, 68, 68, 0.8)'   // Red
                ],
                borderWidth: 1
            }
        ]
    }
})

const categoryDistributionData = computed(() => {
    if (!products.value.length) return { labels: [], datasets: [] }
    
    // Count products by category
    const categoryCounts = {}
    products.value.forEach(product => {
        if (product.category) {
            categoryCounts[product.category] = (categoryCounts[product.category] || 0) + 1
        }
    })
    
    // Convert to chart data format
    const labels = Object.keys(categoryCounts)
    const data = Object.values(categoryCounts)
    
    // Generate dynamic colors
    const generateColors = (count) => {
        const colors = []
        for (let i = 0; i < count; i++) {
            // Dynamic pastel-ish colors
            const hue = (i * 137) % 360
            colors.push(`hsla(${hue}, 70%, 60%, 0.8)`)
        }
        return colors
    }
    
    return {
        labels,
        datasets: [
            {
                data,
                backgroundColor: generateColors(labels.length)
            }
        ]
    }
})

const topProductsData = computed(() => {
    if (!productVariations.value.length) return { labels: [], datasets: [] }
    
    // Group by product_id and sum quantities
    const productQuantities = {}
    const productNames = {}
    
    productVariations.value.forEach(variation => {
        const id = variation.product_id
        if (!productQuantities[id]) {
            productQuantities[id] = 0
            // Find the product name
            const product = products.value.find(p => p.id === id)
            productNames[id] = product ? product.name : `Product ${id}`
        }
        productQuantities[id] += variation.quantity || 0
    })
    
    // Convert to array, sort by quantity, and take top 5
    const sortedProducts = Object.entries(productQuantities)
        .map(([id, quantity]) => ({ id, name: productNames[id], quantity }))
        .sort((a, b) => b.quantity - a.quantity)
        .slice(0, 5)
    
    return {
        labels: sortedProducts.map(p => p.name),
        datasets: [
            {
                label: 'Quantity',
                data: sortedProducts.map(p => p.quantity),
                backgroundColor: 'rgba(99, 102, 241, 0.8)', // Indigo
                borderColor: 'rgba(99, 102, 241, 1)',
                borderWidth: 1
            }
        ]
    }
})

const weeklyStockActivity = ref({
    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
    datasets: [
        {
            label: 'Stock In',
            data: [12, 19, 8, 15, 20, 14, 11],
            backgroundColor: 'rgba(52, 211, 153, 0.8)',
            borderColor: 'rgba(52, 211, 153, 1)',
            borderWidth: 1
        },
        {
            label: 'Stock Out',
            data: [7, 11, 5, 8, 13, 9, 6],
            backgroundColor: 'rgba(239, 68, 68, 0.8)',
            borderColor: 'rgba(239, 68, 68, 1)',
            borderWidth: 1
        }
    ]
})

// Chart options
const pieChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
            labels: {
                color: '#e2e8f0',
                font: {
                    family: 'Inter, system-ui, sans-serif',
                    size: 12
                },
                padding: 20
            }
        },
        tooltip: {
            backgroundColor: 'rgba(17, 24, 39, 0.9)',
            titleColor: '#f9fafb',
            bodyColor: '#f3f4f6',
            borderColor: '#374151',
            borderWidth: 1,
            padding: 12,
            boxPadding: 6,
            usePointStyle: true
        }
    }
}

const barChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false
        },
        tooltip: {
            backgroundColor: 'rgba(17, 24, 39, 0.9)',
            titleColor: '#f9fafb',
            bodyColor: '#f3f4f6',
            borderColor: '#374151',
            borderWidth: 1,
            padding: 12,
            boxPadding: 6
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            grid: {
                color: 'rgba(75, 85, 99, 0.2)'
            },
            ticks: {
                color: '#d1d5db'
            }
        },
        x: {
            grid: {
                display: false
            },
            ticks: {
                color: '#d1d5db'
            }
        }
    }
}

const weeklyActivityOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'top',
            labels: {
                color: '#e2e8f0',
                font: {
                    family: 'Inter, system-ui, sans-serif',
                    size: 12
                },
                padding: 20
            }
        },
        tooltip: {
            backgroundColor: 'rgba(17, 24, 39, 0.9)',
            titleColor: '#f9fafb',
            bodyColor: '#f3f4f6',
            borderColor: '#374151',
            borderWidth: 1,
            padding: 12,
            boxPadding: 6
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            grid: {
                color: 'rgba(75, 85, 99, 0.2)'
            },
            ticks: {
                color: '#d1d5db'
            }
        },
        x: {
            grid: {
                display: false
            },
            ticks: {
                color: '#d1d5db'
            }
        }
    }
}

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

// KPI calculations
const totalInventoryValue = computed(() => {
    return productVariations.value.reduce((total, item) => {
        return total + (item.price || 0) * (item.quantity || 0)
    }, 0)
})

const averageItemPrice = computed(() => {
    if (productVariations.value.length === 0) return 0
    const totalPrice = productVariations.value.reduce((sum, item) => sum + (item.price || 0), 0)
    return totalPrice / productVariations.value.length
})

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

                <!-- Extended Stats Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <!-- Total Products Card -->
                    <div class="bg-gray-800/50 backdrop-blur-sm rounded-lg p-4 border border-gray-700/50 shadow-lg">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-400 text-sm">Total Products</p>
                                <h3 class="text-2xl font-bold text-white mt-1">{{ products.length }}</h3>
                                <p class="text-green-400 text-xs mt-2 font-medium">
                                    <span class="flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                        </svg>
                                        12% from last month
                                    </span>
                                </p>
                            </div>
                            <div class="bg-gradient-to-br from-blue-500/30 to-purple-500/30 p-3 rounded-lg">
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
                                <p class="text-green-400 text-xs mt-2 font-medium">
                                    <span class="flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                        </svg>
                                        8% from last month
                                    </span>
                                </p>
                            </div>
                            <div class="bg-gradient-to-br from-purple-500/30 to-pink-500/30 p-3 rounded-lg">
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
                                <p class="text-yellow-400 text-xs mt-2 font-medium">
                                    <span class="flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        Requires attention
                                    </span>
                                </p>
                            </div>
                            <div class="bg-gradient-to-br from-yellow-500/30 to-orange-500/30 p-3 rounded-lg">
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
                                <p class="text-red-400 text-xs mt-2 font-medium">
                                    <span class="flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        Critical attention
                                    </span>
                                </p>
                            </div>
                            <div class="bg-gradient-to-br from-red-500/30 to-rose-500/30 p-3 rounded-lg">
                                <ArchiveBoxIcon class="w-6 h-6 text-red-400" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- New Additional KPI Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <!-- Total Inventory Value Card -->
                    <div class="bg-gray-800/50 backdrop-blur-sm rounded-lg p-4 border border-gray-700/50 shadow-lg">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-400 text-sm">Total Inventory Value</p>
                                <h3 class="text-2xl font-bold text-white mt-1">
                                    {{ formatCurrency(totalInventoryValue) }}
                                </h3>
                            </div>
                            <div class="bg-gradient-to-br from-emerald-500/30 to-teal-500/30 p-3 rounded-lg">
                                <CurrencyDollarIcon class="w-6 h-6 text-emerald-400" />
                            </div>
                        </div>
                    </div>
                    
                    <!-- Average Item Price Card -->
                    <div class="bg-gray-800/50 backdrop-blur-sm rounded-lg p-4 border border-gray-700/50 shadow-lg">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-400 text-sm">Average Item Price</p>
                                <h3 class="text-2xl font-bold text-white mt-1">
                                    {{ formatCurrency(averageItemPrice) }}
                                </h3>
                            </div>
                            <div class="bg-gradient-to-br from-cyan-500/30 to-blue-500/30 p-3 rounded-lg">
                                <CurrencyDollarIcon class="w-6 h-6 text-cyan-400" />
                            </div>
                        </div>
                    </div>
                    
                    <!-- Active Locations Card -->
                    <div class="bg-gray-800/50 backdrop-blur-sm rounded-lg p-4 border border-gray-700/50 shadow-lg">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-400 text-sm">Active Locations</p>
                                <h3 class="text-2xl font-bold text-white mt-1">
                                    {{ uniqueLocations.length > 0 ? uniqueLocations.length - 1 : 0 }}
                                </h3>
                            </div>
                            <div class="bg-gradient-to-br from-indigo-500/30 to-violet-500/30 p-3 rounded-lg">
                                <BuildingOfficeIcon class="w-6 h-6 text-indigo-400" />
                            </div>
                        </div>
                    </div>
                    
                    <!-- Product Categories Card -->
                    <div class="bg-gray-800/50 backdrop-blur-sm rounded-lg p-4 border border-gray-700/50 shadow-lg">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-400 text-sm">Product Categories</p>
                                <h3 class="text-2xl font-bold text-white mt-1">
                                    {{ uniqueCategories.length > 0 ? uniqueCategories.length - 1 : 0 }}
                                </h3>
                            </div>
                            <div class="bg-gradient-to-br from-fuchsia-500/30 to-pink-500/30 p-3 rounded-lg">
                                <TagIcon class="w-6 h-6 text-fuchsia-400" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Area with Tables and Charts -->
                <div class="flex flex-col xl:flex-row gap-6">
                    <!-- Table Section (2/3 width) -->
                    <div class="w-full xl:w-2/3">
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
                    
                    <!-- Charts and Widgets Section (1/3 width) -->
                    <div class="w-full xl:w-1/3 space-y-6">
                        <!-- Stock Status Pie Chart -->
                        <div class="bg-gray-800/50 backdrop-blur-sm rounded-lg overflow-hidden shadow-xl border border-gray-700/50 p-4">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-200">Stock Status</h3>
                                <ChartPieIcon class="w-5 h-5 text-purple-400" />
                            </div>
                            <div class="h-64">
                                <Pie 
                                    :data="stockStatusData" 
                                    :options="pieChartOptions"
                                />
                            </div>
                        </div>
                        
                        <!-- Category Distribution Chart -->
                        <div class="bg-gray-800/50 backdrop-blur-sm rounded-lg overflow-hidden shadow-xl border border-gray-700/50 p-4">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-200">Category Distribution</h3>
                                <ChartPieIcon class="w-5 h-5 text-cyan-400" />
                            </div>
                            <div class="h-64">
                                <Pie 
                                    :data="categoryDistributionData"
                                    :options="pieChartOptions"
                                />
                            </div>
                        </div>
                        
                        <!-- Top Products Chart -->
                        <div class="bg-gray-800/50 backdrop-blur-sm rounded-lg overflow-hidden shadow-xl border border-gray-700/50 p-4">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-200">Top Products by Quantity</h3>
                                <ChartBarIcon class="w-5 h-5 text-blue-400" />
                            </div>
                            <div class="h-64">
                                <Bar 
                                    :data="topProductsData"
                                    :options="barChartOptions"
                                />
                            </div>
                        </div>
                        
                        <!-- Weekly Stock Activity Chart -->
                        <div class="bg-gray-800/50 backdrop-blur-sm rounded-lg overflow-hidden shadow-xl border border-gray-700/50 p-4">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-200">Weekly Stock Activity</h3>
                                <ChartBarIcon class="w-5 h-5 text-emerald-400" />
                            </div>
                            <div class="h-64">
                                <Bar 
                                    :data="weeklyStockActivity"
                                    :options="weeklyActivityOptions"
                                />
                            </div>
                        </div>
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