<template>
  <section class="grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-10">
    <div
      v-for="(item, i) in items"
      :key="i"
      class="flex gap-4 items-start group"
    >
      <div
        class="flex-shrink-0 w-11 h-11 rounded-[10px] bg-[color:var(--color-accent-50)] flex items-center justify-center text-[color:var(--color-accent-600)] group-hover:scale-[1.03] transition-transform duration-[180ms] ease-out"
      >
        <!-- Icon via slot or inline SVG based on `icon` prop -->
        <slot :name="`icon-${i}`">
          <svg
            v-if="item.icon === 'shield'"
            class="w-5 h-5"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            viewBox="0 0 24 24"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
            <path d="M9 12l2 2 4-4" />
          </svg>
          <svg
            v-else-if="item.icon === 'eye'"
            class="w-5 h-5"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            viewBox="0 0 24 24"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z" />
            <circle cx="12" cy="12" r="3" />
          </svg>
          <svg
            v-else-if="item.icon === 'grid'"
            class="w-5 h-5"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            viewBox="0 0 24 24"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <rect x="3" y="3" width="7" height="7" rx="1" />
            <rect x="14" y="3" width="7" height="7" rx="1" />
            <rect x="3" y="14" width="7" height="7" rx="1" />
            <rect x="14" y="14" width="7" height="7" rx="1" />
          </svg>
          <svg
            v-else
            class="w-5 h-5"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            viewBox="0 0 24 24"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <polyline points="20 6 9 17 4 12" />
          </svg>
        </slot>
      </div>
      <div class="flex-1">
        <h3 class="ui-display text-lg font-semibold text-[color:var(--color-ink)] mb-1 tracking-tight">
          {{ item.title }}
        </h3>
        <p class="text-sm text-[color:var(--color-ink-muted)] leading-relaxed">
          {{ item.body }}
        </p>
      </div>
    </div>
  </section>
</template>

<script setup>
defineProps({
  items: {
    type: Array,
    required: true,
    // [{ icon: 'shield' | 'eye' | 'grid', title, body }, ...]
  },
})
</script>
