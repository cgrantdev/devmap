<template>
  <div class="bg-white rounded-[16px] border border-[color:var(--color-hairline)] shadow-[var(--shadow-sm)] p-6">
    <div class="flex items-center justify-between gap-3 mb-3 flex-wrap">
      <div class="text-[10px] uppercase tracking-[0.14em] font-bold text-[color:var(--color-accent-600)]">Price this vial</div>
      <div class="text-[11px] text-[color:var(--color-ink-subtle)]">{{ countText }}</div>
    </div>

    <div class="flex items-end justify-between gap-4 flex-wrap">
      <div class="min-w-0">
        <div class="text-[13px] text-[color:var(--color-ink-muted)] mb-1">Lowest listed price for a {{ sizeText }}mg {{ compoundName }} vial</div>
        <div class="flex items-baseline gap-2">
          <span class="ui-display text-[40px] leading-none font-bold text-[color:var(--color-ink)] tracking-tight">{{ priceText }}</span>
          <span v-if="pricing.currency_code && pricing.currency_code !== 'USD'" class="text-[12px] font-semibold text-[color:var(--color-ink-muted)]">{{ pricing.currency_code }}</span>
        </div>
        <div class="text-[13px] text-[color:var(--color-ink-muted)] mt-1">at <strong class="text-[color:var(--color-ink)]">{{ pricing.vendor_name }}</strong></div>
      </div>
      <Link
        :href="pricing.listing_url"
        class="ui-focus inline-flex items-center gap-1.5 h-10 px-4 rounded-[10px] text-[13px] font-semibold text-white bg-[color:var(--color-ink)] hover:opacity-90 transition-opacity"
      >View listing &rarr;</Link>
    </div>

    <div v-if="showCosts" class="grid sm:grid-cols-2 gap-3 mt-5">
      <div class="rounded-[12px] bg-[color:var(--color-bg)] p-4">
        <div class="text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)] mb-1">Cost per dose</div>
        <div class="ui-mono text-xl font-bold text-[color:var(--color-ink)]">{{ costPerDoseText }}</div>
        <div class="text-[11px] text-[color:var(--color-ink-subtle)] mt-1">{{ usableDosesText }}</div>
      </div>
      <div class="rounded-[12px] bg-[color:var(--color-bg)] p-4">
        <div class="flex items-center justify-between gap-2 mb-1">
          <span class="text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Per month</span>
          <select
            :value="frequency"
            @change="$emit('update:frequency', $event.target.value)"
            aria-label="Dosing frequency"
            class="h-7 px-2 text-[11px] border border-[color:var(--color-hairline)] rounded-[6px] bg-white text-[color:var(--color-ink)] focus:border-[color:var(--color-accent-500)] focus:outline-none"
          >
            <option v-for="o in frequencyOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
          </select>
        </div>
        <div class="ui-mono text-xl font-bold text-[color:var(--color-ink)]">{{ monthlyText }}</div>
        <div v-if="stabilityLimited" class="text-[11px] text-amber-700 mt-1">Assumes a fresh vial every {{ stabilityDays }} days, the usual fridge life once mixed.</div>
      </div>
    </div>
    <p v-else class="mt-4 text-[12px] text-[color:var(--color-ink-muted)]">{{ noCostReason }}</p>

    <div class="flex flex-wrap gap-x-5 gap-y-2 mt-5 pt-4 border-t border-[color:var(--color-hairline-soft)] text-[13px] font-semibold">
      <Link :href="pricing.compare_url" class="text-[color:var(--color-accent-700)] hover:underline">Compare {{ compoundName }} prices &rarr;</Link>
      <Link :href="pricing.product_listing_url" class="text-[color:var(--color-accent-700)] hover:underline">All {{ compoundName }} listings &rarr;</Link>
      <Link href="/bacteriostatic-water" class="text-[color:var(--color-accent-700)] hover:underline">Bacteriostatic water prices &rarr;</Link>
    </div>
    <p class="mt-3 text-[11px] text-[color:var(--color-ink-subtle)]">Lowest listed vial price among tracked vendors, before coupon codes. Research use only.</p>
  </div>
</template>

<script setup>
// "Price this vial": turns the calculator's dose math into money using the
// cheapest live listing for the preset's compound and vial size.
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  pricing: { type: Object, required: true },
  compoundName: { type: String, default: '' },
  vialMg: { type: Number, default: null },
  dosesPerVial: { type: Number, default: null },
  perWeek: { type: Number, default: 7 },
  stabilityDays: { type: Number, default: 28 },
  frequency: { type: String, default: 'daily' },
  frequencyOptions: { type: Array, default: () => [] },
})
defineEmits(['update:frequency'])

function money(n) {
  if (typeof n !== 'number' || !Number.isFinite(n)) return '—'
  return (props.pricing.currency_symbol || '$') + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const price = computed(() => Number(props.pricing.cheapest_price))
const priceText = computed(() => money(price.value))
const sizeText = computed(() => Number(props.pricing.size_mg).toLocaleString('en-US', { maximumFractionDigits: 3 }))
const countText = computed(() => {
  const n = Number(props.pricing.listing_count) || 0
  return `${n} ${sizeText.value}mg ${n === 1 ? 'listing' : 'listings'} tracked`
})

const sizeMatches = computed(() => typeof props.vialMg === 'number'
  && Math.abs(props.vialMg - Number(props.pricing.size_mg)) < 0.01)
const dosesInWindow = computed(() => Math.floor((props.stabilityDays * props.perWeek) / 7 + 1e-9))
const stabilityLimited = computed(() => typeof props.dosesPerVial === 'number' && props.dosesPerVial > dosesInWindow.value)
const usableDoses = computed(() => {
  if (typeof props.dosesPerVial !== 'number' || props.dosesPerVial <= 0) return 0
  return Math.max(1, Math.min(props.dosesPerVial, dosesInWindow.value))
})
const showCosts = computed(() => sizeMatches.value && usableDoses.value > 0 && price.value > 0)

const costPerDose = computed(() => price.value / usableDoses.value)
const costPerDoseText = computed(() => money(costPerDose.value))
const monthlyText = computed(() => money((costPerDose.value * props.perWeek * 30) / 7))
const usableDosesText = computed(() => (stabilityLimited.value
  ? `${usableDoses.value} doses used within ${props.stabilityDays} days`
  : `${props.dosesPerVial} ${props.dosesPerVial === 1 ? 'dose' : 'doses'} per vial`))
const noCostReason = computed(() => {
  if (!sizeMatches.value) {
    return `This price is for a ${sizeText.value}mg vial. Set the vial size to ${sizeText.value}mg to see cost per dose.`
  }
  return 'Enter a dose to see cost per dose and per month.'
})
</script>
