<template>
  <div
    class="bg-white flex flex-col gap-5 transition-shadow duration-300 items-center hover:shadow-md cursor-pointer rounded-lg"
    @click="handleClick"
  >
    <!-- Logo -->
    <div class="w-full aspect-square bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden mb-1 p-4">
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
        <span class="font-roboto font-semibold text-2xl text-gray-500">{{ initials }}</span>
      </template>
    </div>

    <div class="flex flex-col items-center min-w-0 w-full">
        <!-- Location -->
        <div class="flex items-center gap-1 mb-1">
            <svg width="13" height="16" viewBox="0 0 13 16" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M2.75 12.5C1.37831 12.8088 0.5 13.2832 0.5 13.8152C0.5 14.7457 3.18629 15.5 6.5 15.5C9.81371 15.5 12.5 14.7457 12.5 13.8152C12.5 13.2832 11.6217 12.8088 10.25 12.5" stroke="#6B7280" stroke-linecap="round"/>
              <path d="M8.375 5.75C8.375 6.78553 7.53553 7.625 6.5 7.625C5.46447 7.625 4.625 6.78553 4.625 5.75C4.625 4.71447 5.46447 3.875 6.5 3.875C7.53553 3.875 8.375 4.71447 8.375 5.75Z" stroke="#6B7280"/>
              <path d="M7.44305 12.1202C7.19009 12.3638 6.85199 12.5 6.50011 12.5C6.14824 12.5 5.81014 12.3638 5.55718 12.1202C3.24073 9.87559 0.136392 7.36815 1.65028 3.7278C2.46883 1.75949 4.43371 0.5 6.50011 0.5C8.56652 0.5 10.5314 1.75949 11.3499 3.7278C12.8619 7.36356 9.76522 9.88332 7.44305 12.1202Z" stroke="#6B7280"/>
            </svg>

            <span class="font-roboto font-normal text-sm leading-relaxed text-gray-500">{{ location || 'Location not available' }}</span>
            </div>

            <!-- Name -->
            <div class="w-full min-w-0 px-2">
              <h3
                class="font-roboto font-normal text-2xl leading-tight tracking-normal text-gray-700
                      m-0 mb-1 text-center truncate w-full"
                :title="name"
              >
                {{ name }}
              </h3>
            </div>

            <!-- Rating -->
            <div class="flex items-center gap-2 mt-1 leading-none">
              <div class="flex items-center gap-[2px] leading-none" aria-label="Vendor rating">
                <div
                  v-for="index in 5"
                  :key="index"
                  class="relative w-5 h-[19px]"
                  role="img"
                  aria-hidden="true"
                >
                  <svg
                    class="block w-5 h-[19px] text-gray-200"
                    viewBox="0 0 20 19"
                    fill="currentColor"
                    xmlns="http://www.w3.org/2000/svg"
                  >
                    <path
                      d="M9.51057 0L11.8147 6.82865L19.0211 6.90983L13.2387 11.2113L15.3884 18.0902L9.51057 13.92L3.63271 18.0902L5.78242 11.2113L1.90735e-06 6.90983L7.20645 6.82865L9.51057 0Z"
                    />
                  </svg>
                  <div
                    class="absolute inset-0 overflow-hidden"
                    :style="{ width: `${getStarFill(index - 1)}%` }"
                  >
                    <svg
                      class="block w-5 h-[19px]"
                      :style="{ color: 'var(--Yellow-Star, #FFC25A)' }"
                      viewBox="0 0 20 19"
                      fill="currentColor"
                      xmlns="http://www.w3.org/2000/svg"
                    >
                      <path
                        d="M9.51057 0L11.8147 6.82865L19.0211 6.90983L13.2387 11.2113L15.3884 18.0902L9.51057 13.92L3.63271 18.0902L5.78242 11.2113L1.90735e-06 6.90983L7.20645 6.82865L9.51057 0Z"
                      />
                    </svg>
                  </div>
                </div>
              </div>
              <div class="flex items-baseline gap-1">
                <span class="font-roboto font-semibold text-base leading-relaxed text-gray-800">{{ normalizedRating.toFixed(2) }}</span>
                <span class="font-roboto font-normal text-sm leading-relaxed text-gray-700">({{ reviews }})</span>
              </div>
            </div>
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

const normalizedRating = computed(() => {
  const parsed = parseFloat(props.rating ?? 0)
  if (Number.isNaN(parsed)) return 0
  return Math.max(0, Math.min(5, parsed))
})

const getStarFill = (index) => {
  const value = normalizedRating.value - index
  if (value >= 1) return 100
  if (value <= 0) return 0
  return value * 100
}

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


