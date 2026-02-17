<template>
  <div
    class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-xl hover:border-blue-500 transition-all text-left group"
    @click="handleClick"
  >    
    <!-- Vial/Image Section -->
    <div class="aspect-square bg-gray-50 p-0 flex items-center justify-center mb-6">
      <!-- <div class="w-full h-full flex items-center justify-center"> -->
        <img
          v-if="hasImage"
          :src="image"
          :alt="name"
          class="w-full h-full object-contain select-none flex items-center justify-center"
          loading="lazy"
          @error="onError"
        />
        <div v-else class="w-full h-full flex items-center justify-center">
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
      <!-- </div> -->
    </div>

    <!-- Title and Subtitle -->
    <h3 class="pt-6 border-t border-slate-200 text-xl text-gray-900 mb-1 group-hover:text-blue-600 transition-colors">
      {{ name }}
    </h3>
    <!-- Category Tag -->
    <div class="text-slate-600 text-xs italic justify-between mb-6">
      {{ categoryTag }}
    </div>

    <!-- Learn More Link (Fixed position at bottom) -->
    <div class="pt-3 border-t border-slate-200 flex items-center justify-between">
      <span class="text-gray-900 text-xs italic underline">Learn More</span>
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-right w-4 h-4 text-slate-500 group-hover:text-slate-700 transition-colors" aria-hidden="true">
        <path d="m9 18 6-6-6-6"></path>
      </svg>
    </div>
    <!-- <p class="text-sm text-gray-600 mb-3">{{ subtitle || name }}</p> -->

    <!-- Description - grows to fill space -->
    <!-- <div class="flex-grow mb-4">
      <p class="text-sm text-gray-700 line-clamp-3">
      {{ description || 'Research peptide information' }}
    </p>
    </div> -->

    <!-- Metrics - fixed height -->
    <!-- <div class="flex items-center justify-between text-xs text-gray-500 mb-4 h-5">
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
    </div> -->

    <!-- Key Effects - fixed height -->
    <!-- <div class="border-t border-gray-200 pt-3 min-h-[80px]">
      <p class="text-xs text-gray-900 mb-2">Research Applications:</p>
      <ul class="space-y-1">
        <li 
          v-for="(benefit, index) in limitedBenefits" 
          :key="index"
          class="flex items-start gap-2 text-xs text-gray-700"
        >
          <div class="w-1 h-1 rounded-full bg-green-500 flex-shrink-0 mt-1.5"></div>
          <span class="line-clamp-1">{{ benefit }}</span>
        </li>
      </ul>
    </div> -->

    <!-- Research Studies - fixed height -->
    <!-- <div class="mt-3 pt-3 border-t border-gray-200 h-8">
      <div class="text-xs text-blue-600">{{ researchStudies }}+ research studies</div>
    </div> -->
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
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
  router.visit(`/encyclopedia/${props.slug}`)
}

const hasError = ref(false)

// Check if image exists and is valid
const hasImage = computed(() => {
  return props.image && props.image.trim() !== '' && !hasError.value
})

// Limit benefits to maximum 3 items
const limitedBenefits = computed(() => {
  return props.benefits ? props.benefits.slice(0, 3) : []
})

const onError = (event) => {
  hasError.value = true
  console.warn('Failed to load image:', props.image, event)
}
</script>
