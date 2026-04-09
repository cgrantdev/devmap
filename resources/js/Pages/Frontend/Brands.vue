<template>
  <Head :title="title">
    <meta name="description" :content="description" />
    <meta property="og:type" content="website" />
    <meta property="og:url" :content="url" />
    <meta property="og:title" :content="ogTitle" />
    <meta property="og:description" :content="ogDescription" />
    <meta v-if="ogImage" property="og:image" :content="ogImage" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" :content="ogTitle" />
    <meta name="twitter:description" :content="ogDescription" />
    <link rel="canonical" :href="url" />
  </Head>

  <ModernLayout>
    <!-- Header with subtle accent wash -->
    <section class="relative overflow-hidden border-b border-[color:var(--color-hairline)]">
      <div class="absolute inset-0 bg-gradient-to-br from-[color:var(--color-accent-50)] via-transparent to-transparent opacity-50 pointer-events-none" />
      <div class="relative max-w-[1280px] mx-auto px-6 lg:px-10 pt-8 pb-8">
        <div class="text-[11px] uppercase tracking-[0.12em] font-semibold text-[color:var(--color-accent-600)] mb-3">Vendor network</div>
        <h1 class="ui-display text-4xl md:text-5xl font-semibold tracking-[-0.02em] text-[color:var(--color-ink)] mb-3">All Vendors</h1>
        <p class="text-[15px] text-[color:var(--color-ink-muted)] leading-relaxed max-w-2xl mb-8">
          Compare vendors, read reviews, and find the best peptide sources.
        </p>

        <!-- Search + sort -->
        <div class="flex flex-col sm:flex-row gap-3 max-w-3xl">
          <div class="relative flex-1">
            <input
              v-model="searchQuery"
              @input="handleSearchInput"
              type="text"
              placeholder="Search vendors by name or location…"
              class="ui-focus w-full h-11 pl-10 pr-4 bg-white border border-[color:var(--color-hairline)] rounded-[10px] text-[color:var(--color-ink)] text-[15px] placeholder-[color:var(--color-ink-subtle)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/20 transition-all"
            />
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-[color:var(--color-ink-subtle)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8" /><path d="m21 21-4.35-4.35" />
            </svg>
          </div>
          <select
            :value="sortValue"
            @change="handleSortChange"
            class="ui-focus h-11 px-4 bg-white border border-[color:var(--color-hairline)] rounded-[10px] text-[color:var(--color-ink)] text-[14px] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/20 cursor-pointer"
          >
            <option value="rating|desc">Highest Rated</option>
            <option value="reviews|desc">Most Reviews</option>
            <option value="name|asc">Alphabetical</option>
          </select>
        </div>

        <!-- Filter chips -->
        <div class="flex flex-wrap items-center gap-2 mt-4">
          <button
            v-for="loc in locationFilters"
            :key="loc.value"
            @click="toggleLocation(loc.value)"
            :class="[
              'ui-focus h-8 px-3.5 rounded-full text-[12px] font-semibold transition-all duration-200 border',
              selectedFilters.location === loc.value
                ? 'bg-[color:var(--color-ink)] text-white border-[color:var(--color-ink)]'
                : 'bg-white text-[color:var(--color-ink-muted)] border-[color:var(--color-hairline)] hover:border-[color:var(--color-ink-subtle)] hover:text-[color:var(--color-ink)]',
            ]"
          >
            {{ loc.label }}
          </button>
          <span class="text-[color:var(--color-hairline)] text-sm mx-1">|</span>
          <button
            @click="toggleTopVendors"
            :class="[
              'ui-focus h-8 px-3.5 rounded-full text-[12px] font-semibold transition-all duration-200 border flex items-center gap-1.5',
              selectedFilters.topVendorsOnly
                ? 'bg-[color:var(--color-accent-600)] text-white border-[color:var(--color-accent-600)]'
                : 'bg-white text-[color:var(--color-ink-muted)] border-[color:var(--color-hairline)] hover:border-[color:var(--color-ink-subtle)] hover:text-[color:var(--color-ink)]',
            ]"
          >
            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 2L4 5v6c0 5 3.4 9.7 8 11 4.6-1.3 8-6 8-11V5l-8-3z" /><path d="M9.5 12.5l2 2 4-4.5" />
            </svg>
            Top vendors
          </button>
        </div>
      </div>
    </section>

    <!-- Results -->
    <section class="max-w-[1280px] mx-auto px-6 lg:px-10 py-10">
      <!-- Stats strip -->
      <div class="flex items-center justify-between mb-8">
        <div class="text-[14px] text-[color:var(--color-ink-muted)]">
          <span class="ui-mono font-semibold text-[color:var(--color-ink)]">{{ filteredBrands.length }}</span>
          vendor{{ filteredBrands.length !== 1 ? 's' : '' }} found
        </div>
        <div v-if="hasActiveFilters" class="flex items-center gap-2">
          <button
            @click="clearFilters"
            class="ui-focus text-[13px] text-[color:var(--color-accent-600)] font-medium hover:text-[color:var(--color-accent-700)] transition-colors"
          >
            Clear filters
          </button>
        </div>
      </div>

      <!-- Featured vendor spotlight (first partner/featured vendor gets a big card) -->
      <div v-if="featuredVendor" class="mb-8">
        <a
          :href="`/shop/${featuredVendor.slug}`"
          class="ui-focus group flex flex-col md:flex-row rounded-[16px] border border-[color:var(--color-accent-400)]/30 bg-gradient-to-r from-[color:var(--color-accent-50)] to-white overflow-hidden hover:shadow-[var(--shadow-md)] transition-all duration-[200ms]"
        >
          <!-- Logo area -->
          <div class="md:w-56 flex-shrink-0 flex items-center justify-center p-8" :style="{ background: vendorGradient(featuredVendor.name) }">
            <img
              v-if="featuredVendor.logo"
              :src="featuredVendor.logo"
              :alt="featuredVendor.name"
              class="max-h-20 max-w-[80%] object-contain drop-shadow-lg"
              loading="lazy"
            />
            <span v-else class="ui-display text-white text-4xl font-bold">{{ featuredVendor.initials }}</span>
          </div>
          <!-- Info -->
          <div class="flex-1 p-6 flex flex-col justify-center gap-3">
            <div class="flex items-center gap-2">
              <span class="ui-mono text-[10px] uppercase tracking-[0.1em] px-2 py-0.5 rounded-full bg-[color:var(--color-accent-600)] text-white font-semibold">Featured</span>
            </div>
            <h2 class="ui-display text-2xl font-semibold text-[color:var(--color-ink)] tracking-tight">{{ featuredVendor.name }}</h2>
            <div class="flex items-center flex-wrap gap-4 text-sm text-[color:var(--color-ink-muted)]">
              <div class="flex items-center gap-1">
                <div class="flex gap-0.5">
                  <svg v-for="n in 5" :key="n" class="w-3.5 h-3.5" :class="n <= Math.round(parseFloat(featuredVendor.rating)) ? 'text-[color:var(--color-caution)]' : 'text-[color:var(--color-hairline)]'" viewBox="0 0 20 20" fill="currentColor"><path d="M10 1l2.8 5.7 6.2.9-4.5 4.4 1.1 6.3L10 15.3 4.4 18.3l1.1-6.3L1 7.6l6.2-.9L10 1z"/></svg>
                </div>
                <span class="ui-mono font-semibold text-[color:var(--color-ink)]">{{ parseFloat(featuredVendor.rating).toFixed(1) }}</span>
                <span>({{ featuredVendor.reviews }})</span>
              </div>
              <span v-if="featuredVendor.location" class="flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a8 8 0 00-8 8c0 5.5 8 12 8 12s8-6.5 8-12a8 8 0 00-8-8z"/><circle cx="12" cy="10" r="3"/></svg>
                {{ featuredVendor.location }}
              </span>
              <span class="ui-mono font-semibold text-[color:var(--color-ink)]">{{ featuredVendor.product_count }}</span> compounds
            </div>
          </div>
          <div class="hidden md:flex items-center pr-6">
            <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-[color:var(--color-accent-600)] group-hover:translate-x-1 transition-transform duration-200">
              View vendor
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
            </span>
          </div>
        </a>
      </div>

      <!-- Vendor list (rich rows, not card grid) -->
      <div v-if="filteredBrands.length > 0" class="space-y-3">
        <a
          v-for="(brand, idx) in filteredBrands"
          :key="brand.id"
          :href="`/shop/${brand.slug}`"
          class="ui-focus group flex items-center gap-5 p-4 md:p-5 rounded-[14px] border border-[color:var(--color-hairline)] bg-white hover:border-[color:var(--color-accent-400)] hover:shadow-[var(--shadow-sm)] transition-all duration-[180ms]"
        >
          <!-- Rank -->
          <div class="hidden md:flex w-8 flex-shrink-0 items-center justify-center">
            <span class="ui-mono text-sm font-bold text-[color:var(--color-ink-subtle)]">{{ idx + 1 }}</span>
          </div>

          <!-- Logo -->
          <div class="w-12 h-12 md:w-14 md:h-14 rounded-[12px] flex-shrink-0 overflow-hidden flex items-center justify-center" :style="{ background: vendorGradient(brand.name) }">
            <img
              v-if="brand.logo"
              :src="brand.logo"
              :alt="brand.name"
              class="max-h-full max-w-full object-contain p-1.5"
              loading="lazy"
            />
            <span v-else class="ui-display text-white text-lg font-bold">{{ brand.initials }}</span>
          </div>

          <!-- Info -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1">
              <h3 class="ui-display text-[16px] font-semibold text-[color:var(--color-ink)] truncate tracking-tight">{{ brand.name }}</h3>
              <span v-if="brand.is_partner" class="ui-mono text-[9px] uppercase tracking-[0.1em] px-1.5 py-0.5 rounded-full bg-[color:var(--color-accent-50)] text-[color:var(--color-accent-700)] font-semibold flex-shrink-0">Partner</span>
            </div>
            <div class="flex items-center flex-wrap gap-x-4 gap-y-1 text-xs text-[color:var(--color-ink-muted)]">
              <span v-if="brand.location" class="flex items-center gap-1">
                <svg class="w-3 h-3 text-[color:var(--color-ink-subtle)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a8 8 0 00-8 8c0 5.5 8 12 8 12s8-6.5 8-12a8 8 0 00-8-8z"/><circle cx="12" cy="10" r="3"/></svg>
                {{ brand.location }}
              </span>
              <span class="flex items-center gap-1">
                <svg class="w-3 h-3 text-[color:var(--color-ink-subtle)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                <span class="ui-mono font-semibold text-[color:var(--color-ink)]">{{ brand.product_count }}</span> compounds
              </span>
              <span v-if="brand.last_updated" class="flex items-center gap-1 text-[color:var(--color-ink-subtle)]">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                Updated {{ brand.last_updated }} ago
              </span>
            </div>
          </div>

          <!-- Rating -->
          <div class="hidden sm:flex flex-col items-end gap-1 flex-shrink-0">
            <div class="flex items-center gap-1.5">
              <div class="flex gap-0.5">
                <svg v-for="n in 5" :key="n" class="w-3 h-3" :class="n <= Math.round(parseFloat(brand.rating)) ? 'text-[color:var(--color-caution)]' : 'text-[color:var(--color-hairline)]'" viewBox="0 0 20 20" fill="currentColor"><path d="M10 1l2.8 5.7 6.2.9-4.5 4.4 1.1 6.3L10 15.3 4.4 18.3l1.1-6.3L1 7.6l6.2-.9L10 1z"/></svg>
              </div>
              <span class="ui-mono text-sm font-bold text-[color:var(--color-ink)]">{{ parseFloat(brand.rating).toFixed(1) }}</span>
            </div>
            <span class="text-[11px] text-[color:var(--color-ink-subtle)]">{{ brand.reviews }} reviews</span>
          </div>

          <!-- CTA arrow -->
          <div class="flex-shrink-0 hidden md:block">
            <svg class="w-5 h-5 text-[color:var(--color-ink-subtle)] group-hover:text-[color:var(--color-accent-600)] group-hover:translate-x-0.5 transition-all duration-[180ms]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
              <path d="M9 18l6-6-6-6"/>
            </svg>
          </div>
        </a>
      </div>

      <!-- Empty state -->
      <div v-else class="py-20 text-center">
        <div class="w-14 h-14 mx-auto mb-5 rounded-full bg-[color:var(--color-hairline-soft)] flex items-center justify-center">
          <svg class="w-6 h-6 text-[color:var(--color-ink-subtle)]" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8" /><path d="m21 21-4.35-4.35" />
          </svg>
        </div>
        <h3 class="ui-display text-lg font-semibold text-[color:var(--color-ink)] mb-2">No vendors found</h3>
        <p class="text-[color:var(--color-ink-muted)] text-sm mb-5">Try adjusting your search or filters.</p>
        <button
          @click="clearFilters"
          class="ui-focus h-9 px-5 rounded-[9px] bg-[color:var(--color-accent-600)] text-white text-[13px] font-semibold hover:bg-[color:var(--color-accent-700)] transition-colors"
        >
          Clear filters
        </button>
      </div>
    </section>
  </ModernLayout>
</template>

<script setup>
import { ref, computed, watchEffect } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import ModernLayout from '@/Pages/Layouts/ModernLayout.vue'

const props = defineProps({
  seo: { type: Object, default: () => ({}) },
  brands: { type: Array, default: () => [] },
  search: String,
  sort: String,
  sortDir: String,
  filters: Object,
})

const page = usePage()

const title = computed(() => {
  const base = props.seo?.title || 'Top Rated Peptide Vendors'
  const site = page.props.site_name || 'PeptideMap'
  return `${base} - ${site}`
})
const description = computed(() => props.seo?.description || 'Browse and compare top-rated peptide vendors.')
const url = computed(() => props.seo?.url || page.url)
const ogTitle = computed(() => props.seo?.og_title || title.value)
const ogDescription = computed(() => props.seo?.og_description || description.value)
const ogImage = computed(() => props.seo?.og_image || null)

watchEffect(() => { document.title = title.value })

const searchQuery = ref(props.search || '')
const selectedFilters = ref({
  location: props.filters?.location || '',
  minRating: props.filters?.min_rating || '',
  topVendorsOnly: props.filters?.top_vendors_only === '1',
})

const locationFilters = [
  { label: 'All', value: '' },
  { label: 'United States', value: 'United States' },
  { label: 'Canada', value: 'Canada' },
  { label: 'United Kingdom', value: 'United Kingdom' },
  { label: 'Australia', value: 'Australia' },
]

const sortValue = computed(() => `${props.sort || 'rating'}|${props.sortDir || 'desc'}`)
const filteredBrands = computed(() => props.brands)

// First partner/featured vendor gets a spotlight card
const featuredVendor = computed(() =>
  props.brands.find(b => b.is_partner || b.featured) || null
)

const hasActiveFilters = computed(() =>
  searchQuery.value || selectedFilters.value.location || selectedFilters.value.topVendorsOnly
)

function handleSortChange(e) {
  const [sort, dir] = (e?.target?.value || 'rating|desc').split('|')
  navigate({ sort, sort_dir: dir })
}

let searchTimeout = null
function handleSearchInput() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => navigate({}), 400)
}

function toggleLocation(loc) {
  selectedFilters.value.location = selectedFilters.value.location === loc ? '' : loc
  navigate({})
}

function toggleTopVendors() {
  selectedFilters.value.topVendorsOnly = !selectedFilters.value.topVendorsOnly
  navigate({})
}

function clearFilters() {
  searchQuery.value = ''
  selectedFilters.value = { location: '', minRating: '', topVendorsOnly: false }
  router.visit('/brands', { preserveState: true, preserveScroll: true })
}

function navigate(overrides = {}) {
  const params = new URLSearchParams()
  const s = searchQuery.value.trim()
  if (s) params.set('search', s)

  const sort = overrides.sort || props.sort || 'rating'
  const dir = overrides.sort_dir || props.sortDir || 'desc'
  params.set('sort', sort)
  params.set('sort_dir', dir)

  if (selectedFilters.value.location) params.set('location', selectedFilters.value.location)
  if (selectedFilters.value.minRating) params.set('min_rating', selectedFilters.value.minRating)
  if (selectedFilters.value.topVendorsOnly) params.set('top_vendors_only', '1')

  router.visit(`/brands?${params.toString()}`, { preserveState: true, preserveScroll: true })
}

function vendorGradient(name) {
  const palette = [
    ['#1E293B', '#4F46E5'],
    ['#0F172A', '#6366F1'],
    ['#1E3A8A', '#3B82F6'],
    ['#0C4A6E', '#0EA5E9'],
    ['#134E4A', '#14B8A6'],
    ['#312E81', '#4F46E5'],
    ['#111827', '#7C3AED'],
    ['#164E63', '#06B6D4'],
  ]
  const n = (name || '?').trim()
  let h = 0
  for (let i = 0; i < n.length; i++) h = (h * 31 + n.charCodeAt(i)) & 0xffffffff
  const [a, b] = palette[Math.abs(h) % palette.length]
  return `linear-gradient(135deg, ${a} 0%, ${b} 100%)`
}
</script>
