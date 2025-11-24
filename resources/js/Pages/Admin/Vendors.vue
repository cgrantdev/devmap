<template>
  <AdminLayout>
    <div class="mb-8 flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold">Vendor Management</h1>
        <p class="text-gray-600 mt-2">Manage all registered vendors</p>
      </div>
      <button @click="openCreateModal" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 font-semibold shadow">
        + New Vendor
      </button>
    </div>
    
    <!-- Success Message -->
    <div v-if="$page.props.flash.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
      {{ $page.props.flash.success }}
    </div>
    
    <!-- Error Message -->
    <div v-if="$page.props.flash.error" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
      {{ $page.props.flash.error }}
    </div>
    
    <!-- Create Vendor Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center" style="background-color: rgba(106, 114, 130, 0.4);">
      <div class="bg-white rounded-lg shadow-lg w-full max-w-lg p-8 relative max-h-[90vh] overflow-y-auto">
        <button @click="showCreateModal = false" class="absolute top-2 right-2 text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
        <h2 class="text-2xl font-bold mb-4">Register New Vendor</h2>
        <form @submit.prevent="submitCreateVendor">
          <!-- Error Message -->
          <div v-if="Object.keys(createForm.errors).length > 0" class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            <p class="font-bold">Please fix the following errors:</p>
            <ul class="list-disc list-inside">
              <li v-for="(error, field) in createForm.errors" :key="field" class="text-sm">
                {{ Array.isArray(error) ? error[0] : error }}
              </li>
            </ul>
          </div>
          <input type="hidden" :value="createForm._token" name="_token" />
          <div class="mb-4 flex gap-4">
            <div class="w-1/2">
              <label class="block mb-1 font-medium">Name</label>
              <input v-model="createForm.name" type="text" class="w-full border rounded px-3 py-2" required />
            </div>
            <div class="w-1/2">
              <label class="block mb-1 font-medium">Email Address</label>
              <input v-model="createForm.email" type="email" class="w-full border rounded px-3 py-2" required />
            </div>
          </div>
          <div class="mb-4">
            <label class="block mb-1 font-medium">Password</label>
            <input v-model="createForm.password" type="password" class="w-full border rounded px-3 py-2" required />
          </div>
          <div class="mb-4">
            <label class="block mb-1 font-medium">Company Name</label>
            <input v-model="createForm.company_name" type="text" class="w-full border rounded px-3 py-2" />
          </div>
          <div class="mb-4">
            <label class="block mb-1 font-medium">Company Detail</label>
            <textarea v-model="createForm.company_detail" class="w-full border rounded px-3 py-2" rows="2"></textarea>
          </div>
          <div class="mb-4 flex gap-4">
            <div class="w-1/2">
              <label class="block mb-1 font-medium">Contact Email</label>
              <input v-model="createForm.contact_email" type="email" class="w-full border rounded px-3 py-2" />
            </div>
            <div class="w-1/2">
              <label class="block mb-1 font-medium">Phone Number</label>
              <input v-model="createForm.phone_number" type="text" class="w-full border rounded px-3 py-2" />
            </div>
          </div>
          <div class="mb-4">
            <label class="block mb-1 font-medium">URL</label>
            <input v-model="createForm.url" type="url" class="w-full border rounded px-3 py-2" />
          </div>
          <div class="mb-4 flex gap-4 items-end">
            <div class="w-1/2">
              <label class="block mb-1 font-medium">Banner</label>
              <input @change="e => handleFileChange(e, 'banner')" type="file" accept="image/*" class="w-full" />
              <div v-if="bannerPreview" class="mt-2">
                <img :src="bannerPreview" alt="Banner Preview" class="h-16 rounded object-cover w-full" />
              </div>
            </div>
            <div class="w-1/2">
              <label class="block mb-1 font-medium">Logo</label>
              <input @change="e => handleFileChange(e, 'logo')" type="file" accept="image/*" class="w-full" />
              <div v-if="logoPreview" class="mt-2">
                <img :src="logoPreview" alt="Logo Preview" class="h-16 w-16 rounded-full object-cover mx-auto" />
              </div>
            </div>
          </div>
          <div class="flex justify-end">
            <button type="button" @click="showCreateModal = false" class="mr-2 px-4 py-2 rounded bg-gray-200 hover:bg-gray-300">Cancel</button>
            <button type="submit" :disabled="createForm.processing" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700 font-semibold">
              {{ createForm.processing ? 'Registering...' : 'Register Vendor' }}
            </button>
          </div>
        </form>
      </div>
    </div>
    
    <div class="bg-white rounded-lg shadow">
      <div class="p-6">
        <h2 class="text-xl font-semibold">All Vendors</h2>
      </div>

      <div class="flex items-center gap-4 mb-4 px-6">
        <span>search value: </span>
        <input type="text" v-model="searchValue" class="border rounded px-3 py-2">
      </div>

      <div class="overflow-x-auto">
        <EasyDataTable
          :headers="headers"
          :items="vendors"
          :rows-per-page="10"
          :search-field="searchField"
          :search-value="searchValue"
          theme-color="#6366f1"
        >
          <template #item-name="{ name, settings, created_at }">
            <div class="flex items-center px-6 py-4 whitespace-nowrap">
                <div class="flex-shrink-0 h-10 w-10">
                  <img v-if="settings?.logo" :src="settings.logo" alt="Logo" class="h-10 w-10 rounded-full object-cover" loading="lazy">
                  <div v-else class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                    <span class="text-gray-600 font-semibold">{{ name.charAt(0).toUpperCase() }}</span>
                  </div>
                </div>
                <div class="ml-4">
                  <div class="text-sm font-medium text-gray-900">{{ name }}</div>
                  <div class="text-sm text-gray-500">Joined {{ formatDate(created_at) }}</div>
                </div>
              </div>
          </template>
          <template #item-company_name="{ settings }">
            <div class="text-sm text-gray-900 px-6 py-4 whitespace-nowrap">{{ settings?.company_name || 'N/A' }}</div>
          </template>
          <template #item-email="{ email }">
            <div class="text-sm text-gray-900 px-6 py-4 whitespace-nowrap">{{ email }}</div>
          </template>
          <template #item-status="item">
            <span v-if="item.settings?.status === 1" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
              Active
            </span>
            <span v-else class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
              Inactive
            </span>
          </template>
          <template #item-actions="item">
            <a :href="`/shop/${item.name.toLowerCase().replace(/\s+/g, '-')}`" target="_blank" rel="noopener noreferrer" class="text-indigo-600 hover:text-indigo-900 mr-4">
              {{ item.settings?.status === 1 ? 'View Public Page' : 'Preview Public Page (Inactive)' }}
            </a>
            <Link :href="`/admin/vendors/${item.id}/edit`" class="text-yellow-600 hover:text-yellow-900 mr-4 font-semibold">Edit</Link>
            <button @click="toggleStatus(item)" :disabled="form.processing"
              :class="[
                'px-4 py-2 rounded font-medium transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed',
                item.settings?.status === 1
                  ? 'bg-red-600 text-white hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2'
                  : 'bg-green-600 text-white hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2'
              ]">
              {{ form.processing ? 'Updating...' : (item.settings?.status === 1 ? 'Deactivate' : 'Activate') }}
            </button>
            <button @click="deleteVendor(item)" :disabled="form.processing" class="ml-2 px-4 py-2 rounded bg-red-500 text-white hover:bg-red-700 font-semibold transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
              Delete
            </button>
          </template>
        </EasyDataTable>        
      </div>
      
      <div v-if="vendors.length === 0" class="p-6 text-center text-gray-500">
        No vendors found.
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import { ref, computed } from 'vue'
import EasyDataTable from 'vue3-easy-data-table'
import 'vue3-easy-data-table/dist/style.css';

const props = defineProps({
  vendors: {
    type: Array,
    default: () => []
  }
})

const headers = [
  { text: 'Vendor', value: 'name' },
  { text: 'Company', value: 'company_name' },
  { text: 'Email', value: 'email' },
  { text: 'Status', value: 'status' },
  { text: 'Actions', value: 'actions' }
]

const searchField = ["name", "email", "settings.company_name"];
const searchValue = ref("");

const showCreateModal = ref(false)

const bannerPreview = ref(null)
const logoPreview = ref(null)

const form = useForm({
  _token: usePage().props.csrf_token
})

const createForm = useForm({
  name: '',
  email: '',
  password: '',
  company_name: '',
  company_detail: '',
  url: '',
  contact_email: '',
  phone_number: '',
  banner: null,
  logo: null,
  _token: usePage().props.csrf_token
})

function formatDate(dateString) {
  return new Date(dateString).toLocaleDateString()
}

function toggleStatus(vendor) {
  form.post(`/admin/vendors/${vendor.id}/toggle-status`, {
    onSuccess: () => {
      // The page will be refreshed with updated data from the server
      console.log('Status updated successfully')
    },
    onError: (errors) => {
      console.error('Error updating status:', errors)
    }
  })
}

function handleFileChange(event, field) {
  const file = event.target.files[0]
  if (file) {
    createForm[field] = file
    const reader = new FileReader()
    reader.onload = e => {
      if (field === 'banner') bannerPreview.value = e.target.result
      if (field === 'logo') logoPreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

function submitCreateVendor() {
  createForm.post('/admin/vendors', {
    forceFormData: true,
    onSuccess: () => {
      showCreateModal.value = false
      createForm.reset()
      bannerPreview.value = null
      logoPreview.value = null
    }
  })
}

function openCreateModal() {
  showCreateModal.value = true
  createForm.reset()
  createForm.clearErrors()
  bannerPreview.value = null
  logoPreview.value = null
}

function deleteVendor(vendor) {
  if (confirm(`Are you sure you want to delete vendor '${vendor.name}'? This cannot be undone.`)) {
    form.delete(`/admin/vendors/${vendor.id}`, {
      onSuccess: () => {
        // Success message will be shown via flash
      },
      onError: (errors) => {
        alert('Failed to delete vendor.');
        console.error(errors);
      }
    })
  }
}
</script> 