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
        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
      </select>
    </div>

    <!-- Table -->
    <div class="bg-white border border-[color:var(--color-hairline)] overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-[color:var(--color-hairline)] bg-[color:var(--color-bg)]">
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Product</th>
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Brand</th>
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Category</th>
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Price</th>
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Rating</th>
            <th class="px-5 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Flags</th>
            <th class="px-5 py-3 w-20"></th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="product in products.data || []"
            :key="product.id"
            class="border-b border-[color:var(--color-hairline-soft)] hover:bg-[color:var(--color-hairline-soft)] cursor-pointer transition-colors group"
            @click="router.visit(`/admin/products/${product.id}/edit`)"
          >
            <td class="px-5 py-3.5">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 flex-shrink-0 bg-[color:var(--color-hairline-soft)] border border-[color:var(--color-hairline)] overflow-hidden flex items-center justify-center">
                  <img v-if="product.image_url" :src="product.image_url" :alt="product.name" class="w-full h-full object-cover" loading="lazy" />
                  <span v-else class="text-[10px] font-bold text-[color:var(--color-ink-muted)]">{{ product.name.substring(0, 2).toUpperCase() }}</span>
                </div>
                <div class="min-w-0">
                  <div class="text-[13px] font-medium text-[color:var(--color-ink)] truncate max-w-xs">{{ product.name }}</div>
                  <div v-if="product.dosage || product.size_mg" class="text-[11px] text-[color:var(--color-ink-subtle)]">{{ product.dosage || product.size_mg }}</div>
                </div>
              </div>
            </td>
            <td class="px-5 py-3.5 text-[13px] text-[color:var(--color-ink-muted)]">
              {{ product.vendor_name || product.brand_name || '—' }}
            </td>
            <td class="px-5 py-3.5 text-[13px] text-[color:var(--color-ink-muted)]">
              {{ product.category_name || '—' }}
            </td>
            <td class="px-5 py-3.5">
              <span class="ui-mono text-[13px] text-[color:var(--color-ink)]">${{ product.price || '0.00' }}</span>
              <span v-if="product.original_price && product.original_price > product.price" class="ml-1 text-[11px] text-[color:var(--color-ink-subtle)] line-through ui-mono">${{ product.original_price }}</span>
            </td>
            <td class="px-5 py-3.5">
              <div class="flex items-center gap-1">
                <svg class="w-3.5 h-3.5 text-[color:var(--color-caution)]" viewBox="0 0 20 20" fill="currentColor"><path d="M10 1l2.8 5.7 6.2.9-4.5 4.4 1.1 6.3L10 15.3 4.4 18.3l1.1-6.3L1 7.6l6.2-.9L10 1z"/></svg>
                <span class="ui-mono text-[13px] text-[color:var(--color-ink)]">{{ product.rating || '0.0' }}</span>
              </div>
            </td>
            <td class="px-5 py-3.5">
              <div class="flex flex-wrap gap-1">
                <span v-if="product.featured" class="inline-flex px-1.5 py-0.5 text-[10px] font-semibold bg-[color:var(--color-accent-50)] text-[color:var(--color-accent-600)]">Featured</span>
                <span v-if="product.lab_tested" class="inline-flex px-1.5 py-0.5 text-[10px] font-semibold bg-[color:var(--color-verified-bg)] text-[#065F46]">Lab Tested</span>
                <span v-if="product.hidden" class="inline-flex px-1.5 py-0.5 text-[10px] font-semibold bg-[color:var(--color-hairline-soft)] text-[color:var(--color-ink-muted)]">Hidden</span>
              </div>
            </td>
            <td class="px-5 py-3.5">
              <svg class="w-4 h-4 text-[color:var(--color-ink-subtle)] group-hover:text-[color:var(--color-accent-600)] group-hover:translate-x-0.5 transition-all" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
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
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import PageHeader from '@/components/admin/PageHeader.vue'

const props = defineProps({
  products: { type: Object, default: () => ({ data: [], total: 0 }) },
  brands: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
})

const searchValue = ref('')
const filterBrand = ref('all')
const filterCategory = ref('all')

let searchTimeout = null

function handleSearchInput() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => fetchData(1), 500)
}

function fetchData(page = props.products?.current_page || 1) {
  router.get('/admin/products', {
    page,
    per_page: props.products?.per_page || 20,
    search: searchValue.value || null,
    brand: filterBrand.value !== 'all' ? filterBrand.value : null,
    category: filterCategory.value !== 'all' ? filterCategory.value : null,
  }, {
    preserveState: true,
    preserveScroll: true,
  })
}
</script>
