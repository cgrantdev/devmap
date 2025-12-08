<template>
  <AdminLayout>
    <div class="mx-auto">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-normal text-slate-700">{{ category ? 'Edit Category' : 'Create New Category' }}</h1>
          <p class="text-slate-500 mt-2">{{ category ? 'Update category details' : 'Add a new category' }}</p>
        </div>
        <Link href="/admin/categories" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium transition-colors">Back</Link>
      </div>
      
      <!-- Success Message -->
      <div v-if="$page.props.flash.success" class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl">
        {{ $page.props.flash.success }}
      </div>
      
      <!-- Error Message -->
      <div v-if="$page.props.flash.error" class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
        {{ $page.props.flash.error }}
      </div>
      
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Category Details Form -->
        <div :class="category ? 'lg:col-span-2' : 'lg:col-span-3'">
          <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-6">
            <h2 class="text-xl font-semibold text-slate-800 mb-6">Category Details</h2>
            <div v-if="Object.keys(editForm.errors).length > 0" class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl">
              <p class="font-medium">Please fix the following errors:</p>
              <ul class="list-disc list-inside mt-2">
                <li v-for="(error, field) in editForm.errors" :key="field" class="text-sm">
                  {{ Array.isArray(error) ? error[0] : error }}
                </li>
              </ul>
            </div>
            <form @submit.prevent="submitEdit" class="space-y-6">
              <div>
                <label class="block mb-1.5 font-semibold text-slate-800">Name *</label>
                <input v-model="editForm.name" type="text" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" required />
              </div>
              <div>
                <label class="block mb-1.5 font-semibold text-slate-800">Slug</label>
                <input v-model="editForm.slug" type="text" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" />
                <p class="text-sm text-slate-500 mt-1">Leave empty to auto-generate from name</p>
              </div>
              <div>
                <label class="block mb-1.5 font-semibold text-slate-800">Description</label>
                <textarea v-model="editForm.description" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" rows="4"></textarea>
              </div>
              <div>
                <label class="block mb-1.5 font-semibold text-slate-800">Category Image</label>
                <div v-if="imagePreview" class="mb-4">
                  <img :src="imagePreview" alt="Preview" class="w-full h-48 object-cover rounded-xl border border-slate-100 shadow-sm" />
                </div>
                <div v-else-if="category?.image_url" class="mb-4">
                  <img :src="category.image_url" alt="Current" class="w-full h-48 object-cover rounded-xl border border-slate-100 shadow-sm" />
                </div>
                <div v-else class="mb-4 w-full h-48 bg-slate-100 rounded-xl border border-slate-100 flex items-center justify-center text-slate-400">
                  <span>No Image</span>
                </div>
                <input
                  @change="handleImageChange"
                  type="file"
                  accept="image/*"
                  class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm"
                />
                <p class="text-sm text-slate-500 mt-1">Upload category image (will be converted to WebP)</p>
              </div>
              <div>
                <label class="block mb-1.5 font-semibold text-slate-800">Meta Title</label>
                <input v-model="editForm.meta_title" type="text" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" />
              </div>
              <div>
                <label class="block mb-1.5 font-semibold text-slate-800">Meta Description</label>
                <textarea v-model="editForm.meta_description" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" rows="3"></textarea>
              </div>
              <div>
                <label class="flex items-center gap-2">
                  <input v-model="editForm.is_active" type="checkbox" class="rounded border-slate-100 text-blue-600 focus:ring-blue-500" />
                  <span class="font-semibold text-slate-800">Active</span>
                </label>
              </div>
              <div class="flex justify-end pt-4 border-t border-slate-100">
                <button type="submit" :disabled="editForm.processing" class="px-6 py-2.5 rounded-xl bg-blue-600 text-white hover:bg-blue-700 font-medium transition-colors disabled:opacity-50">
                  {{ editForm.processing ? 'Saving...' : (category ? 'Update Category' : 'Create Category') }}
                </button>
              </div>
            </form>
          </div>
        </div>
        
        <!-- Merge Category Section (only show when editing existing category) -->
        <div v-if="category" class="lg:col-span-1">
          <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <h2 class="text-xl font-semibold text-slate-800 mb-4">Merge Category</h2>
            <p class="text-sm text-slate-600 mb-4">This category has <strong>{{ category.products_count }}</strong> products.</p>
            
            <!-- Search Box -->
            <div class="mb-4">
              <label class="block text-sm font-medium text-slate-700 mb-2">Search Category to Merge:</label>
              <div class="relative">
                <input
                  v-model="mergeSearchQuery"
                  @input="searchCategories"
                  type="text"
                  placeholder="Type to search categories..."
                  class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base"
                />
                <svg v-if="searching" class="absolute right-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-slate-400 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
              </div>
            </div>
            
            <!-- Search Results -->
            <div v-if="mergeSearchResults.length > 0" class="mb-4">
              <p class="text-sm font-medium text-slate-700 mb-2">Search Results:</p>
              <div class="space-y-2 max-h-64 overflow-y-auto">
                <div v-for="result in mergeSearchResults" :key="result.id" class="p-3 border border-slate-100 rounded-xl hover:bg-slate-50 transition-colors">
                  <div class="font-medium text-slate-800">{{ result.name }}</div>
                  <div class="text-sm text-slate-500">ID: {{ result.id }} | {{ result.products_count }} products</div>
                  <button
                    @click="mergeCategory(result.id)"
                    :disabled="mergeForm.processing"
                    class="mt-2 w-full px-4 py-2 rounded-xl bg-orange-600 text-white hover:bg-orange-700 font-medium transition-colors disabled:opacity-50 text-sm"
                  >
                    {{ mergeForm.processing ? 'Merging...' : 'Merge' }}
                  </button>
                </div>
              </div>
            </div>
            
            <!-- Similar Categories (fallback) -->
            <div v-else-if="similarCategories.length > 0 && !mergeSearchQuery" class="mb-4">
              <p class="text-sm font-medium text-slate-700 mb-2">Similar Categories:</p>
              <div class="space-y-2">
                <div v-for="similar in similarCategories" :key="similar.id" class="p-3 border border-slate-100 rounded-xl">
                  <div class="font-medium text-slate-800">{{ similar.name }}</div>
                  <div class="text-sm text-slate-500">{{ similar.products_count }} products</div>
                  <button
                    @click="mergeCategory(similar.id)"
                    :disabled="mergeForm.processing"
                    class="mt-2 w-full px-4 py-2 rounded-xl bg-orange-600 text-white hover:bg-orange-700 font-medium transition-colors disabled:opacity-50 text-sm"
                  >
                    {{ mergeForm.processing ? 'Merging...' : 'Merge' }}
                  </button>
                </div>
              </div>
            </div>
            <div v-else-if="!mergeSearchQuery" class="text-sm text-slate-500">
              No similar categories found. Use search to find categories to merge.
            </div>
            <div v-else-if="mergeSearchQuery && !searching && mergeSearchResults.length === 0" class="text-sm text-slate-500">
              No categories found matching "{{ mergeSearchQuery }}".
            </div>
          </div>
        </div>
      </div>
      
      <!-- Products Section (only show when editing existing category) -->
      <div v-if="category" class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mt-6">
        <h2 class="text-xl font-semibold text-slate-800 mb-4">Products in this Category ({{ products.length }})</h2>
        <div class="overflow-x-auto">
          <EasyDataTable
            :headers="productHeaders"
            :items="products"
            table-class-name="customize-table"
            header-text-direction="left"
            body-text-direction="left"
          >
            <template #item-image_url="{ image_url }">
              <img v-if="image_url" :src="image_url" alt="Product" class="h-12 w-12 object-cover rounded" loading="lazy" />
              <span v-else class="text-gray-400 text-xs">No Image</span>
            </template>
            <template #item-price="{ price }">
              {{ price ? '$' + price : '-' }}
            </template>
            <template #item-discount_price="{ discount_price }">
              {{ discount_price ? '$' + discount_price : '-' }}
            </template>
            <template #item-product_url="{ product_url }">
              <a v-if="product_url" :href="product_url" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">External</a>
              <span v-else class="text-gray-400">-</span>
            </template>
          </EasyDataTable>
        </div>
        <div v-if="products.length === 0" class="p-6 text-center text-slate-500">
          No products in this category.
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { useForm, usePage, Link, router } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import { ref } from 'vue'
import EasyDataTable from 'vue3-easy-data-table'
import 'vue3-easy-data-table/dist/style.css'

const props = defineProps({
  category: Object,
  similarCategories: {
    type: Array,
    default: () => []
  },
  products: {
    type: Array,
    default: () => []
  }
})

const productHeaders = [
  { text: 'Image', value: 'image_url', sortable: false },
  { text: 'Name', value: 'name', sortable: true },
  { text: 'Price', value: 'price', sortable: true },
  { text: 'Discount Price', value: 'discount_price', sortable: true },
  { text: 'Brand', value: 'brand_name', sortable: true },
  { text: 'Product URL', value: 'product_url', sortable: false }
]

const editForm = useForm({
  name: props.category?.name || '',
  slug: props.category?.slug || '',
  description: props.category?.description || '',
  image: null,
  meta_title: props.category?.meta_title || '',
  meta_description: props.category?.meta_description || '',
  is_active: props.category?.is_active ?? true,
  _token: usePage().props.csrf_token
})

const imagePreview = ref(null)

function handleImageChange(event) {
  const file = event.target.files[0]
  if (file) {
    editForm.image = file
    const reader = new FileReader()
    reader.onload = (e) => {
      imagePreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

const mergeForm = useForm({
  source_category_id: null,
  _token: usePage().props.csrf_token
})

const mergeSearchQuery = ref('')
const mergeSearchResults = ref([])
const searching = ref(false)
let searchTimeout = null

function searchCategories() {
  if (!props.category || !mergeSearchQuery.value || mergeSearchQuery.value.length < 2) {
    mergeSearchResults.value = []
    return
  }
  
  clearTimeout(searchTimeout)
  searching.value = true
  
  searchTimeout = setTimeout(async () => {
    try {
      const response = await fetch(`/admin/categories/${props.category.id}/search?query=${encodeURIComponent(mergeSearchQuery.value)}`, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
        },
        credentials: 'same-origin'
      })
      const data = await response.json()
      mergeSearchResults.value = data.results || []
    } catch (error) {
      console.error('Search error:', error)
      mergeSearchResults.value = []
    } finally {
      searching.value = false
    }
  }, 300)
}

function submitEdit() {
  // Update CSRF token before submission
  editForm._token = usePage().props.csrf_token
  
  if (props.category) {
    // Update existing category
    editForm.post(`/admin/categories/${props.category.id}`, {
      preserveState: true,
      preserveScroll: true,
      forceFormData: true,
      _method: 'PUT',
    })
  } else {
    // Create new category
    editForm.post('/admin/categories', {
    preserveState: true,
      preserveScroll: true,
      forceFormData: true,
  })
  }
}

function mergeCategory(sourceCategoryId) {
  if (!props.category) {
    return
  }
  
  if (confirm('Are you sure you want to merge the selected category into this category? All products from the selected category will be moved to this category and the selected category will be deleted. This action cannot be undone.')) {
    mergeForm.source_category_id = sourceCategoryId
    mergeForm.post(`/admin/categories/${props.category.id}/merge`, {
      onSuccess: () => {
        // Redirect will be handled by the controller
      },
      onError: (errors) => {
        console.error('Merge error:', errors)
      }
    })
  }
}
</script>

