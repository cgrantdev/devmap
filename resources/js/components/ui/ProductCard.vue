<template>
  <Card hoverable radius="lg" :padding="false" class="group relative flex flex-col">
    <!-- Image -->
    <div class="aspect-[4/3] bg-[color:var(--color-hairline-soft)] overflow-hidden">
      <img
        v-if="product.image_url"
        :src="product.image_url"
        :alt="product.name"
        class="w-full h-full object-cover group-hover:scale-[1.03] transition-transform duration-[400ms] ease-out"
        loading="lazy"
      />
      <div v-else class="w-full h-full flex items-center justify-center text-[color:var(--color-ink-subtle)]">
        <svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
          <path d="M11 21.73a2 2 0 002 0l7-4A2 2 0 0021 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73z" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>

      <!-- Badges over image -->
      <div class="absolute top-3 left-3 flex flex-wrap gap-1.5">
        <Badge v-if="product.verified" variant="verified" size="xs">Verified</Badge>
        <Badge v-if="product.lab_tested" variant="tested" size="xs">Tested</Badge>
        <Badge v-if="product.featured" variant="featured" size="xs">Featured</Badge>
      </div>
    </div>

    <!-- Body -->
    <div class="flex-1 flex flex-col p-4 gap-3">
      <div class="flex items-start justify-between gap-2">
        <h3 class="ui-display text-[15px] font-semibold text-[color:var(--color-ink)] leading-tight line-clamp-2">
          {{ product.name }}
        </h3>
      </div>

      <div v-if="product.brand_name" class="text-xs text-[color:var(--color-ink-muted)] flex items-center gap-1.5">
        <span>{{ product.brand_name }}</span>
        <VerifiedShield v-if="product.brand_verified" size="xs" :animate="false" />
      </div>

      <!-- Price row -->
      <div class="mt-auto flex items-baseline justify-between gap-2 pt-2 border-t border-[color:var(--color-hairline)]">
        <div class="flex items-baseline gap-1.5">
          <span class="ui-mono text-lg font-semibold text-[color:var(--color-ink)]">
            ${{ displayPrice }}
          </span>
          <span
            v-if="product.original_price"
            class="ui-mono text-xs text-[color:var(--color-ink-subtle)] line-through"
          >
            ${{ product.original_price }}
          </span>
        </div>
        <div v-if="product.price_per_mg" class="ui-mono text-[11px] text-[color:var(--color-ink-muted)]">
          ${{ product.price_per_mg }}/mg
        </div>
      </div>
    </div>

    <!-- Link overlay -->
    <a
      v-if="product.url"
      :href="product.url"
      class="absolute inset-0 ui-focus rounded-[14px]"
      :aria-label="product.name"
    />
  </Card>
</template>

<script setup>
import { computed } from 'vue'
import Card from './Card.vue'
import Badge from './Badge.vue'
import VerifiedShield from './VerifiedShield.vue'

const props = defineProps({
  product: {
    type: Object,
    required: true,
    /* shape: {
      id, name, image_url, url,
      brand_name, brand_verified,
      price, original_price, price_per_mg,
      verified, lab_tested, featured,
    } */
  },
})

const displayPrice = computed(() => {
  const p = props.product.price
  if (p === null || p === undefined || p === '') return '—'
  return typeof p === 'number' ? p.toFixed(2) : p
})
</script>
