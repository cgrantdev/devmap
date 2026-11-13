<template>
  <span
    v-if="formatted"
    class="inline-flex items-center gap-1.5 flex-wrap leading-none"
    :title="tooltip"
  >
    <span :class="['ui-mono tabular-nums', textSize, best ? 'text-[color:var(--color-ink)] font-semibold' : 'text-[color:var(--color-ink-muted)]']">
      {{ formatted }}<span class="text-[color:var(--color-ink-subtle)] font-normal">/mg</span>
    </span>
    <span
      v-if="best && showBadge"
      class="inline-flex items-center gap-0.5 h-[18px] px-1.5 rounded-full border border-emerald-200 bg-emerald-50 text-[10px] font-bold uppercase tracking-[0.06em] text-emerald-800 whitespace-nowrap"
    >
      <svg class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5" /></svg>
      Best<span class="hidden sm:inline">&nbsp;value</span>
    </span>
  </span>
</template>

<script setup>
import { computed } from 'vue'
import { formatPerMg } from './priceFormat'

// Price per mg for a single-vial listing, in the vendor's currency.
// The backend sends null for kits, packs, blends, capsules and price
// outliers, so this renders nothing for them.
const props = defineProps({
  value: { type: [Number, String], default: null },
  currencySymbol: { type: String, default: '$' },
  // Lowest USD $/mg for this size among the listings on the page.
  best: { type: Boolean, default: false },
  // Cards show their own "Best value" ribbon on the image instead.
  showBadge: { type: Boolean, default: true },
  // Size the figure is for ("10mg"), used in the hover text.
  sizeLabel: { type: String, default: '' },
  // True when the per-mg figure already includes the vendor's code.
  withCode: { type: Boolean, default: false },
  size: { type: String, default: 'sm', validator: (v) => ['xs', 'sm', 'md'].includes(v) },
})

const formatted = computed(() => formatPerMg(props.value, props.currencySymbol))

const textSize = computed(() => {
  if (props.size === 'xs') return 'text-[11px]'
  if (props.size === 'md') return 'text-[14px]'
  return 'text-[12px]'
})

const tooltip = computed(() => {
  const basis = props.withCode ? 'Price per mg with the code' : 'Price per mg'
  const parts = [props.sizeLabel ? `${basis} (${props.sizeLabel} vial)` : basis]
  if (props.best) {
    parts.push(props.sizeLabel
      ? `Lowest per-mg price for ${props.sizeLabel} on this page`
      : 'Lowest per-mg price for this size on this page')
  }
  return parts.join('. ')
})
</script>
