<template>
  <component
    :is="as"
    :class="[
      'bg-white border border-[color:var(--color-hairline)] overflow-hidden',
      padding && paddingClasses,
      radiusClasses,
      { 'ui-lift cursor-pointer': hoverable },
      { 'shadow-[var(--shadow-sm)]': elevated },
      { 'ring-1 ring-[color:var(--color-accent-500)] ring-offset-2 ring-offset-[color:var(--color-bg)]': featured },
    ]"
  >
    <slot />
  </component>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  as: { type: String, default: 'div' },
  hoverable: { type: Boolean, default: false },
  elevated: { type: Boolean, default: false },
  featured: { type: Boolean, default: false },
  padding: { type: [Boolean, String], default: 'md' }, // false | 'sm' | 'md' | 'lg'
  radius: { type: String, default: 'lg' }, // 'md' | 'lg' | 'xl'
})

const paddingClasses = computed(() => ({
  sm: 'p-4',
  md: 'p-6',
  lg: 'p-8',
}[props.padding === true ? 'md' : props.padding]))

const radiusClasses = computed(() => ({
  md: 'rounded-[10px]',
  lg: 'rounded-[14px]',
  xl: 'rounded-[20px]',
}[props.radius]))
</script>
