<template>
  <div class="container mx-auto max-w-6xl py-8">
    <div class="flex justify-between items-center mb-8">
      <h1 class="text-3xl font-bold">Vendor Dashboard</h1>
      <form @submit.prevent="logout">
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700" :disabled="form.processing">
          {{ form.processing ? 'Logging out...' : 'Logout' }}
        </button>
      </form>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <!-- Vendor Stats Card -->
      <div class="bg-white p-6 rounded-lg shadow">
        <h2 class="text-xl font-semibold mb-4">Business Overview</h2>
        <div class="space-y-2">
          <p>Total Orders: {{ stats.totalOrders }}</p>
          <p>Revenue: ${{ stats.revenue }}</p>
        </div>
      </div>

      <!-- Recent Orders Card -->
      <div class="bg-white p-6 rounded-lg shadow">
        <h2 class="text-xl font-semibold mb-4">Recent Orders</h2>
        <div class="space-y-2">
          <p v-for="order in recentOrders" :key="order.id" class="text-sm">
            Order #{{ order.id }} - ${{ order.amount }}
          </p>
        </div>
      </div>

      <!-- Quick Actions Card -->
      <div class="bg-white p-6 rounded-lg shadow">
        <h2 class="text-xl font-semibold mb-4">Quick Actions</h2>
        <div class="space-y-2">
          <button class="w-full bg-blue-600 text-white px-4 py-2 rounded">Manage Products</button>
          <button class="w-full bg-blue-600 text-white px-4 py-2 rounded">View Orders</button>
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
  totalOrders: 0,
  revenue: 0
}

const recentOrders = []

function logout() {
  form.post('/logout', {
    onSuccess: () => {
      window.location.href = '/login'
    }
  })
}
</script> 