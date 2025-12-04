<template>
  <AdminLayout>
    <div class="mb-8 flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold">Category Management</h1>
        <p class="text-gray-600 mt-2">Manage all product categories</p>
      </div>
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
        <h2 class="text-xl font-semibold">All Categories</h2>
      </div>

      <div class="flex items-center gap-4 mb-4 px-6">
        <span>search value: </span>
        <input type="text" v-model="searchValue" @input="handleSearchInput" class="border rounded px-3 py-2">
      </div>

      <div class="overflow-x-auto px-6 pb-6">
        <EasyDataTable
          :headers="headers"
          :items="categories.data || []"
          :search-field="searchField"
          :search-value="searchValue"
          :server-items-length="categories.total || 0"
          :server-options="serverOptions"
          @update:server-options="handleServerOptionsChange"
          @update:search-value="handleSearchChange"
          server
          table-class-name="customize-table"
          header-text-direction="left"
          body-text-direction="left"
        >
          <template #item-name="{ name }">
            <div class="text-sm font-medium text-slate-800">{{ name }}</div>
          </template>
          <template #item-is_active="{ is_active }">
            <span v-if="is_active" class="inline-flex px-3 py-1 text-xs font-medium rounded-full bg-green-500 text-white">
              Active
            </span>
            <span v-else class="inline-flex px-3 py-1 text-xs font-medium rounded-full bg-gray-300 text-gray-700">
              Inactive
            </span>
          </template>
          <template #item-products_count="{ products_count }">
            <div class="text-sm text-slate-800">{{ products_count || 0 }}</div>
          </template>
          <template #item-actions="{ id }">
            <Link :href="`/admin/categories/${id}/edit`" class="text-blue-500 hover:text-blue-600 mr-4 transition-colors duration-150">Edit</Link>
          </template>
        </EasyDataTable>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import { ref, watch } from 'vue'
import EasyDataTable from 'vue3-easy-data-table'
import 'vue3-easy-data-table/dist/style.css'

const props = defineProps({
  categories: Object
})

const searchValue = ref('')
const searchField = ['name', 'slug']
const loading = ref(false)

const serverOptions = ref({
  page: props.categories?.current_page || 1,
  rowsPerPage: props.categories?.per_page || 20,
  sortBy: 'name',
  sortType: 'asc'
})

const headers = [
  { text: 'ID', value: 'id', sortable: true },
  { text: 'Name', value: 'name', sortable: true },
  { text: 'Slug', value: 'slug', sortable: true },
  { text: 'Status', value: 'is_active', sortable: true },
  { text: 'Products Count', value: 'products_count', sortable: true },
  { text: 'Created', value: 'created_at', sortable: true },
  { text: 'Actions', value: 'actions', sortable: false }
]

// Sync serverOptions with props when they change
watch(() => props.categories, (categories) => {
  if (categories) {
    serverOptions.value.page = categories.current_page || 1
    serverOptions.value.rowsPerPage = categories.per_page || 20
  }
}, { immediate: true, deep: true })

let searchTimeout = null

function handleSearchInput() {
  // Debounce search to avoid too many requests
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    serverOptions.value.page = 1 // Reset to first page on search
    fetchData()
  }, 500) // Wait 500ms after user stops typing
}

function fetchData() {
  loading.value = true
  router.get('/admin/categories', {
    page: serverOptions.value.page,
    per_page: serverOptions.value.rowsPerPage,
    sort_by: serverOptions.value.sortBy,
    sort_type: serverOptions.value.sortType,
    search: searchValue.value
  }, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loading.value = false
    }
  })
}

function handleServerOptionsChange(options) {
  serverOptions.value = options
  fetchData()
}

function handleSearchChange(value) {
  searchValue.value = value
  serverOptions.value.page = 1
  fetchData()
}
</script>

