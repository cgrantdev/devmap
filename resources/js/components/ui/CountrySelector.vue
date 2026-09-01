<template>
  <div class="relative" ref="rootEl">
    <button
      type="button"
      @click="open = !open"
      :class="[
        'ui-focus flex items-center gap-2 h-10 pl-2.5 pr-2 rounded-[10px] text-sm font-medium transition-colors',
        dark
          ? 'bg-white/10 border border-white/15 text-white hover:bg-white/15'
          : 'border border-[color:var(--color-hairline)] bg-white text-[color:var(--color-ink)] hover:border-[color:var(--color-accent-400)]',
      ]"
      :aria-label="location ? `Filtering by ships to ${location}` : 'Filter by shipping destination'"
    >
      <img
        v-if="selectedCode"
        :src="flagUrl(selectedCode, 40)"
        :alt="`${location} flag`"
        class="w-5 h-[15px] rounded-[2px] object-cover shadow-[0_0_0_1px_rgba(0,0,0,0.04)]"
      />
      <svg v-else class="w-4 h-4 text-[color:var(--color-ink-subtle)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/>
        <circle cx="12" cy="10" r="3"/>
      </svg>
      <span class="hidden md:inline text-[13px] font-semibold tracking-tight">{{ selectedCode || 'ALL' }}</span>
      <svg class="w-3 h-3 text-[color:var(--color-ink-subtle)]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
        <path d="M6 9l6 6 6-6"/>
      </svg>
    </button>

    <transition
      enter-active-class="transition-all duration-[150ms] ease-out"
      enter-from-class="opacity-0 translate-y-1"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition-all duration-[100ms] ease-in"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 translate-y-1"
    >
      <div
        v-if="open"
        class="absolute right-0 mt-2 w-64 rounded-[12px] bg-white border border-[color:var(--color-hairline)] shadow-[var(--shadow-lg)] overflow-hidden z-50"
      >
        <div class="px-3 py-2 border-b border-[color:var(--color-hairline)] text-[10px] uppercase tracking-[0.1em] font-semibold text-[color:var(--color-ink-subtle)]">
          Ships to
        </div>
        <div class="max-h-80 overflow-y-auto py-1">
          <button
            type="button"
            @click="select('')"
            :class="[
              'ui-focus w-full flex items-center gap-3 px-3 py-2 text-sm text-left transition-colors',
              !location
                ? 'bg-[color:var(--color-accent-50)] text-[color:var(--color-accent-700)]'
                : 'text-[color:var(--color-ink)] hover:bg-[color:var(--color-hairline-soft)]',
            ]"
          >
            <span class="w-5 h-[15px] flex items-center justify-center text-[10px] font-bold text-[color:var(--color-ink-subtle)]">All</span>
            <span class="flex-1 truncate">All locations</span>
            <svg
              v-if="!location"
              class="w-3.5 h-3.5 text-[color:var(--color-accent-600)]"
              fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"
              stroke-linecap="round" stroke-linejoin="round"
            >
              <polyline points="20 6 9 17 4 12" />
            </svg>
          </button>
          <button
            v-for="loc in siteLocations"
            :key="loc.name"
            type="button"
            @click="select(loc.name)"
            :class="[
              'ui-focus w-full flex items-center gap-3 px-3 py-2 text-sm text-left transition-colors',
              location === loc.name
                ? 'bg-[color:var(--color-accent-50)] text-[color:var(--color-accent-700)]'
                : 'text-[color:var(--color-ink)] hover:bg-[color:var(--color-hairline-soft)]',
            ]"
          >
            <img
              :src="flagUrl(codeFor(loc.name), 40)"
              :alt="`${loc.name} flag`"
              class="w-5 h-[15px] rounded-[2px] object-cover shadow-[0_0_0_1px_rgba(0,0,0,0.04)] flex-shrink-0"
            />
            <span class="flex-1 truncate">{{ loc.name }}</span>
            <span class="text-[11px] text-[color:var(--color-ink-subtle)] tabular-nums">{{ loc.vendor_count }}</span>
            <svg
              v-if="location === loc.name"
              class="w-3.5 h-3.5 text-[color:var(--color-accent-600)]"
              fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"
              stroke-linecap="round" stroke-linejoin="round"
            >
              <polyline points="20 6 9 17 4 12" />
            </svg>
          </button>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useGlobalLocation, applyLocation } from '@/composables/useGlobalLocation'

defineProps({
  dark: { type: Boolean, default: false },
})

const page = usePage()
const siteLocations = computed(() => page.props.site_locations ?? [])
const { location } = useGlobalLocation()

// Map full country name → ISO code for flag CDN. Extend as new vendor
// locations show up in the DB.
const NAME_TO_CODE = {
  'United States': 'us',
  'United Kingdom': 'gb',
  'Germany': 'de',
  'Czechia': 'cz',
  'Romania': 'ro',
  'Canada': 'ca',
  'Australia': 'au',
  'Netherlands': 'nl',
  'Poland': 'pl',
  'France': 'fr',
  'Italy': 'it',
  'Spain': 'es',
  'Ireland': 'ie',
  'Switzerland': 'ch',
  'Singapore': 'sg',
  'Japan': 'jp',
  'Mexico': 'mx',
  'Brazil': 'br',
  'United Arab Emirates': 'ae',
}
function codeFor(name) { return NAME_TO_CODE[name] || null }
const selectedCode = computed(() => {
  const c = codeFor(location.value)
  return c ? c.toUpperCase() : ''
})

function flagUrl(code, width = 40) {
  if (!code) return ''
  return `https://flagcdn.com/w${width}/${code.toLowerCase()}.png`
}

const open = ref(false)
const rootEl = ref(null)

function select(name) {
  open.value = false
  applyLocation(name)
}

// Sync stored location → URL query on first load. Previously the header
// showed 'Germany' from localStorage but the page URL had no ?location=
// param, so filters didn't apply and the user saw all 34 vendors instead
// of just the ones shipping to DE. Runs once per page load; skips if URL
// already carries the correct value.
onMounted(() => {
  if (typeof window === 'undefined') return
  const stored = (location.value || '').trim()
  if (!stored) return
  try {
    const url = new URL(window.location.href)
    if (url.searchParams.get('location') === stored) return
    // Only auto-apply on pages where the server-side location filter runs.
    // Vendor dashboard / admin pages ignore it, so don't force a reload there.
    const path = url.pathname
    const filteredPaths = ['/', '/brands', '/vendors', '/products', '/peptides', '/compare', '/search']
    const applies = filteredPaths.some(p => path === p || path.startsWith(p + '/'))
    if (!applies) return
    applyLocation(stored)
  } catch {}
})

function handleClickOutside(e) {
  if (open.value && rootEl.value && !rootEl.value.contains(e.target)) {
    open.value = false
  }
}

onMounted(() => document.addEventListener('click', handleClickOutside))
onUnmounted(() => document.removeEventListener('click', handleClickOutside))
</script>
