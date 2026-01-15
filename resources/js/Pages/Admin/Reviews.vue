<template>
  <AdminLayout>
    <div class="mb-8">
      <h1 class="text-3xl text-slate-900 mb-2">Review Management</h1>
      <p class="text-slate-600">Moderate and manage customer reviews</p>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-6 mb-6">
      <div class="bg-white rounded-lg border border-slate-200 p-6">
        <div class="text-sm text-slate-600 mb-1">Total Reviews</div>
        <div class="text-3xl text-slate-900">{{ reviews.length }}</div>
      </div>
      <div class="bg-white rounded-lg border border-slate-200 p-6">
        <div class="text-sm text-slate-600 mb-1">Pending</div>
        <div class="text-3xl text-yellow-600">{{ pendingCount }}</div>
      </div>
      <div class="bg-white rounded-lg border border-slate-200 p-6">
        <div class="text-sm text-slate-600 mb-1">Approved</div>
        <div class="text-3xl text-green-600">{{ approvedCount }}</div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg border border-slate-200 p-4 mb-6">
      <div class="grid grid-cols-2 gap-4">
        <div class="relative">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input
            type="text"
            placeholder="Search reviews..."
            v-model="searchTerm"
            class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        
        <select
          v-model="filterStatus"
          class="px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="all">All Status</option>
          <option value="pending">Pending</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
        </select>
      </div>
    </div>

    <!-- Reviews List -->
    <div class="space-y-4">
      <div v-for="review in filteredReviews" :key="review.id" class="bg-white rounded-lg border border-slate-200 p-6">
        <div class="flex items-start justify-between mb-4">
          <div class="flex-1">
            <div class="flex items-center gap-3 mb-2">
              <span class="text-slate-900">{{ review.user_name || 'Anonymous' }}</span>
              <span v-if="review.verified" class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs">
                Verified Purchase
              </span>
              <span :class="[
                'px-2 py-1 rounded text-xs',
                review.status === 'approved' ? 'bg-green-100 text-green-800' :
                review.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                'bg-red-100 text-red-800'
              ]">
                {{ review.status }}
              </span>
            </div>
            <div class="flex items-center gap-1 mb-2">
              <svg v-for="i in 5" :key="i" :class="[
                'w-4 h-4',
                i <= review.rating ? 'fill-yellow-400 text-yellow-400' : 'text-slate-300'
              ]" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
              </svg>
              <span class="text-sm text-slate-500 ml-2">{{ formatDate(review.created_at) }}</span>
            </div>
          </div>
        </div>

        <p class="text-slate-700 mb-3">{{ review.comment }}</p>
        
        <div class="flex items-center gap-4 text-sm text-slate-500 mb-4">
          <span>Brand: {{ review.brand?.name || 'N/A' }}</span>
        </div>

        <div class="flex gap-2">
          <button
            v-if="review.status !== 'approved'"
            @click="approveReview(review.id)"
            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg flex items-center gap-2 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            Approve
          </button>
          <button
            v-if="review.status !== 'rejected'"
            @click="rejectReview(review.id)"
            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg flex items-center gap-2 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            Reject
          </button>
          <button
            @click="deleteReview(review.id)"
            class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors"
          >
            Delete
          </button>
        </div>
      </div>

      <div v-if="filteredReviews.length === 0" class="bg-white rounded-lg border border-slate-200 p-6 text-center text-slate-500">
        No reviews found.
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'

const props = defineProps({
  reviews: {
    type: Array,
    default: () => []
  }
})

const csrf = usePage().props.csrf_token
const searchTerm = ref('')
const filterStatus = ref('all')

const filteredReviews = computed(() => {
  return props.reviews.filter(review => {
    const matchesSearch = (review.comment || '').toLowerCase().includes(searchTerm.value.toLowerCase()) ||
                         (review.user_name || '').toLowerCase().includes(searchTerm.value.toLowerCase())
    const matchesStatus = filterStatus.value === 'all' || review.status === filterStatus.value
    return matchesSearch && matchesStatus
  })
})

const pendingCount = computed(() => props.reviews.filter(r => r.status === 'pending').length)
const approvedCount = computed(() => props.reviews.filter(r => r.status === 'approved').length)

function formatDate(dateString) {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString()
}

function approveReview(reviewId) {
  router.post(`/admin/reviews/${reviewId}/approve`, {
    _token: csrf
  }, {
    preserveScroll: true
  })
}

function rejectReview(reviewId) {
  if (confirm('Are you sure you want to reject this review?')) {
    router.post(`/admin/reviews/${reviewId}/reject`, {
      _token: csrf
    }, {
      preserveScroll: true
    })
  }
}

function deleteReview(reviewId) {
  if (confirm('Are you sure you want to delete this review?')) {
    const deleteForm = useForm({
      _token: usePage().props.csrf_token
    })
    
    deleteForm.delete(`/admin/reviews/${reviewId}`, {
      preserveScroll: true
    })
  }
}
</script>

