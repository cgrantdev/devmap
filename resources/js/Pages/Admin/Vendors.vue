<template>
  <AdminLayout>
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-3xl text-gray-900 mb-2">Vendor Management</h1>
      <p class="text-gray-600">Manage all vendors and their information</p>
    </div>

    <!-- Success Message -->
    <div v-if="$page.props.flash.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
      {{ $page.props.flash.success }}
    </div>
    
    <!-- Error Message -->
    <div v-if="$page.props.flash.error" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
      {{ $page.props.flash.error }}
    </div>

    <!-- Actions Bar -->
    <div class="bg-white rounded-lg border border-gray-200 p-4 mb-6">
      <div class="flex items-center justify-between gap-4">
        <div class="flex-1 relative">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" aria-hidden="true">
            <path d="m21 21-4.34-4.34" />
            <circle cx="11" cy="11" r="8"></circle>
          </svg>
          <input
            type="text"
            placeholder="Search vendors..."
            v-model="searchTerm"
            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        <button 
          @click="openAddModal"
          class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Add Vendor
        </button>
      </div>
    </div>

    <!-- Vendors Table -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="px-6 py-3 text-left text-xs text-gray-500 uppercase tracking-wider">Vendor</th>
              <th class="px-6 py-3 text-left text-xs text-gray-500 uppercase tracking-wider">Location</th>
              <th class="px-6 py-3 text-left text-xs text-gray-500 uppercase tracking-wider">Rating</th>
              <th class="px-6 py-3 text-left text-xs text-gray-500 uppercase tracking-wider">Reviews</th>
              <th class="px-6 py-3 text-left text-xs text-gray-500 uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-left text-xs text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="vendor in filteredVendors" :key="vendor.id" class="hover:bg-gray-50">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div v-if="vendor.settings?.logo">
                    <img :src="vendor.settings.logo" :alt="vendor.name" class="w-10 h-full object-cotain rounded-lg flex items-center justify-center text-white select-none" />
                  </div>
                  <div v-else class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white">
                    {{ vendor.name.substring(0, 2).toUpperCase() }}
                  </div>
                  <div>
                    <div class="text-sm text-gray-900">{{ vendor.name }}</div>
                    <a
                      v-if="vendor.settings?.shop_url"
                      :href="vendor.settings.shop_url"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="text-xs text-blue-600 hover:underline flex items-center gap-1"
                    >
                      {{ (vendor.settings.shop_url || '').replace('https://', '').replace('http://', '') }}
                      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-external-link w-3 h-3" aria-hidden="true">
                        <path d="M15 3h6v6" />
                        <path d="M10 14 21 3" />
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
                      </svg>
                    </a>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-1 text-sm text-gray-600">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  {{ vendor.location || '-' }}
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-1">
                  <svg class="lucide lucide-star w-4 h-4 fill-yellow-400 text-yellow-400" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z" />
                  </svg>
                  <span class="text-sm text-gray-900">{{ vendor.rating_average || vendor.rating || '0.0' }}</span>
                </div>
              </td>
              <td class="px-6 py-4">
                <span class="text-sm text-gray-900">{{ vendor.rating_count || vendor.reviewCount || 0 }}</span>
              </td>
              <td class="px-6 py-4">
                <div class="flex gap-2">
                  <span v-if="vendor.is_active" class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs">
                    Active
                  </span>
                  <span v-else class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs">
                    Inactive
                  </span>
                  <span v-if="vendor.settings?.top_vendor" class="bg-blue-100 text-blue-700 px-2 py-1 rounded text-xs">
                    Top Vendor
                  </span>
                  <span v-if="vendor.settings?.featured" class="bg-purple-100 text-purple-700 px-2 py-1 rounded text-xs">
                    Featured
                  </span>
                  <span v-if="vendor.settings?.is_partner" class="bg-indigo-100 text-indigo-700 px-2 py-1 rounded text-xs">
                    Partner
                  </span>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <button
                    @click="openEditModal(vendor)"
                    class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                    title="Edit"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  <button
                    @click="deleteVendor(vendor)"
                    :disabled="form.processing"
                    class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                    title="Delete"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="filteredVendors.length === 0" class="bg-white rounded-lg border border-gray-200 p-6 text-center text-gray-500">
      No vendors found.
    </div>

    <!-- Edit Vendor Modal -->
    <div
      v-if="showEditModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="closeEditModal"
    >
      <div class="bg-white rounded-lg max-w-3xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200">
          <h2 class="text-2xl text-gray-900">{{ isEditMode ? 'Edit Vendor' : 'Add Vendor' }}</h2>
        </div>

        <form @submit.prevent="submitVendor" class="p-6 space-y-6">
          <!-- Error Messages -->
          <div v-if="Object.keys(editForm.errors).length > 0" class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
            <p class="font-medium mb-2">Please fix the following errors:</p>
            <ul class="list-disc list-inside text-sm">
              <li v-for="(error, field) in editForm.errors" :key="field">
                {{ Array.isArray(error) ? error[0] : error }}
              </li>
            </ul>
          </div>

          <!-- Basic Info -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm text-gray-700 mb-2">Vendor Name *</label>
              <input
                v-model="editForm.name"
                type="text"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="block text-sm text-gray-700 mb-2">Website *</label>
              <input
                v-model="editForm.shop_url"
                type="url"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm text-gray-700 mb-2">Email</label>
              <input
                v-model="editForm.email"
                type="email"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="block text-sm text-gray-700 mb-2">Phone</label>
              <input
                v-model="editForm.phone_number"
                type="tel"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>            
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm text-gray-700 mb-2">Location *</label>
              <input
                v-model="editForm.location"
                type="text"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="block text-sm text-gray-700 mb-2">Founded Year</label>
              <input
                v-model.number="editForm.founded_year"
                type="number"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>


          <div>
            <label class="block text-sm text-gray-700 mb-2">Description</label>
            <textarea
              v-model="editForm.description"
              rows="3"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            ></textarea>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm text-gray-700 mb-2">Coupon Code</label>
              <input
                v-model="editForm.coupon_code"
                type="text"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="block text-sm text-gray-700 mb-2">Banner Image URL</label>
              <input
                v-model="editForm.banner_image_url"
                type="url"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>

          <div>
            <label class="block text-sm text-gray-700 mb-2">Shipping Information</label>
            <textarea
              v-model="editForm.shipping_info"
              rows="2"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            ></textarea>
          </div>

          <div>
            <label class="block text-sm text-gray-700 mb-2">Return Policy</label>
            <textarea
              v-model="editForm.return_policy"
              rows="2"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            ></textarea>
          </div>

          <div>
            <label class="block text-sm text-gray-700 mb-2">Business Hours</label>
            <input
              v-model="editForm.business_hours"
              type="text"
              placeholder="e.g., Mon-Fri: 9AM-6PM EST"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <!-- Payment Methods -->
          <div>
            <label class="block text-sm text-gray-700 mb-2">Payment Methods</label>
            <div class="flex gap-6 flex-wrap">
              <label class="flex items-center gap-2">
                <input
                  type="checkbox"
                  :value="'Credit Card'"
                  v-model="editForm.payment_methods"
                  class="w-4 h-4 text-blue-600"
                />
                <span class="text-sm text-gray-700">Credit Card</span>
              </label>
              <label class="flex items-center gap-2">
                <input
                  type="checkbox"
                  :value="'PayPal'"
                  v-model="editForm.payment_methods"
                  class="w-4 h-4 text-blue-600"
                />
                <span class="text-sm text-gray-700">PayPal</span>
              </label>
              <label class="flex items-center gap-2">
                <input
                  type="checkbox"
                  :value="'Cryptocurrency'"
                  v-model="editForm.payment_methods"
                  class="w-4 h-4 text-blue-600"
                />
                <span class="text-sm text-gray-700">Cryptocurrency</span>
              </label>
              <label class="flex items-center gap-2">
                <input
                  type="checkbox"
                  :value="'Bank Transfer'"
                  v-model="editForm.payment_methods"
                  class="w-4 h-4 text-blue-600"
                />
                <span class="text-sm text-gray-700">Bank Transfer</span>
              </label>
            </div>
          </div>

          <!-- Checkboxes -->
          <div>
            <label class="block text-sm text-gray-700 mb-2">Status</label> 
            <div class="flex gap-6 flex-wrap">
              <label class="flex items-center gap-2">
                <input
                  type="checkbox"
                  v-model="editForm.is_active"
                  class="w-4 h-4 text-blue-600"
                />
                <span class="text-sm text-gray-700">Active</span>
              </label>
            <label class="flex items-center gap-2">
              <input
                type="checkbox"
                v-model="editForm.top_vendor"
                class="w-4 h-4 text-blue-600"
              />
              <span class="text-sm text-gray-700">Top Vendor</span>
            </label>
            <label class="flex items-center gap-2">
              <input
                type="checkbox"
                v-model="editForm.featured"
                class="w-4 h-4 text-blue-600"
              />
              <span class="text-sm text-gray-700">Featured</span>
            </label>
            <label class="flex items-center gap-2">
              <input
                type="checkbox"
                v-model="editForm.is_partner"
                class="w-4 h-4 text-blue-600"
              />
              <span class="text-sm text-gray-700">Partner</span>
            </label>            
            </div>
          </div>

          <!-- SEO Data Section -->
          <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">SEO Data</h3>
            <div class="space-y-4">
              <div>
                <label class="block text-sm text-gray-700 mb-2">Page Title</label>
                <input
                  v-model="editForm.seo_page_title"
                  type="text"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Enter page title for SEO"
                />
              </div>
              <div>
                <label class="block text-sm text-gray-700 mb-2">Description</label>
                <textarea
                  v-model="editForm.seo_description"
                  rows="3"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Enter meta description for SEO"
                ></textarea>
              </div>
              <div>
                <label class="block text-sm text-gray-700 mb-2">OG:Title</label>
                <input
                  v-model="editForm.seo_og_title"
                  type="text"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Enter Open Graph title"
                />
              </div>
              <div>
                <label class="block text-sm text-gray-700 mb-2">OG:Image</label>
                <input
                  v-model="editForm.seo_og_image"
                  type="url"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Enter Open Graph image URL"
                />
              </div>
              <div>
                <label class="block text-sm text-gray-700 mb-2">OG:Description</label>
                <textarea
                  v-model="editForm.seo_og_description"
                  rows="3"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Enter Open Graph description"
                ></textarea>
              </div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="p-6 border-t border-gray-200 flex justify-end gap-3">
            <button
              type="button"
              @click="closeEditModal"
              class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="editForm.processing"
              class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
            >
              {{ editForm.processing ? (isEditMode ? 'Saving...' : 'Adding...') : (isEditMode ? 'Save Changes' : 'Add Vendor') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Link, useForm, usePage, router } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import { ref, computed, watch } from 'vue'
import { useToast as useVueToastification } from 'vue-toastification'

const props = defineProps({
  vendors: {
    type: Array,
    default: () => []
  },
  locations: {
    type: Array,
    default: () => []
  }
})

// Only use toast for manual error messages
// Success messages are handled automatically by Layout component via flash messages
const toast = useVueToastification()
const toastError = (message) => toast.error(message, { timeout: 4000 })

const searchTerm = ref('')
const showEditModal = ref(false)
const editingVendor = ref(null)
const isEditMode = computed(() => editingVendor.value !== null)

const bannerPreview = ref(null)
const logoPreview = ref(null)
const cacheBuster = ref(Date.now())

const currentBannerUrl = computed(() => {
  return editingVendor.value?.settings?.banner_url || editingVendor.value?.settings?.banner || ''
})

const currentLogoUrl = computed(() => {
  return editingVendor.value?.settings?.logo_url || editingVendor.value?.settings?.logo || ''
})

const editForm = useForm({
  name: '',
  email: '',
  description: '',
  phone_number: '',
  location: '',
  shop_url: '',
  founded_year: '2026',
  coupon_code: '',
  shipping_info: '',
  return_policy: '',
  business_hours: '',
  banner_image_url: '',
  is_active: false,
  top_vendor: false,
  featured: false,
  is_partner: false,
  payment_methods: [],
  seo_page_title: '',
  seo_description: '',
  seo_og_title: '',
  seo_og_image: '',
  seo_og_description: '',
  banner: null,
  logo: null,
  _token: usePage().props.csrf_token
})

const filteredVendors = computed(() => {
  if (!searchTerm.value) {
    return props.vendors
  }
  
  const term = searchTerm.value.toLowerCase()
  return props.vendors.filter(vendor =>
    vendor.name.toLowerCase().includes(term) ||
    (vendor.location && vendor.location.toLowerCase().includes(term)) ||
    (vendor.email && vendor.email.toLowerCase().includes(term))
  )
})

const form = useForm({
  _token: usePage().props.csrf_token
})

function openAddModal() {
  editingVendor.value = null
  editForm.reset()
  editForm.clearErrors()
  bannerPreview.value = null
  logoPreview.value = null
  showEditModal.value = true
}

function openEditModal(vendor) {
  editingVendor.value = vendor
  editForm.name = vendor.name || ''
  editForm.email = vendor.email || vendor.settings?.contact_email || ''
  editForm.description = vendor.settings?.description || ''
  editForm.phone_number = vendor.settings?.phone_number || ''
  editForm.location = vendor.location || ''
  editForm.shop_url = vendor.settings?.shop_url || ''
  editForm.founded_year = vendor.settings?.founded_year || null
  editForm.coupon_code = vendor.settings?.coupon_code || ''
  editForm.shipping_info = vendor.settings?.shipping_info || ''
  editForm.return_policy = vendor.settings?.return_policy || ''
  editForm.business_hours = vendor.settings?.business_hours || ''
  editForm.banner_image_url = vendor.settings?.banner_image_url || ''
  editForm.is_active = vendor.is_active ?? false
  editForm.top_vendor = vendor.settings?.top_vendor || false
  editForm.featured = vendor.settings?.featured || false
  editForm.is_partner = vendor.settings?.is_partner || false
  editForm.payment_methods = vendor.settings?.payment_methods || []
  editForm.seo_page_title = vendor.settings?.seo_page_title || ''
  editForm.seo_description = vendor.settings?.seo_description || ''
  editForm.seo_og_title = vendor.settings?.seo_og_title || ''
  editForm.seo_og_image = vendor.settings?.seo_og_image || ''
  editForm.seo_og_description = vendor.settings?.seo_og_description || ''
  editForm.banner = null
  editForm.logo = null
  bannerPreview.value = null
  logoPreview.value = null
  cacheBuster.value = Date.now()
  showEditModal.value = true
}

function closeEditModal() {
  showEditModal.value = false
  editingVendor.value = null
  editForm.reset()
  editForm.clearErrors()
  bannerPreview.value = null
  logoPreview.value = null
}

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

function submitVendor() {
  if (isEditMode.value) {
    // Update existing vendor
    editForm.post(`/admin/vendors/${editingVendor.value.id}`, {
      forceFormData: true,
      preserveState: true,
      preserveScroll: true,
      data: { _method: 'put' },
      onSuccess: () => {
        bannerPreview.value = null
        logoPreview.value = null
        cacheBuster.value = Date.now()
        const fileInputs = document.querySelectorAll('input[type="file"]')
        fileInputs.forEach(input => input.value = '')
        closeEditModal()
        router.reload({ only: ['vendors'] })
      },
      onError: (errors) => {
        if (errors && Object.keys(errors).length > 0) {
          toastError('Please fix the errors and try again.')
        }
      }
    })
  } else {
    // Create new vendor
    editForm.post('/admin/vendors', {
      forceFormData: true,
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        closeEditModal()
        router.reload({ only: ['vendors'] })
      },
      onError: (errors) => {
        if (errors && Object.keys(errors).length > 0) {
          toastError('Please fix the errors and try again.')
        }
      }
    })
  }
}

function deleteVendor(vendor) {
  if (confirm('Are you sure you want to delete this vendor?')) {
    form.delete(`/admin/vendors/${vendor.id}`, {
      onSuccess: () => {
        // Success toast will be shown automatically from flash message
      },
      onError: (errors) => {
        toastError('Failed to delete vendor. Please try again.')
        console.error(errors)
      }
    })
  }
}
</script> 