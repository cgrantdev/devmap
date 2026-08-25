// Preset USP options for vendor storefronts. Kept as a data-only module
// so both <UspPicker> (form-side) and display sites (preview + brand
// page) can import the same list without triggering Vue's "script setup
// can't export" rule.
export const USP_OPTIONS = [
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
