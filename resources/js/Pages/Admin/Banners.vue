<template>
  <AdminLayout>
    <div class="mb-8 flex items-center justify-between">
      <div>
        <h1 class="text-3xl text-slate-900 mb-2">Banner Ad Management</h1>
        <p class="text-slate-600">Manage homepage carousel banners</p>
      </div>
      <button
        @click="showModal = true; editingBanner = null"
        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Add Banner
      </button>
    </div>

    <div class="space-y-4">
      <div v-for="banner in banners" :key="banner.id" class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="md:col-span-1">
            <img 
              :src="banner.image_url || '/images/banners/default.jpg'"
              :alt="banner.vendor_name || 'Banner'"
              class="w-full h-48 object-cover"
            />
          </div>
          <div class="md:col-span-2 p-6">
            <div class="flex items-start justify-between mb-4">
              <div>
                <h3 class="text-xl text-slate-900 mb-2">{{ banner.vendor_name || 'Homepage Banner' }}</h3>
                <div class="flex items-center gap-4 text-sm text-slate-600">
                  <span>Position: {{ banner.position }}</span>
                  <span>Link: {{ banner.link || 'N/A' }}</span>
                </div>
              </div>
              <button
                @click="toggleActive(banner.id)"
                :class="[
                  'px-4 py-2 rounded-lg flex items-center gap-2',
                  banner.active ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-700'
                ]"
              >
                <svg v-if="banner.active" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                </svg>
                {{ banner.active ? 'Active' : 'Inactive' }}
              </button>
            </div>

            <div class="flex gap-2">
              <button
                @click="editBanner(banner)"
                class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors flex items-center gap-2"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Edit
              </button>
              <button
                @click="deleteBanner(banner.id)"
                class="px-4 py-2 border border-red-300 text-red-600 rounded-lg hover:bg-red-50 transition-colors flex items-center gap-2"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Delete
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-slate-200">
          <h2 class="text-2xl text-slate-900">
            {{ editingBanner ? 'Edit Banner' : 'Add New Banner' }}
          </h2>
        </div>

        <form @submit.prevent="saveBanner" class="p-6 space-y-6">
          <div>
            <label class="block text-sm text-slate-700 mb-2">Image URL *</label>
            <input
              type="url"
              v-model="formData.image_url"
              required
              class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm text-slate-700 mb-2">Position</label>
              <input
                type="number"
                v-model.number="formData.position"
                min="1"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label class="block text-sm text-slate-700 mb-2">Link URL</label>
              <input
                type="url"
                v-model="formData.link"
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>

          <div>
            <label class="block text-sm text-slate-700 mb-2">Vendor Name (Optional)</label>
            <input
              type="text"
              v-model="formData.vendor_name"
              class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
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
            @click="saveBanner"
            class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
          >
            {{ editingBanner ? 'Save Changes' : 'Add Banner' }}
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
  banners: {
    type: Array,
    default: () => []
  }
})

const showModal = ref(false)
const editingBanner = ref(null)

const formData = reactive({
  image_url: '',
  position: 1,
  link: '',
  vendor_name: '',
  active: true
})

function editBanner(banner) {
  editingBanner.value = banner
  formData.image_url = banner.image_url || ''
  formData.position = banner.position || 1
  formData.link = banner.link || ''
  formData.vendor_name = banner.vendor_name || ''
  formData.active = banner.active !== false
  showModal.value = true
}

function saveBanner() {
  const url = editingBanner.value
    ? `/admin/banners/${editingBanner.value.id}`
    : '/admin/banners'
  
  const method = editingBanner.value ? 'put' : 'post'

  router[method](url, formData, {
    preserveScroll: true,
    onSuccess: () => {
      showModal.value = false
      resetForm()
    }
  })
}

function toggleActive(bannerId) {
  router.post(`/admin/banners/${bannerId}/toggle`, {}, {
    preserveScroll: true
  })
}

function deleteBanner(bannerId) {
  if (confirm('Delete this banner?')) {
    router.delete(`/admin/banners/${bannerId}`, {
      preserveScroll: true
    })
  }
}

function resetForm() {
  formData.image_url = ''
  formData.position = 1
  formData.link = ''
  formData.vendor_name = ''
  formData.active = true
  editingBanner.value = null
}
</script>

