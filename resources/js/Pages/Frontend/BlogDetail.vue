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

      <!-- Header -->
      <div class="max-w-3xl mx-auto pt-8 lg:pt-12">
        <!-- Back + category -->
        <div class="flex items-center gap-3 mb-6">
          <a href="/news" class="text-[13px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)] transition-colors flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            News
          </a>
          <span v-if="blog.categoryTag" class="text-[color:var(--color-hairline)]">/</span>
          <span v-if="blog.categoryTag" class="text-[11px] font-semibold text-[color:var(--color-accent-600)] uppercase tracking-wide">{{ blog.categoryTag }}</span>
        </div>

        <!-- Title -->
        <h1 class="ui-display text-3xl lg:text-[42px] font-semibold tracking-tight text-[color:var(--color-ink)] leading-[1.15] mb-5">
          {{ blog.title }}
        </h1>

        <!-- Meta -->
        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-[13px] text-[color:var(--color-ink-muted)] mb-8">
          <span v-if="blog.author_name" class="font-medium text-[color:var(--color-ink)]">
            {{ blog.author_name }}
            <span v-if="blog.author_job" class="font-normal text-[color:var(--color-ink-subtle)]">· {{ blog.author_job }}</span>
          </span>
          <span v-if="blog.date" class="ui-mono">{{ blog.date }}</span>
          <span v-if="blog.readTime">{{ blog.readTime }}</span>
        </div>
      </div>

      <!-- Featured image -->
      <div v-if="blog.image" class="max-w-4xl mx-auto mb-10 lg:mb-14">
        <div class="aspect-[2/1] bg-[#0F172A] overflow-hidden relative">
          <img :src="blog.image" :alt="blog.title" class="w-full h-full object-cover opacity-90" loading="lazy" @error="$event.target.style.display='none'" />
          <div class="absolute inset-0 bg-gradient-to-tr from-[#312E81]/30 via-transparent to-[#4F46E5]/15 pointer-events-none" />
        </div>
      </div>

      <!-- Body -->
      <div class="max-w-3xl mx-auto pb-16 lg:pb-24">

        <!-- Description / lede -->
        <p v-if="blog.description" class="text-[17px] lg:text-[19px] text-[color:var(--color-ink-muted)] leading-relaxed mb-10 font-medium">
          {{ blog.description }}
        </p>

        <!-- Introduction -->
        <div v-if="blog.introduction" class="mb-10">
          <p class="text-[16px] text-[color:var(--color-ink)] leading-[1.8] whitespace-pre-line">{{ blog.introduction }}</p>
        </div>

        <!-- Key Points -->
        <div v-if="blog.key_points && blog.key_points.length > 0" class="mb-10 bg-[color:var(--color-accent-50)] border border-[color:var(--color-accent-100)] p-6 lg:p-8">
          <h2 class="text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-accent-600)] mb-4">Key Takeaways</h2>
          <ul class="space-y-3">
            <li v-for="(point, i) in blog.key_points" :key="i" class="flex gap-3 text-[15px] text-[color:var(--color-ink)] leading-relaxed">
              <span class="flex-shrink-0 w-5 h-5 bg-[color:var(--color-accent-600)] text-white text-[11px] font-bold flex items-center justify-center mt-0.5">{{ i + 1 }}</span>
              <span>{{ point }}</span>
            </li>
          </ul>
        </div>

        <!-- Detailed Analysis -->
        <div v-if="blog.detailed_analysis" class="mb-10">
          <div class="text-[16px] text-[color:var(--color-ink)] leading-[1.8] whitespace-pre-line">{{ blog.detailed_analysis }}</div>
        </div>

        <!-- Conclusion -->
        <div v-if="blog.conclusion" class="mb-10 border-l-4 border-[color:var(--color-accent-500)] pl-6 lg:pl-8">
          <h2 class="text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)] mb-3">Conclusion</h2>
          <p class="text-[16px] text-[color:var(--color-ink)] leading-[1.8] whitespace-pre-line">{{ blog.conclusion }}</p>
        </div>

        <!-- HTML content fallback -->
        <div v-if="!blog.introduction && !blog.detailed_analysis && blog.content" class="prose prose-lg max-w-none" v-html="blog.content"></div>

        <!-- Disclaimer -->
        <div class="mt-10 p-5 bg-[color:var(--color-hairline-soft)] border border-[color:var(--color-hairline)] text-[13px] text-[color:var(--color-ink-muted)]">
          <strong class="text-[color:var(--color-ink)]">Disclaimer:</strong> This article is for informational and educational purposes only. The compounds discussed are intended for research use only and are not approved for human therapeutic use. Consult a qualified professional before making any decisions based on this content.
        </div>

        <!-- Tags -->
        <div v-if="blog.tags?.length" class="mt-8 flex flex-wrap gap-2">
          <span
            v-for="tag in blog.tags"
            :key="tag"
            class="text-[12px] font-medium text-[color:var(--color-ink-subtle)] bg-[color:var(--color-hairline-soft)] px-3 py-1"
          >{{ tag }}</span>
        </div>

        <!-- Related Articles -->
        <div v-if="related?.length" class="mt-16 pt-10 border-t border-[color:var(--color-hairline)]">
          <h2 class="text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)] mb-6">Related Articles</h2>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <a
              v-for="item in related"
              :key="item.id"
              :href="`/blog/${item.slug}`"
              class="ui-focus group flex flex-col border border-[color:var(--color-hairline)] bg-white hover:border-[color:var(--color-accent-400)] hover:shadow-[var(--shadow-md)] hover:-translate-y-[1px] transition-all duration-[200ms] overflow-hidden"
            >
              <div class="aspect-[16/9] bg-[#0F172A] overflow-hidden relative">
                <img v-if="item.image" :src="item.image" :alt="item.title" class="w-full h-full object-cover opacity-80 group-hover:opacity-90 group-hover:scale-[1.03] transition-all duration-500" loading="lazy" />
                <div class="absolute inset-0 bg-gradient-to-tr from-[#312E81]/40 via-transparent to-[#4F46E5]/20 pointer-events-none" />
              </div>
              <div class="p-4 flex-1 flex flex-col gap-1.5">
                <span class="text-[11px] text-[color:var(--color-ink-subtle)] ui-mono">{{ item.date }}</span>
                <h3 class="text-[14px] font-semibold text-[color:var(--color-ink)] leading-snug group-hover:text-[color:var(--color-accent-600)] transition-colors line-clamp-2">{{ item.title }}</h3>
              </div>
            </a>
          </div>
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
  blog: Object,
  related: { type: Array, default: () => [] },
  seo: { type: Object, default: () => ({}) },
})

const page = usePage()

const title = computed(() => props.seo?.title || `${props.blog?.title || 'News'} - ${page.props.site_name || 'PeptideMaps'}`)
const description = computed(() => props.seo?.description || props.blog?.description?.substring(0, 160) || '')
const url = computed(() => props.seo?.url || page.url)
const ogTitle = computed(() => props.seo?.og_title || title.value)
const ogDescription = computed(() => props.seo?.og_description || description.value)
const ogImage = computed(() => props.seo?.og_image || props.blog?.image || null)
const canonical = computed(() => props.seo?.canonical || url.value)
</script>
