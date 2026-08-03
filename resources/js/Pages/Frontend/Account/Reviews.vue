<template>
  <ModernLayout>
    <div class="max-w-4xl mx-auto px-5 lg:px-10 py-10">
      <h1 class="ui-display text-2xl font-semibold tracking-tight text-[color:var(--color-ink)] mb-1">My reviews</h1>
      <p class="text-sm text-[color:var(--color-ink-muted)] mb-8">Reviews you've left for vendors, including ones still pending moderation.</p>

      <div
        v-if="flashSuccess"
        class="mb-6 px-4 py-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm"
      >
        {{ flashSuccess }}
      </div>
      <div
        v-if="flashError"
        class="mb-6 px-4 py-3 bg-red-50 border border-red-200 text-red-800 text-sm"
      >
        {{ flashError }}
      </div>

      <div v-if="!reviewsByVendor || reviewsByVendor.length === 0" class="text-center py-16 border border-[color:var(--color-hairline)] bg-white">
        <p class="text-[color:var(--color-ink-muted)]">You haven't left any reviews yet.</p>
        <Link href="/brands" class="inline-block mt-3 text-[13px] font-medium text-[color:var(--color-accent-600)] hover:text-[color:var(--color-accent-700)]">Browse vendors →</Link>
      </div>

      <div v-else class="space-y-8">
        <div v-for="group in reviewsByVendor" :key="group[0]?.brand_id">
          <h2 class="text-[14px] font-semibold text-[color:var(--color-ink)] mb-3">
            <Link v-if="group[0]?.brand_slug" :href="`/brand/${group[0].brand_slug}/products`" class="hover:text-[color:var(--color-accent-600)]">{{ group[0]?.brand_name }}</Link>
            <span v-else>{{ group[0]?.brand_name }}</span>
          </h2>

          <div class="space-y-3">
            <div
              v-for="review in group"
              :key="review.id"
              class="border border-[color:var(--color-hairline)] bg-white p-4"
            >
              <div class="flex items-start justify-between gap-3 mb-2">
                <div class="flex items-center gap-2">
                  <div class="flex items-center gap-0.5">
                    <svg v-for="i in 5" :key="i" width="14" height="14" viewBox="0 0 20 20" :class="i <= review.rating ? 'text-yellow-400 fill-yellow-400' : 'text-gray-300'" fill="currentColor"><path d="M10 1l2.8 5.7 6.2.9-4.5 4.4 1.1 6.3L10 15.3 4.4 18.3l1.1-6.3L1 7.6l6.2-.9L10 1z"/></svg>
                  </div>
                  <span
                    class="ui-mono text-[10px] uppercase tracking-[0.08em] px-2 py-0.5 rounded-full font-semibold"
                    :class="statusBadge(review.status).classes"
                  >{{ statusBadge(review.status).label }}</span>
                  <span v-if="review.verified" class="inline-flex items-center gap-1 text-[10px] uppercase tracking-[0.06em] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><path d="M12 1l8 4v6c0 5.5-3.4 9.9-8 11-4.6-1.1-8-5.5-8-11V5l8-4z"/></svg>
                    Verified via PMAP
                  </span>
                </div>
                <span class="text-[12px] text-[color:var(--color-ink-subtle)]">{{ formatDate(review.created_at) }}</span>
              </div>

              <p v-if="review.review" class="text-[13px] text-[color:var(--color-ink-muted)] leading-relaxed mb-3">{{ review.review }}</p>
              <p v-if="review.vendor_reply" class="text-[12px] text-[color:var(--color-ink-subtle)] bg-[color:var(--color-hairline-soft)] p-2 mb-3">
                <span class="font-semibold">Vendor reply:</span> {{ review.vendor_reply }}
              </p>

              <div class="flex items-center gap-3">
                <button
                  v-if="review.status !== 'rejected'"
                  type="button"
                  class="text-[12px] font-medium text-[color:var(--color-accent-600)] hover:text-[color:var(--color-accent-700)]"
                  @click="openEdit(review)"
                >
                  Edit
                </button>
                <button
                  v-if="review.status !== 'rejected'"
                  type="button"
                  class="text-[12px] font-medium text-red-600 hover:text-red-700"
                  @click="destroyReview(review)"
                >
                  Delete
                </button>
                <span v-if="review.status === 'rejected'" class="text-[12px] text-[color:var(--color-ink-subtle)]">Rejected reviews can't be edited or deleted.</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Edit modal -->
      <div v-if="editing" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4" @click.self="editing = null">
        <div class="bg-white w-full max-w-md p-6 shadow-xl">
          <h3 class="text-[16px] font-semibold text-[color:var(--color-ink)] mb-4">Edit review</h3>
          <div class="space-y-3 mb-4">
            <div v-for="field in gradingFields" :key="field.key">
              <label class="block text-[12px] font-medium text-[color:var(--color-ink-muted)] mb-1">{{ field.label }}</label>
              <div class="flex gap-1">
                <button
                  v-for="n in 5" :key="n" type="button"
                  @click="editForm[field.key] = n"
                  class="w-7 h-7 text-[12px] border"
                  :class="editForm[field.key] >= n ? 'bg-slate-700 text-white border-slate-700' : 'bg-white text-[color:var(--color-ink-muted)] border-[color:var(--color-hairline)]'"
                >{{ n }}</button>
              </div>
            </div>
            <div>
              <label class="block text-[12px] font-medium text-[color:var(--color-ink-muted)] mb-1">Review (optional)</label>
              <textarea v-model="editForm.review" rows="3" class="w-full border border-[color:var(--color-hairline)] px-3 py-2 text-[13px]"></textarea>
            </div>
          </div>
          <div class="flex justify-end gap-2">
            <button type="button" class="px-4 py-2 text-[13px] text-[color:var(--color-ink-muted)]" @click="editing = null">Cancel</button>
            <button type="button" :disabled="editForm.processing" class="px-4 py-2 text-[13px] font-semibold text-white bg-slate-700 hover:bg-slate-600" @click="submitEdit">
              {{ editForm.processing ? 'Saving…' : 'Save changes' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </ModernLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, useForm, usePage, router } from '@inertiajs/vue3'
import ModernLayout from '@/Pages/Layouts/ModernLayout.vue'

defineProps({
  reviewsByVendor: {
    type: Array,
    default: () => [],
  },
})

const page = usePage()
const flashSuccess = page.props.flash?.success || null
const flashError = page.props.flash?.error || null

const gradingFields = [
  { key: 'shipping_time', label: 'Shipping time' },
  { key: 'customer_service', label: 'Customer service' },
  { key: 'quality', label: 'Quality' },
  { key: 'cost', label: 'Cost' },
  { key: 'packaging', label: 'Packaging' },
]

const editing = ref(null)
const editForm = useForm({
  shipping_time: 0,
  customer_service: 0,
  quality: 0,
  cost: 0,
  packaging: 0,
  review: '',
})

function openEdit(review) {
  editing.value = review
  editForm.shipping_time = review.shipping_time || 0
  editForm.customer_service = review.customer_service || 0
  editForm.quality = review.quality || 0
  editForm.cost = review.cost || 0
  editForm.packaging = review.packaging || 0
  editForm.review = review.review || ''
}

function submitEdit() {
  if (!editing.value) return
  editForm.put(`/account/reviews/${editing.value.id}`, {
    preserveScroll: true,
    onSuccess: () => { editing.value = null },
    onError: () => { alert('Please rate all categories before saving.') },
  })
}

function destroyReview(review) {
  if (!confirm('Delete this review? This can\'t be undone.')) return
  router.delete(`/account/reviews/${review.id}`, { preserveScroll: true })
}

function statusBadge(status) {
  switch (status) {
    case 'approved':
      return { label: 'Published', classes: 'bg-emerald-50 text-emerald-700 border border-emerald-200' }
    case 'rejected':
      return { label: 'Rejected', classes: 'bg-red-50 text-red-700 border border-red-200' }
    default:
      return { label: 'Pending review', classes: 'bg-amber-50 text-amber-700 border border-amber-200' }
  }
}

function formatDate(iso) {
  if (!iso) return ''
  try {
    return new Date(iso).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
  } catch {
    return iso
  }
}
</script>
