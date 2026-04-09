<template>
  <a
    :href="vendor.url"
    class="ui-focus group relative flex flex-col rounded-[14px] border border-[color:var(--color-hairline)] bg-white overflow-hidden ui-lift"
    :aria-label="vendor.name"
  >
    <!-- Banner: vendor logo centered on gradient background -->
    <div class="relative aspect-[16/7] overflow-hidden">
      <div class="absolute inset-0" :style="{ background: coverGradient }" />
      <!-- Centered logo as the banner focal point -->
      <div class="absolute inset-0 flex items-center justify-center p-6">
        <img
          v-if="vendor.logo_url"
          :src="vendor.logo_url"
          :alt="`${vendor.name} logo`"
          class="max-h-full max-w-[70%] object-contain drop-shadow-lg"
          loading="lazy"
        />
        <span
          v-else
          class="ui-display text-white/90 text-4xl font-bold tracking-tight drop-shadow-lg"
        >
          {{ initial }}
        </span>
      </div>
      <!-- Partner chip -->
      <div v-if="vendor.is_partner" class="absolute top-3 right-3 z-10">
        <span class="ui-mono text-[9px] uppercase tracking-[0.14em] px-2 py-0.5 rounded-full bg-white/15 backdrop-blur-sm text-white border border-white/20 font-semibold">
          Partner
        </span>
      </div>
    </div>

    <!-- Body -->
    <div class="px-5 pt-5 pb-5 flex-1 flex flex-col gap-4">
      <div>
        <h3 class="ui-display text-[19px] font-semibold text-[color:var(--color-ink)] leading-tight tracking-tight">
          {{ vendor.name }}
        </h3>
        <div v-if="vendor.tagline" class="text-xs text-[color:var(--color-ink-muted)] mt-1 line-clamp-1">
          {{ vendor.tagline }}
        </div>
      </div>

      <!-- Rating strip -->
      <div class="flex items-center gap-2">
        <div class="flex items-center gap-0.5">
          <svg
            v-for="n in 5"
            :key="n"
            class="w-3.5 h-3.5"
            :class="n <= Math.round(vendor.rating_average || 0) ? 'text-[color:var(--color-caution)]' : 'text-[color:var(--color-hairline)]'"
            viewBox="0 0 20 20"
            fill="currentColor"
          >
            <path d="M10 1l2.8 5.7 6.2.9-4.5 4.4 1.1 6.3L10 15.3 4.4 18.3l1.1-6.3L1 7.6l6.2-.9L10 1z" />
          </svg>
        </div>
        <span class="ui-mono text-sm font-semibold text-[color:var(--color-ink)]">
          {{ (vendor.rating_average || 0).toFixed(1) }}
        </span>
        <span class="text-xs text-[color:var(--color-ink-subtle)]">
          ({{ vendor.rating_count || 0 }})
        </span>
      </div>

      <!-- Key stats: 3 columns, minimal copy -->
      <div class="grid grid-cols-3 gap-3 border-t border-[color:var(--color-hairline-soft)] pt-4">
        <div>
          <div class="ui-mono text-base font-semibold text-[color:var(--color-ink)] leading-none">
            {{ vendor.product_count || 0 }}
          </div>
          <div class="text-[10px] uppercase tracking-[0.08em] text-[color:var(--color-ink-subtle)] mt-1 font-semibold">
            Compounds
          </div>
        </div>
        <div>
          <div class="ui-mono text-base font-semibold text-[color:var(--color-ink)] leading-none truncate">
            {{ vendor.location || '—' }}
          </div>
          <div class="text-[10px] uppercase tracking-[0.08em] text-[color:var(--color-ink-subtle)] mt-1 font-semibold">
            Ships from
          </div>
        </div>
        <div>
          <div class="ui-mono text-base font-semibold leading-none truncate" :class="vendor.rating_average >= 4 ? 'text-[color:var(--color-verified)]' : 'text-[color:var(--color-ink)]'">
            {{ vendor.rating_average >= 4 ? 'High' : vendor.rating_average >= 3 ? 'Good' : '—' }}
          </div>
          <div class="text-[10px] uppercase tracking-[0.08em] text-[color:var(--color-ink-subtle)] mt-1 font-semibold">
            Trust score
          </div>
        </div>
      </div>

      <!-- CTA -->
      <div class="mt-auto pt-4 border-t border-[color:var(--color-hairline-soft)] flex items-center justify-between">
        <div v-if="vendor.founded_year" class="text-xs text-[color:var(--color-ink-subtle)] ui-mono">
          Est. {{ vendor.founded_year }}
        </div>
        <div v-else />
        <span class="inline-flex items-center gap-1 text-sm font-medium text-[color:var(--color-accent-600)] group-hover:translate-x-0.5 transition-transform duration-[180ms]">
          Visit vendor
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M5 12h14M13 5l7 7-7 7"/>
          </svg>
        </span>
      </div>
    </div>
  </a>
</template>

<script setup>
import { computed } from 'vue'
import VendorLogo from './VendorLogo.vue'

const props = defineProps({
  vendor: {
    type: Object,
    required: true,
  },
})

const initial = computed(() => {
  const n = (props.vendor.name || '?').trim()
  const parts = n.split(/\s+/)
  return parts.length >= 2
    ? (parts[0][0] + parts[1][0]).toUpperCase()
    : n.slice(0, 2).toUpperCase()
})

const coverGradient = computed(() => {
  const palette = [
    ['#1E293B', '#4F46E5'],
    ['#0F172A', '#6366F1'],
    ['#1E3A8A', '#3B82F6'],
    ['#0C4A6E', '#0EA5E9'],
    ['#134E4A', '#14B8A6'],
    ['#312E81', '#4F46E5'],
    ['#111827', '#7C3AED'],
    ['#164E63', '#06B6D4'],
  ]
  const n = (props.vendor.name || '?').trim()
  let h = 0
  for (let i = 0; i < n.length; i++) h = (h * 31 + n.charCodeAt(i)) & 0xffffffff
  const [a, b] = palette[Math.abs(h) % palette.length]
  return `linear-gradient(135deg, ${a} 0%, ${b} 100%)`
})
</script>
