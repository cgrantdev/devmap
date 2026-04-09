<template>
  <!-- When a real ad is passed in, render it; otherwise show the
       "Advertise your brand here" placeholder recruiting CTA. -->
  <div class="relative">
    <!-- Real ad mode -->
    <a
      v-if="ad"
      :href="ad.url"
      :target="ad.target || '_blank'"
      rel="noopener sponsored"
      class="ui-focus block relative rounded-[20px] overflow-hidden border border-[color:var(--color-hairline)] group"
    >
      <div class="absolute top-3 left-3 z-10">
        <span class="ui-mono text-[10px] uppercase tracking-[0.1em] px-2 py-1 rounded bg-black/60 backdrop-blur text-white">
          Sponsored
        </span>
      </div>
      <img
        v-if="ad.image"
        :src="ad.image"
        :alt="ad.alt || 'Sponsored content'"
        class="w-full h-full object-cover group-hover:scale-[1.01] transition-transform duration-[300ms]"
        loading="lazy"
      />
      <div
        v-if="ad.title || ad.subtitle"
        class="absolute inset-0 flex flex-col justify-end p-8 bg-gradient-to-t from-black/80 via-black/30 to-transparent text-white"
      >
        <h3 v-if="ad.title" class="ui-display text-2xl md:text-3xl font-semibold tracking-tight mb-1">
          {{ ad.title }}
        </h3>
        <p v-if="ad.subtitle" class="text-sm md:text-base text-white/80">
          {{ ad.subtitle }}
        </p>
      </div>
    </a>

    <!-- Placeholder recruiting mode -->
    <div
      v-else
      class="relative rounded-[20px] overflow-hidden border border-dashed border-[color:var(--color-hairline)] bg-gradient-to-br from-[#1E293B] to-[#0F172A] text-white p-12 md:p-14"
    >
      <!-- Decorative orbs -->
      <div class="absolute -top-20 -left-10 w-64 h-64 rounded-full bg-[color:var(--color-accent-500)] opacity-[0.12] blur-3xl pointer-events-none" />
      <div class="absolute -bottom-24 -right-10 w-80 h-80 rounded-full bg-[color:var(--color-biotech-500)] opacity-[0.12] blur-3xl pointer-events-none" />

      <div class="relative flex flex-col items-center text-center gap-3 max-w-md mx-auto">
        <div class="ui-mono text-[10px] uppercase tracking-[0.14em] text-[color:var(--color-accent-400)] font-semibold">
          Sponsored
        </div>
        <h3 class="ui-display text-2xl md:text-3xl font-semibold tracking-tight">
          {{ title }}
        </h3>
        <p class="text-sm text-white/70 leading-relaxed">
          {{ subtitle }}
        </p>
        <a
          :href="ctaUrl"
          class="ui-focus mt-3 inline-flex items-center gap-2 px-5 py-2.5 rounded-[10px] bg-white text-[color:var(--color-ink)] text-sm font-medium hover:bg-white/90 transition-colors"
        >
          {{ ctaLabel }}
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M5 12h14M13 5l7 7-7 7"/>
          </svg>
        </a>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  ad: {
    type: Object,
    default: null,
    /* shape when provided:
       { url, image, alt?, title?, subtitle?, target? } */
  },
  title: { type: String, default: 'Advertise your brand here' },
  subtitle: {
    type: String,
    default: 'Reach thousands of peptide researchers actively comparing vendors.',
  },
  ctaLabel: { type: String, default: 'Learn more' },
  ctaUrl: { type: String, default: '/become-a-vendor' },
})
</script>
