<template>
  <FrontLayout>
    <!-- Header Section -->
    <section class="bg-gradient-to-br from-slate-700 to-slate-600 text-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <!-- Icon and Title -->
          <div class="flex items-center gap-3 mb-4">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-pill w-12 h-12" aria-hidden="true">
            <path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z"></path>
            <path d="m8.5 8.5 7 7"></path>
          </svg>
          <h1 class="text-5xl">Peptide Encyclopedia</h1>
        </div>
        
        <!-- Subtitle -->
        <p class="text-xl text-slate-100 mb-8 max-w-3xl">
          Your comprehensive guide to peptides. Learn about benefits, dosing protocols, safety information, and real user experiences for every peptide.
        </p>

        <!-- Search Bar -->
        <div class="relative max-w-2xl">
          <input
            v-model="searchQuery"
            @input="handleSearch"
            type="text"
            placeholder="Search peptides by name or description..."
            class="w-full pl-12 pr-4 py-4 bg-white rounded-lg text-slate-900 border border-slate-200 shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-transparent"
          />
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" aria-hidden="true">
            <path d="m21 21-4.34-4.34"></path>
            <circle cx="11" cy="11" r="8"></circle>
          </svg>
        </div>
      </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
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

      <div v-if="encyclopediaEntries && encyclopediaEntries.length > 0" class="mb-12 bg-white border border-slate-200 rounded-lg p-6">
        <div class="flex items-center gap-3 mb-6">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class= "lucide lucide-book-open w-6 h-6 text-slate-700" aria-hidden="true">
            <path d="M12 7v14"></path>
            <path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"></path>
          </svg>
          <h2 class="text-2xl text-slate-900">Featured Research Articles</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <button 
            v-for="entry in encyclopediaEntries"
            :key="entry.id"
            @click="router.visit(`/encyclopedia/article/${entry.slug}`)"
            class="group bg-slate-50 border border-slate-200 rounded-lg p-5 hover:border-slate-400 hover:shadow-lg transition-all text-left"
          >
            <div class="flex items-start justify-between mb-3">
              <div class="bg-slate-700 text-white text-xs px-3 py-1 rounded-full">{{ 'Research Review' }}</div>
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-external-link w-4 h-4 text-slate-400 group-hover:text-slate-700 transition-colors" aria-hidden="true">
                <path d="M15 3h6v6"></path>
                <path d="M10 14 21 3"></path>
                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-slate-900 mb-2 group-hover:text-slate-700 transition-colors">{{ entry.research_title || entry.title }}</h3>
            <p class="text-sm text-slate-600 mb-4 line-clamp-2">
              {{ entry.research_outline || entry.description || entry.subtitle || 'Read the full research article for comprehensive information.' }}
            </p>
            <div class="flex items-center gap-4 text-xs text-slate-500">
              <span class="flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class= "lucide lucide-book-open w-3 h-3" aria-hidden="true">
                  <path d="M12 7v14"></path>
                  <path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"></path>
                </svg>
                Full Article
              </span>
              <span v-if="entry.updated_at">Updated {{ entry.updated_at }}</span>
            </div>
          </button>
        </div>
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
  </FrontLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import FrontLayout from '../Layouts/FrontLayout.vue'
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
