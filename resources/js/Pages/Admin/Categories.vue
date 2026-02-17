<template>
  <AdminLayout>
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-3xl text-slate-900 mb-2">Category Management</h1>
      <p class="text-slate-600">Manage all product categories</p>
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
            placeholder="Search categories..."
            v-model="searchValue"
            @input="handleSearchInput"
            class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        <div class="flex items-center gap-2">
          <div v-if="selectedCategories.length > 0" class="flex gap-2">
            <button
              @click="deselectAll"
              class="px-4 py-2 bg-slate-500 text-white rounded-lg hover:bg-slate-600 transition-colors"
            >
              Deselect All
            </button>
            <button
              v-if="selectedCategories.length > 1"
              @click="bulkMerge"
              :disabled="bulkMergeForm.processing || bulkDeleteForm.processing"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50"
            >
              {{ bulkMergeForm.processing ? 'Merging...' : `Merge (${selectedCategories.length})` }}
            </button>
            <button
              @click="bulkDelete"
              :disabled="bulkMergeForm.processing || bulkDeleteForm.processing"
              class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50"
            >
              {{ bulkDeleteForm.processing ? 'Deleting...' : `Delete (${selectedCategories.length})` }}
            </button>
          </div>
          <button 
            @click="openAddModal"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add Category
          </button>
        </div>
      </div>
    </div>

    <!-- Categories Table -->
    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-slate-50 border-b border-slate-200">
            <tr>
              <th class="px-6 py-3 text-left text-xs text-slate-500 uppercase tracking-wider w-12">
                <input
                  type="checkbox"
                  @change="toggleSelectAll"
                  :checked="selectedCategories.length === (categories.data || []).length && (categories.data || []).length > 0"
                  class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                />
              </th>
              <th class="px-6 py-3 text-left text-xs text-slate-500 uppercase tracking-wider">Category</th>
              <th class="px-6 py-3 text-left text-xs text-slate-500 uppercase tracking-wider">Slug</th>
              <th class="px-6 py-3 text-left text-xs text-slate-500 uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-left text-xs text-slate-500 uppercase tracking-wider">Products</th>
              <th class="px-6 py-3 text-left text-xs text-slate-500 uppercase tracking-wider">Created</th>
              <th class="px-6 py-3 text-left text-xs text-slate-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            <tr v-for="category in categories.data || []" :key="category.id" class="hover:bg-slate-50">
              <td class="px-6 py-4">
                <input
                  type="checkbox"
                  :value="category.id"
                  v-model="selectedCategories"
                  class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                />
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div v-if="category.image_url" class="w-10 h-10 rounded-lg overflow-hidden flex-shrink-0">
                    <img :src="category.image_url" :alt="category.name" class="w-10 h-10 object-cover" />
                  </div>
                  <div v-else class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white flex-shrink-0">
                    {{ category.name.substring(0, 2).toUpperCase() }}
                  </div>
                  <div>
                    <a :href="`/product/${category.slug}`" target="_blank" class="text-sm font-medium text-blue-600 hover:text-blue-800 hover:underline">
                      {{ category.name }}
                    </a>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <span class="text-sm text-slate-600">{{ category.slug }}</span>
              </td>
              <td class="px-6 py-4">
                <span v-if="category.is_active" class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs">
                  Active
                </span>
                <span v-else class="bg-slate-100 text-slate-700 px-2 py-1 rounded text-xs">
                  Inactive
                </span>
              </td>
              <td class="px-6 py-4">
                <span class="text-sm text-slate-900">{{ category.products_count || 0 }}</span>
              </td>
              <td class="px-6 py-4">
                <span class="text-sm text-slate-600">{{ category.created_at }}</span>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <button
                    @click="openEditModal(category)"
                    class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                    title="Edit"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  <button
                    @click="deleteCategory(category.id, category.name)"
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

    <div v-if="!categories.data || categories.data.length === 0" class="bg-white rounded-lg border border-slate-200 p-6 text-center text-slate-500">
      No categories found.
    </div>

    <!-- Pagination -->
    <div v-if="categories.last_page > 1" class="mt-6 flex items-center justify-between bg-white rounded-lg border border-slate-200 p-4">
      <div class="text-sm text-slate-600">
        Showing {{ categories.from || 0 }} to {{ categories.to || 0 }} of {{ categories.total || 0 }} categories
      </div>
      <div class="flex gap-2">
        <button
          @click="goToPage(categories.current_page - 1)"
          :disabled="categories.current_page === 1"
          class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Previous
        </button>
        <button
          @click="goToPage(categories.current_page + 1)"
          :disabled="categories.current_page === categories.last_page"
          class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Next
        </button>
      </div>
    </div>

    <!-- Edit Category Modal -->
    <div
      v-if="showEditModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="closeEditModal"
    >
      <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-slate-200">
          <h2 class="text-2xl text-slate-900">{{ isEditMode ? 'Edit Category' : 'Add Category' }}</h2>
        </div>

        <form @submit.prevent="submitEdit" class="p-6 space-y-6">
            <!-- Category Name -->
            <div>
              <label class="block text-sm text-slate-700 mb-2">
                Category Name *
              </label>
              <input
                v-model="editForm.name"
                type="text"
                required
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <!-- Slug -->
            <div>
              <label class="block text-sm text-slate-700 mb-2">
                Slug
              </label>
              <input
                v-model="editForm.slug"
                type="text"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Auto-generated from name if left empty"
              />
              <p class="text-xs text-slate-500 mt-1">Leave empty to auto-generate from category name</p>
            </div>

            <!-- Image URL -->
            <div>
              <label class="block text-sm text-slate-700 mb-2">
                Image URL
              </label>
              <input
                v-model="editForm.image_url"
                type="url"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="https://example.com/image.jpg"
              />
            </div>

            <!-- Research Area -->
            <div>
              <label class="block text-sm text-slate-700 mb-2">
                Research Area
              </label>
              <input
                v-model="editForm.research_area"
                type="text"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Enter research area"
              />
            </div>

            <!-- Status -->
            <div class="border-t border-slate-200 pt-4">
              <label class="flex items-center gap-2">
                <input
                  v-model="editForm.is_active"
                  type="checkbox"
                  class="h-4 w-4 text-blue-600"
                />
                <span class="text-sm text-slate-700">Active</span>
              </label>
            </div>

            <!-- Error Messages -->
            <div v-if="Object.keys(editForm.errors).length > 0" class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
              <p class="font-medium mb-2">Please fix the following errors:</p>
              <ul class="list-disc list-inside text-sm">
                <li v-for="(error, field) in editForm.errors" :key="field">
                  {{ Array.isArray(error) ? error[0] : error }}
                </li>
              </ul>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
              <button
                type="button"
                @click="closeEditModal"
                class="px-6 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="editForm.processing"
                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors disabled:opacity-50"
              >
                {{ editForm.processing ? (isEditMode ? 'Saving...' : 'Adding...') : (isEditMode ? 'Save Changes' : 'Add Category') }}
              </button>
            </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { router, useForm, usePage } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import { ref, computed, watch } from 'vue'
import { useToast as useVueToastification } from 'vue-toastification'

// Only use toast for manual error messages
// Success messages are handled automatically by Layout component via flash messages
const toast = useVueToastification()
const toastError = (message) => toast.error(message, { timeout: 4000 })
const toastWarning = (message) => toast.warning(message, { timeout: 3000 })

const props = defineProps({
  categories: Object
})

const searchValue = ref('')
const selectedCategories = ref([])
const showEditModal = ref(false)
const editingCategory = ref(null)
const isEditMode = computed(() => editingCategory.value !== null)

const editForm = useForm({
  name: '',
  slug: '',
  image_url: '',
  research_area: '',
  is_active: true,
  _token: usePage().props.csrf_token
})

let searchTimeout = null

function handleSearchInput() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchData(1)
  }, 500)
}

function fetchData(page = props.categories?.current_page || 1) {
  router.get('/admin/categories', {
    page,
    per_page: props.categories?.per_page || 20,
    search: searchValue.value
  }, {
    preserveState: true,
    preserveScroll: true
  })
}

function goToPage(page) {
  if (page >= 1 && page <= props.categories.last_page) {
    fetchData(page)
  }
}

function toggleSelectAll(event) {
  if (event.target.checked) {
    selectedCategories.value = (props.categories.data || []).map(c => c.id)
  } else {
    selectedCategories.value = []
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
        const index = selectedCategories.value.indexOf(id)
        if (index > -1) {
          selectedCategories.value.splice(index, 1)
        }
        fetchData()
      },
      onError: (errors) => {
        console.error('Delete error:', errors)
        toastError('Failed to delete category. Please try again.')
      }
    })
  }
}

function bulkMerge() {
  if (selectedCategories.value.length < 2) {
    toastWarning('Please select at least 2 categories to merge.')
    return
  }
  
  const mainCategory = selectedCategories.value[0]
  const categoriesToMerge = selectedCategories.value.slice(1)
  
  if (confirm(`Are you sure you want to merge ${categoriesToMerge.length} category/categories into "${props.categories.data.find(c => c.id === mainCategory)?.name}"? All products from merged categories will be moved to the main category and merged categories will be deleted. This action cannot be undone.`)) {
    bulkMergeForm.category_ids = selectedCategories.value
    bulkMergeForm.post('/admin/categories/bulk-merge', {
      onSuccess: () => {
        selectedCategories.value = []
        fetchData()
      },
      onError: (errors) => {
        console.error('Bulk merge error:', errors)
        toastError('Failed to merge categories. Please try again.')
      }
    })
  }
}

function bulkDelete() {
  if (selectedCategories.value.length === 0) {
    toastWarning('Please select at least one category to delete.')
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
        toastError('Failed to delete categories. Please try again.')
      }
    })
  }
}

function deselectAll() {
  selectedCategories.value = []
}

function openAddModal() {
  editingCategory.value = null
  editForm.reset()
  editForm.clearErrors()
  editForm.is_active = true
  showEditModal.value = true
}

function openEditModal(category) {
  editingCategory.value = category
  editForm.name = category.name || ''
  editForm.slug = category.slug || ''
  editForm.image_url = category.image_url || ''
  editForm.research_area = category.research_area || ''
  editForm.is_active = !!category.is_active
  editForm.clearErrors()
  showEditModal.value = true
}

function closeEditModal() {
  showEditModal.value = false
  editingCategory.value = null
  editForm.reset()
  editForm.clearErrors()
}

function submitEdit() {
  // Prepare data for submission
  const formData = {
    name: editForm.name,
    slug: editForm.slug || null,
    image_url: editForm.image_url || null,
    research_area: editForm.research_area || null,
    is_active: editForm.is_active,
    _token: editForm._token
  }
  
  if (isEditMode.value) {
    // Update existing category
    editForm.transform(() => formData).put(`/admin/categories/${editingCategory.value.id}`, {
      preserveScroll: true,
      onSuccess: () => {
        closeEditModal()
        fetchData()
      },
      onError: (errors) => {
        console.error('Update error:', errors)
        toastError('Failed to update category. Please check the form for errors.')
      }
    })
  } else {
    // Create new category
    editForm.transform(() => formData).post('/admin/categories', {
      preserveScroll: true,
      onSuccess: () => {
        closeEditModal()
        fetchData()
      },
      onError: (errors) => {
        console.error('Create error:', errors)
        toastError('Failed to create category. Please check the form for errors.')
      }
    })
  }
}
</script>
