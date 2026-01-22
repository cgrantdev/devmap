<template>
  <FrontLayout>
    <!-- Hero Section -->
    <section class="pt-0 pb-16 bg-white">
      <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-[1360px] h-[334px] rounded-[24px] overflow-hidden relative mx-auto">
          <!-- Background Image -->
          <div 
            class="absolute inset-0 bg-cover bg-center bg-no-repeat"
            style="background-image: url('/images/hero/hero1.jpg')"
          >
            <div 
              class="absolute inset-0 pointer-events-none"
              style="background: linear-gradient(260deg, rgba(0, 0, 0, 0.4) 45.49%, rgba(0, 0, 0, 0.81) 79.33%)"
            ></div>
          </div>
          
          <!-- Content -->
          <div class="relative p-10 h-full flex flex-col justify-center max-w-[800px] gap-6">
            <div class="flex flex-col gap-6">
              <h1 class="font-hv-muse font-normal text-[52px] leading-tight tracking-normal text-white m-0">Trusted Brands</h1>
              <p class="font-roboto font-normal text-lg leading-loose tracking-normal text-white m-0">Explore verified brands in the peptide research community.</p>
              <MainButton
                text="View All Vendors"
                to="/brands"
                size="custom"
                bg-color="white"
              />
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Brands Section -->
    <section class="min-h-screen bg-gray-50">
      <div class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
          <h1 class="text-4xl text-gray-900 mb-4">All Vendors</h1>
          <p class="text-xl text-gray-600">Compare vendors, read reviews, and find the best peptide sources</p>
        </div>
      </div>
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Main Content Area (Full Width) -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
          <!-- Search Bar, Sort, and Filters (First Row) -->
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <!-- Search Bar -->
            <div class="md:col-span-2 relative">
              <input
                v-model="searchQuery"
                @input="handleSearchInput"
                type="text"
                placeholder="Search vendors..."
                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" aria-hidden="true">
                <path d="m21 21-4.34-4.34"></path>
                <circle cx="11" cy="11" r="8"></circle>
              </svg>
            </div>

            <!-- Sort and Filters (on the right) -->
            <select
              class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
              :value="sortValue"
              @change="handleSortChange"
            >
              <option value="rating|desc">Highest Rated</option>
              <option value="reviews|desc">Most Reviews</option>
              <option value="name|asc">Alphabetical</option>
            </select>
              
            <button
              type="button"
              class="flex items-center justify-center gap-2 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
              @click="showFilterPanel = !showFilterPanel"
            >
              <component :is="filterIcon" class="w-4 h-4" />
              <span>Filters</span>
              <component
                :is="chevronIcon"
                class="w-4 h-4 transition-transform"
                :class="showFilterPanel ? 'rotate-180' : ''"
              />
            </button>            
          </div>

          <!-- Filter Panel -->
          <div v-if="showFilterPanel" class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-4 border-t border-gray-200">
            
            <!-- Location Filter -->
            <div>
              <label class="block text-sm text-gray-700 mb-2">Location</label>
              <select
                v-model="selectedFilters.location"
                @change="applyFilters"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
              >
                <option value="">All Locations</option>
                <option value="United States">United States</option>
                <option value="Switzerland">Switzerland</option>
                <option value="Canada">Canada</option>
                <option value="United Kingdom">United Kingdom</option>
                <option value="Australia">Australia</option>
              </select>
            </div>

            <!-- Minimum Rating Filter -->
            <div>
              <label class="block text-sm text-gray-700 mb-2">Minimum Rating</label>
              <select
                v-model="selectedFilters.minRating"
                @change="applyFilters"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
              >
                <option value="">All Ratings</option>
                <option value="4.5">4.5+ Stars</option>
                <option value="4.0">4.0+ Stars</option>
                <option value="3.5">3.5+ Stars</option>
              </select>
            </div>

            <!-- Vendor Type Filter -->
            <div>
              <label class="block text-sm text-gray-700 mb-2">Vendor Type</label>
              <label class="flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                <input
                  type="checkbox"
                  v-model="selectedFilters.topVendorsOnly"
                  @change="applyFilters"
                  class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500"
                />
                <span class="text-sm text-gray-700">Top Vendors Only</span>
              </label>
            </div>
            
          </div>
        </div>

        <!-- Vendors Found -->
        <div class="mb-4">
          <p class="text-gray-600">{{ filteredBrands.length }} vendors found</p>
        </div>
      
        <div v-if="filteredBrands.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
        <VendorCard
            v-for="brand in filteredBrands"
          :key="brand.id"
          :id="brand.id"
          :name="brand.name"
          :slug="brand.slug"
          :logo="brand.logo"
          :initials="brand.initials"
          :location="brand.location"
          :rating="brand.rating || '0.00'"
          :reviews="brand.reviews || 0"
          :is-partner="brand.is_partner || false"
          :featured="brand.featured || false"
        />
        </div>
        
        <div v-else class="text-center py-20">
            <p class="font-roboto font-normal text-lg text-gray-500">No vendors found matching your search.</p>
          </div>
        </div>      
    </section>
  </FrontLayout>
</template>

<script setup>
import { ref, computed, h } from 'vue'
import { router } from '@inertiajs/vue3'
import FrontLayout from '../Layouts/FrontLayout.vue'
import VendorCard from '@/components/VendorCard.vue'
import MainButton from '@/components/MainButton.vue'

const props = defineProps({
  brands: {
    type: Array,
    default: () => []
  },
  search: String,
  sort: String,
  sortDir: String,
  filters: Object,
})

// Filter panel
const showFilterPanel = ref(false)
// Initialize searchQuery from props or URL
const searchQuery = ref(props.search || '')

// Selected filters
const selectedFilters = ref({
  location: props.filters?.location || '',
  minRating: props.filters?.min_rating || '',
  topVendorsOnly: props.filters?.top_vendors_only === '1' || false,
})

// Filter icon component
const filterIcon = h('svg', {
  xmlns: 'http://www.w3.org/2000/svg',
  width: '24',
  height: '24',
  viewBox: '0 0 24 24',
  fill: 'none',
  stroke: 'currentColor'
}, [
  h('path', {
    d: 'M10 20a1 1 0 0 0 .553.895l2 1A1 1 0 0 0 14 21v-7a2 2 0 0 1 .517-1.341L21.74 4.67A1 1 0 0 0 21 3H3a1 1 0 0 0-.742 1.67l7.225 7.989A2 2 0 0 1 10 14z'
  })
])

const chevronIcon = h('svg', {
  xmlns: 'http://www.w3.org/2000/svg',
  width: '24',
  height: '24',
  viewBox: '0 0 24 24',
  fill: 'none',
  stroke: 'currentColor',
  'stroke-width': '2',
  'stroke-linecap': 'round',
  'stroke-linejoin': 'round'
}, [
  h('path', { d: 'm6 9 6 6 6-6' })
])

// Sort value computed
const sortValue = computed(() => {
  const sort = props.sort || 'rating'
  const dir = props.sortDir || 'desc'
  return `${sort}|${dir}`
})

// Handle sort change
const handleSortChange = (event) => {
  const value = event?.target?.value || 'rating|desc'
  const [sort, dir] = value.split('|')
  applySort(sort, dir)
}

// Apply sort
const applySort = (sort, dir) => {
  const params = new URLSearchParams(window.location.search)
  
  // Preserve search query
  if (searchQuery.value) {
    params.set('search', searchQuery.value)
  } else {
    params.delete('search')
  }
  
  params.set('sort', sort)
  params.set('sort_dir', dir)
  
  router.visit(`/brands?${params.toString()}`, {
    preserveState: true,
    preserveScroll: true,
  })
}

// Debounced search
let searchTimeout = null

const handleSearchInput = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    applySearch()
  }, 500)
}

const applySearch = () => {
  const params = new URLSearchParams(window.location.search)
  
  // Update search parameter
  if (searchQuery.value) {
    params.set('search', searchQuery.value)
  } else {
    params.delete('search')
  }
  
  // Preserve sort
  if (props.sort) {
    params.set('sort', props.sort)
  }
  if (props.sortDir) {
    params.set('sort_dir', props.sortDir)
  }
  
  router.visit(`/brands?${params.toString()}`, {
    preserveState: true,
    preserveScroll: true,
  })
}

// Apply filters
const applyFilters = () => {
  const params = new URLSearchParams(window.location.search)
  
  // Preserve search query
  if (searchQuery.value) {
    params.set('search', searchQuery.value)
  } else {
    params.delete('search')
  }
  
  // Preserve sort
  if (props.sort) {
    params.set('sort', props.sort)
  }
  if (props.sortDir) {
    params.set('sort_dir', props.sortDir)
  }
  
  // Add filter parameters
  if (selectedFilters.value.location) {
    params.set('location', selectedFilters.value.location)
  } else {
    params.delete('location')
  }
  
  if (selectedFilters.value.minRating) {
    params.set('min_rating', selectedFilters.value.minRating)
  } else {
    params.delete('min_rating')
  }
  
  if (selectedFilters.value.topVendorsOnly) {
    params.set('top_vendors_only', '1')
  } else {
    params.delete('top_vendors_only')
  }
  
  router.visit(`/brands?${params.toString()}`, {
    preserveState: true,
    preserveScroll: true,
  })
}

// Brands are filtered by the backend
const filteredBrands = computed(() => props.brands)
</script>

