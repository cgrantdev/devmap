<template>
  <div
    class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-lg transition-all cursor-pointer flex flex-col"
    @click="handleClick"
  >
    <!-- Logo (Square with colored background) -->
    <div class="bg-gray-50 p-6 flex items-center justify-center border-b border-gray-200">
      <div 
        class="w-20 h-20 text-xl bg-blue-600 rounded-lg flex items-center justify-center text-white select-none"
        :class="logo && !hasError ? '' : getInitialsColorClass(initials)"
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
          <span class="font-roboto font-semibold text-3xl text-white">{{ initials }}</span>
        </template>
      </div>
    </div>

    <!-- Content Section -->
    <div class="p-4 flex flex-col h-full">
      <!-- Brand Name -->
      <h3 class="text-lg text-gray-900 mb-2 text-center min-h-[3rem] flex items-center justify-center">
        {{ name }}
      </h3>
      
      <!-- Location (Fixed height) -->
      <div class="flex items-center justify-center gap-1 text-gray-600 text-sm mb-3 min-h-[1.5rem]">         
        <!-- Location -->
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-map-pin w-3 h-3 flex-shrink-0" aria-hidden="true">
          <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
          <circle cx="12" cy="10" r="3"></circle>
        </svg>
        <span class="text-center">{{ location || 'Location not available' }}</span>
      </div>

      <!-- Spacer to push rating and button to bottom -->
      <div class="flex-grow"></div>

      <!-- Rating and Reviews (Single line) -->
      <div class="flex items-center justify-center gap-1 mb-4 min-h-[1.5rem]">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-star w-4 h-4 fill-yellow-400 text-yellow-400" aria-hidden="true">
          <path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z" />
        </svg>
        <span class="text-sm text-gray-900">{{ formattedRating }}</span>
        <span class="text-xs text-gray-500">({{ reviews }})</span>
      </div>

      <!-- View Store Button -->
      <button
        @click.stop="handleClick"
        class="w-full bg-slate-700 hover:bg-slate-800 text-white py-2 rounded-lg transition-colors text-sm"
      >
        View Store
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  id: [Number, String],
  name: { type: String, required: true },
  slug: { type: String, required: true },
  logo: { type: String, default: null },
  initials: { type: String, default: '' },
  location: { type: String, default: '' },
  rating: { type: [String, Number], default: '0.00' },
  reviews: { type: [String, Number], default: 0 },
  to: { type: String, default: null },
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
    'bg-blue-600',
    'bg-purple-600',
    'bg-blue-600',
    'bg-orange-600',
    'bg-red-600',
  ]
  // Use a simple hash of initials to pick a color
  const hash = initials.split('').reduce((acc, char) => acc + char.charCodeAt(0), 0)
  return colors[hash % colors.length]
}

const handleClick = () => {
  if (props.to) {
    router.visit(props.to)
  } else if (props.slug) {
    router.visit(`/brand/${props.slug}/products`)
  }
}
</script>
