<template>
  <AdminLayout>
    <div class="mb-8">
      <h1 class="text-3xl font-normal text-slate-700">Admin Dashboard</h1>
      <p class="text-slate-500 mt-2">Welcome to the admin panel</p>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <!-- Admin Stats Card -->
      <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
        <h2 class="text-xl font-medium mb-4 text-slate-700">Vendor Management</h2>
        <div class="space-y-2">
          <p class="text-slate-600">Total Vendors: <span class="font-medium text-slate-800">{{ stats.totalVendors }}</span></p>
          <p class="text-slate-600">Active Vendors: <span class="font-medium text-slate-800">{{ stats.activeVendors }}</span></p>
          <p class="text-slate-600">Inactive Vendors: <span class="font-medium text-slate-800">{{ stats.inactiveVendors }}</span></p>
        </div>
      </div>

      <!-- Recent Activity Card -->
      <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
        <h2 class="text-xl font-medium mb-4 text-slate-700">Recent Activity</h2>
        <div class="space-y-2">
          <p v-for="activity in recentActivity" :key="activity.id" class="text-sm text-slate-600">
            {{ activity.description }}
          </p>
        </div>
      </div>

      <!-- Quick Actions Card -->
      <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
        <h2 class="text-xl font-medium mb-4 text-slate-700">Quick Actions</h2>
        <div class="space-y-2">
          <Link href="/admin/vendors" class="block w-full bg-slate-700 text-white px-4 py-2.5 rounded-xl text-center hover:bg-slate-800 transition-all duration-200 font-medium">Manage Vendors</Link>
          <!-- <button class="w-full bg-slate-700 text-white px-4 py-2.5 rounded-xl hover:bg-slate-800 transition-all duration-200 font-medium">View Reports</button> -->
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