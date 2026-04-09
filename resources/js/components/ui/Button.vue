<template>
  <component
    :is="as"
    :class="[
      'ui-focus inline-flex items-center justify-center gap-2 font-medium whitespace-nowrap',
      'transition-all duration-[180ms] ease-out',
      'disabled:opacity-50 disabled:cursor-not-allowed disabled:pointer-events-none',
      sizeClasses,
      variantClasses,
      { 'w-full': block },
    ]"
    :href="as === 'a' ? href : undefined"
    :type="as === 'button' ? type : undefined"
    :disabled="as === 'button' ? disabled : undefined"
    @click="$emit('click', $event)"
  >
    <slot name="icon-left" />
    <span v-if="$slots.default" class="ui-display">
      <slot />
    </span>
    <slot name="icon-right" />
  </component>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  as: { type: String, default: 'button' }, // 'button' | 'a' | custom tag
  href: { type: String, default: undefined },
  type: { type: String, default: 'button' },
  disabled: { type: Boolean, default: false },
  block: { type: Boolean, default: false },
  variant: {
    type: String,
    default: 'primary', // 'primary' | 'secondary' | 'ghost' | 'link'
    validator: (v) => ['primary', 'secondary', 'ghost', 'link'].includes(v),
  },
  size: {
    type: String,
    default: 'md', // 'sm' | 'md' | 'lg'
    validator: (v) => ['sm', 'md', 'lg'].includes(v),
  },
})

defineEmits(['click'])

const sizeClasses = computed(() => ({
  sm: 'h-9 px-3.5 text-sm rounded-[8px]',
  md: 'h-11 px-5 text-[15px] rounded-[10px]',
  lg: 'h-[52px] px-7 text-base rounded-[12px]',
}[props.size]))

const variantClasses = computed(() => ({
  primary:
    'ui-gradient-bg text-white shadow-[0_1px_2px_rgba(10,11,14,0.08),0_8px_24px_-12px_rgba(79,70,229,0.45)] hover:shadow-[0_2px_4px_rgba(10,11,14,0.1),0_12px_32px_-12px_rgba(79,70,229,0.6)] hover:-translate-y-[1px]',
  secondary:
    'bg-white text-[color:var(--color-ink)] border border-[color:var(--color-hairline)] hover:border-[color:var(--color-accent-400)] hover:bg-[color:var(--color-hairline-soft)]',
  ghost:
    'bg-transparent text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)] hover:bg-[color:var(--color-hairline-soft)]',
  link:
    'bg-transparent text-[color:var(--color-accent-600)] hover:text-[color:var(--color-accent-700)] underline-offset-4 hover:underline px-0 h-auto',
}[props.variant]))
</script>
