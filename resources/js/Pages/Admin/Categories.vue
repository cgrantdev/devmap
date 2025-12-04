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
        
        <!-- Bulk Action Buttons -->
        <div v-if="selectedCategories.length > 0" class="ml-auto flex gap-2">
          <button
            @click="deselectAll"
            class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 font-medium transition-colors"
          >
            Deselect All
          </button>
          <button
            v-if="selectedCategories.length > 1"
            @click="bulkMerge"
            :disabled="bulkMergeForm.processing || bulkDeleteForm.processing"
            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 font-medium transition-colors disabled:opacity-50"
          >
            {{ bulkMergeForm.processing ? 'Merging...' : `Bulk Merge (${selectedCategories.length} selected)` }}
          </button>
          <button
            @click="bulkDelete"
            :disabled="bulkMergeForm.processing || bulkDeleteForm.processing"
            class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 font-medium transition-colors disabled:opacity-50"
          >
            {{ bulkDeleteForm.processing ? 'Deleting...' : `Bulk Delete (${selectedCategories.length} selected)` }}
          </button>
        </div>
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
          :loading="loading"
          server
          table-class-name="customize-table"
          header-text-direction="left"
          body-text-direction="left"
        >
          <template #item-checkbox="{ id }">
            <input
              type="checkbox"
              :value="id"
              v-model="selectedCategories"
              class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
            />
          </template>
          <template #item-name="{ name, slug }">
            <a :href="`/product/${slug}`" target="_blank" class="text-sm font-medium text-blue-600 hover:text-blue-800 hover:underline transition-colors duration-150">
              {{ name }}
            </a>
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
          <template #item-actions="{ id, name }">
            <Link :href="`/admin/categories/${id}/edit`" class="text-blue-500 hover:text-blue-600 mr-4 transition-colors duration-150">Edit</Link>
            <button
              @click="deleteCategory(id, name)"
              :disabled="deleteForm.processing"
              class="text-red-500 hover:text-red-600 transition-colors duration-150 disabled:opacity-50"
            >
              Delete
            </button>
          </template>
        </EasyDataTable>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Link, router, useForm, usePage } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import { ref, watch, onMounted } from 'vue'
import EasyDataTable from 'vue3-easy-data-table'
import 'vue3-easy-data-table/dist/style.css'

const props = defineProps({
  categories: Object
})

// Initialize searchValue from URL parameters
const getSearchFromUrl = () => {
  if (typeof window !== 'undefined') {
    const urlParams = new URLSearchParams(window.location.search)
    return urlParams.get('search') || ''
  }
  return ''
}

const searchValue = ref(getSearchFromUrl())
const searchField = ['name', 'slug']
const loading = ref(false)
const isUserAction = ref(false) // Flag to prevent watch from interfering with user actions

// Initialize serverOptions from URL parameters or props
const getSortByFromUrl = () => {
  if (typeof window !== 'undefined') {
    const urlParams = new URLSearchParams(window.location.search)
    return urlParams.get('sort_by') || 'name'
  }
  return 'name'
}

const getSortTypeFromUrl = () => {
  if (typeof window !== 'undefined') {
    const urlParams = new URLSearchParams(window.location.search)
    return urlParams.get('sort_type') || 'asc'
  }
  return 'asc'
}

const serverOptions = ref({
  page: props.categories?.current_page || 1,
  rowsPerPage: props.categories?.per_page || 20,
  sortBy: getSortByFromUrl(),
  sortType: getSortTypeFromUrl()
})

const headers = [
  { text: '', value: 'checkbox', sortable: false, width: 50 },
  { text: 'ID', value: 'id', sortable: true },
  { text: 'Name', value: 'name', sortable: true },
  { text: 'Slug', value: 'slug', sortable: true },
  { text: 'Status', value: 'is_active', sortable: true },
  { text: 'Products Count', value: 'products_count', sortable: true },
  { text: 'Created', value: 'created_at', sortable: true },
  { text: 'Actions', value: 'actions', sortable: false }
]

const selectedCategories = ref([])

// Sync serverOptions with props when they change (only on initial load or external updates)
watch(() => [props.categories?.current_page, props.categories?.per_page], ([currentPage, perPage]) => {
  if (currentPage && perPage && !isUserAction.value) {
    // Only update if values actually changed to prevent loops
    if (serverOptions.value.page !== currentPage || serverOptions.value.rowsPerPage !== perPage) {
      serverOptions.value.page = currentPage
      serverOptions.value.rowsPerPage = perPage
      // Clear selections when data changes (pagination, search, etc.)
      selectedCategories.value = []
    }
  }
  isUserAction.value = false // Reset flag after sync
}, { immediate: true })

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
  isUserAction.value = true // Mark as user action to prevent watch interference
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
      // Sync after data is loaded
      if (props.categories) {
        serverOptions.value.page = props.categories.current_page || 1
        serverOptions.value.rowsPerPage = props.categories.per_page || 20
      }
    }
  })
}

function handleServerOptionsChange(options) {
  isUserAction.value = true // Mark as user action
  serverOptions.value = { ...options } // Create new object to trigger reactivity
  fetchData()
}

function handleSearchChange(value) {
  // This is called by EasyDataTable's built-in search
  // We're using our own search input, so we can ignore this or sync it
  if (searchValue.value !== value) {
    searchValue.value = value
    serverOptions.value.page = 1
    isUserAction.value = true
    fetchData()
  }
}

const bulkMergeForm = useForm({
  category_ids: [],
  _token: usePage().props.csrf_token
})

const bulkDeleteForm = useForm({
  category_ids: [],
  _token: usePage().props.csrf_token
})

const deleteForm = useForm({
  _token: usePage().props.csrf_token
})

function deleteCategory(id, name) {
  if (confirm(`Are you sure you want to delete category "${name}"? This will also delete all products in this category. This action cannot be undone.`)) {
    deleteForm.delete(`/admin/categories/${id}`, {
      preserveScroll: true,
      onSuccess: () => {
        // Remove from selected categories if it was selected
        const index = selectedCategories.value.indexOf(id)
        if (index > -1) {
          selectedCategories.value.splice(index, 1)
        }
        // Refresh data
        fetchData()
      },
      onError: (errors) => {
        console.error('Delete error:', errors)
        alert('Failed to delete category. Please try again.')
      }
    })
  }
}

function bulkMerge() {
  if (selectedCategories.value.length < 2) {
    alert('Please select at least 2 categories to merge.')
    return
  }
  
  const mainCategory = selectedCategories.value[0] // First selected is the main category
  const categoriesToMerge = selectedCategories.value.slice(1) // Others will be merged into main
  
  if (confirm(`Are you sure you want to merge ${categoriesToMerge.length} category/categories into "${props.categories.data.find(c => c.id === mainCategory)?.name}"? All products from merged categories will be moved to the main category and merged categories will be deleted. This action cannot be undone.`)) {
    bulkMergeForm.category_ids = selectedCategories.value
    bulkMergeForm.post('/admin/categories/bulk-merge', {
      onSuccess: () => {
        selectedCategories.value = []
        fetchData()
      },
      onError: (errors) => {
        console.error('Bulk merge error:', errors)
        alert('Failed to merge categories. Please try again.')
      }
    })
  }
}

function bulkDelete() {
  if (selectedCategories.value.length === 0) {
    alert('Please select at least one category to delete.')
    return
  }
  
  const selectedNames = selectedCategories.value.map(id => {
    const category = props.categories.data.find(c => c.id === id)
    return category ? category.name : `ID: ${id}`
  }).join(', ')
  
  if (confirm(`Are you sure you want to delete ${selectedCategories.value.length} category/categories? This will also delete all products in these categories. This action cannot be undone.\n\nCategories: ${selectedNames}`)) {
    bulkDeleteForm.category_ids = selectedCategories.value
    bulkDeleteForm.post('/admin/categories/bulk-delete', {
      onSuccess: () => {
        selectedCategories.value = []
        fetchData()
      },
      onError: (errors) => {
        console.error('Bulk delete error:', errors)
        alert('Failed to delete categories. Please try again.')
      }
    })
  }
}

function deselectAll() {
  selectedCategories.value = []
}
</script>

