<script setup>
import { ref, computed } from 'vue'
import html2pdf from 'html2pdf.js'
import { XMarkIcon, ArrowDownTrayIcon, ArrowPathIcon, BuildingLibraryIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  stockData: {
    type: Object,
    required: true
  },
  grnNumber: {
    type: String,
    required: true
  },
  showModal: {
    type: Boolean,
    required: true
  },
  adjustmentQuantity: {
    type: Number,
    required: true
  }
})

const emit = defineEmits(['close'])
const isPrinting = ref(false)

const generatePDF = () => {
  isPrinting.value = true
  const element = document.getElementById('stock-grn-content')
  const opt = {
    margin: [0.3, 0.3, 0.3, 0.3],
    filename: `Stock-GRN-${props.grnNumber}.pdf`,
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
</script>

<template>
  <div v-if="showModal" class="fixed inset-0 bg-black/80 flex items-center justify-center z-50 p-4 backdrop-blur-sm">
    <div class="bg-white rounded-xl w-full max-w-4xl shadow-2xl max-h-[90vh] overflow-auto">
      <div class="flex justify-between items-center p-5 border-b bg-gray-50 rounded-t-xl sticky top-0 z-10">
        <div class="flex items-center">
          <ArrowPathIcon class="w-6 h-6 text-blue-600 mr-2" />
          <h2 class="text-xl font-semibold text-gray-800">Stock Adjustment GRN</h2>
        </div>
        <div class="flex space-x-2">
          <button 
            @click="generatePDF"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 flex items-center transition-colors duration-200 disabled:opacity-70 shadow-sm"
            :disabled="isPrinting"
          >
            <ArrowDownTrayIcon class="w-5 h-5 mr-2" />
            Export PDF
          </button>
          <button 
            @click="$emit('close')"
            class="p-2 text-gray-500 hover:text-gray-700 rounded-md hover:bg-gray-100 transition-colors duration-200"
          >
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>
      </div>

      <div id="stock-grn-content" class="bg-white p-8">
        <div class="flex justify-between items-start mb-8">
          <div class="flex items-center">
            <div class="w-16 h-16 bg-blue-600 rounded-lg flex items-center justify-center mr-4">
              <BuildingLibraryIcon class="w-10 h-10 text-white" />
            </div>
            <div>
              <h1 class="text-2xl font-bold text-gray-800">LAYOS CLOTHING</h1>
              <p class="text-gray-500">Stock Adjustment Receipt</p>
            </div>
          </div>
          <div class="text-right">
            <h2 class="text-xl font-bold text-blue-600">STOCK GRN</h2>
            <p class="text-gray-500">{{ grnNumber }}</p>
            <p class="text-sm text-gray-500 mt-1">Date: {{ currentDate }}</p>
            <p class="text-sm text-gray-500">Time: {{ currentTime }}</p>
          </div>
        </div>

        <div class="mb-8 border rounded-lg p-6 bg-gray-50">
          <h3 class="text-lg font-semibold text-gray-800 mb-4">Adjustment Details</h3>
          <div class="grid grid-cols-2 gap-6">
            <div>
              <p class="text-gray-600 mb-1">Product ID</p>
              <p class="text-lg font-semibold">#{{ stockData.id }}</p>
            </div>
            <div>
              <p class="text-gray-600 mb-1">Location</p>
              <p class="text-lg font-semibold">{{ stockData.location }}</p>
            </div>
            <div>
              <p class="text-gray-600 mb-1">Previous Stock</p>
              <p class="text-lg font-semibold">{{ stockData.quantity - adjustmentQuantity }}</p>
            </div>
            <div>
              <p class="text-gray-600 mb-1">New Stock</p>
              <p class="text-lg font-semibold">{{ stockData.quantity }}</p>
            </div>
            <div>
              <p class="text-gray-600 mb-1">Adjustment Amount</p>
              <p class="text-lg font-semibold" :class="adjustmentQuantity > 0 ? 'text-emerald-600' : 'text-red-600'">
                {{ adjustmentQuantity > 0 ? '+' : '' }}{{ adjustmentQuantity }}
              </p>
            </div>
            <div>
              <p class="text-gray-600 mb-1">Status</p>
              <p class="text-lg font-semibold">{{ stockData.status }}</p>
            </div>
          </div>
        </div>

        <div class="mt-12 grid grid-cols-3 gap-8">
          <div class="text-center">
            <p class="text-gray-600 mb-8">Adjusted By</p>
            <div class="border-t border-gray-300 pt-2">
              <p class="text-sm text-gray-500">Name & Signature</p>
            </div>
          </div>
          <div class="text-center">
            <p class="text-gray-600 mb-8">Verified By</p>
            <div class="border-t border-gray-300 pt-2">
              <p class="text-sm text-gray-500">Name & Signature</p>
            </div>
          </div>
          <div class="text-center">
            <p class="text-gray-600 mb-8">Approved By</p>
            <div class="border-t border-gray-300 pt-2">
              <p class="text-sm text-gray-500">Name & Signature</p>
            </div>
          </div>
        </div>

        <div class="mt-12 pt-6 border-t text-center">
          <p class="text-sm text-gray-500">This is an official stock adjustment record.</p>
          <p class="text-sm text-gray-500">Generated on {{ currentDate }} at {{ currentTime }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@media print {
  #stock-grn-content {
    padding: 0;
  }
  
  @page {
    size: A4;
    margin: 0.5cm;
  }
}
</style>
