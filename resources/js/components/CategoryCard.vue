<template>
  <a
    :href="to"
    class="ui-focus group flex flex-col bg-white border border-[color:var(--color-hairline)] rounded-[14px] p-4 hover:border-[color:var(--color-accent-400)] hover:shadow-[var(--shadow-md)] hover:-translate-y-[1px] transition-all duration-200 cursor-pointer"
  >
    <!-- Vial image — dominates the card when a real image is present -->
    <div
      :class="[
        'aspect-square flex items-center justify-center mb-4 rounded-[10px] overflow-hidden',
        image && !hasError ? 'bg-white' : 'bg-gradient-to-br from-slate-50 to-slate-100',
      ]"
    >
      <img
        v-if="image && !hasError"
        :src="image"
        :alt="name"
        class="w-full h-full object-contain select-none group-hover:scale-[1.03] transition-transform duration-300"
        loading="lazy"
        @error="onError"
      />
      <div v-else class="w-full h-full flex flex-col items-center justify-center text-slate-400 select-none">
        <svg viewBox="0 0 80 100" class="w-16 h-20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <rect x="26" y="6" width="28" height="6" rx="1" fill="#475569"/>
          <rect x="22" y="12" width="36" height="76" rx="3" fill="white" stroke="#94A3B8" stroke-width="1.5"/>
          <rect x="22" y="50" width="36" height="38" rx="2" fill="#93C5FD" opacity="0.65"/>
          <line x1="60" y1="22" x2="64" y2="22" stroke="#64748B" stroke-width="1"/>
          <line x1="60" y1="34" x2="64" y2="34" stroke="#64748B" stroke-width="1"/>
          <line x1="60" y1="46" x2="64" y2="46" stroke="#64748B" stroke-width="1"/>
          <line x1="60" y1="58" x2="64" y2="58" stroke="#64748B" stroke-width="1"/>
        </svg>
        <span class="text-[10px] uppercase tracking-wider font-semibold text-slate-500 mt-2 line-clamp-1 max-w-full px-2 text-center">{{ name }}</span>
      </div>
    </div>

    <!-- Content -->
    <div class="flex-1 flex flex-col">
      <h3 class="ui-display text-[17px] font-semibold text-[color:var(--color-ink)] leading-tight group-hover:text-[color:var(--color-accent-700)] transition-colors line-clamp-2">
        {{ name }}
      </h3>

      <!-- Vendor count chip — a real, concrete signal instead of the old
           italic category tagline. Only renders when we have a number. -->
      <div v-if="hasCount" class="mt-2 flex items-center gap-1.5 text-[11px] text-[color:var(--color-ink-muted)]">
        <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
        </svg>
        <span><strong class="ui-mono text-[color:var(--color-ink)]">{{ totalItems }}</strong> {{ Number(totalItems) === 1 ? 'product' : 'products' }}</span>
      </div>

      <!-- Bottom action — right-arrow hint that grows on hover instead of a
           tiny underlined text link. Reads as "click me" without shouting. -->
      <div class="mt-4 pt-3 border-t border-[color:var(--color-hairline-soft)] flex items-center justify-between">
        <span class="text-[12px] font-semibold text-[color:var(--color-accent-600)] group-hover:text-[color:var(--color-accent-700)] transition-colors">
          Compare vendors
        </span>
        <svg
          class="w-4 h-4 text-[color:var(--color-accent-600)] group-hover:translate-x-0.5 transition-transform"
          fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"
          stroke-linecap="round" stroke-linejoin="round"
          aria-hidden="true"
        >
          <path d="M5 12h14M13 5l7 7-7 7"/>
        </svg>
      </div>
    </div>
  </a>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  name: { type: String, required: true },
  image: { type: String, default: null },
  totalItems: { type: [String, Number], default: 0 },
  to: { type: String, required: true },
  // Kept for backwards-compat with existing usages, but no longer rendered —
  // "Healing & Recovery" style categories were reading as medical framing.
  researchArea: { type: String, default: null },
})

const hasError = ref(false)
const hasCount = computed(() => {
  const n = Number(props.totalItems)
  return !Number.isNaN(n) && n > 0
})

const onError = () => { hasError.value = true }
</script>
