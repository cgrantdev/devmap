<template>
  <div class="bg-white border-2 rounded-lg p-6 hover:shadow-xl transition-all cursor-pointer group border-purple-300 bg-purple-50/30">
    <!-- Top Row: Tag and Reading Time -->
    <div class="flex items-start justify-between mb-3">
      <!-- Tag -->
      <span
        v-if="tag"
        :class="[
          'text-xs px-2 py-1 rounded bg-green-100 text-green-800',
          getTagClasses(tag)
        ]"
      >
        {{ tag }}
      </span>
      <span class="text-xs text-gray-500 flex items-center gap-1">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clock w-3 h-3" aria-hidden="true">
          <circle cx="12" cy="12" r="10"></circle>
          <path d="M12 6v6l4 2"></path>
        </svg>
        {{ readingTime }}
      </span>    
    </div>     

    <!-- Title -->
    <h3 class="text-xl text-gray-900 mb-2 group-hover:text-purple-600 transition-colors">
      {{ title }}
    </h3>

    <!-- Description -->
    <p class="text-sm text-gray-600 mb-4">
      {{ description }}
    </p>

    <!-- Peptide Tags -->
    <div v-if="peptides && peptides.length > 0" class="mb-4">
      <p class="text-xs text-gray-500 mb-2">Covers:</p>
      <div class="flex flex-wrap gap-1">
        <span
          v-for="peptide in peptides"
          :key="peptide"
          class="text-xs bg-blue-50 text-blue-700 px-2 py-1 rounded border border-blue-200"
        >
          {{ peptide }}
        </span>
      </div>
    </div>

    <!-- Read Guide Button -->
    <button
      @click="router.visit(guideUrl)"
      class="w-full bg-purple-600 hover:bg-purple-700 text-white py-2 rounded flex items-center justify-center gap-2 transition-colors"
    >
      Read Guide
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-right w-4 h-4" aria-hidden="true">
        <path d="m9 18 6-6-6-6"></path>
      </svg>
    </button>
  </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3'

defineProps({
  id: {
    type: [Number, String],
    required: true
  },
  tag: {
    type: String,
    default: null
  },
  readingTime: {
    type: String,
    required: true
  },
  title: {
    type: String,
    required: true
  },
  description: {
    type: String,
    required: true
  },
  peptides: {
    type: Array,
    default: () => []
  },
  guideUrl: {
    type: String,
    default: '#'
  }
})

const getTagClasses = (tag) => {
  const tagLower = tag.toLowerCase()
  if (tagLower === 'beginner') {
    return 'bg-green-100 text-green-700'
  } else if (tagLower === 'stacking') {
    return 'bg-purple-100 text-purple-700'
  } else if (tagLower === 'dosage') {
    return 'bg-blue-100 text-blue-700'
  } else if (tagLower === 'advanced') {
    return 'bg-red-100 text-red-700'
  }
  return 'bg-gray-100 text-gray-700'
}
</script>
