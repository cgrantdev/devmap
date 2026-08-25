<template>
  <!-- Toggle-grid of common vendor USPs. Each option has an icon + label.
       Selected keys are persisted to vendor_settings.usps as a JSON array,
       then rendered on the storefront as a compact icon-row (no free-form
       text to review or moderate). Kept intentionally short (12 options)
       — if a real USP is missing, that's a signal to add it here rather
       than let vendors freeform-type marketing copy. -->
  <div>
    <div class="flex items-baseline justify-between mb-2">
      <label class="block text-sm text-slate-700">
        Unique selling points <span class="text-xs text-slate-500 font-normal">— pick the ones that apply</span>
      </label>
      <span class="text-[11px] ui-mono text-slate-400">{{ (modelValue || []).length }} / {{ OPTIONS.length }}</span>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
      <button
        v-for="opt in OPTIONS"
        :key="opt.key"
        type="button"
        @click="toggle(opt.key)"
        :class="[
          'flex items-center gap-2 px-3 py-2.5 rounded-lg border text-left transition-colors',
          isSelected(opt.key)
            ? 'border-indigo-400 bg-indigo-50 text-indigo-900'
            : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50'
        ]"
      >
        <span class="text-[16px] leading-none flex-shrink-0">{{ opt.icon }}</span>
        <span class="text-[12px] font-medium leading-tight">{{ opt.label }}</span>
      </button>
    </div>
  </div>
</template>

<script setup>
// Preset list — extend as vendors ask for missing ones. Keeping this
// centralized (vs freeform text) means every vendor's USPs render with
// the same icons and copy, so cross-vendor scan on category pages stays
// readable.
export const OPTIONS = [
  { key: 'lab_tested',        icon: '🧪', label: '3rd-party lab tested' },
  { key: 'coa_per_batch',     icon: '📋', label: 'Full COA per batch' },
  { key: 'high_purity',       icon: '🎯', label: '99%+ purity guaranteed' },
  { key: 'cgmp',              icon: '🏭', label: 'cGMP facility' },
  { key: 'same_day_shipping', icon: '⚡', label: 'Same-day shipping' },
  { key: 'international',     icon: '🌍', label: 'Ships internationally' },
  { key: 'temp_controlled',   icon: '🥶', label: 'Temperature-controlled ship' },
  { key: 'us_manufactured',   icon: '🇺🇸', label: 'US-manufactured' },
  { key: 'money_back',        icon: '💰', label: 'Money-back guarantee' },
  { key: 'bulk_discounts',    icon: '📦', label: 'Bulk discounts' },
  { key: 'subscription',      icon: '🔁', label: 'Subscription plans' },
  { key: 'support_24_7',      icon: '💬', label: '24/7 customer support' },
]

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
})
const emit = defineEmits(['update:modelValue'])

function isSelected(key) {
  return (props.modelValue || []).includes(key)
}
function toggle(key) {
  const cur = new Set(props.modelValue || [])
  if (cur.has(key)) cur.delete(key)
  else cur.add(key)
  emit('update:modelValue', Array.from(cur))
}
</script>
