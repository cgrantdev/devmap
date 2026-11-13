import './bootstrap';

import { createApp, h } from 'vue'
import { createInertiaApp, router } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { useGlobalLocation } from '@/composables/useGlobalLocation'
import { stripSiteSuffix } from '@/utils/pageTitle'

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
function shouldSkipPath(path) {
  return path.startsWith('/admin') || path.startsWith('/api')
    || path.startsWith('/og/') || path.startsWith('/sitemap')
    || path.startsWith('/storage/') || path.startsWith('/build/')
}

// Inertia router hook: covers every <Link>, router.visit(), Form POST.
router.on('before', (event) => {
  try {
    const val = globalLocation.value
    if (!val) return
    const visit = event.detail?.visit
    if (!visit) return
    const raw = visit.url
    if (!raw) return

    const urlObj = raw instanceof URL
      ? raw
      : new URL(String(raw), window.location.origin)

    if (shouldSkipPath(urlObj.pathname)) return
    if (urlObj.searchParams.has('location')) return

    urlObj.searchParams.set('location', val)
    visit.url = urlObj
  } catch {}
})

// Native anchor fallback: parts of the app still use plain <a href="/…">
// tags (CategoryCard, some legacy header links, footer rows) that
// trigger a full browser navigation and bypass Inertia's router. Catch
// those at click-time and rewrite the href so the filter rides along.
if (typeof document !== 'undefined') {
  document.addEventListener('click', (e) => {
    try {
      const val = globalLocation.value
      if (!val) return
      // Modified clicks (open in new tab, download, etc.) left alone.
      if (e.defaultPrevented || e.button !== 0) return
      if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return

      const anchor = e.target && e.target.closest ? e.target.closest('a[href]') : null
      if (!anchor) return
      // Inertia Link already carries data-inertia and routes through
      // the before-hook above — leave it.
      if (anchor.hasAttribute('data-inertia')) return
      if (anchor.target && anchor.target !== '' && anchor.target !== '_self') return
      if (anchor.hasAttribute('download')) return

      const href = anchor.getAttribute('href') || ''
      if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:')) return

      const urlObj = new URL(href, window.location.origin)
      if (urlObj.origin !== window.location.origin) return
      if (shouldSkipPath(urlObj.pathname)) return
      if (urlObj.searchParams.has('location')) return

      urlObj.searchParams.set('location', val)
      anchor.setAttribute('href', urlObj.pathname + urlObj.search + urlObj.hash)
    } catch {}
  }, true) // capture-phase so we rewrite before other listeners read href
}

createInertiaApp({
  // Agency rule: no site-name tail on any page title except the homepage.
  title: (title) => {
    if (!title || (typeof window !== 'undefined' && window.location.pathname === '/')) return title
    return stripSiteSuffix(title)
  },
  resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
  // Toasts are admin-only; their container mounts from Admin/Layout.vue so
  // the library and its CSS stay out of the public bundle.
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .mount(el)
  },
})