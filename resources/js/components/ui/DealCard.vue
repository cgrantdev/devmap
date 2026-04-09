<template>
  <Card hoverable radius="lg" :padding="false" class="group relative flex flex-col">
    <!-- Top bar: discount badge -->
    <div class="relative px-5 pt-5 pb-3 flex items-start justify-between gap-3">
      <VendorLogo :src="deal.logo" :name="deal.name" size="md" radius="md" />
      <div class="flex-1 min-w-0">
        <h3 class="ui-display text-[15px] font-semibold text-[color:var(--color-ink)] leading-tight truncate">
          {{ deal.name }}
        </h3>
        <div v-if="deal.rating" class="flex items-center gap-1 text-xs text-[color:var(--color-ink-muted)] mt-0.5">
          <svg class="w-3 h-3 text-[color:var(--color-caution)]" viewBox="0 0 20 20" fill="currentColor">
            <path d="M10 1l2.8 5.7 6.2.9-4.5 4.4 1.1 6.3L10 15.3 4.4 18.3l1.1-6.3L1 7.6l6.2-.9L10 1z"/>
          </svg>
          <span class="ui-mono">{{ Number(deal.rating).toFixed(1) }}</span>
          <span v-if="deal.reviews">·</span>
          <span v-if="deal.reviews">{{ deal.reviews }}</span>
        </div>
      </div>
      <div class="flex-shrink-0 ui-display text-2xl font-bold text-[color:var(--color-accent-600)]">
        {{ deal.discount }}%
      </div>
    </div>

    <!-- Coupon code reveal -->
    <div class="px-5 pb-5 mt-auto">
      <button
        type="button"
        @click="copy"
        class="ui-focus w-full flex items-center justify-between gap-2 rounded-[10px] border border-dashed border-[color:var(--color-accent-400)] bg-[color:var(--color-accent-50)] px-4 py-3 hover:border-solid hover:bg-[color:var(--color-accent-100)] transition-all"
      >
        <div class="flex flex-col items-start min-w-0">
          <span class="text-[10px] uppercase tracking-[0.08em] text-[color:var(--color-ink-muted)] font-semibold">
            Use code
          </span>
          <span class="ui-mono text-sm font-semibold text-[color:var(--color-ink)] truncate">
            {{ deal.code }}
          </span>
        </div>
        <span class="text-xs font-medium text-[color:var(--color-accent-600)] flex items-center gap-1 flex-shrink-0">
          <svg v-if="!copied" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <rect x="9" y="9" width="13" height="13" rx="2" />
            <path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1" />
          </svg>
          <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12" />
          </svg>
          {{ copied ? 'Copied' : 'Copy' }}
        </span>
      </button>
      <a
        v-if="deal.url"
        :href="deal.url"
        class="ui-focus mt-2 flex items-center justify-center gap-1 text-xs text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-accent-600)] transition-colors"
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
import { ref } from 'vue'
import Card from './Card.vue'
import VendorLogo from './VendorLogo.vue'

const props = defineProps({
  deal: {
    type: Object,
    required: true,
    /* shape: {
      id, name, logo, rating, reviews,
      discount: int (percentage),
      code: string,
      url: string | null,
    } */
  },
})

const copied = ref(false)

async function copy() {
  try {
    await navigator.clipboard.writeText(props.deal.code)
    copied.value = true
    setTimeout(() => (copied.value = false), 1500)
  } catch (e) {
    // noop — clipboard blocked
  }
}
</script>
