<template>
  <Layout>
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900">Advertisement Analytics</h1>
      <p class="text-gray-600 mt-2">Coming Soon</p>
      <!-- <p class="text-gray-600 mt-2">Monitor your advertisement performance and engagement</p> -->
    </div>

    <!-- Stats Grid
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="bg-blue-500 w-12 h-12 rounded-lg flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-megaphone w-6 h-6 text-white" aria-hidden="true">
              <path d="m3 11 18-5v12L3 14v-3z"></path>
              <path d="m11.6 16.8 3.5 2.7 3.5-2.7"></path>
              <path d="m11.6 16.8 3.5 2.7v-6.2l-3.5-2.7"></path>
              <path d="m8.1 10.7 3.5 2.7 3.5-2.7"></path>
            </svg>
          </div>
        </div>
        <div class="text-2xl text-gray-900 mb-1">{{ stats.totalImpressions || 0 }}</div>
        <div class="text-sm text-gray-600">Total Impressions</div>
      </div>

      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="bg-green-500 w-12 h-12 rounded-lg flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-mouse-pointer-click w-6 h-6 text-white" aria-hidden="true">
              <path d="m9 9 5 12 1.774-5.226L21 14 9 9Z"></path>
              <path d="m16.071 16.071 4.243 4.243"></path>
              <path d="m7.188 2.239.777 2.897M5.346 7.478l-2.9-.777M13.95 4.05l-2.122 2.122m-5.657 5.656-2.12 2.122"></path>
            </svg>
          </div>
        </div>
        <div class="text-2xl text-gray-900 mb-1">{{ stats.totalClicks || 0 }}</div>
        <div class="text-sm text-gray-600">Total Clicks</div>
      </div>

      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="bg-yellow-500 w-12 h-12 rounded-lg flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-percent w-6 h-6 text-white" aria-hidden="true">
              <circle cx="19" cy="5" r="2"></circle>
              <circle cx="5" cy="19" r="2"></circle>
              <line x1="5" x2="19" y1="5" y2="19"></line>
            </svg>
          </div>
        </div>
        <div class="text-2xl text-gray-900 mb-1">{{ stats.clickThroughRate || '0.00' }}%</div>
        <div class="text-sm text-gray-600">Click-Through Rate</div>
      </div>

      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="bg-purple-500 w-12 h-12 rounded-lg flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-dollar-sign w-6 h-6 text-white" aria-hidden="true">
              <line x1="12" x2="12" y1="2" y2="22"></line>
              <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
            </svg>
          </div>
        </div>
        <div class="text-2xl text-gray-900 mb-1">${{ stats.totalSpent || '0.00' }}</div>
        <div class="text-sm text-gray-600">Total Spent</div>
      </div>
    </div>

    // Charts Section
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Impressions vs Clicks</h2>
        <div class="h-64 flex items-center justify-center text-gray-400">
          <p>Chart will be displayed here</p>
        </div>
      </div>

      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Active Advertisements</h2>
        <div class="space-y-4">
          <div v-for="ad in activeAds" :key="ad.id" class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-gray-100 rounded flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-image text-gray-400">
                  <rect width="18" height="18" x="3" y="3" rx="2" ry="2"></rect>
                  <circle cx="9" cy="9" r="2"></circle>
                  <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"></path>
                </svg>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-900">{{ ad.title }}</p>
                <p class="text-xs text-gray-500">{{ ad.impressions }} impressions</p>
              </div>
            </div>
            <span class="text-xs px-2 py-1 bg-green-100 text-green-800 rounded">{{ ad.status }}</span>
          </div>
          <div v-if="activeAds.length === 0" class="text-center py-8 text-gray-400">
            <p>No active advertisements</p>
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
      totalImpressions: 0,
      totalClicks: 0,
      clickThroughRate: '0.00',
      totalSpent: '0.00'
    })
  },
  activeAds: {
    type: Array,
    default: () => []
  }
})
</script>
