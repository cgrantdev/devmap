<template>
  <!-- One stretched link per card (the vendor name, to its coupon page),
       so the coupon ticket can be a real copy button above it. -->
  <div
    class="group relative flex h-full flex-row sm:flex-col overflow-hidden rounded-[14px] border border-[color:var(--color-hairline)] bg-white transition-all duration-200 hover:-translate-y-[1px] hover:border-[color:var(--color-accent-400)] hover:shadow-[var(--shadow-md)]"
  >
    <!-- Limited-Time badge — shown when the vendor's coupon is boosted. -->
    <div
      v-if="isBoosted"
      class="absolute top-2 left-2 sm:top-3 sm:left-3 z-[1] inline-flex items-center gap-1 bg-amber-500 text-white px-2 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider shadow-md"
    >
      <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      Limited time
    </div>

    <!-- Fixed 16:9 logo box; overflow-hidden keeps the ratio whatever the
         logo file's shape. -->
    <!-- Phones: an 84px logo square beside the details; sm up: 16:9 box. -->
    <div class="relative flex-shrink-0 overflow-hidden w-[84px] h-[84px] m-2.5 mr-0 rounded-[12px] border border-[color:var(--color-hairline)] sm:m-0 sm:w-auto sm:h-auto sm:aspect-[16/9] sm:rounded-none sm:border-0 sm:border-b">
      <div class="absolute inset-0">
        <VendorLogo
          :src="logo"
          :name="name"
          :alt="name + ' logo'"
          size="xl"
          fill
          radius="none"
          :bordered="false"
          fallback="subtle"
          img-class="w-full h-full object-contain p-[12%] sm:px-[16%] sm:py-[14%] select-none"
        />
      </div>
    </div>

    <div class="flex min-w-0 flex-1 flex-col gap-2 p-2.5 pl-3 sm:gap-2.5 sm:p-4">
      <div class="min-w-0">
        <div class="flex items-start justify-between gap-2">
        <h3 class="ui-display min-w-0 truncate text-[16px] font-semibold tracking-tight text-[color:var(--color-ink)] transition-colors group-hover:text-[color:var(--color-accent-700)]">
          <a
            :href="`/coupon/${slug}`"
            class="outline-none after:absolute after:inset-0 after:rounded-[14px] focus-visible:after:ring-2 focus-visible:after:ring-[color:var(--color-accent-500)]"
          >{{ name }}</a>
        </h3>
        <!-- Discount (only when the vendor has said what the code gives) -->
        <span
          v-if="discountValue"
          class="flex-shrink-0 inline-flex items-center h-6 px-2 rounded-full bg-red-600 text-white text-[12px] font-bold"
        >−{{ discountValue }}%</span>
        </div>
        <!-- Rating (hidden until the vendor has real reviews) -->
        <div v-if="Number(reviews) > 0" class="mt-1 flex items-center gap-1 text-xs">
          <svg class="w-3.5 h-3.5 text-[color:var(--color-caution)]" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 1l2.8 5.7 6.2.9-4.5 4.4 1.1 6.3L10 15.3 4.4 18.3l1.1-6.3L1 7.6l6.2-.9L10 1z"/></svg>
          <span class="ui-mono font-semibold text-[color:var(--color-ink)]">{{ formattedRating }}</span>
          <span class="text-[color:var(--color-ink-subtle)]">({{ reviews }})</span>
        </div>
      </div>

      <p v-if="description" class="hidden sm:block text-[13px] leading-snug text-[color:var(--color-ink-muted)] line-clamp-2">{{ description }}</p>

      <div class="mt-auto pt-1">
        <CouponTicket :code="code" :percent="discountValue" size="sm" />
        <span
          aria-hidden="true"
          class="mt-2 hidden sm:flex h-9 items-center justify-center gap-1.5 rounded-[10px] border border-[color:var(--color-hairline)] text-[12px] font-semibold text-[color:var(--color-ink)] transition-colors group-hover:border-[color:var(--color-accent-400)] group-hover:text-[color:var(--color-accent-700)]"
        >
          See the deal
          <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7" /></svg>
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import VendorLogo from '@/components/ui/VendorLogo.vue'
import CouponTicket from '@/components/ui/CouponTicket.vue'

const props = defineProps({
  id: {
    type: [Number, String],
    required: true
  },
  name: {
    type: String,
    required: true
  },
  slug: {
    type: String,
    required: true
  },
  logo: {
    type: String,
    default: null
  },
  initials: {
    type: String,
    default: ''
  },
  rating: {
    type: [String, Number],
    default: '0.00'
  },
  reviews: {
    type: [String, Number],
    default: 0
  },
  // Percent the code gives; null when the vendor has not said.
  discount: {
    type: [String, Number],
    default: null
  },
  code: {
    type: String,
    required: true
  },
  description: {
    type: String,
    default: ''
  },
  isBoosted: {
    type: Boolean,
    default: false,
  },
  boostEndsAt: {
    type: String,
    default: null,
  },
})

const formattedRating = computed(() => {
  const rating = parseFloat(props.rating || 0)
  return rating.toFixed(1)
})

const discountValue = computed(() => {
  const n = parseFloat(props.discount)
  return n > 0 && n < 100 ? Math.round(n) : null
})
</script>
