<template>
  <!-- One stretched link per card (the vendor name, to its coupon page),
       so the coupon ticket can be a real copy button above it. -->
  <div
    class="group relative flex h-full flex-col overflow-hidden rounded-[14px] border border-[color:var(--color-hairline)] bg-white transition-all duration-200 hover:-translate-y-[1px] hover:border-[color:var(--color-accent-400)] hover:shadow-[var(--shadow-md)]"
  >
    <!-- Discount badge (only when the vendor has said what the code gives) -->
    <div
      v-if="discountValue"
      class="absolute top-3 right-3 z-[1] inline-flex items-center gap-1 h-7 px-2.5 rounded-full bg-red-600 text-white text-[13px] font-bold shadow-md"
    >
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 17h6v-6" /><path d="m22 17-8.5-8.5-5 5L2 7" /></svg>
      −{{ discountValue }}%
    </div>

    <!-- Limited-Time badge — shown when the vendor's coupon is boosted. -->
    <div
      v-if="isBoosted"
      class="absolute top-3 left-3 z-[1] inline-flex items-center gap-1 bg-amber-500 text-white px-2 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider shadow-md"
    >
      <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      Limited time
    </div>

    <!-- Fixed 16:9 logo box; overflow-hidden keeps the ratio whatever the
         logo file's shape. -->
    <div class="relative aspect-[16/9] overflow-hidden border-b border-[color:var(--color-hairline)]">
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
          img-class="w-full h-full object-contain px-[16%] py-[14%] select-none"
        />
      </div>
    </div>

    <div class="flex flex-1 flex-col gap-2.5 p-4">
      <div class="min-w-0">
        <h3 class="ui-display truncate text-[16px] font-semibold tracking-tight text-[color:var(--color-ink)] transition-colors group-hover:text-[color:var(--color-accent-700)]">
          <a
            :href="`/coupon/${slug}`"
            class="outline-none after:absolute after:inset-0 after:rounded-[14px] focus-visible:after:ring-2 focus-visible:after:ring-[color:var(--color-accent-500)]"
          >{{ name }}</a>
        </h3>
        <!-- Rating (hidden until the vendor has real reviews) -->
        <div v-if="Number(reviews) > 0" class="mt-1 flex items-center gap-1 text-xs">
          <svg class="w-3.5 h-3.5 text-[color:var(--color-caution)]" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 1l2.8 5.7 6.2.9-4.5 4.4 1.1 6.3L10 15.3 4.4 18.3l1.1-6.3L1 7.6l6.2-.9L10 1z"/></svg>
          <span class="ui-mono font-semibold text-[color:var(--color-ink)]">{{ formattedRating }}</span>
          <span class="text-[color:var(--color-ink-subtle)]">({{ reviews }})</span>
        </div>
      </div>

      <p v-if="description" class="text-[13px] leading-snug text-[color:var(--color-ink-muted)] line-clamp-2">{{ description }}</p>

      <div class="mt-auto pt-1">
        <CouponTicket :code="code" :percent="discountValue" size="sm" />
        <span
          aria-hidden="true"
          class="mt-2 flex h-9 items-center justify-center gap-1.5 rounded-[10px] border border-[color:var(--color-hairline)] text-[12px] font-semibold text-[color:var(--color-ink)] transition-colors group-hover:border-[color:var(--color-accent-400)] group-hover:text-[color:var(--color-accent-700)]"
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
