<template>
  <AdminLayout>
    <div class="mx-auto">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-normal text-slate-700">{{ vendor ? 'Edit Vendor' : 'Create New Vendor' }}</h1>
          <p class="text-slate-500 mt-2">{{ vendor ? 'Update vendor details' : 'Add a new vendor' }}</p>
        </div>
        <Link href="/admin/vendors" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium transition-colors">Back</Link>
      </div>
      <!-- Success Message -->
      <div v-if="$page.props.flash.success" class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl">
        {{ $page.props.flash.success }}
      </div>
      
      <!-- Error Message -->
      <div v-if="$page.props.flash.error" class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
        {{ $page.props.flash.error }}
      </div>
      
      <!-- Profile Edit Card -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-8">
        <h2 class="text-xl font-semibold text-slate-800 mb-6">Vendor Details</h2>
        <!-- Error Message -->
        <div v-if="Object.keys(editForm.errors).length > 0" class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl">
          <p class="font-medium">Please fix the following errors:</p>
          <ul class="list-disc list-inside mt-2">
            <li v-for="(error, field) in editForm.errors" :key="field" class="text-sm">
              {{ Array.isArray(error) ? error[0] : error }}
            </li>
          </ul>
        </div>
        <form @submit.prevent="submitEditVendor" class="space-y-6">
          <div class="flex gap-4">
            <div class="w-1/2">
              <label class="block mb-1.5 font-semibold text-slate-800">Name *</label>
              <input v-model="editForm.name" type="text" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" required />
            </div>
            <div class="w-1/2">
              <label class="block mb-1.5 font-semibold text-slate-800">Contact Email</label>
              <input v-model="editForm.contact_email" type="email" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" />
            </div>
          </div>
          <div>
            <label class="block mb-1.5 font-semibold text-slate-800">Description</label>
            <textarea v-model="editForm.description" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" rows="3"></textarea>
          </div>
          <div class="flex gap-4">
            <div class="w-1/2">
              <label class="block mb-1.5 font-semibold text-slate-800">Phone Number</label>
              <input v-model="editForm.phone_number" type="text" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" />
            </div>
            <div class="w-1/2">
              <label class="block mb-1.5 font-semibold text-slate-800">Shop URL</label>
              <input v-model="editForm.shop_url" type="url" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" />
            </div>
          </div>
          <div class="flex gap-4 items-end">
            <div class="w-1/2">
              <label class="block mb-1.5 font-semibold text-slate-800">Banner</label>
              <input @change="e => handleFileChange(e, 'banner')" type="file" accept="image/*" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" />
              <div v-if="bannerPreview" class="mt-2">
                <img :src="bannerPreview" alt="Banner Preview" class="h-24 rounded-xl object-cover w-full" loading="lazy" />
              </div>
              <div v-else-if="currentBannerUrl" class="mt-2">
                <img :src="currentBannerUrl + '?t=' + cacheBuster" alt="Current Banner" class="h-24 rounded-xl object-cover w-full" loading="lazy" />
              </div>
            </div>
            <div class="w-1/2">
              <label class="block mb-1.5 font-semibold text-slate-800">Logo</label>
              <input @change="e => handleFileChange(e, 'logo')" type="file" accept="image/*,.svg" class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" />
              <div v-if="logoPreview" class="mt-2">
                <img :src="logoPreview" alt="Logo Preview" class="h-24 w-24 rounded-full object-cover mx-auto" loading="lazy" />
              </div>
              <div v-else-if="currentLogoUrl" class="mt-2">
                <img :src="currentLogoUrl + '?t=' + cacheBuster" alt="Current Logo" class="h-24 w-24 rounded-full object-cover mx-auto" loading="lazy" />
              </div>
            </div>
          </div>
          <div class="flex justify-end pt-4 border-t border-slate-100">
            <button type="submit" :disabled="editForm.processing" class="px-6 py-2.5 rounded-xl bg-blue-600 text-white hover:bg-blue-700 font-medium transition-colors disabled:opacity-50">
              {{ editForm.processing ? 'Saving...' : (vendor ? 'Update Vendor' : 'Create Vendor') }}
            </button>
          </div>
        </form>
      </div>
      <!-- XML Import Card (only show when editing) -->
      <div v-if="vendor" class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">Import Products (XML)</h2>
        <!-- Import from Shop URL Button -->
        <div class="mb-4">
          <button @click="importFromShopUrl" :disabled="importShopUrlProcessing" class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 disabled:opacity-50">
            {{ importShopUrlProcessing ? 'Importing...' : 'Import Products from Shop URL' }}
          </button>
          <div v-if="importShopUrlMessage" :class="['mt-2', importShopUrlSuccess ? 'text-green-600' : 'text-red-600']">
            {{ importShopUrlMessage }}
          </div>
        </div>
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
            <input type="file" @change="handleImportFileSelect" accept=".xml" class="w-full border border-slate-100 rounded-lg px-3 py-2" />
          </div>
          <button type="submit" :disabled="importFileForm.processing || !importFileForm.file" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50">Import from File</button>
        </form>
        <form @submit.prevent="importFromUrl" class="flex gap-2 items-end">
          <div class="flex-1">
            <label class="block mb-2 font-medium text-gray-700">Import from URL</label>
            <input v-model="importUrlForm.url" type="url" placeholder="https://example.com/products.xml" class="w-full border border-slate-100 rounded-lg px-3 py-2" />
          </div>
          <button type="submit" :disabled="importUrlForm.processing || !importUrlForm.url" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50">Import from URL</button>
        </form>
      </div>
      <!-- Imported Products Card (only show when editing) -->
      <div v-if="vendor" class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
        <h2 class="text-xl font-semibold mb-4">Imported Products</h2>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Discount Price</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Second Price</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product URL</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="product in currentProducts" :key="product.id" class="hover:bg-gray-50">
                <td class="px-4 py-2">
                  <img v-if="product.image_url" :src="product.image_url" alt="Product" class="h-12 w-12 object-cover rounded" loading="lazy" />
                  <span v-else class="text-gray-400 text-xs">No Image</span>
                </td>
                <td class="px-4 py-2">{{ product.name }}</td>
                <td class="px-4 py-2">{{ product.price ?? '-' }}</td>
                <td class="px-4 py-2">{{ product.discount_price ?? '-' }}</td>
                <td class="px-4 py-2">{{ product.second_price ?? '-' }}</td>
                <td class="px-4 py-2">
                  <a v-if="product.product_url" :href="product.product_url" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">External</a>
                </td>
                <td class="px-4 py-2">
                  <a :href="`/product/${product.id}/${slugify(product.name)}`" target="_blank" class="text-green-600 hover:underline mr-2">Internal</a>
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
  <div v-if="importShopUrlProcessing" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/75">
    <div class="flex flex-col items-center">
      <svg class="animate-spin h-12 w-12 text-white mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
      </svg>
      <span class="text-white text-lg font-semibold">Importing products, please wait...</span>
    </div>
  </div>
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
  name: props.vendor?.name || '',
  email: props.vendor?.email || '',
  description: props.vendor?.settings?.description || '',
  contact_email: props.vendor?.settings?.contact_email || '',
  phone_number: props.vendor?.settings?.phone_number || '',
  shop_url: props.vendor?.settings?.shop_url || '',
  banner: null,
  logo: null,
  is_active: props.vendor?.is_active ?? false,
  _token: usePage().props.csrf_token
})

// Watch for props changes and update form data
watch(() => props.vendor, (newVendor) => {
  console.log('Vendor data changed:', newVendor);
  if (newVendor) {
    editForm.name = newVendor.name || ''
    editForm.email = newVendor.email || ''
    editForm.description = newVendor.settings?.description || ''
    editForm.contact_email = newVendor.settings?.contact_email || ''
    editForm.phone_number = newVendor.settings?.phone_number || ''
    editForm.shop_url = newVendor.settings?.shop_url || ''
    editForm.banner_url = newVendor.settings?.banner_url || ''
    editForm.logo_url = newVendor.settings?.logo_url || ''
  }
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
  if (props.vendor) {
    // Update existing vendor
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
      },
      onError: () => {
      },
      data: { _method: 'put' }
    })
  } else {
    // Create new vendor
    editForm.post('/admin/vendors', {
      forceFormData: true,
      onSuccess: () => {
        router.visit('/admin/vendors')
      },
      onError: () => {
      }
    })
  }
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
      router.reload()
    },
    onError: () => {
      console.log('URL import failed')
    }
  })
}

const importShopUrlProcessing = ref(false)
const importShopUrlMessage = ref('')
const importShopUrlSuccess = ref(false)

function importFromShopUrl() {
  importShopUrlProcessing.value = true
  importShopUrlMessage.value = ''
  importShopUrlSuccess.value = false
  router.post(
    `/admin/vendors/${props.vendor.id}/import-shop-url`,
    { _token: usePage().props.csrf_token },
    {
      forceFormData: true,
      onSuccess: () => {
        importShopUrlProcessing.value = false
        importShopUrlSuccess.value = true
        importShopUrlMessage.value = 'Import Completed'
      },
      onError: (err) => {
        importShopUrlProcessing.value = false
        importShopUrlSuccess.value = false
        importShopUrlMessage.value = 'Import failed'
      }
    }
  )
}

function deleteProduct(productId) {
  if (confirm('Are you sure you want to delete this product?')) {
    const deleteForm = useForm({ _token: usePage().props.csrf_token })
    deleteForm.delete(`/admin/vendors/${props.vendor.id}/products/${productId}`, {
      onSuccess: () => {
        router.reload()
      },
      onError: () => {
        console.log('Product deletion failed')
      }
    })
  }
}

function slugify(text) {
  return text
    .toString()
    .toLowerCase()
    .replace(/\s+/g, '-')           // Replace spaces with -
    .replace(/[^\w\-]+/g, '')      // Remove all non-word chars
    .replace(/\-\-+/g, '-')        // Replace multiple - with single -
    .replace(/^-+/, '')              // Trim - from start of text
    .replace(/-+$/, '');             // Trim - from end of text
}
</script> 