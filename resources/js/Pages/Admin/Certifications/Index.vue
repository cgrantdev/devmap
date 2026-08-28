<template>
  <Layout>
    <Head><title>Certifications · Admin — Peptidemap</title></Head>
    <div class="max-w-[1200px] mx-auto px-6 py-8 space-y-6">
      <div class="flex items-baseline justify-between border-b border-gray-200 pb-4">
        <div>
          <div class="text-[10px] uppercase tracking-[0.14em] font-semibold text-gray-400 mb-1">Vendor claims</div>
          <h1 class="text-2xl font-semibold text-gray-900">Certification queue</h1>
          <p class="text-[12px] text-gray-500 mt-1">Review vendor-submitted docs. Approved claims light up badges + post to Discord.</p>
        </div>
        <div class="flex gap-1">
          <Link v-for="s in ['pending', 'approved', 'rejected']" :key="s" :href="`/admin/certifications?status=${s}`" :class="['px-3 py-1.5 text-[12px] rounded font-medium capitalize', filterStatus === s ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200']">{{ s }}</Link>
        </div>
      </div>

      <div v-if="claims.length === 0" class="text-center py-12 text-gray-500 italic border border-dashed border-gray-200 rounded-lg">
        No {{ filterStatus }} claims.
      </div>

      <div v-for="c in claims" :key="c.id" class="bg-white border border-gray-200 rounded-lg p-5 space-y-3">
        <div class="flex items-start justify-between gap-3 flex-wrap">
          <div class="min-w-0">
            <a :href="`/brand/${c.brand.slug}`" target="_blank" class="text-lg font-semibold text-gray-900 hover:text-indigo-700 hover:underline">{{ c.brand.name }}</a>
            <div class="flex items-center gap-2 mt-1">
              <span class="text-[11px] uppercase tracking-wider font-semibold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-100">{{ c.type_label }}</span>
              <span :class="statusPill(c.status).cls">● {{ c.status }}</span>
            </div>
          </div>
          <div class="text-[11px] text-gray-500 text-right">
            <div>Submitted {{ formatDate(c.created_at) }}</div>
            <div v-if="c.submitted_by">by {{ c.submitted_by.name }} · {{ c.submitted_by.email }}</div>
          </div>
        </div>

        <div v-if="c.notes" class="text-[13px] text-gray-700 bg-gray-50 border border-gray-200 rounded p-3 whitespace-pre-wrap">{{ c.notes }}</div>

        <div class="flex items-center gap-3 flex-wrap">
          <a v-if="c.has_document" :href="`/admin/certifications/${c.id}/document`" target="_blank" class="inline-flex items-center gap-1.5 text-[12px] font-semibold text-indigo-700 hover:text-indigo-900 underline">
            📎 {{ c.document_original_name || 'View document' }}
          </a>
          <span v-else class="text-[12px] text-red-600 italic">No document uploaded — reject with a note asking for one.</span>
        </div>

        <!-- Action row (pending only) -->
        <div v-if="c.status === 'pending'" class="pt-3 border-t border-gray-100">
          <textarea v-model="notesDraft[c.id]" rows="2" placeholder="Optional note (required for reject)…" class="w-full text-[13px] border border-gray-200 rounded p-2 focus:outline-none focus:border-indigo-400"></textarea>
          <div class="flex justify-end gap-2 mt-2">
            <button @click="reject(c)" class="text-[12px] font-semibold text-red-700 hover:text-red-900 border border-red-200 hover:bg-red-50 px-3 py-1.5 rounded">Reject</button>
            <button @click="approve(c)" class="text-[12px] font-semibold text-white bg-emerald-600 hover:bg-emerald-700 px-3 py-1.5 rounded">Approve →</button>
          </div>
        </div>

        <!-- Verified state -->
        <div v-else-if="c.verified_by" class="pt-3 border-t border-gray-100 text-[11px] text-gray-500">
          {{ c.status === 'approved' ? 'Approved' : 'Rejected' }} by {{ c.verified_by.name }} · {{ formatDate(c.verified_at) }}
          <div v-if="c.admin_notes" class="text-[12px] text-gray-700 mt-1 italic">"{{ c.admin_notes }}"</div>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { reactive } from 'vue'
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'
import Layout from '../Layout.vue'

const props = defineProps({ claims: Array, filterStatus: String })
const notesDraft = reactive({})

function statusPill(s) {
  const map = {
    pending:  { cls: 'text-[10px] uppercase tracking-wider font-bold text-amber-800 bg-amber-100 px-2 py-0.5 rounded-full' },
    approved: { cls: 'text-[10px] uppercase tracking-wider font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded-full' },
    rejected: { cls: 'text-[10px] uppercase tracking-wider font-bold text-red-800 bg-red-100 px-2 py-0.5 rounded-full' },
  }
  return map[s] || map.pending
}
function formatDate(iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' })
}
function approve(c) {
  const form = useForm({ _token: usePage().props.csrf_token, admin_notes: notesDraft[c.id] || '' })
  form.post(`/admin/certifications/${c.id}/approve`, { preserveScroll: true })
}
function reject(c) {
  const note = notesDraft[c.id] || ''
  if (!note.trim()) { alert('Please leave a note explaining why so the vendor can fix + resubmit.'); return }
  const form = useForm({ _token: usePage().props.csrf_token, admin_notes: note })
  form.post(`/admin/certifications/${c.id}/reject`, { preserveScroll: true })
}
</script>
