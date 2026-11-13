<template>
  <div
    :class="[
      'relative flex items-center justify-center overflow-hidden flex-shrink-0',
      boxClasses,
      radiusClasses,
      bordered ? 'border border-[color:var(--color-hairline)]' : '',
    ]"
    :style="boxStyle"
  >
    <img
      v-if="showImage"
      :src="src"
      :alt="alt || name"
      :class="imgClasses"
      :loading="eager ? 'eager' : 'lazy'"
      :fetchpriority="eager ? 'high' : undefined"
      decoding="async"
      @error="broken = true"
    />

    <!-- Initials fallback when there's no logo or it failed to load -->
    <template v-else>
      <template v-if="fallback === 'gradient'">
        <div class="absolute inset-0" :style="{ background: gradient }" />
        <div class="absolute inset-0 opacity-[0.04]" :style="dotPattern" />
      </template>
      <span
        :class="[
          'relative ui-display font-semibold tracking-tight select-none',
          size.text,
          fallback === 'gradient' ? 'text-white drop-shadow-sm' : 'text-[color:var(--color-ink-subtle)]',
        ]"
      >{{ initial }}</span>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  src: { type: String, default: null },
  name: { type: String, default: '' },
  alt: { type: String, default: '' },
  // 'xxs' | 'xs' | 'sm' | 'md' | 'lg' | 'xl' | 'header' (responsive 48/64px)
  size: { type: String, default: 'md' },
  // 'none' | 'sm' | 'md' | 'lg' | 'full'
  radius: { type: String, default: 'md' },
  // 'square' keeps a fixed box; 'wide' keeps the height and lets the
  // width follow the logo's aspect ratio up to maxWidth.
  shape: { type: String, default: 'square' },
  maxWidth: { type: Number, default: 160 },
  // Fill the parent box instead of using a size preset.
  fill: { type: Boolean, default: false },
  // Explicit backdrop colour. Defaults to the one encoded in a processed
  // logo filename ("...-lp-bg111827.webp"), else white.
  bg: { type: String, default: null },
  bordered: { type: Boolean, default: true },
  // Above-the-fold logos (vendor header) shouldn't lazy-load.
  eager: { type: Boolean, default: false },
  // 'gradient' (coloured tile) | 'subtle' (quiet grey tile)
  fallback: { type: String, default: 'gradient' },
  // Replaces the default <img> sizing/padding classes when set.
  imgClass: { type: String, default: '' },
})

const broken = ref(false)
watch(() => props.src, () => { broken.value = false })

const showImage = computed(() => !!props.src && !broken.value)

const SIZES = {
  xxs: { box: 'w-7 h-7', wide: 'h-7 min-w-7', text: 'text-[10px]', pad: 'p-0.5' },
  xs: { box: 'w-8 h-8', wide: 'h-8 min-w-8', text: 'text-xs', pad: 'p-1' },
  sm: { box: 'w-10 h-10', wide: 'h-10 min-w-10', text: 'text-sm', pad: 'p-1' },
  md: { box: 'w-12 h-12', wide: 'h-12 min-w-12', text: 'text-base', pad: 'p-1.5' },
  lg: { box: 'w-16 h-16', wide: 'h-16 min-w-16', text: 'text-xl', pad: 'p-2' },
  xl: { box: 'w-20 h-20', wide: 'h-20 min-w-20', text: 'text-2xl', pad: 'p-2.5' },
  header: { box: 'w-12 h-12 lg:w-16 lg:h-16', wide: 'h-12 min-w-12 lg:h-16 lg:min-w-16', text: 'text-lg lg:text-xl', pad: 'p-1.5 lg:p-2' },
}
const size = computed(() => SIZES[props.size] || SIZES.md)

const isWide = computed(() => props.shape === 'wide' && showImage.value && !props.fill)

const boxClasses = computed(() => {
  if (props.fill) return 'w-full h-full'
  return isWide.value ? `${size.value.wide} w-auto` : size.value.box
})

const imgClasses = computed(() => {
  if (props.imgClass) return props.imgClass
  if (isWide.value) return `h-full w-auto max-w-full object-contain ${size.value.pad}`
  return `w-full h-full object-contain ${size.value.pad}`
})

const radiusClasses = computed(() => ({
  none: '',
  sm: 'rounded-[6px]',
  md: 'rounded-[10px]',
  lg: 'rounded-[14px]',
  full: 'rounded-full',
}[props.radius] ?? 'rounded-[10px]'))

const backdrop = computed(() => {
  if (props.bg) return props.bg
  const m = /-lp-bg([0-9a-f]{6})\.webp(?:[?#].*)?$/i.exec(props.src || '')
  return m ? `#${m[1].toLowerCase()}` : null
})

const boxStyle = computed(() => {
  const style = {}
  if (isWide.value) style.maxWidth = `${props.maxWidth}px`
  if (showImage.value) style.backgroundColor = backdrop.value || '#ffffff'
  else if (props.fallback !== 'gradient') style.backgroundColor = 'var(--color-bg-soft, #f4f5f7)'
  return style
})

const initial = computed(() => {
  const n = (props.name || '?').trim()
  if (!n) return '?'
  const parts = n.split(/\s+/)
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase()
  }
  return n.slice(0, 2).toUpperCase()
})

// Deterministic colour per vendor name, so one vendor always gets the
// same tile across the site.
const gradient = computed(() => {
  const palette = [
    ['#0EA5E9', '#3B82F6'],
    ['#8B5CF6', '#0EA5E9'],
    ['#0EA5E9', '#0EA5E9'],
    ['#14B8A6', '#3B82F6'],
    ['#06B6D4', '#8B5CF6'],
    ['#0284C7', '#EC4899'],
    ['#0F172A', '#334155'],
    ['#1E3A8A', '#0EA5E9'],
  ]
  const n = (props.name || '?').trim()
  let h = 0
  for (let i = 0; i < n.length; i++) h = (h * 31 + n.charCodeAt(i)) & 0xffffffff
  const [a, b] = palette[Math.abs(h) % palette.length]
  return `linear-gradient(135deg, ${a} 0%, ${b} 100%)`
})

const dotPattern = {
  backgroundImage: "url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%224%22 height=%224%22 viewBox=%220 0 4 4%22><circle cx=%221%22 cy=%221%22 r=%220.5%22 fill=%22%23000%22/></svg>')",
  backgroundSize: '4px 4px',
}
</script>
