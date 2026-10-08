<template>
  <span :class="['inline-flex items-center justify-center text-[color:var(--color-ink-muted)]', sizeClasses]" :title="label">
    <svg
      viewBox="0 0 24 24"
      fill="none"
      class="w-full h-full"
      aria-hidden="true"
    >
      <circle cx="12" cy="12" r="7.5" stroke="currentColor" stroke-width="1.75" />
      <circle cx="12" cy="12" r="2.25" fill="currentColor" />
    </svg>
    <span v-if="showLabel" class="ml-1.5 text-xs font-medium text-[color:var(--color-ink-muted)]">
      {{ label }}
    </span>
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  size: {
    type: String,
    default: 'md', // 'xs' | 'sm' | 'md' | 'lg'
  },
  label: { type: String, default: 'Listed on Peptidemap' },
  showLabel: { type: Boolean, default: false },
  animate: { type: Boolean, default: false },
})

const sizeClasses = computed(() => ({
  xs: 'w-3.5 h-3.5',
  sm: 'w-4 h-4',
  md: 'w-5 h-5',
  lg: 'w-6 h-6',
}[props.size]))
</script>
