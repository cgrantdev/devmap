// Preset USP options for vendor storefronts. Kept as a data-only module
// so both <UspPicker> (form-side) and display sites (preview + brand
// page) can import the same list without triggering Vue's "script setup
// can't export" rule.
export const USP_OPTIONS = [
  { key: 'lab_tested',        icon: '🧪', label: '3rd-party lab tested (vendor-stated)' },
  { key: 'coa_per_batch',     icon: '📋', label: 'Full COA per batch (vendor-stated)' },
  { key: 'high_purity',       icon: '🎯', label: '99%+ purity (vendor-stated)' },
  { key: 'cgmp',              icon: '🏭', label: 'cGMP facility' },
  { key: 'same_day_shipping', icon: '⚡', label: 'Same-day shipping' },
  { key: 'international',     icon: '🌍', label: 'Ships internationally' },
  { key: 'temp_controlled',   icon: '🥶', label: 'Temperature-controlled ship' },
  // Colin PMAP Sep 30 (#5): 🇺🇸 rendered as literal "us" on Windows
  // Chrome / other systems without a color-emoji font. Marker sentinel
  // gets special-cased in the display components (inline SVG flag).
  { key: 'us_manufactured',   icon: '__US_FLAG__', label: 'US-manufactured' },
  { key: 'money_back',        icon: '💰', label: 'Money-back guarantee (vendor-stated)' },
  { key: 'bulk_discounts',    icon: '📦', label: 'Bulk discounts' },
  { key: 'subscription',      icon: '🔁', label: 'Subscription plans' },
  { key: 'support_24_7',      icon: '💬', label: '24/7 customer support' },
]
