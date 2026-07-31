// Batches banner-slot impressions and posts them to /api/banner-events/impressions.
// Click events are posted individually (small volume, we want them in DB immediately).

const impressionQueue = []
const seen = new Set() // dedupe per session: `${slot}|${banner_key}` fires impression once per pageload
let flushTimer = null

function csrfToken() {
  const el = document.querySelector('meta[name="csrf-token"]')
  return el?.getAttribute('content') || ''
}

function scheduleFlush() {
  if (flushTimer) return
  flushTimer = setTimeout(flushImpressions, 1500)
}

async function flushImpressions() {
  flushTimer = null
  if (impressionQueue.length === 0) return
  const events = impressionQueue.splice(0, impressionQueue.length)
  try {
    await fetch('/api/banner-events/impressions', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
        'X-Requested-With': 'XMLHttpRequest',
      },
      credentials: 'same-origin',
      body: JSON.stringify({ events, page_url: location.pathname }),
      keepalive: true,
    })
  } catch (_) { /* swallow — analytics is best-effort */ }
}

// Flush on unload so late impressions still land.
if (typeof window !== 'undefined') {
  window.addEventListener('pagehide', () => {
    if (impressionQueue.length) flushImpressions()
  })
}

/**
 * Record a banner impression. Deduped per pageload by (slot, banner_key).
 * @param {Object} evt - { slot, banner_key?, banner_id?, brand_id?, meta? }
 */
export function trackImpression(evt) {
  if (!evt?.slot) return
  const key = `${evt.slot}|${evt.banner_key ?? ''}`
  if (seen.has(key)) return
  seen.add(key)
  impressionQueue.push({
    slot: evt.slot,
    banner_key: evt.banner_key ?? null,
    banner_id: evt.banner_id ?? null,
    brand_id: evt.brand_id ?? null,
    meta: evt.meta ?? null,
  })
  scheduleFlush()
}

/**
 * Record a banner click. Fire-and-forget; does not block navigation.
 * @param {Object} evt - { slot, banner_key?, banner_id?, brand_id?, destination_url?, meta? }
 */
export function trackClick(evt) {
  if (!evt?.slot) return
  const payload = JSON.stringify({
    slot: evt.slot,
    banner_key: evt.banner_key ?? null,
    banner_id: evt.banner_id ?? null,
    brand_id: evt.brand_id ?? null,
    destination_url: evt.destination_url ?? null,
    meta: evt.meta ?? null,
  })
  // sendBeacon survives the navigation triggered by the anchor click.
  try {
    if (navigator.sendBeacon) {
      const blob = new Blob([payload], { type: 'application/json' })
      const ok = navigator.sendBeacon('/api/banner-events/click', blob)
      if (ok) return
    }
  } catch (_) { /* fall through */ }
  fetch('/api/banner-events/click', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken(),
      'X-Requested-With': 'XMLHttpRequest',
    },
    credentials: 'same-origin',
    body: payload,
    keepalive: true,
  }).catch(() => {})
}
