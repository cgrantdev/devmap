<template>
  <FrontLayout>
    <section class="py-8 bg-gray-50">
      <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
          <!-- Left: Product Image -->
          <div class="bg-white rounded-lg p-8 flex items-center justify-center">
            <div class="w-full max-w-md">
              <img
                v-if="product.image_url"
                :src="product.image_url"
                :alt="product.name"
                class="w-full h-auto object-contain"
              />
              <div v-else class="w-full aspect-square bg-gray-100 rounded-lg flex items-center justify-center">
                <!-- Vial illustration placeholder -->
                <svg class="w-64 h-64 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
              </div>
            </div>
          </div>

          <!-- Right: Product Details -->
          <div class="space-y-6">
            <!-- Product Identifier Tag -->
            <div v-if="product.category" class="inline-block">
              <span class="bg-gray-200 text-gray-700 px-3 py-1 rounded text-sm font-medium">
                {{ product.category.name }}
              </span>
            </div>

            <!-- Product Title -->
            <h1 class="text-4xl font-bold text-gray-800">{{ product.name }}</h1>

            <!-- Brand/Seller -->
            <div v-if="brand" class="flex items-center gap-2">
              <span class="text-gray-600">by</span>
              <Link
                v-if="brand.slug && brand.is_active"
                :href="`/brand/${brand.slug}/products`"
                class="text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1"
              >
                {{ brand.name }}
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
              </Link>
              <span v-else class="text-blue-600 font-medium">{{ brand.name }}</span>
            </div>

            <!-- Rating -->
            <div class="flex items-center gap-2">
              <div class="flex items-center">
                <svg
                  v-for="i in 5"
                  :key="i"
                  class="w-5 h-5"
                  :class="i <= Math.round(product.rating_average) ? 'text-yellow-500' : 'text-gray-300'"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
              </div>
              <span class="text-gray-700 font-medium">{{ product.rating_average }}</span>
              <span class="text-gray-500">({{ product.rating_count }} reviews)</span>
            </div>

            <!-- Stock Status -->
            <div>
              <span
                :class="product.availability === 'in_stock' ? 'text-green-600' : 'text-red-600'"
                class="font-semibold"
              >
                {{ product.availability === 'in_stock' ? 'In Stock' : 'Out of Stock' }}
              </span>
            </div>

            <!-- Price -->
            <div>
              <p class="text-4xl font-bold text-gray-800">
                ${{ product.discount_price || product.price }}
              </p>
              <p v-if="product.discount_price" class="text-lg text-gray-500 line-through">
                ${{ product.price }}
              </p>
            </div>

            <!-- Product Features -->
            <div class="space-y-2">
              <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span class="text-gray-700">Third-party lab tested</span>
              </div>
              <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span class="text-gray-700">Certificate of Analysis available</span>
              </div>
              <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span class="text-gray-700">Research grade quality</span>
              </div>
            </div>

            <!-- Discount Code -->
            <div v-if="brand && brand.shop_url" class="border-2 border-dashed border-green-500 rounded-lg p-4 bg-green-50">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                  </svg>
                  <span class="text-gray-700 font-medium">Use Code: PMAP</span>
                </div>
                <button
                  @click="copyDiscountCode"
                  class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                >
                  Click to Copy
                </button>
              </div>
            </div>

            <!-- Purchase Button -->
            <div v-if="product.product_url">
              <a
                :href="product.product_url"
                target="_blank"
                rel="noopener noreferrer"
                class="block w-full bg-blue-700 hover:bg-blue-800 text-white font-medium py-3 px-6 rounded-lg text-center flex items-center justify-center gap-2 transition-colors"
              >
                Visit {{ brand.name }} to Purchase
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
              </a>
            </div>

            <!-- Seller Information -->
            <div v-if="brand" class="border-t border-gray-200 pt-6">
              <div class="flex items-center gap-4 mb-4">
                <!--<div
                  v-if="brand.logo"
                  class="w-12 h-12 rounded-lg overflow-hidden flex items-center justify-center"
                  :style="{ backgroundColor: '#3b82f6' }"
                >
                  <img :src="brand.logo" :alt="brand.name" class="w-full h-full object-contain" />
                </div> -->
                <div
                  class="w-12 h-12 rounded-lg flex items-center justify-center text-white font-bold"
                  style="background-color: #3b82f6;"
                >
                  {{ brandInitials }}
                </div>
                <div>
                  <p class="text-sm text-gray-600 mb-1">Sold by</p>
                  <a
                    v-if="brand.shop_url"
                    :href="brand.shop_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-blue-600 hover:text-blue-800 font-medium"
                  >
                    {{ brand.name }}
                  </a>
                  <Link
                    v-else-if="brand.slug && brand.is_active"
                    :href="`/brand/${brand.slug}/products`"
                    class="text-blue-600 hover:text-blue-800 font-medium"
                  >
                    {{ brand.name }}
                  </Link>
                  <span v-else class="text-blue-600 font-medium">{{ brand.name }}</span>
                </div>
              </div>
              <Link
                v-if="brand.slug && brand.is_active"
                :href="`/brand/${brand.slug}/products`"
                class="text-blue-600 hover:text-blue-800 text-sm font-medium"
              >
                View All Products from {{ brand.name }}
              </Link>
            </div>

            <!-- Disclaimer -->
            <div class="bg-yellow-50 border border-yellow-400 rounded-lg p-4">
              <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <p class="text-sm text-gray-800">
                  Research purposes only. Not intended for human consumption.
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Tabs Section -->
        <div class="bg-white rounded-lg shadow-md mb-8">
          <!-- Tabs -->
          <div class="border-b border-gray-200">
            <div class="flex">
              <button
                @click="activeTab = 'description'"
                :class="activeTab === 'description' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 hover:text-gray-800'"
                class="px-6 py-4 font-medium text-sm transition-colors"
              >
                Description
              </button>
              <button
                @click="activeTab = 'reviews'"
                :class="activeTab === 'reviews' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 hover:text-gray-800'"
                class="px-6 py-4 font-medium text-sm transition-colors"
              >
                Reviews ({{ product.rating_count }})
              </button>
            </div>
          </div>

          <!-- Tab Content -->
          <div class="p-6">
            <!-- Description Tab -->
            <div v-if="activeTab === 'description'">
              <p class="text-gray-700 mb-4">
                {{ product.name }} from {{ brand?.name || 'our store' }} is a research peptide designed for scientific and research purposes only.
              </p>
              <p class="text-gray-700">
                This product undergoes third-party testing to ensure quality. Each batch comes with a certificate of analysis (COA) available upon request.
              </p>
            </div>

            <!-- Reviews Tab -->
            <div v-if="activeTab === 'reviews'">
              <p class="text-gray-500">No reviews yet. Be the first to review this product!</p>
            </div>
          </div>
        </div>

        <!-- Product Information Table -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
          <h2 class="text-xl font-semibold text-gray-800 mb-4">Product Information</h2>
          <div class="space-y-3">
            <div class="flex">
              <span class="w-32 text-gray-600 font-medium">Category:</span>
              <span class="text-gray-800">{{ product.category?.name || 'N/A' }}</span>
            </div>
            <div class="flex">
              <span class="w-32 text-gray-600 font-medium">Brand:</span>
              <span class="text-gray-800">{{ brand?.name || 'N/A' }}</span>
            </div>
            <div class="flex">
              <span class="w-32 text-gray-600 font-medium">Stock Status:</span>
              <span
                :class="product.availability === 'in_stock' ? 'text-green-600' : 'text-red-600'"
                class="font-medium"
              >
                {{ product.availability === 'in_stock' ? 'In Stock' : 'Out of Stock' }}
              </span>
            </div>
          </div>
        </div>

        <!-- Related Products -->
        <div v-if="relatedProducts && relatedProducts.length > 0" class="mb-8">
          <h2 class="text-2xl font-semibold text-gray-800 mb-6">Related Products</h2>
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <ProductCard
              v-for="relatedProduct in relatedProducts"
              :key="relatedProduct.id"
              :name="relatedProduct.name"
              :image-url="relatedProduct.image_url"
              :price="relatedProduct.price"
              :discount-price="relatedProduct.discount_price"
              :brand-name="relatedProduct.brand?.name"
              :rating-average="relatedProduct.rating_average || 0"
              :rating-count="relatedProduct.rating_count || 0"
              :category-name="relatedProduct.category?.name || ''"
              :size-mg="relatedProduct.size_mg"
              :availability="relatedProduct.availability"
              :to="`/product/${relatedProduct.slug}/${relatedProduct.id}`"
            />
          </div>
        </div>
      </div>
    </section>
  </FrontLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import FrontLayout from '../Layouts/FrontLayout.vue'
import ProductCard from '@/components/ProductCard.vue'

const props = defineProps({
  product: Object,
  brand: Object,
  relatedProducts: Array,
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
// console.log(props)
</script>
