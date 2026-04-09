<template>
  <ModernLayout>
    <div class="min-h-screen">
      <div class="max-w-[1280px] mx-auto px-6 lg:px-10 py-6">
        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 text-[13px] text-[color:var(--color-ink-muted)] mb-6">
          <button @click="handleClick1" class="hover:text-[color:var(--color-ink)] transition-colors">Products</button>
          <span class="text-[color:var(--color-ink-subtle)]">/</span>
          <button @click="handleClick2" class="hover:text-[color:var(--color-ink)] transition-colors">{{ product.category?.name || 'N/A' }}</button>
          <span class="text-[color:var(--color-ink-subtle)]">/</span>
          <span class="text-[color:var(--color-ink)] font-medium">{{ product.name || 'N/A' }}</span>
        </div>
        <div class="bg-white rounded-[16px] border border-[color:var(--color-hairline)] shadow-[var(--shadow-sm)] mb-8">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 p-6 md:p-8">
            <!-- Left: Product Image -->
            <div>
              <div class="aspect-square bg-gray-50 rounded-lg p-12 sticky top-24">
                <img
                  v-if="product.image_url"
                  :src="product.image_url"
                  :alt="product.name"
                  class="w-full h-full object-contain flex items-center justify-center rounded-lg select-none"
                />
                <div v-else class="w-full aspect-square bg-gray-50 rounded-lg flex items-center justify-center">
                  <!-- Vial illustration placeholder -->
                  <svg class="w-64 h-64 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                  </svg>
                </div>
              </div>
            </div>
  
            <!-- Right: Product Details -->
            <div>
              <!-- Product Identifier Tag -->
              <div v-if="product.category" class="inline-block bg-slate-100 text-slate-700 px-3 py-1.5 rounded text-sm mb-3">
                {{ product.category.name }}
              </div>
  
              <!-- Product Title -->
              <h1 class="ui-display text-[28px] md:text-3xl font-semibold tracking-[-0.02em] text-[color:var(--color-ink)] mb-2">{{ product.name }}</h1>

              <!-- Trust badges — surfaced from buried features, visible immediately -->
              <div class="flex flex-wrap gap-2 mb-3">
                <span v-if="product.lab_tested" class="inline-flex items-center gap-1 px-2 py-1 rounded-[6px] bg-[color:var(--color-verified-bg)] text-[color:var(--color-verified)] text-[11px] font-semibold">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  Lab tested
                </span>
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-[6px] bg-[color:var(--color-accent-50)] text-[color:var(--color-accent-700)] text-[11px] font-semibold">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M9 2v6l-4 7c-1 1.8 0 4 2 4h10c2 0 3-2.2 2-4l-4-7V2"/><path d="M8 2h8"/></svg>
                  COA available
                </span>
                <span v-if="product.purity" class="inline-flex items-center gap-1 px-2 py-1 rounded-[6px] bg-[color:var(--color-hairline-soft)] text-[color:var(--color-ink-muted)] text-[11px] font-semibold ui-mono">
                  {{ product.purity }}% purity
                </span>
              </div>
  
              <!-- Brand/Seller -->
              <Link                
                :href="`/brand/${brand.slug}/products`"
                class="text-blue-600 hover:text-blue-700 mb-4 flex items-center gap-2"
              >
                by {{ brand.name }}
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-external-link w-4 h-4" aria-hidden="true">
                  <path d="M15 3h6v6"></path>
                  <path d="M10 14 21 3"></path>
                  <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                </svg>
              </Link>
  
              <!-- Rating -->
              <div class="flex items-center justify-between pb-4 mb-4 border-b border-gray-200">
                <div class="flex items-center gap-3">
                  <div class="flex items-center gap-1">
                    <svg
                      v-for="i in 5"
                      :key="i"
                      class="lucide lucide-star w-5 h-5"
                      :class="i <= Math.round(product.rating_average) ? 'fill-yellow-400 text-yellow-400' : 'text-gray-300'"
                      fill="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z" />
                    </svg>
                  </div>
                  <span class="text-gray-900">{{ product.rating_average }}</span>
                  <span class="text-gray-500">({{ product.rating_count }} reviews)</span>
                </div>
                <!-- Stock Status -->
                <span
                  :class="product.availability === 'in_stock' ? 'text-green-600' : 'text-red-600'"
                  class="text-sm"
                >
                  {{ product.availability === 'in_stock' ? 'In Stock' : 'Out of Stock' }}
                </span>
              </div>              
  
              <!-- Price — monospace, prominent -->
              <div class="mb-6">
                <div class="flex items-baseline gap-3">
                  <span class="ui-mono text-4xl font-bold text-[color:var(--color-ink)]">
                    ${{ product.discount_price || product.price }}
                  </span>
                  <span
                    v-if="product.discount_price && product.discount_price < product.price"
                    class="ui-mono text-lg text-[color:var(--color-ink-subtle)] line-through"
                  >
                    ${{ product.price }}
                  </span>
                </div>
                <div v-if="product.size_mg" class="ui-mono text-sm text-[color:var(--color-ink-muted)] mt-1">
                  {{ product.size_mg }}mg vial
                </div>
              </div>

              <!-- Purchase Button — primary CTA, clean -->
              <div v-if="product.product_url" class="mb-4">
                <a
                  :href="`/go/${product.id}`"
                  target="_blank"
                  rel="noopener noreferrer nofollow sponsored"
                  class="ui-focus w-full h-[52px] flex items-center justify-center gap-2 rounded-[13px] text-[15px] font-semibold text-white bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] shadow-[inset_0_1px_0_rgba(255,255,255,0.18),0_1px_2px_rgba(10,11,14,0.08),0_10px_24px_-8px_rgba(79,70,229,0.4)] hover:shadow-[inset_0_1px_0_rgba(255,255,255,0.22),0_2px_4px_rgba(10,11,14,0.1),0_14px_32px_-8px_rgba(79,70,229,0.55)] hover:-translate-y-[1px] active:translate-y-0 transition-all"
                >
                  Visit {{ brand.name }} to purchase
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M7 17L17 7M17 7H7M17 7v10" />
                  </svg>
                </a>
              </div>

              <!-- Compare link + discount code — secondary, below CTA -->
              <div class="flex items-center justify-between gap-4 mb-6">
                <a
                  v-if="product.category"
                  :href="`/compare#${product.category.slug || ''}`"
                  class="text-[13px] font-medium text-[color:var(--color-accent-600)] hover:text-[color:var(--color-accent-700)] transition-colors flex items-center gap-1"
                >
                  Compare prices for {{ product.category.name }}
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
                </a>
                <button
                  @click="copyDiscountCode"
                  class="ui-focus flex items-center gap-2 px-3 py-1.5 rounded-[8px] border border-dashed border-[color:var(--color-hairline)] text-[12px] font-semibold text-[color:var(--color-ink-muted)] hover:border-[color:var(--color-accent-400)] hover:text-[color:var(--color-ink)] transition-all"
                >
                  <span class="ui-mono">{{ brand?.discount_code || 'PMAP' }}</span>
                  <span class="text-[color:var(--color-ink-subtle)]">· copy</span>
                </button>
              </div>
  
              <!-- Seller Information -->
              <div v-if="brand" class="border-t border-gray-200 pt-6 mb-6">
                <div class="flex items-center gap-3 mb-3">
                  <div v-if="brand.logo">
                    <img :src="brand.logo" :alt="brand.name" class="w-12 h-full object-cover" />
                  </div>
                  <div v-else class="w-12 h-12 rounded-lg object-cover">
                    {{ brandInitials }}
                  </div>
                  <div>
                    <div class="text-sm text-gray-600">Sold by</div>
                    <a
                      v-if="brand.shop_url"
                      :href="`/brand/${brand.slug}/products`"
                      rel="noopener noreferrer"
                      class="text-blue-600 hover:text-blue-700"
                    >
                      {{ brand.name }}
                    </a>                    
                  </div>
                </div>
                <a                  
                  :href="`/brand/${brand.slug}/products`"
                  class="block w-full text-center border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg transition-colors text-sm"
                >
                  View All Products from {{ brand.name }}
                </a>
              </div>
  
              <!-- Disclaimer -->
              <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                <div class="flex items-start gap-2">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-alert w-4 h-4 text-yellow-600 flex-shrink-0 mt-0.5" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" x2="12" y1="8" y2="12"></line>
                    <line x1="12" x2="12.01" y1="16" y2="16"></line>
                  </svg>
                  <div class="text-xs text-yellow-800">
                    <p>
                      Research purposes only. Not intended for human consumption.
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Tabs Section -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-8">
          <!-- Tabs -->
          <div class="border-b border-gray-200">
            <div class="flex">
              <button
                @click="activeTab = 'description'"
                :class="activeTab === 'description' ? 'text-blue-600' : 'text-gray-600 hover:text-gray-900'"
                class="px-6 py-4 text-sm transition-colors relative"
              >
                Description
                <div v-if="activeTab === 'description'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-blue-600"></div>
              </button>
              <button
                @click="activeTab = 'reviews'"
                :class="activeTab === 'reviews' ? 'text-blue-600' : 'text-gray-600 hover:text-gray-900'"
                class="px-6 py-4 text-sm transition-colors relative"
              >
                Reviews ({{ product.rating_count }})
                <div v-if="activeTab === 'reviews'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-blue-600"></div>
              </button>
            </div>
          </div>

          <!-- Tab Content -->
          <div class="p-8">
            <!-- Description Tab -->
            <div v-if="activeTab === 'description'" class="text-gray-700 space-y-4 max-w-4xl">
              <p>
                {{ product.name }} from {{ brand?.name || 'our store' }} is a research peptide designed for scientific and research purposes only.
              </p>
              <p>
                This product undergoes third-party testing to ensure quality. Each batch comes with a certificate of analysis (COA) available upon request.
              </p>
              <div class="bg-slate-50 rounded-lg p-6 mt-6">
                <h3 class="text-lg text-gray-900 mb-4">Product Information</h3>
                <div class="space-y-3">
                  <div class="flex justify-between py-2 border-b border-gray-200">
                    <span class="text-gray-600">Category:</span>
                    <span class="text-gray-900">{{ product.category?.name || 'N/A' }}</span>
                  </div>
                  <div class="flex justify-between py-2 border-b border-gray-200">
                    <span class="text-gray-600">Brand:</span>
                    <span class="text-gray-900">{{ brand?.name || 'N/A' }}</span>
                  </div>
                  <div class="flex justify-between py-2">
                    <span class="text-gray-600">Stock Status:</span>
                    <span
                      :class="product.availability === 'in_stock' ? 'text-green-600' : 'text-red-600'"
                      class="font-medium"
                    >
                      {{ product.availability === 'in_stock' ? 'In Stock' : 'Out of Stock' }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Reviews Tab -->
            <div v-if="activeTab === 'reviews'" class="max-w-4xl">
              <!-- Reviews List -->
              <div v-if="reviews && reviews.length > 0" class="space-y-6">
                <div 
                  v-for="review in reviews" 
                  :key="review.id"
                  class="bg-white border border-gray-200 rounded-lg p-6"
                >
                  <div class="flex items-start justify-between mb-4">
                    <div>
                      <div class="flex items-center gap-2 mb-2">
                        <span class="text-gray-900 font-medium">{{ review.user_name }}</span>
                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs">
                          Verified Purchase
                        </span>
                      </div>
                      <div class="flex items-center gap-1">
                        <svg 
                          v-for="i in 5" 
                          :key="i" 
                          xmlns="http://www.w3.org/2000/svg" 
                          width="24" 
                          height="24" 
                          viewBox="0 0 24 24" 
                          fill="none" 
                          stroke="currentColor" 
                          stroke-width="2" 
                          stroke-linecap="round" 
                          stroke-linejoin="round" 
                          :class="i <= review.rating ? 'text-yellow-400 fill-yellow-400' : 'text-gray-300'"
                          class="lucide lucide-star w-4 h-4"
                        >
                          <path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"></path>
                        </svg>
                      </div>
                    </div>
                    <span class="text-sm text-gray-500">{{ review.created_at }}</span>
                  </div>
                  <p v-if="review.review" class="text-gray-700">{{ review.review }}</p>
                </div>
              </div>
              
              <!-- No Reviews Message -->
              <div v-else class="text-center py-12">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-star w-12 h-12 text-gray-300 mx-auto mb-4" aria-hidden="true">
                  <path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"></path>
                </svg>
                <p class="text-gray-500 mb-2">No reviews yet</p>
                <p class="text-sm text-gray-400">Be the first to review this product!</p>
              </div>
            </div>
          </div>
        </div>        

        <!-- Related Products -->
        <div v-if="relatedProducts && relatedProducts.length > 0" class="bg-white rounded-lg border border-gray-200 p-8">
          <h2 class="text-2xl text-gray-900 mb-6">Related Products</h2>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <ProductSimpleDetailCard
              v-for="relatedProduct in relatedProducts"
              :key="relatedProduct.id"
              :name="relatedProduct.name"
              :image-url="relatedProduct.image_url"
              :price="relatedProduct.price"
              :discount-price="relatedProduct.discount_price"
              :brand-name="relatedProduct.brand?.name"              
              :to="`/product/${relatedProduct.slug}/${relatedProduct.id}`"
            />
          </div>
        </div>
      </div>
    </div>
  </ModernLayout>
</template>

<script setup>
import { ref, computed, watchEffect } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import ModernLayout from '@/Pages/Layouts/ModernLayout.vue'
import ProductSimpleDetailCard from '@/components/ProductSimpleDetailCard.vue'

const props = defineProps({
  product: Object,
  brand: Object,
  relatedProducts: Array,
  reviews: Array,
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

// Computed values for reactive SEO updates (automatically from product data)
const title = computed(() => {
  // Use SEO title if provided, otherwise generate from product name
  if (props.seo?.title) {
    return props.seo.title
  }
  const siteName = page.props.site_name || 'PeptideMap'
  const vendorName = props.brand?.name || 'our store'
  return `Buy ${props.product?.name || 'Product'} from ${vendorName} - ${siteName}`
})

const description = computed(() => {
  // Use SEO description if provided, otherwise generate from product description
  if (props.seo?.description) {
    return props.seo.description
  }
  if (props.product?.description) {
    // Truncate to ~155 chars
    const desc = props.product.description.replace(/\s+/g, ' ').trim()
    return desc.length > 155 ? desc.substring(0, 155) + '...' : desc
  }
  return 'View detailed information about ' + (props.product?.name || 'this product') + '. Compare prices, read reviews, and find the best deals.'
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
  // Use SEO og_image if provided, otherwise use product image
  return props.seo?.og_image || props.seo?.image || props.product?.image_url || null
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

const activeTab = ref('description')

// Calculate brand initials from brand name
const brandInitials = computed(() => {
  try {
    if (!props.brand || !props.brand.name) {
      return 'PS'
    }
    
    // Always calculate from brand name to ensure correct initials
    const name = String(props.brand.name).trim()
    if (!name || name.length === 0) {
      return 'PS'
    }
    
    // Split by spaces and filter out empty strings
    const words = name.split(/\s+/).filter(word => word && word.length > 0)
    
    if (words.length >= 2) {
      // Take first letter of first two words
      const firstWord = words[0]
      const secondWord = words[1]
      const firstLetter = firstWord && firstWord.length > 0 ? firstWord[0].toUpperCase() : ''
      const secondLetter = secondWord && secondWord.length > 0 ? secondWord[0].toUpperCase() : ''
      
      if (firstLetter && secondLetter) {
        return firstLetter + secondLetter
      }
    }
    
    if (words.length === 1) {
      // If only one word, take first two characters
      const word = words[0]
      if (word && word.length >= 2) {
        return word.substring(0, 2).toUpperCase()
      } else if (word && word.length === 1) {
        // If word is only one character, repeat it
        return (word[0] + word[0]).toUpperCase()
      }
    }
    
    // Fallback: take first two characters of the name
    if (name.length >= 2) {
      return name.substring(0, 2).toUpperCase()
    } else if (name.length === 1) {
      return (name[0] + name[0]).toUpperCase()
    }
    
    return 'PS'
  } catch (error) {
    console.error('Error calculating brand initials:', error)
    return 'PS'
  }
})

const copyDiscountCode = async () => {
  const code = props.brand?.discount_code || 'PMAP'
  
  // Try modern Clipboard API first (requires secure context)
  if (navigator.clipboard && navigator.clipboard.writeText) {
    try {
      await navigator.clipboard.writeText(code)
    alert('Discount code copied to clipboard!')
      return
    } catch (err) {
      console.warn('Clipboard API failed, trying fallback:', err)
    }
  }
  
  // Fallback method for non-secure contexts or older browsers
  try {
    // Create a temporary textarea element
    const textarea = document.createElement('textarea')
    textarea.value = code
    textarea.style.position = 'fixed'
    textarea.style.left = '-999999px'
    textarea.style.top = '-999999px'
    document.body.appendChild(textarea)
    textarea.focus()
    textarea.select()
    
    // Try to copy using execCommand (deprecated but widely supported)
    const successful = document.execCommand('copy')
    document.body.removeChild(textarea)
    
    if (successful) {
      alert('Discount code copied to clipboard!')
    } else {
      // Last resort: show the code and ask user to copy manually
      prompt('Please copy this discount code:', code)
    }
  } catch (err) {
    console.error('Fallback copy method failed:', err)
    // Last resort: show the code and ask user to copy manually
    prompt('Please copy this discount code:', code)
  }
}

const handleClick1 = () => {
  router.visit('/products')
}

const handleClick2 = () => {
  if (props.product?.category?.slug) {
    router.visit(`/product/${props.product.category.slug}`)
  }
}
</script>
