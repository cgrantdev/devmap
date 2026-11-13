<template>
  <ModernLayout>
    <!-- Product Listing Section -->
    <section class="bg-white">
      <!-- Header. Compound name + optional agency-authored H1
           override + inline cross-link pills. Intro paragraph is
           admin-editable at /admin/seo/categories/{id}/edit. -->
      <div class="max-w-[1280px] mx-auto px-5 lg:px-10 pt-8 lg:pt-12 pb-5">
        <h1 class="ui-display text-3xl lg:text-4xl font-semibold tracking-tight text-[color:var(--color-ink)]">
          {{ compoundContent?.h1 || productName }}
        </h1>
        <div
          v-if="compoundContent?.intro"
          class="prose prose-sm max-w-3xl mt-4 text-[color:var(--color-ink-muted)] leading-relaxed"
          v-html="compoundContent.intro"
        ></div>
        <!-- Price summary for the listings shown (filters apply). The
             best-value tile re-sorts the grid by $/mg. -->
        <div v-if="priceSummary" class="mt-5 grid grid-cols-2 lg:grid-cols-4 gap-2.5">
          <div class="rounded-[12px] border border-[color:var(--color-hairline)] bg-white px-4 py-3">
            <div class="text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Vendors</div>
            <div class="ui-display mt-1.5 text-[22px] font-semibold leading-none tracking-tight text-[color:var(--color-ink)]">{{ priceSummary.vendors }}</div>
            <div class="mt-1.5 truncate text-[12px] text-[color:var(--color-ink-muted)]">with listings shown</div>
          </div>
          <div class="rounded-[12px] border border-[color:var(--color-hairline)] bg-white px-4 py-3">
            <div class="text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Listings</div>
            <div class="ui-display mt-1.5 text-[22px] font-semibold leading-none tracking-tight text-[color:var(--color-ink)]">{{ priceSummary.listings }}</div>
            <div class="mt-1.5 truncate text-[12px] text-[color:var(--color-ink-muted)]">sizes, kits and formats</div>
          </div>
          <div v-if="priceSummary.cheapest" class="rounded-[12px] border border-[color:var(--color-hairline)] bg-white px-4 py-3 min-w-0">
            <div class="text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Lowest price</div>
            <div class="ui-display mt-1.5 text-[22px] font-semibold leading-none tracking-tight text-[color:var(--color-ink)]">{{ priceSummary.cheapest.currency_symbol || '$' }}{{ money(priceSummary.cheapest.final_price) }}</div>
            <div class="mt-1.5 truncate text-[12px] text-[color:var(--color-ink-muted)]">{{ priceSummary.cheapest.brand?.name }}<template v-if="priceSummary.cheapest.size_mg"> · {{ formatSize(priceSummary.cheapest.size_mg) }}</template></div>
          </div>
          <button
            v-if="priceSummary.bestValue"
            type="button"
            class="ui-focus min-w-0 text-left rounded-[12px] border border-emerald-200 bg-emerald-50/70 px-4 py-3 hover:border-emerald-400 transition-colors"
            @click="applySort('value', 'asc')"
          >
            <div class="text-[11px] uppercase tracking-[0.08em] font-semibold text-emerald-700">Best value</div>
            <div class="ui-display mt-1.5 text-[22px] font-semibold leading-none tracking-tight text-emerald-800">{{ priceSummary.bestValue.currency_symbol || '$' }}{{ money(priceSummary.bestValue.price_per_mg) }}<span class="text-[13px] font-medium">/mg</span></div>
            <div class="mt-1.5 truncate text-[12px] text-emerald-800/80">{{ priceSummary.bestValue.brand?.name }}<template v-if="priceSummary.bestValue.size_mg"> · {{ formatSize(priceSummary.bestValue.size_mg) }}</template> · sort by value →</div>
          </button>
        </div>

        <CatalogCrossLinks :groups="catalogLinks?.groups || []" />
      </div>

      <div class="max-w-[1280px] mx-auto px-5 lg:px-10 pb-8">
        <LocationFilterChip />
        <div class="mb-6 space-y-3">
          <!-- Search, sort, vendor and count on one row. -->
          <div class="flex flex-wrap items-center gap-2.5">
            <div class="relative flex-1 min-w-[180px] max-w-sm">
              <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[color:var(--color-ink-subtle)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
              <input
                v-model="searchQuery"
                @input="handleSearchInput"
                type="text"
                placeholder="Search these listings…"
                class="w-full h-10 pl-9 pr-3 text-[14px] rounded-[10px] border border-[color:var(--color-hairline)] bg-white focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15"
              />
            </div>

            <select
              class="h-10 pl-3 pr-8 text-[13px] rounded-[10px] border border-[color:var(--color-hairline)] bg-white focus:border-[color:var(--color-accent-500)] focus:outline-none"
              :value="sortValue"
              aria-label="Sort listings"
              @change="handleSortChange"
            >
              <option value="featured|desc">Featured</option>
              <option value="value|asc">Best value ($/mg)</option>
              <option value="price|asc">Price: Low → High</option>
              <option value="price|desc">Price: High → Low</option>
              <option value="popular|desc">Most Popular</option>
            </select>

            <select
              v-if="filterOptions.brands && filterOptions.brands.length > 1"
              class="h-10 pl-3 pr-8 text-[13px] rounded-[10px] border border-[color:var(--color-hairline)] bg-white focus:border-[color:var(--color-accent-500)] focus:outline-none max-w-[220px]"
              :value="selectedBrand"
              aria-label="Filter by vendor"
              @change="handleBrandChange"
            >
              <option value="">All vendors</option>
              <option v-for="b in filterOptions.brands" :key="b.id" :value="b.id">{{ b.name }}</option>
            </select>

            <span class="ml-auto text-[12px] text-[color:var(--color-ink-subtle)] ui-mono">{{ products.total || products.data?.length || 0 }} listings</span>
          </div>

          <!-- Product Format filter — Colin PMAP Sep 30 (#13). Peptide is
               the implicit default so null product_type rows count under
               it (see the backend). One row; scrolls sideways on phones. -->
          <div class="flex flex-nowrap items-center gap-1.5 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden pb-0.5">
            <span class="shrink-0 w-12 text-[11px] uppercase tracking-[0.1em] font-semibold text-[color:var(--color-ink-subtle)]">Format</span>
            <button
              v-for="fmt in FORMAT_OPTIONS"
              :key="fmt"
              type="button"
              @click="toggleFormat(fmt)"
              :aria-pressed="selectedFormats.has(fmt)"
              :class="[
                'shrink-0 whitespace-nowrap h-8 px-3 text-[12px] rounded-full border transition-colors',
                selectedFormats.has(fmt)
                  ? 'bg-[color:var(--color-ink)] text-white border-[color:var(--color-ink)]'
                  : 'bg-white text-[color:var(--color-ink-muted)] border-[color:var(--color-hairline)] hover:border-[color:var(--color-ink-subtle)]',
              ]"
            >
              {{ fmt }}
            </button>
            <button
              v-if="selectedFormats.size > 0"
              type="button"
              @click="clearFormats"
              class="shrink-0 text-[11px] text-[color:var(--color-ink-subtle)] hover:text-[color:var(--color-ink-muted)] underline ml-1"
            >clear</button>
          </div>

          <!-- Dosage size filter — multi-select chips (Colin PMAP Sep 22),
               data-driven: only sizes present for this compound. The most
               listed sizes show first; the rest sit behind "+N more" so
               the bar stays one line. "10 mg" and "10mg" share a chip. -->
          <div class="flex flex-nowrap md:flex-wrap items-center gap-1.5 overflow-x-auto md:overflow-visible [scrollbar-width:none] [&::-webkit-scrollbar]:hidden pb-0.5 md:pb-0">
            <span class="shrink-0 w-12 text-[11px] uppercase tracking-[0.1em] font-semibold text-[color:var(--color-ink-subtle)]">Size</span>
            <button
              v-for="group in visibleSizeGroups"
              :key="group.key"
              type="button"
              @click="toggleSizeGroup(group)"
              :aria-pressed="isSizeGroupSelected(group)"
              :class="[
                'shrink-0 whitespace-nowrap h-8 px-3 text-[12px] rounded-full border transition-colors ui-mono',
                isSizeGroupSelected(group)
                  ? 'bg-[color:var(--color-ink)] text-white border-[color:var(--color-ink)]'
                  : 'bg-white text-[color:var(--color-ink-muted)] border-[color:var(--color-hairline)] hover:border-[color:var(--color-ink-subtle)]',
              ]"
            >
              {{ group.label }}
            </button>
            <button
              v-if="hiddenSizeCount > 0"
              type="button"
              @click="showAllSizes = true"
              class="shrink-0 whitespace-nowrap h-8 px-3 text-[12px] font-semibold rounded-full text-[color:var(--color-accent-700)] hover:bg-[color:var(--color-accent-50)] transition-colors"
            >+{{ hiddenSizeCount }} more</button>
            <button
              v-else-if="showAllSizes && sizeGroups.length > SIZE_CHIP_LIMIT + 2"
              type="button"
              @click="showAllSizes = false"
              class="shrink-0 whitespace-nowrap h-8 px-3 text-[12px] font-semibold rounded-full text-[color:var(--color-ink-muted)] hover:bg-[color:var(--color-hairline-soft)] transition-colors"
            >Fewer</button>
            <button
              v-if="selectedSizes.size > 0"
              type="button"
              @click="clearSizes"
              class="shrink-0 text-[11px] text-[color:var(--color-ink-subtle)] hover:text-[color:var(--color-ink-muted)] underline ml-1"
            >clear</button>
          </div>
        </div>

        <!-- Main Content Area -->
        <div class="w-full">
            <div class="mb-4 hidden">
              <span class="font-roboto font-normal text-base leading-normal tracking-normal text-gray-800">{{ products.total }} products found</span>
            </div>

            <!-- Product Grid -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4 lg:gap-5 mb-8">
              <ProductCard
                v-for="product in products.data"
                :key="product.id"
                :name="product.display_name || product.name"
                :image-url="product.image_url"
                :price="product.price"
                :discount-price="product.discount_price"
                :brand-name="product.brand?.name"
                :rating-average="product.rating_average"
                :rating-count="product.rating_count"
                :category-name="product.category?.name || ''"
                :size-mg="product.size_mg"
                :availability="product.availability"
                :purity="product.purity"
                :product-type="product.product_type"
                :brand-discount-percent="product.brand_discount_percent"
                :brand-coupon-code="product.brand_coupon_code"
                :currency-symbol="product.currency_symbol || '$'"
                :raw-name="product.name || ''"
                :price-per-mg="product.price_per_mg ?? null"
                :best-value="!!product.best_value"
                :to="productUrl(product)"
              />
            </div>
          </div>
        </div>
    </section>

    <!-- Agency-authored FAQs. Visible accordion + FAQPage JSON-LD is
         emitted server-side for Google rich results. -->
    <section v-if="compoundContent?.faqs?.length" class="border-t border-[color:var(--color-hairline)] bg-[color:var(--color-bg)]">
      <div class="max-w-[1024px] mx-auto px-6 lg:px-10 py-12">
        <div class="text-[11px] uppercase tracking-[0.12em] font-semibold text-[color:var(--color-ink-subtle)] mb-3">Frequently asked</div>
        <h2 class="ui-display text-2xl md:text-3xl font-semibold text-[color:var(--color-ink)] mb-6">{{ productName }} FAQ</h2>
        <div class="space-y-3">
          <details v-for="(f, i) in compoundContent.faqs" :key="i" class="group bg-white rounded-[10px] border border-[color:var(--color-hairline)] overflow-hidden">
            <summary class="ui-focus cursor-pointer list-none p-4 flex items-center justify-between gap-3 hover:bg-[color:var(--color-hairline-soft)] transition-colors">
              <span class="text-[15px] font-semibold text-[color:var(--color-ink)]">{{ f.q }}</span>
              <svg class="w-4 h-4 text-[color:var(--color-ink-subtle)] flex-shrink-0 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
            </summary>
            <div class="px-4 pb-4 text-[14px] text-[color:var(--color-ink-muted)] leading-relaxed whitespace-pre-line">{{ f.a }}</div>
          </details>
        </div>
      </div>
    </section>
  </ModernLayout>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch, h, watchEffect } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import ModernLayout from '@/Pages/Layouts/ModernLayout.vue'
import LocationFilterChip from '@/components/ui/LocationFilterChip.vue'
import ProductCard from '@/components/ProductCard.vue'
import MainButton from '@/components/MainButton.vue'
import CatalogCrossLinks from '@/components/CatalogCrossLinks.vue'
import { compareSizeInfo, sizeInfo } from '@/components/price/priceFormat'
import { stripSiteSuffix } from '@/utils/pageTitle'

const props = defineProps({
  productName: String,
  slug: String,
  products: Object,
  filterOptions: Object,
  priceRange: Object,
  filters: Object,
  sort: String,
  sortDir: String,
  search: String,
  category: Object,
  catalogLinks: {
    type: Object,
    default: () => ({ groups: [] }),
  },
  compoundContent: {
    type: Object,
    default: () => ({ h1: null, intro: null, faqs: [] }),
  },
  seo: {
    type: Object,
    default: () => ({
      title: null,
      description: null,
      og_title: null,
      og_description: null,
      og_image: null,
      image: null,
      url: null,
      canonical: null,
    })
  }
})

const page = usePage()

// Computed values for reactive SEO updates (automatically from category data)
const title = computed(() => {
  const siteName = page.props.site_name || 'Peptidemap'
  if (props.seo?.title) {
    return stripSiteSuffix(props.seo.title, siteName)
  }
  return stripSiteSuffix(props.productName || props.category?.name || 'Products', siteName)
})

const description = computed(() => {
  // Use SEO description if provided, otherwise generate from category description
  if (props.seo?.description) {
    return props.seo.description
  }
  if (props.category?.description) {
    // Truncate to ~155 chars
    const desc = props.category.description.replace(/\s+/g, ' ').trim()
    return desc.length > 155 ? desc.substring(0, 155) + '...' : desc
  }
  return 'Browse ' + (props.productName || props.category?.name || 'products') + ' peptides. Compare products, prices, and vendors.'
})

const url = computed(() => {
  return props.seo?.url || page.url
})

const ogTitle = computed(() => {
  return props.seo?.og_title || title.value
})

const ogDescription = computed(() => {
  return props.seo?.og_description || description.value
})

const ogImage = computed(() => {
  // Use SEO og_image if provided, otherwise use category image
  return props.seo?.og_image || props.seo?.image || null
})

const canonical = computed(() => {
  return props.seo?.canonical || url.value
})

// Watch for SEO changes and update document title and meta tags immediately
watchEffect(() => {
  // Update document title
  document.title = title.value
  
  // Update meta description
  let metaDescription = document.querySelector('meta[name="description"]')
  if (!metaDescription) {
    metaDescription = document.createElement('meta')
    metaDescription.setAttribute('name', 'description')
    document.head.appendChild(metaDescription)
  }
  metaDescription.setAttribute('content', description.value)
  
  // Update canonical link
  let canonicalLink = document.querySelector('link[rel="canonical"]')
  if (!canonicalLink) {
    canonicalLink = document.createElement('link')
    canonicalLink.setAttribute('rel', 'canonical')
    document.head.appendChild(canonicalLink)
  }
  canonicalLink.setAttribute('href', canonical.value)
  
  // Update Open Graph tags
  const updateMetaTag = (property, content) => {
    if (!content) return // Don't set empty values
    let meta = document.querySelector(`meta[property="${property}"]`)
    if (!meta) {
      meta = document.createElement('meta')
      meta.setAttribute('property', property)
      document.head.appendChild(meta)
    }
    meta.setAttribute('content', content)
  }
  
  updateMetaTag('og:title', ogTitle.value)
  updateMetaTag('og:description', ogDescription.value)
  updateMetaTag('og:url', url.value)
  if (ogImage.value) {
    updateMetaTag('og:image', ogImage.value)
  }
})

// Hero background lazy loading
const heroBgRef = ref(null)
const heroBgLoaded = ref(false)

// Best value only runs one way (lowest $/mg first), whatever sort_dir says.
const sortValue = computed(() => (props.sort === 'value'
  ? 'value|asc'
  : `${props.sort || 'featured'}|${props.sortDir || 'desc'}`))

// Canonical product URL; the legacy /product/{slug}/{id} form 301s.
function productUrl(product) {
  return product.brand?.slug
    ? `/product/${product.brand.slug}/${product.slug}/${product.id}`
    : `/product/${product.slug}/${product.id}`
}
// Multi-select via a Set. URL carries comma-separated ?size=A,B.
// Colin PMAP Sep 30 (#11): revert to strictly data-driven — show
// only sizes that actually exist in the DB for this category, in
// the sort order the controller returned. Prior preset chips were
// polluting compound pages with unavailable sizes.
const initialSizes = (new URLSearchParams(window.location.search).get('size') || '')
  .split(',')
  .map(s => s.trim())
  .filter(Boolean)
const selectedSizes = ref(new Set(initialSizes))
const selectedBrand = ref(new URLSearchParams(window.location.search).get('brand') || '')

const mergedSizes = computed(() =>
  (props.filterOptions?.sizes || [])
    .map((s) => String(s).trim())
    .filter(Boolean)
)

// One chip per amount: "10 mg", "10mg" and a bare legacy "10" are the
// same size, so they share a chip that toggles every stored spelling.
// Chips sort by milligrams (500mcg before 1mg before 1g); blends and
// other units follow in numeric order.
const sizeGroups = computed(() => {
  const groups = new Map()
  for (const raw of mergedSizes.value) {
    const info = sizeInfo(raw)
    let group = groups.get(info.key)
    if (!group) {
      group = { key: info.key, rank: info.rank, mg: info.mg, values: [], label: '' }
      groups.set(info.key, group)
    }
    group.values.push(raw)
  }
  for (const group of groups.values()) {
    // Prefer a stored spelling that carries its unit and no spaces.
    const preferred = group.values.find((v) => /[a-z]/i.test(v) && !/\s/.test(v))
    const label = preferred || formatSize(group.values[0].replace(/\s+/g, ''))
    group.label = group.key.startsWith('mg:') ? label.toLowerCase() : label
  }
  return [...groups.values()].sort(compareSizeInfo)
})
// Rows per size in the listings shown, so the size bar can lead with the
// sizes people actually find. With a size already picked the counts only
// cover that size, so every chip shows instead.
const SIZE_CHIP_LIMIT = 10
const showAllSizes = ref(false)
const sizeCounts = computed(() => {
  const counts = new Map()
  for (const row of (props.products?.data || [])) {
    if (!row || row.size_mg == null || row.size_mg === '') continue
    const key = sizeInfo(String(row.size_mg)).key
    counts.set(key, (counts.get(key) || 0) + 1)
  }
  return counts
})
const visibleSizeGroups = computed(() => {
  const groups = sizeGroups.value
  if (showAllSizes.value || selectedSizes.value.size > 0 || groups.length <= SIZE_CHIP_LIMIT + 2) return groups
  const top = new Set(
    [...groups]
      .sort((a, b) => (sizeCounts.value.get(b.key) || 0) - (sizeCounts.value.get(a.key) || 0))
      .slice(0, SIZE_CHIP_LIMIT)
      .map((g) => g.key)
  )
  return groups.filter((g) => top.has(g.key))
})
const hiddenSizeCount = computed(() => sizeGroups.value.length - visibleSizeGroups.value.length)

// Summary tiles: vendor and listing counts, the cheapest listing and the
// lowest $/mg, compared in USD (price outliers left out) and shown in the
// listing's own currency.
function money(v) {
  const n = Number(v)
  return Number.isFinite(n) ? n.toFixed(2) : ''
}
const priceSummary = computed(() => {
  const rows = (props.products?.data || []).filter((r) => r && !r.price_outlier)
  if (!rows.length) return null
  const vendors = new Set(rows.map((r) => r.brand?.id ?? r.brand_id).filter(Boolean)).size
  let cheapest = null
  let bestValue = null
  for (const r of rows) {
    const usd = Number(r.usd_final_price)
    if (usd > 0 && (!cheapest || usd < Number(cheapest.usd_final_price))) cheapest = r
    const perMg = Number(r.usd_per_mg)
    if (perMg > 0 && (!bestValue || perMg < Number(bestValue.usd_per_mg))) bestValue = r
  }
  return {
    vendors,
    listings: props.products?.total || rows.length,
    cheapest,
    bestValue: bestValue && bestValue.price_per_mg != null ? bestValue : null,
  }
})

function isSizeGroupSelected(group) {
  return group.values.some((v) => selectedSizes.value.has(v))
}
function toggleSizeGroup(group) {
  const set = new Set(selectedSizes.value)
  if (isSizeGroupSelected(group)) group.values.forEach((v) => set.delete(v))
  else group.values.forEach((v) => set.add(v))
  selectedSizes.value = set
  applyFilters()
}

function clearSizes() {
  selectedSizes.value = new Set()
  applyFilters()
}

// Product Format filter — Colin PMAP Sep 30 (#13).
const FORMAT_OPTIONS = ['Peptide', 'Capsule', 'Nasal Spray', 'Topical', 'Kit', 'Pens']
const initialFormats = (new URLSearchParams(window.location.search).get('format') || '')
  .split(',').map(s => s.trim()).filter(Boolean)
const selectedFormats = ref(new Set(initialFormats))
function toggleFormat(fmt) {
  const set = selectedFormats.value
  if (set.has(fmt)) set.delete(fmt)
  else set.add(fmt)
  selectedFormats.value = new Set(set)
  applyFilters()
}
function clearFormats() {
  selectedFormats.value = new Set()
  applyFilters()
}

const handleSortChange = (event) => {
  const value = event?.target?.value || 'price|asc'
  const [sort, dir] = value.split('|')
  applySort(sort, dir)
}


function handleBrandChange(event) {
  selectedBrand.value = event.target.value
  applyFilters()
}

function formatSize(size) {
  // size_mg is now a string column carrying blend shapes like
  // "10mg/2.5mg" as well as plain "10". Only append the mg suffix
  // when the DB value is a bare number; blend strings already
  // carry their own units.
  if (size == null || size === '') return ''
  const s = String(size).trim()
  if (/^[0-9.]+$/.test(s)) {
    const n = parseFloat(s)
    if (n >= 1000) return (n / 1000) + 'g'
    return n + 'mg'
  }
  // Already-formatted blend / mixed-unit string — return verbatim.
  return s
}

// Filter panel (hidden)
const showFilterPanel = ref(false)
// Initialize searchQuery from props or URL
const searchQuery = ref(props.search || '')

// Filter icon component
const filterIcon = h('svg', {
  xmlns: 'http://www.w3.org/2000/svg',
  width: '24',
  height: '24',
  viewBox: '0 0 24 24',
  fill: 'none',
  stroke: 'currentColor',
  'stroke-width': '2',
  'stroke-linecap': 'round',
  'stroke-linejoin': 'round',
  class: 'lucide lucide-sliders-horizontal'
}, [
  h('path', { d: 'M10 5H3' }),
  h('path', { d: 'M12 19H3' }),
  h('path', { d: 'M14 3v4' }),
  h('path', { d: 'M16 17v4' }),
  h('path', { d: 'M21 12h-9' }),
  h('path', { d: 'M21 19h-5' }),
  h('path', { d: 'M21 5h-7' }),
  h('path', { d: 'M8 10v4' }),
  h('path', { d: 'M8 12H3' })
])

// Selected filters
const selectedFilters = ref({
  use: props.filters?.use ? parseInt(props.filters.use) : null,
  type: props.filters?.type ? parseInt(props.filters.type) : null,
  // Country name from the global location filter, not a numeric id.
  location: props.filters?.location || null,
  verification: props.filters?.verification || '',
  brand: props.filters?.brand ? parseInt(props.filters.brand) : null,
  cost_min: props.filters?.cost_min || '',
  cost_max: props.filters?.cost_max || '',
  inStock: props.filters?.in_stock === '1' || false,
  onSale: props.filters?.on_sale === '1' || false,
  firstTimerDeals: props.filters?.first_timer_deals === '1' || false,
})

// Current sort label
const currentSortLabel = computed(() => {
  if (props.sort === 'popular') return 'Most Popular'
  if (props.sort === 'rating') return 'Highest Rated'
  if (props.sort === 'price' && props.sortDir === 'asc') return 'Price: Low to High'
  if (props.sort === 'price' && props.sortDir === 'desc') return 'Price: High to Low'
  return 'Most Popular'
})


// Check if there are active filters
const hasActiveFilters = computed(() => {
  return selectedFilters.value.inStock ||
    selectedFilters.value.onSale ||
    selectedFilters.value.firstTimerDeals ||
    selectedFilters.value.cost_min ||
    selectedFilters.value.cost_max ||
    selectedFilters.value.use !== null ||
    selectedFilters.value.type !== null ||
    selectedFilters.value.location !== null ||
    selectedFilters.value.verification !== '' ||
    selectedFilters.value.brand !== null
})

const applyFilters = () => {
  const params = new URLSearchParams()

  if (searchQuery.value) params.set('search', searchQuery.value)
  if (selectedSizes.value.size > 0) params.set('size', [...selectedSizes.value].join(','))
  if (selectedFormats.value.size > 0) params.set('format', [...selectedFormats.value].join(','))
  if (selectedBrand.value) params.set('brand', selectedBrand.value)
  if (props.sort) params.set('sort', props.sort)
  if (props.sortDir) params.set('sort_dir', props.sortDir)

  router.visit(`/product/${props.slug}?${params.toString()}`, {
    preserveState: true,
    preserveScroll: true,
  })
}

const clearFilters = () => {
  selectedFilters.value = {
    use: null,
    type: null,
    location: null,
    verification: '',
    brand: null,
    cost_min: '',
    cost_max: '',
    inStock: false,
    onSale: false,
    firstTimerDeals: false,
  }
  applyFilters()
}

const applySort = (sort, dir) => {
  const params = new URLSearchParams(window.location.search)
  if (searchQuery.value) params.set('search', searchQuery.value)
  if (selectedSizes.value.size > 0) params.set('size', [...selectedSizes.value].join(','))
  else params.delete('size')
  if (selectedFormats.value.size > 0) params.set('format', [...selectedFormats.value].join(','))
  else params.delete('format')
  if (selectedBrand.value) params.set('brand', selectedBrand.value)
  else params.delete('brand')
  params.set('sort', sort)
  params.set('sort_dir', dir)
  router.visit(`/product/${props.slug}?${params.toString()}`, { preserveState: true, preserveScroll: true })
}

let searchTimeout = null

const handleSearchInput = () => {
  // Debounce search to avoid too many requests
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    applySearch()
  }, 500) // Wait 500ms after user stops typing
}

const applySearch = () => {
  const params = new URLSearchParams(window.location.search)
  
  // Update search parameter
  if (searchQuery.value) {
    params.set('search', searchQuery.value)
  } else {
    params.delete('search')
  }
  
  // Preserve other filters
  if (selectedFilters.value.use !== null) {
    params.set('use', selectedFilters.value.use)
  }
  if (selectedFilters.value.type !== null) {
    params.set('type', selectedFilters.value.type)
  }
  if (selectedFilters.value.location !== null) {
    params.set('location', selectedFilters.value.location)
  } else {
    params.delete('location')
  }
  if (selectedFilters.value.verification) {
    params.set('verification', selectedFilters.value.verification)
  }
  if (selectedFilters.value.brand !== null) {
    params.set('brand', selectedFilters.value.brand)
  }
  if (selectedFilters.value.cost_min) {
    params.set('cost_min', selectedFilters.value.cost_min)
  }
  if (selectedFilters.value.cost_max) {
    params.set('cost_max', selectedFilters.value.cost_max)
  }
  if (selectedFilters.value.inStock) {
    params.set('in_stock', '1')
  }
  if (selectedFilters.value.onSale) {
    params.set('on_sale', '1')
  }
  if (selectedFilters.value.firstTimerDeals) {
    params.set('first_timer_deals', '1')
  }
  if (props.sort) {
    params.set('sort', props.sort)
  }
  if (props.sortDir) {
    params.set('sort_dir', props.sortDir)
  }
  
  router.visit(`/product/${props.slug}?${params.toString()}`, {
    preserveState: true,
    preserveScroll: true,
  })
}


const handleCtaClick = (url) => {
  router.visit(url)
}

const handleImageError = (event) => {
  // Prevent infinite loop - stop trying to load images if we've already failed
  if (event.target.dataset.failed) {
    return
  }
  // Mark as failed to prevent retry
  event.target.dataset.failed = 'true'
  // Hide the broken image and show placeholder
  event.target.style.display = 'none'
  if (event.target.parentElement) {
    const placeholder = document.createElement('div')
    placeholder.className = 'w-full h-full flex items-center justify-center text-gray-400'
    placeholder.innerHTML = '<span class="text-sm">No Image</span>'
    event.target.parentElement.appendChild(placeholder)
  }
}

// Setup lazy loading for hero background
onMounted(() => {
  nextTick(() => {
    if (heroBgRef.value) {
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const bgImage = entry.target.getAttribute('data-bg-image')
            if (bgImage) {
              const img = new Image()
              img.onload = () => {
                heroBgLoaded.value = true
              }
              img.src = bgImage
            }
            observer.unobserve(entry.target)
          }
        })
      }, {
        rootMargin: '50px'
      })
      observer.observe(heroBgRef.value)
    }
  })
})
</script>

<style scoped>
/* Make range input track transparent so background shows through */
input[type="range"] {
  -webkit-appearance: none;
  appearance: none;
  background: transparent;
}

input[type="range"]::-webkit-slider-track {
  background: transparent;
  height: 8px;
}

input[type="range"]::-moz-range-track {
  background: transparent;
  height: 8px;
}

input[type="range"]::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 20px;
  height: 20px;
  background: #2563eb;
  border-radius: 50%;
  cursor: pointer;
  border: 2px solid white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

input[type="range"]::-moz-range-thumb {
  width: 20px;
  height: 20px;
  background: #2563eb;
  border-radius: 50%;
  cursor: pointer;
  border: 2px solid white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}
</style>
