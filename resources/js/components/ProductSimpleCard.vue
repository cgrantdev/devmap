<template>
    <!-- The whole card is one real link: the title's ::after box stretches
         over the card. The wishlist heart and the coupon ticket sit above
         it (z-10); the ticket only copies the code. Rows have fixed
         heights so a grid lines up whatever the name or discount. -->
    <div
      class="group relative flex h-full flex-col overflow-hidden rounded-[14px] border border-[color:var(--color-hairline)] bg-white transition-all duration-200 hover:-translate-y-[1px] hover:border-[color:var(--color-accent-400)] hover:shadow-[var(--shadow-md)]"
    >
      <div v-if="productId" class="absolute right-2 top-2 z-10">
        <WishlistHeart type="product" :id="productId" size="md" />
      </div>

      <div class="relative flex aspect-square items-center justify-center overflow-hidden border-b border-[color:var(--color-hairline)] bg-[color:var(--color-hairline-soft)] p-4">
        <!-- Lowest USD $/mg for this vial size among these listings. -->
        <span
          v-if="bestValue"
          class="absolute left-2 top-2 z-[1] inline-flex h-5 items-center gap-1 rounded-full bg-emerald-600 px-1.5 text-[10px] font-bold uppercase tracking-[0.06em] text-white shadow-sm"
        >
          <svg class="h-2.5 w-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5" /></svg>
          Best value
        </span>
        <img
          v-if="imageUrl && !hasError"
          :src="imageUrl"
          :alt="name"
          class="max-h-full max-w-full select-none object-contain transition-transform duration-300 group-hover:scale-[1.03]"
          loading="lazy"
          decoding="async"
          @error="onError"
        />
        <svg v-else class="h-20 w-14 text-[color:var(--color-ink-subtle)] opacity-60" viewBox="0 0 56 80" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
          <rect x="16" y="4" width="24" height="9" rx="2" />
          <path d="M19 13v6c-5 2-9 6-9 12v38a6 6 0 0 0 6 6h24a6 6 0 0 0 6-6V31c0-6-4-10-9-12v-6" />
          <path d="M10 48h36" stroke-dasharray="3 3" />
        </svg>
      </div>

      <div class="flex flex-1 flex-col p-3.5">
        <div class="flex h-[2.5rem] items-start gap-1.5 overflow-hidden">
          <h3 class="ui-display min-w-0 flex-1 text-[14px] font-semibold leading-[1.25rem] tracking-tight text-[color:var(--color-ink)] line-clamp-2 transition-colors group-hover:text-[color:var(--color-accent-700)]">
            <Link
              :href="to"
              class="outline-none after:absolute after:inset-0 after:rounded-[14px] focus-visible:after:ring-2 focus-visible:after:ring-[color:var(--color-accent-500)]"
            >{{ name }}</Link>
          </h3>
          <span
            v-if="typeChip"
            :class="['mt-0.5 inline-flex flex-shrink-0 items-center rounded px-1.5 py-0.5 text-[10px] font-semibold leading-tight', typeChip.classes]"
          >{{ typeChip.label }}</span>
        </div>

        <!-- Vendor (related products span vendors) + size / pack line -->
        <p v-if="brandName" class="mt-1 truncate text-[12px] font-medium text-[color:var(--color-ink-muted)]">{{ brandName }}</p>
        <p v-if="sizeLine" class="ui-mono mt-0.5 truncate text-[11px] text-[color:var(--color-ink-subtle)]">{{ sizeLine }}</p>

        <!-- Rating (hidden until the product has real reviews) -->
        <div v-if="Number(ratingCount) > 0" class="mt-1.5 flex items-center gap-1">
          <svg class="h-3 w-3 fill-yellow-400 text-yellow-400" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z" /></svg>
          <span class="text-xs text-[color:var(--color-ink)]">{{ formattedRating }}</span>
          <span class="text-xs text-[color:var(--color-ink-subtle)]">({{ ratingCount || 0 }})</span>
        </div>

        <!-- Price block pinned to the bottom so cards line up. -->
        <div class="mt-auto pt-3">
          <!-- What you pay (with the code when there is one; the ticket
               below names it) and the listed price struck through. -->
          <div class="flex h-[22px] items-baseline gap-2 overflow-hidden whitespace-nowrap">
            <span class="ui-display text-[20px] font-bold leading-none tracking-tight text-[color:var(--color-ink)]">{{ currencySymbol }}{{ discountedPrice || displayPrice }}</span>
            <span v-if="discountedPrice" class="truncate text-[12px] leading-none text-[color:var(--color-ink-subtle)] line-through">{{ currencySymbol }}{{ displayPrice }}</span>
          </div>

          <!-- Price per mg. Rendered (and its row reserved) only when the
               parent passes the price-per-mg prop. -->
          <div v-if="showPerMgRow" class="mt-1.5 flex h-[18px] items-center">
            <PricePerMg
              v-if="pricePerMg !== null && pricePerMg !== ''"
              :value="pricePerMg"
              :currency-symbol="currencySymbol"
              :best="bestValue"
              :show-badge="false"
              :size-label="sizeLabel || ''"
              :with-code="!!discountedPrice"
              size="xs"
            />
          </div>

          <div class="mt-3">
            <CouponTicket v-if="ticketCode" :code="ticketCode" :percent="brandDiscountPercent" size="sm" />
            <div
              v-else
              class="flex h-10 items-center justify-center rounded-[10px] border border-dashed border-[color:var(--color-hairline)] text-[11px] font-medium text-[color:var(--color-ink-subtle)]"
            >Listed price · no code needed</div>
          </div>
        </div>
      </div>
    </div>
  </template>

  <script setup>
  import { computed, ref } from 'vue'
  import { Link } from '@inertiajs/vue3'
  import WishlistHeart from '@/components/ui/WishlistHeart.vue'
  import PricePerMg from '@/components/price/PricePerMg.vue'
  import CouponTicket from '@/components/ui/CouponTicket.vue'
  import { formatSizeLabel, packLabel } from '@/components/price/priceFormat'

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
    currencySymbol: { type: String, default: '$' },
    // Optional extras. brandName shows a vendor line (related products
    // span vendors); sizeMg + rawName give the size / pack line.
    sizeMg: { type: [String, Number], default: null },
    rawName: { type: String, default: '' },
    // undefined = parent does not use $/mg (no row); null = reserve the
    // row so a grid with some per-mg prices stays aligned.
    pricePerMg: { type: [Number, String], default: undefined },
    bestValue: { type: Boolean, default: false },
  })

  const sizeLabel = computed(() => formatSizeLabel(props.sizeMg))
  // Size is dropped when the name already shows it ("BPC-157 (5mg)").
  const sizeLine = computed(() => {
    let pack = packLabel(props.rawName)
    if (pack === 'Kit' && props.productType === 'Kit') pack = null
    const size = sizeLabel.value
    const sizeInName = size && String(props.name || '').toLowerCase().includes(String(size).toLowerCase())
    return [sizeInName ? null : size, pack].filter(Boolean).join(' · ')
  })
  const showPerMgRow = computed(() => props.pricePerMg !== undefined)

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

  // Code on the ticket: the vendor's own code, else PMAP when the vendor
  // has a Peptidemap discount but no code of its own. Null (no ticket)
  // when there is neither.
  const ticketCode = computed(() => {
    const raw = (props.brandCouponCode || '').trim()
    if (raw) return raw.toUpperCase()
    return discountedPrice.value ? 'PMAP' : null
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
  </script>
  