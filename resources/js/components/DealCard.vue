<template>
  <div
    class="bg-white rounded-lg overflow-hidden hover:shadow-lg transition-all cursor-pointer group relative border border-gray-200"
    @click="handleClick"
  >
    <!-- Discount Badge -->
    <div class="absolute top-3 right-3 z-10 bg-red-600 text-white px-3 py-1 rounded-full text-sm shadow-lg">
      <div class="flex items-center gap-1">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4" aria-hidden="true">
          <path d="M16 17h6v-6"></path>
          <path d="m22 17-8.5-8.5-5 5L2 7"></path>
        </svg>
        -{{ discount }}%
      </div>
    </div>

    <!-- Logo/Icon Area -->
    <div class="aspect-square bg-gray-50 p-8 border-b border-gray-200 flex items-center justify-center">
      <div 
        class="w-20 h-20 text-xl rounded-lg flex items-center justify-center text-white select-none"
        :class="getInitialsColorClass(initials)"
      >
        <template v-if="logo && !hasError">
          <img
            :src="logo"
            :alt="name + ' logo'"
            class="w-full h-full object-contain rounded-lg"
            loading="lazy"
            @error="onError"
          />
        </template>
        <template v-else>
          {{ initials }}
        </template>
      </div>
    </div>

    <!-- Content Section -->
    <div class="p-4">
      <!-- Company Name -->
      <h3 class="text-lg text-gray-900 mb-2 text-center group-hover:text-slate-700 transition-colors">
        {{ name }}
      </h3>

      <!-- Rating -->
      <div class="flex items-center justify-center gap-1 mb-3">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 fill-yellow-400 text-yellow-400" aria-hidden="true">
          <path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z" />
        </svg>
        <span class="text-sm text-gray-700">{{ formattedRating }}</span>
        <span class="text-xs text-gray-500">({{ reviews }})</span>
      </div>

      <!-- Description -->
      <p class="text-sm text-gray-600 mb-4 line-clamp-2 text-center">
        {{ description }}
      </p>

      <!-- Use Code Button -->
      <div class="pt-3 border-t border-gray-200">
        <div class="flex items-center justify-between">
          <span class="text-xs text-gray-500">Use code:</span>
          <button
            @click.stop="handleCodeClick"
            class="font-mono text-xs bg-gray-700 text-white px-3 py-1.5 rounded hover:bg-gray-800 transition-colors"
          >
            {{ code }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
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
  slug: {
    type: String,
    required: true
  },
  logo: {
    type: String,
    default: null
  },
  initials: {
    type: String,
    default: ''
  },
  rating: {
    type: [String, Number],
    default: '0.00'
  },
  reviews: {
    type: [String, Number],
    default: 0
  },
  discount: {
    type: [String, Number],
    required: true
  },
  code: {
    type: String,
    required: true
  },
  description: {
    type: String,
    default: ''
  }
})

const hasError = ref(false)

const formattedRating = computed(() => {
  const rating = parseFloat(props.rating || 0)
  return rating.toFixed(1)
})

const onError = () => {
  hasError.value = true
}

// Generate color class based on initials for variety
const getInitialsColorClass = (initials) => {
  const colors = [
    'bg-blue-600',   // PS, PR, PP (Proven)
    'bg-purple-600', // SC
    'bg-orange-600', // LL
    'bg-red-600',    // AA, CP
    'bg-green-600',  // PP (Paradigm)
  ]
  // Use a simple hash of initials to pick a color consistently
  const hash = initials.split('').reduce((acc, char) => acc + char.charCodeAt(0), 0)
  return colors[hash % colors.length]
}

const handleClick = () => {
  router.visit(`/brand/${props.slug}/products`)
}

const handleCodeClick = (event) => {
  event.stopPropagation()
  // Copy code to clipboard
  navigator.clipboard.writeText(props.code).then(() => {
    // You could show a toast notification here
    alert(`Code "${props.code}" copied to clipboard!`)
  }).catch(() => {
    alert('Failed to copy code')
  })
}
</script>
