<template>
  <span
    :class="[
      'inline-flex items-center gap-1 font-medium tracking-tight',
      sizeClasses,
      variantClasses,
    ]"
  >
    <slot name="icon">
      <svg
        v-if="variant === 'verified'"
        class="w-3 h-3"
        viewBox="0 0 12 12"
        fill="currentColor"
        aria-hidden="true"
      >
        <path d="M6 0l1.5 1.2 1.9-.3.8 1.8 1.8.8-.3 1.9L12 6l-1.3 1.5.3 1.9-1.8.8-.8 1.8-1.9-.3L6 12l-1.5-1.3-1.9.3-.8-1.8-1.8-.8.3-1.9L0 6l1.3-1.5-.3-1.9 1.8-.8.8-1.8 1.9.3L6 0z"/>
        <path d="M4.8 7.5L3 5.7l.9-.9 1 1 2.3-2.3.9.9L4.8 7.5z" fill="white"/>
      </svg>
      <svg
        v-else-if="variant === 'tested'"
        class="w-3 h-3"
        viewBox="0 0 12 12"
        fill="none"
        stroke="currentColor"
        stroke-width="1.8"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
      >
        <path d="M4.5 1v3.5L2 9.5c-.5 1 .2 2 1.3 2h5.4c1.1 0 1.8-1 1.3-2L7.5 4.5V1"/>
        <path d="M4 1h4"/>
      </svg>
    </slot>
    <slot />
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  variant: {
    type: String,
    default: 'neutral',
    // 'verified' | 'tested' | 'featured' | 'new' | 'caution' | 'neutral'
  },
  size: {
    type: String,
    default: 'sm', // 'xs' | 'sm' | 'md'
  },
})

const sizeClasses = computed(() => ({
  xs: 'text-[10px] leading-none px-1.5 py-1 rounded-[4px]',
  sm: 'text-[11px] leading-none px-2 py-1 rounded-[5px]',
  md: 'text-xs leading-none px-2.5 py-1.5 rounded-[6px]',
}[props.size]))

const variantClasses = computed(() => ({
  verified: 'bg-[color:var(--color-verified-bg)] text-[color:var(--color-verified)]',
  tested: 'bg-[color:var(--color-biotech-50,#eff6ff)] text-[color:var(--color-biotech-600)]',
  featured: 'ui-gradient-bg text-white',
  new: 'bg-[color:var(--color-accent-50)] text-[color:var(--color-accent-700)]',
  caution: 'bg-[color:var(--color-caution-bg)] text-[color:var(--color-caution)]',
  neutral: 'bg-[color:var(--color-hairline-soft)] text-[color:var(--color-ink-muted)]',
}[props.variant]))
</script>
