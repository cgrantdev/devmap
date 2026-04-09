<template>
  <!-- Split layout: one large featured vendor + up to 2 secondary cards -->
  <div class="grid grid-cols-1 lg:grid-cols-[1.6fr_1fr] gap-6">
    <!-- Hero card: the first premium vendor -->
    <a
      v-if="primary"
      :href="primary.url"
      class="ui-focus relative rounded-[20px] overflow-hidden border border-[color:var(--color-hairline)] bg-gradient-to-br from-[color:var(--color-ink)] to-[color:var(--color-accent-700)] min-h-[360px] group ui-lift"
    >
      <div class="absolute top-4 left-4 z-10 flex gap-2">
        <span class="ui-mono text-[10px] uppercase tracking-[0.1em] px-2 py-1 rounded bg-white/10 backdrop-blur text-[color:var(--color-accent-400)] font-semibold">
          Featured Partner
        </span>
      </div>
      <!-- Decorative -->
      <div class="absolute -top-24 -right-24 w-80 h-80 rounded-full bg-[color:var(--color-accent-500)] opacity-20 blur-3xl pointer-events-none" />
      <div class="absolute -bottom-20 -left-20 w-72 h-72 rounded-full bg-[color:var(--color-biotech-500)] opacity-20 blur-3xl pointer-events-none" />

      <div class="relative h-full p-10 lg:p-12 flex flex-col justify-between text-white">
        <!-- Top: logo + name + verified -->
        <div class="flex items-center gap-4">
          <VendorLogo :src="primary.logo" :name="primary.name" size="xl" radius="lg" />
          <div class="min-w-0">
            <div class="flex items-center gap-2">
              <h3 class="ui-display text-2xl md:text-3xl font-semibold tracking-tight">
                {{ primary.name }}
              </h3>
              <VerifiedShield v-if="primary.verified" size="md" :animate="false" />
            </div>
            <div v-if="primary.rating" class="flex items-center gap-1.5 text-sm text-white/70 mt-1">
              <svg class="w-3.5 h-3.5 text-[color:var(--color-caution)]" viewBox="0 0 20 20" fill="currentColor">
                <path d="M10 1l2.8 5.7 6.2.9-4.5 4.4 1.1 6.3L10 15.3 4.4 18.3l1.1-6.3L1 7.6l6.2-.9L10 1z"/>
              </svg>
              <span class="ui-mono">{{ Number(primary.rating).toFixed(1) }}</span>
              <span v-if="primary.reviews">·</span>
              <span v-if="primary.reviews">{{ primary.reviews }} reviews</span>
            </div>
          </div>
        </div>

        <!-- Bottom: description + CTA -->
        <div>
          <p v-if="primary.description" class="text-white/70 text-base leading-relaxed max-w-md mb-6 line-clamp-2">
            {{ primary.description }}
          </p>
          <div class="flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-5 text-sm text-white/60">
              <div v-if="primary.product_count" class="flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="3" width="7" height="7" rx="1" />
                  <rect x="14" y="3" width="7" height="7" rx="1" />
                  <rect x="3" y="14" width="7" height="7" rx="1" />
                  <rect x="14" y="14" width="7" height="7" rx="1" />
                </svg>
                <span class="ui-mono">{{ primary.product_count }}</span>
                <span>compounds</span>
              </div>
              <div v-if="primary.location" class="flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M12 2a8 8 0 00-8 8c0 5.5 8 12 8 12s8-6.5 8-12a8 8 0 00-8-8z"/>
                  <circle cx="12" cy="10" r="3"/>
                </svg>
                {{ primary.location }}
              </div>
            </div>
            <span class="inline-flex items-center gap-1.5 text-sm font-medium text-white group-hover:translate-x-0.5 transition-transform duration-[180ms]">
              Visit vendor
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14M13 5l7 7-7 7"/>
              </svg>
            </span>
          </div>
        </div>
      </div>
    </a>

    <!-- Secondary: up to 2 stacked partner cards -->
    <div class="grid grid-cols-1 gap-6">
      <a
        v-for="vendor in secondaries"
        :key="vendor.id"
        :href="vendor.url"
        class="ui-focus relative rounded-[20px] overflow-hidden border border-[color:var(--color-hairline)] bg-white p-6 ui-lift group"
      >
        <div class="flex items-center gap-4">
          <VendorLogo :src="vendor.logo" :name="vendor.name" size="lg" radius="md" />
          <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2 mb-1">
              <h3 class="ui-display text-base font-semibold truncate">{{ vendor.name }}</h3>
              <VerifiedShield v-if="vendor.verified" size="xs" :animate="false" />
            </div>
            <div v-if="vendor.rating" class="flex items-center gap-1 text-xs text-[color:var(--color-ink-muted)]">
              <svg class="w-3 h-3 text-[color:var(--color-caution)]" viewBox="0 0 20 20" fill="currentColor">
                <path d="M10 1l2.8 5.7 6.2.9-4.5 4.4 1.1 6.3L10 15.3 4.4 18.3l1.1-6.3L1 7.6l6.2-.9L10 1z"/>
              </svg>
              <span class="ui-mono">{{ Number(vendor.rating).toFixed(1) }}</span>
              <span v-if="vendor.reviews">·</span>
              <span v-if="vendor.reviews">{{ vendor.reviews }} reviews</span>
            </div>
          </div>
          <div class="flex-shrink-0">
            <span class="ui-mono text-[10px] uppercase tracking-[0.08em] px-2 py-1 rounded bg-[color:var(--color-accent-50)] text-[color:var(--color-accent-700)] font-semibold">
              Partner
            </span>
          </div>
        </div>
        <p v-if="vendor.description" class="text-sm text-[color:var(--color-ink-muted)] leading-relaxed mt-4 line-clamp-2">
          {{ vendor.description }}
        </p>
      </a>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import VendorLogo from './VendorLogo.vue'
import VerifiedShield from './VerifiedShield.vue'

const props = defineProps({
  vendors: {
    type: Array,
    required: true,
    /* each vendor: {
      id, name, url, logo, verified,
      rating, reviews, product_count, location, description
    } */
  },
})

const primary = computed(() => props.vendors[0] || null)
const secondaries = computed(() => props.vendors.slice(1, 3))
</script>
