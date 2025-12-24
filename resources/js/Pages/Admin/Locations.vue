<template>
  <AdminLayout>
    <div class="mb-8 flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold">Location Management</h1>
        <p class="text-gray-600 mt-2">Manage all locations</p>
      </div>
      <Link href="/admin/locations/create" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 font-medium transition-colors">
        Create New Location
      </Link>
    </div>
    
    <!-- Flash messages are now handled by toast notifications -->
    
    <div class="bg-white rounded-lg shadow">
      <div class="p-6">
        <h2 class="text-xl font-semibold">All Locations</h2>
      </div>

      <div class="flex items-center gap-4 mb-4 px-6">
        <span>search value: </span>
        <input type="text" v-model="searchValue" @input="handleSearchInput" class="border rounded px-3 py-2">
      </div>

      <div class="overflow-x-auto px-6 pb-6">
        <EasyDataTable
          :headers="headers"
          :items="locations.data || []"
          :search-field="searchField"
          :search-value="searchValue"
          :server-items-length="locations.total || 0"
          :server-options="serverOptions"
          @update:server-options="handleServerOptionsChange"
          @update:search-value="handleSearchChange"
          :loading="loading"
          server
          table-class-name="customize-table"
          header-text-direction="left"
          body-text-direction="left"
        >
          <template #item-name="{ name }">
            <div class="text-sm font-medium text-slate-800">{{ name }}</div>
          </template>
          <template #item-products_count="{ products_count }">
            <div class="text-sm text-slate-800">{{ products_count || 0 }}</div>
          </template>
          <template #item-vendor_settings_count="{ vendor_settings_count }">
            <div class="text-sm text-slate-800">{{ vendor_settings_count || 0 }}</div>
          </template>
          <template #item-created_at="{ created_at }">
            <div class="text-sm text-slate-600">{{ created_at }}</div>
          </template>
          <template #item-actions="{ id }">
            <div class="flex gap-2">
              <Link :href="`/admin/locations/${id}/edit`" class="text-blue-600 hover:text-blue-800 hover:underline transition-colors duration-150">
                Edit
              </Link>
              <button
                @click="deleteLocation(id)"
                class="text-red-600 hover:text-red-800 hover:underline transition-colors duration-150"
              >
                Delete
              </button>
            </div>
          </template>
        </EasyDataTable>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import EasyDataTable from 'vue3-easy-data-table'
import 'vue3-easy-data-table/dist/style.css'

const props = defineProps({
  locations: {
    type: Object,
    required: true
  }
})

const searchValue = ref('')
const searchField = ref('name')
const loading = ref(false)

const serverOptions = ref({
  page: props.locations.current_page || 1,
  rowsPerPage: props.locations.per_page || 20,
  sortBy: 'name',
  sortType: 'asc'
})

const headers = [
  { text: 'ID', value: 'id', sortable: true, width: 80 },
  { text: 'Name', value: 'name', sortable: true },
  { text: 'Products', value: 'products_count', sortable: true, width: 120 },
  { text: 'Vendors', value: 'vendor_settings_count', sortable: true, width: 120 },
  { text: 'Created At', value: 'created_at', sortable: true, width: 180 },
  { text: 'Actions', value: 'actions', sortable: false, width: 150 }
]

const handleSearchInput = () => {
  loading.value = true
  router.get('/admin/locations', {
    search: searchValue.value,
    page: 1,
    per_page: serverOptions.value.rowsPerPage,
    sort_by: serverOptions.value.sortBy,
    sort_type: serverOptions.value.sortType
  }, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loading.value = false
    }
  })
}

const handleSearchChange = (value) => {
  searchValue.value = value
  handleSearchInput()
}

const handleServerOptionsChange = (options) => {
  serverOptions.value = options
  loading.value = true
  
  router.get('/admin/locations', {
    search: searchValue.value,
    page: options.page,
    per_page: options.rowsPerPage,
    sort_by: options.sortBy,
    sort_type: options.sortType
  }, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loading.value = false
    }
  })
}

const deleteLocation = (id) => {
  if (confirm('Are you sure you want to delete this location?')) {
    router.delete(`/admin/locations/${id}`, {
      preserveScroll: true,
      onSuccess: () => {
        // Reload to refresh the list
        router.reload({ only: ['locations'] })
      }
    })
  }
}

onMounted(() => {
  // Initialize server options from props if available
  if (props.locations.current_page) {
    serverOptions.value.page = props.locations.current_page
  }
  if (props.locations.per_page) {
    serverOptions.value.rowsPerPage = props.locations.per_page
  }
})
</script>

