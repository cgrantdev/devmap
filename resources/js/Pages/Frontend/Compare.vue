<template>
  <ModernLayout>
    <Head>
      <title>{{ seo.title }}</title>
      <meta name="description" :content="seo.description" />
    </Head>

    <!-- Header -->
    <section class="border-b border-[color:var(--color-hairline)]">
      <div class="max-w-[1280px] mx-auto px-6 lg:px-10 pt-8 pb-10">
        <div class="text-[11px] uppercase tracking-[0.12em] font-semibold text-[color:var(--color-biotech-600)] mb-3">Side-by-side</div>
        <h1 class="ui-display text-4xl md:text-5xl font-semibold tracking-[-0.02em] text-[color:var(--color-ink)] mb-3">
          Compare Peptide Prices
        </h1>
        <p class="text-lg text-[color:var(--color-ink-muted)] leading-relaxed max-w-2xl">
          Every vendor, every price, sorted cheapest-first. Click any compound to jump to its vendor pricing table.
        </p>
        <div class="mt-4 inline-flex items-center gap-2 px-3 py-1.5 rounded-md bg-[color:var(--color-bg)] border border-[color:var(--color-hairline)] text-[11px] text-[color:var(--color-ink-subtle)]">
          <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
          All products listed are for <strong class="text-[color:var(--color-ink-muted)]">research use only</strong> (RUO). Not for human consumption.
        </div>
      </div>
    </section>

    <!-- Dedicated-landing banner — high-intent shortcut for people looking
         for BAC water specifically. Sits above the compound grid so it
         reads as a distinct destination, not just another tile. -->
    <section class="max-w-[1280px] mx-auto px-6 lg:px-10 pt-8">
      <a
        href="/bacteriostatic-water"
        class="ui-focus group flex items-center gap-4 p-4 lg:p-5 rounded-[12px] border border-sky-200 bg-gradient-to-r from-sky-50 to-cyan-50/60 hover:border-sky-400 hover:shadow-[var(--shadow-md)] transition-all"
      >
        <div class="flex-shrink-0 w-11 h-11 rounded-full bg-white border border-sky-200 flex items-center justify-center">
          <svg class="w-5 h-5 text-sky-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 2.69l5.66 5.66a8 8 0 11-11.31 0z"/>
          </svg>
        </div>
        <div class="flex-1 min-w-0">
          <div class="text-[15px] lg:text-[16px] font-semibold text-[color:var(--color-ink)] leading-tight">
            Looking for bacteriostatic water?
          </div>
          <div class="text-[12px] text-[color:var(--color-ink-muted)] mt-0.5">
            51 vendors, sorted by per-mL price · size filter, coupon codes, FAQ
          </div>
        </div>
        <div class="flex-shrink-0 hidden sm:flex items-center gap-1 text-[13px] font-semibold text-sky-700 group-hover:text-sky-800 transition-colors">
          Open
          <svg class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
        </div>
      </a>
    </section>

    <!-- Compound quick-nav grid -->
    <section class="max-w-[1280px] mx-auto px-6 lg:px-10 py-10">
      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
        <a
          v-for="compound in compounds"
          :key="compound.id"
          :href="`#${compound.anchor}`"
          class="ui-focus group h-full flex flex-col gap-2 p-4 rounded-[12px] border border-[color:var(--color-hairline)] bg-white hover:border-[color:var(--color-accent-400)] hover:shadow-[var(--shadow-md)] transition-all duration-[180ms] relative"
          :title="compound.name"
        >
          <div class="absolute top-2 right-2 z-10">
            <WishlistHeart type="category" :id="compound.id" size="sm" />
          </div>
          <div class="ui-display text-[15px] font-semibold text-[color:var(--color-ink)] leading-tight group-hover:text-[color:var(--color-accent-600)] transition-colors line-clamp-2 min-h-[2.6em] pr-6">
            {{ compound.name }}
          </div>
          <div class="mt-auto flex items-center gap-3 text-[11px] text-[color:var(--color-ink-muted)]">
            <span class="ui-mono font-semibold text-[color:var(--color-ink)]">
              {{ compound.vendor_count }} vendor{{ compound.vendor_count !== 1 ? 's' : '' }}
            </span>
            <span v-if="compound.cheapest_price" class="ui-mono text-[color:var(--color-verified)]">
              from ${{ formatPrice(compound.cheapest_price) }}
            </span>
          </div>
        </a>
      </div>
    </section>

    <!-- Per-compound vendor pricing sections -->
    <section class="max-w-[1280px] mx-auto px-6 lg:px-10 pb-20">
      <div class="space-y-16">
        <div
          v-for="(compound, idx) in compounds"
          :key="compound.id"
          :id="compound.anchor"
          class="scroll-mt-24"
        >
          <!-- Compound header -->
          <div class="flex items-start justify-between gap-4 mb-4 flex-wrap">
            <div>
              <div class="flex items-center gap-3 mb-2">
                <span class="ui-mono text-[11px] font-bold px-2 py-0.5 rounded-md bg-[color:var(--color-accent-50)] text-[color:var(--color-accent-700)]">
                  #{{ idx + 1 }}
                </span>
                <h2 class="ui-display text-2xl md:text-3xl font-semibold tracking-tight text-[color:var(--color-ink)]">
                  {{ compound.name }}
                </h2>
              </div>
              <p v-if="compound.description" class="text-sm text-[color:var(--color-ink-muted)] leading-relaxed max-w-2xl line-clamp-2">
                {{ compound.description }}
              </p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0 flex-wrap">
              <a
                :href="`/compare/${compound.slug}`"
                class="ui-focus inline-flex items-center gap-1.5 h-9 px-4 rounded-[9px] bg-[color:var(--color-ink)] text-white text-[13px] font-semibold hover:opacity-90 transition-opacity"
                :title="`Full ${compound.name} vendor comparison`"
              >
                Full comparison
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
              </a>
              <a
                v-if="compound.encyclopedia_url"
                :href="compound.encyclopedia_url"
                class="ui-focus inline-flex items-center gap-1.5 h-9 px-4 rounded-[9px] border border-[color:var(--color-hairline)] bg-white text-[13px] font-semibold text-[color:var(--color-ink)] hover:border-[color:var(--color-accent-400)] transition-colors"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M4 19.5A2.5 2.5 0 016.5 17H20" /><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z" />
                </svg>
                Read article
              </a>
            </div>
          </div>

          <!-- Mg size selector tabs -->
          <div v-if="getSizes(compound).length > 1" class="flex items-center gap-1.5 mb-4">
            <button
              @click="setSize(idx, null)"
              :class="[
                'ui-mono h-8 px-3 rounded-[8px] text-[12px] font-semibold transition-all',
                getSelectedSize(idx) === null
                  ? 'bg-[color:var(--color-ink)] text-white shadow-sm'
                  : 'bg-white border border-[color:var(--color-hairline)] text-[color:var(--color-ink-muted)] hover:border-[color:var(--color-accent-400)] hover:text-[color:var(--color-ink)]',
              ]"
            >All sizes</button>
            <button
              v-for="size in getSizes(compound)"
              :key="size"
              @click="setSize(idx, size)"
              :class="[
                'ui-mono h-8 px-3 rounded-[8px] text-[12px] font-semibold transition-all',
                getSelectedSize(idx) === size
                  ? 'bg-[color:var(--color-ink)] text-white shadow-sm'
                  : 'bg-white border border-[color:var(--color-hairline)] text-[color:var(--color-ink-muted)] hover:border-[color:var(--color-accent-400)] hover:text-[color:var(--color-ink)]',
              ]"
            >{{ formatSize(size) }}</button>
          </div>

          <!-- Price table -->
          <div v-if="getFilteredProducts(compound, idx).length" class="bg-white rounded-[14px] border border-[color:var(--color-hairline)] overflow-hidden shadow-[var(--shadow-xs)]">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-[color:var(--color-hairline)] bg-[color:var(--color-bg)]">
                  <th class="text-left px-5 py-3 text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Vendor</th>
                  <th class="text-left px-5 py-3 text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Product</th>
                  <th class="text-right px-5 py-3 text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Retail</th>
                  <th class="text-right px-5 py-3 text-[11px] uppercase tracking-[0.08em] font-semibold text-emerald-700 bg-emerald-50/50">Price with code</th>
                  <th class="text-right px-5 py-3 text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)] hidden sm:table-cell">Savings</th>
                  <th class="text-right px-5 py-3 w-[100px]"></th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(product, pidx) in getVisibleProducts(compound, idx)"
                  :key="product.id"
                  :class="[
                    'border-b border-[color:var(--color-hairline-soft)] hover:bg-[color:var(--color-hairline-soft)] transition-colors',
                    pidx === 0 ? 'bg-[color:var(--color-verified-bg)]' : '',
                  ]"
                >
                  <!-- Vendor -->
                  <td class="px-5 py-4">
                    <div class="flex items-center gap-2.5">
                      <div class="flex-shrink-0 w-7 h-7 rounded-[6px] overflow-hidden bg-[color:var(--color-hairline-soft)] border border-[color:var(--color-hairline)]">
                        <img
                          v-if="product.brand_logo"
                          :src="product.brand_logo"
                          :alt="product.brand_name"
                          class="w-full h-full object-cover"
                          loading="lazy"
                        />
                      </div>
                      <div>
                        <a
                          :href="`/shop/${product.brand_slug}`"
                          class="font-semibold text-[color:var(--color-ink)] hover:text-[color:var(--color-accent-600)] transition-colors"
                        >
                          {{ product.brand_name }}
                        </a>
                        <div v-if="pidx === 0" class="flex items-center gap-1 mt-0.5">
                          <span class="text-[10px] font-bold uppercase tracking-[0.1em] text-[color:var(--color-verified)]">Best price</span>
                          <span
                            v-if="percentCheaper(getFilteredProducts(compound, idx))"
                            class="ml-1 inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md bg-[color:var(--color-verified-bg)] border border-[#A7F3D0] text-[10px] font-bold text-[#065F46]"
                          >
                            {{ percentCheaper(getFilteredProducts(compound, idx)) }}% cheaper
                          </span>
                        </div>
                      </div>
                    </div>
                  </td>
                  <!-- Product -->
                  <td class="px-5 py-4 text-[color:var(--color-ink-muted)]">
                    <div class="flex items-center gap-1.5 flex-wrap">
                      <span class="line-clamp-1">{{ product.name }}</span>
                      <span
                        v-if="product.product_type === 'Capsule'"
                        class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-blue-100 text-blue-800 border border-blue-200"
                      >Capsule</span>
                      <span
                        v-else-if="product.product_type === 'Nasal Spray'"
                        class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200"
                      >Nasal Spray</span>
                      <span
                        v-else-if="product.product_type === 'Kit'"
                        class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-800 border border-amber-200"
                      >Kit</span>
                    </div>
                    <span v-if="product.size_mg" class="ui-mono text-[11px] text-[color:var(--color-ink-subtle)]">
                      {{ formatSize(product.size_mg) }}
                    </span>
                  </td>
                  <!-- Retail price -->
                  <td class="px-5 py-4 text-right">
                    <div v-if="product.discount_price && product.discount_price < product.price" class="flex flex-col items-end">
                      <span class="ui-mono text-[15px] font-bold text-[color:var(--color-ink)]">${{ formatPrice(product.discount_price) }}</span>
                      <span class="ui-mono text-[11px] text-[color:var(--color-ink)] line-through">${{ formatPrice(product.price) }}</span>
                    </div>
                    <span v-else class="ui-mono text-[15px] font-bold text-[color:var(--color-ink)]">
                      ${{ formatPrice(product.price) }}
                    </span>
                  </td>
                  <!-- Price with code (separate column; tinted emerald) -->
                  <td class="px-5 py-4 text-right bg-emerald-50/50">
                    <template v-if="discountedPriceFor(product)">
                      <div class="flex flex-col items-end leading-tight">
                        <span class="ui-mono text-[15px] font-bold text-emerald-700">${{ discountedPriceFor(product) }}</span>
                        <span class="ui-mono text-[10px] text-emerald-700 font-semibold uppercase tracking-wide mt-0.5">{{ couponCodeFor(product) }}</span>
                      </div>
                    </template>
                    <span v-else class="text-[12px] text-[color:var(--color-ink-subtle)]">—</span>
                  </td>
                  <!-- Savings — retail → what you actually pay (folds in
                       both the vendor's own sale AND any PMAP coupon). -->
                  <td class="px-5 py-4 text-right hidden sm:table-cell">
                    <span
                      v-if="totalSavingsPct(product)"
                      class="ui-mono text-xs font-semibold text-[color:var(--color-verified)]"
                    >
                      -{{ totalSavingsPct(product) }}%
                    </span>
                    <span v-else class="text-[color:var(--color-ink-subtle)]">—</span>
                  </td>
                  <!-- CTA -->
                  <td class="px-5 py-4 text-right">
                    <a
                      :href="product.go_url"
                      target="_blank"
                      rel="noopener noreferrer nofollow sponsored"
                      class="ui-focus inline-flex items-center gap-1 h-8 px-3 rounded-[8px] text-[12px] font-semibold text-white bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] shadow-[inset_0_1px_0_rgba(255,255,255,0.18),0_1px_2px_rgba(10,11,14,0.08)] hover:shadow-[0_2px_8px_-2px_rgba(79,70,229,0.5)] hover:-translate-y-[0.5px] transition-all"
                    >
                      Buy
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M7 17L17 7M17 7H7M17 7v10" />
                      </svg>
                    </a>
                  </td>
                </tr>
              </tbody>
            </table>
            <!-- Show more / less toggle. Hidden when the filtered set fits under the cap. -->
            <button
              v-if="getFilteredProducts(compound, idx).length > 5"
              @click="toggleCompound(idx)"
              class="ui-focus w-full h-11 flex items-center justify-center gap-1.5 text-[13px] font-semibold text-[color:var(--color-accent-600)] hover:text-[color:var(--color-accent-700)] hover:bg-[color:var(--color-hairline-soft)] border-t border-[color:var(--color-hairline)] transition-colors"
            >
              <template v-if="isExpanded(idx)">
                Show fewer
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M18 15l-6-6-6 6"/></svg>
              </template>
              <template v-else>
                Show all {{ getFilteredProducts(compound, idx).length }} vendors
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
              </template>
            </button>
          </div>

          <!-- Empty state -->
          <div
            v-if="!getFilteredProducts(compound, idx).length"
            class="bg-white rounded-[14px] border border-dashed border-[color:var(--color-hairline)] p-10 text-center text-[color:var(--color-ink-subtle)] text-sm"
          >
            <template v-if="compound.products.length && !getFilteredProducts(compound, idx).length">
              No products at this dosage. Try a different size above.
            </template>
            <template v-else>
              No vendors currently stock this compound.
            </template>
          </div>
        </div>
      </div>
    </section>

    <!-- Head-to-head comparison cards below the primary tables. People come
         to /compare for the tables — vs-pages are a secondary explore path,
         better placed after users have scanned what they came for. -->
    <section v-if="featuredPairs?.length" class="border-t border-[color:var(--color-hairline)] bg-[color:var(--color-bg)]">
      <div class="max-w-[1280px] mx-auto px-6 lg:px-10 py-12">
        <div class="text-[11px] uppercase tracking-[0.12em] font-semibold text-[color:var(--color-ink-subtle)] mb-4">
          Head-to-head comparisons
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
          <a
            v-for="p in featuredPairs"
            :key="p.url"
            :href="p.url"
            class="ui-focus group p-4 rounded-[10px] border border-[color:var(--color-hairline)] bg-white hover:border-[color:var(--color-accent-400)] hover:shadow-[var(--shadow-md)] transition-all"
          >
            <div class="text-[14px] font-semibold leading-tight">
              <span class="text-indigo-700">{{ p.a_name }}</span>
              <span class="text-[color:var(--color-ink-subtle)] mx-1.5 font-light">vs</span>
              <span class="text-emerald-700">{{ p.b_name }}</span>
            </div>
            <div class="mt-1 text-[11px] text-[color:var(--color-ink-muted)]">{{ p.tagline }}</div>
          </a>
        </div>
      </div>
    </section>
  </ModernLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import ModernLayout from '@/Pages/Layouts/ModernLayout.vue'
import WishlistHeart from '@/components/ui/WishlistHeart.vue'

const props = defineProps({
  compounds: { type: Array, default: () => [] },
  featuredPairs: { type: Array, default: () => [] },
  seo: { type: Object, default: () => ({}) },
})

// Track selected mg size per compound (by index). null = "All"
const selectedSizes = ref({})

function getSizes(compound) {
  // Collect unique size strings ("10mg", "5mg/5mg", etc.) and sort by the
  // first numeric token so the tabs read 5mg → 10mg → 20mg → blends after.
  const unique = [...new Set(
    compound.products
      .map(p => p.size_mg)
      .filter(s => s && String(s).trim() !== '')
      .map(s => String(s).toLowerCase())
  )]
  return unique.sort((a, b) => {
    const numA = parseFloat(a) || 0
    const numB = parseFloat(b) || 0
    if (numA !== numB) return numA - numB
    return a.localeCompare(b)
  })
}

function getSelectedSize(idx) {
  return selectedSizes.value[idx] ?? null
}

function setSize(idx, size) {
  selectedSizes.value = { ...selectedSizes.value, [idx]: size }
}

function getFilteredProducts(compound, idx) {
  const size = getSelectedSize(idx)
  if (!size) return compound.products
  return compound.products.filter(p => String(p.size_mg).toLowerCase() === String(size).toLowerCase())
}

// Collapse each compound to top 5 rows by default — the page was scrolling
// forever with 40-100 products per compound. Users can expand any compound
// they want to dig into. State keyed by compound idx (matches the v-for
// key we already pass around).
const COLLAPSED_LIMIT = 5
const expandedCompounds = ref({})
function isExpanded(idx) { return !!expandedCompounds.value[idx] }
function toggleCompound(idx) { expandedCompounds.value[idx] = !expandedCompounds.value[idx] }
function getVisibleProducts(compound, idx) {
  const filtered = getFilteredProducts(compound, idx)
  return isExpanded(idx) ? filtered : filtered.slice(0, COLLAPSED_LIMIT)
}

function percentCheaper(products) {
  if (products.length < 2) return null
  // Compare against the same field the rows are sorted by (final_price =
  // post-coupon when applicable, retail otherwise) so 'X% cheaper than #2'
  // reflects what visitors actually pay.
  const first = parseFloat(products[0]?.final_price ?? products[0]?.effective_price)
  const second = parseFloat(products[1]?.final_price ?? products[1]?.effective_price)
  if (!first || !second || first >= second) return null
  return Math.round((1 - first / second) * 100)
}

function formatPrice(p) {
  if (p === null || p === undefined || p === '') return '—'
  const num = typeof p === 'number' ? p : parseFloat(p)
  return isNaN(num) ? String(p) : num.toFixed(2)
}

// Vendor's effective retail price — discount_price wins when it's a real
// vendor-side sale, otherwise the plain list price.
function effectiveRetail(product) {
  const dp = parseFloat(product?.discount_price)
  const p = parseFloat(product?.price)
  if (!isNaN(dp) && dp > 0 && dp < p) return dp
  return isNaN(p) ? 0 : p
}

// Discounted price using the vendor's configured PeptideMap discount %.
// Returns null when no discount is set so the column shows a dash.
function discountedPriceFor(product) {
  const pct = parseFloat(product?.brand_discount_percent)
  if (!pct || pct <= 0 || pct >= 100) return null
  const base = effectiveRetail(product)
  if (!base || base <= 0) return null
  return (base * (1 - pct / 100)).toFixed(2)
}

// Coupon code label for the discounted price column. Falls back to PMAP
// so the label is never blank when a discount applies.
function couponCodeFor(product) {
  const raw = (product?.brand_coupon_code || '').trim()
  return (raw || 'PMAP').toUpperCase()
}

// Total savings vs. bare retail. Combines the vendor's own sale
// (discount_price vs price) AND any PMAP coupon we apply on top.
// Returns null when there's nothing to show so the cell renders '—'.
function totalSavingsPct(product) {
  const listPrice = parseFloat(product?.price)
  const finalPrice = parseFloat(product?.final_price ?? product?.effective_price)
  if (!listPrice || !finalPrice || finalPrice >= listPrice) return null
  return Math.round((1 - finalPrice / listPrice) * 100)
}

/**
 * Render a size value cleanly. Values are stored with units already
 * ("10mg", "5mg/5mg", "100mcg"). Bare numeric legacy values get "mg"
 * appended for display only.
 */
function formatSize(value) {
  if (!value) return ''
  const str = String(value).trim()
  if (/[a-zA-Z]/.test(str)) return str
  if (!Number.isNaN(Number(str))) return `${Number(str)}mg`
  return str
}
</script>
