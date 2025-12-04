<template>
  <AdminLayout>
    <div class="mb-8 flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold">Vendor Management</h1>
        <p class="text-gray-600 mt-2">Manage all registered vendors</p>
      </div>
      <Link href="/admin/vendors/create" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-medium transition-colors shadow-sm">
        + New Vendor
      </Link>
    </div>
    
    <!-- Success Message -->
    <div v-if="$page.props.flash.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
      {{ $page.props.flash.success }}
    </div>
    
    <!-- Error Message -->
    <div v-if="$page.props.flash.error" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
      {{ $page.props.flash.error }}
    </div>
    
    <div class="bg-white rounded-lg shadow">
      <div class="p-6">
        <h2 class="text-xl font-semibold">All Vendors</h2>
      </div>

      <div class="flex items-center gap-4 mb-4 px-6">
        <span>search value: </span>
        <input type="text" v-model="searchValue" class="border rounded px-3 py-2">
      </div>

      <div class="overflow-x-auto px-6">
        <EasyDataTable
          :headers="headers"
          :items="vendors"
          :rows-per-page="10"
          :search-field="searchField"
          :search-value="searchValue"
          theme-color="#6366f1"
        >
          <template #item-name="{ name, settings, created_at }">
            <div class="flex items-center px-6 py-4 whitespace-nowrap">
                <div class="flex-shrink-0 h-10 w-10">
                  <img v-if="settings?.logo" :src="settings.logo" alt="Logo" class="h-10 w-10 rounded-full object-cover" loading="lazy">
                  <div v-else class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                    <span class="text-gray-600 font-semibold">{{ name.charAt(0).toUpperCase() }}</span>
                  </div>
                </div>
                <div class="ml-4">
                  <div class="text-sm font-medium text-gray-900">{{ name }}</div>
                  <div class="text-sm text-gray-500">Joined {{ formatDate(created_at) }}</div>
                </div>
              </div>
          </template>
          <template #item-email="{ email }">
            <div class="text-sm text-gray-900 px-6 py-4 whitespace-nowrap">{{ email }}</div>
          </template>
          <template #item-status="item">
            <span v-if="item.is_active" class="inline-flex px-3 py-1 text-sm font-medium rounded-full bg-green-500 text-white">
              Active
            </span>
            <span v-else class="inline-flex px-3 py-1 text-sm font-medium rounded-full bg-gray-300 text-gray-700">
              Inactive
            </span>
          </template>
          <template #item-actions="item">
            <div class="flex items-center gap-3 px-6 py-4">
              <a :href="`/brand/${item.slug}/products`" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:text-blue-800 font-medium">
                View Public Page
              </a>
              <Link :href="`/admin/vendors/${item.id}/edit`" class="text-orange-600 hover:text-orange-800 font-medium">Edit</Link>
              <button @click="toggleStatus(item)" :disabled="form.processing"
                class="px-4 py-2 rounded bg-red-600 text-white hover:bg-red-700 font-medium transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                {{ form.processing ? 'Updating...' : (item.is_active ? 'Deactivate' : 'Activate') }}
              </button>
              <button @click="deleteVendor(item)" :disabled="form.processing" 
                class="px-4 py-2 rounded bg-red-600 text-white hover:bg-red-700 font-medium transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                Delete
              </button>
            </div>
          </template>
        </EasyDataTable>        
      </div>
      
      <div v-if="vendors.length === 0" class="p-6 text-center text-gray-500">
        No vendors found.
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import { ref, computed } from 'vue'
import EasyDataTable from 'vue3-easy-data-table'
import 'vue3-easy-data-table/dist/style.css';

const props = defineProps({
  vendors: {
    type: Array,
    default: () => []
  }
})

const headers = [
  { text: 'Vendor', value: 'name' },
  { text: 'Email', value: 'email' },
  { text: 'Status', value: 'status' },
  { text: 'Actions', value: 'actions' }
]

const searchField = ["name", "email"];
const searchValue = ref("");

const form = useForm({
  _token: usePage().props.csrf_token
})

function formatDate(dateString) {
  // Format: 12/3/2025 (M/D/YYYY)
  const date = new Date(dateString)
  const month = date.getMonth() + 1
  const day = date.getDate()
  const year = date.getFullYear()
  return `${month}/${day}/${year}`
}

function toggleStatus(vendor) {
  form.post(`/admin/vendors/${vendor.id}/toggle-status`, {
    onSuccess: () => {
      // The page will be refreshed with updated data from the server
      console.log('Status updated successfully')
    },
    onError: (errors) => {
      console.error('Error updating status:', errors)
    }
  })
}


function deleteVendor(vendor) {
  if (confirm(`Are you sure you want to delete vendor '${vendor.name}'? This cannot be undone.`)) {
    form.delete(`/admin/vendors/${vendor.id}`, {
      onSuccess: () => {
        // Success message will be shown via flash
      },
      onError: (errors) => {
        alert('Failed to delete vendor.');
        console.error(errors);
      }
    })
  }
}
</script> 