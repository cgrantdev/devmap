<template>
  <AdminLayout>
    <div class="mb-8 flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold">Education Posts Management</h1>
        <p class="text-gray-600 mt-2">Manage all education posts</p>
      </div>
      <Link href="/admin/education-posts/create" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 font-semibold shadow">
        + New Education Post
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
        <h2 class="text-xl font-semibold">All Education Posts</h2>
      </div>

      <div class="flex items-center gap-4 mb-4 px-6">
        <span>search value: </span>
        <input type="text" v-model="searchValue" @input="handleSearchInput" class="border rounded px-3 py-2">
      </div>

      <div class="overflow-x-auto px-6 pb-6">
      <EasyDataTable
        :headers="headers"
        :items="posts.data || []"
        :search-field="searchField"
        :search-value="searchValue"
        :server-items-length="posts.total || 0"
        :server-options="serverOptions"
          @update:server-options="handleServerOptionsChange"
          @update:search-value="handleSearchChange"
          server
          table-class-name="customize-table"
          header-text-direction="left"
          body-text-direction="left"
        >
        <template #item-title="{ title, slug }">
          <div class="text-sm font-medium text-slate-800">{{ title }}</div>
          <div class="text-sm text-slate-500">{{ slug }}</div>
        </template>
        <template #item-status="{ status }">
          <span v-if="status === 'published'" class="inline-flex px-3 py-1 text-xs font-medium rounded-full bg-green-50 text-green-700 border border-green-200">
            Published
          </span>
          <span v-else class="inline-flex px-3 py-1 text-xs font-medium rounded-full bg-slate-100 text-slate-600 border border-slate-100">
            Draft
          </span>
        </template>
        <template #item-rating="{ rating, rating_count }">
          <div class="text-sm text-slate-800">{{ rating }} ({{ rating_count }} reviews)</div>
        </template>
        <template #item-published_at="{ published_at }">
          <span v-if="published_at" class="text-sm text-slate-700">{{ published_at }}</span>
          <span v-else class="text-sm text-slate-400">Draft</span>
        </template>
        <template #item-actions="{ id }">
          <Link :href="`/admin/education-posts/${id}/edit`" class="text-blue-500 hover:text-blue-600 mr-4 transition-colors duration-150">Edit</Link>
          <button @click="deletePostById(id)" :disabled="deleteForm.processing" class="text-red-500 hover:text-red-600 transition-colors duration-150">
            Delete
          </button>
        </template>
      </EasyDataTable>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Link, useForm, router } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import { ref, watch } from 'vue'
import EasyDataTable from 'vue3-easy-data-table'
import 'vue3-easy-data-table/dist/style.css'

const props = defineProps({
  posts: Object,
})

// Initialize searchValue from URL parameters
const getSearchFromUrl = () => {
  if (typeof window !== 'undefined') {
    const urlParams = new URLSearchParams(window.location.search)
    return urlParams.get('search') || ''
  }
  return ''
}

const deleteForm = useForm({})
const searchValue = ref(getSearchFromUrl())
const searchField = ['title', 'slug']
const loading = ref(false)

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
  page: props.posts?.current_page || 1,
  rowsPerPage: props.posts?.per_page || 20,
  sortBy: getSortByFromUrl(),
  sortType: getSortTypeFromUrl()
})

const headers = [
  { text: 'Title', value: 'title', sortable: true },
  { text: 'Status', value: 'status', sortable: true },
  { text: 'Rating', value: 'rating', sortable: true },
  { text: 'Published', value: 'published_at', sortable: true },
  { text: 'Created', value: 'created_at', sortable: true },
  { text: 'Actions', value: 'actions', sortable: false }
]

// Sync serverOptions with props when they change
watch(() => props.posts, (posts) => {
  if (posts) {
    serverOptions.value.page = posts.current_page || 1
    serverOptions.value.rowsPerPage = posts.per_page || 20
  }
}, { immediate: true, deep: true })

function fetchData() {
  loading.value = true
  router.get('/admin/education-posts', {
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
  serverOptions.value.page = 1
  fetchData()
}

const deletePostById = (id) => {
  const post = props.posts.data.find(p => p.id === id)
  if (post && confirm(`Are you sure you want to delete "${post.title}"?`)) {
    deleteForm.delete(`/admin/education-posts/${id}`, {
      preserveScroll: true,
    })
  }
}
</script>

