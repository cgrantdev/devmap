<template>
  <VendorLayout>
    <div class="mb-8">
      <h1 class="text-3xl font-bold">Import Products</h1>
      <p class="text-gray-600 mt-2">Import products from XML files or URLs</p>
    </div>

    <!-- Success Message -->
    <div v-if="$page.props.flash.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
      {{ $page.props.flash.success }}
    </div>

    <!-- Error Message -->
    <div v-if="$page.props.flash.error" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
      {{ $page.props.flash.error }}
    </div>

    <!-- Import Sections -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
      <!-- File Upload Section -->
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold mb-4">Import from File</h2>
        <form @submit.prevent="uploadFile">
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Select XML File</label>
            <input 
              type="file" 
              @change="handleFileSelect" 
              accept=".xml"
              class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
            />
          </div>
          <button 
            type="submit" 
            :disabled="!fileForm.file || fileForm.processing"
            class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ fileForm.processing ? 'Importing...' : 'Import from File' }}
          </button>
        </form>
      </div>

      <!-- URL Import Section -->
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold mb-4">Import from URL</h2>
        <form @submit.prevent="importFromUrl">
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">XML URL</label>
            <input 
              v-model="urlForm.url" 
              type="url" 
              placeholder="https://example.com/products.xml"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <button 
            type="submit" 
            :disabled="!urlForm.url || urlForm.processing"
            class="w-full bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ urlForm.processing ? 'Importing...' : 'Import from URL' }}
          </button>
        </form>
      </div>
    </div>

    <!-- Products Table -->
    <div class="bg-white rounded-lg shadow">
      <div class="p-6 border-b">
        <h2 class="text-xl font-semibold">Imported Products</h2>
        <p class="text-gray-600 mt-1">Total: {{ products.length }} products</p>
      </div>
      
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">URL</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="product in products" :key="product.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex-shrink-0 h-16 w-16">
                  <img v-if="product.image_url" :src="product.image_url" alt="Product" class="h-16 w-16 object-cover rounded">
                  <div v-else class="h-16 w-16 bg-gray-200 rounded flex items-center justify-center">
                    <span class="text-gray-400 text-xs">No Image</span>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ product.name }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ product.price }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <a v-if="product.product_url" :href="product.product_url" target="_blank" class="text-indigo-600 hover:text-indigo-900 text-sm">
                  View Product
                </a>
                <span v-else class="text-gray-400 text-sm">No URL</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button @click="deleteProduct(product.id)" class="text-red-600 hover:text-red-900">
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <div v-if="products.length === 0" class="p-6 text-center text-gray-500">
        No products imported yet.
      </div>
    </div>
  </VendorLayout>
</template>

<script setup>
import { useForm, usePage } from '@inertiajs/vue3'
import VendorLayout from './Layout.vue'

const props = defineProps({
  products: {
    type: Array,
    default: () => []
  }
})

const fileForm = useForm({
  file: null,
  _token: usePage().props.csrf_token
})

const urlForm = useForm({
  url: '',
  _token: usePage().props.csrf_token
})

function handleFileSelect(event) {
  const file = event.target.files[0]
  if (file) {
    fileForm.file = file
  }
}

function uploadFile() {
  if (fileForm.file) {
    fileForm.post('/vendor/import/file', {
      onSuccess: () => {
        fileForm.file = null
        // Reset file input
        const fileInput = document.querySelector('input[type="file"]')
        if (fileInput) fileInput.value = ''
      }
    })
  }
}

function importFromUrl() {
  urlForm.post('/vendor/import/url', {
    onSuccess: () => {
      urlForm.url = ''
    }
  })
}

function deleteProduct(productId) {
  if (confirm('Are you sure you want to delete this product?')) {
    const deleteForm = useForm({
      _token: usePage().props.csrf_token
    })
    
    deleteForm.delete(`/vendor/products/${productId}`, {
      onSuccess: () => {
        console.log('Product deleted successfully')
      }
    })
  }
}
</script> 