<template>
  <div :class="['flex flex-col', centered ? 'items-center text-center gap-2' : 'gap-1.5']">
    <!-- Label goes ABOVE when default, BELOW when centered -->
    <div
      v-if="label && !centered"
      class="text-xs uppercase tracking-[0.08em] text-[color:var(--color-ink-subtle)] font-medium"
    >
      {{ label }}
    </div>

    <div :class="['flex', centered ? 'items-baseline justify-center' : 'items-baseline', 'gap-2']">
      <span
        :class="[
          'ui-display font-semibold text-[color:var(--color-ink)] tracking-[-0.02em] leading-none',
          sizeClasses,
        ]"
      >
        <slot>{{ value }}</slot>
      </span>
      <span
        v-if="delta !== null && delta !== undefined"
        :class="[
          'ui-mono text-xs font-medium',
          deltaPositive
            ? 'text-[color:var(--color-verified)]'
            : 'text-[color:var(--color-danger)]',
        ]"
      >
        {{ deltaPositive ? '↑' : '↓' }} {{ formattedDelta }}
      </span>
    </div>

    <div
      v-if="label && centered"
      class="text-[11px] md:text-xs uppercase tracking-[0.12em] text-[color:var(--color-ink-subtle)] font-semibold"
    >
      {{ label }}
    </div>

    <div v-if="hint" class="text-sm text-[color:var(--color-ink-muted)]">
      {{ hint }}
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  label: { type: String, default: '' },
  value: { type: [String, Number], default: '' },
  delta: { type: [String, Number, null], default: null },
  hint: { type: String, default: '' },
  size: {
    type: String,
    default: 'md', // 'sm' | 'md' | 'lg' | 'xl'
  },
  centered: { type: Boolean, default: false },
})

const sizeClasses = computed(() => ({
  sm: 'text-xl',
  md: 'text-3xl',
  lg: 'text-4xl md:text-[44px]',
  xl: 'text-5xl md:text-6xl',
}[props.size]))

const deltaPositive = computed(() => {
  if (typeof props.delta === 'string') return !props.delta.startsWith('-')
  return Number(props.delta) >= 0
})

const formattedDelta = computed(() => {
  if (typeof props.delta === 'string') return props.delta.replace(/^[+-]/, '')
  return `${Math.abs(Number(props.delta))}%`
})
</script>
