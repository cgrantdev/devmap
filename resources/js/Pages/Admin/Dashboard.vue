<template>
  <AdminLayout>
    <div class="mb-8">
      <h1 class="text-3xl font-normal text-slate-700 mb-2">Dashboard Overview</h1>
      <p class="text-slate-500">Welcome back! Here's what's happening with your marketplace.</p>
    </div>
    
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <div class="bg-white rounded-lg border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
          </div>
          <span class="text-sm text-green-600">+12%</span>
        </div>
        <div class="text-2xl text-slate-900 mb-1">{{ stats.totalVendors || 0 }}</div>
        <div class="text-sm text-slate-600">Total Vendors</div>
        <div class="mt-2 text-xs text-slate-500">
          <span class="text-green-600">{{ stats.activeVendors || 0 }} active</span>
          <span class="mx-1">•</span>
          <span class="text-slate-400">{{ stats.inactiveVendors || 0 }} inactive</span>
        </div>
      </div>

      <div class="bg-white rounded-lg border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
          </div>
          <span class="text-sm text-green-600">+8%</span>
        </div>
        <div class="text-2xl text-slate-900 mb-1">{{ stats.totalProducts }}</div>
        <div class="text-sm text-slate-600">Total Products</div>
      </div>

      <div class="bg-white rounded-lg border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-yellow-500 rounded-lg flex items-center justify-center">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
          </div>
          <span class="text-sm text-red-600">-5%</span>
        </div>
        <div class="text-2xl text-slate-900 mb-1">{{ stats.pendingReviews }}</div>
        <div class="text-sm text-slate-600">Pending Reviews</div>
      </div>

      <div class="bg-white rounded-lg border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
          </div>
          <span class="text-sm text-green-600">+18%</span>
        </div>
        <div class="text-2xl text-slate-900 mb-1">{{ stats.totalUsers }}</div>
        <div class="text-sm text-slate-600">Active Users</div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Recent Activity -->
      <div class="bg-white rounded-lg border border-slate-200 p-6">
        <h2 class="text-lg text-slate-900 mb-4">Recent Activity</h2>
        <div class="space-y-4">
          <div v-for="activity in recentActivity" :key="activity.id" class="flex items-start gap-3 pb-4 border-b border-slate-100 last:border-0">
            <div class="w-2 h-2 bg-blue-500 rounded-full mt-2 flex-shrink-0"></div>
            <div class="flex-1 min-w-0">
              <p class="text-sm text-slate-900 font-medium">{{ activity.description }}</p>
              <p class="text-xs text-slate-500 mt-0.5">{{ activity.vendor }}</p>
            </div>
            <span class="text-xs text-slate-500 whitespace-nowrap ml-2">{{ activity.time }}</span>
          </div>
          <div v-if="recentActivity.length === 0" class="text-sm text-slate-500 text-center py-4">
            No recent activity
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="bg-white rounded-lg border border-slate-200 p-6">
        <h2 class="text-lg text-slate-900 mb-4">Quick Actions</h2>
        <div class="grid grid-cols-2 gap-3">
          <Link href="/admin/vendors" class="p-4 border-2 border-slate-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-colors text-left">
            <svg class="w-6 h-6 text-blue-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            <div class="text-sm text-slate-900">Add Vendor</div>
          </Link>
          <Link href="/admin/products" class="p-4 border-2 border-slate-200 rounded-lg hover:border-green-500 hover:bg-green-50 transition-colors text-left">
            <svg class="w-6 h-6 text-green-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
            <div class="text-sm text-slate-900">Add Product</div>
          </Link>
          <Link href="/admin/deals" class="p-4 border-2 border-slate-200 rounded-lg hover:border-yellow-500 hover:bg-yellow-50 transition-colors text-left">
            <svg class="w-6 h-6 text-yellow-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
            </svg>
            <div class="text-sm text-slate-900">Create Deal</div>
          </Link>
          <Link href="/admin/banners" class="p-4 border-2 border-slate-200 rounded-lg hover:border-purple-500 hover:bg-purple-50 transition-colors text-left">
            <svg class="w-6 h-6 text-purple-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <div class="text-sm text-slate-900">Add Banner</div>
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