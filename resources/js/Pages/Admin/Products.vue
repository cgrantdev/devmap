<template>
  <AdminLayout>
    <div class="mx-auto">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">All Products</h1>
      </div>
      <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
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

const loading = ref(false)
const searchValue = ref('')
const searchField = ['name', 'vendor_name']

const serverOptions = ref({
  page: props.products?.current_page || 1,
  rowsPerPage: props.products?.per_page || 20,
  sortBy: 'id',
  sortType: 'desc'
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

function handleSearchChange(value) {
  searchValue.value = value
  serverOptions.value.page = 1 // Reset to first page on search
  fetchData()
}
</script> 