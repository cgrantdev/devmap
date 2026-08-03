import { request } from 'undici'
import { config } from './config.js'

// Thin wrapper around the /api/bot/* endpoints on peptidemap.com.
// All calls bearer-auth'd and return parsed JSON or throw.

async function get(path, params = {}) {
  const url = new URL(config.apiBase + path)
  for (const [k, v] of Object.entries(params)) {
    if (v !== undefined && v !== null) url.searchParams.set(k, String(v))
  }
  const res = await request(url, {
    method: 'GET',
    headers: {
      Authorization: `Bearer ${config.apiToken}`,
      Accept: 'application/json',
      'User-Agent': 'peptidemap-bot/1.0',
    },
    bodyTimeout: 15_000,
    headersTimeout: 15_000,
  })
  if (res.statusCode >= 400) {
    const body = await res.body.text().catch(() => '')
    throw new Error(`API ${path} → ${res.statusCode}: ${body.slice(0, 200)}`)
  }
  return await res.body.json()
}

export const api = {
  health: () => get('/api/bot/health'),
  search: (q, limit = 5) => get('/api/bot/products/search', { q, limit }),
  vendors: (q, limit = 10) => get('/api/bot/vendors', { q, limit }),
  compare: (a, b) => get('/api/bot/compare', { a, b }),
  priceDrops: (since, threshold = 5, limit = 25) =>
    get('/api/bot/price-drops', { since, threshold, limit }),
  newProducts: (since, limit = 25) => get('/api/bot/new-products', { since, limit }),
  reviews: (since, limit = 25) => get('/api/bot/reviews', { since, limit }),
  blogOfDay: () => get('/api/bot/blog-of-day'),
  promoCodes: (limit = 15) => get('/api/bot/promo-codes', { limit }),
}
