<template>
    <Layout>
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Products</h1>
          <p class="text-gray-600 mt-2">Manage your vendor product catalog.</p>
        </div>
  
        <Link
          href="/vendor/import"
          class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition"
        >
          Import / Update Products
        </Link>
      </div>
  
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <div class="text-sm text-gray-500">Total Products</div>
          <div class="mt-2 text-3xl font-bold text-gray-900">{{ stats.totalProducts || 0 }}</div>
        </div>
  
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <div class="text-sm text-gray-500">Active Products</div>
          <div class="mt-2 text-3xl font-bold text-gray-900">{{ stats.activeProducts || 0 }}</div>
        </div>
  
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <div class="text-sm text-gray-500">Hidden Products</div>
          <div class="mt-2 text-3xl font-bold text-gray-900">{{ stats.hiddenProducts || 0 }}</div>
        </div>
  
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <div class="text-sm text-gray-500">Average Rating</div>
          <div class="mt-2 text-3xl font-bold text-gray-900">{{ stats.averageRating || '0.0' }}</div>
        </div>
      </div>
  
  <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-200">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <h2 class="text-xl font-semibold text-gray-900">All Products</h2>
          <p class="text-sm text-gray-600 mt-1">Search, filter, sort, and take quick actions.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2 w-full lg:w-auto">
          <div class="relative">
            <input
              v-model="localFilters.q"
              type="text"
              placeholder="Search name or URL…"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              @keyup.enter="applyFilters"
            />
          </div>

          <select
            v-model="localFilters.visibility"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            @change="applyFilters"
          >
            <option value="all">All visibility</option>
            <option value="visible">Visible</option>
            <option value="hidden">Hidden</option>
          </select>

          <select
            v-model="localFilters.status"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            @change="applyFilters"
          >
            <option value="all">All statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>

          <select
            v-model="localFilters.sort"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            @change="applyFilters"
          >
            <option value="newest">Newest</option>
            <option value="oldest">Oldest</option>
            <option value="price_asc">Price (low → high)</option>
            <option value="price_desc">Price (high → low)</option>
            <option value="rating_desc">Rating</option>
            <option value="reviews_desc">Reviews</option>
          </select>
        </div>
      </div>
        </div>
  
    <div class="px-6 py-4 border-b border-gray-200 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="flex flex-wrap items-center gap-2">
        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
          <input type="checkbox" class="rounded border-gray-300" :checked="allSelected" @change="toggleSelectAll" />
          Select all
        </label>
        <span class="text-xs text-gray-500">{{ selectedIds.length }} selected</span>
      </div>

      <div class="flex flex-wrap gap-2">
        <button
          class="px-3 py-2 rounded-lg border border-gray-300 text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50"
          :disabled="selectedIds.length === 0"
          @click="bulkSetHidden(true)"
        >
          Hide selected
        </button>
        <button
          class="px-3 py-2 rounded-lg border border-gray-300 text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-50"
          :disabled="selectedIds.length === 0"
          @click="bulkSetHidden(false)"
        >
          Show selected
        </button>
        <button
          class="px-3 py-2 rounded-lg bg-blue-600 text-sm text-white hover:bg-blue-700"
          @click="applyFilters"
        >
          Apply
        </button>
        <button
          class="px-3 py-2 rounded-lg border border-gray-300 text-sm text-gray-700 hover:bg-gray-50"
          @click="resetFilters"
        >
          Reset
        </button>
      </div>
    </div>

    <div v-if="productList.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 p-6">
          <div
        v-for="product in productList"
            :key="product.id"
            class="rounded-xl border border-gray-200 overflow-hidden hover:shadow-md transition bg-white"
          >
            <div class="aspect-[4/3] bg-gray-100">
              <img
                v-if="product.image_url"
                :src="product.image_url"
                :alt="product.name"
                class="w-full h-full object-cover"
                loading="lazy"
              />
              <div v-else class="w-full h-full flex items-center justify-center text-sm text-gray-400">
                No image
              </div>
            </div>
  
        <div class="p-5">
              <div class="flex items-start justify-between gap-3">
            <div class="flex items-start gap-3 min-w-0">
              <input
                type="checkbox"
                class="mt-1 rounded border-gray-300"
                :checked="selectedIds.includes(product.id)"
                @change="toggleSelected(product.id)"
              />

              <h3 class="text-base font-semibold text-gray-900 line-clamp-2">
                {{ product.name }}
              </h3>
            </div>
  
                <span
                  class="shrink-0 inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                  :class="product.hidden ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'"
                >
                  {{ product.hidden ? 'Hidden' : (product.status || 'Active') }}
                </span>
              </div>
  
          <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
            <span
              class="inline-flex items-center rounded-full px-2 py-1 font-medium"
              :class="product.auto_scraped ? 'bg-slate-100 text-slate-700' : 'bg-indigo-100 text-indigo-800'"
            >
              {{ product.auto_scraped ? 'Imported' : 'Manual' }}
            </span>
            <span v-if="product.auto_scraped && product.auto_update" class="inline-flex items-center rounded-full px-2 py-1 font-medium bg-emerald-100 text-emerald-800">
              Auto-updating
            </span>
            <span v-if="product.last_scraped_at" class="text-gray-500">
              Last import: {{ formatDate(product.last_scraped_at) }}
            </span>
          </div>

              <div class="mt-3 flex items-center gap-2">
                <span class="text-lg font-bold text-gray-900">{{ formatPrice(product.price) }}</span>
                <span v-if="product.original_price" class="text-sm text-gray-400 line-through">
                  {{ formatPrice(product.original_price) }}
                </span>
              </div>
  
              <div class="mt-3 flex items-center gap-3 text-sm text-gray-600">
                <span>⭐ {{ product.rating_average || '0.0' }}</span>
                <span>•</span>
                <span>{{ product.rating_count || 0 }} reviews</span>
              </div>
  
          <div class="mt-5 flex flex-wrap items-center gap-3">
                <a
                  v-if="product.product_url"
                  :href="product.product_url"
                  target="_blank"
                  class="inline-flex items-center px-3 py-2 rounded-lg border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                  View Product
                </a>
  
            <button
              class="inline-flex items-center px-3 py-2 rounded-lg border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50"
              @click="setHidden(product.id, !product.hidden)"
            >
              {{ product.hidden ? 'Show' : 'Hide' }}
            </button>

                <Link
                  href="/vendor/import"
                  class="inline-flex items-center px-3 py-2 rounded-lg bg-gray-900 text-white text-sm font-medium hover:bg-black"
                >
                  Edit Catalog
                </Link>
              </div>
            </div>
          </div>
        </div>
  
        <div v-else class="px-6 py-12 text-center">
          <div class="text-lg font-semibold text-gray-900">No products found</div>
          <p class="mt-2 text-sm text-gray-600">Start by importing products into your vendor catalog.</p>
  
          <Link
            href="/vendor/import"
            class="inline-flex items-center mt-5 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700"
          >
            Import Products
          </Link>
        </div>

    <div v-if="paginationLinks.length" class="px-6 py-5 border-t border-gray-200 flex flex-wrap gap-2">
      <button
        v-for="link in paginationLinks"
        :key="link.label"
        class="px-3 py-2 rounded-lg border text-sm"
        :class="link.active ? 'bg-gray-900 text-white border-gray-900' : 'border-gray-300 text-gray-700 hover:bg-gray-50'"
        :disabled="!link.url"
        v-html="link.label"
        @click="visit(link.url)"
      />
    </div>
      </div>
    </Layout>
  </template>
  
  <script setup>
  import { computed, reactive, ref, watch } from 'vue'
  import { Link, router, usePage } from '@inertiajs/vue3'
  import Layout from './Layout.vue'
  
  const props = defineProps({
    stats: {
      type: Object,
      default: () => ({
        totalProducts: 0,
        activeProducts: 0,
        hiddenProducts: 0,
        averageRating: '0.0'
      })
    },
    products: {
      type: Object,
      default: () => ({ data: [], links: [], meta: {} })
    },
    filters: {
      type: Object,
      default: () => ({ q: '', visibility: 'all', status: 'all', sort: 'newest', per_page: 24 })
    }
  })

  const page = usePage()
  const selectedIds = ref([])

  const localFilters = reactive({
    q: props.filters?.q ?? '',
    visibility: props.filters?.visibility ?? 'all',
    status: props.filters?.status ?? 'all',
    sort: props.filters?.sort ?? 'newest',
    per_page: props.filters?.per_page ?? 24,
  })

  watch(() => props.filters, (next) => {
    localFilters.q = next?.q ?? ''
    localFilters.visibility = next?.visibility ?? 'all'
    localFilters.status = next?.status ?? 'all'
    localFilters.sort = next?.sort ?? 'newest'
    localFilters.per_page = next?.per_page ?? 24
  })

  const productList = computed(() => props.products?.data ?? [])
  const paginationLinks = computed(() => (props.products?.links ?? []).filter(l => l.label !== 'Previous' && l.label !== 'Next'))

  const allSelected = computed(() => productList.value.length > 0 && selectedIds.value.length === productList.value.length)

  function toggleSelected(id) {
    selectedIds.value = selectedIds.value.includes(id)
      ? selectedIds.value.filter(x => x !== id)
      : [...selectedIds.value, id]
  }

  function toggleSelectAll(e) {
    const checked = !!e.target.checked
    selectedIds.value = checked ? productList.value.map(p => p.id) : []
  }

  function applyFilters() {
    router.get('/vendor/products', { ...localFilters }, { preserveScroll: true, replace: true })
  }

  function resetFilters() {
    localFilters.q = ''
    localFilters.visibility = 'all'
    localFilters.status = 'all'
    localFilters.sort = 'newest'
    applyFilters()
  }

  function visit(url) {
    if (!url) return
    router.visit(url, { preserveScroll: true, replace: true })
  }

  function setHidden(productId, hidden) {
    router.patch(`/vendor/products/${productId}/hidden`, { hidden, _token: page.props.csrf_token }, { preserveScroll: true })
  }

  function bulkSetHidden(hidden) {
    if (selectedIds.value.length === 0) return
    router.post('/vendor/products/bulk-hidden', { product_ids: selectedIds.value, hidden, _token: page.props.csrf_token }, { preserveScroll: true })
  }

  function formatDate(dateString) {
    if (!dateString) return '—'
    return new Date(dateString).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })
  }
  
  function formatPrice(value) {
    const number = Number(value)
  
    if (Number.isNaN(number) || number <= 0) {
      return '$0.00'
    }
  
    return `$${number.toFixed(2)}`
  }
  </script>