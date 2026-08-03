import { readFile, writeFile, mkdir } from 'node:fs/promises'
import { dirname } from 'node:path'

// Tiny JSON-file-backed state store. Used to remember:
//   - last time each feed polled the API (so we don't repost old data)
//   - which product_ids the price-drop feed has already announced,
//     and when, so we can honour PRICE_DROP_DEDUPE_DAYS
//
// One file per bot process; if we ever run >1 instance we'll swap to Redis.

import { fileURLToPath } from 'node:url'
import { resolve, dirname as pdirname } from 'node:path'
const __dir = pdirname(fileURLToPath(import.meta.url))
const STATE_PATH = process.env.BOT_STATE_PATH || resolve(__dir, '../data/state.json')

let cache = null

async function load() {
  if (cache) return cache
  try {
    cache = JSON.parse(await readFile(STATE_PATH, 'utf8'))
  } catch {
    cache = {}
  }
  return cache
}

async function save() {
  await mkdir(dirname(STATE_PATH), { recursive: true }).catch(() => {})
  await writeFile(STATE_PATH, JSON.stringify(cache, null, 2))
}

export async function get(key, fallback = null) {
  const s = await load()
  return s[key] ?? fallback
}

export async function set(key, value) {
  const s = await load()
  s[key] = value
  await save()
}
