<template>
  <div
    :class="[
      'relative flex items-center justify-center overflow-hidden',
      'border border-[color:var(--color-hairline)]',
      sizeClasses,
      radiusClasses,
    ]"
  >
    <!-- Real logo if provided -->
    <img
      v-if="src && !broken"
      :src="src"
      :alt="alt"
      class="w-full h-full object-cover"
      loading="lazy"
      @error="broken = true"
    />

    <!-- Fallback: subtle gradient background with initial -->
    <template v-else>
      <div
        class="absolute inset-0"
        :style="{ background: gradient }"
      />
      <div class="absolute inset-0 opacity-[0.04]" :style="{ backgroundImage: 'url(\'data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%224%22 height=%224%22 viewBox=%220 0 4 4%22><circle cx=%221%22 cy=%221%22 r=%220.5%22 fill=%22%23000%22/></svg>\')', backgroundSize: '4px 4px' }" />
      <span
        :class="[
          'relative ui-display font-semibold text-white tracking-tight drop-shadow-sm',
          initialSize,
        ]"
      >
        {{ initial }}
      </span>
    </template>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  src: { type: String, default: null },
  name: { type: String, default: '' },
  alt: { type: String, default: '' },
  size: {
    type: String,
    default: 'md', // 'xs' | 'sm' | 'md' | 'lg' | 'xl'
  },
  radius: {
    type: String,
    default: 'md', // 'md' | 'lg' | 'full'
  },
})

const broken = ref(false)

const initial = computed(() => {
  const n = (props.name || '?').trim()
  if (!n) return '?'
  const parts = n.split(/\s+/)
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase()
  }
  return n.slice(0, 2).toUpperCase()
})

// Deterministic color per vendor name — so "Acme Peptides" always gets the
// same background color, across the whole site. Not random per render.
const gradient = computed(() => {
  const palette = [
    ['#6366F1', '#3B82F6'], // indigo → blue
    ['#8B5CF6', '#6366F1'], // violet → indigo
    ['#0EA5E9', '#6366F1'], // sky → indigo
    ['#14B8A6', '#3B82F6'], // teal → blue
    ['#06B6D4', '#8B5CF6'], // cyan → violet
    ['#4F46E5', '#EC4899'], // indigo → pink
    ['#0F172A', '#334155'], // slate (dark)
    ['#1E3A8A', '#6366F1'], // navy → indigo
  ]
  const n = (props.name || '?').trim()
  let h = 0
  for (let i = 0; i < n.length; i++) h = (h * 31 + n.charCodeAt(i)) & 0xffffffff
  const [a, b] = palette[Math.abs(h) % palette.length]
  return `linear-gradient(135deg, ${a} 0%, ${b} 100%)`
})

const sizeClasses = computed(() => ({
  xs: 'w-8 h-8',
  sm: 'w-10 h-10',
  md: 'w-12 h-12',
  lg: 'w-16 h-16',
  xl: 'w-20 h-20',
}[props.size]))

const initialSize = computed(() => ({
  xs: 'text-xs',
  sm: 'text-sm',
  md: 'text-base',
  lg: 'text-xl',
  xl: 'text-2xl',
}[props.size]))

const radiusClasses = computed(() => ({
  md: 'rounded-[10px]',
  lg: 'rounded-[14px]',
  full: 'rounded-full',
}[props.radius]))
</script>
