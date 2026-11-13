<template>
  <ModernLayout>
    <Head>
      <title>{{ seo.title }}</title>
      <meta name="description" :content="seo.description" />
    </Head>

    <!-- Header on the light brand band (same treatment as the homepage):
         title, intro, live totals and the RUO note, then straight to the
         compound list. -->
    <section class="relative overflow-hidden ui-brand-band-light border-b border-[color:var(--color-hairline)]">
      <BrandSphere class="hidden md:block -right-28 -top-44" :size="520" :opacity="0.06" />
      <div class="relative max-w-[1280px] mx-auto px-5 lg:px-10 pt-7 pb-6 lg:pt-9 lg:pb-7">
        <div class="text-[11px] uppercase tracking-[0.12em] font-semibold text-[color:var(--color-accent-600)] mb-2">Side-by-side</div>
        <h1 class="ui-display text-3xl md:text-[42px] leading-[1.1] font-semibold tracking-[-0.02em] text-[color:var(--color-ink)]">
          Compare Peptide Prices
        </h1>
        <p class="mt-2.5 max-w-2xl text-[15px] md:text-[16px] leading-relaxed text-[color:var(--color-ink-muted)]">
          Compare research peptide listings and prices across vendors. Open a compound for its vendor table.
        </p>
        <ul class="mt-4 flex flex-wrap items-center gap-2 text-[13px]" role="list">
          <li
            v-for="item in statItems"
            :key="item.label"
            class="inline-flex items-center gap-1.5 h-8 px-3 rounded-full bg-white/85 border border-[color:var(--color-hairline)] text-[color:var(--color-ink-muted)]"
          >
            <strong class="ui-mono font-semibold text-[color:var(--color-ink)]">{{ item.value }}</strong> {{ item.label }}
          </li>
          <li
            v-if="totals.prices_updated_human"
            class="inline-flex items-center gap-1.5 h-8 px-3 rounded-full bg-white/85 border border-[color:var(--color-hairline)] text-[color:var(--color-ink-muted)]"
            :title="totals.prices_updated_iso"
          >
            <span class="w-2 h-2 rounded-full bg-emerald-500" aria-hidden="true" />
            Prices checked {{ totals.prices_updated_human }}
          </li>
        </ul>
        <p class="mt-3 text-[12px] text-[color:var(--color-ink-subtle)]">
          All products listed are for <strong class="font-semibold text-[color:var(--color-ink-muted)]">research use only</strong> (RUO). Not for human consumption.
        </p>
      </div>
    </section>

    <!-- Search and sort. Location comes from the header picker. -->
    <section class="max-w-[1280px] mx-auto px-5 lg:px-10 pt-5">
      <LocationFilterChip />
      <div class="flex items-center gap-2">
        <div class="relative flex-1 min-w-0">
          <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-[18px] h-[18px] text-[color:var(--color-ink-subtle)] pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8" /><path d="m21 21-4.35-4.35" /></svg>
          <input
            v-model="query"
            type="search"
            enterkeyhint="search"
            autocomplete="off"
            placeholder="Find a compound: BPC, reta, GLP-1, GHK…"
            aria-label="Find a compound"
            class="w-full h-12 pl-11 pr-10 text-[15px] rounded-[12px] border border-[color:var(--color-hairline)] bg-white shadow-[var(--shadow-xs)] text-[color:var(--color-ink)] placeholder:text-[color:var(--color-ink-subtle)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15 transition-colors [&::-webkit-search-cancel-button]:hidden"
          />
          <button
            v-if="query"
            type="button"
            aria-label="Clear search"
            class="ui-focus absolute right-2 top-1/2 -translate-y-1/2 p-1.5 rounded-md text-[color:var(--color-ink-subtle)] hover:text-[color:var(--color-ink)]"
            @click="query = ''"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12" /></svg>
          </button>
        </div>
        <label for="hub-sort" class="sr-only">Sort compounds</label>
        <select
          id="hub-sort"
          v-model="sortKey"
          class="ui-focus shrink-0 w-[118px] sm:w-[150px] h-12 pl-3 pr-8 text-[14px] font-medium rounded-[12px] border border-[color:var(--color-hairline)] bg-white shadow-[var(--shadow-xs)] text-[color:var(--color-ink)] focus:border-[color:var(--color-accent-500)] focus:outline-none"
        >
          <option value="popular">Popular</option>
          <option value="vendors">Most vendors</option>
          <option value="name">A–Z</option>
        </select>
      </div>
      <div class="mt-3 flex items-center gap-1.5 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
        <span class="shrink-0 mr-1 text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Also</span>
        <a
          v-for="s in SHORTCUTS"
          :key="s.href"
          :href="s.href"
          class="ui-focus shrink-0 inline-flex items-center h-8 px-3 rounded-full border border-[color:var(--color-hairline)] bg-white text-[12px] font-medium text-[color:var(--color-ink-muted)] hover:border-[color:var(--color-accent-400)] hover:text-[color:var(--color-accent-700)] transition-colors"
        >{{ s.label }}</a>
      </div>
    </section>

    <!-- Compound index: one row per compound with a compare page. The
         whole row opens /compare/{slug}; size chips open it on that size. -->
    <section class="max-w-[1280px] mx-auto px-5 lg:px-10 pt-6 pb-14">
      <div class="flex items-baseline justify-between gap-3 mb-2.5">
        <h2 class="text-[15px] font-semibold text-[color:var(--color-ink)]">
          {{ trimmedQuery ? 'Matching compounds' : 'All compounds' }}
          <span class="ml-1 ui-mono text-[13px] font-medium text-[color:var(--color-ink-subtle)]">{{ trimmedQuery ? `${results.length} of ${compounds.length}` : compounds.length }}</span>
        </h2>
        <span class="hidden sm:inline text-[12px] text-[color:var(--color-ink-subtle)]">Lowest listed prices, in USD</span>
      </div>

      <div class="rounded-[14px] border border-[color:var(--color-hairline)] bg-white shadow-[var(--shadow-xs)] overflow-hidden">
        <div
          v-if="results.length"
          :class="['hidden lg:grid gap-6 px-5 py-2.5 border-b border-[color:var(--color-hairline)] bg-[color:var(--color-bg)] text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]', GRID]"
        >
          <div>Compound</div>
          <div>From</div>
          <div>Best per mg</div>
          <div>Popular sizes</div>
          <div />
        </div>

        <ul v-if="results.length" class="divide-y divide-[color:var(--color-hairline-soft)]" role="list">
          <li
            v-for="(c, i) in results"
            v-show="showAll || trimmedQuery || i < INITIAL_ROWS"
            :id="c.anchor"
            :key="c.id"
            class="group relative scroll-mt-28 hover:bg-[color:var(--color-bg)] transition-colors"
          >
            <div :class="['grid grid-cols-2 gap-x-2.5 gap-y-3 px-4 sm:px-5 py-4 lg:gap-x-6 lg:items-center', GRID]">
              <!-- Compound -->
              <div class="col-span-2 lg:col-span-1 min-w-0 pr-7 lg:pr-0">
                <h3 class="text-[16px] font-semibold leading-snug text-[color:var(--color-ink)]">
                  <a
                    :href="compareHref(c)"
                    class="ui-focus rounded-sm after:absolute after:inset-0 group-hover:text-[color:var(--color-accent-700)] transition-colors"
                  >{{ c.name }}</a>
                </h3>
                <div class="mt-0.5 text-[12.5px] text-[color:var(--color-ink-muted)]">
                  <span class="ui-mono font-semibold text-[color:var(--color-ink)]">{{ c.vendor_count }}</span> {{ c.vendor_count === 1 ? 'vendor' : 'vendors' }}
                  <span class="text-[color:var(--color-ink-subtle)]"> · </span>
                  <span class="ui-mono">{{ c.listing_count }}</span> {{ c.listing_count === 1 ? 'listing' : 'listings' }}
                  <template v-if="c.encyclopedia_url">
                    <span class="text-[color:var(--color-ink-subtle)]"> · </span>
                    <a :href="c.encyclopedia_url" class="ui-focus relative z-10 rounded-sm font-medium text-[color:var(--color-accent-600)] hover:text-[color:var(--color-accent-700)]">Guide</a>
                  </template>
                </div>
                <p v-if="c.description" class="hidden md:block mt-1 text-[12.5px] leading-snug text-[color:var(--color-ink-subtle)] line-clamp-1">{{ c.description }}</p>
              </div>

              <svg class="lg:hidden absolute right-4 top-[19px] w-4 h-4 text-[color:var(--color-ink-subtle)] group-hover:text-[color:var(--color-accent-600)] transition-colors" fill="none" stroke="currentColor" stroke-width="2.25" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>

              <!-- From -->
              <div class="min-w-0 rounded-[10px] bg-[color:var(--color-bg)] px-3 py-2.5 lg:bg-transparent lg:p-0">
                <div class="lg:hidden text-[10.5px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">From</div>
                <template v-if="c.from">
                  <div class="mt-0.5 lg:mt-0 ui-mono text-[16px] font-semibold leading-tight text-[color:var(--color-ink)] truncate">{{ fromMain(c.from) }}</div>
                  <div class="mt-0.5 truncate text-[12px] text-[color:var(--color-ink-muted)]">{{ fromSub(c.from) }}</div>
                </template>
                <div v-else class="mt-0.5 lg:mt-0 text-[14px] text-[color:var(--color-ink-subtle)]">—</div>
              </div>

              <!-- Best per mg -->
              <div class="min-w-0 rounded-[10px] bg-emerald-50/70 px-3 py-2.5 lg:bg-transparent lg:p-0">
                <div class="lg:hidden text-[10.5px] uppercase tracking-[0.08em] font-semibold text-emerald-700">Best per mg</div>
                <template v-if="c.best_per_mg">
                  <div class="mt-0.5 lg:mt-0 ui-mono text-[16px] font-semibold leading-tight text-emerald-700 truncate">{{ perMg(c.best_per_mg.usd_per_mg) }}<span class="text-[12px] font-medium">/mg</span></div>
                  <div class="mt-0.5 truncate text-[12px] text-[color:var(--color-ink-muted)]">{{ c.best_per_mg.size }} · {{ c.best_per_mg.vendor }}</div>
                </template>
                <div v-else class="mt-0.5 lg:mt-0 text-[14px] text-[color:var(--color-ink-subtle)]">—</div>
              </div>

              <!-- Popular sizes -->
              <div :class="['col-span-2 lg:col-span-1 min-w-0', c.sizes?.length ? '' : 'hidden lg:block']">
                <div v-if="c.sizes?.length" class="flex flex-wrap items-center gap-1.5">
                  <span class="lg:hidden mr-0.5 text-[10.5px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Sizes</span>
                  <a
                    v-for="s in c.sizes"
                    :key="s.param"
                    :href="sizeHref(c, s)"
                    :title="`${s.listings} ${s.listings === 1 ? 'listing' : 'listings'} at ${s.label}, from ${money(s.from_usd)}`"
                    class="ui-focus relative z-10 inline-flex items-center gap-1.5 h-7 px-2.5 rounded-full border border-[color:var(--color-hairline)] bg-white text-[12px] text-[color:var(--color-ink-muted)] hover:border-[color:var(--color-accent-400)] hover:text-[color:var(--color-accent-700)] transition-colors"
                  >
                    <span class="font-semibold text-[color:var(--color-ink)]">{{ s.label }}</span>
                    <span class="ui-mono">{{ money(s.from_usd) }}</span>
                  </a>
                </div>
                <span v-else class="text-[14px] text-[color:var(--color-ink-subtle)]">—</span>
              </div>

              <!-- Desktop call to action (the row itself is the link) -->
              <div class="hidden lg:flex justify-end" aria-hidden="true">
                <span class="inline-flex items-center gap-1 h-9 px-3.5 rounded-[10px] border border-[color:var(--color-hairline)] bg-white text-[13px] font-semibold text-[color:var(--color-ink)] group-hover:border-[#0369A1] group-hover:bg-[#0284C7] group-hover:text-white transition-colors">
                  Compare
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7" /></svg>
                </span>
              </div>
            </div>
          </li>
        </ul>

        <button
          v-if="results.length > INITIAL_ROWS && !showAll && !trimmedQuery"
          type="button"
          class="ui-focus w-full h-12 flex items-center justify-center gap-1.5 border-t border-[color:var(--color-hairline)] text-[14px] font-semibold text-[color:var(--color-accent-600)] hover:text-[color:var(--color-accent-700)] hover:bg-[color:var(--color-bg)] transition-colors"
          @click="showAll = true"
        >
          Show all {{ results.length }} compounds
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6" /></svg>
        </button>

        <!-- No match: offer the site search, which also covers vendor
             product names. -->
        <div v-if="!results.length" class="px-6 py-12 text-center">
          <div class="text-[15px] font-semibold text-[color:var(--color-ink)]">
            <template v-if="trimmedQuery">No compound matches “{{ trimmedQuery }}”</template>
            <template v-else>No compounds have listings here yet</template>
          </div>
          <p class="mt-1 text-[13px] text-[color:var(--color-ink-muted)]">
            {{ trimmedQuery ? 'Try a shorter name, or search every listing.' : 'Try another location in the header.' }}
          </p>
          <div v-if="trimmedQuery" class="mt-4 flex flex-wrap justify-center gap-2">
            <a
              :href="`/search?q=${encodeURIComponent(trimmedQuery)}`"
              class="ui-focus inline-flex items-center h-10 px-4 rounded-[10px] text-[13px] font-semibold text-white bg-gradient-to-b from-[#0284C7] to-[#0369A1] shadow-sm"
            >Search all listings</a>
            <button
              type="button"
              class="ui-focus inline-flex items-center h-10 px-4 rounded-[10px] border border-[color:var(--color-hairline)] bg-white text-[13px] font-semibold text-[color:var(--color-ink)] hover:border-[color:var(--color-accent-400)]"
              @click="query = ''"
            >Clear search</button>
          </div>
        </div>
      </div>
    </section>

    <!-- Head-to-head pages. A search narrows these too. -->
    <section v-if="visiblePairs.length" class="border-t border-[color:var(--color-hairline)] bg-[color:var(--color-bg)]">
      <div class="max-w-[1280px] mx-auto px-5 lg:px-10 py-10 lg:py-12">
        <h2 class="ui-display text-xl md:text-2xl font-semibold tracking-tight text-[color:var(--color-ink)]">Head-to-head comparisons</h2>
        <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
          <a
            v-for="p in visiblePairs"
            :key="p.url"
            :href="p.url"
            class="ui-focus group flex items-center gap-3 p-4 rounded-[12px] border border-[color:var(--color-hairline)] bg-white hover:border-[color:var(--color-accent-400)] hover:shadow-[var(--shadow-md)] transition-all"
          >
            <div class="min-w-0 flex-1">
              <div class="text-[15px] font-semibold leading-tight">
                <span class="text-[color:var(--color-accent-700)]">{{ p.a_name }}</span>{{ ' ' }}<span class="text-[12px] font-medium text-[color:var(--color-ink-subtle)]">vs</span>{{ ' ' }}<span class="text-emerald-700">{{ p.b_name }}</span>
              </div>
              <div class="mt-1 truncate text-[12px] text-[color:var(--color-ink-muted)]">{{ p.tagline }}</div>
            </div>
            <svg class="shrink-0 w-4 h-4 text-[color:var(--color-ink-subtle)] group-hover:text-[color:var(--color-accent-600)] group-hover:translate-x-0.5 transition-all" fill="none" stroke="currentColor" stroke-width="2.25" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 5l7 7-7 7" /></svg>
          </a>
        </div>
      </div>
    </section>
  </ModernLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Head } from '@inertiajs/vue3'
import ModernLayout from '@/Pages/Layouts/ModernLayout.vue'
import BrandSphere from '@/components/ui/BrandSphere.vue'
import LocationFilterChip from '@/components/ui/LocationFilterChip.vue'
import { formatMoney, formatPerMg } from '@/components/price/priceFormat'

const props = defineProps({
  compounds: { type: Array, default: () => [] },
  totals: { type: Object, default: () => ({}) },
  featuredPairs: { type: Array, default: () => [] },
  seo: { type: Object, default: () => ({}) },
})

// Shared column template for the desktop header and rows.
const GRID = 'lg:grid-cols-[minmax(0,2.4fr)_minmax(0,1fr)_minmax(0,1fr)_minmax(0,1.7fr)_104px]'

// Rows past this stay in the page (crawlable) but hidden until
// "Show all"; a search always shows every match.
const INITIAL_ROWS = 30

const SHORTCUTS = [
  { label: 'Bacteriostatic water', href: '/bacteriostatic-water' },
  { label: 'Blends', href: '/blends' },
  { label: 'Bulk', href: '/bulk' },
  { label: 'Skincare', href: '/skincare' },
]

// Shortnames people type that don't share letters with the compound
// name, keyed by compare slug. Lowercase.
const ALIASES = {
  'retatrutide': ['reta', 'rta', 'glp3', 'glp-3', 'glp-3r', 'glp3r', 'triple agonist'],
  'semaglutide': ['sema', 'glp1', 'glp-1', 'glp1-s', 'ozempic', 'wegovy'],
  'tirzepatide': ['tirz', 'tirze', 'tzp', 'glp2', 'glp-2', 'glp2-t', 'mounjaro', 'zepbound'],
  'cagrilintide': ['cagri', 'cag', 'amylin'],
  'bpc-157': ['bpc', 'body protection compound'],
  'tb-500': ['tb500', 'tb', 'thymosin beta', 'tb4'],
  'bpc-157-tb-500': ['wolverine', 'bpc tb', 'healing blend'],
  'ghk-cu': ['ghk', 'copper peptide', 'copper'],
  'cjc-1295': ['cjc', 'mod grf', 'modified grf'],
  'cjc-1295-ipamorelin': ['cjc ipa', 'cjc/ipa'],
  'ipamorelin': ['ipa', 'ipam'],
  'sermorelin': ['serm'],
  'tesamorelin': ['tesa', 'egrifta'],
  'mots-c': ['mots', 'motsc'],
  'bremelanotide': ['pt-141', 'pt141', 'pt 141'],
  'kisspeptin': ['kiss', 'kp-10', 'kp10'],
  '5-amino-1mq': ['5amino', 'aminomq', '1mq'],
  'aod-9604': ['aod', 'aod9604'],
  'nad': ['nad+', 'nicotinamide'],
  'glutathione': ['gsh'],
  'igf-1-lr3': ['igf1', 'igf-1', 'igf', 'lr3'],
  'elamipretide': ['ss-31', 'ss31'],
  'tesofensine': ['tesof'],
  'glow': ['glow stack', 'glow blend'],
  'klow-blend-ghk-cu-bpc-157-tb-500-kpv': ['klow', 'klow stack'],
  'thymosin-alpha-1': ['ta1', 'ta-1', 'thymalfasin'],
  'ibutamoren': ['mk-677', 'mk677', 'mk 677'],
  'melanotan-ii': ['mt2', 'mt-2', 'mt ii', 'melanotan 2'],
  'melanotan-i': ['mt1', 'mt-1', 'melanotan 1'],
  'hgh-191aa': ['hgh', 'somatropin', 'growth hormone'],
  'epitalon': ['epithalon', 'epitalone'],
  'dsip': ['delta sleep'],
  'slu-pp-332': ['slu', 'slupp'],
  'semax': ['semaks'],
  'methylene-blue': ['mb', 'methylene'],
  'vitamin-b12': ['b12', 'cyanocobalamin', 'methylcobalamin'],
  'mic-b12': ['mic', 'lipo b12'],
  'bacteriostatic-water': ['bac water', 'bac', 'bacteriostatic'],
  'orforglipron': ['orfo', 'orforg'],
}

const initialParams = typeof window !== 'undefined' ? new URLSearchParams(window.location.search) : new URLSearchParams()
const query = ref(initialParams.get('q') || '')
const sortKey = ref(['vendors', 'name'].includes(initialParams.get('sort')) ? initialParams.get('sort') : 'popular')
const showAll = ref(false)
const trimmedQuery = computed(() => query.value.trim())

// Keep q and sort in the URL so a filtered view can be shared. Other
// params (the header's ?location=) stay as they are.
if (typeof window !== 'undefined') {
  watch([trimmedQuery, sortKey], ([q, sort]) => {
    const url = new URL(window.location.href)
    if (q) url.searchParams.set('q', q)
    else url.searchParams.delete('q')
    if (sort === 'popular') url.searchParams.delete('sort')
    else url.searchParams.set('sort', sort)
    window.history.replaceState(window.history.state, '', url)
  })
}

function normalize(value) {
  return String(value || '').toLowerCase()
}
function compact(value) {
  return normalize(value).replace(/[^a-z0-9]+/g, '')
}
function escapeRegExp(value) {
  return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
}

const indexed = computed(() => props.compounds.map((c, order) => {
  const primary = normalize([c.name, c.slug, String(c.slug || '').replace(/-/g, ' '), ...(ALIASES[c.slug] || [])].join(' '))
  return { c, order, primary, primaryCompact: compact(primary), description: normalize(c.description) }
}))

// 2 = the name, slug or a shortname contains the token ("ghk cu" and
// "bpc157" both work); 1 = a word in the description starts with it.
function tokenScore(entry, token) {
  if (entry.primary.includes(token)) return 2
  const t = compact(token)
  if (t && entry.primaryCompact.includes(t)) return 2
  if (token.length >= 3 && new RegExp('\\b' + escapeRegExp(token)).test(entry.description)) return 1
  return 0
}

function bySort(a, b) {
  if (sortKey.value === 'vendors') {
    return (b.c.vendor_count - a.c.vendor_count) || (b.c.listing_count - a.c.listing_count) || (a.order - b.order)
  }
  if (sortKey.value === 'name') {
    return String(a.c.name).localeCompare(String(b.c.name), 'en', { sensitivity: 'base', numeric: true })
  }
  return a.order - b.order
}

const results = computed(() => {
  const tokens = normalize(trimmedQuery.value).split(/\s+/).filter(Boolean)
  let list = indexed.value
  if (tokens.length) {
    list = list
      .map((entry) => {
        let score = 2
        for (const token of tokens) {
          const hit = tokenScore(entry, token)
          if (!hit) return null
          score = Math.min(score, hit)
        }
        return { ...entry, score }
      })
      .filter(Boolean)
  }
  const sorted = [...list].sort(bySort)
  // Name matches before description matches; the sort holds within each.
  if (tokens.length) sorted.sort((a, b) => b.score - a.score)
  return sorted.map((entry) => entry.c)
})

const visiblePairs = computed(() => {
  const tokens = normalize(trimmedQuery.value).split(/\s+/).filter(Boolean)
  if (!tokens.length) return props.featuredPairs
  return props.featuredPairs.filter((p) => {
    const hay = normalize([
      p.a_name, p.b_name, p.a_slug, p.b_slug,
      ...(ALIASES[p.a_slug] || []), ...(ALIASES[p.b_slug] || []),
    ].join(' '))
    const hayCompact = compact(hay)
    return tokens.every((t) => hay.includes(t) || (compact(t) && hayCompact.includes(compact(t))))
  })
})

const statItems = computed(() => {
  const t = props.totals || {}
  return [
    { value: t.compounds, one: 'compound', many: 'compounds' },
    { value: t.vendors, one: 'vendor', many: 'vendors' },
    { value: t.listings, one: 'listing', many: 'listings' },
  ]
    .filter((item) => Number(item.value) > 0)
    .map((item) => ({
      value: Number(item.value).toLocaleString('en-US'),
      label: Number(item.value) === 1 ? item.one : item.many,
    }))
})

function compareHref(c) {
  return `/compare/${c.slug}`
}
function sizeHref(c, size) {
  return `/compare/${c.slug}?size=${encodeURIComponent(size.param)}`
}

function money(value) {
  return formatMoney(value, '$') || '—'
}
function perMg(value) {
  return formatPerMg(value, '$') || '—'
}

// "10mg" and "10 mg" read the same; blends ("5mg/5mg") stay as listed.
function sizeText(size) {
  const s = String(size || '').trim()
  const m = s.match(/^(\d+(?:\.\d+)?)\s*(mg|mcg|g|ml|iu)$/i)
  return m ? `${m[1]} ${m[2].toLowerCase()}` : s
}

function isForeign(from) {
  return !!from?.currency && from.currency !== 'USD'
}
// Dollars first; a listing priced in another currency shows its USD
// equivalent with the vendor's own price underneath.
function fromMain(from) {
  if (isForeign(from)) return '≈ ' + money(from.usd)
  return formatMoney(from.amount, from.symbol || '$') || '—'
}
function fromSub(from) {
  const parts = []
  if (isForeign(from)) parts.push(formatMoney(from.amount, from.symbol))
  if (from.size) parts.push(sizeText(from.size))
  if (from.vendor) parts.push(from.vendor)
  return parts.filter(Boolean).join(' · ')
}
</script>
