<template>
  <component
    :is="as"
    :class="[
      'ui-focus relative inline-flex items-center justify-center gap-2 whitespace-nowrap font-semibold',
      'transition-all duration-[180ms] ease-out',
      'disabled:opacity-50 disabled:cursor-not-allowed disabled:pointer-events-none',
      'tracking-[-0.005em]',
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
    <span v-if="$slots.default">
      <slot />
    </span>
    <slot name="icon-right" />
  </component>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  as: { type: String, default: 'button' },
  href: { type: String, default: undefined },
  type: { type: String, default: 'button' },
  disabled: { type: Boolean, default: false },
  block: { type: Boolean, default: false },
  variant: {
    type: String,
    default: 'primary', // 'primary' | 'secondary' | 'ghost' | 'link' | 'dark'
  },
  size: {
    type: String,
    default: 'md', // 'sm' | 'md' | 'lg'
  },
})

defineEmits(['click'])

const sizeClasses = computed(() => ({
  sm: 'h-9 px-4 text-[13px] rounded-[9px]',
  md: 'h-11 px-5 text-[14px] rounded-[11px]',
  lg: 'h-[54px] px-7 text-[15px] rounded-[13px]',
}[props.size]))

const variantClasses = computed(() => ({
  primary:
    // Layered gradient + inset highlight + soft glow shadow for a premium feel
    'text-white bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] ' +
    'shadow-[inset_0_1px_0_rgba(255,255,255,0.18),0_1px_2px_rgba(10,11,14,0.08),0_10px_24px_-8px_rgba(79,70,229,0.45)] ' +
    'hover:shadow-[inset_0_1px_0_rgba(255,255,255,0.22),0_2px_4px_rgba(10,11,14,0.1),0_14px_32px_-8px_rgba(79,70,229,0.55)] ' +
    'hover:-translate-y-[1px] active:translate-y-0',
  secondary:
    'text-[color:var(--color-ink)] bg-white border border-[color:var(--color-hairline)] ' +
    'shadow-[0_1px_2px_rgba(10,11,14,0.04)] ' +
    'hover:border-[color:var(--color-ink-subtle)] hover:shadow-[0_1px_2px_rgba(10,11,14,0.06),0_6px_16px_-10px_rgba(10,11,14,0.25)]',
  ghost:
    'text-[color:var(--color-ink-muted)] bg-transparent ' +
    'hover:text-[color:var(--color-ink)] hover:bg-[color:var(--color-hairline-soft)]',
  link:
    'text-[color:var(--color-accent-600)] bg-transparent px-0 h-auto ' +
    'hover:text-[color:var(--color-accent-700)] underline-offset-[5px] hover:underline',
  dark:
    'text-white bg-[color:var(--color-ink)] border border-white/10 ' +
    'shadow-[inset_0_1px_0_rgba(255,255,255,0.08),0_2px_4px_rgba(0,0,0,0.3)] ' +
    'hover:bg-black hover:-translate-y-[1px]',
}[props.variant]))
</script>
