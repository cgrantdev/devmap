<template>
  <div
    class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-xl transition-all cursor-pointer group relative flex flex-col"
  >
    <!-- Top Section: Product Image -->
    <div class="aspect-square bg-gradient-to-br from-blue-50 to-purple-50 p-6 border-b border-gray-200 flex items-center justify-center"> 
      <div class="w-full h-48 bg-[#F0F4F8] flex items-center justify-center p-6 relative overflow-hidden">
        <img
          v-if="imageUrl && !hasError"
          :src="imageUrl"
          :alt="name"
          class="w-full h-full object-contain object-center"
          loading="lazy"
          @error="onError"
        />
        <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
          <svg
            class="w-32 h-40"
            viewBox="0 0 80 120"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <!-- Vial Body -->
            <rect x="20" y="20" width="40" height="80" rx="2" fill="white" stroke="#9CA3AF" stroke-width="1.5"/>
            
            <!-- Vial Cap -->
            <rect x="18" y="15" width="44" height="8" rx="1" fill="#4B5563"/>
            
            <!-- Liquid (fills ~2/3) -->
            <rect x="22" y="60" width="36" height="36" rx="1" fill="#93C5FD"/>
            
            <!-- Label on vial -->
            <rect x="26" y="70" width="28" height="16" rx="1" fill="white" opacity="0.9"/>
            <line x1="28" y1="74" x2="52" y2="74" stroke="#9CA3AF" stroke-width="0.5"/>
            <line x1="28" y1="78" x2="52" y2="78" stroke="#9CA3AF" stroke-width="0.5"/>
            <line x1="28" y1="82" x2="52" y2="82" stroke="#9CA3AF" stroke-width="0.5"/>
            
            <!-- Volume tick marks on right side -->
            <line x1="62" y1="30" x2="65" y2="30" stroke="#4B5563" stroke-width="1"/>
            <line x1="62" y1="45" x2="65" y2="45" stroke="#4B5563" stroke-width="1"/>
            <line x1="62" y1="60" x2="65" y2="60" stroke="#4B5563" stroke-width="1"/>
            <line x1="62" y1="75" x2="65" y2="75" stroke="#4B5563" stroke-width="1"/>
            <line x1="62" y1="90" x2="65" y2="90" stroke="#4B5563" stroke-width="1"/>
          </svg>
        </div>
      </div>
    </div>

    <!-- Bottom Section: Product Information -->
    <div class="p-4 flex flex-col gap-3 flex-1">
      <!-- Category Tag -->
      <div class="flex items-start justify-between">
        <span class="inline-block bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs mb-2">
          {{ categoryName }}
        </span>
      </div>

      <!-- Product Title -->
      <h3 class="text-sm text-gray-900 mb-1 line-clamp-2 group-hover:text-blue-600 transition-colors">
        {{ name }}
      </h3>

      <!-- Brand Name -->
      <p class="text-xs text-gray-500 mb-2">
        {{ brandName || 'Unknown Brand' }}
      </p>

      <!-- Specification Tags -->
      <div class="flex items-center gap-2 flex-wrap">
        <span v-if="purity" class="bg-green-50 text-green-700 px-2 py-0.5 rounded">
          {{ purity }}% Pure
        </span>
        <span v-if="sizeDisplay" class="bg-gray-100 text-gray-700 px-2 py-0.5 rounded">
          {{ sizeDisplay }}
        </span>
      </div>

      <!-- Rating -->
      <div class="flex items-center gap-1.5">
        <svg
          class="w-4 h-4 text-yellow-500"
          fill="currentColor"
          viewBox="0 0 20 20"
        >
          <path
            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
          />
        </svg>
        <span class="font-roboto font-normal text-sm leading-normal text-gray-800">
          {{ formattedRating }} ({{ ratingCount || 0 }})
        </span>
      </div>

      <!-- Stock Status -->
      <div class="inline-flex items-center gap-1.5 px-2.5 py-1">
        <div
          class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 border"
          :class="isInStock ? 'border-green-300 text-green-700' : 'border-red-300 text-red-700'"
        >
          <div 
            class="w-2 h-2 rounded-full"
            :class="isInStock ? 'bg-green-500' : 'bg-red-500'"
          ></div>
          <span class="font-medium">
            {{ stockStatus }}
          </span>
        </div>
      </div>

      <!-- Price -->
      <p class="font-roboto font-semibold text-2xl leading-normal text-gray-900 m-0 mb-3">
        ${{ displayPrice }}
      </p>

      <!-- View Details Button -->
      <button
        @click="handleClick"
        class="w-full bg-blue-600 hover:bg-blue-700 text-white rounded-lg py-2.5 px-4 font-roboto font-medium text-sm leading-normal flex items-center justify-center gap-2 transition-colors duration-200"
      >
        <svg
          class="w-4 h-4"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
          />
        </svg>
        View Details
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  name: { type: String, required: true },
  imageUrl: { type: String, default: null },
  price: { type: [String, Number], default: 0 },
  discountPrice: { type: [String, Number], default: null },
  brandName: { type: String, default: '' },
  ratingAverage: { type: [String, Number], default: 0 },
  ratingCount: { type: [String, Number], default: 0 },
  to: { type: String, required: true },
  categoryName: { type: String, default: '' },
  sizeMg: { type: [String, Number], default: null },
  availability: { type: String, default: 'in_stock' },
})

const hasError = ref(false)

const onError = () => {
  hasError.value = true
}

// Extract category name from product name if not provided
const categoryName = computed(() => {
  if (props.categoryName) return props.categoryName.toUpperCase()
  
  // Try to extract category from product name
  // Example: "BPC-157 5mg" -> "BPC-157"
  const name = props.name || ''
  const match = name.match(/^([A-Z0-9-]+(?:\s+[A-Z0-9-]+)?)/i)
  if (match) {
    return match[1].toUpperCase()
  }
  return 'PRODUCT'
})

// Extract size display
const sizeDisplay = computed(() => {
  if (props.sizeMg) {
    return `${props.sizeMg}mg`
  }
  // Try to extract from name
  const name = props.name || ''
  const match = name.match(/(\d+(?:\.\d+)?)\s*mg/i)
  if (match) {
    return `${match[1]}mg`
  }
  return null
})

// Extract purity from name or use default
const purity = computed(() => {
  const name = props.name || ''
  // Look for patterns like "99.2%", "99%", etc.
  const match = name.match(/(\d+(?:\.\d+)?)\s*%/i)
  if (match) {
    return parseFloat(match[1]).toFixed(1)
  }
  // Default purity if not found
  return '99.0'
})


// Format rating
const formattedRating = computed(() => {
  const rating = parseFloat(props.ratingAverage) || 0
  return rating.toFixed(1)
})

// Display price
const displayPrice = computed(() => {
  const price = props.discountPrice || props.price || 0
  return parseFloat(price).toFixed(2)
})

// Stock status
const stockStatus = computed(() => {
  if (props.availability === 'in_stock' || props.availability === 'available') {
    return 'In Stock'
  }
  return 'Out of Stock'
})

// Check if in stock
const isInStock = computed(() => {
  return props.availability === 'in_stock' || props.availability === 'available'
})

const handleClick = () => {
  router.visit(props.to)
}
</script>
