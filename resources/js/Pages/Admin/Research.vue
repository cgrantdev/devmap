<template>
  <AdminLayout>
    <div class="mb-8 flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-normal text-slate-700">Research Management</h1>
        <p class="text-slate-500 mt-2">Manage all research papers</p>
      </div>
      <Link href="/admin/research/create" class="inline-flex items-center px-5 py-2.5 bg-yellow-500 text-white rounded-xl hover:bg-yellow-600 font-medium shadow-sm hover:shadow transition-all duration-200">
        + New Research Paper
      </Link>
    </div>
    
    <!-- Flash messages are now handled by toast notifications -->
    
    <div class="bg-white rounded-lg shadow">
      <div class="p-6">
        <h2 class="text-xl font-semibold">All Research Papers</h2>
      </div>

      <div class="flex items-center gap-4 mb-4 px-6">
        <span>search value: </span>
        <input type="text" v-model="searchValue" @input="handleSearchInput" class="border rounded px-3 py-2">
      </div>

      <div class="overflow-x-auto px-6 pb-6">
      <EasyDataTable
        :headers="headers"
        :items="research.data || []"
        :search-field="searchField"
        :search-value="searchValue"
        :server-items-length="research.total || 0"
        :server-options="serverOptions"
          @update:server-options="handleServerOptionsChange"
          @update:search-value="handleSearchChange"
          server
          table-class-name="customize-table"
          header-text-direction="left"
          body-text-direction="left"
        >
        <template #item-category="{ category }">
          <span v-if="category" class="inline-flex px-3 py-1 text-xs bg-gray-50 text-gray-700">
            {{ category }}
          </span>
          <span v-else class="text-sm text-slate-400">-</span>
        </template>
        <template #item-title="{ title }">
          <div class="text-sm font-medium text-slate-800">{{ title }}</div>
        </template>
        <template #item-status="{ status }">
          <span v-if="status === 'published'" class="inline-flex px-3 py-1 text-xs font-medium rounded-full bg-green-50 text-green-700 border border-green-200">
            Published
          </span>
          <span v-else class="inline-flex px-3 py-1 text-xs font-medium rounded-full bg-slate-100 text-slate-600 border border-slate-100">
            Draft
          </span>
        </template>
        <template #item-published_at="{ published_at }">
          <span v-if="published_at" class="text-sm text-slate-700">{{ published_at }}</span>
          <span v-else class="text-sm text-slate-400">Draft</span>
        </template>
        <template #item-actions="{ id }">
          <button 
            @click="toggleQuickEdit(id)" 
            class="text-blue-500 hover:text-blue-600 mr-4 transition-colors duration-150"
            :title="expandedRows.includes(id) ? 'Close Quick Edit' : 'Quick Edit'"
          >
            <svg v-if="!expandedRows.includes(id)" class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            <svg v-else class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
          <Link :href="`/admin/research/${id}/edit`" class="text-blue-500 hover:text-blue-600 mr-4 transition-colors duration-150">Edit</Link>
          <button @click="deleteResearchById(id)" :disabled="deleteForm.processing" class="text-red-500 hover:text-red-600 transition-colors duration-150">
            Delete
          </button>
        </template>
      </EasyDataTable>
      </div>
      
      <!-- Quick Edit Modal -->
      <div v-if="selectedResearchId && expandedRows.includes(selectedResearchId)" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/75" @click.self="toggleQuickEdit(selectedResearchId)">
        <div class="bg-white rounded-xl border border-slate-100 p-6 shadow-lg max-w-2xl w-full mx-4" @click.stop>
          <h3 class="text-lg font-medium text-slate-700 mb-4">Quick Edit</h3>
          <div v-if="quickEditForms[selectedResearchId]" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">Title *</label>
              <input 
                v-model="quickEditForms[selectedResearchId].title" 
                type="text" 
                class="w-full border border-slate-100 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                @keyup.enter="saveQuickEdit(selectedResearchId)"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">Status *</label>
              <select 
                v-model="quickEditForms[selectedResearchId].status" 
                class="w-full border border-slate-100 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
              >
                <option value="draft">Draft</option>
                <option value="published">Published</option>
              </select>
            </div>
          </div>
          <div class="mt-4 flex justify-end gap-2">
            <button 
              @click="toggleQuickEdit(selectedResearchId)" 
              class="px-4 py-2 text-sm rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 transition-all duration-200 font-medium"
            >
              Cancel
            </button>
            <button 
              @click="saveQuickEdit(selectedResearchId)" 
              :disabled="quickEditForms[selectedResearchId]?.processing"
              class="px-4 py-2 text-sm rounded-xl bg-yellow-500 text-white hover:bg-yellow-600 font-medium transition-all duration-200 shadow-sm"
            >
              {{ quickEditForms[selectedResearchId]?.processing ? 'Saving...' : 'Update' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Link, useForm, router, usePage } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import { computed, ref, reactive, watch } from 'vue'
import EasyDataTable from 'vue3-easy-data-table'
import 'vue3-easy-data-table/dist/style.css'

const props = defineProps({
  research: Object,
})

const page = usePage()
const deleteForm = useForm({
  _token: page.props.csrf_token,
})
const expandedRows = ref([])
const quickEditForms = reactive({})
const selectedResearchId = ref(null)

// Initialize searchValue from URL parameters
const getSearchFromUrl = () => {
  if (typeof window !== 'undefined') {
    const urlParams = new URLSearchParams(window.location.search)
    return urlParams.get('search') || ''
  }
  return ''
}

const searchValue = ref(getSearchFromUrl())
const searchField = ['title', 'peptide']
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
  page: props.research?.current_page || 1,
  rowsPerPage: props.research?.per_page || 20,
  sortBy: getSortByFromUrl(),
  sortType: getSortTypeFromUrl()
})

const headers = [
  { text: 'Category', value: 'category', sortable: true },
  { text: 'Title', value: 'title', sortable: true },
  { text: 'Status', value: 'status', sortable: true },
  { text: 'Published', value: 'published_at', sortable: true },
  { text: 'Created', value: 'created_at', sortable: true },
  { text: 'Actions', value: 'actions', sortable: false }
]

// Sync serverOptions with props when they change
watch(() => props.research, (research) => {
  if (research) {
    serverOptions.value.page = research.current_page || 1
    serverOptions.value.rowsPerPage = research.per_page || 20
  }
}, { immediate: true, deep: true })

function fetchData() {
  loading.value = true
  router.get('/admin/research', {
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

const toggleQuickEdit = (researchId) => {
  const index = expandedRows.value.indexOf(researchId)
  if (index > -1) {
    expandedRows.value.splice(index, 1)
    selectedResearchId.value = null
    // Clean up form when closing
    delete quickEditForms[researchId]
  } else {
    expandedRows.value.push(researchId)
    selectedResearchId.value = researchId
    // Initialize form with current values when opening
    const researchItem = props.research.data.find(r => r.id === researchId)
    if (researchItem && !quickEditForms[researchId]) {
      quickEditForms[researchId] = useForm({
        title: researchItem.title,
        status: researchItem.status,
        _token: page.props.csrf_token,
      })
    }
  }
}

const saveQuickEdit = (researchId) => {
  const form = quickEditForms[researchId]
  // Update CSRF token before submission
  form._token = page.props.csrf_token
  form.patch(`/admin/research/${researchId}/quick-update`, {
    preserveScroll: true,
    onSuccess: () => {
      // Reload the research data to get updated information
      router.reload({ only: ['research'], preserveScroll: true })
      // Close the quick edit
      toggleQuickEdit(researchId)
    },
  })
}

const deleteResearchById = (id) => {
  const researchItem = props.research.data.find(r => r.id === id)
  if (researchItem && confirm(`Are you sure you want to delete "${researchItem.title}"?`)) {
    // Update CSRF token before deletion
    deleteForm._token = page.props.csrf_token
    deleteForm.delete(`/admin/research/${id}`, {
      preserveScroll: true,
    })
  }
}
</script>
