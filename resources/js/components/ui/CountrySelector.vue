<template>
  <div class="relative" ref="rootEl">
    <button
      type="button"
      @click="open = !open"
      class="ui-focus flex items-center gap-2 h-10 px-3 rounded-[10px] border border-[color:var(--color-hairline)] bg-white text-sm font-medium text-[color:var(--color-ink-muted)] hover:border-[color:var(--color-accent-400)] hover:text-[color:var(--color-ink)] transition-colors"
    >
      <span class="text-base leading-none" aria-hidden="true">{{ selected.flag }}</span>
      <span class="hidden md:inline">{{ selected.code }}</span>
      <svg class="w-3 h-3 text-[color:var(--color-ink-subtle)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
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
            <span class="text-base leading-none" aria-hidden="true">{{ country.flag }}</span>
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
import { ref, onMounted, onUnmounted } from 'vue'

const countries = [
  { code: 'US', flag: '🇺🇸', name: 'United States' },
  { code: 'CA', flag: '🇨🇦', name: 'Canada' },
  { code: 'GB', flag: '🇬🇧', name: 'United Kingdom' },
  { code: 'EU', flag: '🇪🇺', name: 'European Union' },
  { code: 'AU', flag: '🇦🇺', name: 'Australia' },
  { code: 'DE', flag: '🇩🇪', name: 'Germany' },
  { code: 'FR', flag: '🇫🇷', name: 'France' },
  { code: 'JP', flag: '🇯🇵', name: 'Japan' },
  { code: 'SG', flag: '🇸🇬', name: 'Singapore' },
  { code: 'AE', flag: '🇦🇪', name: 'United Arab Emirates' },
  { code: 'MX', flag: '🇲🇽', name: 'Mexico' },
  { code: 'BR', flag: '🇧🇷', name: 'Brazil' },
]

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
