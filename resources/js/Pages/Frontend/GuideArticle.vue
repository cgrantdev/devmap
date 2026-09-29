<template>
  <Head :title="title">
    <meta name="description" :content="description" />
    <meta property="og:type" content="article" />
    <meta property="og:url" :content="url" />
    <meta property="og:title" :content="ogTitle" />
    <meta property="og:description" :content="ogDescription" />
    <meta v-if="ogImage" property="og:image" :content="ogImage" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" :content="ogTitle" />
    <meta name="twitter:description" :content="ogDescription" />
    <meta v-if="ogImage" name="twitter:image" :content="ogImage" />
    <link rel="canonical" :href="canonical" />
  </Head>

  <ModernLayout>
    <article class="max-w-[1280px] mx-auto px-5 lg:px-10">
      <div class="max-w-3xl mx-auto pt-8 lg:pt-12">
        <nav class="flex items-center gap-2 text-[13px] text-[color:var(--color-ink-muted)] mb-6" aria-label="Breadcrumb">
          <a href="/" class="hover:text-[color:var(--color-ink)]">Home</a>
          <span>/</span>
          <a href="/guides" class="hover:text-[color:var(--color-ink)]">Guides</a>
          <span v-if="guide.tag">/</span>
          <span v-if="guide.tag" class="text-[11px] font-semibold text-[color:var(--color-accent-600)] uppercase tracking-wide">{{ guide.tag }}</span>
        </nav>

        <h1 class="ui-display text-3xl lg:text-[42px] font-semibold tracking-tight text-[color:var(--color-ink)] leading-[1.15] mb-5">
          {{ guide.title }}
        </h1>

        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-[13px] text-[color:var(--color-ink-muted)] mb-8">
          <span v-if="guide.date" class="ui-mono">{{ guide.date }}</span>
          <span v-if="guide.readingTime">{{ guide.readingTime }}</span>
        </div>
      </div>

      <div class="max-w-3xl mx-auto pb-16 lg:pb-24">
        <p v-if="guide.description" class="text-[17px] lg:text-[19px] text-[color:var(--color-ink-muted)] leading-relaxed mb-10 font-medium">
          {{ guide.description }}
        </p>

        <div v-if="guide.content" class="edu-article" v-html="guide.content"></div>

        <div class="mt-10 p-5 bg-[color:var(--color-hairline-soft)] border border-[color:var(--color-hairline)] text-[13px] text-[color:var(--color-ink-muted)]">
          <strong class="text-[color:var(--color-ink)]">Disclaimer:</strong> This guide is for educational literacy only. It is not medical advice, legal advice, or dosing instructions.
        </div>
      </div>
    </article>
  </ModernLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import ModernLayout from '@/Pages/Layouts/ModernLayout.vue'

const props = defineProps({
  guide: { type: Object, required: true },
  seo: { type: Object, default: () => ({}) },
})

const page = usePage()
const title = computed(() => props.seo?.title || props.guide?.title || 'Guide')
const description = computed(() => props.seo?.description || props.guide?.description || '')
const url = computed(() => props.seo?.url || page.url)
const ogTitle = computed(() => props.seo?.og_title || title.value)
const ogDescription = computed(() => props.seo?.og_description || description.value)
const ogImage = computed(() => props.seo?.og_image || null)
const canonical = computed(() => props.seo?.canonical || url.value)
</script>
