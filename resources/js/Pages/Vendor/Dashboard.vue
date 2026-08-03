<template>
  <Layout>
    <!-- Header -->
    <div class="mb-8">
      <div class="text-[11px] uppercase tracking-[0.12em] font-semibold text-[color:var(--color-accent-600)] mb-1">Overview</div>
      <h1 class="ui-display text-3xl font-semibold tracking-[-0.02em] text-[color:var(--color-ink)]">
        Welcome back, {{ $page.props.auth.user.name }}
      </h1>
    </div>

    <!-- Stat cards -->
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-4 mb-8">
      <div v-for="stat in statCards" :key="stat.label" class="bg-white rounded-[12px] border border-[color:var(--color-hairline)] p-5 shadow-[var(--shadow-xs)]">
        <div class="text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">{{ stat.label }}</div>
        <div class="mt-2 ui-mono text-2xl font-bold text-[color:var(--color-ink)]">{{ stat.value }}</div>
      </div>
    </div>

    <!-- Last 30 days summary -->
    <div class="bg-white rounded-[14px] border border-[color:var(--color-hairline)] shadow-[var(--shadow-xs)] mb-8 overflow-hidden">
      <div class="flex items-center justify-between px-6 py-5 border-b border-[color:var(--color-hairline)]">
        <div>
          <h2 class="text-[15px] font-semibold text-[color:var(--color-ink)]">Last 30 days</h2>
          <p class="text-[12px] text-[color:var(--color-ink-subtle)] mt-0.5">Storefront traffic, outbound clicks, and new reviews. Change is vs. the previous 30 days.</p>
        </div>
        <Link
          href="/vendor/storefront-analytics"
          class="text-[12px] font-semibold text-[color:var(--color-accent-600)] hover:text-[color:var(--color-accent-700)]"
        >
          Full analytics →
        </Link>
      </div>
      <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-[color:var(--color-hairline)] divide-y lg:divide-y-0">
        <div v-for="s in summary30" :key="s.label" class="p-6">
          <div class="text-[11px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">{{ s.label }}</div>
          <div class="mt-2 flex items-baseline gap-2">
            <span class="ui-mono text-2xl font-bold text-[color:var(--color-ink)]">{{ s.value }}</span>
            <span
              v-if="s.change !== null && s.change !== undefined"
              :class="[
                'text-[11px] font-semibold ui-mono',
                s.change > 0 ? 'text-emerald-600' : s.change < 0 ? 'text-red-500' : 'text-[color:var(--color-ink-subtle)]',
              ]"
            >
              {{ s.change > 0 ? '+' : '' }}{{ s.change }}%
            </span>
            <span v-else class="text-[11px] text-[color:var(--color-ink-subtle)]">—</span>
          </div>
          <div class="mt-1 text-[11px] text-[color:var(--color-ink-subtle)]">{{ s.hint }}</div>
        </div>
      </div>
    </div>

    <!-- Quick links -->
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
      <Link
        v-for="link in quickLinks"
        :key="link.href"
        :href="link.href"
        class="group bg-white rounded-[12px] border border-[color:var(--color-hairline)] p-5 shadow-[var(--shadow-xs)] hover:border-[color:var(--color-accent-400)] hover:shadow-[var(--shadow-md)] transition-all"
      >
        <div class="flex items-center gap-3">
          <div :class="['w-10 h-10 rounded-[10px] flex items-center justify-center flex-shrink-0', link.bg]">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" v-html="link.icon"></svg>
          </div>
          <div>
            <div class="text-[13px] font-semibold text-[color:var(--color-ink)] group-hover:text-[color:var(--color-accent-600)] transition-colors">{{ link.label }}</div>
            <div class="text-[11px] text-[color:var(--color-ink-subtle)]">{{ link.desc }}</div>
          </div>
        </div>
      </Link>
    </div>

    <!-- Recent Products -->
    <div class="bg-white rounded-[14px] border border-[color:var(--color-hairline)] shadow-[var(--shadow-xs)] overflow-hidden">
      <div class="flex items-center justify-between px-6 py-5 border-b border-[color:var(--color-hairline)]">
        <div>
          <h2 class="text-[15px] font-semibold text-[color:var(--color-ink)]">Recent products</h2>
          <p class="text-[12px] text-[color:var(--color-ink-subtle)] mt-0.5">Your latest catalog items</p>
        </div>
        <Link
          href="/vendor/products"
          class="h-8 px-3 text-[12px] font-semibold text-white bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] rounded-[8px] shadow-sm hover:-translate-y-[0.5px] transition-all inline-flex items-center gap-1.5"
        >
          View all
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </Link>
      </div>

      <div v-if="recentProducts.length > 0">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-[color:var(--color-hairline)] bg-[color:var(--color-bg)]">
              <th class="px-6 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Product</th>
              <th class="px-6 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Price</th>
              <th class="px-6 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Rating</th>
              <th class="px-6 py-3 text-left text-[10px] uppercase tracking-[0.08em] font-semibold text-[color:var(--color-ink-subtle)]">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="product in recentProducts"
              :key="product.id"
              class="border-b border-[color:var(--color-hairline-soft)] hover:bg-[color:var(--color-hairline-soft)] transition-colors"
            >
              <td class="px-6 py-3.5">
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 flex-shrink-0 bg-[color:var(--color-hairline-soft)] border border-[color:var(--color-hairline)] rounded-[6px] overflow-hidden flex items-center justify-center">
                    <img v-if="product.image_url" :src="product.image_url" :alt="product.name" class="w-full h-full object-cover" loading="lazy" />
                    <span v-else class="text-[10px] font-bold text-[color:var(--color-ink-muted)]">{{ product.name.substring(0, 2).toUpperCase() }}</span>
                  </div>
                  <span class="text-[13px] font-medium text-[color:var(--color-ink)] truncate max-w-[200px]">{{ product.name }}</span>
                </div>
              </td>
              <td class="px-6 py-3.5 ui-mono text-[13px] text-[color:var(--color-ink)]">{{ formatPrice(product.price) }}</td>
              <td class="px-6 py-3.5">
                <div class="flex items-center gap-1">
                  <svg class="w-3.5 h-3.5 text-[color:var(--color-caution)]" viewBox="0 0 20 20" fill="currentColor"><path d="M10 1l2.8 5.7 6.2.9-4.5 4.4 1.1 6.3L10 15.3 4.4 18.3l1.1-6.3L1 7.6l6.2-.9L10 1z"/></svg>
                  <span class="ui-mono text-[13px] text-[color:var(--color-ink)]">{{ product.rating_average || '0.0' }}</span>
                </div>
              </td>
              <td class="px-6 py-3.5">
                <span
                  :class="[
                    'inline-flex px-2 py-0.5 text-[10px] font-semibold rounded-sm',
                    product.hidden
                      ? 'bg-[color:var(--color-danger-bg)] text-[color:var(--color-danger)]'
                      : 'bg-[color:var(--color-verified-bg)] text-[#065F46]',
                  ]"
                >{{ product.hidden ? 'Hidden' : 'Active' }}</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else class="px-6 py-16 text-center">
        <div class="text-[15px] font-semibold text-[color:var(--color-ink)]">No products yet</div>
        <p class="mt-1 text-[13px] text-[color:var(--color-ink-subtle)]">Import or add products to see them here.</p>
        <Link
          href="/vendor/import"
          class="mt-4 inline-flex h-9 px-4 text-[13px] font-semibold text-white bg-gradient-to-b from-[#5B5FE8] to-[#4338CA] rounded-[8px] items-center gap-2 shadow-sm"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
          Import products
        </Link>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import Layout from './Layout.vue'

const props = defineProps({
  stats: {
    type: Object,
    default: () => ({
      totalProducts: 0,
      activeProducts: 0,
      totalViews: 0,
      totalReviews: 0,
      averageRating: '0.0',
    })
  },
  recentProducts: {
    type: Array,
    default: () => []
  }
})

const statCards = [
  { label: 'Total products', value: props.stats.totalProducts || 0 },
  { label: 'Active products', value: props.stats.activeProducts || 0 },
  { label: 'Storefront views', value: props.stats.totalViews || 0 },
  { label: 'Reviews', value: props.stats.totalReviews || 0 },
  { label: 'Avg. rating', value: props.stats.averageRating || '0.0' },
]

// 30-day summary card. Each stat can show a % change vs the previous 30 days
// (or "—" if the previous period had zero and there's nothing to compare against).
const summary30 = [
  {
    label: 'Storefront views',
    value: (props.stats.views30 ?? 0).toLocaleString(),
    change: props.stats.viewsChange,
    hint: 'People landing on your storefront page',
  },
  {
    label: 'Outbound clicks',
    value: (props.stats.clicks30 ?? 0).toLocaleString(),
    change: props.stats.clicksChange,
    hint: 'Clicks from your products through to your site',
  },
  {
    label: 'Click-through rate',
    value: props.stats.clickRate30 != null ? `${props.stats.clickRate30}%` : '—',
    change: null,
    hint: 'Clicks ÷ views',
  },
  {
    label: 'New reviews',
    value: (props.stats.reviews30 ?? 0).toLocaleString(),
    change: props.stats.reviewsChange,
    hint: 'Reviews written this month',
  },
]

const quickLinks = [
  { href: '/vendor/products', label: 'Products', desc: 'Manage your catalog', bg: 'bg-[color:var(--color-accent-600)]', icon: '<path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>' },
  { href: '/vendor/storefront-analytics', label: 'Storefront', desc: 'Views & clicks', bg: 'bg-[#059669]', icon: '<path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>' },
  { href: '/vendor/reviews', label: 'Reviews', desc: 'Customer feedback', bg: 'bg-[#D97706]', icon: '<path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>' },
  { href: '/vendor/integrations', label: 'Integrations', desc: 'Connect your store', bg: 'bg-[#7C3AED]', icon: '<path d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>' },
]

function formatPrice(value) {
  const number = Number(value)
  if (Number.isNaN(number) || number <= 0) return '$0.00'
  return `$${number.toFixed(2)}`
}
</script>
