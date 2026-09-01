<template>
  <div
    @click="handleClick"
    class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-xl transition-all cursor-pointer group relative flex flex-col h-full"
  >
    <!-- Top Section: Product Image. aspect-[4/3] (was square) so cards
         aren't dominated by empty gradient when images fail to load. -->
    <div class="aspect-[4/3] bg-gradient-to-br from-blue-50 to-purple-50 p-4 border-b border-gray-200 flex items-center justify-center overflow-hidden">
      <img
        v-if="imageUrl && !hasError"
        :src="imageUrl"
        :alt="name"
        class="w-full h-full object-contain select-none"
        loading="lazy"
        @error="onError"
      />
      <svg
        v-else
        class="w-20 h-24 text-gray-300"
        viewBox="0 0 80 120"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
      >
        <rect x="20" y="20" width="40" height="80" rx="2" fill="white" stroke="currentColor" stroke-width="1.5"/>
        <rect x="18" y="15" width="44" height="8" rx="1" fill="currentColor"/>
        <rect x="22" y="60" width="36" height="36" rx="1" fill="#BFDBFE"/>
        <rect x="26" y="70" width="28" height="16" rx="1" fill="white" opacity="0.9"/>
      </svg>
    </div>

    <!-- Bottom Section: tighter padding + condensed price block. -->
    <div class="p-3 flex flex-col flex-1">
      <!-- Product name + optional type chip. min-h-[2.5rem] reserves 2
           lines so cards line up when some names wrap and some don't. -->
      <div class="flex items-start gap-1.5 min-h-[2.5rem]">
        <h3 class="text-[13px] font-semibold text-gray-900 line-clamp-2 leading-tight group-hover:text-blue-600 transition-colors flex-1 min-w-0">
          {{ name }}
        </h3>
        <span
          v-if="typeChip"
          :class="['flex-shrink-0 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold leading-tight', typeChip.classes]"
        >{{ typeChip.label }}</span>
      </div>

      <p class="text-[11px] text-gray-500 mt-0.5 truncate">{{ brandName || 'Unknown Brand' }}</p>

      <!-- Price — condensed. Discount branch: big new price + strikethrough
           retail inline + tiny 'with code PMAP' underline. Undiscounted:
           just the price. min-h keeps buttons aligned across a mixed grid. -->
      <div class="mt-2 mb-2 min-h-[46px] flex flex-col justify-end">
        <template v-if="discountedPrice">
          <div class="flex items-baseline gap-2 flex-wrap">
            <span class="text-lg font-bold text-emerald-700 leading-none">${{ discountedPrice }}</span>
            <span class="text-[12px] text-gray-400 line-through leading-none">${{ displayPrice }}</span>
          </div>
          <div class="text-[10px] uppercase tracking-wide text-emerald-700 font-semibold mt-1 leading-none">
            with code <span class="ui-mono">{{ effectiveCouponCode }}</span>
          </div>
        </template>
        <div v-else class="text-lg font-bold text-gray-900 leading-none">${{ displayPrice }}</div>
      </div>

      <button
        @click="handleClick"
        class="mt-auto h-8 rounded bg-blue-600 hover:bg-blue-700 text-white text-[12px] font-semibold flex items-center justify-center gap-1.5 transition-colors"
      >
        View Product
        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17L17 7M17 7H7M17 7v10"/></svg>
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
  purity: { type: [String, Number], default: null },
  productType: { type: String, default: null },
  brandDiscountPercent: { type: [String, Number], default: null },
  brandCouponCode: { type: String, default: null },
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
    case 'Kit':
      return { label: 'Kit', classes: 'bg-amber-100 text-amber-800 border border-amber-200' }
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

// Display price (vendor's listed price — the "retail" reference)
const displayPrice = computed(() => {
  const price = props.discountPrice || props.price || 0
  return parseFloat(price).toFixed(2)
})

// Effective coupon code shown on the label — falls back to PMAP if the
// brand hasn't set one. Always upper-cased for visual consistency.
const effectiveCouponCode = computed(() => {
  const raw = (props.brandCouponCode || '').trim()
  return (raw || 'PMAP').toUpperCase()
})

// Discounted price = retail × (1 − discount%). Null when no discount or
// math degenerates — card then falls back to plain retail price.
const discountedPrice = computed(() => {
  const pct = parseFloat(props.brandDiscountPercent)
  if (!pct || pct <= 0 || pct >= 100) return null
  const retail = parseFloat(props.discountPrice || props.price || 0)
  if (!retail || retail <= 0) return null
  return (retail * (1 - pct / 100)).toFixed(2)
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
