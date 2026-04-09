<template>
  <a
    :href="product.url"
    class="ui-focus group relative flex flex-col bg-white rounded-[14px] border border-[color:var(--color-hairline)] overflow-hidden ui-lift"
    :aria-label="product.name"
  >
    <!-- Image (1:1 square for balance) -->
    <div class="relative aspect-square overflow-hidden bg-[color:var(--color-hairline-soft)]">
      <img
        v-if="displayImage && !broken"
        :src="displayImage"
        :alt="product.name"
        class="absolute inset-0 w-full h-full object-cover group-hover:scale-[1.04] transition-transform duration-[500ms] ease-out"
        loading="lazy"
        @error="broken = true"
      />
      <template v-else>
        <div class="absolute inset-0" :style="{ background: fallbackGradient }" />
        <div
          class="absolute inset-0 opacity-[0.08]"
          :style="{
            backgroundImage: 'linear-gradient(white 1px, transparent 1px), linear-gradient(90deg, white 1px, transparent 1px)',
            backgroundSize: '22px 22px',
          }"
        />
        <div class="absolute inset-0 flex items-center justify-center p-6">
          <span class="ui-display text-white/70 font-semibold text-sm text-center line-clamp-3 tracking-tight">
            {{ product.name }}
          </span>
        </div>
      </template>

      <!-- Top badges (small, tight) -->
      <div v-if="hasBadges" class="absolute top-2.5 left-2.5 flex flex-wrap gap-1">
        <span
          v-if="product.featured"
          class="ui-mono text-[9px] uppercase tracking-[0.1em] px-1.5 py-0.5 rounded bg-gradient-to-br from-[color:var(--color-accent-500)] to-[color:var(--color-biotech-500)] text-white font-semibold"
        >
          Featured
        </span>
        <span
          v-if="product.lab_tested"
          class="ui-mono text-[9px] uppercase tracking-[0.1em] px-1.5 py-0.5 rounded bg-black/60 backdrop-blur text-white font-semibold"
        >
          Tested
        </span>
      </div>
    </div>

    <!-- Body: fixed vertical rhythm for balance -->
    <div class="flex flex-col gap-2 p-4 flex-1">
      <!-- Brand row -->
      <div v-if="product.brand_name" class="flex items-center gap-1.5 text-[11px] text-[color:var(--color-ink-muted)] font-medium">
        <span class="truncate">{{ product.brand_name }}</span>
        <VerifiedShield v-if="product.brand_verified" size="xs" :animate="false" />
      </div>

      <!-- Title -->
      <h3 class="ui-display text-[15px] font-semibold text-[color:var(--color-ink)] leading-[1.25] line-clamp-2 min-h-[38px]">
        {{ product.name }}
      </h3>

      <!-- Footer: price + CTA (always pinned bottom) -->
      <div class="mt-auto pt-3 flex items-baseline justify-between border-t border-[color:var(--color-hairline-soft)]">
        <div class="flex items-baseline gap-1.5">
          <span class="ui-mono text-[17px] font-bold text-[color:var(--color-ink)] tracking-tight">
            ${{ displayPrice }}
          </span>
          <span
            v-if="product.original_price"
            class="ui-mono text-[11px] text-[color:var(--color-ink-subtle)] line-through"
          >
            ${{ product.original_price }}
          </span>
        </div>
        <span class="inline-flex items-center gap-0.5 text-[11px] font-semibold text-[color:var(--color-accent-600)] group-hover:translate-x-0.5 transition-transform duration-[180ms]">
          View
          <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M5 12h14M13 5l7 7-7 7"/>
          </svg>
        </span>
      </div>
    </div>
  </a>
</template>

<script setup>
import { computed, ref } from 'vue'
import VerifiedShield from './VerifiedShield.vue'

const props = defineProps({
  product: {
    type: Object,
    required: true,
  },
})

const broken = ref(false)

const displayImage = computed(() => props.product.image_url || props.product.placeholder_image || null)

const displayPrice = computed(() => {
  const p = props.product.price
  if (p === null || p === undefined || p === '') return '—'
  return typeof p === 'number' ? p.toFixed(2) : p
})

const hasBadges = computed(() => props.product.featured || props.product.lab_tested)

const fallbackGradient = computed(() => {
  const palette = [
    ['#1E293B', '#4F46E5'],
    ['#0F172A', '#6366F1'],
    ['#1E3A8A', '#3B82F6'],
    ['#134E4A', '#14B8A6'],
    ['#312E81', '#4F46E5'],
  ]
  const n = (props.product.name || '?').trim()
  let h = 0
  for (let i = 0; i < n.length; i++) h = (h * 31 + n.charCodeAt(i)) & 0xffffffff
  const [a, b] = palette[Math.abs(h) % palette.length]
  return `linear-gradient(135deg, ${a} 0%, ${b} 100%)`
})
</script>
