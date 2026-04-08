<template>
  <Layout>
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900">Storefront Analytics</h1>
      <p class="text-gray-600 mt-2">Real-time outbound click performance for your products</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="text-sm text-gray-600 mb-2">Total Clicks (all time)</div>
        <div class="text-3xl font-bold text-gray-900">{{ formatNumber(stats.totalClicks) }}</div>
      </div>
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="text-sm text-gray-600 mb-2">Clicks (last 30 days)</div>
        <div class="text-3xl font-bold text-gray-900">{{ formatNumber(stats.clicks30d) }}</div>
      </div>
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="text-sm text-gray-600 mb-2">Clicks (last 7 days)</div>
        <div class="text-3xl font-bold text-gray-900">{{ formatNumber(stats.clicks7d) }}</div>
      </div>
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="text-sm text-gray-600 mb-2">Unique Visitors (30d)</div>
        <div class="text-3xl font-bold text-gray-900">{{ formatNumber(stats.uniqueVisitors30d) }}</div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
      <!-- Clicks over time (simple sparkline-style bar chart) -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Clicks Over Time (30d)</h2>
        <div v-if="clicksByDay.length > 0" class="flex items-end gap-1 h-48">
          <div
            v-for="day in clicksByDay"
            :key="day.day"
            class="flex-1 bg-blue-500 hover:bg-blue-600 rounded-t transition-colors relative group"
            :style="{ height: barHeight(day.clicks) + '%' }"
            :title="`${day.day}: ${day.clicks} clicks`"
          >
            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 hidden group-hover:block bg-gray-900 text-white text-xs rounded px-2 py-1 whitespace-nowrap">
              {{ day.day }}: {{ day.clicks }}
            </div>
          </div>
        </div>
        <div v-else class="h-48 flex items-center justify-center text-gray-400">
          No click data yet
        </div>
      </div>

      <!-- Top Products -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Top Products (30d)</h2>
        <div class="space-y-3">
          <div
            v-for="(product, index) in topProducts"
            :key="product.id"
            class="flex items-center justify-between p-3 border border-gray-200 rounded-lg"
          >
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-white text-sm font-semibold">
                {{ index + 1 }}
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">{{ product.name }}</p>
                <p class="text-xs text-gray-500">{{ formatNumber(product.clicks) }} clicks</p>
              </div>
            </div>
          </div>
          <div v-if="topProducts.length === 0" class="text-center py-8 text-gray-400">
            No product clicks yet
          </div>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import Layout from './Layout.vue'

const props = defineProps({
  stats: {
    type: Object,
    default: () => ({
      totalClicks: 0,
      clicks30d: 0,
      clicks7d: 0,
      uniqueVisitors30d: 0,
    }),
  },
  topProducts: {
    type: Array,
    default: () => [],
  },
  clicksByDay: {
    type: Array,
    default: () => [],
  },
})

const formatNumber = (n) => new Intl.NumberFormat().format(n ?? 0)

const barHeight = (clicks) => {
  const max = Math.max(...props.clicksByDay.map((d) => d.clicks), 1)
  return Math.max((clicks / max) * 100, 2)
}
</script>
