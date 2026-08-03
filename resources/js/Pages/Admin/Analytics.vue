<template>
  <AdminLayout>
    <div class="mb-8">
      <h1 class="text-3xl text-gray-900 mb-2">Analytics</h1>
      <p class="text-gray-600">Track marketplace performance and metrics</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <!-- Total Visits -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
            <svg class="lucide lucide-eye w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0" />
              <circle stroke-linecap="round" stroke-linejoin="round" stroke-width="2" cx="12" cy="12" r="3"></circle>
            </svg>
          </div>
          <div class="flex items-center gap-1 text-green-600 text-sm">
            <svg class="lucide lucide-trending-up w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7h6v6" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m22 7-8.5 8.5-5-5L2 17" />
            </svg>
            {{ stats[0]?.change }}
          </div>
        </div>
        <div class="text-2xl text-gray-900 mb-1">{{ stats[0]?.value }}</div>
        <div class="text-sm text-gray-600">{{ stats[0]?.label }}</div>
      </div>

      <!-- Active Users -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
            <svg class="lucide lucide-users w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 3.128a4 4 0 0 1 0 7.744" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M22 21v-2a4 4 0 0 0-3-3.87" />
              <circle stroke-linecap="round" stroke-linejoin="round" stroke-width="2" cx="9" cy="7" r="4"></circle>
            </svg>
          </div>
          <div class="flex items-center gap-1 text-green-600 text-sm">
            <svg class="lucide lucide-trending-up w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7h6v6" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m22 7-8.5 8.5-5-5L2 17" />
            </svg>
            {{ stats[1]?.change }}
          </div>
        </div>
        <div class="text-2xl text-gray-900 mb-1">{{ stats[1]?.value }}</div>
        <div class="text-sm text-gray-600">{{ stats[1]?.label }}</div>
      </div>

      <!-- Vendor Signups -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
            <svg class="lucide lucide-store w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 21v-5a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v5" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.774 10.31a1.12 1.12 0 0 0-1.549 0 2.5 2.5 0 0 1-3.451 0 1.12 1.12 0 0 0-1.548 0 2.5 2.5 0 0 1-3.452 0 1.12 1.12 0 0 0-1.549 0 2.5 2.5 0 0 1-3.77-3.248l2.889-4.184A2 2 0 0 1 7 2h10a2 2 0 0 1 1.653.873l2.895 4.192a2.5 2.5 0 0 1-3.774 3.244" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 10.95V19a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8.05" />
            </svg>
          </div>
          <div class="flex items-center gap-1 text-green-600 text-sm">
            <svg class="lucide lucide-trending-up w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7h6v6" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m22 7-8.5 8.5-5-5L2 17" />
            </svg>
            {{ stats[2]?.change }}
          </div>
        </div>
        <div class="text-2xl text-gray-900 mb-1">{{ stats[2]?.value }}</div>
        <div class="text-sm text-gray-600">{{ stats[2]?.label }}</div>
      </div>

      <!-- Products Listed -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
            <svg class="lucide lucide-package w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 22V12" />
              <polyline stroke-linecap="round" stroke-linejoin="round" stroke-width="2" points="3.29 7 12 12 20.71 7"></polyline>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m7.5 4.27 9 5.15" />
            </svg>
          </div>
          <div class="flex items-center gap-1 text-green-600 text-sm">
            <svg class="lucide lucide-trending-up w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7h6v6" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m22 7-8.5 8.5-5-5L2 17" />
            </svg>
            {{ stats[3]?.change }}
          </div>
        </div>
        <div class="text-2xl text-gray-900 mb-1">{{ stats[3]?.value }}</div>
        <div class="text-sm text-gray-600">{{ stats[3]?.label }}</div>
      </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-2 gap-6">
      <!-- Top Vendors -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg text-gray-900 mb-4">Top Performing Vendors</h2>
        <div class="space-y-4">
          <div v-for="(vendor, index) in topVendors" :key="vendor.id" class="flex items-center gap-4">
            <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-white">
              {{ index + 1 }}
            </div>
            <div class="flex-1">
              <div class="text-sm text-gray-900">{{ vendor.name }}</div>
              <div class="text-xs text-gray-500">{{ formatNumber(vendor.clicks) }} clicks (30d)</div>
            </div>
          </div>
          <div v-if="topVendors.length === 0" class="text-sm text-gray-500 text-center py-4">
            No vendor data available
          </div>
        </div>
      </div>

      <!-- Top Products -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg text-gray-900 mb-4">Top Viewed Products</h2>
        <div class="space-y-4">
          <div v-for="(product, index) in topProducts" :key="product.id" class="flex items-center gap-4">
            <div class="w-8 h-8 bg-green-600 rounded-lg flex items-center justify-center text-white">
              {{ index + 1 }}
            </div>
            <div class="flex-1">
              <div class="text-sm text-gray-900">{{ product.name }}</div>
              <div class="text-xs text-gray-500">{{ formatNumber(product.clicks) }} clicks (30d)</div>
            </div>
          </div>
          <div v-if="topProducts.length === 0" class="text-sm text-gray-500 text-center py-4">
            No product data available
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================ -->
    <!-- Page views by type                                           -->
    <!-- ============================================================ -->
    <div class="mt-10">
      <div class="flex items-baseline justify-between mb-4">
        <h2 class="text-xl text-gray-900">Page views</h2>
        <p class="text-xs text-gray-500">Server-side view counts across public pages (bots excluded).</p>
      </div>
      <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wide">
            <tr>
              <th class="text-left px-4 py-3">Page type</th>
              <th class="text-right px-3 py-3">Views (7d)</th>
              <th class="text-right px-3 py-3">Views (30d)</th>
              <th class="text-right px-4 py-3">Views (all)</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="row in pageTypes" :key="row.page_type">
              <td class="px-4 py-3 font-mono text-gray-800">{{ row.page_type }}</td>
              <td class="px-3 py-3 text-right">{{ formatNumber(row.views_7d) }}</td>
              <td class="px-3 py-3 text-right">{{ formatNumber(row.views_30d) }}</td>
              <td class="px-4 py-3 text-right text-gray-500">{{ formatNumber(row.views_all) }}</td>
            </tr>
            <tr v-if="!pageTypes.length">
              <td colspan="4" class="px-4 py-6 text-center text-gray-500">No page views yet.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ============================================================ -->
    <!-- Storefronts — one row per vendor, all interactions rolled up -->
    <!-- ============================================================ -->
    <div class="mt-10">
      <div class="flex items-baseline justify-between mb-4">
        <h2 class="text-xl text-gray-900">Storefronts</h2>
        <p class="text-xs text-gray-500">Per-vendor storefront traffic: views, product clicks, "Visit website" CTA clicks, coupon copies.</p>
      </div>
      <div class="bg-white rounded-lg border border-gray-200 overflow-x-auto">
        <table class="w-full text-sm min-w-[900px]">
          <thead class="bg-gray-50 text-gray-600 text-[11px] uppercase tracking-wide">
            <tr>
              <th class="text-left px-4 py-3">Vendor</th>
              <th class="text-right px-2 py-3">Views 7d</th>
              <th class="text-right px-2 py-3">Views 30d</th>
              <th class="text-right px-2 py-3">Views all</th>
              <th class="text-right px-2 py-3">Product clicks 7d</th>
              <th class="text-right px-2 py-3">Product clicks 30d</th>
              <th class="text-right px-2 py-3">Product clicks all</th>
              <th class="text-right px-2 py-3">CTA clicks 7d</th>
              <th class="text-right px-2 py-3">CTA clicks 30d</th>
              <th class="text-right px-2 py-3">CTA clicks all</th>
              <th class="text-right px-2 py-3">Coupon copies 30d</th>
              <th class="text-right px-4 py-3">CTR (30d)</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="v in vendorBreakdown" :key="v.brand_id">
              <td class="px-4 py-3">
                <a v-if="v.slug" :href="`/brand/${v.slug}`" class="text-blue-700 hover:underline" target="_blank">{{ v.name }}</a>
                <span v-else class="text-gray-800">{{ v.name }}</span>
              </td>
              <td class="px-2 py-3 text-right">{{ formatNumber(v.page_views_7d) }}</td>
              <td class="px-2 py-3 text-right">{{ formatNumber(v.page_views_30d) }}</td>
              <td class="px-2 py-3 text-right text-gray-500">{{ formatNumber(v.page_views_all) }}</td>
              <td class="px-2 py-3 text-right">{{ formatNumber(v.product_clicks_7d) }}</td>
              <td class="px-2 py-3 text-right">{{ formatNumber(v.product_clicks_30d) }}</td>
              <td class="px-2 py-3 text-right text-gray-500">{{ formatNumber(v.product_clicks_all) }}</td>
              <td class="px-2 py-3 text-right">{{ formatNumber(v.storefront_clicks_7d) }}</td>
              <td class="px-2 py-3 text-right">{{ formatNumber(v.storefront_clicks_30d) }}</td>
              <td class="px-2 py-3 text-right text-gray-500">{{ formatNumber(v.storefront_clicks_all) }}</td>
              <td class="px-2 py-3 text-right text-gray-500">{{ formatNumber(v.coupon_copies_30d) }}</td>
              <td class="px-4 py-3 text-right text-blue-700 font-medium">{{ v.visit_ctr_30d ?? '—' }}</td>
            </tr>
            <tr v-if="!vendorBreakdown.length">
              <td colspan="12" class="px-4 py-6 text-center text-gray-500">
                No storefront traffic yet.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ============================================================ -->
    <!-- Banners — hero carousel + homepage CTA                       -->
    <!-- ============================================================ -->
    <div class="mt-10">
      <div class="flex items-baseline justify-between mb-4">
        <h2 class="text-xl text-gray-900">Banners</h2>
        <p class="text-xs text-gray-500">Homepage hero carousel. Set the label from <a href="/admin/banners" class="text-blue-600 hover:underline">Homepage Banners → Analytics Tag</a>.</p>
      </div>

      <div class="bg-white rounded-lg border border-gray-200 overflow-x-auto">
        <table class="w-full text-sm min-w-[800px]">
          <thead class="bg-gray-50 text-gray-600 text-[11px] uppercase tracking-wide">
            <tr>
              <th class="text-left px-4 py-3">Banner</th>
              <th class="text-right px-3 py-3">Impr 7d</th>
              <th class="text-right px-3 py-3">Clicks 7d</th>
              <th class="text-right px-3 py-3">Impr 30d</th>
              <th class="text-right px-3 py-3">Clicks 30d</th>
              <th class="text-right px-3 py-3">Impr all</th>
              <th class="text-right px-3 py-3">Clicks all</th>
              <th class="text-right px-4 py-3">CTR 30d</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="b in bannerBanners" :key="`${b.slot}|${b.banner_key}`">
              <td class="px-4 py-3">
                <div class="flex flex-col">
                  <a v-if="b.url" :href="b.url" target="_blank" rel="noopener"
                    class="text-gray-900 font-medium hover:text-blue-700 hover:underline">{{ b.label }}</a>
                  <span v-else class="text-gray-900 font-medium">{{ b.label }}</span>
                  <span class="text-xs text-gray-400 font-mono">{{ b.banner_key }}</span>
                </div>
              </td>
              <td class="px-3 py-3 text-right">{{ formatNumber(b.impressions_7d) }}</td>
              <td class="px-3 py-3 text-right">{{ formatNumber(b.clicks_7d) }}</td>
              <td class="px-3 py-3 text-right">{{ formatNumber(b.impressions_30d) }}</td>
              <td class="px-3 py-3 text-right">{{ formatNumber(b.clicks_30d) }}</td>
              <td class="px-3 py-3 text-right text-gray-500">{{ formatNumber(b.impressions_all) }}</td>
              <td class="px-3 py-3 text-right text-gray-500">{{ formatNumber(b.clicks_all) }}</td>
              <td class="px-4 py-3 text-right font-medium text-blue-700">{{ b.ctr_30d ?? '—' }}</td>
            </tr>
            <tr v-if="!bannerBanners.length">
              <td colspan="8" class="px-4 py-6 text-center text-gray-500">
                No banner events tracked yet. Impressions appear after visitors load the homepage.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- 30-day daily totals (simple bars) -->
      <div v-if="bannerByDay.length" class="mt-8 bg-white rounded-lg border border-gray-200 p-5">
        <div class="flex items-baseline justify-between mb-4">
          <h3 class="text-base text-gray-900">Daily totals across all slots</h3>
          <span class="text-xs text-gray-500">Last 30 days</span>
        </div>
        <div class="flex items-end gap-1 h-40">
          <div
            v-for="d in bannerByDay"
            :key="d.day"
            class="flex-1 flex flex-col justify-end gap-0.5"
            :title="`${d.day}: ${d.impressions} impr / ${d.clicks} clk`"
          >
            <div
              class="bg-blue-500/70 rounded-sm"
              :style="{ height: barHeight(d.impressions, maxImpressions) }"
            />
            <div
              class="bg-emerald-500 rounded-sm"
              :style="{ height: barHeight(d.clicks, maxImpressions) }"
            />
          </div>
        </div>
        <div class="mt-3 flex items-center gap-4 text-xs text-gray-600">
          <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 bg-blue-500/70 rounded-sm"></span> Impressions</span>
          <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 bg-emerald-500 rounded-sm"></span> Clicks</span>
        </div>
      </div>
    </div>

    <!-- ============================================================ -->
    <!-- Site CTAs — internal metrics, not shown to vendors           -->
    <!-- ============================================================ -->
    <div class="mt-10">
      <div class="flex items-baseline justify-between mb-4">
        <h2 class="text-xl text-gray-900">Site CTAs <span class="text-xs font-normal text-gray-500 ml-2">Internal — funnel metrics for us</span></h2>
        <p class="text-xs text-gray-500">Non-banner call-to-action clicks on public pages (e.g. "Become a vendor").</p>
      </div>
      <div class="bg-white rounded-lg border border-gray-200 overflow-x-auto">
        <table class="w-full text-sm min-w-[800px]">
          <thead class="bg-gray-50 text-gray-600 text-[11px] uppercase tracking-wide">
            <tr>
              <th class="text-left px-4 py-3">CTA</th>
              <th class="text-right px-3 py-3">Impr 7d</th>
              <th class="text-right px-3 py-3">Clicks 7d</th>
              <th class="text-right px-3 py-3">Impr 30d</th>
              <th class="text-right px-3 py-3">Clicks 30d</th>
              <th class="text-right px-3 py-3">Impr all</th>
              <th class="text-right px-3 py-3">Clicks all</th>
              <th class="text-right px-4 py-3">CTR 30d</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="c in siteCtas" :key="`${c.slot}|${c.banner_key}`">
              <td class="px-4 py-3">
                <div class="flex flex-col">
                  <span class="text-gray-900 font-medium">{{ c.label }}</span>
                  <span class="text-xs text-gray-400 font-mono">{{ c.slot }}</span>
                </div>
              </td>
              <td class="px-3 py-3 text-right">{{ formatNumber(c.impressions_7d) }}</td>
              <td class="px-3 py-3 text-right">{{ formatNumber(c.clicks_7d) }}</td>
              <td class="px-3 py-3 text-right">{{ formatNumber(c.impressions_30d) }}</td>
              <td class="px-3 py-3 text-right">{{ formatNumber(c.clicks_30d) }}</td>
              <td class="px-3 py-3 text-right text-gray-500">{{ formatNumber(c.impressions_all) }}</td>
              <td class="px-3 py-3 text-right text-gray-500">{{ formatNumber(c.clicks_all) }}</td>
              <td class="px-4 py-3 text-right font-medium text-blue-700">{{ c.ctr_30d ?? '—' }}</td>
            </tr>
            <tr v-if="!siteCtas.length">
              <td colspan="8" class="px-4 py-6 text-center text-gray-500">
                No CTA events yet.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from './Layout.vue'
import { computed } from 'vue'

const props = defineProps({
  stats: {
    type: Array,
    default: () => [
      { label: 'Total Visits', value: '24,521', change: '+12.5%' },
      { label: 'Active Users', value: '8,492', change: '+8.1%' },
      { label: 'Vendor Signups', value: '127', change: '+15.3%' },
      { label: 'Products Listed', value: '1,842', change: '+3.2%' },
    ]
  },
  topVendors: {
    type: Array,
    default: () => []
  },
  topProducts: {
    type: Array,
    default: () => []
  },
  clicksByDay: {
    type: Array,
    default: () => []
  },
  bannerBanners: { type: Array, default: () => [] },
  siteCtas: { type: Array, default: () => [] },
  bannerByDay: { type: Array, default: () => [] },
  pageTypes: { type: Array, default: () => [] },
  pageViewsByDay: { type: Array, default: () => [] },
  vendorBreakdown: { type: Array, default: () => [] },
})

const formatNumber = (n) => new Intl.NumberFormat().format(n ?? 0)

const maxImpressions = computed(() =>
  Math.max(1, ...props.bannerByDay.map(d => d.impressions || 0))
)
function barHeight(v, max) {
  const pct = Math.round(((v || 0) / max) * 100)
  return `${Math.max(pct, v > 0 ? 2 : 0)}%`
}
</script>

