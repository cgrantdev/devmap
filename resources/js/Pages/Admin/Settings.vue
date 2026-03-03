<template>
  <AdminLayout>
    <div class="mb-8">
      <h1 class="text-3xl text-gray-900 mb-2">Site Settings</h1>
      <p class="text-gray-600">Configure marketplace settings and preferences</p>
    </div>
    
    <!-- Flash messages are now handled by toast notifications -->
    
    <!-- Validation Errors Summary -->
    <div v-if="Object.keys($page.props.errors || {}).length > 0" class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
      <p class="font-semibold mb-2">Please fix the following errors:</p>
      <ul class="list-disc list-inside space-y-1">
        <li v-for="(error, field) in $page.props.errors" :key="field" class="text-sm">
          <strong>{{ field }}:</strong> {{ Array.isArray(error) ? error[0] : error }}
        </li>
      </ul>
    </div>

    <div class="space-y-6">
      <!-- General Settings -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center gap-3 mb-6">
          <svg class="lucide lucide-globe w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <circle stroke-linecap="round" stroke-linejoin="round" stroke-width="2" cx="12" cy="12" r="10"></circle>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 12h20" />
          </svg>
          <h2 class="text-xl text-gray-900">General Settings</h2>
        </div>
        
        <div class="space-y-4">
          <div>
            <label class="block text-sm text-gray-700 mb-2">Site Name</label>
            <input
              v-model="settingsForm.site_name"
              type="text"
              value="Peptidemap"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
          
          <div>
            <label class="block text-sm text-gray-700 mb-2">Site Description</label>
            <textarea
              v-model="settingsForm.site_description"
              rows="3"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            >Compare peptide brands, prices, and reviews</textarea>
          </div>

          <div>
            <label class="block text-sm text-gray-700 mb-2">Contact Email</label>
            <input
              v-model="settingsForm.contact_email"
              type="email"
              value="contact@peptidemaps.com"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
        </div>
      </div>

      <!-- Email Notifications -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center gap-3 mb-6">
          <svg class="lucide lucide-mail w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7" />
            <rect stroke-linecap="round" stroke-linejoin="round" stroke-width="2" width="20" height="16" x="2" y="4" rx="2"></rect>
          </svg>
          <h2 class="text-xl text-gray-900">Email Notifications</h2>
        </div>
        
        <div class="space-y-3">
          <label class="flex items-center gap-3">
            <input 
              type="checkbox" 
              v-model="settingsForm.email_new_vendor_registrations"
              class="w-4 h-4 text-blue-600"
            />
            <span class="text-sm text-gray-700">New vendor registrations</span>
          </label>
          
          <label class="flex items-center gap-3">
            <input 
              type="checkbox" 
              v-model="settingsForm.email_new_product_submissions"
              class="w-4 h-4 text-blue-600"
            />
            <span class="text-sm text-gray-700">New product submissions</span>
          </label>
          
          <label class="flex items-center gap-3">
            <input 
              type="checkbox" 
              v-model="settingsForm.email_review_moderation_alerts"
              class="w-4 h-4 text-blue-600"
            />
            <span class="text-sm text-gray-700">Review moderation alerts</span>
          </label>
        </div>
      </div>

      <!-- Security -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center gap-3 mb-6">
          <svg class="lucide lucide-shield w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
          </svg>
          <h2 class="text-xl text-gray-900">Security</h2>
        </div>
        
        <div class="space-y-3">
          <label class="flex items-center gap-3">
            <input 
              type="checkbox" 
              v-model="settingsForm.require_email_verification"
              class="w-4 h-4 text-blue-600"
            />
            <span class="text-sm text-gray-700">Require email verification for new users</span>
          </label>
          
          <label class="flex items-center gap-3">
            <input 
              type="checkbox" 
              v-model="settingsForm.enable_2fa_for_admins"
              class="w-4 h-4 text-blue-600"
            />
            <span class="text-sm text-gray-700">Enable two-factor authentication for admins</span>
          </label>
        </div>
      </div>

      <!-- Platform Settings -->
      <!-- <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-xl text-gray-900 mb-6">Platform Settings</h2>
        
        <div class="space-y-4">
          <label class="flex items-center justify-between">
            <div>
              <div class="text-gray-900">Maintenance Mode</div>
              <div class="text-sm text-gray-600">Disable site access for maintenance</div>
            </div>
            <input
              type="checkbox"
              v-model="settingsForm.maintenance_mode"
              class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
            />
          </label>

          <label class="flex items-center justify-between">
            <div>
              <div class="text-gray-900">Allow New Registrations</div>
              <div class="text-sm text-gray-600">Enable user sign-ups</div>
            </div>
            <input
              type="checkbox"
              v-model="settingsForm.allow_registration"
              class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
            />
          </label>

          <label class="flex items-center justify-between">
            <div>
              <div class="text-gray-900">Require Email Verification</div>
              <div class="text-sm text-gray-600">Users must verify email to access site</div>
            </div>
            <input
              type="checkbox"
              v-model="settingsForm.require_email_verification_platform"
              class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
            />
          </label>
        </div>
      </div> -->

      <!-- Vendor Settings -->
      <!-- <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h2 class="text-xl text-gray-900 mb-6">Vendor Settings</h2>
        
        <div class="space-y-4">
          <div>
            <label class="block text-sm text-gray-900 mb-2">Banner Ad Price (Monthly)</label>
            <input
              v-model.number="settingsForm.banner_ad_price"
              type="number"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div>
            <label class="block text-sm text-gray-900 mb-2">Top Vendor Spot Price (Monthly)</label>
            <input
              v-model.number="settingsForm.top_vendor_spot_price"
              type="number"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
        </div>
      </div> -->

      <!-- SEO Settings -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between gap-4 mb-6">
          <div class="flex items-center gap-3">
            <svg class="lucide lucide-search w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <circle cx="11" cy="11" r="8" stroke-width="2"></circle>
              <path d="m21 21-4.35-4.35" stroke-width="2"></path>
            </svg>
            <div>
              <h2 class="text-xl text-gray-900">SEO Pages</h2>
              <p class="text-sm text-gray-600">Create SEO entries by key (e.g. <span class="font-mono">products</span>, <span class="font-mono">brands</span>, <span class="font-mono">home</span>).</p>
            </div>
          </div>
          <button
            type="button"
            class="text-sm text-gray-600 hover:text-gray-900"
            @click="resetSeoForm"
          >
            Clear form
          </button>
        </div>

        <!-- Upsert form -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm text-gray-700 mb-2">Key</label>
            <input
              v-model="seoForm.key"
              type="text"
              placeholder="products"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
            />
            <p class="text-xs text-gray-500 mt-1">Allowed: lowercase letters, numbers, <span class="font-mono">-</span>, <span class="font-mono">_</span></p>
          </div>
          <div>
            <label class="block text-sm text-gray-700 mb-2">OG Image URL</label>
            <input
              v-model="seoForm.og_image"
              type="text"
              placeholder="https://example.com/og.jpg"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
            />
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm text-gray-700 mb-2">Title</label>
            <input
              v-model="seoForm.title"
              type="text"
              placeholder="Research Peptides - Browse All Products"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
            />
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm text-gray-700 mb-2">Description</label>
            <textarea
              v-model="seoForm.description"
              rows="3"
              placeholder="Browse our comprehensive collection..."
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
            ></textarea>
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm text-gray-700 mb-2">OG Title (optional)</label>
            <input
              v-model="seoForm.og_title"
              type="text"
              placeholder="(defaults to Title)"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
            />
          </div>

          <div class="md:col-span-2">
            <label class="block text-sm text-gray-700 mb-2">OG Description (optional)</label>
            <textarea
              v-model="seoForm.og_description"
              rows="3"
              placeholder="(defaults to Description)"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
            ></textarea>
          </div>
        </div>

        <div class="mt-5 flex items-center justify-end gap-3">
          <button
            type="button"
            class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50"
            @click="resetSeoForm"
            :disabled="seoForm.processing"
          >
            Cancel
          </button>
          <button
            type="button"
            class="px-5 py-2 rounded-lg bg-orange-600 hover:bg-orange-700 text-white transition-colors"
            @click="saveSeoPage"
            :disabled="seoForm.processing"
          >
            {{ seoForm.processing ? 'Saving...' : (seoForm.id ? 'Update SEO' : 'Create SEO') }}
          </button>
        </div>

        <!-- Existing pages list -->
        <div class="mt-8">
          <h3 class="text-sm font-semibold text-gray-900 mb-3">Existing SEO entries</h3>
          <div v-if="seoPages.length === 0" class="text-sm text-gray-500">
            No SEO entries yet.
          </div>
          <div v-else class="overflow-x-auto border border-gray-200 rounded-lg">
            <table class="min-w-full text-sm">
              <thead class="bg-gray-50 text-gray-700">
                <tr>
                  <th class="text-left px-4 py-3">Key</th>
                  <th class="text-left px-4 py-3">Title</th>
                  <th class="text-left px-4 py-3">Updated</th>
                  <th class="text-right px-4 py-3">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr v-for="p in seoPages" :key="p.id">
                  <td class="px-4 py-3 font-mono text-xs text-gray-800">{{ p.key }}</td>
                  <td class="px-4 py-3 text-gray-800 truncate max-w-[28rem]">{{ p.title || '-' }}</td>
                  <td class="px-4 py-3 text-gray-600">{{ (p.updated_at || p.created_at || '').toString().slice(0, 19).replace('T',' ') }}</td>
                  <td class="px-4 py-3">
                    <div class="flex justify-end gap-2">
                      <button
                        type="button"
                        class="px-3 py-1.5 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50"
                        @click="editSeoPage(p)"
                      >
                        Edit
                      </button>
                      <button
                        type="button"
                        class="px-3 py-1.5 rounded-lg border border-red-200 text-red-700 hover:bg-red-50"
                        @click="deleteSeoPage(p)"
                      >
                        Delete
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Save Settings Button -->
      <div class="flex justify-end">
        <button 
          @click="saveSettings"
          :disabled="settingsForm.processing"
          class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg transition-colors"
        >          
          {{ settingsForm.processing ? 'Saving...' : 'Save Settings' }}
        </button>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { useForm, usePage, router } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'

const props = defineProps({
  settings: {
    type: Object,
    default: () => ({
      site_name: 'Peptidemap',
      site_description: 'Compare peptide brands, prices, and reviews',
      contact_email: 'contact@peptidemaps.com',
      email_new_vendor_registrations: true,
      email_new_product_submissions: true,
      email_review_moderation_alerts: true,
      require_email_verification: true,
      enable_2fa_for_admins: false,
      maintenance_mode: false,
      allow_registration: true,
      require_email_verification_platform: true,
      banner_ad_price: 500,
      top_vendor_spot_price: 250,
    })
  }
  ,
  seoPages: {
    type: Array,
    default: () => []
  }
})

// Settings form
const settingsForm = useForm({
  site_name: props.settings?.site_name || 'Peptidemap',
  site_description: props.settings?.site_description || 'Compare peptide brands, prices, and reviews',
  contact_email: props.settings?.contact_email || 'contact@peptidemaps.com',
  email_new_vendor_registrations: props.settings?.email_new_vendor_registrations ?? true,
  email_new_product_submissions: props.settings?.email_new_product_submissions ?? true,
  email_review_moderation_alerts: props.settings?.email_review_moderation_alerts ?? true,
  require_email_verification: props.settings?.require_email_verification ?? true,
  enable_2fa_for_admins: props.settings?.enable_2fa_for_admins ?? false,
  maintenance_mode: props.settings?.maintenance_mode ?? false,
  allow_registration: props.settings?.allow_registration ?? true,
  require_email_verification_platform: props.settings?.require_email_verification_platform ?? true,
  banner_ad_price: props.settings?.banner_ad_price || 500,
  top_vendor_spot_price: props.settings?.top_vendor_spot_price || 250,
  _token: usePage().props.csrf_token
})

function saveSettings() {
  settingsForm.post('/admin/settings/general', {
    preserveScroll: true,
    onSuccess: () => {
      // Success toast will be shown automatically from flash message
    },
    onError: () => {
      // Error toast will be shown automatically from flash message
    }
  })
}

// SEO Pages form (create/update)
const seoForm = useForm({
  id: null,
  key: '',
  title: '',
  description: '',
  og_title: '',
  og_description: '',
  og_image: '',
  _token: usePage().props.csrf_token
})

function resetSeoForm() {
  seoForm.reset()
  seoForm.id = null
}

function editSeoPage(p) {
  seoForm.id = p.id
  seoForm.key = p.key || ''
  seoForm.title = p.title || ''
  seoForm.description = p.description || ''
  seoForm.og_title = p.og_title || ''
  seoForm.og_description = p.og_description || ''
  seoForm.og_image = p.og_image || ''
}

function saveSeoPage() {
  seoForm.post('/admin/settings/seo-pages', {
    preserveScroll: true,
    onSuccess: () => {
      resetSeoForm()
    }
  })
}

function deleteSeoPage(p) {
  if (!confirm(`Delete SEO entry "${p.key}"?`)) return
  const deleteForm = useForm({
    _token: usePage().props.csrf_token
  })

  deleteForm.delete(`/admin/settings/seo-pages/${p.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      resetSeoForm()
    }
  })
}
</script>
