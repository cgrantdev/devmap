<template>
  <!-- Real ad from API -->
  <a
    v-if="liveBanner"
    :href="liveBanner.link"
    target="_blank"
    rel="noopener sponsored"
    class="ui-focus block relative w-full overflow-hidden border border-[color:var(--color-hairline)] group"
    @click="trackClick"
  >
    <div class="relative aspect-[7/1] md:aspect-[8/1]">
      <img
        v-if="liveBanner.image"
        :src="liveBanner.image"
        :alt="liveBanner.title || 'Sponsored'"
        class="absolute inset-0 w-full h-full object-cover group-hover:scale-[1.01] transition-transform duration-[300ms]"
        loading="lazy"
      />
      <div class="absolute inset-0 bg-gradient-to-r from-black/60 via-black/25 to-transparent" />
      <div class="absolute top-3 left-4 z-10">
        <span class="ui-mono text-[9px] uppercase tracking-[0.14em] px-1.5 py-0.5 bg-black/40 backdrop-blur text-white/85 font-semibold border border-white/10">
          Sponsored
        </span>
      </div>
      <div class="absolute inset-y-0 left-4 md:left-6 flex flex-col justify-center max-w-lg z-10">
        <h3 v-if="liveBanner.title" class="ui-display text-white font-semibold text-base md:text-xl leading-tight mb-0.5 line-clamp-1">
          {{ liveBanner.title }}
        </h3>
        <p v-if="liveBanner.description" class="text-white/70 text-xs md:text-sm leading-snug line-clamp-1">
          {{ liveBanner.description }}
        </p>
      </div>
    </div>
  </a>

  <!-- Placeholder when no live banner available -->
  <a
    v-else
    :href="ctaUrl"
    class="ui-focus block relative w-full overflow-hidden border border-dashed border-[color:var(--color-hairline)] bg-gradient-to-r from-[#0F172A] via-[#111827] to-[#0F172A] group hover:border-[color:var(--color-accent-400)] transition-colors"
  >
    <div class="relative aspect-[7/1] md:aspect-[8/1]">
      <div
        class="absolute inset-0 opacity-40 pointer-events-none"
        :style="{ background: 'linear-gradient(90deg, transparent 0%, rgba(99,102,241,0.15) 40%, rgba(59,130,246,0.15) 60%, transparent 100%)' }"
      />
      <div class="absolute top-3 left-4 z-10">
        <span class="ui-mono text-[9px] uppercase tracking-[0.14em] px-1.5 py-0.5 bg-white/8 backdrop-blur text-[color:var(--color-accent-400)] font-semibold border border-white/10">
          Ad slot
        </span>
      </div>
      <div class="absolute inset-0 flex items-center justify-between px-6 md:px-10 z-10">
        <div class="max-w-xl">
          <h3 class="ui-display text-white text-base md:text-xl font-semibold tracking-tight leading-tight mb-0.5">
            {{ title }}
          </h3>
          <p class="text-white/60 text-xs md:text-sm leading-snug line-clamp-1">
            {{ subtitle }}
          </p>
        </div>
        <div class="hidden sm:flex items-center gap-1.5 text-xs md:text-sm text-white/80 font-medium group-hover:translate-x-0.5 transition-transform duration-[180ms]">
          {{ ctaLabel }}
          <svg class="w-3 h-3 md:w-3.5 md:h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M5 12h14M13 5l7 7-7 7"/>
          </svg>
        </div>
      </div>
    </div>
  </a>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const props = defineProps({
  slot: { type: String, default: 'homepage_1' },
  // Fallback props when no live banner
  title: { type: String, default: 'AD GOES HERE' },
  subtitle: { type: String, default: 'Premium mid-page placement. Inquire about rates.' },
  ctaLabel: { type: String, default: 'Advertise here' },
  ctaUrl: { type: String, default: '/become-a-vendor' },
})

const liveBanner = ref(null)

onMounted(async () => {
  try {
    const res = await fetch(`/api/banners/${props.slot}`)
    if (res.ok) {
      const data = await res.json()
      if (data.banner && data.banner.link) {
        liveBanner.value = data.banner
      }
    }
  } catch (e) {
    // Silently fall back to placeholder
  }
})

async function trackClick() {
  if (!liveBanner.value?.id) return
  try {
    await fetch(`/api/banners/${liveBanner.value.id}/click`, { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' } })
  } catch (e) {
    // noop
  }
}
</script>
