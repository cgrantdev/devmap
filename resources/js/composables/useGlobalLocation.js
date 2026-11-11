// Shared, site-wide vendor-location filter.
//
// One reactive `location` (a country name like "United States" or '' for all),
// persisted to localStorage under 'pmap.location' AND mirrored to a
// pmap_location cookie so the backend's ApplyLocationCookie middleware can
// honor the filter on every request — users who pick a country in the
// top-nav keep that filter through every internal navigation, not just
// the pages where the frontend happens to append ?location=X. Compare.vue
// and BacteriostaticWater.vue read the same key for client-side filters.

import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'

const LOCATION_KEY = 'pmap.location'
const COOKIE_NAME = 'pmap_location'

function readCookie(name) {
  if (typeof document === 'undefined') return ''
  const match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'))
  return match ? decodeURIComponent(match[1]) : ''
}

function writeCookie(name, value) {
  if (typeof document === 'undefined') return
  // 365-day persistence, site-wide path, SameSite=Lax so it rides along on
  // top-level GETs and keeps working across every internal Link navigation.
  const maxAge = 365 * 24 * 60 * 60
  if (value) {
    document.cookie = `${name}=${encodeURIComponent(value)}; Max-Age=${maxAge}; Path=/; SameSite=Lax`
  } else {
    document.cookie = `${name}=; Max-Age=0; Path=/; SameSite=Lax`
  }
}

function initial() {
  if (typeof window === 'undefined') return ''
  // Cookie wins over localStorage so a user who clears site data in one tab
  // but the server already set the cookie still sees the right country.
  const fromCookie = readCookie(COOKIE_NAME)
  if (fromCookie) return fromCookie
  try { return window.localStorage.getItem(LOCATION_KEY) || '' } catch { return '' }
}

const location = ref(initial())

// On first load, mirror whatever initial() resolved to into the cookie
// silently. Watchers only fire on change, so for users whose localStorage
// was set before the cookie shipped, this is the one-time catch-up that
// gets the cookie populated — but no page reload, because:
//  - ApplyLocationCookie middleware 302s the next direct URL visit to
//    add ?location=X for free, so the filter appears in the URL without
//    a client-side flash;
//  - router.on('before') in app.js rewrites every Inertia click to
//    carry ?location=X from this point on;
//  - the LocationFilterChip is reactive to this composable so it
//    appears as soon as the component mounts.
if (typeof document !== 'undefined' && location.value && !readCookie(COOKIE_NAME)) {
  writeCookie(COOKIE_NAME, location.value)
}

watch(location, (val) => {
  try {
    if (val) window.localStorage.setItem(LOCATION_KEY, val)
    else window.localStorage.removeItem(LOCATION_KEY)
  } catch {}
  writeCookie(COOKIE_NAME, val)
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
