<template>
  <AdminLayout>
    <div class="mb-8">
      <h1 class="text-3xl text-slate-900 mb-2">Analytics</h1>
      <p class="text-slate-600">Track marketplace performance and metrics</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <div v-for="stat in stats" :key="stat.label" class="bg-white rounded-lg border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
            <component :is="stat.icon" class="w-6 h-6 text-blue-600" />
          </div>
          <div class="flex items-center gap-1 text-green-600 text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>
            {{ stat.change }}
          </div>
        </div>
        <div class="text-2xl text-slate-900 mb-1">{{ stat.value }}</div>
        <div class="text-sm text-slate-600">{{ stat.label }}</div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Top Vendors -->
      <div class="bg-white rounded-lg border border-slate-200 p-6">
        <h2 class="text-lg text-slate-900 mb-4">Top Performing Vendors</h2>
        <div class="space-y-4">
          <div v-for="(vendor, index) in topVendors" :key="vendor.id" class="flex items-center gap-4">
            <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-white">
              {{ index + 1 }}
            </div>
            <div class="flex-1">
              <div class="text-sm text-slate-900">{{ vendor.name }}</div>
              <div class="text-xs text-slate-500">{{ vendor.views }} views</div>
            </div>
            <div class="text-sm text-slate-900">{{ vendor.sales }}</div>
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
            <div class="w-8 h-8 bg-green-600 rounded-lg flex items-center justify-center text-white">
              {{ index + 1 }}
            </div>
            <div class="flex-1">
              <div class="text-sm text-slate-900">{{ product.name }}</div>
              <div class="text-xs text-slate-500">{{ product.views }} views • {{ product.clicks }} clicks</div>
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
      { label: 'Total Visits', value: '24,521', change: '+12.5%', icon: 'EyeIcon' },
      { label: 'Active Users', value: '8,492', change: '+8.1%', icon: 'UsersIcon' },
      { label: 'Vendor Signups', value: '127', change: '+15.3%', icon: 'StoreIcon' },
      { label: 'Products Listed', value: '1,842', change: '+3.2%', icon: 'PackageIcon' },
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

