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
      </div>
    </section>

    <!-- Compound quick-nav grid -->
    <section class="max-w-[1280px] mx-auto px-6 lg:px-10 py-10">
      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
        <a
          v-for="compound in compounds"
          :key="compound.id"
          :href="`#${compound.anchor}`"
          class="ui-focus group flex flex-col gap-2 p-4 rounded-[12px] border border-[color:var(--color-hairline)] bg-white hover:border-[color:var(--color-accent-400)] hover:shadow-[var(--shadow-md)] transition-all duration-[180ms]"
        >
          <div class="ui-display text-[15px] font-semibold text-[color:var(--color-ink)] leading-tight group-hover:text-[color:var(--color-accent-600)] transition-colors">
            {{ compound.name }}
          </div>
          <div class="flex items-center gap-3 text-[11px] text-[color:var(--color-ink-muted)]">
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
          <div class="flex items-start justify-between gap-4 mb-6 flex-wrap">
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
            <div class="flex items-center gap-3 flex-shrink-0">
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

          <!-- Price table -->
          <div v-if="compound.products.length" class="bg-white rounded-[14px] border border-[color:var(--color-hairline)] overflow-hidden shadow-[var(--shadow-xs)]">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-[color:var(--color-hairline)] bg-[color:var(--color-bg)]">
                  <th class="text-left px-5 py-3 text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Vendor</th>
                  <th class="text-left px-5 py-3 text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Product</th>
                  <th class="text-right px-5 py-3 text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Price</th>
                  <th class="text-right px-5 py-3 text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)] hidden sm:table-cell">Discount</th>
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
                        </div>
                      </div>
                    </div>
                  </td>
                  <!-- Product -->
                  <td class="px-5 py-4 text-[color:var(--color-ink-muted)]">
                    <span class="line-clamp-1">{{ product.name }}</span>
                    <span v-if="product.size_mg" class="ui-mono text-[11px] text-[color:var(--color-ink-subtle)]">
                      {{ product.size_mg }}mg
                    </span>
                  </td>
                  <!-- Price -->
                  <td class="px-5 py-4 text-right">
                    <div v-if="product.discount_price && product.discount_price < product.price" class="flex flex-col items-end">
                      <span class="ui-mono text-[15px] font-bold text-[color:var(--color-ink)]">${{ formatPrice(product.discount_price) }}</span>
                      <span class="ui-mono text-[11px] text-[color:var(--color-ink-subtle)] line-through">${{ formatPrice(product.price) }}</span>
                    </div>
                    <span v-else class="ui-mono text-[15px] font-bold text-[color:var(--color-ink)]">
                      ${{ formatPrice(product.price) }}
                    </span>
                  </td>
                  <!-- Savings -->
                  <td class="px-5 py-4 text-right hidden sm:table-cell">
                    <span
                      v-if="product.discount_price && product.discount_price < product.price"
                      class="ui-mono text-xs font-semibold text-[color:var(--color-verified)]"
                    >
                      -{{ Math.round((1 - product.discount_price / product.price) * 100) }}%
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
          </div>

          <!-- Empty state -->
          <div
            v-else
            class="bg-white rounded-[14px] border border-dashed border-[color:var(--color-hairline)] p-10 text-center text-[color:var(--color-ink-subtle)] text-sm"
          >
            No vendors currently stock this compound.
          </div>
        </div>
      </div>
    </section>
  </ModernLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import ModernLayout from '@/Pages/Layouts/ModernLayout.vue'

defineProps({
  compounds: { type: Array, default: () => [] },
  seo: { type: Object, default: () => ({}) },
})

function formatPrice(p) {
  if (p === null || p === undefined || p === '') return '—'
  const num = typeof p === 'number' ? p : parseFloat(p)
  return isNaN(num) ? String(p) : num.toFixed(2)
}
</script>
