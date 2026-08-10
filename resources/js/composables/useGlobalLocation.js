// Shared, site-wide vendor-location filter.
//
// One reactive `location` (a country name like "United States" or '' for all),
// persisted to localStorage under 'pmap.location'. When set, `applyLocation()`
// reloads the current URL with ?location=X so server-side filters on
// BrandsController/ProductsController/SearchController kick in. Compare.vue
// and BacteriostaticWater.vue read the same key for their client-side filters.

import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'

const LOCATION_KEY = 'pmap.location'

function initial() {
  if (typeof window === 'undefined') return ''
  try { return window.localStorage.getItem(LOCATION_KEY) || '' } catch { return '' }
}

const location = ref(initial())

watch(location, (val) => {
  try {
    if (val) window.localStorage.setItem(LOCATION_KEY, val)
    else window.localStorage.removeItem(LOCATION_KEY)
  } catch {}
  // Broadcast so subscribed pages (Compare, BAC water) can react without
  // needing a page reload.
  try { window.dispatchEvent(new CustomEvent('pmap:location-change', { detail: val })) } catch {}
})

export function useGlobalLocation() {
  return { location }
}

// Reload the current URL with the new location applied as a query param.
// Empty val clears the param.
export function applyLocation(val) {
  location.value = val || ''
  if (typeof window === 'undefined') return
  const url = new URL(window.location.href)
  if (val) url.searchParams.set('location', val)
  else url.searchParams.delete('location')
  // Use Inertia visit so shared props re-fetch and each page's server-side
  // filter re-runs. preserveScroll keeps the user's position on long lists.
  router.visit(url.pathname + url.search + url.hash, {
    preserveScroll: true,
    preserveState: false,
    replace: true,
  })
}
