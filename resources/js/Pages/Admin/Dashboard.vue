<template>
  <div class="container mx-auto max-w-6xl py-8">
    <div class="flex justify-between items-center mb-8">
      <h1 class="text-3xl font-bold">Admin Dashboard</h1>
      <form @submit.prevent="logout">
        <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded">Logout</button>
      </form>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <!-- Admin Stats Card -->
      <div class="bg-white p-6 rounded-lg shadow">
        <h2 class="text-xl font-semibold mb-4">Vendor Management</h2>
        <div class="space-y-2">
          <p>Total Vendors: {{ stats.totalVendors }}</p>
          <p>Active Vendors: {{ stats.activeVendors }}</p>
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
          <button class="w-full bg-gray-800 text-white px-4 py-2 rounded">Manage Vendors</button>
          <button class="w-full bg-gray-800 text-white px-4 py-2 rounded">View Reports</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useForm, usePage } from '@inertiajs/vue3'

const form = useForm({
  _token: usePage().props.csrf_token
})

// Mock data - replace with actual data from your backend
const stats = {
  totalVendors: 0,
  activeVendors: 0
}

const recentActivity = []

function logout() {
  form.post('/admin/logout')
}
</script> 