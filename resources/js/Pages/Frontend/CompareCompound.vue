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

    <!-- Header -->
    <section class="border-b border-[color:var(--color-hairline)]">
      <div class="max-w-[1280px] mx-auto px-6 lg:px-10 pt-6 pb-10">
        <div class="text-[11px] uppercase tracking-[0.12em] font-semibold text-[color:var(--color-biotech-600)] mb-3">Vendor comparison</div>
        <h1 class="ui-display text-4xl md:text-5xl font-semibold tracking-[-0.02em] text-[color:var(--color-ink)] mb-3">
          Cheapest {{ compound.name }}
        </h1>

        <div v-if="compound.vendor_count > 0" class="flex flex-wrap items-baseline gap-x-4 gap-y-1 text-[color:var(--color-ink-muted)] mb-4">
          <span><strong class="ui-mono text-[color:var(--color-ink)]">{{ compound.vendor_count }}</strong> vendor{{ compound.vendor_count === 1 ? '' : 's' }}</span>
          <span class="text-[color:var(--color-ink-subtle)]">·</span>
          <span><strong class="ui-mono text-[color:var(--color-ink)]">{{ compound.product_count }}</strong> product{{ compound.product_count === 1 ? '' : 's' }}</span>
          <span v-if="compound.cheapest_price" class="text-[color:var(--color-ink-subtle)]">·</span>
          <span v-if="compound.cheapest_price">
            prices from <strong class="ui-mono text-[color:var(--color-verified)]">${{ formatPrice(compound.cheapest_price) }}</strong>
            <template v-if="compound.priciest_price && compound.priciest_price !== compound.cheapest_price">
              &nbsp;to <strong class="ui-mono text-[color:var(--color-ink)]">${{ formatPrice(compound.priciest_price) }}</strong>
            </template>
          </span>
        </div>

        <p v-if="compound.summary" class="text-[color:var(--color-ink-muted)] leading-relaxed max-w-3xl mb-4">
          {{ truncate(compound.summary, 320) }}
        </p>

        <div class="flex flex-wrap items-center gap-2 mb-2">
          <a
            v-if="compound.encyclopedia_url"
            :href="compound.encyclopedia_url"
            class="ui-focus inline-flex items-center gap-1 h-8 px-3 rounded-md border border-[color:var(--color-hairline)] bg-white text-[13px] font-medium text-[color:var(--color-ink)] hover:border-[color:var(--color-accent-400)] hover:text-[color:var(--color-accent-700)] transition-colors"
          >
            Read the encyclopedia entry
            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
          </a>
          <a
            href="/compare"
            class="ui-focus inline-flex items-center gap-1 h-8 px-3 rounded-md border border-[color:var(--color-hairline)] bg-white text-[13px] font-medium text-[color:var(--color-ink)] hover:border-[color:var(--color-accent-400)] hover:text-[color:var(--color-accent-700)] transition-colors"
          >
            ← All compare pages
          </a>
        </div>

        <div class="mt-4 inline-flex items-center gap-2 px-3 py-1.5 rounded-md bg-[color:var(--color-bg)] border border-[color:var(--color-hairline)] text-[11px] text-[color:var(--color-ink-subtle)]">
          <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
          All products listed are for <strong class="text-[color:var(--color-ink-muted)]">research use only</strong> (RUO). Not for human consumption.
        </div>
      </div>
    </section>

    <!-- Price table (full — no collapse on the focused per-compound page) -->
    <section class="max-w-[1280px] mx-auto px-6 lg:px-10 py-10">
      <div v-if="compound.products.length" class="bg-white rounded-[14px] border border-[color:var(--color-hairline)] overflow-hidden shadow-[var(--shadow-xs)]">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-[color:var(--color-hairline)] bg-[color:var(--color-bg)]">
                <th class="text-left px-5 py-3 text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Vendor</th>
                <th class="text-left px-5 py-3 text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Product</th>
                <th class="text-left px-5 py-3 text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)] hidden sm:table-cell">Size</th>
                <th class="text-right px-5 py-3 text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Retail</th>
                <th class="text-right px-5 py-3 text-[11px] uppercase tracking-[0.08em] font-semibold text-emerald-700 bg-emerald-50/50">Price with code</th>
                <th class="text-right px-5 py-3 w-[100px]"></th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(product, pidx) in compound.products"
                :key="product.id"
                :class="[
                  'border-b border-[color:var(--color-hairline-soft)] hover:bg-[color:var(--color-hairline-soft)] transition-colors',
                  pidx === 0 ? 'bg-[color:var(--color-verified-bg)]' : '',
                ]"
              >
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
                        :href="`/brand/${product.brand_slug}`"
                        class="font-semibold text-[color:var(--color-ink)] hover:text-[color:var(--color-accent-600)] transition-colors"
                      >
                        {{ product.brand_name }}
                      </a>
                      <div v-if="pidx === 0" class="flex items-center gap-1 mt-0.5">
                        <span class="text-[10px] font-bold uppercase tracking-[0.1em] text-[color:var(--color-verified)]">Best price</span>
                      </div>
                    </div>
                  </div>
                </td>
                <td class="px-5 py-4">
                  <a
                    :href="`/product/${product.brand_slug}/${product.slug}/${product.id}`"
                    class="text-[color:var(--color-ink)] hover:text-[color:var(--color-accent-600)] transition-colors"
                  >
                    {{ product.name }}
                  </a>
                </td>
                <td class="px-5 py-4 hidden sm:table-cell text-[color:var(--color-ink-muted)] ui-mono text-[13px]">
                  {{ product.size_mg || '—' }}
                </td>
                <td class="px-5 py-4 text-right ui-mono text-[color:var(--color-ink)]">
                  <span :class="product.pmap_price ? 'text-[color:var(--color-ink-subtle)] line-through' : ''">
                    ${{ formatPrice(product.effective_price) }}
                  </span>
                </td>
                <td class="px-5 py-4 text-right bg-emerald-50/30">
                  <template v-if="product.pmap_price">
                    <div class="ui-mono font-semibold text-emerald-700">${{ formatPrice(product.pmap_price) }}</div>
                    <div class="text-[10px] uppercase tracking-wide text-emerald-600 mt-0.5">
                      code <span class="ui-mono">{{ product.brand_coupon_code || 'PMAP' }}</span>
                    </div>
                  </template>
                  <span v-else class="text-[color:var(--color-ink-subtle)] text-[12px]">—</span>
                </td>
                <td class="px-5 py-4 text-right">
                  <a
                    :href="product.go_url"
                    @click="openBuy($event, product)"
                    target="_blank"
                    rel="noopener noreferrer nofollow sponsored"
                    class="ui-focus inline-flex items-center gap-1 h-9 px-3 rounded-md text-[12px] font-semibold text-white bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] hover:-translate-y-[1px] transition-all"
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

      <div v-else class="bg-white rounded-[14px] border border-dashed border-[color:var(--color-hairline)] p-12 text-center text-[color:var(--color-ink-subtle)]">
        <p class="text-lg font-medium text-[color:var(--color-ink)] mb-2">No in-stock listings right now</p>
        <p class="text-sm mb-6">None of the {{ 40 }}+ vendors on Peptidemap currently stock {{ compound.name }} with a live price.</p>
        <a href="/compare" class="ui-focus inline-flex items-center gap-1 h-10 px-4 rounded-md bg-[color:var(--color-ink)] text-white text-[13px] font-semibold hover:opacity-90 transition-opacity">
          Browse other compounds
        </a>
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
              <span class="text-indigo-700">{{ p.a_name }}</span>
              <span class="text-[color:var(--color-ink-subtle)] mx-1.5 font-light">vs</span>
              <span class="text-emerald-700">{{ p.b_name }}</span>
            </div>
            <div class="mt-1 text-[11px] text-[color:var(--color-ink-muted)]">{{ p.tagline }}</div>
          </a>
        </div>
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
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import ModernLayout from '../Layouts/ModernLayout.vue'
import BuyThroughModal from '@/components/BuyThroughModal.vue'

defineProps({
  compound: { type: Object, required: true },
  related: { type: Array, default: () => [] },
  vsPairs: { type: Array, default: () => [] },
  seo: { type: Object, default: () => ({}) },
})

const buyModal = ref(null)
function openBuy(ev, product) {
  if (ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.button === 1) return
  const pct = parseFloat(product?.brand_discount_percent)
  if (!pct || pct <= 0 || pct >= 100) return
  const code = ((product?.brand_coupon_code || '').trim() || 'PMAP').toUpperCase()
  ev.preventDefault()
  buyModal.value?.open({
    destination: product.go_url,
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
function truncate(s, max) {
  if (!s) return ''
  return s.length <= max ? s : s.slice(0, max - 1).trimEnd() + '…'
}
</script>
