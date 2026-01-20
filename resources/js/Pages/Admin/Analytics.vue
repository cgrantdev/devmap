<template>
  <AdminLayout>
    <div class="mb-8">
      <h1 class="text-3xl text-slate-900 mb-2">Analytics</h1>
      <p class="text-slate-600">Track marketplace performance and metrics</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <!-- Total Visits -->
      <div class="bg-white rounded-lg border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
          </div>
          <div class="flex items-center gap-1 text-green-600 text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>
            {{ stats[0]?.change }}
          </div>
        </div>
        <div class="text-2xl text-slate-900 mb-1">{{ stats[0]?.value }}</div>
        <div class="text-sm text-slate-600">{{ stats[0]?.label }}</div>
      </div>

      <!-- Active Users -->
      <div class="bg-white rounded-lg border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
          </div>
          <div class="flex items-center gap-1 text-green-600 text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>
            {{ stats[1]?.change }}
          </div>
        </div>
        <div class="text-2xl text-slate-900 mb-1">{{ stats[1]?.value }}</div>
        <div class="text-sm text-slate-600">{{ stats[1]?.label }}</div>
      </div>

      <!-- Vendor Signups -->
      <div class="bg-white rounded-lg border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
          </div>
          <div class="flex items-center gap-1 text-green-600 text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>
            {{ stats[2]?.change }}
          </div>
        </div>
        <div class="text-2xl text-slate-900 mb-1">{{ stats[2]?.value }}</div>
        <div class="text-sm text-slate-600">{{ stats[2]?.label }}</div>
      </div>

      <!-- Products Listed -->
      <div class="bg-white rounded-lg border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
          </div>
          <div class="flex items-center gap-1 text-green-600 text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>
            {{ stats[3]?.change }}
          </div>
        </div>
        <div class="text-2xl text-slate-900 mb-1">{{ stats[3]?.value }}</div>
        <div class="text-sm text-slate-600">{{ stats[3]?.label }}</div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Top Vendors -->
      <div class="bg-white rounded-lg border border-slate-200 p-6">
        <h2 class="text-lg text-slate-900 mb-4">Top Performing Vendors</h2>
        <div class="space-y-4">
          <div v-for="(vendor, index) in topVendors" :key="vendor.id" class="flex items-center gap-4">
            <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-white font-semibold">
              {{ index + 1 }}
            </div>
            <div class="flex-1">
              <div class="text-sm font-medium text-slate-900">{{ vendor.name }}</div>
              <div class="text-xs text-slate-500">{{ vendor.views }}</div>
            </div>
            <div class="text-sm font-semibold text-slate-900">{{ vendor.sales }}</div>
          </div>
          <div v-if="topVendors.length === 0" class="text-sm text-slate-500 text-center py-4">
            No vendor data available
          </div>
        </div>
      </div>

      <!-- Top Products -->
      <div class="bg-white rounded-lg border border-slate-200 p-6">
        <h2 class="text-lg text-slate-900 mb-4">Top Viewed Products</h2>
        <div class="space-y-4">
          <div v-for="(product, index) in topProducts" :key="product.id" class="flex items-center gap-4">
            <div class="w-8 h-8 bg-green-600 rounded-lg flex items-center justify-center text-white font-semibold">
              {{ index + 1 }}
            </div>
            <div class="flex-1">
              <div class="text-sm font-medium text-slate-900">{{ product.name }}</div>
              <div class="text-xs text-slate-500">{{ product.views }} • {{ product.clicks }}</div>
            </div>
          </div>
          <div v-if="topProducts.length === 0" class="text-sm text-slate-500 text-center py-4">
            No product data available
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from './Layout.vue'

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
  }
})
</script>

