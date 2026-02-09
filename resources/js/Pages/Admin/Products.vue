<template>
  <AdminLayout>
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-3xl text-gray-900 mb-2">Product Management</h1>
      <p class="text-gray-600">Manage all products</p>
    </div>

    <!-- Actions Bar -->
    <div class="bg-white rounded-lg border border-gray-200 p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="md:col-span-2">
          <div class="relative">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" aria-hidden="true">
              <path d="m21 21-4.34-4.34" />
              <circle cx="11" cy="11" r="8" />
            </svg>
            <input
              type="text"
              placeholder="Search products..."
              v-model="searchValue"
              @input="handleSearchInput"
              class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
        </div>
        <select
          v-model="filterBrand"
          class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="all">All Brands</option>
          <option v-for="brand in brands" :key="brand.id" :value="brand.id">
            {{ brand.name }}
          </option>
        </select>
        <select
          v-model="filterCategory"
          class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="all">All Categories</option>
          <option v-for="category in categories" :key="category.id" :value="category.id">
            {{ category.name }}
          </option>
        </select>        
      </div>
      <div class="mt-4 flex justify-end">
        <button 
          @click="openAddModal"
          class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-plus w-5 h-5" aria-hidden="true">
            <path d="M5 12h14" />
            <path d="M12 5v14" />
          </svg>
          Add Product
        </button>
      </div>
    </div>

    <!-- Products Table -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="px-6 py-3 text-left text-xs text-gray-500 uppercase tracking-wider">Product</th>
              <th class="px-6 py-3 text-left text-xs text-gray-500 uppercase tracking-wider">Brand</th>
              <th class="px-6 py-3 text-left text-xs text-gray-500 uppercase tracking-wider">Category</th>
              <th class="px-6 py-3 text-left text-xs text-gray-500 uppercase tracking-wider">Price</th>
              <th class="px-6 py-3 text-left text-xs text-gray-500 uppercase tracking-wider">Rating</th>
              <th class="px-6 py-3 text-left text-xs text-gray-500 uppercase tracking-wider">Hidden</th>
              <th class="px-6 py-3 text-left text-xs text-gray-500 uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-left text-xs text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="product in filteredProducts" :key="product.id" class="hover:bg-gray-50">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div v-if="product.image_url" class="w-12 h-12 rounded-lg overflow-hidden flex-shrink-0">
                    <img :src="product.image_url" :alt="product.name" class="w-12 h-12 object-cover" />
                  </div>
                  <div v-else class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center text-white flex-shrink-0">
                    {{ product.name.substring(0, 2).toUpperCase() }}
                  </div>
                  <div>
                    <div class="text-sm text-gray-900">{{ product.name }}</div>
                    <div v-if="product.dosage" class="text-xs text-gray-500">{{ product.dosage }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm text-gray-900">{{ product.vendor_name || product.brand_name || '-' }}</div>
              </td>
              <td class="px-6 py-4">
                <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs">
                  {{ product.category_name || '-' }}
                </span>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm text-gray-900">
                  ${{ product.price || '0.00' }}
                  <span v-if="product.original_price && product.original_price > product.price" class="text-xs text-gray-500 line-through ml-2">
                    ${{ product.original_price }}
                  </span>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-1">
                  <svg class="w-4 h-4 fill-yellow-400 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                  <span class="text-sm text-gray-900">{{ product.rating || '0.0' }}</span>
                  <span class="text-xs text-gray-500">({{ product.review_count }})</span>
                </div>
              </td>
              <td class="px-6 py-4">
                <input
                  type="checkbox"
                  :checked="!!product.hidden"
                  @change="toggleHidden(product, $event)"
                  class="h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />
              </td>
              <td class="px-6 py-4">
                <div class="flex flex-wrap gap-2">
                  <span v-if="product.featured" class="bg-blue-100 text-blue-700 px-2 py-1 rounded text-xs">
                    Featured
                  </span>
                  <span v-if="product.lab_tested" class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs">
                    Lab Tested
                  </span>
                  <span v-if="product.first_timer_deals" class="bg-purple-100 text-purple-700 px-2 py-1 rounded text-xs">
                    First-Timer Deals
                  </span>
                  <span v-if="product.original_price && product.original_price > product.price" class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs flex items-center gap-1">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                  </svg>
                  {{ Math.round(((product.original_price - product.price) / product.original_price) * 100) }}% OFF
                </span>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <button
                    @click="openEditModal(product)"
                    class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                    title="Edit"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  <button
                    @click="deleteProduct(product.id)"
                    :disabled="deleteForm.processing"
                    class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors disabled:opacity-50"
                    title="Delete"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="!filteredProducts || filteredProducts.length === 0" class="bg-white rounded-lg border border-gray-200 p-6 text-center text-gray-500">
      No products found.
    </div>

    <!-- Pagination -->
    <div v-if="products.last_page > 1" class="mt-6 flex items-center justify-between bg-white rounded-lg border border-gray-200 p-4">
      <div class="text-sm text-gray-600">
        Showing {{ products.from || 0 }} to {{ products.to || 0 }} of {{ products.total || 0 }} products
      </div>
      <div class="flex gap-2">
        <button
          @click="goToPage(products.current_page - 1)"
          :disabled="products.current_page === 1"
          class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Previous
        </button>
        <button
          @click="goToPage(products.current_page + 1)"
          :disabled="products.current_page === products.last_page"
          class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Next
        </button>
      </div>
    </div>

    <!-- Edit Product Modal -->
    <div
      v-if="showEditModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="closeEditModal"
    >
      <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200">
          <h2 class="text-2xl text-gray-900">{{ isEditMode ? 'Edit Product' : 'Add Product' }}</h2>
        </div>

        <form @submit.prevent="submitEdit" class="p-6 space-y-6">
            <!-- Product Name -->
            <div>
              <label class="block text-sm text-gray-700 mb-2">
                Product Name *
              </label>
              <input
                v-model="editForm.name"
                type="text"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Brand -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm text-gray-700 mb-2">
                  Brand *
                </label>
                <select
                  v-model="editForm.brand_id"
                  required
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="">Select Brand</option>
                  <option v-for="brand in brands" :key="brand.id" :value="brand.id">
                    {{ brand.name }}
                  </option>
                </select>
              </div>

              <!-- Category -->
              <div>
                <label class="block text-sm text-gray-700 mb-2">
                  Category *
                </label>
                <select
                  v-model="editForm.product_category_id"
                  required
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="">Select Category</option>
                  <option v-for="category in categories" :key="category.id" :value="category.id">
                    {{ category.name }}
                  </option>
                </select>
              </div>
            </div>


            <!-- Price -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm text-gray-700 mb-2">
                  Price *
                </label>
                <input
                  v-model="editForm.price"
                  type="number"
                  step="0.01"
                  min="0"
                  required
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>

              <!-- Dosage -->
              <div>
                <label class="block text-sm text-gray-700 mb-2">
                  Dosage *
                </label>
                <input
                  v-model="editForm.size_mg"
                  type="text"
                  required
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
            </div>

            <!-- On Sale -->
            <div class="border-t border-gray-200 pt-4">
              <label for="on_sale" class="flex items-center gap-2 mb-4">
                <input
                  v-model="editForm.on_sale"
                  type="checkbox"
                  id="on_sale"
                  class="h-4 w-4 text-blue-600"
                />
                <span class="text-sm text-gray-700">On Sale</span>
              </label>
              <div v-if="editForm.on_sale" class="grid grid-cols-2 gap-4">
                <!-- Original Price (shown when On Sale is checked) -->
                <div>
                  <label class="block text-sm text-gray-700 mb-2">
                    Original Price
                  </label>
                  <input
                    v-model="editForm.original_price"
                    type="number"
                    step="0.01"
                    min="0"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
    
                <!-- Discount % (shown when On Sale is checked) -->
                <div>
                  <label class="block text-sm text-gray-700 mb-2">
                    Discount %
                  </label>
                  <input
                    v-model="editForm.discount_percent"
                    type="number"
                    step="0.01"
                    min="0"
                    max="100"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    @input="calculateDiscount"
                  />
                </div>
              </div>
            </div>

            <!-- Purity -->
            <div class="border-t border-gray-200 pt-4">
              <label class="block text-sm text-gray-700 mb-2">
                Purity (%)
              </label>
              <input
                v-model="editForm.purity"
                type="number"
                step="0.1"
                min="0"
                max="100"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="e.g., 99.2"
              />
            </div>

            <!-- Checkboxes Section -->
            <div class="border-t border-gray-200 pt-4">
              <div class="grid grid-cols-2 gap-4">
                <label class="flex items-center gap-2">
                  <input
                    v-model="editForm.featured"
                    type="checkbox"
                    class="h-4 w-4 text-blue-600"
                  />
                  <span class="text-sm text-gray-700">Featured</span>
                </label>
                <label class="flex items-center gap-2">
                  <input
                    v-model="editForm.hidden"
                    type="checkbox"
                    class="h-4 w-4 text-blue-600"
                  />
                  <span class="text-sm text-gray-700">Hidden</span>
                </label>
                <label class="flex items-center gap-2">
                  <input
                    v-model="editForm.lab_tested"
                    type="checkbox"
                    class="h-4 w-4 text-blue-600"
                  />
                  <span class="text-sm text-gray-700">Lab Tested</span>
                </label>
                <label class="flex items-center gap-2">
                  <input
                    v-model="editForm.first_timer_deals"
                    type="checkbox"
                    class="h-4 w-4 text-blue-600"
                  />
                  <span class="text-sm text-gray-700">First-Timer Deals</span>
                </label>
              </div>
            </div>

            <!-- Error Messages -->
            <div v-if="Object.keys(editForm.errors).length > 0" class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
              <p class="font-medium mb-2">Please fix the following errors:</p>
              <ul class="list-disc list-inside text-sm">
                <li v-for="(error, field) in editForm.errors" :key="field">
                  {{ Array.isArray(error) ? error[0] : error }}
                </li>
              </ul>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
              <button
                type="button"
                @click="closeEditModal"
                class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="editForm.processing"
                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors disabled:opacity-50"
              >
                {{ editForm.processing ? (isEditMode ? 'Saving...' : 'Adding...') : (isEditMode ? 'Save Changes' : 'Add Product') }}
              </button>
            </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Link, router, useForm, usePage } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import { useToast as useVueToastification } from 'vue-toastification'

// Only use toast for manual error messages
// Success messages are handled automatically by Layout component via flash messages
const toast = useVueToastification()
const toastError = (message) => toast.error(message, { timeout: 4000 })

const showEditModal = ref(false)
const editingProduct = ref(null)
const isEditMode = computed(() => editingProduct.value !== null)

const editForm = useForm({
  name: '',
  brand_id: null,
  product_category_id: null,
  price: '',
  size_mg: '',
  on_sale: false,
  original_price: '',
  discount_percent: '',
  purity: '',
  featured: false,
  hidden: false,
  lab_tested: false,
  first_timer_deals: false,
  _token: usePage().props.csrf_token
})

const props = defineProps({
  products: Object,
  brands: {
    type: Array,
    default: () => []
  },
  categories: {
    type: Array,
    default: () => []
  }
})

const searchValue = ref('')
const filterBrand = ref('all')
const filterCategory = ref('all')

const filteredProducts = computed(() => {
  let products = props.products?.data || []
  
  if (filterBrand.value !== 'all') {
    products = products.filter(p => p.brand_id === filterBrand.value || p.vendor_id === filterBrand.value)
  }
  
  if (filterCategory.value !== 'all') {
    products = products.filter(p => p.product_category_id === filterCategory.value || p.category_id === filterCategory.value)
  }
  
  if (searchValue.value) {
    const term = searchValue.value.toLowerCase()
    products = products.filter(p => 
      (p.name && p.name.toLowerCase().includes(term)) ||
      (p.vendor_name && p.vendor_name.toLowerCase().includes(term)) ||
      (p.category_name && p.category_name.toLowerCase().includes(term))
    )
  }
  
  return products
})

let searchTimeout = null

function handleSearchInput() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchData(1)
  }, 500)
}

function fetchData(page = props.products?.current_page || 1) {
  router.get('/admin/products', {
    page,
    per_page: props.products?.per_page || 20,
    search: searchValue.value,
    brand: filterBrand.value !== 'all' ? filterBrand.value : null,
    category: filterCategory.value !== 'all' ? filterCategory.value : null
  }, {
    preserveState: true,
    preserveScroll: true
  })
}

function goToPage(page) {
  if (page >= 1 && page <= props.products.last_page) {
    fetchData(page)
  }
}

const deleteForm = useForm({
  _token: usePage().props.csrf_token
})

const visibilityForm = useForm({
  hidden: false,
  _token: usePage().props.csrf_token
})

function toggleHidden(product, event) {
  const hidden = !!event?.target?.checked
  visibilityForm.hidden = hidden
  visibilityForm.patch(`/admin/products/${product.id}/hidden`, {
    preserveScroll: true,
    onSuccess: () => {
      fetchData()
    },
    onError: () => {
      toastError('Failed to update visibility. Please try again.')
      fetchData()
    }
  })
}

function deleteProduct(id) {
  if (confirm('Are you sure you want to delete this product?')) {
    deleteForm.delete(`/admin/products/${id}`, {
      preserveScroll: true,
      onSuccess: () => {
        fetchData()
      },
      onError: (errors) => {
        console.error('Delete error:', errors)
        toastError('Failed to delete product. Please try again.')
      }
    })
  }
}

function openAddModal() {
  editingProduct.value = null
  editForm.reset()
  editForm.clearErrors()
  showEditModal.value = true
}

function openEditModal(product) {
  editingProduct.value = product
  editForm.name = product.name || ''
  editForm.brand_id = product.brand_id || product.vendor_id || null
  editForm.product_category_id = product.product_category_id || product.category_id || null
  editForm.price = product.price || ''
  editForm.size_mg = product.size_mg || product.dosage || ''
  editForm.purity = product.purity || ''
  editForm.featured = !!product.featured
  editForm.hidden = !!product.hidden
  editForm.lab_tested = !!product.lab_tested
  editForm.first_timer_deals = !!product.first_timer_deals
  
  // Determine if product is on sale
  const hasOriginalPrice = product.original_price && product.original_price > product.price
  editForm.on_sale = hasOriginalPrice
  editForm.original_price = product.original_price || ''
  
  // Calculate discount percent if on sale
  if (hasOriginalPrice && product.original_price && product.price) {
    editForm.discount_percent = Math.round(((product.original_price - product.price) / product.original_price) * 100)
  } else {
    editForm.discount_percent = ''
  }
  
  showEditModal.value = true
}

function closeEditModal() {
  showEditModal.value = false
  editingProduct.value = null
  editForm.reset()
  editForm.clearErrors()
}

function calculateDiscount() {
  if (editForm.original_price && editForm.discount_percent) {
    const discount = (parseFloat(editForm.original_price) * parseFloat(editForm.discount_percent)) / 100
    editForm.price = (parseFloat(editForm.original_price) - discount).toFixed(2)
  }
}

// Watch for changes in original_price or discount_percent when on_sale is true
watch([() => editForm.original_price, () => editForm.discount_percent], () => {
  if (editForm.on_sale && editForm.original_price && editForm.discount_percent) {
    calculateDiscount()
  }
})

// Watch for on_sale changes - clear sale fields when unchecked
watch(() => editForm.on_sale, (isOnSale) => {
  if (!isOnSale) {
    editForm.original_price = ''
    editForm.discount_percent = ''
  }
})

function submitEdit() {
  // Prepare data for submission
  const formData = {
    name: editForm.name,
    brand_id: editForm.brand_id,
    product_category_id: editForm.product_category_id,
    price: parseFloat(editForm.price),
    size_mg: editForm.size_mg,
    purity: editForm.purity ? parseFloat(editForm.purity) : null,
    featured: editForm.featured,
    hidden: editForm.hidden,
    lab_tested: editForm.lab_tested,
    first_timer_deals: editForm.first_timer_deals,
    _token: editForm._token
  }
  
  // If on sale, set original_price or calculate from discount
  if (editForm.on_sale) {
    if (editForm.original_price) {
      formData.original_price = parseFloat(editForm.original_price)
    } else if (editForm.discount_percent && editForm.price) {
      // Calculate original price from discount percent
      const price = parseFloat(editForm.price)
      const discountPercent = parseFloat(editForm.discount_percent)
      formData.original_price = (price / (1 - discountPercent / 100)).toFixed(2)
    }
  } else {
    // If not on sale, clear original_price
    formData.original_price = null
  }
  
  if (isEditMode.value) {
    // Update existing product
    editForm.transform(() => formData).put(`/admin/products/${editingProduct.value.id}`, {
      preserveScroll: true,
      onSuccess: () => {
        closeEditModal()
        fetchData()
      },
      onError: (errors) => {
        console.error('Update error:', errors)
        toastError('Failed to update product. Please check the form for errors.')
      }
    })
  } else {
    // Create new product
    editForm.transform(() => formData).post('/admin/products', {
      preserveScroll: true,
      onSuccess: () => {
        closeEditModal()
        fetchData()
      },
      onError: (errors) => {
        console.error('Create error:', errors)
        toastError('Failed to create product. Please check the form for errors.')
      }
    })
  }
}
</script>
