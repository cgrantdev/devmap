<template>
  <FrontLayout>
    <!-- Header Section with Gradient -->
    <section class="bg-gradient-to-br from-blue-600 to-purple-700 text-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <!-- Title and Tagline -->
        <h1 class="text-5xl mb-4">Peptide Knowledge Center</h1>
        <p class="text-xl text-blue-100 mb-8">
          Latest research, industry news, and expert guides from the peptide community
        </p>

        <!-- Search Bar -->
        <div class="relative max-w-2xl">
          <input
            v-model="searchQuery"
            @input="handleSearch"
            type="text"
            placeholder="Search articles, research, and guides..."
            class="w-full pl-12 pr-4 py-4 bg-white rounded-lg text-gray-900 border border-gray-200 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-300 focus:border-transparent"
          />
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" aria-hidden="true">
            <path d="m21 21-4.34-4.34"></path>
            <circle cx="11" cy="11" r="8"></circle>
          </svg>
        </div>
      </div>
    </section>

    <!-- Primary Navigation -->
    <section class="bg-white border-b border-gray-200 sticky top-0 z-10">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="flex gap-8">
          <button
            v-for="nav in primaryNavs"
            :key="nav.value"
            @click="selectPrimaryNav(nav.value)"
            :class="[
              'py-4 border-b-2 transition-colors flex items-center gap-2',
              primaryNav === nav.value
                ? 'border-blue-600 text-blue-600'
                : 'border-transparent text-gray-600 hover:text-gray-900'
            ]"
          >
            <component :is="nav.icon" />
            <span>{{ nav.label }}</span>
          </button>
        </nav>
      </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Research Library Section -->
      <div v-if="primaryNav === 'research'">
        <!-- Research Library Header -->
        <div class="mb-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
          <h3 class="text-lg text-blue-900 mb-2 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="llucide lucide-award w-5 h-5" aria-hidden="true">
              <path d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526"></path>
              <circle cx="12" cy="8" r="6"></circle>
            </svg>
            Research Library
          </h3>
          <p class="text-blue-800">
            Curated collection of clinical studies and research papers on peptide therapies. All studies are linked to their original sources on PubMed.
          </p>
        </div>

        <!-- Research Papers -->
        <div v-if="filteredResearchPapers.length > 0" class="grid grid-cols-1 gap-4">
          <ResearchLibraryCard
            v-for="paper in filteredResearchPapers"
            :key="paper.id"
            :id="paper.id"
            :peptide="paper.peptide"
            :evidence-level="paper.evidenceLevel"
            :title="paper.title"
            :description="paper.description"
            :source="paper.source"
            :date="paper.date"
            :tags="paper.tags"
            :pubmed-url="paper.pubmedUrl"
          />
        </div>

        <div v-else class="text-center py-20">
          <p class="text-gray-500 text-lg">No research papers found. Try a different search.</p>
        </div>
      </div>

      <!-- Educational Guides Section -->
      <div v-else-if="primaryNav === 'guides'">
        <!-- Educational Guides Header -->
        <div class="mb-8 bg-purple-50 border border-purple-200 rounded-lg p-6">
          <h3 class="text-lg text-purple-900 mb-2 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-book-open w-5 h-5" aria-hidden="true">
              <path d="M12 7v14"></path>
              <path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"></path>
            </svg>
            Educational Guides
          </h3>
          <p class="text-purple-800">
            Comprehensive guides written by experts to help you understand peptides, create effective protocols, and use them safely.
          </p>          
        </div>

        <!-- Educational Guides Grid -->
        <div v-if="filteredEducationalGuides.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <EducationalGuideCard
            v-for="guide in filteredEducationalGuides"
            :key="guide.id"
            :id="guide.id"
            :tag="guide.tag"
            :reading-time="guide.readingTime"
            :title="guide.title"
            :description="guide.description"
            :peptides="guide.peptides"
            :guide-url="guide.guideUrl"
          />
        </div>

        <div v-else class="text-center py-20">
          <p class="text-gray-500 text-lg">No educational guides found. Try a different search.</p>
        </div>
      </div>

      <!-- News & Articles Section -->
      <div v-else>
        <!-- Secondary Navigation/Filters -->
        <div class="flex gap-2 mb-8 overflow-x-auto pb-2">
          <div class="flex flex-wrap items-center gap-3">
            <button
              v-for="filter in filters"
              :key="filter.value"
              @click="selectFilter(filter.value)"
              :class="[
                'px-4 py-2 rounded-lg whitespace-nowrap transition-colors border flex items-center gap-2',
                currentFilter === filter.value
                  ? 'bg-blue-600 text-white border-blue-600'
                  : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
              ]"
            >
              <component :is="filter.icon" />
              <span>{{ filter.label }}</span>
            </button>
          </div>
        </div>

        
        <!-- Featured Stories -->
        <div v-if="filteredFeaturedBlogs.length > 0" class="mb-12">
          <h2 class="text-2xl text-gray-900 mb-6 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up w-6 h-6 text-blue-600" aria-hidden="true">
              <path d="M16 7h6v6"></path>
              <path d="m22 7-8.5 8.5-5-5L2 17"></path>
            </svg>
            Featured Stories
          </h2>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <KnowledgeCenterCard
              v-for="blog in filteredFeaturedBlogs"
              :key="blog.id"
              :id="blog.id"
              :title="blog.title"
              :description="blog.description"
              :image="blog.image"
              :date="blog.date"
              :read-time="blog.readTime"
              :category-tag="blog.categoryTag"
              :tags="blog.tags"
              :author="blog.author"
              :author-job="blog.authorJob"
              :slug="blog.slug"
            />
          </div>
        </div>

      <!-- Latest Articles -->
      <div v-if="filteredLatestBlogs.length > 0">
        <h2 class="text-2xl text-gray-900 mb-6">Latest Articles</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <KnowledgeCenterCard
            v-for="blog in filteredLatestBlogs"
            :key="blog.id"
            :id="blog.id"
            :title="blog.title"
            :description="blog.description"
            :image="blog.image"
            :date="blog.date"
            :read-time="blog.readTime"
            :category-tag="blog.categoryTag"
            :tags="blog.tags"
            :author="blog.author"
            :author-job="blog.authorJob"
            :slug="blog.slug"
          />
        </div>
      </div>

        <div v-if="featuredBlogs.length === 0 && filteredLatestBlogs.length === 0" class="text-center py-20">
          <p class="text-gray-500 text-lg">No articles found. Try a different search or filter.</p>
        </div>
      </div>
    </section>
  </FrontLayout>
</template>

<script setup>
import { ref, computed, h } from 'vue'
import { router } from '@inertiajs/vue3'
import FrontLayout from '../Layouts/FrontLayout.vue'
import KnowledgeCenterCard from '@/components/KnowledgeCenterCard.vue'
import ResearchLibraryCard from '@/components/ResearchLibraryCard.vue'
import EducationalGuideCard from '@/components/EducationalGuideCard.vue'

const props = defineProps({
  featuredBlogs: {
    type: Array,
    default: () => []
  },
  latestBlogs: {
    type: Array,
    default: () => []
  },
  researchPapers: {
    type: Array,
    default: () => []
  },
  educationalGuides: {
    type: Array,
    default: () => []
  },
  search: {
    type: String,
    default: ''
  },
  primaryNav: {
    type: String,
    default: 'news'
  },
  filter: {
    type: String,
    default: 'all'
  }
})

const searchQuery = ref(props.search || '')
const currentFilter = ref(props.filter || 'all')
const primaryNav = ref(props.primaryNav || 'news')

// Primary navigation items
const primaryNavs = [
  {
    label: 'News & Articles',
    value: 'news',
    icon: () => h('span', { class: 'text-lg' }, '📰')
  },
  {
    label: 'Research Library',
    value: 'research',
    icon: () => h('span', { class: 'text-lg' }, '🔬')
  },
  {
    label: 'Educational Guides',
    value: 'guides',
    icon: () => h('span', { class: 'text-lg' }, '📚')
  }
]

// Filter buttons
const filters = [
  { label: 'All News', value: 'all', icon: () => h('span', { class: 'text-base' }, '📰') },
  { label: 'Research', value: 'research', icon: () => h('span', { class: 'text-base' }, '🔬') },
  { label: 'Industry', value: 'industry', icon: () => h('span', { class: 'text-base' }, '📊') },
  { label: 'Regulation', value: 'regulation', icon: () => h('span', { class: 'text-base' }, '⚖️') },
  { label: 'Guides', value: 'guides', icon: () => h('span', { class: 'text-base' }, '📚') },
  { label: 'Community', value: 'community', icon: () => h('span', { class: 'text-base' }, '💬') }
]

// Map filter values to categoryTag values used on blogs
const filterToCategoryTag = {
  research: 'Research',
  industry: 'Industry',
  regulation: 'Regulation',
  guides: 'Guides',
  community: 'Community'
}

const currentCategoryTag = computed(() => {
  if (currentFilter.value === 'all') return null
  return filterToCategoryTag[currentFilter.value] || null
})

// Filter latest blogs by current filter (categoryTag)
const filteredLatestBlogs = computed(() => {
  let result = props.latestBlogs || []
  
  if (currentCategoryTag.value) {
    result = result.filter(blog => blog.categoryTag === currentCategoryTag.value)
  }
  
  return result
})

// Filter featured blogs by current filter (categoryTag)
const filteredFeaturedBlogs = computed(() => {
  let result = props.featuredBlogs || []

  if (currentCategoryTag.value) {
    result = result.filter(blog => blog.categoryTag === currentCategoryTag.value)
  }
  
  return result
})

// Filter research papers by search
const filteredResearchPapers = computed(() => {
  let result = props.researchPapers || []
  
  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase().trim()
    result = result.filter(paper => 
      paper.title?.toLowerCase().includes(query) ||
      paper.description?.toLowerCase().includes(query) ||
      (paper.peptide && paper.peptide.toLowerCase().includes(query)) ||
      (paper.tags && paper.tags.some(tag => tag.toLowerCase().includes(query)))
    )
  }
  
  return result
})

// Filter educational guides by search
const filteredEducationalGuides = computed(() => {
  let result = props.educationalGuides || []
  
  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase().trim()
    result = result.filter(guide => 
      guide.title.toLowerCase().includes(query) ||
      guide.description.toLowerCase().includes(query) ||
      (guide.tag && guide.tag.toLowerCase().includes(query)) ||
      (guide.peptides && guide.peptides.some(p => p.toLowerCase().includes(query)))
    )
  }
  
  return result
})

const selectPrimaryNav = (value) => {
  primaryNav.value = value
  updateURL()
}

const selectFilter = (value) => {
  currentFilter.value = value
  updateURL()
}

const handleSearch = () => {
  updateURL()
}

const updateURL = () => {
  const params = new URLSearchParams()
  if (searchQuery.value.trim()) {
    params.set('search', searchQuery.value.trim())
  }
  if (primaryNav.value !== 'news') {
    params.set('nav', primaryNav.value)
  }
  if (currentFilter.value !== 'all') {
    params.set('filter', currentFilter.value)
  }
  
  const queryString = params.toString()
  router.visit(`/news${queryString ? '?' + queryString : ''}`, {
    preserveState: true,
    preserveScroll: true,
    replace: true
  })
}
</script>
