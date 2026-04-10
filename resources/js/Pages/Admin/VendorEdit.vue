<template>
  <AdminLayout>
    <FormPage
      :title="vendor ? vendor.name : 'New Vendor'"
      back-label="Vendors"
      back-href="/admin/vendors"
      :tabs="[
        { id: 'general', label: 'General' },
        { id: 'contact', label: 'Contact' },
        { id: 'policies', label: 'Policies' },
        { id: 'marketing', label: 'Marketing' },
        { id: 'integration', label: 'Integration' },
        { id: 'seo', label: 'SEO' },
        { id: 'products', label: `Products (${(products || []).length})` },
      ]"
      v-model:active-tab="activeTab"
      :saving="editForm.processing"
      :saved="justSaved"
      @save="submitEditVendor"
    >
      <!-- Error banner -->
      <div v-if="Object.keys(editForm.errors).length > 0" class="mb-6 px-4 py-3 bg-[color:var(--color-danger-bg)] border border-[#FECACA] text-[#991B1B] text-sm">
        <span v-for="(error, field) in editForm.errors" :key="field">{{ Array.isArray(error) ? error[0] : error }} </span>
      </div>

      <form @submit.prevent="submitEditVendor">

        <!-- GENERAL TAB -->
        <div v-show="activeTab === 'general'">
          <FormSection title="Basic Information" :columns="2">
            <FormField label="Vendor Name" required>
              <input v-model="editForm.name" type="text" required class="w-full h-10 px-3 text-sm border border-[color:var(--color-hairline)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15" />
            </FormField>
            <FormField label="Website">
              <input v-model="editForm.shop_url" type="url" class="w-full h-10 px-3 text-sm border border-[color:var(--color-hairline)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15" />
            </FormField>
          </FormSection>

          <FormSection title="Description">
            <FormField label="About this vendor">
              <textarea v-model="editForm.description" rows="4" class="w-full px-3 py-2 text-sm border border-[color:var(--color-hairline)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15" />
            </FormField>
          </FormSection>

          <FormSection title="Location & Details" :columns="2">
            <FormField label="Location">
              <input v-model="editForm.location" type="text" class="w-full h-10 px-3 text-sm border border-[color:var(--color-hairline)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15" />
            </FormField>
            <FormField label="Founded Year">
              <input v-model.number="editForm.founded_year" type="number" class="w-full h-10 px-3 text-sm border border-[color:var(--color-hairline)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15" />
            </FormField>
          </FormSection>

          <FormSection title="Status">
            <div class="flex flex-wrap gap-6">
              <label class="flex items-center gap-2 text-sm"><input type="checkbox" v-model="editForm.is_active" class="w-4 h-4 accent-[color:var(--color-accent-600)]" /> Active</label>
              <label class="flex items-center gap-2 text-sm"><input type="checkbox" v-model="editForm.top_vendor" class="w-4 h-4 accent-[color:var(--color-accent-600)]" /> Top Vendor</label>
              <label class="flex items-center gap-2 text-sm"><input type="checkbox" v-model="editForm.featured" class="w-4 h-4 accent-[color:var(--color-accent-600)]" /> Featured</label>
              <label class="flex items-center gap-2 text-sm"><input type="checkbox" v-model="editForm.is_partner" class="w-4 h-4 accent-[color:var(--color-accent-600)]" /> Partner</label>
            </div>
          </FormSection>

          <FormSection title="Media" :columns="2">
            <FormField label="Banner Image">
              <input type="file" accept="image/*" @change="handleFileChange($event, 'banner')" class="text-sm" />
              <img v-if="bannerPreview || currentBannerUrl" :src="bannerPreview || currentBannerUrl" class="mt-2 h-20 object-cover border border-[color:var(--color-hairline)]" />
            </FormField>
            <FormField label="Logo (PNG)">
              <input type="file" accept=".png,image/png" @change="handleFileChange($event, 'logo')" class="text-sm" />
              <img v-if="logoPreview || currentLogoUrl" :src="logoPreview || currentLogoUrl" class="mt-2 h-16 object-contain border border-[color:var(--color-hairline)]" />
            </FormField>
          </FormSection>
        </div>

        <!-- CONTACT TAB -->
        <div v-show="activeTab === 'contact'">
          <FormSection title="Contact Information" :columns="2">
            <FormField label="Email">
              <input v-model="editForm.contact_email" type="email" class="w-full h-10 px-3 text-sm border border-[color:var(--color-hairline)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15" />
            </FormField>
            <FormField label="Phone">
              <input v-model="editForm.phone_number" type="tel" class="w-full h-10 px-3 text-sm border border-[color:var(--color-hairline)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15" />
            </FormField>
          </FormSection>
          <FormSection title="Business Hours">
            <FormField label="Hours" hint="e.g., Mon-Fri: 9AM-6PM EST">
              <input v-model="editForm.business_hours" type="text" class="w-full h-10 px-3 text-sm border border-[color:var(--color-hairline)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15" />
            </FormField>
          </FormSection>
        </div>

        <!-- POLICIES TAB -->
        <div v-show="activeTab === 'policies'">
          <FormSection title="Shipping">
            <FormField label="Shipping Information">
              <textarea v-model="editForm.shipping_info" rows="3" class="w-full px-3 py-2 text-sm border border-[color:var(--color-hairline)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15" />
            </FormField>
          </FormSection>
          <FormSection title="Returns">
            <FormField label="Return Policy">
              <textarea v-model="editForm.return_policy" rows="3" class="w-full px-3 py-2 text-sm border border-[color:var(--color-hairline)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15" />
            </FormField>
          </FormSection>
          <FormSection title="Payment Methods">
            <div class="flex flex-wrap gap-6">
              <label v-for="method in ['Credit Card', 'PayPal', 'Cryptocurrency', 'Bank Transfer']" :key="method" class="flex items-center gap-2 text-sm">
                <input type="checkbox" :value="method" v-model="editForm.payment_methods" class="w-4 h-4 accent-[color:var(--color-accent-600)]" />
                {{ method }}
              </label>
            </div>
          </FormSection>
        </div>

        <!-- MARKETING TAB -->
        <div v-show="activeTab === 'marketing'">
          <FormSection title="Coupon & Affiliate" :columns="2">
            <FormField label="Coupon Code">
              <input v-model="editForm.coupon_code" type="text" class="w-full h-10 px-3 text-sm border border-[color:var(--color-hairline)] ui-mono focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15" />
            </FormField>
            <FormField label="Banner Image URL">
              <input v-model="editForm.banner_image_url" type="url" class="w-full h-10 px-3 text-sm border border-[color:var(--color-hairline)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15" />
            </FormField>
          </FormSection>
          <FormSection title="Affiliate Tracking" :columns="2">
            <FormField label="Affiliate URL Template" hint="Placeholders: {product_url}, {slug}, {id}, {affiliate_tag}">
              <input v-model="editForm.affiliate_url_template" type="text" class="w-full h-10 px-3 text-sm border border-[color:var(--color-hairline)] ui-mono focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15" placeholder="https://vendor.com/{slug}?ref={affiliate_tag}" />
            </FormField>
            <FormField label="Affiliate Tag">
              <input v-model="editForm.affiliate_tag" type="text" class="w-full h-10 px-3 text-sm border border-[color:var(--color-hairline)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15" placeholder="peptidemap" />
            </FormField>
          </FormSection>
        </div>

        <!-- INTEGRATION TAB -->
        <div v-show="activeTab === 'integration'">
          <FormSection title="Platform" :columns="2">
            <FormField label="E-Commerce Platform" hint="Select the vendor's store platform for automatic product sync">
              <select v-model="editForm.api_platform" class="w-full h-10 px-3 text-sm border border-[color:var(--color-hairline)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15">
                <option :value="null">None (manual only)</option>
                <option value="woocommerce">WooCommerce</option>
                <option value="medusa">Medusa</option>
                <option value="shopify">Shopify</option>
                <option value="custom">Custom API</option>
                <option value="page_scrape">Page Scraper</option>
              </select>
            </FormField>
            <FormField label="Store URL" hint="Base URL of the vendor's store">
              <input v-model="editForm.shop_url" type="url" class="w-full h-10 px-3 text-sm border border-[color:var(--color-hairline)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15" placeholder="https://store.example.com" />
            </FormField>
          </FormSection>

          <FormSection v-if="editForm.api_platform && editForm.api_platform !== 'page_scrape'" title="API Credentials">
            <FormField label="API Key" hint="Publishable key, consumer key, or access token. Stored encrypted.">
              <input v-model="editForm.api_key" type="password" autocomplete="off" class="w-full h-10 px-3 text-sm border border-[color:var(--color-hairline)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15 ui-mono" placeholder="Enter API key..." />
            </FormField>
            <p class="text-[12px] text-[color:var(--color-ink-subtle)] mt-2">
              <span v-if="editForm.api_platform === 'woocommerce'">WooCommerce REST API consumer key + secret (format: ck_xxx / cs_xxx). Enter the consumer key here.</span>
              <span v-else-if="editForm.api_platform === 'medusa'">Medusa publishable API key (format: pk_xxx).</span>
              <span v-else-if="editForm.api_platform === 'shopify'">Shopify Admin API access token.</span>
              <span v-else>API key or access token for the vendor's API.</span>
            </p>
          </FormSection>

          <FormSection v-if="editForm.api_platform === 'page_scrape'" title="Page Scraper" description="Automatically fetches each product's page URL and extracts current prices, images, and stock status from the HTML.">
            <div class="bg-[color:var(--color-hairline-soft)] border border-[color:var(--color-hairline)] p-4 text-[13px] text-[color:var(--color-ink-muted)] space-y-2">
              <p>The page scraper will visit each product's URL and extract data using:</p>
              <ol class="list-decimal list-inside space-y-1 text-[12px]">
                <li>JSON-LD structured data (most reliable)</li>
                <li>Open Graph meta tags (og:image, og:title, product:price)</li>
                <li>Common HTML patterns as fallback</li>
              </ol>
              <p class="text-[12px] text-[color:var(--color-ink-subtle)]">Products need a valid product URL set. Images are pulled directly from product pages.</p>
            </div>
          </FormSection>
        </div>

        <!-- SEO TAB -->
        <div v-show="activeTab === 'seo'">
          <FormSection title="Search Engine Optimization">
            <FormField label="Page Title">
              <input v-model="editForm.seo_page_title" type="text" class="w-full h-10 px-3 text-sm border border-[color:var(--color-hairline)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15" />
            </FormField>
            <FormField label="Meta Description">
              <textarea v-model="editForm.seo_description" rows="3" class="w-full px-3 py-2 text-sm border border-[color:var(--color-hairline)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15" />
            </FormField>
          </FormSection>
          <FormSection title="Open Graph" :columns="2">
            <FormField label="OG Title">
              <input v-model="editForm.seo_og_title" type="text" class="w-full h-10 px-3 text-sm border border-[color:var(--color-hairline)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15" />
            </FormField>
            <FormField label="OG Image URL">
              <input v-model="editForm.seo_og_image" type="url" class="w-full h-10 px-3 text-sm border border-[color:var(--color-hairline)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15" />
            </FormField>
            <FormField label="OG Description" class="md:col-span-2">
              <textarea v-model="editForm.seo_og_description" rows="2" class="w-full px-3 py-2 text-sm border border-[color:var(--color-hairline)] focus:border-[color:var(--color-accent-500)] focus:outline-none focus:ring-2 focus:ring-[color:var(--color-accent-500)]/15" />
            </FormField>
          </FormSection>
        </div>

      </form>

      <!-- PRODUCTS TAB -->
      <div v-show="activeTab === 'products'">
        <FormSection :title="`${(products || []).length} products listed`">
          <p v-if="!products || products.length === 0" class="text-sm text-[color:var(--color-ink-subtle)] py-4">No products imported yet.</p>
          <div v-else class="text-sm text-[color:var(--color-ink-muted)]">
            View and manage this vendor's products below. Use the Products section in the sidebar for bulk management.
          </div>
        </FormSection>
      </div>

    </FormPage>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, usePage, Link, router } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import FormPage from '@/components/admin/FormPage.vue'
import FormSection from '@/components/admin/FormSection.vue'
import FormField from '@/components/admin/FormField.vue'
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

const activeTab = ref('general')
const justSaved = ref(false)

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
  location: props.vendor?.location || '',
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
  affiliate_url_template: props.vendor?.affiliate_url_template || '',
  affiliate_tag: props.vendor?.affiliate_tag || '',
  api_platform: props.vendor?.settings?.api_platform || null,
  api_key: '', // never pre-filled for security — blank means "don't change"
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
    editForm.affiliate_url_template = newVendor.affiliate_url_template || ''
    editForm.affiliate_tag = newVendor.affiliate_tag || ''
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
        justSaved.value = true
        setTimeout(() => justSaved.value = false, 3000)
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