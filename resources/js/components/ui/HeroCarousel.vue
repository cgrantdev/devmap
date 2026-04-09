<template>
  <div
    class="relative rounded-[24px] overflow-hidden border border-[color:var(--color-hairline)] bg-black select-none"
    @mouseenter="pause"
    @mouseleave="resume"
  >
    <!-- Embla viewport -->
    <div ref="emblaRef" class="overflow-hidden cursor-grab active:cursor-grabbing">
      <div class="flex">
        <div
          v-for="(slide, i) in slides"
          :key="i"
          class="relative flex-[0_0_100%] min-w-0 h-[460px] md:h-[540px] lg:h-[580px]"
        >
          <!-- Background (image or gradient) -->
          <div
            v-if="slide.image"
            class="absolute inset-0 bg-cover bg-center pointer-events-none"
            :style="{ backgroundImage: `url(${slide.image})` }"
          />
          <div
            v-else
            class="absolute inset-0 pointer-events-none"
            :style="{ background: slideGradient(slide, i) }"
          />

          <!-- Overlays for legibility -->
          <div class="absolute inset-0 bg-gradient-to-br from-black/70 via-black/25 to-transparent pointer-events-none" />
          <div class="absolute inset-x-0 bottom-0 h-48 bg-gradient-to-t from-black/60 to-transparent pointer-events-none" />

          <!-- Decorative orbs for text-only slides -->
          <template v-if="!slide.image">
            <div class="absolute top-1/4 right-[8%] w-[460px] h-[460px] rounded-full bg-[color:var(--color-accent-500)] opacity-[0.18] blur-[120px] pointer-events-none" />
            <div class="absolute bottom-1/4 left-[10%] w-[400px] h-[400px] rounded-full bg-[color:var(--color-biotech-500)] opacity-[0.18] blur-[120px] pointer-events-none" />
            <div
              class="absolute inset-0 opacity-[0.035] pointer-events-none"
              :style="{
                backgroundImage: 'linear-gradient(white 1px, transparent 1px), linear-gradient(90deg, white 1px, transparent 1px)',
                backgroundSize: '32px 32px',
              }"
            />
          </template>

          <!-- Top chip -->
          <div class="absolute top-6 left-6 md:top-8 md:left-8 z-10 flex items-center gap-2 pointer-events-none">
            <span
              v-if="slide.sponsored"
              class="ui-mono text-[10px] uppercase tracking-[0.14em] px-2.5 py-1 rounded-full bg-white/10 backdrop-blur-md text-white/85 font-semibold border border-white/10"
            >
              Sponsored
            </span>
            <span
              v-else-if="slide.badge"
              class="ui-mono text-[10px] uppercase tracking-[0.14em] px-2.5 py-1 rounded-full bg-white/10 backdrop-blur-md text-[color:var(--color-accent-400)] font-semibold border border-white/10"
            >
              {{ slide.badge }}
            </span>
          </div>

          <!-- Content -->
          <div class="relative h-full flex items-center px-8 md:px-14 lg:px-20 pointer-events-none">
            <div class="max-w-2xl">
              <div
                v-if="slide.eyebrow"
                class="text-xs md:text-[13px] uppercase tracking-[0.14em] font-semibold text-white/60 mb-4"
              >
                {{ slide.eyebrow }}
              </div>
              <h1 class="ui-display text-white text-4xl md:text-5xl lg:text-[64px] font-semibold tracking-[-0.025em] leading-[0.98]">
                {{ slide.title }}
              </h1>
              <p
                v-if="slide.subtitle"
                class="mt-5 text-white/70 text-base md:text-lg leading-relaxed max-w-xl line-clamp-3"
              >
                {{ slide.subtitle }}
              </p>
              <div class="mt-8 pointer-events-auto">
                <a
                  :href="slide.url"
                  :target="slide.target || '_self'"
                  :rel="slide.sponsored ? 'noopener sponsored' : undefined"
                  class="ui-focus inline-flex items-center gap-2 h-12 px-6 rounded-[12px] bg-white text-[color:var(--color-ink)] text-[15px] font-semibold hover:bg-white/95 hover:-translate-y-[1px] transition-all shadow-lg"
                >
                  {{ slide.cta || 'Learn more' }}
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14M13 5l7 7-7 7"/>
                  </svg>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Dots only -->
    <div v-if="slides.length > 1" class="absolute bottom-6 md:bottom-8 left-1/2 -translate-x-1/2 flex items-center gap-2 z-20">
      <button
        v-for="(_, i) in slides"
        :key="i"
        @click="scrollTo(i)"
        type="button"
        :class="[
          'transition-all duration-[300ms] ease-out rounded-full',
          selectedIndex === i
            ? 'w-8 h-[5px] bg-white'
            : 'w-[5px] h-[5px] bg-white/35 hover:bg-white/60',
        ]"
        :aria-label="`Go to slide ${i + 1}`"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import emblaCarouselVue from 'embla-carousel-vue'
import Autoplay from 'embla-carousel-autoplay'

const props = defineProps({
  slides: { type: Array, required: true },
  autoplay: { type: Boolean, default: true },
  delay: { type: Number, default: 7000 },
})

const autoplayPlugin = Autoplay({
  delay: props.delay,
  stopOnInteraction: false,
  stopOnMouseEnter: true,
  playOnInit: props.autoplay,
})

const [emblaRef, emblaApi] = emblaCarouselVue(
  {
    loop: true,
    align: 'center',
    duration: 32, // smoother than default
    dragThreshold: 10,
    skipSnaps: false,
    containScroll: 'trimSnaps',
    watchDrag: true,
  },
  props.autoplay ? [autoplayPlugin] : []
)

const selectedIndex = ref(0)

function scrollTo(i) { emblaApi.value?.scrollTo(i) }

function onSelect() {
  if (!emblaApi.value) return
  selectedIndex.value = emblaApi.value.selectedScrollSnap()
}

function pause() {
  if (emblaApi.value && props.autoplay) {
    const ap = emblaApi.value.plugins()?.autoplay
    ap?.stop()
  }
}

function resume() {
  if (emblaApi.value && props.autoplay) {
    const ap = emblaApi.value.plugins()?.autoplay
    ap?.play()
  }
}

onMounted(() => {
  if (emblaApi.value) {
    emblaApi.value.on('select', onSelect)
    emblaApi.value.on('reInit', onSelect)
    onSelect()
  }
})

onUnmounted(() => {
  if (emblaApi.value) {
    emblaApi.value.off('select', onSelect)
  }
})

function slideGradient(slide, i) {
  if (slide.gradient) return `linear-gradient(135deg, ${slide.gradient[0]} 0%, ${slide.gradient[1]} 100%)`
  const palette = [
    ['#0A0B0E', '#4F46E5'],
    ['#0F172A', '#6366F1'],
    ['#1E1B4B', '#3B82F6'],
    ['#111827', '#7C3AED'],
    ['#0C4A6E', '#0EA5E9'],
  ]
  return `linear-gradient(135deg, ${palette[i % palette.length][0]} 0%, ${palette[i % palette.length][1]} 100%)`
}
</script>
