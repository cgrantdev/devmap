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

      <!-- PRODUCTS TAB (outside the form) -->
      <div v-show="activeTab === 'products'">
        <FormSection :title="`${(products || []).length} products`">
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

          <!-- Affiliate tracking -->
          <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg space-y-3">
            <div>
              <label class="block text-sm font-semibold text-slate-800 mb-1">Affiliate URL Template</label>
              <input
                v-model="editForm.affiliate_url_template"
                type="text"
                placeholder="https://vendor.com/product/{slug}?ref=peptidemap"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm"
              />
              <p class="text-xs text-slate-600 mt-1">
                Placeholders: <code>{product_url}</code>, <code>{slug}</code>, <code>{id}</code>, <code>{affiliate_tag}</code>. Leave blank to use the raw product URL.
              </p>
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-800 mb-1">Affiliate Tag</label>
              <input
                v-model="editForm.affiliate_tag"
                type="text"
                placeholder="peptidemap"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
              <p class="text-xs text-slate-600 mt-1">Used as the <code>{affiliate_tag}</code> placeholder in the template above.</p>
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