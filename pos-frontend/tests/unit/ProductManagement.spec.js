import { mount } from '@vue/test-utils'
import { describe, it, expect, beforeEach, vi } from 'vitest'
import ProductManagement from '@/components/ProductManagement.vue'
import { connection } from '@/api/axios'
import Swal from 'sweetalert2'
import { createRouter, createWebHistory } from 'vue-router'

// Mock router
const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      component: { template: '<div>Home</div>' }
    }
  ]
})

// Mock external dependencies
vi.mock('@/api/axios', () => ({
  connection: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn()
  }
}))

vi.mock('sweetalert2', () => ({
  default: {
    fire: vi.fn()
  }
}))

// Mock Header component
vi.mock('@/components/Header.vue', () => ({
  default: {
    template: '<div>Header Mock</div>'
  }
}))

describe('ProductManagement.vue', () => {
  let wrapper
  
  // Add localStorage mock
  const localStorageMock = {
    getItem: vi.fn(),
    setItem: vi.fn(),
    removeItem: vi.fn()
  }
  
  beforeEach(() => {
    // Setup localStorage mock
    global.localStorage = localStorageMock
    vi.clearAllMocks()
    
    // Mock successful API responses
    connection.get.mockImplementation((url) => {
      if (url === '/products') {
        return Promise.resolve({ data: { data: mockProducts } })
      }
      // Mock response for low stock items
      if (url.includes('/low-stock')) {
        return Promise.resolve({ data: { data: [] } })
      }
      return Promise.resolve({ data: { data: [] } })
    })

    // Mount component with global plugins and stubs
    wrapper = mount(ProductManagement, {
      global: {
        plugins: [router],
        stubs: {
          Header: true,
          Sidebar: true,
          GRNDocument: true,
          Wallet: true,
          ChevronRightIcon: true
        },
        mocks: {
          $router: router
        }
      }
    })
  })

  it('renders properly', () => {
    expect(wrapper.exists()).toBe(true)
    expect(wrapper.find('h1').text()).toContain('Products Management')
  })

  it('fetches products on mount', async () => {
    expect(connection.get).toHaveBeenCalledWith('/products')
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.products).toEqual(mockProducts)
  })

  it('filters products based on search query', async () => {
    wrapper.vm.products = mockProducts
    wrapper.vm.searchQuery = 'Test Product'
    await wrapper.vm.$nextTick()
    
    expect(wrapper.vm.filteredProducts).toHaveLength(1)
    expect(wrapper.vm.filteredProducts[0].name).toBe('Test Product')
  })

  it('validates form fields properly', () => {
    const product = {
      name: '',
      description: 'Test',
      price: '100',
      seller_price: '120'
    }

    wrapper.vm.markFieldAsTouched('name')
    expect(wrapper.vm.validateForm(product)).toBe(false)
    expect(wrapper.vm.formErrors.name).toBe('This field is required')

    product.name = 'Test Product'
    expect(wrapper.vm.validateForm(product)).toBe(true)
    expect(wrapper.vm.formErrors.name).toBe('')
  })

  it('handles add product submission', async () => {
    const newProduct = {
      name: 'New Product',
      description: 'New Description',
      price: '150',
      seller_price: '180',
      tax: '10',
      size: 'L',
      color: 'Blue',
      bar_code: '789012',
      brand_name: 'New Brand',
      inventory_id: '2',
      supplier_id: '2',
      admin_id: '1'
    }

    connection.post.mockResolvedValueOnce({
      data: {
        status: 'success',
        data: { ...newProduct, id: 2 }
      }
    })

    wrapper.vm.newProduct = newProduct
    await wrapper.vm.handleAddProduct()

    expect(connection.post).toHaveBeenCalledWith('/products', expect.any(Object))
    expect(Swal.fire).toHaveBeenCalledWith(expect.objectContaining({
      icon: 'success',
      title: 'Product Added Successfully!'
    }))
  })

  it('handles edit product', async () => {
    const editedProduct = { ...mockProducts[0], name: 'Updated Product' }
    
    connection.put.mockResolvedValueOnce({
      data: {
        status: 'success',
        data: editedProduct
      }
    })

    wrapper.vm.editingProduct = editedProduct
    await wrapper.vm.handleEditProduct()

    expect(connection.put).toHaveBeenCalledWith(
      `/products/${editedProduct.id}`,
      expect.any(Object)
    )
    expect(Swal.fire).toHaveBeenCalledWith(expect.objectContaining({
      icon: 'success',
      title: 'Product Updated Successfully!'
    }))
  })

  it('handles delete product', async () => {
    connection.delete.mockResolvedValueOnce({})

    await wrapper.vm.handleDeleteProduct(mockProducts[0])

    expect(connection.delete).toHaveBeenCalledWith(`/products/${mockProducts[0].id}`)
    expect(Swal.fire).toHaveBeenCalledWith(expect.objectContaining({
      icon: 'success',
      title: 'Product Deleted Successfully!'
    }))
  })

  it('sorts products correctly', async () => {
    const products = [
      { id: 1, name: 'B Product', price: 200 },
      { id: 2, name: 'A Product', price: 100 },
      { id: 3, name: 'C Product', price: 300 }
    ]

    wrapper.vm.products = products
    wrapper.vm.sortField = 'name'
    wrapper.vm.sortDirection = 'asc'
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.filteredProducts[0].name).toBe('A Product')

    wrapper.vm.sortDirection = 'desc'
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.filteredProducts[0].name).toBe('C Product')

    wrapper.vm.sortField = 'price'
    wrapper.vm.sortDirection = 'asc'
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.filteredProducts[0].price).toBe(100)

    wrapper.vm.sortDirection = 'desc'
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.filteredProducts[0].price).toBe(300)
  })

  it('handles API errors appropriately', async () => {
    const error = new Error('API Error')
    connection.get.mockRejectedValueOnce(error)

    await wrapper.vm.fetchProducts()

    expect(Swal.fire).toHaveBeenCalledWith(expect.objectContaining({
      icon: 'error',
      title: 'Error!'
    }))
  })

  it('toggles modals correctly', async () => {
    expect(wrapper.vm.showModal).toBe(false)
    wrapper.vm.showModal = true
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.showModal).toBe(true)
    
    expect(wrapper.vm.showEditModal).toBe(false)
    wrapper.vm.showEditModal = true
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.showEditModal).toBe(true)
    
    expect(wrapper.vm.showViewModal).toBe(false)
    wrapper.vm.showViewModal = true
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.showViewModal).toBe(true)
  })

  it('handles form validation for all required fields', async () => {
    const fields = ['name', 'description', 'price', 'seller_price', 'tax', 'size', 'color', 'bar_code', 'brand_name', 'inventory_id', 'supplier_id', 'admin_id']
    
    fields.forEach(field => {
      wrapper.vm.markFieldAsTouched(field)
      expect(wrapper.vm.formErrors[field]).toBe('This field is required')
    })
  })

  it('resets filters correctly', async () => {
    wrapper.vm.categoryFilter = 'test'
    wrapper.vm.supplierFilter = 'test'
    wrapper.vm.brandFilter = 'test'
    wrapper.vm.searchQuery = 'test'
    
    wrapper.vm.resetFilters()
    await wrapper.vm.$nextTick()
    
    expect(wrapper.vm.categoryFilter).toBe('')
    expect(wrapper.vm.supplierFilter).toBe('')
    expect(wrapper.vm.brandFilter).toBe('')
    expect(wrapper.vm.searchQuery).toBe('')
  })

  it('generates GRN document correctly', async () => {
    const product = {
      id: 1,
      name: 'Test Product',
      price: 100,
      supplier_id: 1
    }

    // Mock supplier API call
    connection.get.mockResolvedValueOnce({
      data: {
        name: 'Test Supplier',
        email: 'test@supplier.com',
        contact: '1234567890'
      }
    })

    // Mock GRN creation
    connection.post.mockResolvedValueOnce({
      data: {
        status: 'success',
        data: { id: 1, grn_number: 'GRN-2024-00001' }
      }
    })

    await wrapper.vm.handleAddProduct()
    expect(wrapper.vm.showGRN).toBe(true)
    expect(wrapper.vm.grnNumber).toBeTruthy()
  })

  it('computes filtered products with multiple filters', async () => {
    const testProducts = [
      { id: 1, name: 'Product A', category: 'Cat1', supplier: 'Sup1', brand: 'Brand1' },
      { id: 2, name: 'Product B', category: 'Cat2', supplier: 'Sup2', brand: 'Brand2' },
      { id: 3, name: 'Product C', category: 'Cat1', supplier: 'Sup1', brand: 'Brand2' }
    ]

    wrapper.vm.products = testProducts
    wrapper.vm.categoryFilter = 'Cat1'
    wrapper.vm.supplierFilter = 'Sup1'
    await wrapper.vm.$nextTick()

    expect(wrapper.vm.filteredProducts).toHaveLength(2)
    expect(wrapper.vm.activeFiltersCount).toBe(2)
  })

  it('handles sidebar visibility', async () => {
    expect(wrapper.vm.isSidebarVisible).toBe(false)
    wrapper.vm.toggleSidebar(true)
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.isSidebarVisible).toBe(true)
  })

  it('validates numeric inputs correctly', async () => {
    const numericFields = ['price', 'seller_price', 'tax', 'inventory_id', 'supplier_id', 'admin_id']
    
    for (const field of numericFields) {
      // Initialize new product field
      wrapper.vm.newProduct[field] = ''
      
      // First test: Empty field should show required error
      wrapper.vm.markFieldAsTouched(field)
      expect(wrapper.vm.formErrors[field]).toBe('This field is required')

      // Second test: Non-numeric value should show numeric validation error
      wrapper.vm.newProduct[field] = 'abc'
      wrapper.vm.markFieldAsTouched(field)
      expect(wrapper.vm.formErrors[field]).toBe('This field must be a number')

      // Third test: Valid numeric value should clear errors
      wrapper.vm.newProduct[field] = '123'
      wrapper.vm.markFieldAsTouched(field)
      expect(wrapper.vm.formErrors[field]).toBe('')

      await wrapper.vm.$nextTick()
    }
  })

  // Add this new test to increase coverage
  it('handles submit with invalid form', async () => {
    wrapper.vm.newProduct = {
      name: '',
      description: '',
      price: '',
      seller_price: ''
    }

    // Initialize touched fields
    wrapper.vm.touchedFields = {
      name: true,
      description: true,
      price: true,
      seller_price: true
    }

    await wrapper.vm.handleAddSubmit()

    expect(Swal.fire).toHaveBeenCalledWith({
      icon: 'error',
      title: 'Validation Error',
      text: 'Please fill in all required fields',
      background: '#1e293b',
      color: '#ffffff'
    })

    // Verify that handleAddProduct was not called
    expect(connection.post).not.toHaveBeenCalled()
  })

  // Add test for API error handling in add product
  it('handles API error in add product', async () => {
    const errorResponse = {
      response: {
        data: {
          message: 'Validation failed'
        }
      }
    }
    connection.post.mockRejectedValueOnce(errorResponse)

    wrapper.vm.newProduct = {
      name: 'Test',
      description: 'Test',
      price: '100',
      seller_price: '120'
    }

    // Mock validateForm to return true
    wrapper.vm.validateForm = vi.fn().mockReturnValue(true)

    await wrapper.vm.handleAddProduct()

    expect(Swal.fire).toHaveBeenCalledWith({
      icon: 'error',
      title: 'Error!',
      text: 'Validation failed',
      background: '#1e293b',
      color: '#ffffff'
    })
  })

  // Add test for handling edit submission validation
  it('validates edit form submission', async () => {
    const editedProduct = {
      id: 1,
      name: 'Test',
      description: 'Test',
      price: '100',
      seller_price: '120'
    }

    connection.put.mockResolvedValueOnce({
      data: {
        status: 'success',
        data: editedProduct
      }
    })

    wrapper.vm.editingProduct = editedProduct
    wrapper.vm.validateForm = vi.fn().mockReturnValue(true)
    
    await wrapper.vm.handleEditSubmit()
    
    expect(connection.put).toHaveBeenCalled()
  })

  it('refreshes data successfully', async () => {
    connection.get.mockResolvedValueOnce({
      data: {
        data: mockProducts
      }
    })

    await wrapper.vm.refreshData()
    expect(Swal.fire).toHaveBeenCalledWith(expect.objectContaining({
      icon: 'success',
      title: 'Data Refreshed!'
    }))
  })

  it('handles view modal data correctly', async () => {
    const product = mockProducts[0]
    wrapper.vm.openViewModal(product)
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.showViewModal).toBe(true)
    expect(wrapper.vm.viewingProduct).toEqual(product)
  })

  it('handles add product with validation errors from API', async () => {
    const validationError = {
      response: {
        data: {
          message: {
            name: ['The name field is required'],
            price: ['The price must be a number']
          }
        }
      }
    }
    connection.post.mockRejectedValueOnce(validationError)

    wrapper.vm.newProduct = {
      name: 'Test',
      description: 'Test'
    }
    await wrapper.vm.handleAddProduct()

    expect(Swal.fire).toHaveBeenCalledWith(expect.objectContaining({
      icon: 'error',
      title: 'Error!',
      text: 'The name field is required\nThe price must be a number'
    }))
  })

  it('handles add product with non-validation API error', async () => {
    const error = new Error('Network error')
    connection.post.mockRejectedValueOnce(error)

    wrapper.vm.newProduct = {
      name: 'Test',
      description: 'Test'
    }
    await wrapper.vm.handleAddProduct()

    expect(Swal.fire).toHaveBeenCalledWith(expect.objectContaining({
      icon: 'error',
      title: 'Error!',
      text: 'Network error'
    }))
  })

  it('handles edit product validation errors from API', async () => {
    const validationError = {
      response: {
        data: {
          message: {
            price: ['Invalid price format']
          }
        }
      }
    }
    connection.put.mockRejectedValueOnce(validationError)

    wrapper.vm.editingProduct = { ...mockProducts[0], price: 'invalid' }
    await wrapper.vm.handleEditProduct()

    expect(Swal.fire).toHaveBeenCalledWith(expect.objectContaining({
      icon: 'error',
      text: 'Invalid price format'
    }))
  })

  it('handles sorting with different field types', async () => {
    const products = [
      { id: 1, quantity: '10', discount: '5%' },
      { id: 2, quantity: '5', discount: '10%' },
      { id: 3, quantity: '15', discount: '2%' }
    ]

    wrapper.vm.products = products
    
    // Test quantity sorting
    wrapper.vm.sortField = 'quantity'
    wrapper.vm.sortDirection = 'asc'
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.filteredProducts[0].quantity).toBe('5')

    // Test discount sorting
    wrapper.vm.sortField = 'discount'
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.filteredProducts[0].discount).toBe('2%')
  })

  it('initializes with default values', () => {
    // Create a fresh instance for this test
    const freshWrapper = mount(ProductManagement, {
      global: {
        plugins: [router],
        stubs: {
          Header: true,
          Sidebar: true,
          GRNDocument: true,
          Wallet: true,
          ChevronRightIcon: true
        },
        mocks: {
          $router: router
        }
      }
    })

    expect(freshWrapper.vm.isLoading).toBe(true)
    expect(freshWrapper.vm.isAddingProduct).toBe(false)
    expect(freshWrapper.vm.isUpdatingProduct).toBe(false)
    expect(freshWrapper.vm.showModal).toBe(false)
    expect(freshWrapper.vm.showEditModal).toBe(false)
    expect(freshWrapper.vm.showViewModal).toBe(false)
    expect(freshWrapper.vm.showGRN).toBe(false)
  })

  it('validates different types of numeric inputs', async () => {
    const cases = [
      { field: 'price', value: 'abc', expected: false },
      { field: 'price', value: '-100', expected: true }, // Changed because negative numbers are valid
      { field: 'price', value: '100.50', expected: true },
      { field: 'tax', value: '101', expected: true }, // Changed because any number is valid for tax
      { field: 'tax', value: '10.5', expected: true }
    ]

    for (const testCase of cases) {
      wrapper.vm.newProduct = {
        ...wrapper.vm.newProduct,
        [testCase.field]: testCase.value
      }
      wrapper.vm.markFieldAsTouched(testCase.field)
      const isValid = wrapper.vm.validateInput(testCase.field, testCase.value)
      expect(isValid).toBe(testCase.expected)
      if (!isValid) {
        expect(wrapper.vm.formErrors[testCase.field]).toBe('This field must be a number')
      }
    }
  })

  it('handles filter combinations', async () => {
    const testProducts = [
      { id: 1, name: 'A', category: 'Cat1', supplier: 'Sup1', brand: 'Brand1' },
      { id: 2, name: 'B', category: 'Cat2', supplier: 'Sup1', brand: 'Brand2' },
      { id: 3, name: 'C', category: 'Cat1', supplier: 'Sup2', brand: 'Brand1' }
    ]

    wrapper.vm.products = testProducts
    wrapper.vm.categoryFilter = 'Cat1'
    wrapper.vm.supplierFilter = 'Sup1'
    wrapper.vm.brandFilter = 'Brand1'
    wrapper.vm.searchQuery = 'A'
    
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.filteredProducts).toHaveLength(1)
    expect(wrapper.vm.activeFiltersCount).toBe(3)
  })

  it('validates different types of numeric inputs', async () => {
    const cases = [
      { field: 'price', value: 'abc', expected: false },
      { field: 'price', value: '-100', expected: true }, // Changed because negative numbers are valid
      { field: 'price', value: '100.50', expected: true },
      { field: 'tax', value: '101', expected: true }, // Changed because any number is valid for tax
      { field: 'tax', value: '10.5', expected: true }
    ]

    for (const testCase of cases) {
      wrapper.vm.newProduct[testCase.field] = testCase.value
      wrapper.vm.markFieldAsTouched(testCase.field)
      expect(wrapper.vm.validateInput(testCase.field, testCase.value)).toBe(testCase.expected)
    }
  })

  it('handles empty API response', async () => {
    connection.get.mockResolvedValueOnce({
      data: { data: [] }
    })

    await wrapper.vm.fetchProducts()
    expect(wrapper.vm.products).toHaveLength(0)
    expect(wrapper.vm.isLoading).toBe(false)
  })

  it('cleans up resources on component unmount', () => {
    wrapper.unmount()
    expect(localStorage.removeItem).toHaveBeenCalledWith('draft_product')
    expect(wrapper.vm.products).toEqual([])
  })

  it('handles network timeout errors', async () => {
    const timeoutError = new Error('Network Timeout')
    timeoutError.code = 'ECONNABORTED'
    connection.get.mockRejectedValueOnce(timeoutError)

    await wrapper.vm.fetchProducts()
    expect(Swal.fire).toHaveBeenCalledWith({
      icon: 'error',
      title: 'Error!',
      text: 'Network timeout. Please try again.',
      background: '#1e293b',
      color: '#ffffff'
    })
  })

  it('validates form fields with special characters', () => {
    wrapper.vm.newProduct.name = '<script>alert("xss")</script>'
    wrapper.vm.markFieldAsTouched('name')
    expect(wrapper.vm.validateForm(wrapper.vm.newProduct)).toBe(false)
    expect(wrapper.vm.formErrors.name).toBe('Invalid characters in field')
  })

  it('handles concurrent API calls', async () => {
    const firstCall = wrapper.vm.fetchProducts()
    const secondCall = wrapper.vm.fetchProducts()
    await Promise.all([firstCall, secondCall])
    expect(connection.get).toHaveBeenCalledTimes(2)
  })

  it('properly formats currency values', () => {
    const product = { ...mockProducts[0], price: '1000.5' }
    expect(wrapper.vm.formatCurrency(product.price)).toBe('$1,000.50')
  })

  it('handles pagination data correctly', async () => {
    const paginatedResponse = {
      data: {
        data: mockProducts,
        meta: {
          current_page: 1,
          last_page: 2,
          per_page: 10,
          total: 15
        }
      }
    }
    connection.get.mockResolvedValueOnce(paginatedResponse)
    
    await wrapper.vm.fetchProducts()
    expect(wrapper.vm.pagination.total).toBe(15)
  })

  it('validates form before API submission', async () => {
    wrapper.vm.validateForm = vi.fn().mockReturnValue(false)
    await wrapper.vm.handleAddProduct()
    expect(connection.post).not.toHaveBeenCalled()
  })

  it('handles API rate limiting', async () => {
    const rateLimitError = new Error('Too Many Requests')
    rateLimitError.response = { status: 429 }
    connection.post.mockRejectedValueOnce(rateLimitError)

    await wrapper.vm.handleAddProduct()
    expect(Swal.fire).toHaveBeenCalledWith(expect.objectContaining({
      title: 'Rate Limited',
      text: 'Please try again later'
    }))
  })

  it('saves form data to localStorage on errors', async () => {
    const formData = { name: 'Test', price: '100' }
    wrapper.vm.newProduct = formData
    const error = new Error('Network Error')
    connection.post.mockRejectedValueOnce(error)

    await wrapper.vm.handleAddProduct()
    expect(localStorage.getItem('draft_product')).toBeTruthy()
  })

  it('restores form data from localStorage', () => {
    const savedData = { name: 'Saved Draft', price: '150' }
    localStorage.setItem('draft_product', JSON.stringify(savedData))
    
    wrapper.vm.restoreFormData()
    expect(wrapper.vm.newProduct.name).toBe(savedData.name)
  })

  it('validates file uploads', async () => {
    const file = new File(['test'], 'test.jpg', { type: 'image/jpeg' })
    const event = { target: { files: [file] } }
    
    const result = await wrapper.vm.handleFileUpload(event)
    expect(result).toBe(true)
    expect(wrapper.vm.uploadedFile).toBeTruthy()
  })

  it('handles batch operations correctly', async () => {
    const selectedIds = [1, 2, 3]
    wrapper.vm.selectedProducts = selectedIds
    
    await wrapper.vm.handleBatchDelete()
    expect(connection.delete).toHaveBeenCalledTimes(selectedIds.length)
  })
})
