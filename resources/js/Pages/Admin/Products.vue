<template>
  <AdminLayout>
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-3xl text-slate-900 mb-2">Product Management</h1>
      <p class="text-slate-600">Manage all products</p>
    </div>

    <!-- Actions Bar -->
    <div class="bg-white rounded-lg border border-slate-200 p-4 mb-6">
      <div class="flex items-center justify-between gap-4">
        <div class="flex-1 relative">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input
            type="text"
            placeholder="Search products..."
            v-model="searchValue"
            @input="handleSearchInput"
            class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        <div class="flex items-center gap-4">
          <select
            v-model="filterBrand"
            class="px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="all">All Brands</option>
            <option v-for="brand in brands" :key="brand.id" :value="brand.id">
              {{ brand.name }}
            </option>
          </select>
          <select
            v-model="filterCategory"
            class="px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="all">All Categories</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">
              {{ category.name }}
            </option>
          </select>
        </div>
      </div>
    </div>

    <!-- Products Table -->
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
              <th class="px-6 py-3 text-left text-xs text-slate-500 uppercase tracking-wider">Product</th>
              <th class="px-6 py-3 text-left text-xs text-slate-500 uppercase tracking-wider">Brand</th>
              <th class="px-6 py-3 text-left text-xs text-slate-500 uppercase tracking-wider">Category</th>
              <th class="px-6 py-3 text-left text-xs text-slate-500 uppercase tracking-wider">Price</th>
              <th class="px-6 py-3 text-left text-xs text-slate-500 uppercase tracking-wider">Rating</th>
              <th class="px-6 py-3 text-left text-xs text-slate-500 uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-left text-xs text-slate-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            <tr v-for="product in filteredProducts" :key="product.id" class="hover:bg-slate-50">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div v-if="product.image_url" class="w-12 h-12 rounded-lg overflow-hidden flex-shrink-0">
                    <img :src="product.image_url" :alt="product.name" class="w-12 h-12 object-cover" />
                  </div>
                  <div v-else class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center text-white flex-shrink-0">
                    {{ product.name.substring(0, 2).toUpperCase() }}
                  </div>
                  <div>
                    <div class="text-sm text-slate-900">{{ product.name }}</div>
                    <div v-if="product.dosage" class="text-xs text-slate-500">{{ product.dosage }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm text-slate-900">{{ product.vendor_name || product.brand_name || '-' }}</div>
              </td>
              <td class="px-6 py-4">
                <span class="bg-slate-100 text-slate-700 px-2 py-1 rounded text-xs">
                  {{ product.category_name || '-' }}
                </span>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm text-slate-900">
                  ${{ product.price || '0.00' }}
                  <span v-if="product.original_price && product.original_price > product.price" class="text-xs text-slate-500 line-through ml-2">
                    ${{ product.original_price }}
                  </span>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-1">
                  <svg class="w-4 h-4 fill-yellow-400 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                  <span class="text-sm text-slate-900">{{ product.rating || '0.0' }}</span>
                  <span v-if="product.review_count" class="text-xs text-slate-500">({{ product.review_count }})</span>
                </div>
              </td>
              <td class="px-6 py-4">
                <span v-if="product.original_price && product.original_price > product.price" class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs flex items-center gap-1 w-fit">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                  </svg>
                  {{ Math.round(((product.original_price - product.price) / product.original_price) * 100) }}% OFF
                </span>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <Link :href="`/admin/products/${product.id}/edit`" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </Link>
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

    <div v-if="!filteredProducts || filteredProducts.length === 0" class="bg-white rounded-lg border border-slate-200 p-6 text-center text-slate-500">
      No products found.
    </div>

    <!-- Pagination -->
    <div v-if="products.last_page > 1" class="mt-6 flex items-center justify-between bg-white rounded-lg border border-slate-200 p-4">
      <div class="text-sm text-slate-600">
        Showing {{ products.from || 0 }} to {{ products.to || 0 }} of {{ products.total || 0 }} products
      </div>
      <div class="flex gap-2">
        <button
          @click="goToPage(products.current_page - 1)"
          :disabled="products.current_page === 1"
          class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Previous
        </button>
        <button
          @click="goToPage(products.current_page + 1)"
          :disabled="products.current_page === products.last_page"
          class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Next
        </button>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router, useForm, usePage } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import { useToast as useVueToastification } from 'vue-toastification'

// Only use toast for manual error messages
// Success messages are handled automatically by Layout component via flash messages
const toast = useVueToastification()
const toastError = (message) => toast.error(message, { timeout: 4000 })

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
</script>
