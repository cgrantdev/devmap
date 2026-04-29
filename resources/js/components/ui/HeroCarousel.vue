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
          class="relative flex-[0_0_100%] min-w-0 h-[360px] md:h-[420px] lg:h-[460px]"
        >
          <!-- Background — mobile image (portrait), desktop image (landscape), or gradient -->
          <div
            v-if="slide.image_mobile"
            class="md:hidden absolute inset-0 bg-cover bg-center pointer-events-none"
            :style="{ backgroundImage: `url(${slide.image_mobile})` }"
          />
          <div
            v-if="slide.image"
            :class="[slide.image_mobile ? 'hidden md:block' : '', 'absolute inset-0 bg-cover bg-center pointer-events-none']"
            :style="{ backgroundImage: `url(${slide.image})` }"
          />
          <div
            v-if="!slide.image && !slide.image_mobile"
            class="absolute inset-0 pointer-events-none"
            :style="{ background: slideGradient(slide, i) }"
          />

          <!-- Overlays for legibility — softer than before so the product art breathes -->
          <div class="absolute inset-0 bg-gradient-to-b md:bg-gradient-to-r from-black/60 via-black/30 to-transparent md:from-black/55 md:via-black/15 pointer-events-none" />

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

          <!-- Top chip (badge only, no sponsored chip) -->
          <div
            v-if="slide.badge"
            class="absolute top-6 left-6 md:top-8 md:left-8 z-10 flex items-center gap-2 pointer-events-none"
          >
            <span
              class="ui-mono text-[10px] uppercase tracking-[0.14em] px-2.5 py-1 rounded-full bg-white/10 backdrop-blur-md text-[color:var(--color-accent-400)] font-semibold border border-white/10"
            >
              {{ slide.badge }}
            </span>
          </div>

          <!-- Mobile content — top-aligned, vendor name only, center-justified -->
          <div class="md:hidden relative h-full flex flex-col items-center text-center px-6 pt-7 pointer-events-none">
            <div
              v-if="slide.eyebrow"
              class="text-[10px] uppercase tracking-[0.14em] font-semibold text-white/60 mb-2"
            >
              {{ slide.eyebrow }}
            </div>
            <h1 class="ui-display text-white text-[26px] font-semibold tracking-[-0.02em] leading-[1.05]">
              {{ slide.title_highlight || slide.title }}
            </h1>
            <div class="mt-5 flex flex-wrap items-center justify-center gap-2 pointer-events-auto">
              <a
                :href="slide.url"
                :target="slide.target || '_self'"
                :rel="slide.sponsored ? 'noopener sponsored' : undefined"
                class="ui-focus inline-flex items-center gap-1.5 h-10 px-4 rounded-[10px] bg-white text-[color:var(--color-ink)] text-[13px] font-semibold hover:bg-white/95 transition-all shadow-md"
              >
                {{ slide.cta || 'Learn more' }}
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
              </a>
              <button
                v-if="slide.coupon_code"
                type="button"
                @click="copyCoupon(slide.coupon_code, i)"
                class="ui-focus inline-flex items-center gap-2 h-10 pl-2.5 pr-3 rounded-[10px] bg-gradient-to-b from-emerald-400/20 to-emerald-500/15 border border-emerald-300/40 text-white hover:from-emerald-400/30 hover:to-emerald-500/20 transition-colors shadow-sm"
                :title="`Tap to copy ${slide.coupon_code}`"
              >
                <span class="inline-flex items-center justify-center w-5 h-5 rounded bg-emerald-300/20">
                  <svg v-if="copiedIndex !== i" class="w-3 h-3 text-emerald-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                  <svg v-else class="w-3 h-3 text-emerald-200" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                </span>
                <span class="ui-mono font-bold tracking-wide text-[13px] text-emerald-50 uppercase">{{ slide.coupon_code }}</span>
              </button>
            </div>
          </div>

          <!-- Desktop content — vertically centered, capped at ~60% width so the
               product photography on the right stays unobstructed -->
          <div class="hidden md:flex relative h-full items-center px-14 lg:px-20 pointer-events-none">
            <div class="w-[60%] max-w-[640px]">
              <div
                v-if="slide.eyebrow"
                class="text-[12px] uppercase tracking-[0.14em] font-semibold text-white/60 mb-3"
              >
                {{ slide.eyebrow }}
              </div>

              <!-- Title with optional highlighted vendor span -->
              <h1 class="ui-display text-3xl lg:text-4xl font-semibold tracking-[-0.02em] leading-[1.1]" style="color: #ffffff;">
                <template v-if="slide.title_highlight && slide.title.includes(slide.title_highlight)">
                  <span style="color: #ffffff;">{{ slide.title.split(slide.title_highlight)[0] }}</span><span style="color: var(--color-accent-300, #a5b4fc);">{{ slide.title_highlight }}</span><span style="color: #ffffff;">{{ slide.title.split(slide.title_highlight).slice(1).join(slide.title_highlight) }}</span>
                </template>
                <template v-else><span style="color: #ffffff;">{{ slide.title }}</span></template>
              </h1>

              <p
                v-if="slide.subtitle"
                class="mt-4 text-white/75 text-[15px] leading-relaxed line-clamp-3"
              >
                {{ slide.subtitle }}
              </p>

              <!-- Action row -->
              <div class="mt-8 flex flex-wrap items-center gap-3 pointer-events-auto">
                <a
                  :href="slide.url"
                  :target="slide.target || '_self'"
                  :rel="slide.sponsored ? 'noopener sponsored' : undefined"
                  class="ui-focus inline-flex items-center gap-2 h-12 px-6 rounded-[12px] bg-white text-[color:var(--color-ink)] text-[15px] font-semibold hover:bg-white/95 hover:-translate-y-[1px] transition-all shadow-lg"
                >
                  {{ slide.cta || 'Learn more' }}
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
                </a>

                <!-- Coupon-code pill (refined: green-tint deal vibe, larger code, copy confirmation) -->
                <button
                  v-if="slide.coupon_code"
                  type="button"
                  @click="copyCoupon(slide.coupon_code, i)"
                  class="ui-focus inline-flex items-center gap-3 h-12 pl-3 pr-5 rounded-[12px] bg-gradient-to-b from-emerald-400/20 to-emerald-500/15 border border-emerald-300/40 text-white hover:from-emerald-400/30 hover:to-emerald-500/20 transition-colors shadow-sm"
                  :title="`Click to copy ${slide.coupon_code}`"
                >
                  <span class="inline-flex items-center justify-center w-7 h-7 rounded-md bg-emerald-300/20">
                    <svg v-if="copiedIndex !== i" class="w-3.5 h-3.5 text-emerald-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                    <svg v-else class="w-3.5 h-3.5 text-emerald-200" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                  </span>
                  <div class="flex flex-col items-start leading-tight">
                    <span class="text-emerald-100/70 text-[10px] uppercase tracking-[0.1em] font-semibold">Code</span>
                    <span class="ui-mono font-bold tracking-wide text-[15px] text-emerald-50 uppercase">{{ slide.coupon_code }}</span>
                  </div>
                </button>
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

// Coupon-code copy state — tracks which slide just copied so we can show a
// briefly checkmarked confirmation.
const copiedIndex = ref(null)
async function copyCoupon(code, slideIndex) {
  try {
    await navigator.clipboard.writeText(code)
  } catch {
    // Fallback for older browsers / blocked clipboard
    const ta = document.createElement('textarea')
    ta.value = code
    document.body.appendChild(ta)
    ta.select()
    try { document.execCommand('copy') } catch {}
    document.body.removeChild(ta)
  }
  copiedIndex.value = slideIndex
  setTimeout(() => {
    if (copiedIndex.value === slideIndex) copiedIndex.value = null
  }, 1800)
}

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
