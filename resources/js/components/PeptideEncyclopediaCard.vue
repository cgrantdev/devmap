<template>
  <div
    class="bg-white border-2 border-slate-200 rounded-lg overflow-hidden hover:shadow-xl hover:border-slate-400 transition-all cursor-pointer group"
    @click="handleClick"
  >
    <!-- Vial/Image Section -->
    <div class="bg-gray-50 p-6 border-b border-slate-200 flex items-center justify-center">
      <img
        v-if="hasImage"
        :src="image"
        :alt="name"
        class="w-full h-full object-contain flex items-center justtify-center select-none"
        loading="lazy"
        @error="onError"
      />
      <div v-else class="w-full h-full flex items-center justtify-center">
        <!-- Vial Illustration -->
        <svg
          class="w-full h-full"
          viewBox="0 0 200 200"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
        >
          <rect x="70" y="30" width="60" height="15" fill="#687280" stroke="#374151" stroke-width="2"></rect>
          <rect x="75" y="45" width="50" height="10" fill="#9CA3AF" stroke="#374151" stroke-width="2"></rect>
          <rect x="60" y="55" width="80" height="110" rx="8" fill= "#E5E7EB" stroke="#374151" stroke-width="2"></rect>
          <rect x="65" y="100" width="70" height="60" rx="6" fill="#3B82F6" fill-opacity="0.3"></rect>
          <rect x="70" y="80" width="60" height="30" fill="white" stroke="#9CA3AF" stroke-width="1"></rect>
          <line x1="75" y1="88" x2="125" y2="88" stroke="#D1D5DB" stroke-width="2"></line>
          <line x1="75" y1="95" x2="115" y2="95" stroke="#D1D5DB" stroke-width="2"></line>
          <line x1="75" y1="102" x2="120" y2="102" stroke="#D1D5DB" stroke-width="2"></line> 
          <line x1="145" y1="70" x2="150" y2="70" stroke="#9CA3AF" stroke-width="1"></line>
          <line x1="145" y1="90" x2="150" y2="90" stroke="#9CA3AF" stroke-width="1"></line>
          <line x1="145" y1="110" x2= "150" y2="110" stroke="#9CA3AF" stroke-width="1"></line>
          <line x1="145" y1="130" x2= "150" y2="130" stroke="#9CA3AF" stroke-width="1"></line>
          <line x1="145" y1="150" x2= "150" y2="150" stroke="#9CA3AF" stroke-width="1"></line>
        </svg>
      </div>
    </div>

    <!-- Content Section -->
    <div class="p-6">
      <!-- Name and ID -->
      <div class="flex items-start justify-between mb-2">
        <h3 class="text-lg text-gray-900 group-hover:text-slate-700 transition-colors">
          {{ name }}
        </h3>
        <div class="text-xs text-slate-600 bg-slate-100 px-2 py-1 rounded">
          #{{ id }}
        </div>
      </div>      

      <!-- Description (Fixed height) -->
      <p class="text-sm text-gray-600 mb-3">
        Peptide full name
      </p>

      <!-- Category Tag (Fixed height) -->
      <span class="inline-block bg-slate-700 text-white text-xs px-3 py-1 rounded-full mb-4">
        {{ categoryTag }}
      </span>     
      

      <!-- Learn More Link (Fixed position at bottom) -->
      <div class="pt-3 border-t border-gray-200 flex items-center justify-between">
        <span class="text-slate-700 text-sm group-hover:underline">Learn More</span>
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-right w-4 h-4 text-slate-500 group-hover:text-slate-700 transition-colors" aria-hidden="true">
          <path d="m9 18 6-6-6-6"></path>
        </svg>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  id: {
    type: [Number, String],
    required: true
  },
  name: {
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
  slug: {
    type: String,
    required: true
  },
  categoryTag: {
    type: String,
    default: ''
  }
})

const hasError = ref(false)

// Check if image exists and is valid
const hasImage = computed(() => {
  return props.image && props.image.trim() !== '' && !hasError.value
})

const handleClick = () => {
  router.visit(`/encyclopedia/${props.slug}`)
}

const onError = (event) => {
  hasError.value = true
  console.warn('Failed to load image:', props.image, event)
}
</script>
