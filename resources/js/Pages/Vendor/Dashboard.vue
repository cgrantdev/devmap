<template>
  <Layout>
    <div class="mb-8">
      <h1 class="text-3xl font-bold">Vendor Dashboard</h1>
      <p class="text-gray-600 mt-2">Welcome back, {{ $page.props.auth.user.name }}!</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
      <!-- Total Products -->
      <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex items-center">
          <div class="p-2 bg-blue-100 rounded-lg">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-600">Total Products</p>
            <p class="text-2xl font-semibold text-gray-900">{{ stats.totalProducts }}</p>
          </div>
        </div>
      </div>

      <!-- Public Page Status -->
      <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex items-center">
          <div class="p-2 rounded-lg" :class="{
            'bg-green-100': stats.publicPageStatus === 'Active',
            'bg-red-100': stats.publicPageStatus === 'Inactive',
            'bg-gray-100': stats.publicPageStatus === 'Not Configured'
          }">
            <svg class="w-6 h-6" :class="{
              'text-green-600': stats.publicPageStatus === 'Active',
              'text-red-600': stats.publicPageStatus === 'Inactive',
              'text-gray-600': stats.publicPageStatus === 'Not Configured'
            }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-9m0-9v9"></path>
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-600">Public Page</p>
            <p class="text-lg font-semibold" :class="{
              'text-green-600': stats.publicPageStatus === 'Active',
              'text-red-600': stats.publicPageStatus === 'Inactive',
              'text-gray-600': stats.publicPageStatus === 'Not Configured'
            }">{{ stats.publicPageStatus }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Profile Completion -->
    <div class="bg-white rounded-lg shadow mb-8">
      <div class="p-6 border-b">
        <h2 class="text-xl font-semibold">Profile Completion</h2>
        <p class="text-gray-600 mt-1">Complete your profile to improve your public page</p>
      </div>
      <div class="p-6">
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <div class="w-8 h-8 rounded-full flex items-center justify-center" :class="stats.hasCompanyName ? 'bg-green-100' : 'bg-gray-100'">
                <svg class="w-4 h-4" :class="stats.hasCompanyName ? 'text-green-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
              </div>
              <span class="ml-3 text-sm font-medium text-gray-900">Company Name</span>
            </div>
            <span class="text-sm" :class="stats.hasCompanyName ? 'text-green-600' : 'text-gray-500'">
              {{ stats.hasCompanyName ? 'Completed' : 'Not set' }}
            </span>
          </div>
          
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <div class="w-8 h-8 rounded-full flex items-center justify-center" :class="stats.hasLogo ? 'bg-green-100' : 'bg-gray-100'">
                <svg class="w-4 h-4" :class="stats.hasLogo ? 'text-green-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
              </div>
              <span class="ml-3 text-sm font-medium text-gray-900">Company Logo</span>
            </div>
            <span class="text-sm" :class="stats.hasLogo ? 'text-green-600' : 'text-gray-500'">
              {{ stats.hasLogo ? 'Uploaded' : 'Not uploaded' }}
            </span>
          </div>
          
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <div class="w-8 h-8 rounded-full flex items-center justify-center" :class="stats.hasBanner ? 'bg-green-100' : 'bg-gray-100'">
                <svg class="w-4 h-4" :class="stats.hasBanner ? 'text-green-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
              </div>
              <span class="ml-3 text-sm font-medium text-gray-900">Banner Image</span>
            </div>
            <span class="text-sm" :class="stats.hasBanner ? 'text-green-600' : 'text-gray-500'">
              {{ stats.hasBanner ? 'Uploaded' : 'Not uploaded' }}
            </span>
          </div>
        </div>
        
        <div class="mt-6">
          <Link href="/vendor/settings" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            Complete Profile
          </Link>
        </div>
      </div>
    </div>

    <!-- Recent Products -->
    <div class="bg-white rounded-lg shadow">
      <div class="p-6 border-b">
        <h2 class="text-xl font-semibold">Recent Products</h2>
        <p class="text-gray-600 mt-1">Your latest imported products</p>
      </div>
      <div class="p-6">
        <div v-if="recentProducts.length > 0" class="space-y-4">
          <div v-for="product in recentProducts" :key="product.id" class="flex items-center space-x-4 p-4 border rounded-lg">
            <div class="flex-shrink-0">
              <img v-if="product.image_url" :src="product.image_url" alt="Product" class="h-12 w-12 object-cover rounded" loading="lazy">
              <div v-else class="h-12 w-12 bg-gray-200 rounded flex items-center justify-center">
                <span class="text-gray-400 text-xs">No Image</span>
              </div>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 truncate">{{ product.name }}</p>
              <p class="text-sm text-gray-500">${{ product.price }}</p>
            </div>
            <div class="text-sm text-gray-500">
              {{ formatDate(product.created_at) }}
            </div>
          </div>
        </div>
        <div v-else class="text-center py-8">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">No products yet</h3>
          <p class="mt-1 text-sm text-gray-500">Get started by importing your first products.</p>
          <div class="mt-6">
            <Link href="/vendor/import" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
              </svg>
              Import Products
            </Link>
          </div>
        </div>
      </div>
    </div>
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
      publicPageStatus: 'Not Configured',
      hasCompanyName: false,
      hasLogo: false,
      hasBanner: false
    })
  },
  recentProducts: {
    type: Array,
    default: () => []
  },
  vendorSettings: {
    type: Object,
    default: null
  }
})

function formatDate(dateString) {
  return new Date(dateString).toLocaleDateString()
}
</script> 