<template>
  <Layout>
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900">Reviews</h1>
      <p class="text-gray-600 mt-2">Manage and respond to customer reviews</p>
    </div>

    <!-- Stats Grid -->
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

    <!-- Reviews List -->
    <div class="bg-white rounded-lg border border-gray-200">
      <div class="p-6 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-900">Approved Reviews</h2>
      </div>
      <div class="p-6">
        <div v-if="reviews.length > 0" class="space-y-6">
          <div v-for="review in reviews" :key="review.id" class="border border-gray-200 rounded-lg p-6">
            <div class="flex items-start justify-between mb-4">
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
            
            <!-- Review Text -->
            <p class="text-sm text-gray-700 mb-4">{{ review.review || 'No review text' }}</p>
            
            <!-- Grading Details -->
            <div v-if="review.shipping_time || review.customer_service || review.quality || review.cost || review.packaging" class="mb-4 grid grid-cols-2 md:grid-cols-5 gap-2 text-xs">
              <div v-if="review.shipping_time" class="bg-gray-50 p-2 rounded">
                <div class="text-gray-500">Shipping</div>
                <div class="font-semibold">{{ review.shipping_time }}/5</div>
              </div>
              <div v-if="review.customer_service" class="bg-gray-50 p-2 rounded">
                <div class="text-gray-500">Service</div>
                <div class="font-semibold">{{ review.customer_service }}/5</div>
              </div>
              <div v-if="review.quality" class="bg-gray-50 p-2 rounded">
                <div class="text-gray-500">Quality</div>
                <div class="font-semibold">{{ review.quality }}/5</div>
              </div>
              <div v-if="review.cost" class="bg-gray-50 p-2 rounded">
                <div class="text-gray-500">Cost</div>
                <div class="font-semibold">{{ review.cost }}/5</div>
              </div>
              <div v-if="review.packaging" class="bg-gray-50 p-2 rounded">
                <div class="text-gray-500">Packaging</div>
                <div class="font-semibold">{{ review.packaging }}/5</div>
              </div>
            </div>
            
            <!-- Product Info -->
            <div v-if="review.product" class="text-xs text-gray-500 mb-4">
              Product: {{ review.product.name }}
            </div>
            
            <!-- Vendor Reply Section -->
            <div v-if="review.vendor_reply" class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
              <div class="flex items-start justify-between mb-2">
                <div class="text-sm font-semibold text-blue-900">Your Reply</div>
                <div class="text-xs text-blue-600">{{ formatDate(review.vendor_replied_at) }}</div>
              </div>
              <p class="text-sm text-blue-800">{{ review.vendor_reply }}</p>
            </div>
            
            <!-- Flagged Status -->
            <div v-if="review.flagged" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
              <div class="flex items-center gap-2 text-sm text-red-800">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-flag">
                  <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path>
                  <line x1="4" y1="22" x2="4" y2="15"></line>
                </svg>
                <span class="font-semibold">Flagged for Review</span>
              </div>
              <p v-if="review.flag_reason" class="text-xs text-red-700 mt-1">{{ review.flag_reason }}</p>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex items-center gap-3">
              <button
                v-if="!review.vendor_reply"
                @click="showReplyForm(review.id)"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm transition-colors flex items-center gap-2"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-reply">
                  <polyline points="9 17 4 12 9 7"></polyline>
                  <path d="M20 18v-2a4 4 0 0 0-4-4H4"></path>
                </svg>
                Reply
              </button>
              <button
                v-else
                @click="showReplyForm(review.id)"
                class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg text-sm transition-colors flex items-center gap-2"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-edit">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
                Edit Reply
              </button>
              <button
                v-if="!review.flagged"
                @click="showFlagForm(review.id)"
                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm transition-colors flex items-center gap-2"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-flag">
                  <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path>
                  <line x1="4" y1="22" x2="4" y2="15"></line>
                </svg>
                Flag Review
              </button>
              <button
                v-else
                @click="unflagReview(review.id)"
                class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg text-sm transition-colors flex items-center gap-2"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-flag-off">
                  <path d="M8 2c3 0 5 2 8 2s4-1 4-1v11s-1 1-4 1-5-2-8-2-4 1-4 1V2z"></path>
                  <line x1="4" y1="22" x2="4" y2="15"></line>
                </svg>
                Unflag
              </button>
            </div>
            
            <!-- Reply Form -->
            <div v-if="activeReplyForm === review.id" class="mt-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
              <textarea
                v-model="replyTexts[review.id]"
                :placeholder="review.vendor_reply ? 'Edit your reply...' : 'Write a reply to this review...'"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                rows="4"
              ></textarea>
              <div class="flex items-center gap-2 mt-3">
                <button
                  @click="submitReply(review.id)"
                  :disabled="!replyTexts[review.id] || isSubmitting"
                  class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white rounded-lg text-sm transition-colors"
                >
                  {{ isSubmitting ? 'Submitting...' : 'Submit Reply' }}
                </button>
                <button
                  @click="cancelReply(review.id)"
                  class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-lg text-sm transition-colors"
                >
                  Cancel
                </button>
              </div>
            </div>
            
            <!-- Flag Form -->
            <div v-if="activeFlagForm === review.id" class="mt-4 p-4 bg-red-50 rounded-lg border border-red-200">
              <label class="block text-sm font-medium text-red-900 mb-2">Reason for flagging this review:</label>
              <textarea
                v-model="flagReasons[review.id]"
                placeholder="Please provide a reason for flagging this review..."
                class="w-full px-3 py-2 border border-red-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 text-sm"
                rows="3"
              ></textarea>
              <div class="flex items-center gap-2 mt-3">
                <button
                  @click="submitFlag(review.id)"
                  :disabled="!flagReasons[review.id] || isSubmitting"
                  class="px-4 py-2 bg-red-600 hover:bg-red-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white rounded-lg text-sm transition-colors"
                >
                  {{ isSubmitting ? 'Submitting...' : 'Flag Review' }}
                </button>
                <button
                  @click="cancelFlag(review.id)"
                  class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-lg text-sm transition-colors"
                >
                  Cancel
                </button>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="text-center py-12 text-gray-400">
          <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-message-square mx-auto mb-4">
            <path d="M22 17a2 2 0 0 1-2 2H6.828a2 2 0 0 0-1.414.586l-2.202 2.202A.71.71 0 0 1 2 21.286V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2z"></path>
          </svg>
          <p>No approved reviews yet</p>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { ref } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
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

const page = usePage()
const activeReplyForm = ref(null)
const activeFlagForm = ref(null)
const replyTexts = ref({})
const flagReasons = ref({})
const isSubmitting = ref(false)

function formatDate(dateString) {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function showReplyForm(reviewId) {
  activeFlagForm.value = null
  activeReplyForm.value = activeReplyForm.value === reviewId ? null : reviewId
  if (activeReplyForm.value === reviewId) {
    const review = props.reviews.find(r => r.id === reviewId)
    replyTexts.value[reviewId] = review?.vendor_reply || ''
  }
}

function cancelReply(reviewId) {
  activeReplyForm.value = null
  replyTexts.value[reviewId] = ''
}

function submitReply(reviewId) {
  if (!replyTexts.value[reviewId]) return
  
  isSubmitting.value = true
  const form = useForm({
    vendor_reply: replyTexts.value[reviewId],
    _token: page.props.csrf_token
  })
  
  form.post(`/vendor/reviews/${reviewId}/reply`, {
    preserveScroll: true,
    onSuccess: () => {
      isSubmitting.value = false
      activeReplyForm.value = null
      replyTexts.value[reviewId] = ''
    },
    onError: () => {
      isSubmitting.value = false
    }
  })
}

function showFlagForm(reviewId) {
  activeReplyForm.value = null
  activeFlagForm.value = activeFlagForm.value === reviewId ? null : reviewId
  if (activeFlagForm.value === reviewId) {
    flagReasons.value[reviewId] = ''
  }
}

function cancelFlag(reviewId) {
  activeFlagForm.value = null
  flagReasons.value[reviewId] = ''
}

function submitFlag(reviewId) {
  if (!flagReasons.value[reviewId]) return
  
  isSubmitting.value = true
  const form = useForm({
    flag_reason: flagReasons.value[reviewId],
    _token: page.props.csrf_token
  })
  
  form.post(`/vendor/reviews/${reviewId}/flag`, {
    preserveScroll: true,
    onSuccess: () => {
      isSubmitting.value = false
      activeFlagForm.value = null
      flagReasons.value[reviewId] = ''
    },
    onError: () => {
      isSubmitting.value = false
    }
  })
}

function unflagReview(reviewId) {
  if (!confirm('Are you sure you want to unflag this review?')) return
  
  isSubmitting.value = true
  const form = useForm({
    _token: page.props.csrf_token
  })
  
  form.post(`/vendor/reviews/${reviewId}/unflag`, {
    preserveScroll: true,
    onSuccess: () => {
      isSubmitting.value = false
    },
    onError: () => {
      isSubmitting.value = false
    }
  })
}
</script>
