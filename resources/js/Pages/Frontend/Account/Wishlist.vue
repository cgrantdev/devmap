<template>
  <ModernLayout>
    <div class="max-w-4xl mx-auto px-5 lg:px-10 py-10">
      <div class="flex items-center justify-between mb-8">
        <div>
          <h1 class="ui-display text-2xl font-semibold tracking-tight text-[color:var(--color-ink)] mb-1">My wishlist</h1>
          <p class="text-sm text-[color:var(--color-ink-muted)]">Products and compounds you're watching. We email a weekly digest when prices drop 5%+.</p>
        </div>
        <Link href="/account/reviews" class="hidden sm:inline-flex text-[12px] font-medium text-[color:var(--color-accent-600)] hover:text-[color:var(--color-accent-700)]">
          My reviews →
        </Link>
      </div>

      <div
        v-if="flashSuccess"
        class="mb-6 px-4 py-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm"
      >
        {{ flashSuccess }}
      </div>

      <!-- Watched Products -->
      <section class="mb-12">
        <h2 class="text-[14px] font-semibold text-[color:var(--color-ink)] mb-3 flex items-center gap-2">
          <svg class="w-4 h-4 text-rose-500" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
          Watched products <span class="text-[color:var(--color-ink-subtle)] font-normal">({{ watchedProducts.length }})</span>
        </h2>

        <div v-if="watchedProducts.length === 0" class="text-center py-14 border border-dashed border-[color:var(--color-hairline)] bg-white rounded-[12px]">
          <p class="text-[color:var(--color-ink-muted)] text-sm">Start watching products to get price alerts on drops.</p>
          <Link href="/products" class="inline-block mt-3 text-[13px] font-medium text-[color:var(--color-accent-600)] hover:text-[color:var(--color-accent-700)]">Browse products →</Link>
        </div>

        <div v-else class="grid grid-cols-1 gap-3">
          <div
            v-for="row in watchedProducts"
            :key="row.wishlist_id"
            class="border border-[color:var(--color-hairline)] bg-white rounded-[12px] p-4 flex items-center gap-4"
          >
            <img
              v-if="row.product.image_url"
              :src="row.product.image_url"
              :alt="row.product.name"
              class="w-16 h-16 object-contain bg-gray-50 rounded-md flex-shrink-0"
              loading="lazy"
            />
            <div v-else class="w-16 h-16 bg-gray-50 rounded-md flex-shrink-0" />

            <div class="flex-1 min-w-0">
              <Link
                :href="`/product/${row.product.brand?.slug || 'unknown'}/${row.product.slug}/${row.product.id}`"
                class="text-[14px] font-semibold text-[color:var(--color-ink)] hover:text-[color:var(--color-accent-600)] transition-colors line-clamp-1"
              >
                {{ row.product.name }}
              </Link>
              <div class="text-[12px] text-[color:var(--color-ink-muted)] mt-0.5">
                <span v-if="row.product.brand?.name">{{ row.product.brand.name }}</span>
              </div>
              <div class="mt-1.5 flex items-center flex-wrap gap-x-3 gap-y-1">
                <span class="ui-mono text-[14px] font-bold text-[color:var(--color-ink)]">${{ row.product.current_price.toFixed(2) }}</span>
                <span v-if="pmapPrice(row.product)" class="text-[11px] text-emerald-700 font-semibold">
                  PMAP <span class="ui-mono">${{ pmapPrice(row.product).toFixed(2) }}</span> w/ <span class="ui-mono">{{ (row.product.brand_coupon_code || 'PMAP').toUpperCase() }}</span>
                </span>
                <span
                  v-if="row.price_change_pct !== null"
                  :class="[
                    'text-[11px] font-semibold uppercase tracking-[0.06em] px-2 py-0.5 rounded-full',
                    row.price_change_pct < 0
                      ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                      : row.price_change_pct > 0
                        ? 'bg-rose-50 text-rose-700 border border-rose-200'
                        : 'bg-slate-50 text-slate-600 border border-slate-200',
                  ]"
                  :title="row.last_seen_price !== null ? `Added at $${row.last_seen_price.toFixed(2)}` : ''"
                >
                  <template v-if="row.price_change_pct < 0">▼ {{ Math.abs(row.price_change_pct) }}%</template>
                  <template v-else-if="row.price_change_pct > 0">▲ {{ row.price_change_pct }}%</template>
                  <template v-else>no change</template>
                </span>
              </div>
            </div>

            <div class="flex items-center gap-2 flex-shrink-0">
              <a
                :href="`/go/${row.product.id}`"
                target="_blank"
                rel="noopener noreferrer nofollow sponsored"
                class="inline-flex items-center h-8 px-3 rounded-[8px] text-[12px] font-semibold text-white bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] hover:-translate-y-[0.5px] transition-all"
              >Buy</a>
              <button
                type="button"
                @click="remove(row)"
                class="text-[12px] font-medium text-rose-600 hover:text-rose-700 px-2"
              >Remove</button>
            </div>
          </div>
        </div>
      </section>

      <!-- Watched Compounds -->
      <section>
        <h2 class="text-[14px] font-semibold text-[color:var(--color-ink)] mb-3 flex items-center gap-2">
          <svg class="w-4 h-4 text-[color:var(--color-accent-600)]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2v20M2 12h20"/></svg>
          Watched compounds <span class="text-[color:var(--color-ink-subtle)] font-normal">({{ watchedCompounds.length }})</span>
        </h2>

        <div v-if="watchedCompounds.length === 0" class="text-center py-14 border border-dashed border-[color:var(--color-hairline)] bg-white rounded-[12px]">
          <p class="text-[color:var(--color-ink-muted)] text-sm">Watch a compound on the Compare page to see every vendor and get notified of the best price.</p>
          <Link href="/compare" class="inline-block mt-3 text-[13px] font-medium text-[color:var(--color-accent-600)] hover:text-[color:var(--color-accent-700)]">Browse compounds →</Link>
        </div>

        <div v-else class="grid grid-cols-1 gap-3">
          <div
            v-for="row in watchedCompounds"
            :key="row.wishlist_id"
            class="border border-[color:var(--color-hairline)] bg-white rounded-[12px] p-4"
          >
            <div class="flex items-start justify-between gap-4 mb-3">
              <div class="min-w-0">
                <Link
                  :href="`/encyclopedia/${row.category.slug}`"
                  class="text-[14px] font-semibold text-[color:var(--color-ink)] hover:text-[color:var(--color-accent-600)] transition-colors"
                >{{ row.category.name }}</Link>
                <div class="text-[12px] text-[color:var(--color-ink-muted)] mt-0.5">
                  {{ row.vendor_count }} vendor{{ row.vendor_count === 1 ? '' : 's' }}
                  <template v-if="row.from_price"> · from <span class="ui-mono font-semibold">${{ row.from_price.toFixed(2) }}</span></template>
                </div>
              </div>
              <button
                type="button"
                @click="remove(row)"
                class="text-[12px] font-medium text-rose-600 hover:text-rose-700"
              >Remove</button>
            </div>

            <div v-if="row.top_products.length" class="grid grid-cols-1 sm:grid-cols-3 gap-2">
              <a
                v-for="p in row.top_products"
                :key="p.id"
                :href="`/go/${p.id}`"
                target="_blank"
                rel="noopener noreferrer nofollow sponsored"
                class="border border-[color:var(--color-hairline)] rounded-[8px] px-3 py-2 text-[12px] hover:border-[color:var(--color-accent-400)] transition-colors"
              >
                <div class="text-[color:var(--color-ink-muted)] truncate">{{ p.brand_name }}</div>
                <div class="ui-mono font-semibold text-[color:var(--color-ink)]">${{ Number((p.discount_price && p.discount_price < p.price) ? p.discount_price : p.price).toFixed(2) }}</div>
              </a>
            </div>
          </div>
        </div>
      </section>
    </div>
  </ModernLayout>
</template>

<script setup>
import { Link, usePage, router } from '@inertiajs/vue3'
import ModernLayout from '@/Pages/Layouts/ModernLayout.vue'

defineProps({
  watchedProducts: { type: Array, default: () => [] },
  watchedCompounds: { type: Array, default: () => [] },
})

const page = usePage()
const flashSuccess = page.props.flash?.success || null

// PMAP-discounted price when brand has a coupon-discount % configured.
function pmapPrice(product) {
  const pct = parseFloat(product.brand_discount_percent)
  if (!pct || pct <= 0 || pct >= 100) return null
  return product.current_price * (1 - pct / 100)
}

function remove(row) {
  if (!confirm('Remove from wishlist?')) return
  router.delete(`/account/wishlist/${row.wishlist_id}`, { preserveScroll: true })
}
</script>
