<template>
  <Head :title="title">
    <meta name="description" :content="description" />
    <link rel="canonical" :href="canonical" />
  </Head>

  <ModernLayout>
    <section class="max-w-[1280px] mx-auto px-5 lg:px-10 pt-10 pb-20">
      <nav class="text-[13px] text-[color:var(--color-ink-muted)] mb-6" aria-label="Breadcrumb">
        <a href="/" class="hover:text-[color:var(--color-ink)]">Home</a>
        <span class="mx-1.5">/</span>
        <span class="text-[color:var(--color-ink)]">Guides</span>
      </nav>

      <div class="max-w-3xl mb-10">
        <p class="text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-accent-600)] mb-3">Educational</p>
        <h1 class="ui-display text-3xl lg:text-[42px] font-semibold tracking-tight text-[color:var(--color-ink)] leading-[1.15] mb-4">
          Research Peptide Guides
        </h1>
        <p class="text-[17px] text-[color:var(--color-ink-muted)] leading-relaxed">
          Definitions, documentation literacy, and regulatory pathways. These guides are educational. They are not medical advice, dosing instructions, or price tables.
        </p>
      </div>

      <div v-if="guides.length" class="grid gap-5 max-w-3xl">
        <a
          v-for="guide in guides"
          :key="guide.slug"
          :href="`/guides/${guide.slug}`"
          class="block overflow-hidden border border-[color:var(--color-hairline)] bg-white hover:border-[color:var(--color-accent-400)] transition-colors"
        >
          <div v-if="guide.cover" class="aspect-[16/9] bg-[#0B1424] overflow-hidden">
            <img :src="guide.cover" :alt="guide.title" class="w-full h-full object-cover" loading="lazy" />
          </div>
          <div class="p-6">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[12px] text-[color:var(--color-ink-subtle)] mb-2">
              <span v-if="guide.tag" class="font-semibold uppercase tracking-wide text-[color:var(--color-accent-600)]">{{ guide.tag }}</span>
              <span v-if="guide.readingTime">{{ guide.readingTime }}</span>
              <span v-if="guide.date">{{ guide.date }}</span>
            </div>
            <h2 class="text-[20px] font-semibold text-[color:var(--color-ink)] leading-snug mb-2">{{ guide.title }}</h2>
            <p v-if="guide.description" class="text-[15px] text-[color:var(--color-ink-muted)] leading-relaxed line-clamp-3">{{ guide.description }}</p>
          </div>
        </a>
      </div>
      <p v-else class="text-[color:var(--color-ink-muted)]">No guides published yet.</p>
    </section>
  </ModernLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import ModernLayout from '@/Pages/Layouts/ModernLayout.vue'

const props = defineProps({
  guides: { type: Array, default: () => [] },
  seo: { type: Object, default: () => ({}) },
})

const title = computed(() => props.seo?.title || 'Research Peptide Guides')
const description = computed(() => props.seo?.description || '')
const canonical = computed(() => props.seo?.canonical || props.seo?.url || '')
</script>
