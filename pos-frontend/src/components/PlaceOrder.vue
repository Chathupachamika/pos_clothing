<script setup>
import { useRouter } from 'vue-router' 
import { ref, computed, onMounted, watch, onUnmounted } from 'vue'
import Header from './Header.vue'
import Sidebar from './Sidebar.vue'
import SidebarCashier from './Sidebar-cashier.vue' 
import Swal from 'sweetalert2'
import {
  ShoppingCart,
  CreditCard,
  Banknote,
  Package,
  Plus,
  Minus,
  Trash2,
  ChevronDown,
  Check,
  X,
  LayoutGrid,
  ChevronLeft,
  ChevronRight,
  Search,
  User,
  FileText,
  Tag,
  Percent,
  Printer,
  Box,
  Layers,
  Keyboard,
  Scan
} from 'lucide-vue-next'
import {
  ShieldCheckIcon,
  WrenchScrewdriverIcon,
  BoltIcon,
  WrenchIcon,
  BuildingOffice2Icon,
  HomeModernIcon,
  QueueListIcon,
  UserPlusIcon 
} from '@heroicons/vue/24/outline'
import { connection } from '../api/axios'
import html2pdf from 'html2pdf.js'

const router = useRouter() 

const navigateToCustomers = () => {
  Swal.fire({
    title: 'Navigate to Customers?',
    text: "Do you want to go to the customers page?",
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#3B82F6',
    cancelButtonColor: '#6B7280',
    confirmButtonText: 'Yes, navigate',
    background: '#1e293b',
    color: '#ffffff'
  }).then((result) => {
    if (result.isConfirmed) {
      router.push('/customers')
    }
  })
}

const isSidebarVisible = ref(false)
const isPaymentModalOpen = ref(false)
const isReceiptVisible = ref(false)
const selectedPaymentMethod = ref('CASH')
const customerName = ref('Walk-in Customer')
const orderItems = ref([])
const searchQuery = ref('')
const transactionId = ref('')
const invoiceNumber = ref('')
const currentDate = ref('')
const applyDiscount = ref(false)
const customDiscountRate = ref(10)

const customers = ref([])
const loading = ref(true)

const toggleSidebar = () => {
  isSidebarVisible.value = !isSidebarVisible.value
}

const closeSidebar = () => {
  isSidebarVisible.value = false
}

const showSidebar = () => {
  isSidebarVisible.value = true
}

const currentCategoryPage = ref(0)
const categoriesPerPage = ref(4)

const updateCategoriesPerPage = () => {
  if (window.innerWidth < 640) {
    categoriesPerPage.value = 2
  } else if (window.innerWidth < 1024) {
    categoriesPerPage.value = 3
  } else if (window.innerWidth < 1280) {
    categoriesPerPage.value = 4
  } else {
    categoriesPerPage.value = 5
  }
}

onMounted(() => {
  updateCategoriesPerPage()
  window.addEventListener('resize', updateCategoriesPerPage)

  transactionId.value = '00000' + Math.floor(1000 + Math.random() * 9000).toString()

  const now = new Date()
  currentDate.value = `${now.toLocaleString('default', { month: 'long' })} ${now.getDate()}, ${now.getFullYear()}`
})

const beforeUnmount = () => {
  window.removeEventListener('resize', updateCategoriesPerPage)
}

const products = ref([])
const error = ref(null)

const fetchProducts = async () => {
  try {
    loading.value = true
    const response = await connection.get('/products')
    products.value = response.data.data.map(product => ({
      id: product.id,
      name: product.name,
      price: product.price,
      brand_name: product.brand_name,
      quantity: product.quantity,
      category: product.category,
      status: product.status,
      color: product.color,
      size: product.size,
      discount: product.discount,
      image_url: product.image_url 
    }))
  } catch (err) {
    error.value = 'Failed to fetch products'
    console.error('Error fetching products:', err)
  } finally {
    loading.value = false
  }
}

const searchMode = ref('manual')  // 'manual' or 'scanner'
const barcodeInput = ref('')
const barcodeTimeout = ref(null)

const handleBarcodeScanner = (event) => {
  if (searchMode.value !== 'scanner') return
  
  // Prevent default behavior for Enter key
  if (event.key === 'Enter') {
    event.preventDefault()
    processBarcode()
    return
  }
  
  // Only allow numbers
  if (!/^\d$/.test(event.key)) {
    return
  }
  
  // Clear previous timeout
  if (barcodeTimeout.value) clearTimeout(barcodeTimeout.value)
  
  // Append digit to barcode input
  barcodeInput.value += event.key
  
  // Set timeout longer to handle hardware scanners
  barcodeTimeout.value = setTimeout(() => {
    if (barcodeInput.value.length > 0) {
      processBarcode()
    }
  }, 200) // Increased timeout for better hardware scanner compatibility
}

const processBarcode = () => {
  if (!barcodeInput.value) return
  
  try {
    const searchId = parseInt(barcodeInput.value, 10)
    
    // Find product and handle stock check
    const product = products.value.find(p => p.id === searchId)
    
    if (product) {
      if (product.quantity <= 0) {
        Swal.fire({
          title: 'Out of Stock',
          text: `${product.name} is currently out of stock`,
          icon: 'warning',
          background: '#1e293b',
          color: '#ffffff'
        })
      } else {
        addToOrder(product)
        // Play success sound (optional)
        new Audio('/sounds/beep-success.mp3').play().catch(() => {})
      }
    } else {
      Swal.fire({
        title: 'Product Not Found',
        text: `No product found with barcode: ${searchId}`,
        icon: 'warning',
        background: '#1e293b',
        color: '#ffffff'
      })
    }
  } catch (error) {
    console.error('Error processing barcode:', error)
  }
  
  // Always clear input after processing
  barcodeInput.value = ''
}

const testBarcodeScanner = async () => {
  if (searchMode.value !== 'scanner') return
  
  // Show product list in table format
  console.table(products.value.map(p => ({
    ID: p.id,
    Name: p.name,
    Stock: p.quantity
  })))
  
  const { value: testBarcode } = await Swal.fire({
    title: 'Test Barcode Scanner',
    html: `
      <div class="text-left">
        <p class="mb-2">Available Product IDs:</p>
        <div class="bg-gray-800 p-4 rounded-lg text-xs overflow-auto max-h-48">
          ${products.value.map(p => 
            `<div class="mb-1 text-white">ID: ${p.id} - ${p.name}</div>`
          ).join('')}
        </div>
      </div>
    `,
    input: 'number',
    inputLabel: 'Enter a product ID to test:',
    inputPlaceholder: 'Enter ID...',
    background: '#1e293b',
    color: '#ffffff',
    confirmButtonColor: '#3B82F6',
    showCancelButton: true,
    inputValidator: (value) => {
      if (!value) {
        return 'Please enter a product ID'
      }
      if (!products.value.some(p => p.id === parseInt(value))) {
        return 'Invalid product ID'
      }
    }
  })

  if (testBarcode) {
    barcodeInput.value = testBarcode
    processBarcode()
  }
}

const filteredProducts = computed(() => {
  let result = products.value
  if (searchMode.value === 'manual' && searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(product =>
      product.name.toLowerCase().includes(query) || 
      product.id.toString().includes(query)
    )
  }
  return result
})

onMounted(() => {
  updateCategoriesPerPage()
  window.addEventListener('resize', updateCategoriesPerPage)

  transactionId.value = '00000' + Math.floor(1000 + Math.random() * 9000).toString()

  const now = new Date()
  currentDate.value = `${now.toLocaleString('default', { month: 'long' })} ${now.getDate()}, ${now.getFullYear()}`

  document.addEventListener('keydown', handleBarcodeScanner)
})

onUnmounted(() => {
  if (barcodeTimeout.value) clearTimeout(barcodeTimeout.value)
  document.removeEventListener('keydown', handleBarcodeScanner)
})

watch(categoriesPerPage, () => {
  if (currentCategoryPage.value >= maxCategoryPages.value) {
    currentCategoryPage.value = Math.max(0, maxCategoryPages.value - 1)
  }
})

const addToOrder = (product) => {
  const existingItem = orderItems.value.find(item => item.id === product.id)

  if (existingItem) {
    if (existingItem.quantity >= product.quantity) {
      Swal.fire({
        title: 'Stock Limit Reached',
        text: 'Cannot add more items than available in stock',
        icon: 'warning',
        background: '#1e293b',
        color: '#ffffff'
      })
      return
    }
    existingItem.quantity++
  } else {
    if (product.quantity > 0) {
      orderItems.value.push({
        id: product.id,
        name: product.name,
        price: product.price,
        quantity: 1,
        initialStock: product.quantity 
      })
    } else {
      Swal.fire({
        title: 'Out of Stock',
        text: 'This item is currently out of stock',
        icon: 'error',
        background: '#1e293b',
        color: '#ffffff'
      })
      return
    }
  }

  showToast(`Added ${product.name} to order`)
}

const subtotal = computed(() => {
  return orderItems.value.reduce((total, item) => {
    return total + (item.price * item.quantity)
  }, 0)
})

const discount = computed(() => {
  if (!applyDiscount.value) return 0
  return subtotal.value * (customDiscountRate.value / 100)
})

const total = computed(() => subtotal.value - discount.value)

const showToast = (message) => {
  const toast = document.createElement('div')
  toast.className = 'fixed bottom-8 left-1/2 transform -translate-x-1/2 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-fade-in flex items-center gap-2'
  toast.innerHTML = `
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
    </svg>
    ${message}`
  document.body.appendChild(toast)

  setTimeout(() => {
    toast.classList.add('animate-fade-out')
    setTimeout(() => {
      document.body.removeChild(toast)
    }, 300)
  }, 2000)
}

const updateQuantity = (item, change) => {
  const product = products.value.find(p => p.id === item.id)
  const newQuantity = item.quantity + change

  if (newQuantity <= 0) {
    removeFromOrder(item.id)
    return
  }

  if (newQuantity > product.quantity) { 
    Swal.fire({
      title: 'Stock Limit Reached',
      text: `Maximum available stock is ${product.quantity} units`,
      icon: 'warning',
      background: '#1e293b',
      color: '#ffffff'
    })
    item.quantity = product.quantity
    return
  }

  item.quantity = newQuantity
}

const removeFromOrder = (itemId) => {
  orderItems.value = orderItems.value.filter(item => item.id !== itemId)
}

const clearOrder = () => {
  orderItems.value = []
  customerName.value = 'Walk-in Customer'
  applyDiscount.value = false
  customDiscountRate.value = 10
}

const openPaymentModal = () => {
  if (orderItems.value.length > 0) {
    isPaymentModalOpen.value = true
  }
}

const cashierId = ref(null) 
const orderStatus = ref(1) 
const isProcessingOrder = ref(false)  
const getUserData = () => {
  const userData = localStorage.getItem('user')
  if (userData) {
    const user = JSON.parse(userData)
    cashierId.value = user.id
  }
}

const updateProductStock = (productId, quantity) => {
  const product = products.value.find(p => p.id === productId)
  if (product) {
    product.quantity -= quantity
    
    if (product.quantity === 0) {
      product.status = 'Out Of Stock'
    } else if (product.quantity < 20) {
      product.status = 'Low Stock'
    } else {
      product.status = 'In Stock'
    }
  }
}

const orderNumber = ref('')

const completeOrder = async () => {
  try {
    isProcessingOrder.value = true

    if (!cashierId.value) {
      throw new Error('No cashier ID found. Please login again.')
    }

    const salesData = {
      time: new Date().toISOString(),
      status: orderStatus.value,
      payment_type: selectedPaymentMethod.value,
      amount: total.value,
      cashier_id: cashierId.value,
      customer_id: customerName.value === 'Walk-in Customer' ? null : 
        customers.value.find(c => c.name === customerName.value)?.id,
      discount: applyDiscount.value ? customDiscountRate.value : 0,
      items: orderItems.value.map(item => ({
        product_id: item.id,
        quantity: item.quantity,
        price: item.price
      }))
    }

    const response = await connection.post('/sales', salesData)
    
    if (response.status === 201) {
      // Set both invoice number and order number from the response
      invoiceNumber.value = response.data.data.id.toString().padStart(4, '0')
      orderNumber.value = response.data.data.id.toString().padStart(4, '0')
      
      orderItems.value.forEach(item => {
        updateProductStock(item.id, item.quantity)
      })

      isPaymentModalOpen.value = false
      console.log('Order completed successfully!')

      Swal.fire({
        title: 'Order Completed!',
        text: 'Do you want to print a receipt?',
        icon: 'success',
        showCancelButton: true,
        confirmButtonColor: '#3B82F6',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, print receipt',
        cancelButtonText: 'No, thanks',
        background: '#1e293b',
        color: '#ffffff'
      }).then((result) => {
        if (result.isConfirmed) {
          isReceiptVisible.value = true
        } else {
          clearOrder()
        }
      })
    }
  } catch (error) {
    console.error('Error completing order:', {
      error,
      errorMessage: error.message,
      errorResponse: error.response?.data
    })

    Swal.fire({
      title: 'Error!',
      text: 'Failed to complete the order. Please try again.',
      icon: 'error',
      background: '#1e293b',
      color: '#ffffff'
    })
  } finally {
    isProcessingOrder.value = false
  }
}

const printReceipt = () => {
  const element = document.getElementById('receipt-content')
  const opt = {
    margin: [10, 10, 10, 10],
    filename: `invoice-${invoiceNumber.value}.pdf`,
    image: { type: 'jpeg', quality: 1 },
    html2canvas: { 
      scale: 2,
      useCORS: true,
      logging: false
    },
    jsPDF: { 
      unit: 'mm', 
      format: 'a4', 
      orientation: 'portrait'
    }
  }

  html2pdf().set(opt).from(element).save()
}

const closeReceipt = () => {
  isReceiptVisible.value = false
  clearOrder()
}

const orderId = ref('')

const isLowStock = (quantity) => quantity < 20;

const fetchCustomers = async () => {
  try {
    const response = await connection.get('/customers')
    customers.value = response.data.data
  } catch (err) {
    console.error('Error fetching customers:', err)
  }
}

const isAdmin = ref(false)

const getCategoryIcon = (category) => {
  const iconMap = {
    'Men': Box,
    'Women': Box,
    'Kids': Box,
    'Accessories': Tag,
    'Sportswear': Box,
    'Footwear': Box,
    'Traditional': Box,
    'Casual': Box,
    'Formal': Box
  }
  return iconMap[category] || Box
}

const getCategoryName = (category) => {
  return category || 'Uncategorized'
}

const mapProductToCategory = (category) => {
  return category
}

onMounted(async () => {
  getUserData()
  isAdmin.value = localStorage.getItem('isAdmin') === 'true'
  await fetchProducts()
  await fetchCustomers()
  
  transactionId.value = '00000' + Math.floor(1000 + Math.random() * 9000).toString()

  const now = new Date()
  currentDate.value = `${now.toLocaleString('default', { month: 'long' })} ${now.getDate()}, ${now.getFullYear()}`
})
</script>

<template>
  <div class="min-h-screen bg-[#0f172a] text-white font-sans">
    <div class="fixed left-0 top-0 w-2 h-full z-[55] hover-trigger" @mouseenter="showSidebar"></div>

    <SidebarCashier 
      v-if="!isAdmin"
      :isVisible="isSidebarVisible" 
      @closeSidebar="closeSidebar" 
    />
    <Sidebar
      v-else
      :isVisible="isSidebarVisible"
      @closeSidebar="closeSidebar"
    />

    <Header @toggleSidebar="toggleSidebar" />

    <div class="w-full pt-24 px-4 max-w-[1920px] mx-auto">
      <div class="flex flex-col md:flex-row gap-4">
        <div class="w-full md:w-[75%] lg:w-[80%]">
          <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <div>
              <h1 class="text-2xl font-bold text-[#4dabf7] flex items-center gap-2">
                <ShoppingCart class="w-6 h-6" />
                Place Order
              </h1>
              <p class="text-gray-300 text-sm mt-1">Create a new order for your customers</p>
            </div>

            <!-- Replace the existing search input with this new search section -->
            <div class="relative w-full sm:w-auto space-y-3">
              <!-- Search Mode Tabs -->
              <div class="flex p-1 bg-gray-800/50 rounded-lg shadow-inner">
                <button 
                  @click="searchMode = 'manual'"
                  :class="[
                    'flex-1 px-4 py-2 rounded-md text-sm font-medium transition-all duration-300 flex items-center justify-center gap-2',
                    searchMode === 'manual' 
                      ? 'bg-gradient-to-r from-blue-600 to-blue-500 text-white shadow-lg' 
                      : 'text-gray-400 hover:text-white hover:bg-gray-700/50'
                  ]"
                >
                  <Keyboard class="w-4 h-4" />
                  <span>Manual Search</span>
                </button>
                <button 
                  @click="searchMode = 'scanner'"
                  :class="[
                    'flex-1 px-4 py-2 rounded-md text-sm font-medium transition-all duration-300 flex items-center justify-center gap-2',
                    searchMode === 'scanner' 
                      ? 'bg-gradient-to-r from-blue-600 to-blue-500 text-white shadow-lg' 
                      : 'text-gray-400 hover:text-white hover:bg-gray-700/50'
                  ]"
                >
                  <Scan class="w-4 h-4" />
                  <span>Barcode Scanner</span>
                </button>
              </div>

              <!-- Search Input Area -->
              <div class="relative">
                <template v-if="searchMode === 'manual'">
                  <div class="relative group">
                    <div class="absolute inset-0 bg-blue-500 rounded-lg blur opacity-30 group-hover:opacity-40 transition-opacity"></div>
                    <div class="relative">
                      <input 
                        type="text" 
                        v-model="searchQuery" 
                        placeholder="Search by product name or ID..."
                        class="w-full sm:w-96 px-12 py-3 bg-gray-800/90 border border-gray-700 rounded-lg
                               focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500
                               text-white placeholder-gray-400 transition-all duration-300" 
                      />
                      <Keyboard class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" />
                      <Search class="absolute right-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" />
                    </div>
                  </div>
                </template>
                
                <template v-else>
                  <div class="space-y-3">
                    <div class="relative group">
                      <div class="absolute inset-0 bg-blue-500 rounded-lg blur opacity-30 group-hover:opacity-40 transition-opacity"></div>
                      <div class="relative flex gap-2">
                        <div class="flex-1 relative">
                          <input 
                            type="text" 
                            v-model="barcodeInput"
                            @keydown="handleBarcodeScanner"
                            placeholder="Scan product barcode..."
                            class="w-full px-12 py-3 bg-gray-800/90 border-2 border-blue-500/30 rounded-lg
                                   focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500
                                   text-white placeholder-gray-400 transition-all duration-300"
                            ref="barcodeInput"
                            autocomplete="off"
                          />
                          <div class="absolute left-4 top-1/2 transform -translate-y-1/2">
                            <Scan class="w-5 h-5 text-blue-400 animate-pulse" />
                          </div>
                          <div class="absolute right-4 top-1/2 transform -translate-y-1/2 flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></div>
                          </div>
                        </div>
                        <button 
                          @click="testBarcodeScanner"
                          class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg 
                                 flex items-center gap-2 transition-all duration-300 hover:shadow-lg"
                        >
                          <Scan class="w-4 h-4" />
                          Test
                        </button>
                      </div>
                    </div>
                    
                    <!-- Scanner Status Indicator -->
                    <div class="flex items-center gap-3 px-4 py-2 bg-gray-800/50 rounded-lg border border-gray-700">
                      <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></div>
                        <span class="text-sm text-gray-400">Scanner Active</span>
                      </div>
                      <div class="flex-1 h-1 bg-gray-700 rounded-full overflow-hidden">
                        <div class="h-full w-1/3 bg-blue-500 rounded-full animate-pulse"></div>
                      </div>
                      <span class="text-xs text-gray-500">Ready to scan</span>
                    </div>
                  </div>
                </template>
              </div>
            </div>
          </div>

          <div class="bg-[#1a2234] p-4 rounded-lg shadow-md">
            <div class="flex items-center gap-2 mb-4">
              <div class="p-2 bg-blue-500/20 text-blue-400 rounded-lg">
                <Package class="w-5 h-5" />
              </div>
              <h2 class="font-bold text-gray-200 text-lg">Products</h2>
              <div class="ml-auto text-sm text-gray-400">
                {{ filteredProducts.length }} items found
              </div>
            </div>

            <div v-if="loading" class="flex justify-center items-center py-12">
              <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
            </div>
            
            <div v-else-if="error" class="text-center py-12 text-red-500">
              {{ error }}
            </div>
            
            <div v-else-if="filteredProducts.length === 0" class="text-center py-12 text-gray-500 bg-gray-800/20 rounded-lg">
              <Package class="w-16 h-16 mx-auto mb-4 opacity-30" />
              <p class="text-lg font-medium">No products found</p>
              <p class="text-sm mt-2">Try changing your search term</p>
            </div>

            <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
              <div v-for="product in filteredProducts" 
                   :key="product.id" 
                   @click="addToOrder(product)" 
                   :class="[
                     'bg-gradient-to-br rounded-lg overflow-hidden cursor-pointer transition-all duration-200 border hover:shadow-lg group relative hover:translate-y-[-2px]',
                     {
                       'from-red-500/10 to-red-900/10 border-red-500/50': product.quantity === 0,
                       'from-yellow-500/10 to-yellow-900/10 border-yellow-500/50': product.quantity > 0 && product.quantity < 20,
                       'from-[#1e293b] to-[#1a2234] border-[#334155] hover:border-[#475569]': product.quantity >= 20
                     }
                   ]">
                
                <div class="aspect-w-16 aspect-h-9 bg-gray-800/50">
                  <div v-if="product.image_url" class="w-full h-48 overflow-hidden">
                    <img 
                      :src="product.image_url" 
                      :alt="product.name"
                      class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                      @error="$event.target.src = '/placeholder-product.png'"
                    />
                  </div>
                  <div v-else class="w-full h-48 flex items-center justify-center bg-gray-700/50">
                    <div class="text-4xl font-bold text-gray-500">
                      {{ product.name?.charAt(0).toUpperCase() }}
                    </div>
                  </div>
                </div>
                
                <div class="absolute top-0 left-0 w-1 h-full bg-[#334155]"></div>
                
                <div class="p-4 pl-5">
                  <div class="flex items-center justify-between mb-3">
                    <div class="bg-gray-700/50 text-gray-300 text-xs px-2 py-1 rounded-full flex items-center gap-1">
                      <component :is="getCategoryIcon(product.category)" class="w-3 h-3" />
                      <span>{{ getCategoryName(product.category) }}</span>
                    </div>
                    
                    <div :class="[
                      'text-xs px-2 py-1 rounded-full flex items-center gap-1',
                      {
                        'bg-red-500/20 text-red-400': product.quantity === 0,
                        'bg-yellow-500/20 text-yellow-400': product.quantity > 0 && product.quantity < 20,
                        'bg-green-500/20 text-green-400': product.quantity >= 20
                      }
                    ]">
                      <Layers class="w-3 h-3" />
                      <span>
                        {{ product.quantity === 0 ? 'Out of Stock' : 
                           product.quantity < 20 ? `Low Stock: ${product.quantity}` : 
                           `In Stock: ${product.quantity}` }}
                      </span>
                    </div>
                  </div>
                  
                  <div class="text-xs text-gray-500 mb-1">ID: #{{ product.id.toString().padStart(4, '0') }}</div>
                  
                  <div class="font-medium text-white text-lg mb-2">{{ product.name }}</div>
                  <div class="text-sm text-gray-400 mb-3">{{ product.brand_name }}</div>
                  
                  <div class="h-px bg-gradient-to-r from-transparent via-gray-700 to-transparent my-3"></div>
                  
                  <div class="flex justify-between items-center">
                    <div>
                      <div class="text-xs text-gray-400 mb-1">Price</div>
                      <div class="text-blue-400 font-bold text-lg">Rs. {{ Number(product.price).toLocaleString() }}</div>
                    </div>
                    
                    <button :class="[
                      'p-2 rounded-lg transition-colors duration-200 text-white flex items-center gap-2',
                      product.quantity === 0 ? 'bg-red-500 hover:bg-red-600' : 'bg-blue-500 hover:bg-blue-600'
                    ]">
                      <Plus class="w-4 h-4" />
                      <span>Add</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="w-full md:w-[25%] lg:w-[20%]">
          <div
            class="bg-[#1a2234] rounded-lg border border-[#334155] flex flex-col h-[calc(100vh-100px)] sticky top-24 shadow-lg">
            <div class="p-4 border-b border-gray-700/50 bg-[#1e293b]">
              <div class="flex justify-between items-center mb-3">
                <h2 class="font-bold text-gray-200 text-base flex items-center gap-1.5">
                  <ShoppingCart class="w-4 h-4 text-blue-400" />
                  Order Summary
                </h2>
                <div class="text-xs bg-blue-500/20 text-blue-400 px-2 py-0.5 rounded-full">
                  #{{ orderNumber || 'New Order' }}
                </div>
              </div>

              <div class="mb-3">
                <label class="block text-xs text-gray-400 mb-1.5 flex items-center justify-between">
                  <div class="flex items-center gap-1">
                    <User class="w-3 h-3" />
                    <span>Customer</span>
                  </div>
                  <button @click.prevent="navigateToCustomers" 
                          class="p-1 hover:bg-gray-700/50 rounded-full transition-colors group">
                    <UserPlusIcon class="w-4 h-4 text-blue-400 group-hover:scale-110 transition-transform" />
                  </button>
                </label>
                <select v-model="customerName"
                  class="w-full bg-gray-700/50 border border-gray-600/50 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:ring-1 focus:ring-blue-400/30 appearance-none">
                  <option>Walk-in Customer</option>
                  <option v-for="customer in customers" 
                          :key="customer.id" 
                          :value="customer.name">
                    {{ customer.name }}
                  </option>
                </select>
              </div>
            </div>

            <div class="flex-1 overflow-auto p-4 custom-scrollbar">
              <div v-if="orderItems.length === 0" class="text-center py-10 text-gray-500">
                <ShoppingCart class="w-12 h-12 mx-auto mb-3 opacity-30" />
                <p class="text-sm font-medium">Your cart is empty</p>
                <p class="text-xs mt-1">Click on products to add them to your order</p>
              </div>

              <div v-else class="space-y-3">
                <div v-for="item in orderItems" :key="item.id"
                  class="bg-gray-700/20 rounded-lg p-3 border border-gray-700/30 hover:border-gray-600/50 transition-colors duration-200">
                  <div class="flex-1 min-w-0">
                    <div class="flex justify-between">
                      <div class="font-medium text-sm truncate text-white">{{ item.name }}</div>
                      <div class="text-blue-400 text-xs font-bold">Rs. {{ item.price.toLocaleString() }}</div>
                    </div>

                    <div class="flex justify-between items-center mt-3">
                      <div class="flex items-center gap-1 bg-gray-800/70 rounded-lg p-1">
                        <button @click.stop="updateQuantity(item, -1)"
                          class="p-1 hover:bg-gray-700 rounded transition-colors">
                          <Minus class="w-3 h-3" />
                        </button>
                        <span class="px-2 min-w-[28px] text-center text-xs font-medium text-white">{{ item.quantity }}</span>
                        <button @click.stop="updateQuantity(item, 1)"
                          class="p-1 hover:bg-gray-700 rounded transition-colors">
                          <Plus class="w-3 h-3" />
                        </button>
                      </div>

                      <div class="text-gray-300 text-xs font-medium">
                        Rs. {{ (item.price * item.quantity).toLocaleString() }}
                      </div>

                      <button @click.stop="removeFromOrder(item.id)"
                        class="p-1.5 text-red-400 hover:bg-red-400/10 rounded transition-colors">
                        <Trash2 class="w-3.5 h-3.5" />
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="p-4 border-t border-gray-700/50 bg-[#1e293b]">
              <div class="space-y-2 mb-4 text-sm">
                <div class="flex justify-between text-gray-300">
                  <span>Subtotal</span>
                  <span>Rs. {{ subtotal.toLocaleString() }}</span>
                </div>
                
                <div class="bg-gray-700/30 rounded-lg p-3 border border-gray-600/50">
                  <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                      <div class="p-1.5 bg-blue-500/20 rounded-md">
                        <Percent class="w-4 h-4 text-blue-400" />
                      </div>
                      <label for="apply-discount" class="text-gray-200 font-medium cursor-pointer">
                        Discount Options
                      </label>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                      <input type="checkbox" id="apply-discount" v-model="applyDiscount" 
                             class="sr-only peer">
                      <div class="w-11 h-6 bg-gray-600 rounded-full peer peer-checked:after:translate-x-full 
                                  peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 
                                  after:left-[2px] after:bg-white after:border-gray-300 after:border 
                                  after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-500">
                      </div>
                    </label>
                  </div>
                  
                  <transition name="fade">
                    <div v-if="applyDiscount" class="mt-3 pt-3 border-t border-gray-600/50">
                      <div class="flex flex-col gap-2">
                        <div class="flex items-center justify-between gap-3">
                          <div class="flex-1">
                            <input type="range" v-model="customDiscountRate" min="0" max="100" step="5"
                                   class="w-full h-2 bg-gray-600 rounded-lg appearance-none cursor-pointer
                                          accent-blue-500" />
                          </div>
                          <div class="flex items-center gap-2 min-w-[80px]">
                            <input type="number" v-model="customDiscountRate" min="0" max="100"
                                   class="w-14 bg-gray-700/50 border border-gray-600/50 rounded-lg px-2 py-1 
                                          text-sm text-white focus:outline-none focus:ring-1 focus:ring-blue-400/30 
                                          text-center" />
                            <span class="text-gray-400">%</span>
                          </div>
                        </div>
                        <div class="flex justify-between text-sm">
                          <span class="text-gray-400">Discount Amount:</span>
                          <span class="text-blue-400 font-medium">Rs. {{ discount.toLocaleString() }}</span>
                        </div>
                      </div>
                    </div>
                  </transition>
                </div>

                <div class="flex justify-between font-bold pt-2 border-t border-gray-700/50 text-white">
                  <span>Total</span>
                  <span>Rs. {{ total.toLocaleString() }}</span>
                </div>
              </div>

              <div class="grid grid-cols-2 gap-2">
                <button @click="clearOrder"
                  class="py-2 px-3 bg-red-500/20 text-red-400 hover:bg-red-500/30 rounded-lg transition-colors duration-200 flex items-center justify-center gap-1.5 text-sm">
                  <Trash2 class="w-4 h-4" />
                  <span>Clear</span>
                </button>
                <button @click="openPaymentModal" :disabled="orderItems.length === 0" :class="[
                  'py-2 px-3 rounded-lg transition-colors duration-200 flex items-center justify-center gap-1.5 text-sm',
                  orderItems.length === 0
                    ? 'bg-gray-700/50 text-gray-500 cursor-not-allowed'
                    : 'bg-blue-500 hover:bg-blue-600 text-white'
                ]">
                  <CreditCard class="w-4 h-4" />
                  <span>Checkout</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="isPaymentModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="isPaymentModalOpen = false"></div>

      <div
        class="relative bg-gray-800 rounded-lg shadow-xl border border-gray-700/50 w-full max-w-md p-6 z-10 animate-scale-in">
        <div class="flex justify-between items-center mb-5">
          <h2 class="text-xl font-bold text-gray-200">Complete Payment</h2>
          <button @click="isPaymentModalOpen = false" class="p-1.5 hover:bg-gray-700/50 rounded-full transition-colors">
            <X class="w-5 h-5" />
          </button>
        </div>

        <div class="mb-5">
          <div class="text-sm text-gray-400 mb-1">Order Total</div>
          <div class="text-2xl font-bold text-white">Rs. {{ total.toLocaleString() }}</div>
        </div>

        <div class="mb-6">
          <div class="text-sm text-gray-400 mb-3">Select Payment Method</div>
          <div class="grid grid-cols-2 gap-3">
            <button @click="selectedPaymentMethod = 'CASH'" :class="[
              'flex flex-col items-center gap-2 p-3 rounded-lg transition-all duration-200',
              selectedPaymentMethod === 'CASH'
                ? 'bg-blue-500/20 border-2 border-blue-500/50'
                : 'bg-gray-700/50 border border-gray-700/50 hover:bg-gray-700'
            ]">
              <Banknote class="w-6 h-6" :class="selectedPaymentMethod === 'CASH' ? 'text-blue-400' : 'text-white'" />
              <span class="text-sm text-white">Cash</span>
            </button>

            <button @click="selectedPaymentMethod = 'CREDIT_CARD'" :class="[
              'flex flex-col items-center gap-2 p-3 rounded-lg transition-all duration-200',
              selectedPaymentMethod === 'CREDIT_CARD'
                ? 'bg-blue-500/20 border-2 border-blue-500/50'
                : 'bg-gray-700/50 border border-gray-700/50 hover:bg-gray-700'
            ]">
              <CreditCard class="w-6 h-6" :class="selectedPaymentMethod === 'CREDIT_CARD' ? 'text-blue-400' : 'text-white'" />
              <span class="text-sm text-white">Credit Card</span>
            </button>

            <button @click="selectedPaymentMethod = 'DEBIT_CARD'" :class="[
              'flex flex-col items-center gap-2 p-3 rounded-lg transition-all duration-200',
              selectedPaymentMethod === 'DEBIT_CARD'
                ? 'bg-blue-500/20 border-2 border-blue-500/50'
                : 'bg-gray-700/50 border border-gray-700/50 hover:bg-gray-700'
            ]">
              <CreditCard class="w-6 h-6" :class="selectedPaymentMethod === 'DEBIT_CARD' ? 'text-blue-400' : 'text-white'" />
              <span class="text-sm text-white">Debit Card</span>
            </button>
          </div>
        </div>

        <div class="flex gap-3">
          <button @click="isPaymentModalOpen = false"
            class="flex-1 py-3 px-4 bg-gray-700 hover:bg-gray-600 rounded-lg transition-colors duration-200 text-sm text-white">
            Cancel
          </button>
          <button 
            @click="completeOrder"
            :disabled="isProcessingOrder"
            class="flex-1 py-3 px-4 bg-blue-500 hover:bg-blue-600 rounded-lg transition-colors duration-200 
                   flex items-center justify-center gap-2 text-sm text-white disabled:opacity-50 
                   disabled:cursor-not-allowed disabled:hover:bg-blue-500">
            <template v-if="isProcessingOrder">
              <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>Processing...</span>
            </template>
            <template v-else>
              <Check class="w-5 h-5" />
              <span>Complete Order</span>
            </template>
          </button>
        </div>
      </div>
    </div>

    <div v-if="isReceiptVisible" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="closeReceipt"></div>

      <div
        class="relative bg-white rounded-lg shadow-xl w-full max-w-[800px] z-10 animate-scale-in overflow-auto max-h-[90vh]">
        <div id="receipt-content" class="p-8 text-gray-800">
          <div class="border-b-2 border-gray-300 pb-8">
            <div class="flex justify-between items-start">
              <div>
                <div class="text-3xl font-bold text-blue-600">HARDWARE SUPPLY</div>
                <div class="text-sm text-gray-600 mt-2">
                  102 Railway Ave.<br>
                  Kandy, Sri Lanka<br>
                  Phone: (94) 81 123 4567<br>
                  Email: info@clothingsupply.com
                </div>
              </div>
              <div class="text-right">
                <div class="text-2xl font-bold text-gray-700">INVOICE</div>
                <div class="mt-2 text-sm">
                  <div class="text-gray-600">Invoice No: <span class="text-gray-800 font-medium">#{{ invoiceNumber }}</span></div>
                  <div class="text-gray-600">Date: <span class="text-gray-800">{{ currentDate }}</span></div>
                  <div class="text-gray-600">Transaction ID: <span class="text-gray-800">{{ transactionId }}</span></div>
                </div>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-12 py-8">
            <div>
              <div class="text-sm text-gray-600 uppercase tracking-wider mb-2">Bill To</div>
              <div class="text-base font-bold text-gray-800">{{ customerName }}</div>
            </div>
            <div>
              <div class="text-sm text-gray-600 uppercase tracking-wider mb-2">Payment Details</div>
              <div class="space-y-1 text-sm">
                <div class="grid grid-cols-2">
                  <span class="text-gray-600">Payment Method:</span>
                  <span class="text-gray-800 font-medium">{{ selectedPaymentMethod }}</span>
                </div>
                <div class="grid grid-cols-2">
                  <span class="text-gray-600">Payment Terms:</span>
                  <span class="text-gray-800">Net 30</span>
                </div>
                <div class="grid grid-cols-2">
                  <span class="text-gray-600">Due Date:</span>
                  <span class="text-gray-800">{{ new Date(new Date().setDate(new Date().getDate() + 30)).toLocaleDateString() }}</span>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-8">
            <table class="w-full">
              <thead>
                <tr class="bg-gray-100">
                  <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Description</th>
                  <th class="py-3 px-4 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">Unit Price</th>
                  <th class="py-3 px-4 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">Quantity</th>
                  <th class="py-3 px-4 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">Amount</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr v-for="item in orderItems" :key="item.id" class="text-sm">
                  <td class="py-4 px-4">
                    <div class="font-medium text-gray-800">{{ item.name }}</div>
                    <div class="text-gray-500 text-xs">Item #{{ item.id.toString().padStart(4, '0') }}</div>
                  </td>
                  <td class="py-4 px-4 text-right text-gray-800">Rs. {{ item.price.toLocaleString() }}</td>
                  <td class="py-4 px-4 text-center text-gray-800">{{ item.quantity }}</td>
                  <td class="py-4 px-4 text-right font-medium text-gray-800">Rs. {{ (item.price * item.quantity).toLocaleString() }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="mt-8 border-t border-gray-200 pt-8">
            <div class="flex justify-end">
              <div class="w-64">
                <div class="space-y-2">
                  <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Subtotal</span>
                    <span class="text-gray-800 font-medium">Rs. {{ subtotal.toLocaleString() }}</span>
                  </div>
                  <div v-if="applyDiscount" class="flex justify-between text-sm">
                    <span class="text-gray-600">Discount ({{ customDiscountRate }}%)</span>
                    <span class="text-red-600 font-medium">- Rs. {{ discount.toLocaleString() }}</span>
                  </div>
                  <div class="flex justify-between text-base font-bold border-t border-gray-300 pt-2 mt-2">
                    <span class="text-gray-800">Total</span>
                    <span class="text-blue-600">Rs. {{ total.toLocaleString() }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-12 pt-8 border-t border-gray-200">
            <div class="text-center text-gray-600 text-sm">
              <div class="font-medium text-gray-800 mb-2">Thank you for your business!</div>
              <div>For any inquiries, please contact us at support@clothingsupply.com</div>
              <div class="mt-4 text-xs text-gray-500">
                This is a computer-generated invoice and requires no signature.
              </div>
            </div>
          </div>

          <div class="mt-8 text-xs text-gray-500">
            <div class="font-medium text-gray-600 mb-1">Terms & Conditions:</div>
            <ul class="list-disc pl-4 space-y-1">
              <li>Payment is due within 30 days</li>
              <li>Please include invoice number on your payment</li>
              <li>All prices are in Sri Lankan Rupees (Rs.)</li>
            </ul>
          </div>
        </div>

        <div class="sticky bottom-0 bg-white p-4 border-t border-gray-200">
          <button @click="printReceipt"
            class="w-full py-2.5 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors duration-200 flex items-center justify-center gap-2">
            <Printer class="w-5 h-5" />
            <span>Print Receipt</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.hover-trigger {
  background: transparent;
}

.custom-scrollbar {
  scrollbar-width: thin;
  scrollbar-color: #4B5563 transparent;
}

.custom-scrollbar::-webkit-scrollbar {
  width: 5px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: #4B5563;
  border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background-color: #6B7280;
}

.custom-scrollbar-x {
  scrollbar-width: thin;
  scrollbar-color: #4B5563 transparent;
}

.custom-scrollbar-x::-webkit-scrollbar {
  height: 5px;
}

.custom-scrollbar-x::-webkit-scrollbar-track {
  background: transparent;
}

.custom-scrollbar-x::-webkit-scrollbar-thumb {
  background-color: #4B5563;
  border-radius: 3px;
}

.custom-scrollbar-x::-webkit-scrollbar-thumb:hover {
  background-color: #6B7280;
}

button {
  transition: all 0.2s ease;
}

button:hover:not(:disabled) {
  transform: translateY(-1px);
}

button:active:not(:disabled) {
  transform: translateY(0);
}

.animate-scale-in {
  animation: scaleIn 0.3s ease forwards;
}

.animate-fade-in {
  animation: fadeIn 0.3s ease forwards;
}

.animate-fade-out {
  animation: fadeOut 0.3s ease forwards;
}

@keyframes scaleIn {
  0% {
    opacity: 0;
    transform: scale(0.95);
  }

  100% {
    opacity: 1;
    transform: scale(1);
  }
}

@keyframes fadeIn {
  0% {
    opacity: 0;
  }

  100% {
    opacity: 1;
  }
}

@keyframes fadeOut {
  0% {
    opacity: 1;
    transform: translate(-50%, 0);
  }

  100% {
    opacity: 0;
    transform: translate(-50%, 1rem);
  }
}

.category-icon-container {
  position: relative;
  transition: transform 0.3s ease;
}

.category-icon-container:hover .category-icon {
  animation: icon-wave 2s ease-in-out infinite;
}

@keyframes icon-wave {
  0% {
    transform: translateY(0) scale(1);
  }
  25% {
    transform: translateY(-3px) scale(1.1);
  }
  50% {
    transform: translateY(0) scale(1);
  }
  75% {
    transform: translateY(3px) scale(0.95);
  }
  100% {
    transform: translateY(0) scale(1);
  }
}

[class*="category-icon"].text-blue-400 {
  animation: float 3s ease-in-out infinite;
}

@keyframes float {
  0% {
    transform: translateY(0) rotate(0);
  }
  50% {
    transform: translateY(-5px) rotate(5deg);
  }
  100% {
    transform: translateY(0) rotate(0);
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 0.5;
  }
  50% {
    opacity: 0.2;
  }
}

.animate-bounce {
  animation: bounce 2s infinite;
}

@keyframes bounce {
  0%, 100% {
    transform: translateY(-5%);
    animation-timing-function: cubic-bezier(0.8, 0, 1, 1);
  }
  50% {
    transform: translateY(0);
    animation-timing-function: cubic-bezier(0, 0, 0.2, 1);
  }
}

.fade-enter-active,
.fade-leave-active {
  transition: all 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

input[type="range"]::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 16px;
  height: 16px;
  background: #3B82F6;
  border-radius: 50%;
  cursor: pointer;
  transition: all 0.2s ease;
}

input[type="range"]::-webkit-slider-thumb:hover {
  transform: scale(1.1);
  box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.3);
}

input[type="range"]::-moz-range-thumb {
  width: 16px;
  height: 16px;
  background: #3B82F6;
  border-radius: 50%;
  cursor: pointer;
  transition: all 0.2s ease;
  border: none;
}

input[type="range"]::-moz-range-thumb:hover {
  transform: scale(1.1);
  box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.3);
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}

.low-stock {
  animation: pulse-warning 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse-warning {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.7;
  }
}
</style>