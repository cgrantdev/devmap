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
      <!-- Flash messages are now handled by toast notifications -->
      
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
          <!-- Basic Info -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm text-slate-700 mb-2">Vendor Name *</label>
              <input
                v-model="editForm.name"
                type="text"
                required
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="block text-sm text-slate-700 mb-2">Website *</label>
              <input
                v-model="editForm.shop_url"
                type="url"
                required
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm text-slate-700 mb-2">Email</label>
              <input
                v-model="editForm.contact_email"
                type="email"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="block text-sm text-slate-700 mb-2">Phone</label>
              <input
                v-model="editForm.phone_number"
                type="tel"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm text-slate-700 mb-2">Location *</label>
              <select
                v-model="editForm.location_id"
                required
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option :value="null">Select Location</option>
                <option v-for="location in locations" :key="location.id" :value="location.id">
                  {{ location.name }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-sm text-slate-700 mb-2">Founded Year</label>
              <input
                v-model.number="editForm.founded_year"
                type="number"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>

          <div>
            <label class="block text-sm text-slate-700 mb-2">Description</label>
            <textarea
              v-model="editForm.description"
              rows="3"
              class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            ></textarea>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm text-slate-700 mb-2">Coupon Code</label>
              <input
                v-model="editForm.coupon_code"
                type="text"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="block text-sm text-slate-700 mb-2">Banner Image URL</label>
              <input
                v-model="editForm.banner_image_url"
                type="url"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>

          <div>
            <label class="block text-sm text-slate-700 mb-2">Shipping Information</label>
            <textarea
              v-model="editForm.shipping_info"
              rows="2"
              class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            ></textarea>
          </div>

          <div>
            <label class="block text-sm text-slate-700 mb-2">Return Policy</label>
            <textarea
              v-model="editForm.return_policy"
              rows="2"
              class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            ></textarea>
          </div>

          <div>
            <label class="block text-sm text-slate-700 mb-2">Business Hours</label>
            <input
              v-model="editForm.business_hours"
              type="text"
              placeholder="e.g., Mon-Fri: 9AM-6PM EST"
              class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <!-- File Uploads -->
          <div class="grid grid-cols-2 gap-4 items-end">
            <div>
              <label class="block text-sm text-slate-700 mb-2">Banner (File Upload)</label>
              <input @change="e => handleFileChange(e, 'banner')" type="file" accept="image/*" class="w-full border border-slate-300 rounded-lg px-3 py-2" />
              <div v-if="bannerPreview" class="mt-2">
                <img :src="bannerPreview" alt="Banner Preview" class="h-24 rounded-lg object-cover w-full" loading="lazy" />
              </div>
              <div v-else-if="currentBannerUrl" class="mt-2">
                <img :src="currentBannerUrl + '?t=' + cacheBuster" alt="Current Banner" class="h-24 rounded-lg object-cover w-full" loading="lazy" />
              </div>
            </div>
            <div>
              <label class="block text-sm text-slate-700 mb-2">Logo (File Upload)</label>
              <input @change="e => handleFileChange(e, 'logo')" type="file" accept="image/*,.svg" class="w-full border border-slate-300 rounded-lg px-3 py-2" />
              <div v-if="logoPreview" class="mt-2">
                <img :src="logoPreview" alt="Logo Preview" class="h-24 w-24 rounded-full object-cover mx-auto" loading="lazy" />
              </div>
              <div v-else-if="currentLogoUrl" class="mt-2">
                <img :src="currentLogoUrl + '?t=' + cacheBuster" alt="Current Logo" class="h-24 w-24 rounded-full object-cover mx-auto" loading="lazy" />
              </div>
            </div>
          </div>

          <!-- Checkboxes -->
          <div class="flex gap-6 flex-wrap">
            <label class="flex items-center gap-2">
              <input
                type="checkbox"
                v-model="editForm.is_active"
                class="w-4 h-4 text-blue-600"
              />
              <span class="text-sm text-slate-700">Active</span>
            </label>
            <label class="flex items-center gap-2">
              <input
                type="checkbox"
                v-model="editForm.top_vendor"
                class="w-4 h-4 text-blue-600"
              />
              <span class="text-sm text-slate-700">Top Vendor</span>
            </label>
            <label class="flex items-center gap-2">
              <input
                type="checkbox"
                v-model="editForm.featured"
                class="w-4 h-4 text-blue-600"
              />
              <span class="text-sm text-slate-700">Featured</span>
            </label>
            <label class="flex items-center gap-2">
              <input
                type="checkbox"
                v-model="editForm.is_partner"
                class="w-4 h-4 text-blue-600"
              />
              <span class="text-sm text-slate-700">Partner</span>
            </label>
          </div>

          <!-- Payment Methods -->
          <div>
            <label class="block text-sm text-slate-700 mb-3 font-semibold">Payment Methods</label>
            <div class="flex gap-6 flex-wrap">
              <label class="flex items-center gap-2">
                <input
                  type="checkbox"
                  :value="'Credit Card'"
                  v-model="editForm.payment_methods"
                  class="w-4 h-4 text-blue-600"
                />
                <span class="text-sm text-slate-700">Credit Card</span>
              </label>
              <label class="flex items-center gap-2">
                <input
                  type="checkbox"
                  :value="'PayPal'"
                  v-model="editForm.payment_methods"
                  class="w-4 h-4 text-blue-600"
                />
                <span class="text-sm text-slate-700">PayPal</span>
              </label>
              <label class="flex items-center gap-2">
                <input
                  type="checkbox"
                  :value="'Cryptocurrency'"
                  v-model="editForm.payment_methods"
                  class="w-4 h-4 text-blue-600"
                />
                <span class="text-sm text-slate-700">Cryptocurrency</span>
              </label>
              <label class="flex items-center gap-2">
                <input
                  type="checkbox"
                  :value="'Bank Transfer'"
                  v-model="editForm.payment_methods"
                  class="w-4 h-4 text-blue-600"
                />
                <span class="text-sm text-slate-700">Bank Transfer</span>
              </label>
            </div>
          </div>

          <div class="flex justify-end pt-4 border-t border-slate-200 gap-3">
            <Link href="/admin/vendors" class="px-6 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
              Cancel
            </Link>
            <button type="submit" :disabled="editForm.processing" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors disabled:opacity-50">
              {{ editForm.processing ? 'Saving...' : (vendor ? 'Save Changes' : 'Add Vendor') }}
            </button>
          </div>
        </form>
      </div>
      <!-- XML Import Card (only show when editing) -->
      <div v-if="vendor" class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">Import Products (XML)</h2>
        <!-- Import from Shop URL Button -->
        <div class="mb-4">
          <button @click="importFromShopUrl" class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 disabled:opacity-50">
            Import Products from Shop URL
          </button>
          <div v-if="importShopUrlMessage" :class="['mt-2', importShopUrlSuccess ? 'text-green-600' : 'text-red-600']">
            {{ importShopUrlMessage }}
          </div>
        </div>
        <!-- Import messages are now handled by toast notifications -->
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
        <EasyDataTable
          :headers="productHeaders"
          :items="currentProducts"
          :search-field="productSearchField"
          :search-value="productSearchValue"
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
          <template #item-second_price="{ second_price }">
            {{ second_price ? '$' + second_price : '-' }}
          </template>
          <template #item-product_url="{ product_url }">
            <a v-if="product_url" :href="product_url" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">External</a>
            <span v-else class="text-gray-400">-</span>
          </template>
          <template #item-actions="{ id, name }">
            <a :href="`/product/${id}/${slugify(name)}`" target="_blank" class="text-green-600 hover:underline mr-2">Internal</a>
            <button @click="deleteProduct(id)" class="text-red-600 hover:text-red-900">Delete</button>
          </template>
        </EasyDataTable>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, usePage, Link } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import { router } from '@inertiajs/vue3'
import EasyDataTable from 'vue3-easy-data-table'
import 'vue3-easy-data-table/dist/style.css'
import { useAdminLoading } from '../../composables/useAdminLoading'
import { useToast as useVueToastification } from 'vue-toastification'

// Only use toast for manual error messages
// Success messages are handled automatically by Layout component via flash messages
const toast = useVueToastification()
const toastError = (message) => toast.error(message, { timeout: 4000 })

const props = defineProps({
  vendor: Object,
  products: {
    type: Array,
    default: () => []
  },
  locations: {
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

const productSearchValue = ref('')
const productSearchField = ['name']

const productHeaders = [
  { text: 'Image', value: 'image_url', sortable: false },
  { text: 'Name', value: 'name', sortable: true },
  { text: 'Price', value: 'price', sortable: true },
  { text: 'Discount Price', value: 'discount_price', sortable: true },
  { text: 'Second Price', value: 'second_price', sortable: true },
  { text: 'Product URL', value: 'product_url', sortable: false },
  { text: 'Actions', value: 'actions', sortable: false }
]

const editForm = useForm({
  name: props.vendor?.name || '',
  email: props.vendor?.email || '',
  description: props.vendor?.settings?.description || '',
  contact_email: props.vendor?.settings?.contact_email || '',
  phone_number: props.vendor?.settings?.phone_number || '',
  location_id: props.vendor?.settings?.location_id || null,
  shop_url: props.vendor?.settings?.shop_url || '',
  founded_year: props.vendor?.settings?.founded_year || null,
  coupon_code: props.vendor?.settings?.coupon_code || '',
  shipping_info: props.vendor?.settings?.shipping_info || '',
  return_policy: props.vendor?.settings?.return_policy || '',
  business_hours: props.vendor?.settings?.business_hours || '',
  banner_image_url: props.vendor?.settings?.banner_image_url || '',
  top_vendor: props.vendor?.settings?.top_vendor || false,
  featured: props.vendor?.settings?.featured || false,
  is_partner: props.vendor?.settings?.is_partner || false,
  payment_methods: props.vendor?.settings?.payment_methods || [],
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
    editForm.location_id = newVendor.settings?.location_id || null
    editForm.shop_url = newVendor.settings?.shop_url || ''
    editForm.founded_year = newVendor.settings?.founded_year || null
    editForm.coupon_code = newVendor.settings?.coupon_code || ''
    editForm.shipping_info = newVendor.settings?.shipping_info || ''
    editForm.return_policy = newVendor.settings?.return_policy || ''
    editForm.business_hours = newVendor.settings?.business_hours || ''
    editForm.banner_image_url = newVendor.settings?.banner_image_url || ''
    editForm.top_vendor = newVendor.settings?.top_vendor || false
    editForm.featured = newVendor.settings?.featured || false
    editForm.is_partner = newVendor.settings?.is_partner || false
    editForm.payment_methods = newVendor.settings?.payment_methods || []
    editForm.is_active = newVendor.is_active ?? false
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
        // Toast will be shown automatically from flash message
      },
      onError: (errors) => {
        if (errors && Object.keys(errors).length > 0) {
          toastError('Please fix the errors and try again.')
        }
      },
      data: { _method: 'put' }
    })
  } else {
    // Create new vendor
    editForm.post('/admin/vendors', {
      forceFormData: true,
      onSuccess: () => {
        // Toast will be shown automatically from flash message
        router.visit('/admin/vendors')
      },
      onError: (errors) => {
        if (errors && Object.keys(errors).length > 0) {
          toastError('Please fix the errors and try again.')
        }
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

const { setLoading } = useAdminLoading()
const importShopUrlMessage = ref('')
const importShopUrlSuccess = ref(false)

function importFromShopUrl() {
  setLoading(true, 'Importing products, please wait...')
  importShopUrlMessage.value = ''
  importShopUrlSuccess.value = false
  router.post(
    `/admin/vendors/${props.vendor.id}/import-shop-url`,
    { _token: usePage().props.csrf_token },
    {
      forceFormData: true,
      onSuccess: () => {
        setLoading(false)
        importShopUrlSuccess.value = true
        importShopUrlMessage.value = 'Import Completed'
      },
      onError: (err) => {
        setLoading(false)
        importShopUrlSuccess.value = false
        importShopUrlMessage.value = 'Import failed'
      },
      onFinish: () => {
        setLoading(false)
      }
    }
  )
}

function deleteProduct(productId) {
  if (confirm('Are you sure you want to delete this product?')) {
    const deleteForm = useForm({ _token: usePage().props.csrf_token })
    deleteForm.delete(`/admin/vendors/${props.vendor.id}/products/${productId}`, {
      onSuccess: () => {
        // Toast will be shown automatically from flash message
        router.reload()
      },
      onError: () => {
        toastError('Failed to delete product. Please try again.')
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