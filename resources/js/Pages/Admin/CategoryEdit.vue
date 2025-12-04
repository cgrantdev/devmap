<template>
  <AdminLayout>
    <div class="mx-auto">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-normal text-slate-700">Edit Category</h1>
          <p class="text-slate-500 mt-2">Update category details</p>
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
        <div class="lg:col-span-2">
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
                <label class="block mb-1.5 font-semibold text-slate-800">Image URL</label>
                <input v-model="editForm.image_url" type="url" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" />
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
                  {{ editForm.processing ? 'Saving...' : 'Update Category' }}
                </button>
              </div>
            </form>
          </div>
        </div>
        
        <!-- Merge Category Section -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <h2 class="text-xl font-semibold text-slate-800 mb-4">Merge Category</h2>
            <p class="text-sm text-slate-600 mb-4">This category has <strong>{{ category.products_count }}</strong> products.</p>
            
            <div v-if="similarCategories.length > 0" class="mb-4">
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
                    {{ mergeForm.processing ? 'Merging...' : 'Merge into this' }}
                  </button>
                </div>
              </div>
            </div>
            <div v-else class="text-sm text-slate-500">
              No similar categories found.
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { useForm, usePage, Link } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'

const props = defineProps({
  category: Object,
  similarCategories: {
    type: Array,
    default: () => []
  }
})

const editForm = useForm({
  name: props.category?.name || '',
  slug: props.category?.slug || '',
  description: props.category?.description || '',
  image_url: props.category?.image_url || '',
  meta_title: props.category?.meta_title || '',
  meta_description: props.category?.meta_description || '',
  is_active: props.category?.is_active ?? true,
  _token: usePage().props.csrf_token
})

const mergeForm = useForm({
  target_category_id: null,
  _token: usePage().props.csrf_token
})

function submitEdit() {
  editForm.put(`/admin/categories/${props.category.id}`, {
    preserveState: true,
    preserveScroll: true
  })
}

function mergeCategory(targetCategoryId) {
  if (confirm('Are you sure you want to merge this category? All products will be moved to the target category and this category will be deleted. This action cannot be undone.')) {
    mergeForm.target_category_id = targetCategoryId
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

