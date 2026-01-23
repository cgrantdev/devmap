<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow flex flex-col">
    <!-- Top Row: Tag and Reading Time -->
    <div class="flex items-center justify-between mb-4">
      <!-- Tag -->
      <span
        v-if="tag"
        :class="[
          'px-3 py-1 rounded-full text-xs font-medium',
          getTagClasses(tag)
        ]"
      >
        {{ tag }}
      </span>
      <span v-else></span>
      
      <!-- Reading Time -->
      <div class="flex items-center gap-1 text-sm text-gray-500">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clock" aria-hidden="true">
          <circle cx="12" cy="12" r="10"></circle>
          <polyline points="12 6 12 12 16 14"></polyline>
        </svg>
        <span>{{ readingTime }}</span>
      </div>
    </div>

    <!-- Title -->
    <h3 class="text-lg font-bold text-gray-900 mb-2">
      {{ title }}
    </h3>

    <!-- Description -->
    <p class="text-sm text-gray-600 mb-4 flex-grow">
      {{ description }}
    </p>

    <!-- Peptide Tags -->
    <div v-if="peptides && peptides.length > 0" class="flex flex-wrap gap-2 mb-4">
      <span
        v-for="peptide in peptides"
        :key="peptide"
        class="px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-700 font-medium"
      >
        {{ peptide }}
      </span>
    </div>

    <!-- Read Guide Button -->
    <a
      :href="guideUrl"
      class="mt-auto px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center justify-center gap-2"
    >
      Read Guide
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-right" aria-hidden="true">
        <path d="m9 18 6-6-6-6"></path>
      </svg>
    </a>
  </div>
</template>

<script setup>
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
