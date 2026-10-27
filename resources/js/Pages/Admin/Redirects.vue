<template>
  <AdminLayout>
    <PageHeader title="Redirects" subtitle="301/302 forwards for retired URLs — SEO agency tool">
      <template #actions>
        <Link href="/admin/seo" class="text-[13px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)]">← SEO dashboard</Link>
      </template>
    </PageHeader>

    <div class="max-w-5xl mx-auto px-6 py-6 space-y-6">
      <div class="grid grid-cols-2 gap-4">
        <StatTile :n="summary.total" label="Active redirects" />
        <StatTile :n="summary.total_hits" label="Total hits" />
      </div>

      <!-- New redirect form -->
      <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-6">
        <h2 class="text-sm font-semibold text-[color:var(--color-ink)] mb-4">Add redirect</h2>
        <form @submit.prevent="submit" class="grid grid-cols-12 gap-3">
          <div class="col-span-5">
            <label class="block text-[11px] font-medium text-[color:var(--color-ink-muted)] mb-1">From path</label>
            <input v-model="form.from_path" required placeholder="/old-category/retatrutide" class="ui-input" />
          </div>
          <div class="col-span-5">
            <label class="block text-[11px] font-medium text-[color:var(--color-ink-muted)] mb-1">To path or URL</label>
            <input v-model="form.to_path" required placeholder="/compare/retatrutide" class="ui-input" />
          </div>
          <div class="col-span-1">
            <label class="block text-[11px] font-medium text-[color:var(--color-ink-muted)] mb-1">Code</label>
            <select v-model="form.status_code" class="ui-input">
              <option :value="301">301</option>
              <option :value="302">302</option>
              <option :value="307">307</option>
              <option :value="308">308</option>
            </select>
          </div>
          <div class="col-span-1 flex items-end">
            <button type="submit" class="w-full bg-[color:var(--color-ink)] text-white rounded-lg px-3 py-2 text-sm font-medium">Add</button>
          </div>
          <div class="col-span-12">
            <input v-model="form.note" placeholder="Note (optional) — e.g. 'Audit 10/27 missing page'" class="ui-input" />
          </div>
        </form>
      </div>

      <!-- Search + table -->
      <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-6">
        <div class="flex justify-between items-center mb-4 gap-3">
          <h2 class="text-sm font-semibold text-[color:var(--color-ink)]">Active redirects</h2>
          <input v-model="searchQ" @input="debouncedSearch" placeholder="Search..." class="ui-input max-w-xs" />
        </div>
        <table class="w-full text-[13px]">
          <thead class="text-[11px] text-[color:var(--color-ink-muted)] uppercase tracking-wider border-b border-[color:var(--color-hairline)]">
            <tr>
              <th class="text-left py-2">From</th>
              <th class="text-left py-2">To</th>
              <th class="text-center py-2 w-16">Code</th>
              <th class="text-center py-2 w-20">Hits</th>
              <th class="text-right py-2 w-16"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in redirects.data" :key="r.id" class="border-b border-[color:var(--color-hairline-soft)]">
              <td class="py-2 pr-3 font-mono text-xs truncate max-w-[260px]" :title="r.from_path">{{ r.from_path }}</td>
              <td class="py-2 pr-3 font-mono text-xs truncate max-w-[260px]" :title="r.to_path">{{ r.to_path }}</td>
              <td class="py-2 text-center">{{ r.status_code }}</td>
              <td class="py-2 text-center text-[color:var(--color-ink-muted)]">{{ r.hits }}</td>
              <td class="py-2 text-right">
                <button @click="destroy(r)" class="text-xs text-red-600 hover:underline">Remove</button>
              </td>
            </tr>
            <tr v-if="redirects.data.length === 0">
              <td colspan="5" class="py-6 text-center text-[color:var(--color-ink-muted)]">No redirects yet. Add one above.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import PageHeader from '@/components/admin/PageHeader.vue'
import AdminLayout from './Layout.vue'

const props = defineProps({
  redirects: Object,
  q: String,
  summary: Object,
})

const form = useForm({
  from_path: '',
  to_path: '',
  status_code: 301,
  note: '',
})

const searchQ = ref(props.q || '')
let searchTimeout = null
function debouncedSearch() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    router.get('/admin/redirects', { q: searchQ.value }, { preserveState: true, replace: true })
  }, 300)
}

function submit() {
  form.post('/admin/redirects', {
    onSuccess: () => form.reset(),
  })
}

function destroy(r) {
  if (!confirm(`Remove redirect from ${r.from_path}?`)) return
  router.delete(`/admin/redirects/${r.id}`, { preserveScroll: true })
}

const StatTile = {
  props: ['n', 'label'],
  template: `
    <div class="bg-white rounded-xl border border-[color:var(--color-hairline)] p-5">
      <div class="text-2xl font-semibold text-[color:var(--color-ink)]">{{ n }}</div>
      <div class="text-[11px] text-[color:var(--color-ink-muted)] uppercase tracking-wider mt-1">{{ label }}</div>
    </div>
  `,
}
</script>
