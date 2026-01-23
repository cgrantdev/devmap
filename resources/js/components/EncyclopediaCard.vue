<template>
  <div
    class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-xl hover:border-blue-500 transition-all text-left group"
    @click="handleClick"
  >
    <!-- Tags Row -->
    <div class="flex items-start justify-between mb-3">
      <!-- Category Tag -->
      <div class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">
        {{ categoryTag }}
      </div>
      <!-- Safety Tag -->
      <div 
        :class="[
          'text-xs px-2 py-1 rounded bg-green-100 text-green-800',
          safetyTag === 'High Safety' 
            ? 'bg-green-100 text-green-800'
            : safetyTag === 'Medium Safety'
            ? 'bg-yello-100 text-yello-800'
            : 'bg-red-100 text-red-800'
        ]"
      >
        {{ safetyTag }}
      </div>
    </div>
    
    <!-- Title and Subtitle -->
    <h3 class="text-xl text-gray-900 mb-1 group-hover:text-blue-600 transition-colors">{{ name }}</h3>
    <p class="text-sm text-gray-600 mb-3">{{ subtitle || name }}</p>

    <!-- Description -->
    <p class="text-sm text-gray-700 mb-4 line-clamp-3">
      {{ description || 'Research peptide information' }}
    </p>

    <!-- Metrics -->
    <div class="flex items-center justify-between text-xs text-gray-500 mb-4">
      <span class="flex items-center gap-1">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up w-3 h-3" aria-hidden="true">
          <path d="M16 7h6v6" />
          <path d="m22 7-8.5 8.5-5-5L2 17" />
        </svg>
        {{ popularity }}% popularity
      </span>
      <span class="flex items-center gap-1">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-award w-3 h-3" aria-hidden="true">
          <path d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526" />
          <circle cx="12" cy="8" r="6"></circle>
        </svg>
        {{ rating }}/5 rating
      </span>
    </div>

    <!-- Top Benefits -->
    <div class="border-t border-gray-200 pt-3">
      <p class="text-xs text-gray-500 mb-2">Top Benefits:</p>
      <ul class="space-y-1">
        <li 
          v-for="(benefit, index) in benefits" 
          :key="index"
          class="flex items-start gap-2 text-xs text-gray-700"
        >
          <div class="w-1 h-1 rounded-full bg-green-500 flex-shrink-0 mt-1.5">•</div>
          <span class="line-clamp-1">{{ benefit }}</span>
        </li>
      </ul>
    </div>

    <!-- Research Studies -->
    <div class="mt-3 pt-3 border-t border-gray-200">
      <div class="text-xs text-blue-600">{{ researchStudies }}+ research studies</div>
    </div>
  </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  id: {
    type: [Number, String],
    required: true
  },
  name: {
    type: String,
    required: true
  },
  subtitle: {
    type: String,
    default: ''
  },
  description: {
    type: String,
    default: ''
  },
  image: {
    type: String,
    default: null
  },
  categoryTag: {
    type: String,
    required: true
  },
  safetyTag: {
    type: String,
    default: 'High Safety'
  },
  popularity: {
    type: [Number, String],
    default: 0
  },
  rating: {
    type: [String, Number],
    default: '4.7'
  },
  benefits: {
    type: Array,
    default: () => []
  },
  researchStudies: {
    type: [Number, String],
    default: 0
  },
  slug: {
    type: String,
    required: true
  }
})

const handleClick = () => {
  router.visit(`/education/${props.slug}`)
}
</script>
