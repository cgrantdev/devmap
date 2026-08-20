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
        <li>/</li>
        <li class="text-[color:var(--color-ink-muted)] font-medium">Bacteriostatic Water</li>
      </ol>
    </nav>

    <!-- Hero -->
    <section class="border-b border-[color:var(--color-hairline)]">
      <div class="max-w-[1280px] mx-auto px-6 lg:px-10 pt-6 pb-10">
        <div class="text-[11px] uppercase tracking-[0.12em] font-semibold text-[color:var(--color-biotech-600)] mb-3">
          Cheapest per-mL · updated daily
        </div>
        <h1 class="ui-display text-4xl md:text-5xl font-semibold tracking-[-0.02em] text-[color:var(--color-ink)] mb-3">
          Cheapest Bacteriostatic Water
        </h1>
        <p class="text-[color:var(--color-ink-muted)] leading-relaxed max-w-3xl text-lg">
          Every US vendor stocking BAC water, sorted by <strong class="text-[color:var(--color-ink)]">per-mL price</strong> so a 30 mL vial can beat a smaller cheaper one on the metric that matters. Coupon codes applied automatically.
        </p>
      </div>
    </section>

    <!-- Snapshot stats -->
    <section class="border-b border-[color:var(--color-hairline)] bg-[color:var(--color-bg)]">
      <div class="max-w-[1280px] mx-auto px-6 lg:px-10 py-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
          <StatCard label="Vendors" :value="stats.vendor_count" />
          <StatCard label="Products" :value="stats.product_count" />
          <StatCard label="Cheapest" :value="stats.cheapest_price ? (stats.cheapest_currency_symbol || '$') + fmt(stats.cheapest_price) : '—'" accent="emerald" />
          <StatCard
            v-if="stats.best_per_ml"
            label="Best per-mL"
            :value="(stats.best_per_ml.currency_symbol || '$') + stats.best_per_ml.per_ml_price.toFixed(2) + '/mL'"
            :sub="`${stats.best_per_ml.brand_name} · ${stats.best_per_ml.volume_ml} mL`"
            accent="emerald"
          />
          <StatCard v-else label="Best per-mL" value="—" />
        </div>
      </div>
    </section>

    <!-- Size filter tabs -->
    <section v-if="stats.available_sizes.length > 1" class="max-w-[1280px] mx-auto px-6 lg:px-10 pt-6">
      <div class="flex flex-wrap items-center gap-2 text-[13px]">
        <span class="text-[11px] uppercase tracking-wide font-semibold text-[color:var(--color-ink-subtle)] mr-2">Filter by size:</span>
        <button
          @click="selectedSize = null"
          :class="tabClass(selectedSize === null)"
        >All sizes</button>
        <button
          v-for="size in stats.available_sizes"
          :key="size"
          @click="selectedSize = size"
          :class="tabClass(selectedSize === size)"
        >{{ size }} mL</button>
      </div>
    </section>

    <!-- Price table -->
    <section class="max-w-[1280px] mx-auto px-6 lg:px-10 py-8">
      <div v-if="filteredRows.length" class="bg-white rounded-[14px] border border-[color:var(--color-hairline)] overflow-hidden shadow-[var(--shadow-xs)]">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-[color:var(--color-hairline)] bg-[color:var(--color-bg)]">
                <th class="text-left px-5 py-3 text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Vendor</th>
                <th class="text-left px-5 py-3 text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Product</th>
                <th class="text-right px-5 py-3 text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Size</th>
                <th class="text-right px-5 py-3 text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Total</th>
                <th class="text-right px-5 py-3 text-[11px] uppercase tracking-[0.08em] font-semibold text-emerald-700 bg-emerald-50/50">Per mL</th>
                <th class="text-right px-5 py-3 w-[100px]"></th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(r, idx) in filteredRows"
                :key="r.id"
                :class="['border-b border-[color:var(--color-hairline-soft)] hover:bg-[color:var(--color-hairline-soft)] transition-colors', idx === 0 && !selectedSize ? 'bg-[color:var(--color-verified-bg)]' : '']"
              >
                <td class="px-5 py-4">
                  <div class="flex items-center gap-2.5">
                    <div class="flex-shrink-0 w-7 h-7 rounded-[6px] overflow-hidden bg-[color:var(--color-hairline-soft)] border border-[color:var(--color-hairline)]">
                      <img v-if="r.brand_logo" :src="r.brand_logo" :alt="r.brand_name" class="w-full h-full object-cover" loading="lazy" />
                    </div>
                    <div>
                      <a :href="`/brand/${r.brand_slug}`" class="font-semibold text-[color:var(--color-ink)] hover:text-[color:var(--color-accent-600)] transition-colors">
                        {{ r.brand_name }}
                      </a>
                      <div v-if="idx === 0 && !selectedSize" class="text-[10px] font-bold uppercase tracking-[0.1em] text-[color:var(--color-verified)] mt-0.5">Cheapest overall</div>
                    </div>
                  </div>
                </td>
                <td class="px-5 py-4">
                  <a :href="r.product_url" :title="r.raw_name" class="text-[color:var(--color-ink)] hover:text-[color:var(--color-accent-600)] transition-colors">{{ r.name }}</a>
                </td>
                <td class="px-5 py-4 text-right ui-mono text-[13px] text-[color:var(--color-ink)]">
                  {{ r.volume_ml ? r.volume_ml + ' mL' : '—' }}
                </td>
                <td class="px-5 py-4 text-right ui-mono">
                  <template v-if="r.pmap_price">
                    <div class="text-emerald-700 font-semibold">{{ r.currency_symbol || '$' }}{{ fmt(r.pmap_price) }}</div>
                    <div class="text-[9px] uppercase tracking-wide text-emerald-600">code {{ r.brand_coupon_code || 'PMAP' }}</div>
                  </template>
                  <span v-else class="text-[color:var(--color-ink)]">{{ r.currency_symbol || '$' }}{{ fmt(r.retail_price) }}</span>
                </td>
                <td class="px-5 py-4 text-right ui-mono bg-emerald-50/30">
                  <span v-if="r.per_ml_price" class="font-semibold text-emerald-700">{{ r.currency_symbol || '$' }}{{ r.per_ml_price.toFixed(2) }}</span>
                  <span v-else class="text-[color:var(--color-ink-subtle)] text-[12px]">—</span>
                </td>
                <td class="px-5 py-4 text-right">
                  <a :href="withSrc(r.go_url)" @click="openBuy($event, r)" target="_blank" rel="noopener noreferrer nofollow sponsored" class="ui-focus inline-flex items-center gap-1 h-9 px-3 rounded-md text-[12px] font-semibold text-white bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] hover:-translate-y-[1px] transition-all">
                    Buy
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17L17 7M17 7H7M17 7v10"/></svg>
                  </a>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div v-else class="bg-white rounded-[14px] border border-dashed border-[color:var(--color-hairline)] p-10 text-center text-[color:var(--color-ink-subtle)]">
        No {{ selectedSize }} mL listings right now.
        <button @click="selectedSize = null" class="text-[color:var(--color-accent-600)] hover:text-[color:var(--color-accent-700)] font-medium ml-1">Show all sizes →</button>
      </div>
    </section>

    <!-- Educational content -->
    <section class="border-t border-[color:var(--color-hairline)] bg-white">
      <div class="max-w-[1024px] mx-auto px-6 lg:px-10 py-12">
        <div class="text-[11px] uppercase tracking-[0.12em] font-semibold text-[color:var(--color-ink-subtle)] mb-3">What & why</div>
        <h2 class="ui-display text-2xl md:text-3xl font-semibold text-[color:var(--color-ink)] mb-4">Bacteriostatic water, in 60 seconds</h2>
        <div class="prose prose-slate max-w-none text-[color:var(--color-ink-muted)] leading-relaxed">
          <p>
            <strong class="text-[color:var(--color-ink)]">Bacteriostatic water</strong> is sterile water with 0.9% benzyl alcohol added as a preservative. That preservative is what makes it different from plain sterile water — once a BAC vial is punctured, the benzyl alcohol prevents bacterial growth, so researchers can safely draw from the same vial multiple times over a ~28-day window.
          </p>
          <p>
            Practically, every research peptide arrives lyophilized (dry powder) and needs a solvent to reconstitute. BAC water is the standard choice because a single 10 mL vial can serve multiple peptides across a whole research cycle without needing a fresh vial every draw.
          </p>
          <p class="text-[13px] italic text-[color:var(--color-ink-subtle)] border-l-2 border-[color:var(--color-hairline)] pl-4">
            All products listed are for research use only (RUO). Peptidemap does not provide dosing, medical, or veterinary guidance.
          </p>
        </div>
      </div>
    </section>

    <!-- FAQs (matches FAQPage JSON-LD in the head) -->
    <section class="border-t border-[color:var(--color-hairline)] bg-[color:var(--color-bg)]">
      <div class="max-w-[1024px] mx-auto px-6 lg:px-10 py-12">
        <div class="text-[11px] uppercase tracking-[0.12em] font-semibold text-[color:var(--color-ink-subtle)] mb-3">Frequently asked</div>
        <h2 class="ui-display text-2xl md:text-3xl font-semibold text-[color:var(--color-ink)] mb-6">Bacteriostatic water FAQ</h2>
        <div class="space-y-3">
          <details v-for="(f, i) in faqs" :key="i" class="group bg-white rounded-[10px] border border-[color:var(--color-hairline)] overflow-hidden">
            <summary class="ui-focus cursor-pointer list-none p-4 flex items-center justify-between gap-3 hover:bg-[color:var(--color-hairline-soft)] transition-colors">
              <span class="text-[15px] font-semibold text-[color:var(--color-ink)]">{{ f.q }}</span>
              <svg class="w-4 h-4 text-[color:var(--color-ink-subtle)] flex-shrink-0 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 9l6 6 6-6"/>
              </svg>
            </summary>
            <div class="px-4 pb-4 text-[14px] text-[color:var(--color-ink-muted)] leading-relaxed">{{ f.a }}</div>
          </details>
        </div>
      </div>
    </section>

    <!-- Cross-links -->
    <section class="border-t border-[color:var(--color-hairline)]">
      <div class="max-w-[1280px] mx-auto px-6 lg:px-10 py-10">
        <div class="text-[11px] uppercase tracking-[0.12em] font-semibold text-[color:var(--color-ink-subtle)] mb-4">Related</div>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
          <a href="/calculator" class="p-4 rounded-[10px] border border-[color:var(--color-hairline)] bg-white hover:border-[color:var(--color-accent-400)] hover:shadow-[var(--shadow-md)] transition-all">
            <div class="text-[14px] font-semibold text-[color:var(--color-ink)]">Peptide reconstitution calculator</div>
            <div class="text-[11px] text-[color:var(--color-ink-muted)] mt-1">How much BAC water per peptide vial</div>
          </a>
          <a href="/compare" class="p-4 rounded-[10px] border border-[color:var(--color-hairline)] bg-white hover:border-[color:var(--color-accent-400)] hover:shadow-[var(--shadow-md)] transition-all">
            <div class="text-[14px] font-semibold text-[color:var(--color-ink)]">Compare all peptides</div>
            <div class="text-[11px] text-[color:var(--color-ink-muted)] mt-1">40+ vendors, cheapest first</div>
          </a>
          <a href="/vendors" class="p-4 rounded-[10px] border border-[color:var(--color-hairline)] bg-white hover:border-[color:var(--color-accent-400)] hover:shadow-[var(--shadow-md)] transition-all">
            <div class="text-[14px] font-semibold text-[color:var(--color-ink)]">All vendors</div>
            <div class="text-[11px] text-[color:var(--color-ink-muted)] mt-1">Prices, reviews, COAs, coupon codes</div>
          </a>
        </div>
      </div>
    </section>

    <BuyThroughModal ref="buyModal" />
  </ModernLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import { ref, computed, defineComponent, h } from 'vue'
import ModernLayout from '../Layouts/ModernLayout.vue'
import BuyThroughModal from '@/components/BuyThroughModal.vue'
import { withSrc } from '@/composables/useOutbound'

const props = defineProps({
  rows: { type: Array, default: () => [] },
  stats: { type: Object, default: () => ({ vendor_count: 0, product_count: 0, cheapest_price: null, priciest_price: null, best_per_ml: null, available_sizes: [] }) },
  faqs: { type: Array, default: () => [] },
  seo: { type: Object, default: () => ({}) },
})

const selectedSize = ref(null)

const buyModal = ref(null)
function openBuy(ev, r) {
  if (ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.button === 1) return
  const pct = parseFloat(r?.brand_discount_percent)
  if (!pct || pct <= 0 || pct >= 100) return
  const code = ((r?.brand_coupon_code || '').trim() || 'PMAP').toUpperCase()
  ev.preventDefault()
  buyModal.value?.open({
    destination: withSrc(r.go_url),
    code,
    brandName: r.brand_name,
    discountPct: pct,
  })
}

const filteredRows = computed(() => {
  if (selectedSize.value == null) return props.rows
  return props.rows.filter(r => r.volume_ml === selectedSize.value)
})

function fmt(v) {
  if (v == null) return '—'
  return Number(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function tabClass(active) {
  return [
    'ui-mono h-8 px-3 rounded-[8px] text-[12px] font-semibold transition-all',
    active
      ? 'bg-[color:var(--color-ink)] text-white shadow-sm'
      : 'bg-white border border-[color:var(--color-hairline)] text-[color:var(--color-ink-muted)] hover:border-[color:var(--color-accent-400)] hover:text-[color:var(--color-ink)]',
  ]
}

const StatCard = defineComponent({
  props: { label: String, value: [String, Number], sub: String, accent: { type: String, default: '' } },
  setup(p) {
    return () => h('div', { class: 'bg-white border border-[color:var(--color-hairline)] rounded-[10px] p-4' }, [
      h('div', { class: 'text-[10px] uppercase tracking-[0.1em] font-semibold text-[color:var(--color-ink-subtle)] mb-1' }, p.label),
      h('div', { class: `ui-mono text-2xl font-bold leading-none ${p.accent === 'emerald' ? 'text-emerald-700' : 'text-[color:var(--color-ink)]'}` }, String(p.value)),
      p.sub ? h('div', { class: 'text-[11px] text-[color:var(--color-ink-muted)] mt-1 truncate' }, p.sub) : null,
    ])
  },
})
</script>
