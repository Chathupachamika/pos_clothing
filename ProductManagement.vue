export default {
  // ...existing code...
  methods: {
    // ...existing code...
    async handleEditVariation(variationId, updatedData) {
      try {
        console.log('Sending payload:', updatedData); // Debugging payload
        const response = await this.$axios.put(
          `http://localhost:8000/api/v1/product/variations/${variationId}`,
          updatedData
        );
        console.log('Variation updated successfully:', response.data);
        this.$toast.success('Product variation updated successfully!');
      } catch (error) {
        console.error('Error updating variation:', error);
        this.$toast.error(
          error.response?.data?.message || 'Failed to update product variation.'
        );
      }
    },
    // ...existing code...
  },
  // ...existing code...
}
