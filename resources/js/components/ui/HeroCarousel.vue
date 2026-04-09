<template>
  <div
    class="relative rounded-[24px] overflow-hidden border border-[color:var(--color-hairline)] bg-black"
    @mouseenter="paused = true"
    @mouseleave="paused = false"
  >
    <!-- Slides: all stacked, only active is visible (crossfade) -->
    <div class="relative h-[460px] md:h-[540px] lg:h-[580px]">
      <div
        v-for="(slide, i) in slides"
        :key="i"
        :class="[
          'absolute inset-0 transition-opacity duration-[900ms] ease-out',
          activeIndex === i ? 'opacity-100 z-10' : 'opacity-0 z-0',
        ]"
      >
        <a
          :href="slide.url"
          :target="slide.target || '_self'"
          :rel="slide.sponsored ? 'noopener sponsored' : undefined"
          class="ui-focus block relative w-full h-full group"
        >
          <!-- Background -->
          <div
            v-if="slide.image"
            class="absolute inset-0 bg-cover bg-center will-change-transform"
            :class="activeIndex === i ? 'scale-[1.03]' : 'scale-100'"
            :style="{ backgroundImage: `url(${slide.image})`, transition: 'transform 8000ms linear' }"
          />
          <div
            v-else
            class="absolute inset-0"
            :style="{ background: slideGradient(slide, i) }"
          />

          <!-- Softening vignette / gradient overlay -->
          <div class="absolute inset-0 bg-gradient-to-br from-black/65 via-black/25 to-transparent" />
          <div class="absolute inset-x-0 bottom-0 h-40 bg-gradient-to-t from-black/60 to-transparent" />

          <!-- Decorative orbs for text-only slides -->
          <template v-if="!slide.image">
            <div class="absolute top-1/4 right-[8%] w-[460px] h-[460px] rounded-full bg-[color:var(--color-accent-500)] opacity-[0.18] blur-[120px] pointer-events-none" />
            <div class="absolute bottom-1/4 left-[10%] w-[400px] h-[400px] rounded-full bg-[color:var(--color-biotech-500)] opacity-[0.18] blur-[120px] pointer-events-none" />
          </template>

          <!-- Subtle grid texture over dark backgrounds -->
          <div
            v-if="!slide.image"
            class="absolute inset-0 opacity-[0.04] pointer-events-none"
            :style="{
              backgroundImage: 'linear-gradient(white 1px, transparent 1px), linear-gradient(90deg, white 1px, transparent 1px)',
              backgroundSize: '32px 32px',
            }"
          />

          <!-- Top-left chip -->
          <div class="absolute top-6 left-6 md:top-8 md:left-8 z-10 flex items-center gap-2">
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

          <!-- Content — vertically + horizontally balanced -->
          <div class="relative h-full flex items-center px-8 md:px-14 lg:px-20">
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
              <div class="mt-8 flex items-center gap-3">
                <span class="inline-flex items-center gap-2 h-12 px-6 rounded-[12px] bg-white text-[color:var(--color-ink)] text-[15px] font-semibold group-hover:bg-white/95 transition-all shadow-lg">
                  {{ slide.cta || 'Learn more' }}
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12h14M13 5l7 7-7 7"/>
                  </svg>
                </span>
              </div>
            </div>
          </div>
        </a>
      </div>
    </div>

    <!-- Dots only — no arrows -->
    <div v-if="slides.length > 1" class="absolute bottom-6 md:bottom-8 left-1/2 -translate-x-1/2 flex items-center gap-2 z-20">
      <button
        v-for="(_, i) in slides"
        :key="i"
        @click="scrollTo(i)"
        type="button"
        :class="[
          'transition-all duration-[300ms] ease-out rounded-full',
          activeIndex === i
            ? 'w-8 h-[5px] bg-white'
            : 'w-[5px] h-[5px] bg-white/35 hover:bg-white/60',
        ]"
        :aria-label="`Go to slide ${i + 1}`"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'

const props = defineProps({
  slides: { type: Array, required: true },
  autoplay: { type: Boolean, default: true },
  delay: { type: Number, default: 7000 },
})

const activeIndex = ref(0)
const paused = ref(false)
let timer = null

function scrollTo(i) {
  activeIndex.value = i
  restart()
}

function next() {
  if (props.slides.length <= 1) return
  activeIndex.value = (activeIndex.value + 1) % props.slides.length
}

function start() {
  if (!props.autoplay) return
  stop()
  timer = setInterval(() => {
    if (!paused.value) next()
  }, props.delay)
}

function stop() {
  if (timer) { clearInterval(timer); timer = null }
}

function restart() {
  stop(); start()
}

onMounted(start)
onUnmounted(stop)
watch(() => props.slides.length, start)

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
