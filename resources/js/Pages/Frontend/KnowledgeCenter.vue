<template>
  <ModernLayout>
    <Head>
      <title>{{ seo?.title || 'Knowledge Center' }}</title>
      <meta name="description" :content="seo?.description || ''" />
    </Head>

    <!-- Header -->
    <div class="max-w-[1280px] mx-auto px-5 lg:px-10 pt-8 lg:pt-12">
      <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4 mb-6">
        <div>
          <div class="text-[11px] uppercase tracking-[0.12em] font-semibold text-[color:var(--color-accent-600)] mb-2">Knowledge Center</div>
          <h1 class="ui-display text-3xl lg:text-4xl font-semibold tracking-tight text-[color:var(--color-ink)]">
            Research, news & guides
          </h1>
          <p class="text-[15px] text-[color:var(--color-ink-muted)] mt-2 max-w-xl">
            Latest research papers, industry updates, and expert guides from the peptide community.
          </p>
        </div>
        <!-- Search -->
        <div class="relative w-full lg:w-80">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[color:var(--color-ink-subtle)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          <input
            v-model="searchQuery"
            @input="handleSearch"
            type="text"
            placeholder="Search articles, research..."
            class="w-full h-10 pl-9 pr-4 text-sm border border-[color:var(--color-hairline)] bg-white focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15 transition-colors"
          />
        </div>
      </div>

      <!-- Primary tabs -->
      <div class="border-b border-[color:var(--color-hairline)]">
        <div class="flex gap-0">
          <button
            v-for="nav in primaryNavs"
            :key="nav.value"
            @click="selectPrimaryNav(nav.value)"
            :class="[
              'px-5 py-3 text-[14px] font-medium border-b-2 transition-colors -mb-px',
              activeNav === nav.value
                ? 'border-[color:var(--color-accent-600)] text-[color:var(--color-accent-600)]'
                : 'border-transparent text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)] hover:border-[color:var(--color-hairline)]',
            ]"
          >
            {{ nav.label }}
          </button>
        </div>
      </div>
    </div>

    <!-- Content -->
    <div class="max-w-[1280px] mx-auto px-5 lg:px-10 py-8 lg:py-10">

      <!-- ======================== NEWS VIEW ======================== -->
      <template v-if="activeNav === 'news'">
        <!-- Category filters -->
        <div class="flex items-center gap-1 mb-8 overflow-x-auto pb-1">
          <button
            v-for="f in newsFilters"
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
              <div class="aspect-[16/9] bg-[color:var(--color-bg)] border-b border-[color:var(--color-hairline)] overflow-hidden">
                <img v-if="blog.image" :src="blog.image" :alt="blog.title" class="w-full h-full object-cover" loading="lazy" />
                <div v-else class="w-full h-full flex items-center justify-center text-[color:var(--color-ink-subtle)]">
                  <svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
              </div>
              <div class="p-4 lg:p-5 flex-1 flex flex-col gap-2">
                <div class="flex items-center gap-2">
                  <span v-if="blog.categoryTag" class="text-[11px] font-semibold text-[color:var(--color-accent-600)]">{{ blog.categoryTag }}</span>
                  <span class="text-[11px] text-[color:var(--color-ink-subtle)] ui-mono">{{ blog.date }}</span>
                </div>
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
          <h2 class="text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)] mb-4">Latest</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <a
              v-for="blog in filteredLatestBlogs"
              :key="blog.id"
              :href="`/blog/${blog.slug}`"
              class="ui-focus group flex flex-col border border-[color:var(--color-hairline)] bg-white hover:border-[color:var(--color-accent-400)] hover:shadow-[var(--shadow-md)] hover:-translate-y-[1px] transition-all duration-[200ms] overflow-hidden"
            >
              <div class="aspect-[16/9] bg-[color:var(--color-bg)] border-b border-[color:var(--color-hairline)] overflow-hidden">
                <img v-if="blog.image" :src="blog.image" :alt="blog.title" class="w-full h-full object-cover" loading="lazy" />
                <div v-else class="w-full h-full flex items-center justify-center text-[color:var(--color-ink-subtle)]">
                  <svg class="w-10 h-10" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
              </div>
              <div class="p-4 lg:p-5 flex-1 flex flex-col gap-2">
                <div class="flex items-center gap-2">
                  <span v-if="blog.categoryTag" class="text-[11px] font-semibold text-[color:var(--color-accent-600)]">{{ blog.categoryTag }}</span>
                  <span class="text-[11px] text-[color:var(--color-ink-subtle)] ui-mono">{{ blog.date }}</span>
                </div>
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
      </template>

      <!-- ======================== RESEARCH VIEW ======================== -->
      <template v-if="activeNav === 'research'">
        <p class="text-[14px] text-[color:var(--color-ink-muted)] mb-8 max-w-2xl">
          Curated collection of clinical studies and research papers on peptide therapies. All studies link to their original sources on PubMed.
        </p>

        <div v-if="filteredResearchPapers.length" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
          <a
            v-for="paper in filteredResearchPapers"
            :key="paper.id"
            :href="`/research/${paper.id}`"
            class="ui-focus group flex flex-col p-5 border border-[color:var(--color-hairline)] bg-white hover:border-[color:var(--color-accent-400)] hover:shadow-[var(--shadow-md)] transition-all duration-[200ms]"
          >
            <div class="flex items-center gap-2 mb-3">
              <span v-if="paper.peptide" class="text-[11px] font-semibold text-[color:var(--color-accent-600)] uppercase tracking-wide">{{ paper.peptide }}</span>
              <span :class="[
                'inline-flex items-center px-2 py-0.5 text-[10px] font-semibold',
                paper.evidenceLevel === 'High' ? 'bg-emerald-50 text-emerald-700' :
                paper.evidenceLevel === 'Medium' ? 'bg-amber-50 text-amber-700' :
                'bg-gray-100 text-gray-600'
              ]">{{ paper.evidenceLevel || 'N/A' }}</span>
            </div>
            <h3 class="ui-display text-[15px] font-semibold tracking-tight text-[color:var(--color-ink)] leading-snug mb-2 group-hover:text-[color:var(--color-accent-600)] transition-colors">
              {{ paper.title }}
            </h3>
            <p class="text-[13px] text-[color:var(--color-ink-muted)] leading-relaxed line-clamp-2 mb-3">{{ paper.description }}</p>
            <div class="mt-auto flex items-center justify-between text-[12px] text-[color:var(--color-ink-subtle)]">
              <div class="flex items-center gap-2">
                <span v-if="paper.source">{{ paper.source }}</span>
                <span>· {{ paper.date }}</span>
              </div>
              <span v-if="paper.pubmedUrl" class="text-[color:var(--color-accent-600)] font-medium flex items-center gap-1">
                PubMed
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6M15 3h6v6M10 14L21 3"/></svg>
              </span>
            </div>
            <!-- Tags -->
            <div v-if="paper.tags?.length" class="flex flex-wrap gap-1.5 mt-3 pt-3 border-t border-[color:var(--color-hairline-soft)]">
              <span v-for="tag in paper.tags.slice(0, 4)" :key="tag" class="text-[10px] font-medium text-[color:var(--color-ink-subtle)] bg-[color:var(--color-hairline-soft)] px-2 py-0.5">{{ tag }}</span>
            </div>
          </a>
        </div>

        <div v-else class="py-20 text-center">
          <p class="text-[color:var(--color-ink-subtle)]">No research papers found. Try a different search.</p>
        </div>
      </template>

      <!-- ======================== GUIDES VIEW ======================== -->
      <template v-if="activeNav === 'guides'">
        <p class="text-[14px] text-[color:var(--color-ink-muted)] mb-8 max-w-2xl">
          Comprehensive guides written by experts to help you understand peptides, build protocols, and use them safely.
        </p>

        <div v-if="filteredEducationalGuides.length" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
          <a
            v-for="guide in filteredEducationalGuides"
            :key="guide.id"
            :href="guide.guideUrl || `/guide/${guide.id}`"
            class="ui-focus group flex flex-col p-5 border border-[color:var(--color-hairline)] bg-white hover:border-[color:var(--color-accent-400)] hover:shadow-[var(--shadow-md)] transition-all duration-[200ms]"
          >
            <div class="flex items-center gap-2 mb-3">
              <span v-if="guide.tag" class="text-[11px] font-semibold text-[color:var(--color-accent-600)]">{{ guide.tag }}</span>
              <span v-if="guide.readingTime" class="text-[11px] text-[color:var(--color-ink-subtle)] ui-mono">{{ guide.readingTime }}</span>
            </div>
            <h3 class="ui-display text-[15px] font-semibold tracking-tight text-[color:var(--color-ink)] leading-snug mb-2 group-hover:text-[color:var(--color-accent-600)] transition-colors">
              {{ guide.title }}
            </h3>
            <p class="text-[13px] text-[color:var(--color-ink-muted)] leading-relaxed line-clamp-2 mb-3">{{ guide.description }}</p>
            <!-- Peptides covered -->
            <div v-if="guide.peptides?.length" class="mt-auto flex flex-wrap gap-1.5 pt-3 border-t border-[color:var(--color-hairline-soft)]">
              <span v-for="peptide in guide.peptides.slice(0, 5)" :key="peptide" class="text-[10px] font-medium text-[color:var(--color-ink-subtle)] bg-[color:var(--color-hairline-soft)] px-2 py-0.5">{{ peptide }}</span>
              <span v-if="guide.peptides.length > 5" class="text-[10px] text-[color:var(--color-ink-subtle)]">+{{ guide.peptides.length - 5 }} more</span>
            </div>
          </a>
        </div>

        <div v-else class="py-20 text-center">
          <p class="text-[color:var(--color-ink-subtle)]">No guides found. Try a different search.</p>
        </div>
      </template>
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
  researchPapers: { type: Array, default: () => [] },
  educationalGuides: { type: Array, default: () => [] },
  search: { type: String, default: '' },
  primaryNav: { type: String, default: 'news' },
  filter: { type: String, default: 'all' },
  seo: { type: Object, default: () => ({}) },
})

const searchQuery = ref(props.search || '')
const currentFilter = ref(props.filter || 'all')
const activeNav = ref(props.primaryNav || 'news')

const primaryNavs = [
  { label: 'News & Articles', value: 'news' },
  { label: 'Research Library', value: 'research' },
  { label: 'Educational Guides', value: 'guides' },
]

const newsFilters = [
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

const filteredResearchPapers = computed(() => {
  let result = props.researchPapers || []
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase().trim()
    result = result.filter(p =>
      p.title?.toLowerCase().includes(q) ||
      p.description?.toLowerCase().includes(q) ||
      p.peptide?.toLowerCase().includes(q) ||
      p.tags?.some(t => t.toLowerCase().includes(q))
    )
  }
  return result
})

const filteredEducationalGuides = computed(() => {
  let result = props.educationalGuides || []
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase().trim()
    result = result.filter(g =>
      g.title?.toLowerCase().includes(q) ||
      g.description?.toLowerCase().includes(q) ||
      g.tag?.toLowerCase().includes(q) ||
      g.peptides?.some(p => p.toLowerCase().includes(q))
    )
  }
  return result
})

function selectPrimaryNav(value) {
  activeNav.value = value
  updateURL()
}

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
  if (activeNav.value !== 'news') params.set('nav', activeNav.value)
  if (currentFilter.value !== 'all') params.set('filter', currentFilter.value)
  const qs = params.toString()
  router.visit(`/news${qs ? '?' + qs : ''}`, { preserveState: true, preserveScroll: true, replace: true })
}
</script>
