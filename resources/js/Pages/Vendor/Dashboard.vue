<template>
  <Layout>
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900">Vendor Dashboard</h1>
      <p class="text-gray-600 mt-2">Welcome back, {{ $page.props.auth.user.name }}!</p>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <Link href="/vendor/storefront-analytics" class="bg-white rounded-lg border border-gray-200 p-6 hover:border-blue-500 hover:shadow-lg transition-all">
        <div class="flex items-center gap-4">
          <div class="bg-blue-500 w-12 h-12 rounded-lg flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-store w-6 h-6 text-white">
              <path d="M15 21v-5a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v5"></path>
              <path d="M17.774 10.31a1.12 1.12 0 0 0-1.549 0 2.5 2.5 0 0 1-3.451 0 1.12 1.12 0 0 0-1.548 0 2.5 2.5 0 0 1-3.452 0 1.12 1.12 0 0 0-1.549 0 2.5 2.5 0 0 1-3.77-3.248l2.889-4.184A2 2 0 0 1 7 2h10a2 2 0 0 1 1.653.873l2.895 4.192a2.5 2.5 0 0 1-3.774 3.244"></path>
              <path d="M4 10.95V19a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8.05"></path>
            </svg>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-900">Storefront Analytics</h3>
            <p class="text-sm text-gray-600">Track your storefront performance</p>
          </div>
        </div>
      </Link>

      <Link href="/vendor/advertisement-analytics" class="bg-white rounded-lg border border-gray-200 p-6 hover:border-blue-500 hover:shadow-lg transition-all">
        <div class="flex items-center gap-4">
          <div class="bg-green-500 w-12 h-12 rounded-lg flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-megaphone w-6 h-6 text-white">
              <path d="m3 11 18-5v12L3 14v-3z"></path>
              <path d="m11.6 16.8 3.5 2.7 3.5-2.7"></path>
              <path d="m11.6 16.8 3.5 2.7v-6.2l-3.5-2.7"></path>
              <path d="m8.1 10.7 3.5 2.7 3.5-2.7"></path>
            </svg>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-900">Advertisement Analytics</h3>
            <p class="text-sm text-gray-600">Monitor ad performance</p>
          </div>
        </div>
      </Link>

      <Link href="/vendor/reviews" class="bg-white rounded-lg border border-gray-200 p-6 hover:border-blue-500 hover:shadow-lg transition-all">
        <div class="flex items-center gap-4">
          <div class="bg-yellow-500 w-12 h-12 rounded-lg flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-message-square w-6 h-6 text-white">
              <path d="M22 17a2 2 0 0 1-2 2H6.828a2 2 0 0 0-1.414.586l-2.202 2.202A.71.71 0 0 1 2 21.286V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2z"></path>
                </svg>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-900">Reviews</h3>
            <p class="text-sm text-gray-600">Manage customer reviews</p>
          </div>
        </div>
      </Link>
    </div>

    <!-- Summary Stats
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="text-2xl text-gray-900 mb-1">{{ stats.totalProducts || 0 }}</div>
        <div class="text-sm text-gray-600">Total Products</div>
      </div>
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="text-2xl text-gray-900 mb-1">{{ stats.totalViews || 0 }}</div>
        <div class="text-sm text-gray-600">Storefront Views</div>
      </div>
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="text-2xl text-gray-900 mb-1">{{ stats.totalReviews || 0 }}</div>
        <div class="text-sm text-gray-600">Total Reviews</div>
      </div>
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="text-2xl text-gray-900 mb-1">{{ stats.averageRating || '0.0' }}</div>
        <div class="text-sm text-gray-600">Average Rating</div>
      </div>
    </div> -->  
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
      totalViews: 0,
      totalReviews: 0,
      averageRating: '0.0'
    })
  }
})
</script> 
