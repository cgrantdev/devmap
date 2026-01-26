<template>
  <FrontLayout>
    <div class="min-h-screen bg-slate-50">
      <!-- Search Bar Section -->
      <div class="bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <div class="relative max-w-3xl mx-auto">
            <input
              v-model="searchQuery"
              @keyup.enter="performSearch"
              type="text"
              placeholder="Search brands, products, peptides..."
              class="w-full pl-12 pr-4 py-3 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400 text-lg"
            />
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" aria-hidden="true">
              <path d="m21 21-4.34-4.34"></path>
              <circle cx="11" cy="11" r="8"></circle>
            </svg>
          </div>
          <p v-if="query" class="text-center mt-4 text-slate-600">
            Showing results for "<span class="text-slate-900">{{ query }}</span>"
          </p>
        </div>
      </div>
      
      <!-- Tabs -->
      <div class="bg-white border-b border-slate-200 sticky top-[73px] z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <nav class="flex items-center gap-6 overflow-x-auto">
            <button
              @click="setTab('all')"
              :class="[
                'py-4 px-1 border-b-2 transition-colors whitespace-nowrap text-sm border-transparent text-slate-500 hover:text-slate-700',
                tab === 'all'
                  ? 'border-slate-700 text-slate-900'
                  : 'border-transparent text-slate-500 hover:text-slate-700'
              ]"
            >
              All Results ({{ totals.all }})
            </button>
            <button
              @click="setTab('vendors')"
              :class="[
                'py-4 px-1 border-b-2 transition-colors whitespace-nowrap text-sm border-transparent text-slate-500 hover:text-slate-700',
                tab === 'vendors'
                  ? 'border-slate-700 text-slate-900'
                  : 'border-transparent text-slate-500 hover:text-slate-700'
              ]"
            >
              Vendors ({{ totals.vendors }})
            </button>
            <button
              @click="setTab('products')"
              :class="[
                'py-4 px-1 border-b-2 transition-colors whitespace-nowrap text-sm border-transparent text-slate-500 hover:text-slate-700',
                tab === 'products'
                  ? 'border-slate-700 text-slate-900'
                  : 'border-transparent text-slate-500 hover:text-slate-700'
              ]"
            >
              Products ({{ totals.products }})
            </button>
            <button
              @click="setTab('encyclopedia')"
              :class="[
                'py-4 px-1 border-b-2 transition-colors whitespace-nowrap text-sm border-transparent text-slate-500 hover:text-slate-700',
                tab === 'encyclopedia'
                  ? 'border-slate-700 text-slate-900'
                  : 'border-transparent text-slate-500 hover:text-slate-700'
              ]"
            >
              Encyclopedia ({{ totals.encyclopedia }})
            </button>
            <button
              @click="setTab('news')"
              :class="[
                'py-4 px-1 border-b-2 transition-colors whitespace-nowrap text-sm border-transparent text-slate-500 hover:text-slate-700',
                tab === 'news'
                  ? 'border-slate-700 text-slate-900'
                  : 'border-transparent text-slate-500 hover:text-slate-700'
              ]"
            >
              News ({{ totals.news }})
            </button>
          </nav>
        </div>
      </div>

      <!-- Main Content -->
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">        
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
          <!-- Left Column - Filters -->
          <div class="lg:col-span-1">
            <div class="bg-white rounded-lg border border-slate-200 p-6 sticky top-32">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-slate-900">Filters</h3>
              </div>
              <div class="space-y-3">
                <label class="flex items-center gap-2 cursor-pointer">
                  <input
                    type="checkbox"
                    v-model="filters.verified"
                    @change="applyFilters"
                    class="w-4 h-4 text-slate-700 border-slate-300 rounded focus:ring-slate-400"
                  />
                  <span class="text-sm text-slate-700">Verified Only</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                  <input
                    type="checkbox"
                    v-model="filters.in_stock"
                    @change="applyFilters"
                    class="w-4 h-4 text-slate-700 border-slate-300 rounded focus:ring-slate-400"
                  />
                  <span class="text-sm text-slate-700">In Stock</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                  <input
                    type="checkbox"
                    v-model="filters.rating_min"
                    @change="applyFilters"
                    :true-value="4.5"
                    :false-value="0"
                    class="w-4 h-4 text-slate-700 border-slate-300 rounded focus:ring-slate-400"
                  />
                  <span class="text-sm text-slate-700">4.5+ Rating</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                  <input
                    type="checkbox"
                    v-model="filters.usa_based"
                    @change="applyFilters"
                    class="w-4 h-4 text-slate-700 border-slate-300 rounded focus:ring-slate-400"
                  />
                  <span class="text-sm text-slate-700">USA Based</span>
                </label>
              </div>
            </div>
          </div>

          <!-- Right Column - Results -->
          <div class="lg:col-span-3 space-y-8">

            <!-- Results Content -->
            <div v-if="tab === 'all' || tab === 'vendors'">
              <div class="flex items-center justify-between mb-4">
                <h2 class="text-slate-900">Vendors</h2>
                <Link v-if="results.vendors && results.vendors.length > 0" href="/brands" class="text-sm text-slate-600 hover:text-slate-700">
                  View all →
                </Link>
              </div>
              <div v-if="results.vendors && results.vendors.length > 0" class="grid grid-cols-1 gap-4">
                <div
                  v-for="vendor in results.vendors"
                  :key="vendor.id"
                  class="bg-white border border-slate-200 rounded-lg p-6 hover:shadow-md transition-shadow cursor-pointer"
                  @click="router.visit(`/brand/${vendor.slug}/products`)"
                >
                  <div class="flex items-start gap-4">
                    <div class="w-16 h-16 bg-slate-100 rounded-lg flex items-center justify-center text-3xl flex-shrink-0">
                      <VendorIcon :type="vendor.icon" />
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center gap-2 mb-1">
                        <h3 class="text-slate-900">{{ vendor.name }}</h3>
                        <span v-if="vendor.verified" class="px-2 py-0.5 bg-slate-700 text-white text-xs rounded">
                          Verified
                        </span>
                      </div>
                      <div class="flex items-center gap-4 text-sm text-slate-600 mb-3">
                        <div class="flex items-center gap-1">
                          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-star w-4 h-4 fill-yellow-400 text-yellow-400" aria-hidden="true">
                            <path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"></path>
                          </svg>
                          <span>{{ Number(vendor.rating || 0).toFixed(1) }}</span>
                          <span class="text-slate-400">({{ vendor.reviews_count }} reviews)</span>
                        </div>
                        <div class="flex items-center gap-1">
                          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-map-pin w-4 h-4" aria-hidden="true">
                            <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                          </svg>
                          {{ vendor.location }}
                        </div>
                        <div class="flex items-center gap-1">
                          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-package w-4 h-4" aria-hidden="true">
                            <path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"></path>
                            <path d="M12 22V12"></path>
                            <polyline points="3.29 7 12 12 20.71 7"></polyline>
                            <path d="m7.5 4.27 9 5.15"></path>
                          </svg>
                          {{ vendor.products_count }} products
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <p v-else-if="query" class="text-slate-500 text-sm">No vendors found for "{{ query }}"</p>
            </div>

            <div v-if="tab === 'all' || tab === 'products'">
              <div class="flex items-center justify-between mb-4">
                <h2 class="text-slate-900">Products</h2>
                <Link v-if="results.products && results.products.length > 0" href="/products" class="text-sm text-slate-600 hover:text-slate-700">
                  View all →
                </Link>
              </div>
              <div v-if="results.products && results.products.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div
                  v-for="product in results.products"
                  :key="product.id"
                  class="bg-white border border-slate-200 rounded-lg p-6 hover:shadow-md transition-shadow cursor-pointer"
                  @click="router.visit(`/product/${product.slug}/${product.id}`)"
                >
                  <div class="flex items-start justify-between mb-3">
                    <div>
                      <h3 class="text-slate-900 mb-1">{{ product.name }}</h3>
                      <p class="text-sm text-slate-600">{{ product.brand_name }}</p>
                    </div>
                    <span
                      :class="[
                        'px-2 py-0.5 bg-slate-200 text-slate-600 text-xs rounded',
                        product.availability === 'in_stock'
                          ? 'bg-green-100 text-green-800'
                          : 'bg-red-100 text-red-800'
                      ]"
                    >
                      {{ product.availability === 'in_stock' ? 'In Stock' : 'Out of Stock' }}
                    </span>
                  </div>
                  <div class="flex items-center gap-1 mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-star w-4 h-4 fill-yellow-400 text-yellow-400" aria-hidden="true">
                      <path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"></path>
                    </svg>
                    <span class="text-sm text-slate-700">{{ Number(product.rating || 0).toFixed(1) }}</span>
                    <span class="text-sm text-slate-400">({{ product.reviews_count }})</span>
                  </div>
                  <div class="flex items-center justify-between pt-3 border-t border-slate-200">
                    <span class="text-slate-900">${{ Number(product.discount_price || product.price || 0).toFixed(2) }}</span>                    
                    <span v-if="product.tag" class="text-xs px-2 py-1 bg-slate-100 text-slate-600 rounded">{{ product.tag }}</span>
                  </div>
                </div>
              </div>
              <p v-else-if="query" class="text-slate-500 text-sm">No products found for "{{ query }}"</p>
            </div>

            <div v-if="tab === 'all' || tab === 'encyclopedia'">
              <div class="flex items-center justify-between mb-4">
                <h2 class="text-slate-900">Encyclopedia</h2>
                <Link v-if="results.encyclopedia && results.encyclopedia.length > 0" href="/encyclopedia" class="text-sm text-slate-600 hover:text-slate-700">
                  View all →
                </Link>
              </div>
              <div v-if="results.encyclopedia && results.encyclopedia.length > 0" class="grid grid-cols-1 gap-4">
                <div
                  v-for="entry in results.encyclopedia"
                  :key="entry.id"
                  class="bg-white border border-slate-200 rounded-lg p-6 hover:shadow-md transition-shadow cursor-pointer"
                  @click="router.visit(`/encyclopedia?search=${encodeURIComponent(query)}`)"
                >
                  <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-slate-100 rounded-lg flex items-center justify-center flex-shrink-0">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-book-open w-6 h-6 text-slate-600" aria-hidden="true">
                        <path d="M12 7v4"></path>
                        <path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"></path>
                      </svg>
                    </div>
                    <div class="flex-1">
                      <div class="flex items-center gap-2 mb-1">
                        <h3 class="text-slate-900">{{ entry.name }}</h3>
                        <span class="px-2 py-0.5 bg-slate-100 text-slate-600 text-xs rounded">{{ entry.categoryTag }}</span>
                      </div>
                      <p class="text-sm text-slate-600 mb-2">{{ entry.description }}</p>
                      <div class="flex items-center gap-1 text-xs text-slate-500">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up w-3 h-3" aria-hidden="true">
                          <path d="M16 7h6v6"></path>
                          <path d="m22 7-8.5 8.5-5-5L2 17"></path>
                        </svg>
                        <!-- {{ entry.subtitle }}                         -->
                        High popularity
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <p v-else-if="query" class="text-slate-500 text-sm">No encyclopedia entries found for "{{ query }}"</p>
            </div>

            <div v-if="tab === 'all' || tab === 'news'" class="mb-8">
              <div class="flex items-center justify-between mb-4">
                <h2 class="text-slate-900">News & Articles</h2>
                <Link v-if="results.news && results.news.length > 0" href="/news" class="text-sm text-slate-600 hover:text-slate-700">
                  View all →
                </Link>
              </div>
              <div v-if="results.news && results.news.length > 0" class="grid grid-cols-1 gap-4">
                <div
                  v-for="article in results.news"
                  :key="article.id"
                  class="bg-white border border-slate-200 rounded-lg p-6 hover:shadow-md transition-shadow cursor-pointer"
                  @click="router.visit(`/blog/${article.slug}`)"
                >
                  <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-lg bg-slate-100 flex items-center justify-center flex-shrink-0">
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-newspaper w-6 h-6 text-slate-600" aria-hidden="true">
                        <path d="M15 18h-5"></path>
                        <path d="M18 14h-8"></path>
                        <path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"></path>
                        <rect width="8" height="4" x="10" y="6" rx="1"></rect>
                      </svg>
                    </div>
                    <div class="flex-1">
                      <h3 class="text-slate-900 mb-1">{{ article.title }}</h3>
                      <div class="flex items-center gap-3 text-sm text-slate-500">
                        <span class="px-2 py-0.5 bg-slate-100 text-slate-600 text-xs rounded">
                          {{ article.category }}
                        </span>
                        <span>{{ article.source }}</span>
                        <span>•</span>
                        <span>{{ article.published_at }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <p v-else-if="query" class="text-slate-500 text-sm">No news articles found for "{{ query }}"</p>
            </div>

            <!-- No Results -->
            <div v-if="query && (!totals || totals.all === 0)" class="text-center py-12">
              <p class="text-slate-500 text-lg">No results found for "{{ query }}"</p>
              <p class="text-slate-400 text-sm mt-2">Try adjusting your search or filters</p>
            </div>
            
            <!-- Empty State (no query) -->
            <div v-if="!query" class="text-center py-12">
              <p class="text-slate-500 text-lg">Enter a search term to find vendors, products, encyclopedia entries, and news</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </FrontLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import FrontLayout from '../Layouts/FrontLayout.vue'
import VendorIcon from '@/components/VendorIcon.vue'

const props = defineProps({
  query: {
    type: String,
    default: ''
  },
  location: {
    type: String,
    default: 'all'
  },
  tab: {
    type: String,
    default: 'all'
  },
  filters: {
    type: Object,
    default: () => ({
      verified: false,
      in_stock: false,
      rating_min: 0,
      usa_based: false
    })
  },
  results: {
    type: Object,
    default: () => ({
      vendors: [],
      products: [],
      encyclopedia: [],
      news: []
    })
  },
  totals: {
    type: Object,
    default: () => ({
      all: 0,
      vendors: 0,
      products: 0,
      encyclopedia: 0,
      news: 0
    })
  },
})

const searchQuery = ref(props.query || '')
const currentTab = ref(props.tab || 'all')
const currentFilters = ref({ ...props.filters })

const tab = computed(() => currentTab.value)
const filters = computed(() => currentFilters.value)

const setTab = (newTab) => {
  currentTab.value = newTab
  performSearch()
}

const applyFilters = () => {
  performSearch()
}

const performSearch = () => {
  const params = new URLSearchParams()
  
  if (searchQuery.value) {
    params.set('q', searchQuery.value)
  }
  
  if (currentTab.value !== 'all') {
    params.set('tab', currentTab.value)
  }
  
  if (filters.value.verified) {
    params.set('verified', '1')
  }
  
  if (filters.value.in_stock) {
    params.set('in_stock', '1')
  }
  
  if (filters.value.rating_min) {
    params.set('rating_min', filters.value.rating_min)
  }
  
  if (filters.value.usa_based) {
    params.set('usa_based', '1')
  }
  
  router.visit(`/search?${params.toString()}`, {
    preserveState: true,
    preserveScroll: true,
  })
}
</script>
