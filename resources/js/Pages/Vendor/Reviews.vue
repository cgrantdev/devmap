<template>
  <Layout>
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900">Reviews</h1>
      <p class="text-gray-600 mt-2">Coming Soon</p>
      <!-- <p class="text-gray-600 mt-2">Manage and respond to customer reviews</p> -->
    </div>

    <!-- Stats Grid
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="bg-blue-500 w-12 h-12 rounded-lg flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-message-square w-6 h-6 text-white" aria-hidden="true">
              <path d="M22 17a2 2 0 0 1-2 2H6.828a2 2 0 0 0-1.414.586l-2.202 2.202A.71.71 0 0 1 2 21.286V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2z"></path>
            </svg>
          </div>
        </div>
        <div class="text-2xl text-gray-900 mb-1">{{ stats.totalReviews || 0 }}</div>
        <div class="text-sm text-gray-600">Total Reviews</div>
      </div>

      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="bg-green-500 w-12 h-12 rounded-lg flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-star w-6 h-6 text-white" aria-hidden="true">
              <path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"></path>
            </svg>
          </div>
        </div>
        <div class="text-2xl text-gray-900 mb-1">{{ stats.averageRating || '0.0' }}</div>
        <div class="text-sm text-gray-600">Average Rating</div>
      </div>

      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="bg-yellow-500 w-12 h-12 rounded-lg flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clock w-6 h-6 text-white" aria-hidden="true">
              <circle cx="12" cy="12" r="10"></circle>
              <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
          </div>
        </div>
        <div class="text-2xl text-gray-900 mb-1">{{ stats.pendingReviews || 0 }}</div>
        <div class="text-sm text-gray-600">Pending Reviews</div>
      </div>

      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="bg-purple-500 w-12 h-12 rounded-lg flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-circle w-6 h-6 text-white" aria-hidden="true">
              <circle cx="12" cy="12" r="10"></circle>
              <path d="m9 12 2 2 4-4"></path>
            </svg>
          </div>
        </div>
        <div class="text-2xl text-gray-900 mb-1">{{ stats.respondedReviews || 0 }}</div>
        <div class="text-sm text-gray-600">Responded Reviews</div>
      </div>
    </div>

    // Reviews List
    <div class="bg-white rounded-lg border border-gray-200">
      <div class="p-6 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-900">Recent Reviews</h2>
      </div>
      <div class="p-6">
        <div v-if="reviews.length > 0" class="space-y-4">
          <div v-for="review in reviews" :key="review.id" class="border border-gray-200 rounded-lg p-4">
            <div class="flex items-start justify-between mb-3">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center">
                  <span class="text-gray-600 font-medium">{{ review.user_name?.charAt(0).toUpperCase() || 'U' }}</span>
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900">{{ review.user_name || 'Anonymous' }}</p>
                  <p class="text-xs text-gray-500">{{ formatDate(review.created_at) }}</p>
                </div>
              </div>
              <div class="flex items-center gap-1">
                <svg
                  v-for="i in 5"
                  :key="i"
                  xmlns="http://www.w3.org/2000/svg"
                  width="16"
                  height="16"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  :class="i <= review.rating ? 'text-yellow-400 fill-yellow-400' : 'text-gray-300'"
                  class="lucide lucide-star"
                >
                  <path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"></path>
                </svg>
              </div>
            </div>
            <p class="text-sm text-gray-700 mb-3">{{ review.review || 'No review text' }}</p>
            <div v-if="review.product" class="text-xs text-gray-500 mb-3">
              Product: {{ review.product.name }}
            </div>
            <div class="flex items-center gap-2">
              <span class="text-xs px-2 py-1 rounded" :class="{
                'bg-yellow-100 text-yellow-800': review.status === 'pending',
                'bg-green-100 text-green-800': review.status === 'approved',
                'bg-red-100 text-red-800': review.status === 'rejected'
              }">
                {{ review.status || 'pending' }}
              </span>
            </div>
          </div>
        </div>
        <div v-else class="text-center py-12 text-gray-400">
          <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-message-square mx-auto mb-4">
            <path d="M22 17a2 2 0 0 1-2 2H6.828a2 2 0 0 0-1.414.586l-2.202 2.202A.71.71 0 0 1 2 21.286V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2z"></path>
          </svg>
          <p>No reviews yet</p>
        </div>
      </div>
    </div> -->
  </Layout>
</template>

<script setup>
import Layout from './Layout.vue'

const props = defineProps({
  stats: {
    type: Object,
    default: () => ({
      totalReviews: 0,
      averageRating: '0.0',
      pendingReviews: 0,
      respondedReviews: 0
    })
  },
  reviews: {
    type: Array,
    default: () => []
  }
})

function formatDate(dateString) {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}
</script>
