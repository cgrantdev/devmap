<template>
  <ModernLayout>
    <Head>
      <title>{{ seo?.title || 'News' }}</title>
      <meta name="description" :content="seo?.description || ''" />
    </Head>

    <!-- Header -->
    <div class="max-w-[1280px] mx-auto px-5 lg:px-10 pt-8 lg:pt-12 pb-8">
      <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4 mb-8">
        <div>
          <div class="text-[11px] uppercase tracking-[0.12em] font-semibold text-[color:var(--color-accent-600)] mb-2">News</div>
          <h1 class="ui-display text-3xl lg:text-4xl font-semibold tracking-tight text-[color:var(--color-ink)]">
            Peptide research & industry news
          </h1>
          <p class="text-[15px] text-[color:var(--color-ink-muted)] mt-2 max-w-xl">
            The latest updates from the peptide research community.
          </p>
        </div>
        <!-- Search -->
        <div class="relative w-full lg:w-80">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[color:var(--color-ink-subtle)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          <input
            v-model="searchQuery"
            @input="handleSearch"
            type="text"
            placeholder="Search articles..."
            class="w-full h-10 pl-9 pr-4 text-sm border border-[color:var(--color-hairline)] bg-white focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15 transition-colors"
          />
        </div>
      </div>

      <!-- Category filters -->
      <div class="flex items-center gap-1 overflow-x-auto pb-1">
        <button
          v-for="f in filters"
          :key="f.value"
          @click="selectFilter(f.value)"
          :class="[
            'h-8 px-3.5 text-[13px] font-medium transition-colors whitespace-nowrap',
            currentFilter === f.value
              ? 'bg-[color:var(--color-ink)] text-white'
              : 'text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)] hover:bg-[color:var(--color-hairline-soft)]',
          ]"
        >
          {{ f.label }}
        </button>
      </div>
    </div>

    <!-- Articles -->
    <div class="max-w-[1280px] mx-auto px-5 lg:px-10 pb-16 lg:pb-20">

      <!-- Featured -->
      <div v-if="filteredFeaturedBlogs.length" class="mb-12">
        <h2 class="text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)] mb-4">Featured</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
          <a
            v-for="blog in filteredFeaturedBlogs"
            :key="blog.id"
            :href="`/blog/${blog.slug}`"
            class="ui-focus group flex flex-col border border-[color:var(--color-hairline)] bg-white hover:border-[color:var(--color-accent-400)] hover:shadow-[var(--shadow-md)] hover:-translate-y-[1px] transition-all duration-[200ms] overflow-hidden"
          >
            <div class="aspect-[16/9] bg-[#0F172A] border-b border-[color:var(--color-hairline)] overflow-hidden relative">
              <img v-if="blog.image" :src="blog.image" :alt="blog.title" class="w-full h-full object-cover opacity-80 group-hover:opacity-90 group-hover:scale-[1.03] transition-all duration-500" loading="lazy" />
              <div class="absolute inset-0 bg-gradient-to-tr from-[#312E81]/40 via-transparent to-[#4F46E5]/20 pointer-events-none" />
              <div v-if="blog.categoryTag" class="absolute top-3 left-3 px-2 py-0.5 bg-white/90 backdrop-blur-sm text-[10px] font-semibold text-[color:var(--color-accent-700)] uppercase tracking-wide">{{ blog.categoryTag }}</div>
              <div v-if="!blog.image" class="absolute inset-0 flex items-center justify-center text-white/30">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </div>
            </div>
            <div class="p-4 lg:p-5 flex-1 flex flex-col gap-2">
              <span class="text-[11px] text-[color:var(--color-ink-subtle)] ui-mono">{{ blog.date }}</span>
              <h3 class="ui-display text-[16px] font-semibold tracking-tight text-[color:var(--color-ink)] leading-snug group-hover:text-[color:var(--color-accent-600)] transition-colors">
                {{ blog.title }}
              </h3>
              <p class="text-[13px] text-[color:var(--color-ink-muted)] leading-relaxed line-clamp-2">{{ blog.description }}</p>
              <div class="mt-auto pt-3 flex items-center gap-2 text-[12px] text-[color:var(--color-ink-subtle)]">
                <span v-if="blog.author">{{ blog.author }}</span>
                <span v-if="blog.readTime">· {{ blog.readTime }}</span>
              </div>
            </div>
          </a>
        </div>
      </div>

      <!-- Latest -->
      <div v-if="filteredLatestBlogs.length">
        <h2 v-if="filteredFeaturedBlogs.length" class="text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)] mb-4">Latest</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
          <a
            v-for="blog in filteredLatestBlogs"
            :key="blog.id"
            :href="`/blog/${blog.slug}`"
            class="ui-focus group flex flex-col border border-[color:var(--color-hairline)] bg-white hover:border-[color:var(--color-accent-400)] hover:shadow-[var(--shadow-md)] hover:-translate-y-[1px] transition-all duration-[200ms] overflow-hidden"
          >
            <div class="aspect-[16/9] bg-[#0F172A] border-b border-[color:var(--color-hairline)] overflow-hidden relative">
              <img v-if="blog.image" :src="blog.image" :alt="blog.title" class="w-full h-full object-cover opacity-80 group-hover:opacity-90 group-hover:scale-[1.03] transition-all duration-500" loading="lazy" />
              <div class="absolute inset-0 bg-gradient-to-tr from-[#312E81]/40 via-transparent to-[#4F46E5]/20 pointer-events-none" />
              <div v-if="blog.categoryTag" class="absolute top-3 left-3 px-2 py-0.5 bg-white/90 backdrop-blur-sm text-[10px] font-semibold text-[color:var(--color-accent-700)] uppercase tracking-wide">{{ blog.categoryTag }}</div>
              <div v-if="!blog.image" class="absolute inset-0 flex items-center justify-center text-white/30">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke-linecap="round" stroke-linejoin="round"/></svg>
              </div>
            </div>
            <div class="p-4 lg:p-5 flex-1 flex flex-col gap-2">
              <span class="text-[11px] text-[color:var(--color-ink-subtle)] ui-mono">{{ blog.date }}</span>
              <h3 class="ui-display text-[16px] font-semibold tracking-tight text-[color:var(--color-ink)] leading-snug group-hover:text-[color:var(--color-accent-600)] transition-colors">
                {{ blog.title }}
              </h3>
              <p class="text-[13px] text-[color:var(--color-ink-muted)] leading-relaxed line-clamp-2">{{ blog.description }}</p>
              <div class="mt-auto pt-3 flex items-center gap-2 text-[12px] text-[color:var(--color-ink-subtle)]">
                <span v-if="blog.author">{{ blog.author }}</span>
                <span v-if="blog.readTime">· {{ blog.readTime }}</span>
              </div>
            </div>
          </a>
        </div>
      </div>

      <div v-if="!filteredFeaturedBlogs.length && !filteredLatestBlogs.length" class="py-20 text-center">
        <p class="text-[color:var(--color-ink-subtle)]">No articles found. Try a different search or filter.</p>
      </div>
    </div>
  </ModernLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import ModernLayout from '@/Pages/Layouts/ModernLayout.vue'

const props = defineProps({
  featuredBlogs: { type: Array, default: () => [] },
  latestBlogs: { type: Array, default: () => [] },
  // Keep these props so the controller doesn't break, but we don't render them
  researchPapers: { type: Array, default: () => [] },
  educationalGuides: { type: Array, default: () => [] },
  search: { type: String, default: '' },
  primaryNav: { type: String, default: 'news' },
  filter: { type: String, default: 'all' },
  seo: { type: Object, default: () => ({}) },
})

const searchQuery = ref(props.search || '')
const currentFilter = ref(props.filter || 'all')

const filters = [
  { label: 'All', value: 'all' },
  { label: 'Research', value: 'research' },
  { label: 'Industry', value: 'industry' },
  { label: 'Regulation', value: 'regulation' },
  { label: 'Guides', value: 'guides' },
  { label: 'Community', value: 'community' },
]

const filterToBlogType = {
  research: 'Research',
  industry: 'Industry',
  regulation: 'Regulation',
  guides: 'Guides',
  community: 'Community',
}

const currentBlogType = computed(() => {
  if (currentFilter.value === 'all') return null
  return filterToBlogType[currentFilter.value] || null
})

const filteredFeaturedBlogs = computed(() => {
  let result = props.featuredBlogs || []
  if (currentBlogType.value) result = result.filter(b => b.blogType === currentBlogType.value)
  return result
})

const filteredLatestBlogs = computed(() => {
  let result = props.latestBlogs || []
  if (currentBlogType.value) result = result.filter(b => b.blogType === currentBlogType.value)
  return result
})

function selectFilter(value) {
  currentFilter.value = value
  updateURL()
}

function handleSearch() {
  updateURL()
}

function updateURL() {
  const params = new URLSearchParams()
  if (searchQuery.value.trim()) params.set('search', searchQuery.value.trim())
  if (currentFilter.value !== 'all') params.set('filter', currentFilter.value)
  const qs = params.toString()
  router.visit(`/news${qs ? '?' + qs : ''}`, { preserveState: true, preserveScroll: true, replace: true })
}
</script>
