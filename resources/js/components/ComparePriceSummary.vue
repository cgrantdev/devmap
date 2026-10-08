<template>
  <div v-if="stats" class="mt-6 mb-5">
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
      <div class="rounded-[12px] border border-[color:var(--color-hairline)] bg-white px-4 py-4">
        <div class="text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Listings compared</div>
        <div class="mt-2 text-[15px] text-[color:var(--color-ink)]">
          <strong class="ui-mono">{{ stats.listing_count }}</strong> listings
          <span class="text-[color:var(--color-ink-subtle)]"> · </span>
          <strong class="ui-mono">{{ stats.vendor_count }}</strong> vendors
        </div>
      </div>

      <div v-if="showPerMg" class="rounded-[12px] border border-[color:var(--color-hairline)] bg-white px-4 py-4">
        <div class="text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Lowest listed price per mg</div>
        <div class="mt-2 text-[15px] text-[color:var(--color-ink)]">
          <strong class="ui-mono">${{ money(lowest.usd_per_mg) }}/mg</strong>
          <span class="text-[color:var(--color-ink-subtle)]"> · </span>
          {{ lowest.size_mg }} mg listing from
          <a :href="lowest.url" class="font-semibold text-[color:var(--color-accent-600)] hover:text-[color:var(--color-accent-700)]">{{ lowest.vendor }}</a>
        </div>
      </div>

      <div v-if="showVial" class="rounded-[12px] border border-[color:var(--color-hairline)] bg-white px-4 py-4">
        <div class="text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Lowest listed single-vial price</div>
        <div class="mt-2 text-[15px] text-[color:var(--color-ink)]">
          <strong class="ui-mono">${{ money(vial.listed_usd) }}</strong>
          <span class="text-[color:var(--color-ink-subtle)]"> · </span>
          {{ vial.size_mg }} mg
        </div>
      </div>

      <div v-if="showSizes" class="rounded-[12px] border border-[color:var(--color-hairline)] bg-white px-4 py-4">
        <div class="text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Most-listed vial sizes</div>
        <div class="mt-2 text-[15px] text-[color:var(--color-ink)] ui-mono">
          {{ sizeLine }}
        </div>
      </div>
    </div>
    <p v-if="stats.summary_footnote" class="mt-3 text-[12px] leading-relaxed text-[color:var(--color-ink-subtle)] max-w-4xl">
      {{ stats.summary_footnote }}
    </p>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  stats: { type: Object, default: null },
})

const lowest = computed(() => props.stats?.per_mg?.lowest || null)
const vial = computed(() => props.stats?.lowest_single_vial_listed || null)
const eligibleCount = computed(() => props.stats?.per_mg?.eligible_count ?? 0)
const showPerMg = computed(() => eligibleCount.value >= 3 && !!lowest.value)
const showSizes = computed(() => eligibleCount.value >= 3 && (props.stats?.common_sizes?.length || 0) > 0)
const showVial = computed(() => !!vial.value)
const sizeLine = computed(() => (props.stats?.common_sizes || []).slice(0, 3).map((row) => `${row.size_mg} mg`).join(' · '))

function money(value) {
  const n = Number(value)
  if (!Number.isFinite(n)) return '—'
  return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
</script>
