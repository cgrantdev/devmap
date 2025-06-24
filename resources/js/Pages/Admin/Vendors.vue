<template>
  <AdminLayout>
    <div class="mb-8">
      <h1 class="text-3xl font-bold">Vendor Management</h1>
      <p class="text-gray-600 mt-2">Manage all registered vendors</p>
    </div>
    
    <div class="bg-white rounded-lg shadow">
      <div class="p-6 border-b">
        <h2 class="text-xl font-semibold">All Vendors</h2>
      </div>
      
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="vendor in vendors" :key="vendor.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-10 w-10">
                    <img v-if="vendor.settings?.logo" :src="vendor.settings.logo" alt="Logo" class="h-10 w-10 rounded-full object-cover">
                    <div v-else class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                      <span class="text-gray-600 font-semibold">{{ vendor.name.charAt(0).toUpperCase() }}</span>
                    </div>
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">{{ vendor.name }}</div>
                    <div class="text-sm text-gray-500">Joined {{ formatDate(vendor.created_at) }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ vendor.settings?.company_name || 'N/A' }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ vendor.email }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span v-if="vendor.settings?.status === 1" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                  Active
                </span>
                <span v-else class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                  Inactive
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                  {{ vendor.role }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <Link :href="`/vendor/${vendor.name.toLowerCase().replace(/\s+/g, '-')}`" target="_blank" class="text-indigo-600 hover:text-indigo-900 mr-4">
                  {{ vendor.settings?.status === 1 ? 'View Public Page' : 'Preview Public Page (Inactive)' }}
                </Link>
                <button @click="toggleStatus(vendor)" class="text-gray-600 hover:text-gray-900">
                  {{ vendor.settings?.status === 1 ? 'Deactivate' : 'Activate' }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <div v-if="vendors.length === 0" class="p-6 text-center text-gray-500">
        No vendors found.
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'

const props = defineProps({
  vendors: {
    type: Array,
    default: () => []
  }
})

const form = useForm({})

function formatDate(dateString) {
  return new Date(dateString).toLocaleDateString()
}

function toggleStatus(vendor) {
  form.post(`/admin/vendors/${vendor.id}/toggle-status`, {
    onSuccess: () => {
      // Refresh the page to show updated data
      window.location.reload()
    }
  })
}
</script> 