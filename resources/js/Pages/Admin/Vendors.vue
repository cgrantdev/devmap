<template>
  <AdminLayout>
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-3xl text-slate-900 mb-2">Vendor Management</h1>
      <p class="text-slate-600">Manage all vendors and their information</p>
    </div>

    <!-- Success Message -->
    <div v-if="$page.props.flash.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
      {{ $page.props.flash.success }}
    </div>
    
    <!-- Error Message -->
    <div v-if="$page.props.flash.error" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
      {{ $page.props.flash.error }}
    </div>

    <!-- Actions Bar -->
    <div class="bg-white rounded-lg border border-slate-200 p-4 mb-6">
      <div class="flex items-center justify-between gap-4">
        <div class="flex-1 relative">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input
            type="text"
            placeholder="Search vendors..."
            v-model="searchTerm"
            class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        <Link href="/admin/vendors/create" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Add Vendor
        </Link>
      </div>
    </div>

    <!-- Vendors Table -->
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
              <th class="px-6 py-3 text-left text-xs text-slate-500 uppercase tracking-wider">Vendor</th>
              <th class="px-6 py-3 text-left text-xs text-slate-500 uppercase tracking-wider">Location</th>
              <th class="px-6 py-3 text-left text-xs text-slate-500 uppercase tracking-wider">Rating</th>
              <th class="px-6 py-3 text-left text-xs text-slate-500 uppercase tracking-wider">Reviews</th>
              <th class="px-6 py-3 text-left text-xs text-slate-500 uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-left text-xs text-slate-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            <tr v-for="vendor in filteredVendors" :key="vendor.id" class="hover:bg-slate-50">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div v-if="vendor.settings?.logo" class="w-10 h-10 rounded-lg overflow-hidden flex-shrink-0">
                    <img :src="vendor.settings.logo" :alt="vendor.name" class="w-10 h-10 object-cover" />
                  </div>
                  <div v-else class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white flex-shrink-0">
                    {{ vendor.name.substring(0, 2).toUpperCase() }}
                  </div>
                  <div>
                    <div class="text-sm text-slate-900">{{ vendor.name }}</div>
                    <a
                      v-if="vendor.settings?.shop_url"
                      :href="vendor.settings.shop_url"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="text-xs text-blue-600 hover:underline flex items-center gap-1"
                    >
                      {{ (vendor.settings.shop_url || '').replace('https://', '').replace('http://', '') }}
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                      </svg>
                    </a>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-1 text-sm text-slate-600">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  {{ vendor.location || '-' }}
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-1">
                  <svg class="w-4 h-4 fill-yellow-400 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                  <span class="text-sm text-slate-900">{{ vendor.rating_average || vendor.rating || '0.0' }}</span>
                </div>
              </td>
              <td class="px-6 py-4">
                <span class="text-sm text-slate-900">{{ vendor.rating_count || vendor.reviewCount || 0 }}</span>
              </td>
              <td class="px-6 py-4">
                <div class="flex gap-2 flex-wrap">
                  <span v-if="vendor.is_active" class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs">
                    Active
                  </span>
                  <span v-else class="bg-slate-100 text-slate-700 px-2 py-1 rounded text-xs">
                    Inactive
                  </span>
                  <span v-if="vendor.settings?.top_vendor" class="bg-blue-100 text-blue-700 px-2 py-1 rounded text-xs">
                    Top Vendor
                  </span>
                  <span v-if="vendor.settings?.featured" class="bg-purple-100 text-purple-700 px-2 py-1 rounded text-xs">
                    Featured
                  </span>
                  <span v-if="vendor.settings?.is_partner" class="bg-indigo-100 text-indigo-700 px-2 py-1 rounded text-xs">
                    Partner
                  </span>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <Link :href="`/admin/vendors/${vendor.id}/edit`" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </Link>
                  <button
                    @click="deleteVendor(vendor)"
                    :disabled="form.processing"
                    class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                    title="Delete"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="filteredVendors.length === 0" class="bg-white rounded-lg border border-slate-200 p-6 text-center text-slate-500">
      No vendors found.
    </div>
  </AdminLayout>
</template>

<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import { ref, computed } from 'vue'
import { useToast as useVueToastification } from 'vue-toastification'

const props = defineProps({
  vendors: {
    type: Array,
    default: () => []
  }
})

// Only use toast for manual error messages
// Success messages are handled automatically by Layout component via flash messages
const toast = useVueToastification()
const toastError = (message) => toast.error(message, { timeout: 4000 })

const searchTerm = ref('')

const filteredVendors = computed(() => {
  if (!searchTerm.value) {
    return props.vendors
  }
  
  const term = searchTerm.value.toLowerCase()
  return props.vendors.filter(vendor =>
    vendor.name.toLowerCase().includes(term) ||
    (vendor.location && vendor.location.toLowerCase().includes(term)) ||
    (vendor.email && vendor.email.toLowerCase().includes(term))
  )
})

const form = useForm({
  _token: usePage().props.csrf_token
})

function deleteVendor(vendor) {
  if (confirm('Are you sure you want to delete this vendor?')) {
    form.delete(`/admin/vendors/${vendor.id}`, {
      onSuccess: () => {
        // Success toast will be shown automatically from flash message
      },
      onError: (errors) => {
        toastError('Failed to delete vendor. Please try again.')
        console.error(errors)
      }
    })
  }
}
</script> 