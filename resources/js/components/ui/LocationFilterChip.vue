<template>
  <!-- Visible confirmation that the top-nav CountrySelector's filter is
       in effect on this page. Renders only when ?location=X is in the URL.
       Clicking × clears the filter (reloads the page without the param)
       and matches the UX between /vendors (which has an in-page dropdown)
       and /products + /product/{slug} + /brand/{slug} (which don't). -->
  <div v-if="location" class="flex items-center gap-2 mb-4">
    <span class="text-[11px] uppercase tracking-[0.12em] font-semibold text-[color:var(--color-ink-subtle)]">Location filter</span>
    <button
      type="button"
      @click="clear"
      class="ui-focus inline-flex items-center gap-2 h-8 px-3 rounded-full text-[12px] font-semibold bg-[color:var(--color-accent-50)] text-[color:var(--color-accent-700)] border border-[color:var(--color-accent-200)] hover:bg-[color:var(--color-accent-100)]"
      :aria-label="`Clear location filter (${location})`"
    >
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/>
        <circle cx="12" cy="10" r="3"/>
      </svg>
      <span>{{ location }}</span>
      <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
        <path d="M18 6L6 18M6 6l12 12"/>
      </svg>
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { applyLocation } from '@/composables/useGlobalLocation'

const page = usePage()

const location = computed(() => {
  const url = page.url || ''
  const q = url.includes('?') ? url.split('?')[1] : ''
  const params = new URLSearchParams(q)
  const val = params.get('location')
  return val ? decodeURIComponent(val) : ''
})

function clear() {
  applyLocation('')
}
</script>
