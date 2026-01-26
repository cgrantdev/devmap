<template>
  <div class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-lg transition-shadow cursor-pointer group">
    <div class="flex items-start gap-4">
      <div class="flex-1">
        <!-- Left: Tags -->
        <div class="flex items-center gap-2 mb-2">
          <!-- Peptide Tag -->
          <span
            v-if="peptide"
            class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded"
          >
            {{ peptide }}
          </span>
          <!-- Evidence Level Tag -->
          <span
            v-if="evidenceLevel"
            :class="[
              'text-xs px-2 py-1 rounded',
              evidenceLevel === 'High Evidence'
                ? 'bg-green-100 text-green-800'
                : evidenceLevel === 'Medium Evidence'
                ? 'bg-yellow-100 text-yellow-800'
                : 'bg-gray-100 text-gray-800'
            ]"
          >
            {{ evidenceLevel }}
          </span>
        </div>
        <!-- Title -->
        <h3 class="text-lg text-gray-900 mb-2 group-hover:text-blue-600 transition-colors">
          {{ title }}
        </h3>
        <!-- Description -->
        <p class="text-sm text-gray-600 mb-3">
          {{ description }}
        </p>
        <div class="flex items-center gap-4 text-xs text-gray-500">
          <!-- Source & Date -->
          <span>{{ source }}</span>
          <span> • </span>
          <span>{{ date }}</span>
        </div>        
        <!-- Tags -->
        <div v-if="tags && tags.length > 0" class="flex flex-wrap gap-1 mt-3">
          <span
            v-for="tag in tags"
            :key="tag"
            class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-700"
          >
            {{ tag }}
          </span>
        </div>
      </div>
      <a
        :href="pubmedUrl"
        target="_blank"
        rel="noopener noreferrer"
        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm whitespace-nowrap transition-colors"
      >
        View on PubMed
      </a>
    </div>    
  </div>
</template>

<script setup>
defineProps({
  id: {
    type: [Number, String],
    required: true
  },
  peptide: {
    type: String,
    default: null
  },
  evidenceLevel: {
    type: String,
    default: null
  },
  title: {
    type: String,
    required: true
  },
  description: {
    type: String,
    required: true
  },
  source: {
    type: String,
    required: true
  },
  date: {
    type: String,
    required: true
  },
  tags: {
    type: Array,
    default: () => []
  },
  pubmedUrl: {
    type: String,
    default: '#'
  }
})
</script>
