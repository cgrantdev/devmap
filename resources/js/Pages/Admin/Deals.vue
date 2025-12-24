<template>
  <AdminLayout>
    <div class="mb-8 flex items-center justify-between">
      <div>
        <h1 class="text-3xl text-slate-900 mb-2">Deals & Coupons</h1>
        <p class="text-slate-600">Manage coupon codes and special offers</p>
      </div>
      <button
        @click="showModal = true; editingDeal = null; resetForm()"
        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Add Deal
      </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div v-for="deal in deals" :key="deal.id" class="bg-white rounded-lg border border-slate-200 p-6">
        <div class="flex items-start justify-between mb-4">
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-2">
              <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
              </svg>
              <h3 class="text-xl text-slate-900">{{ deal.code }}</h3>
            </div>
            <p class="text-slate-600 mb-2">{{ deal.description }}</p>
            <div class="flex items-center gap-2 text-sm text-slate-500">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <span>Expires: {{ deal.expiry_date }}</span>
            </div>
          </div>
          <span :class="[
            'px-3 py-1 rounded-full text-sm',
            deal.active ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-700'
          ]">
            {{ deal.active ? 'Active' : 'Inactive' }}
          </span>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-slate-200">
          <div>
            <div class="text-sm text-slate-600">Vendor</div>
            <div class="text-slate-900">{{ deal.vendor_name || 'All Vendors' }}</div>
          </div>
          <div>
            <div class="text-sm text-slate-600">Discount</div>
            <div class="text-2xl text-slate-900">{{ deal.discount }}%</div>
          </div>
        </div>

        <div class="flex gap-2 mt-4">
          <button
            @click="editDeal(deal)"
            class="flex-1 px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors flex items-center justify-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            Edit
          </button>
          <button
            @click="deleteDeal(deal.id)"
            class="px-4 py-2 border border-red-300 text-red-600 rounded-lg hover:bg-red-50 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-slate-200">
          <h2 class="text-2xl text-slate-900">
            {{ editingDeal ? 'Edit Deal' : 'Add New Deal' }}
          </h2>
        </div>

        <form @submit.prevent="saveDeal" class="p-6 space-y-6">
          <!-- Error Messages -->
          <div v-if="dealForm && Object.keys(dealForm.errors).length > 0" class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
            <p class="font-medium mb-2">Please fix the following errors:</p>
            <ul class="list-disc list-inside text-sm">
              <li v-for="(error, field) in dealForm.errors" :key="field">
                {{ Array.isArray(error) ? error[0] : error }}
              </li>
            </ul>
          </div>

          <div>
            <label class="block text-sm text-slate-700 mb-2">Coupon Code *</label>
            <input
              type="text"
              v-model="formData.code"
              required
              :class="[
                'w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500',
                dealForm?.errors?.code ? 'border-red-300' : 'border-slate-300'
              ]"
            />
            <p v-if="dealForm?.errors?.code" class="text-xs text-red-600 mt-1">{{ dealForm.errors.code }}</p>
          </div>

          <div>
            <label class="block text-sm text-slate-700 mb-2">Description *</label>
            <textarea
              v-model="formData.description"
              required
              rows="3"
              :class="[
                'w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500',
                dealForm?.errors?.description ? 'border-red-300' : 'border-slate-300'
              ]"
            ></textarea>
            <p v-if="dealForm?.errors?.description" class="text-xs text-red-600 mt-1">{{ dealForm.errors.description }}</p>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm text-slate-700 mb-2">Discount % *</label>
              <input
                type="number"
                v-model.number="formData.discount"
                required
                min="1"
                max="100"
                :class="[
                  'w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500',
                  dealForm?.errors?.discount ? 'border-red-300' : 'border-slate-300'
                ]"
              />
              <p v-if="dealForm?.errors?.discount" class="text-xs text-red-600 mt-1">{{ dealForm.errors.discount }}</p>
            </div>
            <div>
              <label class="block text-sm text-slate-700 mb-2">Expiry Date *</label>
              <input
                type="date"
                v-model="formData.expiry_date"
                required
                :class="[
                  'w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500',
                  dealForm?.errors?.expiry_date ? 'border-red-300' : 'border-slate-300'
                ]"
              />
              <p v-if="dealForm?.errors?.expiry_date" class="text-xs text-red-600 mt-1">{{ dealForm.errors.expiry_date }}</p>
            </div>
          </div>

          <div>
            <label class="block text-sm text-slate-700 mb-2">Brand/Vendor (Optional)</label>
            <select
              v-model="formData.brand_id"
              :class="[
                'w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500',
                dealForm?.errors?.brand_id ? 'border-red-300' : 'border-slate-300'
              ]"
            >
              <option :value="null">All Vendors (Site-wide)</option>
              <option v-for="brand in brands" :key="brand.id" :value="brand.id">
                {{ brand.name }}
              </option>
            </select>
            <p v-if="dealForm?.errors?.brand_id" class="text-xs text-red-600 mt-1">{{ dealForm.errors.brand_id }}</p>
            <p v-else class="text-xs text-slate-500 mt-1">Leave blank for site-wide deals</p>
          </div>

          <div>
            <label class="flex items-center gap-2">
              <input
                type="checkbox"
                v-model="formData.active"
                class="w-4 h-4 text-blue-600"
              />
              <span class="text-sm text-slate-700">Active</span>
            </label>
          </div>
        </form>

        <div class="p-6 border-t border-slate-200 flex justify-end gap-3">
          <button
            @click="showModal = false"
            class="px-6 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors"
          >
            Cancel
          </button>
          <button
            @click="saveDeal"
            class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
          >
            {{ editingDeal ? 'Save Changes' : 'Add Deal' }}
          </button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'
import { useToast as useVueToastification } from 'vue-toastification'

// Only use toast for manual error messages
// Success messages are handled automatically by Layout component via flash messages
const toast = useVueToastification()

const props = defineProps({
  deals: {
    type: Array,
    default: () => []
  },
  brands: {
    type: Array,
    default: () => []
  }
})

const showModal = ref(false)
const editingDeal = ref(null)
const dealForm = ref(null)

const formData = reactive({
  code: '',
  description: '',
  discount: 0,
  expiry_date: '',
  active: true,
  brand_id: null
})

function editDeal(deal) {
  editingDeal.value = deal
  formData.code = deal.code
  formData.description = deal.description
  formData.discount = deal.discount
  formData.expiry_date = deal.expiry_date
  formData.active = deal.active
  formData.brand_id = deal.brand_id || null
  showModal.value = true
}

function saveDeal() {
  const page = usePage()
  const url = editingDeal.value
    ? `/admin/deals/${editingDeal.value.id}`
    : '/admin/deals'
  
  const formDataToSubmit = {
    code: formData.code,
    description: formData.description,
    discount: formData.discount,
    expiry_date: formData.expiry_date,
    active: formData.active,
    brand_id: formData.brand_id,
    _token: page.props.csrf_token,
  }

  // Add _method for PUT requests (Laravel method spoofing)
  if (editingDeal.value) {
    formDataToSubmit._method = 'put'
  }

  dealForm.value = useForm(formDataToSubmit)

  // Update CSRF token before submission
  dealForm.value._token = page.props.csrf_token

  dealForm.value.post(url, {
    preserveScroll: true,
    onSuccess: () => {
      showModal.value = false
      resetForm()
      // Toast will be shown automatically from Laravel flash message
    },
    onError: (errors) => {
      console.error('Deal save errors:', errors)
      // Show toast for first error
      const firstError = Object.values(errors)[0]
      if (firstError) {
        toast.error(Array.isArray(firstError) ? firstError[0] : firstError, {
          timeout: 4000,
        })
      }
    }
  })
}

function deleteDeal(dealId) {
  if (confirm('Are you sure you want to delete this deal?')) {
    const deleteForm = useForm({
      _token: usePage().props.csrf_token
    })
    
    deleteForm.delete(`/admin/deals/${dealId}`, {
      preserveScroll: true,
      onSuccess: () => {
        // Success toast will be shown automatically from flash message
      },
      onError: (errors) => {
        toast.error('Failed to delete deal. Please try again.', {
          timeout: 4000,
        })
        console.error(errors)
      }
    })
  }
}

function resetForm() {
  formData.code = ''
  formData.description = ''
  formData.discount = 0
  formData.expiry_date = ''
  formData.active = true
  formData.brand_id = null
  editingDeal.value = null
  dealForm.value = null
}
</script>

