<template>
  <!-- Trigger button (used by nav) -->
  <button
    v-if="asTrigger"
    type="button"
    @click="open = true"
    :class="[
      'ui-focus flex items-center gap-2 h-9 px-3 rounded-[10px] text-[13px] transition-colors',
      dark
        ? 'bg-white/10 border border-white/15 text-white/50 hover:bg-white/15 hover:text-white/70'
        : 'bg-white border border-[color:var(--color-hairline)] text-[color:var(--color-ink-subtle)] hover:border-[color:var(--color-accent-400)] hover:text-[color:var(--color-ink-muted)]',
    ]"
  >
    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <circle cx="11" cy="11" r="8" />
      <path d="M21 21l-4.35-4.35" stroke-linecap="round" />
    </svg>
    <span class="flex-1 text-left truncate">{{ label }}</span>
  </button>

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
              autocomplete="off"
              role="combobox"
              aria-autocomplete="list"
              aria-controls="search-palette-results"
              :aria-expanded="results.length > 0 ? 'true' : 'false'"
              :aria-activedescendant="activeIndex >= 0 ? `search-palette-opt-${activeIndex}` : undefined"
              placeholder="Search vendors, compounds, research…"
              class="flex-1 bg-transparent outline-none ui-display text-[17px] placeholder-[color:var(--color-ink-subtle)]"
              @keydown.down.prevent="move(1)"
              @keydown.up.prevent="move(-1)"
              @keydown.enter.prevent="onEnter"
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
            <div v-else>
              <div v-if="results.length" id="search-palette-results" role="listbox" class="py-2">
                <a
                  v-for="(item, idx) in results"
                  :key="item.type + item.url"
                  :id="`search-palette-opt-${idx}`"
                  :href="item.url"
                  role="option"
                  tabindex="-1"
                  :aria-selected="idx === activeIndex ? 'true' : 'false'"
                  :class="[
                    'flex items-center gap-3 px-5 py-2.5 transition-colors',
                    idx === activeIndex ? 'bg-[color:var(--color-hairline-soft)]' : 'hover:bg-[color:var(--color-hairline-soft)]',
                  ]"
                  @mouseenter="setActive(idx)"
                  @click="$emit('navigate')"
                >
                  <span
                    :class="[
                      'flex-shrink-0 w-[68px] text-center text-[10px] uppercase tracking-[0.08em] font-semibold rounded-full py-0.5 border',
                      item.type === 'compound'
                        ? 'text-[color:var(--color-accent-700)] bg-[color:var(--color-accent-50)] border-[color:var(--color-accent-100)]'
                        : 'text-emerald-700 bg-emerald-50 border-emerald-200',
                    ]"
                  >{{ item.type === 'compound' ? 'Compound' : 'Vendor' }}</span>
                  <span class="flex-1 min-w-0">
                    <span class="block truncate text-[14px] font-medium text-[color:var(--color-ink)]">{{ item.label }}</span>
                    <span v-if="item.sublabel" class="block truncate text-[12px] text-[color:var(--color-ink-muted)] ui-mono">{{ item.sublabel }}</span>
                  </span>
                  <svg class="w-3.5 h-3.5 flex-shrink-0 text-[color:var(--color-ink-subtle)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M5 12h14M13 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </a>
              </div>
              <p v-else-if="loading" class="px-5 py-4 text-sm text-[color:var(--color-ink-muted)]">Searching…</p>
              <p v-else-if="query.trim().length >= 2" class="px-5 py-4 text-sm text-[color:var(--color-ink-muted)]">No quick matches.</p>
              <button
                type="button"
                class="w-full flex items-center gap-2 px-5 py-3 border-t border-[color:var(--color-hairline)] text-left text-sm text-[color:var(--color-ink-muted)] hover:bg-[color:var(--color-hairline-soft)] transition-colors"
                @click="submit"
              >
                <span class="flex-1 min-w-0 truncate">
                  Search everything for <span class="text-[color:var(--color-ink)] font-medium">"{{ query.trim() }}"</span>
                </span>
                <kbd class="ui-mono text-[10px] px-1.5 py-0.5 rounded border border-[color:var(--color-hairline)] text-[color:var(--color-ink-muted)]">Enter</kbd>
              </button>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </Teleport>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { useSearchSuggest } from '@/composables/useSearchSuggest'

const props = defineProps({
  asTrigger: { type: Boolean, default: true },
  dark: { type: Boolean, default: false },
  countryCode: { type: String, default: 'US' },
  // Trigger text. The desktop header passes a short one to fit the bar.
  label: { type: String, default: 'search vendors, compounds…' },
  // Only one mounted instance should own Ctrl/Cmd+K, or the shortcut
  // opens two palettes stacked on top of each other.
  shortcut: { type: Boolean, default: true },
})

const emit = defineEmits(['toggle-country', 'navigate'])

const open = ref(false)
defineExpose({ open, show: () => { open.value = true } })
const query = ref('')
const input = ref(null)
const { results, loading, activeIndex, move, current } = useSearchSuggest(query)
function setActive(idx) { activeIndex.value = idx }

const quickLinks = [
  { href: '/products', label: 'Browse all compounds', icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>' },
  { href: '/vendors', label: 'Vendor directory', icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>' },
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

function go(url) {
  emit('navigate')
  window.location.href = url
}

function submit() {
  if (!query.value.trim()) return
  go(`/search?q=${encodeURIComponent(query.value.trim())}`)
}

// Enter opens the highlighted suggestion, otherwise runs the full search.
function onEnter() {
  const item = current()
  if (item) go(item.url)
  else submit()
}

function handleKey(e) {
  // cmd/ctrl + K
  if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
    e.preventDefault()
    open.value = !open.value
  }
}

onMounted(() => { if (props.shortcut) window.addEventListener('keydown', handleKey) })
onUnmounted(() => window.removeEventListener('keydown', handleKey))
</script>
