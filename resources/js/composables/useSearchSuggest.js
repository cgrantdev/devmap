// Live search suggestions from /api/search/suggest (compounds first, then
// vendors). Shared by the header SearchPalette and the homepage search box.
// Debounced, cancels in-flight requests, and caches results per query for
// the life of the page.
import { ref, watch } from 'vue'

export function useSearchSuggest(queryRef, { minLength = 2, debounceMs = 150 } = {}) {
  const results = ref([])
  const loading = ref(false)
  const activeIndex = ref(-1)
  const cache = new Map()
  let timer = null
  let controller = null

  function toItems(data) {
    const compounds = (data?.compounds || []).map((c) => ({
      type: 'compound',
      label: c.name,
      sublabel: [
        c.vendor_count ? `${c.vendor_count} ${c.vendor_count === 1 ? 'vendor' : 'vendors'}` : null,
        c.from_price ? `from $${c.from_price}` : null,
      ].filter(Boolean).join(' · '),
      url: c.url,
    }))
    const vendors = (data?.vendors || []).map((v) => ({
      type: 'vendor',
      label: v.name,
      sublabel: 'Vendor',
      url: v.url,
    }))
    return [...compounds, ...vendors]
  }

  function isCurrent(key) {
    return String(queryRef.value || '').trim().toLowerCase() === key
  }

  watch(queryRef, (value) => {
    const term = String(value || '').trim()
    const key = term.toLowerCase()
    clearTimeout(timer)
    activeIndex.value = -1

    if (term.length < minLength) {
      controller?.abort()
      results.value = []
      loading.value = false
      return
    }
    if (cache.has(key)) {
      results.value = cache.get(key)
      loading.value = false
      return
    }

    loading.value = true
    timer = setTimeout(async () => {
      controller?.abort()
      controller = new AbortController()
      try {
        const res = await fetch(`/api/search/suggest?q=${encodeURIComponent(term)}`, {
          headers: { Accept: 'application/json' },
          signal: controller.signal,
        })
        if (!res.ok) throw new Error(`suggest ${res.status}`)
        const items = toItems(await res.json())
        cache.set(key, items)
        // A late response for an older query must not clear the spinner
        // or replace the list while the newer query is still debouncing.
        if (isCurrent(key)) {
          results.value = items
          loading.value = false
        }
      } catch (e) {
        if (e?.name === 'AbortError') return
        if (isCurrent(key)) {
          results.value = []
          loading.value = false
        }
      }
    }, debounceMs)
  })

  function move(delta) {
    const n = results.value.length
    if (!n) return
    activeIndex.value = activeIndex.value < 0
      ? (delta > 0 ? 0 : n - 1)
      : (activeIndex.value + delta + n) % n
  }

  function current() {
    return results.value[activeIndex.value] || null
  }

  function reset() {
    clearTimeout(timer)
    controller?.abort()
    results.value = []
    loading.value = false
    activeIndex.value = -1
  }

  return { results, loading, activeIndex, move, current, reset }
}
