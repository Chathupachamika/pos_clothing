<script setup>
import { ref } from 'vue';
import GRNDocument from './GRNDocument.vue';

const showGRNModal = ref(false);
const selectedProduct = ref(null);

const handleShowGRN = (product) => {
  selectedProduct.value = product;
  showGRNModal.value = true;
};
</script>

<template>
  <table>
    <thead>
      <tr>
        <th>Name</th>
        <th>Category</th>
        <th>Price</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="product in products" :key="product.id">
        <td>{{ product.name }}</td>
        <td>{{ product.category }}</td>
        <td>{{ product.price }}</td>
        <td class="text-center">
          <button 
            @click="handleShowGRN(product)" 
            class="p-2 text-blue-600 hover:text-blue-800 transition-colors duration-200"
            aria-label="Show GRN Document"
          >
            <DocumentTextIcon class="w-5 h-5" />
          </button>
        </td>
      </tr>
    </tbody>
  </table>

  <GRNDocument 
    v-if="showGRNModal" 
    :productData="selectedProduct" 
    :grnNumber="`GRN-${selectedProduct?.id}`" 
    :showModal="showGRNModal" 
    @close="showGRNModal = false" 
  />
</template>