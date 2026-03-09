<template>
  <Layout>
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900">Storefront Analytics</h1>
      <p class="text-gray-600 mt-2">Coming Soon</p>
      <!-- <p class="text-gray-600 mt-2">Track your storefront performance and visitor metrics</p> -->
    </div>

    <!-- Stats Grid 
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="bg-blue-500 w-12 h-12 rounded-lg flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-eye w-6 h-6 text-white" aria-hidden="true">
              <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path>
              <circle cx="12" cy="12" r="3"></circle>
            </svg>
          </div>
        </div>
        <div class="text-2xl text-gray-900 mb-1">{{ stats.totalViews || 0 }}</div>
        <div class="text-sm text-gray-600">Total Page Views</div>
      </div>

      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="bg-green-500 w-12 h-12 rounded-lg flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-users w-6 h-6 text-white" aria-hidden="true">
              <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
              <circle cx="9" cy="7" r="4"></circle>
              <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
          </div>
        </div>
        <div class="text-2xl text-gray-900 mb-1">{{ stats.uniqueVisitors || 0 }}</div>
        <div class="text-sm text-gray-600">Unique Visitors</div>
      </div>

      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="bg-yellow-500 w-12 h-12 rounded-lg flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-mouse-pointer-click w-6 h-6 text-white" aria-hidden="true">
              <path d="m9 9 5 12 1.774-5.226L21 14 9 9Z"></path>
              <path d="m16.071 16.071 4.243 4.243"></path>
              <path d="m7.188 2.239.777 2.897M5.346 7.478l-2.9-.777M13.95 4.05l-2.122 2.122m-5.657 5.656-2.12 2.122"></path>
            </svg>
          </div>
        </div>
        <div class="text-2xl text-gray-900 mb-1">{{ stats.productClicks || 0 }}</div>
        <div class="text-sm text-gray-600">Product Clicks</div>
      </div>

      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="bg-purple-500 w-12 h-12 rounded-lg flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clock w-6 h-6 text-white" aria-hidden="true">
              <circle cx="12" cy="12" r="10"></circle>
              <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
          </div>
        </div>
        <div class="text-2xl text-gray-900 mb-1">{{ stats.avgSessionDuration || '0:00' }}</div>
        <div class="text-sm text-gray-600">Avg. Session Duration</div>
      </div>
    </div>

    // Charts Section 
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Page Views Over Time</h2>
        <div class="h-64 flex items-center justify-center text-gray-400">
          <p>Chart will be displayed here</p>
        </div>
      </div>

      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Top Products</h2>
        <div class="space-y-4">
          <div v-for="product in topProducts" :key="product.id" class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-gray-100 rounded flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-package text-gray-400">
                  <path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"></path>
                </svg>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">{{ product.name }}</p>
                <p class="text-xs text-gray-500">{{ product.views }} views</p>
              </div>
            </div>
          </div>
          <div v-if="topProducts.length === 0" class="text-center py-8 text-gray-400">
            <p>No product views yet</p>
          </div>
        </div>
      </div>
    </div> -->
  </Layout>
</template>

<script setup>
import Layout from './Layout.vue'

const props = defineProps({
  stats: {
    type: Object,
    default: () => ({
      totalViews: 0,
      uniqueVisitors: 0,
      productClicks: 0,
      avgSessionDuration: '0:00'
    })
  },
  topProducts: {
    type: Array,
    default: () => []
  }
})
</script>
