<template>
  <ModernLayout>
    <!-- Product Listing Section -->
    <section class="bg-white">
      <!-- Header -->
      <div class="max-w-[1280px] mx-auto px-5 lg:px-10 pt-8 lg:pt-12 pb-6">
        <h1 class="ui-display text-3xl lg:text-4xl font-semibold tracking-tight text-[color:var(--color-ink)] mb-2">{{ productName }}</h1>
        <p class="text-[15px] text-[color:var(--color-ink-muted)]">Compare prices across verified vendors</p>
      </div>

      <div class="max-w-[1280px] mx-auto px-5 lg:px-10 pb-8">
        <!-- Sort + Dosage + Search bar -->
        <div class="flex flex-wrap items-center gap-3 mb-6">
          <!-- Search -->
          <div class="relative flex-1 max-w-xs">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[color:var(--color-ink-subtle)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            <input
              v-model="searchQuery"
              @input="handleSearchInput"
              type="text"
              placeholder="Search products..."
              class="w-full h-9 pl-9 pr-3 text-[13px] border border-[color:var(--color-hairline)] bg-white focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15"
            />
          </div>

          <!-- Sort -->
          <select
            class="h-9 px-3 text-[13px] border border-[color:var(--color-hairline)] bg-white focus:border-[color:var(--color-accent-500)] focus:outline-none"
            :value="sortValue"
            @change="handleSortChange"
          >
            <option value="price|asc">Price: Low → High</option>
            <option value="price|desc">Price: High → Low</option>
            <option value="popular|desc">Most Popular</option>
          </select>

          <!-- Dosage size filter -->
          <select
            v-if="filterOptions.sizes && filterOptions.sizes.length > 1"
            class="h-9 px-3 text-[13px] border border-[color:var(--color-hairline)] bg-white focus:border-[color:var(--color-accent-500)] focus:outline-none"
            :value="selectedSize"
            @change="handleSizeChange"
          >
            <option value="">All dosages</option>
            <option v-for="size in filterOptions.sizes" :key="size" :value="size">{{ formatSize(size) }}</option>
          </select>

          <!-- Brand filter (if multiple) -->
          <select
            v-if="filterOptions.brands && filterOptions.brands.length > 1"
            class="h-9 px-3 text-[13px] border border-[color:var(--color-hairline)] bg-white focus:border-[color:var(--color-accent-500)] focus:outline-none"
            :value="selectedBrand"
            @change="handleBrandChange"
          >
            <option value="">All vendors</option>
            <option v-for="b in filterOptions.brands" :key="b.id" :value="b.id">{{ b.name }}</option>
          </select>

          <span class="ml-auto text-[12px] text-[color:var(--color-ink-subtle)] ui-mono">{{ products.total || products.data?.length || 0 }} products</span>
        </div>

        <!-- Main Content Area -->
        <div class="w-full">
            <div class="mb-4 hidden">
              <span class="font-roboto font-normal text-base leading-normal tracking-normal text-gray-800">{{ products.total }} products found</span>
            </div>

            <!-- Product Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-4 gap-6 mb-8">
              <ProductCard
                v-for="product in products.data"
                :key="product.id"
                :name="product.display_name || product.name"
                :image-url="product.image_url"
                :price="product.price"
                :discount-price="product.discount_price"
                :brand-name="product.brand?.name"
                :rating-average="product.rating_average"
                :rating-count="product.rating_count"
                :category-name="product.category?.name || ''"
                :size-mg="product.size_mg"
                :availability="product.availability"
                :purity="product.purity"
                :product-type="product.product_type"
                :brand-discount-percent="product.brand_discount_percent"
                :brand-coupon-code="product.brand_coupon_code"
                :to="`/product/${product.slug}/${product.id}`"
              />
            </div>
          </div>
        </div>
    </section>
  </ModernLayout>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch, h, watchEffect } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import ModernLayout from '@/Pages/Layouts/ModernLayout.vue'
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
  category: Object,
  seo: {
    type: Object,
    default: () => ({
      title: null,
      description: null,
      og_title: null,
      og_description: null,
      og_image: null,
      image: null,
      url: null,
      canonical: null,
    })
  }
})

const page = usePage()

// Computed values for reactive SEO updates (automatically from category data)
const title = computed(() => {
  // Use SEO title if provided, otherwise generate from category name
  if (props.seo?.title) {
    return props.seo.title
  }
  const siteName = page.props.site_name || 'Peptidemap'
  return `${props.productName || props.category?.name || 'Products'} – ${siteName}`
})

const description = computed(() => {
  // Use SEO description if provided, otherwise generate from category description
  if (props.seo?.description) {
    return props.seo.description
  }
  if (props.category?.description) {
    // Truncate to ~155 chars
    const desc = props.category.description.replace(/\s+/g, ' ').trim()
    return desc.length > 155 ? desc.substring(0, 155) + '...' : desc
  }
  return 'Browse ' + (props.productName || props.category?.name || 'products') + ' research peptides. Compare products, prices, and vendors.'
})

const url = computed(() => {
  return props.seo?.url || page.url
})

const ogTitle = computed(() => {
  return props.seo?.og_title || title.value
})

const ogDescription = computed(() => {
  return props.seo?.og_description || description.value
})

const ogImage = computed(() => {
  // Use SEO og_image if provided, otherwise use category image
  return props.seo?.og_image || props.seo?.image || null
})

const canonical = computed(() => {
  return props.seo?.canonical || url.value
})

// Watch for SEO changes and update document title and meta tags immediately
watchEffect(() => {
  // Update document title
  document.title = title.value
  
  // Update meta description
  let metaDescription = document.querySelector('meta[name="description"]')
  if (!metaDescription) {
    metaDescription = document.createElement('meta')
    metaDescription.setAttribute('name', 'description')
    document.head.appendChild(metaDescription)
  }
  metaDescription.setAttribute('content', description.value)
  
  // Update canonical link
  let canonicalLink = document.querySelector('link[rel="canonical"]')
  if (!canonicalLink) {
    canonicalLink = document.createElement('link')
    canonicalLink.setAttribute('rel', 'canonical')
    document.head.appendChild(canonicalLink)
  }
  canonicalLink.setAttribute('href', canonical.value)
  
  // Update Open Graph tags
  const updateMetaTag = (property, content) => {
    if (!content) return // Don't set empty values
    let meta = document.querySelector(`meta[property="${property}"]`)
    if (!meta) {
      meta = document.createElement('meta')
      meta.setAttribute('property', property)
      document.head.appendChild(meta)
    }
    meta.setAttribute('content', content)
  }
  
  updateMetaTag('og:title', ogTitle.value)
  updateMetaTag('og:description', ogDescription.value)
  updateMetaTag('og:url', url.value)
  if (ogImage.value) {
    updateMetaTag('og:image', ogImage.value)
  }
})

// Hero background lazy loading
const heroBgRef = ref(null)
const heroBgLoaded = ref(false)

const sortValue = computed(() => `${props.sort || 'price'}|${props.sortDir || 'asc'}`)
const selectedSize = ref(new URLSearchParams(window.location.search).get('size') || '')
const selectedBrand = ref(new URLSearchParams(window.location.search).get('brand') || '')

const handleSortChange = (event) => {
  const value = event?.target?.value || 'price|asc'
  const [sort, dir] = value.split('|')
  applySort(sort, dir)
}

function handleSizeChange(event) {
  selectedSize.value = event.target.value
  applyFilters()
}

function handleBrandChange(event) {
  selectedBrand.value = event.target.value
  applyFilters()
}

function formatSize(size) {
  if (size >= 1000) return (size / 1000) + 'g'
  return size + 'mg'
}

// Filter panel (hidden)
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

  if (searchQuery.value) params.set('search', searchQuery.value)
  if (selectedSize.value) params.set('size', selectedSize.value)
  if (selectedBrand.value) params.set('brand', selectedBrand.value)
  if (props.sort) params.set('sort', props.sort)
  if (props.sortDir) params.set('sort_dir', props.sortDir)

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
  if (searchQuery.value) params.set('search', searchQuery.value)
  if (selectedSize.value) params.set('size', selectedSize.value)
  else params.delete('size')
  if (selectedBrand.value) params.set('brand', selectedBrand.value)
  else params.delete('brand')
  params.set('sort', sort)
  params.set('sort_dir', dir)
  router.visit(`/product/${props.slug}?${params.toString()}`, { preserveState: true, preserveScroll: true })
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
