<template>
  <div class="vendor-grading max-w-lg mx-auto">
    <!-- Overall Rating -->
    <div class="mb-6">
      <div class="flex items-center justify-between mb-2">
        <h3 class="font-roboto font-bold text-lg leading-relaxed text-gray-800 m-0">Overall Rating</h3>
        <div class="flex items-center gap-2">
          <span class="font-roboto font-bold text-2xl leading-relaxed text-gray-800">{{ overallScore }}/25</span>
          <span class="font-roboto font-normal text-lg leading-relaxed text-gray-600">= {{ overallPercentage }}% Review Score</span>
        </div>
      </div>
    </div>

    <!-- Rating Categories -->
    <div class="space-y-4">
      <div
        v-for="category in categories"
        :key="category.key"
        class="flex items-center justify-between"
      >
        <span class="font-roboto font-normal text-base leading-relaxed text-gray-800">{{ category.label }}</span>
        <div class="flex items-center gap-1">
          <button
            v-for="i in 5"
            :key="i"
            type="button"
            @click="setRating(category.key, i)"
            class="p-0 border-0 bg-transparent cursor-pointer hover:opacity-80 transition-opacity"
            :aria-label="`Rate ${category.label} ${i} out of 5`"
          >
            <svg
              class="w-5 h-5"
              :class="i <= getSelectedRating(category.key) ? 'text-yellow-500' : 'text-gray-300'"
              fill="currentColor"
              viewBox="0 0 20 20"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path
                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
              />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- User Information -->
    <div class="mt-6 space-y-4">
      <div>
        <label for="reviewer-name" class="block font-roboto font-medium text-sm leading-relaxed text-gray-800 mb-2">
          Your Name <span class="text-red-500">*</span>
        </label>
        <input
          id="reviewer-name"
          v-model="userName"
          type="text"
          required
          class="w-full px-4 py-2 rounded-lg border border-black font-roboto font-normal text-sm leading-relaxed text-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-800 focus:border-black"
          placeholder="Enter your name"
        />
      </div>
      <div>
        <label for="reviewer-email" class="block font-roboto font-medium text-sm leading-relaxed text-gray-800 mb-2">
          Your Email <span class="text-red-500">*</span>
        </label>
        <input
          id="reviewer-email"
          v-model="userEmail"
          type="email"
          required
          class="w-full px-4 py-2 rounded-lg border border-black font-roboto font-normal text-sm leading-relaxed text-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-800 focus:border-black"
          placeholder="Enter your email"
        />
      </div>
      <div>
        <label for="review-text" class="block font-roboto font-medium text-sm leading-relaxed text-gray-800 mb-2">
          Review (Optional)
        </label>
        <textarea
          id="review-text"
          v-model="reviewText"
          rows="4"
          class="w-full px-4 py-2 rounded-lg border border-black font-roboto font-normal text-sm leading-relaxed text-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-800 focus:border-black resize-none"
          placeholder="Write your review here..."
        ></textarea>
      </div>
    </div>

    <!-- Submit Button -->
    <div class="mt-6 flex justify-end">
      <MainButton
        :text="isLoading ? 'Submitting...' : 'Submit Rating'"
        size="custom-small"
        bg-color="gray-800"
        :svg="loadingSpinner"
        :class="{ 'opacity-50 cursor-not-allowed': !isFormValid || isLoading }"
        @click="handleSubmit"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, ref, h } from 'vue'
import MainButton from '@/components/MainButton.vue'

const props = defineProps({
  shippingTime: { type: [Number, String], default: 0 },
  customerService: { type: [Number, String], default: 0 },
  quality: { type: [Number, String], default: 0 },
  cost: { type: [Number, String], default: 0 },
  packaging: { type: [Number, String], default: 0 },
  isLoading: { type: Boolean, default: false },
})

const emit = defineEmits(['submit'])

// Editable ratings state
const selectedRatings = ref({
  shippingTime: Math.round(parseFloat(props.shippingTime) || 0),
  customerService: Math.round(parseFloat(props.customerService) || 0),
  quality: Math.round(parseFloat(props.quality) || 0),
  cost: Math.round(parseFloat(props.cost) || 0),
  packaging: Math.round(parseFloat(props.packaging) || 0),
})

// User information
const userName = ref('')
const userEmail = ref('')
const reviewText = ref('')

const categories = computed(() => [
  { key: 'shippingTime', label: 'Shipping Time' },
  { key: 'customerService', label: 'Customer Service' },
  { key: 'quality', label: 'Quality' },
  { key: 'cost', label: 'Cost' },
  { key: 'packaging', label: 'Packaging' },
])

const getSelectedRating = (key) => {
  return selectedRatings.value[key] || 0
}

const setRating = (key, value) => {
  selectedRatings.value[key] = value
}

const overallScore = computed(() => {
  return Object.values(selectedRatings.value).reduce((total, rating) => total + (rating || 0), 0)
})

const overallPercentage = computed(() => {
  return Math.round((overallScore.value / 25) * 100)
})

const isFormValid = computed(() => {
  return userName.value.trim() !== '' && 
         userEmail.value.trim() !== '' && 
         /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(userEmail.value)
})

const loadingSpinner = computed(() => {
  if (!props.isLoading) return null
  
  return h('svg', {
    class: 'animate-spin h-4 w-4 text-white',
    xmlns: 'http://www.w3.org/2000/svg',
    fill: 'none',
    viewBox: '0 0 24 24'
  }, [
    h('circle', {
      class: 'opacity-25',
      cx: '12',
      cy: '12',
      r: '10',
      stroke: 'currentColor',
      'stroke-width': '4'
    }),
    h('path', {
      class: 'opacity-75',
      fill: 'currentColor',
      d: 'M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z'
    })
  ])
})

const handleSubmit = () => {
  if (!isFormValid.value || props.isLoading) {
    return
  }

  emit('submit', {
    shipping_time: selectedRatings.value.shippingTime,
    customer_service: selectedRatings.value.customerService,
    quality: selectedRatings.value.quality,
    cost: selectedRatings.value.cost,
    packaging: selectedRatings.value.packaging,
    user_name: userName.value.trim(),
    user_email: userEmail.value.trim(),
    review: reviewText.value.trim() || null,
  })
}
</script>
