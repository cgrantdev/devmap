<template>
  <AdminLayout>
    <div class="mb-8">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-normal text-slate-700">Pages</h1>
        <Link href="/admin/pages/create" class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors font-medium">
          + Create New Page
        </Link>
      </div>

      <!-- Flash messages are now handled by toast notifications -->

      <div class="bg-white rounded-2xl shadow-sm border border-slate-100">
        <div class="flex items-center gap-4 mb-4 px-6 pt-6">
          <span class="text-slate-600">Search: </span>
          <input 
            type="text" 
            v-model="searchValue" 
            @input="handleSearchInput" 
            class="border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Search pages..."
          />
        </div>

        <div class="overflow-x-auto px-6 pb-6">
          <EasyDataTable
            :headers="headers"
            :items="pages.data || []"
            :search-field="searchField"
            :search-value="searchValue"
            :server-items-length="pages.total || 0"
            :server-options="serverOptions"
            @update:server-options="handleServerOptionsChange"
            @update:search-value="handleSearchChange"
            :loading="loading"
            server
            table-class-name="customize-table"
            header-text-direction="left"
            body-text-direction="left"
          >
            <template #item-title="{ title, slug }">
              <a :href="`/${slug}`" target="_blank" class="text-sm font-medium text-blue-600 hover:text-blue-800 hover:underline transition-colors duration-150">
                {{ title }}
              </a>
            </template>
            <template #item-slug="{ slug }">
              <code class="text-xs bg-slate-100 px-2 py-1 rounded">{{ slug }}</code>
            </template>
            <template #item-actions="{ id, title }">
              <div class="flex items-center gap-3">
                <Link :href="`/admin/pages/${id}/edit`" class="text-blue-600 hover:text-blue-800 font-medium transition-colors duration-150">
                  Edit
                </Link>
                <button
                  @click="deletePage(id, title)"
                  :disabled="deleteForm.processing"
                  class="text-red-600 hover:text-red-800 font-medium transition-colors duration-150 disabled:opacity-50"
                >
                  Delete
                </button>
              </div>
            </template>
          </EasyDataTable>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Link, router, useForm } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import { ref, watch } from 'vue'
import EasyDataTable from 'vue3-easy-data-table'
import 'vue3-easy-data-table/dist/style.css'

const props = defineProps({
  pages: Object
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
const searchField = ['title', 'slug']
const loading = ref(false)
const isUserAction = ref(false)

// Initialize serverOptions from URL parameters or props
const getSortByFromUrl = () => {
  if (typeof window !== 'undefined') {
    const urlParams = new URLSearchParams(window.location.search)
    return urlParams.get('sort_by') || 'created_at'
  }
  return 'created_at'
}

const getSortTypeFromUrl = () => {
  if (typeof window !== 'undefined') {
    const urlParams = new URLSearchParams(window.location.search)
    return urlParams.get('sort_type') || 'desc'
  }
  return 'desc'
}

const serverOptions = ref({
  page: props.pages?.current_page || 1,
  rowsPerPage: props.pages?.per_page || 20,
  sortBy: getSortByFromUrl(),
  sortType: getSortTypeFromUrl()
})

const headers = [
  { text: 'Title', value: 'title', sortable: true },
  { text: 'Slug', value: 'slug', sortable: true },
  { text: 'Created', value: 'created_at', sortable: true },
  { text: 'Updated', value: 'updated_at', sortable: true },
  { text: 'Actions', value: 'actions', sortable: false }
]

const deleteForm = useForm({})

const handleSearchInput = () => {
  isUserAction.value = true
  handleSearchChange(searchValue.value)
}

const handleSearchChange = (value) => {
  if (!isUserAction.value) return
  
  loading.value = true
  const params = new URLSearchParams(window.location.search)
  
  if (value) {
    params.set('search', value)
  } else {
    params.delete('search')
  }
  
  params.set('page', '1')
  params.set('sort_by', serverOptions.value.sortBy)
  params.set('sort_type', serverOptions.value.sortType)
  params.set('rows_per_page', serverOptions.value.rowsPerPage)
  
  router.get(`/admin/pages?${params.toString()}`, {}, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loading.value = false
      isUserAction.value = false
    }
  })
}

const handleServerOptionsChange = (options) => {
  isUserAction.value = true
  serverOptions.value = { ...options }
  
  loading.value = true
  const params = new URLSearchParams(window.location.search)
  
  params.set('page', options.page)
  params.set('sort_by', options.sortBy)
  params.set('sort_type', options.sortType)
  params.set('rows_per_page', options.rowsPerPage)
  
  if (searchValue.value) {
    params.set('search', searchValue.value)
  }
  
  router.get(`/admin/pages?${params.toString()}`, {}, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loading.value = false
      isUserAction.value = false
    }
  })
}

const deletePage = (id, title) => {
  if (confirm(`Are you sure you want to delete "${title}"? This action cannot be undone.`)) {
    deleteForm.delete(`/admin/pages/${id}`, {
      preserveScroll: true,
    })
  }
}

// Sync serverOptions with props when they change
watch(() => props.pages, (pages) => {
  if (pages && !isUserAction.value) {
    serverOptions.value.page = pages.current_page || 1
    serverOptions.value.rowsPerPage = pages.per_page || 20
  }
}, { immediate: true, deep: true })
</script>

