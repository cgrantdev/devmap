<template>
  <FrontLayout>
    <!-- Hero Section -->
    <section class="pt-0 pb-16 bg-white">
      <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-[1360px] h-[334px] rounded-[24px] overflow-hidden relative mx-auto">
          <!-- Background Image -->
          <div 
            ref="heroBgRef"
            class="absolute inset-0 bg-cover bg-center bg-no-repeat"
            data-bg-image="/images/hero/hero1.jpg"
            :style="{ backgroundImage: heroBgLoaded ? `url(/images/hero/hero1.jpg)` : 'none' }"
          >
            <div 
              class="absolute inset-0 pointer-events-none"
              :style="{ background: 'linear-gradient(260deg, rgba(0, 0, 0, 0.4) 45.49%, rgba(0, 0, 0, 0.81) 79.33%)' }"
            ></div>
          </div>
          
          <!-- Content -->
          <!-- Content -->
          <div class="relative p-6 h-full flex flex-col justify-center max-w-[800px] gap-4">
            <div class="flex flex-col gap-3">
              <h1 class="font-hv-muse font-normal text-[52px] leading-loose tracking-normal text-white m-0">Harmony Through Discovery</h1>
              <p class="font-roboto font-normal text-lg leading-loose tracking-normal text-white m-0">Everything You Need to Know About Peptides</p>
            </div>
            <MainButton
              text="Read Details"
              to="/education"
              size="custom"
              bg-color="white"
            />
          </div>
        </div>
      </div>
    </section>

    <!-- Product Listing Section -->
    <section class="py-16 bg-white">
      <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Product Name Title -->
        <h2 class="font-hv-muse font-normal text-center text-5xl leading-normal tracking-normal text-gray-800 m-0 mb-6">{{ productName }}</h2>

        <!-- Main Content Area (Full Width) -->
        <div class="w-full">
            <!-- Search Bar, Item Count, and Sort (First Row) -->
            <div class="mb-4 flex items-center gap-4">
              <!-- Search Bar -->
              <div class="relative flex-1 min-w-[200px] max-w-md">
                <input
                  v-model="searchQuery"
                  @input="handleSearchInput"
                  type="text"
                  placeholder="Search Peptide..."
                  class="w-full pl-12 pr-5 py-3 border border-black rounded-[500px] font-roboto font-normal text-sm leading-normal tracking-normal text-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-500"
                />
                <svg class="absolute left-5 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>

              <!-- Item Count and Sort (on the right) -->
              <div class="flex items-center gap-4 ml-auto">
                <span class="font-roboto font-normal text-base leading-normal tracking-normal text-gray-800">{{ products.total }} Items</span>
                <div class="relative">
                  <MainButton
                    text="Sort Items"
                    size="custom-small"
                    bg-color="gray-200"
                    :svg="sortIcon"
                    @click="showSortDropdown = !showSortDropdown"
                  />
                  <div 
                    v-if="showSortDropdown"
                    class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg z-10 border border-gray-200"
                  >
                    <button 
                      @click="applySort('price', 'asc')"
                      class="w-full text-left px-4 py-2 hover:bg-gray-100 font-roboto font-normal text-sm leading-normal tracking-normal text-gray-800 flex items-center gap-2"
                    >
                      <svg width="20" height="18" viewBox="0 0 20 18" fill="none" xmlns="http://www.w3.org/2000/svg" class="flex-shrink-0">
                        <path d="M1 1H14M1 5H10M1 9H10M15 5V17M15 17L11 13M15 17L19 13" stroke="#1F2937" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                      Low to High
                    </button>
                    <button 
                      @click="applySort('price', 'desc')"
                      class="w-full text-left px-4 py-2 hover:bg-gray-100 font-roboto font-normal text-sm leading-normal tracking-normal text-gray-800 flex items-center gap-2"
                    >
                      <svg width="20" height="18" viewBox="0 0 20 18" fill="none" xmlns="http://www.w3.org/2000/svg" class="flex-shrink-0">
                        <g transform="translate(0,18) scale(1,-1)">
                          <path d="M1 1H14M1 5H10M1 9H10M15 5V17M15 17L11 13M15 17L19 13" stroke="#1F2937" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </g>
                      </svg>
                      High to Low
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Filter By Label and Filter Buttons (Second Row) -->
            <div class="mb-6 flex items-center gap-2 flex-wrap">
              <span class="font-roboto font-medium text-sm leading-none tracking-normal text-gray-800">Filter By</span>
              <MainButton
                v-for="filter in quickFilters"
                :key="filter.key"
                :text="filter.label"
                :badge="getFilterCount(filter.key) > 0 ? getFilterCount(filter.key) : null"
                size="custom-small"
                :bg-color="activeFilters[filter.key] ? 'gray-800' : 'gray-200'"
                @click="toggleQuickFilter(filter.key)"
              />
            </div>

            <!-- Product Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-4 gap-6 mb-8">
              <ProductCard
                v-for="product in products.data"
                :key="product.id"
                :name="product.name"
                :image-url="product.image_url"
                :price="product.price"
                :discount-price="product.discount_price"
                :brand-name="product.brand?.name"
                :rating-average="product.rating_average"
                :rating-count="product.rating_count"
                :to="`/product/${product.slug}`"
              />
            </div>

            <!-- Pagination -->
            <Pagination
              :pagination="products"
              :get-page-url="getPageUrl"
              :per-page-options="[10, 20, 50, 100]"
              :on-per-page-change="handlePerPageChange"
            />
          </div>
        </div>
    </section>

    <!-- Filter Sidebar Modal Overlay -->
    <div 
      v-if="showSidebar"
      class="fixed inset-0 z-50 flex"
      @click="showSidebar = false"
    >
      <!-- Backdrop -->
      <div class="absolute inset-0 bg-black/50"></div>
      
      <!-- Sidebar -->
      <div class="w-full sm:w-96 bg-white shadow-xl overflow-y-auto ml-auto relative z-10" @click.stop>
        <div class="p-6 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <h3 class="font-hv-muse font-normal text-2xl leading-normal tracking-normal text-teal-700 m-0">Filter Peptides</h3>
            <button 
              @click="showSidebar = false"
              class="text-gray-500 hover:text-gray-700"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
        <div class="p-6 space-y-4">
          <!-- Use Filter -->
          <div>
            <button 
              @click="expandedFilters.use = !expandedFilters.use"
              class="w-full flex items-center justify-between py-2 font-roboto font-medium text-base leading-normal tracking-normal text-gray-800"
            >
              <span>Use</span>
              <svg class="w-5 h-5" :class="expandedFilters.use ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div v-if="expandedFilters.use" class="mt-2 space-y-2">
              <label 
                v-for="use in filterOptions.uses"
                :key="use.id"
                class="flex items-center gap-2 cursor-pointer"
              >
                <input 
                  type="radio"
                  :value="use.id"
                  v-model="selectedFilters.use"
                  @change="applyFilters"
                  class="rounded border-gray-300"
                  name="use-filter"
                />
                <span class="font-roboto font-normal text-sm leading-normal tracking-normal text-gray-700">{{ use.name }}</span>
              </label>
            </div>
          </div>

          <!-- Type Filter -->
          <div>
            <button 
              @click="expandedFilters.type = !expandedFilters.type"
              class="w-full flex items-center justify-between py-2 font-roboto font-medium text-base leading-normal tracking-normal text-gray-800"
            >
              <span>Type</span>
              <svg class="w-5 h-5" :class="expandedFilters.type ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div v-if="expandedFilters.type" class="mt-2 space-y-2">
              <label 
                v-for="type in filterOptions.types"
                :key="type.id"
                class="flex items-center gap-2 cursor-pointer"
              >
                <input 
                  type="radio"
                  :value="type.id"
                  v-model="selectedFilters.type"
                  @change="applyFilters"
                  class="rounded border-gray-300"
                  name="type-filter"
                />
                <span class="font-roboto font-normal text-sm leading-normal tracking-normal text-gray-700">{{ type.name }}</span>
              </label>
            </div>
          </div>

          <!-- Cost Filter -->
          <div>
            <button 
              @click="expandedFilters.cost = !expandedFilters.cost"
              class="w-full flex items-center justify-between py-2 font-roboto font-medium text-base leading-normal tracking-normal text-gray-800"
            >
              <span>Cost</span>
              <svg class="w-5 h-5" :class="expandedFilters.cost ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div v-if="expandedFilters.cost" class="mt-2 space-y-2">
              <div class="flex items-center gap-2">
                <input 
                  type="number"
                  v-model.number="selectedFilters.cost_min"
                  @change="applyFilters"
                  placeholder="Min"
                  class="w-full px-3 py-2 border border-gray-300 rounded-[500px] font-roboto font-normal text-sm leading-normal tracking-normal text-gray-800"
                />
                <span class="font-roboto font-normal text-sm leading-normal tracking-normal text-gray-600">to</span>
                <input 
                  type="number"
                  v-model.number="selectedFilters.cost_max"
                  @change="applyFilters"
                  placeholder="Max"
                  class="w-full px-3 py-2 border border-gray-300 rounded-[500px] font-roboto font-normal text-sm leading-normal tracking-normal text-gray-800"
                />
              </div>
            </div>
          </div>

          <!-- Location Filter -->
          <div>
            <button 
              @click="expandedFilters.location = !expandedFilters.location"
              class="w-full flex items-center justify-between py-2 font-roboto font-medium text-base leading-normal tracking-normal text-gray-800"
            >
              <span>Location</span>
              <svg class="w-5 h-5" :class="expandedFilters.location ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div v-if="expandedFilters.location" class="mt-2 space-y-2">
              <label 
                class="flex items-center gap-2 cursor-pointer"
              >
                <input 
                  type="radio"
                  :value="null"
                  v-model="selectedFilters.location"
                  @change="applyFilters"
                  class="rounded border-gray-300"
                  name="location-filter"
                />
                <span class="font-roboto font-normal text-sm leading-normal tracking-normal text-gray-700">All Locations</span>
              </label>
              <label 
                v-for="location in filterOptions.locations"
                :key="location.id"
                class="flex items-center gap-2 cursor-pointer"
              >
                <input 
                  type="radio"
                  :value="location.id"
                  v-model="selectedFilters.location"
                  @change="applyFilters"
                  class="rounded border-gray-300"
                  name="location-filter"
                />
                <span class="font-roboto font-normal text-sm leading-normal tracking-normal text-gray-700">{{ location.name }}</span>
              </label>
            </div>
          </div>

          <!-- Verification Filter -->
          <div>
            <button 
              @click="expandedFilters.verification = !expandedFilters.verification"
              class="w-full flex items-center justify-between py-2 font-roboto font-medium text-base leading-normal tracking-normal text-gray-800"
            >
              <span>Verification</span>
              <svg class="w-5 h-5" :class="expandedFilters.verification ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div v-if="expandedFilters.verification" class="mt-2 space-y-2">
              <label class="flex items-center gap-2 cursor-pointer">
                <input 
                  type="radio"
                  value=""
                  v-model="selectedFilters.verification"
                  @change="applyFilters"
                  class="rounded border-gray-300"
                />
                <span class="font-roboto font-normal text-sm leading-normal tracking-normal text-gray-700">All</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <input 
                  type="radio"
                  value="1"
                  v-model="selectedFilters.verification"
                  @change="applyFilters"
                  class="rounded border-gray-300"
                />
                <span class="font-roboto font-normal text-sm leading-normal tracking-normal text-gray-700">Verified</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <input 
                  type="radio"
                  value="0"
                  v-model="selectedFilters.verification"
                  @change="applyFilters"
                  class="rounded border-gray-300"
                />
                <span class="font-roboto font-normal text-sm leading-normal tracking-normal text-gray-700">Not Verified</span>
              </label>
            </div>
          </div>

          <!-- Brand Filter -->
          <div>
            <button 
              @click="expandedFilters.brand = !expandedFilters.brand"
              class="w-full flex items-center justify-between py-2 font-roboto font-medium text-base leading-normal tracking-normal text-gray-800"
            >
              <span>Brand</span>
              <svg class="w-5 h-5" :class="expandedFilters.brand ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div v-if="expandedFilters.brand" class="mt-2 space-y-2">
              <label 
                v-for="brand in filterOptions.brands"
                :key="brand.id"
                class="flex items-center gap-2 cursor-pointer"
              >
                <input 
                  type="radio"
                  :value="brand.id"
                  v-model="selectedFilters.brand"
                  @change="applyFilters"
                  class="rounded border-gray-300"
                  name="brand-filter"
                />
                <span class="font-roboto font-normal text-sm leading-normal tracking-normal text-gray-700">{{ brand.name }}</span>
              </label>
            </div>
          </div>
        </div>
        <div class="p-6 border-t border-gray-200 flex gap-4">
          <button 
            @click="clearFilters"
            class="flex-1 py-2 px-4 rounded-[500px] bg-gray-200 font-roboto font-medium text-sm leading-none tracking-normal text-gray-800 hover:bg-gray-300 flex items-center justify-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            Clear Filters
          </button>
          <button 
            @click="applyFilters"
            class="flex-1 py-2 px-4 rounded-[500px] bg-teal-700 font-roboto font-medium text-sm leading-none tracking-normal text-white hover:bg-teal-800 flex items-center justify-center gap-2"
          >
            Show ({{ products.total }})
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>
      </div>
    </div>
  </FrontLayout>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch, h } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import FrontLayout from '../Layouts/FrontLayout.vue'
import ProductCard from '@/components/ProductCard.vue'
import MainButton from '@/components/MainButton.vue'
import Pagination from '@/components/Pagination.vue'

const props = defineProps({
  productName: String,
  slug: String,
  products: Object,
  filterOptions: Object,
  priceRange: Object,
  filters: Object,
  sort: String,
  sortDir: String,
  search: String,
})

// Hero background lazy loading
const heroBgRef = ref(null)
const heroBgLoaded = ref(false)

// Sort dropdown
const showSortDropdown = ref(false)
// Filter sidebar
const showSidebar = ref(false)
// Initialize searchQuery from props or URL
const searchQuery = ref(props.search || '')
const perPage = ref(props.products.per_page || 20)

// Sort icon component
const sortIcon = computed(() => {
  if (props.sortDir === 'asc') {
    return h('svg', {
      width: '20',
      height: '18',
      viewBox: '0 0 20 18',
      fill: 'none',
      xmlns: 'http://www.w3.org/2000/svg'
    }, [
      h('path', {
        d: 'M1 1H14M1 5H10M1 9H10M15 5V17M15 17L11 13M15 17L19 13',
        stroke: '#1F2937',
        'stroke-width': '2',
        'stroke-linecap': 'round',
        'stroke-linejoin': 'round'
      })
    ])
  } else {
    return h('svg', {
      width: '20',
      height: '18',
      viewBox: '0 0 20 18',
      fill: 'none',
      xmlns: 'http://www.w3.org/2000/svg'
    }, [
      h('g', {
        transform: 'translate(0,18) scale(1,-1)'
      }, [
        h('path', {
          d: 'M1 1H14M1 5H10M1 9H10M15 5V17M15 17L11 13M15 17L19 13',
          stroke: '#1F2937',
          'stroke-width': '2',
          'stroke-linecap': 'round',
          'stroke-linejoin': 'round'
        })
      ])
    ])
  }
})

// Expanded filters - expand location if it has an active filter
const expandedFilters = ref({
  use: false,
  type: false,
  cost: false,
  location: props.filters?.location ? true : false,
  verification: false,
  brand: false,
})

// Selected filters
const selectedFilters = ref({
  use: props.filters?.use ? parseInt(props.filters.use) : null,
  type: props.filters?.type ? parseInt(props.filters.type) : null,
  location: props.filters?.location ? parseInt(props.filters.location) : null,
  verification: props.filters?.verification || '',
  brand: props.filters?.brand ? parseInt(props.filters.brand) : null,
  cost_min: props.filters?.cost_min || '',
  cost_max: props.filters?.cost_max || '',
})

// Quick filters
const quickFilters = [
  { key: 'use', label: 'Use' },
  { key: 'type', label: 'Type' },
  { key: 'cost', label: 'Cost' },
  { key: 'location', label: 'Location' },
  { key: 'verification', label: 'Verification' },
  { key: 'brand', label: 'Brand' },
]

// Active filters for quick filter buttons
const activeFilters = computed(() => {
  return {
    use: selectedFilters.value.use !== null,
    type: selectedFilters.value.type !== null,
    cost: selectedFilters.value.cost_min || selectedFilters.value.cost_max,
    location: selectedFilters.value.location !== null,
    verification: selectedFilters.value.verification !== '',
    brand: selectedFilters.value.brand !== null,
  }
})

const getFilterCount = (key) => {
  if (key === 'use') return selectedFilters.value.use !== null ? 1 : 0
  if (key === 'type') return selectedFilters.value.type !== null ? 1 : 0
  if (key === 'location') return selectedFilters.value.location !== null ? 1 : 0
  if (key === 'brand') return selectedFilters.value.brand !== null ? 1 : 0
  if (key === 'verification') return selectedFilters.value.verification !== '' ? 1 : 0
  if (key === 'cost') return (selectedFilters.value.cost_min || selectedFilters.value.cost_max) ? 1 : 0
  return 0
}

const toggleQuickFilter = (key) => {
  // Show sidebar and expand the filter section when clicking quick filter button
  showSidebar.value = true
  if (key === 'use' || key === 'type' || key === 'location' || key === 'brand' || key === 'verification' || key === 'cost') {
    expandedFilters.value[key] = true
  }
}

const applyFilters = () => {
  const params = new URLSearchParams()
  
  // Preserve search query
  if (searchQuery.value) {
    params.set('search', searchQuery.value)
  }
  
  if (selectedFilters.value.use !== null) {
    params.set('use', selectedFilters.value.use)
  }
  if (selectedFilters.value.type !== null) {
    params.set('type', selectedFilters.value.type)
  }
  if (selectedFilters.value.location !== null) {
    params.set('location', selectedFilters.value.location)
  } else {
    params.delete('location')
  }
  if (selectedFilters.value.verification) {
    params.set('verification', selectedFilters.value.verification)
  }
  if (selectedFilters.value.brand !== null) {
    params.set('brand', selectedFilters.value.brand)
  }
  if (selectedFilters.value.cost_min) {
    params.set('cost_min', selectedFilters.value.cost_min)
  }
  if (selectedFilters.value.cost_max) {
    params.set('cost_max', selectedFilters.value.cost_max)
  }
  if (props.sort) {
    params.set('sort', props.sort)
  }
  if (props.sortDir) {
    params.set('sort_dir', props.sortDir)
  }
  if (perPage.value) {
    params.set('per_page', perPage.value)
  }

  router.visit(`/product/${props.slug}?${params.toString()}`, {
    preserveState: true,
    preserveScroll: true,
  })
}

const clearFilters = () => {
  selectedFilters.value = {
    use: null,
    type: null,
    location: null,
    verification: '',
    brand: null,
    cost_min: '',
    cost_max: '',
  }
  applyFilters()
}

const applySort = (sort, dir) => {
  showSortDropdown.value = false
  const params = new URLSearchParams(window.location.search)
  
  // Preserve search query
  if (searchQuery.value) {
    params.set('search', searchQuery.value)
  }
  
  params.set('sort', sort)
  params.set('sort_dir', dir)
  router.visit(`/product/${props.slug}?${params.toString()}`, {
    preserveState: true,
    preserveScroll: true,
  })
}

let searchTimeout = null

const handleSearchInput = () => {
  // Debounce search to avoid too many requests
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    applySearch()
  }, 500) // Wait 500ms after user stops typing
}

const applySearch = () => {
  const params = new URLSearchParams(window.location.search)
  
  // Update search parameter
  if (searchQuery.value) {
    params.set('search', searchQuery.value)
  } else {
    params.delete('search')
  }
  
  // Reset to first page when searching
  params.set('page', '1')
  
  // Preserve other filters
  if (selectedFilters.value.use !== null) {
    params.set('use', selectedFilters.value.use)
  }
  if (selectedFilters.value.type !== null) {
    params.set('type', selectedFilters.value.type)
  }
  if (selectedFilters.value.location !== null) {
    params.set('location', selectedFilters.value.location)
  } else {
    params.delete('location')
  }
  if (selectedFilters.value.verification) {
    params.set('verification', selectedFilters.value.verification)
  }
  if (selectedFilters.value.brand !== null) {
    params.set('brand', selectedFilters.value.brand)
  }
  if (selectedFilters.value.cost_min) {
    params.set('cost_min', selectedFilters.value.cost_min)
  }
  if (selectedFilters.value.cost_max) {
    params.set('cost_max', selectedFilters.value.cost_max)
  }
  if (props.sort) {
    params.set('sort', props.sort)
  }
  if (props.sortDir) {
    params.set('sort_dir', props.sortDir)
  }
  if (perPage.value) {
    params.set('per_page', perPage.value)
  }
  
  router.visit(`/product/${props.slug}?${params.toString()}`, {
    preserveState: true,
    preserveScroll: true,
  })
}

const applyPerPage = () => {
  applyFilters()
}

const handlePerPageChange = (newPerPage) => {
  perPage.value = newPerPage
  const params = new URLSearchParams(window.location.search)
  params.set('per_page', newPerPage)
  params.set('page', 1) // Reset to first page when changing per page
  router.visit(`/product/${props.slug}?${params.toString()}`, {
    preserveState: true,
    preserveScroll: false,
  })
}

const getPageUrl = (page) => {
  const params = new URLSearchParams(window.location.search)
  params.set('page', page)
  return `/product/${props.slug}?${params.toString()}`
}

const handleCtaClick = (url) => {
  router.visit(url)
}

const handleImageError = (event) => {
  // Prevent infinite loop - stop trying to load images if we've already failed
  if (event.target.dataset.failed) {
    return
  }
  // Mark as failed to prevent retry
  event.target.dataset.failed = 'true'
  // Hide the broken image and show placeholder
  event.target.style.display = 'none'
  if (event.target.parentElement) {
    const placeholder = document.createElement('div')
    placeholder.className = 'w-full h-full flex items-center justify-center text-gray-400'
    placeholder.innerHTML = '<span class="text-sm">No Image</span>'
    event.target.parentElement.appendChild(placeholder)
  }
}

// Setup lazy loading for hero background
onMounted(() => {
  nextTick(() => {
    if (heroBgRef.value) {
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const bgImage = entry.target.getAttribute('data-bg-image')
            if (bgImage) {
              const img = new Image()
              img.onload = () => {
                heroBgLoaded.value = true
              }
              img.src = bgImage
            }
            observer.unobserve(entry.target)
          }
        })
      }, {
        rootMargin: '50px'
      })
      observer.observe(heroBgRef.value)
    }
  })
})

// Close dropdowns when clicking outside
onMounted(() => {
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.relative')) {
      showSortDropdown.value = false
    }
  })
})
</script>

