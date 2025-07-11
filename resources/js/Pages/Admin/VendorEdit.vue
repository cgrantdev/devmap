<template>
  <AdminLayout>
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Edit Vendor</h1>
        <Link href="/admin/vendors" class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold">Back</Link>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Profile Edit Section -->
        <div>
          <h2 class="text-xl font-semibold mb-4">Profile Details</h2>
          <form @submit.prevent="submitEditVendor">
            <div class="mb-4 flex gap-4">
              <div class="w-1/2">
                <label class="block mb-1 font-medium">Name</label>
                <input v-model="editForm.name" type="text" class="w-full border rounded px-3 py-2" required />
              </div>
              <div class="w-1/2">
                <label class="block mb-1 font-medium">Email Address</label>
                <input v-model="editForm.email" type="email" class="w-full border rounded px-3 py-2" required />
              </div>
            </div>
            <div class="mb-4">
              <label class="block mb-1 font-medium">Password <span class="text-xs text-gray-500">(leave blank to keep current)</span></label>
              <input v-model="editForm.password" type="password" class="w-full border rounded px-3 py-2" />
            </div>
            <div class="mb-4">
              <label class="block mb-1 font-medium">Company Name</label>
              <input v-model="editForm.company_name" type="text" class="w-full border rounded px-3 py-2" />
            </div>
            <div class="mb-4">
              <label class="block mb-1 font-medium">Company Detail</label>
              <textarea v-model="editForm.company_detail" class="w-full border rounded px-3 py-2" rows="2"></textarea>
            </div>
            <div class="mb-4 flex gap-4">
              <div class="w-1/2">
                <label class="block mb-1 font-medium">Contact Email</label>
                <input v-model="editForm.contact_email" type="email" class="w-full border rounded px-3 py-2" />
              </div>
              <div class="w-1/2">
                <label class="block mb-1 font-medium">Phone Number</label>
                <input v-model="editForm.phone_number" type="text" class="w-full border rounded px-3 py-2" />
              </div>
            </div>
            <div class="mb-4">
              <label class="block mb-1 font-medium">URL</label>
              <input v-model="editForm.url" type="url" class="w-full border rounded px-3 py-2" />
            </div>
            <div class="mb-4 flex gap-4 items-end">
              <div class="w-1/2">
                <label class="block mb-1 font-medium">Banner</label>
                <input @change="e => handleFileChange(e, 'banner')" type="file" accept="image/*" class="w-full" />
                <div v-if="bannerPreview" class="mt-2">
                  <img :src="bannerPreview" alt="Banner Preview" class="h-16 rounded object-cover w-full" />
                </div>
                <div v-else-if="editForm.banner_url" class="mt-2">
                  <img :src="editForm.banner_url" alt="Current Banner" class="h-16 rounded object-cover w-full" />
                </div>
              </div>
              <div class="w-1/2">
                <label class="block mb-1 font-medium">Logo</label>
                <input @change="e => handleFileChange(e, 'logo')" type="file" accept="image/*" class="w-full" />
                <div v-if="logoPreview" class="mt-2">
                  <img :src="logoPreview" alt="Logo Preview" class="h-16 w-16 rounded-full object-cover mx-auto" />
                </div>
                <div v-else-if="editForm.logo_url" class="mt-2">
                  <img :src="editForm.logo_url" alt="Current Logo" class="h-16 w-16 rounded-full object-cover mx-auto" />
                </div>
              </div>
            </div>
            <div class="flex justify-end">
              <button type="submit" :disabled="editForm.processing" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700 font-semibold">
                {{ editForm.processing ? 'Saving...' : 'Save Changes' }}
              </button>
            </div>
          </form>
        </div>
        <!-- XML Import Section -->
        <div>
          <h2 class="text-xl font-semibold mb-4">Import Products (XML)</h2>
          <form @submit.prevent="importFromFile" class="mb-6">
            <label class="block mb-2 font-medium">Import from File</label>
            <input type="file" @change="handleImportFileSelect" accept=".xml" class="mb-2" />
            <button type="submit" :disabled="importFileForm.processing || !importFileForm.file" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50">Import from File</button>
          </form>
          <form @submit.prevent="importFromUrl">
            <label class="block mb-2 font-medium">Import from URL</label>
            <input v-model="importUrlForm.url" type="url" placeholder="https://example.com/products.xml" class="w-full border rounded px-3 py-2 mb-2" />
            <button type="submit" :disabled="importUrlForm.processing || !importUrlForm.url" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50">Import from URL</button>
          </form>
        </div>
        <!-- Imported Products Table -->
        <div class="mt-8">
          <h2 class="text-xl font-semibold mb-4">Imported Products</h2>
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="product in products" :key="product.id" class="hover:bg-gray-50">
                  <td class="px-4 py-2">
                    <img v-if="product.image_url" :src="product.image_url" alt="Product" class="h-12 w-12 object-cover rounded" />
                    <span v-else class="text-gray-400 text-xs">No Image</span>
                  </td>
                  <td class="px-4 py-2">{{ product.name }}</td>
                  <td class="px-4 py-2">{{ product.price }}</td>
                  <td class="px-4 py-2">
                    <button @click="deleteProduct(product.id)" class="text-red-600 hover:text-red-900">Delete</button>
                  </td>
                </tr>
              </tbody>
            </table>
            <div v-if="products.length === 0" class="p-4 text-center text-gray-500">No products imported yet.</div>
          </div>
        </div>
      </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { useForm, usePage, Link } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'

const props = defineProps({
  vendor: Object,
  products: {
    type: Array,
    default: () => []
  }
})

console.log(props.vendor);

const bannerPreview = ref(null)
const logoPreview = ref(null)

const editForm = useForm({
  name: props.vendor.name || '',
  email: props.vendor.email || '',
  password: '',
  company_name: props.vendor.settings?.company_name || '',
  company_detail: props.vendor.settings?.company_detail || '',
  url: props.vendor.settings?.url || '',
  contact_email: props.vendor.settings?.contact_email || '',
  phone_number: props.vendor.settings?.phone_number || '',
  banner: null,
  logo: null,
  banner_url: props.vendor.settings?.banner_url || '',
  logo_url: props.vendor.settings?.logo_url || '',
  _token: usePage().props.csrf_token
})

function handleFileChange(event, field) {
  const file = event.target.files[0]
  if (file) {
    editForm[field] = file
    const reader = new FileReader()
    reader.onload = e => {
      if (field === 'banner') bannerPreview.value = e.target.result
      if (field === 'logo') logoPreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

function submitEditVendor() {
  editForm.post(`/admin/vendors/${props.vendor.id}`, {
    forceFormData: true,
    onSuccess: () => {
      bannerPreview.value = null
      logoPreview.value = null
    }
  })
}

// XML Import logic (as before)
const importFileForm = useForm({
  file: null,
  _token: usePage().props.csrf_token
})
const importUrlForm = useForm({
  url: '',
  _token: usePage().props.csrf_token
})

function handleImportFileSelect(event) {
  const file = event.target.files[0]
  if (file) {
    importFileForm.file = file
  }
}

function importFromFile() {
  importFileForm.post(`/admin/vendors/${props.vendor.id}/products/import`, {
    forceFormData: true,
    onSuccess: () => {
      importFileForm.file = null
    }
  })
}

function importFromUrl() {
  importUrlForm.post(`/admin/vendors/${props.vendor.id}/products/import-url`, {
    onSuccess: () => {
      importUrlForm.url = ''
    }
  })
}

// Products table logic
const products = props.products

function deleteProduct(productId) {
  if (confirm('Are you sure you want to delete this product?')) {
    const deleteForm = useForm({ _token: usePage().props.csrf_token })
    deleteForm.delete(`/admin/vendors/${props.vendor.id}/products/${productId}`, {
      onSuccess: () => {
        // Optionally reload products or show a message
      }
    })
  }
}
</script> 