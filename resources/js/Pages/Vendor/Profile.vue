<template>
  <Layout>
    <div class="mb-8 flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Vendor Profile</h1>
        <p class="text-gray-600 mt-2">{{ isEditing ? 'Edit your vendor information' : 'View your vendor signup information' }}</p>
      </div>
      <div v-if="!isEditing && vendor">
        <button
          @click="startEditing"
          class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-edit">
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
          </svg>
          Edit Profile
        </button>
      </div>
    </div>

    <!-- Success Message -->
    <div v-if="$page.props.flash?.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
      {{ $page.props.flash.success }}
    </div>

    <!-- Error Message -->
    <div v-if="$page.props.flash?.error" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
      {{ $page.props.flash.error }}
    </div>

    <div v-if="!vendor" class="bg-white rounded-lg border border-gray-200 p-8 text-center">
      <p class="text-gray-600">No vendor profile found. Please contact support.</p>
    </div>

    <form v-else @submit.prevent="submitForm" class="space-y-6">
      <!-- Company Information Section -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-building-2">
            <path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"></path>
            <path d="M6 12h12"></path>
            <path d="M6 6h12"></path>
            <path d="M6 18h12"></path>
            <path d="M10 6v6"></path>
            <path d="M14 6v6"></path>
          </svg>
          Company Information
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Company Name <span class="text-red-500">*</span></label>
            <input
              v-if="isEditing"
              v-model="form.name"
              type="text"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="{ 'border-red-500': form.errors.name }"
            />
            <div v-else class="p-3 bg-gray-50 rounded-lg border border-gray-200 text-gray-900">
              {{ vendor.name || 'N/A' }}
            </div>
            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Website <span class="text-red-500">*</span></label>
            <input
              v-if="isEditing"
              v-model="form.website"
              type="url"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="{ 'border-red-500': form.errors.website }"
              placeholder="https://example.com"
            />
            <div v-else class="p-3 bg-gray-50 rounded-lg border border-gray-200">
              <a v-if="vendor.settings?.website" :href="vendor.settings.website" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:text-blue-800 underline">
                {{ vendor.settings.website }}
              </a>
              <span v-else class="text-gray-500">N/A</span>
            </div>
            <p v-if="form.errors.website" class="mt-1 text-sm text-red-600">{{ form.errors.website }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Year Established</label>
            <input
              v-if="isEditing"
              v-model.number="form.founded_year"
              type="number"
              min="1800"
              :max="new Date().getFullYear()"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="{ 'border-red-500': form.errors.founded_year }"
            />
            <div v-else class="p-3 bg-gray-50 rounded-lg border border-gray-200 text-gray-900">
              {{ vendor.settings?.founded_year || 'N/A' }}
            </div>
            <p v-if="form.errors.founded_year" class="mt-1 text-sm text-red-600">{{ form.errors.founded_year }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Location/Country <span class="text-red-500">*</span></label>
            <select
              v-if="isEditing"
              v-model.number="form.location_id"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="{ 'border-red-500': form.errors.location_id }"
            >
              <option value="">Select a location</option>
              <option v-for="location in locations" :key="location.id" :value="location.id">
                {{ location.name }}
              </option>
            </select>
            <div v-else class="p-3 bg-gray-50 rounded-lg border border-gray-200 text-gray-900">
              {{ vendor.settings?.location || 'N/A' }}
            </div>
            <p v-if="form.errors.location_id" class="mt-1 text-sm text-red-600">{{ form.errors.location_id }}</p>
          </div>
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Company Logo</label>
            <div v-if="isEditing" class="space-y-3">
              <div v-if="logoPreview || vendor.settings?.logo" class="flex items-center gap-4">
                <img :src="logoPreview || vendor.settings?.logo" alt="Logo Preview" class="h-24 w-auto object-contain border border-gray-300 rounded">
                <button
                  type="button"
                  @click="removeLogo"
                  class="text-red-600 hover:text-red-800 text-sm"
                >
                  Remove
                </button>
              </div>
              <input
                type="file"
                @change="handleLogoUpload"
                accept="image/png"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
              <p class="text-sm text-gray-500">Accepted format: PNG only (Max: 2MB)</p>
            </div>
            <div v-else-if="vendor.settings?.logo" class="p-3 bg-gray-50 rounded-lg border border-gray-200">
              <img :src="vendor.settings.logo" alt="Company Logo" class="h-24 w-auto object-contain">
            </div>
            <div v-else class="p-3 bg-gray-50 rounded-lg border border-gray-200 text-gray-500">
              No logo uploaded
            </div>
            <p v-if="form.errors.logo" class="mt-1 text-sm text-red-600">{{ form.errors.logo }}</p>
          </div>
        </div>
      </div>

      <!-- Contact Information Section -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-contact-round">
            <path d="M16 7a4 4 0 0 1-8 0"></path>
            <circle cx="12" cy="12" r="10"></circle>
            <path d="M12 16v-4"></path>
            <path d="M8 12h8"></path>
          </svg>
          Contact Information
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
            <div class="p-3 bg-gray-50 rounded-lg border border-gray-200 text-gray-900">
              {{ vendor.user?.name || 'N/A' }}
            </div>
            <p class="mt-1 text-xs text-gray-500">This is your account name and cannot be changed here.</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
              <a :href="`mailto:${vendor.user?.email || vendor.settings?.contact_email}`" class="text-blue-600 hover:text-blue-800">
                {{ vendor.user?.email || vendor.settings?.contact_email || 'N/A' }}
              </a>
            </div>
            <p class="mt-1 text-xs text-gray-500">This is your account email and cannot be changed here.</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Contact Email <span class="text-red-500">*</span></label>
            <input
              v-if="isEditing"
              v-model="form.contact_email"
              type="email"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="{ 'border-red-500': form.errors.contact_email }"
              placeholder="contact@example.com"
            />
            <div v-else class="p-3 bg-gray-50 rounded-lg border border-gray-200">
              <a v-if="vendor.settings?.contact_email" :href="`mailto:${vendor.settings.contact_email}`" class="text-blue-600 hover:text-blue-800">
                {{ vendor.settings.contact_email }}
              </a>
              <span v-else class="text-gray-500">N/A</span>
            </div>
            <p v-if="form.errors.contact_email" class="mt-1 text-sm text-red-600">{{ form.errors.contact_email }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
            <input
              v-if="isEditing"
              v-model="form.phone_number"
              type="tel"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="{ 'border-red-500': form.errors.phone_number }"
              placeholder="+1 (555) 123-4567"
            />
            <div v-else class="p-3 bg-gray-50 rounded-lg border border-gray-200 text-gray-900">
              {{ vendor.settings?.phone_number || 'N/A' }}
            </div>
            <p v-if="form.errors.phone_number" class="mt-1 text-sm text-red-600">{{ form.errors.phone_number }}</p>
          </div>
        </div>
      </div>

      <!-- Business Information Section -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-briefcase">
            <path d="M16 20V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
            <rect width="20" height="14" x="2" y="6" rx="2"></rect>
          </svg>
          Business Information
        </h2>
        <div class="space-y-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Company Description</label>
            <textarea
              v-if="isEditing"
              v-model="form.description"
              rows="6"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="{ 'border-red-500': form.errors.description }"
              placeholder="Describe your company..."
            ></textarea>
            <div v-else class="p-3 bg-gray-50 rounded-lg border border-gray-200 text-gray-900 whitespace-pre-wrap min-h-[100px]">
              {{ vendor.settings?.description || 'N/A' }}
            </div>
            <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Payment Methods</label>
            <div v-if="isEditing" class="space-y-2">
              <label v-for="method in paymentMethodOptions" :key="method" class="flex items-center gap-2">
                <input
                  type="checkbox"
                  :value="method"
                  v-model="form.payment_methods"
                  class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />
                <span>{{ method }}</span>
              </label>
            </div>
            <div v-else class="p-3 bg-gray-50 rounded-lg border border-gray-200">
              <div v-if="vendor.settings?.payment_methods && vendor.settings.payment_methods.length > 0" class="flex flex-wrap gap-2">
                <span v-for="method in vendor.settings.payment_methods" :key="method" class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
                  {{ method }}
                </span>
              </div>
              <span v-else class="text-gray-500">N/A</span>
            </div>
            <p v-if="form.errors.payment_methods" class="mt-1 text-sm text-red-600">{{ form.errors.payment_methods }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Business Hours</label>
            <textarea
              v-if="isEditing"
              v-model="form.business_hours"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="{ 'border-red-500': form.errors.business_hours }"
              placeholder="Monday - Friday: 9:00 AM - 5:00 PM"
            ></textarea>
            <div v-else class="p-3 bg-gray-50 rounded-lg border border-gray-200 text-gray-900 whitespace-pre-wrap">
              {{ vendor.settings?.business_hours || 'N/A' }}
            </div>
            <p v-if="form.errors.business_hours" class="mt-1 text-sm text-red-600">{{ form.errors.business_hours }}</p>
          </div>
        </div>
      </div>

      <!-- Shipping & Policies Section -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-package">
            <path d="m7.5 4.27 9 5.15"></path>
            <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path>
            <path d="m3.3 7 8.7 5 8.7-5"></path>
            <path d="M12 22V12"></path>
          </svg>
          Shipping & Policies
        </h2>
        <div class="space-y-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Shipping Information</label>
            <textarea
              v-if="isEditing"
              v-model="form.shipping_info"
              rows="4"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="{ 'border-red-500': form.errors.shipping_info }"
              placeholder="Enter your shipping information..."
            ></textarea>
            <div v-else class="p-3 bg-gray-50 rounded-lg border border-gray-200 text-gray-900 whitespace-pre-wrap min-h-[80px]">
              {{ vendor.settings?.shipping_info || 'N/A' }}
            </div>
            <p v-if="form.errors.shipping_info" class="mt-1 text-sm text-red-600">{{ form.errors.shipping_info }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Return Policy</label>
            <textarea
              v-if="isEditing"
              v-model="form.return_policy"
              rows="4"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="{ 'border-red-500': form.errors.return_policy }"
              placeholder="Enter your return policy..."
            ></textarea>
            <div v-else class="p-3 bg-gray-50 rounded-lg border border-gray-200 text-gray-900 whitespace-pre-wrap min-h-[80px]">
              {{ vendor.settings?.return_policy || 'N/A' }}
            </div>
            <p v-if="form.errors.return_policy" class="mt-1 text-sm text-red-600">{{ form.errors.return_policy }}</p>
          </div>
        </div>
      </div>

      <!-- Account Status Section -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shield-check">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"></path>
            <path d="m9 12 2 2 4-4"></path>
          </svg>
          Account Status
        </h2>
        <div class="flex items-center gap-3">
          <span class="text-sm font-medium text-gray-700">Approval Status:</span>
          <span v-if="vendor.settings?.approval_status === 'approved'" class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">
            Approved
          </span>
          <span v-else-if="vendor.settings?.approval_status === 'pending'" class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-medium">
            Pending
          </span>
          <span v-else-if="vendor.settings?.approval_status === 'rejected'" class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-medium">
            Rejected
          </span>
          <span v-else class="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-sm font-medium">
            Unknown
          </span>
        </div>
      </div>

      <!-- Form Actions -->
      <div v-if="isEditing" class="bg-white rounded-lg border border-gray-200 p-6 flex items-center justify-end gap-3">
        <button
          type="button"
          @click="cancelEditing"
          class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
          :disabled="form.processing"
        >
          Cancel
        </button>
        <button
          type="submit"
          class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors flex items-center gap-2"
          :disabled="form.processing"
        >
          <svg v-if="form.processing" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ form.processing ? 'Saving...' : 'Save Changes' }}
        </button>
      </div>
    </form>
  </Layout>
</template>

<script setup>
import Layout from './Layout.vue'
import { useForm, usePage } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const props = defineProps({
  vendor: {
    type: Object,
    default: null
  },
  locations: {
    type: Array,
    default: () => []
  }
})

const page = usePage()
const isEditing = ref(false)
const logoPreview = ref(null)

const paymentMethodOptions = ['Credit Card', 'PayPal', 'Cryptocurrency', 'Bank Transfer']

const form = useForm({
  name: props.vendor?.name || '',
  website: props.vendor?.settings?.website || props.vendor?.settings?.shop_url || '',
  founded_year: props.vendor?.settings?.founded_year || null,
  location_id: props.vendor?.settings?.location_id || null,
  description: props.vendor?.settings?.description || '',
  contact_email: props.vendor?.settings?.contact_email || props.vendor?.user?.email || '',
  phone_number: props.vendor?.settings?.phone_number || '',
  shipping_info: props.vendor?.settings?.shipping_info || '',
  return_policy: props.vendor?.settings?.return_policy || '',
  business_hours: props.vendor?.settings?.business_hours || '',
  payment_methods: props.vendor?.settings?.payment_methods || [],
  logo: null,
  _token: page.props.csrf_token
})

function startEditing() {
  isEditing.value = true
  logoPreview.value = null
  // Refresh CSRF token when starting to edit
  form._token = page.props.csrf_token || ''
}

function cancelEditing() {
  isEditing.value = false
  logoPreview.value = null
  form.reset()
  form.clearErrors()
  // Refresh CSRF token after reset
  form._token = page.props.csrf_token || ''
}

function handleLogoUpload(event) {
  const file = event.target.files[0]
  if (file) {
    // Validate file size (2MB max)
    if (file.size > 2048 * 1024) {
      alert('File size must be less than 2MB')
      event.target.value = ''
      return
    }

    // Validate file type - PNG only
    if (file.type !== 'image/png') {
      alert('Please upload a PNG file only.')
      event.target.value = ''
      return
    }
    
    // Also check file extension as a backup
    const fileName = file.name.toLowerCase()
    if (!fileName.endsWith('.png')) {
      alert('Please upload a PNG file only.')
      event.target.value = ''
      return
    }

    form.logo = file

    // Create preview
    const reader = new FileReader()
    reader.onload = (e) => {
      logoPreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

function removeLogo() {
  form.logo = null
  logoPreview.value = null
  // Also clear the file input if it exists
  const fileInput = document.querySelector('input[type="file"]')
  if (fileInput) {
    fileInput.value = ''
  }
}

function submitForm() {
  // Ensure CSRF token is up to date before submission
  form._token = page.props.csrf_token || ''
  
  form.post('/vendor/profile', {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      isEditing.value = false
      logoPreview.value = null
    },
    onError: (errors) => {
      // If CSRF token error, refresh the page to get a new token
      if (errors.message && errors.message.includes('419')) {
        window.location.reload()
      }
    }
  })
}
</script>
