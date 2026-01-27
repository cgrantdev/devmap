<template>
  <div
    class="bg-white border-2 border-slate-200 rounded-lg overflow-hidden hover:shadow-xl hover:border-slate-400 transition-all cursor-pointer group flex flex-col h-full"
    @click="handleClick"
  >
    <!-- Vial/Image Section -->
    <div class="w-full h-48 bg-gray-50 border-b border-slate-200 flex items-center justify-center p-6 flex-shrink-0">
      <img
        v-if="hasImage"
        :src="image"
        :alt="name"
        class="max-w-full max-h-full w-auto h-auto object-contain object-center"
        loading="lazy"
        @error="onError"
      />
      <div v-else class="w-full h-full flex items-center justify-center">
        <!-- Vial Illustration -->
        <svg
          class="w-32 h-40"
          viewBox="0 0 80 120"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
        >
          <!-- Vial Body -->
          <rect x="20" y="20" width="40" height="80" rx="2" fill="#BFDBFE" stroke="#4B5563" stroke-width="1.5"/>
          
          <!-- Vial Cap -->
          <rect x="18" y="15" width="44" height="8" rx="1" fill="#4B5563"/>
          
          <!-- Liquid (fills ~2/3) -->
          <rect x="22" y="60" width="36" height="36" rx="1" fill="#93C5FD"/>
          
          <!-- Label on vial -->
          <rect x="26" y="70" width="28" height="16" rx="1" fill="white" opacity="0.9"/>
          <line x1="28" y1="74" x2="52" y2="74" stroke="#9CA3AF" stroke-width="0.5"/>
          <line x1="28" y1="78" x2="52" y2="78" stroke="#9CA3AF" stroke-width="0.5"/>
          <line x1="28" y1="82" x2="52" y2="82" stroke="#9CA3AF" stroke-width="0.5"/>
        </svg>
      </div>
    </div>

    <!-- Content Section -->
    <div class="p-6 flex flex-col flex-1 min-h-0">
      <!-- Name and ID -->
      <div class="flex items-center justify-between mb-2 gap-2 h-14">
        <h3 class="text-lg text-gray-900 group-hover:text-slate-700 transition-colors flex-1 flex items-center" style="text-transform: none !important;">
          <span class="line-clamp-2" style="text-transform: none !important;">{{ name }}</span>
        </h3>
        <div class="text-xs text-slate-600 bg-slate-100 px-2 py-1 rounded flex-shrink-0">
          #{{ id }}
        </div>
      </div>

      <!-- Spacer to push description, tag, and button to consistent positions -->
      <div class="flex-grow"></div>

      <!-- Description (Fixed height) -->
      <p class="text-sm text-gray-600 mb-3 line-clamp-2 h-10">
        {{ description || 'Research peptide information' }}
      </p>

      <!-- Category Tag (Fixed height) -->
      <div class="mb-4 h-6 flex items-center">
        <span class="inline bg-slate-700 text-white text-xs px-3 py-1 rounded-full">
          {{ categoryTag }}
        </span>
      </div>

      <!-- Learn More Link (Fixed position at bottom) -->
      <div class="pt-3 border-t border-gray-200 flex items-center justify-between h-10 flex-shrink-0">
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
