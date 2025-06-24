<template>
  <AdminLayout>
    <div class="mb-8">
      <h1 class="text-3xl font-bold">Admin Dashboard</h1>
      <p class="text-gray-600 mt-2">Welcome to the admin panel</p>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <!-- Admin Stats Card -->
      <div class="bg-white p-6 rounded-lg shadow">
        <h2 class="text-xl font-semibold mb-4">Vendor Management</h2>
        <div class="space-y-2">
          <p>Total Vendors: {{ stats.totalVendors }}</p>
          <p>Active Vendors: {{ stats.activeVendors }}</p>
          <p>Inactive Vendors: {{ stats.inactiveVendors }}</p>
        </div>
      </div>

      <!-- Recent Activity Card -->
      <div class="bg-white p-6 rounded-lg shadow">
        <h2 class="text-xl font-semibold mb-4">Recent Activity</h2>
        <div class="space-y-2">
          <p v-for="activity in recentActivity" :key="activity.id" class="text-sm">
            {{ activity.description }}
          </p>
        </div>
      </div>

      <!-- Quick Actions Card -->
      <div class="bg-white p-6 rounded-lg shadow">
        <h2 class="text-xl font-semibold mb-4">Quick Actions</h2>
        <div class="space-y-2">
          <Link href="/admin/vendors" class="block w-full bg-gray-800 text-white px-4 py-2 rounded text-center hover:bg-gray-700">Manage Vendors</Link>
          <button class="w-full bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-700">View Reports</button>
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
      inactiveVendors: 0
    })
  }
})

const recentActivity = []
</script> 