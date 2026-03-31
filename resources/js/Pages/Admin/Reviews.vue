<template>
  <AdminLayout>
    <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
      <div>
        <h1 class="text-3xl text-slate-900 mb-2">Review Management</h1>
        <p class="text-slate-600">
          Review customer feedback, handle vendor flag requests, and make the final moderation decision.
        </p>
      </div>

      <div class="flex flex-wrap gap-2">
        <button
          @click="focusFlaggedQueue"
          class="inline-flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-800 hover:bg-amber-100 transition-colors"
        >
          <span class="inline-flex h-2.5 w-2.5 rounded-full bg-amber-500"></span>
          Flagged queue: {{ flaggedCount }}
        </button>
      </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5 mb-6">
      <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="text-sm text-slate-500 mb-1">Total reviews</div>
        <div class="text-3xl text-slate-900">{{ statsData.total }}</div>
      </div>
      <div class="bg-white rounded-xl border border-amber-200 p-5">
        <div class="text-sm text-amber-700 mb-1">Flagged by vendors</div>
        <div class="text-3xl text-amber-600">{{ statsData.flagged }}</div>
      </div>
      <div class="bg-white rounded-xl border border-yellow-200 p-5">
        <div class="text-sm text-yellow-700 mb-1">Pending</div>
        <div class="text-3xl text-yellow-600">{{ statsData.pending }}</div>
      </div>
      <div class="bg-white rounded-xl border border-green-200 p-5">
        <div class="text-sm text-green-700 mb-1">Approved</div>
        <div class="text-3xl text-green-600">{{ statsData.approved }}</div>
      </div>
      <div class="bg-white rounded-xl border border-red-200 p-5">
        <div class="text-sm text-red-700 mb-1">Rejected</div>
        <div class="text-3xl text-red-600">{{ statsData.rejected }}</div>
      </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-4 mb-6">
      <div class="grid gap-4 lg:grid-cols-[minmax(0,1.5fr)_220px_220px]">
        <div class="relative">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input
            v-model="searchTerm"
            type="text"
            placeholder="Search by customer, brand, email, comment, or flag reason"
            class="w-full rounded-lg border border-slate-300 pl-10 pr-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <select
          v-model="filterStatus"
          class="rounded-lg border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="all">All statuses</option>
          <option value="pending">Pending</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
        </select>

        <select
          v-model="filterQueue"
          class="rounded-lg border border-slate-300 px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="all">All reviews</option>
          <option value="flagged">Flagged only</option>
          <option value="normal">Not flagged</option>
        </select>
      </div>
    </div>

    <div v-if="flaggedReviews.length" ref="flaggedQueueRef" class="mb-6">
      <div class="mb-3 flex items-center justify-between">
        <div>
          <h2 class="text-lg text-slate-900">Flagged review queue</h2>
          <p class="text-sm text-slate-500">These reviews were flagged by vendors and need an admin decision.</p>
        </div>
      </div>

      <div class="space-y-4">
        <ReviewCard
          v-for="review in flaggedReviews"
          :key="`flagged-${review.id}`"
          :review="review"
          @approve="approveReview"
          @reject="rejectReview"
          @delete="deleteReview"
        />
      </div>
    </div>

    <div>
      <div class="mb-3 flex items-center justify-between">
        <div>
          <h2 class="text-lg text-slate-900">All reviews</h2>
          <p class="text-sm text-slate-500">{{ filteredReviews.length }} review(s) match your filters.</p>
        </div>
      </div>

      <div class="space-y-4">
        <ReviewCard
          v-for="review in filteredReviews"
          :key="review.id"
          :review="review"
          @approve="approveReview"
          @reject="rejectReview"
          @delete="deleteReview"
        />

        <div v-if="filteredReviews.length === 0" class="bg-white rounded-xl border border-slate-200 p-8 text-center text-slate-500">
          No reviews found for the current filters.
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { computed, defineComponent, h, ref } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'

const props = defineProps({
  reviews: {
    type: Array,
    default: () => []
  },
  stats: {
    type: Object,
    default: () => ({
      total: 0,
      flagged: 0,
      pending: 0,
      approved: 0,
      rejected: 0,
    })
  }
})

const page = usePage()
const searchTerm = ref('')
const filterStatus = ref('all')
const filterQueue = ref('all')
const flaggedQueueRef = ref(null)

const statsData = computed(() => ({
  total: props.stats?.total ?? props.reviews.length,
  flagged: props.stats?.flagged ?? props.reviews.filter(r => r.flagged).length,
  pending: props.stats?.pending ?? props.reviews.filter(r => r.status === 'pending').length,
  approved: props.stats?.approved ?? props.reviews.filter(r => r.status === 'approved').length,
  rejected: props.stats?.rejected ?? props.reviews.filter(r => r.status === 'rejected').length,
}))

const filteredReviews = computed(() => {
  const keyword = searchTerm.value.trim().toLowerCase()

  return props.reviews.filter((review) => {
    const haystack = [
      review.user_name,
      review.user_email,
      review.comment,
      review.brand?.name,
      review.flag_reason,
      review.flag_resolution,
      review.flag_resolution_note,
      review.vendor_reply,
      review.status,
    ]
      .filter(Boolean)
      .join(' ')
      .toLowerCase()

    const matchesSearch = !keyword || haystack.includes(keyword)
    const matchesStatus = filterStatus.value === 'all' || review.status === filterStatus.value
    const matchesQueue =
      filterQueue.value === 'all' ||
      (filterQueue.value === 'flagged' && review.flagged) ||
      (filterQueue.value === 'normal' && !review.flagged)

    return matchesSearch && matchesStatus && matchesQueue
  })
})

const flaggedReviews = computed(() => filteredReviews.value.filter(review => review.flagged))
const flaggedCount = computed(() => props.reviews.filter(review => review.flagged).length)

function focusFlaggedQueue() {
  filterQueue.value = 'flagged'
  requestAnimationFrame(() => {
    flaggedQueueRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  })
}

function approveReview(reviewId) {
  router.post(`/admin/reviews/${reviewId}/approve`, { _token: page.props.csrf_token }, { preserveScroll: true })
}

function rejectReview(reviewId) {
  if (!window.confirm('Reject this review? This will remove it from the approved public list.')) {
    return
  }

  router.post(`/admin/reviews/${reviewId}/reject`, { _token: page.props.csrf_token }, { preserveScroll: true })
}

function deleteReview(reviewId) {
  if (!window.confirm('Delete this review permanently?')) {
    return
  }

  const form = useForm({ _token: page.props.csrf_token })
  form.delete(`/admin/reviews/${reviewId}`, { preserveScroll: true })
}

function formatDate(dateString, withTime = false) {
  if (!dateString) return '—'

  const options = withTime
    ? { year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }
    : { year: 'numeric', month: 'short', day: 'numeric' }

  return new Date(dateString).toLocaleString(undefined, options)
}

const ReviewCard = defineComponent({
  name: 'ReviewCard',
  props: {
    review: {
      type: Object,
      required: true,
    },
  },
  emits: ['approve', 'reject', 'delete'],
  setup(cardProps, { emit }) {
    const metricList = computed(() => {
      const labels = {
        shipping_time: 'Shipping',
        customer_service: 'Service',
        quality: 'Quality',
        cost: 'Cost',
        packaging: 'Packaging',
      }

      return Object.entries(labels)
        .map(([key, label]) => ({ key, label, value: cardProps.review[key] }))
        .filter(metric => metric.value !== null && metric.value !== undefined)
    })

    const badgeClass = computed(() => {
      if (cardProps.review.status === 'approved') return 'bg-green-100 text-green-800'
      if (cardProps.review.status === 'rejected') return 'bg-red-100 text-red-800'
      return 'bg-yellow-100 text-yellow-800'
    })

    const auditBadges = computed(() => {
      const badges = []
      const resolvedByAdmin = !!(cardProps.review.flag_resolution || cardProps.review.flag_reviewed_at)
      if (resolvedByAdmin) {
        badges.push({ label: 'resolved by admin', class: 'bg-emerald-100 text-emerald-800' })
      }

      const isRemoved = cardProps.review.status === 'rejected' || cardProps.review.flag_resolution === 'rejected'
      if (isRemoved) {
        badges.push({ label: 'review removed', class: 'bg-gray-900 text-white' })
      } else if (cardProps.review.status === 'approved' && cardProps.review.flag_resolution === 'approved') {
        badges.push({ label: 'kept approved', class: 'bg-green-100 text-green-800' })
      }

      return badges
    })

    return () => h('div', { class: 'bg-white rounded-xl border border-slate-200 p-6 shadow-sm' }, [
      h('div', { class: 'flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between' }, [
        h('div', { class: 'flex-1 min-w-0' }, [
          h('div', { class: 'flex flex-wrap items-center gap-2 mb-2' }, [
            h('div', { class: 'text-lg text-slate-900' }, cardProps.review.user_name || 'Anonymous'),
            cardProps.review.verified ? h('span', { class: 'rounded-full bg-emerald-100 px-2.5 py-1 text-xs text-emerald-700' }, 'Verified purchase') : null,
            cardProps.review.flagged ? h('span', { class: 'rounded-full bg-amber-100 px-2.5 py-1 text-xs text-amber-800' }, 'Flagged by vendor') : null,
            h('span', { class: `rounded-full px-2.5 py-1 text-xs ${badgeClass.value}` }, cardProps.review.status),
            ...auditBadges.value.map(b => h('span', { class: `rounded-full px-2.5 py-1 text-xs ${b.class}` }, b.label)),
          ].filter(Boolean)),
          h('div', { class: 'flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-500 mb-3' }, [
            h('span', `Brand: ${cardProps.review.brand?.name || 'N/A'}`),
            h('span', `Rating: ${cardProps.review.rating || 0}/5`),
            h('span', formatDate(cardProps.review.created_at)),
            cardProps.review.user_email ? h('span', cardProps.review.user_email) : null,
          ].filter(Boolean)),
          h('div', { class: 'flex items-center gap-1 mb-3' }, Array.from({ length: 5 }, (_, index) =>
            h('svg', {
              class: `w-4 h-4 ${index + 1 <= (cardProps.review.rating || 0) ? 'fill-amber-400 text-amber-400' : 'text-slate-300'}`,
              fill: 'currentColor',
              viewBox: '0 0 20 20',
            }, [
              h('path', {
                d: 'M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z'
              })
            ])
          )),
          h('p', { class: 'text-slate-700 whitespace-pre-line mb-4' }, cardProps.review.comment || 'No review text provided.'),
          metricList.value.length
            ? h('div', { class: 'mb-4 grid grid-cols-2 gap-2 md:grid-cols-5' }, metricList.value.map(metric =>
                h('div', { class: 'rounded-lg bg-slate-50 p-3' }, [
                  h('div', { class: 'text-xs text-slate-500 mb-1' }, metric.label),
                  h('div', { class: 'text-sm text-slate-900' }, `${metric.value}/5`),
                ])
              ))
            : null,
          cardProps.review.flagged
            ? h('div', { class: 'mb-4 rounded-lg border border-amber-200 bg-amber-50 p-4' }, [
                h('div', { class: 'text-sm text-amber-900 mb-1' }, 'Vendor flag reason'),
                h('p', { class: 'text-sm text-amber-800 whitespace-pre-line' }, cardProps.review.flag_reason || 'No reason provided.'),
              ])
            : null,
          cardProps.review.flag_resolution
            ? h('div', { class: 'mb-4 rounded-lg border border-slate-200 bg-slate-50 p-4' }, [
                h('div', { class: 'text-sm text-slate-900 mb-1' }, 'Moderation audit'),
                h('div', { class: 'text-xs text-slate-600 mb-1' }, [
                  `Resolution: ${cardProps.review.flag_resolution}`,
                  cardProps.review.flag_reviewed_at ? ` • ${formatDate(cardProps.review.flag_reviewed_at, true)}` : '',
                  cardProps.review.flag_reviewed_by ? ` • Admin #${cardProps.review.flag_reviewed_by}` : '',
                ].join('')),
                cardProps.review.flag_resolution_note
                  ? h('p', { class: 'text-sm text-slate-700 whitespace-pre-line mt-2' }, cardProps.review.flag_resolution_note)
                  : null,
              ])
            : null,
          cardProps.review.vendor_reply
            ? h('div', { class: 'mb-4 rounded-lg border border-blue-200 bg-blue-50 p-4' }, [
                h('div', { class: 'flex flex-wrap items-center justify-between gap-2 mb-1' }, [
                  h('div', { class: 'text-sm text-blue-900' }, 'Vendor reply'),
                  h('div', { class: 'text-xs text-blue-700' }, formatDate(cardProps.review.vendor_replied_at, true)),
                ]),
                h('p', { class: 'text-sm text-blue-800 whitespace-pre-line' }, cardProps.review.vendor_reply),
              ])
            : null,
        ]),
        h('div', { class: 'flex flex-row flex-wrap gap-2 xl:w-44 xl:flex-col xl:items-stretch' }, [
          cardProps.review.status !== 'approved'
            ? h('button', {
                class: 'rounded-lg bg-green-600 px-4 py-2 text-sm text-white hover:bg-green-700 transition-colors',
                onClick: () => emit('approve', cardProps.review.id),
              }, cardProps.review.flagged ? 'Keep review' : 'Approve')
            : h('button', {
                class: 'rounded-lg border border-green-200 bg-green-50 px-4 py-2 text-sm text-green-700 hover:bg-green-100 transition-colors',
                onClick: () => emit('approve', cardProps.review.id),
              }, 'Approve again'),
          cardProps.review.status !== 'rejected'
            ? h('button', {
                class: 'rounded-lg bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700 transition-colors',
                onClick: () => emit('reject', cardProps.review.id),
              }, cardProps.review.flagged ? 'Reject review' : 'Reject')
            : h('button', {
                class: 'rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700 hover:bg-red-100 transition-colors',
                onClick: () => emit('reject', cardProps.review.id),
              }, 'Reject again'),
          h('button', {
            class: 'rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 transition-colors',
            onClick: () => emit('delete', cardProps.review.id),
          }, 'Delete'),
        ])
      ])
    ])
  },
})
</script>
