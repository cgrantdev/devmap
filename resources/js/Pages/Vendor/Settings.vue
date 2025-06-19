<template>
  <Layout>
    <div class="bg-white p-8">
    <h1 class="text-2xl font-bold mb-6">Settings</h1>
    <form class="max-w-lg space-y-6" @submit.prevent="submit">
      <div>
        <label class="block mb-1 font-semibold">Banner Image</label>
        <input type="file" accept="image/*" @change="onBannerChange" />
        <div v-if="bannerPreview" class="mt-2">
          <img :src="bannerPreview" alt="Banner Preview" class="w-full max-h-40 object-cover rounded border" />
        </div>
      </div>
      <div>
        <label class="block mb-1 font-semibold">Logo Image</label>
        <input type="file" accept="image/*" @change="onLogoChange" />
        <div v-if="logoPreview" class="mt-2">
          <img :src="logoPreview" alt="Logo Preview" class="h-20 w-20 object-contain rounded border" />
        </div>
      </div>
      <div>
        <label class="block mb-1 font-semibold">Company Name</label>
        <input type="text" class="w-full border rounded px-3 py-2" v-model="form.company_name" />
      </div>
      <div>
        <label class="block mb-1 font-semibold">Company Detail</label>
        <textarea class="w-full border rounded px-3 py-2" v-model="form.company_detail" rows="3"></textarea>
      </div>
      <div>
        <label class="block mb-1 font-semibold">URL</label>
        <input type="text" class="w-full border rounded px-3 py-2" v-model="form.url" />
      </div>
      <div>
        <label class="block mb-1 font-semibold">Contact Email</label>
        <input type="email" class="w-full border rounded px-3 py-2" v-model="form.contact_email" />
      </div>
      <div>
        <label class="block mb-1 font-semibold">Phone Number</label>
        <input type="text" class="w-full border rounded px-3 py-2" v-model="form.phone_number" />
      </div>
      <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700" :disabled="form.processing">
        Save
      </button>
    </form>
  </div>
  </Layout>
</template>


<script setup>
import Layout from './Layout.vue'
import { usePage, router } from '@inertiajs/vue3'
import { reactive, ref } from 'vue'

const props = defineProps({
  settings: Object
})

const user = usePage().props.auth.user || {}
const csrf_token = usePage().props.csrf_token

const form = reactive({
  company_name: props.settings?.company_name || '',
  company_detail: props.settings?.company_detail || '',
  url: props.settings?.url || '',
  contact_email: props.settings?.contact_email || user.email || '',
  phone_number: props.settings?.phone_number || '',
  banner: null,
  logo: null
})

function getImageUrl(path) {
  if (!path) return null
  if (path.startsWith('http://') || path.startsWith('https://') || path.startsWith('/')) return path
  return `/storage/${path}`
}

const bannerPreview = ref(getImageUrl(props.settings?.banner))
const logoPreview = ref(getImageUrl(props.settings?.logo))

function onBannerChange(e) {
  const file = e.target.files[0]
  form.banner = file
  if (file) {
    const reader = new FileReader()
    reader.onload = e => bannerPreview.value = e.target.result
    reader.readAsDataURL(file)
  } else {
    bannerPreview.value = getImageUrl(props.settings?.banner)
  }
}

function onLogoChange(e) {
  const file = e.target.files[0]
  form.logo = file
  if (file) {
    const reader = new FileReader()
    reader.onload = e => logoPreview.value = e.target.result
    reader.readAsDataURL(file)
  } else {
    logoPreview.value = getImageUrl(props.settings?.logo)
  }
}

function submit() {
  const data = new FormData()
  Object.keys(form).forEach(key => {
    if (form[key] !== null && form[key] !== undefined) {
      data.append(key, form[key])
    }
  })
  data.append('_token', csrf_token)
  router.post('/vendor/settings', data, {
    forceFormData: true,
    onSuccess: () => {
      // Optionally show a success message
    }
  })
}
</script> 