<template>
  <AdminLayout>
    <PageHeader title="Products" :subtitle="`${products.total || 0} products total`">
      <template #actions>
        <Link
          href="/admin/products/create"
          class="h-9 px-4 text-[13px] font-semibold text-white bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] shadow-sm hover:-translate-y-[0.5px] transition-all flex items-center gap-2"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
          Add Product
        </Link>
      </template>
    </PageHeader>

    <!-- Flash -->
    <div v-if="$page.props.flash?.success" class="mb-4 px-4 py-3 bg-[color:var(--color-verified-bg)] border border-[#A7F3D0] text-[#065F46] text-sm">
      {{ $page.props.flash.success }}
    </div>

    <!-- Live / Hidden tabs -->
    <div class="mb-4 border-b border-[color:var(--color-hairline)] flex items-center gap-1">
      <button
        type="button"
        @click="setTab('live')"
        :class="[
          'relative h-10 px-4 text-[13px] font-semibold flex items-center gap-2 transition-colors',
          activeTab === 'live'
            ? 'text-[color:var(--color-ink)] border-b-2 border-[color:var(--color-accent-600)] -mb-px'
            : 'text-[color:var(--color-ink-subtle)] hover:text-[color:var(--color-ink-muted)]'
        ]"
      >
        Live
        <span :class="['inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-[11px] rounded-full', activeTab === 'live' ? 'bg-[color:var(--color-accent-100)] text-[color:var(--color-accent-700)]' : 'bg-[color:var(--color-hairline-soft)] text-[color:var(--color-ink-subtle)]']">{{ liveCount }}</span>
      </button>
      <button
        type="button"
        @click="setTab('hidden')"
        :class="[
          'relative h-10 px-4 text-[13px] font-semibold flex items-center gap-2 transition-colors',
          activeTab === 'hidden'
            ? 'text-[color:var(--color-ink)] border-b-2 border-slate-700 -mb-px'
            : 'text-[color:var(--color-ink-subtle)] hover:text-[color:var(--color-ink-muted)]'
        ]"
      >
        🚫 Hidden
        <span :class="['inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-[11px] rounded-full', activeTab === 'hidden' ? 'bg-slate-200 text-slate-800' : 'bg-[color:var(--color-hairline-soft)] text-[color:var(--color-ink-subtle)]']">{{ hiddenCount }}</span>
      </button>
    </div>

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 mb-4">
      <!-- Search -->
      <div class="relative flex-1 max-w-sm">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[color:var(--color-ink-subtle)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
        </svg>
        <input
          v-model="searchValue"
          @input="handleSearchInput"
          type="text"
          placeholder="Search products..."
          class="w-full h-9 pl-9 pr-4 text-sm border border-[color:var(--color-hairline)] bg-white focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15 transition-colors"
        />
      </div>
      <!-- Brand filter -->
      <select v-model="filterBrand" @change="fetchData(1)" class="h-9 px-3 text-[13px] border border-[color:var(--color-hairline)] bg-white focus:border-[color:var(--color-accent-500)] focus:outline-none">
        <option value="all">All Brands</option>
        <option v-for="brand in brands" :key="brand.id" :value="brand.id">{{ brand.name }}</option>
      </select>
      <!-- Category filter -->
      <select v-model="filterCategory" @change="fetchData(1)" class="h-9 px-3 text-[13px] border border-[color:var(--color-hairline)] bg-white focus:border-[color:var(--color-accent-500)] focus:outline-none">
        <option value="all">All Categories</option>
        <option value="uncategorized">⚠ Uncategorized Only</option>
        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
      </select>
      <!-- Missing-data filter (VA triage) -->
      <select v-model="filterMissing" @change="fetchData(1)" class="h-9 px-3 text-[13px] border border-[color:var(--color-hairline)] bg-white focus:border-[color:var(--color-accent-500)] focus:outline-none">
        <option value="all">All Products</option>
        <option value="category">⚠ Missing Category</option>
        <option value="type">⚠ Missing Type</option>
        <option value="size">⚠ Missing Size</option>
        <option value="both">⚠ Missing Cat + Size</option>
      </select>
    </div>

    <!-- Bulk-action bar — slides in when at least one row is selected -->
    <div
      v-if="selectedIds.length > 0"
      class="mb-3 flex flex-wrap items-center gap-3 px-4 py-2.5 bg-[color:var(--color-accent-50)] border border-[color:var(--color-accent-300)] rounded"
    >
      <span class="text-[13px] font-semibold text-[color:var(--color-accent-700)]">
        {{ selectedIds.length }} selected
      </span>
      <span class="text-[12px] text-[color:var(--color-ink-muted)]">·</span>
      <button @click="clearSelection" type="button" class="text-[12px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)] underline">
        Clear
      </button>
      <span class="text-[12px] text-[color:var(--color-ink-muted)]">·</span>
      <span class="text-[12px] text-[color:var(--color-ink-muted)]">Apply to selected:</span>
      <select v-model="bulkCategory" @change="bulkApplyCategory" class="h-8 px-2 text-[12px] border border-[color:var(--color-hairline)] bg-white focus:border-[color:var(--color-accent-500)] focus:outline-none rounded">
        <option value="">Set category…</option>
        <option value="__uncategorized__">— Clear category —</option>
        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
      </select>
      <select v-model="bulkType" @change="bulkApplyType" class="h-8 px-2 text-[12px] border border-[color:var(--color-hairline)] bg-white focus:border-[color:var(--color-accent-500)] focus:outline-none rounded">
        <option value="">Set type…</option>
        <option value="__clear__">— Clear type —</option>
        <option v-for="t in typeOptions" :key="t" :value="t">{{ t }}</option>
      </select>
      <button @click="bulkHide(true)" type="button" :disabled="bulkBusy" class="h-8 px-3 text-[12px] font-semibold text-white bg-slate-700 hover:bg-slate-800 rounded disabled:opacity-50">
        🚫 Hide from site
      </button>
      <button @click="bulkHide(false)" type="button" :disabled="bulkBusy" class="h-8 px-3 text-[12px] font-semibold text-slate-700 border border-slate-300 bg-white hover:bg-slate-50 rounded disabled:opacity-50">
        Unhide
      </button>
      <button @click="bulkDelete" type="button" :disabled="bulkBusy" class="h-8 px-3 text-[12px] font-semibold text-white bg-[#DC2626] hover:bg-[#B91C1C] rounded disabled:opacity-50 ml-auto">
        Delete {{ selectedIds.length }}…
      </button>
    </div>

    <!-- Table -->
    <div class="bg-white border border-[color:var(--color-hairline)] overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-[color:var(--color-hairline)] bg-[color:var(--color-bg)]">
            <th class="px-3 py-3 w-10">
              <input
                type="checkbox"
                :checked="allOnPageSelected"
                :indeterminate.prop="someOnPageSelected"
                @change="toggleSelectAllOnPage"
                class="h-4 w-4 cursor-pointer accent-[color:var(--color-accent-600)]"
                title="Select all on this page"
              />
            </th>
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Product</th>
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Brand</th>
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)] w-52">Category</th>
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)] w-36">Type</th>
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)] w-28">Size</th>
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Price</th>
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Flags</th>
            <th class="px-5 py-3 w-48 text-right text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="product in products.data || []"
            :key="product.id"
            class="border-b border-[color:var(--color-hairline-soft)] hover:bg-[color:var(--color-hairline-soft)] cursor-pointer transition-colors group"
            :class="selectedIds.includes(product.id) ? 'bg-[color:var(--color-accent-50)]' : ''"
            @click="router.visit(`/admin/products/${product.id}/edit`)"
          >
            <td class="px-3 py-3.5 w-10" @click.stop>
              <input
                type="checkbox"
                :value="product.id"
                v-model="selectedIds"
                class="h-4 w-4 cursor-pointer accent-[color:var(--color-accent-600)]"
              />
            </td>
            <td class="px-5 py-3.5">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 flex-shrink-0 bg-[color:var(--color-hairline-soft)] border border-[color:var(--color-hairline)] overflow-hidden flex items-center justify-center">
                  <img v-if="product.image_url" :src="product.image_url" :alt="product.name" class="w-full h-full object-cover" loading="lazy" />
                  <span v-else class="text-[10px] font-bold text-[color:var(--color-ink-muted)]">{{ product.name.substring(0, 2).toUpperCase() }}</span>
                </div>
                <div class="min-w-0">
                  <div class="flex items-center gap-1.5">
                    <span class="text-[13px] font-medium text-[color:var(--color-ink)] truncate max-w-xs" :title="product.name">{{ product.name }}</span>
                  </div>
                  <!-- Derived public display name preview. Only shown when it
                       differs from the raw import (i.e. category + size are set).
                       Type renders as a colored chip to mirror the public site. -->
                  <div
                    v-if="product.display_name && product.display_name !== product.name"
                    class="flex items-center gap-1.5 mt-0.5 max-w-xs"
                  >
                    <span
                      class="text-[11px] text-[color:var(--color-accent-600)] truncate"
                      :title="`Shows on the public site as: ${product.display_name}`"
                    >→ {{ product.display_name }}</span>
                    <span
                      v-if="product.product_type === 'Capsule'"
                      class="flex-shrink-0 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-blue-100 text-blue-800 border border-blue-200"
                    >Capsule</span>
                    <span
                      v-else-if="product.product_type === 'Nasal Spray'"
                      class="flex-shrink-0 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200"
                    >Nasal Spray</span>
                    <span
                      v-else-if="product.product_type === 'Kit'"
                      class="flex-shrink-0 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-800 border border-amber-200"
                    >Kit</span>
                  </div>
                </div>
              </div>
            </td>
            <td class="px-5 py-3.5 text-[13px] text-[color:var(--color-ink-muted)]">
              {{ product.vendor_name || product.brand_name || '—' }}
            </td>
            <td class="px-5 py-3.5" @click.stop>
              <select
                :value="product.hidden ? '__hidden__' : (product.category_id || '')"
                @change="onCategoryChange(product, $event.target.value)"
                :class="[
                  'w-full px-2 py-1.5 text-[12px] border rounded bg-white transition-colors focus:outline-none focus:ring-1',
                  product.hidden
                    ? 'border-slate-400 text-slate-700 bg-slate-100 focus:ring-slate-400'
                    : (!product.category_id
                        ? 'border-rose-300 text-rose-700 focus:ring-rose-300 bg-rose-50'
                        : 'border-[color:var(--color-hairline)] text-[color:var(--color-ink-muted)] hover:border-[color:var(--color-accent-400)] focus:ring-[color:var(--color-accent-400)]')
                ]"
                :title="product.hidden ? 'Hidden from public site' : (!product.category_id ? 'No category — assign one' : 'Change category')"
              >
                <option value="">— Uncategorized —</option>
                <option value="__hidden__">🚫 Hide from site</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                  {{ cat.name }}
                </option>
              </select>
            </td>
            <td class="px-5 py-3.5" @click.stop>
              <select
                :value="product.product_type || ''"
                @change="updateField(product.id, 'product_type', $event.target.value || null)"
                :class="[
                  'w-full px-2 py-1.5 text-[12px] border rounded bg-white transition-colors focus:outline-none focus:ring-1',
                  !product.product_type
                    ? 'border-amber-300 text-amber-700 focus:ring-amber-300 bg-amber-50'
                    : 'border-[color:var(--color-hairline)] text-[color:var(--color-ink-muted)] hover:border-[color:var(--color-accent-400)] focus:ring-[color:var(--color-accent-400)]'
                ]"
                :title="!product.product_type ? 'No type — assign one' : 'Change type'"
              >
                <option value="">—</option>
                <option v-for="t in typeOptions" :key="t" :value="t">{{ t }}</option>
              </select>
            </td>
            <td class="px-5 py-3.5" @click.stop>
              <select
                :value="sizeOptions.includes(normalizedSize(product.size_mg)) ? normalizedSize(product.size_mg) : ''"
                @change="updateField(product.id, 'size_mg', $event.target.value || null)"
                class="w-full px-2 py-1.5 text-[12px] border border-[color:var(--color-hairline)] rounded bg-white text-[color:var(--color-ink-muted)] hover:border-[color:var(--color-accent-400)] focus:outline-none focus:ring-1 focus:ring-[color:var(--color-accent-400)] ui-mono"
              >
                <option value="">—</option>
                <option v-for="size in sizeOptions" :key="size" :value="size">{{ size }}</option>
              </select>
            </td>
            <td class="px-5 py-3.5">
              <span class="ui-mono text-[13px] text-[color:var(--color-ink)]">${{ product.price || '0.00' }}</span>
              <span v-if="product.original_price && product.original_price > product.price" class="ml-1 text-[11px] text-[color:var(--color-ink-subtle)] line-through ui-mono">${{ product.original_price }}</span>
            </td>
            <td class="px-5 py-3.5">
              <div class="flex flex-wrap gap-1">
                <span v-if="product.featured" class="inline-flex px-1.5 py-0.5 text-[10px] font-semibold bg-[color:var(--color-accent-50)] text-[color:var(--color-accent-600)]">Featured</span>
                <span v-if="product.lab_tested" class="inline-flex px-1.5 py-0.5 text-[10px] font-semibold bg-[color:var(--color-verified-bg)] text-[#065F46]">Lab Tested</span>
                <span v-if="product.hidden" class="inline-flex px-1.5 py-0.5 text-[10px] font-semibold bg-[color:var(--color-hairline-soft)] text-[color:var(--color-ink-muted)]">Hidden</span>
              </div>
            </td>
            <td class="px-5 py-3.5 text-right whitespace-nowrap" @click.stop>
              <div class="inline-flex items-center gap-1.5">
                <a
                  :href="publicProductUrl(product)"
                  target="_blank"
                  rel="noopener"
                  class="inline-flex items-center gap-1.5 h-8 px-3 text-[12px] font-semibold text-[color:var(--color-ink-muted)] border border-[color:var(--color-hairline)] hover:border-[color:var(--color-ink-subtle)] hover:text-[color:var(--color-ink)] rounded transition-colors"
                  title="Open the public product page in a new tab"
                >
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6M15 3h6v6M10 14L21 3"/>
                  </svg>
                  View
                </a>
                <button
                  type="button"
                  @click="confirmDelete(product)"
                  class="inline-flex items-center gap-1.5 h-8 px-3 text-[12px] font-semibold text-white bg-[#DC2626] hover:bg-[#B91C1C] rounded transition-colors"
                  title="Delete this product"
                >
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z"/><path d="M10 11v6M14 11v6"/>
                  </svg>
                  Delete
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="!products.data || products.data.length === 0" class="py-12 text-center text-sm text-[color:var(--color-ink-subtle)]">
        No products found.
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="products.last_page > 1" class="mt-4 flex items-center justify-between py-3">
      <span class="text-[12px] text-[color:var(--color-ink-subtle)]">
        Showing {{ products.from || 0 }}-{{ products.to || 0 }} of {{ products.total || 0 }}
      </span>
      <div class="flex gap-1">
        <button
          @click="fetchData(products.current_page - 1)"
          :disabled="products.current_page === 1"
          class="h-8 px-3 text-[12px] font-medium border border-[color:var(--color-hairline)] bg-white hover:bg-[color:var(--color-hairline-soft)] disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >Prev</button>
        <button
          @click="fetchData(products.current_page + 1)"
          :disabled="products.current_page === products.last_page"
          class="h-8 px-3 text-[12px] font-medium border border-[color:var(--color-hairline)] bg-white hover:bg-[color:var(--color-hairline-soft)] disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >Next</button>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import PageHeader from '@/components/admin/PageHeader.vue'

const props = defineProps({
  products: { type: Object, default: () => ({ data: [], total: 0 }) },
  brands: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
  tab: { type: String, default: 'live' },
  live_count: { type: Number, default: 0 },
  hidden_count: { type: Number, default: 0 },
})

const activeTab = ref(props.tab || 'live')
const liveCount = computed(() => props.live_count)
const hiddenCount = computed(() => props.hidden_count)

function setTab(tab) {
  if (activeTab.value === tab) return
  activeTab.value = tab
  clearSelection()
  fetchData(1)
}

const searchValue = ref('')
const filterBrand = ref('all')
const filterCategory = ref('all')
const filterMissing = ref('all')

// Product formats (vial, capsule, nasal spray, other). Stored as a
// free-text varchar but constrained to this list both in the inline
// dropdown and in the controller's `in:` validation rule.
const typeOptions = ['Peptide', 'Capsule', 'Nasal Spray', 'Kit', 'Other']

// Common research peptide vial sizes + blend ratios.
// Each option is the literal string stored in size_mg.
const sizeOptions = [
  // Singles 0.5–100mg (granular at the small end where peptides actually vary)
  '0.5mg', '1mg', '2mg', '2.5mg', '5mg', '10mg', '15mg',
  '20mg', '25mg', '30mg', '50mg', '100mg',
  // Larger singles 200–1000mg in 100mg increments (NAD+, methylene blue, etc.)
  '200mg', '300mg', '400mg', '500mg', '600mg', '700mg', '800mg', '900mg', '1000mg',
  // Common blend ratios from real vendor listings
  '5mg/5mg', '10mg/10mg', '50mg/10mg/10mg', '50mg/10mg/10mg/10mg',
]

/**
 * Normalize size_mg for the dropdown's :value binding. Handles three cases:
 *   "10mg"   → "10mg"   (already normalized — return as-is)
 *   "5mg/5mg"→ "5mg/5mg"
 *   "10.00"  → "10mg"   (legacy decimal, append unit + strip trailing zeros)
 *   "10"     → "10mg"
 */
function normalizedSize(value) {
  if (!value) return ''
  const str = String(value)
  if (/[a-zA-Z\/]/.test(str)) return str // already has unit or blend separator
  if (!Number.isNaN(Number(str))) {
    // Strip trailing zeros: "10.00" → "10", "9.50" → "9.5"
    const num = Number(str)
    return `${num}mg`
  }
  return str
}

/**
 * Inline single-field update from the Category / Size dropdowns.
 * Auto-saves on change. Uses preserveScroll so the VA can keep working
 * down the list without losing their position.
 */
function updateField(productId, field, value) {
  router.patch(`/admin/products/${productId}/quick-update`, {
    [field]: value,
    _token: usePage().props.csrf_token,
  }, {
    preserveScroll: true,
    preserveState: true,
  })
}

/**
 * Category-dropdown change handler with one special value:
 *   '__hidden__' → flip the product's `hidden` flag on so it stops
 *                  appearing on the public site. Category is left
 *                  untouched so unhiding later restores the original.
 * Any other value (including '' / a real category id) sets the
 * category and unhides the product in one PATCH.
 */
function onCategoryChange(product, value) {
  const payload = { _token: usePage().props.csrf_token }
  if (value === '__hidden__') {
    payload.hidden = true
  } else {
    payload.product_category_id = value || null
    if (product.hidden) payload.hidden = false
  }
  router.patch(`/admin/products/${product.id}/quick-update`, payload, {
    preserveScroll: true,
    preserveState: true,
  })
}

let searchTimeout = null

function handleSearchInput() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => fetchData(1), 500)
}

// ---- Bulk-selection state ----------------------------------------------
const selectedIds = ref([])
const bulkCategory = ref('')
const bulkType = ref('')
const bulkBusy = ref(false)

const visibleIds = computed(() => (props.products?.data || []).map(p => p.id))
const allOnPageSelected = computed(() =>
  visibleIds.value.length > 0 && visibleIds.value.every(id => selectedIds.value.includes(id))
)
const someOnPageSelected = computed(() =>
  visibleIds.value.some(id => selectedIds.value.includes(id)) && !allOnPageSelected.value
)

function toggleSelectAllOnPage(e) {
  if (e.target.checked) {
    // Union: keep selections from other pages, add this page's ids
    const set = new Set([...selectedIds.value, ...visibleIds.value])
    selectedIds.value = [...set]
  } else {
    selectedIds.value = selectedIds.value.filter(id => !visibleIds.value.includes(id))
  }
}

function clearSelection() {
  selectedIds.value = []
  bulkCategory.value = ''
  bulkType.value = ''
}

// When the page of products changes (search/filter/pagination), clear
// selections so the user doesn't accidentally apply a bulk action to
// rows they can no longer see.
watch(() => props.products?.data, () => { clearSelection() })

function bulkApplyCategory() {
  if (!bulkCategory.value || selectedIds.value.length === 0) return
  const isClear = bulkCategory.value === '__uncategorized__'
  const label = isClear
    ? 'Clear the category on'
    : `Set the category on`
  if (!confirm(`${label} ${selectedIds.value.length} product${selectedIds.value.length === 1 ? '' : 's'}?`)) {
    bulkCategory.value = ''
    return
  }
  bulkBusy.value = true
  router.patch('/admin/products/bulk-update', {
    ids: selectedIds.value,
    product_category_id: isClear ? null : bulkCategory.value,
    _token: usePage().props.csrf_token,
  }, {
    preserveScroll: true,
    onFinish: () => {
      bulkBusy.value = false
      bulkCategory.value = ''
    },
  })
}

function bulkApplyType() {
  if (!bulkType.value || selectedIds.value.length === 0) return
  const isClear = bulkType.value === '__clear__'
  const label = isClear ? 'Clear the type on' : 'Set the type on'
  if (!confirm(`${label} ${selectedIds.value.length} product${selectedIds.value.length === 1 ? '' : 's'}?`)) {
    bulkType.value = ''
    return
  }
  bulkBusy.value = true
  router.patch('/admin/products/bulk-update', {
    ids: selectedIds.value,
    product_type: isClear ? null : bulkType.value,
    _token: usePage().props.csrf_token,
  }, {
    preserveScroll: true,
    onFinish: () => {
      bulkBusy.value = false
      bulkType.value = ''
    },
  })
}

function bulkHide(hide) {
  if (selectedIds.value.length === 0) return
  const verb = hide ? 'Hide' : 'Unhide'
  if (!confirm(`${verb} ${selectedIds.value.length} product${selectedIds.value.length === 1 ? '' : 's'}?`)) return
  bulkBusy.value = true
  router.patch('/admin/products/bulk-update', {
    ids: selectedIds.value,
    hidden: hide,
    _token: usePage().props.csrf_token,
  }, {
    preserveScroll: true,
    onFinish: () => { bulkBusy.value = false },
  })
}

function bulkDelete() {
  if (selectedIds.value.length === 0) return
  const n = selectedIds.value.length
  if (!confirm(`Permanently delete ${n} product${n === 1 ? '' : 's'}?\n\nThis cannot be undone.`)) return
  bulkBusy.value = true
  router.delete('/admin/products/bulk-delete', {
    data: {
      ids: selectedIds.value,
      _token: usePage().props.csrf_token,
    },
    preserveScroll: true,
    onFinish: () => { bulkBusy.value = false },
  })
}
// ------------------------------------------------------------------------

/**
 * Public URL for a product. Always points at peptidemap.com (the live
 * marketing host) regardless of which admin subdomain you're on, since
 * VAs will usually want to QA how the row renders to real visitors.
 * Falls back gracefully if slug is missing.
 */
function publicProductUrl(product) {
  const slug = product.slug || String(product.name || '').toLowerCase()
    .replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '').replace(/-+/g, '-')
  return `https://peptidemap.com/product/${slug}/${product.id}`
}

function confirmDelete(product) {
  const msg = `Delete "${product.name}"?\n\nThis permanently removes the product and any associated click logs. This cannot be undone.`
  if (!confirm(msg)) return
  router.delete(`/admin/products/${product.id}`, {
    preserveScroll: true,
    preserveState: false, // force refresh so the row drops out of the list
  })
}

function fetchData(page = props.products?.current_page || 1) {
  router.get('/admin/products', {
    page,
    per_page: props.products?.per_page || 20,
    tab: activeTab.value,
    search: searchValue.value || null,
    brand: filterBrand.value !== 'all' ? filterBrand.value : null,
    category: filterCategory.value !== 'all' ? filterCategory.value : null,
    missing: filterMissing.value !== 'all' ? filterMissing.value : null,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}
</script>
