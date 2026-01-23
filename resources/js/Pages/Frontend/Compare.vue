<template>
  <FrontLayout>
    <!-- Header Section -->
    <section class="bg-white border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h1 class="text-4xl text-gray-900 mb-4">Compare Products</h1>
        <p class="text-xl text-gray-600">
          Compare peptides side-by-side: prices, purity, reviews, and more.
        </p>
      </div>
    </section>

    <!-- Information Banner -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
        <div class="flex items-start gap-3">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-alert w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" aria-hidden="true">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" x2="12" y1="8" y2="12"></line>
            <line x1="12" x2="12.01" y1="16" y2="16"></line>
          </svg>
          <div>
            <h3 class="text-blue-900 mb-1">How to use Compare</h3>
            <p class="text-sm text-blue-800">
              Search for products below and select up to 4 to compare side-by-side. Great for finding the best deals, highest purity, or comparing vendors!
            </p>
          </div>
        </div>
      </div>

      <!-- Selection Status and Search -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
        <!-- Selection Status -->
        <div class="mb-4">
          <label class="block text-sm text-gray-700 mb-2">
            Selected ({{ selectedProducts.length }}/4)
          </label>
          <p v-if="selectedProducts.length === 0" class="text-sm text-gray-500">
            No products selected yet.
          </p>
          <div v-else class="flex flex-wrap gap-2 mt-2">
            <div
              v-for="product in selectedProducts"
              :key="product.id"
              class="flex items-center gap-2 bg-gray-100 rounded-lg px-3 py-2"
            >
              <span class="text-sm text-gray-700">{{ product.name }}</span>
              <button
                @click="removeProduct(product.id)"
                class="text-gray-500 hover:text-gray-700"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M18 6L6 18"></path>
                  <path d="M6 6l12 12"></path>
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Search Bar -->
        <div class="relative">
          <input
            v-model="searchQuery"
            @input="handleSearch"
            type="text"
            placeholder="Search products to compare..."
            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" aria-hidden="true">
            <path d="m21 21-4.34-4.34"></path>
            <circle cx="11" cy="11" r="8"></circle>
          </svg>
        </div>
      </div>
    </section>

    <!-- Products List -->
    <section class="bg-gray-50 min-h-screen">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div v-if="filteredProducts.length > 0" class="space-y-3">
          <CompareProductItem
            v-for="product in filteredProducts"
            :key="product.id"
            :product="product"
            :is-selected="isSelected(product.id)"
            @toggle="toggleProduct"
          />
        </div>
        <div v-else class="text-center py-12">
          <p class="text-gray-500">No products found. Try a different search term.</p>
        </div>
      </div>
    </section>

    <!-- Comparison Table (shown when products are selected) -->
    <section v-if="selectedProducts.length > 0" class="bg-white border-t border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Comparison</h2>
        <div class="overflow-x-auto">
          <table class="w-full border-collapse">
            <thead>
              <tr class="border-b border-gray-200">
                <th class="text-left py-3 px-4 font-semibold text-gray-900">Feature</th>
                <th
                  v-for="product in selectedProducts"
                  :key="product.id"
                  class="text-center py-3 px-4 font-semibold text-gray-900 min-w-[200px]"
                >
                  {{ product.name }}
                </th>
              </tr>
            </thead>
            <tbody>
              <tr class="border-b border-gray-100">
                <td class="py-3 px-4 font-medium text-gray-700">Price</td>
                <td
                  v-for="product in selectedProducts"
                  :key="product.id"
                  class="py-3 px-4 text-center text-gray-600"
                >
                  ${{ formatPrice(product.discount_price || product.price) }}
                </td>
              </tr>
              <tr class="border-b border-gray-100">
                <td class="py-3 px-4 font-medium text-gray-700">Brand</td>
                <td
                  v-for="product in selectedProducts"
                  :key="product.id"
                  class="py-3 px-4 text-center text-gray-600"
                >
                  {{ product.brand_name || 'N/A' }}
                </td>
              </tr>
              <tr class="border-b border-gray-100">
                <td class="py-3 px-4 font-medium text-gray-700">Purity</td>
                <td
                  v-for="product in selectedProducts"
                  :key="product.id"
                  class="py-3 px-4 text-center text-gray-600"
                >
                  {{ product.purity ? `${product.purity}%` : 'N/A' }}
                </td>
              </tr>
              <tr class="border-b border-gray-100">
                <td class="py-3 px-4 font-medium text-gray-700">Rating</td>
                <td
                  v-for="product in selectedProducts"
                  :key="product.id"
                  class="py-3 px-4 text-center text-gray-600"
                >
                  {{ product.rating_average }}/5 ({{ product.rating_count }} reviews)
                </td>
              </tr>
              <tr class="border-b border-gray-100">
                <td class="py-3 px-4 font-medium text-gray-700">Size</td>
                <td
                  v-for="product in selectedProducts"
                  :key="product.id"
                  class="py-3 px-4 text-center text-gray-600"
                >
                  {{ product.size_mg ? `${product.size_mg}mg` : 'N/A' }}
                </td>
              </tr>
              <tr class="border-b border-gray-100">
                <td class="py-3 px-4 font-medium text-gray-700">Availability</td>
                <td
                  v-for="product in selectedProducts"
                  :key="product.id"
                  class="py-3 px-4 text-center"
                >
                  <span
                    :class="[
                      'px-2 py-1 rounded text-xs font-medium',
                      product.availability === 'in_stock' 
                        ? 'bg-green-100 text-green-700'
                        : 'bg-red-100 text-red-700'
                    ]"
                  >
                    {{ product.availability === 'in_stock' ? 'In Stock' : 'Out of Stock' }}
                  </span>
                </td>
              </tr>
              <tr class="border-b border-gray-100">
                <td class="py-3 px-4 font-medium text-gray-700">Verified</td>
                <td
                  v-for="product in selectedProducts"
                  :key="product.id"
                  class="py-3 px-4 text-center"
                >
                  <span
                    v-if="product.verified"
                    class="px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-700"
                  >
                    Verified
                  </span>
                  <span v-else class="text-gray-400">-</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </FrontLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import FrontLayout from '../Layouts/FrontLayout.vue'
import CompareProductItem from '@/components/CompareProductItem.vue'

const props = defineProps({
  products: {
    type: Array,
    default: () => []
  },
  selectedProducts: {
    type: Array,
    default: () => []
  },
  search: {
    type: String,
    default: ''
  },
  selectedIds: {
    type: Array,
    default: () => []
  }
})

const searchQuery = ref(props.search || '')
const selectedProductIds = ref([...props.selectedIds])

// Get selected products from props (these are fetched from server)
const selectedProducts = computed(() => {
  return props.selectedProducts || []
})

// Filter products (exclude already selected ones from search results)
const filteredProducts = computed(() => {
  let result = props.products || []
  
  // Filter out already selected products
  result = result.filter(p => !selectedProductIds.value.includes(p.id))
  
  // Apply search if needed
  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase().trim()
    result = result.filter(p => 
      p.name.toLowerCase().includes(query) ||
      (p.brand_name && p.brand_name.toLowerCase().includes(query))
    )
  }
  
  return result
})

const isSelected = (productId) => {
  return selectedProductIds.value.includes(productId)
}

const toggleProduct = (product) => {
  const index = selectedProductIds.value.indexOf(product.id)
  
  if (index > -1) {
    // Remove if already selected
    selectedProductIds.value.splice(index, 1)
  } else {
    // Add if not selected and under limit
    if (selectedProductIds.value.length < 4) {
      selectedProductIds.value.push(product.id)
    }
  }
  
  updateURL()
}

const removeProduct = (productId) => {
  const index = selectedProductIds.value.indexOf(productId)
  if (index > -1) {
    selectedProductIds.value.splice(index, 1)
    updateURL()
  }
}

const handleSearch = () => {
  updateURL()
}

const updateURL = () => {
  const params = new URLSearchParams()
  if (searchQuery.value.trim()) {
    params.set('search', searchQuery.value.trim())
  }
  if (selectedProductIds.value.length > 0) {
    params.set('selected', selectedProductIds.value.join(','))
  }
  
  const queryString = params.toString()
  router.visit(`/compare${queryString ? '?' + queryString : ''}`, {
    preserveState: true,
    preserveScroll: true,
    replace: true
  })
}

const formatPrice = (price) => {
  return parseFloat(price || 0).toFixed(2)
}
</script>
