<template>
  <section
    v-if="series.length >= 2"
    class="rounded-[12px] border border-[color:var(--color-hairline)] bg-white px-4 pt-3 pb-2.5"
  >
    <div class="flex items-center justify-between gap-x-3 gap-y-1.5 flex-wrap">
      <h3 class="text-[11px] uppercase tracking-[0.1em] font-semibold text-[color:var(--color-ink-subtle)]">{{ heading }}</h3>
      <span
        v-if="callout.kind === 'lowest'"
        class="inline-flex items-center gap-1 h-6 px-2 rounded-full border border-emerald-200 bg-emerald-50 text-[11px] font-semibold text-emerald-800"
      >
        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5" /></svg>
        {{ callout.text }}
      </span>
      <span v-else class="text-[12px] text-[color:var(--color-ink-muted)]">
        {{ callout.text }}
        <span v-if="callout.value" class="ui-mono font-semibold text-[color:var(--color-ink)]">{{ callout.value }}</span>
      </span>
    </div>

    <div ref="plotRef" class="relative mt-2" :style="{ height: `${H}px` }">
      <svg
        :width="W"
        :height="H"
        :viewBox="`0 0 ${W} ${H}`"
        class="block overflow-visible outline-none touch-pan-y cursor-crosshair focus-visible:ring-2 focus-visible:ring-[color:var(--color-accent-500)]/40 rounded"
        role="img"
        :aria-label="ariaLabel"
        tabindex="0"
        @pointermove="onPointer"
        @pointerdown="onPointer"
        @pointerleave="clearActive"
        @focus="onFocus"
        @blur="clearActive"
        @keydown="onKey"
      >
        <!-- Recessive hairline at the period low. -->
        <line
          :x1="PAD_L"
          :x2="W - PAD_R"
          :y1="lowY"
          :y2="lowY"
          style="stroke: var(--color-hairline); stroke-width: 1"
        />
        <path
          :d="linePath"
          fill="none"
          style="stroke: var(--color-accent-600); stroke-width: 2; stroke-linejoin: round; stroke-linecap: round"
        />
        <template v-if="active">
          <line
            :x1="active.x"
            :x2="active.x"
            :y1="0"
            :y2="H"
            style="stroke: var(--color-ink-subtle); stroke-width: 1"
          />
          <circle :cx="active.x" :cy="active.y" r="4" style="fill: var(--color-accent-600); stroke: #fff; stroke-width: 2" />
        </template>
        <circle v-else :cx="endPoint.x" :cy="endPoint.y" r="4" style="fill: var(--color-accent-600); stroke: #fff; stroke-width: 2" />
      </svg>

      <div
        v-if="active"
        class="pointer-events-none absolute bottom-full mb-1 z-10 -translate-x-1/2 rounded-md bg-[color:var(--color-ink)] px-2 py-1 text-white shadow-md whitespace-nowrap"
        :style="{ left: `${tooltipLeft}px` }"
      >
        <div class="ui-mono text-[12px] font-semibold leading-tight">{{ money(active.price) }}</div>
        <div class="text-[10px] text-white/70 leading-tight">{{ active.label }}</div>
      </div>
    </div>

    <div class="mt-1 flex items-center justify-between gap-2 text-[11px] text-[color:var(--color-ink-subtle)]">
      <span>{{ startLabel }}</span>
      <span
        v-if="change"
        :class="['font-semibold ui-mono', change.dir < 0 ? 'text-emerald-700' : change.dir > 0 ? 'text-rose-700' : 'text-[color:var(--color-ink-muted)]']"
      >{{ change.text }}</span>
      <span>Today</span>
    </div>

    <details class="mt-1.5">
      <summary class="ui-focus cursor-pointer list-none text-[11px] font-medium text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)] inline-flex items-center gap-1">
        Price history
        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6" /></svg>
      </summary>
      <table class="mt-2 w-full text-[12px]">
        <caption class="sr-only">{{ heading }}, in the vendor's currency</caption>
        <thead>
          <tr class="text-left text-[10px] uppercase tracking-[0.08em] text-[color:var(--color-ink-subtle)]">
            <th scope="col" class="py-1 font-semibold">Date</th>
            <th scope="col" class="py-1 font-semibold text-right">Retail</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in changeRows" :key="row.key" class="border-t border-[color:var(--color-hairline-soft)]">
            <td class="py-1 text-[color:var(--color-ink-muted)]">{{ row.label }}</td>
            <td class="py-1 text-right ui-mono tabular-nums text-[color:var(--color-ink)]">
              {{ money(row.price) }}<span v-if="row.isLow" class="ml-1 text-[10px] font-sans text-[color:var(--color-ink-subtle)]">low</span>
            </td>
          </tr>
        </tbody>
      </table>
    </details>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { formatMoney } from './priceFormat'

// Step line of the vendor's retail price (its sale price when lower) over
// the last 90 days. The backend stores a snapshot only when a price
// changes, so each point is the price in force until the next point.
const props = defineProps({
  // { points: [{ date: 'YYYY-MM-DD', price }], lowest, highest, current,
  //   is_lowest, change_pct, currency_symbol, days }
  history: { type: Object, required: true },
})

const H = 56
const PAD_L = 6
const PAD_R = 8
const PAD_T = 8
const PAD_B = 8
const DAY = 86400000

const plotRef = ref(null)
const W = ref(320)
let observer = null

onMounted(() => {
  const el = plotRef.value
  if (!el) return
  const measure = () => {
    const w = Math.round(el.clientWidth || 0)
    if (w > 0) W.value = Math.max(160, w)
  }
  measure()
  if (typeof ResizeObserver !== 'undefined') {
    observer = new ResizeObserver(measure)
    observer.observe(el)
  }
})
onBeforeUnmount(() => observer?.disconnect())

function parseDay(s) {
  const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(s || ''))
  return m ? Date.UTC(Number(m[1]), Number(m[2]) - 1, Number(m[3])) : NaN
}
function dayLabel(t) {
  return new Date(t).toLocaleDateString('en-US', { month: 'short', day: 'numeric', timeZone: 'UTC' })
}
const symbol = computed(() => props.history?.currency_symbol || '$')
function money(v) {
  return formatMoney(v, symbol.value) ?? ''
}

const series = computed(() => {
  const raw = Array.isArray(props.history?.points) ? props.history.points : []
  return raw
    .map((p) => ({ t: parseDay(p?.date), price: Number(p?.price) }))
    .filter((p) => Number.isFinite(p.t) && Number.isFinite(p.price) && p.price > 0)
    .sort((a, b) => a.t - b.t)
})

const t0 = computed(() => series.value[0]?.t ?? 0)
const t1 = computed(() => {
  const last = series.value[series.value.length - 1]?.t ?? 0
  return last > t0.value ? last : t0.value + DAY
})
const prices = computed(() => series.value.map((p) => p.price))
const lo = computed(() => Math.min(...prices.value))
const hi = computed(() => Math.max(...prices.value))
const flat = computed(() => hi.value - lo.value < 0.005)
const current = computed(() => series.value[series.value.length - 1]?.price ?? null)
const first = computed(() => series.value[0]?.price ?? null)
const spanDays = computed(() => Math.round((t1.value - t0.value) / DAY))
const fullWindow = computed(() => spanDays.value >= (Number(props.history?.days) || 90) - 1)
const startLabel = computed(() => (series.value.length ? dayLabel(t0.value) : ''))

// "Retail" matches the price block's label for the vendor's own price
// (before any Peptidemap code).
const heading = computed(() => (fullWindow.value
  ? `Retail price, last ${Number(props.history?.days) || 90} days`
  : `Retail price since ${startLabel.value}`))

const callout = computed(() => {
  const period = fullWindow.value ? `in ${Number(props.history?.days) || 90} days` : `since ${startLabel.value}`
  if (flat.value) return { kind: 'flat', text: `No change ${period}`, value: null }
  if (current.value !== null && current.value <= lo.value + 0.005) {
    return { kind: 'lowest', text: `Lowest ${period}`, value: null }
  }
  return { kind: 'low', text: fullWindow.value ? `${Number(props.history?.days) || 90}-day low` : 'Low', value: money(lo.value) }
})

const change = computed(() => {
  if (first.value === null || current.value === null || first.value <= 0) return null
  const pct = Math.round(((current.value - first.value) / first.value) * 1000) / 10
  if (pct === 0) return flat.value ? null : { dir: 0, text: '0.0%' }
  return { dir: pct < 0 ? -1 : 1, text: `${pct < 0 ? '↓' : '↑'} ${Math.abs(pct).toFixed(1)}%` }
})

// Scales. A flat series sits mid-height; otherwise 12% headroom.
const domain = computed(() => {
  if (flat.value) {
    const v = lo.value
    return [v * 0.95, v * 1.05 || 1]
  }
  const pad = (hi.value - lo.value) * 0.12
  return [lo.value - pad, hi.value + pad]
})
function xOf(t) {
  return PAD_L + ((t - t0.value) / (t1.value - t0.value)) * (W.value - PAD_L - PAD_R)
}
function yOf(price) {
  const [d0, d1] = domain.value
  const span = d1 - d0 || 1
  return PAD_T + (1 - (price - d0) / span) * (H - PAD_T - PAD_B)
}

const coords = computed(() => series.value.map((p) => ({ x: xOf(p.t), y: yOf(p.price) })))
const linePath = computed(() => {
  const c = coords.value
  if (!c.length) return ''
  let d = `M${c[0].x.toFixed(1)},${c[0].y.toFixed(1)}`
  for (let i = 1; i < c.length; i++) {
    d += ` H${c[i].x.toFixed(1)} V${c[i].y.toFixed(1)}`
  }
  return d
})
const endPoint = computed(() => coords.value[coords.value.length - 1] || { x: 0, y: 0 })
const lowY = computed(() => yOf(lo.value))

// Price in force on a given day: the last point on or before it.
function priceAt(t) {
  let price = series.value[0]?.price ?? null
  for (const p of series.value) {
    if (p.t <= t) price = p.price
    else break
  }
  return price
}

// Hover and keyboard share one readout. Pointer snaps to the nearest
// day; arrow keys step between price changes.
const active = ref(null)
const keyIndex = ref(-1)

function setActiveAt(t, label) {
  const price = priceAt(t)
  if (price === null) return
  active.value = { x: xOf(t), y: yOf(price), price, label }
}
function onPointer(ev) {
  const rect = ev.currentTarget.getBoundingClientRect()
  const px = ev.clientX - rect.left
  const ratio = Math.min(1, Math.max(0, (px - PAD_L) / (W.value - PAD_L - PAD_R)))
  const t = t0.value + Math.round((ratio * (t1.value - t0.value)) / DAY) * DAY
  setActiveAt(t, t >= t1.value ? 'Today' : dayLabel(t))
}
function showIndex(i) {
  const n = series.value.length
  if (!n) return
  keyIndex.value = Math.min(n - 1, Math.max(0, i))
  const p = series.value[keyIndex.value]
  setActiveAt(p.t, keyIndex.value === n - 1 ? 'Today' : dayLabel(p.t))
}
function onFocus() {
  showIndex(series.value.length - 1)
}
function onKey(ev) {
  const keys = { ArrowLeft: -1, ArrowRight: 1 }
  if (ev.key in keys) {
    ev.preventDefault()
    showIndex((keyIndex.value < 0 ? series.value.length - 1 : keyIndex.value) + keys[ev.key])
  } else if (ev.key === 'Home') {
    ev.preventDefault()
    showIndex(0)
  } else if (ev.key === 'End') {
    ev.preventDefault()
    showIndex(series.value.length - 1)
  } else if (ev.key === 'Escape') {
    clearActive()
  }
}
function clearActive() {
  active.value = null
  keyIndex.value = -1
}

const tooltipLeft = computed(() => {
  if (!active.value) return 0
  return Math.min(W.value - 44, Math.max(44, active.value.x))
})

// Table view: the opening price and each change after it, newest first.
// A point that repeats the previous price (today's price when it has not
// moved since the last change) adds nothing and is skipped.
const changeRows = computed(() => {
  const n = series.value.length
  const rows = []
  series.value.forEach((p, i) => {
    const prev = rows[rows.length - 1]
    if (prev && Math.abs(prev.price - p.price) < 0.005) return
    rows.push({
      key: `${p.t}-${i}`,
      label: i === n - 1 ? 'Today' : (i === 0 && fullWindow.value ? `${dayLabel(p.t)} (start)` : dayLabel(p.t)),
      price: p.price,
      isLow: !flat.value && Math.abs(p.price - lo.value) < 0.005,
    })
  })
  return rows.reverse()
})

const ariaLabel = computed(() => {
  if (!series.value.length) return ''
  const parts = [
    `${heading.value}: ${money(first.value)} on ${startLabel.value}, ${money(current.value)} today`,
    flat.value ? 'no change' : `low ${money(lo.value)}, high ${money(hi.value)}`,
  ]
  if (callout.value.kind === 'lowest') parts.push(callout.value.text)
  return parts.join('; ') + '. Use the arrow keys to step through price changes.'
})
</script>
