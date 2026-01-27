<template>
  <div
    class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-xl transition-all cursor-pointer group ring-2 ring-blue-500 flex flex-col h-full"
    @click="handleClick"
  >
    <!-- Image -->
    <div class="aspect-video overflow-hidden flex-shrink-0">
      <img
        v-if="image && !hasError"
        :src="image"
        :alt="title"
        class="w-full h-full object-cover group-hover:scale-105 transition-transform"
        loading="lazy"
        @error="onError"
      />
      <div v-else class="w-full h-full flex items-center justify-center bg-gray-200">
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-12 h-12 text-gray-400">
          <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
          <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
        </svg>
      </div>
    </div>

    <!-- Content -->
    <div class="p-6 flex flex-col flex-1 min-h-0">
      <!-- Category Tag and Date -->
      <div class="flex items-center gap-2 mb-3 h-6 flex-shrink-0">
        <span
          :class="[
            'text-xs px-2 py-1 rounded-full',
            getCategoryTagClass(categoryTag)
          ]"
        >
          {{ categoryTag }}
        </span>
        <span class="text-xs text-gray-500 flex items-center gap-1">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar w-3 h-3" aria-hidden="true">
            <path d="M8 2v4"></path>
            <path d="M16 2v4"></path>
            <rect width="18" height="18" x="3" y="4" rx="2"></rect>
            <path d="M3 10h18"></path>
          </svg>
          {{ date }}
        </span>
        <span class="text-xs text-gray-500 flex items-center gap-1">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clock w-3 h-3" aria-hidden="true">
            <path d="M12 6v6l4 2"></path>
            <circle cx="12" cy="12" r="10"></circle>
          </svg>
          {{ readTime }}
        </span>
      </div>

      <!-- Title -->
      <h3 class="text-lg text-gray-900 mb-2 group-hover:text-blue-600 transition-colors line-clamp-2 h-14 flex items-center flex-shrink-0">
        <span class="line-clamp-2">{{ title }}</span>
      </h3>

      <!-- Description -->
      <div class="flex-grow mb-4">
        <p class="text-sm text-gray-600 line-clamp-3">
        {{ description }}
      </p>
      </div>

      <!-- Author -->
      <div class="flex items-center justify-between h-6 mb-3 flex-shrink-0">
        <div class="text-xs text-gray-500">
          By {{ author }}
          <span class="ml-1">• Regulatory Expert</span>
        </div>
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-right w-5 h-5 text-gray-400 group-hover:text-blue-600 transition-colors flex-shrink-0" aria-hidden="true">
          <path d="m9 18 6-6-6-6"></path>
        </svg>
      </div>

      <!-- Tags -->
      <div class="flex flex-wrap gap-1 min-h-[1.5rem] flex-shrink-0">
        <span
          v-for="tag in tags"
          :key="tag"
          class="text-xs text-gray-600 bg-gray-100 px-2 py-0.5 rounded"
        >
          {{ tag }}
        </span>
      </div>

      
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  id: {
    type: [Number, String],
    required: true
  },
  title: {
    type: String,
    required: true
  },
  description: {
    type: String,
    default: ''
  },
  image: {
    type: String,
    default: null
  },
  date: {
    type: String,
    default: ''
  },
  readTime: {
    type: String,
    default: '5 min'
  },
  categoryTag: {
    type: String,
    default: 'Research'
  },
  tags: {
    type: Array,
    default: () => []
  },
  author: {
    type: String,
    default: 'PeptideMap Editorial'
  },
  slug: {
    type: String,
    required: true
  }
})

const hasError = ref(false)

const getCategoryTagClass = (category) => {
  const classes = {
    'Regulation': 'bg-red-100 text-red-800',
    'Research': 'bg-blue-100 text-blue-800',
    'Industry': 'bg-green-100 text-green-800',
    'Guides': 'bg-purple-100 text-purple-800',
    'Community': 'bg-yellow-100 text-yellow-800',
  }
  return classes[category] || 'bg-gray-100 text-gray-800'
}

const handleClick = () => {
  router.visit(`/blog/${props.slug}`)
}

const onError = () => {
  hasError.value = true
}
</script>
