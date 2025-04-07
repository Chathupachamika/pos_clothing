<script setup>
import { ref, computed, watch } from 'vue'
import html2pdf from 'html2pdf.js'
import { XMarkIcon, ArrowDownTrayIcon, PrinterIcon, CheckCircleIcon, TruckIcon, DocumentTextIcon } from '@heroicons/vue/24/outline'
import axios from 'axios'

const props = defineProps({
  productData: {
    type: Object,
    required: true,
    default: () => ({})
  },
  supplierData: {
    type: Object,
    required: true,
    default: () => ({})
  },
  grnNumber: {
    type: String,
    required: true,
    default: ''
  },
  showModal: {
    type: Boolean,
    required: true
  }
})

const emit = defineEmits(['close'])

watch(() => props.productData, (newVal) => {
  console.log('GRN Product Data:', newVal)
}, { immediate: true })

watch(() => props.grnNumber, (newVal) => {
  console.log('GRN Number:', newVal)
}, { immediate: true })

watch(() => props.productData?.supplierDetails, (newVal) => {
  console.log('Supplier Details:', newVal)
}, { immediate: true })

const isPrinting = ref(false)
const currentStatus = ref('Received')

const generatePDF = () => {
  isPrinting.value = true
  const element = document.getElementById('grn-content')
  const opt = {
    margin: [0.3, 0.3, 0.3, 0.3],
    filename: `GRN-${props.grnNumber}.pdf`,
    image: { type: 'jpeg', quality: 0.98 },
    html2canvas: { scale: 2, useCORS: true },
    jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
  }

  html2pdf().set(opt).from(element).save().then(() => {
    isPrinting.value = false
  })
}

const formatDate = (date) => {
  return new Date(date || Date.now()).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const formatTime = (date) => {
  return new Date(date || Date.now()).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: true
  })
}

const currentDate = formatDate()
const currentTime = formatTime()
const documentId = `GRN-${props.grnNumber}-${new Date().getFullYear()}`

const statusColor = computed(() => {
  switch(currentStatus.value) {
    case 'Received': return 'bg-green-100 text-green-800';
    case 'Pending': return 'bg-yellow-100 text-yellow-800';
    case 'Rejected': return 'bg-red-100 text-red-800';
    default: return 'bg-blue-100 text-blue-800';
  }
})

const orderRef = `ORD-${Math.floor(10000 + Math.random() * 90000)}`

const supplierDetails = ref({
  id: 'N/A',
  name: 'N/A',
  email: 'N/A',
  contact: 'N/A'
});

const fetchSupplierDetails = async (supplierId) => {
  try {
    const response = await axios.get(`/api/suppliers/${supplierId}`);
    supplierDetails.value = {
      id: response.data.id || 'N/A',
      name: response.data.name || 'N/A',
      email: response.data.email || 'N/A',
      contact: response.data.contact || 'N/A'
    };
  } catch (error) {
    console.error('Error fetching supplier details:', error);
    supplierDetails.value = {
      id: 'N/A',
      name: 'N/A',
      email: 'N/A',
      contact: 'N/A'
    };
  }
};

// Call the function when the component is mounted or when supplierData changes
watch(() => props.supplierData?.id, (newSupplierId) => {
  if (newSupplierId) {
    fetchSupplierDetails(newSupplierId);
  }
}, { immediate: true });

const productDetails = computed(() => ({
  name: props.productData?.name || 'N/A',
  description: props.productData?.description || 'N/A',
  brand_name: props.productData?.brand_name || 'N/A',
  size: props.productData?.size || 'N/A',
  color: props.productData?.color || 'N/A',
  price: props.productData?.price || 0,
  category: props.productData?.category || 'N/A',
  location: props.productData?.location || 'N/A',
}))

const productVariations = computed(() => props.productData?.variations || [])
</script>

<template>
  <div v-if="showModal && productData" class="fixed inset-0 bg-black/80 flex items-center justify-center z-50 p-4 backdrop-blur-sm">
    <div class="bg-white rounded-xl w-full max-w-5xl shadow-2xl max-h-[90vh] overflow-auto">
      <div class="flex justify-between items-center p-5 border-b bg-gray-50 rounded-t-xl">
        <div class="flex items-center">
          <DocumentTextIcon class="w-6 h-6 text-blue-600 mr-2" />
          <h2 class="text-xl font-semibold text-gray-800">Goods Received Note</h2>
          <span class="ml-3 px-3 py-1 rounded-full text-xs font-medium" :class="statusColor">
            {{ currentStatus }}
          </span>
        </div>
        <div class="flex space-x-2">
          <button 
            @click="generatePDF"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 flex items-center transition-colors duration-200 disabled:opacity-70 shadow-sm"
            :disabled="isPrinting"
          >
            <span v-if="isPrinting">Processing...</span>
            <template v-else>
              <ArrowDownTrayIcon class="w-5 h-5 mr-2" />
              Export PDF
            </template>
          </button>
          <button 
            @click="$emit('close')"
            class="p-2 text-gray-500 hover:text-gray-700 rounded-md hover:bg-gray-100 transition-colors duration-200"
            aria-label="Close"
          >
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>
      </div>

      <div id="grn-content" class="bg-white p-8">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 pb-6">
          <div class="flex items-center mb-4 md:mb-0">
            <div class="w-16 h-16 bg-blue-600 rounded-lg flex items-center justify-center mr-4 text-white">
              <TruckIcon class="w-10 h-10" />
            </div>
            <div>
              <h1 class="text-2xl font-bold text-gray-800 tracking-tight">LAYOS CLOTHING</h1>
              <p class="text-gray-600 text-sm">Premium Building Materials & Supplies</p>
              <p class="text-gray-500 text-xs mt-1">123 Commerce Boulevard, Industrial District</p>
              <p class="text-gray-500 text-xs">Tel: (123) 456-7890 | support@layosclothing.com</p>
            </div>
          </div>
          <div class="text-right bg-gray-50 p-4 rounded-lg border border-gray-200 w-full md:w-auto">
            <h2 class="text-xl font-bold text-blue-700 mb-1 tracking-tight">GOODS RECEIVED NOTE</h2>
            <div class="flex flex-col text-sm space-y-1 mt-2">
              <div class="flex justify-between">
                <span class="font-medium text-gray-500 mr-3">Document ID:</span>
                <span class="font-semibold text-gray-800">{{ documentId }}</span>
              </div>
              <div class="flex justify-between">
                <span class="font-medium text-gray-500 mr-3">Order Reference:</span>
                <span class="font-semibold text-gray-800">{{ orderRef }}</span>
              </div>
              <div class="flex justify-between">
                <span class="font-medium text-gray-500 mr-3">Date:</span>
                <span class="font-semibold text-gray-800">{{ currentDate }}</span>
              </div>
              <div class="flex justify-between">
                <span class="font-medium text-gray-500 mr-3">Time:</span>
                <span class="font-semibold text-gray-800">{{ currentTime }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-0 print:opacity-5">
          <div class="rotate-45 text-gray-300 text-9xl font-bold tracking-widest">RECEIVED</div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
          <div class="bg-white p-5 rounded-lg border border-gray-200 shadow-sm">
            <h3 class="font-bold text-gray-800 mb-3 pb-2 border-b flex items-center">
              <span class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center mr-2 text-blue-600 text-xs">1</span>
              Receiving Details
            </h3>
            <div class="space-y-3">
              <div class="flex border-b border-gray-100 pb-2">
                <span class="font-medium w-40 text-gray-500">GRN Number:</span>
                <span class="font-semibold text-gray-800">{{ grnNumber }}</span>
              </div>
              <div class="flex border-b border-gray-100 pb-2">
                <span class="font-medium w-40 text-gray-500">Date Received:</span>
                <span class="font-semibold text-gray-800">{{ currentDate }}</span>
              </div>
              <div class="flex border-b border-gray-100 pb-2">
                <span class="font-medium w-40 text-gray-500">Admin ID:</span>
                <span class="font-semibold text-gray-800">{{ productData.admin_id }}</span>
              </div>
              <div class="flex">
                <span class="font-medium w-40 text-gray-500">Receiving Status:</span>
                <span class="px-2 py-1 rounded-full text-xs font-medium" :class="statusColor">
                  {{ currentStatus }}
                </span>
              </div>
            </div>
          </div>
          
          <div class="bg-white p-5 rounded-lg border border-gray-200 shadow-sm">
            <h3 class="font-bold text-gray-800 mb-3 pb-2 border-b flex items-center">
              <span class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center mr-2 text-blue-600 text-xs">2</span>
              Supplier Information
            </h3>
            <div class="space-y-3">
              <div class="flex border-b border-gray-100 pb-2">
                <span class="font-medium w-40 text-gray-500">Supplier ID:</span>
                <span class="font-semibold text-gray-800">{{ supplierDetails.value?.id || 'N/A' }}</span>
              </div>
              <div class="flex border-b border-gray-100 pb-2">
                <span class="font-medium w-40 text-gray-500">Company Name:</span>
                <span class="font-semibold text-gray-800">{{ supplierDetails.value?.name || 'N/A' }}</span>
              </div>
              <div class="flex border-b border-gray-100 pb-2">
                <span class="font-medium w-40 text-gray-500">Email Address:</span>
                <span class="font-semibold text-gray-800">{{ supplierDetails.value?.email || 'N/A' }}</span>
              </div>
              <div class="flex border-b border-gray-100 pb-2">
                <span class="font-medium w-40 text-gray-500">Contact Number:</span>
                <span class="font-semibold text-gray-800">{{ supplierDetails.value?.contact || 'N/A' }}</span>
              </div>
              <div class="flex">
                <span class="font-medium w-40 text-gray-500">Supplier Rating:</span>
                <div class="flex">
                  <span v-for="i in 5" :key="i" class="text-yellow-400">★</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="mb-8">
          <h3 class="font-bold text-gray-800 mb-4 pb-2 border-b flex items-center">
            <span class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center mr-2 text-blue-600 text-xs">3</span>
            Product Details
          </h3>
          <div class="overflow-hidden rounded-lg border border-gray-200 shadow-sm">
            <table class="w-full border-collapse">
              <thead>
                <tr class="bg-gray-50 text-xs uppercase tracking-wider">
                  <th class="border-b border-gray-200 p-4 text-left font-semibold text-gray-600">Image</th>
                  <th class="border-b border-gray-200 p-4 text-left font-semibold text-gray-600">Name</th>
                  <th class="border-b border-gray-200 p-4 text-left font-semibold text-gray-600">Brand</th>
                  <th class="border-b border-gray-200 p-4 text-left font-semibold text-gray-600">Description</th>
                </tr>
              </thead>
              <tbody>
                <tr class="hover:bg-gray-50 transition-colors duration-150">
                  <td class="p-4">
                    <div class="flex items-center">
                      <div class="h-10 w-10 flex-shrink-0 rounded bg-gray-100 flex items-center justify-center mr-3">
                        <span class="text-xs text-gray-500">IMG</span>
                      </div>
                    </div>
                  </td>
                  <td class="p-4 text-gray-800">{{ productDetails.name }}</td>
                  <td class="p-4 text-gray-800">{{ productDetails.brand_name }}</td>
                  <td class="p-4 text-gray-800">{{ productDetails.description }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="mb-8">
          <h3 class="font-bold text-gray-800 mb-3 pb-2 border-b flex items-center">
            <span class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center mr-2 text-blue-600 text-xs">4</span>
            Notes & Comments
          </h3>
          <div class="border border-gray-200 p-4 rounded-lg bg-gray-50 min-h-[80px] shadow-inner">
            <p class="text-gray-500 italic">All items received in good condition. No damages reported.</p>
          </div>
        </div>

        <div class="mb-8">
          <h3 class="font-bold text-gray-800 mb-3 pb-2 border-b flex items-center">
            <span class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center mr-2 text-blue-600 text-xs">5</span>
            Quality Control
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="border border-gray-200 rounded-lg p-4 bg-green-50">
              <div class="flex items-center mb-2">
                <CheckCircleIcon class="h-5 w-5 text-green-500 mr-2" />
                <h4 class="font-semibold text-gray-800">Packaging Condition</h4>
              </div>
              <p class="text-sm text-gray-600">Excellent - No damage</p>
            </div>
            <div class="border border-gray-200 rounded-lg p-4 bg-green-50">
              <div class="flex items-center mb-2">
                <CheckCircleIcon class="h-5 w-5 text-green-500 mr-2" />
                <h4 class="font-semibold text-gray-800">Product Condition</h4>
              </div>
              <p class="text-sm text-gray-600">Excellent - As specified</p>
            </div>
            <div class="border border-gray-200 rounded-lg p-4 bg-green-50">
              <div class="flex items-center mb-2">
                <CheckCircleIcon class="h-5 w-5 text-green-500 mr-2" />
                <h4 class="font-semibold text-gray-800">Quantity Verification</h4>
              </div>
              <p class="text-sm text-gray-600">Correct - All items received</p>
            </div>
          </div>
        </div>

        <div class="mb-8">
          <h3 class="font-bold text-gray-800 mb-4 pb-2 border-b flex items-center">
            <span class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center mr-2 text-blue-600 text-xs">6</span>
            Product Variations
          </h3>
          <div v-if="productVariations.length" class="overflow-hidden rounded-lg border border-gray-200 shadow-sm">
            <table class="w-full border-collapse">
              <thead>
                <tr class="bg-gray-50 text-xs uppercase tracking-wider">
                  <th class="border-b border-gray-200 p-4 text-left font-semibold text-gray-600">Color</th>
                  <th class="border-b border-gray-200 p-4 text-left font-semibold text-gray-600">Size</th>
                  <th class="border-b border-gray-200 p-4 text-center font-semibold text-gray-600">Barcode</th>
                  <th class="border-b border-gray-200 p-4 text-center font-semibold text-gray-600">Quantity</th>
                  <th class="border-b border-gray-200 p-4 text-right font-semibold text-gray-600">Unit Price</th>
                  <th class="border-b border-gray-200 p-4 text-right font-semibold text-gray-600">Total Price</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="variation in productVariations" :key="variation.id" class="hover:bg-gray-50 transition-colors duration-150">
                  <td class="p-4 text-gray-800">{{ variation.color }}</td>
                  <td class="p-4 text-gray-800">{{ variation.size }}</td>
                  <td class="p-4 text-center text-gray-800">{{ variation.barcode || 'N/A' }}</td>
                  <td class="p-4 text-center text-gray-800">{{ variation.quantity }}</td>
                  <td class="p-4 text-right text-gray-800">Rs. {{ Number(variation.price).toFixed(2) }}</td>
                  <td class="p-4 text-right text-gray-800">Rs. {{ (variation.quantity * variation.price).toFixed(2) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <p v-else class="text-gray-500 italic">No variations available for this product.</p>
          <div v-if="productVariations.length" class="mt-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
            <div class="flex justify-between text-sm font-medium text-gray-700">
              <span>Total Variations:</span>
              <span>{{ productVariations.length }}</span>
            </div>
            <div class="flex justify-between text-sm font-medium text-gray-700 mt-2">
              <span>Total Quantity:</span>
              <span>{{ productVariations.reduce((sum, variation) => sum + variation.quantity, 0) }}</span>
            </div>
            <div class="flex justify-between text-sm font-medium text-gray-700 mt-2">
              <span>Total Price:</span>
              <span>Rs. {{ productVariations.reduce((sum, variation) => sum + (variation.quantity * variation.price), 0).toFixed(2) }}</span>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-12">
          <div class="border-t border-gray-200 pt-4">
            <p class="text-center text-sm font-medium text-gray-600 mb-8">Received By</p>
            <p class="text-center font-semibold border-b border-gray-400 pb-1 mx-4">_________________________</p>
            <p class="text-center text-xs text-gray-500 mt-1">Name & Signature</p>
            <p class="text-center text-xs text-gray-500 mt-3">Date: {{ currentDate }}</p>
          </div>
          <div class="border-t border-gray-200 pt-4">
            <p class="text-center text-sm font-medium text-gray-600 mb-8">Quality Checked By</p>
            <p class="text-center font-semibold border-b border-gray-400 pb-1 mx-4">_________________________</p>
            <p class="text-center text-xs text-gray-500 mt-1">Name & Signature</p>
            <p class="text-center text-xs text-gray-500 mt-3">Date: {{ currentDate }}</p>
          </div>
          <div class="border-t border-gray-200 pt-4">
            <p class="text-center text-sm font-medium text-gray-600 mb-8">Approved By</p>
            <p class="text-center font-semibold border-b border-gray-400 pb-1 mx-4">_________________________</p>
            <p class="text-center text-xs text-gray-500 mt-1">Name & Signature</p>
            <p class="text-center text-xs text-gray-500 mt-3">Date: {{ currentDate }}</p>
          </div>
        </div>

        <div class="mt-12 pt-4 border-t border-gray-200">
          <h3 class="text-sm font-semibold text-gray-700 mb-2">Terms & Conditions:</h3>
          <ul class="text-xs text-gray-600 space-y-1 list-disc pl-5">
            <li>All goods received are subject to final inspection and approval by our quality control department.</li>
            <li>Any discrepancies must be reported within 48 hours of receipt.</li>
            <li>This document serves as official confirmation of goods received from the supplier.</li>
            <li>Payment will be processed according to the terms agreed upon in the purchase order.</li>
          </ul>
        </div>

        <div class="mt-8 pt-4 border-t border-gray-200 text-center">
          <div class="flex justify-center items-center mb-2">
            <div class="h-8 w-8 bg-blue-600 rounded-full flex items-center justify-center text-white mr-2">
              <TruckIcon class="w-5 h-5" />
            </div>
            <p class="text-sm font-semibold text-gray-700">LAYOS CLOTHING</p>
          </div>
          <p class="text-xs text-gray-500">This is an official receipt of goods. Please retain for your records.</p>
          <p class="text-xs text-gray-500 mt-1">© {{ new Date().getFullYear() }} LAYOS CLOTHING Inc. All rights reserved.</p>
          <div class="flex justify-center mt-2 space-x-1">
            <div class="h-1 w-1 rounded-full bg-gray-300"></div>
            <div class="h-1 w-1 rounded-full bg-gray-300"></div>
            <div class="h-1 w-1 rounded-full bg-gray-300"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@media print {
  #grn-content {
    padding: 0;
  }
  
  @page {
    size: A4;
    margin: 0.5cm;
  }
}
</style>