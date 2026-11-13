<template>
  <ModernLayout>
    <Head>
      <title>{{ seo.title }}</title>
      <meta name="description" :content="seo.description" />
    </Head>

    <!-- Breadcrumb -->
    <nav class="max-w-[1280px] mx-auto px-6 lg:px-10 pt-6 text-[12px] text-[color:var(--color-ink-subtle)]" aria-label="Breadcrumb">
      <ol class="flex items-center gap-1.5">
        <li><a href="/" class="hover:text-[color:var(--color-ink)]">Home</a></li>
        <li class="text-[color:var(--color-ink-subtle)]">/</li>
        <li><a href="/compare" class="hover:text-[color:var(--color-ink)]">Compare</a></li>
        <li class="text-[color:var(--color-ink-subtle)]">/</li>
        <li class="text-[color:var(--color-ink-muted)] font-medium truncate">{{ compound.name }}</li>
      </ol>
    </nav>

    <!-- Header: title, a short intro, summary tiles, then straight to the
         table. Long copy is clamped (all of it stays in the page). -->
    <section class="border-b border-[color:var(--color-hairline)]">
      <div class="max-w-[1280px] mx-auto px-5 lg:px-10 pt-5 pb-6">
        <div class="text-[11px] uppercase tracking-[0.12em] font-semibold text-[color:var(--color-accent-600)] mb-2">Vendor comparison</div>
        <h1 class="ui-display text-3xl md:text-[42px] leading-[1.1] font-semibold tracking-[-0.02em] text-[color:var(--color-ink)]">
          {{ compound.custom_h1 || seo.h1 || compound.name }}
        </h1>
        <div v-if="compound.alias" class="mt-2 flex items-center gap-2">
          <span class="inline-flex items-center gap-1.5 text-[11px] uppercase tracking-[0.12em] font-semibold text-[color:var(--color-ink-subtle)]">Also known as</span>
          <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-white border border-[color:var(--color-hairline)] text-[12px] font-semibold ui-mono text-[color:var(--color-ink)]">{{ compound.alias }}</span>
        </div>
        <!-- Screen-reader heading for the comparison. Retatrutide uses
             price_intro instead. An empty catalog omits the heading. -->
        <h2 v-if="compound.vendor_count > 0 && !compound.price_intro" class="sr-only">{{ compound.name }} — compare listings from {{ compound.vendor_count }} {{ compound.vendor_count === 1 ? 'vendor' : 'vendors' }}</h2>

        <!-- Agency-authored intro (editable at /admin/seo/categories) stays
             under the H1, clamped to two lines with Read more so the table
             is reached sooner. The full text is in the page either way. -->
        <div v-if="compound.custom_intro || compound.price_intro || compound.summary" class="mt-3 max-w-3xl">
          <div :class="['relative text-[15px] text-[color:var(--color-ink-muted)] leading-relaxed', introOpen ? '' : 'max-h-[3.3em] overflow-hidden']">
            <div v-if="compound.custom_intro" class="prose prose-sm max-w-none text-[color:var(--color-ink-muted)]" v-html="compound.custom_intro"></div>
            <p v-if="compound.price_intro" :class="compound.custom_intro ? 'mt-3' : ''">{{ compound.price_intro }}</p>
            <p v-else-if="compound.summary" :class="compound.custom_intro ? 'mt-3' : ''">{{ compound.summary }}</p>
            <div v-if="!introOpen" class="absolute inset-x-0 bottom-0 h-6 bg-gradient-to-t from-[color:var(--color-bg)] to-transparent pointer-events-none" />
          </div>
          <button type="button" class="ui-focus mt-1 text-[13px] font-semibold text-[color:var(--color-accent-600)] hover:text-[color:var(--color-accent-700)] transition-colors" :aria-expanded="introOpen ? 'true' : 'false'" @click="introOpen = !introOpen">
            {{ introOpen ? 'Show less' : 'Read more' }}
          </button>
        </div>

        <ComparePriceSummary v-if="compound.price_stats" :stats="compound.price_stats" />
        <div v-else-if="summary" class="mt-5 grid grid-cols-2 lg:grid-cols-4 gap-2.5">
          <div class="rounded-[12px] border border-[color:var(--color-hairline)] bg-white px-4 py-3">
            <div class="text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Vendors</div>
            <div class="ui-display mt-1.5 text-[22px] font-semibold leading-none tracking-tight text-[color:var(--color-ink)]">{{ summary.vendors }}</div>
          </div>
          <div class="rounded-[12px] border border-[color:var(--color-hairline)] bg-white px-4 py-3">
            <div class="text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Listings</div>
            <div class="ui-display mt-1.5 text-[22px] font-semibold leading-none tracking-tight text-[color:var(--color-ink)]">{{ summary.listings }}</div>
          </div>
          <div v-if="summary.cheapest" class="min-w-0 rounded-[12px] border border-[color:var(--color-hairline)] bg-white px-4 py-3">
            <div class="text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Lowest price</div>
            <div class="ui-display mt-1.5 text-[22px] font-semibold leading-none tracking-tight text-[color:var(--color-ink)]">{{ isForeign(summary.cheapest) ? '≈ ' + usd(summary.cheapest.usd_final_price) : formatMoney(summary.cheapest, summary.cheapest.pmap_price || summary.cheapest.effective_price) }}</div>
            <div class="mt-1.5 truncate text-[12px] text-[color:var(--color-ink-muted)]">{{ summary.cheapest.brand_name }}<template v-if="summary.cheapest.size_mg"> · {{ summary.cheapest.size_mg }}</template><template v-if="isForeign(summary.cheapest)"> · {{ formatMoney(summary.cheapest, summary.cheapest.pmap_price || summary.cheapest.effective_price) }}</template></div>
          </div>
          <button
            v-if="summary.best"
            type="button"
            class="ui-focus min-w-0 text-left rounded-[12px] border border-emerald-200 bg-emerald-50/70 px-4 py-3 hover:border-emerald-400 transition-colors"
            @click="showBestValue"
          >
            <div class="text-[11px] uppercase tracking-[0.08em] font-semibold text-emerald-700">Best per mg</div>
            <div class="ui-display mt-1.5 text-[22px] font-semibold leading-none tracking-tight text-emerald-800">{{ usd(summary.best.usd_per_mg) }}<span class="text-[13px] font-medium">/mg</span></div>
            <div class="mt-1.5 truncate text-[12px] text-emerald-800/80">{{ summary.best.brand_name }}<template v-if="summary.best.size_mg"> · {{ summary.best.size_mg }}</template> · sort by value →</div>
          </button>
        </div>

        <CatalogCrossLinks :groups="catalogLinks?.groups || []" />
      </div>
    </section>

    <!-- Listings: size tabs, format, sort and trust filters, then one row
         per listing (cards on phones, a table from sm up). Filters run in
         the browser and are kept in the URL; trust filters reload. -->
    <section class="max-w-[1280px] mx-auto px-5 lg:px-10 pt-6 pb-10">
      <div v-if="compound.products.length">
        <div class="mb-4 space-y-3">
          <div ref="sizeScroller" class="flex items-center gap-1.5 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden pb-0.5" role="tablist" aria-label="Vial size">
            <span class="shrink-0 w-12 text-[11px] uppercase tracking-[0.1em] font-semibold text-[color:var(--color-ink-subtle)]">Size</span>
            <button type="button" role="tab" :aria-selected="selectedSize === 'all' ? 'true' : 'false'" :class="chipClass(selectedSize === 'all')" @click="selectedSize = 'all'">
              All sizes <span class="ml-1 ui-mono text-[11px] opacity-60">{{ compound.products.length }}</span>
            </button>
            <button
              v-for="tab in sizeTabs"
              :key="tab.key"
              type="button"
              role="tab"
              :aria-selected="selectedSize === tab.key ? 'true' : 'false'"
              :class="chipClass(selectedSize === tab.key)"
              @click="selectedSize = tab.key"
            >
              <span class="ui-mono">{{ tab.label }}</span> <span class="ml-1 ui-mono text-[11px] opacity-60">{{ tab.count }}</span>
            </button>
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <!-- Phones: format and trust rows fold behind this button so the
                 listings start sooner. -->
            <button
              type="button"
              class="sm:hidden ui-focus inline-flex items-center gap-1.5 h-9 px-3 rounded-[10px] border border-[color:var(--color-hairline)] bg-white text-[13px] font-semibold text-[color:var(--color-ink)]"
              :aria-expanded="filtersOpen ? 'true' : 'false'"
              @click="filtersOpen = !filtersOpen"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 5h18M6 12h12M10 19h4"/></svg>
              Filters
              <span v-if="activeFilterCount" class="inline-flex items-center justify-center h-5 min-w-5 px-1 rounded-full bg-[color:var(--color-ink)] text-white text-[11px]">{{ activeFilterCount }}</span>
            </button>
            <div v-if="formatOptions.length > 1" :class="[filtersOpen ? 'flex w-full order-last' : 'hidden', 'sm:flex sm:w-auto sm:order-none items-center gap-1.5 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden max-w-full']">
              <span class="shrink-0 w-12 text-[11px] uppercase tracking-[0.1em] font-semibold text-[color:var(--color-ink-subtle)]">Format</span>
              <button type="button" :class="chipClass(selectedFormat === 'all')" @click="selectedFormat = 'all'">All</button>
              <button v-for="f in formatOptions" :key="f" type="button" :class="chipClass(selectedFormat === f)" @click="selectedFormat = f">{{ f }}</button>
            </div>
            <div class="ml-auto flex items-center gap-2">
              <label for="compare-sort" class="text-[11px] uppercase tracking-[0.1em] font-semibold text-[color:var(--color-ink-subtle)]">Sort</label>
              <select id="compare-sort" v-model="sortKey" class="h-9 pl-3 pr-8 text-[13px] rounded-[10px] border border-[color:var(--color-hairline)] bg-white focus:border-[color:var(--color-accent-500)] focus:outline-none">
                <option value="price">Cheapest first</option>
                <option value="value">Best price per mg</option>
                <option value="vendor">Vendor A–Z</option>
              </select>
            </div>
          </div>

          <!-- Trust filters — Colin PMAP tab 1: same toggles as the vendors
               and peptides pages (?verified=csv&usp=us_manufactured). -->
          <div :class="[filtersOpen ? 'flex' : 'hidden', 'sm:flex flex-wrap items-center gap-1.5']">
            <span class="shrink-0 w-12 text-[11px] uppercase tracking-[0.1em] font-semibold text-[color:var(--color-ink-subtle)]">Trust</span>
            <button
              v-for="f in verifiedFilters"
              :key="f.value"
              type="button"
              @click="toggleVerified(f.value)"
              :aria-pressed="activeVerified.has(f.value) ? 'true' : 'false'"
              :class="[
                'ui-focus h-8 px-3 rounded-full text-[12px] font-semibold transition-colors border flex items-center gap-1.5',
                activeVerified.has(f.value)
                  ? 'bg-[color:var(--color-ink)] text-white border-[color:var(--color-ink)]'
                  : 'bg-white text-[color:var(--color-ink-muted)] border-[color:var(--color-hairline)] hover:border-[color:var(--color-ink-subtle)]',
              ]"
            >
              <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>
              {{ f.label }}
            </button>
            <button
              type="button"
              @click="toggleUsp('us_manufactured')"
              :aria-pressed="activeUsp === 'us_manufactured' ? 'true' : 'false'"
              :class="[
                'ui-focus h-8 px-3 rounded-full text-[12px] font-semibold transition-colors border flex items-center gap-1.5',
                activeUsp === 'us_manufactured'
                  ? 'bg-[color:var(--color-ink)] text-white border-[color:var(--color-ink)]'
                  : 'bg-white text-[color:var(--color-ink-muted)] border-[color:var(--color-hairline)] hover:border-[color:var(--color-ink-subtle)]',
              ]"
            >
              <svg class="w-4 h-3 rounded-[1px]" viewBox="0 0 21 15" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <rect width="21" height="15" fill="#b22234"/>
                <path stroke="#fff" stroke-width="1.15" d="M0 2.3h21M0 4.6h21M0 6.9h21M0 9.2h21M0 11.5h21M0 13.8h21"/>
                <rect width="9" height="8" fill="#3c3b6e"/>
              </svg>
              US Made
            </button>
            <button
              v-if="activeVerified.size > 0 || activeUsp"
              type="button"
              @click="clearTrustFilters"
              class="text-[11px] text-[color:var(--color-ink-subtle)] hover:text-[color:var(--color-ink-muted)] underline ml-1"
            >clear</button>
          </div>
        </div>

        <p class="mb-2.5 text-[12px] text-[color:var(--color-ink-subtle)]">
          <span class="ui-mono font-semibold text-[color:var(--color-ink-muted)]">{{ visibleRows.length }}</span> {{ visibleRows.length === 1 ? 'listing' : 'listings' }}
          · {{ sortLabel }} · prices in each vendor's currency, ≈ USD under the rest
        </p>

        <!-- Phones: one card per listing; price and Buy always visible. -->
        <ul class="sm:hidden space-y-2">
          <li
            v-for="(product, pidx) in visibleRows"
            :key="product.id"
            :class="[
              'rounded-[14px] border bg-white p-3',
              isLowest(product, pidx) ? 'border-emerald-300 ring-1 ring-emerald-200' : 'border-[color:var(--color-hairline)]',
            ]"
          >
            <div class="flex items-start gap-3">
              <VendorLogo :src="product.brand_logo" :name="product.brand_name" :alt="product.brand_name" size="sm" radius="md" fallback="subtle" />
              <div class="min-w-0 flex-1">
                <div class="flex items-start justify-between gap-2">
                  <div class="min-w-0">
                    <a :href="`/brand/${product.brand_slug}`" class="block truncate text-[14px] font-semibold text-[color:var(--color-ink)]">{{ product.brand_name }}</a>
                    <a :href="`/product/${product.brand_slug}/${product.slug}/${product.id}`" class="block truncate text-[12px] text-[color:var(--color-ink-muted)]">{{ product.name }}</a>
                    <span v-if="isLowest(product, pidx)" class="mt-1 inline-block text-[10px] font-bold uppercase tracking-[0.1em] text-[color:var(--color-verified)]">Lowest listed</span>
                  </div>
                  <div class="flex-shrink-0 text-right">
                    <div :class="['ui-mono text-[16px] font-bold leading-tight', product.pmap_price ? 'text-emerald-700' : 'text-[color:var(--color-ink)]']">{{ formatMoney(product, product.pmap_price || product.effective_price) }}</div>
                    <div v-if="product.pmap_price" class="ui-mono text-[11px] text-[color:var(--color-ink-subtle)] line-through">{{ formatMoney(product, product.effective_price) }}</div>
                    <div v-if="isForeign(product)" class="text-[11px] text-[color:var(--color-ink-subtle)]">≈ {{ usd(product.usd_final_price) }}</div>
                  </div>
                </div>
                <div class="mt-2 flex items-center gap-2 min-w-0">
                  <CouponChip v-if="product.pmap_price" :code="product.brand_coupon_code || 'PMAP'" />
                  <PricePerMg
                    v-if="product.price_per_mg"
                    :value="product.price_per_mg"
                    :currency-symbol="product.currency_symbol || '$'"
                    :best="!!product.best_value"
                    :size-label="product.size_mg || ''"
                    :with-code="!!product.pmap_price"
                    size="xs"
                  />
                  <a
                    :href="withSrc(product.go_url)"
                    @click="openBuy($event, product)"
                    target="_blank"
                    rel="noopener noreferrer nofollow sponsored"
                    class="ui-focus ml-auto flex-shrink-0 inline-flex items-center gap-1 h-9 px-3.5 rounded-[10px] text-[13px] font-semibold text-white bg-gradient-to-b from-[#0284C7] to-[#0369A1]"
                  >
                    Buy
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17L17 7M17 7H7M17 7v10"/></svg>
                  </a>
                </div>
              </div>
            </div>
          </li>
        </ul>

        <!-- sm and up: table. -->
        <div class="hidden sm:block bg-white rounded-[14px] border border-[color:var(--color-hairline)] overflow-hidden shadow-[var(--shadow-xs)]">
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-[color:var(--color-hairline)] bg-[color:var(--color-bg)]">
                  <th class="text-left px-5 py-3 text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Vendor</th>
                  <th class="text-left px-5 py-3 text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Product</th>
                  <th class="text-left px-5 py-3 text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Size</th>
                  <th class="text-right px-5 py-3 text-[11px] uppercase tracking-[0.08em] font-semibold text-emerald-700" title="What you pay, with the vendor's code when one applies">Price</th>
                  <th class="text-right px-5 py-3 text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]" title="Price per mg for single vials, after the code when one applies">Per mg</th>
                  <th class="text-right px-5 py-3 w-[100px]"></th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(product, pidx) in visibleRows"
                  :key="product.id"
                  :class="[
                    'border-b border-[color:var(--color-hairline-soft)] hover:bg-[color:var(--color-hairline-soft)] transition-colors',
                    isLowest(product, pidx) ? 'bg-[color:var(--color-verified-bg)]' : '',
                  ]"
                >
                  <td class="px-5 py-4">
                    <div class="flex items-center gap-2.5">
                      <VendorLogo :src="product.brand_logo" :name="product.brand_name" :alt="product.brand_name" size="xxs" radius="sm" fallback="subtle" />
                      <div class="min-w-0">
                        <a :href="`/brand/${product.brand_slug}`" class="font-semibold text-[color:var(--color-ink)] hover:text-[color:var(--color-accent-600)] transition-colors">{{ product.brand_name }}</a>
                        <!-- Price outliers sort last and never take this badge. -->
                        <div v-if="isLowest(product, pidx)" class="mt-0.5 text-[10px] font-bold uppercase tracking-[0.1em] text-[color:var(--color-verified)]">Lowest listed</div>
                      </div>
                    </div>
                  </td>
                  <td class="px-5 py-4">
                    <a :href="`/product/${product.brand_slug}/${product.slug}/${product.id}`" class="text-[color:var(--color-ink)] hover:text-[color:var(--color-accent-600)] transition-colors">{{ product.name }}</a>
                  </td>
                  <td class="px-5 py-4 text-[color:var(--color-ink-muted)] ui-mono text-[13px] whitespace-nowrap">{{ product.size_mg || '—' }}</td>
                  <td class="px-5 py-4 text-right whitespace-nowrap">
                    <div :class="['ui-mono font-semibold', product.pmap_price ? 'text-emerald-700' : 'text-[color:var(--color-ink)]']">{{ formatMoney(product, product.pmap_price || product.effective_price) }}</div>
                    <div v-if="product.pmap_price" class="ui-mono text-[11px] text-[color:var(--color-ink-subtle)] line-through">{{ formatMoney(product, product.effective_price) }}</div>
                    <div v-if="isForeign(product)" class="text-[11px] text-[color:var(--color-ink-subtle)]">≈ {{ usd(product.usd_final_price) }}</div>
                    <div v-if="product.pmap_price" class="mt-1 flex justify-end">
                      <CouponChip :code="product.brand_coupon_code || 'PMAP'" />
                    </div>
                  </td>
                  <td class="px-5 py-4 text-right whitespace-nowrap">
                    <PricePerMg
                      v-if="product.price_per_mg"
                      :value="product.price_per_mg"
                      :currency-symbol="product.currency_symbol || '$'"
                      :best="!!product.best_value"
                      :size-label="product.size_mg || ''"
                      :with-code="!!product.pmap_price"
                    />
                    <span v-else class="text-[color:var(--color-ink-subtle)] text-[12px]">—</span>
                    <div v-if="product.price_per_mg && isForeign(product) && product.usd_per_mg" class="text-[11px] text-[color:var(--color-ink-subtle)]">≈ {{ usd(product.usd_per_mg) }}/mg</div>
                  </td>
                  <td class="px-5 py-4 text-right">
                    <a
                      :href="withSrc(product.go_url)"
                      @click="openBuy($event, product)"
                      target="_blank"
                      rel="noopener noreferrer nofollow sponsored"
                      class="ui-focus inline-flex items-center gap-1 h-9 px-3 rounded-md text-[12px] font-semibold text-white bg-gradient-to-b from-[#0284C7] to-[#0369A1] hover:-translate-y-[1px] transition-all"
                    >
                      Buy
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17L17 7M17 7H7M17 7v10"/></svg>
                    </a>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div v-if="!visibleRows.length" class="mt-3 rounded-[14px] border border-dashed border-[color:var(--color-hairline)] bg-white p-8 text-center">
          <p class="text-[14px] text-[color:var(--color-ink-muted)]">No listings match this size and format.</p>
          <button type="button" class="mt-3 text-[13px] font-semibold text-[color:var(--color-accent-600)] hover:text-[color:var(--color-accent-700)]" @click="resetFilters">Show all listings</button>
        </div>

        <div class="mt-4 inline-flex items-center gap-2 px-3 py-1.5 rounded-md bg-white border border-[color:var(--color-hairline)] text-[11px] text-[color:var(--color-ink-subtle)]">
          <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
          All products listed are for <strong class="text-[color:var(--color-ink-muted)]">research use only</strong> (RUO). Not for human consumption.
        </div>
      </div>

      <div v-else class="bg-white rounded-[14px] border border-dashed border-[color:var(--color-hairline)] p-12 text-center text-[color:var(--color-ink-subtle)]">
        <p class="text-lg font-medium text-[color:var(--color-ink)] mb-2">No priced listings to compare</p>
        <p class="text-sm mb-6">Peptidemap has no vendor listing with a live price for {{ compound.name }}.</p>
        <a href="/compare" class="ui-focus inline-flex items-center gap-1 h-10 px-4 rounded-md bg-[color:var(--color-ink)] text-white text-[13px] font-semibold hover:opacity-90 transition-opacity">
          Browse other compounds
        </a>
      </div>
    </section>

    <section v-if="compound.price_stats" class="border-t border-[color:var(--color-hairline)] bg-white">
      <div class="max-w-[1280px] mx-auto px-6 lg:px-10 py-10">
        <h2 class="text-[22px] font-semibold tracking-[-0.01em] text-[color:var(--color-ink)] mb-3">{{ compound.per_mg_title }}</h2>
        <p class="text-[14px] text-[color:var(--color-ink-muted)] leading-relaxed max-w-3xl mb-5">{{ compound.per_mg_intro }}</p>
        <PricePerMgTable
          :rows="compound.price_stats.per_mg.rows"
          :footer="compound.price_stats.per_mg.footer"
        />
      </div>
    </section>

    <section v-if="compound.code_names" class="border-t border-[color:var(--color-hairline)] bg-[color:var(--color-bg)]">
      <div class="max-w-[1280px] mx-auto px-6 lg:px-10 py-10">
        <h2 class="text-[22px] font-semibold tracking-[-0.01em] text-[color:var(--color-ink)] mb-3">{{ compound.code_names.title }}</h2>
        <p class="text-[14px] text-[color:var(--color-ink-muted)] leading-relaxed max-w-3xl mb-5">
          <template v-for="(part, i) in compound.code_names.intro" :key="'cn'+i">
            <a v-if="part.href" :href="part.href" class="text-[color:var(--color-accent-600)] hover:text-[color:var(--color-accent-700)]">{{ part.text }}</a>
            <template v-else>{{ part.text }}</template>
          </template>
        </p>
        <div v-if="compound.code_names.rows?.length" class="bg-white rounded-[14px] border border-[color:var(--color-hairline)] overflow-hidden mb-5">
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-[color:var(--color-hairline)] bg-white">
                  <th scope="col" class="text-left px-5 py-3 text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Catalog name</th>
                  <th scope="col" class="text-left px-5 py-3 text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Listed by</th>
                  <th scope="col" class="text-left px-5 py-3 text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Example catalog listing</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in compound.code_names.rows" :key="row.url" class="border-b border-[color:var(--color-hairline-soft)]">
                  <td class="px-5 py-4 text-[color:var(--color-ink)]">{{ row.label }}</td>
                  <td class="px-5 py-4 text-[color:var(--color-ink-muted)]">{{ row.listed_by }}</td>
                  <td class="px-5 py-4">
                    <a :href="row.url" class="text-[color:var(--color-accent-600)] hover:text-[color:var(--color-accent-700)]">{{ row.link_label }}</a>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <p v-if="compound.code_names.blend" class="text-[14px] text-[color:var(--color-ink-muted)] leading-relaxed max-w-3xl mb-4">
          <strong class="text-[color:var(--color-ink)]">{{ compound.code_names.blend.lead }}</strong>
          <a :href="compound.code_names.blend.url" class="text-[color:var(--color-accent-600)] hover:text-[color:var(--color-accent-700)]">{{ compound.code_names.blend.link_label }}</a>{{ compound.code_names.blend.after }}
        </p>
        <p class="text-[14px] text-[color:var(--color-ink-muted)] leading-relaxed max-w-3xl">{{ compound.code_names.outro }}</p>
      </div>
    </section>

    <section v-if="compound.why_prices" class="border-t border-[color:var(--color-hairline)] bg-white">
      <div class="max-w-[1280px] mx-auto px-6 lg:px-10 py-10">
        <h2 class="text-[22px] font-semibold tracking-[-0.01em] text-[color:var(--color-ink)] mb-3">{{ compound.why_prices.title }}</h2>
        <div class="max-w-3xl space-y-4 text-[14px] text-[color:var(--color-ink-muted)] leading-relaxed">
          <p>{{ compound.why_prices.intro }}</p>
          <ul class="list-disc pl-5 space-y-2">
            <li v-for="(bullet, i) in compound.why_prices.bullets" :key="'w'+i">
              <strong class="text-[color:var(--color-ink)]">{{ bullet.title }}</strong>
              <template v-for="(part, j) in bullet.parts" :key="'wp'+i+'-'+j">
                <a v-if="part.href" :href="part.href" class="text-[color:var(--color-accent-600)] hover:text-[color:var(--color-accent-700)]">{{ part.text }}</a>
                <template v-else>{{ part.text }}</template>
              </template>
            </li>
          </ul>
          <p>{{ compound.why_prices.closing }}</p>
        </div>
      </div>
    </section>

    <!-- Retatrutide only. Trial dose outcomes stay on the encyclopedia page. -->
    <section v-if="compound.trial_context" class="border-t border-[color:var(--color-hairline)] bg-white">
      <div class="max-w-[1280px] mx-auto px-6 lg:px-10 py-8">
        <p class="text-[14px] text-[color:var(--color-ink-muted)] leading-relaxed max-w-3xl">
          {{ compound.trial_context.line }}
          <a :href="compound.trial_context.href" class="text-[color:var(--color-accent-600)] hover:text-[color:var(--color-accent-700)]">{{ compound.trial_context.link }}</a>
        </p>
      </div>
    </section>

    <!-- Head-to-head suggestions — pairs from FEATURED_VS_PAIRS involving this compound.
         Positioned right after the price table so someone finishing a scan
         of the vendor list has a natural next-click to a comparison page. -->
    <section v-if="vsPairs?.length" class="border-t border-[color:var(--color-hairline)] bg-white">
      <div class="max-w-[1280px] mx-auto px-6 lg:px-10 py-10">
        <div class="text-[11px] uppercase tracking-[0.12em] font-semibold text-[color:var(--color-ink-subtle)] mb-4">
          Compare {{ compound.name }} with…
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
          <a
            v-for="p in vsPairs"
            :key="p.url"
            :href="p.url"
            class="ui-focus group p-4 rounded-[10px] border border-[color:var(--color-hairline)] bg-white hover:border-[color:var(--color-accent-400)] hover:shadow-[var(--shadow-md)] transition-all"
          >
            <div class="text-[14px] font-semibold leading-tight">
              <span class="text-sky-700">{{ p.a_name }}</span>
              <span class="text-[color:var(--color-ink-subtle)] mx-1.5 font-light">vs</span>
              <span class="text-emerald-700">{{ p.b_name }}</span>
            </div>
            <div class="mt-1 text-[11px] text-[color:var(--color-ink-muted)]">{{ p.tagline }}</div>
          </a>
        </div>
      </div>
    </section>

    <!-- FAQ — rendered visibly so it matches the FAQPage schema.
         Answers are catalog counts. They do not name a price or a vendor. -->
    <section v-if="compound.faqs && compound.faqs.length" class="border-t border-[color:var(--color-hairline)] bg-white">
      <div class="max-w-[1280px] mx-auto px-6 lg:px-10 py-10">
        <div class="text-[11px] uppercase tracking-[0.12em] font-semibold text-[color:var(--color-ink-subtle)] mb-4">
          {{ compound.name }} — frequently asked
        </div>
        <div class="max-w-3xl space-y-6">
          <div v-for="(f, i) in compound.faqs" :key="i">
            <h3 class="text-[15px] font-semibold text-[color:var(--color-ink)] mb-1.5">{{ f.q }}</h3>
            <p class="text-[14px] text-[color:var(--color-ink-muted)] leading-relaxed">{{ f.a }}</p>
          </div>
        </div>
      </div>
    </section>

    <section v-if="compound.related_reading?.length" class="border-t border-[color:var(--color-hairline)] bg-white">
      <div class="max-w-[1280px] mx-auto px-6 lg:px-10 py-8">
        <p class="text-[14px] text-[color:var(--color-ink-muted)] leading-relaxed">
          Related reading:
          <template v-for="(link, i) in compound.related_reading" :key="link.href">
            <span v-if="i"> · </span>
            <a :href="link.href" class="text-[color:var(--color-accent-600)] hover:text-[color:var(--color-accent-700)]">{{ link.label }}</a>
          </template>
        </p>
      </div>
    </section>

    <!-- Related compounds (internal linking, strategist rec #16) -->
    <section v-if="related.length" class="border-t border-[color:var(--color-hairline)] bg-[color:var(--color-bg)]">
      <div class="max-w-[1280px] mx-auto px-6 lg:px-10 py-10">
        <div class="text-[11px] uppercase tracking-[0.12em] font-semibold text-[color:var(--color-ink-subtle)] mb-4">Compare other compounds</div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 gap-3">
          <a
            v-for="r in related"
            :key="r.id"
            :href="r.url"
            class="ui-focus group p-4 rounded-[10px] border border-[color:var(--color-hairline)] bg-white hover:border-[color:var(--color-accent-400)] hover:shadow-[var(--shadow-md)] transition-all"
          >
            <div class="text-[14px] font-semibold text-[color:var(--color-ink)] group-hover:text-[color:var(--color-accent-600)] transition-colors leading-tight line-clamp-2 min-h-[2.6em]">
              {{ r.name }}
            </div>
            <div class="mt-2 text-[11px] text-[color:var(--color-ink-muted)]">
              {{ r.product_count }} vendor{{ r.product_count === 1 ? '' : 's' }} →
            </div>
          </a>
        </div>
      </div>
    </section>

    <BuyThroughModal ref="buyModal" />
  </ModernLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import ModernLayout from '../Layouts/ModernLayout.vue'
import BuyThroughModal from '@/components/BuyThroughModal.vue'
import ComparePriceSummary from '@/components/ComparePriceSummary.vue'
import PricePerMgTable from '@/components/PricePerMgTable.vue'
import PricePerMg from '@/components/price/PricePerMg.vue'
import VendorLogo from '@/components/ui/VendorLogo.vue'
import CouponChip from '@/components/ui/CouponChip.vue'
import CatalogCrossLinks from '@/components/CatalogCrossLinks.vue'
import { withSrc } from '@/composables/useOutbound'
import { sizeInfo, compareSizeInfo } from '@/components/price/priceFormat'

const props = defineProps({
  compound: { type: Object, required: true },
  related: { type: Array, default: () => [] },
  vsPairs: { type: Array, default: () => [] },
  catalogLinks: { type: Object, default: () => ({ groups: [] }) },
  seo: { type: Object, default: () => ({}) },
  trustFilters: { type: Object, default: () => ({ verified: [], usp: null }) },
})

// Trust filter chips — Colin PMAP tab 1. Same URL contract as
// /vendors (?verified=csv&usp=key) so the two surfaces stay
// consistent and a shared bookmarked URL keeps its shape.
const verifiedFilters = [
  { label: 'cGMP (vendor-reported)', value: 'cgmp' },
  { label: '7+ Tested', value: 'testing_7x' },
]
const activeVerified = computed(() => new Set(props.trustFilters?.verified || []))
const activeUsp = computed(() => props.trustFilters?.usp || null)

function trustNavigate(nextVerified, nextUsp) {
  const params = new URLSearchParams(window.location.search)
  if (nextVerified.size > 0) params.set('verified', [...nextVerified].join(','))
  else params.delete('verified')
  if (nextUsp) params.set('usp', nextUsp)
  else params.delete('usp')
  const qs = params.toString()
  router.visit(window.location.pathname + (qs ? '?' + qs : ''), {
    preserveState: true,
    preserveScroll: true,
  })
}
function toggleVerified(value) {
  const set = new Set(activeVerified.value)
  if (set.has(value)) set.delete(value)
  else set.add(value)
  trustNavigate(set, activeUsp.value)
}
function toggleUsp(value) {
  trustNavigate(activeVerified.value, activeUsp.value === value ? null : value)
}
function clearTrustFilters() {
  trustNavigate(new Set(), null)
}

const buyModal = ref(null)
function openBuy(ev, product) {
  if (ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.button === 1) return
  const pct = parseFloat(product?.brand_discount_percent)
  if (!pct || pct <= 0 || pct >= 100) return
  const code = ((product?.brand_coupon_code || '').trim() || 'PMAP').toUpperCase()
  ev.preventDefault()
  buyModal.value?.open({
    destination: withSrc(product.go_url),
    code,
    brandName: product.brand_name,
    discountPct: pct,
  })
}

function formatPrice(v) {
  if (v == null) return '—'
  const n = Number(v)
  return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}
function formatMoney(product, amount) {
  const symbol = product?.currency_symbol || '$'
  const gap = /[A-Za-z]$/.test(symbol) ? ' ' : ''
  return symbol + gap + formatPrice(amount)
}
function truncate(s, max) {
  if (!s) return ''
  return s.length <= max ? s : s.slice(0, max - 1).trimEnd() + '…'
}

// ---------------------------------------------------------------
// Listing filters: size tabs, format, sort (client side, kept in the URL)
// ---------------------------------------------------------------
const introOpen = ref(false)
const filtersOpen = ref(false)
const sizeScroller = ref(null)
const rows = computed(() => props.compound?.products || [])

function formatOf(row) {
  switch (row?.product_type) {
    case 'Kit': return 'Kits'
    case 'Capsule': return 'Capsules'
    case 'Nasal Spray': return 'Sprays'
    case 'Topical': return 'Topicals'
    case 'Pens': return 'Pens'
    case null:
    case undefined:
    case '':
    case 'Peptide': return 'Vials'
    default: return 'Other'
  }
}
const FORMAT_ORDER = ['Vials', 'Kits', 'Capsules', 'Sprays', 'Topicals', 'Pens', 'Other']
const formatOptions = computed(() => {
  const present = new Set(rows.value.map(formatOf))
  return FORMAT_ORDER.filter((f) => present.has(f))
})

const sizeGroups = computed(() => {
  const groups = new Map()
  for (const r of rows.value) {
    if (r.size_mg == null || r.size_mg === '') continue
    const info = sizeInfo(r.size_mg)
    let g = groups.get(info.key)
    if (!g) {
      g = { key: info.key, info, label: String(r.size_mg).replace(/\s+/g, ''), count: 0, vials: 0 }
      groups.set(info.key, g)
    }
    g.count++
    if (formatOf(r) === 'Vials') g.vials++
  }
  return [...groups.values()]
})
// The eight most-listed sizes, in size order.
const sizeTabs = computed(() =>
  [...sizeGroups.value]
    .sort((a, b) => b.count - a.count)
    .slice(0, 8)
    .sort((a, b) => compareSizeInfo(a.info, b.info))
)

// First view compares like with like: the most-listed single-vial size.
// Small catalogs, or ones without a clear common size, show everything.
const defaultSizeKey = computed(() => {
  if (rows.value.length < 12) return 'all'
  const top = [...sizeGroups.value].sort((a, b) => b.vials - a.vials)[0]
  return top && top.vials >= 3 ? top.key : 'all'
})

const initialParams = typeof window !== 'undefined' ? new URLSearchParams(window.location.search) : new URLSearchParams()
const urlSize = initialParams.get('size')
const selectedSize = ref(urlSize === 'all' ? 'all' : (urlSize ? sizeInfo(urlSize).key : defaultSizeKey.value))
const urlFormat = initialParams.get('format')
const selectedFormat = ref(urlFormat && FORMAT_ORDER.includes(urlFormat) ? urlFormat : 'all')
const urlSort = initialParams.get('sort')
const sortKey = ref(['price', 'value', 'vendor'].includes(urlSort) ? urlSort : 'price')

const sortLabel = computed(() => ({
  price: 'cheapest first',
  value: 'best price per mg first',
  vendor: 'vendor A–Z',
}[sortKey.value]))

function numOrInf(v) {
  const n = Number(v)
  return Number.isFinite(n) && n > 0 ? n : Infinity
}
function cmp(a, b) {
  if (a === b) return 0
  return a < b ? -1 : 1
}

// Rows arrive cheapest-first in USD (outliers last) from the server.
const visibleRows = computed(() => {
  let list = rows.value.filter((r) =>
    (selectedSize.value === 'all' || (r.size_mg != null && r.size_mg !== '' && sizeInfo(r.size_mg).key === selectedSize.value))
    && (selectedFormat.value === 'all' || formatOf(r) === selectedFormat.value)
  )
  if (sortKey.value === 'value') {
    list = [...list].sort((a, b) =>
      cmp(a.price_outlier ? 1 : 0, b.price_outlier ? 1 : 0)
      || cmp(numOrInf(a.usd_per_mg), numOrInf(b.usd_per_mg))
      || cmp(numOrInf(a.usd_final_price), numOrInf(b.usd_final_price))
    )
  } else if (sortKey.value === 'vendor') {
    list = [...list].sort((a, b) => String(a.brand_name || '').localeCompare(String(b.brand_name || '')))
  }
  return list
})

function isLowest(product, index) {
  return index === 0 && sortKey.value === 'price' && !props.compound.price_stats && !product.price_outlier
}

function chipClass(active) {
  return [
    'ui-focus shrink-0 whitespace-nowrap inline-flex items-center h-8 px-3 rounded-full border text-[12px] font-medium transition-colors',
    active
      ? 'bg-[color:var(--color-ink)] text-white border-[color:var(--color-ink)]'
      : 'bg-white text-[color:var(--color-ink-muted)] border-[color:var(--color-hairline)] hover:border-[color:var(--color-ink-subtle)]',
  ]
}

const activeFilterCount = computed(() =>
  (selectedFormat.value !== 'all' ? 1 : 0) + activeVerified.value.size + (activeUsp.value ? 1 : 0)
)

// The default size tab can sit past the right edge on phones; bring the
// active tab into view without scrolling the page.
function revealActiveSize() {
  const scroller = sizeScroller.value
  const active = scroller?.querySelector('[aria-selected="true"]')
  if (!scroller || !active) return
  const offset = active.getBoundingClientRect().left - scroller.getBoundingClientRect().left
  if (offset < 56 || offset + active.offsetWidth > scroller.clientWidth) {
    scroller.scrollLeft += offset - 56
  }
}
onMounted(() => nextTick(revealActiveSize))
watch(selectedSize, () => nextTick(revealActiveSize))

function resetFilters() {
  selectedSize.value = 'all'
  selectedFormat.value = 'all'
}

function showBestValue() {
  sortKey.value = 'value'
  selectedSize.value = 'all'
}

// Keep the view in the URL without a reload or a new history entry.
watch([selectedSize, selectedFormat, sortKey], () => {
  if (typeof window === 'undefined') return
  const url = new URL(window.location.href)
  if (selectedSize.value === defaultSizeKey.value) url.searchParams.delete('size')
  else url.searchParams.set('size', selectedSize.value === 'all' ? 'all' : (sizeGroups.value.find((g) => g.key === selectedSize.value)?.label || 'all'))
  if (selectedFormat.value === 'all') url.searchParams.delete('format')
  else url.searchParams.set('format', selectedFormat.value)
  if (sortKey.value === 'price') url.searchParams.delete('sort')
  else url.searchParams.set('sort', sortKey.value)
  window.history.replaceState(window.history.state, '', url.pathname + url.search + url.hash)
})

// Summary tiles when the server sends no price_stats.
const summary = computed(() => {
  const list = rows.value.filter((r) => !r.price_outlier)
  if (!list.length) return null
  let cheapest = null
  let best = null
  for (const r of list) {
    const usdPrice = Number(r.usd_final_price)
    if (usdPrice > 0 && (!cheapest || usdPrice < Number(cheapest.usd_final_price))) cheapest = r
    const perMg = Number(r.usd_per_mg)
    if (perMg > 0 && (!best || perMg < Number(best.usd_per_mg))) best = r
  }
  return {
    vendors: new Set(list.map((r) => r.brand_slug || r.brand_name)).size,
    listings: rows.value.length,
    cheapest,
    best,
  }
})

function isForeign(product) {
  return !!product?.currency_code && product.currency_code !== 'USD'
}
function usd(value) {
  const n = Number(value)
  return Number.isFinite(n) && n > 0 ? '$' + n.toFixed(2) : '—'
}
</script>
