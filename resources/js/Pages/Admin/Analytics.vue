<template>
  <AdminLayout>
    <div class="mb-8">
      <h1 class="text-3xl text-gray-900 mb-2">Analytics</h1>
      <p class="text-gray-600">Track marketplace performance and metrics</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
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

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
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
              <div class="text-xs text-gray-500">{{ vendor.views }}</div>
            </div>
            <div class="text-sm text-gray-900">{{ vendor.sales }}</div>
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
              <div class="text-xs text-gray-500">{{ product.views }} • {{ product.clicks }}</div>
            </div>
          </div>
          <div v-if="topProducts.length === 0" class="text-sm text-gray-500 text-center py-4">
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

