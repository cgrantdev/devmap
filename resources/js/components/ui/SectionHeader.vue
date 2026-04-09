<template>
  <div class="flex items-end justify-between gap-6 flex-wrap mb-10 md:mb-12">
    <div :class="['max-w-2xl', { 'mx-auto text-center': center }]">
      <div
        v-if="eyebrow"
        :class="[
          'text-[11px] uppercase tracking-[0.12em] font-semibold mb-3',
          eyebrowColor,
        ]"
      >
        {{ eyebrow }}
      </div>
      <h2
        class="ui-display text-[28px] md:text-4xl font-semibold tracking-[-0.015em] text-[color:var(--color-ink)] leading-[1.1]"
      >
        <slot name="title">{{ title }}</slot>
      </h2>
      <p
        v-if="description || $slots.description"
        class="mt-3 text-[15px] md:text-base text-[color:var(--color-ink-muted)] leading-relaxed"
      >
        <slot name="description">{{ description }}</slot>
      </p>
    </div>
    <div v-if="$slots.cta" class="flex-shrink-0">
      <slot name="cta" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  eyebrow: { type: String, default: '' },
  title: { type: String, default: '' },
  description: { type: String, default: '' },
  center: { type: Boolean, default: false },
  accent: {
    type: String,
    default: 'accent', // 'accent' | 'biotech' | 'muted' | 'caution'
  },
})

const eyebrowColor = computed(() => ({
  accent: 'text-[color:var(--color-accent-600)]',
  biotech: 'text-[color:var(--color-biotech-600)]',
  muted: 'text-[color:var(--color-ink-muted)]',
  caution: 'text-[color:var(--color-caution)]',
}[props.accent]))
</script>
