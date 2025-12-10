<template>
  <AdminLayout>
    <div class="mx-auto">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h1 class="text-3xl font-normal text-slate-700">{{ location ? 'Edit Location' : 'Create New Location' }}</h1>
          <p class="text-slate-500 mt-2">{{ location ? 'Update location details' : 'Add a new location' }}</p>
        </div>
        <Link href="/admin/locations" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium transition-colors">Back</Link>
      </div>
      
      <!-- Success Message -->
      <div v-if="$page.props.flash.success" class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl">
        {{ $page.props.flash.success }}
      </div>
      
      <!-- Error Message -->
      <div v-if="$page.props.flash.error" class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
        {{ $page.props.flash.error }}
      </div>
      
      <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
        <h2 class="text-xl font-semibold text-slate-800 mb-6">Location Details</h2>
        <div v-if="Object.keys(editForm.errors).length > 0" class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl">
          <p class="font-medium">Please fix the following errors:</p>
          <ul class="list-disc list-inside mt-2">
            <li v-for="(error, field) in editForm.errors" :key="field" class="text-sm">
              {{ Array.isArray(error) ? error[0] : error }}
            </li>
          </ul>
        </div>
        <form @submit.prevent="submitEdit" class="space-y-6">
          <div>
            <label class="block mb-1.5 font-semibold text-slate-800">Name *</label>
            <input 
              v-model="editForm.name" 
              type="text" 
              class="w-full border border-slate-100 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-sans text-base" 
              required 
            />
            <div v-if="editForm.errors.name" class="text-red-500 text-sm mt-1">
              {{ editForm.errors.name }}
            </div>
          </div>
          
          <div class="flex gap-4">
            <button
              type="submit"
              :disabled="editForm.processing"
              class="px-6 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-medium transition-colors disabled:opacity-50"
            >
              {{ editForm.processing ? 'Saving...' : (location ? 'Update Location' : 'Create Location') }}
            </button>
            <Link href="/admin/locations" class="px-6 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 font-medium transition-colors">
              Cancel
            </Link>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import AdminLayout from './Layout.vue'

const props = defineProps({
  location: {
    type: Object,
    default: null
  }
})

const editForm = useForm({
  name: props.location?.name || '',
})

const submitEdit = () => {
  if (props.location) {
    // Update existing location
    editForm.put(`/admin/locations/${props.location.id}`, {
      preserveScroll: true,
    })
  } else {
    // Create new location
    editForm.post('/admin/locations', {
      preserveScroll: true,
    })
  }
}
</script>



