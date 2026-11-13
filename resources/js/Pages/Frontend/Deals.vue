<template>
  <ModernLayout>
    <!-- Header: what this page is, the real top discount, and the
         sort, in one compact block so codes start above the fold. -->
    <section class="relative overflow-hidden border-b border-[color:var(--color-hairline)]">
      <div class="absolute inset-0 bg-gradient-to-br from-[color:var(--color-caution-bg)] via-transparent to-transparent opacity-60 pointer-events-none" />
      <div class="relative max-w-[1280px] mx-auto px-5 lg:px-10 pt-7 pb-6 lg:pt-10 lg:pb-8">
        <div class="text-[11px] uppercase tracking-[0.12em] font-semibold text-[color:var(--color-caution)] mb-2">Vendor coupon codes</div>
        <h1 class="ui-display text-3xl md:text-5xl font-semibold tracking-[-0.02em] text-[color:var(--color-ink)]">Active Discount Codes</h1>
        <p class="mt-2.5 text-[15px] text-[color:var(--color-ink-muted)] leading-relaxed max-w-2xl">
          <template v-if="maxDiscount">Save up to {{ maxDiscount }}% with {{ dealsCount }} vendor codes. </template>
          <template v-else>{{ dealsCount }} vendor codes. </template>
          Tap a code to copy it. Codes come from the vendors; confirm the price at checkout.
        </p>

        <div class="mt-5 flex items-center gap-2 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
          <span class="shrink-0 text-[11px] uppercase tracking-[0.1em] font-semibold text-[color:var(--color-ink-subtle)] mr-1">Sort</span>
          <button
            v-for="opt in sortOptions"
            :key="opt.value"
            type="button"
            @click="handleSort(opt.value)"
            :aria-pressed="sortBy === opt.value"
            :class="[
              'shrink-0 whitespace-nowrap h-8 px-3.5 rounded-full text-[13px] font-medium border transition-colors',
              sortBy === opt.value
                ? 'bg-[color:var(--color-ink)] text-white border-[color:var(--color-ink)]'
                : 'bg-white text-[color:var(--color-ink-muted)] border-[color:var(--color-hairline)] hover:border-[color:var(--color-ink-subtle)]',
            ]"
          >{{ opt.label }}</button>
        </div>
      </div>
    </section>

    <!-- Discount Cards Grid -->
    <section class="bg-[color:var(--color-bg)] min-h-screen">
      <div class="max-w-[1280px] mx-auto px-5 lg:px-10 py-8 lg:py-10">
        <div v-if="deals.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4 lg:gap-5">
          <DealCard
            v-for="deal in deals"
            :key="deal.id"
            :id="deal.id"
            :name="deal.name"
            :slug="deal.slug"
            :logo="deal.logo"
            :initials="deal.initials"
            :rating="deal.rating"
            :reviews="deal.reviews"
            :discount="deal.discount"
            :code="deal.code"
            :description="deal.description"
            :is-boosted="deal.is_boosted"
            :boost-ends-at="deal.boost_ends_at"
          />
        </div>
        <div v-else class="text-center py-12">
          <p class="text-[color:var(--color-ink-muted)]">No vendor codes here yet<template v-if="hasLocationFilter"> for this location</template>.</p>
        </div>
      </div>
    </section>
  </ModernLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import ModernLayout from '@/Pages/Layouts/ModernLayout.vue'
import DealCard from '@/components/DealCard.vue'

const props = defineProps({
  deals: {
    type: Array,
    default: () => []
  },
  sortBy: {
    type: String,
    default: 'best_discount'
  },
  dealsCount: {
    type: Number,
    default: 0
  }
})

const sortBy = ref(props.sortBy)

const sortOptions = [
  { value: 'best_discount', label: 'Best discount' },
  { value: 'top_rated', label: 'Top rated' },
  { value: 'a_z', label: 'A–Z' },
]

// Largest known discount, for the header line (no made-up "up to 25%").
const maxDiscount = computed(() => {
  const values = (props.deals || []).map((d) => Number(d.discount)).filter((n) => n > 0)
  return values.length ? Math.max(...values) : null
})

const hasLocationFilter = typeof window !== 'undefined'
  && new URLSearchParams(window.location.search).has('location')

const handleSort = (sort) => {
  sortBy.value = sort
  router.get('/deals', { sort }, {
    preserveState: true,
    preserveScroll: true,
  })
}
</script>
