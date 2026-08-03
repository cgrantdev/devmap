<template>
    <div
      class="bg-white border border-gray-100 rounded-xl hover:border-gray-300 hover:shadow-lg transition-all cursor-pointer overflow-hidden group relative"
      @click="handleClick"
    >
      <!-- Wishlist heart — top-right, layered above image -->
      <div v-if="productId" class="absolute top-2 right-2 z-10">
        <WishlistHeart type="product" :id="productId" size="md" />
      </div>

      <!-- Top Section: Product Image — object-contain so vials/boxes fit
           inside the card without cropping. Light bg so transparent PNGs
           don't disappear, with a gentle hover scale. -->
      <div class="aspect-square bg-gray-50 p-3 flex items-center justify-center">
        <img
          v-if="imageUrl && !hasError"
          :src="imageUrl"
          :alt="name"
          class="max-w-full max-h-full object-contain select-none group-hover:scale-[1.04] transition-transform duration-300"
          loading="lazy"
          @error="onError"
        />
        <div v-else>
          <svg
            class="w-full h-full flex items-center justify-center"
            viewBox="0 0 200 200"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <rect x="70" y="30" width="60" height="15" fill="#687280" stroke="#374151" stroke-width="2"></rect>
            <rect x="75" y="45" width="50" height="10" fill="#9CA3AF" stroke="#374151" stroke-width="2"></rect>
            <rect x="60" y="55" width="80" height="110" rx="8" fill="#E5E7EB" stroke="#374151" stroke-width="2"></rect>
            <rect x="65" y="100" width= "70" height="60" rx="6" fill="#3B82F6" fill-opacity= "0.3"></rect>
            <rect x="70" y="80" width="60" height="30" fill="white" stroke="#9CA3AF" stroke-width="1"></rect>
            <line x1="75" y1="88" x2="125" y2="88" stroke="#D1D5DB" stroke-width="2"></line>
            <line x1="75" y1="95" x2="115" y2="95" stroke="#D1D5DB" stroke-width="2"></line>
            <line x1="75" y1="102" x2="120" y2="102" stroke="#D1D5DB" stroke-width="2"></line>
            <line x1="145" y1="70" x2="150" y2="70" stroke="#9CA3AF" stroke-width="1"></line>
            <line x1="145" y1="90" x2="150" y2="90" stroke="#9CA3AF" stroke-width="1"></line>
            <line x1="145" y1="110" x2="150" y2="110" stroke="#9CA3AF" stroke-width="1"></line>
            <line x1="145" y1="130" x2="150" y2="130" stroke="#9CA3AF" stroke-width="1"></line>
            <line x1="145" y1="150" x2="150" y2="150" stroke="#9CA3AF" stroke-width="1"></line>
          </svg>
        </div>
      </div>
  
      <!-- Bottom Section: Product Information -->
      <div class="p-4">  
        <!-- Product Title + Type chip -->
        <div class="flex items-start gap-1.5 mb-2">
          <h3 class="text-sm text-gray-900 line-clamp-2 group-hover:text-blue-600 transition-colors flex-1 min-w-0">
            {{ name }}
          </h3>
          <span
            v-if="typeChip"
            :class="['flex-shrink-0 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold leading-tight mt-0.5', typeChip.classes]"
          >{{ typeChip.label }}</span>
        </div>
  
        <!-- Rating -->
        <div class="flex items-center gap-1 mb-2">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-star w-3 h-3 fill-yellow-400 text-yellow-400" aria-hidden="true">
            <path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"></path>
          </svg>
          <span class="text-xs text-gray-900">
            {{ formattedRating }}
          </span>
          <span class="text-xs text-gray-500">
            ({{ ratingCount || 0 }})
          </span>
        </div>
  
        <!-- Price -->
        <div>
          <template v-if="discountedPrice">
            <div class="flex items-baseline gap-2">
              <span class="text-[11px] uppercase tracking-wide text-gray-700 font-semibold leading-tight">Retail</span>
              <span class="text-base text-gray-700 line-through leading-tight">${{ displayPrice }}</span>
            </div>
            <div class="text-[11px] uppercase tracking-wide text-emerald-700 font-semibold mt-1.5 leading-tight">
              Price with code <span class="ui-mono">{{ effectiveCouponCode }}</span>
            </div>
            <div class="text-xl text-emerald-700 font-bold leading-tight">${{ discountedPrice }}</div>
          </template>
          <div v-else class="text-lg text-gray-900">${{ displayPrice }}</div>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { computed, ref } from 'vue'
  import { router } from '@inertiajs/vue3'
  import WishlistHeart from '@/components/ui/WishlistHeart.vue'

  const props = defineProps({
    name: { type: String, required: true },
    imageUrl: { type: String, default: null },
    price: { type: [String, Number], default: 0 },
    discountPrice: { type: [String, Number], default: null },
    brandName: { type: String, default: '' },
    ratingAverage: { type: [String, Number], default: 0 },
    ratingCount: { type: [String, Number], default: 0 },
    productType: { type: String, default: null },
    brandDiscountPercent: { type: [String, Number], default: null },
    brandCouponCode: { type: String, default: null },
    // Optional — when provided, renders a wishlist heart in the top-right.
    productId: { type: [Number, String], default: null },
    to: { type: String, required: true },
  })

  // Color-coded format chip rendered next to the product name. Only
  // Capsule + Nasal Spray render — Peptide is the implicit default.
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
    
  // Format rating
  const formattedRating = computed(() => {
    const rating = parseFloat(props.ratingAverage) || 0
    return rating.toFixed(1)
  })
  
  // Vendor's listed price (retail reference).
  const displayPrice = computed(() => {
    const price = props.discountPrice || props.price || 0
    return parseFloat(price).toFixed(2)
  })

  // Effective coupon code shown on the label — falls back to PMAP.
  const effectiveCouponCode = computed(() => {
    const raw = (props.brandCouponCode || '').trim()
    return (raw || 'PMAP').toUpperCase()
  })

  // Discounted price = retail × (1 − discount%). Null when no discount
  // or math degenerates — card falls back to plain retail price.
  const discountedPrice = computed(() => {
    const pct = parseFloat(props.brandDiscountPercent)
    if (!pct || pct <= 0 || pct >= 100) return null
    const retail = parseFloat(props.discountPrice || props.price || 0)
    if (!retail || retail <= 0) return null
    return (retail * (1 - pct / 100)).toFixed(2)
  })
  
  const handleClick = () => {
    router.visit(props.to)
  }
  </script>
  