<template>
  <AdminLayout>
    <div class="mb-8">
      <h1 class="text-3xl text-gray-900 mb-2">Dashboard Overview</h1>
      <p class="text-gray-600">Welcome back! Here's what's happening with your marketplace.</p>
    </div>
    
    <!-- Stats Grid -->
    <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="bg-blue-500 w-12 h-12 rounded-lg flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-store w-6 h-6 text-white" aria-hidden="true">
              <path d="M15 21v-5a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v5" />
              <path d="M17.774 10.31a1.12 1.12 0 0 0-1.549 0 2.5 2.5 0 0 1-3.451 0 1.12 1.12 0 0 0-1.548 0 2.5 2.5 0 0 1-3.452 0 1.12 1.12 0 0 0-1.549 0 2.5 2.5 0 0 1-3.77-3.248l2.889-4.184A2 2 0 0 1 7 2h10a2 2 0 0 1 1.653.873l2.895 4.192a2.5 2.5 0 0 1-3.774 3.244" />
              <path d="M4 10.95V19a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8.05" />
            </svg>
          </div>
          <span class="text-sm text-green-600">+12%</span>
        </div>
        <div class="text-2xl text-gray-900 mb-1">{{ stats.totalVendors || 0 }}</div>
        <div class="text-sm text-gray-600">Total Vendors</div>        
      </div>

      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="bg-green-500 w-12 h-12 rounded-lg flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-package w-6 h-6 text-white" aria-hidden="true">
              <path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z" />
              <path d="M12 22V12" />
              <polyline points="3.29 7 12 12 20.71 7"></polyline>
              <path d="m7.5 4.27 9 5.15" />
            </svg>
          </div>
          <span class="text-sm text-green-600">+8%</span>
        </div>
        <div class="text-2xl text-gray-900 mb-1">{{ stats.totalProducts }}</div>
        <div class="text-sm text-gray-600">Total Products</div>
      </div>

      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="bg-yellow-500 w-12 h-12 rounded-lg flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-message-square w-6 h-6 text-white" aria-hidden="true">
              <path d="M22 17a2 2 0 0 1-2 2H6.828a2 2 0 0 0-1.414.586l-2.202 2.202A.71.71 0 0 1 2 21.286V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2z" />
            </svg>
          </div>
          <span class="text-sm text-green-600">-5%</span>
        </div>
        <div class="text-2xl text-gray-900 mb-1">{{ stats.pendingReviews }}</div>
        <div class="text-sm text-gray-600">Pending Reviews</div>
      </div>

      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="bg-purple-500 w-12 h-12 rounded-lg flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-users w-6 h-6 text-white" aria-hidden="true">
              <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
              <path d="M16 3.128a4 4 0 0 1 0 7.744" />
              <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
              <circle cx="9" cy="7" r="4"></circle>
            </svg>
          </div>
          <span class="text-sm text-green-600">+18%</span>
        </div>
        <div class="text-2xl text-gray-900 mb-1">{{ stats.totalUsers }}</div>
        <div class="text-sm text-gray-600">Active Users</div>
      </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-2 gap-6">
      <!-- Recent Activity -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg text-gray-900 mb-4">Recent Activity</h2>
        <div class="space-y-4">
          <div v-for="activity in recentActivity" :key="activity.id" class="flex items-start gap-3 pb-4 border-b border-gray-100 last:border-0">
            <div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
            <div class="flex-1">
              <p class="text-sm text-gray-900">{{ activity.description }}</p>
              <p class="text-xs text-gray-500">{{ activity.vendor }}</p>
            </div>
            <span class="text-xs text-gray-500">{{ activity.time }}</span>
          </div>
          <div v-if="recentActivity.length === 0" class="text-sm text-gray-500 text-center py-4">
            No recent activity
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-lg text-gray-900 mb-4">Quick Actions</h2>
        <div class="grid grid-cols-2 gap-3">
          <Link href="/admin/vendors" class="p-4 border-2 border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-colors text-left">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-store w-6 h-6 text-blue-600 mb-2" aria-hidden="true">
              <path d="M15 21v-5a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v5" />
              <path d="M17.774 10.31a1.12 1.12 0 0 0-1.549 0 2.5 2.5 0 0 1-3.451 0 1.12 1.12 0 0 0-1.548 0 2.5 2.5 0 0 1-3.452 0 1.12 1.12 0 0 0-1.549 0 2.5 2.5 0 0 1-3.77-3.248l2.889-4.184A2 2 0 0 1 7 2h10a2 2 0 0 1 1.653.873l2.895 4.192a2.5 2.5 0 0 1-3.774 3.244" />
              <path d="M4 10.95V19a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8.05" />
            </svg>
            <div class="text-sm text-gray-900">Add Vendor</div>
          </Link>
          <Link href="/admin/products" class="p-4 border-2 border-gray-200 rounded-lg hover:border-green-500 hover:bg-green-50 transition-colors text-left">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-package w-6 h-6 text-green-600 mb-2" aria-hidden="true">
              <path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z" />
              <path d="M12 22V12" />
              <polyline points="3.29 7 12 12 20.71 7"></polyline>
              <path d="m7.5 4.27 9 5.15" />
            </svg>
            <div class="text-sm text-gray-900">Add Product</div>
          </Link>
          <Link href="/admin/deals" class="p-4 border-2 border-gray-200 rounded-lg hover:border-yellow-500 hover:bg-yellow-50 transition-colors text-left">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-tag w-6 h-6 text-yellow-600 mb-2" aria-hidden="true">
              <path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z" />
              <circle cx="7.5" cy="7.5" r=".5" fill="currentColor"></circle>
            </svg>
            <div class="text-sm text-gray-900">Create Deal</div>
          </Link>
          <Link href="/admin/banners" class="p-4 border-2 border-gray-200 rounded-lg hover:border-purple-500 hover:bg-purple-50 transition-colors text-left">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-image w-6 h-6 text-purple-600 mb-2" aria-hidden="true">
              <rect width="18" height="18" x="3" y="3" rx="2" ry="2"></rect>
              <circle cx="9" cy="9" r="2"></circle>
              <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21" />
            </svg>
            <div class="text-sm text-gray-900">Add Banner</div>
          </Link>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'

const props = defineProps({
  stats: {
    type: Object,
    default: () => ({
      totalVendors: 0,
      activeVendors: 0,
      inactiveVendors: 0,
      totalProducts: 0,
      pendingReviews: 0,
      totalUsers: 0
    })
  },
  recentActivity: {
    type: Array,
    default: () => []
  }
})
</script> 