<template>
  <div class="relative">
    <!-- Embla viewport -->
    <div ref="emblaRef" class="overflow-hidden rounded-[20px]">
      <div class="flex">
        <div
          v-for="(slide, i) in slides"
          :key="i"
          class="relative flex-[0_0_100%] min-w-0"
        >
          <!-- Each slide is a full-height card -->
          <a
            :href="slide.url"
            class="ui-focus block relative h-[460px] md:h-[520px] rounded-[20px] overflow-hidden group"
            :target="slide.target || '_self'"
            :rel="slide.sponsored ? 'noopener sponsored' : undefined"
          >
            <!-- Background: image OR gradient -->
            <div
              v-if="slide.image"
              class="absolute inset-0 bg-cover bg-center"
              :style="{ backgroundImage: `url(${slide.image})` }"
            />
            <div
              v-else
              class="absolute inset-0"
              :style="{ background: slideGradient(slide, i) }"
            />

            <!-- Dark gradient overlay for text legibility -->
            <div class="absolute inset-0 bg-gradient-to-br from-black/70 via-black/30 to-transparent" />

            <!-- Decorative orbs for text-only slides -->
            <template v-if="!slide.image">
              <div class="absolute -top-24 -right-10 w-[420px] h-[420px] rounded-full bg-[color:var(--color-accent-500)] opacity-20 blur-3xl pointer-events-none" />
              <div class="absolute -bottom-20 -left-20 w-[380px] h-[380px] rounded-full bg-[color:var(--color-biotech-500)] opacity-20 blur-3xl pointer-events-none" />
            </template>

            <!-- Top tag -->
            <div class="absolute top-6 left-6 flex items-center gap-2 z-10">
              <span
                v-if="slide.sponsored"
                class="ui-mono text-[10px] uppercase tracking-[0.12em] px-2 py-1 rounded bg-white/10 backdrop-blur text-white/90 font-semibold"
              >
                Sponsored
              </span>
              <span
                v-else-if="slide.badge"
                class="ui-mono text-[10px] uppercase tracking-[0.12em] px-2 py-1 rounded bg-white/10 backdrop-blur text-[color:var(--color-accent-400)] font-semibold"
              >
                {{ slide.badge }}
              </span>
            </div>

            <!-- Content -->
            <div class="relative h-full flex flex-col justify-end p-10 md:p-14 max-w-3xl">
              <div v-if="slide.eyebrow" class="text-xs uppercase tracking-[0.12em] font-semibold text-white/70 mb-3">
                {{ slide.eyebrow }}
              </div>
              <h1 class="ui-display text-white text-3xl md:text-5xl lg:text-6xl font-semibold tracking-[-0.02em] leading-[1] mb-4">
                {{ slide.title }}
              </h1>
              <p v-if="slide.subtitle" class="text-white/80 text-base md:text-lg leading-relaxed max-w-xl mb-6 line-clamp-2">
                {{ slide.subtitle }}
              </p>
              <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-2 px-5 py-2.5 rounded-[10px] bg-white text-[color:var(--color-ink)] text-sm font-medium group-hover:bg-white/90 transition-all">
                  {{ slide.cta || 'Learn more' }}
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14M13 5l7 7-7 7"/>
                  </svg>
                </span>
              </div>
            </div>
          </a>
        </div>
      </div>
    </div>

    <!-- Arrows -->
    <button
      v-if="slides.length > 1"
      @click="scrollPrev"
      type="button"
      class="ui-focus absolute left-4 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/90 backdrop-blur border border-[color:var(--color-hairline)] shadow-sm flex items-center justify-center text-[color:var(--color-ink)] hover:bg-white transition-colors z-20"
      aria-label="Previous slide"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
        <path d="M15 18l-6-6 6-6"/>
      </svg>
    </button>
    <button
      v-if="slides.length > 1"
      @click="scrollNext"
      type="button"
      class="ui-focus absolute right-4 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/90 backdrop-blur border border-[color:var(--color-hairline)] shadow-sm flex items-center justify-center text-[color:var(--color-ink)] hover:bg-white transition-colors z-20"
      aria-label="Next slide"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
        <path d="M9 6l6 6-6 6"/>
      </svg>
    </button>

    <!-- Dots -->
    <div v-if="slides.length > 1" class="absolute bottom-6 left-1/2 -translate-x-1/2 flex items-center gap-2 z-20">
      <button
        v-for="(_, i) in slides"
        :key="i"
        @click="scrollTo(i)"
        type="button"
        :class="[
          'transition-all duration-[200ms] rounded-full',
          selectedIndex === i
            ? 'w-8 h-1.5 bg-white'
            : 'w-1.5 h-1.5 bg-white/40 hover:bg-white/60',
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
  slides: {
    type: Array,
    required: true,
    /* each slide: {
      title, subtitle?, eyebrow?, cta?, url, image?, badge?, sponsored?,
      gradient?: [from, to], target?
    } */
  },
  autoplay: { type: Boolean, default: true },
  delay: { type: Number, default: 7000 },
})

const [emblaRef, emblaApi] = emblaCarouselVue(
  { loop: true, align: 'start', duration: 28 },
  props.autoplay ? [Autoplay({ delay: props.delay, stopOnInteraction: false, stopOnMouseEnter: true })] : []
)

const selectedIndex = ref(0)

function scrollPrev() { emblaApi.value?.scrollPrev() }
function scrollNext() { emblaApi.value?.scrollNext() }
function scrollTo(i) { emblaApi.value?.scrollTo(i) }

function onSelect() {
  if (!emblaApi.value) return
  selectedIndex.value = emblaApi.value.selectedScrollSnap()
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
