<template>
  <AdminLayout>
    <PageHeader title="Coupon SEO" subtitle="Title, meta, H1, copy and FAQs for each /coupon/{vendor} page">
      <template #actions>
        <Link href="/admin/seo" class="text-[13px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]">← SEO dashboard</Link>
      </template>
    </PageHeader>

    <div class="max-w-5xl mx-auto px-6 py-6 space-y-4">
      <p v-if="!ready" class="px-4 py-3 rounded-lg bg-amber-50 border border-amber-200 text-[13px] text-amber-900">
        Coupon page editing turns on after the pending database update. You can browse the list now.
      </p>

      <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-4">
        <label for="coupon-search" class="sr-only">Search vendors</label>
        <input id="coupon-search" v-model="searchQ" @input="debouncedSearch" placeholder="Search vendor…" class="ui-input w-full" />
      </div>

      <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-[color:var(--color-bg-soft)] text-[11px] uppercase tracking-wide text-[color:var(--color-ink-muted)]">
            <tr>
              <th class="text-left px-4 py-3">Vendor</th>
              <th class="text-left px-4 py-3">Code</th>
              <th class="text-left px-4 py-3">Custom copy</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[color:var(--color-hairline-soft)]">
            <tr v-for="r in rows" :key="r.id">
              <td class="px-4 py-3">
                <div class="font-semibold text-[color:var(--color-ink)] text-[13px]">{{ r.name }}</div>
                <div class="text-[11px] font-mono text-[color:var(--color-ink-muted)]">{{ r.url }}</div>
              </td>
              <td class="px-4 py-3 font-mono text-[12px]">{{ r.code }}<span v-if="r.percent_off" class="text-[color:var(--color-ink-muted)]"> · {{ r.percent_off }}%</span></td>
              <td class="px-4 py-3 text-[12px]">
                <span v-if="r.has_copy" class="text-emerald-700 font-medium">Yes</span>
                <span v-else class="text-[color:var(--color-ink-subtle)]">Default</span>
              </td>
              <td class="px-4 py-3 text-right whitespace-nowrap">
                <Link :href="`/admin/seo/coupons/${r.id}/edit`" class="text-[12px] font-semibold text-[color:var(--color-accent-700)] hover:underline">Edit →</Link>
                <a :href="r.url" target="_blank" class="ml-3 text-[11px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]">View ↗</a>
              </td>
            </tr>
            <tr v-if="!rows.length">
              <td colspan="4" class="px-4 py-10 text-center text-[color:var(--color-ink-muted)]">No vendors with a coupon page match.</td>
            </tr>
          </tbody>
        </table>
      </div>
      <p class="text-[11px] text-[color:var(--color-ink-muted)]">Only active vendors with a coupon code have a coupon page. Codes themselves are managed by the Peptidemap team.</p>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import PageHeader from '@/components/admin/PageHeader.vue'
import AdminLayout from './Layout.vue'

const props = defineProps({
  rows: { type: Array, default: () => [] },
  q: { type: String, default: '' },
  ready: { type: Boolean, default: false },
})

const searchQ = ref(props.q || '')
let searchTimeout = null
function debouncedSearch() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    router.get('/admin/seo/coupons', { q: searchQ.value || undefined }, { preserveState: true, replace: true })
  }, 300)
}
</script>
