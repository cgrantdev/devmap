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
        <h2 class="font-hv-muse font-normal text-center text-5xl leading-normal tracking-normal text-gray-800 m-0 mb-2">{{ productName }}</h2>
        <p class="font-roboto font-normal text-base leading-normal tracking-normal text-gray-800 text-center mb-6">Compare prices, check availability, and read verified reviews.</p>

        <!-- Main Content Area (Full Width) -->
        <div class="w-full">
            <!-- Search Bar, Sort, and Filters (First Row) -->
            <div class="mb-4 flex items-center gap-4">
              <!-- Search Bar -->
              <div class="relative flex-1 min-w-[200px]">
                <input
                  v-model="searchQuery"
                  @input="handleSearchInput"
                  type="text"
                  placeholder="Search products, brands, or categories..."
                  class="w-full h-[52px] pl-12 pr-5 py-0 border border-black rounded-md font-roboto font-normal text-sm leading-normal tracking-normal text-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-500"
                />
                <svg class="absolute left-5 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>

              <!-- Sort and Filters (on the right) -->
              <div class="flex items-center gap-4 ml-auto">
                <div class="relative">
                  <select
                    class="h-[52px] px-4 pr-10 rounded-md border border-solid border-gray-800 bg-transparent hover:bg-gray-50 shadow-none font-roboto font-medium text-sm leading-normal tracking-normal text-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-500"
                    :value="sortValue"
                    @change="handleSortChange"
                  >
                    <option value="popular|desc">Most Popular</option>
                    <option value="rating|desc">Highest Rated</option>
                    <option value="price|asc">Price: Low to High</option>
                    <option value="price|desc">Price: High to Low</option>
                  </select>
                </div>
                <MainButton
                  text="Filters"
                  bg-color="white"
                  :class="[
                    'rounded-md !border !border-solid !shadow-none',
                    showFilterPanel
                      ? '!border-blue-600 !bg-blue-600 !text-white hover:!bg-blue-700'
                      : '!border-gray-800 !bg-transparent !text-gray-800 hover:bg-gray-50'
                  ]"
                  size="custom-small"
                  :svg="filterIcon"
                  icon-position="left"
                  @click="showFilterPanel = !showFilterPanel"
                />
              </div>
            </div>

            <!-- Filter Panel -->
            <div v-if="showFilterPanel" class="mb-6 bg-white border border-gray-200 rounded-lg p-6">
              <!-- Checkboxes -->
              <div class="mb-6 grid grid-cols-2 sm:grid-cols-4 gap-4">
                <label class="flex items-center gap-2 cursor-pointer">
                  <input 
                    type="checkbox"
                    v-model="selectedFilters.inStock"
                    @change="applyFilters"
                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                  />
                  <span class="font-roboto font-normal text-sm leading-normal tracking-normal text-gray-800">In Stock Only</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                  <input 
                    type="checkbox"
                    v-model="selectedFilters.onSale"
                    @change="applyFilters"
                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                  />
                  <span class="font-roboto font-normal text-sm leading-normal tracking-normal text-gray-800">On Sale</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                  <input 
                    type="checkbox"
                    v-model="selectedFilters.labTested"
                    @change="applyFilters"
                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                  />
                  <span class="font-roboto font-normal text-sm leading-normal tracking-normal text-gray-800">Lab Tested</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                  <input 
                    type="checkbox"
                    v-model="selectedFilters.firstTimerDeals"
                    @change="applyFilters"
                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                  />
                  <span class="font-roboto font-normal text-sm leading-normal tracking-normal text-gray-800">First-Timer Deals</span>
                </label>
              </div>

              <!-- Min Purity Slider and Price Range (Side by Side) -->
              <div class="mb-6 flex flex-col sm:flex-row gap-6">
                <!-- Min Purity Slider -->
                <div class="flex-[2]">
                  <div class="flex items-center justify-between mb-2">
                    <label class="font-roboto font-normal text-sm leading-normal tracking-normal text-gray-800">Min Purity: {{ selectedFilters.minPurity }}%</label>
                  </div>
                  <div class="relative">
                    <div 
                      class="absolute inset-0 h-2 bg-gray-200 rounded-lg pointer-events-none"
                      :style="{ background: `linear-gradient(to right, #2563eb ${selectedFilters.minPurity}%, #e5e7eb ${selectedFilters.minPurity}%)` }"
                    ></div>
                    <input 
                      type="range"
                      v-model.number="selectedFilters.minPurity"
                      @input="applyFilters"
                      min="0"
                      max="100"
                      step="1"
                      class="w-full h-2 rounded-lg appearance-none cursor-pointer relative z-10 bg-transparent"
                      style="accent-color: #2563eb;"
                    />
                  </div>
                </div>

                <!-- Price Range -->
                <div class="flex-1">
                  <label class="font-roboto font-normal text-sm leading-normal tracking-normal text-gray-800 mb-2 block">Price Range: ${{ selectedFilters.cost_min || 0 }} - ${{ selectedFilters.cost_max || 200 }}</label>
                  <div class="flex items-center gap-2">
                    <input 
                      type="number"
                      v-model.number="selectedFilters.cost_min"
                      @change="applyFilters"
                      placeholder="0"
                      min="0"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg font-roboto font-normal text-sm leading-normal tracking-normal text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                    <span class="font-roboto font-normal text-sm leading-normal tracking-normal text-gray-600">-</span>
                    <input 
                      type="number"
                      v-model.number="selectedFilters.cost_max"
                      @change="applyFilters"
                      placeholder="200"
                      min="0"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg font-roboto font-normal text-sm leading-normal tracking-normal text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
                </div>
              </div>

              <!-- Active Filters -->
              <div v-if="hasActiveFilters" class="flex items-center gap-2 flex-wrap">
                <span class="font-roboto font-medium text-sm leading-normal tracking-normal text-gray-800">Active filters:</span>
                <span
                  v-if="selectedFilters.minPurity > 0"
                  class="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 text-blue-800 rounded-full font-roboto font-normal text-sm leading-normal tracking-normal"
                >
                  {{ selectedFilters.minPurity }}% Purity
                  <button @click="removePurityFilter" class="hover:text-blue-900">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </span>
                <span
                  v-if="selectedFilters.inStock"
                  class="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 text-blue-800 rounded-full font-roboto font-normal text-sm leading-normal tracking-normal"
                >
                  In Stock Only
                  <button @click="selectedFilters.inStock = false; applyFilters()" class="hover:text-blue-900">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </span>
                <span
                  v-if="selectedFilters.onSale"
                  class="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 text-blue-800 rounded-full font-roboto font-normal text-sm leading-normal tracking-normal"
                >
                  On Sale
                  <button @click="selectedFilters.onSale = false; applyFilters()" class="hover:text-blue-900">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </span>
                <span
                  v-if="selectedFilters.labTested"
                  class="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 text-blue-800 rounded-full font-roboto font-normal text-sm leading-normal tracking-normal"
                >
                  Lab Tested
                  <button @click="selectedFilters.labTested = false; applyFilters()" class="hover:text-blue-900">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </span>
                <span
                  v-if="selectedFilters.firstTimerDeals"
                  class="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 text-blue-800 rounded-full font-roboto font-normal text-sm leading-normal tracking-normal"
                >
                  First-Timer Deals
                  <button @click="selectedFilters.firstTimerDeals = false; applyFilters()" class="hover:text-blue-900">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </span>
                <span
                  v-if="selectedFilters.cost_min || selectedFilters.cost_max"
                  class="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 text-blue-800 rounded-full font-roboto font-normal text-sm leading-normal tracking-normal"
                >
                  Price Range
                  <button @click="selectedFilters.cost_min = ''; selectedFilters.cost_max = ''; applyFilters()" class="hover:text-blue-900">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </span>
                <button 
                  @click="clearFilters"
                  class="text-blue-600 hover:text-blue-800 font-roboto font-normal text-sm leading-normal tracking-normal underline"
                >
                  Clear all
                </button>
              </div>
            </div>

            <!-- Products Found (moved to bottom left) -->
            <div class="mb-4">
              <span class="font-roboto font-normal text-base leading-normal tracking-normal text-gray-800">{{ products.total }} products found</span>
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
                :category-name="product.category?.name || ''"
                :size-mg="product.size_mg"
                :availability="product.availability"
                :to="`/product/${product.slug}/${product.id}`"
              />
            </div>
          </div>
        </div>
    </section>
  </FrontLayout>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch, h } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import FrontLayout from '../Layouts/FrontLayout.vue'
import ProductCard from '@/components/ProductCard.vue'
import MainButton from '@/components/MainButton.vue'

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

const sortValue = computed(() => `${props.sort || 'popular'}|${props.sortDir || 'desc'}`)

const handleSortChange = (event) => {
  const value = event?.target?.value || 'popular|desc'
  const [sort, dir] = value.split('|')
  applySort(sort, dir)
}
// Filter panel
const showFilterPanel = ref(false)
// Initialize searchQuery from props or URL
const searchQuery = ref(props.search || '')

// Filter icon component
const filterIcon = h('svg', {
  xmlns: 'http://www.w3.org/2000/svg',
  width: '24',
  height: '24',
  viewBox: '0 0 24 24',
  fill: 'none',
  stroke: 'currentColor',
  'stroke-width': '2',
  'stroke-linecap': 'round',
  'stroke-linejoin': 'round',
  class: 'lucide lucide-sliders-horizontal'
}, [
  h('path', { d: 'M10 5H3' }),
  h('path', { d: 'M12 19H3' }),
  h('path', { d: 'M14 3v4' }),
  h('path', { d: 'M16 17v4' }),
  h('path', { d: 'M21 12h-9' }),
  h('path', { d: 'M21 19h-5' }),
  h('path', { d: 'M21 5h-7' }),
  h('path', { d: 'M8 10v4' }),
  h('path', { d: 'M8 12H3' })
])

// Selected filters
const selectedFilters = ref({
  use: props.filters?.use ? parseInt(props.filters.use) : null,
  type: props.filters?.type ? parseInt(props.filters.type) : null,
  location: props.filters?.location ? parseInt(props.filters.location) : null,
  verification: props.filters?.verification || '',
  brand: props.filters?.brand ? parseInt(props.filters.brand) : null,
  cost_min: props.filters?.cost_min || '',
  cost_max: props.filters?.cost_max || '',
  inStock: props.filters?.in_stock === '1' || false,
  onSale: props.filters?.on_sale === '1' || false,
  labTested: props.filters?.lab_tested === '1' || false,
  firstTimerDeals: props.filters?.first_timer_deals === '1' || false,
  minPurity: props.filters?.min_purity ? parseInt(props.filters.min_purity) : 0,
})

// Current sort label
const currentSortLabel = computed(() => {
  if (props.sort === 'popular') return 'Most Popular'
  if (props.sort === 'rating') return 'Highest Rated'
  if (props.sort === 'price' && props.sortDir === 'asc') return 'Price: Low to High'
  if (props.sort === 'price' && props.sortDir === 'desc') return 'Price: High to Low'
  return 'Most Popular'
})


// Check if there are active filters
const hasActiveFilters = computed(() => {
  return selectedFilters.value.minPurity > 0 ||
    selectedFilters.value.inStock ||
    selectedFilters.value.onSale ||
    selectedFilters.value.labTested ||
    selectedFilters.value.firstTimerDeals ||
    selectedFilters.value.cost_min ||
    selectedFilters.value.cost_max ||
    selectedFilters.value.use !== null ||
    selectedFilters.value.type !== null ||
    selectedFilters.value.location !== null ||
    selectedFilters.value.verification !== '' ||
    selectedFilters.value.brand !== null
})

// Remove purity filter
const removePurityFilter = () => {
  selectedFilters.value.minPurity = 0
  applyFilters()
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
  if (selectedFilters.value.inStock) {
    params.set('in_stock', '1')
  }
  if (selectedFilters.value.onSale) {
    params.set('on_sale', '1')
  }
  if (selectedFilters.value.labTested) {
    params.set('lab_tested', '1')
  }
  if (selectedFilters.value.firstTimerDeals) {
    params.set('first_timer_deals', '1')
  }
  if (selectedFilters.value.minPurity > 0) {
    params.set('min_purity', selectedFilters.value.minPurity)
  }
  if (props.sort) {
    params.set('sort', props.sort)
  }
  if (props.sortDir) {
    params.set('sort_dir', props.sortDir)
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
    inStock: false,
    onSale: false,
    labTested: false,
    firstTimerDeals: false,
    minPurity: 0,
  }
  applyFilters()
}

const applySort = (sort, dir) => {
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
  if (selectedFilters.value.inStock) {
    params.set('in_stock', '1')
  }
  if (selectedFilters.value.onSale) {
    params.set('on_sale', '1')
  }
  if (selectedFilters.value.labTested) {
    params.set('lab_tested', '1')
  }
  if (selectedFilters.value.firstTimerDeals) {
    params.set('first_timer_deals', '1')
  }
  if (selectedFilters.value.minPurity > 0) {
    params.set('min_purity', selectedFilters.value.minPurity)
  }
  if (props.sort) {
    params.set('sort', props.sort)
  }
  if (props.sortDir) {
    params.set('sort_dir', props.sortDir)
  }
  
  router.visit(`/product/${props.slug}?${params.toString()}`, {
    preserveState: true,
    preserveScroll: true,
  })
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

<style scoped>
/* Make range input track transparent so background shows through */
input[type="range"] {
  -webkit-appearance: none;
  appearance: none;
  background: transparent;
}

input[type="range"]::-webkit-slider-track {
  background: transparent;
  height: 8px;
}

input[type="range"]::-moz-range-track {
  background: transparent;
  height: 8px;
}

input[type="range"]::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 20px;
  height: 20px;
  background: #2563eb;
  border-radius: 50%;
  cursor: pointer;
  border: 2px solid white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

input[type="range"]::-moz-range-thumb {
  width: 20px;
  height: 20px;
  background: #2563eb;
  border-radius: 50%;
  cursor: pointer;
  border: 2px solid white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}
</style>
