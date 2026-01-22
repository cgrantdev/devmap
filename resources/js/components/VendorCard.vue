<template>
  <div
    class="bg-white rounded-lg overflow-hidden hover:shadow-lg transition-all cursor-pointer group relative border-2 border-slate-300 shadow-md"
  >
    <!-- Partner Badge (Top Right Corner) -->
    <div class="absolute top-0 right-0 z-10">
      <div
        v-if="isPartner"
        class="px-3 py-1 rounded-bl-lg text-xs shadow-sm bg-slate-100 text-slate-700"
      >
        Partner
      </div>
    </div>

    <!-- Logo (80x80) -->
    <div class="p-8 pb-5 flex justify-center">
      <div 
        class="w-20 h-20 text-xl bg-blue-600 rounded-lg flex items-center justify-center text-white select-none"
        :class="logo && !hasError ? '' : 'bg-blue-600 p-4'"
      >
        <template v-if="logo && !hasError">
          <img
            :src="logo"
            :alt="name + ' logo'"
            class="w-full h-full object-contain"
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
    <div class="px-5 pb-5">
      <!-- Brand Name with Featured Dot -->
      <div class="mb-3 text-center"> 
        <div class="flex items-center justify-center gap-1.5 mb-1">
          <h3 class="text-base text-gray-900 group-hover:text-blue-600 transition-colors line-clamp-1">
            {{ name }}
          </h3>
          <!-- Featured Indicator (Blue Circle) -->
          <div
            v-if="featured"
            class="w-4 h-4 bg-blue-600 rounded-full flex-shrink-0"
            title="Featured Partner"
          ></div>
        </div>
        
        <!-- Location -->
        <div class="flex items-center justify-center gap-1 text-xs text-gray-500">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-map-pin w-3 h-3 flex-shrink-0" aria-hidden="true">
            <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
            <circle cx="12" cy="10" r="3"></circle>
          </svg>
          <span class="truncate">{{ location || 'Location not available' }}</span>
        </div>
      </div>

      <!-- Rating and Reviews (Side by Side) -->
      <div class="grid grid-cols-2 gap-2 mb-3">
        <!-- Rating Section -->
        <div class="bg-gray-50 rounded-lg p-2 text-center">
          <div class="flex items-center justify-center gap-1 mb-0.5">
            <svg class="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
            </svg>
            <span class="text-base text-gray-900">{{ formattedRating }}</span>
          </div>
          <div class="text-xs text-gray-500">Rating</div>
        </div>

        <!-- Reviews Section -->
        <div class="bg-gray-50 rounded-lg p-2 text-center">
          <div class="text-base text-gray-900 mb-0.5">{{ reviews }}</div>
          <div class="text-xs text-gray-500">Reviews</div>
        </div>
      </div>

      <!-- View Store Button -->
      <button
        @click.stop="handleClick"
        class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg transition-colors text-sm"
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
  isPartner: { type: Boolean, default: false },
  featured: { type: Boolean, default: false },
})

const hasError = ref(false)

const formattedRating = computed(() => {
  const rating = parseFloat(props.rating || 0)
  return rating.toFixed(1)
})

const onError = () => {
  hasError.value = true
}

const handleClick = () => {
  if (props.to) {
    router.visit(props.to)
  } else if (props.slug) {
    router.visit(`/brand/${props.slug}/products`)
  }
}
</script>


