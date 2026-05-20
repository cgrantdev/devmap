<template>
  <div
    @click="handleClick"
    class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-xl transition-all cursor-pointer group relative"
  >
    <!-- Top Section: Product Image -->
    <div class="aspect-square bg-gradient-to-br from-blue-50 to-purple-50 p-6 border-b border-gray-200 flex items-center justify-center"> 
      <img
        v-if="imageUrl && !hasError"
        :src="imageUrl"
        :alt="name"
        class="w-full h-full object-contain rounded-lg flex items-center justify-center select-none"
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

    <!-- Bottom Section: Product Information -->
    <div class="p-4">
      <!-- Category Tag -->
      <!-- <div class="inline-block bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs mb-2">
        {{ categoryName }}
      </div> -->

      <!-- Product Title + Type chip -->
      <div class="flex items-start gap-1.5 mb-1">
        <h3 class="text-sm text-gray-900 line-clamp-2 group-hover:text-blue-600 transition-colors flex-1 min-w-0">
          {{ name }}
        </h3>
        <span
          v-if="typeChip"
          :class="['flex-shrink-0 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold leading-tight mt-0.5', typeChip.classes]"
        >{{ typeChip.label }}</span>
      </div>

      <!-- Brand Name -->
      <p class="text-xs text-gray-500 mb-2">
        {{ brandName || 'Unknown Brand' }}
      </p>

      <!-- Specification Tags -->
      <!-- <div class="flex items-center gap-2 mb-2 text-xs text-gray-600">
        <span v-if="purity" class="bg-green-50 text-green-700 px-2 py-0.5 rounded">
          {{ purity }}% Pure
        </span>
        <span v-if="sizeDisplay" class="bg-gray-100 text-gray-700 px-2 py-0.5 rounded">
          {{ sizeDisplay }}
        </span>
      </div> -->

      <!-- Stock Status -->
      <!-- <div class="mb-3">
        <div
          class="inline-flex items-center gap-1.5 bg-green-50 text-green-700 border-green-200 border rounded-full text-xs px-2 py-1"
          :class="isInStock ? 'border-green-200 text-green-700' : 'border-red-200 text-red-700'"
        >
          <div 
            class="bg-green-500 w-1.5 h-1.5 rounded-full animate-pulse"
            :class="isInStock ? 'bg-green-500' : 'bg-red-500'"
          ></div>
          <span class="font-medium">
            {{ stockStatus }}
          </span>
        </div>
      </div> -->

      <!-- Price -->
      <div class="text-lg text-gray-900 font-semibold mb-3">
        ${{ displayPrice }}
      </div>

      <!-- View Details Button -->
      <div class="flex gap-2">
        <button
          @click="handleClick"
          class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded text-sm flex items-center justify-center gap-2 transition-colors"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shopping-cart w-4 h-4 flex-shrink-0" aria-hidden="true">
            <circle cx="8" cy="21" r="1"></circle>
            <circle cx="19" cy="21" r="1"></circle>
            <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"></path>
          </svg>
          View Product
        </button>
      </div>
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
  purity: { type: [String, Number], default: null },
  productType: { type: String, default: null },
})

// Color-coded format chip shown next to the product name. Only Capsule
// and Nasal Spray render — Peptide is the implicit default and 'Other'
// adds no useful info.
const typeChip = computed(() => {
  switch (props.productType) {
    case 'Capsule':
      return { label: 'Capsule', classes: 'bg-blue-100 text-blue-800 border border-blue-200' }
    case 'Nasal Spray':
      return { label: 'Nasal Spray', classes: 'bg-emerald-100 text-emerald-800 border border-emerald-200' }
    default:
      return null
  }
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

// Extract size display.
// size_mg is now stored with its unit baked in ("10mg", "5mg/5mg",
// "100mcg") — only fall back to appending "mg" when the value is
// a bare legacy number.
const sizeDisplay = computed(() => {
  if (props.sizeMg) {
    const str = String(props.sizeMg).trim()
    if (/[a-zA-Z]/.test(str)) return str
    if (!Number.isNaN(Number(str))) return `${Number(str)}mg`
    return str
  }
  // Try to extract from name
  const name = props.name || ''
  const match = name.match(/(\d+(?:\.\d+)?)\s*mg/i)
  if (match) {
    return `${match[1]}mg`
  }
  return null
})

// Use real purity from database, or extract from name, or use default
const purity = computed(() => {
  // First, use the purity prop if provided (from database)
  if (props.purity !== null && props.purity !== undefined && props.purity !== '') {
    return parseFloat(props.purity).toFixed(1)
  }
  
  // Otherwise, try to extract from name
  const name = props.name || ''
  const match = name.match(/(\d+(?:\.\d+)?)\s*%/i)
  if (match) {
    return parseFloat(match[1]).toFixed(1)
  }
  
  // Default purity if not found
  return null
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
