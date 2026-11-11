import './bootstrap';

import { createApp, h } from 'vue'
import { createInertiaApp, router } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import Toast from 'vue-toastification'
import 'vue-toastification/dist/index.css'
import { useGlobalLocation } from '@/composables/useGlobalLocation'

// Site-wide sticky location filter: whenever the top-nav CountrySelector
// has a country set, every Inertia visit automatically carries that
// country as ?location=X. Keeps URLs honest, shareable and SEO-visible
// instead of hiding the filter in a cookie — Colin 11/12: "it needs to
// hold the filter throughout the whole site ... forever".
//
// Admin / API / asset URLs are excluded. An explicit ?location= in the
// outgoing visit URL wins (the LocationFilterChip's clear-via-applyLocation
// path visits with no param, which this hook must NOT re-append).
const { location: globalLocation } = useGlobalLocation()
router.on('before', (event) => {
  try {
    const val = globalLocation.value
    if (!val) return
    const visit = event.detail?.visit
    if (!visit) return
    const raw = visit.url
    if (!raw) return

    // visit.url may be a URL instance or a string on different Inertia
    // builds; normalize to URL against the current origin.
    const urlObj = raw instanceof URL
      ? raw
      : new URL(String(raw), window.location.origin)

    const path = urlObj.pathname
    if (
      path.startsWith('/admin') || path.startsWith('/api')
      || path.startsWith('/og/') || path.startsWith('/sitemap')
      || path.startsWith('/storage/') || path.startsWith('/build/')
    ) return

    // If the caller is deliberately clearing the location, don't
    // sneak the param back in (applyLocation('') writes '' into
    // globalLocation BEFORE visiting, so val is already '' by here —
    // this `has` guard is a belt-and-suspenders for edge cases).
    if (urlObj.searchParams.has('location')) return

    urlObj.searchParams.set('location', val)
    visit.url = urlObj
  } catch {}
})

createInertiaApp({
  resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(Toast, {
        position: 'top-right',
        timeout: 3000,
        closeOnClick: true,
        pauseOnFocusLoss: true,
        pauseOnHover: true,
        draggable: true,
        draggablePercent: 0.6,
        showCloseButtonOnHover: false,
        hideProgressBar: false,
        closeButton: 'button',
        icon: true,
        rtl: false,
        transition: 'Vue-Toastification__bounce',
        maxToasts: 5,
        newestOnTop: true,
      })
      .mount(el)
  },
})