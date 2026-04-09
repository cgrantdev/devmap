<template>
  <Card hoverable radius="lg" padding="lg" class="group relative">
    <!-- Top: logo + verified badge -->
    <div class="flex items-start justify-between mb-5">
      <div class="flex items-center gap-3">
        <VendorLogo :src="vendor.logo_url" :name="vendor.name" size="md" radius="md" />
        <div class="min-w-0">
          <h3 class="ui-display font-semibold text-[color:var(--color-ink)] text-base leading-tight truncate">
            {{ vendor.name }}
          </h3>
          <div class="flex items-center gap-1.5 mt-0.5 text-xs text-[color:var(--color-ink-muted)]">
            <svg class="w-3.5 h-3.5 text-[color:var(--color-caution)]" viewBox="0 0 20 20" fill="currentColor">
              <path d="M10 1l2.8 5.7 6.2.9-4.5 4.4 1.1 6.3L10 15.3 4.4 18.3l1.1-6.3L1 7.6l6.2-.9L10 1z" />
            </svg>
            <span class="ui-mono">{{ (vendor.rating_average || 0).toFixed(1) }}</span>
            <span>·</span>
            <span>{{ vendor.rating_count || 0 }} reviews</span>
          </div>
        </div>
      </div>
      <VerifiedShield v-if="vendor.verified" size="md" />
    </div>

    <!-- Middle: product thumbnails strip -->
    <div v-if="thumbs.length" class="flex gap-2 mb-5">
      <div
        v-for="(thumb, i) in thumbs"
        :key="i"
        class="aspect-square flex-1 rounded-[8px] bg-[color:var(--color-hairline-soft)] border border-[color:var(--color-hairline)] overflow-hidden"
      >
        <img
          v-if="thumb"
          :src="thumb"
          alt=""
          class="w-full h-full object-cover"
          loading="lazy"
        />
      </div>
    </div>

    <!-- Bottom: data strip -->
    <div class="flex items-center justify-between text-xs">
      <div class="flex items-center gap-3">
        <div class="flex items-center gap-1 text-[color:var(--color-ink-muted)]">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <rect x="3" y="3" width="7" height="7" rx="1" />
            <rect x="14" y="3" width="7" height="7" rx="1" />
            <rect x="3" y="14" width="7" height="7" rx="1" />
            <rect x="14" y="14" width="7" height="7" rx="1" />
          </svg>
          <span class="ui-mono">{{ vendor.product_count || 0 }}</span>
          <span>compounds</span>
        </div>
        <div v-if="vendor.last_tested_label" class="flex items-center gap-1 text-[color:var(--color-ink-muted)]">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M9 2v6l-4 7c-1 1.8 0 4 2 4h10c2 0 3-2.2 2-4l-4-7V2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M8 2h8" stroke-linecap="round"/>
          </svg>
          <span>Tested {{ vendor.last_tested_label }}</span>
        </div>
      </div>
      <span class="text-[color:var(--color-accent-600)] font-medium group-hover:translate-x-0.5 transition-transform duration-[180ms] ease-out flex items-center gap-1">
        View
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
          <path d="M5 12h14M13 5l7 7-7 7"/>
        </svg>
      </span>
    </div>

    <!-- Link overlay -->
    <a
      v-if="vendor.url"
      :href="vendor.url"
      class="absolute inset-0 ui-focus rounded-[14px]"
      :aria-label="`View ${vendor.name}`"
    />
  </Card>
</template>

<script setup>
import { computed } from 'vue'
import Card from './Card.vue'
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
      last_tested_label: 'today' | '3d ago' | null,
      product_thumbs: [url, url, url],
    } */
  },
})

const thumbs = computed(() => (props.vendor.product_thumbs || []).slice(0, 4))
</script>
