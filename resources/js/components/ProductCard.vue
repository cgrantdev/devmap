<template>
  <!-- The whole card is one real link: the title's ::after box stretches
       over the card, so middle-click, open-in-new-tab and crawlers all
       see an <a href>. The coupon ticket sits above that overlay (z-10)
       and only copies the code. Every row has a fixed height so cards in
       a grid line up whatever the name length or discount. -->
  <div
    class="group relative flex h-full flex-col overflow-hidden rounded-[14px] border border-[color:var(--color-hairline)] bg-white transition-all duration-200 hover:-translate-y-[1px] hover:border-[color:var(--color-accent-400)] hover:shadow-[var(--shadow-md)]"
  >
    <div class="relative flex aspect-[4/3] items-center justify-center overflow-hidden border-b border-[color:var(--color-hairline)] bg-[color:var(--color-hairline-soft)] p-4">
      <!-- Lowest USD $/mg for this vial size on the page. -->
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
        class="h-full w-full select-none object-contain transition-transform duration-300 group-hover:scale-[1.03]"
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
      <!-- Vendor first: on a compound page every card is the same
           compound, so who sells it is what people scan for. -->
      <div class="flex h-[18px] items-center gap-1.5">
        <span class="ui-display min-w-0 truncate text-[14px] font-semibold tracking-tight text-[color:var(--color-ink)] transition-colors group-hover:text-[color:var(--color-accent-700)]">
          {{ brandName || 'Unknown vendor' }}
        </span>
        <span
          v-if="typeChip"
          :class="['inline-flex flex-shrink-0 items-center rounded px-1.5 py-0.5 text-[10px] font-semibold leading-tight', typeChip.classes]"
        >{{ typeChip.label }}</span>
      </div>
      <h3 class="mt-1 h-[2.25rem] overflow-hidden text-[12.5px] leading-[1.125rem] text-[color:var(--color-ink-muted)] line-clamp-2">
        <Link
          :href="to"
          class="outline-none after:absolute after:inset-0 after:rounded-[14px] focus-visible:after:ring-2 focus-visible:after:ring-[color:var(--color-accent-500)]"
        >{{ name }}</Link>
      </h3>
      <p v-if="sizeLine" class="ui-mono mt-0.5 truncate text-[11px] text-[color:var(--color-ink-subtle)]">{{ sizeLine }}</p>

      <!-- Bottom block pinned so price, ticket and button line up across
           a row. Price: what you pay (with the code when there is one;
           the ticket right below names it), listed price struck through,
           then price per mg. -->
      <div class="mt-auto pt-3">
        <div class="flex h-[22px] items-baseline gap-2 overflow-hidden whitespace-nowrap">
          <span class="ui-display text-[20px] font-bold leading-none tracking-tight text-[color:var(--color-ink)]">{{ currencySymbol }}{{ discountedPrice || displayPrice }}</span>
          <span v-if="discountedPrice" class="truncate text-[12px] leading-none text-[color:var(--color-ink-subtle)] line-through">{{ currencySymbol }}{{ displayPrice }}</span>
        </div>
        <div class="mt-1.5 flex h-[18px] items-center">
          <PricePerMg
            v-if="pricePerMg !== null && pricePerMg !== undefined && pricePerMg !== ''"
            :value="pricePerMg"
            :currency-symbol="currencySymbol"
            :best="bestValue"
            :show-badge="false"
            :size-label="sizeDisplay || ''"
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

        <!-- Visual CTA only; the stretched title link handles the click. -->
        <span
          aria-hidden="true"
          class="mt-2.5 flex h-9 items-center justify-center gap-1.5 rounded-[10px] border border-[color:var(--color-hairline)] text-[12px] font-semibold text-[color:var(--color-ink)] transition-colors group-hover:border-[color:var(--color-accent-400)] group-hover:text-[color:var(--color-accent-700)]"
        >
          View listing
          <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7" /></svg>
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import PricePerMg from '@/components/price/PricePerMg.vue'
import CouponTicket from '@/components/ui/CouponTicket.vue'
import { packLabel } from '@/components/price/priceFormat'

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
  // Resolved from the vendor's HQ country on the backend
  // (ProductRowCurrency). Defaults to USD when the backend didn't
  // attach one — same behavior as before this prop existed.
  currencySymbol: { type: String, default: '$' },
  // Vendor's own product name (the card title is the cleaned display
  // name); used to spot pack sizes like "10 vials" or "60 ct".
  rawName: { type: String, default: '' },
  // Price per mg with the code, vendor currency (backend price_per_mg).
  pricePerMg: { type: [Number, String], default: null },
  // Lowest USD $/mg for this size on the page (backend best_value).
  bestValue: { type: Boolean, default: false },
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

// "10mg · 10 vials". Kit is dropped when the format chip already says it,
// and the size when the name already shows it ("BPC-157 (5mg)").
const sizeLine = computed(() => {
  let pack = packLabel(props.rawName || props.name)
  if (pack === 'Kit' && props.productType === 'Kit') pack = null
  const size = sizeDisplay.value
  const sizeInName = size && String(props.name || '').toLowerCase().includes(String(size).toLowerCase())
  return [sizeInName ? null : size, pack].filter(Boolean).join(' · ')
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

// Code on the ticket: the vendor's own code, else PMAP when the vendor
// has a Peptidemap discount but no code of its own. Null (no ticket)
// when there is neither.
const ticketCode = computed(() => {
  const raw = (props.brandCouponCode || '').trim()
  if (raw) return raw.toUpperCase()
  return discountedPrice.value ? 'PMAP' : null
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
</script>
