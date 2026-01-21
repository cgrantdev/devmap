<template>
  <div
    class="bg-white rounded-lg overflow-hidden hover:shadow-lg transition-all cursor-pointer group relative border-2 border-slate-300 shadow-md flex flex-col"
  >
    <!-- Partner Badge (Top Right Corner) -->
    <div
      v-if="isPartner"
      class="absolute top-2 right-2 inline-flex items-center px-2 py-0.5 bg-gray-100 border border-gray-300 rounded-full text-xs font-roboto font-normal text-gray-800 leading-none z-10"
    >
      Partner
    </div>

    <!-- Logo (80x80) -->
    <div class="w-full flex items-center justify-center py-4">
      <div 
        class="w-20 h-20 flex items-center justify-center overflow-hidden"
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
    <div class="flex flex-col items-center px-4 py-5 gap-3">
      <!-- Brand Name with Featured Dot -->
      <div class="flex items-center justify-center gap-2">
        <h3 class="font-roboto font-normal text-lg leading-tight tracking-normal text-gray-700 group-hover:text-blue-600 transition-colors duration-200 m-0">
          {{ name }}
        </h3>
        <!-- Featured Indicator (Blue Circle) -->
        <div
          v-if="featured"
          class="w-4 h-4 bg-blue-600 rounded-full flex-shrink-0"
        ></div>
      </div>

      <!-- Location -->
      <div class="flex items-center gap-1.5">
        <svg width="13" height="16" viewBox="0 0 13 16" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M2.75 12.5C1.37831 12.8088 0.5 13.2832 0.5 13.8152C0.5 14.7457 3.18629 15.5 6.5 15.5C9.81371 15.5 12.5 14.7457 12.5 13.8152C12.5 13.2832 11.6217 12.8088 10.25 12.5" stroke="#6B7280" stroke-linecap="round"/>
          <path d="M8.375 5.75C8.375 6.78553 7.53553 7.625 6.5 7.625C5.46447 7.625 4.625 6.78553 4.625 5.75C4.625 4.71447 5.46447 3.875 6.5 3.875C7.53553 3.875 8.375 4.71447 8.375 5.75Z" stroke="#6B7280"/>
          <path d="M7.44305 12.1202C7.19009 12.3638 6.85199 12.5 6.50011 12.5C6.14824 12.5 5.81014 12.3638 5.55718 12.1202C3.24073 9.87559 0.136392 7.36815 1.65028 3.7278C2.46883 1.75949 4.43371 0.5 6.50011 0.5C8.56652 0.5 10.5314 1.75949 11.3499 3.7278C12.8619 7.36356 9.76522 9.88332 7.44305 12.1202Z" stroke="#6B7280"/>
        </svg>
        <span class="font-roboto font-normal text-xs leading-normal text-gray-500">{{ location || 'Location not available' }}</span>
      </div>

      <!-- Rating and Reviews (Side by Side) -->
      <div class="w-full flex items-center gap-2">
        <!-- Rating Section -->
        <div class="flex-1 bg-gray-100 rounded-lg px-3 py-2 flex flex-col items-center">
          <div class="flex items-center gap-1 mb-0.5">
            <svg class="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
            </svg>
            <span class="font-roboto font-normal text-sm leading-tight text-gray-800">{{ formattedRating }}</span>
          </div>
          <span class="font-roboto font-normal text-xs leading-tight text-gray-500">Rating</span>
        </div>

        <!-- Reviews Section -->
        <div class="flex-1 bg-gray-100 rounded-lg px-3 py-2 flex flex-col items-center">
          <span class="font-roboto font-normal text-sm leading-tight text-gray-800">{{ reviews }}</span>
          <span class="font-roboto font-normal text-xs leading-tight text-gray-500">Reviews</span>
        </div>
      </div>

      <!-- View Store Button -->
      <button
        @click.stop="handleClick"
        class="w-full bg-blue-600 hover:bg-blue-700 text-white rounded-lg py-2.5 px-4 font-roboto font-medium text-sm leading-normal transition-colors duration-200 mt-1"
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


