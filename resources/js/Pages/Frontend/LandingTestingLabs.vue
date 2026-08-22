<template>
  <ModernLayout>
    <Head>
      <title>{{ seo.title }}</title>
      <meta name="description" :content="seo.description" />
      <link rel="canonical" :href="seo.canonical" />
    </Head>

    <section class="border-b border-[color:var(--color-hairline)]">
      <div class="max-w-[1000px] mx-auto px-6 lg:px-10 py-10">
        <div class="text-[11px] uppercase tracking-wider font-semibold text-[color:var(--color-accent-600)] mb-2">
          Trust & Verification
        </div>
        <h1 class="ui-display text-3xl md:text-4xl font-semibold text-[color:var(--color-ink)] mb-3">
          Third-Party Testing Labs
        </h1>
        <p class="text-[color:var(--color-ink-muted)] leading-relaxed max-w-3xl text-[15px]">
          Not every vendor tests their peptides — and among those who do, the lab they use matters. This page groups our verified vendors by which third-party analytical lab issues their COAs, so you can verify purity claims before you buy.
        </p>
      </div>
    </section>

    <section>
      <div class="max-w-[1000px] mx-auto px-6 lg:px-10 py-10 space-y-8">
        <div
          v-for="lab in lab_sections"
          :key="lab.key"
          class="border border-[color:var(--color-hairline)] rounded-[12px] bg-white overflow-hidden"
        >
          <div class="p-6 border-b border-[color:var(--color-hairline)]">
            <div class="flex items-center justify-between gap-4 flex-wrap">
              <div>
                <h2 class="text-[18px] font-semibold text-[color:var(--color-ink)]">{{ lab.name }}</h2>
                <div class="text-[12px] text-[color:var(--color-ink-subtle)] mt-0.5">
                  {{ lab.country }} · {{ lab.vendor_count }} {{ lab.vendor_count === 1 ? 'vendor' : 'vendors' }}
                </div>
              </div>
            </div>
            <p class="text-[13px] text-[color:var(--color-ink-muted)] mt-3">{{ lab.note }}</p>
          </div>
          <ul class="divide-y divide-[color:var(--color-hairline-soft)]">
            <li v-for="v in lab.vendors" :key="v.slug">
              <a :href="`/brand/${v.slug}`" class="flex items-center justify-between px-6 py-3 hover:bg-[color:var(--color-accent-50)]/40 transition-colors">
                <div>
                  <div class="text-[14px] font-medium text-[color:var(--color-ink)]">{{ v.name }}</div>
                  <div v-if="v.location" class="text-[11px] text-[color:var(--color-ink-subtle)] mt-0.5">Ships from {{ v.location }}</div>
                </div>
                <svg class="w-4 h-4 text-[color:var(--color-ink-subtle)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
              </a>
            </li>
          </ul>
        </div>

        <div v-if="unspecified.length" class="border border-[color:var(--color-hairline)] rounded-[12px] bg-[color:var(--color-bg)] p-6">
          <h2 class="text-[15px] font-semibold text-[color:var(--color-ink)] mb-2">Lab not specified</h2>
          <p class="text-[13px] text-[color:var(--color-ink-muted)] mb-4">
            These vendors haven't publicly disclosed which lab handles their testing. Ask before you buy — most will name their lab on request.
          </p>
          <div class="flex flex-wrap gap-2">
            <a
              v-for="v in unspecified"
              :key="v.slug"
              :href="`/brand/${v.slug}`"
              class="ui-focus inline-flex items-center px-3 py-1.5 rounded-full border border-[color:var(--color-hairline)] bg-white text-[12px] text-[color:var(--color-ink)] hover:border-[color:var(--color-accent-400)] transition-colors"
            >
              {{ v.name }}
            </a>
          </div>
        </div>
      </div>
    </section>
  </ModernLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import ModernLayout from '@/Pages/Layouts/ModernLayout.vue'

defineProps({
  lab_sections: { type: Array, default: () => [] },
  unspecified: { type: Array, default: () => [] },
  seo: { type: Object, default: () => ({}) },
})
</script>
