<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
    <div class="flex items-start justify-between gap-4 mb-4">
      <!-- Left: Tags -->
      <div class="flex flex-wrap gap-2">
        <!-- Peptide Tag -->
        <span
          v-if="peptide"
          class="px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-700"
        >
          {{ peptide }}
        </span>
        <!-- Evidence Level Tag -->
        <span
          v-if="evidenceLevel"
          :class="[
            'px-3 py-1 rounded-full text-sm font-medium',
            evidenceLevel === 'High Evidence'
              ? 'bg-green-100 text-green-700'
              : evidenceLevel === 'Medium Evidence'
              ? 'bg-yellow-100 text-yellow-700'
              : 'bg-gray-100 text-gray-700'
          ]"
        >
          {{ evidenceLevel }}
        </span>
      </div>
    </div>

    <!-- Title -->
    <h3 class="text-lg font-bold text-gray-900 mb-2">
      {{ title }}
    </h3>

    <!-- Description -->
    <p class="text-sm text-gray-600 mb-4">
      {{ description }}
    </p>

    <!-- Source & Date -->
    <p class="text-xs text-gray-500 mb-4">
      {{ source }} • {{ date }}
    </p>

    <!-- Tags -->
    <div v-if="tags && tags.length > 0" class="flex flex-wrap gap-2 mb-4">
      <span
        v-for="tag in tags"
        :key="tag"
        class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-700"
      >
        {{ tag }}
      </span>
    </div>

    <!-- View on PubMed Button -->
    <div class="flex justify-end">
      <a
        :href="pubmedUrl"
        target="_blank"
        rel="noopener noreferrer"
        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors"
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
