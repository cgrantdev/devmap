<template>
  <AdminLayout>
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-3xl text-slate-900 mb-2">Location Management</h1>
      <p class="text-slate-600">Manage all locations</p>
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
            placeholder="Search locations..."
            v-model="searchValue"
            @input="handleSearchInput"
            class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        <Link href="/admin/locations/create" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Add Location
        </Link>
      </div>
    </div>

    <!-- Locations Table -->
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
              <th class="px-6 py-3 text-left text-xs text-slate-500 uppercase tracking-wider">Location</th>
              <th class="px-6 py-3 text-left text-xs text-slate-500 uppercase tracking-wider">Products</th>
              <th class="px-6 py-3 text-left text-xs text-slate-500 uppercase tracking-wider">Vendors</th>
              <th class="px-6 py-3 text-left text-xs text-slate-500 uppercase tracking-wider">Created</th>
              <th class="px-6 py-3 text-left text-xs text-slate-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            <tr v-for="location in locations.data || []" :key="location.id" class="hover:bg-slate-50">
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  <span class="text-sm font-medium text-slate-900">{{ location.name }}</span>
                </div>
              </td>
              <td class="px-6 py-4">
                <span class="text-sm text-slate-900">{{ location.products_count || 0 }}</span>
              </td>
              <td class="px-6 py-4">
                <span class="text-sm text-slate-900">{{ location.vendor_settings_count || 0 }}</span>
              </td>
              <td class="px-6 py-4">
                <span class="text-sm text-slate-600">{{ location.created_at }}</span>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <Link :href="`/admin/locations/${location.id}/edit`" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </Link>
                  <button
                    @click="deleteLocation(location.id)"
                    :disabled="deleteForm.processing"
                    class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors disabled:opacity-50"
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

    <div v-if="!locations.data || locations.data.length === 0" class="bg-white rounded-lg border border-slate-200 p-6 text-center text-slate-500">
      No locations found.
    </div>

    <!-- Pagination -->
    <div v-if="locations.last_page > 1" class="mt-6 flex items-center justify-between bg-white rounded-lg border border-slate-200 p-4">
      <div class="text-sm text-slate-600">
        Showing {{ locations.from || 0 }} to {{ locations.to || 0 }} of {{ locations.total || 0 }} locations
      </div>
      <div class="flex gap-2">
        <button
          @click="goToPage(locations.current_page - 1)"
          :disabled="locations.current_page === 1"
          class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Previous
        </button>
        <button
          @click="goToPage(locations.current_page + 1)"
          :disabled="locations.current_page === locations.last_page"
          class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Next
        </button>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router, useForm, usePage } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import { useToast as useVueToastification } from 'vue-toastification'

// Only use toast for manual error messages
// Success messages are handled automatically by Layout component via flash messages
const toast = useVueToastification()
const toastError = (message) => toast.error(message, { timeout: 4000 })

const props = defineProps({
  locations: {
    type: Object,
    required: true
  }
})

const searchValue = ref('')

let searchTimeout = null

function handleSearchInput() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchData(1)
  }, 500)
}

function fetchData(page = props.locations?.current_page || 1) {
  router.get('/admin/locations', {
    page,
    per_page: props.locations?.per_page || 20,
    search: searchValue.value
  }, {
    preserveState: true,
    preserveScroll: true
  })
}

function goToPage(page) {
  if (page >= 1 && page <= props.locations.last_page) {
    fetchData(page)
  }
}

const deleteForm = useForm({
  _token: usePage().props.csrf_token
})

function deleteLocation(id) {
  if (confirm('Are you sure you want to delete this location?')) {
    deleteForm.delete(`/admin/locations/${id}`, {
      preserveScroll: true,
      onSuccess: () => {
        fetchData()
      },
      onError: (errors) => {
        console.error('Delete error:', errors)
        toastError('Failed to delete location. Please try again.')
      }
    })
  }
}
</script>
