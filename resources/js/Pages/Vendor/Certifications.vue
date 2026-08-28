<template>
  <VendorLayout>
    <Head><title>Certifications — Vendor Dashboard</title></Head>
    <div class="max-w-3xl mx-auto px-6 py-8 space-y-6">
      <div class="border-b border-slate-200 pb-4">
        <h1 class="text-2xl font-semibold text-slate-900">Certifications & Testing</h1>
        <p class="text-[13px] text-slate-600 mt-1">
          Submit proof of cGMP-compliant manufacturing or independent lab-testing to earn a verified badge on your storefront + product cards. Approved by our team within 3 business days.
        </p>
      </div>

      <div v-for="[type, label] in Object.entries(availableTypes)" :key="type" class="bg-white border border-slate-200 rounded-lg p-5">
        <div class="flex items-baseline justify-between gap-3 flex-wrap">
          <h2 class="text-lg font-semibold text-slate-900">{{ label }}</h2>
          <span v-if="claims[type]" :class="statusPill(claims[type].status).cls">● {{ claims[type].status }}</span>
        </div>

        <div v-if="claims[type]?.status === 'approved'" class="mt-2 text-[13px] text-emerald-700">
          Verified on {{ formatDate(claims[type].verified_at) }}. The badge is now live on your storefront.
        </div>
        <div v-else-if="claims[type]?.status === 'pending'" class="mt-2 text-[13px] text-amber-800">
          Submitted {{ formatDate(claims[type].submitted_at) }} — under review.
        </div>
        <div v-else-if="claims[type]?.status === 'rejected'" class="mt-2 text-[13px] text-red-800">
          Rejected. Reviewer note: <em>"{{ claims[type].admin_notes }}"</em>
          Fix the issue and resubmit below — it will replace the previous submission.
        </div>

        <!-- Upload form (hidden when approved) -->
        <form v-if="!claims[type] || claims[type].status !== 'approved'" @submit.prevent="submit(type)" class="mt-4 pt-4 border-t border-slate-100 space-y-3">
          <div>
            <label class="block text-[12px] font-semibold text-slate-700 mb-1">Supporting document</label>
            <input :ref="el => (fileRefs[type] = el)" type="file" accept="application/pdf,image/png,image/jpeg,image/webp" class="block w-full text-sm text-slate-700 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-slate-700 file:text-white hover:file:bg-slate-600 file:cursor-pointer cursor-pointer" />
            <p class="mt-1 text-[11px] text-slate-500">PDF, PNG, JPG, or WebP · up to 15 MB. Facility audit, cert, or batch COAs.</p>
          </div>
          <div>
            <label class="block text-[12px] font-semibold text-slate-700 mb-1">Notes for the reviewer (optional)</label>
            <textarea v-model="notesDraft[type]" rows="3" placeholder="Anything the reviewer should know…" class="w-full text-[13px] border border-slate-200 rounded p-2 focus:outline-none focus:border-slate-400"></textarea>
          </div>
          <div class="flex items-center justify-end">
            <button type="submit" class="text-[13px] font-semibold text-white bg-slate-800 hover:bg-slate-900 px-4 py-2 rounded">Submit for review →</button>
          </div>
        </form>
      </div>
    </div>
  </VendorLayout>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import VendorLayout from '@/Pages/Layouts/ModernLayout.vue'

const props = defineProps({ brand: Object, claims: Object, availableTypes: Object })
const notesDraft = reactive({})
const fileRefs = reactive({})

function statusPill(s) {
  const map = {
    pending:  'text-[10px] uppercase tracking-wider font-bold text-amber-800 bg-amber-100 px-2 py-0.5 rounded-full',
    approved: 'text-[10px] uppercase tracking-wider font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded-full',
    rejected: 'text-[10px] uppercase tracking-wider font-bold text-red-800 bg-red-100 px-2 py-0.5 rounded-full',
  }
  return { cls: map[s] || map.pending }
}
function formatDate(iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' })
}
function submit(type) {
  const fileInput = fileRefs[type]
  const file = fileInput?.files?.[0]
  if (!file) { alert('Please attach a document.'); return }
  const form = useForm({
    _token: usePage().props.csrf_token,
    type,
    document: file,
    notes: notesDraft[type] || '',
  })
  form.post('/vendor/certifications', {
    forceFormData: true,
    onSuccess: () => {
      notesDraft[type] = ''
      if (fileInput) fileInput.value = ''
    },
  })
}
</script>
