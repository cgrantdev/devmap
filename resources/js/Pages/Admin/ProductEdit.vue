<template>
  <AdminLayout>
    <div class="mx-auto">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-normal text-slate-700">Edit Product</h1>
          <p class="text-slate-500 mt-2">Update product details</p>
        </div>
        <Link href="/admin/products" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium transition-colors">Back</Link>
      </div>
      
      <!-- Success Message -->
      <div v-if="$page.props.flash.success" class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl">
        {{ $page.props.flash.success }}
      </div>
      
      <!-- Error Message -->
      <div v-if="$page.props.flash.error" class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
        {{ $page.props.flash.error }}
      </div>
      
      <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
        <h2 class="text-xl font-semibold text-slate-800 mb-6">Product Details</h2>
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
            <label class="block mb-1.5 font-semibold text-slate-800">Category</label>
            <select v-model="editForm.product_category_id" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base">
              <option :value="null">-- Select Category --</option>
              <option v-for="category in categories" :key="category.id" :value="category.id">
                {{ category.name }}
              </option>
            </select>
            <p class="text-sm text-slate-500 mt-1">Select a category for this product</p>
          </div>
          <div class="flex justify-end pt-4 border-t border-slate-100">
            <button type="submit" :disabled="editForm.processing" class="px-6 py-2.5 rounded-xl bg-blue-600 text-white hover:bg-blue-700 font-medium transition-colors disabled:opacity-50">
              {{ editForm.processing ? 'Saving...' : 'Update Product' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { useForm, usePage, Link } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'

const props = defineProps({
  product: Object,
  categories: {
    type: Array,
    default: () => []
  }
})

const editForm = useForm({
  name: props.product?.name || '',
  product_category_id: props.product?.product_category_id || null,
  _token: usePage().props.csrf_token
})

function submitEdit() {
  editForm.put(`/admin/products/${props.product.id}`, {
    preserveState: true,
    preserveScroll: true
  })
}
</script>

