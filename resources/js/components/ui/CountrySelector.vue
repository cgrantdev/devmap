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
    >
      <img
        :src="flagUrl(selected.code, 40)"
        :alt="`${selected.name} flag`"
        class="w-5 h-[15px] rounded-[2px] object-cover shadow-[0_0_0_1px_rgba(0,0,0,0.04)]"
      />
      <span class="hidden md:inline text-[13px] font-semibold tracking-tight">{{ selected.code }}</span>
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
            v-for="country in countries"
            :key="country.code"
            type="button"
            @click="select(country)"
            :class="[
              'ui-focus w-full flex items-center gap-3 px-3 py-2 text-sm text-left transition-colors',
              selected.code === country.code
                ? 'bg-[color:var(--color-accent-50)] text-[color:var(--color-accent-700)]'
                : 'text-[color:var(--color-ink)] hover:bg-[color:var(--color-hairline-soft)]',
            ]"
          >
            <img
              :src="flagUrl(country.code, 40)"
              :alt="`${country.name} flag`"
              class="w-5 h-[15px] rounded-[2px] object-cover shadow-[0_0_0_1px_rgba(0,0,0,0.04)] flex-shrink-0"
            />
            <span class="flex-1 truncate">{{ country.name }}</span>
            <svg
              v-if="selected.code === country.code"
              class="w-3.5 h-3.5 text-[color:var(--color-accent-600)]"
              fill="none"
              stroke="currentColor"
              stroke-width="2.5"
              viewBox="0 0 24 24"
              stroke-linecap="round"
              stroke-linejoin="round"
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
import { ref, onMounted, onUnmounted, defineProps } from 'vue'

const props = defineProps({
  dark: { type: Boolean, default: false },
})

const countries = [
  { code: 'US', name: 'United States' },
  { code: 'CA', name: 'Canada' },
  { code: 'GB', name: 'United Kingdom' },
  { code: 'EU', name: 'European Union' },
  { code: 'AU', name: 'Australia' },
  { code: 'DE', name: 'Germany' },
  { code: 'FR', name: 'France' },
  { code: 'JP', name: 'Japan' },
  { code: 'SG', name: 'Singapore' },
  { code: 'AE', name: 'United Arab Emirates' },
  { code: 'MX', name: 'Mexico' },
  { code: 'BR', name: 'Brazil' },
]

// flagcdn.com serves PNG flags at arbitrary widths. Free, fast, cached on CDN.
// For EU we fall back to a generic star graphic since there's no ISO country code.
function flagUrl(code, width = 40) {
  if (!code) return ''
  if (code === 'EU') return `https://flagcdn.com/w${width}/eu.png`
  return `https://flagcdn.com/w${width}/${code.toLowerCase()}.png`
}

const STORAGE_KEY = 'pmap.country'
const selected = ref(countries[0])
const open = ref(false)
const rootEl = ref(null)

onMounted(() => {
  try {
    const stored = localStorage.getItem(STORAGE_KEY)
    if (stored) {
      const found = countries.find((c) => c.code === stored)
      if (found) selected.value = found
    }
  } catch (e) { /* noop */ }

  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

function select(c) {
  selected.value = c
  open.value = false
  try { localStorage.setItem(STORAGE_KEY, c.code) } catch (e) { /* noop */ }
  // Dispatch an event so other parts of the app can listen for region changes
  window.dispatchEvent(new CustomEvent('pmap:country-change', { detail: c }))
}

function handleClickOutside(e) {
  if (open.value && rootEl.value && !rootEl.value.contains(e.target)) {
    open.value = false
  }
}
</script>
