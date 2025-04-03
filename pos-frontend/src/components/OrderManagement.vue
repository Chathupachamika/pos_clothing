<script setup>
import Header from './Header.vue'
import Sidebar from './Sidebar.vue'
import SidebarCashier from './Sidebar-cashier.vue' 
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import Swal from 'sweetalert2'
import { connection } from '../api/axios'
import html2pdf from 'html2pdf.js'
import {
  ShoppingCart,
  ClipboardList,
  Search,
  Filter,
  ChevronDown,
  ChevronUp,
  ChevronLeft,
  ChevronRight,
  Calendar,
  Clock,
  User,
  CreditCard,
  Banknote,
  Package,
  FileText,
  Printer,
  Download,
  Eye,
  Edit,
  Trash2,
  CheckCircle,
  XCircle,
  AlertCircle,
  ArrowUpDown,
  MoreHorizontal,
  RefreshCw,
  X,
  RotateCcw
} from 'lucide-vue-next'

const router = useRouter()

const isSidebarVisible = ref(false)
const isAdmin = ref(false)

onMounted(() => {
  isAdmin.value = localStorage.getItem('isAdmin') === 'true'
  fetchOrders()
})

const toggleSidebar = () => {
  isSidebarVisible.value = !isSidebarVisible.value
}

const closeSidebar = () => {
  isSidebarVisible.value = false
}

const showSidebar = () => {
  isSidebarVisible.value = true
}

const searchQuery = ref('')
const selectedDateRange = ref('all')
const selectedPaymentMethod = ref('all')
const currentPage = ref(1)
const itemsPerPage = ref(10)
const sortField = ref('date')
const sortDirection = ref('desc')
const selectedOrders = ref([])
const isFilterPanelOpen = ref(false)
const isExportModalOpen = ref(false)
const isDetailModalOpen = ref(false)
const currentOrderDetail = ref(null)

const isUpdateModalOpen = ref(false)
const editingOrder = ref(null)

const isReturnModalOpen = ref(false)
const returningOrder = ref(null)
const selectedItems = ref([])
const isProcessingReturn = ref(false)

const customStartDate = ref('')
const customEndDate = ref('')

const dateRanges = [
  { id: 'all', label: 'All Time' },
  { id: 'today', label: 'Today' },
  { id: 'yesterday', label: 'Yesterday' },
  { id: 'week', label: 'This Week' },
  { id: 'month', label: 'This Month' },
  { id: 'custom', label: 'Custom Range' }
]

const paymentMethods = [
  { id: 'all', label: 'All Methods' },
  { id: 'CASH', label: 'Cash' },
  { id: 'CREDIT_CARD', label: 'Credit Card' },
  { id: 'DEBIT_CARD', label: 'Debit Card' }
]

const orderStatuses = [
  { id: 'all', label: 'All Statuses' },
  { id: 'completed', label: 'Completed' },
  { id: 'pending', label: 'Pending' },
  { id: 'cancelled', label: 'Cancelled' },
  { id: 'processing', label: 'Processing' }
]

const orders = ref([])
const isLoading = ref(false)

const fetchOrders = async () => {
  isLoading.value = true
  try {
    const response = await connection.get('/sales')
    console.log('API Response:', response.data)
    const sortedData = response.data.data.sort((a, b) => b.id - a.id)
    orders.value = sortedData.map(order => ({
      id: order.id,
      customer_id: order.customer_id,
      customer: order.customer ? {
        id: order.customer.id,
        name: order.customer.name,
        email: order.customer.email,
        phone: order.customer.phone
      } : null,
      cashier_id: order.cashier_id,
      date: order.time,
      items: order.product_sales?.length || 0,
      total: order.amount,
      status: getOrderStatus(order.status),
      payment: order.payment_type,
      products: order.product_sales ? order.product_sales.map(ps => ({
        id: ps.product?.id,
        name: ps.product?.name || 'Unknown Product',
        description: ps.product?.description || '',
        quantity: ps.quantity,
        price: ps.price,
        total: ps.price * ps.quantity,
        brand: ps.product?.brand_name,
        size: ps.product?.size,
        color: ps.product?.color,
        barCode: ps.product?.bar_code
      })) : [],
      discount: order.discount || 0,
      subtotal: order.product_sales?.reduce((sum, ps) => sum + (ps.price * ps.quantity), 0) || 0
    }))
  } catch (error) {
    console.error('Error fetching orders:', error)
    Swal.fire({
      title: 'Error',
      text: 'Failed to fetch orders',
      icon: 'error',
      confirmButtonColor: '#3B82F6',
      background: '#1e293b',
      color: '#ffffff'
    })
  } finally {
    isLoading.value = false
  }
}

const getOrderStatus = (status) => {
  return status === 1 ? 'completed' : 'pending'
}

const formatPaymentType = (type) => {
  const types = {
    'CASH': 'cash',
    'CREDIT_CARD': 'card',
    'DEBIT_CARD': 'card'
  }
  return types[type] || type.toLowerCase()
}

const createOrder = async (orderData) => {
  try {
    const response = await connection.post('/sales', {
      customer_id: orderData.customer_id,
      cashier_id: orderData.cashier_id,
      payment_type: orderData.payment_type,
      status: orderData.status === 'completed' ? 1 : 0,
      time: new Date().toISOString(),
      amount: orderData.total,
      items: orderData.products.map(p => ({
        product_id: p.id,
        quantity: p.quantity,
        price: p.price
      }))
    })
    return response.data
  } catch (error) {
    throw new Error(error.response?.data?.message || 'Failed to create order')
  }
}

const updateOrderInAPI = async (id, orderData) => {
  try {
    const response = await connection.put(`/sales/${id}`, {
      customer_id: orderData.customer_id,
      cashier_id: orderData.cashier_id,
      payment_type: orderData.payment_type,
      status: orderData.status === 'completed' ? 1 : 0,
      time: new Date().toISOString(),
      amount: orderData.total,
      items: orderData.products.map(p => ({
        product_id: p.id,
        quantity: p.quantity,
        price: p.price
      }))
    })
    return response.data
  } catch (error) {
    throw new Error(error.response?.data?.message || 'Failed to update order')
  }
}

onMounted(() => {
  fetchOrders()
})

const filteredOrders = computed(() => {
  let result = [...orders.value] 

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(order =>
      order.id.toString().toLowerCase().includes(query) ||
      (order.customer?.name || '').toLowerCase().includes(query)
    )
  }

  if (selectedPaymentMethod.value !== 'all') {
    result = result.filter(order => order.payment === selectedPaymentMethod.value)
  }

  if (selectedDateRange.value !== 'all') {
    const now = new Date()
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
    const yesterday = new Date(today)
    yesterday.setDate(yesterday.getDate() - 1)

    const startOfWeek = new Date(today)
    startOfWeek.setDate(today.getDate() - today.getDay())

    const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1)

    result = result.filter(order => {
      const orderDate = new Date(order.date)

      switch (selectedDateRange.value) {
        case 'today':
          return orderDate >= today
        case 'yesterday':
          return orderDate >= yesterday && orderDate < today
        case 'week':
          return orderDate >= startOfWeek
        case 'month':
          return orderDate >= startOfMonth
        case 'custom':
          if (customStartDate.value && customEndDate.value) {
            const start = new Date(customStartDate.value)
            const end = new Date(customEndDate.value)
            end.setHours(23, 59, 59, 999)
            return orderDate >= start && orderDate <= end
          }
          return true
        default:
          return true
      }
    })
  }

  result = result.sort((a, b) => {
    let valueA, valueB

    switch (sortField.value) {
      case 'id':
        return sortDirection.value === 'asc' 
          ? parseInt(a.id) - parseInt(b.id)
          : parseInt(b.id) - parseInt(a.id)
      case 'date':
        valueA = new Date(a.date)
        valueB = new Date(b.date)
        break
      case 'total':
        valueA = a.total
        valueB = b.total
        break
      case 'items':
        valueA = a.items
        valueB = b.items
        break
      case 'customer':
        valueA = a.customer
        valueB = b.customer
        break
      default:
        valueA = a[sortField.value]
        valueB = b[sortField.value]
    }

    if (sortDirection.value === 'asc') {
      return valueA > valueB ? 1 : -1
    } else {
      return valueA < valueB ? 1 : -1
    }
  })

  return result
})

const paginatedOrders = computed(() => {
  const startIndex = (currentPage.value - 1) * itemsPerPage.value
  return filteredOrders.value.slice(startIndex, startIndex + itemsPerPage.value)
})

const totalPages = computed(() => {
  return Math.ceil(filteredOrders.value.length / itemsPerPage.value)
})

const statusCounts = computed(() => {
  const counts = {
    all: orders.value.length,
    completed: 0,
    pending: 0,
    cancelled: 0,
    processing: 0
  }

  orders.value.forEach(order => {
    counts[order.status]++
  })

  return counts
})

const totalRevenue = computed(() => {
  return orders.value
    .filter(order => order.status !== 'cancelled')
    .reduce((sum, order) => sum + order.total, 0)
})

const todayRevenue = computed(() => {
  const today = new Date()
  today.setHours(0, 0, 0, 0)

  return orders.value
    .filter(order => {
      const orderDate = new Date(order.date)
      return orderDate >= today && order.status !== 'cancelled'
    })
    .reduce((sum, order) => sum + order.total, 0)
})

const toggleSort = (field) => {
  if (sortField.value === field) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortField.value = field
    sortDirection.value = 'asc'
  }
}

const toggleFilterPanel = () => {
  isFilterPanelOpen.value = !isFilterPanelOpen.value
}

const resetFilters = () => {
  searchQuery.value = ''
  selectedDateRange.value = 'all'
  selectedPaymentMethod.value = 'all'
  customStartDate.value = ''
  customEndDate.value = ''
  currentPage.value = 1
}

const toggleOrderSelection = (orderId) => {
  const index = selectedOrders.value.indexOf(orderId)
  if (index === -1) {
    selectedOrders.value.push(orderId)
  } else {
    selectedOrders.value.splice(index, 1)
  }
}

const selectAllOrders = () => {
  if (selectedOrders.value.length === paginatedOrders.value.length) {
    selectedOrders.value = []
  } else {
    selectedOrders.value = paginatedOrders.value.map(order => order.id)
  }
}

const openExportModal = () => {
  if (selectedOrders.value.length > 0) {
    isExportModalOpen.value = true
  } else {
    Swal.fire({
      title: 'No Orders Selected',
      text: 'Please select at least one order to export',
      icon: 'warning',
      confirmButtonColor: '#3B82F6',
      background: '#1e293b',
      color: '#ffffff'
    })
  }
}

const exportToPDF = async (orders) => {
  const element = document.createElement('div')
  element.innerHTML = `
    <div style="padding: 20px; font-family: Arial, sans-serif;">
      <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="color: #1e293b; margin: 0;">Order Export Report</h1>
        <p style="color: #64748b; margin: 10px 0;">Clothing Supply Store</p>
        <p style="color: #64748b; margin: 5px 0;">Generated on ${new Date().toLocaleString()}</p>
      </div>

      <div style="margin-bottom: 20px;">
        <h2 style="color: #1e293b; font-size: 18px;">Export Summary</h2>
        <p style="color: #64748b; margin: 5px 0;">Total Orders: ${orders.length}</p>
        <p style="color: #64748b; margin: 5px 0;">Total Revenue: Rs. ${orders.reduce((sum, order) => sum + order.total, 0).toLocaleString()}</p>
      </div>

      ${orders.map(order => `
        <div style="border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 20px; padding: 15px;">
          <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
            <div>
              <h3 style="color: #1e293b; margin: 0;">Order #${order.id}</h3>
              <p style="color: #64748b; margin: 5px 0;">${formatDate(order.date)} ${formatTime(order.date)}</p>
            </div>
            <div style="text-align: right;">
              <p style="color: #64748b; margin: 0;">Customer: ${order.customer ? order.customer.name : 'Walk-in Customer'}</p>
              <p style="color: #64748b; margin: 5px 0;">Payment: ${order.payment}</p>
            </div>
          </div>

          <table style="width: 100%; border-collapse: collapse; margin-bottom: 15px;">
            <thead>
              <tr style="background-color: #f8fafc;">
                <th style="text-align: left; padding: 8px; border-bottom: 1px solid #e2e8f0;">Product</th>
                <th style="text-align: center; padding: 8px; border-bottom: 1px solid #e2e8f0;">Quantity</th>
                <th style="text-align: right; padding: 8px; border-bottom: 1px solid #e2e8f0;">Unit Price</th>
                <th style="text-align: right; padding: 8px; border-bottom: 1px solid #e2e8f0;">Total</th>
              </tr>
            </thead>
            <tbody>
              ${order.products.map(product => `
                <tr>
                  <td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">${product.name}</td>
                  <td style="text-align: center; padding: 8px; border-bottom: 1px solid #e2e8f0;">${product.quantity}</td>
                  <td style="text-align: right; padding: 8px; border-bottom: 1px solid #e2e8f0;">Rs. ${product.price.toLocaleString()}</td>
                  <td style="text-align: right; padding: 8px; border-bottom: 1px solid #e2e8f0;">Rs. ${(product.price * product.quantity).toLocaleString()}</td>
                </tr>
              `).join('')}
            </tbody>
          </table>

          <div style="display: flex; justify-content: flex-end;">
            <div style="width: 200px;">
              <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                <span style="color: #64748b;">Subtotal:</span>
                <span style="color: #1e293b;">Rs. ${order.subtotal.toLocaleString()}</span>
              </div>
              ${order.discount > 0 ? `
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                  <span style="color: #64748b;">Discount (${order.discount}%):</span>
                  <span style="color: #ef4444;">-Rs. ${((order.subtotal * order.discount) / 100).toLocaleString()}</span>
                </div>
              ` : ''}
              <div style="display: flex; justify-content: space-between; padding-top: 5px; border-top: 1px solid #e2e8f0;">
                <span style="color: #1e293b; font-weight: bold;">Total:</span>
                <span style="color: #3b82f6; font-weight: bold;">Rs. ${order.total.toLocaleString()}</span>
              </div>
            </div>
          </div>
        </div>
      `).join('')}
    </div>
  `

  const opt = {
    margin: 10,
    filename: `orders_export_${new Date().toISOString().split('T')[0]}.pdf`,
    image: { type: 'jpeg', quality: 0.98 },
    html2canvas: { scale: 2 },
    jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
  }

  try {
    await html2pdf().set(opt).from(element).save()
    return true
  } catch (error) {
    console.error('PDF generation error:', error)
    return false
  }
}

const exportOrders = async () => {
  isLoading.value = true

  try {
    const selectedOrdersData = orders.value.filter(order =>
      selectedOrders.value.includes(order.id)
    )

    const success = await exportToPDF(selectedOrdersData)
    if (!success) throw new Error('PDF generation failed')

    isExportModalOpen.value = false
    Swal.fire({
      title: 'Export Successful',
      text: `${selectedOrders.value.length} orders exported as PDF`,
      icon: 'success',
      confirmButtonColor: '#3B82F6',
      background: '#1e293b',
      color: '#ffffff'
    })
  } catch (error) {
    console.error('Error exporting orders:', error)
    Swal.fire({
      title: 'Export Failed',
      text: 'Failed to export orders. Please try again.',
      icon: 'error',
      confirmButtonColor: '#3B82F6',
      background: '#1e293b',
      color: '#ffffff'
    })
  } finally {
    isLoading.value = false
    selectedOrders.value = []
  }
}

const viewOrderDetails = (order) => {
  currentOrderDetail.value = order
  isDetailModalOpen.value = true
}

const printOrderDetails = () => {
  const printWindow = window.open('', '_blank')

  const orderDate = new Date(currentOrderDetail.value.date)
  const formattedDate = orderDate.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })

  const formattedTime = orderDate.toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit'
  })

  let productsHtml = ''
  currentOrderDetail.value.products.forEach(product => {
    productsHtml += `
      <tr>
        <td style="padding: 8px; border-bottom: 1px solid #eee;">${product.name}</td>
        <td style="padding: 8px; border-bottom: 1px solid #eee; text-align: center;">${product.quantity}</td>
        <td style="padding: 8px; border-bottom: 1px solid #eee; text-align: right;">Rs. ${product.price.toLocaleString()}</td>
        <td style="padding: 8px; border-bottom: 1px solid #eee; text-align: right;">Rs. ${(product.price * product.quantity).toLocaleString()}</td>
      </tr>
    `
  })

  printWindow.document.write(`
    <html>
      <head>
        <title>Order Details - ${currentOrderDetail.value.id}</title>
        <style>
          body {
            font-family: 'Segoe UI', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
          }
          .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f97316;
          }
          .header h1 {
            color: #1e293b;
            margin-bottom: 5px;
          }
          .order-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
          }
          .order-info-box {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            width: 48%;
          }
          .order-info-box h2 {
            margin-top: 0;
            font-size: 16px;
            color: #666;
            border-bottom: 1px solid #eee;
            padding-bottom: 8px;
            margin-bottom: 10px;
          }
          table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
          }
          th {
            background-color: #f8f9fa;
            text-align: left;
            padding: 12px 8px;
            border-bottom: 2px solid #ddd;
          }
          .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
          }
          .status-completed { background-color: #dcfce7; color: #166534; }
          .status-pending { background-color: #fef9c3; color: #854d0e; }
          .status-cancelled { background-color: #fee2e2; color: #b91c1c; }
          .status-processing { background-color: #dbeafe; color: #1e40af; }
          .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 14px;
            color: #666;
          }
          .totals {
            margin-left: auto;
            width: 300px;
          }
          .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
          }
          .final-total {
            font-weight: bold;
            font-size: 18px;
            border-top: 2px solid #ddd;
            padding-top: 8px;
          }
          @media print {
            .no-print {
              display: none;
            }
          }
        </style>
      </head>
      <body>
        <div class="header">
          <h1>Order Details</h1>
          <p>Hardware Supply Store</p>
        </div>
        
        <div class="order-info">
          <div class="order-info-box">
            <h2>Order Information</h2>
            <p><strong>Order ID:</strong> ${currentOrderDetail.value.id}</p>
            <p><strong>Invoice:</strong> ${currentOrderDetail.value.invoice}</p>
            <p><strong>Date:</strong> ${formattedDate}</p>
            <p><strong>Time:</strong> ${formattedTime}</p>
            <p><strong>Status:</strong> <span class="status-badge status-${currentOrderDetail.value.status}">${currentOrderDetail.value.status}</span></p>
            <p><strong>Payment Method:</strong> ${currentOrderDetail.value.payment}</p>
          </div>
          
          <div class="order-info-box">
            <h2>Customer Information</h2>
            <p><strong>Customer:</strong> ${currentOrderDetail.value.customer}</p>
            <p><strong>Items:</strong> ${currentOrderDetail.value.items}</p>
            <p><strong>Total Amount:</strong> Rs. ${currentOrderDetail.value.total.toLocaleString()}</p>
          </div>
        </div>
        
        <h2>Order Items</h2>
        <table>
          <thead>
            <tr>
              <th>Product</th>
              <th style="text-align: center;">Quantity</th>
              <th style="text-align: right;">Unit Price</th>
              <th style="text-align: right;">Subtotal</th>
            </tr>
          </thead>
          <tbody>
            ${productsHtml}
          </tbody>
        </table>
        
        <div class="totals">
          <div class="total-row">
            <span>Subtotal:</span>
            <span>Rs. ${currentOrderDetail.value.total.toLocaleString()}</span>
          </div>
          <div class="total-row">
            <span>Tax (5%):</span>
            <span>Rs. ${(currentOrderDetail.value.total * 0.05).toLocaleString()}</span>
          </div>
          <div class="total-row final-total">
            <span>Total:</span>
            <span>Rs. ${(currentOrderDetail.value.total * 1.05).toLocaleString()}</span>
          </div>
        </div>
        
        <div class="footer">
          <p>Thank you for your business!</p>
          <p>For any questions, please contact us at support@hardwaresupply.com</p>
        </div>
        
        <div class="no-print" style="text-align: center; margin-top: 30px;">
          <button onclick="window.print();" style="padding: 10px 20px; background: #3B82F6; color: white; border: none; border-radius: 5px; cursor: pointer;">
            Print Order Details
          </button>
        </div>
      </body>
    </html>
  `)
}

const isDeleting = ref(false)
const isUpdating = ref(false)

const deleteOrder = async (orderId) => {
  try {
    const result = await Swal.fire({
      title: 'Delete Order?',
      text: `Are you sure you want to delete order ${orderId}? This action cannot be undone.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#EF4444',
      cancelButtonColor: '#6B7280',
      confirmButtonText: 'Yes, delete it',
      background: '#1e293b',
      color: '#ffffff'
    })

    if (result.isConfirmed) {
      isDeleting.value = true
      await connection.delete(`/sales/${orderId}`)

      orders.value = orders.value.filter(order => order.id !== orderId)
      const index = selectedOrders.value.indexOf(orderId)
      if (index !== -1) {
        selectedOrders.value.splice(index, 1)
      }

      Swal.fire({
        title: 'Deleted!',
        text: `Order ${orderId} has been deleted.`,
        icon: 'success',
        confirmButtonColor: '#3B82F6',
        background: '#1e293b',
        color: '#ffffff'
      })
    }
  } catch (error) {
    console.error('Error deleting order:', error)
    Swal.fire({
      title: 'Error',
      text: error.response?.data?.message || 'Failed to delete order',
      icon: 'error',
      confirmButtonColor: '#3B82F6',
      background: '#1e293b',
      color: '#ffffff'
    })
  } finally {
    isDeleting.value = false
  }
}

const updateOrder = async () => {
  if (!editingOrder.value) return

  try {
    const result = await Swal.fire({
      title: 'Update Order?',
      text: `Are you sure you want to update order ${editingOrder.value.id}?`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#3B82F6',
      cancelButtonColor: '#6B7280',
      confirmButtonText: 'Yes, update it',
      background: '#1e293b',
      color: '#ffffff'
    })

    if (result.isConfirmed) {
      isUpdating.value = true

      const updateData = {
        customer_id: editingOrder.value.customer_id,
        cashier_id: editingOrder.value.cashier_id,
        payment_type: editingOrder.value.payment,
        time: editingOrder.value.date,
        status: editingOrder.value.status === 'completed' ? 1 : 0,
        amount: editingOrder.value.products.reduce((sum, p) => sum + (p.price * p.quantity), 0),
        items: editingOrder.value.products.map(p => ({
          product_id: p.id,
          quantity: p.quantity,
          price: p.price
        })),
        discount: editingOrder.value.discount || 0,
        amount: calculateTotal(editingOrder.value)
      }

      const response = await connection.put(`/sales/${editingOrder.value.id}`, updateData)

      isUpdateModalOpen.value = false
      await fetchOrders() 

      Swal.fire({
        title: 'Updated!',
        text: `Order ${editingOrder.value.id} has been updated.`,
        icon: 'success',
        confirmButtonColor: '#3B82F6',
        background: '#1e293b',
        color: '#ffffff'
      })
    }
  } catch (error) {
    console.error('Error updating order:', {
      error,
      details: error.response?.data
    })

    let errorMessage = 'Failed to update order'
    if (error.response?.data?.errors) {
      const validationErrors = Object.values(error.response.data.errors).flat()
      errorMessage = validationErrors.join('\n')
    } else if (error.response?.data?.message) {
      errorMessage = error.response.data.message
    }

    Swal.fire({
      title: 'Error',
      text: errorMessage,
      icon: 'error',
      confirmButtonColor: '#3B82F6',
      background: '#1e293b',
      color: '#ffffff'
    })
  } finally {
    isUpdating.value = false
  }
}

const bulkDeleteOrders = async () => {
  if (selectedOrders.value.length === 0) {
    Swal.fire({
      title: 'No Orders Selected',
      text: 'Please select at least one order to delete',
      icon: 'warning',
      confirmButtonColor: '#3B82F6',
      background: '#1e293b',
      color: '#ffffff'
    })
    return
  }

  try {
    const result = await Swal.fire({
      title: 'Delete Selected Orders?',
      text: `Are you sure you want to delete ${selectedOrders.value.length} orders? This action cannot be undone.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#EF4444',
      cancelButtonColor: '#6B7280',
      confirmButtonText: 'Yes, delete them',
      background: '#1e293b',
      color: '#ffffff'
    })

    if (result.isConfirmed) {
      isDeleting.value = true

      await Promise.all(
        selectedOrders.value.map(orderId =>
          connection.delete(`/sales/${orderId}`)
        )
      )

      orders.value = orders.value.filter(order => !selectedOrders.value.includes(order.id))
      selectedOrders.value = []

      Swal.fire({
        title: 'Deleted!',
        text: 'Selected orders have been deleted.',
        icon: 'success',
        confirmButtonColor: '#3B82F6',
        background: '#1e293b',
        color: '#ffffff'
      })

      await fetchOrders()
    }
  } catch (error) {
    console.error('Error deleting orders:', error)
    Swal.fire({
      title: 'Error',
      text: error.response?.data?.message || 'Failed to delete orders',
      icon: 'error',
      confirmButtonColor: '#3B82F6',
      background: '#1e293b',
      color: '#ffffff'
    })
  } finally {
    isDeleting.value = false
  }
}

const changeOrderStatus = (orderId, newStatus) => {
  const order = orders.value.find(order => order.id === orderId)
  if (order) {
    order.status = newStatus
  }
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatTime = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getStatusColor = (status) => {
  switch (status) {
    case 'completed':
      return 'bg-gray-100 text-gray-800'
    case 'pending':
      return 'bg-gray-100 text-gray-800'
    case 'cancelled':
      return 'bg-gray-100 text-gray-800'
    case 'processing':
      return 'bg-gray-100 text-gray-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

const getStatusIcon = (status) => {
  switch (status) {
    case 'completed':
      return CheckCircle
    case 'pending':
      return Clock
    case 'cancelled':
      return XCircle
    case 'processing':
      return RefreshCw
    default:
      return AlertCircle
  }
}

const getPaymentIcon = (method) => {
  switch (method) {
    case 'cash':
      return Banknote
    case 'card':
      return CreditCard
    case 'bank':
      return FileText
    default:
      return CreditCard
  }
}

const openUpdateModal = (order) => {
  editingOrder.value = JSON.parse(JSON.stringify(order)) 
  isUpdateModalOpen.value = true
}

const openReturnModal = (order) => {
  returningOrder.value = order
  selectedItems.value = order.products.map(p => ({
    product_id: p.id,
    name: p.name,
    max_quantity: p.quantity,
    quantity: 0,
    reason: ''
  }))
  isReturnModalOpen.value = true
}

const validateReturnQuantity = (item) => {
  if (!item.quantity || item.quantity === 0) {
    return {
      valid: false,
      message: 'Enter return quantity',
      severity: 'info'
    }
  }
  if (item.quantity < 0) {
    return {
      valid: false,
      message: 'Quantity cannot be negative',
      severity: 'error'
    }
  }
  if (item.quantity > item.max_quantity) {
    return {
      valid: false,
      message: `Maximum ${item.max_quantity} items available`,
      severity: 'error'
    }
  }
  return { 
    valid: true,
    message: `${item.quantity} of ${item.max_quantity} items selected`,
    severity: 'success'
  }
}

const submitReturn = async () => {
  try {
    const itemsToReturn = selectedItems.value.filter(item => item.quantity > 0)
    
    const invalidItems = selectedItems.value
      .filter(item => item.quantity > 0)
      .map(item => ({
        item,
        validation: validateReturnQuantity(item)
      }))
      .filter(({ validation }) => !validation.valid)

    if (invalidItems.length > 0) {
      Swal.fire({
        title: 'Invalid Quantities',
        html: invalidItems.map(({ item, validation }) => 
          `<p>• ${item.name}: ${validation.message}</p>`
        ).join(''),
        icon: 'error',
        confirmButtonColor: '#3B82F6',
        background: '#1e293b',
        color: '#ffffff'
      })
      return
    }

    const result = await Swal.fire({
      title: 'Confirm Return',
      text: 'Are you sure you want to return these items?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#3B82F6',
      cancelButtonColor: '#6B7280',
      confirmButtonText: 'Yes, return items',
      background: '#1e293b',
      color: '#ffffff'
    })

    if (result.isConfirmed) {
      isProcessingReturn.value = true
      await connection.post(`/return/sales/${returningOrder.value.id}`, {
        cashier_id: 1, 
        payment_type: returningOrder.value.payment,
        status: 0,
        items: itemsToReturn.map(item => ({
          product_id: item.product_id,
          quantity: item.quantity,
          reason: item.reason
        }))
      })

      isReturnModalOpen.value = false
      await fetchOrders() 

      Swal.fire({
        title: 'Success!',
        text: 'Items have been returned successfully',
        icon: 'success',
        confirmButtonColor: '#3B82F6',
        background: '#1e293b',
        color: '#ffffff'
      })
    }
  } catch (error) {
    console.error('Error returning items:', error)
    Swal.fire({
      title: 'Error',
      text: error.response?.data?.message || 'Failed to return items',
      icon: 'error',
      confirmButtonColor: '#3B82F6',
      background: '#1e293b',
      color: '#ffffff'
    })
  } finally {
    isProcessingReturn.value = false
  }
}

const calculateDiscountAmount = (order) => {
  const subtotal = order.products.reduce((sum, item) => sum + (item.price * item.quantity), 0);
  return (subtotal * order.discount) / 100;
};

const calculateTotal = (order) => {
  const subtotal = order.products.reduce((sum, item) => sum + (item.price * item.quantity), 0);
  const discountAmount = (subtotal * (order.discount || 0)) / 100;
  return subtotal - discountAmount;
};

</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-[#0f172a] to-[#1e293b] text-white font-sans">
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

    <div class="w-full p-4 lg:p-8 pt-28 lg:pt-28">
      <header class="bg-[#1a2234]/95 border-b border-[#334155] sticky top-0 z-10 backdrop-blur-sm shadow-lg">
        <div class="max-w-[1920px] mx-auto px-6 py-5">
          <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
              <h1
                class="text-2xl font-bold bg-gradient-to-r from-[#4dabf7] to-[#3b82f6] bg-clip-text text-transparent flex items-center gap-2">
                <ClipboardList class="w-6 h-6 text-[#4dabf7]" />
                Order Management
              </h1>
              <p class="text-gray-400 text-sm mt-1">View and manage customer orders efficiently</p>
            </div>

            <div class="flex items-center gap-3 w-full md:w-auto">
              <div class="relative flex-1 md:w-64">
                <input type="text" v-model="searchQuery" placeholder="Search orders..."
                  class="bg-[#1e293b] border border-[#334155] rounded-lg pl-10 pr-4 py-2.5 w-full focus:outline-none focus:ring-2 focus:ring-[#3b82f6]/30 text-white placeholder-gray-500" />
                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
              </div>

              <button @click="toggleFilterPanel"
                class="p-2.5 bg-[#1e293b] border border-[#334155] rounded-lg hover:bg-[#263244] transition-colors flex items-center justify-center"
                :class="{ 'bg-[#3b82f6]/20 border-[#3b82f6]/50': isFilterPanelOpen }" aria-label="Toggle filter panel">
                <Filter class="w-5 h-5" :class="{ 'text-[#3b82f6]': isFilterPanelOpen }" />
              </button>

              <button @click="openExportModal" :disabled="selectedOrders.length === 0" :class="[
                'p-2.5 rounded-lg transition-colors flex items-center justify-center gap-2 text-sm font-medium',
                selectedOrders.length === 0
                  ? 'bg-[#1e293b]/50 border border-[#334155]/50 text-gray-500 cursor-not-allowed'
                  : 'bg-[#1e293b] border border-[#334155] hover:bg-[#263244] text-white'
              ]" aria-label="Export selected orders">
                <Download class="w-5 h-5" />
                <span class="hidden sm:inline">Export</span>
              </button>
            </div>
          </div>
        </div>
      </header>

      <div v-if="isFilterPanelOpen"
        class="bg-[#1a2234]/95 border-b border-[#334155] py-5 animate-slide-down shadow-lg backdrop-blur-sm">
        <div class="max-w-[1920px] mx-auto px-4">
          <div class="flex flex-col md:flex-row gap-4 md:items-end">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 flex-1">
              <div>
                <label class="block text-sm text-gray-400 mb-1.5 font-medium" for="date-filter">Date Range</label>
                <div class="space-y-3">
                  <div class="relative">
                    <select id="date-filter" v-model="selectedDateRange"
                      class="appearance-none bg-[#1e293b] border border-[#334155] rounded-lg pl-4 pr-10 py-2.5 w-full focus:outline-none focus:ring-2 focus:ring-[#3b82f6]/30 text-white">
                      <option v-for="range in dateRanges" :key="range.id" :value="range.id">
                        {{ range.label }}
                      </option>
                    </select>
                    <Calendar
                      class="absolute right-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none" />
                  </div>

                  <div v-if="selectedDateRange === 'custom'" class="grid grid-cols-2 gap-3">
                    <div>
                      <label class="block text-xs text-gray-400 mb-1">Start Date</label>
                      <input 
                        type="date" 
                        v-model="customStartDate"
                        class="bg-[#1e293b] border border-[#334155] rounded-lg px-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-[#3b82f6]/30 text-white text-sm [color-scheme:dark]"
                      >
                    </div>
                    <div>
                      <label class="block text-xs text-gray-400 mb-1">End Date</label>
                      <input 
                        type="date" 
                        v-model="customEndDate"
                        :min="customStartDate"
                        class="bg-[#1e293b] border border-[#334155] rounded-lg px-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-[#3b82f6]/30 text-white text-sm [color-scheme:dark]"
                      >
                    </div>
                  </div>
                </div>
              </div>

              <div>
                <label class="block text-sm text-gray-400 mb-1.5 font-medium" for="payment-filter">Payment
                  Method</label>
                <div class="relative">
                  <select id="payment-filter" v-model="selectedPaymentMethod"
                    class="appearance-none bg-[#1e293b] border border-[#334155] rounded-lg pl-4 pr-10 py-2.5 w-full focus:outline-none focus:ring-2 focus:ring-[#3b82f6]/30 text-white">
                    <option v-for="method in paymentMethods" :key="method.id" :value="method.id">
                      {{ method.label }}
                    </option>
                  </select>
                  <CreditCard
                    class="absolute right-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none" />
                </div>
              </div>
            </div>

            <div class="flex gap-3">
              <button @click="resetFilters"
                class="py-2.5 px-4 bg-gray-700 hover:bg-gray-600 rounded-lg transition-colors text-sm font-medium text-white">
                Reset
              </button>
              <button @click="toggleFilterPanel"
                class="py-2.5 px-4 bg-[#3b82f6] hover:bg-[#2563eb] rounded-lg transition-colors text-sm font-medium text-white">
                Apply Filters
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="max-w-[1920px] mx-auto px-6 py-8">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <div
            class="bg-[#1a2234]/80 backdrop-blur-sm border border-[#334155] rounded-xl p-6 flex items-center shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
            <div
              class="p-3 bg-gradient-to-br from-blue-500/20 to-blue-600/20 text-blue-400 rounded-lg mr-4 shadow-inner">
              <ClipboardList class="w-6 h-6" />
            </div>
            <div>
              <h3 class="text-sm text-gray-400 font-medium">Total Orders</h3>
              <div
                class="text-2xl font-bold bg-gradient-to-r from-gray-100 to-gray-300 bg-clip-text text-transparent mt-1">
                {{ orders.length }}
              </div>
            </div>
          </div>

          <div
            class="bg-[#1a2234] border border-[#334155] rounded-lg p-5 flex items-center shadow-sm hover:shadow-md transition-shadow">
            <div class="p-3 bg-green-500/20 text-green-400 rounded-lg mr-4">
              <CheckCircle class="w-6 h-6" />
            </div>
            <div>
              <h3 class="text-sm text-gray-400 font-medium">Completed Orders</h3>
              <div class="text-2xl font-bold text-white mt-1">{{ statusCounts.completed }}</div>
            </div>
          </div>

          <div
            class="bg-[#1a2234] border border-[#334155] rounded-lg p-5 flex items-center shadow-sm hover:shadow-md transition-shadow">
            <div class="p-3 bg-purple-500/20 text-purple-400 rounded-lg mr-4">
              <CreditCard class="w-6 h-6" />
            </div>
            <div>
              <h3 class="text-sm text-gray-400 font-medium">Total Revenue</h3>
              <div class="text-2xl font-bold text-white mt-1">Rs. {{ totalRevenue.toLocaleString() }}</div>
            </div>
          </div>

          <div
            class="bg-[#1a2234] border border-[#334155] rounded-lg p-5 flex items-center shadow-sm hover:shadow-md transition-shadow">
            <div class="p-3 bg-orange-500/20 text-orange-400 rounded-lg mr-4">
              <Calendar class="w-6 h-6" />
            </div>
            <div>
              <h3 class="text-sm text-gray-400 font-medium">Today's Revenue</h3>
              <div class="text-2xl font-bold text-white mt-1">Rs. {{ todayRevenue.toLocaleString() }}</div>
            </div>
          </div>
        </div>
      </div>

      <div class="max-w-[1920px] mx-auto px-6 pb-8">
        <div
          class="bg-[#1a2234]/80 backdrop-blur-sm border border-[#334155] rounded-xl overflow-hidden relative shadow-xl">

          <div class="p-5 border-b border-[#334155] bg-[#1e293b]/50">
            <div class="flex justify-between items-center">
              <div class="flex items-center gap-2">
                <div class="p-2 bg-blue-500/20 text-blue-400 rounded-lg">
                  <ClipboardList class="w-5 h-5" />
                </div>
                <h2 class="font-bold text-gray-200 text-lg">Orders</h2>
                <div class="ml-2 text-sm text-gray-400">
                  {{ filteredOrders.length }} orders found
                </div>
              </div>

              <div class="flex items-center gap-3">
                <div class="relative">
                  <select v-model="itemsPerPage"
                    class="appearance-none bg-[#1e293b] border border-[#334155] rounded-lg pl-4 pr-10 py-2 text-sm w-24 focus:outline-none focus:ring-1 focus:ring-[#3b82f6]/30 text-white"
                    aria-label="Rows per page">
                    <option value="5">5 rows</option>
                    <option value="10">10 rows</option>
                    <option value="20">20 rows</option>
                    <option value="50">50 rows</option>
                  </select>
                  <ChevronDown
                    class="absolute right-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" />
                </div>
              </div>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full min-w-[800px]">
              <thead>
                <tr class="bg-gradient-to-r from-[#1e293b]/70 to-[#1e293b]/50 text-gray-400 text-sm">
                  <th class="py-3 px-4 text-left">
                    <div class="flex items-center">
                      <input type="checkbox" @change="selectAllOrders"
                        :checked="selectedOrders.length === paginatedOrders.length && paginatedOrders.length > 0"
                        class="rounded border-gray-600 text-[#3b82f6] focus:ring-[#3b82f6]/30 bg-gray-700 w-4 h-4"
                        aria-label="Select all orders" />
                    </div>
                  </th>
                  <th class="py-3 px-4 text-left">
                    <button @click="toggleSort('id')" class="flex items-center gap-1 hover:text-white transition-colors"
                      aria-label="Sort by Order ID">
                      Order ID
                      <ArrowUpDown class="w-4 h-4" :class="{ 'text-[#3b82f6]': sortField === 'id' }" />
                    </button>
                  </th>
                  <th class="py-3 px-4 text-left">
                    <button @click="toggleSort('customer')"
                      class="flex items-center gap-1 hover:text-white transition-colors" aria-label="Sort by Customer">
                      Customer
                      <ArrowUpDown class="w-4 h-4" :class="{ 'text-[#3b82f6]': sortField === 'customer' }" />
                    </button>
                  </th>
                  <th class="py-3 px-4 text-left">
                    <button @click="toggleSort('date')"
                      class="flex items-center gap-1 hover:text-white transition-colors" aria-label="Sort by Date">
                      Date & Time
                      <ArrowUpDown class="w-4 h-4" :class="{ 'text-[#3b82f6]': sortField === 'date' }" />
                    </button>
                  </th>
                  <th class="py-3 px-4 text-left">Payment</th>
                  <th class="py-3 px-4 text-left">
                    <button @click="toggleSort('items')"
                      class="flex items-center gap-1 hover:text-white transition-colors" aria-label="Sort by Items">
                      Items
                      <ArrowUpDown class="w-4 h-4" :class="{ 'text-[#3b82f6]': sortField === 'items' }" />
                    </button>
                  </th>
                  <th class="py-3 px-4 text-left">
                    <button @click="toggleSort('total')"
                      class="flex items-center gap-1 hover:text-white transition-colors" aria-label="Sort by Total">
                      Total
                      <ArrowUpDown class="w-4 h-4" :class="{ 'text-[#3b82f6]': sortField === 'total' }" />
                    </button>
                  </th>
                  <th class="py-3 px-4 text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="isLoading">
                  <td colspan="9" class="py-12 text-center text-gray-500">
                    Loading orders...
                  </td>
                </tr>
                <tr v-else-if="paginatedOrders.length === 0">
                  <td colspan="9" class="py-12 text-center text-gray-500">
                    <Package class="w-16 h-16 mx-auto mb-4 opacity-30" />
                    <p class="text-lg font-medium">No orders found</p>
                    <p class="text-sm mt-1">Try changing your search or filter criteria</p>
                  </td>
                </tr>

                <tr v-for="order in paginatedOrders" :key="order.id"
                  class="border-t border-[#334155]/30 hover:bg-[#1e293b]/50 transition-colors duration-200">
                  <td class="py-4 px-4">
                    <input type="checkbox" :checked="selectedOrders.includes(order.id)"
                      @change="toggleOrderSelection(order.id)"
                      class="rounded border-gray-600 text-[#3b82f6] focus:ring-[#3b82f6]/30 bg-gray-700 w-4 h-4"
                      :aria-label="`Select order ${order.id}`" />
                  </td>
                  <td class="py-4 px-4 font-medium text-[#4dabf7]">{{ order.id }}</td>
                  <td class="py-4 px-4">
                    <div class="flex items-center gap-2">
                      <div class="w-8 h-8 rounded-full flex items-center justify-center"
                        :class="{
                          'bg-[#3b82f6]/20 text-[#3b82f6]': order.customer,
                          'bg-yellow-500/20 text-yellow-400': !order.customer
                        }">
                        <User class="w-4 h-4" />
                      </div>
                      <div class="flex flex-col">
                        <div class="flex items-center gap-2">
                          <span>{{ order.customer ? order.customer.name : 'Walk-in Customer' }}</span>
                          <span v-if="!order.customer" 
                            class="px-1.5 py-0.5 text-[10px] font-medium bg-yellow-500/20 text-yellow-400 rounded-full">
                            WALK-IN
                          </span>
                        </div>
                        <span v-if="order.customer" class="text-xs text-gray-400">
                          {{ order.customer.email || 'No email' }}
                        </span>
                      </div>
                    </div>
                  </td>
                  <td class="py-4 px-4">
                    <div class="flex flex-col">
                      <span>{{ formatDate(order.date) }}</span>
                      <span class="text-xs text-gray-400 mt-1">{{ formatTime(order.date) }}</span>
                    </div>
                  </td>
                  <td class="py-4 px-4">
                    <div class="flex items-center gap-1.5">
                      <component :is="getPaymentIcon(order.payment)" class="w-4 h-4 text-gray-400" />
                      <span class="capitalize">{{ order.payment }}</span>
                    </div>
                  </td>
                  <td class="py-4 px-4">{{ order.items }}</td>
                  <td class="py-4 px-4 font-medium text-[#3b82f6]">Rs. {{ order.total.toLocaleString() }}</td>
                  <td class="py-4 px-4">
                    <div class="flex items-center justify-center gap-2">
                      <button @click="viewOrderDetails(order)"
                        class="p-1.5 hover:bg-[#334155] rounded-lg transition-colors group" title="View Details"
                        aria-label="View order details">
                        <Eye class="w-4 h-4 text-blue-400 group-hover:text-blue-300" />
                      </button>
                      <button @click="openUpdateModal(order)"
                        class="p-1.5 hover:bg-[#334155] rounded-lg transition-colors group" title="Edit Order"
                        aria-label="Edit order">
                        <Edit class="w-4 h-4 text-yellow-400 group-hover:text-yellow-300" />
                      </button>
                      <button @click="openReturnModal(order)"
                        class="p-1.5 hover:bg-[#334155] rounded-lg transition-colors group" title="Return Order"
                        aria-label="Return order">
                        <RotateCcw class="w-4 h-4 text-purple-400 group-hover:text-purple-300" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="max-w-[1920px] mx-auto px-6 pb-8 mt-4">
        <div
          class="bg-[#1a2234]/80 backdrop-blur-sm border border-[#334155] rounded-xl overflow-hidden p-4 flex justify-between items-center">
          <div class="text-sm text-gray-400">
            Showing {{ (currentPage - 1) * itemsPerPage + 1 }} to
            {{ Math.min(currentPage * itemsPerPage, filteredOrders.length) }} of
            {{ filteredOrders.length }} orders
          </div>

          <div class="flex items-center gap-2">
            <button @click="currentPage--" :disabled="currentPage === 1"
              class="p-2 rounded-lg bg-[#1e293b] border border-[#334155] text-gray-400 hover:bg-[#263244] transition-colors"
              aria-label="Previous page">
              <ChevronLeft class="w-5 h-5" />
            </button>

            <div class="flex items-center">
              <button v-for="page in Math.min(totalPages, 5)" :key="page" @click="currentPage = page" :class="[
                'w-10 h-10 rounded-lg transition-colors text-sm font-medium ml-1',
                currentPage === page
                  ? 'bg-[#3b82f6] text-white'
                  : 'bg-[#1e293b] border border-[#334155] text-gray-300 hover:bg-[#263244]'
              ]" :aria-label="`Page ${page}`" :aria-current="currentPage === page ? 'page' : null">
                {{ page }}
              </button>
            </div>

            <button @click="currentPage++" :disabled="currentPage === totalPages"
              class="p-2 rounded-lg bg-[#1e293b] border border-[#334155] text-gray-400 hover:bg-[#263244] transition-colors"
              aria-label="Next page">
              <ChevronRight class="w-5 h-5" />
            </button>
          </div>
        </div>
      </div>

      <div v-if="isExportModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="isExportModalOpen = false"></div>

        <div
          class="relative bg-[#1a2234] rounded-lg shadow-xl border border-[#334155] w-full max-w-md p-6 z-10 animate-scale-in">
          <div class="flex justify-between items-center mb-5">
            <h2 class="text-xl font-bold text-gray-200">Export Orders</h2>
            <button @click="isExportModalOpen = false" class="p-1.5 hover:bg-gray-700/50 rounded-full transition-colors"
              aria-label="Close export modal">
              <X class="w-5 h-5" />
            </button>
          </div>

          <div class="mb-5">
            <div class="text-sm text-gray-400 mb-1">Selected Orders</div>
            <div class="text-lg font-bold text-white">{{ selectedOrders.length }} orders selected</div>
          </div>

          <div class="mb-6">
            <div class="p-4 bg-[#1e293b] rounded-lg border border-[#334155]">
              <div class="flex items-center justify-center gap-3">
                <FileText class="w-8 h-8 text-blue-400" />
                <div class="text-left">
                  <div class="text-sm font-medium text-white">Export as PDF</div>
                  <div class="text-xs text-gray-400">Generate a detailed PDF document</div>
                </div>
              </div>
            </div>
          </div>

          <div class="flex gap-3">
            <button @click="isExportModalOpen = false"
              class="flex-1 py-3 px-4 bg-gray-700 hover:bg-gray-600 rounded-lg transition-colors duration-200 text-sm font-medium text-white">
              Cancel
            </button>
            <button @click="exportOrders"
              class="flex-1 py-3 px-4 bg-blue-500 hover:bg-blue-600 rounded-lg transition-colors duration-200 flex items-center justify-center gap-2 text-sm font-medium text-white"
              :disabled="isLoading">
              <span v-if="isLoading"
                class="w-5 h-5 border-2 border-t-white border-r-transparent border-b-transparent border-l-transparent rounded-full animate-spin"></span>
              <Download v-else class="w-5 h-5" />
              <span>Export PDF</span>
            </button>
          </div>
        </div>
      </div>

      <div v-if="isDetailModalOpen && currentOrderDetail"
        class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="isDetailModalOpen = false"></div>

        <div
          class="relative bg-[#1a2234] rounded-xl shadow-2xl border border-[#334155] w-full max-w-4xl p-6 z-10 animate-scale-in max-h-[90vh] overflow-auto custom-scrollbar">
          <div class="flex justify-between items-center mb-5">
            <h2 class="text-xl font-bold text-gray-200">Order #{{ currentOrderDetail.id }}</h2>
            <button @click="isDetailModalOpen = false"
              class="p-1.5 hover:bg-gray-700/50 rounded-full transition-colors">
              <X class="w-5 h-5" />
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="space-y-4">
              <div>
                <label class="flex items-center gap-2 text-sm text-gray-400">
                  Customer Information
                  <span v-if="!currentOrderDetail.customer_id" 
                    class="px-1.5 py-0.5 text-[10px] font-medium bg-yellow-500/20 text-yellow-400 rounded-full">
                    WALK-IN CUSTOMER
                  </span>
                </label>
                <div class="mt-2 flex items-center gap-2">
                  <div class="w-8 h-8 rounded-full flex items-center justify-center"
                    :class="{
                      'bg-[#3b82f6]/20 text-[#3b82f6]': currentOrderDetail.customer_id,
                      'bg-yellow-500/20 text-yellow-400': !currentOrderDetail.customer_id
                    }">
                    <User class="w-4 h-4" />
                  </div>
                  <div class="flex flex-col">
                    <span class="text-white">
                      {{ currentOrderDetail.customer ? currentOrderDetail.customer.name : 'Walk-in Customer' }}
                    </span>
                    <span class="text-sm text-gray-400">
                      {{ currentOrderDetail.customer ? currentOrderDetail.customer.email : 'No customer account' }}
                    </span>
                  </div>
                </div>

                <div v-if="!currentOrderDetail.customer_id" 
                  class="mt-3 p-3 bg-yellow-500/10 rounded-lg border border-yellow-500/20">
                  <div class="flex items-start gap-2">
                    <AlertCircle class="w-5 h-5 text-yellow-400 mt-0.5" />
                    <div class="flex-1">
                      <p class="text-sm text-yellow-400 font-medium">Walk-in Customer Order</p>
                      <p class="text-xs text-gray-400 mt-1">
                        This order was created for a walk-in customer without a registered account.
                      </p>
                    </div>
                  </div>
                </div>
              </div>
              <div>
                <span class="text-gray-400">Date:</span>
                <span class="ml-2">{{ formatDate(currentOrderDetail.date) }}</span>
              </div>
              <div>
                <span class="text-gray-400">Time:</span>
                <span class="ml-2">{{ formatTime(currentOrderDetail.date) }}</span>
              </div>
            </div>
            <div class="space-y-4">
              <div>
                <span class="text-gray-400">Payment Method:</span>
                <div class="mt-2 flex items-center gap-2">
                  <component 
                    :is="getPaymentIcon(currentOrderDetail.payment)" 
                    class="w-5 h-5 text-purple-400"
                  />
                  <span class="capitalize">{{ currentOrderDetail.payment }}</span>
                </div>
              </div>
              <div>
                <span class="text-gray-400">Status:</span>
                <div class="mt-2 flex items-center gap-2">
                  <component 
                    :is="getStatusIcon(currentOrderDetail.status)" 
                    class="w-5 h-5"
                    :class="{
                      'text-emerald-400': currentOrderDetail.status === 'completed',
                      'text-yellow-400': currentOrderDetail.status === 'pending',
                      'text-red-400': currentOrderDetail.status === 'cancelled'
                    }"
                  />
                  <span class="capitalize">{{ currentOrderDetail.status }}</span>
                </div>
              </div>
              <div>
                <span class="text-gray-400">Total Amount:</span>
                <div class="mt-2 text-lg font-bold text-blue-400">
                  Rs. {{ currentOrderDetail.total.toLocaleString() }}
                </div>
              </div>
            </div>
          </div>

          <!-- Order Items Table -->
          <div class="bg-[#1e293b] rounded-lg p-4 border border-[#334155]">
            <h3 class="font-medium mb-4">Order Items</h3>
            <div class="overflow-x-auto">
              <table class="w-full min-w-[500px]">
                <thead>
                  <tr class="text-gray-400 text-sm border-b border-[#334155]">
                    <th class="py-2 px-4 text-left">Product Name</th>
                    <th class="py-2 px-4 text-left">Description</th>
                    <th class="py-2 px-4 text-center">Quantity</th>
                    <th class="py-2 px-4 text-right">Unit Price</th>
                    <th class="py-2 px-4 text-right">Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="currentOrderDetail.products.length === 0">
                    <td colspan="5" class="py-4 text-center text-gray-500">No items in this order</td>
                  </tr>
                  <tr v-for="product in currentOrderDetail.products" :key="product.id"
                    class="border-b border-[#334155]/50">
                    <td class="py-3 px-4">{{ product.name }}</td>
                    <td class="py-3 px-4 text-gray-400">{{ product.description || 'No description available' }}</td>
                    <td class="py-3 px-4 text-center">{{ product.quantity }}</td>
                    <td class="py-3 px-4 text-right">Rs. {{ product.price.toLocaleString() }}</td>
                    <td class="py-3 px-4 text-right font-medium">Rs. {{ product.total.toLocaleString() }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="mt-8 bg-[#1e293b] rounded-lg border border-[#334155] p-4">
            <h3 class="text-sm font-medium text-gray-400 mb-4">Order Summary</h3>
            <div class="space-y-3">
              <div class="flex justify-between items-center pb-3 border-b border-[#334155]/50">
                <span class="text-gray-400">Subtotal</span>
                <span class="text-white font-medium">Rs. {{ currentOrderDetail.subtotal?.toLocaleString() }}</span>
              </div>
              
              <div v-if="currentOrderDetail.discount > 0" 
                  class="flex justify-between items-center pb-3 border-b border-[#334155]/50">
                <div class="flex items-center gap-2">
                  <span class="text-gray-400">Discount</span>
                  <span class="px-2 py-0.5 bg-blue-500/20 text-blue-400 rounded-full text-xs">
                    {{ currentOrderDetail.discount }}%
                  </span>
                </div>
                <span class="text-red-400">
                  -Rs. {{ ((currentOrderDetail.subtotal * currentOrderDetail.discount) / 100).toLocaleString() }}
                </span>
              </div>
              
              <div class="flex justify-between items-center pt-2">
                <span class="text-lg font-medium text-white">Total Amount</span>
                <span class="text-lg font-bold text-blue-400">
                  Rs. {{ currentOrderDetail.total.toLocaleString() }}
                </span>
              </div>
            </div>
          </div>

          <div class="flex justify-end gap-3 mt-6">
            <button @click="isDetailModalOpen = false" class="py-2 px-4 bg-gray-700 hover:bg-gray-600 rounded-lg">
              Close
            </button>
          </div>
        </div>
      </div>

      <div v-if="isUpdateModalOpen && editingOrder" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="isUpdateModalOpen = false"></div>

        <div
          class="relative bg-[#1a2234] rounded-lg shadow-xl border border-[#334155] w-full max-w-2xl p-6 z-10 animate-scale-in">
          <div class="flex justify-between items-center mb-5">
            <h2 class="text-xl font-bold text-gray-200 flex items-center gap-2">
              <Edit class="w-5 h-5 text-yellow-400" />
              Update Order
            </h2>
            <button @click="isUpdateModalOpen = false" class="p-1.5 hover:bg-gray-700/50 rounded-full transition-colors"
              aria-label="Close update modal">
              <X class="w-5 h-5" />
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
              <label class="flex items-center gap-2 text-sm text-gray-400 mb-1.5">
                Customer ID
                <span v-if="!editingOrder.customer_id" 
                  class="px-1.5 py-0.5 text-[10px] font-medium bg-yellow-500/20 text-yellow-400 rounded-full">
                  WALK-IN CUSTOMER
                </span>
              </label>
              <div class="relative">
                <input type="text" 
                  v-model="editingOrder.customer_id"
                  :placeholder="editingOrder.customer_id ? '' : 'Walk-in Customer'"
                  class="bg-[#1e293b] border border-[#334155] rounded-lg pl-4 pr-10 py-2.5 w-full focus:outline-none focus:ring-2 focus:ring-[#3b82f6]/30 text-white"
                >
                <div v-if="!editingOrder.customer_id" 
                  class="absolute right-3 top-1/2 transform -translate-y-1/2 flex items-center gap-2">
                  <span class="text-xs text-yellow-400">Walk-in</span>
                  <AlertCircle class="w-4 h-4 text-yellow-400" />
                </div>
              </div>
              <div v-if="!editingOrder.customer_id" class="mt-2 p-3 bg-yellow-500/10 rounded-lg border border-yellow-500/20">
                <div class="flex items-start gap-2">
                  <AlertCircle class="w-5 h-5 text-yellow-400 mt-0.5" />
                  <div class="flex-1">
                    <p class="text-sm text-yellow-400 font-medium">Walk-in Customer Order</p>
                    <p class="text-xs text-gray-400 mt-1">
                      This order was created for a walk-in customer without a registered account. 
                      You can add a customer ID if they register later.
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <div>
              <label class="block text-sm text-gray-400 mb-1.5">Payment Method</label>
              <select v-model="editingOrder.payment"
                class="bg-[#1e293b] border border-[#334155] rounded-lg px-4 py-2.5 w-full focus:outline-none focus:ring-2 focus:ring-[#3b82f6]/30 text-white">
                <option value="CASH">Cash</option>
                <option value="CREDIT_CARD">Credit Card</option>
                <option value="DEBIT_CARD">Debit Card</option>
              </select>
            </div>

            <div class="md:col-span-2">
              <label class="block text-sm text-gray-400 mb-1.5">Date & Time</label>
              <input type="datetime-local" v-model="editingOrder.date"
                class="bg-[#1e293b] border border-[#334155] rounded-lg px-4 py-2.5 w-full focus:outline-none focus:ring-2 focus:ring-[#3b82f6]/30 text-white [color-scheme:dark]">
            </div>
          </div>

          <div>
            <label class="block text-sm text-gray-400 mb-1.5">Discount (%)</label>
            <div class="flex items-center gap-2">
              <input 
                type="number" 
                v-model="editingOrder.discount"
                min="0"
                max="100"
                class="bg-[#1e293b] border border-[#334155] rounded-lg px-4 py-2.5 w-32 focus:outline-none focus:ring-2 focus:ring-[#3b82f6]/30 text-white"
              >
              <span class="text-gray-400">%</span>
              <span v-if="editingOrder.discount > 0" class="text-sm text-blue-400">
                Discount amount: Rs. {{ calculateDiscountAmount(editingOrder).toLocaleString() }}
              </span>
            </div>
          </div>

          <div class="border-t border-[#334155] pt-6">
            <h3 class="text-sm text-gray-400 mb-3 font-medium">Order Items</h3>
            <div class="space-y-3 max-h-[200px] overflow-y-auto custom-scrollbar pr-2">
              <div v-for="(product, index) in editingOrder.products" :key="index"
                class="flex items-center gap-4 bg-[#1e293b] p-3 rounded-lg border border-[#334155]">
                <div class="w-32">
                  <label class="text-xs text-gray-400 mb-1 block">Product ID</label>
                  <input 
                    type="text" 
                    v-model="product.id"
                    class="bg-[#1e293b] border border-[#334155] rounded-lg px-3 py-1.5 w-full focus:outline-none focus:ring-2 focus:ring-[#3b82f6]/30 text-white text-sm"
                    placeholder="Product ID"
                  >
                </div>
                <div class="flex-1">
                  <label class="text-xs text-gray-400 mb-1 block">Product Name</label>
                  <input 
                    type="text"
                    v-model="product.name"
                    readonly
                    class="bg-[#1e293b]/50 border border-[#334155] rounded-lg px-3 py-1.5 w-full focus:outline-none text-gray-400 text-sm cursor-not-allowed"
                    placeholder="Product name"
                  >
                </div>
                <div class="w-24">
                  <label class="text-xs text-gray-400 mb-1 block">Quantity</label>
                  <input 
                    type="number"
                    v-model="product.quantity"
                    min="1"
                    class="bg-[#1e293b] border border-[#334155] rounded-lg px-3 py-1.5 w-full focus:outline-none focus:ring-2 focus:ring-[#3b82f6]/30 text-white text-sm"
                    placeholder="Qty"
                  >
                </div>
                <div class="w-32">
                  <label class="text-xs text-gray-400 mb-1 block">Price</label>
                  <input 
                    type="number"
                    v-model="product.price"
                    min="0"
                    class="bg-[#1e293b] border border-[#334155] rounded-lg px-3 py-1.5 w-full focus:outline-none focus:ring-2 focus:ring-[#3b82f6]/30 text-white text-sm"
                    placeholder="Price"
                  >
                </div>
                <div class="pt-5">
                  <button 
                    @click="editingOrder.products.splice(index, 1)"
                    class="p-1.5 hover:bg-red-500/20 text-red-400 rounded-lg transition-colors"
                  >
                    <X class="w-4 h-4" />
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="flex justify-end gap-3 mt-6">
            <button @click="isUpdateModalOpen = false"
              class="py-2.5 px-4 bg-gray-700 hover:bg-gray-600 rounded-lg transition-colors text-sm font-medium text-white">
              Cancel
            </button>
            <button @click="updateOrder" :disabled="isUpdating"
              class="py-2.5 px-4 bg-[#3b82f6] hover:bg-[#2563eb] rounded-lg transition-colors text-sm font-medium text-white flex items-center gap-2">
              <RefreshCw v-if="isUpdating" class="w-4 h-4 animate-spin" />
              <Edit v-else class="w-4 h-4" />
              {{ isUpdating ? 'Updating...' : 'Update Order' }}
            </button>
          </div>
        </div>
      </div>

      <div v-if="isReturnModalOpen && returningOrder" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="isReturnModalOpen = false"></div>

        <div class="relative bg-[#1a2234] rounded-xl shadow-xl border border-[#334155] w-full max-w-5xl p-6 z-10 animate-scale-in">
          <div class="flex items-center justify-between pb-6 border-b border-[#334155]">
            <div class="flex items-center gap-4">
              <div class="p-3 bg-purple-500/20 text-purple-400 rounded-lg">
                <RotateCcw class="w-6 h-6" />
              </div>
              <div>
                <h2 class="text-xl font-bold text-white">Process Return</h2>
                <p class="text-sm text-gray-400 mt-1">Order #{{ returningOrder.id }}</p>
              </div>
            </div>
            <button @click="isReturnModalOpen = false" 
              class="p-2 hover:bg-[#334155] rounded-lg transition-colors"
              aria-label="Close modal">
              <X class="w-5 h-5" />
            </button>
          </div>

          <div class="py-6">
            <div class="flex items-center gap-2 mb-4">
              <Package class="w-4 h-4 text-purple-400" />
              <h3 class="font-medium text-gray-200">Return Items</h3>
            </div>

            <div class="space-y-4 max-h-[60vh] overflow-y-auto custom-scrollbar pr-2">
              <div v-for="(item, index) in selectedItems" :key="index"
                class="bg-[#1e293b] rounded-xl border border-[#334155] overflow-hidden transition-all duration-300 hover:border-purple-500/30">
                <div class="p-4 bg-[#1e293b]/50 border-b border-[#334155] flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <div class="p-2 bg-[#334155] rounded-lg">
                      <Package class="w-4 h-4 text-purple-400" />
                    </div>
                    <div>
                      <h4 class="font-medium text-gray-200">{{ item.name }}</h4>
                      <div class="text-xs text-gray-400 mt-0.5">Product ID: {{ item.product_id }}</div>
                    </div>
                  </div>
                  <div class="flex items-center gap-3">
                    <div class="px-3 py-1 bg-[#334155] rounded-lg text-sm">
                      <span class="text-gray-400">Available:</span>
                      <span class="text-white font-medium ml-1">{{ item.max_quantity }}</span>
                    </div>
                  </div>
                </div>

                <div class="p-4">
                  <div class="grid grid-cols-2 gap-6">
                    <div>
                      <label class="block text-sm text-gray-400 mb-2">Return Quantity</label>
                      <div class="relative">
                        <input 
                          type="number"
                          v-model="item.quantity"
                          :max="item.max_quantity"
                          min="0"
                          :class="[
                            'w-full bg-[#1e293b] border rounded-lg pl-3 pr-10 py-2.5 text-sm transition-all duration-300',
                            !item.quantity ? 'border-[#334155] focus:border-[#3b82f6] text-white' : '',
                            item.quantity && validateReturnQuantity(item).valid ? 'border-emerald-500/50 text-emerald-400' : '',
                            item.quantity && !validateReturnQuantity(item).valid ? 'border-red-500/50 text-red-400' : ''
                          ]"
                        >
                        <div class="absolute right-3 top-1/2 -translate-y-1/2">
                          <component 
                            :is="item.quantity ? (validateReturnQuantity(item).valid ? CheckCircle : XCircle) : AlertCircle"
                            class="w-4 h-4"
                            :class="[
                              !item.quantity ? 'text-gray-500' : '',
                              item.quantity && validateReturnQuantity(item).valid ? 'text-emerald-400' : '',
                              item.quantity && !validateReturnQuantity(item).valid ? 'text-red-400' : ''
                            ]"
                          />
                        </div>
                      </div>
                      
                      <div class="min-h-[20px] mt-2">
                        <div v-if="item.quantity" 
                          class="flex items-center gap-1.5 text-xs"
                          :class="{
                            'text-emerald-400': validateReturnQuantity(item).valid,
                            'text-red-400': !validateReturnQuantity(item).valid
                          }">
                          <component :is="validateReturnQuantity(item).valid ? CheckCircle : AlertCircle" class="w-3.5 h-3.5" />
                          <span>{{ validateReturnQuantity(item).message }}</span>
                        </div>
                      </div>
                    </div>

                    <div>
                      <label class="block text-sm text-gray-400 mb-2">Return Reason</label>
                      <input 
                        type="text"
                        v-model="item.reason"
                        class="w-full bg-[#1e293b] border border-[#334155] rounded-lg px-3 py-2.5 text-sm text-white transition-all duration-300
                          focus:border-[#3b82f6] focus:ring-1 focus:ring-[#3b82f6]/30"
                        placeholder="Why are you returning this item?"
                      >
                      <div class="min-h-[20px] mt-2">
                        <div v-if="!item.reason && item.quantity > 0" class="flex items-center gap-1.5 text-xs text-yellow-400">
                          <AlertCircle class="w-3.5 h-3.5" />
                          <span>Please provide a reason for return</span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="mt-4">
                    <div class="h-1 bg-[#334155] rounded-full overflow-hidden">
                      <div 
                        class="h-full transition-all duration-300"
                        :class="{
                          'bg-emerald-400': validateReturnQuantity(item).valid,
                          'bg-red-400': item.quantity && !validateReturnQuantity(item).valid,
                          'bg-gray-600': !item.quantity
                        }"
                        :style="{
                          width: item.quantity ? `${(Math.min(item.quantity, item.max_quantity) / item.max_quantity) * 100}%` : '0%'
                        }"
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="flex justify-end gap-3 pt-6 border-t border-[#334155] mt-6">
            <button 
              @click="isReturnModalOpen = false"
              class="px-4 py-2.5 bg-[#334155] hover:bg-[#3f4b5e] rounded-lg transition-all duration-300 text-sm font-medium text-white"
            >
              Cancel
            </button>
            <button 
              @click="submitReturn"
              :disabled="isProcessingReturn"
              class="min-w-[140px] px-4 py-2.5 bg-purple-500 hover:bg-purple-600 disabled:bg-purple-500/50 
                disabled:cursor-not-allowed rounded-lg transition-all duration-300 text-sm font-medium text-white
                flex items-center justify-center gap-2"
            >
              <RefreshCw v-if="isProcessingReturn" class="w-4 h-4 animate-spin" />
              <RotateCcw v-else class="w-4 h-4" />
              {{ isProcessingReturn ? 'Processing...' : 'Submit Return' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.animate-slide-down {
  animation: slideDown 0.4s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

.animate-scale-in {
  animation: scaleIn 0.4s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

@keyframes slideDown {
  0% {
    opacity: 0;
    transform: translateY(-20px);
  }

  100% {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes scaleIn {
  0% {
    opacity: 0;
    transform: scale(0.95) translateY(-10px);
  }

  100% {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

button {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

button:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
}

button:active:not(:disabled) {
  transform: translateY(0);
  box-shadow: none;
}

.custom-scrollbar {
  scrollbar-width: thin;
  scrollbar-color: rgba(75, 85, 99, 0.3) transparent;
}

.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: rgba(75, 85, 99, 0.3);
  border-radius: 6px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background-color: rgba(75, 85, 99, 0.5);
}

input[type="datetime-local"]::-webkit-calendar-picker-indicator {
  filter: invert(1);
  opacity: 0.5;
  cursor: pointer;
}

input[type="datetime-local"]::-webkit-calendar-picker-indicator:hover {
  opacity: 0.8;
}

input[type="date"]::-webkit-calendar-picker-indicator {
  filter: invert(1);
  opacity: 0.5;
  cursor: pointer;
}

input[type="date"]::-webkit-calendar-picker-indicator:hover {
  opacity: 0.8;
}
</style>