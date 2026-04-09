<template>
  <ModernLayout>
    <!-- Header Section -->
    <section class="relative overflow-hidden border-b border-[color:var(--color-hairline)]">
      <div class="absolute inset-0 bg-gradient-to-br from-[#0F172A] to-[#4F46E5]" />
      <div class="absolute inset-0 opacity-[0.03]" :style="{ backgroundImage: 'linear-gradient(white 1px, transparent 1px), linear-gradient(90deg, white 1px, transparent 1px)', backgroundSize: '40px 40px' }" />
      <div class="absolute top-0 right-0 w-[500px] h-[500px] rounded-full bg-[color:var(--color-accent-500)] opacity-[0.15] blur-[100px] pointer-events-none" />
      <div class="relative max-w-[1280px] mx-auto px-6 lg:px-10 pt-10 pb-14">
        <div class="text-[11px] uppercase tracking-[0.12em] font-semibold text-[color:var(--color-accent-400)] mb-3">Reference</div>
        <h1 class="ui-display text-4xl md:text-5xl font-semibold tracking-[-0.02em] text-white mb-4">Peptide Encyclopedia</h1>
        <p class="text-lg text-white/70 leading-relaxed max-w-2xl mb-8">
          Your comprehensive guide to peptides. Learn about benefits, dosing protocols, safety information, and real user experiences.
        </p>

        <!-- Search Bar -->
        <div class="relative max-w-2xl">
          <input
            v-model="searchQuery"
            @input="handleSearch"
            type="text"
            placeholder="Search peptides by name or description..."
            class="w-full pl-12 pr-4 py-3.5 bg-white/95 backdrop-blur rounded-[12px] text-[color:var(--color-ink)] border border-white/20 shadow-lg focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)] text-[15px]"
          />
          <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-[color:var(--color-ink-subtle)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8" />
            <path d="m21 21-4.35-4.35" />
          </svg>
        </div>
      </div>
    </section>

    <section class="max-w-[1280px] mx-auto px-6 lg:px-10 py-10">
      <!-- Information Banner -->
      <div class="mb-8 bg-slate-50 border border-slate-200 rounded-lg p-6">
        <div class="flex items-start gap-3">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-alert w-5 h-5 text-slate-600 flex-shrink-0 mt-0.5" aria-hidden="true">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" x2="12" y1="8" y2="12"></line>
            <line x1="12" x2="12.01" y1="16" y2="16"></line>
          </svg>
          <div>
            <h3 class="text-slate-900 mb-1">Evidence-Based Information</h3>
            <p class="text-sm text-slate-700">
              All peptide information is backed by scientific research and clinical studies. Each page includes comprehensive data and real user experiences to help you make informed decisions.
            </p>
          </div>
        </div>
      </div>

      <!-- Filter Bar -->
      <div class="flex gap-2 mb-8 overflow-x-auto pb-2">
          <button
            v-for="filter in filters"
            :key="filter.value"
            @click="selectFilter(filter.value)"
            :class="[
              'px-4 py-2 rounded-lg whitespace-nowrap transition-colors border',
              selectedFilter === filter.value
                ? 'bg-slate-700 text-white border-slate-700'
                : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'
            ]"
          >
            {{ filter.label }}
          </button>
      </div>

      <!-- Results Count -->
      <div class="mb-4">
        <p class="text-gray-600">
          {{ filteredPeptides.length }} {{ filteredPeptides.length === 1 ? 'peptide' : 'peptides' }} found
        </p>
      </div>
      
      <!-- Peptide Cards Grid -->
      <div v-if="filteredPeptides.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <EncyclopediaCard
          v-for="peptide in filteredPeptides"
          :key="peptide.id"
          :id="peptide.id"
          :name="peptide.name"
          :subtitle="peptide.subtitle"
          :description="peptide.description"
          :image="peptide.image"
          :category-tag="peptide.categoryTag"
          :safety-tag="peptide.safetyTag"
          :popularity="peptide.popularity"
          :rating="peptide.rating"
          :benefits="peptide.benefits"
          :research-studies="peptide.researchStudies"
          :slug="peptide.slug"
        />
      </div>

      <div v-else class="text-center py-20">
        <p class="text-gray-500 text-lg">No peptides found matching your search.</p>
      </div>
    </section>
  </ModernLayout>
</template>

<script setup>
import { ref, computed, onMounted, watchEffect } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import ModernLayout from '@/Pages/Layouts/ModernLayout.vue'
import EncyclopediaCard from '@/components/EncyclopediaCard.vue'

const props = defineProps({
  peptides: {
    type: Array,
    default: () => []
  },
  encyclopediaEntries: {
    type: Array,
    default: () => []
  },
  search: {
    type: String,
    default: ''
  },
  category: {
    type: String,
    default: 'all'
  },
  seo: {
    type: Object,
    default: () => ({
      title: null,
      description: null,
      og_title: null,
      og_description: null,
      og_image: null,
      image: null,
      url: null,
    })
  }
})

const page = usePage()

// Computed values for reactive SEO updates
const title = computed(() => {
  const baseTitle = props.seo?.title || 'Peptide Encyclopedia - Comprehensive Research Guide'
  const siteName = page.props.site_name || 'Peptidemap'
  return `${baseTitle} - ${siteName}`
})

const description = computed(() => {
  return props.seo?.description || 'Explore our comprehensive peptide encyclopedia. Detailed information on research peptides including benefits, dosing, safety, and research applications.'
})

const url = computed(() => {
  return props.seo?.url || page.url
})

const ogTitle = computed(() => {
  return props.seo?.og_title || title.value
})

const ogDescription = computed(() => {
  return props.seo?.og_description || description.value
})

const ogImage = computed(() => {
  return props.seo?.og_image || props.seo?.image || null
})

// Watch for SEO changes and update document title and meta tags immediately
watchEffect(() => {
  // Update document title
  document.title = title.value
  
  // Update meta description
  let metaDescription = document.querySelector('meta[name="description"]')
  if (!metaDescription) {
    metaDescription = document.createElement('meta')
    metaDescription.setAttribute('name', 'description')
    document.head.appendChild(metaDescription)
  }
  metaDescription.setAttribute('content', description.value)
  
  // Update Open Graph tags
  const updateMetaTag = (property, content) => {
    let meta = document.querySelector(`meta[property="${property}"]`)
    if (!meta) {
      meta = document.createElement('meta')
      meta.setAttribute('property', property)
      document.head.appendChild(meta)
    }
    meta.setAttribute('content', content)
  }
  
  updateMetaTag('og:title', ogTitle.value)
  updateMetaTag('og:description', ogDescription.value)
  updateMetaTag('og:url', url.value)
  if (ogImage.value) {
    updateMetaTag('og:image', ogImage.value)
  }
})

const searchQuery = ref(props.search || '')
const selectedFilter = ref(props.category || 'all')

const filters = [
  { label: 'All Peptides', value: 'all' },
  { label: 'Healing & Recovery', value: 'Healing & Recovery' },
  { label: 'Growth & Recovery', value: 'Growth & Recovery' },
  { label: 'Performance', value: 'Performance' },
  { label: 'Anti-Aging', value: 'Anti-Aging' }
]

// Filter peptides
const filteredPeptides = computed(() => {
  let result = props.peptides || []

  // Apply category filter
  if (selectedFilter.value !== 'all') {
    result = result.filter(p => p.categoryTag === selectedFilter.value)
    console.log(props.peptides)
  }

  // Apply search filter
  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase().trim()
    result = result.filter(p => 
      p.name.toLowerCase().includes(query) ||
      (p.subtitle && p.subtitle.toLowerCase().includes(query)) ||
      (p.description && p.description.toLowerCase().includes(query))
    )
  }

  return result
})

const selectFilter = (value) => {
  selectedFilter.value = value
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
  if (selectedFilter.value !== 'all') {
    params.set('category', selectedFilter.value)
  }
  
  const queryString = params.toString()
  router.visit(`/encyclopedia${queryString ? '?' + queryString : ''}`, {
    preserveState: true,
    preserveScroll: true,
    replace: true
  })
}
</script>
