<template>
  <Card hoverable radius="lg" :padding="false" class="group relative flex flex-col">
    <!-- Top band: logo centered + prominent, with gradient background -->
    <div class="relative overflow-hidden">
      <div class="absolute inset-0" :style="{ background: bandGradient }" />
      <div
        class="absolute inset-0 opacity-[0.08] pointer-events-none"
        :style="{
          backgroundImage: 'linear-gradient(white 1px, transparent 1px), linear-gradient(90deg, white 1px, transparent 1px)',
          backgroundSize: '20px 20px',
        }"
      />
      <!-- Discount badge top-right -->
      <div class="absolute top-3 right-3 z-10">
        <span class="ui-mono text-[11px] font-bold px-2 py-1 rounded-md bg-white/95 backdrop-blur text-[color:var(--color-ink)]">
          -{{ deal.discount }}%
        </span>
      </div>

      <div class="relative flex flex-col items-center pt-8 pb-5">
        <div class="p-1.5 bg-white rounded-[14px] border border-white/20 shadow-[0_4px_16px_-4px_rgba(0,0,0,0.25)]">
          <VendorLogo :src="deal.logo" :name="deal.name" size="lg" radius="md" />
        </div>
      </div>
    </div>

    <!-- Body -->
    <div class="flex flex-col items-center text-center px-5 pt-4 pb-5 flex-1 gap-3">
      <div>
        <h3 class="ui-display text-[16px] font-semibold text-[color:var(--color-ink)] leading-tight truncate">
          {{ deal.name }}
        </h3>
        <div class="flex items-center justify-center gap-1 mt-1 text-xs text-[color:var(--color-ink-muted)]">
          <svg class="w-3 h-3 text-[color:var(--color-caution)]" viewBox="0 0 20 20" fill="currentColor">
            <path d="M10 1l2.8 5.7 6.2.9-4.5 4.4 1.1 6.3L10 15.3 4.4 18.3l1.1-6.3L1 7.6l6.2-.9L10 1z"/>
          </svg>
          <span class="ui-mono font-medium text-[color:var(--color-ink)]">{{ Number(deal.rating).toFixed(1) }}</span>
          <span v-if="deal.reviews" class="text-[color:var(--color-ink-subtle)]">({{ deal.reviews }})</span>
        </div>
      </div>

      <!-- Coupon reveal -->
      <button
        type="button"
        @click.prevent="copy"
        class="ui-focus w-full flex items-center justify-between gap-2 rounded-[10px] border border-dashed border-[color:var(--color-accent-400)] bg-[color:var(--color-accent-50)] px-3 py-2.5 hover:border-solid hover:bg-[color:var(--color-accent-100)] transition-all"
      >
        <div class="flex flex-col items-start min-w-0">
          <span class="text-[9px] uppercase tracking-[0.1em] text-[color:var(--color-ink-muted)] font-bold">
            Code
          </span>
          <span class="ui-mono text-sm font-bold text-[color:var(--color-ink)] truncate">
            {{ deal.code }}
          </span>
        </div>
        <span class="text-[11px] font-semibold text-[color:var(--color-accent-600)] flex items-center gap-1 flex-shrink-0">
          <svg v-if="!copied" class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <rect x="9" y="9" width="13" height="13" rx="2" />
            <path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1" />
          </svg>
          <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12" />
          </svg>
          {{ copied ? 'Copied' : 'Copy' }}
        </span>
      </button>

      <a
        v-if="deal.url"
        :href="deal.url"
        class="ui-focus flex items-center justify-center gap-1 text-xs text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-accent-600)] transition-colors font-medium"
      >
        Visit store
        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
          <path d="M5 12h14M13 5l7 7-7 7"/>
        </svg>
      </a>
    </div>
  </Card>
</template>

<script setup>
import { ref, computed } from 'vue'
import Card from './Card.vue'
import VendorLogo from './VendorLogo.vue'

const props = defineProps({
  deal: { type: Object, required: true },
})

const copied = ref(false)

async function copy() {
  try {
    await navigator.clipboard.writeText(props.deal.code)
    copied.value = true
    setTimeout(() => (copied.value = false), 1500)
  } catch (e) { /* clipboard blocked */ }
}

const bandGradient = computed(() => {
  const palette = [
    ['#4F46E5', '#7C3AED'],
    ['#3B82F6', '#4F46E5'],
    ['#0EA5E9', '#6366F1'],
    ['#14B8A6', '#3B82F6'],
    ['#06B6D4', '#8B5CF6'],
    ['#1E293B', '#4F46E5'],
  ]
  const n = (props.deal.name || '?').trim()
  let h = 0
  for (let i = 0; i < n.length; i++) h = (h * 31 + n.charCodeAt(i)) & 0xffffffff
  const [a, b] = palette[Math.abs(h) % palette.length]
  return `linear-gradient(135deg, ${a} 0%, ${b} 100%)`
})
</script>
