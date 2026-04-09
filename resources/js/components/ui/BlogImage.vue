<template>
  <div
    :class="[
      'relative overflow-hidden bg-[color:var(--color-hairline-soft)]',
      radiusClasses,
      aspectClass,
    ]"
  >
    <img
      v-if="src && !broken"
      :src="src"
      :alt="alt"
      class="w-full h-full object-cover transition-transform duration-[400ms] ease-out"
      :class="{ 'group-hover:scale-[1.02]': hover }"
      loading="lazy"
      @error="broken = true"
    />
    <template v-else>
      <!-- Gradient fallback keyed to a deterministic hash of the title -->
      <div class="absolute inset-0" :style="{ background: gradient }" />
      <!-- Faint grid pattern overlay -->
      <div
        class="absolute inset-0 opacity-[0.07]"
        :style="{
          backgroundImage: 'linear-gradient(white 1px, transparent 1px), linear-gradient(90deg, white 1px, transparent 1px)',
          backgroundSize: '24px 24px',
        }"
      />
      <!-- Title text centered -->
      <div class="relative h-full flex items-center justify-center p-6">
        <span class="ui-display text-white/80 font-semibold text-center text-sm md:text-base line-clamp-3 tracking-tight">
          {{ title || alt || 'Research' }}
        </span>
      </div>
      <!-- Bottom-right decorative dot -->
      <div class="absolute bottom-4 right-4 w-1.5 h-1.5 rounded-full bg-white/40" />
    </template>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  src: { type: String, default: null },
  title: { type: String, default: '' },
  alt: { type: String, default: '' },
  aspect: { type: String, default: '16/10' }, // '16/10' | '4/3' | '1/1' | 'square'
  radius: { type: String, default: 'lg' }, // 'md' | 'lg' | 'xl'
  hover: { type: Boolean, default: true },
})

const broken = ref(false)

const gradient = computed(() => {
  const palette = [
    ['#1E293B', '#4F46E5'],
    ['#0F172A', '#6366F1'],
    ['#1E3A8A', '#3B82F6'],
    ['#0C4A6E', '#0EA5E9'],
    ['#134E4A', '#14B8A6'],
    ['#2E1065', '#7C3AED'],
    ['#164E63', '#06B6D4'],
    ['#312E81', '#4F46E5'],
  ]
  const n = (props.title || props.alt || '?').trim()
  let h = 0
  for (let i = 0; i < n.length; i++) h = (h * 31 + n.charCodeAt(i)) & 0xffffffff
  const [a, b] = palette[Math.abs(h) % palette.length]
  return `linear-gradient(135deg, ${a} 0%, ${b} 100%)`
})

const radiusClasses = computed(() => ({
  md: 'rounded-[10px]',
  lg: 'rounded-[14px]',
  xl: 'rounded-[20px]',
}[props.radius]))

const aspectClass = computed(() => ({
  '16/10': 'aspect-[16/10]',
  '4/3': 'aspect-[4/3]',
  '1/1': 'aspect-square',
  'square': 'aspect-square',
  '3/2': 'aspect-[3/2]',
  '2/1': 'aspect-[2/1]',
}[props.aspect] || 'aspect-[16/10]'))
</script>
