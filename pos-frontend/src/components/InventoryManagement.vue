<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import Swal from 'sweetalert2'
import Header from './Header.vue'
import Sidebar from './Sidebar.vue'
import { 
  ArchiveBoxIcon, 
  ArrowTrendingUpIcon, 
  ArrowTrendingDownIcon,
  ExclamationTriangleIcon,
  MagnifyingGlassIcon,
  PlusCircleIcon,
  ArrowPathIcon,
  FunnelIcon,
  ArrowDownTrayIcon,
  XMarkIcon,
  CheckIcon,
  PencilIcon,
  TrashIcon,
  ChevronUpIcon,
  ChevronDownIcon,
  EyeIcon
} from '@heroicons/vue/24/outline'
import { Bar } from 'vue-chartjs'
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend } from 'chart.js'
import { connection } from '@/api/axios'
import * as XLSX from 'xlsx'
import StockUpdateGRN from './StockUpdateGRN.vue'
import html2pdf from 'html2pdf.js'

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend)

const searchQuery = ref('')
const selectedCategory = ref('All')
const showAddModal = ref(false)
const showStockModal = ref(false)
const showDeleteConfirm = ref(false)
const showViewModal = ref(false)
const sortBy = ref('name')
const sortDirection = ref('asc')
const currentPage = ref(1)
const itemsPerPage = ref(10)
const selectedItem = ref(null)

const getSriLankaDateTime = () => {
  const options = { timeZone: 'Asia/Colombo' }
  const sriLankaDate = new Date().toLocaleString('en-US', options)
  const date = new Date(sriLankaDate)
  return date.toISOString().slice(0, 16) // Format: YYYY-MM-DDTHH:mm
}

const stockAdjustment = ref({
  quantity: 0,
  restock_date_time: getSriLankaDateTime(),
  added_stock_amount: 0,
  location: '',
  status: 'In Stock'
})

const inventory = ref([])

const stockHistory = ref([])

const newItem = ref({
  quantity: 0,
  restock_date_time: getSriLankaDateTime(),
  added_stock_amount: 0,
  location: '',
  status: 'In Stock',
})

const isAddingInventory = ref(false)

const categories = ref([
  'All'
])

const locations = ref([
  // Update with other location options if needed
])

const suppliers = ref([
  'SafetyFirst Ltd',
  'PowerMax Tools',
  'HandyTools Inc',
  'PlumbPro Supplies',
  'BuildRight Materials',
  'ColorMaster Supplies',
  'BrightSpark Electrical'
])

const adjustmentReasons = ref([
  'Restock',
  'Sale',
  'Damaged',
  'Returned',
  'Inventory Count',
  'Transfer',
  'Other'
])

const inventoryChartData = computed(() => {
  const locationData = {
    currentStock: {},
    stockChanges: {}
  }
  
  inventory.value.forEach(item => {
    if (!locationData.currentStock[item.location]) {
      locationData.currentStock[item.location] = 0
      locationData.stockChanges[item.location] = 0
    }
    locationData.currentStock[item.location] += item.quantity
    locationData.stockChanges[item.location] += (item.added_stock_amount || 0)
  })
  
  return {
    labels: Object.keys(locationData.currentStock),
    datasets: [
      {
        label: 'Current Stock',
        data: Object.values(locationData.currentStock),
        backgroundColor: 'rgba(34, 197, 94, 0.7)',
        borderColor: 'rgba(34, 197, 94, 1)',
        borderWidth: 2,
        borderRadius: 6,
        order: 1
      },
      {
        label: 'Stock Updates',
        data: Object.values(locationData.stockChanges),
        backgroundColor: 'rgba(99, 102, 241, 0.7)',
        borderColor: 'rgba(99, 102, 241, 1)',
        borderWidth: 2,
        borderRadius: 6,
        order: 2
      }
    ]
  }
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: {
    intersect: false,
    mode: 'index'
  },
  plugins: {
    legend: {
      display: true,
      position: 'top',
      labels: {
        color: '#e5e7eb',
        usePointStyle: true,
        pointStyle: 'circle',
        padding: 15,
        font: {
          size: 12
        }
      }
    },
    tooltip: {
      backgroundColor: 'rgba(17, 24, 39, 0.95)',
      titleColor: '#e5e7eb',
      bodyColor: '#e5e7eb',
      borderColor: 'rgba(75, 85, 99, 0.3)',
      borderWidth: 1,
      padding: 12,
      displayColors: true,
      callbacks: {
        label: function(context) {
          let label = context.dataset.label || '';
          if (label) {
            label += ': ';
          }
          if (context.parsed.y !== null) {
            label += context.parsed.y.toLocaleString() + ' units';
          }
          return label;
        }
      }
    },
    title: {
      display: true,
      text: 'Stock Distribution by Location',
      color: '#e5e7eb',
      font: {
        size: 16,
        weight: 'bold'
      },
      padding: {
        top: 10,
        bottom: 20
      }
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      grid: {
        color: 'rgba(75, 85, 99, 0.2)',
        drawBorder: false
      },
      ticks: {
        color: '#9ca3af',
        font: {
          size: 11
        },
        callback: function(value) {
          return value.toLocaleString() + ' units';
        }
      },
      title: {
        display: true,
        text: 'Stock Quantity',
        color: '#9ca3af',
        font: {
          size: 12
        }
      }
    },
    x: {
      grid: {
        display: false
      },
      ticks: {
        color: '#9ca3af',
        font: {
          size: 11
        }
      },
      title: {
        display: true,
        text: 'Location',
        color: '#9ca3af',
        font: {
          size: 12
        }
      }
    }
  }
}

const filteredInventory = computed(() => {
  let result = inventory.value

  if (selectedCategory.value !== 'All') {
    result = result.filter(item => item.location === selectedCategory.value)
  }

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(item => 
      item.status?.toLowerCase().includes(query) ||
      item.name?.toLowerCase().includes(query) || 
      item.id?.toString().includes(query)
    )
  }

  result = [...result].sort((a, b) => {
    let comparison = 0
    
    switch(sortBy.value) {
      case 'name':
        comparison = (a.name || '').localeCompare(b.name || '')
        break
      case 'quantity':
        comparison = a.quantity - b.quantity
        break
      case 'added_stock_amount':
        comparison = (a.added_stock_amount || 0) - (b.added_stock_amount || 0)
        break
      case 'location':
        comparison = a.location.localeCompare(b.location)
        break
      case 'status':
        comparison = a.status.localeCompare(b.status)
        break
      case 'lastUpdated':
        comparison = new Date(a.restock_date_time) - new Date(b.restock_date_time)
        break
    }
    
    return sortDirection.value === 'asc' ? comparison : -comparison
  })

  return result
})

const paginatedInventory = computed(() => {
  const startIndex = (currentPage.value - 1) * itemsPerPage.value
  const endIndex = startIndex + itemsPerPage.value
  return filteredInventory.value.slice(startIndex, endIndex)
})

const totalPages = computed(() => {
  return Math.ceil(filteredInventory.value.length / itemsPerPage.value)
})

const lowStockItems = computed(() => {
  return inventory.value.filter(item => item.quantity < 20 && item.quantity > 0)
})

const totalItems = computed(() => {
  return inventory.value.reduce((sum, item) => sum + item.quantity, 0)
})

const inventoryValue = computed(() => {
  return inventory.value.reduce((sum, item) => {
    // Get product price from the joined data
    const price = item.price || 0;
    return sum + (item.quantity * price);
  }, 0)
})

const totalProfit = computed(() => {
  return inventory.value.reduce((sum, item) => {
    return sum + (item.total_profit || 0)
  }, 0)
})

const isSidebarVisible = ref(false)

const toggleSidebar = () => {
  isSidebarVisible.value = !isSidebarVisible.value
}

const closeSidebar = () => {
  isSidebarVisible.value = false
}

const showSidebar = () => {
  isSidebarVisible.value = true
}

const toggleSort = (column) => {
  if (sortBy.value === column) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortBy.value = column
    sortDirection.value = 'asc'
  }
}

const getSortIcon = (column) => {
  if (sortBy.value !== column) return null
  return sortDirection.value === 'asc' ? ChevronUpIcon : ChevronDownIcon
}

const openAddModal = () => {
  newItem.value = {
    quantity: 0,
    restock_date_time: getSriLankaDateTime(),
    added_stock_amount: 0,
    location: '',
    status: 'In Stock',
  }
  showAddModal.value = true
}

const saveNewItem = async () => {
  if (!validateNewItem()) return
  isAddingInventory.value = true

  try {
    const response = await connection.post('/inventory', newItem.value)
    inventory.value.push(response.data)
    
    newItem.value = {
      quantity: 0,
      restock_date_time: getSriLankaDateTime(),
      added_stock_amount: 0,
      location: '',
      status: 'In Stock'
    }
    
    showAddModal.value = false
    await Swal.fire({
      position: "center",
      icon: "success",
      title: "Inventory Added Successfully!",
      showConfirmButton: false,
      timer: 1500,
      background: '#1e293b',
      color: '#ffffff'
    })
  } catch (error) {
    let errorMessage = 'Failed to add inventory item'
    if (error.response?.data?.message) {
      errorMessage = error.response.data.message
    }
    console.error('Error adding inventory:', error)
    Swal.fire({
      icon: "error",
      title: "Error!",
      text: errorMessage,
      background: '#1e293b',
      color: '#ffffff'
    })
  } finally {
    isAddingInventory.value = false
  }
}

const determineStatus = (quantity) => {
  if (quantity === 0) {
    return 'Out Of Stock'
  } else if (quantity < 5) {
    return 'Low Stock'
  }
  return 'In Stock'
}

const validateNewItem = () => {
  if (!newItem.value.quantity || !newItem.value.location) {
      const missingField = !newItem.value.quantity ? 'quantity' : 
                         !newItem.value.location ? 'location' : ''
      
      Swal.fire({
          icon: "error",
          title: "Error!",
          text: `Inventory ${missingField} is required`,
          background: '#1e293b',
          color: '#ffffff'
      })
      return false
  }
  return true
}

const isLoading = ref(true)

const fetchInventory = async () => {
  try {
    isLoading.value = true
    const response = await connection.get('/inventory')
    
    if (!response.data) {
      throw new Error('No data received from server')
    }
    
    inventory.value = response.data.map(item => ({
      ...item,
      price: parseFloat(item.price || 0),
      profit: parseFloat(item.profit || 0),
      total_value: parseFloat(item.total_value || 0),
      total_profit: parseFloat(item.total_profit || 0),
      brand_name: item.brand_name || '',
      name: item.name || ''
    }))
  } catch (error) {
    console.error('Error fetching inventory:', error)
    let errorMessage = 'Failed to fetch inventory'
    if (error.response?.data?.message) {
      errorMessage += `: ${error.response.data.message}`
    }
    
    Swal.fire({
      icon: "error",
      title: "Error!",
      text: errorMessage,
      background: '#1e293b',
      color: '#ffffff'
    })
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchInventory()
})

const statusOptions = ref([
  'In Stock',
  'Out of Stock',
  'Low Stock'
])

const router = useRouter()

const openStockModal = (item) => {
  selectedItem.value = item
  stockAdjustment.value = {
    quantity: 0,
    restock_date_time: getSriLankaDateTime(),
    added_stock_amount: 0,
    location: '',
    status: 'In Stock'
  }
  showStockModal.value = true
  
  nextTick(() => {
    document.getElementById('stock-quantity').focus()
  })
}

const closeStockModal = () => {
  showStockModal.value = false
  selectedItem.value = null
}

const isUpdatingStock = ref(false)

const showGRN = ref(false)
const grnProduct = ref(null)
const grnNumber = ref('')

const confirmStockAdjustment = async () => {
  if (!selectedItem.value || stockAdjustment.value.quantity === 0) {
    Swal.fire({
      icon: "error",
      title: "Error!",
      text: "Please enter a valid quantity",
      background: '#1e293b',
      color: '#ffffff'
    })
    return
  }

  // Check if removing more stock than available
  if (stockAdjustment.value.quantity < 0 && 
      Math.abs(stockAdjustment.value.quantity) > selectedItem.value.quantity) {
    Swal.fire({
      icon: "error",
      title: "Error!",
      text: "Cannot remove more stock than available",
      background: '#1e293b',
      color: '#ffffff'
    })
    return
  }

  try {
    isUpdatingStock.value = true
    const newQuantity = Math.max(0, selectedItem.value.quantity + stockAdjustment.value.quantity)
    
    // Update inventory first
    const response = await connection.put(`/inventory/${selectedItem.value.id}`, {
      quantity: newQuantity,
      restock_date_time: stockAdjustment.value.restock_date_time,
      added_stock_amount: stockAdjustment.value.quantity > 0 ? stockAdjustment.value.quantity : 0,
      location: selectedItem.value.location,
      status: determineStatus(newQuantity)
    })

    // Update local state
    const index = inventory.value.findIndex(item => item.id === selectedItem.value.id)
    if (index !== -1) {
      inventory.value[index] = response.data
    }

    // Only generate GRN for positive stock adjustments
    if (stockAdjustment.value.quantity > 0) {
      try {
        const timestamp = Date.now()
        const randomNum = Math.floor(Math.random() * 1000)
        const newGrnNumber = `GRN-${timestamp}-${randomNum}`
        grnNumber.value = newGrnNumber

        // Get the supplier and admin IDs from the selected item or use default values
        const supplierID = response.data.supplier_id || 1  // Use a default supplier ID if null
        const adminID = response.data.admin_id || 1        // Use a default admin ID if null

        // Create GRN note
        await connection.post('/grn-notes', {
          grn_number: newGrnNumber,
          product_id: selectedItem.value.id,
          supplier_id: supplierID,
          admin_id: adminID,
          price: selectedItem.value.price || 0,
          product_details: {
            name: selectedItem.value.name || '',
            description: selectedItem.value.description || '',
            brand_name: selectedItem.value.brand_name || '',
            size: selectedItem.value.size || '',
            color: selectedItem.value.color || ''
          },
          received_date: stockAdjustment.value.restock_date_time,
          previous_quantity: selectedItem.value.quantity - stockAdjustment.value.quantity,
          new_quantity: newQuantity,
          adjusted_quantity: stockAdjustment.value.quantity,
          adjustment_type: 'addition'
        })

        showGRN.value = true

        await Swal.fire({
          position: "center",
          icon: "success",
          title: "Stock Updated Successfully!",
          text: "GRN document has been generated.",
          showConfirmButton: false,
          timer: 2000,
          background: '#1e293b',
          color: '#ffffff'
        })
      } catch (grnError) {
        console.error('Error generating GRN:', grnError)
        Swal.fire({
          icon: "warning",
          title: "Stock Updated",
          text: `Stock was updated but there was an error generating the GRN report: ${grnError.response?.data?.message || grnError.message}`,
          background: '#1e293b',
          color: '#ffffff'
        })
      }
    }

    closeStockModal()
  } catch (error) {
    console.error('Error updating stock:', error)
    Swal.fire({
      icon: "error",
      title: "Error!",
      text: error.response?.data?.message || "Failed to update stock",
      background: '#1e293b',
      color: '#ffffff'
    })
  } finally {
    isUpdatingStock.value = false
  }
}

const addStockHistory = (itemId, change, reason, notes, previousQuantity = null, newQuantity = null) => {
  const item = inventory.value.find(i => i.id === itemId)
  if (!item) return
  
  const today = new Date()
  const formattedDate = `${today.getFullYear()}/${String(today.getMonth() + 1).padStart(2, '0')}/${String(today.getDate()).padStart(2, '0')}`
  const formattedTime = today.toLocaleTimeString()
  
  stockHistory.value.unshift({
    id: Date.now(),
    itemId,
    itemName: item.name,
    change,
    reason,
    notes,
    previousQuantity,
    newQuantity,
    date: formattedDate,
    time: formattedTime
  })
}

const openDeleteConfirm = (item) => {
  selectedItem.value = item
  Swal.fire({
    title: "Are you sure?",
    text: "Do you want to delete this inventory item?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "Yes, delete it!",
    background: '#1e293b',
    color: '#ffffff'
  }).then((result) => {
    if (result.isConfirmed) {
      confirmDelete()
    }
  })
}

const closeDeleteConfirm = () => {
  showDeleteConfirm.value = false
  selectedItem.value = null
}

const confirmDelete = async () => {
  if (!selectedItem.value) return

  try {
    // Call backend API to delete the item
    await connection.delete(`/inventory/${selectedItem.value.id}`)
    
    // Remove item from local state
    const index = inventory.value.findIndex(i => i.id === selectedItem.value.id)
    if (index !== -1) {
      inventory.value.splice(index, 1)
    }
    
    await Swal.fire({
      position: "center",
      icon: "success",
      title: "Item Deleted Successfully!",
      showConfirmButton: false,
      timer: 1500,
      background: '#1e293b',
      color: '#ffffff'
    })
  } catch (error) {
    console.error('Error deleting inventory item:', error)
    Swal.fire({
      icon: "error",
      title: "Error!",
      text: "Failed to delete item",
      background: '#1e293b',
      color: '#ffffff'
    })
  }
}

const isExporting = ref(false)
const currentDateTime = ref(new Date().toLocaleString())

const getWorksheetStyle = (color) => ({
  font: { bold: true, color: { rgb: color } },
  fill: { fgColor: { rgb: color === 'FFFFFF' ? '2B5BA1' : 'F0F4F8' } },
  alignment: { horizontal: 'center', vertical: 'center' },
  border: {
    top: { style: 'thin', color: { rgb: 'E2E8F0' } },
    bottom: { style: 'thin', color: { rgb: 'E2E8F0' } },
    left: { style: 'thin', color: { rgb: 'E2E8F0' } },
    right: { style: 'thin', color: { rgb: 'E2E8F0' } }
  }
})

const exportInventory = async () => {
  try {
    isExporting.value = true
    const response = await connection.get('/inventory/export-data')
    const inventoryData = response.data.data
    const summary = response.data.summary

    const workbook = XLSX.utils.book_new()

    // Create summary worksheet with total profit
    const summaryWs = XLSX.utils.aoa_to_sheet([
      ['CLOTHING POS SYSTEM - INVENTORY SUMMARY REPORT'],
      [`Report Generated: ${new Date().toLocaleString()}`],
      [''],
      ['EXECUTIVE SUMMARY'],
      ['Total Items:', totalItems.value],
      ['Total Value:', `Rs. ${summary.total_value.toLocaleString()}`],
      ['Total Profit:', `Rs. ${summary.total_profit.toLocaleString()}`],
      ['Low Stock Items:', lowStockItems.value.length],
      ['Categories:', categories.value.length - 1],
      ['']
    ])

    // Define styles
    const headerStyle = {
      fill: {
        fgColor: { rgb: '1F2937' }
      },
      font: {
        bold: true,
        color: { rgb: 'FFFFFF' },
        sz: 12,
        name: 'Arial'
      },
      alignment: {
        horizontal: 'center',
        vertical: 'center',
        wrapText: true
      },
      border: {
        top: { style: 'medium', color: { rgb: '374151' } },
        bottom: { style: 'medium', color: { rgb: '374151' } },
        left: { style: 'medium', color: { rgb: '374151' } },
        right: { style: 'medium', color: { rgb: '374151' } }
      }
    }

    const subHeaderStyle = {
      fill: {
        fgColor: { rgb: 'F3F4F6' }
      },
      font: {
        bold: true,
        color: { rgb: '1F2937' },
        sz: 11,
        name: 'Arial'
      },
      alignment: {
        horizontal: 'center',
        vertical: 'center'
      },
      border: {
        bottom: { style: 'medium', color: { rgb: 'E5E7EB' } }
      }
    }

    const dataStyle = {
      font: {
        color: { rgb: '1F2937' },
        sz: 10,
        name: 'Arial'
      },
      alignment: {
        horizontal: 'center',
        vertical: 'center',
        wrapText: true
      },
      border: {
        top: { style: 'thin', color: { rgb: 'E5E7EB' } },
        bottom: { style: 'thin', color: { rgb: 'E5E7EB' } },
        left: { style: 'thin', color: { rgb: 'E5E7EB' } },
        right: { style: 'thin', color: { rgb: 'E5E7EB' } }
      }
    }

    const currencyStyle = {
      ...dataStyle,
      numFmt: '"Rs. "#,##0.00'
    }

    const percentStyle = {
      ...dataStyle,
      numFmt: '0.00"%"'
    }

    // Create summary worksheet with header data
    const summaryData = [
      ['CLOTHING POS SYSTEM - INVENTORY SUMMARY REPORT'],
      [`Report Generated: ${new Date().toLocaleString()}`],
      [''],
      ['EXECUTIVE SUMMARY'],
      ['Total Items:', totalItems.value],
      ['Total Value:', `Rs. ${inventoryValue.value.toLocaleString()}`],
      ['Low Stock Items:', lowStockItems.value.length],
      ['Categories:', categories.value.length - 1],
      ['']
    ]

    // Create inventory details worksheet
    const inventoryHeaders = [
      ['INVENTORY DETAILS REPORT'],
      [''],
      [
        'Inventory ID',
        'Product ID',
        'Name',
        'Brand Name',
        'Description',
        'Size',
        'Color',
        'Quantity',
        'Price (Rs.)',
        'Seller Price (Rs.)',
        'Discount (%)',
        'Profit (Rs.)',
        'Total Value (Rs.)',
        'Location',
        'Status',
        'Supplier ID',
        'Admin ID',
        'Added Stock',
        'Last Restocked',
        'Created Date',
        'Updated Date'
      ]
    ]

    const inventoryRows = inventoryData.map(item => [
      item.id,
      item.product?.id || 'N/A',
      item.product?.name || 'N/A',
      item.product?.brand_name || 'N/A',
      item.product?.description || 'N/A',
      item.product?.size || 'N/A',
      item.product?.color || 'N/A',
      item.quantity,
      item.product?.price || 0,
      item.product?.seller_price || 0,
      item.product?.discount || 0,
      item.product?.profit || 0,
      (item.product?.price || 0) * item.quantity,
      item.location,
      item.status,
      item.product?.supplier_id || 'N/A',
      item.product?.admin_id || 'N/A',
      item.added_stock_amount || 0,
      new Date(item.restock_date_time).toLocaleString(),
      item.created_at ? new Date(item.created_at).toLocaleString() : 'N/A',
      item.updated_at ? new Date(item.updated_at).toLocaleString() : 'N/A'
    ])

    const inventoryWs = XLSX.utils.aoa_to_sheet([
      ...inventoryHeaders,
      ...inventoryRows
    ])

    // Apply styles to both worksheets
    ;[summaryWs, inventoryWs].forEach(sheet => {
      const range = XLSX.utils.decode_range(sheet['!ref'])
      
      for (let R = 0; R <= range.e.r; R++) {
        for (let C = 0; C <= range.e.c; C++) {
          const cell = XLSX.utils.encode_cell({ r: R, c: C })
          if (!sheet[cell]) continue

          if (R === 0) {
            sheet[cell].s = headerStyle
          } else if (R === 2) {
            sheet[cell].s = subHeaderStyle
          } else {
            // Apply specific styles based on column type
            if (C >= 9 && C <= 14) { // Price columns
              sheet[cell].s = currencyStyle
            } else if (C === 11 || C === 12) { // Discount and Tax columns
              sheet[cell].s = percentStyle
            } else {
              sheet[cell].s = dataStyle
            }
          }
        }
      }
    })

    // Set column widths
    const setColWidths = (sheet, widths) => {
      sheet['!cols'] = widths.map(w => ({ wch: w, wpx: w * 7 }))
    }

    setColWidths(summaryWs, Array(5).fill(25))
    setColWidths(inventoryWs, [
      12,  // Inventory ID
      12,  // Product ID
      35,  // Name
      25,  // Brand Name
      45,  // Description
      15,  // Size
      15,  // Color
      12,  // Quantity
      15,  // Price
      15,  // Seller Price
      12,  // Discount
      15,  // Profit
      18,  // Total Value
      15,  // Location
      15,  // Status
      12,  // Supplier ID
      12,  // Admin ID
      15,  // Added Stock
      25,  // Restock Date
      25,  // Created At
       25   // Updated At
    ])

    // Set print settings
    inventoryWs['!printHeader'] = true
    inventoryWs['!printFooter'] = true

    // Add sheets to workbook
    XLSX.utils.book_append_sheet(workbook, summaryWs, 'Summary')
    XLSX.utils.book_append_sheet(workbook, inventoryWs, 'Inventory Details')

    // Generate Excel file with styles
    const excelBuffer = XLSX.write(workbook, {
      bookType: 'xlsx',
      type: 'array',
      cellStyles: true
    })

    const blob = new Blob([excelBuffer], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    })

    const fileName = `Clothing_POS_Inventory_Report_${new Date().toISOString().split('T')[0]}.xlsx`
    const link = document.createElement('a')
    link.href = window.URL.createObjectURL(blob)
    link.download = fileName
    link.click()

    showNotification('Professional inventory report generated successfully', 'success')
  } catch (error) {
    console.error('Error generating report:', error)
    showNotification('Failed to generate inventory report', 'error')
  } finally {
    isExporting.value = false
    showExportOptions.value = false
  }
}

const showNotification = (message, type = 'success') => {
  Swal.fire({
    position: "center",
    icon: type,
    title: message,
    showConfirmButton: false,
    timer: 1500,
    background: '#1e293b',
    color: '#ffffff'
  })
}

const changePage = (page) => {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
}

const dashboardStats = computed(() => [
  {
    title: 'Total Items',
    value: totalItems.value,
    icon: ArchiveBoxIcon,
    color: 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20'
  },
  {
    title: 'Total Value',
    value: `Rs. ${inventoryValue.value.toLocaleString()}`,
    icon: ArrowTrendingUpIcon,
    color: 'bg-violet-500/10 text-violet-500 border-violet-500/20'
  },
  {
    title: 'Total Profit',
    value: `Rs. ${totalProfit.value.toLocaleString()}`,
    icon: ArrowTrendingUpIcon,
    color: 'bg-green-500/10 text-green-500 border-green-500/20'
  },
  {
    title: 'Low Stock Items',
    value: lowStockItems.value.length,
    icon: ExclamationTriangleIcon,
    color: 'bg-amber-500/10 text-amber-500 border-amber-500/20'
  }
])

const viewItemDetails = (item) => {
  selectedItem.value = item
  showViewModal.value = true
}

const showExportOptions = ref(false)

const exportToPDF = async () => {
  try {
    isExporting.value = true
    const response = await connection.get('/inventory')
    const inventoryData = response.data

    let totalValue = 0
    inventoryData.forEach(item => {
      // Calculate total value using product price
      totalValue += (item.price || 0) * item.quantity
    })

    // Create the PDF content element
    const element = document.createElement('div')
    element.innerHTML = `
      <div style="padding: 20px; font-family: Arial, sans-serif;">
        <h1 style="text-align: center; color: #1f2937;">CLOTHING POS SYSTEM</h1>
        <h2 style="text-align: center; color: #374151;">Inventory Management Report</h2>
        <p style="text-align: center; color: #4b5563;">Report Generated: ${new Date().toLocaleString()}</p>
        
        <div style="margin-top: 30px;">
          <h3 style="color: #1f2937;">EXECUTIVE SUMMARY</h3>
          <table style="width: 100%; margin-top: 10px; border-collapse: collapse;">
            <tr>
              <th style="text-align: left; padding: 8px; border: 1px solid #d1d5db;">Metric</th>
              <th style="text-align: right; padding: 8px; border: 1px solid #d1d5db;">Value</th>
            </tr>
            <tr>
              <td style="padding: 8px; border: 1px solid #d1d5db;">Total Inventory Items</td>
              <td style="text-align: right; padding: 8px; border: 1px solid #d1d5db;">${totalItems.value}</td>
            </tr>
            <tr>
              <td style="padding: 8px; border: 1px solid #d1d5db;">Total Inventory Value</td>
              <td style="text-align: right; padding: 8px; border: 1px solid #d1d5db;">Rs. ${totalValue.toLocaleString()}</td>
            </tr>
            <tr>
              <td style="padding: 8px; border: 1px solid #d1d5db;">Low Stock Items</td>
              <td style="text-align: right; padding: 8px; border: 1px solid #d1d5db;">${lowStockItems.value.length}</td>
            </tr>
          </table>
        </div>

        <div style="margin-top: 30px;">
          <h3 style="color: #1f2937;">DETAILED INVENTORY LIST</h3>
          <table style="width: 100%; margin-top: 10px; border-collapse: collapse;">
            <tr>
              <th style="text-align: left; padding: 8px; border: 1px solid #d1d5db;">ID</th>
              <th style="text-align: left; padding: 8px; border: 1px solid #d1d5db;">Name</th>
              <th style="text-align: left; padding: 8px; border: 1px solid #d1d5db;">Location</th>
              <th style="text-align: right; padding: 8px; border: 1px solid #d1d5db;">Quantity</th>
              <th style="text-align: right; padding: 8px; border: 1px solid #d1d5db;">Unit Price</th>
              <th style="text-align: left; padding: 8px; border: 1px solid #d1d5db;">Status</th>
              <th style="text-align: right; padding: 8px; border: 1px solid #d1d5db;">Total Value</th>
            </tr>
            ${inventoryData.map(item => `
              <tr>
                <td style="padding: 8px; border: 1px solid #d1d5db;">${item.id}</td>
                <td style="padding: 8px; border: 1px solid #d1d5db;">${item.name || 'N/A'}</td>
                <td style="padding: 8px; border: 1px solid #d1d5db;">${item.location}</td>
                <td style="text-align: right; padding: 8px; border: 1px solid #d1d5db;">${item.quantity}</td>
                <td style="text-align: right; padding: 8px; border: 1px solid #d1d5db;">Rs. ${(item.price || 0).toLocaleString()}</td>
                <td style="padding: 8px; border: 1px solid #d1d5db;">${item.status}</td>
                <td style="text-align: right; padding: 8px; border: 1px solid #d1d5db;">Rs. ${((item.price || 0) * item.quantity).toLocaleString()}</td>
              </tr>
            `).join('')}
            <tr>
              <td colspan="6" style="text-align: right; padding: 8px; border: 1px solid #d1d5db; font-weight: bold;">Total:</td>
              <td style="text-align: right; padding: 8px; border: 1px solid #d1d5db; font-weight: bold;">Rs. ${totalValue.toLocaleString()}</td>
            </tr>
          </table>
        </div>

        <div style="margin-top: 30px; text-align: center; color: #6b7280; font-size: 12px;">
          <p>Generated by Clothing POS System</p>
          <p>${new Date().toLocaleString()}</p>
        </div>
      </div>
    `

    const opt = {
      margin: 10,
      filename: `inventory_report_${new Date().toISOString().split('T')[0]}.pdf`,
      image: { type: 'jpeg', quality: 0.98 },
      html2canvas: { scale: 2 },
      jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    }

    await html2pdf().from(element).set(opt).save()
    showNotification('PDF report generated successfully', 'success')
  } catch (error) {
    console.error('Error generating PDF:', error)
    showNotification('Failed to generate PDF report', 'error')
  } finally {
    isExporting.value = false
    showExportOptions.value = false
  }
}
</script>

<template>
  <div class="bg-gradient-to-br from-gray-900 min-h-screen to-gray-900 via-gray-800">
    <div class="h-full w-2 fixed hover-trigger left-0 top-0 z-[55]"
         @mouseenter="showSidebar"></div>
    
    <Sidebar :isVisible="isSidebarVisible" @closeSidebar="closeSidebar" />
    <Header @toggleSidebar="toggleSidebar" />

    <div class="p-4 w-full lg:p-8 lg:pt-28 pt-28">
      <div class="flex flex-col justify-between gap-4 items-start lg:flex-row lg:items-center mb-6">
        <div>
          <h1 class="text-2xl text-white font-bold lg:text-3xl mb-2">
            Inventory Management
          </h1>
          <p class="text-gray-400 text-sm">Manage your clothing store inventory efficiently</p>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-4 lg:grid-cols-4 mb-6 sm:grid-cols-2">
        <div v-for="(stat, index) in dashboardStats" :key="index" 
             class="flex bg-gray-800/80 border border-gray-700/50 p-4 rounded-xl shadow-lg gap-4 items-center">
          <div :class="stat.color" class="p-3 rounded-lg">
            <component :is="stat.icon" class="h-6 w-6" />
          </div>
          <div>
            <h3 class="text-gray-400 text-sm font-medium">{{ stat.title }}</h3>
            <p class="text-white text-xl font-bold">{{ stat.value }}</p>
          </div>
        </div>
      </div>

      <div class="flex flex-col gap-6 lg:flex-row">
        <div class="bg-gray-800/80 border border-gray-700/50 p-6 rounded-xl shadow-xl w-full lg:w-2/3 overflow-hidden">
          <div class="flex flex-col justify-between gap-4 items-start mb-6 md:flex-row md:items-center">
            <h2 class="text-white text-xl font-bold">Inventory Items</h2>
            
            <div class="flex flex-col w-full gap-3 md:flex-row md:w-auto">
              <div class="relative">
                <MagnifyingGlassIcon class="h-4 text-gray-400 w-4 -translate-y-1/2 absolute left-3 top-1/2 transform" />
                <input 
                  v-model="searchQuery"
                  type="text" 
                  placeholder="Search by ID or name..." 
                  class="bg-gray-700/70 border border-gray-600/50 rounded-lg text-gray-200 text-sm w-full focus:outline-none focus:ring-2 focus:ring-emerald-500/50 md:w-64 pl-10 pr-4 py-2"
                />
              </div>
              
              <button 
                @click="showExportOptions = true" 
                class="flex bg-gray-700/70 rounded-lg text-gray-200 text-sm duration-200 gap-2 hover:bg-gray-600/70 items-center px-4 py-2 transition-colors"
                :disabled="isExporting"
              >
                <template v-if="isExporting">
                  <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <span>Generating Report...</span>
                </template>
                <template v-else>
                  <ArrowDownTrayIcon class="h-4 w-4" />
                  <span>Export Report</span>
                </template>
              </button>
            </div>
          </div>
          
          <div class="overflow-x-auto">
            <div v-if="isLoading" class="flex justify-center items-center py-20">
              <div class="border-b-2 border-emerald-500 h-12 rounded-full w-12 animate-spin"></div>
              <span class="text-gray-400 ml-3">Loading inventory...</span>
            </div>
            
            <table v-else class="border-separate border-spacing-0 text-gray-200 w-full">
              <thead>
                <tr class="text-gray-300 text-left text-sm">
                  <th class="text-left font-medium pb-4">ID</th>
                  <th @click="toggleSort('name')" class="text-left cursor-pointer font-medium pb-4">
                    <div class="flex gap-1 items-center">
                      Name
                      <component :is="getSortIcon('name')" v-if="getSortIcon('name')" class="h-4 w-4" />
                    </div>
                  </th>
                  <th @click="toggleSort('quantity')" class="text-left cursor-pointer font-medium pb-4">
                    <div class="flex gap-1 items-center">
                      Quantity
                      <component :is="getSortIcon('quantity')" v-if="getSortIcon('quantity')" class="h-4 w-4" />
                    </div>
                  </th>
                  <th @click="toggleSort('added_stock_amount')" class="text-left cursor-pointer font-medium pb-4">
                    <div class="flex gap-1 items-center">
                      Added Stock
                      <component :is="getSortIcon('added_stock_amount')" v-if="getSortIcon('added_stock_amount')" class="h-4 w-4" />
                    </div>
                  </th>
                  <th @click="toggleSort('location')" class="text-left cursor-pointer font-medium pb-4">
                    <div class="flex gap-1 items-center">
                      Location
                      <component :is="getSortIcon('location')" v-if="getSortIcon('location')" class="h-4 w-4" />
                    </div>
                  </th>
                  <th @click="toggleSort('status')" class="text-left cursor-pointer font-medium pb-4">
                    <div class="flex gap-1 items-center">
                      Status
                      <component :is="getSortIcon('status')" v-if="getSortIcon('status')" class="h-4 w-4" />
                    </div>
                  </th>
                  <th @click="toggleSort('lastUpdated')" class="text-left cursor-pointer font-medium pb-4">
                    <div class="flex gap-1 items-center">
                      Last Updated
                      <component :is="getSortIcon('lastUpdated')" v-if="getSortIcon('lastUpdated')" class="h-4 w-4" />
                    </div>
                  </th>
                  <th class="text-left font-medium pb-4">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in paginatedInventory" :key="item.id" 
                    class="border-gray-700/50 border-t duration-200 hover:bg-gray-700/30 transition-colors"
                    :class="{'bg-red-900/20': item.status === 'Low Stock' || item.status === 'Out Of Stock'}">
                  <td class="font-medium py-4">{{ item.id }}</td>
                  <td class="font-medium">{{ item.name }}</td>
                  <td>
                    <span :class="{'text-red-400': item.status === 'Low Stock' || item.status === 'Out Of Stock', 'font-bold': true}">
                      {{ item.quantity }}
                    </span>
                  </td>
                  <td>
                    <span class="text-emerald-400 font-medium">
                      {{ item.added_stock_amount || 0 }}
                    </span>
                  </td>
                  <td class="text-gray-300">
                    <span class="bg-gray-700/50 rounded-full text-xs px-2 py-1">{{ item.location }}</span>
                  </td>
                  <td>
                    <span :class="{
                      'px-2 py-1 rounded-full text-xs': true,
                      'bg-emerald-500/20 text-emerald-400': item.status === 'In Stock',
                      'bg-yellow-500/20 text-yellow-400': item.status === 'Low Stock',
                      'bg-red-500/20 text-red-400': item.status === 'Out Of Stock'
                    }">
                      {{ item.status }}
                    </span>
                  </td>
                  <td class="text-gray-300 text-sm">
                    {{ item.restock_date_time ? new Date(item.restock_date_time).toLocaleString() : 'Not updated' }}
                  </td>
                  <td>
                    <div class="flex gap-2">
                      <button @click="viewItemDetails(item)" 
                              class="bg-blue-500/20 p-1.5 rounded text-blue-400 duration-200 hover:bg-blue-500/30 transition-colors" 
                              title="View Details">
                        <EyeIcon class="h-4 w-4" />
                      </button>
                      <button @click="openStockModal(item)" class="bg-emerald-500/20 p-1.5 rounded text-emerald-400 duration-200 hover:bg-emerald-500/30 transition-colors" title="Adjust Stock">
                        <ArrowPathIcon class="h-4 w-4" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <div class="flex flex-col justify-between gap-4 items-center mt-6 sm:flex-row">
            <div class="text-gray-400 text-sm">
              Showing {{ Math.min(filteredInventory.length, 1 + (currentPage - 1) * itemsPerPage) }}-{{ Math.min(filteredInventory.length, currentPage * itemsPerPage) }} of {{ filteredInventory.length }} items
            </div>
            
            <div class="flex gap-2">
              <button 
                @click="changePage(currentPage - 1)" 
                :disabled="currentPage === 1"
                class="bg-gray-700 rounded text-gray-200 disabled:bg-gray-800 disabled:text-gray-600 duration-200 hover:bg-gray-600 px-3 py-1 transition-colors"
              >
                Previous
              </button>
              
              <button 
                v-for="page in totalPages" 
                :key="page" 
                @click="changePage(page)"
                :class="[
                  'px-3 py-1 rounded transition-colors duration-200',
                  page === currentPage ? 'bg-emerald-600 text-white' : 'bg-gray-700 hover:bg-gray-600 text-gray-200'
                ]"
              >
                {{ page }}
              </button>
              
              <button 
                @click="changePage(currentPage + 1)" 
                :disabled="currentPage === totalPages"
                class="bg-gray-700 rounded text-gray-200 disabled:bg-gray-800 disabled:text-gray-600 duration-200 hover:bg-gray-600 px-3 py-1 transition-colors"
              >
                Next
              </button>
            </div>
          </div>
          
          <div v-if="filteredInventory.length === 0" class="text-center text-gray-400 py-8">
            <ArchiveBoxIcon class="h-12 w-12 mb-4 mx-auto opacity-50" />
            <p>No inventory items found matching your criteria</p>
            <button @click="searchQuery = ''; selectedCategory = 'All'" class="flex text-emerald-400 gap-2 hover:text-emerald-300 items-center mt-4 mx-auto">
              <ArrowPathIcon class="h-4 w-4" />
              <span>Reset filters</span>
            </button>
          </div>
        </div>

        <div class="flex flex-col w-full gap-6 lg:w-1/3">
          <div class="bg-gray-800/80 border border-gray-700/50 p-6 rounded-xl shadow-xl overflow-hidden">
            <div class="mb-4">
              <h2 class="text-white text-xl font-bold">Inventory by Category</h2>
              <p class="text-gray-400 text-sm">Distribution of items across categories</p>
            </div>
            <div class="h-[300px]">
              <Bar 
                :data="inventoryChartData"
                :options="chartOptions"
              />
            </div>
          </div>
          
          <div class="bg-gray-800/80 border border-gray-700/50 p-6 rounded-xl shadow-xl overflow-hidden">
            <div class="flex justify-between items-center mb-4">
              <div>
                <h2 class="text-white text-xl font-bold">Low Stock Items</h2>
                <p class="text-gray-400 text-sm">Items below threshold level</p>
              </div>
              <span class="bg-amber-500/20 rounded-full text-amber-400 text-sm px-2 py-1">
                {{ lowStockItems.length }} items
              </span>
            </div>
            
            <div v-if="lowStockItems.length > 0" class="custom-scrollbar max-h-[240px] overflow-y-auto pr-1 space-y-3">
              <div v-for="(item, index) in lowStockItems" :key="item.id" 
                   class="flex bg-red-500/10 border border-red-500/20 justify-between p-3 rounded-lg items-center"
                   :class="{'animate-fade-in': index < 3}">
                <div>
                  <div class="text-white font-bold">{{ item.name }}</div>
                  <div class="text-gray-400 text-sm">{{ item.category }}</div>
                </div>
                <div class="text-right">
                  <div class="text-red-400 font-bold">{{ item.quantity }} / {{ item.threshold }}</div>
                  <div class="text-gray-400 text-xs">In stock / Threshold</div>
                </div>
              </div>
            </div>
            
            <div v-else class="text-center text-gray-400 py-6">
              <p>No low stock items! 🎉</p>
            </div>
          </div>
          
          <!-- Add this new Profit Summary card -->
          <div class="bg-gray-800/80 border border-gray-700/50 p-6 rounded-xl shadow-xl overflow-hidden">
            <div class="flex justify-between items-center mb-4">
              <div>
                <h2 class="text-white text-xl font-bold">Total Profit</h2>
                <p class="text-gray-400 text-sm">Overall profit calculation</p>
              </div>
              <span class="bg-green-500/20 rounded-full text-green-400 text-sm px-3 py-1.5">
                Rs. {{ totalProfit.toLocaleString() }}
              </span>
            </div>
            
            <div class="bg-gradient-to-br from-green-500/10 to-green-600/10 border border-green-500/20 p-4 rounded-xl">
              <div class="flex justify-between items-center">
                <div class="flex gap-3 items-center">
                  <div class="bg-green-500/20 p-2 rounded-lg">
                    <ArrowTrendingUpIcon class="h-5 text-green-400 w-5" />
                  </div>
                  <div>
                    <span class="text-gray-400 text-sm">Total Value</span>
                    <p class="text-white font-bold mt-0.5">Rs. {{ inventoryValue.toLocaleString() }}</p>
                  </div>
                </div>
                <div class="text-right">
                  <span class="text-gray-400 text-sm">Profit Margin</span>
                  <p class="text-green-400 font-bold mt-0.5">
                    {{ ((totalProfit / inventoryValue) * 100).toFixed(1) }}%
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Recent Stock Activity section remains unchanged -->
          <div class="bg-gray-800/80 border border-gray-700/50 p-6 rounded-xl shadow-xl overflow-hidden">
            <div class="mb-4">
              <h2 class="text-white text-xl font-bold">Recent Stock Activity</h2>
              <p class="text-gray-400 text-sm">Latest inventory changes</p>
            </div>
            
            <div v-if="stockHistory.length > 0" class="max-h-[300px] overflow-y-auto pr-2 space-y-3">
              <div v-for="activity in stockHistory.slice(0, 5)" :key="activity.id" 
                   class="bg-gray-700/50 border border-gray-600/50 p-3 rounded-lg">
                <div class="flex justify-between items-start">
                  <div>
                    <div class="text-white font-bold">{{ activity.itemName }}</div>
                    <div class="text-gray-400 text-sm">
                      {{ activity.reason }}
                    </div>
                  </div>
                  <div :class="[
                    'px-2 py-1 rounded-full text-xs font-medium',
                    activity.change > 0 ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400'
                  ]">
                    {{ activity.change > 0 ? '+' : '' }}{{ activity.change }}
                  </div>
                </div>
                <div class="text-gray-400 text-xs mt-2">
                  {{ activity.date }} at {{ activity.time }}
                </div>
              </div>
            </div>
            
            <div v-else class="text-center text-gray-400 py-6">
              <p>No recent stock activity</p>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div v-if="showStockModal" 
         class="flex bg-black/80 justify-center p-4 backdrop-blur-sm fixed inset-0 items-center z-50">
      <div class="bg-gradient-to-br border border-gray-700/50 p-6 rounded-2xl shadow-2xl w-full animate-fade-in from-gray-900/95 max-w-lg to-gray-800/95">
        <!-- Header Section with Icon -->
        <div class="flex justify-between items-start mb-8">
          <div class="flex gap-4 items-center">
            <div :class="[
              'p-3 rounded-xl shadow-lg border border-opacity-50 transition-colors',
              stockAdjustment.quantity >= 0 
                ? 'bg-gradient-to-br from-emerald-600/10 to-emerald-700/10 border-emerald-500/20' 
                : 'bg-gradient-to-br from-red-600/10 to-red-700/10 border-red-500/20'
            ]">
              <ArrowPathIcon class="h-6 w-6" :class="stockAdjustment.quantity >= 0 ? 'text-emerald-400' : 'text-red-400'" />
            </div>
            <div>
              <h2 class="text-2xl text-white font-bold mb-1">Stock Adjustment</h2>
              <p class="text-gray-400 text-sm">Update inventory quantities</p>
            </div>
          </div>
          <button @click="closeStockModal" 
                  class="p-2 rounded-lg duration-200 hover:bg-gray-700/50 transition-colors">
            <XMarkIcon class="h-5 text-gray-400 w-5" />
          </button>
        </div>

        <div v-if="selectedItem" class="space-y-6">
          <!-- Current Stock Info Card -->
          <div class="bg-gradient-to-br border border-gray-700/50 p-5 rounded-xl from-gray-800 to-gray-800/50">
            <div class="flex justify-between items-center mb-4">
              <div class="flex gap-2 items-center">
                <ArchiveBoxIcon class="h-5 text-gray-400 w-5" />
                <span class="text-gray-400">Item #{{ selectedItem.id }}</span>
              </div>
              <span :class="{
                'px-3 py-1 rounded-full text-xs font-medium': true,
                'bg-emerald-500/20 text-emerald-400': selectedItem.status === 'In Stock',
                'bg-yellow-500/20 text-yellow-400': selectedItem.status === 'Low Stock',
                'bg-red-500/20 text-red-400': selectedItem.status === 'Out Of Stock'
              }">{{ selectedItem.status }}</span>
            </div>
            <div class="grid grid-cols-2 gap-6">
              <div>
                <span class="text-gray-400 text-sm">Current Stock</span>
                <div class="text-3xl text-white font-bold mt-1">{{ selectedItem.quantity }}</div>
              </div>
              <div class="text-right">
                <span class="text-gray-400 text-sm">Location</span>
                <div class="text-lg text-white font-medium mt-1">{{ selectedItem.location }}</div>
              </div>
            </div>
          </div>

          <!-- Adjustment Controls -->
          <div class="space-y-5">
            <!-- Quantity Adjustment -->
            <div>
              <label class="flex justify-between items-center mb-2">
                <span class="text-gray-300 font-medium">Adjustment Amount</span>
                <span :class="{
                  'text-sm font-medium': true,
                  'text-emerald-400': stockAdjustment.quantity > 0,
                  'text-red-400': stockAdjustment.quantity < 0,
                  'text-gray-400': stockAdjustment.quantity === 0
                }">New Total: {{ Math.max(0, selectedItem.quantity + stockAdjustment.quantity) }}</span>
              </label>
              <div class="flex gap-3 items-center">
                <button @click="stockAdjustment.quantity = Math.max(-selectedItem.quantity, stockAdjustment.quantity - 1)" 
                        class="bg-gradient-to-br border border-red-500/20 p-2.5 rounded-lg duration-200 from-red-500/10 group hover:from-red-500/20 hover:to-red-600/20 to-red-600/10 transition-all">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 text-red-400 w-5 group-hover:text-red-300 transition-colors" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                  </svg>
                </button>
                
                <input 
                  id="stock-quantity"
                  v-model.number="stockAdjustment.quantity"
                  type="number"
                  :max="999999"
                  :min="-selectedItem.quantity"
                  class="flex-1 bg-gray-800 border border-gray-700 rounded-lg text-2xl text-center text-white duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500/50 font-bold px-4 py-2.5 transition-all"
                />
                
                <button @click="stockAdjustment.quantity++" 
                        class="bg-gradient-to-br border border-emerald-500/20 p-2.5 rounded-lg duration-200 from-emerald-500/10 group hover:from-emerald-500/20 hover:to-emerald-600/20 to-emerald-600/10 transition-all">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 text-emerald-400 w-5 group-hover:text-emerald-300 transition-colors" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                  </svg>
                </button>
              </div>
            </div>

          </div>

          <!-- Action Buttons -->
          <div class="flex border-gray-700/50 border-t justify-end gap-3 items-center mt-8 pt-6">
            <button @click="closeStockModal" 
                    class="rounded-lg text-gray-400 duration-200 hover:bg-gray-700/50 hover:text-gray-300 px-4 py-2 transition-colors">
              Cancel
            </button>
            <button 
              @click="confirmStockAdjustment" 
              :disabled="stockAdjustment.quantity === 0 || isUpdatingStock"
              :class="[
                'px-6 py-2.5 rounded-lg transition-all duration-200 flex items-center gap-2 font-medium disabled:opacity-50 disabled:cursor-not-allowed',
                stockAdjustment.quantity > 0 
                  ? 'bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-500 hover:to-emerald-400 text-white shadow-lg shadow-emerald-500/20' 
                  : 'bg-gradient-to-r from-red-600 to-red-500 hover:from-red-500 hover:to-red-400 text-white shadow-lg shadow-red-500/20'
              ]"
            >
              <template v-if="isUpdatingStock">
                <svg class="h-5 w-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>{{ stockAdjustment.quantity > 0 ? 'Adding...' : 'Removing...' }}</span>
              </template>
              <template v-else>
                {{ stockAdjustment.quantity > 0 ? 'Add Stock' : 'Remove Stock' }}
              </template>
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <div v-if="showDeleteConfirm" class="flex bg-black/70 justify-center p-4 backdrop-blur-sm fixed inset-0 items-center z-50">
      <div class="bg-gray-800 border border-gray-700 p-6 rounded-xl shadow-2xl w-full animate-fade-in max-w-md">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-white text-xl font-bold">Confirm Deletion</h2>
          <button @click="closeDeleteConfirm" class="p-1 rounded-full text-gray-400 hover:bg-gray-700">
            <XMarkIcon class="h-5 w-5" />
          </button>
        </div>
        
        <div v-if="selectedItem" class="text-gray-300 mb-6">
          <p>Are you sure you want to delete <span class="font-bold">{{ selectedItem.name }}</span>?</p>
          <p class="mt-2">This action cannot be undone.</p>
        </div>
        
        <div class="flex justify-end gap-3 mt-6">
          <button @click="closeDeleteConfirm" class="rounded-lg text-gray-300 duration-200 hover:bg-gray-700 px-4 py-2 transition-colors">
            Cancel
          </button>
          <button @click="confirmDelete" class="bg-red-600 rounded-lg text-white duration-200 hover:bg-red-700 px-4 py-2 transition-all">
            Delete Item
          </button>
        </div>
      </div>
    </div>
    
    <div v-if="showAddModal" class="flex bg-black/80 justify-center p-4 backdrop-blur-sm fixed inset-0 items-center z-50">
      <div class="bg-gradient-to-br border border-gray-700/50 p-6 rounded-2xl shadow-2xl w-full animate-fade-in from-gray-900/95 max-w-lg to-gray-800/95">
        <!-- Header -->
        <div class="flex justify-between items-start mb-8">
          <div class="flex gap-4 items-center">
            <div class="bg-gradient-to-br border border-emerald-500/20 p-3 rounded-xl shadow-lg from-emerald-600/10 to-emerald-700/10">
              <PlusCircleIcon class="h-6 text-emerald-400 w-6" />
            </div>
            <div>
              <h2 class="text-2xl text-white font-bold mb-1">Add New Inventory</h2>
              <p class="text-gray-400 text-sm">Enter item details below</p>
            </div>
          </div>
          <button @click="showAddModal = false" 
                  class="p-2 rounded-lg duration-200 hover:bg-gray-700/50 transition-colors">
            <XMarkIcon class="h-5 text-gray-400 w-5" />
          </button>
        </div>

        <!-- Form Content -->
        <div class="space-y-6">
          <!-- Quantity and Location Group -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="text-gray-300 text-sm block font-medium mb-2">
                Quantity*
                <span class="text-emerald-500 ml-1">*</span>
              </label>
              <div class="relative">
                <input 
                  v-model.number="newItem.quantity"
                  type="number"
                  min="0"
                  class="bg-gray-800/50 border border-gray-700 rounded-lg text-gray-200 w-full duration-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 px-4 py-2.5 transition-all"
                  required
                />
                <div class="flex absolute inset-y-0 items-center pr-3 right-0">
                  <ArchiveBoxIcon class="h-5 text-gray-500 w-5" />
                </div>
              </div>
            </div>

            <div>
              <label class="text-gray-300 text-sm block font-medium mb-2">
                Location
                <span class="text-emerald-500 ml-1">*</span>
              </label>
              <select 
                v-model="newItem.location"
                class="bg-gray-800/50 border border-gray-700 rounded-lg text-gray-200 w-full appearance-none cursor-pointer duration-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 px-4 py-2.5 transition-all"
                required
              >
                <option value="" disabled>Select Location</option>
                <option v-for="location in locations" :key="location" :value="location">
                  {{ location }}
                </option>
              </select>
            </div>
          </div>

          <!-- Date and Status Group -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="text-gray-300 text-sm block font-medium mb-2">
                Restock Date & Time
                <span class="text-emerald-500 ml-1">*</span>
              </label>
              <input 
                v-model="newItem.restock_date_time"
                type="datetime-local"
                class="bg-gray-800/50 border border-gray-700 rounded-lg text-gray-200 w-full duration-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 px-4 py-2.5 transition-all"
                required
              />
            </div>

            <div>
              <label class="text-gray-300 text-sm block font-medium mb-2">Status</label>
              <select 
                v-model="newItem.status"
                class="bg-gray-800/50 border border-gray-700 rounded-lg text-gray-200 w-full appearance-none cursor-pointer duration-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 px-4 py-2.5 transition-all"
              >
                <option v-for="status in statusOptions" :key="status" :value="status">
                  {{ status }}
                </option>
              </select>
            </div>
          </div>

          <!-- Additional Stock -->
          <div>
            <label class="text-gray-300 text-sm block font-medium mb-2">Additional Stock</label>
            <div class="relative">
              <input 
                v-model.number="newItem.added_stock_amount"
                type="number"
                min="0"
                class="bg-gray-800/50 border border-gray-700 rounded-lg text-gray-200 w-full duration-200 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 px-4 py-2.5 transition-all"
              />
              <div class="flex absolute inset-y-0 items-center pr-3 right-0">
                <ArrowTrendingUpIcon class="h-5 text-gray-500 w-5" />
              </div>
            </div>
          </div>

          <!-- Required Fields Note -->
          <p class="text-gray-400 text-sm">
            <span class="text-emerald-500">*</span> Required fields
          </p>

          <!-- Action Buttons -->
          <div class="flex border-gray-700/50 border-t justify-end gap-3 items-center mt-8 pt-6">
            <button 
              @click="showAddModal = false" 
              class="rounded-lg text-gray-400 duration-200 hover:bg-gray-700/50 hover:text-gray-300 px-4 py-2 transition-colors"
            >
              Cancel
            </button>
            <button 
              @click="saveNewItem"
              :disabled="isAddingInventory || !newItem.quantity || !newItem.location || !newItem.restock_date_time"
              class="flex bg-gradient-to-r rounded-lg shadow-emerald-500/20 shadow-lg text-white disabled:cursor-not-allowed disabled:opacity-50 disabled:shadow-none duration-300 font-medium from-emerald-600 gap-2 hover:from-emerald-500 hover:to-emerald-400 items-center px-6 py-2.5 relative to-emerald-500 transition-all"
            >
              <template v-if="isAddingInventory">
                <svg class="h-5 w-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Adding Item...</span>
              </template>
              <template v-else>
                <PlusCircleIcon class="h-5 w-5" />
                <span>Add Item</span>
              </template>
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showViewModal" class="flex bg-black/80 justify-center p-4 backdrop-blur-sm fixed inset-0 items-center z-50">
      <div class="bg-gradient-to-br border border-gray-700/50 p-6 rounded-2xl shadow-2xl w-full animate-fade-in from-gray-900/95 max-w-2xl to-gray-800/95">
        <!-- Enhanced Header -->
        <div class="flex justify-between items-start mb-8">
          <div class="flex gap-4 items-center">
            <div class="bg-gradient-to-br border border-blue-500/20 p-3 rounded-xl shadow-lg from-blue-600/10 to-blue-700/10">
              <EyeIcon class="h-6 text-blue-400 w-6" />
            </div>
            <div>
              <h2 class="text-2xl text-white font-bold mb-1">Inventory Details</h2>
              <p class="text-gray-400 text-sm">Complete item information</p>
            </div>
          </div>
          <button @click="showViewModal = false" 
                  class="p-2 rounded-lg duration-200 hover:bg-gray-700/50 transition-colors">
            <XMarkIcon class="h-5 text-gray-400 w-5" />
          </button>
        </div>

        <div v-if="selectedItem" class="space-y-6">
          <!-- Main Info Card -->
          <div class="bg-gradient-to-br border border-gray-700/50 p-5 rounded-xl from-gray-800/50 to-gray-800/30">
            <div class="flex justify-between items-center mb-4">
              <div class="flex gap-3 items-center">
                <div class="bg-gray-700/50 p-2 rounded-lg">
                  <ArchiveBoxIcon class="h-5 text-gray-400 w-5" />
                </div>
                <div>
                  <span class="text-gray-400 text-sm">Inventory ID</span>
                  <h3 class="text-lg text-white font-bold">#{{ selectedItem.id }}</h3>
                </div>
              </div>
              <span :class="{
                'px-3 py-1 rounded-full text-xs font-medium flex items-center gap-2': true,
                'bg-emerald-500/20 text-emerald-400': selectedItem.status === 'In Stock',
                'bg-yellow-500/20 text-yellow-400': selectedItem.status === 'Low Stock',
                'bg-red-500/20 text-red-400': selectedItem.status === 'Out Of Stock'
              }">
                <span class="h-2 rounded-full w-2" :class="{
                  'bg-emerald-400': selectedItem.status === 'In Stock',
                  'bg-yellow-400': selectedItem.status === 'Low Stock',
                  'bg-red-400': selectedItem.status === 'Out Of Stock'
                }"></span>
                {{ selectedItem.status }}
              </span>
            </div>
          </div>

          <!-- Stats Grid -->
          <div class="grid grid-cols-3 gap-4">
            <!-- Current Stock -->
            <div class="bg-gradient-to-br border border-emerald-500/20 p-4 rounded-xl from-emerald-500/10 to-emerald-600/10">
              <div class="flex gap-3 items-center mb-3">
                <ArchiveBoxIcon class="h-5 text-emerald-400 w-5" />
                <span class="text-gray-300 text-sm font-medium">Current Stock</span>
              </div>
              <div class="text-2xl text-white font-bold">{{ selectedItem.quantity }}</div>
              <div class="text-emerald-400/80 text-sm mt-1">Available Units</div>
            </div>

            <!-- Added Stock -->
            <div class="bg-gradient-to-br border border-blue-500/20 p-4 rounded-xl from-blue-500/10 to-blue-600/10">
              <div class="flex gap-3 items-center mb-3">
                <ArrowTrendingUpIcon class="h-5 text-blue-400 w-5" />
                <span class="text-gray-300 text-sm font-medium">Added Stock</span>
              </div>
              <div class="text-2xl text-white font-bold">+{{ selectedItem.added_stock_amount || 0 }}</div>
              <div class="text-blue-400/80 text-sm mt-1">Last Addition</div>
            </div>

            <!-- Stock Value -->
            <div class="bg-gradient-to-br border border-violet-500/20 p-4 rounded-xl from-violet-500/10 to-violet-600/10">
              <div class="flex gap-3 items-center mb-3">
                <ArrowTrendingUpIcon class="h-5 text-violet-400 w-5" />
                <span class="text-gray-300 text-sm font-medium">Stock Value</span>
              </div>
              <div class="text-2xl text-white font-bold">Rs. {{ (selectedItem.price * selectedItem.quantity).toLocaleString() }}</div>
              <div class="text-sm text-violet-400/80 mt-1">Total Value</div>
            </div>
          </div>

          <!-- Additional Details -->
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-4">
              <div class="bg-gray-800/50 border border-gray-700/50 p-4 rounded-xl">
                <span class="text-gray-400 text-sm">Location</span>
                <div class="flex gap-2 items-center mt-1">
                  <FunnelIcon class="h-5 text-gray-400 w-5" />
                  <p class="text-lg text-white font-medium">{{ selectedItem.location }}</p>
                </div>
              </div>
            </div>

            <div class="space-y-4">
              <div class="bg-gray-800/50 border border-gray-700/50 p-4 rounded-xl">
                <span class="text-gray-400 text-sm">Last Updated</span>
                <div class="flex gap-2 items-center mt-1">
                  <ArrowPathIcon class="h-5 text-gray-400 w-5" />
                  <p class="text-lg text-white font-medium">
                    {{ new Date(selectedItem.restock_date_time).toLocaleString() }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Action Button -->
          <div class="flex border-gray-700/50 border-t justify-end pt-6">
            <button @click="showViewModal = false" 
                    class="flex bg-gradient-to-r rounded-lg text-white duration-200 font-medium from-gray-700 gap-2 hover:from-gray-600 hover:to-gray-500 items-center px-6 py-2.5 to-gray-600 transition-all">
              <XMarkIcon class="h-5 w-5" />
              Close Details
            </button>
          </div>
        </div>
      </div>
    </div>

    <StockUpdateGRN 
        v-if="showGRN"
        :stockData="selectedItem"
        :grnNumber="grnNumber"
        :showModal="showGRN"
        :adjustmentQuantity="stockAdjustment.quantity"
        @close="showGRN = false"
    />

    <div v-if="showExportOptions" class="flex bg-black/80 justify-center p-4 backdrop-blur-sm fixed inset-0 items-center z-50">
      <div class="bg-gradient-to-br border border-gray-700/50 p-6 rounded-2xl shadow-2xl w-full animate-fade-in from-gray-900/95 max-w-md to-gray-800/95">
        <!-- Header -->
        <div class="flex justify-between items-start mb-6">
          <div class="flex gap-4 items-center">
            <div class="bg-gradient-to-br border border-indigo-500/20 p-3 rounded-xl shadow-lg from-indigo-600/10 to-indigo-700/10">
              <ArrowDownTrayIcon class="h-6 text-indigo-400 w-6" />
            </div>
            <div>
              <h3 class="text-2xl text-white font-bold mb-1">Export Report</h3>
              <p class="text-gray-400 text-sm">Choose your preferred format</p>
            </div>
          </div>
          <button @click="showExportOptions = false" class="p-2 rounded-lg hover:bg-gray-700/50 transition-colors">
            <XMarkIcon class="h-5 text-gray-400 w-5" />
          </button>
        </div>

        <!-- Format Options -->
        <div class="grid grid-cols-2 gap-4 mb-6">
          <button 
            @click="exportInventory"
            class="bg-gradient-to-br border border-blue-500/20 p-4 rounded-xl duration-200 from-blue-600/10 group hover:from-blue-600/20 hover:to-blue-700/20 relative to-blue-700/10 transition-all"
          >
            <div class="flex flex-col gap-3 items-center">
              <div class="bg-blue-500/10 border border-blue-500/20 p-3 rounded-lg">
                <svg class="h-8 text-blue-400 w-8" viewBox="0 0 24 24" fill="none">
                  <path d="M20 7L12 3L4 7M20 7L12 11M20 7V17L12 21M12 11L4 7M12 11V21M4 7V17L12 21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </div>
              <div class="text-center">
                <h4 class="text-white font-semibold mb-1">Excel Format</h4>
                <p class="text-gray-400 text-sm">Detailed spreadsheet</p>
              </div>
            </div>
            <div class="border border-blue-500/0 rounded-xl absolute duration-200 group-hover:border-blue-500/50 inset-0 transition-all"></div>
          </button>

          <button 
            @click="exportToPDF"
            class="bg-gradient-to-br border border-red-500/20 p-4 rounded-xl duration-200 from-red-600/10 group hover:from-red-600/20 hover:to-red-700/20 relative to-red-700/10 transition-all"
          >
            <div class="flex flex-col gap-3 items-center">
              <div class="bg-red-500/10 border border-red-500/20 p-3 rounded-lg">
                <svg class="h-8 text-red-400 w-8" viewBox="0 0 24 24" fill="none">
                  <path d="M7 18H17V16H7V18Z" fill="currentColor"/>
                  <path d="M17 14H7V12H17V14Z" fill="currentColor"/>
                  <path d="M7 10H11V8H7V10Z" fill="currentColor"/>
                  <path fill-rule="evenodd" clip-rule="evenodd" d="M6 2C4.34315 2 3 3.34315 3 5V19C3 20.6569 4.34315 22 6 22H18C19.6569 22 21 20.6569 21 19V9C21 5.13401 17.866 2 14 2H6ZM6 4H13V9H19V19C19 19.5523 18.5523 20 18 20H6C5.44772 20 5 19.5523 5 19V5C5 4.44772 5.44772 4 6 4ZM15 4.10002C16.6113 4.4271 17.9413 5.52906 18.584 7H15V4.10002Z" fill="currentColor"/>
                </svg>
              </div>
              <div class="text-center">
                <h4 class="text-white font-semibold mb-1">PDF Format</h4>
                <p class="text-gray-400 text-sm">Professional report</p>
              </div>
            </div>
            <div class="border border-red-500/0 rounded-xl absolute duration-200 group-hover:border-red-500/50 inset-0 transition-all"></div>
          </button>
        </div>

        <!-- Features List -->
        <div class="bg-gray-800/50 border border-gray-700/50 p-4 rounded-xl space-y-3">
          <h4 class="text-gray-300 font-medium mb-2">Report Features:</h4>
          <div class="flex text-sm gap-2 items-center">
            <CheckIcon class="h-5 text-emerald-400 w-5" />
            <span class="text-gray-300">Executive summary with key metrics</span>
          </div>
          <div class="flex text-sm gap-2 items-center">
            <CheckIcon class="h-5 text-emerald-400 w-5" />
            <span class="text-gray-300">Detailed inventory breakdown</span>
          </div>
          <div class="flex text-sm gap-2 items-center">
            <CheckIcon class="h-5 text-emerald-400 w-5" />
            <span class="text-gray-300">Stock value analysis</span>
          </div>
          <div class="flex text-sm gap-2 items-center">
            <CheckIcon class="h-5 text-emerald-400 w-5" />
            <span class="text-gray-300">Low stock alerts</span>
          </div>
        </div>

        <!-- Footer -->
        <div class="border-gray-700/50 border-t mt-6 pt-6">
          <div class="flex justify-between text-sm items-center">
            <span class="text-gray-400">Report will include {{ filteredInventory.length }} items</span>
            <button @click="showExportOptions = false" 
                    class="rounded-lg text-gray-400 hover:bg-gray-700/50 hover:text-gray-300 px-4 py-2 transition-colors">
              Cancel
            </button>
          </div>
        </div>

        <!-- Loading Overlay -->
        <div v-if="isExporting" 
             class="flex bg-gray-900/50 justify-center rounded-2xl absolute backdrop-blur-sm inset-0 items-center">
          <div class="text-center">
            <svg class="h-8 text-indigo-500 w-8 animate-spin mb-3 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-white font-medium">Generating Report...</p>
            <p class="text-gray-400 text-sm mt-1">Please wait a moment</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.rounded-xl {
  backdrop-filter: blur(8px);
  box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
}

@keyframes fade-in {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.animate-fade-in {
  animation: fade-in 0.3s ease-out forwards;
}

::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

::-webkit-scrollbar-track {
  background: transparent;
}

::-webkit-scrollbar-thumb {
  background: rgba(156, 163, 175, 0.5);
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: rgba(156, 163, 175, 0.7);
}

.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(239, 68, 68, 0.3);
  border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(239, 68, 68, 0.5);
}
</style>