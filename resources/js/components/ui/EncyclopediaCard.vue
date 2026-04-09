<template>
  <a
    :href="entry.url"
    class="ui-focus group relative rounded-[14px] border border-[color:var(--color-hairline)] bg-white overflow-hidden ui-lift flex flex-col"
  >
    <!-- Hero image (with gradient fallback) -->
    <BlogImage
      :src="entry.image"
      :title="entry.name"
      :alt="entry.name"
      aspect="16/10"
      radius="lg"
      class="!rounded-none !rounded-t-[14px] border-b border-[color:var(--color-hairline)]"
    />

    <!-- Body -->
    <div class="flex-1 flex flex-col gap-3 p-6">
      <div class="flex items-start justify-between gap-3">
        <h3 class="ui-display font-semibold text-[color:var(--color-ink)] text-lg leading-tight tracking-tight">
          {{ entry.name }}
        </h3>
        <span class="flex-shrink-0 ui-mono text-xs text-[color:var(--color-ink-muted)] whitespace-nowrap">
          {{ entry.products_count }} compounds
        </span>
      </div>
      <p v-if="entry.description" class="text-sm text-[color:var(--color-ink-muted)] leading-relaxed line-clamp-2">
        {{ entry.description }}
      </p>

      <div class="mt-auto pt-3 flex items-center justify-between">
        <span class="inline-flex items-center gap-1 text-xs font-medium text-[color:var(--color-accent-600)] group-hover:translate-x-0.5 transition-transform duration-[180ms]">
          Read article
          <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M5 12h14M13 5l7 7-7 7"/>
          </svg>
        </span>
      </div>
    </div>

    <!-- Sponsor slot (optional) -->
    <div
      v-if="entry.sponsor"
      class="border-t border-[color:var(--color-hairline)] bg-[color:var(--color-hairline-soft)] px-6 py-3 flex items-center gap-3"
    >
      <VendorLogo
        :src="entry.sponsor.logo"
        :name="entry.sponsor.name"
        size="xs"
        radius="md"
      />
      <div class="flex-1 min-w-0">
        <div class="text-[10px] uppercase tracking-[0.1em] font-semibold text-[color:var(--color-ink-subtle)]">
          Sponsored by
        </div>
        <div class="text-xs font-medium text-[color:var(--color-ink)] truncate">
          {{ entry.sponsor.name }}
        </div>
      </div>
    </div>
  </a>
</template>

<script setup>
import BlogImage from './BlogImage.vue'
import VendorLogo from './VendorLogo.vue'

defineProps({
  entry: {
    type: Object,
    required: true,
    /* shape: {
      id, name, description, products_count, image, url,
      sponsor?: { name, logo }
    } */
  },
})
</script>
