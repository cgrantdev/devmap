<template>
  <a
    :href="vendor.url"
    class="ui-focus group relative flex flex-col rounded-[14px] border border-[color:var(--color-hairline)] bg-white overflow-hidden ui-lift"
    :aria-label="`View ${vendor.name}`"
  >
    <!-- Cover: product thumbs collage or deterministic gradient -->
    <div class="relative aspect-[16/9] overflow-hidden border-b border-[color:var(--color-hairline)] bg-[color:var(--color-hairline-soft)]">
      <!-- If vendor has product thumbs, show a 4-tile collage -->
      <div v-if="thumbs.length >= 4" class="absolute inset-0 grid grid-cols-4 gap-[1px] bg-[color:var(--color-hairline)]">
        <div
          v-for="(thumb, i) in thumbs.slice(0, 4)"
          :key="i"
          class="bg-[color:var(--color-hairline-soft)] overflow-hidden"
        >
          <img
            :src="thumb"
            alt=""
            class="w-full h-full object-cover group-hover:scale-[1.04] transition-transform duration-[400ms] ease-out"
            loading="lazy"
          />
        </div>
      </div>
      <!-- If fewer than 4 thumbs, single image + gradient fill -->
      <template v-else>
        <div class="absolute inset-0" :style="{ background: coverGradient }" />
        <div
          class="absolute inset-0 opacity-[0.07]"
          :style="{
            backgroundImage: 'linear-gradient(white 1px, transparent 1px), linear-gradient(90deg, white 1px, transparent 1px)',
            backgroundSize: '24px 24px',
          }"
        />
        <img
          v-if="thumbs[0]"
          :src="thumbs[0]"
          alt=""
          class="absolute right-4 bottom-4 w-24 h-24 object-cover rounded-[10px] border border-white/20 shadow-lg group-hover:scale-[1.04] transition-transform duration-[400ms] ease-out"
          loading="lazy"
        />
      </template>

      <!-- Top-right verified shield -->
      <div v-if="vendor.verified" class="absolute top-3 right-3">
        <VerifiedShield size="md" :animate="false" />
      </div>
    </div>

    <!-- Body -->
    <div class="relative p-5 pb-6 flex-1 flex flex-col gap-4">
      <!-- Logo + name row. Logo lifts up over the cover -->
      <div class="flex items-start gap-3 -mt-10">
        <div class="p-1 bg-white rounded-[12px] border border-[color:var(--color-hairline)] shadow-sm flex-shrink-0">
          <VendorLogo :src="vendor.logo_url" :name="vendor.name" size="md" radius="md" />
        </div>
        <div class="flex-1 min-w-0 pt-11">
          <h3 class="ui-display text-[17px] font-semibold text-[color:var(--color-ink)] leading-tight truncate">
            {{ vendor.name }}
          </h3>
          <div class="flex items-center gap-1.5 mt-1 text-xs text-[color:var(--color-ink-muted)]">
            <svg class="w-3 h-3 text-[color:var(--color-caution)]" viewBox="0 0 20 20" fill="currentColor">
              <path d="M10 1l2.8 5.7 6.2.9-4.5 4.4 1.1 6.3L10 15.3 4.4 18.3l1.1-6.3L1 7.6l6.2-.9L10 1z" />
            </svg>
            <span class="ui-mono font-medium text-[color:var(--color-ink)]">{{ (vendor.rating_average || 0).toFixed(1) }}</span>
            <span class="text-[color:var(--color-ink-subtle)]">·</span>
            <span>{{ vendor.rating_count || 0 }} reviews</span>
          </div>
        </div>
      </div>

      <!-- Data strip -->
      <div class="flex items-center flex-wrap gap-x-4 gap-y-2 text-xs text-[color:var(--color-ink-muted)] pb-4 border-b border-[color:var(--color-hairline-soft)]">
        <div class="flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5 text-[color:var(--color-ink-subtle)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="7" height="7" rx="1" />
            <rect x="14" y="3" width="7" height="7" rx="1" />
            <rect x="3" y="14" width="7" height="7" rx="1" />
            <rect x="14" y="14" width="7" height="7" rx="1" />
          </svg>
          <span class="ui-mono font-medium text-[color:var(--color-ink)]">{{ vendor.product_count || 0 }}</span>
          <span>compounds</span>
        </div>
        <div v-if="vendor.location" class="flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5 text-[color:var(--color-ink-subtle)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 2a8 8 0 00-8 8c0 5.5 8 12 8 12s8-6.5 8-12a8 8 0 00-8-8z"/>
            <circle cx="12" cy="10" r="3"/>
          </svg>
          <span>{{ vendor.location }}</span>
        </div>
        <div v-if="vendor.last_tested_label" class="flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5 text-[color:var(--color-verified)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 2v6l-4 7c-1 1.8 0 4 2 4h10c2 0 3-2.2 2-4l-4-7V2"/>
            <path d="M8 2h8"/>
          </svg>
          <span>Tested {{ vendor.last_tested_label }}</span>
        </div>
      </div>

      <!-- Footer CTA -->
      <div class="flex items-center justify-between">
        <span class="text-xs text-[color:var(--color-ink-muted)]">
          <template v-if="vendor.verified">Verified vendor</template>
          <template v-else>Listed vendor</template>
        </span>
        <span class="inline-flex items-center gap-1 text-sm font-medium text-[color:var(--color-accent-600)] group-hover:translate-x-0.5 transition-transform duration-[180ms]">
          View
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
import VerifiedShield from './VerifiedShield.vue'
import VendorLogo from './VendorLogo.vue'

const props = defineProps({
  vendor: {
    type: Object,
    required: true,
    /* shape: {
      id, name, logo_url, url,
      rating_average, rating_count,
      verified: bool,
      product_count: int,
      location?: string,
      last_tested_label?: string,
      product_thumbs: [url, url, url, url],
    } */
  },
})

const thumbs = computed(() => (props.vendor.product_thumbs || []).filter(Boolean))

const coverGradient = computed(() => {
  const palette = [
    ['#1E293B', '#4F46E5'],
    ['#0F172A', '#6366F1'],
    ['#1E3A8A', '#3B82F6'],
    ['#0C4A6E', '#0EA5E9'],
    ['#134E4A', '#14B8A6'],
    ['#312E81', '#4F46E5'],
  ]
  const n = (props.vendor.name || '?').trim()
  let h = 0
  for (let i = 0; i < n.length; i++) h = (h * 31 + n.charCodeAt(i)) & 0xffffffff
  const [a, b] = palette[Math.abs(h) % palette.length]
  return `linear-gradient(135deg, ${a} 0%, ${b} 100%)`
})
</script>
