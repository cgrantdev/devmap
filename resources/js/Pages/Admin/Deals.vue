<template>
  <AdminLayout>
    <div class="mb-8 flex items-center justify-between">
      <div>
        <h1 class="text-3xl text-slate-900 mb-2">Deals & Coupons</h1>
        <p class="text-slate-600">Manage coupon codes and special offers</p>
      </div>
      <button
        @click="showModal = true; editingDeal = null"
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
          <div>
            <label class="block text-sm text-slate-700 mb-2">Coupon Code *</label>
            <input
              type="text"
              v-model="formData.code"
              required
              class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div>
            <label class="block text-sm text-slate-700 mb-2">Description *</label>
            <textarea
              v-model="formData.description"
              required
              rows="3"
              class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            ></textarea>
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
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="block text-sm text-slate-700 mb-2">Expiry Date *</label>
              <input
                type="date"
                v-model="formData.expiry_date"
                required
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
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
import { router } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'

const props = defineProps({
  deals: {
    type: Array,
    default: () => []
  },
  vendors: {
    type: Array,
    default: () => []
  }
})

const showModal = ref(false)
const editingDeal = ref(null)

const formData = reactive({
  code: '',
  description: '',
  discount: 0,
  expiry_date: '',
  active: true,
  vendor_id: null
})

function editDeal(deal) {
  editingDeal.value = deal
  formData.code = deal.code
  formData.description = deal.description
  formData.discount = deal.discount
  formData.expiry_date = deal.expiry_date
  formData.active = deal.active
  formData.vendor_id = deal.vendor_id
  showModal.value = true
}

function saveDeal() {
  const url = editingDeal.value
    ? `/admin/deals/${editingDeal.value.id}`
    : '/admin/deals'
  
  const method = editingDeal.value ? 'put' : 'post'

  router[method](url, formData, {
    preserveScroll: true,
    onSuccess: () => {
      showModal.value = false
      resetForm()
    }
  })
}

function deleteDeal(dealId) {
  if (confirm('Delete this deal?')) {
    router.delete(`/admin/deals/${dealId}`, {
      preserveScroll: true
    })
  }
}

function resetForm() {
  formData.code = ''
  formData.description = ''
  formData.discount = 0
  formData.expiry_date = ''
  formData.active = true
  formData.vendor_id = null
  editingDeal.value = null
}
</script>

