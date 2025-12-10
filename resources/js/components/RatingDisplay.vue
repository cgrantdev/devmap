<template>
  <div class="flex items-center gap-2 leading-none">
    <div class="flex items-center gap-[2px] leading-none" aria-label="Rating">
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
            :style="{ color: starColor }"
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
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  rating: { type: [String, Number], default: 0 },
  reviews: { type: [String, Number], default: 0 },
  starColor: { type: String, default: 'var(--Yellow-Star, #FFC25A)' },
})

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
</script>
