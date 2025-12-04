<template>
  <AdminLayout>
    <div class="mx-auto">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">All Products</h1>
      </div>
      
      <div class="bg-white rounded-lg shadow">
        <div class="p-6">
          <h2 class="text-xl font-semibold">All Products</h2>
        </div>

        <div class="flex items-center gap-4 mb-4 px-6">
          <span>search value: </span>
          <input type="text" v-model="searchValue" @input="handleSearchInput" class="border rounded px-3 py-2">
        </div>

        <div class="overflow-x-auto px-6 pb-6">
        <EasyDataTable
          :headers="headers"
          :items="tableItems"
          :search-field="searchField"
          :search-value="searchValue"
          :loading="loading"
          :server-items-length="products.total || 0"
          :server-options="serverOptions"
          @update:server-options="handleServerOptionsChange"
          @update:search-value="handleSearchChange"
          server
          table-class-name="customize-table"
          header-text-direction="left"
          body-text-direction="left"
        >
          <template #item-image_url="{ image_url }">
            <img v-if="image_url" :src="image_url" alt="Product" class="h-12 w-12 object-cover rounded" loading="lazy" />
            <span v-else class="text-gray-400 text-xs">No Image</span>
          </template>
          <template #item-price="{ price }">
            ${{ price || '0.00' }}
          </template>
          <template #item-actions="{ id, slug }">
            <a :href="`/product/${id}/${slug}`" target="_blank" class="text-blue-600 hover:underline">View</a>
          </template>
        </EasyDataTable>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import EasyDataTable from 'vue3-easy-data-table'
import 'vue3-easy-data-table/dist/style.css'

const props = defineProps({
  products: Object
})

// Initialize searchValue from URL parameters
const getSearchFromUrl = () => {
  if (typeof window !== 'undefined') {
    const urlParams = new URLSearchParams(window.location.search)
    return urlParams.get('search') || ''
  }
  return ''
}

const loading = ref(false)
const searchValue = ref(getSearchFromUrl())
const searchField = ['name', 'vendor_name']

// Initialize serverOptions from URL parameters or props
const getSortByFromUrl = () => {
  if (typeof window !== 'undefined') {
    const urlParams = new URLSearchParams(window.location.search)
    return urlParams.get('sort_by') || 'id'
  }
  return 'id'
}

const getSortTypeFromUrl = () => {
  if (typeof window !== 'undefined') {
    const urlParams = new URLSearchParams(window.location.search)
    return urlParams.get('sort_type') || 'desc'
  }
  return 'desc'
}

const serverOptions = ref({
  page: props.products?.current_page || 1,
  rowsPerPage: props.products?.per_page || 20,
  sortBy: getSortByFromUrl(),
  sortType: getSortTypeFromUrl()
})

const headers = [
  { text: 'Image', value: 'image_url', sortable: false },
  { text: 'Name', value: 'name', sortable: true },
  { text: 'Price', value: 'price', sortable: true },
  { text: 'Vendor', value: 'vendor_name', sortable: true },
  { text: 'Actions', value: 'actions', sortable: false }
]

const tableItems = computed(() => {
  return props.products?.data || []
})

// Sync serverOptions with props when they change
watch(() => props.products, (products) => {
  if (products) {
    serverOptions.value.page = products.current_page || 1
    serverOptions.value.rowsPerPage = products.per_page || 20
  }
}, { immediate: true, deep: true })

function fetchData() {
  loading.value = true
  router.get('/admin/products', {
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

let searchTimeout = null

function handleSearchInput() {
  // Debounce search to avoid too many requests
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    serverOptions.value.page = 1 // Reset to first page on search
    fetchData()
  }, 500) // Wait 500ms after user stops typing
}

function handleSearchChange(value) {
  searchValue.value = value
  serverOptions.value.page = 1 // Reset to first page on search
  fetchData()
}
</script> 