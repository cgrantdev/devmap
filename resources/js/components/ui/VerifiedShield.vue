<template>
  <span :class="['inline-flex items-center justify-center', sizeClasses]" :title="label">
    <span :class="['relative inline-flex', { 'ui-breathe': animate }]">
      <svg
        viewBox="0 0 24 24"
        fill="none"
        class="w-full h-full"
        aria-hidden="true"
      >
        <defs>
          <linearGradient :id="gradId" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="var(--color-accent-500)" />
            <stop offset="100%" stop-color="var(--color-biotech-500)" />
          </linearGradient>
        </defs>
        <path
          d="M12 2L4 5v6c0 5 3.4 9.7 8 11 4.6-1.3 8-6 8-11V5l-8-3z"
          :fill="`url(#${gradId})`"
        />
        <path
          d="M9.5 12.5l2 2 4-4.5"
          stroke="white"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
          fill="none"
        />
      </svg>
    </span>
    <span v-if="showLabel" class="ml-1.5 text-xs font-medium text-[color:var(--color-verified)]">
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
  label: { type: String, default: 'Verified' },
  showLabel: { type: Boolean, default: false },
  animate: { type: Boolean, default: true },
})

// Unique gradient id so multiple instances on one page don't collide
const gradId = `ui-shield-grad-${Math.random().toString(36).slice(2, 9)}`

const sizeClasses = computed(() => ({
  xs: 'w-3.5 h-3.5',
  sm: 'w-4 h-4',
  md: 'w-5 h-5',
  lg: 'w-6 h-6',
}[props.size]))
</script>
