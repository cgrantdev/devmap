<template>
  <AdminLayout>
    <div class="mx-auto">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Edit Vendor</h1>
        <Link href="/admin/vendors" class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold">Back</Link>
      </div>
      <!-- Profile Edit Card -->
      <div class="bg-white rounded-lg shadow p-8 mb-8">
        <h2 class="text-xl font-semibold mb-4">Profile Details</h2>
        <!-- Success Message -->
        <div v-if="$page.props.flash.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
          {{ $page.props.flash.success }}
        </div>
        <!-- Error Message -->
        <div v-if="Object.keys(editForm.errors).length > 0" class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
          <p class="font-bold">Please fix the following errors:</p>
          <ul class="list-disc list-inside">
            <li v-for="(error, field) in editForm.errors" :key="field" class="text-sm">
              {{ Array.isArray(error) ? error[0] : error }}
            </li>
          </ul>
        </div>
        <form @submit.prevent="submitEditVendor" class="space-y-4">
          <div class="flex gap-4">
            <div class="w-1/2">
              <label class="block mb-1 font-medium text-gray-700">Name</label>
              <input v-model="editForm.name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" required />
            </div>
            <div class="w-1/2">
              <label class="block mb-1 font-medium text-gray-700">Email Address</label>
              <input v-model="editForm.email" type="email" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" required />
            </div>
          </div>
          <div>
            <label class="block mb-1 font-medium text-gray-700">Password <span class="text-xs text-gray-500">(leave blank to keep current)</span></label>
            <input v-model="editForm.password" type="password" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" />
          </div>
          <div>
            <label class="block mb-1 font-medium text-gray-700">Company Name</label>
            <input v-model="editForm.company_name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" />
          </div>
          <div>
            <label class="block mb-1 font-medium text-gray-700">Company Detail</label>
            <textarea v-model="editForm.company_detail" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" rows="2"></textarea>
          </div>
          <div class="flex gap-4">
            <div class="w-1/2">
              <label class="block mb-1 font-medium text-gray-700">Contact Email</label>
              <input v-model="editForm.contact_email" type="email" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" />
            </div>
            <div class="w-1/2">
              <label class="block mb-1 font-medium text-gray-700">Phone Number</label>
              <input v-model="editForm.phone_number" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" />
            </div>
          </div>
          <div>
            <label class="block mb-1 font-medium text-gray-700">URL</label>
            <input v-model="editForm.url" type="url" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" />
          </div>
          <div class="flex gap-4 items-end">
            <div class="w-1/2">
              <label class="block mb-1 font-medium text-gray-700">Banner</label>
              <input @change="e => handleFileChange(e, 'banner')" type="file" accept="image/*" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              <div v-if="bannerPreview" class="mt-2">
                <img :src="bannerPreview" alt="Banner Preview" class="h-16 rounded object-cover w-full" />
              </div>
              <div v-else-if="currentBannerUrl" class="mt-2">
                <img :src="currentBannerUrl + '?t=' + cacheBuster" alt="Current Banner" class="h-16 rounded object-cover w-full" />
              </div>
            </div>
            <div class="w-1/2">
              <label class="block mb-1 font-medium text-gray-700">Logo</label>
              <input @change="e => handleFileChange(e, 'logo')" type="file" accept="image/*" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
              <div v-if="logoPreview" class="mt-2">
                <img :src="logoPreview" alt="Logo Preview" class="h-16 w-16 rounded-full object-cover mx-auto" />
              </div>
              <div v-else-if="currentLogoUrl" class="mt-2">
                <img :src="currentLogoUrl + '?t=' + cacheBuster" alt="Current Logo" class="h-16 w-16 rounded-full object-cover mx-auto" />
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
      <!-- XML Import Card -->
      <div class="bg-white rounded-lg shadow p-8 mb-8">
        <h2 class="text-xl font-semibold mb-4">Import Products (XML)</h2>
        <!-- Import Success/Error Messages -->
        <div v-if="$page.props.flash.success && $page.props.flash.success.includes('imported')" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
          {{ $page.props.flash.success }}
        </div>
        <div v-if="$page.props.flash.error" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
          {{ $page.props.flash.error }}
        </div>
        <form @submit.prevent="importFromFile" class="mb-6 flex gap-2 items-end">
          <div class="flex-1">
            <label class="block mb-2 font-medium text-gray-700">Import from File</label>
            <input type="file" @change="handleImportFileSelect" accept=".xml" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
          </div>
          <button type="submit" :disabled="importFileForm.processing || !importFileForm.file" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50">Import from File</button>
        </form>
        <form @submit.prevent="importFromUrl" class="flex gap-2 items-end">
          <div class="flex-1">
            <label class="block mb-2 font-medium text-gray-700">Import from URL</label>
            <input v-model="importUrlForm.url" type="url" placeholder="https://example.com/products.xml" class="w-full border border-gray-300 rounded-lg px-3 py-2" />
          </div>
          <button type="submit" :disabled="importUrlForm.processing || !importUrlForm.url" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50">Import from URL</button>
        </form>
      </div>
      <!-- Imported Products Card -->
      <div class="bg-white rounded-lg shadow p-8">
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
              <tr v-for="product in currentProducts" :key="product.id" class="hover:bg-gray-50">
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
          <div v-if="currentProducts.length === 0" class="p-4 text-center text-gray-500">No products imported yet.</div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, usePage, Link } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  vendor: Object,
  products: {
    type: Array,
    default: () => []
  }
})

console.log('Vendor props:', props.vendor);
console.log('Products props:', props.products);

const bannerPreview = ref(null)
const logoPreview = ref(null)
const cacheBuster = ref(Date.now())

// Reactive computed properties for current image URLs
const currentBannerUrl = computed(() => {
  return props.vendor?.settings?.banner_url || ''
})

const currentLogoUrl = computed(() => {
  return props.vendor?.settings?.logo_url || ''
})

// Reactive computed property for products
const currentProducts = computed(() => {
  return props.products || []
})

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

// Watch for props changes and update form data
watch(() => props.vendor, (newVendor) => {
  console.log('Vendor data changed:', newVendor);
  if (newVendor) {
    editForm.name = newVendor.name || ''
    editForm.email = newVendor.email || ''
    editForm.company_name = newVendor.settings?.company_name || ''
    editForm.company_detail = newVendor.settings?.company_detail || ''
    editForm.url = newVendor.settings?.url || ''
    editForm.contact_email = newVendor.settings?.contact_email || ''
    editForm.phone_number = newVendor.settings?.phone_number || ''
    editForm.banner_url = newVendor.settings?.banner_url || ''
    editForm.logo_url = newVendor.settings?.logo_url || ''
  }
}, { deep: true })

// Watch for products changes
watch(() => props.products, (newProducts) => {
  console.log('Products data changed:', newProducts);
}, { deep: true })

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
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      bannerPreview.value = null
      logoPreview.value = null
      cacheBuster.value = Date.now()
      // Clear file inputs
      const fileInputs = document.querySelectorAll('input[type="file"]')
      fileInputs.forEach(input => input.value = '')
      console.log('Form submitted successfully, cache buster updated to:', cacheBuster.value)
    },
    onError: () => {
      console.log('Form submission failed')
    },
    data: { _method: 'put' }
  })
}

// XML Import logic
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
      // Clear file input
      const fileInput = document.querySelector('input[accept=".xml"]')
      if (fileInput) fileInput.value = ''
      console.log('File import successful, reloading page...')
      // Reload the page to get updated products
      router.reload()
    },
    onError: () => {
      console.log('File import failed')
    }
  })
}

function importFromUrl() {
  importUrlForm.post(`/admin/vendors/${props.vendor.id}/products/import-url`, {
    onSuccess: () => {
      importUrlForm.url = ''
      console.log('URL import successful, reloading page...')
      // Reload the page to get updated products
      router.reload()
    },
    onError: () => {
      console.log('URL import failed')
    }
  })
}

function deleteProduct(productId) {
  if (confirm('Are you sure you want to delete this product?')) {
    const deleteForm = useForm({ _token: usePage().props.csrf_token })
    deleteForm.delete(`/admin/vendors/${props.vendor.id}/products/${productId}`, {
      onSuccess: () => {
        console.log('Product deleted successfully, reloading page...')
        // Reload the page to get updated products
        router.reload()
      },
      onError: () => {
        console.log('Product deletion failed')
      }
    })
  }
}
</script> 