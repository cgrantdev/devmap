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


      <!-- Peptide Cards Grid -->
      <div v-if="filteredPeptides.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
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
