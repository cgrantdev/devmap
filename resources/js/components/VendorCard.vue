<template>
  <div
    @click.stop="handleClick"
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

    <!-- Logo (Full Top Area) -->
    <div class="p-8 pb-5 flex justify-center">
      <template v-if="logo && !hasError">
        <img
          :src="logo"
          :alt="name + ' logo'"
          class="w-full h-20 object-contain flex items-center justify-center select-none"
          loading="lazy"
          @error="onError"
        />
      </template>
      <template v-else>
        <div class="w-20 h-20 text-xl bg-blue-600 rounded-lg flex items-center justify-center text-white select-none">
          <span>{{ initials }}</span>
        </div>
      </template>
    </div>

    <!-- Content Section -->
    <div class="px-5 pb-5">
      <!-- Brand Name with Featured Dot -->
      <div class="mb-3 text-center">
        <div class="flex items-center justify-center gap-1.5 mb-1">
          <div class="h-14 flex items-end justify-center">
            <h3 class="text-base text-gray-900 group-hover:text-blue-600 transition-colors leading-tight">
              {{ name }}
            </h3>            
          </div>
          <!-- Featured Indicator (Blue Circle) -->
          <div
            v-if="featured"
            class="flex-shrink-0"
            title="Featured Partner"
          >
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check-big w-5 h-5 text-white fill-blue-600 drop-shadow-sm" aria-hidden="true">
              <path d="M21.801 10A10 10 0 1 1 17 3.335"></path>
              <path d="m9 11 3 3L22 4"></path>
            </svg>
          </div>
        </div>
        <!-- Location (Fixed height for alignment) -->
        <div class="flex items-center justify-center gap-1 text-xs text-gray-500">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-map-pin w-3 h-3" aria-hidden="true">
            <path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"></path>
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
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-star w-3 h-3 fill-yellow-400 text-yellow-400" aria-hidden="true">
              <path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z" />
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


