<template>
  <!-- Trigger button (used by nav) — combined search + country flag -->
  <div v-if="asTrigger" class="flex items-center">
    <button
      type="button"
      @click="open = true"
      :class="[
        'ui-focus flex items-center gap-2 h-9 pl-3 pr-2 text-[13px] transition-colors',
        dark
          ? 'bg-white/10 border border-white/15 text-white/50 hover:bg-white/15 hover:text-white/70 rounded-[10px]'
          : 'bg-white border border-[color:var(--color-hairline)] border-r-0 text-[color:var(--color-ink-subtle)] hover:border-[color:var(--color-accent-400)] rounded-l-[10px]',
      ]"
    >
      <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <circle cx="11" cy="11" r="8" />
        <path d="M21 21l-4.35-4.35" stroke-linecap="round" />
      </svg>
      <span class="flex-1 text-left truncate">search vendors, compounds…</span>
    </button>
    <button
      v-if="!dark"
      type="button"
      @click="$emit('toggle-country')"
      class="ui-focus flex items-center gap-1.5 h-9 pl-2.5 pr-2.5 bg-white border border-[color:var(--color-hairline)] border-l-[color:var(--color-hairline-soft)] rounded-r-[10px] text-[13px] font-semibold text-[color:var(--color-ink)] hover:border-[color:var(--color-accent-400)] transition-colors"
    >
      <img
        :src="`https://flagcdn.com/w40/${(countryCode || 'us').toLowerCase()}.png`"
        :alt="countryCode"
        class="w-5 h-[15px] rounded-[2px] object-cover shadow-[0_0_0_1px_rgba(0,0,0,0.04)]"
      />
      <span class="hidden lg:inline text-[color:var(--color-ink-muted)]">{{ countryCode || 'US' }}</span>
      <svg class="w-2.5 h-2.5 text-[color:var(--color-ink-subtle)]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
    </button>
  </div>

  <!-- Modal overlay -->
  <Teleport to="body">
    <transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="open"
        class="fixed inset-0 z-[100] bg-[rgba(10,11,14,0.4)] backdrop-blur-sm flex items-start justify-center pt-[12vh] px-4"
        @click.self="open = false"
        @keydown.esc="open = false"
      >
        <div
          class="w-full max-w-xl bg-white rounded-[16px] border border-[color:var(--color-hairline)] shadow-[var(--shadow-lg)] overflow-hidden"
        >
          <div class="flex items-center gap-3 px-5 py-4 border-b border-[color:var(--color-hairline)]">
            <svg class="w-5 h-5 text-[color:var(--color-ink-muted)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <circle cx="11" cy="11" r="8" />
              <path d="M21 21l-4.35-4.35" stroke-linecap="round" />
            </svg>
            <input
              ref="input"
              v-model="query"
              type="text"
              placeholder="Search vendors, compounds, research…"
              class="flex-1 bg-transparent outline-none ui-display text-[17px] placeholder-[color:var(--color-ink-subtle)]"
              @keydown.enter="submit"
            />
            <kbd class="ui-mono text-[10px] px-1.5 py-0.5 rounded border border-[color:var(--color-hairline)] text-[color:var(--color-ink-muted)]">ESC</kbd>
          </div>

          <!-- Empty state / suggestions -->
          <div class="max-h-[50vh] overflow-y-auto">
            <div v-if="!query" class="p-5 text-sm text-[color:var(--color-ink-muted)]">
              <div class="text-xs uppercase tracking-[0.08em] text-[color:var(--color-ink-subtle)] font-medium mb-3">
                Quick actions
              </div>
              <div class="grid gap-1">
                <a
                  v-for="link in quickLinks"
                  :key="link.href"
                  :href="link.href"
                  class="flex items-center gap-3 px-3 py-2.5 rounded-[8px] hover:bg-[color:var(--color-hairline-soft)] text-[color:var(--color-ink)] transition-colors"
                >
                  <span class="text-[color:var(--color-ink-muted)]" v-html="link.icon" />
                  <span class="flex-1">{{ link.label }}</span>
                  <svg class="w-3.5 h-3.5 text-[color:var(--color-ink-subtle)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M5 12h14M13 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </a>
              </div>
            </div>
            <div v-else class="p-5 text-sm text-[color:var(--color-ink-muted)]">
              <p>
                Press Enter to search for
                <span class="text-[color:var(--color-ink)] font-medium">"{{ query }}"</span>.
              </p>
              <p class="text-xs mt-2 text-[color:var(--color-ink-subtle)]">
                Live typeahead coming next iteration — for now this routes to /search.
              </p>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </Teleport>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted, nextTick } from 'vue'

defineProps({
  asTrigger: { type: Boolean, default: true },
  dark: { type: Boolean, default: false },
  countryCode: { type: String, default: 'US' },
})

defineEmits(['toggle-country'])

const open = ref(false)
const query = ref('')
const input = ref(null)

const quickLinks = [
  { href: '/products', label: 'Browse all compounds', icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>' },
  { href: '/vendors', label: 'Verified vendors', icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>' },
  { href: '/compare', label: 'Compare vendors', icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M16 3h5v5M4 20L21 3M21 16v5h-5M15 15l6 6M4 4l5 5"/></svg>' },
  { href: '/encyclopedia', label: 'Peptide encyclopedia', icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/></svg>' },
]

watch(open, async (v) => {
  if (v) {
    await nextTick()
    input.value?.focus()
  } else {
    query.value = ''
  }
})

function submit() {
  if (!query.value.trim()) return
  window.location.href = `/search?q=${encodeURIComponent(query.value.trim())}`
}

function handleKey(e) {
  // cmd/ctrl + K
  if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
    e.preventDefault()
    open.value = !open.value
  }
}

onMounted(() => window.addEventListener('keydown', handleKey))
onUnmounted(() => window.removeEventListener('keydown', handleKey))
</script>
