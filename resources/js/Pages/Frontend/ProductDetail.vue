<template>
  <FrontLayout>
    <div class="min-h-screen bg-gray-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex items-center gap-2 text-sm text-gray-600 mb-6">
          <button 
            @click="handleClick1"
            class="hover:text-gray-900">Products
          </button>
          <span>/</span>
          <button 
            @click="handleClick2"
            class="hover:text-gray-900">{{ product.category?.name || 'N/A' }}
          </button>
          <span>/</span>
          <span class="text-gray-900">{{ product.name || 'N/A' }}</span>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 mb-8">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 p-8">
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
              <h1 class="text-3xl text-gray-900 mb-3">{{ product.name }}</h1>
  
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
  
              <!-- Price -->
              <div class="mb-6">
                <!-- <p class="text-4xl text-gray-900 mb-1">
                  ${{ product.discount_price || product.price }}
                </p> -->
                <div class="text-4xl text-gray-900 mb-1">
                  ${{ product.price }}
                </div>
              </div>
  
              <!-- Product Features -->
              <div class="space-y-2 mb-6">
                <div class="flex items-center gap-2 text-sm text-gray-700">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check-big w-4 h-4 text-green-600" aria-hidden="true">
                    <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                    <path d="m9 11 3 3L22 4"></path>
                  </svg>
                  <span>Third-party lab tested</span>
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-700">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check-big w-4 h-4 text-green-600" aria-hidden="true">
                    <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                    <path d="m9 11 3 3L22 4"></path>
                  </svg>
                  <span>Certificate of Analysis available</span>
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-700">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check-big w-4 h-4 text-green-600" aria-hidden="true">
                    <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
                    <path d="m9 11 3 3L22 4"></path>
                  </svg>
                  <span>Research grade quality</span>
                </div>
              </div>
  
              <!-- Discount Code -->
              <button
                @click="copyDiscountCode"
                class="w-full mb-6 px-6 py-4 rounded-lg flex items-center justify-between transition-all border-[3px] border-dashed border-green-600 bg-green-50 hover:bg-green-100 text-green-700 hover:text-green-800 group cursor-pointer"
              >
                <div class="flex items-center gap-3">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-tag w-5 h-5" aria-hidden="true">
                    <path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"></path>
                    <circle cx="7.5" cy="7.5" r=".5" fill="currentColr"></circle>
                  </svg>
                  <span class="text-sm font-semibold">Use Code:</span>
                  <span class="font-mono tracking-wider font-bold">PMAP</span>
                </div>                
                <span class="text-xs opacity-75">Click to Copy</span>
              </button>              
  
              <!-- Purchase Button -->
              <div v-if="product.product_url">
                <a
                  :href="product.product_url"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="w-full bg-blue-600 hover:bg-blue-700 text-white px-6 py-4 rounded-lg transition-colors flex items-center justify-center gap-2 mb-6"
                >
                  Visit {{ brand.name }} to Purchase
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-external-link w-5 h-5" aria-hidden="true">
                    <path d="M15 3h6v6"></path>
                    <path d="M10 14 21 3"></path>
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                  </svg>
                </a>
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
  </FrontLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import FrontLayout from '../Layouts/FrontLayout.vue'
import ProductSimpleDetailCard from '@/components/ProductSimpleDetailCard.vue'

const props = defineProps({
  product: Object,
  brand: Object,
  relatedProducts: Array,
  reviews: Array,
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

const copyDiscountCode = () => {
  navigator.clipboard.writeText('PMAP').then(() => {
    alert('Discount code copied to clipboard!')
  }).catch(() => {
    alert('Failed to copy discount code')
  })
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
