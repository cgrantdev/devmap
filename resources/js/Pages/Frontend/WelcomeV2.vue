<template>
  <ModernLayout>
    <Head>
      <title>{{ seo.title }}</title>
      <meta name="description" :content="seo.description" />
    </Head>

    <!-- ========================================================= -->
    <!-- 1. INTRO ROW + PARTNER HERO                                -->
    <!-- ========================================================= -->
    <section class="relative">
      <div class="ui-spotlight absolute inset-0 pointer-events-none" />
      <!-- The page's only H1 (what Peptidemap is, with live counts) and the
           search box share one short row, so the partner carousel below is
           the first big thing on screen. The carousel's slide titles are
           h2s; keep this H1 smaller than them so the two don't compete.
           seo.h1 carries the same text server-side. -->
      <div class="relative max-w-[1280px] mx-auto px-5 lg:px-10 pt-5 lg:pt-7">
        <div class="flex flex-col lg:flex-row lg:items-center gap-4 lg:gap-10">
          <div class="min-w-0 flex-1">
            <h1 class="ui-display text-[22px] sm:text-[24px] lg:text-[26px] font-semibold tracking-[-0.02em] leading-[1.15] text-[color:var(--color-ink)] text-balance">
              {{ heading }}
            </h1>
            <!-- The green dot doubles as the separator before the last item,
                 so a wrap on phones never leaves a dangling middot. -->
            <p class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-[12px] lg:text-[13px] text-[color:var(--color-ink-muted)]">
              <span v-if="statItems.length" class="inline-flex flex-wrap items-center gap-x-2">
                <template v-for="(item, i) in statItems" :key="item.label">
                  <span v-if="i > 0" aria-hidden="true" class="text-[color:var(--color-ink-subtle)]">·</span>
                  <span><span class="ui-mono font-semibold text-[color:var(--color-ink)]">{{ formatNumber(item.value) }}</span> {{ item.label }}</span>
                </template>
              </span>
              <span class="inline-flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-[color:var(--color-verified)]" aria-hidden="true" />
                prices checked daily
              </span>
            </p>
          </div>

          <div class="w-full lg:w-[400px] xl:w-[460px] flex-shrink-0">
            <form ref="homeSearchForm" role="search" action="/search" method="get" class="relative" @submit.prevent="submitHomeSearch">
              <label for="home-search" class="sr-only">Search compounds and vendors</label>
              <div class="flex items-center gap-2 h-12 pl-4 pr-1.5 rounded-[12px] bg-white border border-[color:var(--color-hairline)] shadow-[var(--shadow-sm)] transition-shadow focus-within:border-[color:var(--color-accent-400)] focus-within:shadow-[0_0_0_4px_var(--color-accent-100)]">
                <svg class="w-[18px] h-[18px] flex-shrink-0 text-[color:var(--color-ink-subtle)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                  <circle cx="11" cy="11" r="8" />
                  <path d="M21 21l-4.35-4.35" stroke-linecap="round" />
                </svg>
                <!-- 16px text so iOS Safari doesn't zoom the page on focus. -->
                <input
                  id="home-search"
                  v-model="homeQuery"
                  name="q"
                  type="search"
                  autocomplete="off"
                  spellcheck="false"
                  enterkeyhint="search"
                  placeholder="Compound or vendor name"
                  role="combobox"
                  aria-autocomplete="list"
                  aria-controls="home-search-results"
                  :aria-expanded="suggestOpen && results.length > 0 ? 'true' : 'false'"
                  :aria-activedescendant="suggestOpen && activeIndex >= 0 ? `home-suggest-${activeIndex}` : undefined"
                  class="flex-1 min-w-0 h-full bg-transparent outline-none text-[16px] text-[color:var(--color-ink)] placeholder-[color:var(--color-ink-subtle)]"
                  @focus="setSearchFocus(true)"
                  @blur="setSearchFocus(false)"
                  @keydown.down.prevent="openAndMove(1)"
                  @keydown.up.prevent="openAndMove(-1)"
                  @keydown.esc="closeSuggest"
                />
                <button
                  type="submit"
                  class="ui-focus flex-shrink-0 h-9 px-4 rounded-[9px] text-[13px] font-semibold text-white bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] shadow-sm hover:brightness-110 transition-all"
                >
                  Search
                </button>
              </div>

              <!-- Live suggestions. mousedown.prevent keeps focus in the
                   input so the list doesn't close before a click lands. -->
              <div
                v-if="suggestOpen"
                class="absolute left-0 right-0 top-full mt-1.5 z-40 rounded-[12px] bg-white border border-[color:var(--color-hairline)] shadow-[var(--shadow-lg)] overflow-hidden"
                @mousedown.prevent
              >
                <div v-if="results.length" id="home-search-results" role="listbox" aria-label="Suggestions" class="py-1.5 max-h-[60vh] overflow-y-auto">
                  <a
                    v-for="(item, idx) in results"
                    :id="`home-suggest-${idx}`"
                    :key="item.type + item.url"
                    :href="item.url"
                    role="option"
                    tabindex="-1"
                    :aria-selected="idx === activeIndex ? 'true' : 'false'"
                    :class="[
                      'flex items-center gap-3 px-4 py-2.5 transition-colors',
                      idx === activeIndex ? 'bg-[color:var(--color-hairline-soft)]' : 'hover:bg-[color:var(--color-hairline-soft)]',
                    ]"
                    @mouseenter="setActive(idx)"
                  >
                    <span
                      :class="[
                        'flex-shrink-0 w-[68px] text-center text-[10px] uppercase tracking-[0.08em] font-semibold rounded-full py-0.5 border',
                        item.type === 'compound'
                          ? 'text-[color:var(--color-accent-700)] bg-[color:var(--color-accent-50)] border-[color:var(--color-accent-100)]'
                          : 'text-emerald-700 bg-emerald-50 border-emerald-200',
                      ]"
                    >{{ item.type === 'compound' ? 'Compound' : 'Vendor' }}</span>
                    <span class="flex-1 min-w-0">
                      <span class="block truncate text-[14px] font-medium text-[color:var(--color-ink)]">{{ item.label }}</span>
                      <span v-if="item.sublabel" class="block truncate text-[12px] text-[color:var(--color-ink-muted)] ui-mono">{{ item.sublabel }}</span>
                    </span>
                  </a>
                </div>
                <p v-else-if="loading" class="px-4 py-3 text-[13px] text-[color:var(--color-ink-muted)]">Searching…</p>
                <p v-else class="px-4 py-3 text-[13px] text-[color:var(--color-ink-muted)]">No quick matches.</p>
                <button
                  type="button"
                  class="w-full flex items-center gap-2 px-4 py-2.5 border-t border-[color:var(--color-hairline)] text-left text-[13px] text-[color:var(--color-ink-muted)] hover:bg-[color:var(--color-hairline-soft)] transition-colors"
                  @click="searchEverything"
                >
                  <span class="flex-1 min-w-0 truncate">
                    Search everything for <span class="text-[color:var(--color-ink)] font-medium">"{{ homeQuery.trim() }}"</span>
                  </span>
                  <kbd class="hidden sm:inline ui-mono text-[10px] px-1.5 py-0.5 rounded border border-[color:var(--color-hairline)] text-[color:var(--color-ink-muted)]">Enter</kbd>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <div class="relative max-w-[1280px] mx-auto px-5 lg:px-10 pt-4 lg:pt-5">
        <HeroCarousel :slides="heroSlides" />
      </div>

      <!-- Popular compounds: one row under the banner. Scrolls sideways on
           phones instead of wrapping into a ragged second line. -->
      <div v-if="popularCompounds.length" class="relative max-w-[1280px] mx-auto px-5 lg:px-10 pt-4">
        <div class="flex items-center gap-2 overflow-x-auto whitespace-nowrap [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
          <span class="flex-shrink-0 text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)] mr-1">Popular</span>
          <a
            v-for="compound in popularCompounds"
            :key="compound.slug"
            :href="`/product/${compound.slug}`"
            class="ui-focus flex-shrink-0 inline-flex items-center h-8 px-3 rounded-full border border-[color:var(--color-hairline)] bg-white text-[12px] font-medium text-[color:var(--color-ink-muted)] hover:border-[color:var(--color-accent-400)] hover:text-[color:var(--color-accent-700)] transition-colors"
          >{{ compound.name }}</a>
        </div>
      </div>
    </section>

    <!-- ========================================================= -->
    <!-- 2. VENDORS — visually continues from hero (no gap)         -->
    <!-- ========================================================= -->
    <section class="bg-[color:var(--color-bg)] border-b border-[color:var(--color-hairline)]">
      <div class="max-w-[1280px] mx-auto px-5 lg:px-10 py-8 lg:py-12">
        <div class="flex items-center justify-between mb-5">
          <div class="flex items-center gap-3">
            <h2 class="text-[13px] lg:text-[14px] font-semibold text-[color:var(--color-ink)] tracking-tight">Top-rated vendors</h2>
            <span class="text-[11px] text-[color:var(--color-ink-subtle)] ui-mono">{{ formatNumber(stats.total_vendors || verifiedVendors.length) }} listed</span>
          </div>
          <a href="/vendors" class="text-[12px] font-semibold text-[color:var(--color-accent-600)] hover:text-[color:var(--color-accent-700)] transition-colors flex items-center gap-1">
            All vendors
            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
          </a>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 lg:gap-4">
          <!-- One stretched link per tile (the name's ::after covers the
               card), so the coupon ticket can be a real button above it. -->
          <div
            v-for="vendor in verifiedVendors.slice(0, 6)"
            :key="vendor.id"
            class="group relative flex flex-col overflow-hidden rounded-[14px] border border-[color:var(--color-hairline)] bg-white transition-all duration-200 hover:-translate-y-[1px] hover:border-[color:var(--color-accent-400)] hover:shadow-[var(--shadow-md)]"
          >
            <!-- Fixed 16:9 logo box. The image is absolutely centred so a
                 square or oversized file can't stretch the tile (and with it
                 every card in the row). Rounded so an opaque square logo
                 reads as an app icon rather than a pasted block. -->
            <div
              class="relative aspect-[16/9] overflow-hidden border-b border-[color:var(--color-hairline)]"
              :style="{ backgroundColor: logoBackdrop(vendor.logo_url) }"
            >
              <img
                v-if="vendor.logo_url && !brokenLogos[vendor.id]"
                :src="vendor.logo_url"
                :alt="vendor.name"
                class="absolute inset-0 m-auto w-auto h-auto max-w-[72%] max-h-[60%] object-contain rounded-[12px]"
                loading="lazy"
                decoding="async"
                @error="brokenLogos[vendor.id] = true"
              />
              <span v-else class="absolute inset-0 flex items-center justify-center ui-display text-2xl lg:text-4xl font-bold text-[color:var(--color-ink-subtle)]">{{ vendor.name.slice(0,2).toUpperCase() }}</span>
            </div>
            <div class="p-3 lg:p-5 flex-1 flex flex-col gap-2.5 lg:gap-3">
              <div class="flex items-center justify-between gap-2 min-w-0">
                <h3 class="ui-display text-[14px] lg:text-[17px] font-semibold text-[color:var(--color-ink)] tracking-tight truncate min-w-0 group-hover:text-[color:var(--color-accent-700)] transition-colors">
                  <a
                    :href="vendor.url"
                    class="ui-focus outline-none after:absolute after:inset-0 after:rounded-[14px]"
                  >{{ vendor.name }}</a>
                </h3>
                <!-- Rating hidden until the vendor has actually collected
                     reviews on their storefront. Same treatment as /vendors. -->
                <span v-if="(vendor.rating_count || 0) > 0" class="flex-shrink-0 inline-flex items-center gap-1 text-xs">
                  <svg class="w-3.5 h-3.5 text-[color:var(--color-caution)]" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 1l2.8 5.7 6.2.9-4.5 4.4 1.1 6.3L10 15.3 4.4 18.3l1.1-6.3L1 7.6l6.2-.9L10 1z"/></svg>
                  <span class="ui-mono font-semibold text-[color:var(--color-ink)]">{{ (vendor.rating_average || 0).toFixed(1) }}</span>
                </span>
              </div>
              <CouponTicket v-if="vendor.coupon_code" :code="vendor.coupon_code" :percent="vendor.coupon_percent" size="sm" />
              <div class="mt-auto pt-2 lg:pt-3 border-t border-[color:var(--color-hairline-soft)] flex items-center justify-between text-[11px] lg:text-xs text-[color:var(--color-ink-muted)]">
                <span><span class="ui-mono font-semibold text-[color:var(--color-ink)]">{{ vendor.product_count }}</span> listings</span>
                <span class="text-[color:var(--color-accent-600)] font-semibold group-hover:translate-x-0.5 transition-transform duration-[180ms] flex items-center gap-0.5">
                  View <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ========================================================= -->
    <!-- 3. COMPOUND CATEGORIES — "What are you researching?"       -->
    <!-- ========================================================= -->
    <section class="max-w-[1280px] mx-auto px-5 lg:px-10 py-12 lg:py-20">
      <SectionHeader
        eyebrow="Browse compounds"
        title="What are you researching?"
        description="Explore every major peptide category — vendor pricing, lab data, and reference material for each compound."
        accent="accent"
      >
        <template #cta>
          <Button as="a" href="/products" variant="secondary">
            Full catalog
            <template #icon-right>
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14M13 5l7 7-7 7"/>
              </svg>
            </template>
          </Button>
        </template>
      </SectionHeader>

      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 lg:gap-4">
        <a
          v-for="compound in topCompounds"
          :key="compound.slug"
          :href="`/product/${compound.slug}`"
          class="ui-focus group flex flex-col gap-2 lg:gap-3 p-4 lg:p-5 rounded-[14px] border border-[color:var(--color-hairline)] bg-white hover:border-[color:var(--color-accent-400)] hover:shadow-[var(--shadow-md)] hover:-translate-y-[1px] transition-all duration-[200ms]"
        >
          <div class="ui-display text-[14px] lg:text-[16px] font-semibold text-[color:var(--color-ink)] tracking-tight group-hover:text-[color:var(--color-accent-600)] transition-colors leading-tight">
            {{ compound.name }}
          </div>
          <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3 text-[11px] lg:text-[12px] text-[color:var(--color-ink-muted)]">
            <span class="flex items-center gap-1">
              <svg class="w-3 h-3 text-[color:var(--color-ink-subtle)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
              <span class="ui-mono font-semibold text-[color:var(--color-ink)]">{{ compound.vendor_count }}</span> vendors
            </span>
            <span v-if="compound.from_price" class="ui-mono text-[color:var(--color-verified)]">
              lowest listed ${{ compound.from_price }}
            </span>
          </div>
        </a>
      </div>
    </section>

    <!-- ========================================================= -->
    <!-- 4. COMPARE + CALCULATOR + BAC WATER CTAs                   -->
    <!-- ========================================================= -->
    <section class="max-w-[1280px] mx-auto px-5 lg:px-10 pb-12 lg:pb-20">
      <div class="grid md:grid-cols-3 gap-4 lg:gap-5">
        <!-- Compare CTA -->
        <div class="rounded-[16px] border border-[color:var(--color-hairline)] bg-gradient-to-br from-[color:var(--color-accent-50)] to-white p-7 lg:p-10 flex flex-col gap-4">
          <div class="text-[11px] uppercase tracking-[0.12em] font-semibold text-[color:var(--color-accent-600)]">Price comparison</div>
          <h2 class="ui-display text-xl lg:text-2xl font-semibold tracking-tight text-[color:var(--color-ink)]">
            See every vendor's price, side by side
          </h2>
          <p class="text-[13px] text-[color:var(--color-ink-muted)] leading-relaxed">
            {{ formatNumber(stats.listings) }} listings for {{ formatNumber(stats.compounds) }} compounds across {{ formatNumber(stats.total_vendors) }} vendors. Compare every price side by side.
          </p>
          <div class="mt-auto pt-2">
            <Button as="a" href="/compare" variant="primary">
              Compare prices
              <template #icon-right>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
              </template>
            </Button>
          </div>
        </div>
        <!-- Calculator CTA -->
        <div class="rounded-[16px] border border-[color:var(--color-hairline)] bg-gradient-to-br from-emerald-50 to-white p-7 lg:p-10 flex flex-col gap-4">
          <div class="text-[11px] uppercase tracking-[0.12em] font-semibold text-emerald-600">Free tool</div>
          <h2 class="ui-display text-xl lg:text-2xl font-semibold tracking-tight text-[color:var(--color-ink)]">
            Peptide reconstitution calculator
          </h2>
          <p class="text-[13px] text-[color:var(--color-ink-muted)] leading-relaxed">
            Calculate concentration, volume, and doses per vial. Presets for 55+ popular compounds.
          </p>
          <div class="mt-auto pt-2">
            <Button as="a" href="/calculator" variant="secondary">
              Open calculator
              <template #icon-right>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
              </template>
            </Button>
          </div>
        </div>
        <!-- Bacteriostatic water CTA. Added so the per-mL comparison
             page actually surfaces on the homepage — every peptide
             reconstitution needs BAC water, so it's the single
             highest cross-sell item on the site. -->
        <div class="rounded-[16px] border border-[color:var(--color-hairline)] bg-gradient-to-br from-sky-50 to-white p-7 lg:p-10 flex flex-col gap-4">
          <div class="text-[11px] uppercase tracking-[0.12em] font-semibold text-sky-700">Reconstitution supply</div>
          <h2 class="ui-display text-xl lg:text-2xl font-semibold tracking-tight text-[color:var(--color-ink)]">
            Bacteriostatic water price index
          </h2>
          <p class="text-[13px] text-[color:var(--color-ink-muted)] leading-relaxed">
            Every peptide needs it. US vendor listings, ranked by per-mL price across 2 mL, 10 mL and 30 mL vials.
          </p>
          <div class="mt-auto pt-2">
            <Button as="a" href="/bacteriostatic-water" variant="secondary">
              See prices
              <template #icon-right>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
              </template>
            </Button>
          </div>
        </div>
      </div>
    </section>

    <!-- ========================================================= -->
    <!-- 5. ENCYCLOPEDIA — compound knowledge base                  -->
    <!-- ========================================================= -->
    <section v-if="encyclopediaCategories && encyclopediaCategories.length" class="bg-[#0A0B0E]">
      <div class="max-w-[1280px] mx-auto px-5 lg:px-10 py-12 lg:py-20">
        <div class="flex items-start justify-between gap-4 mb-8 flex-wrap">
          <div>
            <div class="text-[11px] uppercase tracking-[0.12em] font-semibold text-[color:var(--color-accent-400)] mb-2">Peptide encyclopedia</div>
            <h2 class="ui-display text-2xl md:text-3xl font-semibold tracking-tight text-white">Research compound profiles</h2>
            <p class="text-[14px] text-white/50 leading-relaxed mt-2 max-w-xl">Detailed reference material for each compound — structure, research context, vendor availability, and pricing data.</p>
          </div>
          <a href="/encyclopedia" class="inline-flex items-center gap-1.5 h-9 px-4 rounded-[9px] border border-white/15 text-[13px] font-semibold text-white/70 hover:text-white hover:border-white/30 transition-colors flex-shrink-0">
            View all
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
          </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <a
            v-for="entry in encyclopediaCategories.slice(0, 6)"
            :key="entry.slug"
            :href="entry.url"
            class="ui-focus group rounded-[14px] border border-white/8 bg-white/[0.04] hover:bg-white/[0.08] hover:border-white/15 p-5 lg:p-6 transition-all duration-200"
          >
            <div class="flex items-start justify-between gap-3 mb-3">
              <h3 class="ui-display text-[16px] font-semibold text-white group-hover:text-[color:var(--color-accent-400)] transition-colors leading-tight">
                {{ entry.name }}
              </h3>
              <svg class="w-4 h-4 text-white/20 group-hover:text-[color:var(--color-accent-400)] group-hover:translate-x-0.5 transition-all flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
            </div>
            <p v-if="entry.description" class="text-[13px] text-white/40 leading-relaxed line-clamp-2 mb-4">{{ entry.description }}</p>
            <div class="flex items-center gap-3 text-[11px]">
              <span class="text-white/70 group-hover:text-[color:var(--color-accent-400)] font-semibold uppercase tracking-[0.08em] transition-colors">Learn More</span>
            </div>
          </a>
        </div>
      </div>
    </section>

    <!-- ========================================================= -->
    <!-- 6. LATEST RESEARCH                                         -->
    <!-- ========================================================= -->
    <section v-if="editorial.length" class="bg-white border-y border-[color:var(--color-hairline)]">
      <div class="max-w-[1280px] mx-auto px-5 lg:px-10 py-12 lg:py-20">
        <SectionHeader
          eyebrow="Research & education"
          title="Latest insights"
          accent="muted"
        >
          <template #cta>
            <Button as="a" href="/news" variant="link">
              All research
              <template #icon-right>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M5 12h14M13 5l7 7-7 7"/>
                </svg>
              </template>
            </Button>
          </template>
        </SectionHeader>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 lg:gap-6">
          <a
            v-if="editorial[0]"
            :href="`/blog/${editorial[0].slug}`"
            class="ui-focus group flex flex-col border border-[color:var(--color-hairline)] bg-white hover:border-[color:var(--color-accent-400)] hover:shadow-[var(--shadow-md)] hover:-translate-y-[1px] transition-all duration-[200ms] overflow-hidden"
          >
            <div class="aspect-[16/9] bg-[#0F172A] overflow-hidden relative">
              <img v-if="editorial[0].image" :src="editorial[0].image" :alt="editorial[0].title" class="w-full h-full object-cover opacity-80 group-hover:opacity-90 group-hover:scale-[1.03] transition-all duration-500" loading="lazy" />
              <div class="absolute inset-0 bg-gradient-to-tr from-[#312E81]/40 via-transparent to-[#4F46E5]/20 pointer-events-none" />
            </div>
            <div class="p-5 lg:p-6 flex-1 flex flex-col gap-2">
              <span class="text-[11px] text-[color:var(--color-ink-subtle)] ui-mono">{{ editorial[0].date }} · {{ editorial[0].read_time }}</span>
              <h3 class="ui-display text-lg lg:text-xl font-semibold tracking-tight text-[color:var(--color-ink)] leading-snug group-hover:text-[color:var(--color-accent-600)] transition-colors">
                {{ editorial[0].title }}
              </h3>
              <p class="text-[14px] text-[color:var(--color-ink-muted)] leading-relaxed line-clamp-3">{{ editorial[0].excerpt }}</p>
            </div>
          </a>

          <div class="flex flex-col gap-5 lg:gap-6">
            <a
              v-for="post in editorial.slice(1, 4)"
              :key="post.id"
              :href="`/blog/${post.slug}`"
              class="ui-focus group flex flex-row border border-[color:var(--color-hairline)] bg-white hover:border-[color:var(--color-accent-400)] hover:shadow-[var(--shadow-md)] hover:-translate-y-[0.5px] transition-all duration-[200ms] overflow-hidden"
            >
              <div class="w-40 lg:w-48 flex-shrink-0 bg-[#0F172A] overflow-hidden relative">
                <img v-if="post.image" :src="post.image" :alt="post.title" class="w-full h-full object-cover opacity-80 group-hover:opacity-90 transition-all duration-500" loading="lazy" />
                <div class="absolute inset-0 bg-gradient-to-tr from-[#312E81]/30 via-transparent to-[#4F46E5]/15 pointer-events-none" />
              </div>
              <div class="p-4 lg:p-5 flex-1 flex flex-col gap-1.5 justify-center">
                <span class="text-[11px] text-[color:var(--color-ink-subtle)] ui-mono">{{ post.date }} · {{ post.read_time }}</span>
                <h3 class="ui-display text-[15px] font-semibold tracking-tight text-[color:var(--color-ink)] leading-snug group-hover:text-[color:var(--color-accent-600)] transition-colors">
                  {{ post.title }}
                </h3>
                <p class="text-[13px] text-[color:var(--color-ink-muted)] leading-relaxed line-clamp-2">{{ post.excerpt }}</p>
              </div>
            </a>
          </div>
        </div>
      </div>
    </section>

    <!-- ========================================================= -->
    <!-- Listed prices — direct-to-affiliate cards. Moved DOWN from    -->
    <!-- above-the-fold (Colin Sep 7: vendors at the top). Each card  -->
    <!-- = /go/{id} outbound (logged + GA4 + UTM'd).                 -->
    <!-- ========================================================= -->
    <section v-if="bestDeals && bestDeals.length" class="bg-gradient-to-b from-amber-50/60 to-transparent border-y border-[color:var(--color-hairline)]">
      <div class="max-w-[1280px] mx-auto px-5 lg:px-10 py-10 lg:py-12">
        <div class="flex items-baseline justify-between mb-5 flex-wrap gap-2">
          <div>
            <div class="text-[10px] uppercase tracking-[0.14em] font-bold text-amber-700 mb-1">🔥 Live prices — updated daily</div>
            <h2 class="ui-display text-2xl lg:text-3xl font-semibold tracking-tight text-[color:var(--color-ink)]">Listed prices</h2>
          </div>
          <a href="/products" class="text-[12px] font-semibold text-[color:var(--color-accent-600)] hover:text-[color:var(--color-accent-700)]">All products →</a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
          <a
            v-for="d in bestDeals"
            :key="d.id"
            :href="d.go_url"
            target="_blank"
            rel="noopener noreferrer nofollow sponsored"
            class="group bg-white border border-[color:var(--color-hairline)] rounded-lg p-3 hover:border-amber-400 hover:shadow-md transition-all relative"
          >
            <span v-if="d.coupon_boost_active" class="absolute top-2 right-2 text-[9px] uppercase tracking-wider font-bold bg-red-600 text-white px-1.5 py-0.5 rounded-full">boost</span>
            <div class="text-[11px] text-[color:var(--color-ink-muted)] truncate">{{ d.brand_name }}</div>
            <div class="text-[13px] font-semibold text-[color:var(--color-ink)] leading-tight line-clamp-2 mt-0.5 min-h-[2.5em]">{{ d.name }}</div>
            <div class="mt-2 flex items-baseline gap-2 flex-wrap">
              <span class="text-lg font-bold text-emerald-700">${{ d.final_price.toFixed(2) }}</span>
              <span class="text-[11px] text-gray-400 line-through">${{ d.retail.toFixed(2) }}</span>
            </div>
            <div class="mt-1 flex items-center gap-1.5 text-[10px] uppercase tracking-wide font-bold">
              <span class="text-red-700 bg-red-50 border border-red-200 px-1.5 py-0.5 rounded">Save ${{ d.savings.toFixed(0) }}</span>
              <span class="text-emerald-700">code <span class="ui-mono">{{ d.coupon_code }}</span></span>
            </div>
            <button type="button" class="mt-2 w-full h-8 rounded bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] text-white text-[11px] font-semibold group-hover:brightness-110 transition-all flex items-center justify-center gap-1">
              Buy at {{ d.brand_name }}
              <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17L17 7M17 7H7M17 7v10"/></svg>
            </button>
          </a>
        </div>
      </div>
    </section>

    <!-- ========================================================= -->
    <!-- 7. VENDOR CTA (the RUO notice lives in the site footer)    -->
    <!-- ========================================================= -->
    <section class="max-w-[1280px] mx-auto px-5 lg:px-10 py-12 lg:py-16">
      <div class="border border-[color:var(--color-hairline)] bg-gradient-to-r from-[color:var(--color-accent-50)] to-white p-8 md:p-12 flex flex-col md:flex-row items-start md:items-center gap-6 md:gap-10">
        <div class="flex-1">
          <div class="text-[11px] uppercase tracking-[0.12em] font-semibold text-[color:var(--color-accent-600)] mb-2">For vendors</div>
          <h2 class="ui-display text-xl md:text-2xl font-semibold tracking-tight text-[color:var(--color-ink)] mb-2">
            List your research peptides on Peptidemap
          </h2>
          <p class="text-[14px] text-[color:var(--color-ink-muted)] leading-relaxed">
            Auto-sync from WooCommerce. Click analytics. Affiliate tracking. Free during beta.
          </p>
        </div>
        <div class="flex items-center gap-4 flex-shrink-0">
          <a
            href="/become-a-vendor"
            @click="trackVendorCtaClick"
            class="ui-focus inline-flex items-center gap-2 h-11 px-6 text-[14px] font-semibold text-white bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] shadow-sm hover:-translate-y-[0.5px] transition-all"
          >
            Get listed
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
          </a>
          <a href="/login" class="text-[13px] text-[color:var(--color-ink-muted)] hover:text-[color:var(--color-ink)] font-medium transition-colors">
            Vendor sign in →
          </a>
        </div>
      </div>
    </section>
  </ModernLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import ModernLayout from '@/Pages/Layouts/ModernLayout.vue'
import Button from '@/components/ui/Button.vue'
import SectionHeader from '@/components/ui/SectionHeader.vue'
import HeroCarousel from '@/components/ui/HeroCarousel.vue'
import CouponTicket from '@/components/ui/CouponTicket.vue'
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue'
import { trackImpression, trackClick } from '@/lib/bannerTracking'
import { useSearchSuggest } from '@/composables/useSearchSuggest'
import { homeSearchInView } from '@/composables/useHomeSearchInView'

// Processed logos carry their backdrop in the filename
// ("...-lp-bg111827.webp"); white-on-dark marks need it behind them.
// Vendor ids whose logo failed to load; their tile shows initials.
const brokenLogos = reactive({})

function logoBackdrop(url) {
  const m = /-lp-bg([0-9a-f]{6})\.webp(?:[?#].*)?$/i.exec(url || '')
  return m ? `#${m[1].toLowerCase()}` : '#ffffff'
}

onMounted(() => {
  trackImpression({ slot: 'homepage_vendor_cta', banner_key: 'become-a-vendor' })
})

// Tell the header when this search box is on screen, so it shows its own
// search only once this one has scrolled away. The top margin covers the
// fixed header plus the notice bar.
const homeSearchForm = ref(null)
let searchObserver = null
onMounted(() => {
  if (typeof IntersectionObserver === 'undefined' || !homeSearchForm.value) {
    homeSearchInView.value = false
    return
  }
  searchObserver = new IntersectionObserver(
    ([entry]) => { homeSearchInView.value = entry.isIntersecting },
    { rootMargin: '-100px 0px 0px 0px' }
  )
  searchObserver.observe(homeSearchForm.value)
})
onUnmounted(() => {
  searchObserver?.disconnect()
  homeSearchInView.value = true
})

function trackVendorCtaClick() {
  trackClick({
    slot: 'homepage_vendor_cta',
    banner_key: 'become-a-vendor',
    destination_url: '/become-a-vendor',
  })
}

const props = defineProps({
  stats: { type: Object, default: () => ({}) },
  heroHeading: { type: String, default: '' },
  heroSlides: { type: Array, default: () => [] },
  verifiedVendors: { type: Array, default: () => [] },
  topCompounds: { type: Array, default: () => [] },
  bestDeals: { type: Array, default: () => [] },
  encyclopediaCategories: { type: Array, default: () => [] },
  editorial: { type: Array, default: () => [] },
  seo: { type: Object, default: () => ({}) },
})

function formatNumber(n) {
  if (n === null || n === undefined) return '—'
  return new Intl.NumberFormat().format(n)
}

// H1 band. The controller builds the heading from live counts and sends
// the same string as seo.h1.
const heading = computed(() =>
  props.heroHeading || props.seo?.h1 || 'Compare research peptide prices across vendors'
)
const statItems = computed(() => [
  { value: props.stats?.listings, label: 'listings' },
  { value: props.stats?.compounds, label: 'compounds' },
  { value: props.stats?.total_vendors, label: 'vendors' },
].filter((s) => Number(s.value) > 0))
const popularCompounds = computed(() =>
  (props.topCompounds || []).filter((c) => c && c.slug).slice(0, 10)
)

// Homepage search box with live suggestions (same endpoint as the header
// palette). Arrow keys pick a suggestion, Enter opens it; with nothing
// picked, Enter runs the full /search.
const homeQuery = ref('')
const searchFocused = ref(false)
const suggestDismissed = ref(false)
const { results, loading, activeIndex, move, current } = useSearchSuggest(homeQuery)
watch(homeQuery, () => { suggestDismissed.value = false })
const suggestOpen = computed(() =>
  searchFocused.value && !suggestDismissed.value && homeQuery.value.trim().length >= 2
)
function setSearchFocus(focused) { searchFocused.value = focused }
function setActive(idx) { activeIndex.value = idx }
function closeSuggest() { suggestDismissed.value = true }
function openAndMove(delta) {
  suggestDismissed.value = false
  move(delta)
}
function searchEverything() {
  const term = homeQuery.value.trim()
  window.location.href = term ? `/search?q=${encodeURIComponent(term)}` : '/products'
}
function submitHomeSearch() {
  const item = suggestOpen.value ? current() : null
  if (item) {
    window.location.href = item.url
    return
  }
  searchEverything()
}
</script>
