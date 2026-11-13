<template>
  <!-- The network sphere from the logo, as a faint decorative watermark
       (same motif as the social cards). Crops the existing logo file to
       its sphere, so there is no extra asset. Position it with classes
       on the component, e.g. class="-right-20 -top-24". -->
  <div aria-hidden="true" class="pointer-events-none absolute select-none" :style="style" />
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  // Height in px; the sphere is about as wide as it is tall.
  size: { type: Number, default: 520 },
  opacity: { type: Number, default: 0.14 },
})

// The sphere fills the left 1.05 x height of logo.png; the wordmark
// starts after that, so a box this wide shows only the sphere.
const style = computed(() => ({
  width: `${Math.round(props.size * 1.05)}px`,
  height: `${props.size}px`,
  backgroundImage: 'url(/images/logo.png)',
  backgroundSize: 'auto 100%',
  backgroundPosition: 'left center',
  backgroundRepeat: 'no-repeat',
  opacity: props.opacity,
}))
</script>
