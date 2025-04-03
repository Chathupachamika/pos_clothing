<script setup>
import Header from "./Header.vue"
import Sidebar from "./Sidebar.vue"
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { 
  BuildingLibraryIcon, BanknotesIcon, 
  PencilIcon, TrashIcon, PlusIcon,
  MagnifyingGlassIcon, ArrowsRightLeftIcon,
  ChevronUpIcon, ChevronDownIcon,
  EyeIcon, XMarkIcon
} from '@heroicons/vue/24/outline'
import { connection } from '@/api/axios'
import Swal from 'sweetalert2'

const formatCurrency = (value) => {
  return new Intl.NumberFormat('en-LK', {
    style: 'currency',
    currency: 'LKR',
    minimumFractionDigits: 2
  }).format(value || 0)
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  }).format(date)
}

const activeTab = ref('assets')
const isLoading = ref(true)
const showForm = ref(false)
const formMode = ref('add')
const searchQuery = ref('')
const sortField = ref('name')
const sortDirection = ref('asc')
const currentItem = ref(null)
const showViewModal = ref(false)
const viewingItem = ref(null)

const assets = ref([])
const investments = ref([])
const loans = ref([])

const formData = ref({
  name: '',
  type: '',
  location: '',
  value: '',
  
  investor_name: '',
  amount: '',
  investment_date: '',
  description: '',
  
  borrower_name: '',
  loan_date: '',
  due_date: '',
  status: 'pending'
})

const filteredItems = computed(() => {
  let items = []
  
  switch (activeTab.value) {
    case 'assets':
      items = assets.value
      break
    case 'investments':
      items = investments.value
      break
    case 'loans':
      items = loans.value
      break
  }
  
  if (!searchQuery.value) return items
  
  return items.filter(item => {
    if (activeTab.value === 'assets') {
      return item.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
             item.type.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
             item.location.toLowerCase().includes(searchQuery.value.toLowerCase())
    } else if (activeTab.value === 'investments') {
      return item.investor_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
             item.description?.toLowerCase().includes(searchQuery.value.toLowerCase())
    } else {
      return item.borrower_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
             item.status.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
             item.description?.toLowerCase().includes(searchQuery.value.toLowerCase())
    }
  })
})

const sortedItems = computed(() => {
  return [...filteredItems.value].sort((a, b) => {
    let aValue = a[sortField.value]
    let bValue = b[sortField.value]
    
    if (!isNaN(parseFloat(aValue)) && !isNaN(parseFloat(bValue))) {
      aValue = parseFloat(aValue)
      bValue = bValue
    } else if (typeof aValue === 'string' && typeof bValue === 'string') {
      aValue = aValue.toLowerCase()
      bValue = bValue.toLowerCase()
    }
    
    if (aValue < bValue) return sortDirection.value === 'asc' ? -1 : 1
    if (aValue > bValue) return sortDirection.value === 'asc' ? 1 : -1
    return 0
  })
})

const totalValue = computed(() => {
  if (activeTab.value === 'assets') {
    return assets.value.reduce((sum, asset) => sum + parseFloat(asset.value || 0), 0)
  } else if (activeTab.value === 'investments') {
    return investments.value.reduce((sum, investment) => sum + parseFloat(investment.amount || 0), 0)
  } else {
    return loans.value.reduce((sum, loan) => sum + parseFloat(loan.amount || 0), 0)
  }
})

const formTitle = computed(() => {
  const action = formMode.value === 'add' ? 'Add' : 'Edit'
  const type = activeTab.value.charAt(0).toUpperCase() + activeTab.value.slice(1, -1)
  return `${action} ${type}`
})

const isValidAssetForm = computed(() => {
  if (activeTab.value !== 'assets') return false
  return !!(formData.value.name?.trim() && 
    formData.value.type?.trim() && 
    formData.value.location?.trim() && 
    formData.value.value && 
    Number(formData.value.value) > 0)
})

const isValidInvestmentForm = computed(() => {
  if (activeTab.value !== 'investments') return false
  return !!(formData.value.investor_name?.trim() && 
    formData.value.amount && 
    Number(formData.value.amount) > 0 && 
    formData.value.investment_date)
})

const isValidLoanForm = computed(() => {
  if (activeTab.value !== 'loans') return false
  return !!(formData.value.borrower_name?.trim() && 
    formData.value.amount && 
    Number(formData.value.amount) > 0 && 
    formData.value.loan_date && 
    formData.value.due_date &&
    formData.value.status &&
    new Date(formData.value.due_date) >= new Date(formData.value.loan_date))
})

const isFormValid = computed(() => {
  switch (activeTab.value) {
    case 'assets':
      return isValidAssetForm.value
    case 'investments':
      return isValidInvestmentForm.value
    case 'loans':
      return isValidLoanForm.value
    default:
      return false
  }
})

const isSubmitting = ref(false)

const fetchData = async () => {
  isLoading.value = true
  try {
    const [assetsRes, investmentsRes, loansRes] = await Promise.all([
      connection.get('/assets'),
      connection.get('/investments'),
      connection.get('/loans')
    ])
    
    assets.value = assetsRes.data
    investments.value = investmentsRes.data
    
    const updatedLoans = updateOverdueLoans(loansRes.data)
    loans.value = updatedLoans
    
    const overdueUpdates = updatedLoans
      .filter(loan => loan.status === 'overdue' && loansRes.data.find(l => l.id === loan.id)?.status === 'pending')
      .map(loan => connection.put(`/loans/${loan.id}`, { ...loan }))
    
    if (overdueUpdates.length > 0) {
      await Promise.all(overdueUpdates)
    }
    
  } catch (error) {
    console.error('Error fetching data:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error!',
      text: 'Failed to load or update data',
      background: '#1e293b',
      color: '#ffffff'
    })
  } finally {
    isLoading.value = false
  }
}

const setActiveTab = (tab) => {
  activeTab.value = tab
  resetForm()
}

const openAddForm = () => {
  formMode.value = 'add'
  resetForm()
  showForm.value = true
}

const openEditForm = (item) => {
  formMode.value = 'edit'
  currentItem.value = item
  
  if (activeTab.value === 'assets') {
    formData.value = {
      name: item.name,
      type: item.type,
      location: item.location,
      value: item.value
    }
  } else if (activeTab.value === 'investments') {
    formData.value = {
      investor_name: item.investor_name,
      amount: item.amount,
      investment_date: item.investment_date,
      description: item.description || ''
    }
  } else {
    formData.value = {
      borrower_name: item.borrower_name,
      amount: item.amount,
      loan_date: item.loan_date,
      due_date: item.due_date,
      status: item.status,
      description: item.description || ''
    }
  }
  
  showForm.value = true
}

const closeForm = () => {
  showForm.value = false
  resetForm()
}

const resetForm = () => {
  formData.value = {
    name: '',
    type: '',
    location: '',
    value: '',
    investor_name: '',
    amount: '',
    investment_date: '',
    borrower_name: '',
    loan_date: '',
    due_date: '',
    status: 'pending',
    description: ''
  }
  currentItem.value = null
}

const handleSubmit = async () => {
  if (!isFormValid.value || isSubmitting.value) return

  isSubmitting.value = true
  try {
    if (formMode.value === 'add') {
      let response
      if (activeTab.value === 'assets') {
        if (!isValidAssetForm.value) {
          throw new Error('Please fill in all required asset fields correctly')
        }
        response = await connection.post('/assets', formData.value)
        assets.value.push(response.data)
      } else if (activeTab.value === 'investments') {
        if (!isValidInvestmentForm.value) {
          throw new Error('Please fill in all required investment fields correctly')
        }
        response = await connection.post('/investments', formData.value)
        investments.value.push(response.data)
      } else {
        if (!isValidLoanForm.value) {
          throw new Error('Please fill in all required loan fields correctly')
        }
        response = await connection.post('/loans', formData.value)
        loans.value.push(response.data)
      }
      Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: `${activeTab.value.slice(0, -1)} added successfully`,
        background: '#1e293b',
        color: '#ffffff'
      })
    } else {
      let response
      if (activeTab.value === 'assets') {
        if (!isValidAssetForm.value) {
          throw new Error('Please fill in all required asset fields correctly')
        }
        response = await connection.put(`/assets/${currentItem.value.id}`, formData.value)
        const index = assets.value.findIndex(item => item.id === currentItem.value.id)
        if (index !== -1) assets.value[index] = response.data
      } else if (activeTab.value === 'investments') {
        if (!isValidInvestmentForm.value) {
          throw new Error('Please fill in all required investment fields correctly')
        }
        response = await connection.put(`/investments/${currentItem.value.id}`, formData.value)
        const index = investments.value.findIndex(item => item.id === currentItem.value.id)
        if (index !== -1) investments.value[index] = response.data
      } else {
        if (!isValidLoanForm.value) {
          throw new Error('Please fill in all required loan fields correctly')
        }
        response = await connection.put(`/loans/${currentItem.value.id}`, formData.value)
        const index = loans.value.findIndex(item => item.id === currentItem.value.id)
        if (index !== -1) loans.value[index] = response.data
      }
      Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: `${activeTab.value.slice(0, -1)} updated successfully`,
        background: '#1e293b',
        color: '#ffffff'
      })
    }
    closeForm()
  } catch (error) {
    console.error('Error submitting form:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error!',
      text: error.message || 'Failed to save data. Please try again.',
      background: '#1e293b',
      color: '#ffffff'
    })
  } finally {
    isSubmitting.value = false
  }
}

const deleteItem = async (item) => {
  if (confirm('Are you sure you want to delete this item?')) {
    try {
      if (activeTab.value === 'assets') {
        await connection.delete(`/assets/${item.id}`)
        assets.value = assets.value.filter(asset => asset.id !== item.id)
      } else if (activeTab.value === 'investments') {
        await connection.delete(`/investments/${item.id}`)
        investments.value = investments.value.filter(investment => investment.id !== item.id)
      } else {
        await connection.delete(`/loans/${item.id}`)
        loans.value = loans.value.filter(loan => loan.id !== item.id)
      }
    } catch (error) {
      console.error('Error deleting item:', error)
      alert('Failed to delete item. Please try again.')
    }
  }
}

const getActiveItems = () => {
  switch (activeTab.value) {
    case 'assets':
      return assets.value
    case 'investments':
      return investments.value
    case 'loans':
      return loans.value
    default:
      return []
  }
}

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

const viewDetails = (item) => {
  viewingItem.value = item
  showViewModal.value = true
}

const updateOverdueLoans = (loansList) => {
  const today = new Date()
  today.setHours(0, 0, 0, 0) 
  
  return loansList.map(loan => {
    const dueDate = new Date(loan.due_date)
    dueDate.setHours(0, 0, 0, 0)
    
    if (loan.status === 'pending' && dueDate < today) {
      return {
        ...loan,
        status: 'overdue'
      }
    }
    return loan
  })
}

onMounted(() => {
  fetchData()
  const intervalId = setInterval(() => {
    if (loans.value.length > 0) {
      loans.value = updateOverdueLoans(loans.value)
    }
  }, 3600000) 
  
  onUnmounted(() => {
    clearInterval(intervalId)
  })
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
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900">
    <div class="fixed left-0 top-0 w-2 h-full z-[55] hover-trigger" 
         @mouseenter="showSidebar"></div>

    <Sidebar :isVisible="isSidebarVisible" @closeSidebar="closeSidebar" />
    
    <Header @toggleSidebar="toggleSidebar" />

    <div class="w-full p-4 lg:p-8 pt-28 lg:pt-28">
      <div class="financial-details-container h-screen w-full bg-gradient-to-b from-slate-900 to-slate-800 text-white flex flex-col">
        <div class="px-6 py-5 flex items-center justify-between border-b border-slate-700/50 backdrop-blur-sm bg-slate-900/80">
          <div class="flex items-center">
            <div class="bg-indigo-500/10 p-2.5 rounded-xl mr-3">
              <BuildingLibraryIcon class="w-6 h-6 text-indigo-400" />
            </div>
            <div>
              <h1 class="text-xl font-medium">
                Financial <span class="text-indigo-400 font-bold">Details</span>
              </h1>
              <p class="text-xs text-slate-400">Manage your financial assets</p>
            </div>
          </div>
          
          <div class="flex items-center space-x-5">
            <div class="text-right">
              <div class="text-xs text-slate-400 mb-0.5">Total Value</div>
              <div class="text-lg font-bold text-white">{{ formatCurrency(totalValue) }}</div>
            </div>
            <button @click="openAddForm" 
                    class="p-2.5 rounded-full bg-indigo-600 text-white hover:bg-indigo-500 transition-all shadow-lg shadow-indigo-600/20">
              <PlusIcon class="w-5 h-5" />
            </button>
          </div>
        </div>
        
        <div class="px-6 py-3 border-b border-slate-700/50 flex bg-slate-800/50 backdrop-blur-sm">
          <button @click="setActiveTab('assets')" 
                  class="px-4 py-2.5 rounded-lg flex items-center mr-2 transition-all duration-200"
                  :class="activeTab === 'assets' ? 'bg-indigo-500/20 text-indigo-300 shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-700/30'">
            <BuildingLibraryIcon class="w-5 h-5 mr-2" />
            <span class="font-medium">Assets</span>
          </button>
          <button @click="setActiveTab('investments')" 
                  class="px-4 py-2.5 rounded-lg flex items-center mr-2 transition-all duration-200"
                  :class="activeTab === 'investments' ? 'bg-indigo-500/20 text-indigo-300 shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-700/30'">
            <BanknotesIcon class="w-5 h-5 mr-2" />
            <span class="font-medium">Investments</span>
          </button>
          <button @click="setActiveTab('loans')" 
                  class="px-4 py-2.5 rounded-lg flex items-center transition-all duration-200"
                  :class="activeTab === 'loans' ? 'bg-indigo-500/20 text-indigo-300 shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-700/30'">
            <ArrowsRightLeftIcon class="w-5 h-5 mr-2" />
            <span class="font-medium">Loans</span>
          </button>
        </div>
        
        <div class="px-6 py-4 border-b border-slate-700/50 bg-slate-800/30">
          <div class="relative">
            <input v-model="searchQuery"
                   type="text" 
                   placeholder="Search..." 
                   class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl pl-11 pr-4 py-2.5 text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all" />
            <div class="absolute left-3.5 top-3 text-slate-500">
              <MagnifyingGlassIcon class="w-5 h-5" />
            </div>
          </div>
        </div>
        
        <div class="flex-1 overflow-auto">
          <div v-if="isLoading" class="flex flex-col items-center justify-center h-full">
            <div class="w-14 h-14 border-4 border-indigo-400/20 border-t-indigo-400 rounded-full animate-spin"></div>
            <div class="mt-4 text-slate-400 font-medium">Loading data...</div>
          </div>
          
          <div v-else-if="filteredItems.length === 0" class="flex flex-col items-center justify-center h-full text-center p-6">
            <div class="w-20 h-20 bg-slate-800/80 rounded-2xl flex items-center justify-center mb-5 border border-slate-700/50 shadow-xl">
              <component :is="activeTab === 'assets' ? BuildingLibraryIcon : 
                          activeTab === 'investments' ? BanknotesIcon : 
                          ArrowsRightLeftIcon" 
                        class="w-10 h-10 text-slate-500" />
            </div>
            <h3 class="text-xl font-medium text-slate-300 mb-2">No items found</h3>
            <p class="text-slate-500 max-w-md mb-8">
              {{ searchQuery ? 'Try adjusting your search query' : `You don't have any ${activeTab} yet` }}
            </p>
            <button @click="openAddForm" 
                    class="px-5 py-2.5 rounded-xl bg-indigo-600 text-white font-medium hover:bg-indigo-500 transition-all shadow-lg shadow-indigo-600/20 flex items-center">
              <PlusIcon class="w-5 h-5 mr-2" />
              <span>Add {{ activeTab.slice(0, -1) }}</span>
            </button>
          </div>
          
          <div v-else-if="activeTab === 'assets'" class="w-full">
            <table class="w-full border-collapse">
              <thead>
                <tr class="text-left border-b border-slate-700/50 bg-slate-800/30">
                  <th @click="toggleSort('name')" 
                      class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase cursor-pointer hover:text-white">
                    <div class="flex items-center">
                      Name
                      <component v-if="getSortIcon('name')" :is="getSortIcon('name')" class="w-4 h-4 ml-1" />
                    </div>
                  </th>
                  <th @click="toggleSort('type')" 
                      class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase cursor-pointer hover:text-white">
                    <div class="flex items-center">
                      Type
                      <component v-if="getSortIcon('type')" :is="getSortIcon('type')" class="w-4 h-4 ml-1" />
                    </div>
                  </th>
                  <th @click="toggleSort('location')" 
                      class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase cursor-pointer hover:text-white">
                    <div class="flex items-center">
                      Location
                      <component v-if="getSortIcon('location')" :is="getSortIcon('location')" class="w-4 h-4 ml-1" />
                    </div>
                  </th>
                  <th @click="toggleSort('value')" 
                      class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase cursor-pointer hover:text-white">
                    <div class="flex items-center">
                      Value
                      <component v-if="getSortIcon('value')" :is="getSortIcon('value')" class="w-4 h-4 ml-1" />
                    </div>
                  </th>
                  <th class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in sortedItems" :key="item.id" class="border-b border-slate-700/30 hover:bg-slate-700/20 transition-colors">
                  <td class="px-6 py-4 text-white font-medium">{{ item.name }}</td>
                  <td class="px-6 py-4 text-slate-300">{{ item.type }}</td>
                  <td class="px-6 py-4 text-slate-300">{{ item.location }}</td>
                  <td class="px-6 py-4 text-slate-300 font-medium">{{ formatCurrency(item.value) }}</td>
                  <td class="px-6 py-4">
                    <div class="flex justify-end space-x-2">
                      <button @click="viewDetails(item)" class="text-cyan-400 hover:text-cyan-300 p-1.5 hover:bg-slate-700/50 rounded-lg transition-colors">
                        <EyeIcon class="w-5 h-5" />
                      </button>
                      <button @click="openEditForm(item)" class="text-indigo-400 hover:text-indigo-300 p-1.5 hover:bg-slate-700/50 rounded-lg transition-colors">
                        <PencilIcon class="w-5 h-5" />
                      </button>
                      <button @click="deleteItem(item)" class="text-rose-400 hover:text-rose-300 p-1.5 hover:bg-slate-700/50 rounded-lg transition-colors">
                        <TrashIcon class="w-5 h-5" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <div v-else-if="activeTab === 'investments'" class="w-full">
            <table class="w-full border-collapse">
              <thead>
                <tr class="text-left border-b border-slate-700/50 bg-slate-800/30">
                  <th @click="toggleSort('investor_name')" 
                      class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase cursor-pointer hover:text-white">
                    <div class="flex items-center">
                      Investor
                      <component v-if="getSortIcon('investor_name')" :is="getSortIcon('investor_name')" class="w-4 h-4 ml-1" />
                    </div>
                  </th>
                  <th @click="toggleSort('amount')" 
                      class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase cursor-pointer hover:text-white">
                    <div class="flex items-center">
                      Amount
                      <component v-if="getSortIcon('amount')" :is="getSortIcon('amount')" class="w-4 h-4 ml-1" />
                    </div>
                  </th>
                  <th @click="toggleSort('investment_date')" 
                      class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase cursor-pointer hover:text-white">
                    <div class="flex items-center">
                      Date
                      <component v-if="getSortIcon('investment_date')" :is="getSortIcon('investment_date')" class="w-4 h-4 ml-1" />
                    </div>
                  </th>
                  <th @click="toggleSort('description')" 
                      class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase cursor-pointer hover:text-white">
                    <div class="flex items-center">
                      Description
                      <component v-if="getSortIcon('description')" :is="getSortIcon('description')" class="w-4 h-4 ml-1" />
                    </div>
                  </th>
                  <th class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in sortedItems" :key="item.id" class="border-b border-slate-700/30 hover:bg-slate-700/20 transition-colors">
                  <td class="px-6 py-4 text-white font-medium">{{ item.investor_name }}</td>
                  <td class="px-6 py-4 text-slate-300 font-medium">{{ formatCurrency(item.amount) }}</td>
                  <td class="px-6 py-4 text-slate-300">{{ formatDate(item.investment_date) }}</td>
                  <td class="px-6 py-4 text-slate-300">{{ item.description }}</td>
                  <td class="px-6 py-4">
                    <div class="flex justify-end space-x-2">
                      <button @click="viewDetails(item)" class="text-cyan-400 hover:text-cyan-300 p-1.5 hover:bg-slate-700/50 rounded-lg transition-colors">
                        <EyeIcon class="w-5 h-5" />
                      </button>
                      <button @click="openEditForm(item)" class="text-indigo-400 hover:text-indigo-300 p-1.5 hover:bg-slate-700/50 rounded-lg transition-colors">
                        <PencilIcon class="w-5 h-5" />
                      </button>
                      <button @click="deleteItem(item)" class="text-rose-400 hover:text-rose-300 p-1.5 hover:bg-slate-700/50 rounded-lg transition-colors">
                        <TrashIcon class="w-5 h-5" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <div v-else class="w-full">
            <table class="w-full border-collapse">
              <thead>
                <tr class="text-left border-b border-slate-700/50 bg-slate-800/30">
                  <th @click="toggleSort('borrower_name')" 
                      class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase cursor-pointer hover:text-white">
                    <div class="flex items-center">
                      Borrower
                      <component v-if="getSortIcon('borrower_name')" :is="getSortIcon('borrower_name')" class="w-4 h-4 ml-1" />
                    </div>
                  </th>
                  <th @click="toggleSort('amount')" 
                      class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase cursor-pointer hover:text-white">
                    <div class="flex items-center">
                      Amount
                      <component v-if="getSortIcon('amount')" :is="getSortIcon('amount')" class="w-4 h-4 ml-1" />
                    </div>
                  </th>
                  <th @click="toggleSort('loan_date')" 
                      class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase cursor-pointer hover:text-white">
                    <div class="flex items-center">
                      Loan Date
                      <component v-if="getSortIcon('loan_date')" :is="getSortIcon('loan_date')" class="w-4 h-4 ml-1" />
                    </div>
                  </th>
                  <th @click="toggleSort('due_date')" 
                      class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase cursor-pointer hover:text-white">
                    <div class="flex items-center">
                      Due Date
                      <component v-if="getSortIcon('due_date')" :is="getSortIcon('due_date')" class="w-4 h-4 ml-1" />
                    </div>
                  </th>
                  <th @click="toggleSort('status')" 
                      class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase cursor-pointer hover:text-white">
                    <div class="flex items-center">
                      Status
                      <component v-if="getSortIcon('status')" :is="getSortIcon('status')" class="w-4 h-4 ml-1" />
                    </div>
                  </th>
                  <th class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in sortedItems" :key="item.id" class="border-b border-slate-700/30 hover:bg-slate-700/20 transition-colors">
                  <td class="px-6 py-4 text-white font-medium">{{ item.borrower_name }}</td>
                  <td class="px-6 py-4 text-slate-300 font-medium">{{ formatCurrency(item.amount) }}</td>
                  <td class="px-6 py-4 text-slate-300">{{ formatDate(item.loan_date) }}</td>
                  <td class="px-6 py-4 text-slate-300">{{ formatDate(item.due_date) }}</td>
                  <td class="px-6 py-4">
                    <span class="px-3 py-1.5 rounded-full text-xs font-medium inline-block"
                          :class="{
                            'bg-amber-500/20 text-amber-400 border border-amber-500/20': item.status === 'pending',
                            'bg-emerald-500/20 text-emerald-400 border border-emerald-500/20': item.status === 'paid',
                            'bg-rose-500/20 text-rose-400 border border-rose-500/20': item.status === 'overdue'
                          }">
                      {{ item.status.charAt(0).toUpperCase() + item.status.slice(1) }}
                    </span>
                  </td>
                  <td class="px-6 py-4">
                    <div class="flex justify-end space-x-2">
                      <button @click="viewDetails(item)" class="text-cyan-400 hover:text-cyan-300 p-1.5 hover:bg-slate-700/50 rounded-lg transition-colors">
                        <EyeIcon class="w-5 h-5" />
                      </button>
                      <button @click="openEditForm(item)" class="text-indigo-400 hover:text-indigo-300 p-1.5 hover:bg-slate-700/50 rounded-lg transition-colors">
                        <PencilIcon class="w-5 h-5" />
                      </button>
                      <button @click="deleteItem(item)" class="text-rose-400 hover:text-rose-300 p-1.5 hover:bg-slate-700/50 rounded-lg transition-colors">
                        <TrashIcon class="w-5 h-5" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        
        <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/80 backdrop-blur-sm">
          <div class="bg-gradient-to-b from-slate-800 to-slate-900 rounded-2xl border border-slate-700/50 shadow-2xl w-full max-w-lg mx-4 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-700/50 flex items-center justify-between bg-slate-800/80">
              <h3 class="text-lg font-medium text-white flex items-center">
                <span class="w-8 h-8 rounded-lg bg-indigo-500/20 flex items-center justify-center mr-3">
                  <component :is="activeTab === 'assets' ? BuildingLibraryIcon : 
                              activeTab === 'investments' ? BanknotesIcon : 
                              ArrowsRightLeftIcon" 
                            class="w-5 h-5 text-indigo-400" />
                </span>
                {{ formTitle }}
              </h3>
              <button @click="closeForm" class="text-slate-400 hover:text-white p-1.5 hover:bg-slate-700/50 rounded-lg transition-colors">
                <XMarkIcon class="w-5 h-5" />
              </button>
            </div>
            
            <form @submit.prevent="handleSubmit" class="p-6">
              <template v-if="activeTab === 'assets'">
                <div class="mb-5">
                  <label class="block text-sm font-medium text-slate-300 mb-1.5">Name</label>
                  <input v-model="formData.name" type="text" required
                         class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all" />
                </div>
                <div class="mb-5">
                  <label class="block text-sm font-medium text-slate-300 mb-1.5">Type</label>
                  <input v-model="formData.type" type="text" required
                         class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all" />
                </div>
                <div class="mb-5">
                  <label class="block text-sm font-medium text-slate-300 mb-1.5">Location</label>
                  <input v-model="formData.location" type="text" required
                         class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all" />
                </div>
                <div class="mb-5">
                  <label class="block text-sm font-medium text-slate-300 mb-1.5">Value</label>
                  <input v-model="formData.value" type="number" min="0" step="0.01" required
                         class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all" />
                </div>
                <div v-if="formData.value && !formData.value.value" class="text-rose-400 text-sm mt-1">
                  Please enter a valid value greater than 0
                </div>
              </template>
              
              <template v-else-if="activeTab === 'investments'">
                <div class="mb-5">
                  <label class="block text-sm font-medium text-slate-300 mb-1.5">Investor Name</label>
                  <input v-model="formData.investor_name" type="text" required
                         class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all" />
                </div>
                <div class="mb-5">
                  <label class="block text-sm font-medium text-slate-300 mb-1.5">Amount</label>
                  <input v-model="formData.amount" type="number" min="0" step="0.01" required
                         class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all" />
                </div>
                <div class="mb-5">
                  <label class="block text-sm font-medium text-slate-300 mb-1.5">Investment Date</label>
                  <input v-model="formData.investment_date" type="date" required
                         class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all" />
                </div>
                <div class="mb-5">
                  <label class="block text-sm font-medium text-slate-300 mb-1.5">Description</label>
                  <textarea v-model="formData.description" rows="3"
                            class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all"></textarea>
                </div>
                <div v-if="formData.value && !formData.value.amount" class="text-rose-400 text-sm mt-1">
                  Please enter a valid amount greater than 0
                </div>
              </template>
              
              <template v-else>
                <div class="mb-5">
                  <label class="block text-sm font-medium text-slate-300 mb-1.5">Borrower Name</label>
                  <input v-model="formData.borrower_name" type="text" required
                         class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all" />
                </div>
                <div class="mb-5">
                  <label class="block text-sm font-medium text-slate-300 mb-1.5">Amount</label>
                  <input v-model="formData.amount" type="number" min="0" step="0.01" required
                         class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all" />
                </div>
                <div class="mb-5">
                  <label class="block text-sm font-medium text-slate-300 mb-1.5">Loan Date</label>
                  <input v-model="formData.loan_date" type="date" required
                         class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all" />
                </div>
                <div class="mb-5">
                  <label class="block text-sm font-medium text-slate-300 mb-1.5">Due Date</label>
                  <input v-model="formData.due_date" type="date" required
                         class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all" />
                </div>
                <div class="mb-5">
                  <label class="block text-sm font-medium text-slate-300 mb-1.5">Status</label>
                  <select v-model="formData.status" required
                          class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all">
                    <option value="pending">Pending</option>
                    <option value="paid">Paid</option>
                    <option value="overdue">Overdue</option>
                  </select>
                </div>
                <div class="mb-5">
                  <label class="block text-sm font-medium text-slate-300 mb-1.5">Description</label>
                  <textarea v-model="formData.description" rows="3"
                            class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all"></textarea>
                </div>
                <div v-if="formData.loan_date && formData.due_date && new Date(formData.due_date) < new Date(formData.loan_date)" 
                     class="text-rose-400 text-sm mt-1">
                  Due date must be after loan date
                </div>
              </template>
              
              <div class="flex justify-end space-x-3 mt-6">
                <button type="button" @click="closeForm"
                        class="px-4 py-2.5 rounded-xl border border-slate-600/50 text-slate-300 hover:text-white hover:bg-slate-700 transition-all">
                  Cancel
                </button>
                <button type="submit"
                        :disabled="!isFormValid || isSubmitting"
                        :class="[
                          'px-5 py-2.5 rounded-xl font-medium transition-all shadow-lg',
                          isFormValid && !isSubmitting 
                            ? 'bg-indigo-600 text-white hover:bg-indigo-500 shadow-indigo-600/20' 
                            : 'bg-slate-600 text-slate-300 cursor-not-allowed'
                        ]">
                  <span v-if="isSubmitting" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Processing...
                  </span>
                  <span v-else>
                    {{ formMode === 'add' ? 'Add' : 'Update' }}
                  </span>
                </button>
              </div>
            </form>
          </div>
        </div>

        <div v-if="showViewModal" class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm flex items-center justify-center z-50 p-4">
          <div class="bg-gradient-to-b from-slate-800 to-slate-900 rounded-2xl w-full max-w-xl p-6 shadow-2xl border border-slate-700/50 max-h-[90vh] overflow-auto">
            <div class="flex justify-between items-center mb-6 border-b border-slate-700/50 pb-4">
              <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-cyan-500/20 flex items-center justify-center">
                  <EyeIcon class="w-6 h-6 text-cyan-400" />
                </div>
                <h2 class="text-xl font-semibold text-white">
                  <span class="text-cyan-400">{{ activeTab.slice(0, -1) }}</span> Details
                </h2>
              </div>
              <button 
                @click="showViewModal = false"
                class="text-slate-400 hover:text-slate-200 hover:bg-slate-700/50 p-2 rounded-lg transition-colors"
              >
                <XMarkIcon class="w-5 h-5" />
              </button>
            </div>

            <div class="space-y-5" v-if="viewingItem">
              <template v-if="activeTab === 'assets'">
                <div class="bg-slate-800/50 backdrop-blur-sm p-5 rounded-xl border border-slate-700/30 space-y-4">
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Name</span>
                    <p class="text-white mt-1.5 font-medium">{{ viewingItem.name }}</p>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Type</span>
                    <p class="text-white mt-1.5">{{ viewingItem.type }}</p>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Location</span>
                    <p class="text-white mt-1.5">{{ viewingItem.location }}</p>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Value</span>
                    <p class="text-white mt-1.5 font-medium">{{ formatCurrency(viewingItem.value) }}</p>
                  </div>
                </div>
              </template>

              <template v-if="activeTab === 'investments'">
                <div class="bg-slate-800/50 backdrop-blur-sm p-5 rounded-xl border border-slate-700/30 space-y-4">
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Investor Name</span>
                    <p class="text-white mt-1.5 font-medium">{{ viewingItem.investor_name }}</p>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Amount</span>
                    <p class="text-white mt-1.5 font-medium">{{ formatCurrency(viewingItem.amount) }}</p>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Investment Date</span>
                    <p class="text-white mt-1.5">{{ formatDate(viewingItem.investment_date) }}</p>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Description</span>
                    <p class="text-white mt-1.5">{{ viewingItem.description || 'No description provided' }}</p>
                  </div>
                </div>
              </template>

              <template v-if="activeTab === 'loans'">
                <div class="bg-slate-800/50 backdrop-blur-sm p-5 rounded-xl border border-slate-700/30 space-y-4">
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Borrower Name</span>
                    <p class="text-white mt-1.5 font-medium">{{ viewingItem.borrower_name }}</p>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Amount</span>
                    <p class="text-white mt-1.5 font-medium">{{ formatCurrency(viewingItem.amount) }}</p>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Loan Date</span>
                    <p class="text-white mt-1.5">{{ formatDate(viewingItem.loan_date) }}</p>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Due Date</span>
                    <p class="text-white mt-1.5">{{ formatDate(viewingItem.due_date) }}</p>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Status</span>
                    <p class="mt-1.5">
                      <span class="px-3 py-1.5 rounded-full text-xs font-medium inline-block"
                            :class="{
                              'bg-amber-500/20 text-amber-400 border border-amber-500/20': viewingItem.status === 'pending',
                              'bg-emerald-500/20 text-emerald-400 border border-emerald-500/20': viewingItem.status === 'paid',
                              'bg-rose-500/20 text-rose-400 border border-rose-500/20': viewingItem.status === 'overdue'
                            }">
                        {{ viewingItem.status.charAt(0).toUpperCase() + viewingItem.status.slice(1) }}
                      </span>
                    </p>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Description</span>
                    <p class="text-white mt-1.5">{{ viewingItem.description || 'No description provided' }}</p>
                  </div>
                </div>
              </template>
            </div>

            <div class="flex justify-end mt-6 pt-4 border-t border-slate-700/50">
              <button 
                @click="showViewModal = false"
                class="px-5 py-2.5 bg-slate-700 text-white rounded-xl hover:bg-slate-600 transition-all shadow-lg flex items-center"
              >
                <XMarkIcon class="w-5 h-5 mr-2" />
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
.financial-details-container {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
}

::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

::-webkit-scrollbar-track {
  background: rgba(15, 23, 42, 0.8);
}

::-webkit-scrollbar-thumb {
  background: rgba(99, 102, 241, 0.3);
  border-radius: 8px;
}

::-webkit-scrollbar-thumb:hover {
  background: rgba(99, 102, 241, 0.5);
}

* {
  transition: background-color 0.2s, border-color 0.2s, color 0.2s, box-shadow 0.2s;
}

.hover-trigger {
  background: transparent;
}
</style>