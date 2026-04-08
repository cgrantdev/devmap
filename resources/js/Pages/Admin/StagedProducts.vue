<template>
  <AdminLayout>
    <div class="mb-8">
      <h1 class="text-3xl text-slate-900 mb-2">Staged Products</h1>
      <p class="text-slate-600">
        Review products pulled by scrapers and integrations before they go live in the catalog.
      </p>
    </div>

    <!-- Status counts -->
    <div class="grid grid-cols-3 gap-4 mb-6">
      <button
        type="button"
        @click="setStatus('pending')"
        class="bg-white rounded-lg border p-5 text-left hover:shadow transition"
        :class="filters.status === 'pending' ? 'border-blue-500 ring-2 ring-blue-100' : 'border-slate-200'"
      >
        <div class="text-sm text-slate-500 mb-1">Pending review</div>
        <div class="text-3xl font-bold text-slate-900">{{ counts.pending }}</div>
      </button>
      <button
        type="button"
        @click="setStatus('approved')"
        class="bg-white rounded-lg border p-5 text-left hover:shadow transition"
        :class="filters.status === 'approved' ? 'border-green-500 ring-2 ring-green-100' : 'border-slate-200'"
      >
        <div class="text-sm text-slate-500 mb-1">Approved</div>
        <div class="text-3xl font-bold text-green-600">{{ counts.approved }}</div>
      </button>
      <button
        type="button"
        @click="setStatus('rejected')"
        class="bg-white rounded-lg border p-5 text-left hover:shadow transition"
        :class="filters.status === 'rejected' ? 'border-red-500 ring-2 ring-red-100' : 'border-slate-200'"
      >
        <div class="text-sm text-slate-500 mb-1">Rejected</div>
        <div class="text-3xl font-bold text-red-500">{{ counts.rejected }}</div>
      </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg border border-slate-200 p-4 mb-4 flex flex-wrap items-end gap-3">
      <div>
        <label class="block text-xs text-slate-500 mb-1">Brand</label>
        <select v-model="filters.brand_id" @change="apply" class="border border-slate-300 rounded px-3 py-2 text-sm">
          <option :value="null">All brands</option>
          <option v-for="b in brands" :key="b.id" :value="b.id">{{ b.name }}</option>
        </select>
      </div>
      <div>
        <label class="block text-xs text-slate-500 mb-1">Source</label>
        <select v-model="filters.source_type" @change="apply" class="border border-slate-300 rounded px-3 py-2 text-sm">
          <option :value="null">All sources</option>
          <option value="woo_api">WooCommerce API</option>
          <option value="css_scrape">CSS Scraper</option>
          <option value="xml_feed">XML Feed</option>
        </select>
      </div>
      <div class="flex-1 min-w-[200px]">
        <label class="block text-xs text-slate-500 mb-1">Search</label>
        <input
          v-model="filters.q"
          @keyup.enter="apply"
          type="text"
          placeholder="Product name"
          class="w-full border border-slate-300 rounded px-3 py-2 text-sm"
        />
      </div>
      <button @click="apply" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">Apply</button>
    </div>

    <!-- Bulk actions -->
    <div v-if="selected.length > 0" class="bg-blue-50 border border-blue-200 rounded-lg px-4 py-3 mb-3 flex items-center justify-between">
      <div class="text-sm text-blue-800">{{ selected.length }} selected</div>
      <div class="flex gap-2">
        <button @click="bulkPromote" class="px-3 py-1.5 bg-green-600 text-white text-sm rounded hover:bg-green-700">Promote selected</button>
        <button @click="bulkReject" class="px-3 py-1.5 bg-red-600 text-white text-sm rounded hover:bg-red-700">Reject selected</button>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white border border-slate-200 rounded-lg overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-600 text-left">
          <tr>
            <th class="p-3 w-8"><input type="checkbox" @change="toggleAll" :checked="allSelected" /></th>
            <th class="p-3">Product</th>
            <th class="p-3">Brand</th>
            <th class="p-3">Source</th>
            <th class="p-3">Price</th>
            <th class="p-3">Last seen</th>
            <th class="p-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in staged.data" :key="row.id" class="border-t border-slate-100 hover:bg-slate-50">
            <td class="p-3"><input type="checkbox" :value="row.id" v-model="selected" /></td>
            <td class="p-3">
              <div class="flex items-center gap-3">
                <img v-if="row.image_url" :src="row.image_url" class="w-10 h-10 object-cover rounded border" alt="" />
                <div class="w-10 h-10 bg-slate-100 rounded border" v-else></div>
                <div>
                  <div class="font-medium text-slate-900">{{ row.name }}</div>
                  <a v-if="row.source_url" :href="row.source_url" target="_blank" rel="noopener" class="text-xs text-blue-600 hover:underline">View source</a>
                </div>
              </div>
            </td>
            <td class="p-3 text-slate-700">{{ row.brand || '—' }}</td>
            <td class="p-3">
              <span class="px-2 py-0.5 rounded text-xs font-medium" :class="sourceBadge(row.source_type)">
                {{ sourceLabel(row.source_type) }}
              </span>
            </td>
            <td class="p-3">
              <div class="text-slate-900">${{ row.price || '—' }}</div>
              <div v-if="row.previous_price" class="text-xs text-amber-600">
                was ${{ row.previous_price }}
              </div>
            </td>
            <td class="p-3 text-slate-500 text-xs">{{ row.last_scraped_at || '—' }}</td>
            <td class="p-3 text-right">
              <div v-if="row.status === 'pending'" class="flex justify-end gap-1">
                <button @click="promote(row)" class="px-2 py-1 bg-green-600 text-white text-xs rounded hover:bg-green-700">Promote</button>
                <button @click="reject(row)" class="px-2 py-1 bg-red-600 text-white text-xs rounded hover:bg-red-700">Reject</button>
              </div>
              <div v-else-if="row.status === 'approved'" class="text-xs text-green-600">
                Live
                <a v-if="row.product_slug" :href="`/product/${row.product_slug}`" target="_blank" class="underline">view</a>
              </div>
              <div v-else class="text-xs text-red-500">Rejected</div>
            </td>
          </tr>
          <tr v-if="staged.data.length === 0">
            <td colspan="7" class="p-12 text-center text-slate-400">No staged products match these filters</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="staged.links && staged.links.length > 3" class="mt-4 flex gap-1">
      <a
        v-for="(link, i) in staged.links"
        :key="i"
        :href="link.url || '#'"
        v-html="link.label"
        class="px-3 py-1.5 text-sm border rounded"
        :class="link.active ? 'bg-blue-600 text-white border-blue-600' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
      />
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'

const props = defineProps({
  staged: Object,
  counts: Object,
  brands: Array,
  filters: Object,
})

const filters = ref({ ...props.filters })
const selected = ref([])

const allSelected = computed(() =>
  props.staged.data.length > 0 && selected.value.length === props.staged.data.length
)

function toggleAll(e) {
  selected.value = e.target.checked ? props.staged.data.map((r) => r.id) : []
}

function apply() {
  router.get('/admin/staged-products', filters.value, { preserveState: true, preserveScroll: true })
}

function setStatus(s) {
  filters.value.status = s
  apply()
}

function promote(row) {
  router.post(`/admin/staged-products/${row.id}/promote`, {}, { preserveScroll: true })
}

function reject(row) {
  if (!confirm(`Reject "${row.name}"?`)) return
  router.post(`/admin/staged-products/${row.id}/reject`, {}, { preserveScroll: true })
}

function bulkPromote() {
  if (!confirm(`Promote ${selected.value.length} products to live catalog?`)) return
  router.post('/admin/staged-products/bulk-promote', { ids: selected.value }, {
    preserveScroll: true,
    onSuccess: () => (selected.value = []),
  })
}

function bulkReject() {
  if (!confirm(`Reject ${selected.value.length} staged products?`)) return
  router.post('/admin/staged-products/bulk-reject', { ids: selected.value }, {
    preserveScroll: true,
    onSuccess: () => (selected.value = []),
  })
}

function sourceLabel(s) {
  return {
    woo_api: 'WooCommerce',
    css_scrape: 'Scraper',
    wp_rest: 'WordPress',
    xml_feed: 'XML feed',
  }[s] || s
}

function sourceBadge(s) {
  return {
    woo_api: 'bg-purple-100 text-purple-700',
    css_scrape: 'bg-amber-100 text-amber-700',
    wp_rest: 'bg-blue-100 text-blue-700',
    xml_feed: 'bg-teal-100 text-teal-700',
  }[s] || 'bg-slate-100 text-slate-700'
}
</script>
